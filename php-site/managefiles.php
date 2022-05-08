<?

	$login=2;

	require_once("include/general.lib.php");

	$db->prepare_query("SELECT * FROM files WHERE userid = ? ORDER BY length(location) ASC",$userData['userid']);

	if($db->numrows()==0){
		$allperms = array( 0 => 	array(	'userid' => $userData['userid'],
											'location' => $config['basefiledir'] . floor($userData['userid']/1000) . "/" . $userData['userid'] . "/",
											'list' => 'y',
											'read' => 'y',
											'readphp' => 'n',
											'write' => 'y',
											'writephp' => 'n',
											'recursive' => 'n',
											'filesizelimit' => $config['filesizelimit'],
											'quota' => $config['quota'] ) );

		if(!file_exists($masterserver . $config['basefiledir'] . floor($userData['userid']/1000)))
			mkdir($masterserver . $config['basefiledir'] . floor($userData['userid']/1000) . "/");

		if(!file_exists($masterserver . $config['basefiledir'] . floor($userData['userid']/1000) . "/" . $userData['userid'] ))
			mkdir($masterserver . $config['basefiledir'] . floor($userData['userid']/1000) . "/" . $userData['userid']);
/*
			if(!empty($action) && $action == 'Continue'){

			}else{
				incHeader();

				echo "Some TOS thing here<br>";
				echo "<form action=$PHP_SELF>";
				echo "<input class=body type=submit name=action value=Continue>";
				echo "</form>";

				incFooter();
				exit;
			}
		}
*/
		$urlRoot = $config['imgserver'];
	}else{
		$allperms = array();

		while($line = $db->fetchrow())
			$allperms[] = $line;

		$urlRoot = $wwwdomain;
	}

//	print_r($allperms);

	$baseuserdir = getBaseDir($allperms);

	if($baseuserdir === false)
		die("no base directory. Contact the webmaster");

	$basedir = $masterserver . $baseuserdir;

	if(!isset($opendir) || @strpos(realpath($basedir . $opendir),$basedir)===false || !is_dir($basedir . $opendir))
		$opendir="/";


	if(!isset($sortd) || ($sortd!="ASC" && $sortd!="DESC"))
		$sortd="ASC";

	if(!isset($sortt) || ($sortt!="name" && $sortt!="size" && $sortt!="date"))
		$sortt="name";

	$opendirperms = getPerms($baseuserdir . $opendir);

//	print_r($opendirperms);

	if($opendirperms==false || $opendirperms['list']=='n')
		die("you don't have list access to this folder");

	if(!isset($action))
		$action="";

	switch($action){
		case "Upload":				upload($userfile,$userfile_name);			break;
		case "edit":				edit($filename);							break;
		case "Save":				save($filename,$text);						break;
		case "delete":				delete($filename);							break;
		case "Create File":			createfile($filename);						break;
		case "Create Directory":	createdir($dirname);						break;
		case "view":				view($filename);							break;
		case "download":			download($filename);						break;
		case "rename":				renamefile($filename,$new);					break;
	}

	browse($opendir);

	exit;

/////////////////////////////////////////////////

function upload($userfile,$userfile_name){
	global $opendirperms,$msgs,$basedir,$opendir;

	if($opendirperms['write']=='n'){
		$msgs->addMsg("You don't have permission to upload");
		return;
	}

	if(!isset($userfile) || $userfile=="none")
		return;

	if(strpos($userfile_name,"..")!==false || strpos($userfile_name,"/")!==false){
		$msgs->addMsg("File " . $userfile_name . " is illegal. Check its name and size.");
		return;
	}

	if($opendirperms['filesizelimit'] > 0 && filesize($userfile) > $opendirperms['filesizelimit']){
		$msgs->addMsg("You do not have permission to upload files bigger than $opendirperms[filesizelimit] bytes");
		return;
	}

	if($opendirperms['quota'] > 0 && dirsize($basedir) + filesize($userfile) > $opendirperms['quota']){
		$msgs->addMsg("You do not have enough room to upload this file.");
		return;
	}

	if(getFileExt($userfile_name)=="php" && $opendirperms['writephp']!='y'){
		$msgs->addMsg("You do not have permission to upload php files");
		return;
	}

	$destfile=$basedir . $opendir . $userfile_name;
	if(!copy($userfile, $destfile)){
		$msgs->addMsg("File could not be copied");
		return;
	}
}

