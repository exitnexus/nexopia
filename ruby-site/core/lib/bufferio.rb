require 'zlib'
=begin
# I don't think this should really be necessary, but these calls actually go into
# C code which I think bypasses our overriding of stdout.
module Kernel
	alias raw_puts puts;
	alias raw_print print;
	alias raw_printf printf;
	public :raw_puts, :raw_print, :raw_printf;

	def puts(*args)
		out = Thread.current[:output] || $stdout;
		out.puts(*args);
	end

	def print(*args)
		out = Thread.current[:output] || $stdout;
		out.print(*args);
	end

	def printf(*args)
		out = Thread.current[:output] || $stdout;
		out.printf(*args);
	end
end
=end

# Overload IO class and realias the raw_* methods to the ones that the IO class
# overloaded from Kernel
class IO
	alias raw_puts puts;
	alias raw_print print;
	alias raw_printf printf;
	alias raw_ls <<;
	public :raw_puts, :raw_print, :raw_printf, :raw_ls;
end
# Overload StringIO class and realis the raw_* methods to the ones that the StringIO
# class overloaded from Kernel (StringIO does not derive from IO, so this needs
# to be done in addition to overloading IO).
class StringIO
	alias raw_puts puts;
	alias raw_print print;
	alias raw_printf printf;
	alias raw_ls <<;
	public :raw_puts, :raw_print, :raw_printf, :raw_ls;
end

class Array
	alias raw_ls <<;
	public :raw_ls;
end

# this class is .extend()ed into the IO class handed to the page handler
# in order to overload the output functions to actually print headers before
# the first output.
module BufferIO
	def initialize_buffer(&before_output)
		@bufferio_buffer_string = StringIO.new

		@bufferio_buffer_array = []#StringIO.new;
		@bufferio_before_output = before_output;
		@bufferio_buffered = true;
	end

	def clear_buffer()
		@bufferio_buffer_array = []
		@bufferio_buffer_string = StringIO.new
	end

	def deflate_buffer()
		$log.info "deflating output", :debug
		flush_array()
		@bufferio_buffer_string.string = Zlib::Deflate.deflate(@bufferio_buffer_string.string, $site.config.zlib_compression_level)
	end

	def gzip_buffer()
		$log.info "gzipping output", :debug
		flush_array()
		out = StringIO.new();
		gz = Zlib::GzipWriter.new(out)
		gz.write(@bufferio_buffer_string.string)
		gz.close
		@bufferio_buffer_string = out
	end

=begin
	# Called internally to either add something to the buffer runner or
	# run it immediately.
	def buffer_run(&block)
		if (@bufferio_buffered)
			@bufferio_buffer.push(block);
		else
			block.call(self);
		end
	end
	private :buffer_run;
=end

	def flush_array()
		@bufferio_buffer_string << @bufferio_buffer_array.join('')
		@bufferio_buffer_array = []		
	end
	
	def flush_buffer(io = self)
		flush_array()
		@bufferio_before_output.call() if @bufferio_before_output;
		io.raw_print(@bufferio_buffer_string.string);
		clear_buffer();
	end

	# Returns true if IO is currently being buffered.
	def buffer()
		if (@bufferio_buffered)
			@bufferio_buffer_string
		else
			false
		end
	end

	# Set to false to flush the buffer and make it so that further writes
	# buffer IO.
	# You will also want to set the Content-Encoding header to an empty string
	# so that the page handler knows how to send the unbuffered data properly.
	def buffer=(buffer)
		@bufferio_buffered_string = buffer;
		if (!buffer)
			flush_buffer();
		end
	end

	def end_buffering()
		flush_buffer();
		@bufferio_before_output = nil;
	end

	def capture_output(strio = StringIO.new(), arr = [])
		inner_buffer_string = io;
		inner_buffer_array = arr;
		inner_buffered = true;
		
		# swap out buffering information
		@bufferio_buffer_string, inner_buffer_string = inner_buffer_string, @bufferio_buffer_string;
		@bufferio_buffer_array, inner_buffer_array = inner_buffer_array, @bufferio_buffer_array;
		
		@bufferio_buffered, inner_buffered = inner_buffered, @bufferio_buffered;
		begin
			yield self;
		ensure
			@bufferio_buffer_string, inner_buffer_string = inner_buffer_string, @bufferio_buffer_string;
			@bufferio_buffer_array, inner_buffer_array = inner_buffer_array, @bufferio_buffer_array;
			
			@bufferio_buffered, inner_buffered = inner_buffered, @bufferio_buffered;
		end
		return io;
	end

	def puts(*args)
		out = @bufferio_buffered ? @bufferio_buffer_array : self;
		out.raw_ls(*args) 
		out.raw_ls("\n")
	end

	def print(*args)
		out = @bufferio_buffered ? @bufferio_buffer_array : self;
		out.raw_ls(*args)
	end

	def printf(*args)
		out = @bufferio_buffered ? @bufferio_buffer_string : self;
		flush_array
		out.raw_printf(*args);
	end

	def <<(*args)
		out = @bufferio_buffered ? @bufferio_buffer_array : self;
		out.raw_ls(*args)
	end
end
