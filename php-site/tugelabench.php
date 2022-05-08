<?

	error_reporting (E_ALL);

	if(!isset($argv) || count($argv) != 4)
		die("Bad Arguments\n");

	include_once("include/sql.php");
	include_once("include/multisql.php");

//	if(!dl("pdo_pgsql.so")){
//		passthru("ls /usr/local/php-5.1.1/lib/php/extensions/");
//		exit;
//	}

//	include_once("include/mysql.php");
	include_once("include/pdo.php");


	include_once("include/memcached-client.php");
	include_once("include/memcache2.php");

	$memcacheoptions = 	array(
		'servers' => array( '192.168.0.50:11211' ),
		'debug'   => false,
		'compress_threshold' => 8000,
		"compress" => 0,
		'persistant' => false);

	$tugelaoptions = 	array(
		'servers' => array( '192.168.0.50:11211' ),
		'debug'   => false,
		'compress_threshold' => 8000,
		"compress" => 0,
		'persistant' => false);

	$memcache = new memcached($memcacheoptions);
	$cache    = new cache($memcache, "/home/nexopia/cache", false);
	$tugelacache = new memcached($tugelaoptions);
	$tugela    = new cache($tugelacache, "/home/nexopia/cache", false);

//	$cache    = new cache($memcache, "/home/timo/nexopia/trunk/cache", false);

	define("MSG_INBOX",	1);
	define("MSG_SENT",	2);
	define("MSG_TRASH",	3);

	define('DB_KEY_OPTIONAL',  0);
	define('DB_KEY_REQUIRED',  1);
	define('DB_KEY_FORBIDDEN', 2);

	$config['linesPerPage'] = 25;

//*
	$config['transactions'] = true;
	$config['db'] = 'nexopiamsgbench';
/*/
	$config['transactions'] = true;
	$config['db'] = 'nexopiamsgbench2';
//*/


define("SEQ_MSG",        1);
define("SEQ_MSG_FOLDER", 2);

	$benchdb =
		new sql_db(
					array(
						"engine" => "pgsql",
						"host" => "192.168.0.50",
						"login" => 'graham',
						"passwd" => 'Hawaii',

#						"engine" => "mysql",
#						"host" => "10.0.2.41",
#						"login" => 'root',
#						"passwd" => 'pRlUvi$t',

						"db" => $config['db'],
						"slowtime" => 100000,
						"debug" => 0,
						"transactions" => $config['transactions'],
						"needkey" => DB_KEY_REQUIRED,
						"seqtable" => "usercounter",
						 )
					);


//	new multiple_sql_db_hash(
//			array(
/*
				array(	"host" => "10.0.2.41",
						"login" => "root",
						"passwd" => 'pRlUvi$t',
						"db" => $config['db'],
						"slowtime" => 100000,
						"debug" => 0,
						"transactions" => $config['transactions'],
						"needkey" => DB_KEY_REQUIRED,
						"seqtable" => "usercounter",
						 ),
//*
				array(	"host" => "10.0.2.42",
						"login" => "root",
						"passwd" => 'pRlUvi$t',
						"db" => $config['db'],
						"slowtime" => 100000,
						"debug" => 0,
						"transactions" => $config['transactions'],
						"needkey" => DB_KEY_REQUIRED,
						"seqtable" => "usercounter",
						 ),
				array(	"host" => "10.0.2.43",
						"login" => "root",
						"passwd" => 'pRlUvi$t',
						"db" => $config['db'],
						"slowtime" => 100000,
						"debug" => 0,
						"transactions" => $config['transactions'],
						"needkey" => DB_KEY_REQUIRED,
						"seqtable" => "usercounter",
						 ),
//*/
/*
				array(	"host" => "192.168.0.50",
						"login" => "root",
						"passwd" => 'Hawaii',
						"db" => $config['db'],
						"slowtime" => 100000,
						"debug" => 0,
						"transactions" => $config['transactions'],
						"needkey" => DB_KEY_REQUIRED,
						"seqtable" => "usercounter",
						 ),
//*/
//			)
//					);

