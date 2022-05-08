<?
global $sitebasedir;

define("ERROR_MISSING_OPEN_TAG",  'End tag {$1} has no matching start tag', false);
define("ERROR_MISSING_CLOSE_TAG", 'Start tag {$1} has no matching end tag', false);
define("ERROR_INVALID_FUNCTION",  '$1 is not a valid function', false);
define("ERROR_INVALID_TAG",       '{$1} is not a valid template tag', false);
define("ERROR_MISSING_IF",        '$1 does not have a matching \'if\' tag');

define("STRT_DELIM",            "{", false);
define("END_DELIM",             "}", false);
define("COND_LOOP_IND",         ":", false);
define("FUNCTION_IND",          "|", false);
define("URLENCODE_IND",         "%", false);
define("HTMLENTITIES_IND",      "#", false);
define("COUNT_IND",             "@", false);
define("SLASHES_IND",           "&", false);
define("VARIABLE_IND",          "$", false);
define("PARSED_FILES_PATH",     "$sitebasedir/templates/compiled_files/" );
define("TEMPLATE_FILES_PATH",   "$sitebasedir/templates/template_files/");

function tmpl_switch($i, $arr)
{
	return $arr[ $i % count($arr) ];
}

class Template
{

	private $tmpl_str;
	private $vars;
	private $if_stack;
	private $loop_stack;
	private $errors;
	private $show_whitespace;
	private $parsed;
	private $parsed_str;
	private $tmpl_name_prefix;
	private $found_vars;
	private $allowed_functions = array(
	                                'uppercase'         =>  'strtoupper($1)',
//	                                'date'              =>  'date( $2, $1)',
	                                'lowercase'         =>  'strtolower($1)',
	                                'userdate'          =>  'userdate($2, $1)',
	                                'htmlformattext'    =>  'nl2br(smilies(parseHTML($1)))',
	                                'implode'           =>  'implode( $2, $1)',
	                                'alternate'         =>  '$1 = !$1',
	                                'assign'            =>  '$1 = $2',
	                                'truncate'          =>  'truncate($1, $2)',
	                                'number_format'     =>  'number_format($1,$2)',
	                                'count'             =>  'count($1)',
	                                'switch'			=>  'tmpl_switch($1, array($2, $3))',
	                                );

	//constructor
	function Template($filepath, $parsed = true)
	{
		if($parsed)
		{
			$this->tmpl_str = @file_get_contents(PARSED_FILES_PATH.$filepath.".parsed.php");
			if(!$this->tmpl_str )
			{
				$this->tmpl_str = @file_get_contents(TEMPLATE_FILES_PATH.$filepath.".html");
				$parsed=false;
			}
		}
		else
		{
			$this->tmpl_str = @file_get_contents(TEMPLATE_FILES_PATH.$filepath.".html");
		}

		if($this->tmpl_str === false)
			die("Template '$filepath' not found.");

		//$this->tmpl_str  = file_get_contents($filepath);
		$this->vars	       = array();
		$this->loop_stack  = array();
		$this->errors      = array();
		$this->if_stack    = array();
		$this->loop_stack  = array();
		$this->show_whitespace = true;
		$this->parsed       = $parsed;
		$this->tmpl_name_prefix = str_replace('/', '_', $filepath);
		$this->found_vars = array();

		$this->setMultiple(array(
			'THIS_PAGE' => $_SERVER['PHP_SELF']
		));
	}

	function show_whitespace($bool)
	{
		$this->show_whitespace = $bool;
	}


	//used to set a variable in the template
	function set($key, $value)
	{
		$this->vars[$this->tmpl_name_prefix ."_". $key] = $value;
	}


	//used to set multiple variables in the template
	//$vars should be an association array.
	function setMultiple($vars)
	{
		foreach($vars as $key => $value)
			$this->set($key, $value);
	}