function edit($filename){
	global $opendirperms,$msgs,$basedir,$opendir,$PHP_SELF,$sortt,$sortd;

	if(!isset($filename)){
		$msgs->addMsg("bad data");
		return;
	}
	if($opendirperms['write']=='n'){
		$msgs->addMsg("You don't have permission to edit files");
		return;
	}
	if(getFileExt($filename)=='php' && $opendirperms['writephp']=='n'){
		$msgs->addMsg("You don't have permission to edit php files");
		return;
	}
	if(getFileType($filename) != 'text' && getFileType($filename) != 'php'){
		$msgs->addMsg("You can only edit text files");
		return;
	}
	if(strpos($filename,"..")!==false || strpos($filename,"/")!==false){
		$msgs->addMsg("Invalid File name");
		return;
	}
	if(!file_exists($basedir . $opendir . $filename)){
		$msgs->addMsg("File doesn't exist");
		return;
	}

	if(!($fp = fopen( $basedir . $opendir . $filename, 'r' ))){
		$msgs->addMsg("Cannot open file");
		return;
	}

	$file_contents = fread( $fp, filesize( $basedir . $opendir . $filename ) );

	fclose( $fp );


	incHeader();

	echo "<center>";
	echo "$urlRoot$baseuserdir$opendir$filename<br>";
	echo "<form action=$PHP_SELF method=post>";
	echo "<textarea name=text wrap=off style='font: 10pt courier new; width: 750; height:500'>";
	echo htmlentities($file_contents);
	echo "</textarea><br>";
	echo "<input type=hidden name=opendir value=\"$opendir\">";
	echo "<input type=hidden name=filename value=\"$filename\">";
	echo "<input type=hidden name=sortt value=$sortt>";
	echo "<input type=hidden name=sortd value=$sortd>";
	echo "<input class=body type=submit name=action value=Save>";
	echo "<input class=body type=submit name=action value=Cancel>";
	echo "</form>";
	echo "</center>";

	incFooter();
	exit;
}

function save($filename,$text){
	global $opendirperms,$msgs,$basedir,$opendir;

	if(!isset($filename)){
		$msgs->addMsg("bad data");
		return false;
	}
	if($opendirperms['write']=='n'){
		$msgs->addMsg("You don't have permission to edit files");
		return false;
	}
	if(getFileExt($filename)=='php' && $opendirperms['writephp']=='n'){
		$msgs->addMsg("You don't have permission to edit php files");
		return false;
	}
	if(getFileType($filename) != 'text' && getFileType($filename) != 'php'){
		$msgs->addMsg("You can only edit text files");
		return false;
	}
	if(strpos($filename,"..")!==false || strpos($filename,"/")!==false){
		$msgs->addMsg("Invalid File name");
		return false;
	}

	if($opendirperms['filesizelimit'] > 0 && strlen($text) > $opendirperms['filesizelimit']){
		$msgs->addMsg("You do not have permission to create files bigger than $opendirperms[filesizelimit] bytes");
		return;
	}

	if($opendirperms['quota'] > 0 && dirsize($basedir) + strlen($text) > $opendirperms['quota']){
		$msgs->addMsg("You do not have enough room to save this file.");
		return;
	}

	if(!($fp = fopen( $basedir . $opendir . $filename, 'w' ))){
		$msgs->addMsg("Cannot open file");
		return false;
	}

	fwrite( $fp, $text );
	fclose( $fp );

	return true;
}

function delete($filename){
	global $msgs,$basedir,$opendir,$opendirperms;

	if($opendirperms['write']=='n'){
		$msgs->addMsg("You don't have permission");
		return;
	}

	if(!file_exists($basedir . $opendir . $filename)){
		$msgs->addMsg("File doesn't exist");
		return;
	}
	if(strpos($filename,"..")!==false || strpos($filename,"/")!==false){
		$msgs->addMsg("Invalid File name");
		return;
	}

	if(is_dir($basedir . $opendir . $filename)){
		if($opendirperms['recursive']=='n'){
			$msgs->addMsg("You don't have permission to delete directories");
			return;
		}
		if(rmdir($basedir . $opendir . $filename))
			$msgs->addMsg("Directory \"$filename\" was deleted");
		else
			$msgs->addMsg("Directory \"$filename\" was NOT deleted. Directory must be empty");
	}else{
		if(unlink($basedir . $opendir . $filename))
			$msgs->addMsg("File \"$filename\" was deleted");
		else
			$msgs->addMsg("File \"$filename\" was NOT deleted");
	}
}

