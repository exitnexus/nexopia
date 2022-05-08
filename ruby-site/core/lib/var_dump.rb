require 'stringio';
require 'core/lib/lazy'

#Additional and overridden methods for the root Object
class Object
	def var_get(depth = 0, object_list = Array.new)
		return if (depth > 100)
		out = "";
		#the object's to_s string followed by <id: OBJECT_ID, class: CLASS>
		out << to_s+" <id: "+(__id__).to_s()+", class: "+self.class.to_s()+">";
		tabs = "\t" * depth;
		#check for recursion, object list will contain the object if it is a parent of itself
		if (!object_list.member?(self))
			#add self to the object list so that if it is a child of itself it wont print again
			object_list << self;
			#only list instance variables if there are any
			if (!instance_variables.empty?)
				out << " (\n";
				#var_dump all of the instance variables
				instance_variables().sort.each { |var|
					if (evaluated?(var))
						out << "#{tabs}\t#{var} = ";
						if (evaluated?(instance_variable_get(var)))
							out << (instance_variable_get(var).var_get(depth+1)).to_s;
						else
							out << "***PROMISED***\n";
						end
					end
				}
				out << "#{tabs})\n";
			else
				out << "\n";
			end
			#remove ourself from the object list
			object_list.pop();
		else
			out << "\n#{tabs}\t***RECURSION***\n";
		end
		return out;
	end

	#print_r style output var_dumps for any object, pass in an IO object if
	#you don't want it to use $stdout
	def var_dump(out = $>)
		out.print(var_get());
	end
	alias var_print var_dump;

	def html_get(out = $>)
		sout = var_get();
		output = htmlencode(sout);

		output.gsub!(/(=\s*&gt;|=)\s?(.*) (&lt;id: -?\d+, class: [A-Za-z0-9:]+&gt;)/) {
			symbol = $1;
			val = $2;
			stats = $3;
			case stats
			when /Boolean|FalseClass|TrueClass/
				"#{symbol} <font color=\"#FF6600\"><strong>#{val}</strong></font> #{stats}";
			when /Symbol|Enum/
				"#{symbol} <font color=\"#FF6600\"><em>#{val}</em></font> #{stats}";
			when /Fixnum/
				"#{symbol} <font color=\"#CC0099\">#{val}</font> #{stats}";
			when /String/
				"#{symbol} <font color=\"#FF6600\">\"#{val}\"</font> #{stats}";
			else
				"#{symbol} #{val} #{stats}"
			end
		}
		output.gsub!(/(&lt;id: -?\d+, class: [A-Za-z0-9:]+&gt;)/, "<font color=\"#0000DD\">\\1</font>")  # <id: .., class: ..>
		output.gsub!(/(#&lt;[A-Za-z0-9:]+&gt;)/, "<font color=\"#00DD00\">\\1</font>"); # #<...>

		#output.gsub!(/= (\w+) (&lt;id: -?\d+, class: (Enum|Boolean|Symbol)&gt);/, "<em>= \\1 \\2</em>");
		output.gsub!(/ nil /, " <strong><font color=\"#BB0000\">nil</font></strong> ");
		output.gsub!(/(\*\*\*PROMISED\*\*\*)/, "<strong><font color=\"goldenrod\">\\1</font></strong>");

		output.gsub!(/\t/, "&#160;&#160;&#160;&#160;&#160;&#160;&#160;&#160;");
		output.gsub!(/\n/, "<br/>");
		output = "<div xmlns=\"http://www.w3.org/1999/xhtml\" xmlns:t=\"http://www.nexopia.com/dev/template\">" + output + "</div>"

		return output;
	end

	def html_dump(out = nil)
		unless out
			puts(html_get());
		else
			out.puts html_get
		end
	end
	alias html_print html_dump;
end



class Array
	#In addition to an object var_dump this var_dumps all of the items in the array by index
	def var_get(depth = 0, object_list = Array.new)
		out = StringIO.new()
		out.print " <id: "+(__id__).to_s()+", class: "+self.class.to_s()+">";
		if (!object_list.member?(self))
			object_list << self;
			out.print " Contains: "
			var_dump_members(out, depth, object_list);
			object_list.pop();
		end
		return out.string();
	end

	#This function var_dumps all objects in the array by index
	def var_dump_members(out = $stdout, depth = 0, object_list = Array.new)
		tabs = "\t" * depth;
		if (empty?)
			out.puts "[]";
		else
			out.puts "("
			each_with_index { |element,i|
				out.print "#{tabs}\t[#{i}] => ";
				if (evaluated?(element))
					out.print(element.var_get(depth+1, object_list));
				else
					out.puts("***PROMISED***");
				end
			}
			out.puts "#{tabs})";
		end
	end
end

class Hash
	#In addition to an object var_dump this var_dumps all of the items in the hash by their key
	def var_get(depth = 0, object_list = Array.new)
		out = StringIO.new();
		out.print " <id: "+(__id__).to_s()+", class: "+self.class.to_s()+">";
		if (!object_list.member?(self))
			object_list << self;
			out.print " Contains: "
			var_dump_members(out, depth, object_list);
			object_list.pop();
		end
		return out.string();
	end

	#This function var_dumps all objects in the hash by key
	def var_dump_members(out = $stdout, depth = 0, object_list = Array.new)
		tabs = "\t" * depth;
		if (empty?)
			out.puts "[]";
		else
			out.puts "("
			each_pair { |key, element|
				out.print "#{tabs}\t[#{key}] => ";
				if (evaluated?(element))
					out.print(element.var_get(depth+1, object_list));
				else
					out.puts("***PROMISED***");
				end
			}
			out.puts "#{tabs})";
		end
	end
end

class Enum
	def var_get(depth = 0, object_list = Array.new)
		return self.to_s + " " + @symbols.inspect + " <id: "+(__id__).to_s()+", class: "+self.class.to_s()+">\n";
	end
end

class NilClass
	def var_get(depth = 0, object_list = Array.new)
		return "nil <id: "+(__id__).to_s()+", class: "+self.class.to_s()+">\n";
	end
end
