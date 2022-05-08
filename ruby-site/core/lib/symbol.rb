
class Symbol
	def self.dump_symbols(filename)
		File.open(filename,'w'){|fp|
			fp.write("id,symbol,length\n");
			Symbol.all_symbols.each{|sym|
				fp.write("#{sym.to_i},#{sym.inspect},#{sym.to_s.length+1}\n")
			}
		}
	end
end

