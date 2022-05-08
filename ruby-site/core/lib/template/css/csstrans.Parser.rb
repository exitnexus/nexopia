# This file is generated. DO NOT MODIFY!


module CSS

class Parser
	private; MaxT = 31
	private; MaxP = 31

	private; T = true
	private; X = false
	
	def initialize(scanner)
		@token=nil			# last recognized token
		@t=nil				# lookahead token
	    @scanner=scanner
	end
    
	attr :symboltable, true;

	private; def Parser.SemErr(n)
		Scanner.err.SemErr(n, @t.line, @t.col)
	end
	



	private; def Error(n)
		@scanner.err.ParsErr(n, @t.line, @t.col, @t.kind, @t.val)
	end
	
	private; def Get
		while true
			@token = @t
			@t = @scanner.Scan
			return if (@t.kind<=MaxT)

			@t = @token
		end
	end
	
	private; def Expect(n)
		if (@t.kind==n) then
		  Get()
		else
		  Error(n)
		end
	end
	
	private; def StartOf(s)
		return @@set[s][@t.kind]
	end
	
	private; def ExpectWeak(n, follow)
		if (@t.kind == n)
		  Get()
		else
		  Error(n);
		  while (!StartOf(follow))
		    Get();
		  end
		end
	end
	
	private; def WeakSeparator(n, syFol, repFol)
		s = []
		if (@t.kind==n) then
		  Get()
		  return true
		elsif (StartOf(repFol))
		  return false
		else
			for i in 0..MaxT
				s[i] = @@set[syFol][i] || @@set[repFol][i] || @@set[0][i]
			end
			Error(n)
			while (!s[@t.kind])
			  Get()
			end
			return StartOf(syFol)
		end
	end
	
	private; def URL()
		begin_pos = @scanner.pos
		Expect(29)
		u = "url("; 
		if (@t.kind==7) then
			Get()
			u << @token.val 
		elsif (StartOf(1) || nil == 'URL') then
			while (StartOf(2) || nil == 'URL')
				case (@t.kind)
				when 3 then

					Get()
				when 2 then

					Get()
				when 1 then

					Get()
				when 28 then

					Get()
				when 20 then

					Get()
				when 25 then

					Get()
				when 14 then

					Get()
				end
				u << @token.val 
			end
		else Error(32)
end
		Expect(22)
		u << ")"; 
		return u
	end

	private; def Number()
		begin_pos = @scanner.pos
		n = "" 
		if (@t.kind==26) then
			Get()
			n << @token.val 
		end
		if (@t.kind==14) then
			Get()
			n << "0." 
			Expect(2)
			n << @token.val 
		elsif (@t.kind==2) then
			Get()
			n << @token.val 
			if (@t.kind==14) then
				Get()
				n << "." 
				Expect(2)
				n << @token.val 
			end
		else Error(33)
end
		if (@t.kind==1 || @t.kind==30) then
			if (@t.kind==1) then
				Get()
			else
				Get()
			end
			n << @token.val 
		end
		return n
	end

	private; def Color()
		begin_pos = @scanner.pos
		Expect(13)
		s = "#" 
		while (@t.kind==1 || @t.kind==2)
			if (@t.kind==2) then
				Get()
			else
				Get()
			end
			s << @token.val 
		end
		return s
	end

	private; def Operator()
		begin_pos = @scanner.pos
		if (@t.kind==28) then
			Get()
			while (@t.kind==6)
				Get()
			end
			o = []; 
		elsif (@t.kind==9) then
			Get()
			while (@t.kind==6)
				Get()
			end
			o = []; 
		else Error(34)
end
		return o
	end

	private; def Expr()
		begin_pos = @scanner.pos
		value = [[]]; 
		v = Value()
		value.last << v; 
		while (StartOf(3) || nil == 'Expr')
			if (@t.kind==9 || @t.kind==28) then
				o = Operator()
				value << o; 
			end
			v = Value()
			value.last << v; 
		end
		if (@t.kind==27) then
			Get()
			value.last << [@token.val]; #HACK!!! 
		end
		while (@t.kind==6)
			Get()
		end
		return value
	end

	private; def RuleSet()
		begin_pos = @scanner.pos
		decls = {} 
		sel = FullSelector()
		selectors = sel 
		Expect(4)
		while (@t.kind==6)
			Get()
		end
		if (StartOf(4) || nil == 'RuleSet') then
			sdecls = Declarations()
			(specs, subs) = sdecls; 
											selectors.each{|selector|
  												decls[selector] ||= [];
  												decls[selector].concat(specs); 
      											subs.each{|decl_set|
      												decl_set.each{|(sub_selector, sub_rule)|
	      												full_selector = selector.clone.concat(sub_selector)
		  												decls[full_selector] ||= [];
		  												decls[full_selector].concat(sub_rule);
		  											} 
      											}
  											}
  											
  										
		end
		Expect(5)
		while (@t.kind==6)
			Get()
		end
		return decls
	end

	private; def Declaration()
		begin_pos = @scanner.pos
		decl = [] 
		if (@t.kind==12) then
			Get()
			key = [@token.val]; #IE6 Hack
			Expect(1)
			key << @token.val; 
		elsif (@t.kind==25) then
			Get()
			key = [@token.val]; #IE7 Hack
			Expect(1)
			key << @token.val; 
		elsif (@t.kind==26) then
			Get()
			key = [@token.val]; #-moz rules
			Expect(1)
			key << @token.val; 
		elsif (@t.kind==1) then
			Get()
			key = [@token.val]; 
		else Error(35)
