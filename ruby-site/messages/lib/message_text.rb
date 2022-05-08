lib_require :Core, 'storable/storable'
class MessageText < Storable
	init_storable(:usersdb, 'msgtext');
end