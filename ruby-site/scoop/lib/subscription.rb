module Scoop
	class Subscription < Storable
		init_storable(:usersdb, 'scoop_subscriptions')
		extend TypeID
	end
end