<?php
/**
Master database:
-class
 -id
 -name
 -replcount
-devicegroups
 -id
 -name
 -role (backup/read/write?)
-devices
 -id
 -readuri
 -writeuri
 -state
 -size
 -usage
 -readweight
 -writeweight


User database:
-files
 -userid - 4b
 -fileid - 4b
 -classid (pics/gallery/etc) -1b
 -cur version -1b
 -state -1b
-file version
 -userid
 -fileid
 -class
 -version
 -size
 -date
-file instances
 -userid
 -fileid
 -class
 -version
 -deviceid


User Requests
Application - url -> userid/fileid/class/filename/whatever
FS Client
Database
DAV backends -> devuri/<userid/1000>/userid/class/fileid/version


$fs->add($userid, $fileid, $class, $data);


http://images.nexopia.com/userpics/1234/234/2345123.jpg

class=userpics
userid=1234
fileid=2345123
version=0 - current


http://images.nexopia.com/userfiles/userid/directory/filename.txt

**/


/**
 * Interface representing a file.
 * When given a URL like:
 *	http://images.nexopia.com/userpics/1234/234/2345123.jpg
 *	http://images.nexopia.com/userfiles/userid/directory/filename.txt
 * Can give the userid, classid, fileid, as well as a self referencing URL
 *
 * $pic = new UserPicsFile($userid, $fileid);
 * $thumb = new UserPicsThumbFile($userid, $fileid);
 * $file = new UserFilesFile($userid, $path_to_file);
 *
 * $filesystem = new NexopiaFilesystem();
 * $filesystem->add($pic, $data);
 * $filesystem->del($thumb);
 *
 * $data = $filesystem->get($file);
 * header('Content-Type: ' . $file->get_content_type());
 * header('Content-Length: ' . strlen($data));
 * print $data;
 **/
interface iNexopiaFile {

	public function get_file_userid();
	public function get_file_classid();
	public function get_file_fileid();
	public function get_file_url();
	public function get_content_type();

}

/**
 * A couple quick & dirty examples
 **/
define('NEXOPIA_FILESYSTEM_CLASS_USERPICS', 1);
class UserPicsFile implements iNexopiaFile {
	private $userid;
	private $fileid;

	public function __construct($userid, $fileid) {
		$this->userid = $userid;
		$this->fileid = $fileid;
	}

	public function get_file_userid()	{return $this->userid;}
	public function get_file_classid()	{return NEXOPIA_FILESYSTEM_CLASS_USERPICS;}
	public function get_file_fileid()	{return $this->fileid;}
	public function get_content_type()	{return 'image/jpeg';}
	public function get_file_url() {
		return 'userpics/' . $this->userid . '/' . floor($this->userid / 1000) . '/' . $this->fileid . '.jpg';
	}
}

define('NEXOPIA_FILESYSTEM_CLASS_USERPICSTHUMB', 2);
class UserPicsThumbFile extends UserPicsFile {
	public function get_file_classid()	{return NEXOPIA_FILESYSTEM_CLASS_USERPICSTHUMB;}
	public function get_file_url() {
		return 'userpicsthumb/' . $this->userid . '/' . floor($this->userid / 1000) . '/' . $this->fileid . '.jpg';
	}
}

/**
 * A little more complex of an example
 **/
define('NEXOPIA_FILESYSTEM_CLASS_USERFILES', 3);
class UserFilesFile implements iNexopiaFile {
	private $userid;
	private $path;
	public function __construct($userid, $path) {
		$this->userid = $userid;
		$this->path = $path;
	}

	public function get_file_userid()	{return $this->userid;}
	public function get_file_classid()	{return NEXOPIA_FILESYSTEM_CLASS_USERFILES;}
	public function get_file_fileid() {
		global $db;
		// fileid doesn't exists in database currently
		$sql = 'SELECT fileid FROM userfileslayout WHERE userid = % AND path = ? AND type = "file"';
		$id = $db->db->prepare_query($sql)->fetchfield();
		
		// NexopiaFilesystem will handle FALSE
		return $id;
	}
	public function get_file_url() {
		return 'userpicsthumb/' . $this->userid . '/' . floor($this->userid / 1000) . '/' . $this->path;
	}

	public function get_content_type()	{
		// this should be switched a a hashtable lookup rather then a lame switch()
		$parts = Array();
		if (preg_match('/\.([A-Za-z0-9]+)$/', $this->path, $parts)) {
			switch ($parts[1]) {
				case 'jpg':
				case 'jpeg':
					return 'images/jpeg';
				case 'png':
					return 'images/png';
				case 'htm':
				case 'html':
					return 'text/html';
				default:
					return 'text/plain';
			}
		} else {
			return 'text/plain';
		}
	}
}
