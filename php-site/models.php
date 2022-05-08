<?	

	$login=1;

	require_once("include/general.lib.php");

$models = array("babygirl",
"chinitapnay",
"ETOWNLMX",
"hockeybabe14",
"ExoticCherry",
"AngelinaRay",
"Vixen",
"crème",
"DancinAngel",
"bANAners",
"BabyBoo17",
"foxychica11",
"STEMMY",
"temptation69",
"porcelain",
"rubberdukie",
"Rebecca",
"polishprncs",
"AspenRain",
"deethacutie");

$subject = "Enternexus Model Invitation";
$message = "Hi, 
 
Enternexus would like to invite you to be a model for us at a local Edmonton event called V8less Nights (Urban Battles). It is the largest import car show and urban scene exhibition in Alberta. Enternexus will have a booth there promoting our community and we wish to represent ourselves. 
 
You will be provided:  
- Enternexus Racer-Back Tank Top  
- Admission to the show  
- Enternexus forum avatar title: EN Model 
 
We will need a 100% commitment and guarantee that you'll attend the event. Transportation can be provided to and from the event. For information about the event go to [url]http://www.v8less.com[/url]. 
 
Please reply as soon as possibly via private message, or email ([email]webmaster@enternexus.com[/email])
 
Thank you. 
 
 
Timo Ewalds 
Enternexus Administration";

foreach($models as $model)
	deliverMsg(getUserID($model),$subject,$message);
