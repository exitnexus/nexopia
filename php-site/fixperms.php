<?

	set_time_limit(0);

	$dir = "/home/nexopia/public_html/users";

	$owner = fileowner($dir);

	fixperms($dir, $owner);

function fixperms($dir, $owner){
	$handle = opendir($dir);

	while(false!==($FolderOrFile = readdir($handle))){
		if($FolderOrFile != "." && $FolderOrFile != ".."){
//			chown("$dir/$FolderOrFile", $owner);
			if(is_dir("$dir/$FolderOrFile")){
				chmod("$dir/$FolderOrFile", 0755);
				fixperms("$dir/$FolderOrFile", $owner);
			}else{
				chmod("$dir/$FolderOrFile", 0666);
			}
		}
	}

	closedir($handle);
}



