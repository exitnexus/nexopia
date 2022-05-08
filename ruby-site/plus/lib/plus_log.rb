lib_require :Core, 'storable/storable'

module Plus
class PlusLog < Storable
	init_storable(:db, 'pluslog')
end
end