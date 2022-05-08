# Allow constraints and actions to be registered.
module Orwell
	class ConstraintsAndActions
		# Register a constraint.
		def self.add_constraint(klass, name)
			$log.info "Adding constraint #{klass.class}\##{name.to_s} for type #{klass.typeid}", :debug, :worker
			@@constraints = Hash.new() if !defined?(@@constraints)
			@@constraints[klass.typeid] = [klass, name]
		end

		def self.add_action(klass, name)
			$log.info "Adding action #{klass.class}\##{name.to_s} for type #{klass.typeid}", :debug, :worker
			@@actions = Hash.new() if !defined?(@@actions)
			@@actions[klass.typeid] = [klass, name]
		end

		def self.call_constraint(typeid, user)
			@@constraints = Hash.new() if !defined?(@@constraints)
			raise "Cannot find constraint" if !@@constraints.has_key?(typeid)

			elem = @@constraints[typeid]
			return elem[0].send(elem[1], user)
		end
		
		# Call all the constraints on this user object.
		# Return an array of typeids for constraints that
		# passed.
		def self.call_constraints(user)
			@@constraints = Hash.new() if !defined?(@@constraints)
			matches = Array.new()
			@@constraints.each { |key, value|
				if self.call_constraint(key, user)
					matches << key
				end
			}
			return matches
		end

		def self.call_action(typeid, user)
			@@actions = Hash.new() if !defined?(@@actions)
			raise "Cannot find action" if !@@actions.has_key?(typeid)

			elem = @@actions[typeid]
			return elem[0].send(elem[1], user)
		end
		
		def self.call_actions(typeids, user)
			typeids.each { |typeid|
				self.call_action(typeid, user)
			}
		end
	end
	
end

module Kernel
	def orwell_constraint(name)
		Orwell::ConstraintsAndActions.add_constraint(self, name);
	end

	def orwell_action(name)
		Orwell::ConstraintsAndActions.add_action(self, name);
	end
end
