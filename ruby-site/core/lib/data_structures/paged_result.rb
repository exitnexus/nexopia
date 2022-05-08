lib_require :Core, 'data_structures/storable_result'

class PagedResult < StorableResult
	attr_accessor :total_rows, :page, :page_length, :calculated_total

	def total_pages
		count = page
		if (calculated_total?)
			count = (total_rows.to_f/page_length.to_f).ceil
		elsif (more?)
			count = page+1
		end
		return count
	end

	def total_rows
		guessed_rows = 0
		if (calculated_total?)
			guessed_rows = @total_rows
		elsif (more?)
			guessed_rows = (page)*page_length + @total_rows
		else
			guessed_rows = (page-1)*page_length + @total_rows
		end
		return guessed_rows
	end

	def calculated_total?
		return calculated_total
	end

	def more?
		if (calculated_total?)
			return (@total_rows.to_f/page_length.to_f).ceil > page
		else
			return @total_rows >= page_length
		end
	end
	
end