require 'erb';
require 'stringio';
include ERB::Util;

#Additional and overridden methods for the root Object
class Object
	#print_r style output var_dumps for any object, pass in an IO object if
	#you don't want it to use $stdout
	def var_dump(out = $stdout, depth = 0, object_list = Array.new)
		#the object's to_s string followed by <id: OBJECT_ID, class: CLASS>
		out.print to_s+" <id: "+(__id__).to_s()+", class: "+self.class.to_s()+">";
		tabs = "";
		depth.times { tabs += "\t" }
		#check for recursion, object list will contain the object if it is a parent of itself
		if (!object_list.member?(self))
			#add self to the object list so that if it is a child of itself it wont print again
			object_list << self;
			#only list instance variables if there are any
			if (!instance_variables.empty?)
				out.puts " (";
				#var_dump all of the instance variables
				instance_variables().each { |var|
					out.print "#{tabs}\t#{var} = ";
					instance_variable_get(var).var_dump(out, depth+1);
				}
				out.puts "#{tabs})";
			else
				out.print "\n";
			end
			#remove ourself from the object list
			object_list.pop();
		else
			out.puts "\n#{tabs}\t***RECURSION***";
		end
	end
	
	def html_dump(out = $stdout)
		sout = StringIO.new;
		var_dump(sout);
		output = html_escape(sout.string);
		output.gsub!(/\t/, "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;");
		output.gsub!(/\n/, "<br>");
		out.puts(output);
	end
end



class Array
	#In addition to an object var_dump this var_dumps all of the items in the array by index
	def var_dump(out = $stdout, depth = 0, object_list = Array.new)
		super(out, depth, object_list);
		tabs = "";
		depth.times { tabs += "\t" }
		if (!object_list.member?(self))
			object_list << self;
			out.print "#{tabs}  Contains: "
			var_dump_members(out, depth, object_list);
			object_list.pop();
		end
	end
	
	#This function var_dumps all objects in the array by index
	def var_dump_members(out = $stdout, depth = 0, object_list = Array.new)
		tabs = "";
		depth.times { tabs += "\t" }
		if (empty?)
			out.puts "[]";
		else
			out.puts "("
			each_with_index { |element,i|
				out.print "#{tabs}\t[#{i}] => ";
				element.var_dump(out, depth+1, object_list);
			}
			out.puts "#{tabs})";
		end
	end
end

class Hash
	#In addition to an object var_dump this var_dumps all of the items in the hash by their key
	def var_dump(out = $stdout, depth = 0, object_list = Array.new)
		super(out, depth, object_list);
		tabs = "";
		depth.times { tabs += "\t" }
		if (!object_list.member?(self))
			object_list << self;
			out.print "#{tabs}  Contains: "
			var_dump_members(out, depth, object_list);
			object_list.pop();
		end
	end
	
	#This function var_dumps all objects in the hash by key
	def var_dump_members(out = $stdout, depth = 0, object_list = Array.new)
		tabs = "";
		depth.times { tabs += "\t" }
		if (empty?)
			out.puts "[]";
		else
			out.puts "("
			each_pair { |key, element|
				out.print "#{tabs}\t[#{key}] => ";
				element.var_dump(out, depth+1, object_list);
			}
			out.puts "#{tabs})";
		end
	end
end