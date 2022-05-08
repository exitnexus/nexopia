lib_require :Blogs, 'blog_type', 'embeded_video'
lib_require :Polls, 'userpoll_question', 'userpoll_answer', 'userpoll_vote'

module Blogs
	class BattleBlog < BlogType
		init_storable(:usersdb, 'blogtype_battle')
		
		extend TypeID
		
		attr_accessor :userbattlequestion, :userbattleanswers
		
		def self.build(post, user, params)
			$log.info "Build a battle blog for #{user}, #{post}."

			# Store the Blog extra content
			if (post.extra_content.nil?)
				battle = BattleBlog.new
				battle.userid = user.userid
				battle.blogid = post.id
			else
				battle = post.extra_content
			end

			# Store the question and answers
			question = params["blog_post_title", String, ""].strip
			battle.battletype = params["battletype", String, "photo"]
			battle.caption1 = params["caption_1", String, ""].strip
			battle.link1 = params["link_1", String, ""].strip
			battle.caption2 = params["caption_2", String, ""].strip
			battle.link2 = params["link_2", String, ""].strip
			answers = [battle.link1, battle.link2]
			raise "Empty link" if battle.link1.empty?
			raise "Empty link" if battle.link2.empty?
			
			battle.userbattlequestion = Polls::User::UserPollQuestion.new()
			battle.userbattlequestion.userid = user.userid
			battle.userbattlequestion.typeid = BattleBlog::typeid
			battle.userbattlequestion.question = question
			battle.userbattlequestion.date = Time.now.to_i()
			which_answer = 0
			battle.userbattleanswers = Array.new
			answers.each { |answer|
				userbattleanswer = Polls::User::UserPollAnswer.new()
				userbattleanswer.userid = user.userid
				userbattleanswer.typeid = BattleBlog::typeid
				which_answer += 1
				userbattleanswer.answer = which_answer
				userbattleanswer.answertext = answer

				battle.userbattleanswers << userbattleanswer
			}
			return battle
		end
		
		# Because #build doesn't create the userbattlequestion and userbattleanswer
		# objects, we have to do so here
		def after_create
			raise "No question specified" unless defined? self.userbattlequestion
			raise "No answers specified" unless defined? self.userbattleanswers
			if self.userbattleanswers.length != 2
				raise "Battles must have exactly 2 answers"
			end
			
			self.userbattlequestion.parentid = blogid
			self.userbattlequestion.store
			self.userbattleanswers.each { |userbattleanswer|
				userbattleanswer.parentid = blogid
				userbattleanswer.store
			}
		end
		
		# Initialise the @userbattlequestion and @userbattleanswers appropriately.
		# Do things this way instead of via relations because we use the
		# #display method to preview ourselves, and we may not have related
		# data at that point.
		def after_load
			@userbattlequestion = Polls::User::UserPollQuestion.find(:first,
			 	[self.userid, BattleBlog::typeid, self.blogid])
			@userbattleanswers = Polls::User::UserPollAnswer.find(
				[self.userid, BattleBlog::typeid, self.blogid],
				:order => "answer ASC")
		end

		def display(request)
			bar_max = 300
			# Figure out if the user has already voted
			if (request.session.anonymous?)
				userbattle_vote = nil
			else
				userbattle_vote = Polls::User::UserPollVote.find(:first,
				 	[self.userid, BattleBlog::typeid, self.blogid,
					request.session.user.userid])
			end
			if userbattle_vote != nil # already voted?
				t = Template::instance('blogs',
				 	"blog_post_battle_#{self.battletype}_results")
			else
				t = Template::instance('blogs',
					"blog_post_battle_#{self.battletype}_vote")
			end
			t.user = User.get_by_id(self.userid)
			t.blogid = self.blogid
			t.anon = request.session.anonymous?
			t.question = @userbattlequestion.question
			t.answers = Array.new()
			@userbattleanswers.each_index { |index|
				answer = @userbattleanswers[index]
				bar_len = 0
				# Is there a way to do this through JavaScript?
				if (@userbattlequestion.tvotes > 0)
					bar_len = answer.votes * bar_max / @userbattlequestion.tvotes
					bar_len = bar_max if bar_len > bar_max
				end
				t.answers << [ answer.answer, answer.answertext, answer.votes,
					bar_len ]
			}
			if (@userbattlequestion.tvotes == 1)
				t.votes = "1 vote"
			else
				t.votes = "#{@userbattlequestion.tvotes} votes"
			end

			t.battle_blog = self
			
			# Resize videos?
			if (self.battletype == 'video')
				t.battle_blog.link1 =
					Blogs::EmbededVideo.resize(t.battle_blog.link1, :battle)
				t.battle_blog.link2 =
					Blogs::EmbededVideo.resize(t.battle_blog.link2, :battle)
			end
			t.video_width_1 = Blogs::EmbededVideo.width(t.battle_blog.link1)
			t.video_height_1 = Blogs::EmbededVideo.height(t.battle_blog.link1)
			t.video_width_2 = Blogs::EmbededVideo.width(t.battle_blog.link2)
			t.video_height_2 = Blogs::EmbededVideo.height(t.battle_blog.link2)
			
			return t.display
		end
		
		def blog_type
			return :battle
		end
		
	end
end

