lib_want :Moderator, "modqueue"

if (site_module_loaded?(:Moderator))
	module Profile
		class UserAbuseModItem < Moderator::ModItem
			relation_singular :user, :itemid, User
		end
		
		class UserAbuseQueue < Moderator::QueueBase
			declare_queue("User Abuse", 31)
			self.item_type = UserAbuseModItem

			def handle_yes(items)
				raise "Yes votes on users #{items.join(',')}, but not implemented yet."
			end
			def handle_no(items)
				raise "No votes on users #{items.join(',')}, but not implemented yet."
			end
		end

		class UserAbuseConfirmQueue < Moderator::QueueBase
			declare_queue("Confirmed Abuse", 32)
			self.item_type = UserAbuseModItem

			def handle_yes(items)
				raise "Yes votes on users #{items.join(',')}, but not implemented yet."
			end
			def handle_no(items)
				raise "No votes on users #{items.join(',')}, but not implemented yet."
			end
		end	
	end
end