function show_img_pane(elt){
	var pic = document.getElementById(elt);
	if (pic.style.display == "none")
		pic.style.display = "";
	else
		pic.style.display = "none";
};