end
		while (@t.kind==6)
			Get()
		end
		Expect(20)
		decl << key; 
		while (@t.kind==6)
			Get()
		end
		value = Expr()
		decl << value; 
		while (@t.kind==6)
			Get()
		end
		return decl
	end

	private; def Value()
		begin_pos = @scanner.pos
		value = []; 
		case (@t.kind)
		when 13 then

			s = Color()
			value << s; 
		when 2, 14, 26 then

			s = Number()
			value << s; 
		when 29 then

			u = URL()
			value << u; 
		when 7 then

			Get()
			value << @token.val; 
		when 3 then

			Get()
			value << @token.val; 
		when 1 then

			f = Function()
			value << f 
		else
  Error(36)
		end
		while (@t.kind==6)
			Get()
		end
		return value
	end

	private; def Function()
		begin_pos = @scanner.pos
		f = [] 
		Expect(1)
		f << @token.val; 
		if (@t.kind==20) then
			Get()
			f << ":"; 
			Expect(1)
			f << @token.val; 
		end
		while (@t.kind==14)
			Get()
			f << "."; 
			Expect(1)
			f << @token.val; 
		end
		if (@t.kind==21) then
			Get()
			f << @token.val; 
			if (@t.kind==1) then
				Get()
				f << @token.val; 
				Expect(16)
				f << "="; 
				v = Value()
				f << v; 
				while (@t.kind==9)
					Get()
					f << ","; 
					Expect(6)
					while (@t.kind==6)
						Get()
					end
					Expect(1)
					f << @token.val; 
					Expect(16)
					f << "="; 
					v = Value()
					f << v; 
				end
			end
			Expect(22)
			f << @token.val; 
		end
		return f
	end

	private; def Pseudo()
		begin_pos = @scanner.pos
		Expect(20)
		Expect(1)
		p = ":" + @token.val 
		if (@t.kind==21) then
			Get()
			while (@t.kind==6)
				Get()
			end
			if (@t.kind==1) then
				Get()
			end
			while (@t.kind==6)
				Get()
			end
			Expect(22)
			raise "Not implemented yet." 
		end
		return p
	end

	private; def Attrib()
		begin_pos = @scanner.pos
		Expect(15)
		a = "[" 
		while (@t.kind==6)
			Get()
		end
		Expect(1)
		a += @token.val 
		while (@t.kind==6)
			Get()
		end
		if (@t.kind==16 || @t.kind==17 || @t.kind==18) then
			if (@t.kind==16) then
				Get()
			elsif (@t.kind==17) then
				Get()
			else
				Get()
			end
			a += @token.val 
			while (@t.kind==6)
				Get()
			end
			if (@t.kind==1) then
				Get()
			elsif (@t.kind==7) then
				Get()
			else Error(37)
end
			a += @token.val 
			while (@t.kind==6)
				Get()
			end
		end
		Expect(19)
		a += "]"; 
		return a
	end

	private; def Class()
		begin_pos = @scanner.pos
		Expect(14)
		Expect(1)
		c = ".#{@token.val}" 
		return c
	end

	private; def Hash()
		begin_pos = @scanner.pos
		Expect(13)
		Expect(1)
		h = "##{@token.val}" 
		return h
	end

	private; def SimpleSelector()
		begin_pos = @scanner.pos
		sel = []; 
		if (@t.kind==1 || @t.kind==12) then
			if (@t.kind==1) then
				Get()
			else
				Get()
			end
			sel << @token.val.downcase;  
			while (StartOf(5) || nil == 'SimpleSelector')
				if (@t.kind==13) then
					s = Hash()
				elsif (@t.kind==14) then
					s = Class()
				elsif (@t.kind==15) then
					s = Attrib()
				else
					s = Pseudo()
				end
				sel << s 
			end
		elsif (StartOf(5) || nil == 'SimpleSelector') then
			if (@t.kind==13) then
				s = Hash()
			elsif (@t.kind==14) then
				s = Class()
			elsif (@t.kind==15) then
				s = Attrib()
			else
				s = Pseudo()
			end
			sel << s 
			while (StartOf(5) || nil == 'SimpleSelector')
				if (@t.kind==13) then
					s = Hash()
				elsif (@t.kind==14) then
					s = Class()
				elsif (@t.kind==15) then
					s = Attrib()
				else
					s = Pseudo()
				end
				sel << s 
			end
		else Error(38)