//	$benchdb->query(null, "SET AUTOCOMMIT = 0");

	$benchdb->query(null, "SELECT 1");

	$uidbase = $argv[1];
	$uidgroups = $argv[2];
	$uidgroupsize = $argv[3];



	$bench = array();
	$bench['randseed'] = $uidbase*1000003;

//read vs write freq
	$bench['reply'] = 0.8;
	$bench['new'] = 0.3;

//text length
	$bench['minlength'] = 1;
	$bench['maxlength'] = 50;

//pool size
	$bench['minuid'] = 1;
	$bench['maxuid'] = $uidgroups * $uidgroupsize;

//limits
	$bench['loops'] = 10000;
	$bench['time'] = 60;

//features
	$bench['getnumpages'] = false;



	$stats = array();
	$stats['numlist'] = 0;
	$stats['numread'] = 0;
	$stats['numreply'] = 0;
	$stats['numnew'] = 0;

	$stats['timelist'] = 0;
	$stats['timeread'] = 0;
	$stats['timereply'] = 0;
	$stats['timenew'] = 0;

	echo ".";

	$memcache->set("start", 1, 10);

	sleep(3);

	while($memcache->get("start"))
		usleep(10000);

//	usleep(($bench['maxuid'] - $uid)*50000); //sleep for a while so all processes start around the same time
	echo "$uidbase ";

//////////////////////////

	set_time_limit($bench['time']+10); //10 secs should be enough for cleanup

	srand($bench['randseed']); //so it's reproducable

	$startTime = gettime();

	for($i = 0; $i < $bench['loops']; $i++){
		$uid = intval(($uidbase - 1)*$uidgroupsize + 1 + ($i % $uidgroupsize));

		$curtime = gettime();

		if($curtime - $startTime > $bench['time']*10000)
			break;


		$time1 = gettime();
		$messages = listMessages();
		$stats['timelist'] += gettime() - $time1;
		$stats['numlist']++;


		foreach($messages as $msg){
			if($msg['status'] == 'new'){
				$time1 = gettime();
				$msgtext = viewMsg($msg['id']);
				$stats['timeread'] += gettime() - $time1;
				$stats['numread']++;

				if(rand()/getrandmax() < $bench['reply']){
					$time1 = gettime();
					deliverMsg($msg['from'], "This is a subject", getRandText(rand($bench['minlength'], $bench['maxlength'])), $msg['id'], "user$uid", $uid, false, false);
					$stats['timereply'] += gettime() - $time1;;
					$stats['numreply']++;
				}
			}
		}

		if(rand()/getrandmax() < $bench['new']){
			$time1 = gettime();
			deliverMsg(rand($bench['minuid'], $bench['maxuid']), "This is a subject", getRandText(rand($bench['minlength'], $bench['maxlength'])), 0, "user$uid", $uid, false, false);
			$stats['timenew']+= gettime() - $time1;
			$stats['numnew']++;
		}
//		usleep(10000);
	}

	$endTime = gettime();


	$benchdb->prepare_query($uidbase, "INSERT INTO bench SET uid = #, list = #, `read` = #, reply = #, `new` = #, runtime = #", $uidbase, $stats['numlist'], $stats['numread'], $stats['numreply'], $stats['numnew'], ($endTime - $startTime)/10);

//	echo "<pre>" . print_r($stats, true) . "</pre>";


	$str = "\n" . lpad($uidbase, 3) . ": ";
	$str.= ($config['transactions'] ? 't' : '');
	$str.= ($bench['getnumpages'] ? 'p' : '');
	$str .= "  ";
	$str.= "list: " . lpad($stats['numlist'], 5) . " " . lpad(number_format(100*$stats['timelist']/($endTime - $startTime)), 2) . "% "  . lpad(number_format(($stats['numlist'] ? $stats['timelist']/(10*$stats['numlist']) : 0), 2),6)  . "ms   ";
	$str.= "read: " . lpad($stats['numread'], 5) . " " . lpad(number_format(100*$stats['timeread']/($endTime - $startTime)), 2) . "% "  . lpad(number_format(($stats['numread'] ? $stats['timeread']/(10*$stats['numread']) : 0), 2),6)  . "ms   ";
	$str.= "repl: " . lpad($stats['numreply'], 5). " " . lpad(number_format(100*$stats['timereply']/($endTime - $startTime)), 2) . "% " . lpad(number_format(($stats['numreply'] ? $stats['timereply']/(10*$stats['numreply']) : 0), 2),6). "ms   ";
	$str.= "new: " .  lpad($stats['numnew'], 5)  . " " . lpad(number_format(100*$stats['timenew']/($endTime - $startTime)), 2) . "% "   . lpad(number_format(($stats['numnew'] ? $stats['timenew']/(10*$stats['numnew']) : 0), 2),6)    . "ms   ";
	$str.= number_format(($endTime - $startTime)/10000,2) . "s  " . lpad($benchdb->getnumqueries(), 6) . " q  " . number_format($benchdb->getnumqueries()*10000/($endTime - $startTime)) . " q/s";
