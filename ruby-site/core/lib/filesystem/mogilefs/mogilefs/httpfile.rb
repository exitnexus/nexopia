require 'fcntl'
require 'socket'
require 'stringio'
require 'uri'

##
# HTTPFile wraps up the new file operations for storing files onto an HTTP
# storage node.
#
# You really don't want to create an HTTPFile by hand.  Instead you want to
# create a new file using MogileFS::MogileFS.new_file.
#
# WARNING! HTTP mode is completely untested as I cannot make it work on
# FreeBSD.  Please send patches/tests if you find bugs.
#--
# TODO dup'd content in MogileFS::NFSFile

class MogileFS::HTTPFile < StringIO

	##
	# The path this file will be stored to.

	attr_reader :path

	##
	# The key for this file.  This key won't represent a real file until you've
	# called #close.

	attr_reader :key

	##
	# The class of this file.

	attr_reader :class

	##
	# Works like File.open.  Use MogileFS::MogileFS#new_file instead of this
	# method.

	def self.open(*args)
		fp = new(*args)

		return fp unless block_given?

		begin
			yield fp
		ensure
			fp.close
		end
	end

	##
	# Creates a new HTTPFile with MogileFS-specific data.  Use
	# MogileFS::MogileFS#new_file instead of this method.

	def initialize(mg, fid, dests, klass, key)
		super ''
		@mg = mg
		@fid = fid
		@klass = klass
		@key = key

		@dests = dests
		@tried = {}

		@path = nil
		@devid = nil
	end

	def mkdir(path, url)
		connect_socket(path) { |socket|
			socket.write("MKCOL #{url} HTTP/1.0\r\n\r\n")

			line = socket.gets
			raise 'Unable to read response line from server in MKCOL' if line.nil?
			if(match = line.match(/^HTTP\/\d+\.\d+\s+(\d+)/))
				case match[1].to_i
				when 200..299
				#(Created) - The collection or structured resource was created in its entirety.
				when 405 #(Method Not Allowed) - MKCOL can only be executed on a deleted/non-existent resource.
				else
					raise "Got status #{status} for url #{url}"
				end
			end
		}
		return true;
		#	when 403 #(Forbidden) - This indicates at least one of two conditions: 1) the server does not allow the creation of collections at the given location in its namespace, or 2) the parent collection of the Request-URI exists but cannot accept members.
		#	when 409 #(Conflict) - A collection cannot be made at the Request-URI until one or more intermediate collections have been created.
		#	when 415 #(Unsupported Media Type)- The server does not support the request type of the body.
		#	when 507 #(Insufficient Storage) - The resource does not have sufficient space to record the state of the resource after the execution of this method.      end
	end

	##
	# Closes the file handle and marks it as closed in MogileFS.

	def close
		@dests.each { |dest|
			@devid, path = dest
			@path = URI.parse(path)

			begin
				if($site.config.mogilefs_options[:mkcols_required])
					wd = "";
					@path.request_uri.split("/")[1...-1].each{|dir|
						wd << "/#{dir}";
						mkdir(@path, wd);
					}
				end

				connect_socket(@path) {|socket|
					socket.write("PUT #{@path.request_uri} HTTP/1.0\r\nContent-length: #{length}\r\n\r\n#{string}")

					line = socket.gets
					raise 'Unable to read response line from server' if line.nil?

					if(match = line.match(/HTTP\/\d+\.\d+\s+(\d+)\s+(.*)/))
						case match[1].to_i
						when 200..299 then # success!
						else
							found_header = false
							body = ''
							while(line = socket.gets)
								if(found_header)
									body << line
								elsif(line.strip == '')
									found_header = true
								end
							end
							body = body[0, 512] if body.length > 512
							raise "HTTP response #{match[1]} #{match[2]}: #{body}"
						end
					else
						raise "Response line not understood: #{line}"
					end
				}

				@mg.backend.create_close(:fid => @fid, :devid => @devid,
				                         :domain => @mg.domain, :key => @key,
				                         :path => @path, :size => length)

				return nil
			rescue Object
				#just retry on the next dest
			end
		}
		raise "File not stored on any mogile servers"
	end

	private

	def connect_socket(path)
		raise 'Invalid path: #{path}' if path.nil?

		socket = TCPSocket.new(path.host, path.port)
		if(block_given?)
			begin
				yield socket
			ensure
				socket.close
			end
		else
			return socket
		end
	end
end

