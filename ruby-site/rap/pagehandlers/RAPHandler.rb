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

class Array
	def to_hash
		h = {};
		each_with_index{|e,i|
			h[i] = e;
		}
		h
	end
end
class RAPminiHandler < PageHandler
	declare_handlers("") {
		area :Public
		access_level :Any
		
		page :GetRequest, :Php, :rap_missing,	input(/.*\.php/)
		handle :GetRequest, :bannerview, "bannerview.php"
		handle :GetRequest, :rap_about,		"rap", "about"
		handle :GetRequest, :head, "header"
		
		area :Internal
		handle :GetRequest, :internal_header, "header"
		handle :GetRequest, :block, "blocks", input(String)
		
	}
	
	def bannerview
		srequest = prepare_request("bannerview.php")

		begin
			RapModule::php.register_object self, "rap_pagehandler"
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
			headers[key.strip] ||= []
			headers[key.strip] << value.strip;
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
		$log.info "Prep variables:"
		files = {};
		vars = {};
		
		params.each {|k, param|
			$log.info "#{k}"
			$log.object param
			$log.object param.respond_to?(:original_filename)
			if (param.respond_to?(:original_filename))
				files[k] = param
			elsif (param.kind_of? Hash)
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
		$log.object vars;
		$log.object files;
		$log.info "------------------------"
		return [vars, files]
	end
	
	def prepare_request(*match)
		post = Hash.new
		get = Hash.new
		files = Hash.new
		cookies = Hash.new

		$log.object params.to_hash, :critical
		if params.to_hash.length > 0 then
			get, files = prep_variables_r(params.to_hash)
			if (request.method == :PostRequest)
				post = get;
			end
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
			user_obj["interests"] = session.user.interests.to_a.map{|i| i.interestid}.join(",");
			user_obj['username'] = session.user.username;
			if (session.user.state.to_s == "active")
				user_obj['loggedIn'] = true
			else
				user_obj['loggedIn'] = false
			end
			user_obj['halfLoggedIn'] = true;
			user_obj['sessionkey'] = session.sessionid;
			user_obj['sessionlockip'] = session.lockip;
			user_obj['premium'] = session.user.plus?;
			user_obj['debug'] = (session.user.userid == 203)
		else
			user_obj['loggedIn'] = false;
			user_obj['halfLoggedIn'] = false;
			user_obj['timeoffset']=0;
			user_obj['limitads'] = false;
			user_obj['premium'] = false;
			user_obj['debug'] = false;
			user_obj['userid'] = -1;
		end		

		srequest.files = {};
		$log.info "These are the files:", :critical
		$log.object files;
		files.each{|handle, arr|
			srequest.files[handle] = { 
				'name' => {},
				'type' => {},
				'size' => {},
				'tmp_name' => {},
				'error' => {}
			}
			[*arr].each_with_index{|io,index|
				$log.info "Adding file #{handle}", :error
				$log.object io, :error;
				if (io.kind_of? Tempfile || (!io.kind_of? File))
					f = File.new("/tmp/input_file_#{handle}.#{Process.pid}", "w")
					f.write(io.read)
					f.flush
					f.rewind;
					tmpfile = f;
				else
					tmpfile = io;
				end
				
				srequest.files[handle]['name'][index] = io.original_filename
				srequest.files[handle]['type'][index] = ""
				srequest.files[handle]['size'][index] = io.length
				srequest.files[handle]['tmp_name'][index] = tmpfile.path
				srequest.files[handle]['error'][index] = ''
			}
		}
		 
		srequest.globals = {"myvar" => ""}
		srequest.ruby = {"auth" => user_obj}
		srequest.get = get
		srequest.post = post
		srequest.server = request.headers
		srequest.server['SCRIPT_NAME'] = "/#{match}"
		srequest.server['SCRIPT_FILENAME'] = "#{$site.config.svn_base_dir}/php-site/public_html/#{match}"
		srequest.server['DOCUMENT_ROOT'] = "#{$site.config.svn_base_dir}/php-site/public_html/"
		srequest.server['ORIG_PATH_INFO'] = ""
		srequest.server['ORIG_SCRIPT_NAME'] = "/#{match}"
		srequest.server['ORIG_SCRIPT_FILENAME'] = "#{$site.config.svn_base_dir}/php-site/public_html/#{match}"
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
	
	def rap_missing *match
		srequest = prepare_request(*match)

		begin
			dbobj = $site.dbs[:usersdb]
			
			RapModule::php.register_object dbobj, "dbobj"
			
			RapModule::php.register_object self, "rap_pagehandler"
			results = RapModule::php.exec "#{match.join("/")}", srequest 
		rescue
			$log.info $!, :error
			$log.info $!.backtrace, :error
		end
		
		headers = parse_headers(results)
		handle_php_headers(headers)

		#TODO: Put this back to how it was
 		puts results[:output]
#		puts RAPminiHandler::RAP_page(results[:output], headers)
	end
	
	def do_an_add one, two
		return one + two
	end
	
	def a_msg
		return "hi there"
	end
	
	def get_ruby_object
		return self
	end
	
	def get_an_array
		return [1, 4, "cat"]
	end
	
	def do_something_with_an_array in_val
		return "#{in_val.to_s}"
	end
end
