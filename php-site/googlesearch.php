<?
	$login = 0;

	require_once('include/general.lib.php');

	$googleimg = "${skinloc}google.png";

	$q = htmlspecialchars(getREQval('q'));
/*	$checked = array('nexopia' => '', 'web' => '');

	if ( ($which = getREQval('sitesearch')) == 'nexopia.com')
		$checked['nexopia'] = ' checked="checked"';
	else
		$checked['web'] = ' checked="checked"';
*/
	incHeader();

	switch($skin){
		case "azure":     $color = "GALT:#004481;GL:1;DIV:#D6E3EE;VLC:004481;AH:center;BGC:D6E3EE;LBGC:D6E3EE;ALC:0E66B6;LC:0E66B6;T:000000;GFNT:004481;GIMP:004481;"; break;
		case "aurora":    $color = "GALT:#287C4E;GL:1;DIV:#DFDED9;VLC:287C4E;AH:center;BGC:DFDED9;LBGC:DFDED9;ALC:41A56E;LC:41A56E;T:215670;GFNT:287C4E;GIMP:287C4E;"; break;
		case "black":     $color = "GALT:#888888;GL:1;DIV:#DFDED9;VLC:888888;AH:center;BGC:2A2A2B;LBGC:2A2A2B;ALC:B4B4B4;LC:B4B4B4;T:BCBEC0;GFNT:888888;GIMP:888888;"; break;
		case "carbon":    $color = "GALT:#004481;GL:1;DIV:#DFDED9;VLC:004481;AH:center;BGC:E9E9E9;LBGC:E9E9E9;ALC:0767BC;LC:0767BC;T:000000;GFNT:004481;GIMP:004481;"; break;
		case "crush":     $color = "GALT:#D6E7FA;GL:1;DIV:#5E739B;VLC:D6E7FA;AH:center;BGC:5E739B;LBGC:5E739B;ALC:F6DEEE;LC:F6DEEE;T:FFFFFF;GFNT:D6E7FA;GIMP:D6E7FA;"; break;
		case "flowers":   $color = "GALT:#FF7FA4;GL:1;DIV:#FFF1F6;VLC:FF7FA4;AH:center;BGC:FFF1F6;LBGC:FFF1F6;ALC:E0567E;LC:E0567E;T:FF6991;GFNT:FF7FA4;GIMP:FF7FA4;"; break;
		case "greenx":    $color = "GALT:#628241;GL:1;DIV:#272727;VLC:628241;AH:center;BGC:272727;LBGC:272727;ALC:84C14A;LC:84C14A;T:878787;GFNT:628241;GIMP:628241;"; break;
		case "halloween": $color = "GALT:#C24701;GL:1;DIV:#000000;VLC:C24701;AH:center;BGC:000000;LBGC:000000;ALC:FFFFFF;LC:FFFFFF;T:F2984C;GFNT:C24701;GIMP:C24701;"; break;
		case "megaleet":  $color = "GALT:#7995E5;GL:1;DIV:#000000;VLC:7995E5;AH:center;BGC:000000;LBGC:000000;ALC:4C4CCF;LC:4C4CCF;T:FFFFFF;GFNT:7995E5;GIMP:7995E5;"; break;
		default:
		case "newblue":   $color = "GALT:#0353A2;GL:1;DIV:#FFFFFF;VLC:0353A2;AH:center;BGC:FFFFFF;LBGC:FFFFFF;ALC:1874CF;LC:1874CF;T:323237;GFNT:0353A2;GIMP:0353A2;"; break;
		case "newyears":  $color = "GALT:#9060BE;GL:1;DIV:#313030;VLC:9060BE;AH:center;BGC:313030;LBGC:313030;ALC:B89A48;LC:B89A48;T:B8B2CD;GFNT:9060BE;GIMP:9060BE;"; break;
		case "orange":    $color = "GALT:#BF0E00;GL:1;DIV:#E9E9E9;VLC:BF0E00;AH:center;BGC:E9E9E9;LBGC:E9E9E9;ALC:FE9900;LC:FE9900;T:000000;GFNT:BF0E00;GIMP:BF0E00;"; break;
		case "pink":      $color = "GALT:#FF1493;GL:1;DIV:#DFDED9;VLC:FF1493;AH:center;BGC:DFDED9;LBGC:DFDED9;ALC:C41775;LC:C41775;T:000000;GFNT:FF1493;GIMP:FF1493;"; break;
		case "rushhour":  $color = "GALT:#626262;GL:1;DIV:#D3D3D3;VLC:626262;AH:center;BGC:D3D3D3;LBGC:D3D3D3;ALC:242424;LC:242424;T:000000;GFNT:626262;GIMP:626262;"; break;
		case "solar":     $color = "GALT:#FE9800;GL:1;DIV:#666666;VLC:FE9800;AH:center;BGC:666666;LBGC:666666;ALC:FDAB30;LC:FDAB30;T:FFFFFF;GFNT:FE9800;GIMP:FE9800;"; break;
		case "splatter":  $color = "GALT:#D74256;GL:1;DIV:#231F20;VLC:D74256;AH:center;BGC:231F20;LBGC:231F20;ALC:E36677;LC:E36677;T:BCBEC0;GFNT:D74256;GIMP:D74256;"; break;
		case "vagrant":   $color = "GALT:#5F5F5F;GL:1;DIV:#FEFEFE;VLC:5F5F5F;AH:center;BGC:FEFEFE;LBGC:FEFEFE;ALC:888888;LC:888888;T:000000;GFNT:5F5F5F;GIMP:5F5F5F;"; break;
		case "verypink":  $color = "GALT:#636466;GL:1;DIV:#F7F8F8;VLC:636466;AH:center;BGC:F7F8F8;LBGC:F7F8F8;ALC:F27FC2;LC:F27FC2;T:6F6F6F;GFNT:636466;GIMP:636466;"; break;
		case "winter":    $color = "GALT:#1F6199;GL:1;DIV:#E7F2FB;VLC:1F6199;AH:center;BGC:E7F2FB;LBGC:E7F2FB;ALC:4A88BC;LC:4A88BC;T:000000;GFNT:1F6199;GIMP:1F6199;"; break;
		case "wireframe": $color = "GALT:#D5CE72;GL:1;DIV:#403F40;VLC:D5CE72;AH:center;BGC:403F40;LBGC:403F40;ALC:E6DD6C;LC:E6DD6C;T:BCBEC0;GFNT:D5CE72;GIMP:D5CE72;"; break;
		case "abacus":    $color = "GALT:#ADA083;GL:1;DIV:#FFFFFF;VLC:ADA083;AH:center;BGC:FFFFFF;LBGC:FFFFFF;ALC:00A8D8;LC:00A8D8;T:808080;GFNT:ADA083;GIMP:ADA083;"; break;
		case "bigmusic":  $color = "GALT:#FF6666;GL:1;DIV:#FFCCCC;VLC:FF6666;AH:center;BGC:FFCCCC;LBGC:FFCCCC;ALC:FF6666;LC:FF6666;T:CC6666;GFNT:FF6666;GIMP:FF6666;"; break;
		case "cabin":     $color = "GALT:#A8662C;GL:1;DIV:#FFFFFF;VLC:DDDDDD;AH:center;BGC:FFFFFF;LBGC:FFFFFF;ALC:A8662C;LC:A8662C;T:000000;GFNT:A8662C;GIMP:A8662C;"; break;
		case "candy":     $color = "GALT:#D9757A;GL:1;DIV:#E3EBA6;VLC:81939A;AH:center;BGC:E3EBA6;LBGC:E3EBA6;ALC:648489;LC:648489;T:000000;GFNT:D9757A;GIMP:D9757A;"; break;
		case "earth":     $color = "GALT:#145F79;GL:1;DIV:#E1D5B8;VLC:145F79;AH:center;BGC:E1D5B8;LBGC:E1D5B8;ALC:B45C15;LC:B45C15;T:000000;GFNT:145F79;GIMP:145F79;"; break;
		case "friends":   $color = "GALT:#FFFFFF;GL:1;DIV:#FF7777;VLC:FFFFFF;AH:center;BGC:FF7777;LBGC:FF7777;ALC:FFFFFF;LC:FFFFFF;T:000000;GFNT:FFFFFF;GIMP:FFFFFF;"; break;
		case "newflowers":$color = "GALT:#C7C1FE;GL:1;DIV:#E630AE;VLC:C7C1FE;AH:center;BGC:E630AE;LBGC:E630AE;ALC:C7C1FE;LC:C7C1FE;T:FDE6FA;GFNT:C7C1FE;GIMP:C7C1FE;"; break;
		case "nextacular":$color = "GALT:#F837BB;GL:1;DIV:#0D0D0D;VLC:F837BB;AH:center;BGC:0D0D0D;LBGC:0D0D0D;ALC:F0D30E;LC:F0D30E;T:E5E5E5;GFNT:F837BB;GIMP:F837BB;"; break;
		case "rockstar":  $color = "GALT:#8C6E89;GL:1;DIV:#D9DADC;VLC:000000;AH:center;BGC:D9DADC;LBGC:D9DADC;ALC:8C6E89;LC:8C6E89;T:000000;GFNT:8C6E89;GIMP:8C6E89;"; break;
		case "schematic": $color = "GALT:#7C7C7C;GL:1;DIV:#FFFFFF;VLC:7C7C7C;AH:center;BGC:FFFFFF;LBGC:FFFFFF;ALC:5C9664;LC:5C9664;T:323237;GFNT:7C7C7C;GIMP:7C7C7C;"; break;
		case "somber":    $color = "GALT:#959899;GL:1;DIV:#F2F2F2;VLC:959899;AH:center;BGC:F2F2F2;LBGC:F2F2F2;ALC:000000;LC:000000;T:000000;GFNT:959899;GIMP:959899;"; break;
		case "twilight":  $color = "GALT:#2F5FAC;GL:1;DIV:#00003B;VLC:2F5FAC;AH:center;BGC:00003B;LBGC:00003B;ALC:FFFFFF;LC:FFFFFF;T:CCCCCC;GFNT:2F5FAC;GIMP:2F5FAC;"; break;
	}

	echo <<<ENDTEXT