function createfile($filename){
	global $msgs,$basedir,$opendir,$opendirperms;

	if(file_exists($basedir . $opendir . $filename)){
		$msgs->addMsg("File already exists");
		return;
	}

	if(save($filename,""))	//create file, checks permissions
		edit($filename);	//exit, checks permissions
}

function createdir($name){
	global $opendirperms,$msgs,$basedir,$opendir;

	if(!$opendirperms['write'] || !$opendirperms['recursive']){
		$msgs->addMsg("You don't have permission to create a directory");
		return;
	}
	if(strpos($name,"..")!==false || strpos($name,"/")!==false){
		$msgs->addMsg("Invalid directory name");
		return;
	}
	mkdir($basedir . $opendir . $name,0744);
}

function renamefile($filename,$new){
	global $opendirperms,$msgs,$basedir,$opendir;

	if(!isset($filename)){
		$msgs->addMsg("bad data");
		return;
	}
	if($opendirperms['write']=='n'){
		$msgs->addMsg("You don't have permission to rename files");
		return;
	}
	if(getFileExt($new)=='php' && $opendirperms['writephp']=='n'){
		$msgs->addMsg("You don't have permission to create php files");
		return;
	}
	if($opendirperms['readphp']=='n' && getFileType($filename)=='php'){
		$msgs->addMsg("You don't have permission to rename a php file");
		return;
	}
	if(strpos($filename,"..")!==false || strpos($filename,"/")!==false){
		$msgs->addMsg("Invalid File name");
		return;
	}
	if(strpos($new,"..")!==false || strpos($new,"/")!==false){
		$msgs->addMsg("Invalid File name");
		return;
	}
	if(!file_exists($basedir . $opendir . $filename)){
		$msgs->addMsg("File does not exist");
		return;
	}
	if(file_exists($basedir . $opendir . $new)){
		$msgs->addMsg("File $new already exists. Delete it first");
		return;
	}
	if(!rename($basedir.$opendir.$filename, $basedir.$opendir.$new))
		$msgs->addMsg("File $filename not renamed to $new");
}

function view($filename){
	global $opendirperms,$msgs,$basedir,$opendir,$baseuserdir,$PHP_SELF,$urlRoot;

	if($opendirperms['read']=='n'){
		$msgs->addMsg("You don't have read permissions");
		return;
	}

	if($opendirperms['readphp']=='n' && getFileType($filename)=='php'){
		$msgs->addMsg("You don't have read permissions on php files");
		return;
	}

	if(!file_exists($basedir.$opendir.$filename)){
		$msgs->addMsg("File doesn't exist");
		return;
	}


	switch(getFileType($filename)){
		case "php":
			incHeader();

			echo "$urlRoot$baseuserdir$opendir$filename<br>";
			echo "<table><tr><td class=body>";

			show_source($basedir.$opendir.$filename);

			echo "</td></tr></table>";

			incFooter();
			exit;

		case "text":
			incHeader();

			echo "$urlRoot$baseuserdir$opendir$filename<br>";
			echo "<table><tr><td class=body><pre>";

			readfile($basedir.$opendir.$filename);

			echo "</pre></td></tr></table>";

			incFooter();
			exit;
		case "image":
			incHeader();

			echo "[img]$urlRoot$baseuserdir$opendir$filename" . "[/img]<br>";
			echo "<a class=body href=$PHP_SELF?opendir=$opendir><img src=\"$urlRoot$baseuserdir$opendir$filename\" border=0></a>";

			incFooter();
			exit;
	}
}

function download($filename){
	global $opendirperms,$msgs,$basedir,$opendir,$baseuserdir;


	if($opendirperms['read']=='n'){
		$msgs->addMsg("You don't have read permissions");
		return;
	}

	if($opendirperms['readphp']=='n' && getFileType($filename)=='php'){
		$msgs->addMsg("You don't have read permissions on php files");
		return;
	}

	if(!file_exists($basedir.$opendir.$filename)){
		$msgs->addMsg("File doesn't exist");
		return;
	}

	header("Content-disposition: attachment; filename=$filename");
	header("Content-length: ".filesize($basedir.$opendir.$filename));
	header("Content-type: application/force-download");
	header("Connection: close");
	header("Expires: 0");
	set_time_limit(0);
	readfile($basedir.$opendir.$filename);
	exit;
}


////////////////////////////////////
///////// Start Functions //////////
////////////////////////////////////

function getFileExt( $filename ) {
	return substr($filename,strrpos($filename,".")+1) ;
}


