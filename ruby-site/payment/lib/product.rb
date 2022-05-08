lib_require :Core, 'form_generator'
lib_require :Payment, 'product_input_choice'
require 'stringio'
class Product < Storable
	init_storable(:shopdb, 'products');
	attr_reader(:choices);
	
	def after_load
		case self.input
		when "mc" #multiple choice
			@choices = ProductInputChoice.find(:all, :promise => true, :conditions => ["productid = #", id]);
		end
	end
	
	def display()
		output = StringIO.new;
		output.puts("<strong>#{name}</strong><br>");
		case self.input 
		when "text"
			output.puts("#{inputname} <input type=\"text\" name=\"input_#{id}\">")
		when "mc"
			output.puts("#{inputname} <select name=\"input_#{id}\">");
			@choices.each {|choice| 
				output.puts "<option value=\"#{choice.id}\">#{choice.name}";
			}
			output.puts("</select>");
		end
		output.puts("Quantity <input type=\"text\" value=\"0\" name=\"quantity_#{id}\"><br><br>")
		puts output.string;
	end
	
	# Returns the numerical product subtype specified by the input.
	# Raises an exception on invalid input, be sure to catch this and respond appropriately.
	def process_input(input)
		if (self.validinput.empty?)
			return input.to_i;
		else 
			result = self.send(:"#{self.validinput}", input);
			if (result)
				return result;
			else
				raise SiteError, "Invalid input for #{validinput}: #{input}";
			end
		end
	end
	
	def get_uid(input)
		user = User.get_by_name(input);
		return user.userid if (user);
		return nil;
	end
end