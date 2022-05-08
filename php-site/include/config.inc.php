<?
	// New config file layout works like this:
	// - Use config.inc.php.local to override $databaseprofile and/or $configprofile
	// - Database profiles are called 'dbconf.$profilename.php' and config profiles
	//   are called 'config.$profilename.inc.php'.
	// - Put your settings as a whole in those files, or inherit them from the dev
	//   profile by including it, and then overriding specifically.
	// - DO NOT EVER COMMIT YOUR CONFIG.INC.PHP.LOCAL. You can commit your custom named
	//   profile if there's any reason you'd like to have it versioned.

	$configprofile = 'dev';

	if(isset($_SERVER['NEXOPIA_PHP_CONFIG']) && $_SERVER['NEXOPIA_PHP_CONFIG'])
		$configprofile = $_SERVER['NEXOPIA_PHP_CONFIG'];
	elseif(file_exists(__FILE__ . ".local"))
		include(__FILE__ . ".local");

	if(isset($configprofile) && file_exists($profinc = inherit_config_profile($configprofile)))
		include($profinc);

	// call with include inherit_config_profile('name');
	function inherit_config_profile($profilename){
		global $cwd;
		return("$cwd/include/config.$profilename.inc.php");
	}