//	$str.= "\n";
	echo $str;


//	$benchdb->outputQueries();

//	outputQueries();


function lpad($str, $len, $chr = " "){
	return str_pad($str, $len, $chr, STR_PAD_LEFT);
}

function listMessages(){
	global $config, $benchdb, $uid, $bench, $tugela;

	$page = 0;

	$folder = MSG_INBOX;

	$query = "SELECT " . ($bench['getnumpages'] ? "SQL_CALC_FOUND_ROWS " : "") . "id, userid FROM msgs WHERE userid = # /*&& folder = # */ ORDER BY date DESC LIMIT #, #";

	$res = $benchdb->prepare_query($uid, $query, $uid, $folder, $page*$config['linesPerPage'], $config['linesPerPage']);

	$getmessages = array();
	while($line = $res->fetchrow())
		$getmessages[] = "$line[userid]:$line[id]";

	$res->freeresult();

	$messageinfos = $tugela->get_multi($getmessages, "messages-");

	$messages = array();
	foreach ($getmessages as $id)
		$messages[$id] = $messageinfos[$id];

	if($bench['getnumpages']){
		$res = $benchdb->query($uid, "SELECT FOUND_ROWS()");
		$numrows = $res->fetchfield();
		$numpages =  ceil($numrows / $config['linesPerPage']);
		$res->freeresult();
	}

	return $messages;
}

function viewMsg($id){
	global $benchdb, $uid, $config, $tugela;

	$msg = getMsg($uid, $id);

	if(!$msg)
		listMsgs();

	if($msg['replyto'])
		$rmsg = getMsg($uid, $msg['replyto'], true);

	if($msg['status']=='new'){
		$msg['status'] = 'read';
		$tugela->put("messages-$msg[to]:$id", $msg, 0);
		$tugela->put("messages-$msg[from]:$msg[othermsgid]", $msg, 0);
	}

	return $msg;
}


function getMsg($uid, $ids, $cached = false){ //cached version has an outdated status, and may not show the right folder
	global $cache, $benchdb, $tugela;

	$multiple = is_array($ids);

	if(!$multiple)
		$ids = array($ids);

	$msgs = array();

	if($cached)
		$msgs = $cache->get_multi($ids, "msgs-$uid-");

	$missingids = array_diff($ids, array_keys($msgs));

	if(count($missingids)){
		$msgitems = $tugela->get_multi($missingids, "messages-$uid:");
		foreach ($msgitems as $line)
		{
			$msgs[$line['id']] = $line;
			$cache->put("msgs-$uid-$line[id]", $line, 86400);
		}
	}


	if(!count($msgs))
		return ($multiple ? array() : false);

	return ($multiple ? $msgs : array_shift($msgs));
}

$idcounter = array();

/*
function getid($uid){
	global $idcounter;
	if (!isset($idcounter[$uid]))
		$idcounter[$uid] = (time() - 1147200000)*1000;
	return $idcounter[$uid]++;
}
/*/
function getid($uid){
	global $benchdb;
	return $benchdb->getSeqID($uid, SEQ_MSG);
}
//*/

