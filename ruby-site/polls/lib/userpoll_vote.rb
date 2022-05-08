lib_require :Core, "storable/storable"
lib_require :Polls, "userpoll_answer"

module Polls
	module User

		class UserPollVote < Cacheable
			init_storable(:usersdb, "userpollvotes")

			relation :singular, :owner, [:userid, :typeid, :parentid, :answer],
			 	Polls::User::UserPollAnswer

			def validate!
				if (self.answer != nil)
					if ((self.answer < 1) || (self.answer > 10))
						raise "Invalid answer value #{self.answer}"
					end
				end
			end

			def before_create
				validate!
			end

			def delete
				# We don't actually ever delete poll votes, just
				# keep them around for dumps.
			end

		end

	end
end
