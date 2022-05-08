
lib_require :Core, "storable/storable";

class Blogcommentsunread < Storable
	attr_reader :username

	init_storable(:usersdb, "blogcommentsunread");

	def created()
	end

	def updated()
	end

	def deleted()
	end
end
