<?

	$forceserver = true;

	$_SERVER['DOCUMENT_ROOT'] = getcwd();

	require_once($_SERVER['DOCUMENT_ROOT'] . "/include/general.lib.php");


	// this test figures out the impact on memory of putting many many large
    // objects to many memcache connections. The connections are not persistant,
    // and are made new every iteration, so that large numbers of them are spawned.
    // Should be run many times in parallel in order to create a backlog of data
    // and connections.

	$i = 100000;
	$j = 1000000;
	$data = str_repeat('0', $j);
	echo "<p>Outputing $i items of " . strlen($data) . " length</p>";
	while (1)
	{
		$mycacheclient = new memcached(array('servers' => array('192.168.0.50:22122'), 'compress_threshold' => 100000000000000000));
		$mycache = new cache($mycacheclient, '/temp');
		$mycache->put("blah$i$j" . rand(), $data, 10);
	}

