lib_require :Core, "storable/storable"

module Polls
	module User

		class UserPollAnswer < Cacheable
			MAX_ANSWERS = 10

			init_storable(:usersdb, "userpollanswers")

			def initialize(*args)
				self.votes = 0

				super(*args)
			end

			def validate!
				raise "Invalid answer number" if self.answer < 1
				raise "Invalid answer number" if self.answer > 10
				self.answertext = self.answertext.slice(0..254).strip
				raise "No answer" if self.answertext.empty?()
				raise "Invalid votes value" if self.votes < 0
			end

			def before_create
				validate!
			end

			def delete
				# We don't actually ever delete poll answers, just
				# keep them around for dumps.
			end
			
			def incr_vote
				self.votes += 1
				self.invalidate()
				db.query("UPDATE IGNORE userpollanswers
				 		  SET votes = votes + 1
						  WHERE userid = # AND typeid = ? AND parentid = ?
						  AND answer = ?",
						 self.userid, self.typeid, self.parentid, self.answer)
			end

		end

	end
end
