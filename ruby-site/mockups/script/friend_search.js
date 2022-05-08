function friendSearch(name) {
	if (document.getElementById(name).style.visibility == "visible") {
		document.getElementById(name).style.visibility = "hidden";
	}else{
		document.getElementById(name).style.visibility = "visible";
	}
}