function deliverMsg($toid, $subject, $message, $replyto = 0, $fromname = false, $fromid = false, $ignorable = true, $html = false){
	global $emaildomain, $config, $benchdb, $cache, $tugela;

	$toid = intval($toid);
	$fromid = intval($fromid);

	$tousers[$toid] = "user$toid"; //getUserInfo($toid);

	$nsubject = removeHTML(trim($subject));

	if(!$html)
		$nmsg = removeHTML(trim($message));
	else
		$nmsg = trim($message);

	if($nsubject=="")
		$nsubject="No Subject";


	$time = time();
	$firstmsgid = array();
	$secondmsgid = array();
	foreach ($tousers as $toid => $toname){
	//identical runs, just in sorted order to reduce deadlocks
		if($toid < $fromid){
			$firstmsgids[$toid] = getid($toid);
			$secondmsgids[$toid] = ($fromid ? getid($fromid) : 0);
		}else{
			$secondmsgids[$toid] = ($fromid ? getid($fromid) : 0);
			$firstmsgids[$toid] = getid($toid);
		}

	}


	$benchdb->begin();

	$otherreply = 0;

	if($replyto){
		$otherreply = getMsg($toid, $replyto);

		if($otherreply)
			$otherreply = $otherreply['othermsgid'];
		else
			$otherreply = 0;
	}

	$html = 'n';
	$msgtext = array('html' => $html, 'msg' => $nmsg);

	foreach($tousers as $toid => $toname){
		$firstmsgid = $firstmsgids[$toid];
		$secondmsgid = $secondmsgids[$toid];

		$msgobj = array(
			'userid' => $toid, 'id' => $firstmsgid, 'msg' => $nmsg,
			'date' => $time, 'html' => $html, 'folder' => MSG_INBOX,
			'otheruserid' => $fromid, 'to' => $toid, 'toname' => $toname,
			'from' => $fromid, 'fromname' => $fromname, 'status' => 'new',
			'subject' => $nsubject, 'replyto' => $otherreply,
			'othermsgid' => $secondmsgid,);

		$tugela->put("messages-$toid:$firstmsgid", $msgobj, 0);

		$benchdb->prepare_query($toid, "INSERT INTO msgs SET userid = #, id = #, folder = #, date = #, replyto = #",
							$toid, $firstmsgid, MSG_SENT, $time, $replyto);

		if($fromid){
			$tugela->put("messages-$fromid:$secondmsgid", $msgobj, 0);

			$benchdb->prepare_query($fromid, "INSERT INTO msgs SET userid = #, id = #, folder = #, date = #, replyto = #",
								$fromid, $secondmsgid, MSG_SENT, $time, $replyto);
		}

//		$benchdb->prepare_query($toid, "INSERT INTO msgarchive SET id = ?, `to` = ?, toname = ?, `from` = ?, fromname = ?, date = ?, subject = ?, msg = ?",
//					$firstmsgid, $toid, $toname, $fromid, $fromname, $time, $nsubject, $nmsg);

		$new = $cache->remove("newmsglist-$toid");
	}

	if($otherreply){
		$replied = getMsg($toid, $replyto);
		$replied['status'] = 'replied';
		$tugela->put("messages-$toid:$replyto", $replied, 0);
		$replied = getMsg($fromid, $otherreply);
		$replied['status'] = 'replied';
		$tugela->put("messages-$fromid:$otherreply", $replied, 0);
	}

	$benchdb->commit();

	return true;
}





//*

