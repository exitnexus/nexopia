lib_require :Core, "storable/cacheable"
lib_want :Profile, "profile_display_block"
lib_want :GoogleProfile, "google_user"
lib_require :Polls, "userpoll_answer"

lib_want :UserDump, "dumpable"

module Polls
	module User

		class UserPollQuestion < Cacheable
			init_storable(:usersdb, "userpollquestions")
			relation :multi, :userpollanswers, [:userid, :typeid, :parentid],
			 	UserPollAnswer, :order => "answer ASC", :extra_columns => :answer

			if (site_module_loaded?(:Profile))
				relation_singular :owner, [:userid, :parentid],
				 	Profile::ProfileDisplayBlock
			end

			def initialize(*args)
				self.deleted = false
				self.tvotes = 0
				
				super(*args)
			end

			def validate!
				self.question = self.question.slice(0..254).strip
				raise "No question" if self.question.empty?()
				raise "Invalid tvotes value" if self.tvotes < 0
			end

			def valid_answer?(ans)
				return true if ans == nil

				ans = ans.to_i
				return false if (ans < 1)
				return false if (ans > self.userpollanswers.length())
				return true
			end	

			def before_create
				validate!
#				if (site_module_loaded?(:GoogleProfile) &&
#					site_module_loaded?(:Profile))
#					
#					self.owner.update_hash
#				end
				super
			end

			def after_create
#				if (site_module_loaded?(:GoogleProfile))
#					self.owner.update_hash
#				end
				super
			end

			def delete
				self.deleted = true
				store()
			end
			
			def incr_vote
				self.tvotes += 1
				self.invalidate()
				db.query("UPDATE IGNORE userpollquestions
				 		  SET tvotes = tvotes + 1
						  WHERE userid = # AND typeid = ? AND parentid = ?",
						 self.userid, self.typeid, self.parentid)
			end
			
			if (site_module_loaded?(:UserDump))
	  	  extend Dumpable

	  		def self.user_dump(user_id, start_time = 0, end_time = Time.now())
	    		poll_list = self.find([user_id,
		 			Profile::ProfileDisplayBlock::typeid]);
					
					out = "";
					poll_list.each{|poll|
						out += "-" * 80
						out += "\n"
						out += "Poll #{poll.parentid}\n"
						out += "Question: #{poll.question}\n"
						out += "\tTotal Votes: #{poll.tvotes}\n"
						poll.userpollanswers.each{|answer|
							out += "\t\t1. #{answer.answertext}\t\tVotes: #{answer.votes}\n"
						}
						out += "Posted on: #{Time.at(poll.date).gmtime.to_s()}\n"
						if(poll.deleted)
							out += "Poll Deleted By User\n"
						end
						out += "-" * 80
						out += "\n\n\n"
					};

		      return Dumpable.str_to_file("#{user_id}-userpolls.txt", out)
			  end
		  end		
		end

	end
end
