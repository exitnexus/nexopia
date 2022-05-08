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

# this class is .extend()ed into the IO class handed to the page handler
# in order to overload the output functions to actually print headers before
# the first output.
module BufferIO
	def initialize_buffer(&before_output)
		@bufferio_buffer = StringIO.new;
		@bufferio_before_output = before_output;
		@bufferio_buffered = true;
	end

	def clear_buffer()
		@bufferio_buffer = StringIO.new;
	end

	def deflate_buffer()
		require 'zlib'
		$log.info "deflating output", :debug
		@bufferio_buffer.string = Zlib::Deflate.deflate(@bufferio_buffer.string)
	end

	def gzip_buffer()
		require 'zlib'
		$log.info "gzipping output", :debug
		out = StringIO.new();
		gz = Zlib::GzipWriter.new(out)
		gz.write(@bufferio_buffer.string)
		gz.close
		@bufferio_buffer.string = out.string

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

	def flush_buffer(io = self)
		@bufferio_before_output.call() if @bufferio_before_output;
		io.raw_print(@bufferio_buffer.string);
		clear_buffer();
	end

	# Returns true if IO is currently being buffered.
	def buffer()
		return @bufferio_buffered;
	end

	# Set to false to flush the buffer and make it so that further writes
	# buffer IO
	def buffer=(buffer)
		@bufferio_buffered = buffer;
		if (!buffer)
			flush_buffer();
		end
	end

	def end_buffering()
		flush_buffer();
		@bufferio_before_output = nil;
	end

	def capture_output(io = StringIO.new())
		inner_buffer = io;
		inner_buffered = true;
		# swap out buffering information
		@bufferio_buffer, inner_buffer = inner_buffer, @bufferio_buffer;
		@bufferio_buffered, inner_buffered = inner_buffered, @bufferio_buffered;
		begin
			yield self;
		ensure
			@bufferio_buffer, inner_buffer = inner_buffer, @bufferio_buffer;
			@bufferio_buffered, inner_buffered = inner_buffered, @bufferio_buffered;
		end
		return io;
	end

	def puts(*args)
		out = @bufferio_buffered ? @bufferio_buffer : self;
		out.raw_puts(*args);
	end

	def print(*args)
		out = @bufferio_buffered ? @bufferio_buffer : self;
		out.raw_print(*args);
	end

	def printf(*args)
		out = @bufferio_buffered ? @bufferio_buffer : self;
		out.raw_printf(*args);
	end

	def <<(*args)
		out = @bufferio_buffered ? @bufferio_buffer : self;
		out.raw_ls(*args);
	end
end
