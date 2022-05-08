<?

	$login=0;

	require_once("include/general.lib.php");

	$default = "main";
	// wiki data
	$base = 'SiteText/music';

	class musicsection extends pagehandler {
		function __construct() {

			$this->registerSubHandler('/music',
				new urisubhandler($this, 'varhandler', REQUIRE_ANY,
					uriargs('addr', 'string')
				)
			);

			$this->registerSubHandler('/music',
				new urisubhandler($this, 'varhandler', REQUIRE_ANY
				)
			);
			
			$this->registerSubHandler(__FILE__,
				new urisubhandler($this, 'varhandler', REQUIRE_ANY,
					uriargs('addr', 'string')
				)
			);

			$this->registerSubHandler(__FILE__,
				new urisubhandler($this, 'varhandler', REQUIRE_ANY
				)
			);
		}

		function varhandler($addr = ''){
			global $wiki, $default, $base;

			$addr = trim($addr, '/');

			if(strpos($addr, '/') || !$addr) //invalid
				$addr = $default;

			$entry = $wiki->getPage("/$base/$addr");

			incHeader(602);
			
			echo $entry['output'];

			incFooter();
			exit;
		}
	}

	$musicsection = new musicsection();
	return $musicsection->runPage();
