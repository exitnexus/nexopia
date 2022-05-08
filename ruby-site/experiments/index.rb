=begin
This is a basic test of the index template.
It does not connect to the database, and just uses canned data.
=end

require 'erb'
require 'template.rb'
require 'pagehandlers/News'
require 'articles'

t = Template.new("index/index");
articles = Article.load_all_where("moded='y' && time >= #{Time.now.to_i - 86400*600} ORDER BY time DESC LIMIT 4");
articles_array = Array.new
articles.each {|article|
    article.cat = ArticleCategory.new(article.category);
    articles_array.push(article.to_hash);
}
t.set('articledata', articles_array);

t.set("limit_ads", true);
t.set("news", News.news_content);


#Create the inline ERB template object
str = t.toString();
#print str;

t.display();
