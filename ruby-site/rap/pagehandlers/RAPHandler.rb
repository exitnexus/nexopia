#!/usr/bin/env ruby
#
#  Created by Sean Healy
#  sean@pointscape.org
#
#  RAPminiHandler.rb
#  -------
#  Pagehandler for RAPmini.
#
###

require 'rubygems'
require 'RAP'

lib_want :Gallery, 'gallery_pic'

class Array
	def to_hash
		h = {};
		each_with_index{|e,i|
			h[i] = e;
		}
		h
	end
end
class PhpBinding
	def initialize(bind)
		@bind = bind
	end
	def method_missing(name)
		name = name.to_s.gsub(/^g_/, '$')
		eval(name, @bind)
	end
end

class RAPminiHandler < PageHandler
	declare_handlers("") {
		area :Public
		access_level :Any
		
		page :GetRequest, :Php, :rap_missing, input(/.*\.php/), remain;
		handle :GetRequest, :bannerview, "bannerview.php"
		handle :GetRequest, :rap_about, "rap", "about"
		handle :GetRequest, :head, "header"

		area :Internal
		handle :GetRequest, :internal_header, "header"
		handle :GetRequest, :block, "blocks", input(String)
		
	}
	
	def bannerview
		srequest = prepare_request("bannerview.php")

		begin
			RapModule::php.register_object self, "rap_pagehandler"
			RapModule::php.register_object(srequest.post, "ruby_post");
			RapModule::php.register_object($site, "ruby_site_obj");
			RapModule::php.register_object(PhpBinding.new(binding), "Ruby")
			
			results = RapModule::php.exec "bannerview.php", srequest 
		rescue
			$log.info $!, :error
			$log.info $!.backtrace, :error
		end
				
		headers = parse_headers(results)
		handle_php_headers(headers)
		puts results[:output];
	end
	
	def rap_about
		print RAP::about
	end
	
	MonthValue = { 'JAN' => 1, 'FEB' => 2, 'MAR' => 3, 'APR' => 4, 'MAY' => 5, 'JUN' => 6, 'JUL' => 7, 'AUG' => 8, 'SEP' => 9, 'OCT' =>10, 'NOV' =>11, 'DEC' =>12}
	def php_date(date)
		if /\A\s*
		(?:Mon|Tue|Wed|Thu|Fri|Sat|Sun),\x20
		(\d{2})-
		(Jan|Feb|Mar|Apr|May|Jun|Jul|Aug|Sep|Oct|Nov|Dec)-
		(\d{4})\x20
		(\d{2}):(\d{2}):(\d{2})\x20
			GMT
			\s*\z/ix =~ date
			Time.utc($3.to_i, MonthValue[$2.upcase], $1.to_i,
					$4.to_i, $5.to_i, $6.to_i)
		else
			raise ArgumentError.new("not RFC 2616 compliant date: #{date.inspect}")
		end
	end
	
	def parse_headers(results)
		headers = {};
		
		results[:headers].split("\n").each do |header|
			key,value = header.split(":", 2)
			if(key && value)
				headers[key.strip] ||= []
				headers[key.strip] << value.strip;
			else
				$log.info "Invalid header received through rap: #{header}", :warning
			end
		end
		
		return headers
	end
	
	def head
		t = Template::instance("nexoskel", "frame_header");
		
		t.skindata = SkinMediator.request_all_values("nexoskel", PageRequest.current.session.user.skin)
		t.cmodule = "nexoskel";
		t.reporev = Time.now.to_i;
		t.skin = {};
		t.skin['cellspacing'] = "8";

		puts t.display();
	end

	def internal_header
		t = Template::instance("nexoskel", "header");
		
		t.skindata = SkinMediator.request_all_values("nexoskel", PageRequest.current.session.user.skin)
		t.cmodule = "nexoskel";
		t.reporev = Time.now.to_i;
		t.skin = {};
		t.skin['cellspacing'] = "8";

		puts t.display();
	end
	
	@@blocks = {"incPlusBlock" => "plus_block",
				"incMyTreatBlock" => "mytreat_block",
				"incNewestMembersBlock" => "new_members_block",
				"incFriendBlock" => "friends_block",
				"incRecentUpdateProfileBlock" => "list_users_block",
				"incSortBlock" => "sort_block",
				"incMsgBlock" => "msg_block",
				"incModBlock" => "mod_block",
				"incSubscriptionBlock" => "subscriptions_block",
				"incPollBlock" => "poll_block",
				"incLoginBlock" => "login_block"
				} 
	@@names = {"incPlusBlock" => "Plus",
				"incMyTreatBlock" => "MyTreat",
				"incNewestMembersBlock" => "New Members",
				"incFriendBlock" => "Friends",
				"incRecentUpdateProfileBlock" => "Updated Profiles",
				"incSortBlock" => "User Search",
				"incMsgBlock" => "Messages",
				"incModBlock" => "Moderator",
				"incSubscriptionBlock" => "Subscriptions",
				"incPollBlock" => "Polls",
				"incLoginBlock" => "Log in"
				} 
	def block(name)
		name = name.strip
		t = Template::instance("nexoskel", "block");
		t.header = @@names[name]
		if (!@@blocks[name])
			$log.info "Bad block: '#{name}'", :error
			return
		end
		t2 = Template::instance("nexoskel", @@blocks[name]);
		t.block_contents = t2.display();
		puts t.display();
	end
	
	def prep_variables_r(params)		
		files = {};
		vars = {};
		
		# Somehow we can get an Array as a parameter just like we can get a Hash. In the spirit of not
		# doing drastic modifications of complex code that I simply don't understand, I am hacking it
		# out in the first part of this loop. I explicitly check if the params method parameter is an
		# Array. If it is, I know that it doesn't follow the "key,value" format that a Hash would, so
		# the "k" value below will actually be the parameter. I then use a counter value as the key
		# to the "files" or "vars" hash because the methods that call this method expect a hash returned.
		# It seems to work fine, and at the very least, it shouldn't break anything that wasn't broken
		# before, as before we were blind to Arrays through RAP.
		#
		# TODO: When someone understands this code more and has a little time, fix the hackiness here
		# to either make sure we never get an Array passed through (I though PHP only used hashes?!?)
		# or deal more elegantly with either possibility.
		array_index = 0;
		params.each {|k, param|
			if (params.kind_of? Array)
				param = k;
				k = array_index;
				array_index = array_index + 1;
			end
				
			if (param.respond_to?(:original_filename))
				files[k] = param
			elsif (param.kind_of?(Hash) || param.kind_of?(Array))
				r_vars, r_files = prep_variables_r(param);
				r_files.each_pair{|index, f|
					files[k] ||= []
					files[k] << f 
				}
				vars[k] = r_vars
			else
				vars[k] = param
			end
		}
		return [vars, files]
	end
	
	def unhash_contents(h)
		return h if !h.is_a?(Hash)
		a = Array.new()
		for i in 0..h.length - 1
			key = i.to_s
			if !h.has_key?(key)
				# Couldn't do it, array indices are messed up
				return h
			end
			a << h[key]
		end
		return a
	end
	
	def prepare_request(match, remain = nil)
		post    = {}
		get     = {}
		files   = {}
		cookies = {}

		paramhash = params.to_hash
		if(paramhash.length > 0)
			vars, files = prep_variables_r(paramhash)
			if (request.method == :PostRequest)
				post = vars;
			else
				get = vars;
			end
			# Undo hashing of subelements
			vars.each { |k, v|
				vars[k] = unhash_contents(v) if v.is_a?(Hash)
			}
		end
		headers = request.headers;
		
		request.cookies.each{|name, value|
			cookies[name] = value[0];
		}
		
		srequest = RAP::PHPRequest.new;
		
		user_obj = {};
		if (not session.user.anonymous?)
			session.user.class.columns.each_value {|column|
				user_obj["#{column.name}"] = session.user.send(:"#{column.name}");
			}
			user_obj['interests'] = session.user.interests.to_a.map{|i| i.interestid}.join(",");
			user_obj['username'] = session.user.username;
			user_obj['loggedIn'] = (session.user.state.to_s == "active");
			user_obj['halfLoggedIn'] = true;
			user_obj['sessionkey'] = session.sessionid;
			user_obj['sessionlockip'] = session.lockip;
			user_obj['premium'] = session.user.plus?;
			user_obj['debug'] = false;
			user_obj['userid'] = session.user.userid;
			user_obj['limitads'] = session.user.limitads;
			user_obj['trustjstimezone'] = session.user.trustjstimezone;
		else
			user_obj['loggedIn'] = false;
			user_obj['halfLoggedIn'] = false;
			user_obj['timeoffset'] = 0;
			user_obj['limitads'] = false;
			user_obj['premium'] = false;
			user_obj['debug'] = false;
			user_obj['userid'] = -1;
		end		
		
		srequest.files = {};
		files.each{|handle, io|
			srequest.files[handle] = { 
				'name' => {},
				'type' => {},
				'size' => {},
				'tmp_name' => {},
				'error' => {}
			}
			if (io.kind_of? Array)
				io.each_with_index{|io,index|
					if (io.kind_of? Tempfile)
						tmpfile = io
					else
						f = Tempfile.new("input_file")
						f.write(io.read)
						f.flush
						f.rewind;
						tmpfile = f;
					end
			
					srequest.files[handle]['name'][index] = io.original_filename
					srequest.files[handle]['type'][index] = ""
					srequest.files[handle]['size'][index] = io.length
					srequest.files[handle]['tmp_name'][index] = tmpfile.path
					srequest.files[handle]['error'][index] = ''
				}
			else
				if (io.kind_of? Tempfile)
					tmpfile = io
				else
					f = Tempfile.new("input_file")
					f.write(io.read)
					f.flush
					f.rewind;
					tmpfile = f;
				end
		
				srequest.files[handle]['name'] = io.original_filename
				srequest.files[handle]['type'] = ""
				srequest.files[handle]['size'] = io.length
				srequest.files[handle]['tmp_name'] = tmpfile.path
				srequest.files[handle]['error'] = ''				
			end
		}

		filename = "/" + match.to_s();
		
		needed_modules = PageHandler.modules.collect{|mod| mod.javascript_dependencies + [mod] }.flatten.uniq.compact
		paths = needed_modules.map{|mod|
			mod.javascript_config.javascript_paths
		}
		paths = paths.flatten.uniq
		
		srequest.globals = {"myvar" => ""}
		srequest.ruby = {"auth" => user_obj, "scripts" => paths}
		srequest.get = get
		srequest.post = post
		srequest.server = request.headers
		srequest.server.delete('HTTP_ACCEPT_ENCODING') #php should NOT be gzipping the data before sending it to us
		srequest.server['NEXOPIA_PHP_CONFIG'] = $site.config.rap_php_config if $site.config.rap_php_config
		srequest.server['HTTP_HOST'] = $site.config.www_url.to_s
		srequest.server['SCRIPT_NAME'] = filename
		srequest.server['SCRIPT_FILENAME'] = "#{$site.config.legacy_base_dir}#{filename}"
		srequest.server['DOCUMENT_ROOT'] = "#{$site.config.legacy_base_dir}/"
		srequest.server['ORIG_PATH_INFO'] = ""
		srequest.server['ORIG_SCRIPT_NAME'] = filename
		srequest.server['ORIG_SCRIPT_FILENAME'] = "#{$site.config.legacy_base_dir}#{filename}"
		srequest.server['PATH_TRANSLATED'] = ""
		srequest.cookies = cookies
		return srequest;
	end

	class Poll
		def has_voted?(*args)
			return false;
		end
		def answers
			return [];
		end
		def id
			return 0;
		end
		def key
			return 0;
		end
		def question
			"blah"
		end
	end
	
	def handle_php_headers(headers)
		headers.each_pair{|key, values|
			values.each{|value|
				if (key.downcase == "set-cookie")
					cookie = CGI::Cookie.new();
					cookie.name = value.split(";")[0].split("=")[0]
					cookie.value = value.split(";")[0].split("=")[1]
					begin
						cookie.expires = php_date(value.split(";")[1].split("=")[1])
					rescue 
						cookie.expires = Time.now + (86400*30);
					end
					cookie.domain = ".#{$site.config.cookie_domain}"
					#cookie.domain = value.split(";")[2].split("=")[1]
					cookie.path = "/"
					reply.set_cookie(cookie)
				else
					reply.headers[key] = value;
				end
				if (key.downcase == "location")
					reply.headers["Status"] = 302
				end
			}
		}
	end
	
	def self.RAP_page(body, headers)
		
		t = nil
		if (PageRequest.current.session.user.skintype === 'frames')
			t = Template::instance("nexoskel", "page_base_frames")
		else
			t = Template::instance("nexoskel", "page_base_noframes")
		end
		
		t.skindata = SkinMediator.request_all_values("nexoskel", PageRequest.current.session.user.skin)
		t.cmodule = "nexoskel";
		t.reporev = Time.now.to_i;
		t.right_blocks = [];
		t.left_blocks = [];
		t.body_center = false;
		t.skin = {};
		t.skin['cellspacing'] = "8";
		
		$log.info "Received the following headers from PHP:", :debug
		$log.object headers, :debug
		headers.each{|key, values|
			values.each{|value|
				if (key === "X-LeftBlocks")
					t.left_blocks = value.split(",");
				end
				if (key === "X-Center")
					if (value === "1")
						value = "100%"
					end
					t.body_center = value;
				end
				if (key === "X-width")
					t.body_center = value;
				end
				if (key === "X-RightBlocks")
					t.right_blocks = value.split(",");
				end
			}
		}
		if(!t.right_blocks.empty? || PageRequest.current.session.user.showrightblocks)
			t.right_blocks << "incMsgBlock"
			t.right_blocks << "incFriendBlock"
			t.right_blocks << "incModBlock"
			t.right_blocks << "incSubscriptionBlock"
		end
		
		t.left_blocks.delete("incLoginBlock") if (!PageRequest.current.session.user.anonymous?)
		
		$poll = Poll.new
		
		if (t.body_center)
			center = Template::instance("nexoskel", "inc_center");
			center.width = t.body_center;
			center.body = body;
			body = center.display();
		end
		
		t.body = body;
		
		return t.display();
	end
	
	def rap_missing(match, remain)
		PageHandler.top[:modules] << SiteModuleBase.get(:Interstitial);
		
		srequest = prepare_request(match, remain);

		begin
			RapModule::php.register_object(srequest.post, "ruby_post");
			RapModule::php.register_object(self, "rap_pagehandler");
			RapModule::php.register_object($site, "ruby_site_obj");
			RapModule::php.register_object(PhpBinding.new(binding), "Ruby")
			results = RapModule::php.exec("#{match}", srequest);
		rescue
			$log.info $!, :error
			$log.info $!.backtrace, :error
			raise
		end
		
		headers = parse_headers(results)
		handle_php_headers(headers)

		#TODO: Put this back to how it was
		puts results[:output]
