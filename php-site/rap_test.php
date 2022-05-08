<? ?>

<pre style="border: solid 1px #000; padding: 5px; background-color: #cfc;">
<u>PHP Object Layer</u>:
<hr style="height: 1px; border: 0px; background-color: #555; color: #555; margin-top: 10px;" /><?
		echo $RAP->test_call("this is a test");
		
	?><hr style="height: 1px; border: 0px; background-color: #555; color: #555; margin-top: 10px;" /><?
		echo $rap_pagehandler->do_an_add(100, 3.14159);
		
	?><hr style="height: 1px; border: 0px; background-color: #555; color: #555; margin-top: 10px;" /><?
		echo $rap_pagehandler->a_msg();
		
	?><hr style="height: 1px; border: 0px; background-color: #555; color: #555; margin-top: 10px;" /><?
		$obj = $rap_pagehandler->get_ruby_object();
		echo $obj->a_msg();
		
	?><hr style="height: 1px; border: 0px; background-color: #555; color: #555; margin-top: 10px;" /><?
		echo $RAP->test_call("this is a test");
		
	?><hr style="height: 1px; border: 0px; background-color: #555; color: #555; margin-top: 10px;" /><?
		print_r($rap_pagehandler->get_an_array());
		
	?><hr style="height: 1px; border: 0px; background-color: #555; color: #555; margin-top: 10px;" /><?
	$an_array = array("one", "two", "bob" => "here");
	print_r($RAP->test_call($an_array));
		
	?><hr style="height: 1px; border: 0px; background-color: #555; color: #555; margin-top: 10px;" /><?
	$another_array = array("one", "two", "bob" => "here");
	print_r($rap_pagehandler->do_something_with_an_array($another_array));

?>


==========


<?
	$res = $dbobj->query("SELECT * FROM users WHERE userid IN (#)", 5);//array(5,203, 200));
	while($line = $res->fetch())
		print_r($line);
?>


</pre>