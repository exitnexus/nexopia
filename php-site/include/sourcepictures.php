<?php

class sourcepictures
{
	public $db;

	function __construct($gallerydb)
	{
		$this->db = $gallerydb;
	}

	function getSourcePictures($sourceids)
	{
		$template = new sourcepicture($this->db);
		return $template->getObjects($sourceids);
	}

	function getSourcePicture($sourceid)
	{
		$template = new sourcepicture($this->db);
		return $template->getObject($sourceid);
	}
}

class sourcepicture extends databaseobject
{
	private $db;

	function __construct($db)
	{
		global $galleries;

		parent::__construct($db, 'sourcepics', DB_AREA_SOURCEPICS, 'sourcepics-',
			array(
				'userid' => '%',
				'id' => '!',
				'uploadtime' => '#',
			)
		);
		$this->db = $galleries->db;
	}

	function invalidateCache($type)
	{
		if ($type == 'delete')
		{
			$pic = $this->getPicPath(true);
			$thumb = $this->getPicPath(false);

			if (file_exists($pic))
				@unlink($pic);
			if (file_exists($thumb))
				@unlink($thumb);
		}
	}

	private function limitSize($width, $height, $maxwidth, $maxheight)
	{
		$aspectRat = (float)($width / $height);

		if($maxwidth>0 && $maxheight >0 && $width > $maxwidth && $height > $maxheight){
			$ratio = (float)($maxwidth / $maxheight);
			if($ratio < $aspectRat){
				$picX = $maxwidth;
				$picY = $maxwidth / $aspectRat;
			}else{
				$picY = $maxheight;
				$picX = $maxheight * $aspectRat;
			}
		}elseif($maxwidth >0 && $width>$maxwidth){
			$picX = $maxwidth;
			$picY = $maxwidth / $aspectRat;
		}elseif($maxheight > 0 && $height>$maxheight){
			$picY = $maxheight;
			$picX = $maxheight * $aspectRat;
		}else{
			$picX = $width;
			$picY = $height;
		}

		$picX = ceil($picX);
		$picY = ceil($picY);

		return array($picX, $picY);
	}

	private function resizeImage($sourceImg, $width, $height, $picX, $picY)
	{
		global $config;

		$destImg = ImageCreate($picX, $picY);
		if(!$config['gd2']){
			$destImg = ImageCreate($picX, $picY );
			ImageCopyResized($destImg, $sourceImg, 0,0,0,0, $picX, $picY, $width, $height);
		}else{
			$destImg = ImageCreateTrueColor($picX, $picY );
			ImageCopyResampled($destImg, $sourceImg, 0,0,0,0, $picX, $picY, $width, $height);
		}
		return $destImg;
	}

	private function addBranding($destImg, $picX, $picY)
	{
		global $config;

		$padding = 3;
		$border = 1;
		$offset = 10;
		$textwidth = (strlen($config['picText'])*7)-1; // 6 pixels per character, 1 pixel space, 3 pixels padding
		$textheight = 14;

		$white = ImageColorClosest($destImg, 255, 255, 255);
		$black = ImageColorClosest($destImg, 0, 0, 0);

		ImageRectangle($destImg,$picX-$textwidth-$offset-$border*2-$padding*2,$picY-$textheight-$offset-$border,$picX-$offset,$picY-$offset,$black);
		ImageFilledRectangle($destImg,$picX-$textwidth-$offset-$border-$padding*2,$picY-$textheight-$offset,$picX-$border-$offset,$picY-$border-$offset,$white);
		ImageString ($destImg, 3, $picX-$textwidth-$offset-$padding, $picY-$textheight-$offset, $config['picText'], $black);
	}

	private function setExifData($filename)
	{
		global $config, $userData;

		//put in exif info
		include_once("include/JPEG.php");
		$jpeg = new JPEG($filename);

		$jpeg->setExifField("ImageDescription", "$config[picText]:$userData[userid]");
		$jpeg->save();
	}

	private function loadImageFromFile($sourcePath, &$size)
	{
		global $msgs;

		$size = @GetImageSize($sourcePath);
		if ( !$size ){
			$msgs->addMsg("Could not open picture");
			return false;
		}

		if($size[2] == 2)
			$sourceImg = ImageCreateFromJPEG($sourcePath);
	//	elseif($size[2] == 3)
	//	    $sourceImg = @ImageCreateFromPNG($uploadFile);
		else{
			$msgs->addMsg("Wrong or unknown image type. Only JPG is supported");
			return false;
		}

		if(!$sourceImg){
			$msgs->addMsg("Bad or corrupt image.");
			return false;
		}
		return $sourceImg;
	}

