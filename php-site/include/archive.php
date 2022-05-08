<?

define("ARCHIVE_MESSAGE",         1);
define("ARCHIVE_COMMENT",        11);
define("ARCHIVE_PROFILE",        21);
define("ARCHIVE_GALLERYCOMMENT", 31);
define("ARCHIVE_BLOGPOST",       41);
define("ARCHIVE_BLOGCOMMENT",    42);

define("ARCHIVE_FORUMPOST",      101);
define("ARCHIVE_ARTICLE",        111);
define("ARCHIVE_ARTICLECOMMENT", 112);
define("ARCHIVE_POLLCOMMENT",    121);
define("ARCHIVE_VIDEOCOMMENT",   131);

define("ARCHIVE_VISIBILITY_ANON",    1);
define("ARCHIVE_VISIBILITY_USER",    2);
define("ARCHIVE_VISIBILITY_FRIEND",  3);
define("ARCHIVE_VISIBILITY_ADMIN",   4);
define("ARCHIVE_VISIBILITY_PRIVATE", 5);

define("ARCHIVE_OLDEST_TIME", gmmktime( 0, 0, 0, 6, 0, 2005 ));

class archive {
	public $db;
	public $tablecache;

	function __construct($db){
		$this->db = $db;
		$this->tablecache = false;
	}

	function save($userid, $id, $type, $visibility, $touserid, $itemid, $subject, $msg){
		$this->insert($userid, $id, $type, $visibility, time(), ($userid ? ip2int(getip()) : 0), $touserid, $itemid, $subject, $msg);
	}

	function insert($userid, $id, $type, $visibility, $time, $ip, $touserid, $itemid, $subject, $msg, $checktable = false){
		$tablename = $this->tablename($time);

		if($checktable)
			$this->createTable($time);

		$rv = $this->db->prepare_query("INSERT INTO $tablename SET userid = %, id = #, type = #, visibility = #, time = #, ip = #, touserid = #, itemid = #, subject = ?, msg = ?", $userid, $id, $type, $visibility, $time, $ip, $touserid, $itemid, $subject, $msg);

		//get a way of finding when the above insert failed, create the table, then try again.
		//probably need exceptions, or extensive sql lib changes to allow and report certain errors
		if ($rv->affectedrows() < 1) {
			// Insert failure, attempt to insert into newusersanon
			$dbs = $this->db->getSplitDBs();
			if(count($dbs) > 0)
				$dbs[0]->prepare_query("INSERT INTO $tablename SET userid = %, id = #, type = #, visibility = #, time = #, ip = #, touserid = #, itemid = #, subject = ?, msg = ?", $userid, $id, $type, $visibility, $time, $ip, $touserid, $itemid, $subject, $msg);
		}
	}

	function tablename($time = false){
		if (!$time)
			$time = time();

		return "archive" . gmdate("Ym", $time);
	}

	function createTable($time = false){
		if(!$time)
			$time = time() + 86400*28; //create for next month by default

		$schematablename = 'archive';
		$newtablename = $this->tablename($time);

	//don't try to create a table that has already been created
		if(isset($this->tablecache[$newtablename]))
			return;

		$this->tablecache[$newtablename] = true;

	//get the base schema
		$res = $this->db->query("SHOW CREATE TABLE `$schematablename`");
		$row = $res->fetchrow();
		$oldcreatetable = $row['Create Table'];

	//update it with the new date
		$newcreatetable = str_replace("CREATE TABLE `$schematablename`", "CREATE TABLE IF NOT EXISTS `$newtablename`", $oldcreatetable);

	//create!
		$this->db->query($newcreatetable);

	//run on the anon server too, if there is one
		$dbs = $this->db->getSplitDBs();
		if(count($dbs) > 1)
			$dbs[0]->query(str_replace("DEFAULT CHARSET=latin1 ", '', $newcreatetable));
	}

	function search($userid, $type = false, $daterange = false) {
		$now = time();
		// Default type is everything
		if ($type == false) {
			$type = Array(
				ARCHIVE_MESSAGE, ARCHIVE_COMMENT, ARCHIVE_PROFILE, ARCHIVE_GALLERYCOMMENT, ARCHIVE_BLOGPOST, 
				ARCHIVE_BLOGCOMMENT, ARCHIVE_FORUMPOST, ARCHIVE_ARTICLE, ARCHIVE_ARTICLECOMMENT, ARCHIVE_POLLCOMMENT, 
				ARCHIVE_VIDEOCOMMENT
			);
			// ARCHIVE_VISIBILITY_ANON, ARCHIVE_VISIBILITY_USER, ARCHIVE_VISIBILITY_FRIEND, ARCHIVE_VISIBILITY_ADMIN, ARCHIVE_VISIBILITY_PRIVATE
		}

		// Default date range is ARCHIVE_OLDEST_TIME to now
		if ($daterange == false) {
			$daterange = Array(ARCHIVE_OLDEST_TIME, $now);
		} else {
			if ($daterange[0] < ARCHIVE_OLDEST_TIME)
				$daterange[0] = ARCHIVE_OLDEST_TIME;

			if ($daterange[1] > $now)
				$daterange[1] = $now;

			if ($daterange[0] > $daterange[1])
				$daterange[0] = $daterange[1];
		}
	}
}
