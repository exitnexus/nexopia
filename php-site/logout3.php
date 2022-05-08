<?
	if(isset($userid)){
		setCookie("userid",$userid,time()-10000000);
		setCookie("userid",$userid,time()-10000000,'/',"www.enternexus.com");
		setCookie("userid",$userid,time()-10000000,'/',".www.enternexus.com");
		setCookie("userid",$userid,time()-10000000,'/',"enternexus.com");
		setCookie("userid",$userid,time()-10000000,'/',".enternexus.com");
	}
	if(isset($key)){
		setCookie("key",$key,time()-10000000);
		setCookie("key",$key,time()-10000000,'/',"www.enternexus.com");
		setCookie("key",$key,time()-10000000,'/',".www.enternexus.com");
		setCookie("key",$key,time()-10000000,'/',"enternexus.com");
		setCookie("key",$key,time()-10000000,'/',".enternexus.com");
	}

	echo "Cookies cleared. Click <a href=/login.php>here to login</a>";
