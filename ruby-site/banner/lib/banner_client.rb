require 'socket'

class BannerClient
	DEFAULT_READ_LENGTH = 256
	
	attr_reader :sockets
	
	def initialize
		@sockets = Array.new
		$site.config.banner_servers.each {|ip|
			@sockets << TCPSocket.new(ip, $site.config.banner_server_port)
		}
	end
	
	def command_all(command_string)
		self.sockets.each {|socket|
			self.write(socket, command_string)
		}
		results = self.sockets.map {|socket|
			self.read(socket)
		}
		return results
	end
	
	def command_user(uid, command_string)
		socket = self.sockets[uid.abs % @sockets.length]
		self.write(socket, command_string)
		return self.read(socket)
	end
	
	def write(socket, msg)
		socket.write(msg+"\n")
		socket.flush
	end
	
	def read(socket)
		result = ""
		length = 0
		
		header = socket.readline
		if (header.index("BANNER_HEADER:") == 0)
			length = header.sub("BANNER_HEADER:","").to_i
		else
			result = header
		end

		begin
			while (true)
				received = socket.read_nonblock(DEFAULT_READ_LENGTH)
				result += received
			end
		rescue
			if ($!.kind_of?(Errno::EAGAIN) || $!.kind_of?(Errno::EINTR))
				if (result.length < length)
					retry
				end
			end
		end
		return result
	end
end