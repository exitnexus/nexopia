##
# MogileFS is a Ruby client for Danga Interactive's open source distributed
# filesystem.
#
# To read more about Danga's MogileFS: http://danga.com/mogilefs/

module MogileFS

  ##
  # Raised when a socket remains unreadable for too long.

  class UnreadableSocketError < RuntimeError; end

end

require 'socket'

lib_require :FileServing, 'mogilefs/backend', 'mogilefs/nfsfile', 'mogilefs/httpfile', 'mogilefs/client', 'mogilefs/mogilefs', 'mogilefs/admin'

# Overload site to provide mogile instance handling
class Site
	def mogile_connection(name)
		@mogile_connections ||= {}
		name = name.to_sym
		
		return @mogile_connections[name] if (@mogile_connections[name])
		
		if (!config.mogilefs_configs[name].nil?)
			if (mog_config = config.mogilefs_configs[name])
				@mogile_connections[name] = MogileFS::CachingMogileFS.new(mog_config)
				return @mogile_connections[name]
			else
				return nil
			end
		else
			return mogile_connection(:default)
		end
	end
end