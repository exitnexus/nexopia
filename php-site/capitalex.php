<?

	$login = 0;
	
	require_once('include/general.lib.php');
	
	class capitalex extends pagehandler {
		function __construct() {
		
			$this->registerSubHandler('/capitalex',
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
		
			$entry = $wiki->getPage("/wiki/SiteText/capitalex");

			incHeader(600);

			echo $entry['output'];

			incFooter();
			exit;
		}
	}

	$capitalex = new capitalex();
	return $capitalex->runPage();
