
class String
	def range_list
		ret = []
		
		self.split(',').each{|s|
			if(s['-'])
				b,e = s.split('-')
				ret << (b.to_i .. e.to_i).to_a
			else
				ret << s.to_i
			end
		}
		
		return ret.flatten
	end
end