<!-- SiteSearch Google -->
<form method="get" action="/googlesearch">

	<input type="hidden" name="domains" value="nexopia.com"></input>
	<input type="hidden" name="client" value="pub-5130698294741465"></input>
	<input type="hidden" name="forid" value="1"></input>
	<input type="hidden" name="channel" value="5636268345"></input>
	<input type="hidden" name="ie" value="ISO-8859-1"></input>
	<input type="hidden" name="oe" value="ISO-8859-1"></input>
	<input type="hidden" name="cof" value="${color}FORID:11"></input>
	<input type="hidden" name="hl" value="en"></input>
	<input type="hidden" name="safe" value="active"></input>
	<input type="hidden" name="sitesearch" value="">



	<table border="0">
		<tr>
			<td class="body">
				<label for="sbi" style="display: none;">Enter your search terms</label>
				<input class="body" type="text" name="q" size="31" maxlength="255" value="${q}" id="sbi"></input>
			</td>
			<td class="body">
				<input type="image" style="border: none;" src="${googleimg}">
			</td>
		</tr>
	</table>
</form>
<!-- SiteSearch Google -->
ENDTEXT;

	if($q){
		echo <<<ENDTEXT
<!-- Google Search Result Snippet Begins -->
<div id="googleSearchUnitIframe"></div>

<script type="text/javascript">
	var googleSearchIframeName = 'googleSearchUnitIframe';
	var googleSearchFrameWidth = 700;
	var googleSearchFrameborder = 0 ;
	var googleSearchDomain = 'www.google.ca';
</script>
<script type="text/javascript" src="http://www.google.com/afsonline/show_afs_search.js">
</script>
<script>
setTimeout(function () {
	var divGoogle = document.getElementById('googleSearchUnitIframe');
	var fraGoog = divGoogle.getElementsByTagName('iframe')[0];

	fraGoog.scrolling = 'auto';
	fraGoog.height = 1250;
	fraGoog.width = divGoogle.scrollWidth;
}, 10);
</script>
<!-- Google Search Result Snippet Ends -->
ENDTEXT;

	}

	incFooter();
