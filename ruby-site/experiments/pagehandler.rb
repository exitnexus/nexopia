require "auth"

# This is a simple class used pretty much only to tell whether or not
# an input to a pagehandler should be captured and passed to the handler itself
class PageHandlerInput
	attr :key;

	def initialize(key)
		@key = key;
	end
end

# HandlerTreeNode is used to build a tree of pagehandler information. Nodes are
# added to it via add_node, and fetched via find_node. Both functions are recursive
# and add_node constructs the tree as it inserts.
class HandlerTreeNode
	def initialize()
		@child_nodes = {};
		@this_node = nil;
	end

	# path is an array of path components under which to add the item. Does a
	# recursive lookup to find the correct place to put the node and creates
	# items as it goes.
	def add_node(path, handler_info)
		if (path.length == 0)
			@this_node = handler_info;
			return;
		end
		# Find the node, insert if necessary, and then pass it on or set the handler to it.
		node = @child_nodes.fetch(path.first) {
			@child_nodes[path.first] = HandlerTreeNode.new();
		}
		node.add_node(path[1, path.length - 1], handler_info);
	end

	# recursively searches the tree from this node for an item that matches
	# the given path as completely as possible. It will use the deepest
	# match it can find. If you pass a block to it, matches will be
	# passed to it and can be rejected be the caller.
	def find_node(path, inputs = [])
		# break out early if we're on a complete match.
		if (path.length == 0)
			return path, @this_node, inputs;
		end
		cur_path = path.first;
		remain = path[1, path.length - 1];

		match_remain = path;
		match_val = @this_node;
		match_inputs = inputs;

		# because our nodes may have regexes or whatever else, we have to loop
		# through it rather than use the builtin lookup functions
		@child_nodes.each_pair {|key, child|
			returnit = false;
			if (key.kind_of? PageHandlerInput)
				key = key.key;
				returnit = true;
			end
			if (key === cur_path)
				newmatch_inputs = match_inputs;
				if (returnit)
					newmatch_inputs = newmatch_inputs + [cur_path];
				end
				newmatch_remain, newmatch_val, newmatch_inputs = child.find_node(remain, newmatch_inputs);
				if (newmatch_remain.length < match_remain.length && !newmatch_val.nil?)
					if (!block_given? || yield(newmatch_val))
						match_remain, match_val, match_inputs = newmatch_remain, newmatch_val, newmatch_inputs;
					end
				end
			end
		}
		return match_remain, match_val, match_inputs;
	end
end

# this class is .extend()ed into the IO class handed to the page handler
# in order to overload the output functions to actually print headers before
# the first output.
module PageHandlerIO
	alias raw_puts puts;
	alias raw_print print;
	alias raw_printf printf;

	def initialize_page_handler(page_handler)
		@page_handler = page_handler;
	end

	def puts(*args)
		@page_handler.send_headers();
		self.send(:raw_puts, *args);
	end

	def print(*args)
		@page_handler.send_headers();
		self.send(:raw_print, *args);
	end

	def printf(*args)
		@page_handler.send_headers();
		self.send(:raw_printf, *args);
	end
end

# I don't think this should really be necessary, but these calls actually go into
# C code which I think bypasses our overriding of stdout.
module Kernel
	alias raw_puts puts;
	alias raw_print print;
	alias raw_printf printf;

	def puts(*args)
		$stdout.puts(*args);
	end

	def print(*args)
		$stdout.print(*args);
	end

	def printf(*args)
		$stdout.printf(*args);
	end
end

# PageHandler is the core of the pagehandler system. It loads all the relevant
# files from the file system and initializes their classes, collecting information
# about the pages they handle. When a request comes in, it also discovers the
# correct class to call and calls it with the cgi request.
class PageHandler
	protected
	attr :output;
	attr :cgi;
	attr :session;

	# be careful overloading initialize. If you need to for some reason, make
	# sure you pass along these inputs to super() or everything will break.
	# For routine initialization, use page_initialize instead.
	def initialize(output, cgi)
		output.extend(PageHandlerIO);
		output.initialize_page_handler(self);

		@output = output;
		@cgi = cgi;
		@headers = {'type' => 'text/html', 'status' => '200 OK', 'cookie' => []};
		@headers_sent = false;

		@session = Session.get(self);
		$session = @session; # this should go away at some point.

		page_initialize();
	end

	# base class version does nothing. Overload this for class initialization.
	# child classes should not directly overload initialize without good reason.
	def page_initialize()
	end

	public

	def headers_sent?()
		return @headers_sent;
	end

	def send_headers()
		if (!headers_sent?)
			@headers_sent = true;
			output.puts cgi.header(@headers);
		end
	end

	def header(name, val)
		if (!headers_sent?)
			@headers[name] = val;
		else
			#throw some kind of error here
		end
	end

	def set_cookie(name, value, expire, path, domain)
		cookie = CGI::Cookie::new('name'    => name,
								 'value'   => value,
								 'path'    => path,
								 'domain'  => domain,
								 'expires' => expire);
		@headers['cookie'].push(cookie);
	end

	def cookies()
		return @cgi.cookies;
	end

	def cookie(key)
		cookie = $cgi.cookies[key]
		if (cookie == [])
			return nil;
		end
		return cookie.value
	end

	def params()
		return @cgi.params;
	end

	def request_method()
		return @cgi.request_method;
	end

	public

	#:Any, :NotLoggedIn, :LoggedIn,
	# :Plus, or :Admin, :IsUser
	def run_handler(handler, inputs)
		has_access =
			case handler.level
			when :Any then 				true;
			when :NotLoggedIn then 		session.nil?;
			when :LoggedIn then			!session.nil?;
			when :Plus then				(!session.nil? && session.get_user.plus?);
			when :Admin then 			false; # Needs implementing
			when :IsUser then			false; # Needs implementing
			end
		if (has_access)
			send(handler.method_name, *inputs); # add other arguments.
		else
			puts("Access denied.");
			# todo: throw some kind of error.
		end
	end

	protected

	# This is the singleton part of PageHandler
	PAGEHANDLER_SOURCEDIR = "pagehandlers";
	PageHandlerInfo = Struct.new('PageHandlerInfo', :type, :area, :level, :priv, :class_name, :method_name);
	@@handler_classes = {};
	@@handler_trees = {
		:Public => HandlerTreeNode.new(),
		:User => HandlerTreeNode.new(),
		:Admin => HandlerTreeNode.new(),
	};

