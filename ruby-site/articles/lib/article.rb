lib_require :Core, 'form_generator'

class Article < Storable
	init_storable(:articlesdb, "articles");
	include(FormGenerator)
end
