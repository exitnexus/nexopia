require 'date'
require 'skin'
require 'blocks'

ERROR_MISSING_OPEN_TAG =  'End tag {@1} has no matching start tag'
ERROR_MISSING_CLOSE_TAG = 'Start tag {@1} has no matching end tag'
ERROR_INVALID_def =  '@1 is not a valid def'
ERROR_INVALID_TAG =       '{@1} is not a valid template tag'
ERROR_MISSING_IF =        '@1 does not have a matching \'if\' tag'

STRT_DELIM = "{"
END_DELIM = "}"
COND_LOOP_IND = ":"
def_IND = "|"
URLENCODE_IND = "%"
HTMLENTITIES_IND = "#"
VARIABLE_IND = "$"
#PARSED_FILES_PATH = "/home/troy/workspace/Nexopia/trunk/templates/compiled_files/" 
#TEMPLATE_FILES_PATH = "/home/troy/workspace/Nexopia/trunk/templates/template_files/"
#PARSED_FILES_PATH = "/home/baxter/src/trunk/templates/compiled_files/" 
#TEMPLATE_FILES_PATH = "/home/baxter/src/trunk/templates/template_files/"

#Just prints out all local variables.  Used for general debugging.
def info
    local_variables.each{|var|
        print var.to_s + " = ";
        eval("print " + var);
        print "\n"
    }
end

def userdate(p1, p2)
    return Date.today.to_s
end

def array_pop(array)
    return array.pop()
end

def array_push(array, obj)
    return array.push(obj)
end

def array(*arr)
    return arr;
end

def strlen(str)
    return str.length();
end

def substr2(str, index, len)
    return str[index...index+len]
end

def substr(str, index)
    return str[index..str.length]
end

#This function returns a string or an array with all occurrences of search
#in subject  replaced with the given replace value.
def str_replace(search, replace, subject)
    return subject.sub(search, replace)
end

def trim(str)
    return str.strip()
end

def strpos(str, sub)
    return str.index(sub)
end

#class String
#    alias old_pos []
#    
#    def [](index)
#        return old_pos(index...index+1)
#    end
#end