	//primitive function -- does a dump of the interpreted php need to view source
	function dump()
	{
		$parsed = $this->parse($this->tmpl_str);
		$lines = explode("\n", $parsed);

		echo "<table><tr><td class=header colspan=2>This is a dump</td></tr>";

		$i = 1;
		foreach($lines as $line)
			echo "<tr><td class=header align=right> " . ($i++) . ": </td><td class=body><xmp>$line</xmp></td></tr>";
		echo "</table>";
		return;
	}

	function write($file_path)
	{
		$parsed_str = $this->parse($this->tmpl_str);

		$file_path =  PARSED_FILES_PATH.$file_path.".parsed.php";
		$dirs = explode('/', $file_path);
		$dir_path = "";
		foreach( $dirs as $dir )
		{
			if($dir == $dirs[count($dirs) -1])
				break;

			$dir_path .= $dir;
			if(!is_dir($dir_path) && $dir != ".." )
			{
				if(!mkdir($dir_path))
					echo "Cannot create directory (". $dir_path . ")";
			}
			$dir_path .= "/";
		}

		$handle = fopen($file_path, "wb");
		if (!$handle)
		{
			echo "Cannot open file (". $file_path . ")";
			exit;
		}



		if(fwrite($handle, $parsed_str) === FALSE)
		{
			echo "Cannot write to file (". $file_path . ")";
			exit;
		}
		fclose($handle);
	}


	//displayes the template
	function display(){
		echo $this->toString();
	}

	function toString()
	{
		timeline('start output');

		$this->parsed_str = "";
		if(!$this->parsed){
			$this->parsed_str = $this->parse($this->tmpl_str);
			timeline('compiled');
		}else{
			$this->parsed_str = $this->tmpl_str;
		}

		extract($this->vars);

		ob_start();
		eval('?'.'>'. $this->parsed_str);
		$content=ob_get_contents();

		ob_end_clean();

		timeline('done output');

		return $content;
	}

//returns a string of the template that can be echoed.
	function parse($str)
	{
		$is_php_code     = false;
		$is_php_comment  = false;
		$is_php_quotes   = false;
		$comment = 0;
		$quotes  = 0;
		$i       = 0;

		$strlen = strlen($str);
		$php_str = "";

		while($i < $strlen)
		{
			$current_char = $str{$i};
			$next_char = isset($str{$i+1}) ? $str{$i+1} : " ";
			$prev_char = isset($str{$i-1}) ? $str{$i-1} : " ";


			//evaluates to true when <? is found no in quotes or in comments
			if($current_char == '<' && $next_char == '?' && !$is_php_comment && !$is_php_quotes)
			{
				$is_php_code = true;
				$php_str .= '<'.'?';
				$i +=2;
				continue;
			}

			//evaluates to true when ? > is found no in quotes or in comments
			if($current_char == '?' && $next_char == '>' && !$is_php_comment && !$is_php_quotes)
			{
				$is_php_code = false;
				$php_str .= '?'.'>';
				$i +=2;
				continue;
			}


			if($is_php_code)
			{
				//deals with double quotes in php code
				if($current_char == '"' && $prev_char != '\\' && !$is_php_comment)
				{
					if( $is_php_quotes && $quotes == 2)
					{
						$quotes = 0;
						$is_php_quotes = false;
					}
					elseif(!$is_php_quotes && $quotes == 0)
					{
						$quotes = 2;
						$is_php_quotes = true;
					}
				}

				//deals with single quotes in php code
				if($current_char == '\'' && (!isset($str{$i-1}) || (isset($str{$i-1}) && $str{$i-1} != '\\') ))
				{
					if($is_php_quotes && $quotes == 1)
					{
						$quotes = 0;
						$is_php_quotes = false;
					}
					elseif(!$is_php_quotes  && $quotes == 0)
					{
						$quotes = 1;
						$is_php_quotes = true;
					}
				}

				//deals with beginning single line comments in php code
				if(!$is_php_comment && $current_char == '/' && (!isset($str{$i-1}) || (isset($str{$i-1}) && $str{$i-1} == '/')))
				{
					$is_php_comment = true;
					$comment = 1;
				}

				//deals with ending single line comments in php code
				if($is_php_comment && $comment == 1 && $current_char == "\n" )
				{
					$is_php_comment = false;
					$comment = 0;
				}

				//deals with starting multiple line comments in php code
				if(!$is_php_comment && $current_char == '*' && $prev_char == '/')
				{
					$is_php_comment = true;
					$comment = 2;
				}

				//deals with ending multiple line comments in php code
				if($is_php_comment && $comment == 2 && $current_char == '/' && $prev_char == '*' )
				{
					$is_php_comment = false;
					$comment = 0;
				}

				$php_str .= $str{$i++};

			}
			else //handling for non-php code
			{

				if($current_char == STRT_DELIM && $prev_char != '\\' )//a tempate tag has been detected
				{
					$pos_end_delim =  $this->strpos_end_delim(substr($str, $i+1), STRT_DELIM, END_DELIM); 		//find end tag
					$invalid_tag = ($pos_end_delim === false);

					if(!$invalid_tag)
					{

						$tag = substr($str, $i+1 ,($pos_end_delim)); //get contents of the tag

						$php_str .= $this->process_tag($tag);
						$i = $i + $pos_end_delim + 2 ;
					}
					else
					{
						$php_str .= $str{$i++};
					}
				}
				else
				{
					if($current_char == '\\' && $next_char == STRT_DELIM) 	//skips the \ character before an escaped opening tag so \{test} prints out as {test}
					{
						$i++;
					}
					elseif(!$this->show_whitespace && trim($str{$i}) == '')
					{
						$i++;
					}
					else
					{
						$php_str .= $str{$i++};
					}
				}
			}
		}

		foreach($this->if_stack as $value) 		//record all if tags that are missing the {endif} tag
			array_push($this->errors,str_replace('$1', $value, ERROR_MISSING_CLOSE_TAG));

		foreach($this->loop_stack  as $value) 	// record all loop tags that are missing the {endloop} tag
			array_push($this->errors,str_replace('$1', $value, ERROR_MISSING_CLOSE_TAG));

		if($this->has_errors())
		{
			$this->print_errors();
			die();
		}
		else
		{
			return $php_str;
		}
	}

