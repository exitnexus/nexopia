<?

	$login = 0;
	
	require_once('include/general.lib.php');
	
	class aboutus extends pagehandler {
		function __construct() {
		
			$this->registerSubHandler('/about',
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
		
			$entry = $wiki->getPage("/wiki/SiteText/about");

			incHeader(600);

			echo $entry['output'];

			incFooter();
			exit;
		}
	}

	$aboutus = new aboutus();
	return $aboutus->runPage();