function dirsize($dir,$maxLevel=3,$level=0) { // calculate the size of files in $dir, (it descends recursively into other dirs)
	if($level > $maxLevel)
		return 0;
	if (!($dh = @opendir($dir))){
		echo "Could not open $dir for reading<br>\n";
		return false;
	}

	$size = 0;
	while (($file = readdir($dh)) !== false){
		if ($file != "." and $file != "..") {
			$path = $dir."/".$file;
			if (is_dir($path))
				$size += dirsize($path,$maxLevel,$level+1);
			elseif (is_file($path))
				$size += filesize($path);
		}
	}

	closedir($dh);

	return $size;
}


function browse($opendir){
	global $basedir,$baseuserdir,$userData,$opendirperms, $sortt, $sortd,$PHP_SELF,$config;

	if (!($dir = @opendir($basedir . $opendir))){
		echo "Could not open " . $basedir . $opendir . " for reading";
		return;
	}

	$listing = array();
	$totalsize=0;
	while($file = readdir($dir)) {
		if($file[0]!="."){
			if(is_dir($basedir . $opendir . $file)){
				$file_size = -0.001 - dirsize($basedir . $opendir . $file)/1024;
				$totalsize -= $file_size;
				$filetype = "directory";
			}else{
				$file_size = filesize($basedir . $opendir . $file) /1024;
				$totalsize+=$file_size;

				$filetype = getFileType($file);

			}
			$file_date =  filemtime($basedir . $opendir . $file);
			$listing[]=array("filename" => $file, "filesize" => $file_size , "filedate" => $file_date, 'filetype'=> $filetype);
		}
	}
	closedir($dir);

	incHeader();

	echo "<form action=$PHP_SELF>";
	echo "<input type=hidden name=opendir value=\"$opendir\">";
	echo "<input type=hidden name=sortt value=$sortt>";
	echo "<input type=hidden name=sortd value=$sortd>";

	echo "<table cellpadding=2 cellspacing=1 width=100%>\n";
	echo "<tr>";
	echo "<td colspan=7 class=header>Current Dir: $opendir</td></tr>";
	echo "<tr>\n";
	echo "	<td class=header width=20><img src=$config[imageloc]delete.gif alt=Delete></td>";
	echo "	<td class=header width=20><img src=$config[imageloc]rename.gif alt=Rename></td>";
	echo "	<td class=header width=20><img src=$config[imageloc]down.png alt=Download></td>";
	echo "	<td class=header width=20><img src=$config[imageloc]edit.gif alt=Edit></td>";

	echo "	<td class=header><a class=header href=\"$PHP_SELF?opendir=$opendir&sortd=" . ($sortt=="name" ? ($sortd=="ASC" ? "DESC" : "ASC") : $sortd). "&sortt=name\">Name</a>". ($sortt=="name" ? "&nbsp<img src=/images/$sortd.png>" : "") ."</td>\n";
	echo "	<td class=header align=right><a class=header href=\"$PHP_SELF?opendir=$opendir&sortd=" . ($sortt=="size" ? ($sortd=="ASC" ? "DESC" : "ASC") : $sortd). "&sortt=size\">Size</a>". ($sortt=="size" ? "&nbsp<img src=/images/$sortd.png>" : "") ."</td>\n";
	echo "	<td class=header align=right><a class=header href=\"$PHP_SELF?opendir=$opendir&sortd=" . ($sortt=="date" ? ($sortd=="ASC" ? "DESC" : "ASC") : $sortd). "&sortt=date\">Date</a>". ($sortt=="date" ? "&nbsp<img src=/images/$sortd.png>" : "") ."</td>\n";
	echo "</tr>\n";



	if($opendir != "/"){
		$up=dirname($opendir);
		if($up!="/")
			$up.="/";
		echo "<tr><td class=body></td><td class=body></td><td class=body></td><td class=body></td><td class=body>[&nbsp<a class=body href=\"$PHP_SELF?opendir=$up&sortd=$sortd&sortt=$sortt\">Up one level</a>&nbsp]</td><td class=body></td><td class=body></td></tr>\n";
	}

	if(sizeof($listing) >= 1){
		usort ($listing, "cmp" . $sortt . $sortd);

		foreach($listing as $file){
			echo "<tr>";
//directories
			if($file['filetype']=='directory'){
				$perms = getPerms($baseuserdir . $opendir . $file['filename'] . "/");

//delete
				echo "<td class=body>";
				if($perms['write']=='y')
					echo "<a class=body href=\"javascript:confirmLink('$PHP_SELF?opendir=$opendir&action=delete&filename=$file[filename]','delete this directory')\"><img src=$config[imageloc]delete.gif border=0 alt=Delete></a> ";
				echo "</td>";
//rename
				echo "<td class=body>";
				if($perms['write']=='y')
					echo "<a class=body href=\"javascript: if(name = prompt('Rename to what?','$file[filename]')) location.href= '$PHP_SELF?opendir=$opendir&action=rename&filename=$file[filename]&new=' + name\"><img src=$config[imageloc]rename.gif border=0 alt=Rename></a> ";
				echo "</td>";

				echo "<td class=body></td>"; //download
				echo "<td class=body></td>"; //edit



				echo "<td class=body>";
				echo "[&nbsp;";
				if($perms['list'])
					echo "<a class=body href=\"$PHP_SELF?opendir=$opendir$file[filename]/&sortd=$sortd&sortt=$sortt\">";
				echo $file['filename'];
				if($perms['list'])
					echo "</a>";
				echo "&nbsp;]";
			}else{
//files
//delete
				echo "<td class=body>";
				if($opendirperms['write']=='y')
					echo "<a class=body href=\"javascript:confirmLink('$PHP_SELF?opendir=$opendir&action=delete&filename=$file[filename]','delete this file')\"><img src=$config[imageloc]delete.gif border=0 alt=Delete></a> ";
				echo "</td>";
//rename
				echo "<td class=body>";
				if($opendirperms['write']=='y' && (getFileType($file['filename'])!='php' || $opendirperms['readphp']=='y'))
					echo "<a class=body href=\"javascript: if(name = prompt('Rename to what?','$file[filename]')) location.href= '$PHP_SELF?opendir=$opendir&action=rename&filename=$file[filename]&new=' + name\"><img src=$config[imageloc]rename.gif border=0 alt=Rename></a> ";
				echo "</td>";
//down
				echo "<td class=body>";
				if($opendirperms['read']=='y' && (getFileType($file['filename'])!='php' || $opendirperms['readphp']=='y'))
					echo "<a class=body href=\"$PHP_SELF?opendir=$opendir&action=download&filename=$file[filename]\"><img src=$config[imageloc]down.png border=0 alt=Download></a> ";
				echo "</td>";
//edit
				echo "<td class=body>";
				if($opendirperms['read']=='y' && ($file['filetype']=='text' || ($file['filetype']=='php' && $opendirperms['writephp']=='y')))
					echo "<a class=body href=\"$PHP_SELF?opendir=$opendir&action=edit&filename=$file[filename]\"><img src=$config[imageloc]edit.gif border=0 alt=Edit></a>";
				echo "</td>";



				echo "<td class=body>";
				if($opendirperms['read']=='n' || getFileType($file['filename'])=='unknown' || ($opendirperms['readphp']=='n' && getFileType($file['filename'])=='php'))
					echo $file['filename'];
				else
					echo "<a class=body href=\"$PHP_SELF?opendir=$opendir&action=view&filename=$file[filename]\">$file[filename]</a>";
			}

			echo "</td>";
			echo "<td class=body align=right>" . number_format(abs($file["filesize"])) ." KB</td>";
			echo "<td class=body align=right>" . userdate("m/d/y h:i:s A",$file["filedate"]) . "</td>";
			echo "</tr>\n";
		}

	}

	echo "<tr>";
	echo "<td class=header></td>";
	echo "<td class=header></td>";
	echo "<td class=header></td>";
	echo "<td class=header></td>";
	echo "<td class=header>$opendir</td>";
	echo "<td class=header align=right>" . number_format($totalsize) . " KB</td>";
	echo "<td class=header align=right>Quota: " . number_format($opendirperms['quota']/1024) . " KB</td></tr>\n";
	echo "</table>";

	if($opendirperms['write']=='y'){
		echo "<table><form action=$PHP_SELF>";
		echo "<input type=hidden name=opendir value=\"$opendir\">";
		echo "<input type=hidden name=sortt value=$sortt>";
		echo "<input type=hidden name=sortd value=$sortd>";
		echo "<tr><td class=header colspan=2>Create New Text or HTML File</td></tr>";
		echo "<tr><td class=body><input class=body type=text name=filename size=40></td><td class=body><input class=body type=submit name=action value='Create File'></td></tr>";
		echo "</form>";
		echo "<tr><td class=body colspan=2>&nbsp;</td></tr>";

		if($opendirperms['recursive']=='y'){
			echo "<form action=$PHP_SELF>";
			echo "<input type=hidden name=opendir value=\"$opendir\">";
			echo "<input type=hidden name=sortt value=$sortt>";
			echo "<input type=hidden name=sortd value=$sortd>";
			echo "<tr><td class=header colspan=2>Create Directory</td></tr>";
			echo "<tr><td class=body><input class=body type=text name=dirname size=40></td><td class=body><input class=body type=submit name=action value='Create Directory'></td></tr>";
			echo "</form>";
			echo "<tr><td class=body colspan=2>&nbsp;</td></tr>";
		}

		echo "<form action=\"$PHP_SELF\" ENCTYPE=\"multipart/form-data\" method=POST>\n";
		echo "<input type=hidden name=opendir value=\"$opendir\">";
		echo "<input type=hidden name=sortt value=$sortt>";
		echo "<input type=hidden name=sortd value=$sortd>";

		echo "<tr><td class=header colspan=3>Upload Files</td></tr>";


		echo "<tr><td class=body><input class=body name=\"userfile\" type=\"file\" size=40></td><td class=body><input class=body type=submit name=action value=\"Upload\">\n";
		echo "</table></form>";

	}
	incFooter();
}


