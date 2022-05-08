<?php
require_once("include/general.lib.php");
?>
<html>
<body>
v2
<form method="post">
 <input type="password" name="password" />
</form>
<?php
 if ($password = getREQval('password', 'string'))
 {
	echo "<div>PHP Native Method: " . $auth->mysql_hash_password($password) . "\n</div>";
	$res = $db->prepare_query("SELECT PASSWORD(?)", $password)->fetchfield();
	echo "<div>Mysql Method: " . $res . "\n</div>";
 }
?>

</body>
</html>
