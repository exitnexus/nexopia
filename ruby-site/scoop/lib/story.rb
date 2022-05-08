module Scoop
	class Story < Storable
		init_storable(:usersdb, 'scoop_story')
		extend TypeID

		# User that this story object was distributed to.
		relation :singular, :user, [:userid], User
		
		# Returns the object that reported the story
		# This will be the picture/gallery/user/whatever that caused the story to happen.
		def reporter
			storable_class = TypeID.get_class(self.typeid)
			promise_callback = lambda { |result|
				if (!result.nil?)
					result
				else
					$log.info "Deleting #{self} because associated reporter was unavailable.", :info
					$log.object caller, :debug
					self.delete
					nil
				end
			}
			if (storable_class.indexes[:PRIMARY].length > 1)
				storable = storable_class.find(self.primaryid, self.secondaryid, :first, :promise => promise_callback);
			else
				storable = storable_class.find(self.primaryid, :first, :promise => promise_callback);
			end
			return storable
		end
		
		def reevaluate_permissions!
			if (reporter.nil? || !reporter.can_view_event?(user.userid))
				delete
			end
		end
	end
end