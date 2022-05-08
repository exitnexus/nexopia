<?

	$login=0;

	require_once("include/general.lib.php");

	// This page is unnecessarily complicated as it is more of a demo than a template.
    // It offers up the following means of accessing it:
    // - as its own filename (newempty.php) with up to 2 arguments called blah (an int) and stuff (a string).
    //   It further follows seperate control paths based on whether you pass it blah or not.
    // - as a 'nice format url' with one string argument.

	class emptypage extends pagehandler
	{
		function __construct()
		{
			$this->registerSubHandler(__FILE__,
				new varsubhandler($this, array('normalpage', 1, 2, 'hasblah'), REQUIRE_ANY,
					varargs('blah', 'integer', 'request'),
					varargs('stuff', 'string', 'request', false, 'blorp')
				)
			);
			$this->registerSubHandler(__FILE__,
				new varsubhandler($this, array('normalpage', 1, 2, 'noblah'), REQUIRE_ANY,
					varargs('stuff', 'string', 'request', false, 'blorp')
				)
			);
			$this->registerSubHandler('/empty page',
				new urisubhandler($this, 'nicepage', REQUIRE_LOGGEDIN,
					uriargs('text', 'string')
				)
			);
			$this->registerSubHandler('/empty page',
				new urisubhandler($this, 'nicepage', REQUIRE_LOGGEDIN)
			);
		}

		function normalpage($one, $two, $three)
		{
			incHeader();
			echo "Hello $one, $two, and $three! I got the following in:\n<code><pre>";
			print_r(array_slice(func_get_args(), 3));
			echo "</pre></code>";
			incFooter();
		}

		function nicepage()
		{
			$args = func_get_args();
			incHeader();
			echo "Hello World! I got the following in:\n<code><pre>";
			print_r($args);
			echo "</pre></code>";
			incFooter();
		}
	}
	$emptypage = new emptypage();
	return $emptypage->runPage();
