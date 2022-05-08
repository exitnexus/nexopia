lib_require :Core, "typesafehash", "bufferio", "session", "user_time", "pagehandler", 'url';
#require "system_timer";

# This class contains the main information used in handling an http request.
# It is taken from whatever source information was passed to the site application.
class PageRequest
	module MimeType
		HTML = "text/html";
		XHTML = "application/xhtml+xml";
		XML = "application/xml";
		XMLText = "text/xml";
		CSS = "text/css";

		PNG = 'image/png';
		JPEG = 'image/jpeg';

		PlainText = "text/plain";

		JavaScript = "text/javascript";

		ZIP = "application/zip"
	end

	# taken from the lighttpd default config file.
	MimeTypeExtensions = {
		".pdf"          =>      "application/pdf",
		".sig"          =>      "application/pgp-signature",
		".spl"          =>      "application/futuresplash",
		".class"        =>      "application/octet-stream",
		".ps"           =>      "application/postscript",
		".torrent"      =>      "application/x-bittorrent",
		".dvi"          =>      "application/x-dvi",
		".gz"           =>      "application/x-gzip",
		".pac"          =>      "application/x-ns-proxy-autoconfig",
		".swf"          =>      "application/x-shockwave-flash",
		".tar.gz"       =>      "application/x-tgz",
		".tgz"          =>      "application/x-tgz",
		".tar"          =>      "application/x-tar",
		".zip"          =>      "application/zip",
		".mp3"          =>      "audio/mpeg",
		".m3u"          =>      "audio/x-mpegurl",
		".wma"          =>      "audio/x-ms-wma",
		".wax"          =>      "audio/x-ms-wax",
		".ogg"          =>      "application/ogg",
		".wav"          =>      "audio/x-wav",
		".gif"          =>      "image/gif",
		".jpg"          =>      "image/jpeg",
		".jpeg"         =>      "image/jpeg",
		".png"          =>      "image/png",
		".xbm"          =>      "image/x-xbitmap",
		".xpm"          =>      "image/x-xpixmap",
		".xwd"          =>      "image/x-xwindowdump",
		".css"          =>      "text/css",
		".html"         =>      "text/html",
		".htm"          =>      "text/html",
		".js"           =>      "text/javascript",
		".asc"          =>      "text/plain",
		".c"            =>      "text/plain",
		".cpp"          =>      "text/plain",
		".log"          =>      "text/plain",
		".conf"         =>      "text/plain",
		".text"         =>      "text/plain",
		".txt"          =>      "text/plain",
		".dtd"          =>      "text/xml",
		".xml"          =>      "text/xml",
		".mpeg"         =>      "video/mpeg",
		".mpg"          =>      "video/mpeg",
		".mov"          =>      "video/quicktime",
		".qt"           =>      "video/quicktime",
		".avi"          =>      "video/x-msvideo",
		".asf"          =>      "video/x-ms-asf",
		".asx"          =>      "video/x-ms-asf",
		".wmv"          =>      "video/x-ms-wmv",
		".bz2"          =>      "application/x-bzip",
		".tbz"          =>      "application/x-bzip-compressed-tar",
		".tar.bz2"      =>      "application/x-bzip-compressed-tar",
		"default"		=>		"application/octet-stream",
	}

	# Identifies the HTTP method of the request. :GetRequest or :PostRequest are valid.
	attr_reader :method;
	# Is this a head request?
	attr_reader :head;
	# Identifies the area being used. See PageRequest.area for a list of them.
	attr_reader :area;
	# The URL path from the root of the domain of the request.
	attr_reader :uri;
	# A hash of headers passed in to the HTTP request.
	attr_reader :headers;
	# The user the request refers to. If area is :User, it'll be the user who's
	# profile is being viewed. If the area is :Self, it'll be the logged in user.
	# This is used in detecting the :IsSame access level.
	attr_reader :user;
	# The selector of the request. This is what's after a : in the uri. This
	# is used to choose alternate views of the same data (for skinning or ajax)
	attr_reader :selector;

	# All cookies sent by the client for this request.
	attr_reader :cookies;
	# Parameters passed in either through the query string for :GetRequest or
	# through the body of the request for :PostRequest.
	attr_reader :params;
	# An array of the PageRequest objects that led up to this one.
	attr_reader :request_stack;

	# The session of the user who made the request, if logged in. Nil otherwise.
	attr_reader :session;

	# A hash of {:facility => [LogBufferItem...]} for the request tree as a whole.
	attr_reader :log;

	# A hash of {:facility => [LogBufferItem...]} for the current request only.
	attr_reader :current_log;

	#pagehandler that is processing the request
	attr_accessor :handler
	
	# The skeleton used by the request (changes inherited by child requests)
	attr :skeleton, true;

	# The PageReply object associated with this request.
	attr :reply, true;

	# A context object used by the cache.
	attr_reader :context;
	
	# A token for the current request tree to be used in log output
	attr_reader :token

	LogStartRequest = Struct.new(:prev_req, :this_req);
	class LogStartRequest
		def to_s()
			str = "Request Start #{this_req}"
			str << ", called from #{prev_req.to_s(false)}" if prev_req
			str
		end
	end
	LogEndRequest = Struct.new(:prev_req, :this_req, :time);
	class LogEndRequest
		def to_s()
			str = "Request Done [#{format("%.3f", time)} msec] #{this_req}"
			str << ", going back to: #{prev_req.to_s(false)}" if prev_req
			str
		end
	end

	def get_reply_output()
		if(!@reply.nil?() && !@reply.out.nil?())
			return @reply.out.string;
		end
		return "";
	end

	
	def get_reply_ok()
		return @reply.ok?;
	end
	

	# Initialize and run a new request object. Yields to the caller to do the actual execution.
	# This allows it to manage the request stack correctly. The request object is only considered
	# active for the duration of that block, but is still available afterwards.
	# method takes :GetRequest, :PostRequest, and :HeadRequest. If it's HeadRequest, it
	# the method method will return :GetRequest but the head method will return true.
	def initialize(method, area, uri, headers, cookies, params, user, reply = nil)
		begin
