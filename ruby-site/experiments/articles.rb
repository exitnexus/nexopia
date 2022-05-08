class Article
	include Storable;
	storable_initialize(DBI.connect('DBI:Mysql:newarticles:192.168.0.50', "root", "Hawaii"), "articles");
	
	attr(:cat, true);
	
	def to_hash
		var_hash = Hash["cats" => Array[Hash["id" => cat.id.to_i, "name" => cat.name]],
                 "title" => title,
                 "time" => time.to_i,
                 "authorid" => authorid.to_i,
                 "author" => author,
                 "ntext" => text];
		return var_hash;
	end

end

class ArticleCategory
	include Storable;
	storable_initialize(DBI.connect('DBI:Mysql:newarticles:192.168.0.50', "root", "Hawaii"), "cats");
	
	def initialize(id=false)
		if (id)
			load_by_id!(id);
		end
	end
	
end
