lib_want	:Profile, "profile_block_query_info_module", "profile_block", "profile_display_block"
lib_require :Polls, "userpoll_question", "userpoll_answer", "userpoll_vote"

module Profile
	class UserPollBlock < PageHandler
		declare_handlers("profile_blocks/Polls/poll") {
			area :User
			access_level :Any
			page	:GetRequest, :Full, :userpoll_block, input(Integer)
			handle	:PostRequest, :userpoll_skip, input(Integer), "skip"
			handle	:PostRequest, :userpoll_vote, input(Integer), "vote"

			area :Self
			access_level :IsUser
			page	:GetRequest, :Full, :userpoll_block_new, "new"

			handle	:PostRequest, :userpoll_block_save, input(Integer), "create"
			handle	:PostRequest, :visibility_save, input(Integer), "visibility"

			access_level :IsUser, CoreModule, :editprofile
			handle	:PostRequest, :userpoll_block_remove, input(Integer), "remove"
		}


		def userpoll_block(block_id)
			edit_mode = params["profile_edit_mode", Boolean, false]

			if(!ProfileDisplayBlock.verify_visibility(block_id, request.user, request.session.user, edit_mode))
				puts "<h1>Not visible</h1>"
				return
			end

			userpoll_question = Polls::User::UserPollQuestion.find(:first,
			 	[request.user.userid, ProfileDisplayBlock::typeid,
				 block_id])
			userpoll_answers = Polls::User::UserPollAnswer.find(
				[request.user.userid, ProfileDisplayBlock::typeid,
				 block_id], :order => "answer ASC")

			p_block = ProfileDisplayBlock.find(:first,
			 	[request.user.userid, block_id])
			wide = (p_block.current_column() == :wide)
			bar_max = wide ? 400 : 200
			# Figure out if the user has already voted
			if (request.session.anonymous?)
				userpoll_vote = nil
			else
				userpoll_vote = Polls::User::UserPollVote.find(:first,
				 	[request.user.userid, ProfileDisplayBlock::typeid,
					 block_id, request.session.user.userid])
			end
			if userpoll_vote != nil # already voted?
				if wide
					t = Template::instance('polls', 'poll_results_wide')
				else
					t = Template::instance('polls', 'poll_results_narrow')
				end
			else
				if wide
					t = Template::instance('polls', 'poll_vote_wide')
				else
					t = Template::instance('polls', 'poll_vote_narrow')
				end
			end
			t.user = request.user
			t.parentid = block_id
			t.anon = request.session.anonymous?
			t.question = userpoll_question.question
			t.answers = Array.new()
			userpoll_answers.each_index { |index|
				answer = userpoll_answers[index]
				bar_len = 0
				# Is there a way to do this through JavaScript?
				if (userpoll_question.tvotes > 0)
					bar_len = answer.votes * (wide ? 400 : 200) /
						userpoll_question.tvotes
					bar_len = bar_max if bar_len > bar_max
				end
				t.answers << [ answer.answer, answer.answertext, answer.votes,
					bar_len ]
			}
			if (userpoll_question.tvotes == 1)
				t.votes = "1 vote"
			else
				t.votes = "#{userpoll_question.tvotes} votes"
			end

			puts t.display()
		end


		def self.userpoll_block_query(info)
			if(site_module_loaded?(:Profile))
				info.extend(ProfileBlockQueryInfo)
				info.title = "Poll"
				info.initial_position = 50
				info.initial_column = 1
				info.form_factor = :both
				info.max_number = 1
				info.immutable_after_create = true
				info.javascript_init_function = ProfileBlockQueryInfo::JavascriptFunction.new("initialize_userpoll_create")
				info.content_cache_timeout = 120
				info.admin_removable = true;
			end

			return info
		end


		def userpoll_skip(block_id)
			raise "Anonymous users cannot vote" if request.session.anonymous?
			userpoll_vote = Polls::User::UserPollVote.find(:first,
				[request.user.userid, ProfileDisplayBlock::typeid,
				 block_id, request.session.user.userid])
			# Ensure the user hasn't already voted.  If they have, don't record
			# the vote, just show the results
			if userpoll_vote == nil
				# Vote with null answer
				userpoll_vote = Polls::User::UserPollVote.new()
				userpoll_vote.userid = request.user.userid
				userpoll_vote.typeid = ProfileDisplayBlock::typeid
				userpoll_vote.parentid = block_id
				userpoll_vote.voterid = request.session.user.userid
				userpoll_vote.answer = nil
				userpoll_vote.time = Time.now.to_i()
				userpoll_vote.store
			end
			
			# Load the results
			if (params['ajax', String, 'true'] == 'true')
				result = userpoll_block(block_id)
			end
		end


		def userpoll_vote(block_id)
			# Ensure the user hasn't already voted
			raise "Anonymous users cannot vote" if request.session.anonymous?
			userpoll_vote = Polls::User::UserPollVote.find(:first,
				[request.user.userid, ProfileDisplayBlock::typeid,
				 block_id, request.session.user.userid])
			# Ensure the user hasn't already voted.  If they have, don't record
			# the vote, just show the results
			if userpoll_vote == nil
				vote = params["vote", String, "-1"].to_i
				raise "Invalid vote value" if (vote < 1) || (vote > 10)
			
				userpoll_answer = Polls::User::UserPollAnswer.find(:first,
					[request.user.userid, ProfileDisplayBlock::typeid,
					 block_id, vote])
				raise "Invalid vote value" if userpoll_answer == nil
			
				userpoll_question = Polls::User::UserPollQuestion.find(:first,
			 		[request.user.userid, ProfileDisplayBlock::typeid,
				 	 block_id])
				raise "Invalid question" if userpoll_question == nil
			
				# Vote with selected answer
				userpoll_vote = Polls::User::UserPollVote.new()
				userpoll_vote.userid = request.user.userid
				userpoll_vote.typeid = ProfileDisplayBlock::typeid
				userpoll_vote.parentid = block_id
				userpoll_vote.voterid = request.session.user.userid
				userpoll_vote.answer = vote
				userpoll_vote.time = Time.now.to_i()
				userpoll_vote.store
			
				userpoll_answer.incr_vote()
				userpoll_question.incr_vote()
			end

			# Load the results
			if (params['ajax', String, 'true'] == 'true')
				result = userpoll_block(block_id)
			end
		end


		def userpoll_block_new()
			request.reply.headers['Content-Type'] = PageRequest::MimeType::PlainText

			t = Template::instance('polls', 'poll_create')
			puts t.display()
		end

		def userpoll_block_save(block_id)
			request.reply.headers['Content-Type'] = PageRequest::MimeType::PlainText

			question = params["question", String, ""].strip
			answers = Array.new()
			for i in 1..10
				answer = params["ans_#{i}", String, ""].strip
				answers << answer if !answer.empty?
			end
			raise "Empty question" if question.empty?
			raise "Insufficient answers" if answers.length() < 2

			# Remove any existing questions
			existing_questions = Polls::User::UserPollQuestion.find(
				[request.user.userid, ProfileDisplayBlock::typeid,
				 block_id])
			existing_questions.each { |question|
				question.delete()
			}
			
			# Now deal with the question and answers
			userpollquestion = Polls::User::UserPollQuestion.new()
			userpollquestion.userid = request.user.userid
			userpollquestion.typeid = ProfileDisplayBlock::typeid
			userpollquestion.parentid = block_id
			userpollquestion.question = question
			userpollquestion.date = Time.now.to_i()
			userpollquestion.store()
			which_answer = 0
			answers.each { |answer|
				userpollanswer = Polls::User::UserPollAnswer.new()
				userpollanswer.userid = request.user.userid
				userpollanswer.typeid = ProfileDisplayBlock::typeid
				userpollanswer.parentid = block_id
				which_answer += 1
				userpollanswer.answer = which_answer
				userpollanswer.answertext = answer
				userpollanswer.store
			}

			if (request.impersonation?)
				$log.info(["edit text", "Edited text on block id: #{block_id}"], :info, :admin)
			end
		end

		def userpoll_block_remove(block_id)
			userpoll_question = Polls::User::UserPollQuestion.find(:first,
		 		[request.user.userid, ProfileDisplayBlock::typeid,
			 	 block_id])
			userpoll_question.delete() if userpoll_question != nil
		end

		def visibility_save(block_id)
			return
		end
	end
end
