# This file is generated. DO NOT MODIFY!


module CSS;


class Token
  attr_accessor :kind, :pos, :col, :line, :val

  def initialize
    @val = ""
    @kind = @pos = @col = @line = 0
  end

  def clone
    return Marshal.load(Marshal.dump(self))
  end

  def ==(o)
    ! o.nil? &&
      @kind == o.kind &&
      @pos == o.pos &&
      @col == o.col &&
      @line == o.line &&
      @val == o.val
  end

  def to_s
    "<Token@#{self.id}: \"#{@val}\" k=#{@kind}, p=#{@pos}, c=#{@col}, l=#{@line}>"
  end

end

class Buffer

  def initialize
    @buf = ""
    @bufLen = 0
    @pos = 0
  end
  
  # cls_attr_accessor :pos
  def pos
    @pos
  end
  
  def Fill(name)
    @buf = File.new(name).read
    @bufLen = @buf.size
  end
  
  def FillStr(str)
    @buf = str
    @bufLen = @buf.size
  end

  def Set(position)
    if (position < 0) then
      position = 0
    elsif (position >= @bufLen) then
      position = @bufLen
    end
    @pos = position
  end
  
  def read
    c = 0
    if (@pos < @bufLen) then
      c = @buf[@pos]
      @pos += 1
    else
      c = 65535				# FIX!!!
    end
    return c
  end
end

class BitSet

  attr_reader :size, :bits, :trueCount

  def initialize(size=128)
    @trueCount = 0
    @size = size
    @bits = Array.new(size, false)
  end

  def clone
    return Marshal.load(Marshal.dump(self))
  end

  def ==(o)
    return @size == o.size && @bits == o.bits
  end

  def to_s
    indexes = []
    @bits.each_with_index do |t,i|
      indexes << i if t
    end

    "{#{indexes.join(", ")}}"
  end

  def clear(i)
    @trueCount -= 1
    @bits[i] = false
  end 
  # Sets the bit specified by the index to false .

  def get(i)
    @bits[i]
  end 
  alias :[] :get
  # Returns the value of the bit with the specified index. 

  def set(i)
    @trueCount += 1
    @bits[i] = true
  end 
  # Sets the bit specified by the index to true .

  def and(s) 
    s.size.times do |i|
      self.clear(i) unless s[i] && self[i]
    end
  end
  # Performs a logical AND of this target bit set with the argument bit set. 

  def or(s)
    s.size.times do |i|
      self.set(i) if s[i]
    end
  end 
  # Performs a logical OR of this bit set with  

  def andNot(set)
    raise "something"
  end 
  # Clears all of the bits in this BitSet whose corresponding  bit is set in the specified BitSet .

  def xor(set)
    raise "something"
  end 
  # Performs a logical XOR of this bit set with the bit set   argument. 

  def length
    raise "something"
  end 
  # Returns the "logical size" of this BitSet : the index of  the highest set bit in the BitSet plus one. 

end

