// JavaScript Document

var correct;
var still_valid = true;

document.getElementById("answer_buttons").style.display = "inherit";
document.getElementById("continue_button").style.display = "none";
document.getElementById("error_container").style.display = "none";


function addToScore() {
	var url = unescape(window.document.location);
	
	if(url.indexOf("?") > -1)
	{
		var list1 = url.split("?");
		var list2 = list1[1].split("=");
		
		for(var i=0;i<list2.length;i+=2)
		{
			if(list2[i] == "correct")
			{
				correct = parseInt(list2[i+1], 10);
			}
		}
	}
	else
	{
			correct = 0;
	}
	
	if(still_valid)
	{
		correct = correct + 1;
	}
}


function wrongAnswer() {
	still_valid = false;
	
	document.getElementById("answer_buttons").style.display = "none";
	document.getElementById("continue_button").style.display = "block";
	document.getElementById("error_container").style.display = "block";
}


function scoreContinue() {
	var url = unescape(window.document.location);
	
	if(url.indexOf("?") > -1)
	{
		var list1 = url.split("?");
		var list2 = list1[1].split("=");
		
		for(var i=0;i<list2.length;i+=2)
		{
			if(list2[i] == "correct")
			{
				correct = parseInt(list2[i+1], 10);
			}
		}
	}
	else
	{
			correct = 0;
	}
}


function finalScore() {
	var url = unescape(window.document.location);
	
	if(url.indexOf("?") > -1)
	{
		var list1 = url.split("?");
		var list2 = list1[1].split("=");
		
		for(var i=0;i<list2.length;i+=2)
		{
			if(list2[i] == "correct")
			{
				correct = parseInt(list2[i+1], 10);
			}
		}
	}
	
	var score_display = document.getElementById("final_score");
	score_display.innerHTML = correct;
}