#			SystemTimer.timeout_after(1800) do
				prev_req = PageRequest.current
				@method = method;
				@area = area;
				if (uri.class == String)
					@uri = uri.split(':');
					if (@uri.length > 1)
						@selector = @uri.last.to_sym;
					else
						@selector = :Page;
					end
					@uri = @uri.first;
				else # expecting it to be an array of path components here, and we assume it's a body request.
					@uri = uri
					@selector = :Body
				end

				@cookies = cookies;

				if(prev_req && prev_req.session)
					@session = prev_req.session
				else
					@session = promise {
						if (@cookies['sessionkey'].first)
							#Ruby site generated session
							sessioncookie = @cookies['sessionkey'];
							if (session = Session.get(sessioncookie.value.to_s()))
								self.reply.headers['X-LIGHTTPD-userid'] = session.user.userid
								self.reply.headers['X-LIGHTTPD-age'] = session.user.age
								self.reply.headers['X-LIGHTTPD-sex'] = session.user.sex
								self.reply.headers['X-LIGHTTPD-loc'] = session.user.loc
								self.reply.headers['X-LIGHTTPD-usertype'] = session.user.plus? ? 'plus' : 'user'
								
								session
							else
								self.reply.headers['X-LIGHTTPD-usertype'] = 'anon'
								Session.create_anonymous_session(headers['REMOTE_ADDR'])
							end
						else
							self.reply.headers['X-LIGHTTPD-usertype'] = 'anon'
							Session.create_anonymous_session(headers['REMOTE_ADDR'])
						end
					}
				end

				if (prev_req && prev_req.token)
					@token = prev_req.token
				else
					@token = "req:#{Process.pid}:#{(rand*1000).to_i}"
					if (params['log_token'])
						@token << ":#{params['log_token'][0..15]}"
					end
				end

				# Make these not hard code
				@skeleton = (prev_req ? prev_req.skeleton : $site.default_skeleton);

				@headers = headers;
				@params = TypeSafeHash.new(params);
				# TODO: Find a way to remove the @area == :User test to user code.
				@user = user || (@area == :User && @session && !@session.anonymous?() && @session.user && !@session.user.anonymous?());

				@log = (prev_req ? prev_req.log : {});
				@current_log = {};

				@reply = if(reply)
					reply
				elsif(prev_req)
					prev_req.reply
				else
					accepted_encodings = [];
					accepted_encodings << :gzip if (headers['HTTP_ACCEPT_ENCODING'] =~ /gzip/)
					accepted_encodings << :deflate if (headers['HTTP_ACCEPT_ENCODING'] =~ /deflate/)

					PageReply.new(StringIO.new(), true, accepted_encodings)
				end

				starttime = Time.now.to_f

				PageRequest.stack.push(self);
				@request_stack = PageRequest.stack.dup;
				begin
					$log.info(LogStartRequest.new(prev_req, self), :debug, :pagehandler);

					old_time_zone = UserTime.default_time_zone
					UserTime.default_time_zone = promise {
						if (!@session.anonymous?)
							UserTime.zone_name_by_index(@session.user.timeoffset)
						else
							old_time_zone
						end
					}

					yield self

				ensure
					UserTime.default_time_zone = old_time_zone
					PageRequest.stack.pop;
					endtime = Time.now.to_f
					$log.info(LogEndRequest.new(prev_req, self, (endtime-starttime)*1000), :debug, :pagehandler);
				end
			end
