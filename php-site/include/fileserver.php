<?

class fileserver {

	public $drivedir= "/data/drives/"; //ends in /
	public $basedir = "/home/nexopia/public_html"; //does NOT end in /
	public $url = "http://plus.img.nexopia.com";
	public $masterrsync = "216.194.67.199";

	public $classes = array();
	
	function __construct(){
	
		//format: /(static)/(variable-balanced)/(variable-non-balanced)/(file)
		//regex format: /\/(static)\/(variable-balanced)\/(variable-non-balanced)\/(file)/
	
		//$matches[0] => whole thing
		//$matches[1] => static
		//$matches[count($matches)-1] => filename
		//if(count($matches) >= 4) $matches[2] => variable balanced
		//if(count($matches) == 5) $matches[3] => variable non-balanced
		//
		//static can be multiple levels deep
		//variable-balanced is a single level, but need not exist at all
		//variable-non-balanced can be multiple levels, and need not exist at all
		//filename must not include any directories
	
	
	 	$this->classes = array(
		 						"users"        => '/^\/(users)\/([0-9]{1,6})\/([\.a-zA-Z0-9]{0,13})$/',
		 						"userthumb"    => '/^\/(users\/thumbs)\/([0-9]{1,6})\/([\.a-zA-Z0-9]{0,13})$/',
	 							"gallery"      => '/^\/(gallery)\/([0-9]{1,6})\/([\.a-zA-Z0-9]{0,13})$/',
								"gallerythumb" => '/^\/(gallery\/thumbs)\/([0-9]{1,6})\/([\.a-zA-Z0-9]{0,13})$/',
								"galleryfull"  => '/^\/(gallery\/full)\/([0-9]{1,6})\/([\.a-zA-Z0-9]{0,13})$/',
	 							"banner"       => '/^\/(banners)\/([\.\-_ a-zA-Z0-9]{0,48})$/',
	 							"uploads"      => '/^\/(uploads)\/([0-9]{1,5})\/([0-9]{1,8})\/([\.\-_ a-zA-Z0-9]{0,32})$/',
	 							"smilies"      => '/^\/(images\/smilies)\/([\.a-zA-Z0-9]{0,20})$/',
	 							"images"       => '/^\/(images)\/([\._a-zA-Z0-9]{0,20})$/',
	 							"skin"         => '/^\/(skins)\/([a-zA-Z0-9]{1,16})\/([\._a-zA-Z0-9]{0,20})$/',
	 							"skinjs"       => '/^\/(skins)\/([\.a-zA-Z0-9]{0,16})$/',
	 						);
	}


	function getSpace(){
		static $drives = array();
		static $timeout = 0;
		
		if($timeout < time() - 10){ //only check every 10 seconds
			$drives = array();
			$devs = array(); //used to make sure each device is only used once

			clearstatcache();

			$basestat = stat($this->drivedir);
			$devs[$basestat['dev']] = 1; //used to make sure if a mount point disapears, files aren't being put on the base drive

			$dir = opendir($this->drivedir);
			while(($file = readdir($dir)) !== false){
				if($file{0} != '.'){
					$stat = stat($this->drivedir . $file);
					$space = disk_free_space($this->drivedir . $file);
					
					if(!isset($devs[$stat['dev']]) && $space){
						$drives[$this->drivedir . $file] = $space;
						$devs[$stat['dev']] = 1;
					}
				}
			}
			closedir($dir);

			$timeout = time();
		}
	
		return $drives;
	}
	
	function testDrives(){
		$drives = $this->getSpace();

		$failed = array();

		foreach($drives as $drive => $space){
			if(	!($fp = @fopen("$drive/test", 'w')) || 
				!fwrite($fp, "test") || 
				!fflush($fp) || 
				!fclose($fp))

				$failed[] = $drive;

			if(file_exists("$drive/test"))
				@unlink("$drive/test");
		}
	
		return $failed;
	}

	function getClass($file, $filereq = true){
		foreach($this->classes as $class => $dir)
			if(preg_match($dir, $file, $matches) && (!$filereq || $matches[count($matches)-1]))
				return $class;
		
		return false;
	}
	
