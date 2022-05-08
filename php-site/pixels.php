<?

	$login=0;

	require_once("include/general.lib.php");


	$rows = 20;
	$cols = 30;
	$size = 10;

	$pixels = array();
	$default = "FFFFFF";


	$click = getREQval('click', 'array');

	if($click){

		foreach($click as $loc => $color){
			list($r, $c) = explode('x', $loc);

			$db->prepare_query("UPDATE pixels SET color = ? WHERE x = # && y = #", $color, $c, $r);
			if($db->affectedrows() == 0)
				$db->prepare_query("INSERT IGNORE INTO pixels SET color = ?, x = #, y = #", $color, $c, $r);
		}
	}

	$db->query("SELECT x, y, color FROM pixels");
	while($line = $db->fetchrow())
		$pixels[$line['y']][$line['x']] = $line['color'];

	incHeader();

?>
<script>

var changes = new Array();
var timeout;

function cl(r,c){

	clearTimeout(timeout);
	window.onbeforeunload = function() {};
	window.onunload = function() {};

	var ajaxurl = '<?=$_SERVER[PHP_SELF]?>?a=a';
	var loc = r + 'x' + c;

	str = document.getElementById('mclick').innerHTML;

	first = str.lastIndexOf('#') + 1;
	last = str.length;
	color = str.substring(first, last);

	changes[loc] = color;

	var send = 0;
	for(var i in changes)
		ajaxurl += '&click[' + i + ']=' + changes[i];

	timeout = setTimeout("AjaxSendURL('" + ajaxurl + "&timeout', true)", 2000);
	if (window.onbeforeunload)
		window.onbeforeunload = function() { window.onunload = function() {}; AjaxSendURL(ajaxurl + "&onbeforeunload", false) };
	if (window.onunload)
		window.onunload = function() { clearTimeout(timeout); AjaxSendURL(ajaxurl + "&onunload", false) };

	el = document.getElementById('r' + r + 'c' + c).style.backgroundColor = color;
}

function AjaxSendURL(ajaxurl, async){
	var http = getHTTPObject();
	if (http){
		http.open("GET", ajaxurl, async);
		http.send(null);
	}
	changes = new Array();
}


</script>
<?


	echo "<table align=center width=366><form action=$_SERVER[PHP_SELF] method=post>";

	echo "<tr><td class=body align=center valign=top height=300>";
	echo "<div style=\"position:relative;\">";
	echo "<script src=$config[imgserver]/skins/color.js></script>";
	echo "<script>displayColors();</script>";
	echo "</div>";
	echo "</td></tr>";


	echo "<tr><td class=header align=center>Create a picture</td></tr>";
	echo "<tr><td class=body>";

	echo "<table cellspacing=1 cellpadding=0>";
	for($r = 0; $r < $rows; $r++){
		echo "<tr>";
		for($c = 0; $c < $cols; $c++){
			echo "<td id='r{$r}c{$c}' style=\"background-color: #" . (isset($pixels[$r][$c]) ? $pixels[$r][$c] : $default) . "\" width=$size height=$size onClick=\"cl($r,$c)\">";
			echo "</td>";
		}
		echo "</tr>";
	}
	echo "</table>";

	echo "</td></tr>";
	echo "</table>";

	incFooter();