#		rescue Timeout::Error
#			$log.info "PageRequest Timeout::Error, uri: #{uri}, userid: #{user == nil ? "none" : user.userid}", :error
#		end
	end

	#This changes hash2 and its subhashes in place, it's private for a reason, 
	#not meant to be used outside of convert_to_nested_hash.
	def PageRequest.recursive_merge(hash1, hash2)
		hash1.each_pair { |key, value|
			#Added to circumvent the "Poison NULL" exploit.
			if(key.kind_of?(String))
				key = key.gsub(/\000/, "");
			end
			if(value.kind_of?(String))
				value = value.gsub(/\000/, "");
			end
			
			if (!hash2[key])
				hash2[key] = value;
			elsif (hash2[key].kind_of?(Hash) && value.kind_of?(Hash))
				hash2[key] = recursive_merge(value, hash2[key]);
			end
		}
		return hash2;
	end	

	def PageRequest.convert_to_nested_hash(cgi_hash)
		new_hash = {};
		cgi_hash.each_pair {|key, value|
			if ((value.kind_of? Array) && key !~ /\[\]$/)
				value = value.first
			end
			
			#Added to circumvent the "Poison NULL" exploit.
			if(key.kind_of?(String))
				key = key.gsub(/\000/, "");
			end
			if(value.kind_of?(String))
				value = value.gsub(/\000/, "");
			end
			
			while (key =~ /(.*)\[(.+)?\]$/)
				key = $1;
				if ($2)
					value = {$2 => value}
				else
					new_hash[key] ||= []
					if (!new_hash[key].kind_of? Array)
						$log.info "Tried to add '#{key}' as an array and a value.", :error
					end
					old_vals = [*value];
					old_vals.each{|v|
						new_hash[key] << v
					}
				end
			end
			if (new_hash[key].kind_of?(Hash) && value.kind_of?(Hash))
				new_hash[key] = recursive_merge(value, new_hash[key]);
			else
				new_hash[key] = value;
			end
		}
		return new_hash
	end
	

	# Gets the currently running request object.
	def PageRequest.current()
		return stack.last;
	end

	# Gets the topmost page request object.
	def PageRequest.top()
		return stack.first;
	end

	# Gets the overall request stack
	def PageRequest.stack()
		if (!Thread.current[:request_stack])
			Thread.current[:request_stack] = [];
		end
		return Thread.current[:request_stack];
	end

	# Translates HTTP/CGI request methods to our internal names for them.
	def PageRequest.cgi_to_request_method(method)
		return case method
			when "GET" then :GetRequest;
			when "HEAD" then :HeadRequest;
			when "POST" then :PostRequest;
			else :GetRequest;
		end
	end

	# Translates our internal request methods to HTTP/CGI correct ones.
	def PageRequest.request_method_to_cgi(method)
		return case method
			when :GetRequest then "GET";
			when :HeadRequest then "HEAD";
			when :PostRequest then "POST";
			else "GET";
		end
	end

	# Creates a PageRequest object from a cgi object. Like PageRequest.new, this
	# yields with the request object and it is considered to be active for the
	# duration of that block.
	def PageRequest.new_from_cgi(cgi)
		if (!(['GET','POST','HEAD'].include? cgi.env_table['REQUEST_METHOD']))
			# See core/fcgi-native-ext.rb for more details on how the cgi object got this far, and why we're
			# just returning an empty PageReply. This 'if' statement, as well as the following code from
			# core/fcgi-native-ext.rb should be removed if we do start supporting PUT requests:
			#
			# 	# Ultra-safe checking of the request parameter to make sure that it isn't a PUT request, which Ruby's cgi.rb does not 
			# 	# support (see the initialize_query method). If it is, we don't go any further with initialization because to call 'super' 
			# 	# would result in the child process dying. Instead, we return the CGI object as is, to be dealt with hastily in the
			# 	# PageRequest.new_from_cgi method.
			# 	if (!request.nil? && !request.env.nil? && !request.env['REQUEST_METHOD'].nil? && request.env['REQUEST_METHOD'] == 'PUT')
			# 		return self;
			# 	end
			$log.info "#{cgi.env_table['REQUEST_METHOD']} requests are not supported yet.", :error
			return PageReply.new(StringIO.new(), true);
		end
		# If you get this error, you're doing something you shouldn't. Most likely, you have just innocently
		# used 'json_obj' as a parameter name, which has been reserved for application/json form posts. This
		# is a relatively new way of posting form data which our partners Tynt are using and we might use in
		# the future. It does seem to have limited use, at least, in the Rails community. Check out the file
		# core/fcgi-native-ext.rb (and search for 'json_obj' in this file) for a more in-depth explanation, 
		# but if you're doing a regular form post and just want the damn thing to work, you probably don't 
		# need to go any further. Just take a deep breath, think of another name for your 'json_obj' parameter,
		# and all will be well. Happy coding!
		if (cgi.params.has_key?("json_obj") && cgi.env_table["CONTENT_TYPE"] !~ /application\/json/)
			raise "DO NOT use 'json_obj' as a parameter on a non-JSON Content-type";
		end

		# if this is a get request, we have to parse the request variables
		# manually, since lighttpd doesn't do it for us if it goes through a
		# 404-dispatcher, and we get better consistancy if we just always do it.
		request_uri = cgi.env_table["REQUEST_URI"].split(/\?/);
		request_hash = {}
	
		if (cgi.request_method == "POST")
			request_hash.merge!(cgi.params);
		end
		if (request_uri.length > 1)
			#This will overwrite any post variables that match keys...
			request_vars = request_uri[1].split(/&/);
			request_vars = request_vars.collect {|x| x.split(/=/); }
			request_vars.each {|x|
				if (x.length == 2)
					decoded_key = urldecode(x[0])
					request_hash[decoded_key] ||= []
					request_hash[decoded_key] << urldecode(x[1]);
				end
			}
		end

		uri = request_uri[0];
		if (uri.nil?)
			uri = '/'; # for debugging purposes only.
		end

		host = cgi.host.split(':', 2)[0]; # pull off the port number if present.
		uri = "/webrequest/#{host}#{uri}";

			
		accepted_encodings = [];
		accepted_encodings << :gzip if (cgi.env_table['HTTP_ACCEPT_ENCODING'] =~ /gzip/)
		accepted_encodings << :deflate if (cgi.env_table['HTTP_ACCEPT_ENCODING'] =~ /deflate/)
		reply = PageReply.new(cgi.stdoutput, true, accepted_encodings)
		

		return PageRequest.new(cgi_to_request_method(cgi.request_method),
							   :Internal, uri, cgi.env_table, cgi.cookies,
							   PageRequest.convert_to_nested_hash(request_hash),
							   nil, reply) {|req| 
									$site.cache.use_context({}) {
										yield req
									}
								};
	end

	# Creates a PageRequest object from a mongrel request object. Like
	# PageRequest.new, this yields with the request object and it is considered
	# to be active for the duration of that block.
	def PageRequest.new_from_mongrel(request, out)
		uri = request.params['REQUEST_URI'];
		host = request.params['HTTP_HOST'].split(':', 2)[0];
		uri = "/webrequest/#{host}#{uri}";
		$log.object [uri, host, uri]
		method = cgi_to_request_method(request.params['REQUEST_METHOD']);
		env = request.params;
		params = Mongrel::HttpRequest.query_parse(request.params['QUERY_STRING']);
		cookies = Mongrel::HttpRequest.query_parse(request.params['HTTP_COOKIE']);
		cookies.default = [];

		return PageRequest.new(method, :Internal, uri, env, cookies, params, nil, PageReply.new(out, false)) {|req| yield req};
	end

	def real_method
		return @method
	end
	def method # redefine to distinguish get from head
		return head ? :GetRequest : @method
	end
	def head # redefine to identify whether the request is a head request
		return @method == :HeadRequest
	end
	
	def impersonation?
		if(area != :Self)
			raise("Impersonation? has no meaning outside Self area");
		end
		
		if (session.anonymous?)
			return false
		end
		return (session.user.id != user.id)? session.user : false
	end
	
	def area_base_uri
		case area
		when :Self
			if (impersonation?)
				$site.admin_self_url/user.username
			else
				$site.self_url
			end
		else
			$site.area_to_url([area,user])
		end
	end

	def get_ip_as_int()
		ip = get_ip();

		parts = ip.split(".");

		if(parts.size != 4)
			return 0;
		end

		int = (parts[0].to_i<<24)|(parts[1].to_i<<16)|(parts[2].to_i<<8)|(parts[3]).to_i;

		if(int >= 2**31) # bad but needed for compatibility with the php code
			int -= 2**32;
		end

		return int;
	end

	def self.mike_ip(ip)
			parts = ip.split(".");

			if(parts.size != 4)
				return 0;
			end

			int = (parts[0].to_i<<24)|(parts[1].to_i<<16)|(parts[2].to_i<<8)|(parts[3]).to_i;

			if(int >= 2**31) # bad but needed for compatibility with the php code
				int -= 2**32;
			end

			return int;
	end
	
	
	def get_ip()
		if(@ip)
			return @ip;
		end
		
		if (!headers['REMOTE_ADDR'] && !headers['HTTP_X_FORWARDED_FOR'])
			return @ip = '127.0.0.1'
		end

		remote_addr = headers['REMOTE_ADDR'];
		http_x_forwarded_for = headers['HTTP_X_FORWARDED_FOR'];

		if(! http_x_forwarded_for.nil? && http_x_forwarded_for[0,7] != "unknown")
			ips = http_x_forwarded_for.split(",");

			ips.each { |ip|
				if(is_routable_ip(ip.strip))
					@ip = ip.strip;
				end
			};
		end

		if(!@ip)
			@ip = remote_addr
		end

		return @ip;
	end


	def is_routable_ip(ip)
		parts = ip.split(".");

		return !(	parts[0].to_i == 0 || 																							# 0.0.0.0/8
					parts[0].to_i == 10 || 																									# 10.0.0.0/8
					parts[0].to_i == 127 || 																								# 127.0.0.0/8
				(	parts[0].to_i == 172 && parts[1].to_i >= 16 && parts[1].to_i <= 31) ||	# 172.16.0.0/12
				(	parts[0].to_i == 192 && parts[1].to_i == 168) );												# 192.168.0.0/16
	end


	# Tells the client to delete their session cookie.
	def destroy_session()
		if (@session)
			cookie = @session.destroy(headers["REMOTE_ADDR"]);
			reply.set_cookie(cookie);
			@session = nil;
		end
	end