	/*
	 * This function contains logic for determining what type of template tag the current
	 * template tag is and performing the appropriate logic.
	 */
	function process_tag($tag)
	{
		$php_str = "";
		$tag = trim($tag);
		$i = -1;
		$var_ind = URLENCODE_IND  . HTMLENTITIES_IND . VARIABLE_IND . COUNT_IND . SLASHES_IND ."!";

		//if and elseif tag
		if( ( $if_tag = substr($tag,0,2) ) == 'if' || ( $if_tag = substr($tag,0,6) ) == 'elseif')
		{
			$offset = ($if_tag == 'if') ? 2 : 6 ;

			$if = $this->process_if(trim(substr($tag,$offset)), $if_tag);

			if($if == null)
			{
				array_push($this->errors , str_replace('$1', $tag, ERROR_INVALID_TAG));
			}
			else
			{
				if(trim($if_tag) == 'if' )
				{
					array_push($this->if_stack, $tag);
				}
				else
				{
					if(count($this->if_stack) == 0)
					{
						array_push($this->errors , str_replace('$1', $tag, ERROR_MISSING_IF));
						$if = null;
					}
				}
				$php_str .= $if;
			}
		}

		//loop tag
		elseif(substr($tag,0,4) == 'loop')
		{
			$loop = $this->process_loop(trim(substr($tag,4)));
			if($loop != null)
			{
				array_push($this->loop_stack, $tag);
				$php_str .= $loop;
			}
			else
			{
				array_push($this->errors , str_replace('$1', $tag, ERROR_INVALID_TAG));
			}
		}

		elseif(substr($tag,0,6) == 'header')
		{

			$header = $this->process_header(trim(substr($tag, 6)));
			if($header != null)
			{
				$php_str .= $header;
			}
			else
			{
				array_push($this->errors , str_replace('$1', $tag, ERROR_INVALID_TAG));
			}

		}
		elseif($tag == "addRefreshHeaders")
		{
			$php_str .= "<? addRefreshHeaders(); ?".">";
		}
		elseif($tag == "footer")
		{
			$php_str .= "<? incFooter(); ?".">";
		}

		//else tag
		elseif($tag == "else")
		{
			if(count($this->if_stack) > 0)
			{
				$php_str .= "<? } else { ?".">";
			}
			else
			{
				array_push($this->errors , str_replace('$1', $tag, ERROR_MISSING_IF));
			}
		}

		//endloop or endif tag
		elseif($tag == "endif" || $tag == "endloop")
		{
			$popped = "";
			if($tag == "endif")
			{
				$popped = array_pop($this->if_stack);
			}
			else
			{
				$popped = array_pop($this->loop_stack);
			}

			if($popped == null)
			{
				array_push($this->errors , str_replace('$1', $tag, ERROR_MISSING_OPEN_TAG));
			}

			$php_str .= "<? }"."?".">";
		}
		//variable or function call
		elseif(strpos($var_ind, $tag{0}) !== false)
		{

			$processed_var = $this->process_var($tag,$is_output);

			if($processed_var == null)
				array_push($this->errors , str_replace('$1', $tag, ERROR_INVALID_TAG));
			elseif($is_output)
				$php_str .= '<?=' . $processed_var . '; ?'.'>';
			else
				$php_str .= '<? ' . $processed_var . '; ?'. '>';
		}
		//template comment, ignore it.
		elseif(substr(trim($tag),0,1) == '*')
		{
			$php_str = "";
		}
		else
		{
			array_push($this->errors , str_replace('$1', $tag, ERROR_INVALID_TAG));
		}

		return $php_str;
	}

