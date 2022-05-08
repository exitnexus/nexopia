class Class
	def descendant_of?(a_class)
		if (self.superclass.nil?)
			return false
		elsif (self.superclass == a_class)
			return true
		else
			return self.superclass.descendant_of?(a_class)
		end
	end
	
	def ancestor_of?(a_class)
		return a_class.descendant_of?(self)
	end
end