class Template

    attr_writer :vars;
    attr_reader :vars;
    
    #constructor
    def initialize(filepath)
        @tmpl_str = "";
        @vars = {};
        @if_stack = {};
        @loop_stack = {};
        @errors = {};
        @parsed = false;
        @show_whitespace = {};
        @tmpl_name_prefix = {};
        
        @allowed_defs = Hash[      'uppercase'         =>  'strtoupper($1)',
                                   'date'              =>  'date( $2, $1)',
                                   'lowercase'         =>  'strtolower($1)',
                                   'userdate'          =>  'userdate($2, $1)',
                                   'htmlformattext'    =>  'nl2br(smilies(parseHTML($1)))',
                                   'implode'           =>  'implode( $2, $1)',
                                   'alternate'         =>  '$1 = !$1',
                                   'assign'            =>  '$1 = $2',
                                   'truncate'          =>  'truncate($1, $2)'  ]
    
        if(@parsed)
            @tmpl_str = File.read("#{$config.template_parse_dir}/#{filepath}.parsed.php");
        else
            @tmpl_str    = File.read("#{$config.template_files_dir}/#{filepath}.html");
        end
        
        if(!@tmpl_str)
            die("File #{$config.template_parse_dir}/#{filepath}.parsed.php is empty");  
        end
        
        #@tmpl_str    = file_get_contents(@filepath);
        @vars        = Hash[];
        @loop_stack  = array();
        @errors      = array();
        @if_stack    = array();
        @loop_stack  = array();
        @show_whitespace = true;
        @parsed       = @parsed;
        @tmpl_name_prefix = "___"; #str_replace('/', '_', @filepath);

        @parsed_str = "";
        if(!@parsed)
            @parsed_str = parse(@tmpl_str);
        else
            @parsed_str = @tmpl_str;
        end

        true
    end

    #This step evaluates all the variables in the template and returns the binding.
    def get_binding()
        for var in @vars.keys
            eval(var.to_s + " = @vars[var]");
        end
        eval("____THIS_PAGE = ''");
        return binding;
    end

    def show_whitespace( bool )
        @show_whitespace = bool;
    end
 

    #used to set a variable in the template
    def set(key, value)
        @vars[@tmpl_name_prefix + "_" +  key] = value;
    end


    #used to set multiple variables in the template
    #vars should be an association array.
    def setMultiple(vars)
        for key, value in vars
            set(@tmpl_name_prefix + "_" + key, value);
        end
    end


    #primitive def -- does a dump of the interpreted php need to view source
    def dump()
        echo "<b> This is a dump (right click to view source)</b> <br>";
        echo parse(@tmpl_str);
    end
    
    def write(file_path)
        
        file_path =  "#{$config.template_parse_dir}/#{file_path}.parsed.php";  
        dirs = explode('/', file_path);
        dir_path = "";
        for dir in dirs
            if(dir == dirs[dirs.length - 1])
                break;
            end
                
            dir_path += dir;
            if(!is_dir(dir_path) && dir != ".+" )
                if(!mkdir(dir_path))
                   echo "Cannot create directory (" + dir_path + ")";  
                end
            end
            dir_path += "/";
        end
        
        handle = fopen(file_path, "wb");
        if (!handle)
            echo "Cannot open file (" + file_path + ")";
            exit;
        end
         
        if(fwrite(handle, @parsed_str) === FALSE)
            echo "Cannot write to file (" + file_path + ")";
            exit;      
        end
        fclose(handle);   
    end    

    def result()
        rhtml = ERB.new(@parsed_str, 0);
        
        return rhtml.result(get_binding());
        
    end
    
    #displayes the template
    def display()
        #extract(vars);
   
        #ob_start();
        #eval(@parsed_str);
        #@content=ob_get_contents();

        #ob_end_clean();
        rhtml = ERB.new(@parsed_str, 0);
        
        #Run with the current binding, which allows the template to access
        #all of the variables we set.
        rhtml.run(get_binding());
        
  
    end
    
  #returns a string of the template that can be echoed.
    def parse(str)
        is_php_code     = false;
        is_php_comment  = false;
        is_php_quotes   = false;
        comment = 0;
        quotes  = 0;
        i       = 0;
         
        strlen = str.length;
        php_str = "";

        while(i < strlen)
            current_char = str[i, 1];
            next_char = str[i+1, 1];
            prev_char = str[i-1, 1];
            
            if(current_char == STRT_DELIM && prev_char != '\\' )#a tempate tag has been detected
                str_end = substr(str, i+1);
                pos_end_delim =  strpos_end_delim(str_end, STRT_DELIM, END_DELIM); 		#find end tag
                invalid_tag = (pos_end_delim == false);
                
                if(!invalid_tag)
                    tag = substr2(str, i+1, pos_end_delim); #get contents of the tag
                    php_str += process_tag(tag);
                    i = i + pos_end_delim + 2 ;
                else
                    php_str += str[i, 1]
                    i+=1
                end
            else
                if(current_char == '\\' && next_char == STRT_DELIM) 	#skips the \ character before an escaped opening tag so \{test} prints out as {test}
                    i += 1
                else
                    if(!@show_whitespace && trim(str[i, 1]) == '')
                        i += 1
                    else
                        php_str += str[i, 1];
                        i+=1;
                    end
                end
            end
        end
    
        for value in @if_stack 		#record all if tags that are missing the {endif} tag
            array_push(@errors,str_replace('@1', value, ERROR_MISSING_CLOSE_TAG));
        end
    
        for value in @loop_stack 	# record all loop tags that are missing the {endloop} tag
            array_push(@errors,str_replace('@1', value, ERROR_MISSING_CLOSE_TAG));
        end
      
        if( has_errors() == true )
            print_errors();
            exit;        
        else
            return php_str;
        end
    end
      
   
