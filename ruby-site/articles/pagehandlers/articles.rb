lib_require :Articles, "article";

class Articles < PageHandler
	declare_handlers("articles") {
		area :Public
		access_level :Any
		handle :GetRequest, :show_articles
		handle :GetRequest, :show_article, "view"
		handle :GetRequest, :article_form, "create"
		handle :GetRequest, :create_article, "submit"
	}

	def show_articles(*args)
		articles = Article.find(:all, :limit => 4, :conditions => "moded = 'y'", :order => "time DESC");
		articles.html_dump();
	end

	def show_article(*args)
		id = params["id", Integer];

		if (id)
			article = Article.find(id).first;
		else
			article = Article.find(:first, :conditions => "moded = 'y'", :order => "time DESC");
		end

		if (!article)
			puts "Article #{id} not found.";
		else
			article.html_dump();
		end
	end

	def create_article(*args)
		return;
	end

	def article_form(*args)
		Article.new().make_form().html_dump();
	end
end
