lib_require :Core, 'storable/storable'

class PlusVoucher < Storable
	init_storable(:shopdb, 'paygcards')
end