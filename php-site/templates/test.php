<?
require_once('template.php');
$template = new Template('test.txt');
$template->show_whitespace(true);
$template->set('test', "daria");
$template->set('num', 1);
$template->set('show', true);
$template->set('array', array( "1" => 10, "2" => 11 ));
$template->set('array2', array('test this', 'test2'));
$template->set('date', time());
//$template->set('name', "daria" );
$template->display();

?>
