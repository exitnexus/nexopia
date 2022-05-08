lib_require :Core, "json"

describe String do
	it "should not be different from utf8 version for lower 128 in character set" do
		lower128 = []
		(0..127).each { |i| lower128 << i.chr }
		lower128_string = lower128.join
		lower128_string.should == lower128_string.utf8
	end
	
	it "should remove characters that can't be converted to utf8" do
		upper128 = []
		(128..255).each { |i| upper128 << i.chr }
		upper128_string = upper128.join
		convertible = upper128_string.convertible_to_utf8
		bad_characters = []
		for i in (0..convertible.length)
			if([129,141,143,144,157].include?(convertible[i]))
				bad_characters << convertible[i]
			end
		end
		
		bad_characters.length.should == 0
	end
end