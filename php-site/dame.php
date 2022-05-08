<?

	$login = 0;
	
	require_once('include/general.lib.php');
	
	class dame extends pagehandler {
		function __construct() {
		
			$this->registerSubHandler('/dame',
				new urisubhandler($this, 'varhandler', REQUIRE_ANY
				)
			);
			$this->registerSubHandler(__FILE__,
				new urisubhandler($this, 'varhandler', REQUIRE_ANY
				)
			);
		}
		
		function varhandler($addr = ''){
			global $wiki;
		
			$entry = $wiki->getPage("/wiki/SiteText/content/dame");

			incHeader(600);

			echo $entry['output'];

			incFooter();
			exit;
		}
	}

	$dame = new dame();
	return $dame->runPage();
