<?

function uploadPic($uploadFile,$picID){
	global $config, $staticRoot, $msgs, $userData, $filesystem, $mogfs;

	$picName = $config['picdir'] . floor($userData['userid']/1000) . "/" . weirdmap($userData['userid']) . "/" . $picID . ".jpg";
	$thumbName = $config['thumbdir'] . floor($userData['userid']/1000) . "/" . weirdmap($userData['userid']) . "/" . $picID . ".jpg";

	$size = @GetImageSize($uploadFile);
	if ( !$size ){
		$msgs->addMsg("Could not open picture");
		return false;
	}

	include_once("include/JPEG.php");

	$jpeg = new JPEG($uploadFile);

	$description = $jpeg->getExifField("ImageDescription");

	if(!empty($description) && substr($description,0,strlen($config['picText'])) == $config['picText']){
		$userid = substr($description,strlen($config['picText'])+1);
		if(!empty($userid) && $userid != $userData['userid']){
			$msgs->addMsg("This picture has been blocked because it was taken from another Profile or Gallery.");
			return false;
		}
	}

	if($size[2] == 2)
	    $sourceImg = @ImageCreateFromJPEG($uploadFile);
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

	$aspectRat = (float)($size[0] / $size[1]);

	if($config['maxPicWidth']>0 && $config['maxPicHeight'] >0 && $size[0] > $config['maxPicWidth'] && $size[1] > $config['maxPicHeight']){
		$ratio = (float)($config['maxPicWidth'] / $config['maxPicHeight']);
		if($ratio < $aspectRat){
			$picX = $config['maxPicWidth'];
			$picY = $config['maxPicWidth'] / $aspectRat;
		}else{
			$picY = $config['maxPicHeight'];
			$picX = $config['maxPicHeight'] * $aspectRat;
		}
	}elseif($config['maxPicWidth'] >0 && $size[0]>$config['maxPicWidth']){
		$picX = $config['maxPicWidth'];
		$picY = $config['maxPicWidth'] / $aspectRat;
	}elseif($config['maxPicHeight'] > 0 && $size[1]>$config['maxPicHeight']){
		$picY = $config['maxPicHeight'];
		$picX = $config['maxPicHeight'] * $aspectRat;
	}else{
		$picX = $size[0];
		$picY = $size[1];
	}

	$picX = ceil($picX);
	$picY = ceil($picY);

	if($picX < $config['minPicWidth'] || $picY < $config['minPicHeight']){
		$msgs->addMsg("Picture is too small");
		return false;
	}

	if(!$config['gd2']){
		$destImg = ImageCreate($picX, $picY );
		ImageCopyResized($destImg, $sourceImg, 0,0,0,0, $picX, $picY, $size[0], $size[1]);
	}else{
		$destImg = ImageCreateTrueColor($picX, $picY );
		ImageCopyResampled($destImg, $sourceImg, 0,0,0,0, $picX, $picY, $size[0], $size[1]);
	}


	$white = ImageColorClosest($destImg, 255, 255, 255);
	$black = ImageColorClosest($destImg, 0, 0, 0);


	$padding = 3;
	$border = 1;
	$offset = 10;
	$textwidth = (strlen($config['picText'])*7)-1; // 6 pixels per character, 1 pixel space, 3 pixels padding
	$textheight = 14;

	ImageRectangle($destImg,$picX-$textwidth-$offset-$border*2-$padding*2,$picY-$textheight-$offset-$border,$picX-$offset,$picY-$offset,$black);
	ImageFilledRectangle($destImg,$picX-$textwidth-$offset-$border-$padding*2,$picY-$textheight-$offset,$picX-$border-$offset,$picY-$border-$offset,$white);
	ImageString ($destImg, 3, $picX-$textwidth-$offset-$padding, $picY-$textheight-$offset, $config['picText'], $black);

	imagejpeg($destImg, $staticRoot . $picName,80);


//put in exif info
	$jpeg = new JPEG($staticRoot . $picName);

	$jpeg->setExifField("ImageDescription", "$config[picText]:$userData[userid]");
	$jpeg->save();

	if($config['thumbWidth']>0 && $config['thumbHeight'] >0 && $size[0] > $config['thumbWidth'] && $size[1] > $config['thumbHeight']){
		$ratio = (float)($config['thumbWidth'] / $config['thumbHeight']);
		if($ratio < $aspectRat){
			$newX = $config['thumbWidth'];
			$newY = $config['thumbWidth'] / $aspectRat;
		}else{
			$newY = $config['thumbHeight'];
			$newX = $config['thumbHeight'] * $aspectRat;
		}
	}elseif($config['thumbWidth'] >0 && $size[0]>$config['thumbWidth']){
		$newX = $config['thumbWidth'];
		$newY = $config['thumbWidth'] / $aspectRat;
	}elseif($config['thumbHeight'] > 0 && $size[1]>$config['thumbHeight']){
		$newY = $config['thumbHeight'];
		$newX = $config['thumbHeight'] * $aspectRat;
	}else{
		$newX = $size[0];
		$newY = $size[1];
	}

	if(!$config['gd2']){
		$thumbImg = ImageCreate($newX, $newY );
		ImageCopyResized($thumbImg, $destImg, 0,0,0,0, $newX, $newY, $picX, $picY);
	}else{
		$thumbImg = ImageCreateTrueColor($newX, $newY );
	    ImageCopyResampled($thumbImg, $destImg, 0,0,0,0, $newX, $newY, $picX, $picY);
	}

	imagejpeg($thumbImg, $staticRoot . $thumbName, 80);

	$jpeg = new JPEG($staticRoot . $thumbName);

	$jpeg->setExifField("ImageDescription", "$config[picText]:$userData[userid]");
	$jpeg->save();

	$filesystem->add($picName);
	$filesystem->add($thumbName);

	$mogfs->add(FS_USERPICS, "{$userData['userid']}/${picID}", file_get_contents("${staticRoot}${picName}"));
	$mogfs->add(FS_USERPICSTHUMB, "{$userData['userid']}/${picID}", file_get_contents("${staticRoot}${thumbName}"));

	return true;
}

