# This file is generated. DO NOT MODIFY!

module CSS

class ErrorStream

	attr_accessor :count

	def initialize()
	  @count = 0
	end

	def ==(o)
	  !o.nil? &&
	    @count == o.count
	end
	
	def ParsErr(n, line, col, i, str)
		s = ""
		@count += 1
		case (n)
			when 0; s = "EOF expected"
			when 1; s = "ident expected"
			when 2; s = "number expected"
			when 3; s = "var expected"
			when 4; s = "lb expected"
			when 5; s = "rb expected"
			when 6; s = "whitespace expected"
			when 7; s = "string expected"
			when 8; s = "badString expected"
			when 9; s = "',' expected"
			when 10; s = "'+' expected"
			when 11; s = "'>' expected"
			when 12; s = "'*' expected"
			when 13; s = "'#' expected"
			when 14; s = "'.' expected"
			when 15; s = "'[' expected"
			when 16; s = "'=' expected"
			when 17; s = "'=~' expected"
			when 18; s = "'|=' expected"
			when 19; s = "']' expected"
			when 20; s = "':' expected"
			when 21; s = "'(' expected"
			when 22; s = "')' expected"
			when 23; s = "';' expected"
			when 24; s = "'&' expected"
			when 25; s = "'_' expected"
			when 26; s = "'-' expected"
			when 27; s = "'!important' expected"
			when 28; s = "'/' expected"
			when 29; s = "'url(' expected"
			when 30; s = "'%' expected"
			when 31; s = "??? expected"
			when 32; s = "invalid URL"
			when 33; s = "invalid Number"
			when 34; s = "invalid Operator"
			when 35; s = "invalid Declaration"
			when 36; s = "invalid Value"
			when 37; s = "invalid Attrib"
			when 38; s = "invalid SimpleSelector"
			when 39; s = "invalid NospaceCombinator"
			when 40; s = "invalid Declarations"
			when 41; s = "invalid Declarations"

		else s = "error #{n}"
		end
		raise("-- line #{line} col #{col}: \n" + s + ", found #{str} : #{i}");
	end
	
	def SemErr(n, line, col)
		@count += 1
		raise("-- line #{line} col #{col}: Semantic Error #{n}")
	end
	
	def Exception(s)
		raise(s)
		exit(1)
	end
	
end
end
