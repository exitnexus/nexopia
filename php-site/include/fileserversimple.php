<?
//this is a compatability class for the raid setup

class fileserver {

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

	function getClass($file, $filereq = true){
		foreach($this->classes as $class => $dir)
			if(preg_match($dir, $file, $matches) && (!$filereq || $matches[count($matches)-1]))
				return $class;
		
		return false;
	}

	function checkLinks($dir){ //can't check as none exist
		return array();
	}

	function testDrives(){ //can't fail in the normal sense
		return array();
	}
	
	function makeDirectory($dir){ //makes the directory if needed
		mkdir($this->basedir . $dir, 0777, true);
			
		return true;
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