function addPic($userfile,$description, $signpic, &$reachedMax){
	global $userData, $msgs, $usersdb, $config, $staticRoot, $mods;

	if(!file_exists($userfile)){
		$msgs->addMsg("You must upload a file. If you tried, the file might be too big (1mb max).");
		return false;
	}

	$md5 = md5_file($userfile);

	$res = $usersdb->prepare_query("SELECT times FROM picbans WHERE md5 = ? && userid IN (%)", $md5, array(0, $userData['userid']));
	$ban = $res->fetchrow();

	if($ban){
		$times = $ban['times'];

		if($times > 1){
			$msgs->addMsg("This picture has been banned because it has been denied twice already.");
			return false;
		}
	}

	$res = $usersdb->prepare_query("SELECT userid FROM picspending WHERE md5 = ? && userid = %", $md5, $userData['userid']);

	if($res->fetchrow()){
		$msgs->addMsg("You already uploaded this picture");
		return false;
	}

	$picID = $usersdb->getSeqID($userData['userid'], DB_AREA_PICS);

	umask(0);

	if(!is_dir($staticRoot . $config['picdir'] . floor($userData['userid']/1000) . "/" . weirdmap($userData['userid'])))
		@mkdir($staticRoot . $config['picdir'] . floor($userData['userid']/1000) . "/" . weirdmap($userData['userid']),0777, true);
	if(!is_dir($staticRoot . $config['thumbdir'] . floor($userData['userid']/1000) . "/" . weirdmap($userData['userid'])))
		@mkdir($staticRoot . $config['thumbdir'] . floor($userData['userid']/1000) . "/" . weirdmap($userData['userid']),0777, true);

	$usersignpic = $userData['signpic'];
	$maxpics = ($userData['premium'] ? $config['maxpicspremium'] : $config['maxpics']);
	if($usersignpic == 'y')
		$maxpics++;
	$reachedMax = false;

	$res = $usersdb->prepare_query("INSERT INTO picspending SET userid = %, id = #, description = ?, md5 = ?, signpic = ?, priority = ?, time = #", $userData['userid'], $picID, removeHTML(trim(str_replace("\n", ' ', $description))), $md5, ($signpic ? 'y' : 'n'), ($userData['premium'] ? 'y' : 'n'), time());

	$num = $usersdb->prepare_query("SELECT count(*) FROM pics WHERE userid = %", $userData['userid'])->fetchfield();
	$num += $usersdb->prepare_query("SELECT count(*) FROM picspending WHERE userid = %", $userData['userid'])->fetchfield();
	
	if ($num <= $maxpics)
	{
		if($res->affectedrows() == 1 && uploadPic($userfile, $picID)){
				$mods->newSplitItem(MOD_PICS,array($userData['userid'] => $picID),$userData['premium']);
		
				$msgs->addMsg("Picture uploaded successfully.");
				return $picID;
		}
	} else {
		$msgs->addMsg("You have uploaded your maximum number of pictures ($maxpics)");
		$reachedMax = true;
	}
	// clear out a picspending entry made above if the rest of the process failed somehow.
	$usersdb->prepare_query("DELETE FROM picspending WHERE userid = % AND id = #", $userData['userid'], $picID);
	return false;
}