require 'yaml'
	# Creates a new session object and sends a cookie for it to the user.
	def create_session(userid, cachedlogin)
		destroy_session();
		sessionobj = Session.build(headers["REMOTE_ADDR"], userid, cachedlogin);
		reply.set_cookie(sessionobj.cookie);
		@session = sessionobj;
	end

	# Creates a modified duplicate of this request and begins a subrequest based
	# on it. Like PageRequest.new, this yields with the request object and it is
	# considered to be active for the duration of that block.
	def dup_modify(changes)
		if (changes[:selector] && changes[:uri])
			changes[:uri][/(:.*)?$/] = changes[:selector];
		elsif (changes[:uri])
			if (changes[:uri].kind_of?(String) && !changes[:uri][':'])
				changes[:uri] += ":#{@selector}";
			end
		end
		
		changes[:headers] &&= @headers.merge(changes[:headers])
		changes[:params] &&= @params.to_hash.merge(changes[:params]) 

		new_req = PageRequest.new(
			changes[:method] || @method,
			changes[:area] || @area,
			changes[:uri] || "#{@uri}:#{@selector}",
			changes[:headers] || @headers,
			@cookies,
			changes[:params] || @params,
			changes[:user] || @user,
			changes[:reply] || @reply
		) {|req|
			if (changes[:skeleton])
				req.skeleton = changes[:skeleton];
			end
			yield req;
		};

		return new_req;
	end

	# Translates the request to a string useful for debugging
	def to_s(with_depth = true)
		return "#{method}(#{area}#{uri}:#{selector})" + (with_depth ? " depth: #{request_stack.size}" : "");
	end

	# The area of the request mapped into a domain name.
	def base_url()
		return $site.area_to_url(area);
	end

	# The uri of the current request.
	def uri()
		if (@uri == '/')
			return '/index';
		else
			return @uri;
		end
	end

	class AccessLevelError < PageError
		attr_reader :why, :url_to_fix, :request_user
		def initialize(why, url_to_fix = nil, request_user = PageRequest.current.user)
			@why = why
			@url_to_fix = url_to_fix
			@request_user = request_user
			super(403)
		end
	end
	
	# Determines if the request meets the specified access level (see PageHandler.access_level).
	# Raises an AccessLevelError with details about why access was denied. Otherwise,
	# returns true.
	def has_access(handler)
		error = case handler.level
		when :Any then
			nil
		when :NotLoggedIn then
			(session.anonymous?) 	|| AccessLevelError.new("Should not be logged in to access this page", $site.www_url/"logout.php")
		when :LoggedIn then
			(!session.anonymous?)	|| AccessLevelError.new("Should be logged in to access this page", $site.www_url/"login.php")
		when :Activated then
			if (session.anonymous?)
				AccessLevelError.new("Should be logged in and activated to access this page", $site.www_url/"login.php")
			else
				if (session.user.state != 'active')
					AccessLevelError.new("Should be activated to access this page", $site.www_url/:account/:activate)
				else
					nil
				end
			end
		when :Plus then
			(!session.anonymous? && session.user.plus?) ||
			                           AccessLevelError.new("Must have Plus to access this page")
		when :Admin then
			(!session.anonymous? && session.has_priv?(*handler.priv)) ||
			                           AccessLevelError.new("Must be an admin to access this page")
		when :DebugInfo then
			(!session.anonymous? && $site.debug_user?(session.user.id)) ||
			                           AccessLevelError.new("Must be a debug info user to access this page")
		when :IsUser then
			if (session.anonymous?)
				AccessLevelError.new("Should be logged in to access this page", $site.www_url/"login.php")
			else
				(!user.anonymous? && (session.user.id == user.id ||
            (handler.priv && session.has_priv?(*handler.priv)))) ||
			                           AccessLevelError.new("Must be the user this area points to to use this page")
			end
		when :IsFriend then
			(!session.anonymous? && !user.anonymous? && user.friends.include?(session.user)) ||
			                           AccessLevelError.new("Must be a friend of the user to use this page")
		else 						   AccessLevelError.new("Unknown restriction prevents you from seeing this page")
		end
		if (error.kind_of?(AccessLevelError))
			raise error, error.why
		end
		return true
	end

	# returns the first content type that is safe according to the ACCEPT headers
	# sent by the client, if any, or the last one otherwise.
	def negotiate_content_type(*types)
		accept_headers = headers["HTTP_ACCEPT"] || nil;
		if (accept_headers)
			accept_headers = accept_headers.split(',');
			accept_choices = {};
			accept_headers.each {|choice|
				choice = choice.split(';', 2);
				if (choice.length == 1)
					choice.push(100);
				else
					q = /q=([0-9\.]+)/.match(choice[1]) || [nil, "1"];
					choice[1] = q[1].to_f * 100;
				end
				$log.info("Client accepts type #{choice[0]} at quality #{choice[1]}", :debug, :pagehandler);
				accept_choices[choice[0]] = choice[1];
			}
			best = nil;
			types.each {|type|
				$log.info("Negotiating type #{type}...", :debug, :pagehandler);
				if (quality = accept_choices[type]) #  || accept_choices['*/*'] removed because IE doesn't give it a quality and never says it can take text/html.
					$log.info("Type #{type} possible with quality #{quality}", :debug, :pagehandler);
					if (!best || best[1] < quality)
						$log.info("Type #{type} is the best so far.", :debug, :pagehandler);
						best = [type, quality];
					end
				end
			}
			if (best)
				return best[0];
			end
		end
		return types.last;
	end

	def html_content_type()
		return negotiate_content_type(MimeType::HTML);
	end
	def xhtml_content_type()
		return negotiate_content_type(MimeType::XHTML, MimeType::HTML);
	end
	def extension_content_type(filename)
		if (filename)
			ext = filename.sub(/^.*(\.[a-zA-Z]+)$/, '\1').downcase
		else
			ext = nil;
		end
		
		return MimeTypeExtensions[ext] || MimeTypeExtensions['default']
	end