#		puts RAPminiHandler::RAP_page(results[:output], headers)
	end
	
	def build_session(user_id, cached_login, lock_ip)
		new_session = Session.build(PageRequest.current.get_ip_as_int(), user_id, cached_login, lock_ip);
		if(new_session.cookie.nil?())
			$log.info("New cookie was nil!")
		end
		PageRequest.current.reply.set_cookie(new_session.cookie);
	end
	
	def destroy_session(user_id, key)
		temp_session = Session.get("#{key}:#{user_id}");
		if(!temp_session.nil?())
			request_ip = PageRequest.current.get_ip_as_int();
			expired_cookie = temp_session.destroy(request_ip);
			if(!expired_cookie.nil?())
				PageRequest.current.reply.set_cookie(expired_cookie);
			end
		end
	end
	
	def ruby_log(s)
		$log.info(s, :warning);
	end

	def delete_gallery_pics(pics)
		if (site_module_loaded?(:Gallery))
			pics = pics.values.map {|pic| pic.split(':').map {|id| id.to_i}}
			pics = Gallery::Pic.find(*pics)
			pics.each {|pic| pic.delete}
		end
	end
	
	def ruby_log_object(obj)
		$log.object(obj, :warning);
	end
	
	def do_an_add(one, two)
		return one + two
	end
		
	def get_ruby_object
		return self
	end
	
	def get_an_array
		return [1, 4, "cat"]
	end
	
	def do_something_with_an_array(in_val)
		return "#{in_val.to_s}"
	end	
end