	function process_header($tag)
	{
		if(trim($tag) == "")
			return "<? incHeader();?".">" ;

		if($tag{0} != ':')
			return null;
		else
			$tag = substr($tag, 1);

		$header_args = $this->explode_tag(" ",$tag, "(", ")");
		$args_count = 0;
		$width_value = "";
		$rightboxes_value = "";
		$leftboxes_value = "";
		$arg_num = 0;


		foreach($header_args as $value)
		{
			$value = trim($value);

			if($args_count % 3 == 1)
			{
				if( $value != "=")
					return null;
			}
			elseif($args_count % 3 == 0)
			{
				if($value == "width")
					$arg_num = 1;
				elseif($value == "leftblocks")
					$arg_num = 2;
				elseif($value == "rightblocks")
					$arg_num = 3;
				else
					return null;
			}
			else
			{
				$arg_value = "";

				if($this->is_literal($value))
					$arg_value = $value;
				elseif($value{0} == "(")
					$arg_value = $this->process_list($value);
				else
					$arg_value = $this->process_var($value);


				if($arg_value == null)
					return null;

				if($arg_num == 1)
					$width_value = $arg_value;
				if($arg_num == 2)
					$leftboxes_value = $arg_value;
				if($arg_num == 3)
					$rightboxes_value = $arg_value;
			}

			$args_count ++;
		}
		$return_str = "<? incHeader(";
		if($width_value != "" && $width_value != null)
		{
			$return_str .= $width_value;

			if( $leftboxes_value != ""  &&  $leftboxes_value != null )
			{
				$return_str .= ", ". $leftboxes_value;
				if( $rightboxes_value != ""  &&  $rightboxes_value != null )
				{
					$return_str .= ", ". $rightboxes_value;
				}
			}

		}
		$return_str .= "); ?".">";
		return $return_str;


	}

