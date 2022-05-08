=begin

fcgi.rb 0.8.5 - fcgi.so compatible pure-ruby FastCGI library

fastcgi.rb Copyright (C) 2001 Eli Green
fcgi.rb    Copyright (C) 2002-2003 MoonWolf <moonwolf@moonwolf.com>
fcgi.rb    Copyright (C) 2004 Minero Aoki

=end

require 'fcntl'

#This is a cheap hack c/o Thomas.  For some reason, "print" on File/StringIO
#objects is buggered, so I've replaced calls to "print" with "write".  This
#might be related to module BufferIO, but its hard to tell and harder to debug
#at this point.
class CGI
  module QueryExtension
	def read_multipart(boundary, content_length)
      params = Hash.new([])
      boundary = "--" + boundary
      quoted_boundary = Regexp.quote(boundary, "n")
      buf = ""
      bufsize = 10 * 1024
      boundary_end=""

      # start multipart/form-data
      stdinput.binmode if defined? stdinput.binmode
      boundary_size = boundary.size + EOL.size
      content_length -= boundary_size
      status = stdinput.read(boundary_size)
      if nil == status
        raise EOFError, "no content body"
      elsif boundary + EOL != status
        raise EOFError, "bad content body"
      end

      loop do
        head = nil
#        if 10240 < content_length
          require "tempfile"
          body = Tempfile.new("CGI")