#     * This def contains logic for determining what type of template tag the current 
#     * template tag is and performing the appropriate logic.
#     */          
    def process_tag(tag)
        php_str = "";
        tag = tag.strip();
        i = -1;
        var_ind = "!$%#";
	
        #if and elseif tag
        if( ( if_tag = tag[0,2] ) == 'if' || ( if_tag = tag[0,4] ) == 'elseif')
            offset = (if_tag == 'if') ? 2 : 6 ; 
            
             _if = process_if(trim(substr(tag,offset)), if_tag);
             
             if(_if == nil)
                 array_push(@errors , str_replace('@1', tag, ERROR_INVALID_TAG));
             else
                if(trim(if_tag) == 'if' )
                    array_push(@if_stack, tag);
                else
                    if(@if_stack.length == 0)
                        array_push(@errors , str_replace('@1', tag, ERROR_MISSING_IF));  
                        _if = nil;
                    end
                end
                php_str += _if;
            end
        elsif(tag[0,4] == 'loop')
            loop = process_loop(substr(tag,4).strip());
            if(loop != nil)
                array_push(@loop_stack, tag);
                php_str += loop;
            else
                array_push(@errors , str_replace('@1', tag, ERROR_INVALID_TAG));
            end
        elsif(tag[0,6] == 'header')
        
            header = process_header(substr(tag, 6).strip());
            if(header != nil)
                php_str += header;
            else
                array_push(@errors , str_replace('@1', tag, ERROR_INVALID_TAG));
            end
        elsif(tag == "footer")
            php_str += "<%= incFooter(); %>";
        elsif(tag == "else")
            if(@if_stack.length > 0)
                php_str += "<% else %>";
            else
                array_push(@errors , str_replace('@1', tag, ERROR_MISSING_IF));  
            end
        elsif(tag == "endif" || tag == "endloop")
            popped = "";
            if(tag == "endif")
                popped = array_pop(@if_stack);
            else
                popped = array_pop(@loop_stack);
            end
        
            if(popped == nil)
                array_push(@errors , str_replace('@1', tag, ERROR_MISSING_OPEN_TAG));   
            end
                  
            php_str += "<% end %>";
            
        elsif(var_ind.index(tag[0, 1]) != nil)
            processed_var, is_output = process_var(tag);
             
            if(processed_var == nil)
                array_push(@errors , str_replace('@1', tag, ERROR_INVALID_TAG));
                print "nil\n";
            elsif(is_output)
                php_str += '<%=' + processed_var + '; %>';
            else
                print "not nil\n"
                array_push(@errors , str_replace('@1', tag, ERROR_INVALID_TAG));
            end
        else
            php_str += "Error: " + tag + "\n"
        end
            
        return php_str;
    end
    
    def process_header(tag)
        if(trim(tag) == "")
            return "<%= incHeader();%>" ;
        end
            
        if(tag[0, 1] != ':')
            return nil;
        else
            tag = substr(tag, 1);
        end
 
        header_args = explode_tag(" ",tag, "(", ")");
        args_count = 0;
        width_value = "";
        rightboxes_value = "";
        leftboxes_value = "";
        arg_num = 0;
        return_str ="";
        for value in header_args
            value = trim(value);
            return_str += args_count.to_s + ":" + value + ", ";
            if(args_count % 3 == 1)
                if( value != "=")
                    return nil;    
                end
            elsif(args_count % 3 == 0)
                if(value == "width")
                    arg_num = 1;
                elsif(value == "leftblocks")
                    arg_num = 2;
                elsif(value == "rightblocks")
                    arg_num = 3;
                end
            else
                arg_value = "";
                
                if(is_literal(value))
                    arg_value = value;
                elsif(value[0, 1] == "(")
                    arg_value = process_list(value);
                else
                    arg_value, dummy = process_var(value);
                end
                
                    
                if(arg_value == nil)
                    return nil;
                end
                    
                if(arg_num == 1)
                    width_value = arg_value;
                end
                    
                if(arg_num == 2)
                    leftboxes_value = arg_value;
                end
                
                if(arg_num == 3)
                    rightboxes_value = arg_value; 
                end
                    
            end
            args_count += 1;
        end

        return_str = "<%= incHeader(";
        if(width_value != "" && width_value != nil)
            return_str += width_value;
            if( leftboxes_value != ""  &&  leftboxes_value != nil )
                return_str += ", " + leftboxes_value;
                if( rightboxes_value != ""  &&  rightboxes_value != nil )
                    return_str += ", " + rightboxes_value;
                end
            end
            
        end
        return_str += "); "+"%>";
        return return_str;
    end    
   
    def process_list(list)

        if(list[0, 1] != "(" || list[strlen(list) - 1, 1] != ")" )
            return nil;
        end
        
        list_items = explode_tag(",",list[1,strlen(list) - 2]);
        php_list = "array( ";
        for item in list_items
            if (is_literal(item))
                php_list += item+", ";
            else
                var_value, dummy = process_var(item);
                if(var_value == nil)
                    return nil;
                end
                php_list += var_value+", ";
            end
            
        end
        php_list = php_list[0, strlen(php_list) - 2]; #trim trailing ', '
        return php_list + ")";
    end  
    
