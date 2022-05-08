
require 'net/protocol'
require 'net/http'
require 'uri'

=begin
	Massive hack c/o Thomas

	The original HTTP class uses Timeouts that cause serious performance penalty.
=end

module Net   #:nodoc:

	class FastBufferedIO < BufferedIO
		def timeout(arg)
			yield;
		end
		private
		def rbuf_fill
			@rbuf << @io.sysread(16384)
		end
	end
	class HTTPFast < HTTP
	    def connect
	      D "opening connection to #{conn_address()}..."
	      s = timeout(@open_timeout) { TCPSocket.open(conn_address(), conn_port()) }
	      D "opened"
	      if use_ssl?
	        unless @ssl_context.verify_mode
	          warn "warning: peer certificate won't be verified in this SSL session"
	          @ssl_context.verify_mode = OpenSSL::SSL::VERIFY_NONE
	        end
	        s = OpenSSL::SSL::SSLSocket.new(s, @ssl_context)
	        s.sync_close = true
	      end
	      @socket = FastBufferedIO.new(s)
	      @socket.read_timeout = @read_timeout
	      @socket.debug_output = @debug_output
	      if use_ssl?
	        if proxy?
	          @socket.writeline sprintf('CONNECT %s:%s HTTPFast/%s',
	                                    @address, @port, HTTPFastVersion)
	          @socket.writeline "Host: #{@address}:#{@port}"
	          if proxy_user
	            credential = ["#{proxy_user}:#{proxy_pass}"].pack('m')
	            credential.delete!("\r\n")
	            @socket.writeline "Proxy-Authorization: Basic #{credential}"
	          end
	          @socket.writeline ''
	          HTTPFastResponse.read_new(@socket).value
	        end
	        s.connect
	      end
	      on_connect
	    end
	    private :connect
	end
end   # module Net
