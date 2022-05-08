# These queues should be moved out of here as the modules for their parts of the
# site are created.

module Moderator
	class UserModItem < ModItem # where itemid is a reference to a user
		relation_singular :user, :itemid, User, :promise
		
		def validate()
			return !self.user.nil?
		end
	end
	
	class ForumPostQueue < QueueBase
		declare_queue("Forum Post", 11)

		def self.handle_yes(items)
			raise "Yes votes on items #{items.join(',')}, but not implemented yet."
		end
		def self.handle_no(items)
			raise "No votes on items #{items.join(',')}, but not implemented yet."
		end
	end

	class ForumRankQueue < QueueBase
		declare_queue("Forum Rank", 12)
		self.item_type = UserModItem

		def self.handle_no(items)
			users = items.collect {|item|
				item.user
			}
			users.each {|user|
				user.forumrank = ''
				user.store
			}
		end
	end

	class ForumBanQueue < QueueBase
		declare_queue("Forum Ban", 13)

		# This queue doesn't actually do anything with the items, it just displays them and gives
		# the mods an opportunity to do something with them.
	end
	
	class ArticleCat < Storable
		init_storable(:articlesdb, "cats")
		relation_singular :parent_cat, :parent, ArticleCat
		
		def to_s()
			# Not a very good implementation, really. But it'll do for moderation's purposes. We need a to_html thing or something.
			if (!parent_cat.nil?)
				return parent_cat.to_s + " > #{htmlencode(name)}"
			else
				return htmlencode(name)
			end
		end
	end
	
	class Article < Storable
		init_storable(:articlesdb, "articles")
		relation_singular :user, :authorid, User
		relation_singular :cat, :category, ArticleCat
		
		def text_preview()
			if (text.length > 500)
				text[0...500] + "..."
			else
				text
			end
		end
		
		def uri_info(type = '')
			return ["Read More...", url/"article.php" & {:id=>id}]
		end
	end
	
	class ArticleQueueItem < ModItem
		relation_singular :article, :itemid, Article
		
		def validate()
			return false if article.nil?
			if (article.user.nil?)
				article.delete
				return false
			end
			return true
		end
	end

	class ArticleQueue < QueueBase
		declare_queue("Article", 51)
		self.item_type = ArticleQueueItem
		self.lock_per_item_time = 300
		self.lock_base_time = 300

		def self.handle_yes(items)
			items.each {|item|
				if (!item.article.user.nil?)
					message = Message.new;
					message.sender_name = "Nexopia";
					message.receiver = item.article.user;
					message.subject = "Article Accepted";
					message.text = "Congrats! Your article '#{item.article.title}' has been accepted and is now being featured in Nexopia's article section.
					
					Cheers,
					-- The Nex Team"
					message.send();
				end
				
				item.article.moded = true
				item.article.time = Time.now.to_i
				item.article.store
			}
		end
		def self.handle_no(items)
			items.each {|item|
				if (!item.article.user.nil?)
					message = Message.new;
					message.sender_name = "Nexopia";
					message.receiver = item.article.user;
					message.subject = "Article Denied";
					message.text = "Your article '#{item.article.title}' didn't make the cut this time.  No worries, due to the amount of articles submitted, about 85% are rejected.

					Other reasons may include:
					[list][*]The article is too [b]short[/b]
					[*]The article contains too much poor [b]language[/b] (please avoid net-slang)
					[*]The article contains too much incorrect [b]spelling and/or grammar[/b] (please edit and spellcheck before you submit!)
					[*]The article is too heavily [b]formatted[/b] (please do not use different fonts, text colors, sizes, and images)
					[*]The article topic is [b]written about too often[/b]
					[*]The article topic is [b]out of date[/b] (elections, holidays, etc, that have long since past)
					[*]The article has too much [b]rambling[/b] and contains no structure or point
					[*]The article contains blatant [b]advertising[/b]
					[*]The article contains a significant amount of [b]plagiarized[/b] work (the content was taken from another source and was not written by the user submitting it)[/list]
					Try sending us another one [url=/addarticle.php]here[/url].
					
					Cheers,
					-- The Nex Team"
					message.send();
				end
				
				item.article.delete
			}
		end
	end
end