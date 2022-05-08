lib_require :Core, 'users/user'

class User < Cacheable
	def update_hash
		self.googlehash = Time.now.to_i
		self.store
	end
	class << self
		def update_hash(userids)
			users = self.find(:all, *userids)
			users.each {|user|
				user.update_hash
			}
		end
	end
end