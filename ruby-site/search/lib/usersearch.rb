require 'net/http'
lib_require :Core, "data_structures/boolean"

module Search
	class UserSearch
		def initialize(host = nil, port = nil)
			@host = host || $site.config.search_server.split(":")[0]
			@port = port || $site.config.search_server.split(":")[1]
			@timeout = 0.05; #50ms should be tons
			
			@server_defaults = {
				"agemin"    => 14,
				"agemax"    => 60,
				"sex"       => 2,
				"location"  => 0,
				"interests" => 0,
				"socials"   => 0,
				"active"    => 3,
				"pic"       => 1,
				"sexuality" => 0,
				"single"    => 0,
				"offset"    => 0,
				"rowcount"  => 25,
				}
		end

		def add_user(params)
			if(params.class == User)
				user = params;
				params = {
					'userid'    => user.userid,
					'age'       => user.age,
					'sex'       => (user.sex == 'Male' ? 0 : 1),
					'loc'       => user.loc,
					'active'    => (user.online ? 1 : 0) + (user.activetime > Time.now.to_i - 600) + (user.activetime > Time.now.to_i - 86400*7) + (user.activetime > Time.now.to_i - 86400*30),
					'pic'       => (user.firstpic > 0 ? 1 : 0) + (user.signpic ? 1 : 0),
					'single'    => (user.single ? 1 : 0),
					'sexuality' => user.sexuality
				};
			end

			if(!params['userid'] ||
			   !params['age'] ||
			   !params['sex'] ||
			   !params['loc'] ||
			   !params['active'] ||
			   !params['pic'] ||
			   !params['single'] ||
			   !params['sexuality']
			  )
			  	raise "Missing user information, add_user failed";
			end

			return (cmd("adduser", params) == "SUCCESS");	
		end

		def update_user(userid, params)
			if(params.length == 0)
				return false;
			end

			params['userid'] = userid;

			return (cmd("updateuser", params) == "SUCCESS");
		end

		def delete_user(userid)
			return (cmd("deleteuser", {'userid' => userid} ) == "SUCCESS");
		end

		def self.clean_params(params, defaults)
			opts = {}

			params.each {|k,v|
				if(nil == defaults[k] || !(defaults[k] === v))
					if(v.kind_of? Array)
						opts[k] = v.map{ |i| i.to_i}.join(',')
					else
						opts[k] = v.to_i
					end
				end
			}

			return opts;
		end

		def search(params, page, pagesize)
			opts = UserSearch.clean_params(params, @server_defaults);
			opts['offset'] = page * pagesize;
			opts['rowcount'] = pagesize;

			 #locs is used by the server, location by the client
			opts['locs'] = opts['location']; #might be nil already
			opts['location'] = nil;

			str = cmd("search", opts);

			results = {};

			str.each_line {|line|
				k, v = line.split(":", 2);
				if(k == 'userids')
					results[k] = v.strip.chomp(",").split(",").map {|v| v.to_i};
				else
					results[k] = v.to_i;
				end
			}

			return results;
		end

		def self.prepare(cmd, params = {} )
			uri = (cmd[0...1] == '/' ? cmd : "/" + cmd);

			sep = (uri['?'] ? '&' : '?');

			params.each {|k, v|
				if(v)
					uri << sep + CGI::escape(k.to_s) + "=" + CGI::escape(v.to_s);
					sep = '&';
				end
			}

			return uri;
		end

		def cmd(cmd, params = {} )
			uri = UserSearch.prepare(cmd, params);

			return Net::HTTP.get(URI.parse("http://#{@host}:#{@port}#{uri}")).strip;

			#return Net::HTTP.get(@host, uri, @port).strip;
			#return Net::HTTPFast.get(@host, uri, @port).strip
		end
	end
end

