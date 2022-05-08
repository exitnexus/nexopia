<?

	$login = 1;

	require_once("include/general.lib.php");

	if(!$mods->isadmin($userData['userid'],"editadmins"))
		die("Permission denied");

//	if(!in_array($userData['userid'],$debuginfousers))
//		die("Permission Denied");


	switch($action){
		case 'editrole':
			$id = getREQval('id','int');
		
			editRole($id);
			listRoles();

		case 'updaterole':
			$id = getPOSTval('id','int', -1); //-1 so that if it isn't posted, it doesn't do anything
			$data = getPOSTval('data', 'array');

			if($id != -1)
				updateRole($id, $data);

			listRoles();
			break;

		case 'deleterole':
			die("Not implemented yet");

			listRoles();
			break;

		case 'listroles':
			$dispmode = getREQval('dispmode', 'string', 'partial');
			listRoles($dispmode);


		case 'listadmins':
			listAdmins();
			
		case 'addadmin':
			editAdmin();
		
		case 'editadmin':
			$uid = getREQval('uid');
			editAdmin($uid);

		case 'updateadmin':
			$uid = getPOSTval('uid');
			$roles = getPOSTval('roles', 'array');
			if($uid)
				updateAdmin($uid, $roles);
			listAdmins();
	}

	listAdmins();
	
	


///////////////

function getRoles(){
	global $moddb;

//horrible hack! use a real way to get the column names
	$res = $moddb->prepare_query("SELECT * FROM adminroles LIMIT 1");
	$row = $res->fetchrow();

	$roles = array_keys($row);

	return array_combine($roles, $roles);
}

function menu(){
	echo "<a class=body href=$_SERVER[PHP_SELF]?action=listadmins>List Admins</a> | ";
	echo "List Roles: ";
	echo "<a class=body href=$_SERVER[PHP_SELF]?action=listroles&dispmode=partial>Partial</a>, ";
	echo "<a class=body href=$_SERVER[PHP_SELF]?action=listroles&dispmode=full>Full</a>, ";
	echo "<a class=body href=$_SERVER[PHP_SELF]?action=listroles&dispmode=vertical>Vertical</a> | ";
	echo "<a class=body href=$_SERVER[PHP_SELF]?action=editrole>Create a role</a>";
}


function listAdmins(){
	global $moddb;
	
	$res = $moddb->prepare_query("SELECT * FROM admins");

	$users = array();
	while($line = $res->fetchrow())
		$users[$line['userid']][] = $line['roleid'];


	$res = $moddb->prepare_query("SELECT * FROM adminroles");

	$roles = array();
	while($line = $res->fetchrow())
		$roles[$line['id']] = $line;

	
	$usernames = getUserName(array_keys($users));
	
	foreach($users as $userid => & $user)
		$user['username'] = $usernames[$userid];
		
	sortCols($users, SORT_ASC, SORT_CASESTR, 'username');
	
	incHeader();
	
	menu();
	
	$classes = array('body','body2');
	$i = 0;
	
	echo "<table>";
	
	echo "<tr>";
	echo "<td class=header>Admin</td>";
	echo "<td class=header>Roles</td>";
	echo "<td class=header>Titles</td>";
	echo "<td class=header>Permissions</td>";
	echo "<td class=header>Funcs</td>";
	echo "</tr>";
	
	foreach($users as $uid => $userroles){
		$i = !$i;

		$rolenames = array();
		$titles = array();
		$perms = array();
		foreach($userroles as $roleid){
			$rolenames[] = $roles[$roleid]['rolename'];
			
			if($roles[$roleid]['title'])
				$titles[] = $roles[$roleid]['title'];
			
			foreach($roles[$roleid] as $k => $v)
				if($v == 'y')
					$perms[$k] = $k;
		}

		echo "<tr>";
		echo "<td class=$classes[$i] valign=top><a class=body href=/profile.php?uid=$uid>" . $usernames[$uid] . "</a></td>";
		echo "<td class=$classes[$i] valign=top nowrap>" . implode("<br>", $rolenames) . "</td>";
		echo "<td class=$classes[$i] valign=top nowrap>" . implode("<br>", $titles) . "</td>";
		echo "<td class=$classes[$i] valign=top>" . implode(", ", $perms) . "</td>";
		echo "<td class=$classes[$i] valign=top><a class=body href=$_SERVER[PHP_SELF]?action=editadmin&uid=$uid>Edit</a></td>";
		echo "</tr>";
	}
	
	echo "<tr><td class=header align=right colspan=5><a class=header href=$_SERVER[PHP_SELF]?action=addadmin>Add Admin</a></td></tr>";
	
	echo "</table>";

	incFooter();
	exit;
}

