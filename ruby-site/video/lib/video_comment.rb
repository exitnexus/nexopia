lib_require :Core, 'storable/storable'

class VideoComment < Storable
	init_storable(:videodb, 'videocomment');
	
	relation_singular :user, :userid, User;
	
	def username
		user.username;
	end 
	
	def user_age
		return user.age;
	end
	
	def user_sex
		return user.sex;
	end
	
	def user_stats
		return "Age #{user_age}, #{user_sex}";
	end

	def display_comment
		#e_str = BBCode::ErrorStream.new();
		#bb_scan = BBCode::Scanner.new();
		#bb_scan.InitFromStr(comment, e_str);
		#bb_parser = BBCode::Parser.new(bb_scan);
		
		#return bb_parser.Parse();
		return BBCode.parse(comment)
	end
end