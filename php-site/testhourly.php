<?

	if($DOCUMENT_ROOT==""){ //from commandline
		if(substr(dirname($PATH_TRANSLATED),0,1)=='/')
			$DOCUMENT_ROOT = dirname($PATH_TRANSLATED);
		else
			$DOCUMENT_ROOT = $PWD . "/" . dirname($PATH_TRANSLATED);
	}

	require_once("include/general.lib.php");

echo "done";