function cmpnameASC($a,$b){
	if($a["filesize"] < 0 && $b["filesize"] >= 0)
		return -1;
	if($a["filesize"] >= 0 && $b["filesize"] < 0)
		return 1;
	return strnatcasecmp($a["filename"],$b["filename"]);
}

function cmpnameDESC($a,$b){
	if($a["filesize"] < 0 && $b["filesize"] >= 0)
		return -1;
	if($a["filesize"] >= 0 && $b["filesize"] < 0)
		return 1;
	return strnatcasecmp($b["filename"],$a["filename"]);
}
function cmpsizeASC($a,$b){
	if($a["filesize"] < 0 && $b["filesize"] >= 0)
		return -1;
	if($a["filesize"] >= 0 && $b["filesize"] < 0)
		return 1;
	if($a["filesize"] == $b["filesize"])
		return 0;
	return ($a["filesize"] < $b["filesize"] ? -1 : 1);
}
function cmpsizeDESC($a,$b){
	if($a["filesize"] < 0 && $b["filesize"] >= 0)
		return -1;
	if($a["filesize"] >= 0 && $b["filesize"] < 0)
		return 1;
	if($a["filesize"] == $b["filesize"])
		return 0;
	return ($a["filesize"] < $b["filesize"] ? 1 : -1);
}
function cmpdateASC($a,$b){
	if($a["filesize"] < 0 && $b["filesize"] >= 0)
		return -1;
	if($a["filesize"] >= 0 && $b["filesize"] < 0)
		return 1;
	if($a["filedate"] == $b["filedate"])
		return 0;
	return ($a["filedate"] < $b["filedate"] ? -1 : 1);
}
function cmpdateDESC($a,$b){
	if($a["filesize"] < 0 && $b["filesize"] >= 0)
		return -1;
	if($a["filesize"] >= 0 && $b["filesize"] < 0)
		return 1;
	if($a["filedate"] == $b["filedate"])
		return 0;
	return ($a["filedate"] < $b["filedate"] ? 1 : -1);
}

function getBaseDir($allperms){
	foreach($allperms as $perm)
		if($perm['list']=='y')
			return substr($perm['location'],0,-1);
	return false;
}

function getFileType($filename){
	$extension = strtolower(getFileExt($filename));


	switch($extension){
		case "jpg":
		case "jpeg":
		case "gif":
		case "png":
		case "bmp":
			return "image";
		case "txt":
		case "c":
		case "cpp":
		case "h":
		case "nfo":
		case "css":
		case "js":
		case "html":
		case "csv":
			return "text";
		case "php":
		case "php3":
			return "php";
		default:
			return "unknown";
	}
	return "unknown";
}

function getPerms($directory){
	global $allperms;

	foreach($allperms as $perm){
		if($perm['location'] == $directory)
			return $perm;
	}

	while(1){
		$pos = strrpos(substr($directory,0,-1),"/");
		if($pos===false)
			break;
	 	$directory = substr($directory,0,$pos+1);

		foreach($allperms as $perm){
			if($perm['location'] == $directory && $perm['recursive']=='y')
				return $perm;
		}
	}

	return false;
}

