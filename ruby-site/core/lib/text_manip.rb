require 'strscan'

class SpamFilterException < Exception
end



class String
	def wrap(len)
		s = StringScanner.new(self)
		output = "";
		while(!s.eos?)
			word = s.scan(/([^<\[\s]+)/);
			if (word)
				while (word.length > len)
					output << word[0...len] + " ";
					word = word[len..-1];
				end
				output << word;
			end
			space = s.scan(/\s+/);
			if (space)
				output << space
			end
			tag = s.scan(/(<|\[)([^>\]]+)(>|\])/);
			if (tag)
				output << tag;
			end
		end
		return output
	end

	def spam_filter()
	
		if (self.length <= 1)
			raise SpamFilterException.new("Message is too short.");
		end
		if (self.length > 15000)
			raise SpamFilterException.new("Message is too long.");
		end
	
		#can't break the rest if it's that short
		if (self.length < 200)
			return true;
		end
	
		if (self.count("\n") > 150)
			raise SpamFilterException.new("Message is too many paragraphs.");
		end
		if (self.count(":") > 200)
			raise SpamFilterException.new("Too many smileys.");
		end
		if (self.count("[img") > 30)
			raise SpamFilterException.new("Too many images.");
		end
	
		if (self.length > 500)
			wordlen=0;
			html=false;
			total = 0;
	
			(0...self.length).each{|i|
				if(self[i]== ?< || self[i]== ?[)
					html=true;
				elsif(self[i]== ?> || self[i]== ?])
					html=false;
				end
	
				if !html
					if [' ', "\t", "\n", "\r", '[', ']', '<', '>'].index(self[i..i+1])
						if(wordlen > 35)
							total += wordlen;
						end
						wordlen=0;
					else
						if (wordlen > 100)
							raise SpamFilterException.new("At least 1 word is too long.")
						end
						wordlen +=1;
					end
				end
			}
	
			if (total > 1000)
				raise SpamFilterException.new("Too many overly long words.");
			end
	
			whitespace = self.gsub(/[^\s]+/, '').length;
	
			if whitespace > (self.length / 2)
				raise SpamFilterException.new("Too much white space.");
			end
		end
	
		return true;
	end

end


def time
	start = Time.now.to_f
	yield
	Time.now.to_f - start;
end

=begin
#str = "kkjasdlkjasldjkkjasdlkjasldjkkjasdlkjasldjkkjasdlkjasldjkkjasdlkjasldjkkjasdlkjasldjkkjasdlkjasldjkkjasdlkjasldjkkjasdlkjasldjkkjasdlkjasldj kkjasdlkjasldjkkjasdlkjasldjkkjasdlkjasldj kkjasdlkjasldjkkjasdlkjasldjkkjasdlkjasldj "
#str = "alskdjaslkdjalskdj  :) kjshdfkjshdkjshdfkjsfdh :transport: kjhsadkhaksjhdas : : : : : : : : : "
#str = File.new("/home/troy/bbcode.txt").read 
str = "Timo wants real words http://kjshdf :( okay :transport: :blush: :blush: :blush: :blush: :blush: :love:"

if (Smilies::smilify(str) != Smilies::smilify4(str))
	puts "comparison failed"
	puts Smilies::smilify(str)
	puts Smilies::smilify4(str)
end

puts time{
	(1..1000).each{
		Smilies::smilify(str);
	}
}
puts time{
	(1..1000).each{
		Smilies::smilify3(str);
	}
}
puts time{
	(1..1000).each{
		Smilies::smilify4(str);
	}
}
puts time{
	(1..1000).each{
		Smilies::smilify5(str);
	}
}
=end
