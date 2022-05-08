<?php

//	$login=0;
	include("include/general.lib.php");

set_time_limit(60);

$algorythm = getREQval('algorythm','int');

if(!$algorythm){
?>
<table><tr>
<td bgcolor=#FF0000><img src="colorwheel.php?algorythm=1"></td>
<td bgcolor=#FF0000><img src="colorwheel.php?algorythm=2"></td>
<td bgcolor=#FF0000><img src="colorwheel.php?algorythm=3"></td>
</tr>
<tr>
<td bgcolor=#FF0000><img src="colorwheel.php?algorythm=4"></td>
<td bgcolor=#FF0000><img src="colorwheel.php?algorythm=5"></td>
<td bgcolor=#FF00FF><img src="colorwheel.php?algorythm=6"></td>
</tr></table>
<?
exit;
}

$size = 256; //must be divisible by 4
$bandsize = 0.2; //fraction of the radius that should be the colour wheel
$insidemargin = 5; //pixels from the wheel to the inside

$halfsize = round($size/2);
$quartersize = round($size/4);


$image=imagecreatetruecolor($size,$size);
imageAlphaBlending($image,FALSE);
imagesavealpha($image,TRUE);

// fill background for no reason
$white=imageColorAllocate($image,255,255,255);
imageFilledRectangle($image,0,0,$sizeS,$size,$white);
$black=imageColorAllocate($image,0,0,0);

// function adapted from the DHTML Color Calculator 
function hsb2rgb($h, $s, $b) {
	$max = round($b*51/20);
	$min = round($max*(1 - $s/100));
	if ($min == $max) return array($max, $max, $max);
	$d = $max - $min;
	$h6 = $h/60;
	if ($h6 <= 1) return array($max, round($min + $h6*$d), $min);
	if ($h6 <= 2) return array(round($min - ($h6 - 2)*$d), $max, $min);
	if ($h6 <= 3) return array($min, $max, round($min + ($h6 - 2)*$d));
	if ($h6 <= 4) return array($min, round($min - ($h6 - 4)*$d), $max);
	if ($h6 <= 5) return array(round($min + ($h6 - 4)*$d), $min, $max);
	return array($max, $min, round($min - ($h6 - 6)*$d));
}

//make the wheel

for ($k=0; $k<=$size; $k++) {
  for ($j=0; $j<=$size; $j++) {
    $x = $j - $halfsize;
    $y = $halfsize - $k;
    $x2 = $x * $x;
    $y2 = $y * $y;
    $xs = ($x < 0)?-1:1;
    $ys = ($y < 0)?-1:1;
    $xn = $x/$halfsize; //normalize x
    $rr = sqrt($x2 + $y2); //raw radius
    $rn = $rr/$halfsize; //normalized radius
    $r = $rn;
    $ar = acos($x/$rr); //angle in radians 
    $arc = ($y>=0)?$ar:pi()-$ar+pi();  //correct below axis
    $ad = rad2deg($arc);  //convert to degrees
    $a = $ad;
    if ($x == 0 & $y == 0) {
		$rgb = array(0,0,0); // same as $r == 0
    }elseif ($rn > 1){
    	$color=imageColorAllocate($image,204,204,204);
	}elseif($rn < 1 - $bandsize) {// white area
		if($k > $size/2)
			$color=imageColorAllocate($image,255,255,255);
		else
			$color=imageColorAllocate($image,0,0,0);
	}else{
	    $rgb = hsb2rgb($a,100,100);
	    $color=imageColorAllocate($image,$rgb[0],$rgb[1],$rgb[2]);
	}
    imageSetPixel($image,$j,$k,$color);
  }
}


$radius = round($size*(1 - $bandsize)/2) - $insidemargin;

