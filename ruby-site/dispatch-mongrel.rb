#!/bin/env ruby
require 'mongrel';
require 'site_initialization';
initialize_site();

class SiteHandler < Mongrel::HttpHandler
	def process(request, response)
		stdout = StringIO.new();
		Thread.current[:output] = stdout;
		req = nil
		PageRequest.new_from_mongrel(request, stdout) {|req|
			PageHandler.execute(req);
		}

		statuscode = req.reply.headers['Status'].to_s[/^[0-9]{3}/].to_i;

		response.start(statuscode) do |head,out|
			req.reply.headers.each {|name, val|
				if (name != 'Status')
					head[name] = val;
				end
			}

			out << stdout.string;
		end
	end
end

h = Mongrel::HttpServer.new("0.0.0.0", $port)
h.register("/", SiteHandler.new)
h.run.join
