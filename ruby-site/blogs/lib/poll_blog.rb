lib_require :Blogs, 'blog_type'
lib_require :Polls, 'userpoll_question', 'userpoll_answer', 'userpoll_vote'

module Blogs
	class PollBlog < BlogType
		set_enums(
			:size => {
				:size_auto => 0,
				:size_100 => 1,
				:size_75 => 2,
				:size_50 => 3,
				:size_25 => 4
			},
			:align => {
				:center => 0,
				:left => 1,
				:right => 2
			}
		)
		
		SIZE_PERCENTAGES = {
			:size_original => nil,
			:size_100 => "98%",
			:size_75 => "75%",
			:size_50 => "50%",
			:size_25 => "25%"
		}
		
		ALIGN_STYLES = {
			:center => "margin-left:auto;margin-right:auto;",
			:left => "margin-right:auto;",
			:right => "margin-left:auto;"
		}
		
		init_storable(:usersdb, 'blogtype_poll')
		
		extend TypeID
		
		attr_accessor :userpollquestion, :userpollanswers
		
		def size_percent
			return SIZE_PERCENTAGES[self.size]
		end
		
		def align_style
			return ALIGN_STYLES[self.align]
		end
		
		def self.build(post, user, params)
			# Store the Blog extra content
			if (post.extra_content.nil?)
				poll = PollBlog.new
				poll.userid = user.userid
				poll.blogid = post.id
			else
				poll = post.extra_content
			end
			poll.link = params["blog_post_photo_link", String, ""]
			poll.size = params["blog_post_photo_size", Integer, 0]
			poll.align = params["blog_post_photo_align", Integer, 0]

			# Store the question and answers
			question = params["blog_post_title", String, ""].strip
			answers = params["answers", Array, Array.new()]
			answers.map! { |answer|
				answer.strip
			}
			answers = answers.select { |answer|
				!answer.empty?
			}
			raise "Empty question" if question.empty?
			raise "Insufficient answers" if answers.length() < 2

			poll.userpollquestion = Polls::User::UserPollQuestion.new()
			poll.userpollquestion.userid = user.userid
			poll.userpollquestion.typeid = PollBlog::typeid
			poll.userpollquestion.question = question
			poll.userpollquestion.date = Time.now.to_i()
			which_answer = 0
			poll.userpollanswers = Array.new
			answers.each { |answer|
				userpollanswer = Polls::User::UserPollAnswer.new()
				userpollanswer.userid = user.userid
				userpollanswer.typeid = PollBlog::typeid
				which_answer += 1
				userpollanswer.answer = which_answer
				userpollanswer.answertext = answer

				poll.userpollanswers << userpollanswer
			}
			return poll
		end
		
		# Because #build doesn't create the userpollquestion and userpollanswer
		# objects, we have to do so here
		def after_create
			raise "No question specified" unless defined? self.userpollquestion
			raise "No answers specified" unless defined? self.userpollanswers
			raise "Not enough answers defined" if self.userpollanswers.length < 2
			
			self.userpollquestion.parentid = blogid
			self.userpollquestion.store
			self.userpollanswers.each { |userpollanswer|
				userpollanswer.parentid = blogid
				userpollanswer.store
			}
		end
		
		# Initialise the @userpollquestion and @userpollanswers appropriately.
		# Do things this way instead of via relations because we use the
		# #display method to preview ourselves, and we may not have related
		# data at that point.
		def after_load
			@userpollquestion = Polls::User::UserPollQuestion.find(:first,
			 	[self.userid, PollBlog::typeid, self.blogid])
			@userpollanswers = Polls::User::UserPollAnswer.find(
				[self.userid, PollBlog::typeid, self.blogid],
				:order => "answer ASC")
		end

		def display(request)
			bar_max = 400
			# Figure out if the user has already voted
			if (request.nil? || request.session.anonymous?)
				userpoll_vote = nil
				anonymous = true
			else
				userpoll_vote = Polls::User::UserPollVote.find(:first,
				 	[self.userid, PollBlog::typeid, self.blogid,
					request.session.user.userid])
			end
			if userpoll_vote != nil # already voted?
				t = Template::instance('blogs', 'blog_post_poll_results')
			else
				t = Template::instance('blogs', 'blog_post_poll_vote')
			end
			t.user = User.get_by_id(self.userid)
			t.blogid = self.blogid
			t.anon = anonymous
			t.question = @userpollquestion.question
			t.answers = Array.new()
			@userpollanswers.each_index { |index|
				answer = @userpollanswers[index]
				bar_len = 0
				# Is there a way to do this through JavaScript?
				if (@userpollquestion.tvotes > 0)
					bar_len = answer.votes * bar_max / @userpollquestion.tvotes
					bar_len = bar_max if bar_len > bar_max
				end
				t.answers << [ answer.answer, answer.answertext, answer.votes,
					bar_len ]
			}
			if (@userpollquestion.tvotes == 1)
				t.votes = "1 vote"
			else
				t.votes = "#{@userpollquestion.tvotes} votes"
			end

			t.poll_blog = self
			return t.display
		end
		
		def blog_type()
			return :poll
		end
		
	end
end

