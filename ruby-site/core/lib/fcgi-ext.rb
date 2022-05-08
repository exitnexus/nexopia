require "fcgi"

class FCGI
	def self::save_stdio
		stdio_orig = []
		stdio_orig[0] = File.new('/dev/zero', 'r')
		stdio_orig[0].reopen(IO.for_fd(0))
		stdio_orig[1] = File.new('/dev/null', 'w')
		stdio_orig[1].reopen(IO.for_fd(1))
		stdio_orig[2] = File.new('/dev/null', 'w')
		stdio_orig[2].reopen(IO.for_fd(2))
		return stdio_orig
	end

	def self::restore_stdio(stdio_orig)
		IO.for_fd(0).reopen(stdio_orig[0])
		IO.for_fd(1).reopen(stdio_orig[1])
		IO.for_fd(2).reopen(stdio_orig[2])
	end

	def self::listen(ipaddr, port)
		sock = Socket.new(AF_INET, SOCK_STREAM, 0);
		sockaddr = Socket.pack_sockaddr_in( port, ipaddr );
		sock.setsockopt(Socket::SOL_SOCKET, Socket::SO_REUSEADDR, true);
		sock.bind(sockaddr);
		sock.listen(1024);

		fd0 = IO.for_fd(0);
		@stdio_orig = save_stdio()

		fd0.reopen(sock);
		sock.close();
	end

	# replace io[0..2] with their originals for the duration of a block
	def self::break_to_console
		tmp = save_stdio()
		begin
			yield
		ensure
			restore_stdio(tmp)
		end
	end

	def self::each_cgi(*args)
		require 'cgi'

		eval(<<-EOS,TOPLEVEL_BINDING)
		class CGI
			public :env_table
			def self::remove_params
				if (const_defined?(:CGI_PARAMS))
					remove_const(:CGI_PARAMS)
					remove_const(:CGI_COOKIES)
				end
			end
		end # ::CGI class

		class FCGI
			class CGI < ::CGI
				def initialize(request, *args)
					::CGI.remove_params
					@request = request
					super(*args)
					@args = *args
				end
				def args
					@args
				end
				def env_table
					@request.env
				end
				def stdinput
					@request.in
				end
				def stdoutput
					@request.out
				end
			end # FCGI::CGI class
		end # FCGI class
		EOS

		if FCGI::is_cgi?
			yield ::CGI.new(*args)
		else
			exit_requested = false

			handled_count = 0;
			FCGI::each {|request|
				$after_request = {};
				$stdout, $stderr = request.out, request.err

				begin
					yield CGI.new(request, *args)
					handled_count += 1;
				ensure
					request.finish

					if ($site.config.max_requests && (handled_count % $site.config.max_requests) == 0)
						exit;
					end

					$after_request.each {|name, item|
						item.call();
					}
				end
			}
		end
	end
end
