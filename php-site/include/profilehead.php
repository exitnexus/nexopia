<?

function incProfileHead($user){
	global $userData, $weblog;

	$uid = $user['userid'];

	$userblog = new userblog($weblog, $uid);

	$isFriend = $userData['loggedIn'] && isFriend($userData['userid'], $uid);

	$comments = $user['enablecomments']=='y';
	$blog = $userblog->isVisible($userData['loggedIn'], $isFriend);
	$gallery = ( $user['gallery']=='anyone' || 
				($user['gallery']=='loggedin' && $userData['loggedIn']) || 
				($user['gallery']=='friends' && $isFriend) );

	$cols = 2;
	if($comments) $cols++;
	if($gallery)  $cols++;
	if($blog)     $cols++;

	$width = round(100.0/$cols);

	$str = "";
	$str .= "<table width=100%><tr>";
	$str .= "<td class=header align=center width=$width%><a class=header href=/profile.php?uid=$uid><b>Profile</b></a></td>";
	if($comments)
		$str .= "<td class=header align=center width=$width%><a class=header href=/usercomments.php?id=$uid><b>Comments</b></a></td>";
	if($gallery)
		$str .= "<td class=header align=center width=$width%><a class=header href=/gallery.php?uid=$uid><b>Gallery</b></a></td>";
	if($blog)
		$str .= "<td class=header align=center width=$width%><a class=header href=/weblog.php?uid=$uid><b>Blog</b></a></td>";
	$str .= "<td class=header align=center width=$width%><a class=header href='/users/".getUserName($uid)."/friends'><b>Friends</b></a></td>";
	$str .= "</tr></table>";
	
	return $str;
}

