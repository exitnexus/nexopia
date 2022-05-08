<?

	$login=0;

	require_once("include/general.lib.php");

	if(empty($key))
		die("Error");

	$text = capchatext($key);

	$font = 5;
	$padding = 3;
	$border = 1;
	$textwidth = (strlen($text)*ImageFontWidth($font))-1;
	$textheight = ImageFontHeight($font);

	$picX = $textwidth+$border*2+$padding*2;
	$picY = $textheight+$border*2+$padding*2;

	$destImg = ImageCreateTrueColor($picX, $picY );

	$white = ImageColorClosest($destImg, 255, 255, 255);
	$black = ImageColorClosest($destImg, 0, 0, 0);


	ImageRectangle($destImg,0,0,$picX-$border,$picY-$border,$black);
	ImageFilledRectangle($destImg,$border,$border,$picX-$border*2,$picY-$border*2,$white);
	ImageString($destImg, $font, $border+$padding, $border+$padding, $text, $black);

	header("Content-type: image/png");
	imagepng($destImg);


