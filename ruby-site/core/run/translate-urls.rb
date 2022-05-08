lib_require :Core, 'users/user_name'
require 'cgi'

# first do a mapping of userids to usernames
def get_users
	r = UserName.db.query("SELECT * FROM usernames WHERE live IS NOT NULL")
	users = {}
	r.each {|row|
		users[ row['userid'] ] = row['username']
	}
	users
end
users = get_users

def escape(s)
	if (s)
		CGI::escape(s)
	else
		"<unknown>"
	end
end

# now go through the urls in urls.in, and reoutput them to urls.out with the transformations applied.
File.open("urls.in", "r") {|fin|
	File.open("urls.out", "w") {|fout|
		fin.each {|line|
			cols = line.chomp.split(' ', 3)
			time, host, url = cols
			
			if (time && host && url) # ignore lines that don't parse properly.
				orig_url = url.dup
				url.gsub!(/profile\.php\?uid=(\d+)/) {|i| "users/#{escape(users[$1])}\?" }
				url.gsub!(/gallery\.php\?uid=(\d+)/) {|i| "users/#{escape(users[$1])}/gallery\?" }
				url.gsub!(/galleries\/([^\/]+)\/(\d+)\/.*$/) {|i| "users/#{escape($1)}/gallery\/#{$2}" }
				url.gsub!(/galleries\/([^\/]+)/) {|i| "users/#{escape($1)}/gallery\?" }
				url.gsub!(/friends\.php\?uid=(\d+)/) {|i| "users/#{escape(users[$1])}/friends\?" }
				url.gsub!(/usercomments\.php\?id=(\d+)/) {|i| "users/#{escape(users[$1])}\?" }
				url.gsub!(/userconversation\.php\?uid1=(\d+)&uid2=(\d+)/) {|i| "users/#{escape(users[$1])}/comments/conversation/#{$2}\?" }
				
				if (url =~ /<unknown>/)
					puts("unknown userid encountered translating #{orig_url}, skipping.")
					next
				end
	
				fout.puts("#{time} #{host} #{url}")
			end
		}
	}
}