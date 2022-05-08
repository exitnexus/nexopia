
class MogileFileUploadPair
	attr_accessor :mogile_file_name, :original_file_name, :input_name_base, :input_name_index, :input_array_syntax_type, :mogile_file_path;
	
	def initialize()
		self.input_array_syntax_type = :bracket;
	end
	
	def mogile_key()
		create_key("mogile");
	end
	
	def file_key()
		create_key("name");
	end
	
	def mogile_path_key()
		create_key("mogile_path");
	end
	
	def create_key(suffix)
		key_start = "#{self.input_name_base}_#{suffix}";
		
		if(self.input_name_index != nil)
			if(self.input_array_syntax_type == :bracket)
				key = "#{key_start}[#{self.input_name_index}]";
			elsif(self.input_array_syntax_type == :parenthesis)
				key = "#{key_start}(#{self.input_name_index})";
			end
			
		else
			key = key_start;
		end
		
		return key;
	end
end
