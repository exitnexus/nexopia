<?php

    class html_sanitizer
    {
        public static $allowed_html = array(  'DIV'           => array(1 => 'class',2 => 'align'),
                                    'BLOCKQUOTE'    => array(1=>'class'),
                                    'FONT'          => array(1=>'class',2=>'face','size', 'color'),
                                    'P'             => array(1=>'class'),
                                    'EM'            => array(1=>'class'),
                                    'STRONG'        => array(1=>'class'),
                                    'IMG'           => array(1=>'class', 2=>'src',3=>'alt', 4=>'width', 5=>'height'),
                                    'A'             => array(1=>'class', 2=>'href', ),
                                    'BR'            => array(),
                                    'HR'            => array(),
                                    'BODY'          => array(),
                                    'U'             => array(),
                                    'OL'			=> array(),
                                    'LI'			=> array(),
                                    'UL'			=> array(),
                                    'SUP'			=> array(),
                                    'SUB'			=> array()
                                    );
        private static $blank_elements = "|HR|BR|";

        static function sanitize($html)
        {
            return removeHTML($html); // short circuit to remove html parsing from the site. Remove this line to reenable.

            $dom = new DomDocument();
            if(trim($html) != '')
            {
               	$html = html_sanitizer::validatehtml($html);
			    $html = utf8_encode($html);
                $html = '<head><meta http-equiv="Content-Type" content="text/html; charset=utf-8"/></head>'."<br/>".$html ; //the br is a hack so that loadHTML does not add p tags around stuff\

                if(@$dom->loadHTML($html))
                {
                    $body = $dom->getElementsByTagName('body')->item(0);
                    $new_html_str = "";
                    html_sanitizer::validatestruct($body, $new_html_str );
                    $new_html_str = substr($new_html_str, 5); //removes the previously added br tag

                    return $new_html_str;
                }
            }
            else
            {
                return null;
            }
        }

		/*
		 * This function takes all invalid html and htmlentities it so that
		 * the loadHTML function of the dom document cannot ignore the invalid
		 * html.
		 */
		static function validatehtml($html)
        {
        	$intag = false;
        	$i = -1;
        	$curr_tag = '';
        	$return_html = '';
        	while( ++$i < strlen($html))
	   		{
	   			$cur_char = $html{$i};
				if($intag)
				{
					if($cur_char == '<' && $intag == true)
					{
						$return_html .= htmlentities($curr_tag);
						$curr_tag = '';
					}
					$curr_tag .= $cur_char;
					if($cur_char == '>' && $intag == true)
					{
						$return_html .= $curr_tag;
						$curr_tag = '';
						$intag = false;
					}

				}
				else
				{
					if($cur_char == '<' && $intag == false)
					{
						$intag = true;
						$curr_tag .= $cur_char;
					}
					else
						$return_html .= $cur_char;
				}



			}

			if(strlen($curr_tag) > 0 && $intag)
				$return_html .= htmlentities($curr_tag);

			return $return_html;

		}
		static function validatestruct($element, &$html)
        {

            if(isset(html_sanitizer::$allowed_html[strtoupper($element->nodeName)]))
                $allowed_attributes = html_sanitizer::$allowed_html[strtoupper($element->nodeName)];
            else
                $allowed_attributes = null;

            //if node has no value return false.


            if($element->nodeName == '#text')
            {
                $text = str_replace('&nbsp;', '',htmlentities(utf8_decode($element->nodeValue)));
                if(isset($text) && trim($text) != "")
                {

                    $html .= $text;
                    return true;
                }
                else
                    return false;
            }
            else
            {
                    if(!$element->childNodes && (!isset($element->nodeValue) ||(isset($element->nodeValue) && trim($element->nodeValue) == "") && strpos(html_sanitizer::$blank_elements, strtoupper($element->nodeName)) === false)){

                        return false;
                    }
            }
           $current_html = '';
            if(is_array($allowed_attributes))
            {

                if(strtoupper($element->nodeName) != "BODY" )
                {

                    $current_html .= "<".$element->nodeName;
                    $attributes = $element->attributes;

                    $allowed_attributes = array_flip($allowed_attributes);
                    foreach($attributes as $attribute)
                    {
                        if(isset($allowed_attributes[strtolower($attribute->nodeName)]))
                        {
                            if(strtolower($attribute->nodeName) == 'href' || strtolower($attribute->nodeName) == 'src')
                                $current_html .= " " . $attribute->nodeName . " = '". htmlentities(forumcode_safeurl($attribute->nodeValue))."' ";
                            else
                                $current_html .= " " . $attribute->nodeName . " = '". htmlentities($attribute->nodeValue)."' ";
                        }
                    }
                    if(strpos(html_sanitizer::$blank_elements, strtoupper($element->nodeName)))
                        $current_html .= "/>";
                    else
                        $current_html .= ">";
                }

                $child_html = "";
                if ($element->childNodes)
                {
                    $empty_count = 0;
                    foreach ($element->childNodes as $child)
                    {

                        if(!html_sanitizer::validatestruct($child, $child_html))
                        {
                            $empty_count++;
                        }
                    }

                }

                if($element->childNodes->length == 0 || $empty_count != $element->childNodes->length)
                    $current_html .= $child_html;

                if (strpos(html_sanitizer::$blank_elements, strtoupper($element->nodeName)) === false && strtoupper($element->nodeName) != "BODY" && $element->childNodes->length != 0 && $empty_count != $element->childNodes->length )
                    $current_html .=  "</".$element->nodeName.">";
                elseif( $element->childNodes->length != 0 && $empty_count == $element->childNodes->length )
                {
                    if(strtoupper($element->nodeName) == 'P')
                    {
                        $current_html = "<br>";
                        $html .= $current_html;
                        return true;
                    }
                    else
                        $current_html = "";
                    return false;
                }

            }
            $html .= $current_html;
            return true;
        }
    }