switch($algorythm){
	case 1: //square
		$startx = $starty = round($size/2 - $radius*cos(45/180*M_PI));
		$endx =   $endy = round($size/2 + $radius*cos(45/180*M_PI));
	
		$sqsize = $endx - $startx;
	
		// make the transparent SV square:  0=opaque 127=transparent
		for ($k=0; $k<=$sqsize; $k++) {
			for ($j=0; $j<=$sqsize; $j++) {
				$x = $j + $startx; 
				$y = $k + $starty;
				$grey = round(255*(1 - $k/$sqsize)); //ranges from white at the top to black at the bottom
				
				$trans = round(127*($j/$sqsize * (1 - $k/$sqsize)));
				
				$color=imageColorAllocateAlpha($image,$grey,$grey,$grey,$trans);
				imageSetPixel($image,$x,$y,$color);
			}
		}
		break;

	case 2: //triangle
	case 3:
	case 4: 
	case 5:
	case 6:	
		$startx = round($size/2 - $radius*cos(30/180*M_PI));
		$endx = round($size/2 + $radius*cos(30/180*M_PI));
		
		$starty = $halfsize - round($radius/2);
		$endy = $halfsize + $radius;
		
		$length = $endx - $startx;
		$height = round(3*$radius/2);
		
		//die("rad: $radius, len: $length, height: $height");

		for($y = 0; $y <= $height; $y++) {
		
			$linewidth = round($length*(1 - $y/$height));
			$linestart = round($length*(1 - $linewidth/$length)/2);
		
			for($x = 0; $x <= $linewidth; $x++) {
		
				switch($algorythm){
					case 2:
						$grey = round(255*(1 - ($y/$height)));
						$trans = round(127*($x/$linewidth));
						$color=imageColorAllocateAlpha($image, $grey, $grey, $grey, $trans);
						break;
					
					case 3:
						$grey = round(255*(1 - ($y/$height)));
						$trans = round(127*($x/$linewidth * (1 - $y/$height)));
						$color=imageColorAllocateAlpha($image, $grey, $grey, $grey, $trans);
						break;

					case 4:					
						$rgb = hsb2rgb(0, round(100*($x/$linewidth)), round(100*(1 - ($y/$height))));
						$color=imageColorAllocateAlpha($image, $rgb[1], $rgb[1], $rgb[1], round(($rgb[0]-$rgb[1])/2));
						break;

					case 5: //not transparent, just used as a reference
						$rgb = hsb2rgb(0, round(100*($x/$linewidth)), round(100*(1 - ($y/$height))));
						$color=imageColorAllocate($image, $rgb[0],$rgb[1],$rgb[2]);
						break;
					case 6:
						//$width =$ length;
						//$scaledx = (($linestart+$x)/$length);
						//$scaledy = ($y/$height);
						$d1 = (($length-($linestart+$x))*0.866 + $y*0.5) / ($radius*1.5);
						$d2 = (($linestart+$x)*0.5 + $y*0.866) / ($length);
						
						//$scaledx2 = (($linestart+$x)/$height);
						//$scaledy2 = ($y/$length);
						
						//$rgb = hsb2rgb(0, round(100*($x/$linewidth)), round(100*(1 - ($y/$height))));
						//$alpha = (0.866-((($linestart+$x)/($radius*1.73))*0.866) + (($y/$height)*0.5))*127;
						//$grey = 1.11-($scaledx*0.5 + $scaledy*0.866);
						//$grey=1-$scaledy;
						//$grey = 1;
						//$alpha = 1-(0.866-$scaledx*0.866 + $scaledy*0.5);
						//$alpha = pow($alpha,1.27);//-pow($alpha,3)+1.5*pow($alpha,2)+0.5*$alpha;
						//$alpha=0;
						//$grey = pow($grey,1);
						//$grey *=255/1.11;
						$alpha = (1-$d1) * 127;
						//$alpha = 0;
						$grey = (1-$d2) * 255;
						//$grey = 0;
						//(((($linestart+$x)/($radius*1.73))*0.866) + (($y/$height)*0.5))*255;
						//if ($grey < 0) die();
						$color=imageColorAllocateAlpha($image, $grey, $grey, $grey, $alpha);
						break;
					
				}
				
				imageSetPixel($image, $startx + $linestart + $x, $starty + $y, $color);
			}
		}
		break;
}
//(($radius*1.73-(($x+$linestart)/($radius*1.73)))*0.866025404 + ($y/$height)*0.5)*127;
//(($radius*1.5-(($x+$linestart)/($radius*1.5)))*0.866
// output PNG
header("Content-type: image/png");
imagePNG($image);
imageDestroy($image);
