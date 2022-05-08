
module Kernel
	module EncodeConst
		SPACE = ' '
		PLUS = '+'
		PERCENT = '%'
		URLENCODE_UNPACK = 'H2'
		URLENCODE_PACK = 'H*'

		HTML_AMP = '&amp;'
		HTML_QUOT = '&quot;'
		HTML_GT = '&gt;'
		HTML_LT = '&lt;'

		HTML_AMP2 = 'amp'
		HTML_QUOT2 = 'quot'
		HTML_GT2 = 'gt'
		HTML_LT2 = 'lt'

		AMPERSAND = '&'
		DOUBLE_QUOTE = '"'
		GREAT_THAN = '>'
		LESS_THAN = '<'

		HTML_PACK = 'U'

	end

	# URL encodes a string to have all special url characters encoded as + for space
	# and %XX where XX is the ascii code of the character.
	def urlencode(string)
		string = demand(string);
		string.gsub(/([^ a-zA-Z0-9_.-]+)/n){
			EncodeConst::PERCENT + $1.unpack(EncodeConst::URLENCODE_UNPACK * $1.size).join(EncodeConst::PERCENT).upcase
		}.tr(EncodeConst::SPACE, EncodeConst::PLUS)
	end

	def urldecode(string)
		string = demand(string);
		string.tr(EncodeConst::PLUS, EncodeConst::SPACE).gsub(/((?:%[0-9a-fA-F]{2})+)/n){
			[$1.delete(EncodeConst::PERCENT)].pack(EncodeConst::URLENCODE_PACK)
		}
	end


	# Escape special characters in HTML, namely &\"<>
	#   htmlencode('Usage: foo "bar" <baz>')
	#      # => "Usage: foo &quot;bar&quot; &lt;baz&gt;"
	def htmlencode(string)
		string = demand(string);
		out = string.dup
		out.gsub!(/&nbsp;/n, ' ')
		out.gsub!(/&/, EncodeConst::HTML_AMP)
		out.gsub!(/\"/n, EncodeConst::HTML_QUOT)
		out.gsub!(/>/n, EncodeConst::HTML_GT)
		out.gsub!(/</n, EncodeConst::HTML_LT)
		(0..32).each {|i|
			out.gsub!(/&##{i};/n, "")
		}
		out
	end
	
	
	# Unescape a string that has been HTML-escaped
	#   htmldecode("Usage: foo &quot;bar&quot; &lt;baz&gt;")
	#      # => "Usage: foo \"bar\" <baz>"
	def htmldecode(string)
		string = demand(string);
		string.gsub(/&(amp|quot|gt|lt|\#[0-9]+|\#x[0-9A-Fa-f]+);/n) do
			match = $1.dup
			case match
			when EncodeConst::HTML_AMP2   then EncodeConst::AMPERSAND
			when EncodeConst::HTML_QUOT2  then EncodeConst::DOUBLE_QUOTE
			when EncodeConst::HTML_GT2    then EncodeConst::GREAT_THAN
			when EncodeConst::HTML_LT2    then EncodeConst::LESS_THAN
			when /\A#0*(\d+)\z/n       then
				if Integer($1) < 256
					Integer($1).chr
				else
					if Integer($1) < 65536 and ($KCODE[0] == ?u or $KCODE[0] == ?U)
						[Integer($1)].pack(EncodeConst::HTML_PACK)
					else
						"&##{$1};"
					end
				end
			when /\A#x([0-9a-f]+)\z/ni then
				if $1.hex < 256
					$1.hex.chr
				else
					if $1.hex < 65536 and ($KCODE[0] == ?u or $KCODE[0] == ?U)
						[$1.hex].pack(EncodeConst::HTML_PACK)
					else
						"&#x#{$1};"
					end
				end
			else
				"&#{match};"
			end
		end
	end


	# builds the path components of a url while urlencoding parts of it.
	# If given arguments, constructs an initial url out of those. Returns
	# a UrlPathBuilder that can be used to add additional components to the url
	# by using a / operator.
	def url(*components)
		if (components.length < 1)
			return UrlPathBuilder.new();
		else
			path = UrlPathBuilder.new(components.shift.to_s);
			components.each {|component|
				path /= component;
			}
			return path;
		end
	end
end

class String
	# upcases without upcasing html entities (and probably later, tags)
	def upcase_html()
		out = self.upcase
		out.gsub!(/(&[A-Z]+;)/n) {|ent|
			ent.downcase
		}
		out
	end
end

class UrlPathBuilder < String

	QUESTION_MARK = '?'
	AMPERSAND = '&'

	# Adds a urlencoded path component to the end of the url.
	# if component is an array, it'll splat them.
	def /(component)
		ns = self.dup
		if (component.is_a?(Array))
			component = component.collect! {|i|
				if (i.kind_of? String)
					urlencode(i)
				else
					urlencode(i.to_s)
				end
			}.join('/')
		else
			component = if (component.kind_of? String)
				urlencode(component)
			else
				urlencode(component.to_s)
			end
		end
		params = ns[/\?|$/];
		ns[/\?|$/] = "/#{component}#{params}";
		return ns;
	end
	# Adds a parameter to the end of the url.
	def &(params)
		ns = self.dup();
		first = !ns[QUESTION_MARK];

		params.each {|key, value|
			ns[/(\?|&)?$/] = (first ? QUESTION_MARK : AMPERSAND);
			ns << "#{urlencode(key.to_s)}=#{urlencode(value.to_s)}";
			first = false;
		}
		return ns;
	end
	# Converts to a URL (no change)
	def to_url
		return self;
	end
end

class String
	def to_url
		return UrlPathBuilder.new(self);
	end
end