end
		while (@t.kind==6)
			Get()
		end
		return sel
	end

	private; def NospaceCombinator()
		begin_pos = @scanner.pos
		if (@t.kind==10) then
			Get()
		elsif (@t.kind==11) then
			Get()
		else Error(39)
end
		comb = @token.val 
		while (@t.kind==6)
			Get()
		end
		return comb
	end

	private; def Selector()
		begin_pos = @scanner.pos
		selector = []; 
		sel = SimpleSelector()
		selector << sel; 
		if (StartOf(6) || nil == 'Selector') then
			if (@t.kind==10 || @t.kind==11) then
				comb = NospaceCombinator()
				selector << comb; 
				sel = Selector()
				selector << sel; 
			else
				sel = Selector()
				selector << " "; selector << sel; 
			end
		end
		return selector
	end

	private; def Declarations()
		begin_pos = @scanner.pos
		decls = []; sub_rules = []; 
		if (StartOf(7) || nil == 'Declarations') then
			decl = Declaration()
			decls << decl 
			while (@t.kind==23)
				Get()
				while (@t.kind==6)
					Get()
				end
				if (StartOf(4) || nil == 'Declarations') then
					others = Declarations()
					decls.concat(others[0]); sub_rules.concat(others[1]) 
				end
			end
		elsif (@t.kind==24) then
			Get()
			if (@t.kind==6) then
				Get()
				while (@t.kind==6)
					Get()
				end
				rs = RuleSet()
				rs.each_pair{|selector, rules|
	    										selector.unshift(" ")
	    									}
	    									sub_rules << rs 
	    								
			elsif (StartOf(8) || nil == 'Declarations') then
				rs = RuleSet()
				sub_rules << rs 
			else Error(40)
end
			while (StartOf(4) || nil == 'Declarations')
				others = Declarations()
				decls.concat(others[0]); sub_rules.concat(others[1]) 
			end
		else Error(41)
end
		all = [decls, sub_rules] 
		return all
	end

	private; def FullSelector()
		begin_pos = @scanner.pos
		selectors = []; 
		s = Selector()
		selectors << s; 
		while (@t.kind==9)
			Get()
			while (@t.kind==6)
				Get()
			end
			s = Selector()
			selectors << s; 
		end
		return selectors
	end

	private; def CSS()
		begin_pos = @scanner.pos
		decls = {};     
		while (StartOf(9) || nil == 'CSS')
			while (@t.kind==6)
				Get()
			end
			sel = FullSelector()
			selectors = sel 
			Expect(4)
			while (@t.kind==6)
				Get()
			end
			if (StartOf(4) || nil == 'CSS') then
				specs = Declarations()
				rules, sub_decls = specs;
												selectors.each{|selector|
	  												decls[selector] ||= [];
	  												decls[selector].concat(rules); 
	      											sub_decls.each{|hash|
	      												hash.each_pair{|sub_selector, sub_rule|
		      												full_selector = selector.clone.concat(sub_selector)
			  												decls[full_selector] ||= [];
			  												decls[full_selector].concat(sub_rule);
			  											} 
	      											}
	  											}
	  											
	  										
			end
			Expect(5)
			while (@t.kind==6)
				Get()
			end
		end
		Expect(0)
		return decls;	
	end



	public; def Parse()
		@t = Token.new();
		Get();
		CSS()

	end

	@@set = [
	[T,X,X,X, X,X,X,X, X,X,X,X, X,X,X,X, X,X,X,X, X,X,X,X, X,X,X,X, X,X,X,X, X],
	[X,T,T,T, X,X,X,X, X,X,X,X, X,X,T,X, X,X,X,X, T,X,T,X, X,T,X,X, T,X,X,X, X],
	[X,T,T,T, X,X,X,X, X,X,X,X, X,X,T,X, X,X,X,X, T,X,X,X, X,T,X,X, T,X,X,X, X],
	[X,T,T,T, X,X,X,T, X,T,X,X, X,T,T,X, X,X,X,X, X,X,X,X, X,X,T,X, T,T,X,X, X],
	[X,T,X,X, X,X,X,X, X,X,X,X, T,X,X,X, X,X,X,X, X,X,X,X, T,T,T,X, X,X,X,X, X],
	[X,X,X,X, X,X,X,X, X,X,X,X, X,T,T,T, X,X,X,X, T,X,X,X, X,X,X,X, X,X,X,X, X],
	[X,T,X,X, X,X,X,X, X,X,T,T, T,T,T,T, X,X,X,X, T,X,X,X, X,X,X,X, X,X,X,X, X],
	[X,T,X,X, X,X,X,X, X,X,X,X, T,X,X,X, X,X,X,X, X,X,X,X, X,T,T,X, X,X,X,X, X],
	[X,T,X,X, X,X,X,X, X,X,X,X, T,T,T,T, X,X,X,X, T,X,X,X, X,X,X,X, X,X,X,X, X],
	[X,T,X,X, X,X,T,X, X,X,X,X, T,T,T,T, X,X,X,X, T,X,X,X, X,X,X,X, X,X,X,X, X],
	]
end
end

