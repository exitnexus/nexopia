lib_require :core, "validation/results"

module Validation
	
	# A Validation::Chain represents a collection of Validation::Rules that will be checked in the order 
	# they were added to the Validation::Chain. The first rule that gives a Validation::Results in an
	# :error state will "break" the chain. No further validation will happen, and the Validation::Results
	# that will be returned. If the entire Validation::Chain contains rules that only resolve to :valid
	# or :warning state Validation::Results, the Validation::Chain will be considered to be :valid and
	# the Validation::Results from the last evaluated rule will be returned.
	class Chain
		
		def initialize()
			@chain = Array.new;
			@none_override = nil;
			@valid_override = nil;
		end
	
	
		def add(rule)
			@chain << rule;
		end
	
	
		# Sets a rule that will pre-maturely break the Validation::Chain if it returns Validation::Results
		# that have a state of :none. This override takes precedence over the valid_override.
		def set_none_override(rule)
			@none_override = rule;
		end
	

		# Sets a rule that will pre-maturely break the Validation::Chain if it returns Validation::Results
		# that have a state of :valid. This override is trumped by the none_override.
		def set_valid_override(rule)
			@valid_override = rule;
		end
	

		# Validates the chain and returns Validation::Results in the following order:
		# 1. A none_override that has Validation::Results in a :none state.
		# 2. A valid_override that has Validation::Results in a :valid state.
		# 3. The Validation::Results of a rule that fails to resolve into a :valid or
		# 	 :warning state.
		# 4. The Validation::Results of the rule that was last added to the chain.
		def validate
			if (!@none_override.nil?)
				results = @none_override.validate;
				if (results.state == :none)
					return results;
				end
			end

			if (!@valid_override.nil?)
				results = @valid_override.validate;
				if (results.state == :valid)
					return results;
				end
			end
		
			results = Results.new(:valid,"");
		
			@chain.each { |rule|
				results = rule.validate;
				if (results.state == :error)
					return results;
				end
			};
		
			return results;
		end
		
		
		def javascript(name)
			string = "#{name}_chain = new ValidationChain();\n"
			@chain.each { |rule|
				string = string + "#{name}_chain.add(#{rule.javascript()});\n"
			};
			
			if (!@none_override.nil?)
				string = string + "#{name}_chain.setNoneOverride(#{@none_override.javascript()});\n";
			end
			
			if (!@valid_override.nil?)
				string = string + "#{name}_chain.setValidOverride(#{@valid_override.javascript()});\n";
			end
			
			return string;
		end
	end
end