	private function newImage($sourceImg, $origWidth, $origHeight, $newPath, $maxWidth, $maxHeight, $addBrand)
	{
		list($newWidth, $newHeight) = $this->limitSize($origWidth, $origHeight, $maxWidth, $maxHeight);
		$destImg = $this->resizeImage($sourceImg, $origWidth, $origHeight, $newWidth, $newHeight);
		if ($addBrand)
			$this->addBranding($destImg, $newWidth, $newHeight);

		if (imagejpeg($destImg, $newPath, 80))
		{
			if ($addBrand)
				$this->setExifData($newPath);		
		}
		else
			trigger_error('imagejpeg() returned false when attempting to create "' . $newPath . '"', E_USER_WARNING);
	}		

	// pass in array of criteria:
	// $imgInfo = array( array($newPath, $maxWidth, $maxHeight, $addBrand), array(...) );
	public function duplicateImage($imgInfos)
	{
		global $config;

		$cnt = 0;
		$basePath = $this->getPicPath();

		$size = array();
		$sourceImg = $this->loadImageFromFile($basePath, $size);
		if (!$sourceImg)
			return 0;

		foreach ($imgInfos as $imgInfo)
		{
			list($newPath, $maxWidth, $maxHeight, $addBrand) = $imgInfo;
			$this->newImage($sourceImg, $size[0], $size[1], $newPath, $maxWidth, $maxHeight, $addBrand);
			++$cnt;
		}
		return $cnt;
	}

	function getPicPath($full = true)
	{
		global $config, $staticRoot;
		$path = $staticRoot . $config['sourcepicdir'];

		umask(0);
		if(!is_dir($path))
			@mkdir($path,0777,true);

		# NEX-801 not changing, doesn't seem to be used anywhere live.
		$uidbase = floor($this->userid / 1000);

		$path .= "{$uidbase}/{$this->userid}/{$this->id}";
		if (!$full)
			$path .= '.thumb';

		$path .= '.jpg';
		return $path;
	}

	function uploadPic($uploadFile, $checkExif = true){
		global $config, $staticRoot, $msgs, $userData, $filesystem;

		if(!file_exists($uploadFile)){
			$msgs->addMsg("You must upload a file. If you tried, the file might be too big (1mb max).");
			return false;
		}

		$this->uploadtime = 0; // make it clear that this one isn't processed yet.
		$this->commit(); // create an ID.
		$this->uploadtime = time(); // when we recommit, set the uploadtime correctly.

		$picName = $this->getPicPath(true);
		$thumbName = $this->getPicPath(false);

		$picID = $this->id;

		$basePath = dirname($picName);

		umask(0);
		if(!is_dir($basePath))
			@mkdir($basePath,0777,true);

		$picName = "$basePath/$picID.jpg";
		$thumbName = "$basePath/$picID.thumb.jpg";

		$size = false;
		$sourceImg = $this->loadImageFromFile($uploadFile, $size);
		if (!$sourceImg)
		{
			$this->delete();
			return false;
		}

		include_once("include/JPEG.php");
		$jpeg = new JPEG($uploadFile);

		$description = $jpeg->getExifField("ImageDescription");

		if($checkExif && !empty($description) && substr($description,0,strlen($config['picText'])) == $config['picText']){
			$userid = substr($description,strlen($config['picText'])+1);
			if(!empty($userid) && $userid != $userData['userid']){
				$msgs->addMsg("You have been banned from uploading this image");
				$this->delete();
				return false;
			}
		}

		// verify that this image doesn't end up too small.
		list($picX, $picY) = $this->limitSize($size[0], $size[1], $config['maxGalleryPicWidth'], $config['maxGalleryPicHeight']);
		if($picX < $config['minPicWidth'] || $picY < $config['minPicHeight']){
			$msgs->addMsg("Picture is too small");
			$this->delete();
			return false;
		}

		$this->newImage($sourceImg, $size[0], $size[1], $picName, $config['maxFullPicWidth'], $config['maxFullPicHeight'], false);
		$this->newImage($sourceImg, $size[0], $size[1], $thumbName, $config['thumbWidth'], $config['thumbHeight'], false);
		$msgs->addMsg("Picture uploaded successfully.");

		$this->commit();
		return true;
	}
}