class Scanner

  attr :ignore, true;
  attr :buffer, true;
  attr :pos, true;
  attr :t, true;
  attr :buf, true;
  attr :state, true;
  
  EOF = 0					# TODO: verify... 
  CR = "\r"[0]
  NL = "\n"[0]					# FIX: this sucks
  EOL = NL
  MAXASCII = 255				# FIX: this is dumb
  MAXCHR = 65535				# FIX: not ruby compatible
  SPACE = ' '[0]

	private; @@nosym = 31; 
	private; @@start = [
 57,  0,  0,  0,  0,  0,  0,  0,  0,  6,  6,  0,  0,  6,  0,  0,
  0,  0,  0,  0,  0,  0,  0,  0,  0,  0,  0,  0,  0,  0,  0,  0,
  6, 38, 15, 23,  3, 53, 35, 16, 32, 33, 22, 20, 19, 37, 24, 48,
  2,  2,  2,  2,  2,  2,  2,  2,  2,  2, 31, 34,  0, 26, 21,  0,
  3,  1,  1,  1,  1,  1,  1,  1,  1,  1,  1,  1,  1,  1,  1,  1,
  1,  1,  1,  1,  1,  1,  1,  1,  1,  1,  1, 25,  0, 30,  0, 36,
  0,  1,  1,  1,  1,  1,  1,  1,  1,  1,  1,  1,  1,  1,  1,  1,
  1,  1,  1,  1,  1, 54,  1,  1,  1,  1,  1,  4, 28,  5,  0,  0,
  0]


  public 

  def err			# HACK: added because @err was accessed from Parser.rb
    @err
  end

  def setIgnore(n)
      @ignore.set(n);
  end
  
  def unsetIgnore(n)
      @ignore.clear(n);
  end

  def InitFromFile(file, e=ErrorStream.new)
    @buffer = Buffer.new();
    @buffer.Fill(file)
    InitMain(e);
  end
  
  def InitFromStr(str, e=ErrorStream.new)
    @buffer = Buffer.new();
    @buffer.FillStr(str)
    InitMain(e)
  end

  def InitMain(e)
    @err = nil					# error messages
    @t=Token.new			# current token
    @ch=nil			# current input character
    @pos=0			# position of current character
    @line=0			# line number of current character
    @lineStart=0			# start position of current line
    @oldEols=0			# >0: no. of EOL in a comment
    @ignore=BitSet.new(128)	# set of characters to be ignored by the scanner
		
    @err = e
    @pos = -1
    @line = 1
    @lineStart = 0
    @oldEols = 0
    self.NextCh
  end
	
  def NextCh
    if (@oldEols > 0) then
      @ch = EOL
      @oldEols -= 1
    else
      @ch = @buffer.read
      @pos += 1
      if (@ch==NL || @ch==CR) then
	@line += 1
	@lineStart = @pos + 1
      end
    end
    if (@ch > MAXASCII) then
      if (@ch == MAXCHR) then
	@ch = EOF
      else
	$stderr.puts("-- invalid character (#{@ch}) at line #{@line} col #{@pos - @lineStart}")
	@ch = SPACE
      end
    end
		@valCh = @ch;

  end
	
private; def Comment0()
	level = 1; line0 = @line; lineStart0 = @lineStart; startCh=nil
	NextCh()
	if ((@valCh == ?*)) then
		NextCh()
		loop do
			if ((@valCh == ?*)) then
				NextCh()
				if ((@valCh == ?/)) then
					level -= 1
					if (level==0) then ; oldEols=@line-line0; NextCh(); return true; end
					NextCh()
				end
			elsif ((@valCh == ?/)) then
				NextCh()
				if ((@valCh == ?*)) then
					level += 1; NextCh()
				end
			elsif (@ch==EOF) then; return false
			else NextCh()
			end
		end
	else
		if (@ch==EOL) then; @line -= 1; @lineStart = lineStart0; end
		@pos -= 2; @buffer.Set(@pos+1); NextCh()
	end
	return false
end

	
  def CheckLiteral
  	#$stderr.puts "Checking #{@t.val}"
		case (@t.val[0])
			when nil
		end

  end

  public; def Scan
    self.NextCh while @ignore.get(@ch)
		if ((@valCh == ?/) && Comment0() ) then ; return Scan(); end
    @t = Token.new
    @t.pos = @pos
    @t.col = @pos - @lineStart
    @t.line = @line 
    @t.kind = "FIX"
    @buf = ""
    @state = @@start[@ch] || 0;
    @apx = 0

    while true
		@buf += @ch.chr unless @ch < 0
		self.NextCh

		#$stderr.puts "state = #{@state}, ch = #{@ch} / '#{@ch.chr}'"
		#if (@state == 0)
		#when 0				# NextCh already done
		@@stateMap ||= MakeStateMap();
		if @@stateMap[@state].call(self) == true
			break
		end
    end
    #$stderr.puts "buf = '#{@buf}':#{@t.kind} state = #{@state} line=#{@t.line} col=#{@t.col} "
    @t.val = @buf.to_s

    raise "kind not set for Token" unless @t.kind and @t.kind != "FIX"

