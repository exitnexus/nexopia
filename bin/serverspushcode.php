#!/usr/local/php/bin/php
<?

$localroot = "/home/nexopia/public_html/";
$remoteroot = "/home/nexopia/public_html_ram/";
$remotecache = "/home/nexopia/cache";

$filename = array_shift($argv);

if($argc == 1){
	while(!feof(STDIN))
		$argv[] = trim(fgets(STDIN));
}

foreach($argv as $k => $x){
	if(strpos($x, "public_html/") === 0)
		$argv[$k] = $x = substr($x, 12);

	if(file_exists($localroot . $x) && is_file($localroot . $x))
		echo "$x:\n" . `diff $localroot$x $remoteroot$x`;
	else
		unset($argv[$k]);
}

//print_r($argv);
//exit;

if(!count($argv))
	die("No files chosen\n");

$cmd = "";
foreach($argv as $x){
	$cmd .= "cp $localroot$x $remoteroot$x\n";
	if(substr($x, 0, 33) == "include/templates/template_files/")
		$cmd .= "rm -f $remotecache/templates/" . substr($x, 33, -5) . ".parsed.php\n";
}
$cmd .= "rm -f $remotecache/compiled*\n";

$hosts = file("/home/nexopia/servers/php");

foreach($hosts as $host){
	$host = trim($host);
	if(!trim($host))
		continue;

	echo "$host ";
	echo `ssh root@$host "$cmd"`;
}

echo "local\n";
echo `$cmd`;
