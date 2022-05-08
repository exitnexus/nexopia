# describe UserName do
# 	before(:each) do
# 		@user_name_obj = UserName.new();
# 	end
# 	
# 	before(:all) do
# 		temp = UserName.new();
# 		temp.userid = 1;
# 		temp.username = "Mike";
# 		temp.store();
# 		
# 		temp = UserName.new();
# 		temp.userid = 2;
# 		temp.username = "Nathan";
# 		temp.store();
# 	end
# 	
# 	it "can set username" do
# 		user_name = UserName.new();
# 		user_name.username = "Mike";
# 		user_name.username.should eql("Mike");
# 	end
# 	
# 	it "username obj to_s returns Username.username" do
# 		user_name = UserName.new();
# 		user_name.username = "Mike";
# 		user_name.to_s().should eql("Mike");
# 	end
# 	
# 	it "can load username from userid" do
# 		user_name_list = UserName.find(:all, :scan);
# 		user_name_list.should_not be_empty();
# 		user_name = UserName.find(:first, user_name_list.first.userid);
# 		user_name.username.should eql(user_name_list.first.username);
# 	end
# 	
# 	it "can not load username from invalid userid" do
# 		user_name = UserName.find(:first, -1);
# 		user_name.should be_nil();
# 	end
# 	
# 	it "can set userid" do
# 		user_name = UserName.new();
# 		user_name.userid = 1;
# 		user_name.userid.should eql(1);
# 	end
# 	
# 	it "can load a UserName object from a username" do
# 		user_name_list = UserName.find(:all, :scan);
# 		user_name_list.should_not be_empty();
# 		user_name = UserName.by_name(user_name_list.first.username);
# 		user_name.username.should eql(user_name_list.first.username);
# 	end
# 	
# 	after(:all) do
# 		UserName.db.query("TRUNCATE usernames;")
# 	end
# 
# end