function editAdmin($uid = 0){
	global $moddb;
	
	$res = $moddb->prepare_query("SELECT * FROM adminroles ORDER BY rolename");

	$roles = array();
	while($line = $res->fetchrow())
		$roles[$line['id']] = $line;
	
	$userroles = array();
	
	if($uid){
		$res = $moddb->prepare_query("SELECT roleid FROM admins WHERE userid = #", $uid);

		while($line = $res->fetchrow())
			$userroles[$line['roleid']] = $line['roleid'];
	}
	
	incHeader();
	
	menu();
	
	echo "<form action=$_SERVER[PHP_SELF] method=post>";
	echo "<table>";
	echo "<tr><td class=header colspan=2>Edit Admin</td></tr>";
	
	if($uid){
		echo "<input type=hidden name=uid value=$uid>";
		echo "<tr><td class=body>Username:</td><td class=body>" . getUserName($uid) . "</td></tr>";
	}else{
		echo "<tr><td class=body>Username:</td><td class=body><input class=body type=text name=uid></td></tr>";
	}
	
	foreach($roles as $roleid => $role)
		echo "<tr><td class=body colspan=2>" . makeCheckbox("roles[$roleid]", $role['rolename'], isset($userroles[$roleid])) . "</td></tr>";

	echo "<input type=hidden name=action value=updateadmin>";
	echo "<tr><td class=body colspan=2><input class=body type=submit value=Go></td></tr>";

	echo "</table>";
	echo "</form>";

	incFooter();
	exit;
}

function updateAdmin($uid, $roles){
	global $moddb;
	
	
	$uid = getUserID($uid);
	
	if(!$uid)
		return;
	
	$moddb->prepare_query("DELETE FROM admins WHERE userid = #", $uid);
	
	foreach($roles as $roleid => $v)
		$moddb->prepare_query("INSERT INTO admins SET userid = #, roleid = #", $uid, $roleid);
}

