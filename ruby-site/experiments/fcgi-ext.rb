require "fcgi"

class FCGI
  def self::accept_cgi(*args)
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
	  request = FCGI::accept;
		$stdout, $stderr = request.out, request.err

		yield CGI.new(request, *args)

		request.finish
	end
  end
end
