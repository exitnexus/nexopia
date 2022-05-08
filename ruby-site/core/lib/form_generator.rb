require 'stringio';
module FormGenerator
	def make_form(id=nil)
		names = self.instance_variables.map { |var|
			if (var[1,var.length] =~ /__.+__/)
				next;
			else
				name = var[1,var.length];
			end
		}
		names -= [nil]
		#names.html_dump;
		form = StringIO.new;
		form.puts('<table><form>');
		names.each {|name|
			value = self.send(:"#{name}");
			form.puts("<tr><td>#{name}</td><td><input type=\"text\" name=\"#{self.class}_#{name}#{id}\" value=\"#{value}\"/></td></tr>")
		}
		form.puts("<tr><td></td><td><input type=\"submit\" value=\"Send\"");
		form.puts('</form></table>');
		puts form.string;
	end
end
