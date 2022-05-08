lib_require :Core, 'storable/storable'

module Plus
class Product < Storable
	init_storable(:shopdb, 'products')
end # class Product
end # module Plus