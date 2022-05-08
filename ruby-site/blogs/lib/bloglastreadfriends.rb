
lib_require :Core, "storable/storable";

class Bloglastreadfriends < Storable
	attr_reader :username

	init_storable(:usersdb, "bloglastreadfriends");

	def created()
	end

	def updated()
	end

	def deleted()
	end
end
