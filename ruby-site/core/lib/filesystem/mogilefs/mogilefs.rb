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

lib_require :Core, 'filesystem/mogilefs/mogilefs/backend'
lib_require :Core, 'filesystem/mogilefs/mogilefs/nfsfile'
lib_require :Core, 'filesystem/mogilefs/mogilefs/httpfile'
lib_require :Core, 'filesystem/mogilefs/mogilefs/client'
lib_require :Core, 'filesystem/mogilefs/mogilefs/mogilefs'
lib_require :Core, 'filesystem/mogilefs/mogilefs/admin'

