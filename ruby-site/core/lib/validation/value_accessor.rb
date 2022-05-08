lib_require :core, 'validation/results';

require 'resolv'

module Validation
	class ValueAccessor
		attr_reader :field_id, :field_value;

		def initialize(field_id, field_value)
			@field_id = field_id;
			@field_value = field_value;
		end
		
		
		def value
			return field_value;
		end
		
		
		def javascript
			string = "new ValidationValueAccessor(\"#{field_id}\", \"#{field_value}\")";
			
			return string;
		end
	end
end