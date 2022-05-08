lib_require :core, 'storable/user_content'

require 'strscan'
require 'uri'

# A simple stack based implementation BBCode
# it does absolutely no security, so depends on an html parser to enforce
# things like no javascript in urls, etc.
# Abandon hope all ye who enter here.

class BBCode
	# body
	#        true     Body, which could contain BB code.  [b]hello! [i]italic[/i][/b]
	#        false    No body.  For example, [img=some_image.jpg]
	#        literal  We have a body, [img]some_image.jpg[/img]
	Tag = Struct.new(:param, :body, :func)

	TAGS = {
		'code' => [
			Tag.new(false, :literal, lambda {|param, body| "#{body}"})
			],
		'comment' => [
			Tag.new(false, true,  lambda {|param, body| ""}),
			Tag.new(true,  false, lambda {|param, body| ""}),
			],
		'quote' => [
			Tag.new(false, true,  lambda {|param, body| "<div class=\"quote\">#{body}</div>" }),
			Tag.new(true,  true,  lambda {|param, body| "<div class=\"quote\"><div><i>Originally posted by:</i> <a class=\"body\" href=\"/users/#{param}\">#{param}</a></div>#{body}</div>"}),
			],
		'img' => [
			Tag.new(/^([0-9]{0,3}%?)x([0-9]{0,3}%?)$/, :literal, lambda {|param, body|
					s = "<img minion_name=\"user_content_image\" url=\"#{body}\" border=\"0\""
					s << " width=\"#{param[1]}\"" if param[1].chomp('%').length > 0
					s << " height=\"#{param[2]}\"" if param[2].chomp('%').length > 0
					s << ">"
					s
				}),
			Tag.new(false, :literal,  lambda {|param, body| "<img minion_name=\"user_content_image\" url=\"#{self.escape(body.to_s)}\" border=\"0\">" }),
			Tag.new(true,  false, lambda {|param, body| "<img minion_name=\"user_content_image\" url=\"#{param}\" border=\"0\">" }),
			],
		'url' => [
			Tag.new(false, :literal,  lambda {|param, body| "<a class=\"body user_content\" href=\"#{self.escape(body.to_s)}\" target=\"_new\">#{parse_simple_bbcode(body)}</a>" }),
			Tag.new(true,  true,  lambda {|param, body| "<a class=\"body user_content\" href=\"#{param}\" target=\"_new\">#{body}</a>" }),
			],
		'user' => [
			Tag.new(false, :literal,  lambda {|param, body|  "<a class=\"body user_content\" href=\"/users/#{self.escape_username(body.to_s)}\" target=\"_new\">#{body}</a>" }),
			Tag.new(true,  false, lambda {|param, body| "<a class=\"body user_content\" href=\"/users/#{param}\" target=\"_new\">#{param}</a>" }),
			],
		'email' => [
			Tag.new(false, :literal,  lambda {|param, body| "<a class=\"body user_content\" href=\"mailto:#{self.escape(body.to_s)}\">#{body}</a>" }),
			Tag.new(true,  false, lambda {|param, body| "<a class=\"body user_content\" href=\"mailto:#{param}\">#{param}</a>" }),
			],
		'size'    => [ Tag.new(true,  true,  lambda {|param, body| "<font size=\"#{param}\">#{body}</font>" }) ],
		'font'    => [ Tag.new(true,  true,  lambda {|param, body| "<font face=\"#{param}\">#{body}</font>" }) ],
		'color'   => [ Tag.new(true,  true,  lambda {|param, body| "<font color=\"#{param}\">#{body}</font>" })],
		'colour'  => [ Tag.new(true,  true,  lambda {|param, body| "<font color=\"#{param}\">#{body}</font>" })],
		'b'       => [ Tag.new(false, true,  lambda {|param, body| "<b>#{body}</b>" })],
		'u'       => [ Tag.new(false, true,  lambda {|param, body| "<u>#{body}</u>" })],
		'i'       => [ Tag.new(false, true,  lambda {|param, body| "<i>#{body}</i>" })],
		'sup'     => [ Tag.new(false, true,  lambda {|param, body| "<sup>#{body}</sup>" })],
		'sub'     => [ Tag.new(false, true,  lambda {|param, body| "<sub>#{body}</sub>" })],
		'strike'  => [ Tag.new(false, true,  lambda {|param, body| "<strike>#{body}</strike>" })],
		'center'  => [ Tag.new(false, true,  lambda {|param, body| "<center>#{body}</center>" })],
		'left'    => [ Tag.new(false, true,  lambda {|param, body| "<div style=\"text-align:left\">#{body}</div>" })],
		'right'   => [ Tag.new(false, true,  lambda {|param, body| "<div style=\"text-align:right\">#{body}</div>" })],
		'justify' => [ Tag.new(false, true,  lambda {|param, body| "<div style=\"text-align:justify\">#{body}</div>" })],

		'hr'      => [ Tag.new(false, false, lambda {|param, body| "<hr />" })],
		'*'       => [ Tag.new(false, true, lambda {|param, body| "<li>#{body}" })],

		'list' => [
			Tag.new(false, true,  lambda {|param, body| "<ul>#{body}</ul>" } ),
			Tag.new(true,  true,  lambda {|param, body| "<ol type=\"#{param}\">#{body}</ol>" }),
			],
		}
	
	
	def self.escape(string)
		return URI.escape(string, /['"`]/)
	end

	def self.escape_username(string)
		return CGI::escape(string)
	end


	def self.do_node(input, skip_tag_end = false, parent_tag_stack=Array.new) #input is a StringScanner
		ret = []

		prev_pos = -1
		loop {
			if (input.pos == prev_pos)
				# We have entered an infinite loop somehow
				raise "bbcodemodule.rb infinite loop, position #{input.pos}, rest of input is #{input.rest}"
			end
			prev_pos = input.pos

			#grab any text nodes
			text_node = input.scan(/[^\[]+/)
			ret << text_node if text_node

			#state is either the end of the string, or begin of a (close?) tag

			return ret if(input.eos?) #end

			if(input.peek(2) == '[/') # an unexpected close tag
				if(skip_tag_end) #if already at the top of the stack, just eat the tag and continue
					ret << input.scan(/\[\//)
					next
				end
				
				# Might be part of the parent tag (or it might be part of a literal that looks like a tag),
				# so we need to do a bit more checking. If it is part of the parent tag, we want to return
				# here. If not, we keep going.
				input.check(/\[\/([*a-z]+)\]/i);
				check_tag = input[1];
				check_tag.downcase! if !check_tag.nil?
				if(parent_tag_stack.include?(check_tag)) #otherwise, if this is part of a parent tag, bump up the stack
					return ret;
				end
			end

			input.scan(/\[/) #eat the [

			#find the tag, save orig_tagname in case it isn't a real tag
			orig_tagname = input.scan(/[*a-z]+/i)
			
			if(!orig_tagname) #if there is no tag, it's a literal [
				ret << "["
				next
			end

			#use the lowercase version for parsing
			tagname = orig_tagname.downcase

			if(!TAGS[tagname]) #if it's not a recognized tag, output it as a literal
				ret << "["
				ret << orig_tagname
				next
			end

			param = nil
			
			#find the param, if there is one
			if(input.scan(/=/))
				param = input.scan(/[^\]]+/)
				param = "" if param.nil?

				# BEGIN CRAZY PARAMETER CHECKING THAT MIGHT BE GOING TOO FAR
				# Check for the closing off of any "[" characters that shouldn't be interpreted
				# as part of the bbcode
				extra_brackets = param.nil? ? 0 : param.count("[");
				if(extra_brackets > 0)
					(0...extra_brackets).each {
						match = input.scan(/[^\]]+/)
						param = param + match.to_s if match != nil
						# Make sure we didn't go past the closing tag if
						# they missed a "]"
						if(param =~ /\[\/#{tagname}\]/i)
							input.unscan if match != nil
							break;
						else
							param = param + input.scan(/\]/).to_s							
						end
					}
					param = param + input.scan(/[^\]]+/).to_s
				end
				# Note: the above will only work for the first [] pair if it is within the
				# parameter. After that things will go as usual. I could be smarter here, but
				# then I'd be getting into the possibility of infinite loops again, and seriously,
				# this bbcode is starting to drive me nuts. I think I've already gone a little
				# bonkers trying to cover the above case. Feel free to take it out if you think
				# so. That will cause one of the tests to fail, however.
				# END CRAZY PARAMETER CHECKING THAT MIGHT BE GOING TOO FAR
			end
			
			if(input.eos?)
				ret << "[#{orig_tagname}"
				ret << "=#{param}" if param
				return ret
			end

			#make sure the open tag is complete
			if(!input.scan(/\]/))
				ret << "[#{orig_tagname}"
				ret << "=#{param}" if param
				ret << "]"
				return ret
			end

			body_begin_pos = input.pos;
			body = input.scan_until(/\[\/#{tagname}\]/i)
			body = body[0,body.length-input.matched_size] if input.matched? && !body.nil?

			# Try and fallback in the event of bad tag syntax
			if (input.pos == body_begin_pos)
				# First, try to find any closing tag
				body = input.scan_until(/\[\/.*\]?/i)
				body = body[0,body.length-input.matched_size] if input.matched? && !body.nil?
				
				if (input.pos == body_begin_pos)
					# Finally, try to find any tag
					input.scan_until(/\[.*\]?/i)
					body = body[0,body.length-input.matched_size] if input.matched? && !body.nil?
				end		
				
				if (input.pos == body_begin_pos)
					# At long last, try to just find some sort of termination for the body
					body = input.scan(/[^\[\]]+/)
				end
			end
			if (body =~ /\[\/#{tagname}\]/i)
				body = nil;
			end

			#scan the tag list to figure out which tag this actually matches, if any.
			found = nil
			body_match = nil
			params_match = nil

			TAGS[tagname].each { |tag|
				# Checking for regexp params
				if(tag.param.kind_of?(Regexp))
					if(param && (params_match = param.match(tag.param)))
						found = tag;
					else
						# Need to look at the next tag template. This one was looking for a regexp on the params that didn't match
						next;
					end
				elsif((tag.param && param) || (!tag.param && !param))
					found = tag;
				end
				
				# Things look good if found != nil, but we can't break until we make sure the tag.body template also works 
				# for this tag's body. So, without further ado...
				
				# Checking for regexp body
				if(tag.body.kind_of?(Regexp))
					if (body && (body_match = body.match(tag.body)))
						break if found; # We can break here because checking of the params already worked
					else
						found = nil;
						# Need to look at the next tag template. This one was looking for a regexp on the body that didn't match
						next;
					end	
				elsif((tag.body && body) || (!tag.body && !body))
					break if found; # We can break here because checking of the params already worked
				end
			}

			#no valid tag found, output as a literal
			if(!found)
				ret << "[#{orig_tagname}"
				ret << "=#{param}" if param
				ret << "]"
				next
			end

			# if this node doesn't have a body, build the node and continue parsing on the same level
			if(!found.body)
				input.pos = body_begin_pos
				ret << found.func.call((params_match || param), nil);
				next
			end

			# if either a regular expression or literal body, we want to return right away
			if((found.body.kind_of?(Regexp) && body_match) || found.body == :literal)
				if (body)
					ret << found.func.call((params_match || param), (body_match || body));
					next
				end
			# otherwise, recurse to the next level
			else
				input.pos = body_begin_pos
				parent_tag_stack << tagname
				body = do_node(input, false, parent_tag_stack)
			end				
			
			#eat the end of tag if there is one
			input.scan(/\[\/#{tagname}\]/i)

			#build this node
			ret << found.func.call((params_match || param), (body_match || body));
		}
	end


	def self.parse_simple_bbcode(str)
		return str if str.nil?
		
		str = str.gsub(/\[size=([0-9])\](.*?)\[\/size\]/im, '<font size="\1">\2</font>')
		#remove any null characters
		str.gsub!(/\000/, "");
		str.gsub!(/\[font=([a-zA-Z0-9 ]+)\](.*?)\[\/font\]/im, '<font face="\1">\2</font>')
		str.gsub!(/\[colou?r=([#a-zA-Z0-9]+)\](.*?)\[\/colou?r\]/im, '<font color="\1">\2</font>')
		str.gsub!(/\[b\](.*?)\[\/b\]/im, '<b>\1</b>')
		str.gsub!(/\[u\](.*?)\[\/u\]/im, '<u>\1</u>')
		str.gsub!(/\[i\](.*?)\[\/i\]/im, '<i>\1</i>')
		str.gsub!(/\[sup\](.*?)\[\/sup\]/im, '<sup>\1</sup>')
		str.gsub!(/\[sub\](.*?)\[\/sub\]/im, '<sub>\1</sub>')
		str.gsub!(/\[strike\](.*?)\[\/strike\]/im, '<strike>\1</strike>')

		# Do the same substitutions without the end tag in case the user forgot to put it in
		str.gsub!(/\[size=([0-9])\](.*)/im, '<font size="\1">\2</font>')
		str.gsub!(/\[font=([a-zA-Z0-9 ]+)\](.*)/im, '<font face="\1">\2</font>')
		str.gsub!(/\[colou?r=([#a-zA-Z0-9]+)\](.*)/im, '<font color="\1">\2</font>')
		str.gsub!(/\[b\](.*)/im, '<b>\1</b>')
		str.gsub!(/\[u\](.*)/im, '<u>\1</u>')
		str.gsub!(/\[i\](.*)/im, '<i>\1</i>')
		str.gsub!(/\[sup\](.*)/im, '<sup>\1</sup>')
		str.gsub!(/\[sub\](.*)/im, '<sub>\1</sub>')
		str.gsub!(/\[strike\](.*)/im, '<strike>\1</strike>')

		return str;
	end

	def self.parse(str)
		#remove any null characters
		str = str.gsub(/\000/, "");
		return str unless str['[']

		return do_node(StringScanner.new(str), true).flatten.join('');
	end

	UserContent::register_converter(:bbcode, BBCode::method(:parse), true, UserContent::ContentConverter::GENERATES_HTML)
end