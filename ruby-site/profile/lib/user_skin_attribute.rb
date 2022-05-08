module Profile
	class UserSkinAttribute
		attr_accessor :value, :input_type;
		
		def input_type
			if(@input_type.nil?())
				return :text_swatch;
			end
			return @input_type;
		end
		
		def duplicate()
			new_attr = self.class.new();
			new_attr.value = @value;
			if(!@input_type.nil?())
				new_attr.input_type = @input_type;
			end
			return new_attr;
		end
		
		def generate_input(attr_name, label, *args)
			if(self.input_type == :text_swatch)
				return generate_text_swatch(attr_name, label, args.first);
			elsif(self.input_type == :checkbox)
				return generate_checkbox(attr_name, label, args.first);
			end
		end
		
		def generate_text_swatch(attr_name, label, options_hash)
			t = Template.instance("profile", "skin_edit_text_swatch_control");
			
			t.attr_name = attr_name;
			t.value = self.value;
			t.label = label;
			
			return t.display();
		end
		
		def generate_checkbox(attr_name, label, options_hash)
			t = Template.instance("profile", "skin_edit_checkbox_control");
			
			show_gutters = false;
			compare_color = options_hash[:compare_value];
			if(self.value == compare_color)
				show_gutters = true;
			end
			
			t.attr_name = attr_name;
			t.label = label;
			t.show_gutters = show_gutters;
			
			return t.display();
		end
	end
end