	function process_list($list)
	{

		if($list{0} != "(" || $list{strlen($list) - 1} != ")" )
			return null;
		$list_items = $this->explode_tag(",",substr($list,1,strlen($list) - 2));
		$php_list = "array( ";
		foreach($list_items as $item)
		{
			if ($this->is_literal($item))
				$php_list .= $item.", ";
			else
			{
				$var_value = $this->process_var($item);
				if($var_value == null)
					return null;
				$php_list .= $var_value.", ";
			}

		}
		$php_list = substr($php_list, 0, strlen($php_list) - 2); //trim trailing ', '
		return $php_list . ")";
	}

	/*
	 * This function returns the php interpritation of the template variable tag.
	 */
	function process_var($tag, &$is_output = true)
	{
		$tag = trim($tag);
		$php_var = "";
		$is_output = true;
		$validprefixchar    = URLENCODE_IND . HTMLENTITIES_IND . VARIABLE_IND . COUNT_IND . SLASHES_IND;
		$validvarfirstchar  = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ";
		$validvarchars      = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789_";

		$in_array_element   = false;
		$var_started        = false;
		$var_finished       = false;

		$function_call_strt_pos = 0;
		$var                    = "";
		$i                      = -1;
		$htmlentities           = false;
		$urlencode              = false;
		$countfunc              = false;
		$is_equal_stmt          = false;
		$addslashes             = false;
		$in_quotes              = 0;
		$is_increment_stmt		= false;
		$is_decrement_stmt		= false;

		while(++$i < strlen($tag))
		{

			//Handling whitespace
			if( trim($tag{$i}) == "")
			{
				//if a variable has been started but not finished then finish the variable
				if($var_started && !$var_finished)
					$var_finished = true;
				//otherwise ignore the blank.
				elseif($var_finished)
					$var .= " ";
			}
			//Handling a funciton call
			elseif($var_started  && $tag{$i} == '|')//is a function call
			{
				$var_finished = true;
				$function_call_strt_pos = $i;
				break;
			}
			elseif($var_started && $tag{$i} == "=")
			{

				$var_finished = true;
				$is_equal_stmt = true;
				break;
			}
			elseif($var_started && isset($tag{$i+1}) && $tag{$i+1} == '=' && ($tag{$i} == '+' || $tag{$i} == '-'))
			{
				if($tag{$i} == '+')
					$is_increment_stmt = true;
				else
					$is_decrement_stmt = true;
				$var_finished = true;
				break;
			}
			//Handle array call e.g. $array[1]
			elseif( $var_started && !$in_quotes && $tag{$i} == '[')
			{
				$delim_end_pos = $this->strpos_end_delim(substr($tag, $i+1), "[", "]");
				if($delim_end_pos === false)
					return null;

				$element = substr($tag, $i+1, $delim_end_pos);

				//checks if element is a literal or a var and applies approriate logic
				if (!$this->is_literal($element))
				{
					$processed_var = $this->process_var($element);
					if($processed_var == null)
						return null;
					$var .= "[" . $processed_var . "]";
				}
				else
				{
					$var .= "[".$element."]";
				}

				$i += $delim_end_pos + 1 ;

			}
			elseif($var_started && !$in_quotes && $tag{$i} == '-' && isset($tag{$i + 1}) && $tag{$i + 1} == '>')
			{
				$var .= $tag{$i}.$tag{++$i};
			}
			//Handles !(not) operator
			elseif(!$var_started && $tag{$i} == '!')
			{
				$var .= $tag{$i};
			}
			//startes a variable if one has not been started and if the prefix and first char is valid
			elseif(!$var_started && strpos($validprefixchar,$tag{$i}) !== false && isset($tag{$i+1}) && strpos($validvarfirstchar, $tag{$i+1}) !== false)
			{

				if(	$tag{$i} == HTMLENTITIES_IND)
					$htmlentities = true;
				if( $tag{$i} == URLENCODE_IND )
					$urlencode = true;
				if( $tag {$i} == SLASHES_IND )
					$addslashes = true;
				if( $tag {$i} == COUNT_IND )
					$countfunc = true;
				$var_started = true;
				$var .= '$'.$this->tmpl_name_prefix."_" ;
			}
			//if a variable has been started and not finished and the current char is valid then append to the variable
			elseif($var_started && !$var_finished && strpos($validvarchars, $tag{$i})  !== false)
			{
				$var .= $tag{$i};
			}
			else
			{
				return null;
			}
		}


		if($urlencode)//urlencode
		{
			$php_var .= 'urlencode(';
			$php_var .= $var;
			$php_var .= ')';
		}

		elseif( $htmlentities )//htmlentities
		{
			$php_var .= 'htmlentities(';
			$php_var .= $var;
			$php_var .= ')';
		}
		elseif( $addslashes )
		{
			$php_var .= 'addslashes(';
			$php_var .= $var;
			$php_var .= ')';
		}
		elseif( $countfunc)
		{
			$php_var .= 'count(';
			$php_var .= $var;
			$php_var .= ')';
		}
		else
		{
			$php_var = $var;
		}

		if($is_equal_stmt)
		{
			$vars = explode('=', $tag);
			if(count($vars) != 2)
				return null;
			if($this->is_literal($vars[1]))
				$php_var = "$var = ". $vars[1];
			else
			{
				$var2 = $this->process_var($vars[1]);
				if($var2 == null)
					return null;
				$php_var = "$var = ". $var2;
			}
			$is_output = false;
		}
		elseif($is_increment_stmt || $is_decrement_stmt )
		{

			$vars = $is_increment_stmt ?  explode('+=', $tag) : explode('-=', $tag);
			if(count($vars) != 2)
				return null;
			$expr =  $is_increment_stmt ? "+=":"-=";
			if($this->is_literal($vars[1]))
				$php_var = "$var ". $expr . $vars[1];
			else
			{
				$var2 = $this->process_var($vars[1]);
				if($var2 == null)
					return null;
				$php_var = "$var ". $expr. $var2;
			}
			$is_output = false;

		}

		//handles functions
		if($function_call_strt_pos > 0)
		{
			$function_call = $this->process_function(substr($tag, $function_call_strt_pos + 1), $php_var) ;
			if($function_call == null)
				return null;
			else
				return $function_call;
		}
		else
		{
			return $php_var;
		}

	}

