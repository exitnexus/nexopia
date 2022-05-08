require "cgi";

module Kernel
	# URL encodes a string to have all special url characters encoded as + for space
	# and %XX where XX is the ascii code of the character.
	def urlencode(component)
		return CGI::escape(component);
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

class UrlPathBuilder < String
	# Adds a urlencoded path component to the end of the url.
	# if component is an array, it'll splat them.
	def /(component)
		if (component.is_a?(Array))
			ns = self
			component.each {|i|
				ns /= i
			}
			return ns
		else
			ns = self.dup();
			params = ns[/\?|$/];
			ns[/\?|$/] = "/#{urlencode(component.to_s)}#{params}";
			return ns;
		end
	end
	# Adds a parameter to the end of the url.
	def &(params)
		ns = self.dup();
		first = !ns['?'];

		params.each {|key, value|
			ns[/(\?|&)?$/] = (first ? '?' : '&');
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