	function makeDirectory($dir){ //makes the directory if needed

	//check that the full directory exists and is good. disk_free_space returns false if it's a bad directory
		if(file_exists($this->basedir . $dir) && disk_free_space($this->basedir . $dir) !== false)
			return true;

	//either doesn't exist, or points to a bad location


		$static = '';
		$variable1 = '';
		$variable2 = '';


	//$matches[0] => whole thing
	//$matches[1] => static
	//$matches[count($matches)-1] => filename
	//if(count($matches) >= 4) $matches[2] => variable balanced
	//if(count($matches) == 5) $matches[3] => variable non-balanced
	//
	
		$class = $this->getClass($dir, false);
		
		preg_match($this->classes[$class], $dir, $matches);
		
		$static = '/' . $matches[1] . '/';
		
		if(count($matches) >= 4)
			$variable1 = $matches[2] . '/';
		
		if(count($matches) == 5)
			$variable2 = $matches[3] . '/';

	//create static if needed
	//create variable balanced if needed and class defines one
	//create variable non-balanced if class defines one


	//if the static directory doesn't exist, create it. This should never happen, though does make setting up a new server easy
		if(!file_exists($this->basedir . $static)){
			mkdir($this->basedir . $static, 0777, true);
		}
	
	//check that the variable balanced dir exists, and is on a good drive
		if($variable1 != '' && !$this->checkLink($this->basedir . $static . $variable1)){

			$drives = $this->getSpace();
			asort($drives);
			
			foreach($drives as $drive => $space); //get the last one
			
			if($space == '' || $space == 0){
				trigger_error("No space left on any drives!", E_USER_WARNING);
				return false;
			}

			if(!file_exists($drive . $static . $variable1))
				mkdir($drive . $static . $variable1, 0777, true);
			
			symlink($drive . $static . $variable1, substr($this->basedir . $static . $variable1, 0, -1));
		}
		
	//check that the non-balanced dir exists
		if($variable2 != '' && !file_exists($this->basedir . $static . $variable1 . $variable2)){
			mkdir($this->basedir . $static . $variable1 . $variable2, 0777, true);
		}

		return true;
	}

	function checkLinks($basedir){ //checks which directories are on drives that are still up
		$drives = $this->getSpace();
		
		$redo = array();
	
		$dir = opendir($basedir);
		
		while(($file = readdir($dir)) !== false)
			if($file{0} != '.' && is_link($basedir . $file) && !$this->checkLink($basedir . $file))
				$redo[] = $file;

		closedir($dir);
		
		return $redo;
	}
	
	function checkLink($link){ //check to make sure it points to a drive that is up
		if(substr($link, -1) == '/')
			$link = substr($link, 0, -1);

		$info = linkinfo($link);

		if($info == -1) //no file there at all
			return 0;
		
		if($info == 0){ //link points to a location that doesn't exist
			unlink($link);
			return 0;		
		}

	//valid link, does it point to a location that makes sense?
		$drives = $this->getSpace();

		$loc = readlink($link); //where the link points to

		if($loc === false){ //not a link, likely a directory
			trigger_error("Link $link not actually a link", E_USER_NOTICE);
			return 1;
		}

	//return 2 if the link points to a drive that is up
		foreach($drives as $drive => $space)
			if($space && substr($loc, 0, strlen($drive)) == $drive && file_exists($loc))
				return 2; //continue to next file

	//pointing to something outside the drive dir, ie weird but fine
		if(substr($loc, 0, strlen($this->drivedir)) != $this->drivedir){
			trigger_error("Link $link pointing to $loc, outside the drive dir", E_USER_NOTICE);
			return 1;
		}
	
	//bad link, get rid of it, up to the caller to create a new one and sync it
		unlink($link);
		return 0;
	}
	
	function syncDir($dir){ //$dir is relative to $basedir
		if(substr($dir, -1) != '/')
			$dir .= '/';

		$numfiles = exec("rsync -rWv --size-only " . $this->masterrsync. "::public_html$dir " . $this->basedir . $dir . " | wc -l") - 4;

		return $numfiles;
	}
	
	function addFile($filename){
	
		if(!file_exists($this->basedir . $filename)){
	
			$fp = fopen($this->url . $filename, 'r');
			
			if(!$fp){ //file not on master server
				return false;
			}
	
			$this->makeDirectory(dirname($filename) . '/');

			file_put_contents($this->basedir . $filename, $fp);
			fclose($fp);
		}
	}

	function deleteFile($filename){
		if(file_exists($this->basedir . $filename))
			unlink($this->basedir . $filename);
	}	
}
