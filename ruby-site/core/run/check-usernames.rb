lib_require :Core, 'users/user_name'
require 'json'
require 'iconv'

def each_user
	r = UserName.db.query("SELECT * FROM usernames WHERE live IS NOT NULL")
	r.each {|row|
		yield(row['userid'], row['username'])
	}
end

iconv = Iconv.new('UTF-8', 'WINDOWS-1252')
back = Iconv.new('WINDOWS-1252', 'UTF-8')

each_user {|userid, username|
	begin
		username.to_json
	rescue
		begin
			unicode = iconv.iconv(username)
			reverse = back.iconv(unicode)
			unicode.to_json
			puts("Succeeded by using iconv: #{userid} (#{username}, #{unicode}, #{reverse})")
			if (username != reverse)
				puts("Conversion was lossy: #{userid} (original: #{username}, reverse: #{reverse})")
			end
		rescue
			puts("Failed even with iconv: #{userid}")
		end
	end
}