	//limited to one variable only
	//can pass an array of $vars in the future.
	function process_function($tag, $var)
	{
		if($var == null)
			return null;

		$function_array = $this->explode_tag(',', $tag);
		$func_tmpl = $this->allowed_functions[trim($function_array[0])]; //looks up the function
		$func_str = "";

		//if function is a valid function
		if($func_tmpl != null)
		{
			$func_str = str_replace("$1", $var, $func_tmpl); //replace place holder for variable

			//replace all place holders with their values
			for( $i = 1; $i < count($function_array); $i++)
			{
				if($this->is_literal($function_array[$i]))
				{
					$func_str = str_replace("$".($i + 1), $function_array[$i], $func_str);
				}
				else
				{
					$variable = $this->process_var($function_array[$i]);
					if($variable == null)
					{
						$func_str = null;
						break;
					}
					$func_str = str_replace("$".($i + 1), $variable, $func_str);
				}
			}
		}
		else
		{
			$func_str = null;
			array_push($this->errors , str_replace('$1', $function_array[0], ERROR_INVALID_FUNCTION));
		}

		return $func_str;
	}

	//processes loop conditions
	function process_loop($tag)
	{

		$php_str    = 'foreach (';
		$loop_cond  = $tag;
		$as_pos     = strpos($loop_cond, ' as' ) + 1;
		$arrow_pos  = strpos($loop_cond, '=>');
		$comma_pos  = strpos($loop_cond, ',');
		$array_var = "";
		$key_var = "";
		$value_var = "";
		$valid_array_cond = true;
		$tag = trim($tag);
		$counter_var = null;

		if($loop_cond{0} != ':')
		{
			$valid_array_cond = false;
		}
		else
		{
			$loop_cond = substr($tag, 1);
		}

		if($as_pos !== false)
		{

			$array_var = $this->process_var(substr($loop_cond,0, $as_pos - 1));

			if($array_var != null and $arrow_pos !== false)
			{

				$key_var =  $this->process_var(substr($loop_cond, $as_pos + 1, $arrow_pos - ( $as_pos + 2) ));
				if($comma_pos !== false && $comma_pos > $arrow_pos)
				{
					$value_var = $this->process_var(substr( $loop_cond, $arrow_pos + 2, $comma_pos - ($arrow_pos + 3)));
					$counter_var = $this->process_var(substr( $loop_cond, $comma_pos + 1, strlen($loop_cond)-($comma_pos +1)));
					if($counter_var == null) {
						$valid_array_cond = false;
					}
					else
						$php_str = "$counter_var = -1; " . $php_str;
				}
				else
				{
					$value_var = $this->process_var(substr( $loop_cond, $arrow_pos + 2, strlen($loop_cond) - ($arrow_pos + 2) ));
				}
				if($value_var != null && $key_var != null)
				{
					$php_str .= $array_var . " as " . $key_var . " ="."> " . $value_var;
				}
				else
				{
					$valid_array_cond = false;
				}
			}
			elseif($array_var != null)
			{
				if($comma_pos !== false && $comma_pos > $as_pos)
				{
					$value_var = $this->process_var(substr( $loop_cond, $as_pos + 2, $comma_pos - ($as_pos + 3)));

					$counter_var = $this->process_var(substr( $loop_cond, $comma_pos + 1, strlen($loop_cond)-($comma_pos +1)));

					if($counter_var == null) {
						$valid_array_cond = false;
					}
					else
						$php_str = "$counter_var = -1; " . $php_str;
				} else {
					$value_var = $this->process_var(substr($loop_cond, $as_pos + 2, strlen($loop_cond) - ( $as_pos + 2) ));
				}

				if($value_var != null) {
					$php_str .= $array_var . " as " . $value_var;
				} else {
					$valid_array_cond = false;
				}
			}
		}
		else
		{
			$valid_array_cond = false;
		}

		if($valid_array_cond)
		{
			if($counter_var != null)
				return "<?".$php_str . "){ $counter_var ++; ?".">";
			else
				return "<?".$php_str . "){ ?".">";
		}
		else
		{
			array_pop($this->loop_stack);
			return null;
		}
	}

