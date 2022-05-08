lib_require :core, 'storable/storable'
class ProductPrices < Storable
	init_storable(:shopdb, 'productprices')
end
