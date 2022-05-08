require "wiki";

class News < PageHandler
	declare_handlers("/") {
		area :Public
		access_level :Any

		handle :GetRequest, :news_page, "news"
	}

	def news_page()
		puts News.news_content;
	end

	def News.news_content()
		news_wiki = WikiPage.new;
		news_wiki.load_by_name!("news");
		news_wiki_data = WikiData.new;
		news_wiki_data.load_by_pageid_revision!(news_wiki.id, news_wiki.maxrev);
		return news_wiki_data.content;
	end
end
