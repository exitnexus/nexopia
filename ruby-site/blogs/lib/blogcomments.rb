
lib_require :Core, "storable/storable";

class BlogComments < Storable
	attr_reader :username

	init_storable(:usersdb, "blogcomments");

end