=begin
	 use this with a block to configure the pagehandler object to handle
	 requests. base_path is a component of the path common to all of the handlers'
	 parts.
	 A declare_handlers section has the following format:
	 declare_handlers("galleries") {
		area :User
		access_level :Any

		# http://users.nexopia.com/username/galleries
		handle :GetRequest, :user_gallery_list
		# http://users.nexopia.com/username/galleries/galleryname
		handle :GetRequest, :user_gallery, input(/.*/)
		# http://users.nexopia.com/username/galleries/galleryname/picid
		handle :GetRequest, :user_gallery_picture, input(/.*/), input(/[0-9]+/)
	}
=end
	def PageHandler.declare_handlers(base_path)
		@cur_area = :Public;
		@cur_level = :Any;
		@cur_priv = nil;
		@cur_base_path = base_path.split('/');
		@@handler_classes[self.name] = self;
		yield;
	end

	# the area under which this page handler works. Either :Public, :User, or
	# :Admin. These areas will be identified by the url used to get to them. Ie.
	# http://www.nexopia.com/..., http://users.nexopia.com/username/..., and
	# http://admin.nexopia.com/... respectively.
	def PageHandler.area(area_marker)
		@cur_area = case area_marker
			when :Public, :User, :Admin
				area_marker;
			else
				@cur_area;
			end
	end

	# The access level required to view the page. :Any, :NotLoggedIn, :LoggedIn,
	# :Plus, or :Admin, :IsUser. These are fairly self-explanatory except :IsUser,
	# which will be set if the user viewing the page is also the user being viewed
	# (for the :User area).
	def PageHandler.access_level(level_marker, admin_priv = nil)
		@cur_level = case level_marker
			when :Any, :NotLoggedIn, :LoggedIn, :Plus, :Admin, :IsUser
				level_marker;
			else
				@cur_level;
			end
		if (admin_priv != nil)
			@cur_priv = admin_priv;
		end
	end

	# identifies a member function to handle a particular form of URL under
	# the base_name. Type is the request method (:GetRequest, :PostRequest),
	# method_name is a symbol identifying the member function that handles
	# the request, and path_components is a varargs of the path components
	# that should lead to this handler actually handling the request. Any
	# wrapped in a call to input() will be passed as arguments to the function.
	def PageHandler.handle(type, method_name, *path_components)
		info = PageHandlerInfo.new(type, @cur_area, @cur_level, @cur_priv, self.name, method_name);

		@@handler_trees[@cur_area].add_node(@cur_base_path + path_components, info);
	end

	# use this to indicate that an argument to handle should be passed into the
	# handler function.
	def PageHandler.input(key)
		return PageHandlerInput.new(key);
	end

	public

	# Given a cgi object, finds the pagehandler responsible for the URL given
	# and calls it.
	def PageHandler.execute(cgi)
		page_name = cgi.script_name;
		if (page_name.nil?)
			page_name = '/'; # for debugging purposes only.
		end
		if (page_name == '/')
			page_name = 'index';
		else
			page_name = page_name[1, page_name.length - 1]; # trim off leading /
			page_name.chomp!('/'); # trim off trailing /
		end
		path = page_name.split('/');

		host = cgi.host;
		area = case host
		       when $config.user_domain, $config.plus_user_domain then :User
		       when $config.admin_domain then :Admin
		       else :Public
			end

		remain, handler, inputs = @@handler_trees[area].find_node(path) { |possibility|
			possibility.type == :GetRequest || (possibility.type == :PostRequest && cgi.request_method == "POST");
		}
		if (!handler.nil?)
			# find the object for the class given
			generator = @@handler_classes[handler.class_name];
			object = generator.new($stdout, cgi);
			# call the method on it
			inputs += remain;
			object.run_handler(handler, inputs);
		end
	end

	private

	# From this point on loads up the child handlers based on the directory tree PAGEHANDLER_SOURCEDIR
	def PageHandler.find_handlers(path)
		Dir["#{path}/*"].each {|file|
			if (File.ftype(file) == 'directory')
				files += find_handlers(file);
			else
				yield(file);
			end
		}
	end
	$debugout.print("Searching #{PAGEHANDLER_SOURCEDIR} for pagehandler files.\n");
	find_handlers(PAGEHANDLER_SOURCEDIR) {|file|
		$debugout.print("Found #{file}, loading.\n");
		require(file);
	}
end