	function process_if($tag, $if_ind)
	{
		$php_str = "<? ";
		$php_str .= ($if_ind == 'elseif') ? "}" : "";
		$php_str .=  $if_ind."( " ;
		$tag = trim($tag);

		if($tag{0} != ':')
		{
			$valid_array_cond = false;
		}
		else
		{
			$tag = substr($tag, 1);
		}

		$condition_array = preg_split("/\s+/", trim($tag)); //split on whitespace

		if(count($condition_array) != 3 && count($condition_array) != 1)
		{
			return null;
		}
		else
		{
			$i = -1;
			while(++$i < count($condition_array))
			{
				if($i == 0 || $i == 2)
				{

					if($this->is_literal($condition_array[$i]))
					{
						$php_str .= $condition_array[$i];
					}
					else
					{
						$var = $this->process_var($condition_array[$i]);
						if($var == null)
							return null;
						$php_str .= $var;
					}
				}
				else
				{

					$op = $condition_array[$i];
					if($op == '==' || $op == '!=' || $op == ">" || $op == '<' || $op == "<=" || $op == ">=" || $op == "===" || $op == "!==")
					{
						$php_str .= " ".$op." ";
					}
					else
					{
						return null;
					}

				}
			}
		}

		return $php_str . "){ ?" . ">";
	}

