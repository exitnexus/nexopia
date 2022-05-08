lib_require :Core, 'storable/storable'

class PlusBatch < Storable
	init_storable(:shopdb, 'paygbatches')
end