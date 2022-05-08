lib_require :core, 'validation/results';

require 'resolv'

module Validation
	class Rule
		def initialize(value_accessors, static_values=[])
			@value_accessors = value_accessors;
			@static_values = static_values;
		end
		
		
		def javascript
			value_accessor_string = @value_accessors.map { | value_accessor | "#{value_accessor.javascript}" } * ",";
			static_value_string = @static_values.map { | static_value | static_value.nil? ? "null" : "\"#{static_value}\"" } * ",";
			
			#determining the javascript class and namespace to use for the validation rule.
			class_name = self.class.to_s;
			name_parts = class_name.split("::");
			derived_class_name = name_parts.last;
			name_parts.delete(derived_class_name);
			javascript_name_space = "";
			name_parts.each{|s| javascript_name_space << s}
			javascript_classname = javascript_name_space << "." << derived_class_name;
			
			string = "new #{javascript_classname}(new Array(#{value_accessor_string}), new Array(#{static_value_string}))";
			
			return string;
		end
	end
end