function listRoles($mode = 'partial'){
	global $moddb;

	$res = $moddb->prepare_query("SELECT * FROM adminroles ORDER BY rolename");

	$rows = $res->fetchrowset();


	incHeader();

	menu();

	$classes = array("body2","body");

	switch($mode){
		case 'partial':
			echo "<table>";
			echo "<tr>";
			echo "<td class=header nowrap>Role Name</td>";
			echo "<td class=header>Title</td>";
			echo "<td class=header>Permissions</td>";
			echo "<td class=header>Func</td>";
			echo "</tr>";
			
			foreach($rows as $row){
				$i = !$i;
			
				$id = $row[id];
			
				echo "<tr>";
				echo "<td class=$classes[$i] valign=top nowrap>$row[rolename]</td>";
				echo "<td class=$classes[$i] valign=top nowrap>$row[title]</td>";
				
				unset($row['id'],$row['rolename'],$row['title']);
				foreach($row as $k => $v)
					if($v == 'n')
						unset($row[$k]);
				
				echo "<td class=$classes[$i] valign=top>" . implode(", ", array_keys($row)) . "</td>";
				echo "<td class=$classes[$i] valign=top nowrap>";
				echo "<a class=body href=$_SERVER[PHP_SELF]?action=editrole&id=$id>Edit</a> | ";
				echo "<a class=body href=$_SERVER[PHP_SELF]?action=deleterole&id=$id>Delete</a>";
				echo "</td>";
				echo "</tr>";
			}
			echo "<tr><td class=header colspan=4>" . count($rows) . " roles</td></tr>";
			echo "</table>";
			break;

		case 'full':
			echo "<table cellspacing=1 cellpadding=2>";
		
			$cols = 0;
			foreach($rows as $row){
				if(!$cols){
					echo "<tr>";
					foreach($row as $name => $val)
						echo "<td class=header>$name</td>";
					echo "</tr>";
					$cols = count($row);
				}
				$i = !$i;
				echo "<tr>";
				
				foreach($row as $name => $val){
					echo "<td class=$classes[$i]>";
					switch($name){
						case "rolename":
						case "title":
						case "id":
							echo $val;
							break;
						default:
							echo ($val == 'y' ? 'Yes' : '');
					}
					echo "</td>";
				}
				echo "</tr>";
			}
			echo "<tr><td class=header colspan=$cols>" . count($rows) . " roles</td></tr>";
			echo "</table>";
			break;

		case 'vertical':
			$perms = array_keys($rows[0]);

			unset($perms['rolename']);

			echo "<table>";

			echo "<tr>";
			echo "<td class=header></td>";
			foreach($rows as $row)
				echo "<td class=header align=center>$row[rolename]</td>";
			echo "</tr>";
			
			
			foreach($perms as $perm){
				$i = !$i;
			
				echo "<tr>";
				echo "<td class=header align=center>$perm</td>";
				
				foreach($rows as $row){
					echo "<td class=$classes[$i] align=center>";
					switch($perm){
						case "rolename":
						case "title":
						case "id":
							echo $row[$perm];
							break;
						default:
							echo ($row[$perm] == 'y' ? 'Yes' : '');
					}
					echo "</td>";
				}
				echo "</tr>";
			}
			echo "</table>";

			break;
	}


	incFooter();
	exit;
}

function editRole($id = 0){
	global $moddb;

	$roles = getRoles();

	unset($roles['id'], $roles['rolename'], $roles['title']);

	$role = array('rolename' => '', 'title' => '');
	if($id){
		$res = $moddb->prepare_query("SELECT * FROM adminroles WHERE id = #", $id);
		
		$role = $res->fetchrow();
	}

	incHeader();

	menu();
	
	echo "<form action=$_SERVER[PHP_SELF] method=post>";
	echo "<input type=hidden name=id value=$id>";
	echo "<input type=hidden name=action value=updaterole>";
	echo "<table align=center>";
	echo "<tr><td class=header colspan=2 align=center>Create a Role</td></tr>";
	
	echo "<tr><td class=body>Name:</td><td class=body><input class=body type=text name=data[rolename] value='$role[rolename]'></td></tr>";
	echo "<tr><td class=body>Title:</td><td class=body><input class=body type=text name=data[title] value='$role[title]'></td></tr>";
	
	foreach($roles as $v)
		echo "<tr><td class=body colspan=2>" . makeCheckBox("data[$v]", $v, (isset($role[$v]) && $role[$v] == 'y')) . "</td></tr>";

	echo "<tr><td class=body colspan=2><input class=body type=submit value=Create></td></tr>";

	echo "</table>";
	echo "</form>";

	incFooter();
	exit;
}

function updateRole($id, $data){
	global $moddb;

//horrible hack! use a real way to get the column names
	$roles = getRoles();
	unset($roles['id'], $roles['rolename'], $roles['title']);

	$set = array();
	$set[] = $moddb->prepare("rolename = ?", $data['rolename']);
	$set[] = $moddb->prepare("title = ?", $data['title']);
	
	unset($data['rolename'], $data['title']);
	
	foreach($roles as $role)
		$set[] = "$role = " . (isset($data[$role]) ? "'y'" : "'n'");
	
	if($id){
		$moddb->query("UPDATE adminroles SET " . implode(", ", $set) . $moddb->prepare(" WHERE id = #", $id));
	}else{
		$moddb->query("INSERT INTO adminroles SET " . implode(", ", $set));
	}
}

