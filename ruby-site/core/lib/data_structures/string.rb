class String
	def each_char
		i = 0;
		while(i < length) do
			yield self[i].chr;
			i += 1;
		end
	end
end
		