

var basetop = 0;
var baseleft = 0;
var w = 20;
var h = 20;

/////////////////

var t=0;
var l=0;
var section=0;
var step=0;

function formatColor(i,j,k){
	return (convert(i)+convert(j)+convert(k));
}

function convert(i){
	switch(i){
		case 0:  return "00";
		case 1:  return "11";
		case 2:  return "22";
		case 3:  return "33";
		case 4:  return "44";
		case 5:  return "55";
		case 6:  return "66";
		case 7:  return "77";
		case 8:  return "88";
		case 9:  return "99";
		case 10: return "AA";
		case 11: return "BB";
		case 12: return "CC";
		case 13: return "DD";
		case 14: return "EE";
		case 15: return "FF";
	}
}

function pos(){
	if(section==0 || section==2){
		if(l>=6){
			l=0;
			t++;
		}
	}else if(section==1){
		if(l<=-1){
			l=5;
			t++;
		}
	}else if(section==3 || section==5){
		if(l>=6){
			l=0;
			t--;
		}
	}else if(section==4){
		if(l<=-1){
			l=5;
			t--;
		}
	}

	var ret = "top:"+ ((t + basetop)*h) +"px; left:"+ (((l+baseleft) * w)+((section%3)*w*6)) + "px; ";

	if(section==1 || section==4){
		l--;
	}else{
		l++;
	}

	if(++step >= 36){
		step=0;
		++section;
		if(section<=2){
			t=0;
		}else if(section<=5){
			t=11;
		}else{ //section = 6
			t=12;
		}
		if(section==1 || section==4){
			l=5;
		}else{
			l=0;
		}
	}
	return ret;
}

function displayColors(){
	for(i=0;i<16;i+=3){
		for(j=0;j<16;j+=3){
			for(k=0;k<16;k+=3){
				color=formatColor(i,j,k);
				document.write("<DIV STYLE=\"position:absolute; background-color: #" + color + "; width:" + w + "px; height:" + h + "px; " + pos() + "\" onMouseOver=\"mouseOver('" + color + "')\" onClick=\"mouseClick('" + color + "')\"></DIV>");
			}
		}
	}
	for(i=0;i<16;i+=1){
		color=formatColor(i,i,i);
		document.write("<DIV STYLE=\"position:absolute; background-color: #" + color + "; width:" + w + "px; height:" + h + "px; " + pos() + "\" onMouseOver=\"mouseOver('" + color + "')\" onClick=\"mouseClick('" + color + "')\"></DIV>");
	}
	document.write("<DIV ID=mover STYLE=\"position:absolute; width:180px; height:20px; top: " + (h*(12+basetop+1)) + "px; left: " + (w*(baseleft)) + "px; font-size: " + Math.round(w*0.75) + "pt\">Hover: #FFFFFF</DIV>");
	document.write("<DIV ID=mclick STYLE=\"position:absolute; width:180px; height:20px; top: " + (h*(12+basetop+1)) + "px; left: " + (w*(baseleft+9)) + "px; font-size: " + Math.round(w*0.75) + "pt\">Click: #FFFFFF</DIV>");
}

function mouseOver(customColor){
	putinnerHTML('mover', "Hover: #"+customColor);
}

function mouseClick(customColor){
	putinnerHTML('mclick', "Click: #"+customColor);
}

function putinnerHTML(div,str){
	if(document.all){
		document.all[div].innerHTML = str;
	}else{
		eval("document.getElementById('" + div + "').innerHTML = str;");
	}
}

