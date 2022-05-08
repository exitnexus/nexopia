<?

define("WEBLOG_PUBLIC",		1);
define("WEBLOG_LOGGEDIN",	2);
define("WEBLOG_FRIENDS",	3);
define("WEBLOG_PRIVATE",	4);

class weblog {

	var $db;

	var $scopes;

	var $entriesPerPage;

	function weblog( & $db ){
		$this->db = & $db;

 		$this->scopes = array( 	WEBLOG_PUBLIC	=> "Public",
 								WEBLOG_LOGGEDIN	=> "Logged-in only",
								WEBLOG_FRIENDS	=> "Friends only",
								WEBLOG_PRIVATE	=> "Private" );

		$this->entriesPerPage = 25;
	}

	function getEntry( $id ){
		$this->db->prepare_query("SELECT userid, title, scope, time, msg, nmsg, comments, allowcomments FROM blog, blogtext WHERE blog.id = ? && blogtext.id = blog.id", $id);

		return $this->db->fetchrow();
	}

	function insertEntry($userid, $title, $msg, $scope, $allowcomments){
		$ntitle = removeHTML($title);

		$nmsg = trim($msg);
		$nmsg = removeHTML($nmsg);

		$nmsg2 = parseHTML($nmsg);
		$nmsg3 = smilies($nmsg2);
		$nmsg3 = wrap($nmsg3);
		$nmsg3 = nl2br($nmsg3);

		$this->db->prepare_query("INSERT INTO blog SET userid = ?, title = ?, time = ?, scope = ?, allowcomments = ?", $userid, $ntitle, time(), $scope, ($allowcomments ? 'y' : 'n'));

		$id = $this->db->insertid();

		$this->db->prepare_query("INSERT INTO blogtext SET id = ?, msg = ?, nmsg = ?", $id, $nmsg, $nmsg3);

		$this->updateSubscriptions($userid, $scope);
	}

	function updateEntry($id, $userid, $title, $msg, $scope, $time, $allowcomments){

		$ntitle = removeHTML($title);

		$nmsg = trim($msg);
		$nmsg = removeHTML($nmsg);

		$nmsg2 = parseHTML($nmsg);
		$nmsg3 = smilies($nmsg2);
		$nmsg3 = wrap($nmsg3);
		$nmsg3 = nl2br($nmsg3);

		$set = "title = ?, scope = ?, msg = ?, nmsg = ?, allowcomments = ?";
		$params = array($ntitle, $scope, $nmsg, $nmsg3, ($allowcomments ? 'y' : 'n'));

		if($time){
			$set .= ", time = ?";
			$params[] = time();
		}

		$params[] = $userid;
		$params[] = $id;

		$this->db->prepare_array_query("UPDATE blog, blogtext SET $set WHERE blog.id = blogtext.id && userid = ? && blog.id = ?", $params);

		if($time)
			$this->updateSubscriptions($userid, $scope);
	}

	function deleteEntry($userid, $id){
		$this->db->prepare_query("DELETE blog, blogtext, blogcomments FROM blog, blogtext LEFT JOIN blogcomments ON blog.id=blogcomments.blogid WHERE blog.id = blogtext.id && blog.id = # && blog.userid = #", $id, $userid);
	}

	function deleteComments($userid, $blogid, $ids){
		$this->db->prepare_query("DELETE blogcomments FROM blog LEFT JOIN blogcomments ON blog.id=blogcomments.blogid WHERE blog.id = # && blog.userid = # && blogcomments.id IN (#)", $blogid, $userid, $ids);

		$num = $this->db->affectedrows();

		$this->db->prepare_query("UPDATE blog SET comments = comments - # WHERE id = # && userid = #", $num, $blogid, $userid);
	}

	function getVisibility($userid){
		$this->db->prepare_query("SELECT count(*) as count, MIN(scope) as scope FROM blog WHERE userid = ?", $userid);

		$line = $this->db->fetchrow();

		return ($line['count'] ? $line['scope'] : 0);
	}


	function getEntries($userid, $scope, $page){
		$this->db->prepare_query("SELECT blog.id, title, comments, time, msg, nmsg FROM blog, blogtext WHERE blog.userid = ? && scope <= ? && blogtext.id = blog.id ORDER BY time DESC LIMIT " . ($page * $this->entriesPerPage) . ", " . $this->entriesPerPage, $userid, $scope);

		return $this->db->fetchrowset();
	}

	function getComments($id){
		$this->db->prepare_query("SELECT id, userid, username, time, msg, nmsg FROM blogcomments WHERE blogid = ? ORDER BY time ASC", $id);

		return $this->db->fetchrowset();
	}

	function addComment($id, $userid, $username, $msg){

		$nmsg = trim($msg);
		$nmsg = removeHTML($nmsg);

		$nmsg2 = parseHTML($nmsg);
		$nmsg3 = smilies($nmsg2);
		$nmsg3 = wrap($nmsg3);
		$nmsg3 = nl2br($nmsg3);

		$this->db->prepare_query("INSERT INTO blogcomments SET blogid = ?, userid = ?, username = ?, time = ?, msg = ?, nmsg = ?", $id, $userid, $username, time(), $nmsg, $nmsg3);

		$this->db->prepare_query("UPDATE blog SET comments = comments + 1 WHERE id = #", $id);
	}

	function deleteComment($ids, $userid){
		$this->db->prepare_query("DELETE FROM blogcomments WHERE id IN (?) && userid = ?", $ids, $userid);
	}


	function subscribe($userid, $blogid){
		$this->db->prepare_query("INSERT INTO blogsubscriptions SET userid = ?, blogid = ?, time = ?", $userid, $blogid, time());
	}

	function subscriptionTouch($userid, $blogid){
		$this->db->prepare_query("UPDATE blogsubscriptions SET time = ? WHERE userid = ? && blogid = ?", time(), $userid, $blogid);
	}

	function unsubscribe($userid, $blogid){
		$this->db->prepare_query("DELETE FROM blogsubscriptions WHERE userid = ? && blogid = ?", $userid, $blogid);
	}

	function getSubscriptions($userid){
		$this->db->prepare_query("SELECT blogid, new FROM blogsubscriptions WHERE userid = ?", $userid);

		$ret = array();
		while($line = $this->db->fetchrow())
			$ret[$line['blogid']] = $line['new'];

		return $ret;
	}

	function updateSubscriptions($blogid, $scope){
		switch($scope){
			case WEBLOG_PUBLIC:
			case WEBLOG_LOGGEDIN:
				$this->db->prepare_query("UPDATE blogsubscriptions SET new = 'y' WHERE blogid = ?", $blogid);
				break;

			case WEBLOG_FRIENDS:
				$friends = array_keys(getFriendsList($blogid));

				$this->db->prepare_query("UPDATE blogsubscriptions SET new = 'y' WHERE blogid = ? && userid IN (?)", $blogid, $friends);
				break;
		//	case WEBLOG_PRIVATE:
		}
	}

}