function getRandText($len){
$LoremIpsum = "Lorem ipsum mea nonumy commune argumentum ne. Cum an iudico deserunt sapientem, ei rebum ancillae usu. Est possim corrumpit cu, fugit ubique duo ad, ut eum tantas mucius. His id reque platonem, mucius regione sensibus an has, et eum inermis singulis intellegam. Est no vero vide nulla. Sit odio possim delenit ex.

Dicit aperiri abhorreant ei cum. Tantas necessitatibus eum id, nam eruditi erroribus eu, ne sumo epicuri vix. Est ne iracundia maiestatis persequeris, ius lobortis suavitate ei, velit dicunt disputationi ne duo. Persius repudiare ei quo, in aperiam meliore conceptam mea. Nibh veri id usu, te has impedit mentitum. Dico quidam qui ea, eam solet nonummy deleniti cu, eos copiosae theophrastus te.

Sed simul appareat fabellas ei, est eius illum tamquam id. Te vidit legimus iracundia pro. Ex mea volutpat adolescens theophrastus, no augue laudem voluptatum mel, ea veniam vulputate pri. Sit periculis aliquando conclusionemque at. Ad numquam propriae persequeris vix, an eos dico nibh delectus, labitur repudiandae theophrastus ius no. Errem labores quaestio eu his, odio quodsi ei eum.

Eu ius dictas postulant democritum, splendide abhorreant sea eu, augue putant efficiendi has ut. Melius dolorem erroribus sed ei, ad qui brute scribentur. Sale brute cetero duo eu, vel iuvaret sanctus sententiae cu. Pro docendi lucilius persequeris ut. Nam fabellas mandamus no. Sed tincidunt constituto et. Solet nonumy scaevola ne mel.

Copiosae recteque disputationi et eam. Eam ad sint aeterno prompta, vis ad fabulas vulputate. Mei iuvaret accommodare te. Lucilius corrumpit ius ut. Id cibo malorum sit, recteque iracundia tincidunt ius ut, vel an partiendo sapientem vulputate.

Est quodsi referrentur te. Partem aperiam vocibus has at, eam an perpetua accusamus elaboraret. Duo denique molestiae scriptorem te. Mea te munere civibus appellantur, ea his justo nostrud. Appareat accusata pertinax ut nam. Quis meliore argumentum ius at, id eos modo nulla. Est et sumo feugait detracto, vix hinc idque fuisset ea, sea fabulas pertinacia percipitur cu.

Cibo ignota ius ut, id vix meis detraxit deseruisse, an qui fabellas indoctum. Ferri reque similique sit ut. Vix reque iuvaret assueverit at, viris luptatum expetenda sit ut, nam omnium copiosae id. Accusam concludaturque per at, cum at consetetur moderatius reprimique, pro at veri atomorum torquatos. Id eruditi petentium eam, sit et nobis commune qualisque.

Offendit abhorreant id eos. Eam te velit tation adversarium, vide animal ullamcorper quo ut. His an fastidii repudiandae ullamcorper. In ius illud vocent aperiri. Odio elitr clita eu vim.

Ius id exerci libris tamquam. Duo ex solum possim volutpat, quem semper luptatum sit et. Nec cu feugiat senserit liberavisse, minim fierent consulatu duo an. Agam utinam minimum an usu.

Adhuc quaerendum pri ea, inani perfecto constituam sea et. Et has soleat iuvaret meliore, est atqui scripta no. Duis philosophia ut sed, ea invenire mediocrem complectitur has, ea erat probo honestatis eam. Ad cum dictas appareat, his molestiae incorrupte et, dicit comprehensam quo ex.

Sed ornatus luptatum principes ne, sed ut zzril partem scripserit. Ad mei labore nominavi intellegebat, accusamus adipiscing sit ei. Sed eu nibh aliquyam antiopam, nam exerci causae ad. Sonet impetus no sit. Ex mei simul iudico. Mel id malorum assentior. Mei amet sonet assueverit cu, vix no feugiat definiebas definitiones.

Summo scaevola molestiae ei est. Quando semper indoctum usu at. Prima delenit elaboraret duo ut, duo id alienum euripidis voluptatibus, prompta atomorum his eu. An eos delenit legimus gloriatur. Sit malorum persius petentium in, appetere molestiae interpretaris duo at, ut ius habeo molestiae vulputate. Nec tempor albucius disputationi cu, his ex ubique intellegebat.

Duo duis adipisci no. Eu quo virtute pertinax, cu eum ancillae scriptorem disputando. No idque audire aliquyam vis, est vidit decore an. Sed probo denique ea, ex nam mazim epicurei appetere, vix ad nostrum commune deserunt. Ut ferri iusto laboramus mei, porro vitae diceret duo ad.

No nullam habemus vel, usu brute etiam te, sed ex tation verear. Wisi novum accumsan cu quo. Prima latine honestatis ne qui. Pro et saperet evertitur. Esse aliquam no vel. Graeci fabellas eu usu, magna vocent consectetuer eu ius, vel id malis iisque democritum.

Ius graecis incorrupte eu, in prompta moderatius sea, an eam primis iriure. Est an molestie incorrupte delicatissimi, mei ea sanctus fastidii, at delenit consequuntur sit. Iriure ullamcorper eu sed, nullam delicata mei in. Modo scaevola no his, causae cetero labores vis ne.

Vix eu aliquip percipitur instructior, cu natum placerat iracundia nam, sed alii offendit vituperatoribus in. Harum expetendis sadipscing nam ex. Sit an fuisset accusamus salutatus, volumus omnesque temporibus eu sea, eam reque menandri no. Te suas solet est, per et vero delicata. Id cum quem noluisse deseruisse. Iusto legere molestiae ea sea, cu graeco reprimique eam.

Sea id ludus ullamcorper, wisi debet in cum. Pro molestiae intellegam ex, eu animal fuisset vulputate eum, nam mazim debet denique in. Ex est graeci erroribus instructior, vel at aeterno conclusionemque. Posse timeam neglegentur cum et, ad mea nulla zzril deserunt. Per cu civibus principes. Eos wisi illud meliore ex, est quis esse molestiae et, eam omnis apeirian te. Cum consequat dissentiet ei, his possim denique ei.

Indoctum corrumpit ea sed. An nam eius dissentiunt, eos minim delicatissimi ad. Qui at odio epicurei, sit an enim fuisset sensibus. Rebum numquam atomorum te vis, cu oblique dolorum mei. Aperiam voluptaria an eum, delenit disputationi no has.

Eum offendit facilisi reprehendunt ad, mea et viderer singulis adolescens. Ipsum eripuit omittam cu est, suas magna sit ei, cu vim accusam euripidis mnesarchum. Dolor ornatus dolorem sed ex, tota modus mediocritatem mei ei, vix tota libris inimicus ut. Ei eum nostrud fabulas dissentias, mazim consul definiebas et pro.

Pro putent constituam an, est audire prodesset in. Nibh dolore quaeque ei eos. Dictas inciderint ad sit, mea modo conclusionemque an, ei eum lorem movet. Albucius placerat ex quo. Ne labitur dolorum usu. Laudem dissentiet mea ex, usu no alii choro fuisset, mea ipsum facer officiis at.

Commodo splendide an eam, ei eam brute iriure, est id augue nobis dicunt. Te illum clita has, ei nec feugiat scriptorem. Solet everti ius ei, qui ut facilis disputando, et eros aliquid quo. Nobis constituto pro ea, an eleifend neglegentur sea.

Invidunt similique ne usu, eum ad modus delicatissimi. Vim et nusquam signiferumque, eu eos aperiri salutatus. Clita corpora eos ut, qui munere argumentum intellegebat ut, salutandi ocurreret consequuntur ut pri. Omnis porro in quo. Ei qui stet natum omnesque.

An liber mentitum sit, et fugit minim admodum pro, ad quo debitis expetendis. In kasd illud sensibus est, an sale voluptatum his. Causae alterum labores ne vim. Quo id harum fabulas definitiones. Eam ne malis ridens denique, mea in quas repudiandae, aliquam ancillae consequuntur ad sed. Sea ne nemore dictas, est ad movet vivendo partiendo, nam at tale delenit. Dico copiosae consequuntur eu vis.

Nam nonumy causae conclusionemque at. Mazim cetero vel id. Nisl aeque per eu, impetus civibus officiis te eos. Ad sea vidit appareat aliquando, homero repudiandae id mel.

Ex has vidisse accusamus. Dolore audiam usu ne, cu eam quis natum. Ex indoctum vituperatoribus dico mei. Ludus consetetur efficiendi ex nam, diam epicuri iudicabit quo ex. Ei quo quot intellegat, ne his porro latine interpretaris, et wisi causae aperiri quo.

Virtute maiorum usu ea. Ut elit laudem epicurei eum, eligendi laboramus sit in. Nec veri ceteros suscipiantur te. Eam ea libris equidem explicari, inani feugait usu ne. Id qui essent luptatum.

Vis feugait legendos id, quod latine per an, an offendit disputando sea. Impetus admodum ius in. Pro at dicant dissentias, ut dolore forensibus nam. Ad quodsi malorum has, lorem incorrupte an nec, cu cum timeam atomorum.

Nam no corrumpit dissentias repudiandae, iisque oporteat sensibus id mel, nam at quem interesset mediocritatem. Vel commodo salutandi id, tempor option pro ad. Odio homero propriae ne his. Eam unum percipit disputando ne. At maluisset salutatus vel, ei paulo regione oporteat nam.

Nobis repudiare contentiones per ea. His eu puto augue quaestio, eam perfecto sensibus at, ex eos dicant accusam. Ad est dicam labitur. Saperet tibique nominati sed ei, ad erroribus dissentiunt nec, et cum utroque tractatos. Nominavi consulatu eos id, ne vix persius ceteros, no novum feugait percipitur mel.

Has tibique accusata appellantur eu, et assum consul civibus mea. Cum nihil dissentiet an, nonumy oporteat repudiandae quo ei. Veniam eloquentiam nam no. Est ut atqui mediocrem rationibus, at putant admodum nec, no consetetur efficiantur his. Est probo vulputate et, mea oblique noluisse urbanitas an. An has laoreet convenire, te nobis prompta sit.

Ius vulputate constituto an. Eum et legendos torquatos, id doctus nostrud sea. Pro rebum persius ut, cu vix aliquyam deterruisset. Ius dicunt maiestatis democritum at. Pro ea mazim elitr elaboraret, lucilius ullamcorper eu qui, illum ipsum has ex.

Iusto eloquentiam ius eu. Ex adhuc sadipscing mei, illud delenit ut nec. Autem nostro ne mel, ut vim quod dicit. In semper melius voluptua sit. Ut per sonet dictas, vis fabulas conclusionemque ad, cu usu ferri epicuri mediocrem. Cu dicat tollit ornatus has, ex nullam nominavi assentior his, mea dicunt lucilius id.

Cu nominavi sensibus scriptorem est, pri dicat dicam mediocrem at, ea amet veritus definiebas eum. Vis ludus voluptua argumentum et, adhuc aperiam ex mel. Mel essent nostro an. Eum libris utroque ea, no sea luptatum referrentur. Ne diam corpora posidonium duo. Usu lucilius adolescens cu, et sed senserit eloquentiam.

Ut pro habemus aliquyam, has nobis debitis conceptam cu. No admodum menandri mea, tibique similique ne pri. Cu per summo ridens, eu eum dico semper. Usu ne causae epicuri, qui no saepe antiopam accusamus, pri no iusto eripuit phaedrum. Ea nam omnis facete scripta, sapientem deseruisse quo no. At cum ullum praesent, putent facilis eu mel. Vix id tollit partiendo gubergren.

Simul soleat per ei. At euismod menandri tincidunt sit, in novum propriae mei. Vel te laudem vocent, nam ne ipsum congue appetere, sea verterem abhorreant cotidieque at. Qui ea dicant doming mentitum. Liber iriure mea ex, ad nec vocent animal malorum, eu sit dictas cotidieque. Te vix soleat postea tamquam, velit dolorum mel at.

Cu putant epicuri mei. Ad qui quod oporteat neglegentur. Sale noluisse vix eu, ei meis prima ridens sit, sea ne habeo soluta. Viris interesset quo ei. Et per sale epicurei mnesarchum, modo discere epicuri in quo.

Quod constituto contentiones eum et. Vix id rebum aliquyam mediocritatem. No mea utinam deseruisse, eam ut debet conclusionemque, mei nostrum commune constituam ut. Te habeo delicata sit, ad quot exerci petentium duo.

Libris omittam vel ea, ei euismod omnesque pri. Meis praesent cum ut, in option mentitum eos. Volumus evertitur deseruisse sed id. Sit ut timeam aperiam constituto.

Cu mutat choro dissentias his. No errem dicam senserit per. Cu qui fierent honestatis, vituperata disputando no mei, nusquam eligendi ex sea. Deleniti recteque definiebas qui te, deleniti suscipiantur his id. Ius malis augue aperiam no, duo cu ullum aliquam periculis, recusabo dignissim an vim.

An eam fuisset eloquentiam efficiantur. Mel utinam tractatos qualisque eu. Sea alterum ullamcorper ei. Ad mel modus recteque torquatos, nobis laoreet voluptatum et est.

Error aeque ne vis. Vim id wisi epicuri salutandi, ne dolor honestatis sit. Ad illum euismod invenire sit, tibique perfecto ex his, id mel magna quodsi constituam. Ex congue fuisset eam, ei his movet ponderum. Est tollit fastidii placerat ea, eam iriure conceptam no, sea idque disputando no. Per nisl legendos laboramus at, mei et porro possit, te est dolorum assentior. Usu eu sumo apeirian efficiendi, id eros oporteat usu.

Everti molestie appareat no mei, lorem dicant facete cu nam. Eu admodum philosophia ius. Ius dicunt adipisci repudiandae ne, sea te velit deterruisset. Reque nihil dissentiet sea in, elitr offendit mea id. Feugait hendrerit est no, dicam aliquyam iudicabit no duo.

An dolore quaestio pro, sed ex erant labitur persecuti, nullam officiis tincidunt ne sea. Ea mea porro error eruditi. Nam homero labitur facilisi in, facete labores docendi in ius. Sit ignota eripuit gubergren ne, eos dicat tempor senserit ex. Ne vim vocibus docendi, vix adhuc complectitur contentiones in.

An sed solum vivendo erroribus. Nec tempor labore in. Id cum vidit novum definiebas. Te mea scribentur mediocritatem, id vix percipit deseruisse.

Pro atqui everti cu. His noster accusam reprimique ea, eu his option appareat ponderum. Vix dicunt delectus cu, no illud accusam phaedrum mea. Per et facilisi abhorreant constituto, vivendo albucius vix ea.

An iudicabit repudiare mei. Cum an debet officiis, quas aperiri ne has. An placerat salutatus duo, quas facilis ne pri, no erant verear erroribus sea. Malis ullum possim eu eam, lobortis molestiae pro in. His utamur bonorum deleniti cu, ad pri solet causae. Mea an regione efficiendi, eius diceret no duo. Movet veritus percipit te pri, ne vitae epicurei reprehendunt has.

Ex regione senserit reprehendunt est. Quo no epicuri copiosae, inermis accommodare ut pro. Ne diceret molestie pri, eos integre deleniti definiebas ut. Est ei nusquam tractatos.

Et ludus homero qui, at sit prima nominavi delicatissimi. Vidit dicam temporibus mea ne, ad has iudico scriptorem, illud dolore nostrum in mel. Homero interesset usu ut, pri equidem deleniti eu, voluptaria omittantur ei mea. Usu quot omnis prima cu, unum cibo voluptaria sit ei, solum saperet eos cu.

Eam ut voluptatum liberavisse, no vix quod adhuc. Eam option efficiendi no, aliquip habemus disputando et his. Quaeque vivendo adversarium in eum, laboramus abhorreant pro ne. Cu eos animal legendos, cum facer suscipit ad, at senserit necessitatibus ius. Vis et docendi nominavi neglegentur. Has augue legimus invenire ne.

Nullam efficiendi deterruisset in nam. In pri animal aeterno reprehendunt, quo et soleat nostrud aliquid. Labore volutpat philosophia quo eu. Autem intellegat suscipiantur cu vel. Eum congue nonumy aperiri ne.";

	$text = explode('.', $LoremIpsum);
	shuffle($text);

	return implode('.', array_slice($text, 0, $len));
}
//*/


function gettime(){
	list($usec, $sec) = explode(" ",microtime());
	return (10000*((float)$usec + (float)$sec));
}
function removeHTML($str){
	$str = str_replace("<", "&lt;", $str);

	for($i = 0; $i <= 32; $i++)
		$str = str_replace("&#$i;", "", $str);

//	$str = str_replace("&nbsp;", " ", $str);

	return $str;
}