function removePic($uid, $id){ //only one picture at a time
	global $msgs, $config, $usersdb, $mods, $filesystem, $staticRoot, $cache;

	setMaxPriority($usersdb, "pics", $id, $usersdb->prepare("userid = %", $uid), $uid);

	$usersdb->prepare_query("DELETE FROM pics WHERE userid = % AND id = #", $uid, $id);

	$cache->remove("pics-$uid");

	deletePic($uid, $id);

	$msgs->addMsg("Picture Deleted");
}


function removePicPending($ids, $deletemoditem = true){
	global $msgs,$config,$usersdb,$mods, $filesystem, $staticRoot;

	if(!is_array($ids))
		$ids = array($ids);

	$keys = array('userid' => '%', 'id' => '#');
	$usersdb->prepare_query("DELETE FROM picspending WHERE ^", $usersdb->prepare_multikey($keys, $ids));

	if($deletemoditem)
		$mods->deleteItem(MOD_PICS,$ids);

	foreach($ids as $id)
	{
		list($uid, $id) = explode(':', $id);
		deletePic($uid, $id);
	}

	if(count($ids) > 1)
		$msgs->addMsg(count($ids) . " Pictures Deleted");
	else
		$msgs->addMsg("Picture Deleted");
}

function deletePic($uid, $id){
	global $filesystem, $config, $staticRoot, $mogfs;

	$picName = $config['picdir'] . floor($uid/1000) . "/" . weirdmap($uid) . "/" . $id . ".jpg";
	$thumbName = $config['thumbdir'] . floor($uid/1000) . "/" . weirdmap($uid) . "/" . $id . ".jpg";

	$filesystem->delete($picName);
	$filesystem->delete($thumbName);

	$mogfs->delete(FS_USERPICS, "${uid}/${id}");
	$mogfs->delete(FS_USERPICSTHUMB, "${uid}/{$id}");

	if(file_exists($staticRoot . $picName))
			@unlink($staticRoot . $picName);
	if(file_exists($staticRoot . $thumbName))
			@unlink($staticRoot . $thumbName);
}

function removeAllUserPics($uids){
	global $usersdb;

	$usersdb->prepare_query("DELETE FROM picbans WHERE userid IN (%)", $uids);

	$result = $usersdb->prepare_query("SELECT userid, id FROM pics WHERE userid IN (%)", $uids);

	while($line = $result->fetchrow())
		deletePic($line['userid'], $line['id']);

	$result = $usersdb->prepare_query("SELECT userid, id FROM picspending WHERE userid IN (%)", $uids);
	while($line = $result->fetchrow())
		deletePic($line['userid'], $line['id']);

	$usersdb->prepare_query("DELETE FROM pics WHERE userid IN (%)", $uids);
	$usersdb->prepare_query("DELETE FROM picspending WHERE userid IN (%)", $uids);
}

function setFirstPic($uids){
	global $usersdb, $cache;

	$result = $usersdb->prepare_query("SELECT id, userid FROM pics WHERE userid IN (%) && priority = 1", $uids);

	while($line = $result->fetchrow()){
		$usersdb->prepare_query("UPDATE users SET firstpic = # WHERE userid = % && firstpic != #", $line['id'], $line['userid'], $line['id']);
		if($usersdb->affectedrows()){
			$cache->remove("userinfo-$line[userid]");
			$cache->remove("userprefs-$line[userid]");

		}
	}

	if(is_array($uids)){
		foreach($uids as $uid){
			$cache->remove("pics-$uid");
		}
	}else{
		$cache->remove("pics-$uids");
	}
}


