lib_require :Core, 'storable/storable'

class DeletedUser < Storable
	init_storable(:db, "deletedusers");
end