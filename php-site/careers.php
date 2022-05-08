<?

	$login = 0;
	
	require_once('include/general.lib.php');
	
	class careers extends pagehandler {
		function __construct() {
		
			$this->registerSubHandler('/careers',
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
		
			$entry = $wiki->getPage("/wiki/SiteText/careers");

			incHeader(600);

			echo $entry['output'];

			incFooter();
			exit;
		}
	}

	$careers = new careers();
	return $careers->runPage();
