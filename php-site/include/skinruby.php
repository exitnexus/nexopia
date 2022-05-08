<?

function openCenter($width = true){
	echo "<table cellpadding=3 cellspacing=0 width=" . ($width === true ? "100%" : "$width align=center" ) . " style=\"border-collapse: collapse\" border=1 bordercolor=#000000>";
	echo "<tr><td class=body>";
}

function closeCenter(){
	echo "</td></tr></table>";
}

function createHeader(){} //exists purely so a race condition at login time doesn't put stuff in the error log. It doesn't get used anyway.

function incHeader($incCenter=true, $incLeftBlocks=false, $incRightBlocks=false, $skeleton=false, $modules=array()){
	if($incLeftBlocks){
		header("X-LeftBlocks: " . implode(",", $incLeftBlocks));
	}
	if($incRightBlocks){
		header("X-RightBlocks: " . implode(",", $incRightBlocks));
	}
	if($incCenter)
		header("X-Center: $incCenter");
	// end incBlocks

		
							

}

function incFooter(){
}