#    /*
#     * This def returns the php interpritation of the template variable tag.
#     */   
    def process_var(tag)
        is_output = nil;
        tag = trim(tag);
        php_var = "";
        is_output = true;
        validprefixchar    = URLENCODE_IND + HTMLENTITIES_IND + VARIABLE_IND;
        validvarfirstchar  = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ";
        validvarchars      = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789_";
        
        in_array_element   = false;
        var_started        = false;
        var_finished       = false;
        
        def_call_strt_pos = 0;
        var                    = "";
        i                      = 0;
        htmlentities           = false;
        urlencode              = false;
        is_equal_stmt          = false;
        
        while(i < strlen(tag))
	       
            #Handling whitespace
            if( trim(tag[i, 1]) == "")
                #if a variable has been started but not finished then finish the variable
                if(var_started && !var_finished)
                    var_finished = true;
                elsif(var_finished)
                    #otherwise ignore the blank.
                    var += " ";
		end
            elsif(var_started  && tag[i, 1] == '|') #is a def call
                #Handling a function call
                var_finished = true;
                def_call_strt_pos = i;
                break;
            elsif(var_started && tag[i, 1] == "=")
                var_finished = true;
                is_equal_stmt = true;
                break;
            elsif( var_started && !@in_quotes && tag[i, 1] == '[')
                #Handle array call e.g. @array[1, 1]
                delim_end_pos = strpos_end_delim(substr(tag, i+1), "[", "]");
                if(delim_end_pos === false)
                    return nil, is_output;
                end
                element = substr2(tag, i+1, delim_end_pos);
                
                #checks if element is a literal or a var and applies approriate logic
                if (!is_literal(element))
                    processed_var, dummy = process_var(element);
                    if(processed_var == nil)
                        return nil, false;
                    end
                    var += "[" + processed_var + "]";
                else
                    var += "["+element+"]";
                end
                
                i += delim_end_pos + 1 ;
                
            elsif(!var_started && tag[i, 1] == '!')
                #Handles !(not) operator
                var += tag[i, 1];
            #elsif(!var_started && tag[i, 1] == "$")
            #    #skip "$" characters... legacy PHP
            #    var_started = true;
            #    var += @tmpl_name_prefix + "_" ;
            elsif(!var_started && strpos(validprefixchar,tag[i, 1]) != false &&
                    strpos(validvarfirstchar, tag[i+1, 1]) != false)
                #startes a variable if one has not been started and if the prefix and first char is valid
		 
                if(	tag[i, 1] == HTMLENTITIES_IND)
                    htmlentities = true;
                end
                if( tag[i, 1] == URLENCODE_IND )
                    url_encode = true;
                end
                var_started = true;
                var += @tmpl_name_prefix + "_" ;
            elsif(var_started && !var_finished &&
                    strpos(validvarchars, tag[i, 1])  != false)
                #if a variable has been started and not finished and the current char is valid then append to the variable
                var += tag[i, 1];
            else
                print tag[i, 1] + "\n";
                return nil, is_output;
            end
            i += 1;
        end

        
        if(url_encode)#urlencode
            php_var += 'urlencode(';
            php_var += var;
            php_var += ')';
        elsif( htmlentities )#htmlentities
            php_var += 'htmlentities(';
            php_var += var;
            php_var += ')';
        else
            php_var = var;
        end
	
        if(is_equal_stmt)
            vars = explode('=', tag);
            if(vars.length != 2)
                return nil, is_output;
            end
            if(is_literal(vars[1, 1]))
                php_var = "var = " + vars[1, 1];
            else
                var2, dummy = process_var(vars[1, 1]);
                if(var2 == nil)
                    return nil, false;
                end
                php_var = "var = " + var2;
            end
            is_output = false;
        end
        #handles defs
        if(def_call_strt_pos > 0)
            def_call = process_def(substr(tag, def_call_strt_pos + 1), php_var) ;  
            if(def_call == nil)
                return nil, is_output;
            else
                return def_call, is_output;
            end
        else
	       return php_var, is_output;
        end
    
    end
    
    #limited to one variable only 
    #can pass an array of vars in the future.
    def process_def(tag, var)
        if(var == nil)
            return nil;
        end
        
        def_array = explode_tag(',', tag);
        func_tmpl = @allowed_defs[trim(def_array[0])]; #looks up the def
        func_str = "";
        
        #if def is a valid def
        if(func_tmpl != nil)
            func_str = str_replace("$1", var, func_tmpl); #replace place holder for variable
            #replace all place holders with their values
            for i in (0 ... def_array.length)
                if(is_literal(def_array[i]))
                    func_str = str_replace("$"+(i + 1).to_s, def_array[i], func_str); 
                else
                    variable, dummy = process_var(def_array[i]);
                    if(variable == nil)
                        func_str = nil;
                        break;
                    end
                    func_str = str_replace("$"+(i + 1).to_s, variable, func_str);  
                end
            end
        else
            func_str = nil;
            array_push(@errors , str_replace('@1', def_array[0], ERROR_INVALID_def));
        end
        return func_str;
    end
  
    #processes loop conditions
    def process_loop(tag)
        
        php_str    = 'for ';
        loop_cond  = tag;
        as_pos     = strpos(loop_cond, 'as' );
        arrow_pos  = strpos(loop_cond, '=>');
        comma_pos  = strpos(loop_cond, ',');
        array_var = "";
        key_var = "";
        value_var = "";
        valid_array_cond = true;
        tag = trim(tag);
        counter_var = nil;
        
        if(loop_cond[0, 1] != ':')
            valid_array_cond = false;
        else
            loop_cond = substr(tag, 1);
        end

        
        if(as_pos != nil)
            array_var, dummy = process_var(substr2(loop_cond,0, as_pos - 1));
            if(array_var != nil and arrow_pos != nil)
                key_var, dummy =  process_var(substr(loop_cond, as_pos + 1, arrow_pos - ( as_pos + 2) ))  
                if(comma_pos != nil && comma_pos > arrow_pos)
                    value_var, dummy = process_var(substr2( loop_cond, arrow_pos + 2, comma_pos - (arrow_pos + 2)));
                    counter_var, dummy = process_var(substr2( loop_cond, comma_pos + 1, strlen(loop_cond)-(comma_pos +1)));
                    if(counter_var == nil)
                        valid_array_cond = false;
                    else
                        php_str = "counter_var = -1; \n" + php_str;
                    end
                else
                    value_var, dummy = process_var(substr( loop_cond, arrow_pos + 2, strlen(loop_cond) - (arrow_pos + 2) ));
                end
                
                if(value_var != nil && key_var != nil)
                    php_str += key_var + " in " + array_var + " ="+"> " + value_var;
                else
                    valid_array_cond = false;
                end
            elsif(array_var != nil)
                if(comma_pos != nil && comma_pos > as_pos)
                    value_var, dummy = process_var(substr( loop_cond, as_pos + 2, comma_pos - (as_pos + 3)));
                  
                    counter_var, dummy = process_var(substr( loop_cond, comma_pos + 1, strlen(loop_cond)-(comma_pos +1)));
                    
                    if(counter_var == nil)
                        valid_array_cond = false;
                    else
                        php_str = "counter_var = -1; \n" + php_str;
                    end
                else
                    value_var, dummy = process_var(substr2(loop_cond, as_pos + 2, strlen(loop_cond) - ( as_pos + 2) ));   
                end
                
                if(value_var != nil)
                    php_str += value_var + " in " + array_var;
                else
                    valid_array_cond = false;
                end
            end
        else
            valid_array_cond = false;
        end
        
        if(valid_array_cond)
            if(counter_var != nil)
                return "<%" + php_str + " \n counter_var ++; %>";
            else
                return "<%" + php_str + " %>";
            end
        else
            array_pop(@loop_stack);
            return nil;
        end
    end 
  
    def process_if(tag, if_ind)
        php_str = "<% ";
        php_str += (if_ind == 'elsif') ? "" : "";
        php_str +=  if_ind+"( " ;
        tag = trim(tag);
       
        if(tag[0, 1] != ':')
            valid_array_cond = false;
        else
            tag = substr(tag, 1);
        end
                
        condition_array = trim(tag).split(/\s+/); #split on whitespace
      
        if(condition_array.length != 3 && condition_array.length != 1)
            return nil;
        else
            i = 0;
            while(i < condition_array.length)
                if(i == 0 || i == 2)
                    if(is_literal(condition_array[i]))
                        php_str += condition_array[i];
                    else
                        var, is_output = process_var(condition_array[i]);
                        if(var == nil)
                            return nil;
                        end
                        php_str += var;
                    end
                else
                    op = condition_array[i];
                    if(op == '==' || op == '!=' || op == ">" || op == '<' || op == "<=" || op == ">=")
                        php_str += " "+op+" ";
                    else
                        return nil;
                    end
                end
                i+=1;
            end
        end
        return  php_str + ") %>"
    end
        
    
    
        
    
    def has_errors()
        return (@errors.length > 0);
    end
  
    def print_errors()
        print "The following errors have occured <br>\n";
        for error in @errors
            print error + "<br>\n";
        end
    end
    
    def strpos_end_delim(str, strt_delim, end_delim)
	quotes = false;
	stack_delims = array();
        i=0;
	while( i < strlen(str))
            last_char = str[i-1, 1];
            char = str[i, 1];
            if (char == "'" && last_char != "\\") && (quotes != 2)
                if(quotes == false)
                    quotes = 1;
                elsif(quotes == 1)
                    quotes = false;
                end
            elsif (char == '"' && last_char != "\\")  && (quotes != 1)  
                if(quotes == false)
                    quotes = 2;
                elsif(quotes == 2)
                    quotes = false;
                end
            elsif(!quotes && char == strt_delim)
                array_push(stack_delims, 1); 
            elsif(!quotes && char == end_delim)
                popped = array_pop(stack_delims);
                if(popped == nil)
                    return i;
                end
            end
            i+=1;
        end
        return false;
    end
	   
            
    
    def is_literal(str)
        str = trim(str);
        strlen = strlen(str);
        
        if(str.downcase == 'true' || str.downcase == 'false')
            return true;
        end
        if(str.downcase == str.upcase)
            return true;
        end
        if(str[0, 1] == "'")
            if(str[strlen - 1, 1] == "'" &&  str[strlen - 2, 1] != "\\")
                return true;
            end
        elsif(str[0, 1] == '"')
            if(str[strlen - 1, 1] == '"' &&  str[strlen - 2, 1] != "\\")
                return true;
            end
        end
        
        return false;
    end
    
    def explode_tag(delim, str, strtdelim = "", enddelim = "")

        return_array = Array[];
        quotes = 0;
        currentstr = "";
        i = 0;
        indelim = 0;
        while (i < strlen(str))
            if (str[i, 1] == '"' && str[i - 1, 1] != "\\")  && !(indelim > 0)
                if(quotes == 0)
                    quotes = 2;
                elsif(quotes == 2)
                    quotes = 0;
                end
                        
                currentstr += str[i, 1];
                i+=1;
                next; 
            end
            
            if(str[i, 1] == "'" && str[i -1, 1] != "\\" && !(indelim > 0))
                if(quotes == 0)
                    quotes = 1;
                elsif(quotes == 1)
                    quotes = 0;
                end
                        
                currentstr += str[i, 1];
                i+=1;
                next; 
            end
            
            if(strtdelim != "" && enddelim != "")
                if(strpos(strtdelim, str[i, 1]) != nil && !(quotes>0) && !(indelim > 0))
                    indelim = (strpos(strtdelim, str[i, 1])!=nil)?1:0;
                    currentstr += str[i, 1];
                    i+=1;
                    next; 
                end
            
                if(strpos(enddelim, str[i, 1]) != nil && !(quotes>0) && indelim > 0)
                    indelim = ((strpos(enddelim, str[i, 1])!=nil)?1:0) == indelim ? 0 : indelim;
                    currentstr += str[i, 1];
                    i+=1;
                    next;        
                end
            end
            
            if(str[i, 1] == delim && !(indelim > 0) && !(quotes>0) && strlen(trim(currentstr)) != 0 )
                array_push(return_array, currentstr);
                currentstr = "";
            else
                currentstr += str[i, 1];
            end
            i+=1;
        end
            
        if(strlen(currentstr) != 0)
            array_push(return_array, currentstr);
        end
        return return_array;
    end

    def toString
        return @parsed_str;
    end
end

