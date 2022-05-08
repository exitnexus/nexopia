lib_require :Core, 'storable/storable'
lib_require :Plus, 'product'

module Plus
class InvoiceItem < Storable
	init_storable(:shopdb, 'invoiceitems')
	
	relation :singular, :product, :productid, Product
end # class InvoiceItem
end # module Plus