lib_require :Core, 'validation/display', 'validation/set', 'validation/results', 'validation/rules', 'validation/chain'
lib_require :Core, 'validation/rule', 'validation/value_accessor'

module GroupsValidationHelper
	def _validate_location(location)
		chain = Validation::Chain.new;

		chain.add(Validation::Rules::CheckLocation.new(Validation::ValueAccessor.new("location", location)));
		
		chain.set_none_override(Validation::Rules::ClearOnNil.new(Validation::ValueAccessor.new("location", location)));

		return chain;
	end


	def _validate_name(name)
		chain = Validation::Chain.new;
		
		chain.add(Validation::Rules::CheckNotEmpty.new(Validation::ValueAccessor.new("name", name)));
		chain.add(Validation::Rules::CheckLength.new(Validation::ValueAccessor.new("name", name), "Name", 3, 100));
		chain.add(Validation::Rules::CheckAlphaCharactersExist.new(Validation::ValueAccessor.new("name", name)));
		
		chain.set_none_override(Validation::Rules::ClearOnNil.new(Validation::ValueAccessor.new("name", name)));

		return chain;
	end


	def _validate_from(from_month, from_year)
		chain = Validation::Chain.new;

		chain.add(Validation::Rules::CheckNotEqualTo.new(
			Validation::ValueAccessor.new("from_month", from_month),
			-1, "Must select a month"));
		chain.add(Validation::Rules::CheckNotEqualTo.new(
			Validation::ValueAccessor.new("from_year", from_year),
			-1, "Must select a year"));
	
		chain.set_none_override(Validation::Rules::ClearOnNil.new(Validation::ValueAccessor.new("from_month", from_month)));
	
		return chain;
	end


	def _validate_to(to_month, to_year, present)
		chain = Validation::Chain.new;

		chain.add(Validation::Rules::CheckNotEqualTo.new(
			Validation::ValueAccessor.new("to_month", to_month),
			-1, "Must select a month"));
		chain.add(Validation::Rules::CheckNotEqualTo.new(
			Validation::ValueAccessor.new("to_year", to_year),
			-1, "Must select a year"));
		
		chain.set_valid_override(Validation::Rules::CheckChecked.new(
			Validation::ValueAccessor.new("present", present)));
		
		# We want the icon to get cleared out if there is no to_month and no present. We clear on a nil value.
		# While the month will end up being nil if it wasn't there, we're already converting the value of the
		# checkbox to be true/false before we pass it in. This is a little hacky, yes, but it works. It does
		# really suggest that the concept of the "override" and "clear_on" stuff needs to be reworked.
		value = to_month || (present ? present : nil);
		
		chain.set_none_override(Validation::Rules::ClearOnNil.new(Validation::ValueAccessor.new("to_month", value)));

		return chain;
	end
end