end

class PageReply
	# Cookies that have been set in processing the request. Use PageReply#set_cookie
	# to set them or merge_cookies to merge them
	attr :cookies
	# A hash of headers to be sent to the client
	attr_reader :headers;
	# An IO handle that output from this request should be sent to.
	attr_reader :out;

	# Whether or not headers should be prefixed to the output before sending to
	# the client.
	attr_reader :mix_headers;

	def initialize(out = nil, mix_headers = true, supported_encodings = [])
		if(out.nil?())
			out = StringIO.new();
		end

		@cookies = {};
		@mix_headers = mix_headers;
		@headers = {'Status' => '200 OK', 'Content-Type' => "text/html"};

		@headers_sent = false;

		out.extend BufferIO;
		# If sending unbuffered data, see bufferio.rb, BufferIO#buffer=.
		out.initialize_buffer {
			if (@headers['Content-Type'] =~ /^text\// && !@headers['Content-Encoding'])
				if (supported_encodings.index(:gzip))
					out.gzip_buffer 
					@headers['Content-Encoding'] = "gzip"
				elsif (supported_encodings.index(:deflate))
					out.deflate_buffer 
					@headers['Content-Encoding'] = "deflate"
				end
			end
			send_reply
		}
		out.clear_buffer(); # clear it out, we're in a new request now.
		@out = out;
	end
	
	def status()
		return self.headers['Status']
	end

	def merge_cookies(other_reply)
		other_reply.cookies.values.each {|cookie|
			self.set_cookie(cookie)
		}
	end
	# Set a cookie to be sent back to the user on this request.
	# cookie should be a CGI::Cookie object.
	def set_cookie(cookie)
		@cookies[cookie.name] = cookie;
	end

	# True if headers have been mixed into the output, thus preventing new headers
	# from being added.
	def headers_sent?()
		return @headers_sent;
	end

	# returns true if the status is 200.
	def ok?()
		return @headers["Status"] && /^200.*$/.match(@headers["Status"].to_s);
	end

	#
	# Send the reply, headers and cookies if it hasn't already
	# been sent
	def send_reply
		if @mix_headers
			self.send_headers() unless headers_sent?
			self.send_cookies() unless @cookies_sent
			self.finalize_output
		end
	end

	#  Include Cookies into output
	def send_cookies()
			@cookies.each {|name, cookie|
				@out.raw_print("Set-Cookie: #{cookie}\r\n");
			}

			@cookies_sent = true
	end
	# Injects headers into the output


	def send_headers()
		@headers.each {|key, val|
			@out.raw_print("#{key}: #{val}\r\n");
		}
		@headers_sent = true;
	end


	def finalize_output
		@out.raw_print("\r\n");
	end

	# only runs the block if this is NOT a head request
	def body
		if (!PageRequest.current.head)
			yield
		end
	end
end

class ErrorLog
	# Logs to a page being displayed if there is one, otherwise fails.
	def log_page(realstr, level, facility)
		if (PageRequest.current)
			str = detailed_string(facility, level, realstr, Time.now);
			PageRequest.current.reply.out.puts("<pre>#{htmlencode(str)}</pre>");
		end
	end

	# Logs to the actual request object in a way that allows for actually getting
	# meaningful information out of it.
	def request_buffer(name, *loginfo)
		if (req = PageRequest.top)
			if (!req.log.has_key?(name))
				req.log[name] = [];
			end

			req.log[name].push(LogBufferItem.new(Time.now(), *loginfo));
		end
		if (req = PageRequest.current)
			if (!req.current_log.has_key?(name))
				req.current_log[name] = [];
			end

			req.current_log[name].push(LogBufferItem.new(Time.now(), *loginfo));
		end
	end
end
