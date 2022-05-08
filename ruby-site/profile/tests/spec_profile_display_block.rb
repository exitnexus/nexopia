lib_require :Profile, 'profile_display_block';

module Profile
	describe ProfileDisplayBlock do
		it "should show it's nuts" do
			display_block = ProfileDisplayBlock.new();
			display_block.userid = 216;
			display_block.userid.should eql(216);
		end
	
		it "should show nothing" do
			display_block = ProfileDisplayBlock.new();
			display_block.path = "profile";
			display_block.path.should eql("profile");
		end
	end
end