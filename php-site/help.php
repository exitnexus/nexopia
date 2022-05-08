<?

	$login=0;

	require_once("include/general.lib.php");

	$default = "General";
	$base = 'help';

	class helppage extends pagehandler {
		function __construct() {

			$this->registerSubHandler('/help',
				new urisubhandler($this, 'varhandler', REQUIRE_ANY,
					uriargs('addr', 'string')
				)
			);

			$this->registerSubHandler('/help',
				new urisubhandler($this, 'varhandler', REQUIRE_ANY
				)
			);
			
			$this->registerSubHandler('/help.php',
				new urisubhandler($this, 'varhandler', REQUIRE_ANY,
					uriargs('addr', 'string')
				)
			);

			$this->registerSubHandler('/help.php',
				new urisubhandler($this, 'varhandler', REQUIRE_ANY
				)
			);
		}
		
		function varhandler($addr = ''){
			global $wiki, $default, $base;

			$addr = trim($addr, '/');

			if(strpos($addr, '/') || !$addr) //invalid
				$addr = $default;

		//get categories
			$basenames = $wiki->getPageChildren("/$base/", false);

			if(!in_array($addr, $basenames))
				die("Bad Category");

			$basepages = array();
			foreach($basenames as $name)
				$basepages[$name] = $wiki->getPage("/$base/$name");

		//get questions in this category
			$childnames = $wiki->getPageChildren("/$base/$addr", false);

			$children = array();
			foreach($childnames as $name)
				$children[$name] = $wiki->getPage("/$base/$addr/$name");
		

			$template = new template('help/varhandler');
			$template->set('names', $basenames);
			$template->set('basepages', $basepages);
			$template->set('addr', $addr);
			
			$outputchildren = array();
			foreach($children as $child){
				$pos = strpos($child['output'], "\n");
				$title = substr($child['output'], 0, $pos - ($child['autonewlines'] == 'y' ? 7 : 0)); //-7 to cut off the <br />\n
				$body =  substr($child['output'], $pos+1);
				$outputchildren[] = array('title' => $title, 'body' => $body);
			}
			$template->set('children', $outputchildren);
			$template->display();
			exit;
		}
	}

	$helppage = new helppage();
	return $helppage->runPage();
