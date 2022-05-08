<?

function banner($size,$linkclass='side'){
	global $config,$fastdb,$userData;

//	if(!$config['enablebanners'])
		return "";

	$time = time();

	if(in_array($size,array("468x60","120x60","120x240","text"))){
		$commands = array();
		$commands[] = "moded='y'";
		$commands[] = "bannersize='$size'";
		$commands[] = "(maxviews<1 || views<maxviews)";
		$commands[] = "startdate < $time";
		$commands[] = "(enddate > $time || enddate=0)";
		$commands[] = "bannerclients.id=clientid";
		$commands[] = "(`limit`='none' || (`limit`='views' && credits>0) || (`limit`='time' && credits > $time))";

		if($userData['loggedIn']){
			$commands[] = "(sex='unknown' || sex='$userData[sex]')";
			$commands[] = "((minage='0' && maxage='0') || (minage<='$userData[age]' && (maxage=0 || maxage>='$userData[age]')))";
		}else{
			$commands[] = "sex='unknown'";
			$commands[] = "(minage='0' && maxage='0')";
		}

		$query = "SELECT banners.id,weight,link,alt,status,bannertype,clientid,image,`limit`,bannersize FROM banners,bannerclients WHERE " . implode(" && ", $commands);

		$result = $fastdb->query($query);

		if($fastdb->numrows($result)==0)
			return "";

		$totalweight=0;

		while($line = $fastdb->fetchrow($result)){
			$banners[]=$line;
			$totalweight+=$line['weight'];
		}

		randomize();
		$val = rand(1,$totalweight);

		$i=-1;
		while($val>0){
			$i++; //can't post increment, leads to an $i out of range.
			$val-=$banners[$i]['weight'];
		}
		$banner = $banners[$i];
	}else{
		$fastdb->prepare_query("SELECT id,weight,link,alt,status,'time' as `limit`,bannertype,clientid,image,bannersize FROM banners WHERE id = ?", $size);
		if($fastdb->numrows()==0)
			return "";
		$banner = $fastdb->fetchrow();
	}

	if($banner['limit']!='time'){
		$fastdb->prepare_query("UPDATE banners,bannerclients SET views = views+1, credits = credits-1 WHERE banners.clientid = bannerclients.id && banners.id = ?", $banner['id']);
	}else{
		$fastdb->prepare_query("UPDATE banners SET views = views+1 WHERE id = ?", $banner['id']);
	}

	if(substr($banner['image'],0,7)=="http://" || $banner['bannertype']=='html')
		$picName = $banner['image'];
	else
		$picName = $config['bannerloc'] . $banner['image'];


	if($size!='text'){
		$width = substr($banner['bannersize'],0,strpos($banner['bannersize'],'x'));
		$height = substr($banner['bannersize'],strpos($banner['bannersize'],'x')+1);
	}


	switch($banner['bannertype']){
		case "image":
			if($banner['link']=="")
				return "<img src=\"$picName\"" . ($banner['alt']=="" ? "" : " alt=\"$banner[alt]\"" ) . ">"; //  width=$width height=$height
			else
				return "<a href=\"bannerclick.php?id=$banner[id]\" target=_blank" . ($banner['status']=="" ? "" : " onmouseover=\"window.status='$banner[status]';return true;\" onmouseout=\"window.status=' ';return true;\"" ) . "><img src=\"$picName\" border=0" . ($banner['alt']=="" ? "" : " alt=\"$banner[alt]\"" ) . "></a>";
		case "flash":
			$link = "";
			if($banner['link']=="")
				$link = "bannerclick.php?id=$banner[id]";
			return "<script src=$config[imgserver]/skins/banner.js></script><script>flashbanner('$picName', '$banner[alt]', '$link', 468, 60, '#000000');</script>";
		case "iframe":
			return "<iframe src='$picName' width=$width height=$height frameborder=no border=0 MARGINWIDTH=0 MARGINHEIGHT=0 SCROLLING=no></iframe>";

		case "text":
			if($banner['link']=="")
				return "<b>$banner[image]</b></a><br>$banner[alt]";
			else
				return "<a class=$linkclass href=\"bannerclick.php?id=$banner[id]\" target=_blank" . ($banner['status']=="" ? "" : " onmouseover=\"window.status='$banner[status]';return true;\" onmouseout=\"window.status=' ';return true;\"" ) . ">$banner[image]</a><br>$banner[alt]";
		case "html":
			return $picName;
	}
}