#        else
#          begin
#            require "stringio"
#            body = StringIO.new
#          rescue LoadError
#            require "tempfile"
#            body = Tempfile.new("CGI")
#          end
#        end
        body.binmode if defined? body.binmode
        until head and /#{quoted_boundary}(?:#{EOL}|--)/n.match(buf)

          if (not head) and /#{EOL}#{EOL}/n.match(buf)
            buf = buf.sub(/\A((?:.|\n)*?#{EOL})#{EOL}/n) do
              head = $1.dup
              ""
            end
            next
          end

          if head and ( (EOL + boundary + EOL).size < buf.size )
            body.write buf[0 ... (buf.size - (EOL + boundary + EOL).size)]
            buf[0 ... (buf.size - (EOL + boundary + EOL).size)] = ""
          end
          c = if bufsize < content_length
                stdinput.read(bufsize)
              else
                stdinput.read(content_length)
              end
          if c.nil? || c.empty?
            raise EOFError, "bad content body"
          end
          buf.concat(c)
          content_length -= c.size
        end

        buf = buf.sub(/\A((?:.|\n)*?)(?:[\r\n]{1,2})?#{quoted_boundary}([\r\n]{1,2}|--)/n) do
          body.write $1;
          if "--" == $2
            content_length = -1
          end
         boundary_end = $2.dup
          ""
        end

        body.rewind

        /Content-Disposition:.* filename=(?:"((?:\\.|[^\"])*)"|([^;]*))/ni.match(head)
        if ($1 or $2)
	        filename = ($1 or $2 or "")
	        if /Mac/ni.match(env_table['HTTP_USER_AGENT']) and
	            /Mozilla/ni.match(env_table['HTTP_USER_AGENT']) and
	            (not /MSIE/ni.match(env_table['HTTP_USER_AGENT']))
	          filename = CGI::unescape(filename)
	        end

	        /Content-Type: (.*)/ni.match(head)
	        content_type = ($1 or "")

	        (class << body; self; end).class_eval do
	          alias local_path path
	          define_method(:original_filename) {filename.dup.taint}
	          define_method(:content_type) {content_type.dup.taint}
	        end
	    else
	    	body = body.read;
	    end

        /Content-Disposition:.* name="?([^\";]*)"?/ni.match(head)
        name = $1.dup

        if params.has_key?(name)
          params[name].push(body)
        else
          params[name] = [body]
        end
        break if buf.size == 0
        break if content_length == -1
      end
      raise EOFError, "bad boundary end of body part" unless boundary_end=~/--/

      params
    end # read_multipart
  end
end

require 'socket'
require 'stringio'

class FCGI

	class BufferedSocket
		def initialize(id, sock, type)
			@type = type
			@sock = sock
			@id = id
			@string = StringIO.new()	
		end
	
		PACKET_SIZE = 16384

		def remaining_length()
			@string.length - @string.pos
		end
		
		def send_packet()
			return if (@string.length < PACKET_SIZE)
			@string.rewind
			while (remaining_length >= PACKET_SIZE)
				pre_remaining_length = remaining_length
				send()
				if (pre_remaining_length == remaining_length)
					raise "FCGI Send did not actually send all of its output: #{pre_remaining_length}, #{remaining_length}"
				end
			end
			s = @string.read()
			@string = StringIO.new(s)
		end
		
		def <<(*data)
			@string.<<(*data);
			send_packet
		end
		alias :raw_ls :<<
		def write(*data)
			@string.write(*data);
			send_packet
		end
		alias :raw_write :write
		def puts(*data)
			@string.puts(*data);
			send_packet
		end
		alias :raw_puts :puts
		def print(data)
			@string.print(data);
			send_packet
		end
		alias :raw_print :print
		
		def send
			s = @string.read(PACKET_SIZE)
			if (s != nil) && (s != "")
				@sock.send_record GenericDataRecord.new(@type, @id, s)
			else			
				raise "FCGI Send was unable to retrieve anything from string i/o buffer"
			end
		end
		
		def close()
			@string.rewind;
			while (remaining_length > 0)
				send()
			end
			@sock.send_record GenericDataRecord.new(@type, @id, '')
		end
	
	end
  def self::is_cgi?
	begin
	  s = Socket.for_fd($stdin.fileno)
	  s.getpeername
	  false
	rescue Errno::ENOTCONN
	  false
	rescue Errno::ENOTSOCK, Errno::EINVAL
	  true
	end
  end

  def self::each(&block)
	f = default_connection()
	Server.new(f).each_request(&block)
  ensure
	begin
	  f.close if f
	rescue Errno::EBADF
	end
  end

  def self::each_request(&block)
	f = default_connection()
	Server.new(f).each_request(&block)
  ensure
	begin
	  f.close if f
	rescue Errno::EBADF
	end
  end

  def self::default_connection
	::Socket.for_fd($stdin.fileno)
  end



  ProtocolVersion = 1

  # Record types
  FCGI_BEGIN_REQUEST = 1
  FCGI_ABORT_REQUEST = 2
  FCGI_END_REQUEST = 3
  FCGI_PARAMS = 4
  FCGI_STDIN = 5
  FCGI_STDOUT = 6
  FCGI_STDERR = 7
  FCGI_DATA = 8
  FCGI_GET_VALUES = 9
  FCGI_GET_VALUES_RESULT = 10
  FCGI_UNKNOWN_TYPE = 11
  FCGI_MAXTYPE = FCGI_UNKNOWN_TYPE

  FCGI_NULL_REQUEST_ID = 0

  # FCGI_BEGIN_REQUSET.role
  FCGI_RESPONDER = 1
  FCGI_AUTHORIZER = 2
  FCGI_FILTER = 3

  # FCGI_BEGIN_REQUEST.flags
  FCGI_KEEP_CONN = 1

  # FCGI_END_REQUEST.protocolStatus
  FCGI_REQUEST_COMPLETE = 0
  FCGI_CANT_MPX_CONN = 1
  FCGI_OVERLOADED = 2
  FCGI_UNKNOWN_ROLE = 3

  class Server

	def initialize(server)
	  @server = server
	  @server.fcntl(Fcntl::F_SETFL, @server.fcntl(Fcntl::F_GETFL) | Fcntl::O_NONBLOCK)
	  @buffers = {}
	  @default_parameters = {
		"FCGI_MAX_CONNS" => 1,
		"FCGI_MAX_REQS"  => 1,
		"FCGI_MPX_CONNS" => true
	  }
	end

	def each_request(&block)
	  graceful = false
	  trap("SIGUSR1") { graceful = true }
		catch(:graceful) {
		  while true
				begin
				  session(&block)
				rescue Errno::EPIPE, Errno::ECONNRESET, EOFError
				  # HTTP request is canceled by the remote user
				end
				exit 0 if graceful
		  end
		}
	end

	def session
	  sock = nil
	  begin
			while (!IO.select([@server], nil, nil))
			  # just wait 'til it found a readable.
			end
			sock, addr = *@server.accept
	  rescue Errno::EWOULDBLOCK, Errno::EAGAIN
			# just continue the loop.
			retry
	  end
	  return unless sock
	  sock.fcntl(Fcntl::F_SETFL, sock.fcntl(Fcntl::F_GETFL) & ~Fcntl::O_NONBLOCK)
	  fsock = FastCGISocket.new(sock)
	  req = next_request(fsock)
		begin
	  	yield req
		ensure
	  	respond_to req, fsock, FCGI_REQUEST_COMPLETE
		end
	rescue Errno::EIO
	  # Connection closed prematurely.
	ensure
	  sock.close if sock and not sock.closed?
	end

	private

	def next_request(sock)
	  while rec = sock.read_record
		if rec.management_record?
		  case rec.type
		  when FCGI_GET_VALUES
			sock.send_record handle_GET_VALUES(rec)
		  else
			sock.send_record UnknownTypeRecord.new(rec.request_id, rec.type)
		  end
		else
		  case rec.type
		  when FCGI_BEGIN_REQUEST
			@buffers[rec.request_id] = RecordBuffer.new(rec)
		  when FCGI_ABORT_REQUEST
			raise "got ABORT_REQUEST"   # FIXME
		  else
			buf = @buffers[rec.request_id]   or next # inactive request
			buf.push rec
			if buf.ready?
			  @buffers.delete rec.request_id
			  return buf.new_request(sock)
			end
		  end
		end
	  end
	  raise Errno::EIO, "must not happen: FCGI socket unexpected EOF"
	end

	def handle_GET_VALUES(rec)
	  h = {}
	  rec.values.each_key do |name|
		h[name] = @default_parameters[name]
	  end
	  ValuesRecord.new(FCGI_GET_VALUES_RESULT, rec.request_id, h)
	end
	
	def respond_to(req, sock, status)
	  req.err.close
	  req.out.close
	  sock.send_record EndRequestRecord.new(req.id, 0, status)
	end


  end


  class FastCGISocket
	def initialize(sock)
	  @socket = sock
	end

	def read_record
	  header = @socket.read(Record::HEADER_LENGTH) or return nil
	  return nil unless header.size == Record::HEADER_LENGTH
	  version, type, reqid, clen, padlen, reserved = *Record.parse_header(header)
	  Record.class_for(type).parse(reqid, read_record_body(clen, padlen))
	end

	def read_record_body(clen, padlen)
	  buf = ''
	  while buf.length < clen
		buf << @socket.read([1024, clen - buf.length].min)
	  end
	  @socket.readbytes(padlen) if(padlen > 0)
	  buf
	end
	private :read_record_body

	def send_record(rec)
	  if @socket and not @socket.closed?
	  	s = rec.serialize
	  	@socket.write s 
	  	@socket.flush
	  else
		raise "FastCGISocket.send_record() attempted to write to a dead socket"
      end
	end
  end


  class RecordBuffer
	def initialize(rec)
	  @begin_request = rec
	  @envs = []
	  @stdins = []
	  @datas = []
	end

	def push(rec)
	  case rec
	  when ParamsRecord
		@envs.push rec
	  when StdinDataRecord
		@stdins.push rec
	  when DataRecord
		@datas.push rec
	  else
		raise "got unknown record: #{rec.class}"
	  end
	end

	def ready?
	  case @begin_request.role
	  when FCGI_RESPONDER
		completed?(@envs) and
		completed?(@stdins)
	  when FCGI_AUTHORIZER
		completed?(@envs)
	  when FCGI_FILTER
		completed?(@envs) and
		completed?(@stdins) and
		completed?(@datas)
	  else
		raise "unknown role: #{@begin_request.role}"
	  end
	end

	def completed?(records)
	  records.last and records.last.empty?
	end
	private :completed?

	def new_request(socket)
	  Request.new(socket, @begin_request.request_id, env(), stdin(), data())
	end

	def env
	  h = {}
	  @envs.each {|rec| h.update rec.values }
	  h
	end

	def stdin
	  StringIO.new(@stdins.inject('') {|buf, rec| buf << rec.flagment })
	end

	def data
	  StringIO.new(@datas.inject('') {|buf, rec| buf << rec.flagment })
	end
  end


  class Request
	def initialize(socket, id, env, stdin, data = nil)
	  @id = id
	  @env = env
	  @in = stdin
	  @out = BufferedSocket.new(id, socket, FCGI_STDOUT)
	  @err = BufferedSocket.new(id, socket, FCGI_STDERR)
	  @data = data || StringIO.new
	end

	attr_reader :id
	attr_reader :env
	attr_reader :in
	attr_reader :out
	attr_reader :err
	attr_reader :data

	def finish   # for backword compatibility
	end
  end


  class Record
	# uint8_t  protocol_version;
	# uint8_t  record_type;
	# uint16_t request_id;     (big endian)
	# uint16_t content_length; (big endian)
	# uint8_t  padding_length;
	# uint8_t  reserved;
	HEADER_FORMAT = 'CCnnCC'
	HEADER_LENGTH = 8

	def self::parse_header(buf)
	  return *buf.unpack(HEADER_FORMAT)
	end

	def self::class_for(type)
	  RECORD_CLASS[type]
	end

	def initialize(type, reqid)
	  @type = type
	  @request_id = reqid
	end

	def version
	  ::FCGI::ProtocolVersion
	end

	attr_reader :type
	attr_reader :request_id

	def management_record?
	  @request_id == FCGI_NULL_REQUEST_ID
	end

	def serialize
	  body = make_body()
	  padlen = body.length % 8
	  header = make_header(body.length, padlen)
	  header + body + "\000" * padlen
	end

	private

	def make_header(clen, padlen)
	  [version(), @type, @request_id, clen, padlen, 0].pack(HEADER_FORMAT)
	end
  end

  class BeginRequestRecord < Record
	# uint16_t role; (big endian)
	# uint8_t  flags;
	# uint8_t  reserved[5];
	BODY_FORMAT = 'nCC5'

	def BeginRequestRecord.parse(id, body)
	  role, flags, *reserved = *body.unpack(BODY_FORMAT)
	  new(id, role, flags)
	end

	def initialize(id, role, flags)
	  super FCGI_BEGIN_REQUEST, id
	  @role = role
	  @flags = flags
	end

	attr_reader :role
	attr_reader :flags

	def make_body
	  [@role, @flags, 0, 0, 0, 0, 0].pack(BODY_FORMAT)
	end
  end

  class AbortRequestRecord < Record
	def AbortRequestRecord.parse(id, body)
	  new(id)
	end

	def initialize(id)
	  super FCGI_ABORT_REQUEST, id
	end
  end

  class EndRequestRecord < Record
	# uint32_t appStatus; (big endian)
	# uint8_t  protocolStatus;
	# uint8_t  reserved[3];
	BODY_FORMAT = 'NCC3'

	def self::parse(id, body)
	  appstatus, protostatus, *reserved = *body.unpack(BODY_FORMAT)
	  new(id, appstatus, protostatus)
	end

	def initialize(id, appstatus, protostatus)
	  super FCGI_END_REQUEST, id
	  @application_status = appstatus
	  @protocol_status = protostatus
	end

	attr_reader :application_status
	attr_reader :protocol_status

	private

	def make_body
	  [@application_status, @protocol_status, 0, 0, 0].pack(BODY_FORMAT)
	end
  end

  class UnknownTypeRecord < Record
	# uint8_t type;
	# uint8_t reserved[7];
	BODY_FORMAT = 'CC7'

	def self::parse(id, body)
	  type, *reserved = *body.unpack(BODY_FORMAT)
	  new(id, type)
	end

	def initialize(id, t)
	  super FCGI_UNKNOWN_TYPE, id
	  @unknown_type = t
	end

	attr_reader :unknown_type

	private

	def make_body
	  [@unknown_type, 0, 0, 0, 0, 0, 0, 0].pack(BODY_FORMAT)
	end
  end

  class ValuesRecord < Record
	def self::parse(id, body)
	  new(id, parse_values(body))
	end

	def self::parse_values(buf)
	  result = {}
	  until buf.empty?
		name, value = *read_pair(buf)
		result[name] = value
	  end
	  result
	end

	def self::read_pair(buf)
	  nlen = read_length(buf)
	  vlen = read_length(buf)
	  return buf.slice!(0, nlen), buf.slice!(0, vlen)
	end

	def self::read_length(buf)
	  if buf[0] >> 7 == 0
	  then buf.slice!(0,1)[0]
	  else buf.slice!(0,4).unpack('N')[0] & ((1<<31) - 1)
	  end
	end

	def initialize(type, id, values)
	  super type, id
	  @values = values
	end

	attr_reader :values

	private

	def make_body
	  buf = ''
	  @values.each do |name, value|
		buf << serialize_length(name.length)
		buf << serialize_length(value.length)
		buf << name
		buf << value
	  end
	  buf
	end

	def serialize_length(len)
	  if len < 0x80
	  then len.chr
	  else [len | (1<<31)].pack('N')
	  end
	end
  end

  class GetValuesRecord < ValuesRecord
	def initialize(id, values)
	  super FCGI_GET_VALUES, id, values
	end
  end

  class ParamsRecord < ValuesRecord
	def initialize(id, values)
	  super FCGI_PARAMS, id, values
	end

	def empty?
	  @values.empty?
	end
  end

  class GenericDataRecord < Record
	def self::parse(id, body)
	  new(id, body)
	end

	def initialize(type, id, flagment)
	  super type, id
	  @flagment = flagment
	end

	attr_reader :flagment

	def empty?
	  @flagment.empty?
	end

	private

	def make_body
	  @flagment
	end
  end

  class StdinDataRecord < GenericDataRecord
	def initialize(id, flagment)
	  super FCGI_STDIN, id, flagment
	end
  end

  class StdoutDataRecord < GenericDataRecord
	def initialize(id, flagment)
	  super FCGI_STDOUT, id, flagment
	end
  end

  class DataRecord < GenericDataRecord
	def initialize(id, flagment)
	  super FCGI_DATA, id, flagment
	end
  end

  class Record   # redefine
	RECORD_CLASS = {
	  FCGI_GET_VALUES    => GetValuesRecord,

	  FCGI_BEGIN_REQUEST => BeginRequestRecord,
	  FCGI_ABORT_REQUEST => AbortRequestRecord,
	  FCGI_PARAMS        => ParamsRecord,
	  FCGI_STDIN         => StdinDataRecord,
	  FCGI_DATA          => DataRecord,
	  FCGI_STDOUT        => StdoutDataRecord,
	  FCGI_END_REQUEST   => EndRequestRecord
	}
  end

end # FCGI class

# There is no C version of 'each_cgi'
# Note: for ruby-1.6.8 at least, the constants CGI_PARAMS/CGI_COOKIES
# are defined within module 'CGI', even if you have subclassed it

class FCGI
	def self::listen(ipaddr, port)
		sock = Socket.new(AF_INET, SOCK_STREAM, 0);
		sockaddr = Socket.pack_sockaddr_in( port, ipaddr );
		sock.setsockopt(Socket::SOL_SOCKET, Socket::SO_REUSEADDR, true);
		sock.bind(sockaddr);
		sock.listen(1024);
		fd0 = IO.for_fd(0);
		fd0.reopen(sock);
		sock.close();
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
			# BEGIN JSON post body support
			# Tynt is using JSON form posts, which is apparently something that isn't uncommon with Rails apps. We're using
			# JSON more and more, so it wouldn't be reaching too far to think that we might want to do this as well in the
			# near future. Plus, it's not too difficult to build in, so it makes sense to fix it here than get Tynt to use
			# the usual methods, which we could always handle. Basically, what's happening below is this: The CGI::parse
			# method, which is in the Ruby standard library, is getting redefined to detect a JSON-like string. It would be
			# nice to detect the 'application/json' content-type first and use that to decide that it's a JSON string, but
			# unfortunately, moving the logic here to a place where we could do that would result in a much messier patch
			# without much gain (i.e. you shouldn't be beginning parameter names with the '{' character, and the only case
			# you'd run into an issue is if you were doing that AND had a parameter value at the end with a '}' character).
			# So, in the event that the query string has a '{' at the beginning and a '}' at the end, we assume that we're
			# dealing with JSON and not regular parameters. We then return a parameter hash with a single parameter named
			# 'json_obj'. In order to catch the rare but possible case noted above, we're stricter than we have to be and
			# at the time that a PageRequest is made out of the CGI object, we raise a ruby-killing error if 'json_obj' is
			# being used and the request content-type is not 'application/json'. It's pretty unlikely that this would ever
			# be an issue, but hopefully all this information will help you if it does.
			class << self
				alias_method :parse_normal, :parse
				def parse(query)
					if (!query.nil? && !query.empty? && query[0,1] == "{" && query[query.length-1,query.length] == "}")
						params = Hash.new([].freeze);
						params['json_obj'] = query;
						return params;
					else
						return parse_normal(query);
					end
				end
			end
			# END JSON post body support
    end # ::CGI class

    class FCGI
      class CGI < ::CGI
        def initialize(request, *args)
          ::CGI.remove_params
          @request = request
					# Ultra-safe checking of the request parameter to make sure that it isn't a PUT request, which Ruby's cgi.rb does not 
					# support (see the initialize_query method). If it is, we don't go any further with initialization because to call 'super' 
					# would result in the child process dying. Instead, we return the CGI object as is, to be dealt with hastily in the
					# PageRequest.new_from_cgi method.
					if (!request.nil? && !request.env.nil? && !(['GET','POST','HEAD'].include? request.env['REQUEST_METHOD']))
						return self;
					end
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
					throw :graceful;
				end

				$after_request.each {|name, item|
					item.call();
				}
			end
		}
	end
  end
end