#    puts @t

    return @t
  end
  
  def MakeStateMap()
		@@stateMap ||= {
			0 => Proc.new{|sc|
				sc.t.kind = @@nosym
				true;
				}, 1 => Proc.new{|sc|
					sc.instance_eval{
					if (((@valCh == ?-) || @valCh.chr =~ /[\x30-\x39]/ || @valCh.chr =~ /[\x41-\x5a]/ || (@valCh == ?_) || @valCh.chr =~ /[\x61-\x7a]/)) then
					else
@t.kind = 1
				true
end
}
				}, 2 => Proc.new{|sc|
					sc.instance_eval{
					if ((@valCh.chr =~ /[\x30-\x39]/)) then
					else
@t.kind = 2
				true
end
}
				}, 3 => Proc.new{|sc|
					sc.instance_eval{
					if (((@valCh == ?%) || @valCh.chr =~ /[\x2d-\x2e]/ || @valCh.chr =~ /[\x30-\x39]/ || (@valCh == ?=) || @valCh.chr =~ /[\x41-\x5a]/ || (@valCh == ?_) || @valCh.chr =~ /[\x61-\x7a]/)) then
					else
@t.kind = 3
				true
end
}
				}, 4 => Proc.new{|sc|
					sc.instance_eval{
					@t.kind = 4
				true
}
				}, 5 => Proc.new{|sc|
					sc.instance_eval{
					@t.kind = 5
				true
}
				}, 6 => Proc.new{|sc|
					sc.instance_eval{
					if ((@valCh.chr =~ /[\x09-\x0a]/ || (@valCh == 13) || (@valCh == 32))) then
					else
@t.kind = 6
				true
end
}
				}, 7 => Proc.new{|sc|
					sc.instance_eval{
					if ((@valCh.chr =~ /[\x00-\x21]/ || @valCh.chr =~ /[\x23-\x7a]/ || (@valCh == ?|) || @valCh.chr =~ /[\x7e-\x7f]/)) then
					elsif ((@valCh == ?")) then
						@state = 9
					else
					@t.kind = @@nosym; true; end
}
				}, 8 => Proc.new{|sc|
					sc.instance_eval{
					if ((@valCh.chr =~ /[\x00-\x26]/ || @valCh.chr =~ /[\x28-\x7a]/ || (@valCh == ?|) || @valCh.chr =~ /[\x7e-\x7f]/)) then
					elsif ((@valCh == 39)) then
						@state = 9
					else
					@t.kind = @@nosym; true; end
}
				}, 9 => Proc.new{|sc|
					sc.instance_eval{
					@t.kind = 7
				true
}
				}, 10 => Proc.new{|sc|
					sc.instance_eval{
					if (((@valCh == 10) || (@valCh == 13))) then
						@state = 14
					elsif (((@valCh == 92))) then
						@state = 17
					else
					@t.kind = @@nosym; true; end
}
				}, 11 => Proc.new{|sc|
					sc.instance_eval{
					if ((@valCh == ?")) then
						@state = 10
					else
					@t.kind = @@nosym; true; end
}
				}, 12 => Proc.new{|sc|
					sc.instance_eval{
					if (((@valCh == 10) || (@valCh == 13))) then
						@state = 14
					elsif (((@valCh == 92))) then
						@state = 18
					else
					@t.kind = @@nosym; true; end
}
				}, 13 => Proc.new{|sc|
					sc.instance_eval{
					if ((@valCh == 39)) then
						@state = 12
					else
					@t.kind = @@nosym; true; end
}
				}, 14 => Proc.new{|sc|
					sc.instance_eval{
					@t.kind = 8
				true
}
				}, 15 => Proc.new{|sc|
					sc.instance_eval{
					if ((@valCh.chr =~ /[\x00-\x21]/ || @valCh.chr =~ /[\x23-\x7a]/ || (@valCh == ?|) || @valCh.chr =~ /[\x7e-\x7f]/)) then
						@state = 7
					elsif (((@valCh == 10) || (@valCh == 13))) then
						@state = 14
					elsif ((@valCh.chr =~ /[\x00-\x21]/ || @valCh.chr =~ /[\x23-\x5b]/ || @valCh.chr =~ /[\x5d-\x7a]/ || (@valCh == ?|) || @valCh.chr =~ /[\x7e-\x7f]/)) then
						@state = 10
					elsif (((@valCh == 92))) then
						@state = 17
					elsif ((@valCh == ?")) then
						@state = 9
					else
					@t.kind = @@nosym; true; end
}
				}, 16 => Proc.new{|sc|
					sc.instance_eval{
					if ((@valCh.chr =~ /[\x00-\x26]/ || @valCh.chr =~ /[\x28-\x7a]/ || (@valCh == ?|) || @valCh.chr =~ /[\x7e-\x7f]/)) then
						@state = 8
					elsif (((@valCh == 10) || (@valCh == 13))) then
						@state = 14
					elsif ((@valCh.chr =~ /[\x00-\x26]/ || @valCh.chr =~ /[\x28-\x5b]/ || @valCh.chr =~ /[\x5d-\x7a]/ || (@valCh == ?|) || @valCh.chr =~ /[\x7e-\x7f]/)) then
						@state = 12
					elsif (((@valCh == 92))) then
						@state = 18
					elsif ((@valCh == 39)) then
						@state = 9
					else
					@t.kind = @@nosym; true; end
}
				}, 17 => Proc.new{|sc|
					sc.instance_eval{
					if (((@valCh == 10) || (@valCh == 13))) then
						@state = 14
					elsif ((@valCh.chr =~ /[\x00-\x5b]/ || @valCh.chr =~ /[\x5d-\x7a]/ || (@valCh == ?|) || @valCh.chr =~ /[\x7e-\x7f]/)) then
						@state = 10
					else
					@t.kind = @@nosym; true; end
}
				}, 18 => Proc.new{|sc|
					sc.instance_eval{
					if (((@valCh == 10) || (@valCh == 13))) then
						@state = 14
					elsif ((@valCh.chr =~ /[\x00-\x5b]/ || @valCh.chr =~ /[\x5d-\x7a]/ || (@valCh == ?|) || @valCh.chr =~ /[\x7e-\x7f]/)) then
						@state = 12
					else
					@t.kind = @@nosym; true; end
}
				}, 19 => Proc.new{|sc|
					sc.instance_eval{
					@t.kind = 9
				true
}
				}, 20 => Proc.new{|sc|
					sc.instance_eval{
					@t.kind = 10
				true
}
				}, 21 => Proc.new{|sc|
					sc.instance_eval{
					@t.kind = 11
				true
}
				}, 22 => Proc.new{|sc|
					sc.instance_eval{
					@t.kind = 12
				true
}
				}, 23 => Proc.new{|sc|
					sc.instance_eval{
					@t.kind = 13
				true
}
				}, 24 => Proc.new{|sc|
					sc.instance_eval{
					@t.kind = 14
				true
}
				}, 25 => Proc.new{|sc|
					sc.instance_eval{
					@t.kind = 15
				true
}
				}, 26 => Proc.new{|sc|
					sc.instance_eval{
					if ((@valCh == ?~)) then
						@state = 27
					else
@t.kind = 16
				true
end
}
				}, 27 => Proc.new{|sc|
					sc.instance_eval{
					@t.kind = 17
				true
}
				}, 28 => Proc.new{|sc|
					sc.instance_eval{
					if ((@valCh == ?=)) then
						@state = 29
					else
					@t.kind = @@nosym; true; end
}
				}, 29 => Proc.new{|sc|
					sc.instance_eval{
					@t.kind = 18
				true
}
				}, 30 => Proc.new{|sc|
					sc.instance_eval{
					@t.kind = 19
				true
}
				}, 31 => Proc.new{|sc|
					sc.instance_eval{
					@t.kind = 20
				true
}
				}, 32 => Proc.new{|sc|
					sc.instance_eval{
					@t.kind = 21
				true
}
				}, 33 => Proc.new{|sc|
					sc.instance_eval{
					@t.kind = 22
				true
}
				}, 34 => Proc.new{|sc|
					sc.instance_eval{
					@t.kind = 23
				true
}
				}, 35 => Proc.new{|sc|
					sc.instance_eval{
					@t.kind = 24
				true
}
				}, 36 => Proc.new{|sc|
					sc.instance_eval{
					@t.kind = 25
				true
}
				}, 37 => Proc.new{|sc|
					sc.instance_eval{
					@t.kind = 26
				true
}
				}, 38 => Proc.new{|sc|
					sc.instance_eval{
					if ((@valCh == ?i)) then
						@state = 39
					else
					@t.kind = @@nosym; true; end
}
				}, 39 => Proc.new{|sc|
					sc.instance_eval{
					if ((@valCh == ?m)) then
						@state = 40
					else
					@t.kind = @@nosym; true; end
}
				}, 40 => Proc.new{|sc|
					sc.instance_eval{
					if ((@valCh == ?p)) then
						@state = 41
					else
					@t.kind = @@nosym; true; end
}
				}, 41 => Proc.new{|sc|
					sc.instance_eval{
					if ((@valCh == ?o)) then
						@state = 42
					else
					@t.kind = @@nosym; true; end
}
				}, 42 => Proc.new{|sc|
					sc.instance_eval{
					if ((@valCh == ?r)) then
						@state = 43
					else
					@t.kind = @@nosym; true; end
}
				}, 43 => Proc.new{|sc|
					sc.instance_eval{
					if ((@valCh == ?t)) then
						@state = 44
					else
					@t.kind = @@nosym; true; end
}
				}, 44 => Proc.new{|sc|
					sc.instance_eval{
					if ((@valCh == ?a)) then
						@state = 45
					else
					@t.kind = @@nosym; true; end
}
				}, 45 => Proc.new{|sc|
					sc.instance_eval{
					if ((@valCh == ?n)) then
						@state = 46
					else
					@t.kind = @@nosym; true; end
}
				}, 46 => Proc.new{|sc|
					sc.instance_eval{
					if ((@valCh == ?t)) then
						@state = 47
					else
					@t.kind = @@nosym; true; end
}
				}, 47 => Proc.new{|sc|
					sc.instance_eval{
					@t.kind = 27
				true
}
				}, 48 => Proc.new{|sc|
					sc.instance_eval{
					@t.kind = 28
				true
}
				}, 49 => Proc.new{|sc|
					sc.instance_eval{
					if ((@valCh == ?r)) then
						@state = 50
					else
					@t.kind = @@nosym; true; end
}
				}, 50 => Proc.new{|sc|
					sc.instance_eval{
					if ((@valCh == ?l)) then
						@state = 51
					else
					@t.kind = @@nosym; true; end
}
				}, 51 => Proc.new{|sc|
					sc.instance_eval{
					if ((@valCh == ?()) then
						@state = 52
					else
					@t.kind = @@nosym; true; end
}
				}, 52 => Proc.new{|sc|
					sc.instance_eval{
					@t.kind = 29
				true
}
				}, 53 => Proc.new{|sc|
					sc.instance_eval{
					@t.kind = 30
				true
}
				}, 54 => Proc.new{|sc|
					sc.instance_eval{
					if (((@valCh == ?-) || @valCh.chr =~ /[\x30-\x39]/ || @valCh.chr =~ /[\x41-\x5a]/ || (@valCh == ?_) || @valCh.chr =~ /[\x61-\x71]/ || @valCh.chr =~ /[\x73-\x7a]/)) then
						@state = 1
					elsif (((@valCh == ?r))) then
						@state = 55
					else
@t.kind = 1
				true
end
}
				}, 55 => Proc.new{|sc|
					sc.instance_eval{
					if (((@valCh == ?-) || @valCh.chr =~ /[\x30-\x39]/ || @valCh.chr =~ /[\x41-\x5a]/ || (@valCh == ?_) || @valCh.chr =~ /[\x61-\x6b]/ || @valCh.chr =~ /[\x6d-\x7a]/)) then
						@state = 1
					elsif (((@valCh == ?l))) then
						@state = 56
					else
@t.kind = 1
				true
end
}
				}, 56 => Proc.new{|sc|
					sc.instance_eval{
					if (((@valCh == ?-) || @valCh.chr =~ /[\x30-\x39]/ || @valCh.chr =~ /[\x41-\x5a]/ || (@valCh == ?_) || @valCh.chr =~ /[\x61-\x7a]/)) then
						@state = 1
					elsif ((@valCh == ?()) then
						@state = 52
					else
@t.kind = 1
				true
end
}
				}, 57 => Proc.new{|sc|
					sc.instance_eval{
					@t.kind = 0
					true }
					}

		}
  end
end
end
