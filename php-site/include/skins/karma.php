<?

	$skindata = array();

//general
	$skindata['name']        = 'karma'; //name of the skin, used for ruby layer translation
	$skindata['skinWidth']   = "100%"; //width of the skin, 100% for full width, otherwise in pixels
	$skindata['cellspacing'] = 8;      //spacing between the center and blocks,
	$skindata['incCenter']   = true;   //have a border around the center
	$skindata['backgroundpic'] = "";   //background for the whole page, only useful if borders are specified below

//borders for the full page, width and colours
	$skindata['topBorderSize']    = 0;
	$skindata['topBorder']        = "";
	$skindata['leftBorderSize']   = 0;
	$skindata['leftBorder']       = "";
	$skindata['rightBorderSize']  = 0;
	$skindata['rightBorder']      = "";
	$skindata['bottomBorderSize'] = 0;
	$skindata['bottomBorder']     = "";

//floating logo for non-frames - floats right
	$skindata['floatinglogo'] = "";             //image to float
	$skindata['floatinglogovalign'] = "bottom"; //valign top or bottom

//non-frames header
	$skindata['headerpic'] = "karma_header.jpg"; //name of the header background (1600xVAR, assume only 750 width visible)
	$skindata['headerheight'] = 90;           //height of the header

//frames header
	$skindata['headersmall'] = "karma_header60.jpg"; //header for 800x600  users (1600x60, assume only 300x60 visible)
	$skindata['headerbig']   = "karma_header.jpg";   //header for 1024x768 users (1600x90, assume only 300x90 visible)
	$skindata['headerplus']  = "karma_header60.jpg"; //header for plus     users (1600x60, assume only 750x60 visible)
	$skindata['headernorepeat'] = "background-repeat: no-repeat;";
	$skindata['headerbackgroundcolor'] = "#5D5E59";
	
//menu
	$skindata['menupic'] = "#000"; //background for the menus, either a image or a colour (starting with #)
	$skindata['menuheight'] = 23;        //height of the menu (generally the pic from above)
	$skindata['menudivider'] = " | ";    //separater between menu items
	$skindata['menuspacer'] = "#5D5E59"; //separater between menus, either a image or a colour (starting with #)
	$skindata['menuspacersize'] = 1;     //size of the spacer
	$skindata['menugutter'] = "#ffffff"; //gutter below the top menu, either a image or a colour (starting with #)
	$skindata['menuguttersize'] = 0;     //size of the gutter
	$skindata['menuends'] = "";          //menu ends, two images, prefixed with 'left' and 'right', with this suffix

//body
	$skindata['mainbg'] = "karma_body.jpg"; //background image for the main body, if empty or a colour, use the one from the css
	$skindata['mainbgnorepeat'] = true;

//blocks
	$skindata['sideWidth'] = 130;                //width of the side blocks, in pixels
	$skindata['blockBorder'] = 0;                //border size of the side blocks, colour in the css
	$skindata['blockheadpic'] = "blockhead.png"; //block head, two images, prefixed with 'left' and 'right', with this suffix
	$skindata['blockheadpicsize'] = 30;          //size of the image
	$skindata['valignsideheader'] = "center";    //valign of the text in the block (bottom or center in general)

