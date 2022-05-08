<?
	// New config file layout works like this:
	// - Use config.inc.php.local to override $databaseprofile and/or $configprofile
	// - Database profiles are called 'dbconf.$profilename.php' and config profiles
	//   are called 'config.$profilename.inc.php'.
	// - Put your settings as a whole in those files, or inherit them from the dev
	//   profile by including it, and then overriding specifically.
	// - DO NOT EVER COMMIT YOUR CONFIG.INC.PHP.LOCAL. You can commit your custom named
	//   profile if there's any reason you'd like to have it versioned.

	$databaseprofile = 'dev';
	$configprofile = 'dev';

   if (file_exists(__FILE__ . ".local"))
        include_once __FILE__ . ".local";

	if (isset($configprofile) && file_exists($profinc = inherit_config_profile($configprofile)))
		include_once($profinc);