	function has_errors()
	{
		return (count($this->errors) > 0);
	}

	function print_errors()
	{
		echo "The following errors have occured <br>";
		foreach ($this->errors as $error)
		{
			echo $error. "<br>";
		}
	}

	function strpos_end_delim($str, $strt_delim, $end_delim)
	{
		$i = -1;

		$quotes = 0;
		$stack_delims = array();

		while( ++$i < strlen($str))
		{
			if($str{$i} == "'" && (!isset($str{$i-1}) ||  (isset($str{$i-1}) && $str{$i-1} != "\\" )) && $quotes != 2)
			{
				if($quotes == 0)
					$quotes = 1;
				elseif($quotes == 1)
					$quotes = 0;
			}
			elseif($str{$i} == '"' && (!isset($str{$i-1}) ||  (isset($str{$i-1}) && $str{$i-1} != "\\" )) && $quotes != 1)
			{
				if($quotes == 0)
					$quotes = 2;
				elseif($quotes == 2)
					$quotes = 0;
			}
			elseif(!$quotes && $str{$i} == $strt_delim)
			{
				array_push($stack_delims, 1);
			}
			elseif(!$quotes && $str{$i} == $end_delim)
			{
				$popped = array_pop($stack_delims);
				if($popped == null)
					return $i;
			}
		}
		return false;
	}

	function is_literal($str)
	{
		$str = trim($str);
		$strlen = strlen($str);

		if(strtolower($str) == 'true' || strtolower($str) == 'false')
			return true;
		if(is_numeric($str))
			return true;
		if($str{0} == "'")
		{
			if($strlen >= 2 && $str{$strlen - 1} == "'" &&  $str{$strlen - 2} != "\\")
				return true;
		}
		elseif($str{0} == '"')
		{
			if($strlen >= 2 && $str{$strlen - 1} == '"' &&  $str{$strlen - 2} != "\\")
				return true;
		}

		return false;
	}

	function explode_tag($delim, $str, $strtdelim = "", $enddelim = "")
	{


		$return_array = array();
		$quotes = 0;
		$currentstr = "";
		$i = -1;
		$indelim = 0;

		while (++$i < strlen($str))
		{
			if($str{$i} == '"' && (!isset($str{$i -1}) || (isset($str{$i -1}) && $str{$i -1} != "\\"))  && !$indelim)
			{
				if($quotes == 0)
					$quotes = 2;
				elseif($quotes == 2)
					$quotes = 0;

				$currentstr .= $str{$i};
				continue;
			}

			if($str{$i} == "'" &&  (!isset($str{$i -1}) || (isset($str{$i -1}) && $str{$i -1} != "\\")) && !$indelim)
			{
				if($quotes == 0)
					$quotes = 1;
				elseif($quotes == 1)
					$quotes = 0;

				$currentstr .= $str{$i};
				continue;
			}

			if($strtdelim != "" && $enddelim != "")
			{

				if(strpos($strtdelim, $str{$i}) !== false && !$quotes && !$indelim)
				{
					$indelim = strpos($strtdelim, $str{$i}) + 1;
					$currentstr .= $str{$i};
					continue;
				}

				if(strpos($enddelim, $str{$i}) !== false && !$quotes && $indelim)
				{
					$indelim = (strpos($enddelim, $str{$i}) + 1) == $indelim ? 0 : $indelim;
					$currentstr .= $str{$i};
					continue;
				}
			}


			if($str{$i} == $delim && !$indelim && !$quotes && strlen(trim($currentstr)) != 0 )
			{
				array_push($return_array, $currentstr);
				$currentstr = "";
			}
			else
			{
				$currentstr .= $str{$i};
			}

		}
		if(strlen($currentstr) != 0)
		{
		  array_push($return_array, $currentstr);
		}
		return $return_array;
	}
}
