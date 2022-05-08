class SelectorOptions
	SelectorOption = Struct.new :value, :text;
	
	def SelectorOptions.option_array_from_assoc_array(assoc_array)
		options = Array.new;
		
		assoc_array.each { |member| 
			options << SelectorOption.new(member[0], member[1]);
		};
		
		return options;
	end
end