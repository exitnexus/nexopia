module Profile
	class UserSkinAttribute
		attr_accessor :value, :input_type;
		
		def input_type
			if(@input_type.nil?())
				return :text;
			end
			return @input_type;
		end
	end
end
