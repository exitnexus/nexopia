module RubyProf
    class GraphHtmlPrinter
 	  def template
'
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">
<html>
<head>
  <style media="all" type="text/css">
    table {
	    border-collapse: collapse;
	    border: 1px solid #CCC;
	    font-family: Verdana, Arial, Helvetica, sans-serif;
	    font-size: 9pt;
	    line-height: normal;
    }

    th {
	    text-align: center;
	    border-top: 1px solid #FB7A31;
	    border-bottom: 1px solid #FB7A31;
	    background: #FFC;
	    padding: 0.3em;
	    border-left: 1px solid silver;
    }

		tr.break td {
		  border: 0;
	    border-top: 1px solid #FB7A31;
			padding: 0;
			margin: 0;
		}

    tr.method td {
			font-weight: bold;
    }

    td {
	    padding: 0.3em;
    }

    td:first-child {
	    width: 190px;
	    }

    td {
	    border-left: 1px solid #CCC;
	    text-align: center;
    }	
  </style>
	</head>
	<body>
		<h1>Profile Report</h1>
		<!-- Threads Table -->
		<table>
			<tr>
				<th>Thread ID</th>
				<th>Total Time</th>
			</tr>
			<% for thread_id, methods in @result.threads %>
			<tr>
				<td><a href="#<%= thread_id %>"><%= thread_id %></a></td>
				<td><%= @result.toplevel(thread_id).total_time*1000 %></td>
			</tr>
			<% end %>
		</table>

		<!-- Methods Tables -->
		<% for thread_id, methods in @result.threads %>
			<h2><a name="<%= thread_id %>">Thread <%= thread_id %></a></h2>

			<table>
				<tr>
					<th><%= sprintf("%#{PERCENTAGE_WIDTH}s", "%Total") %></th>
					<th><%= sprintf("%#{PERCENTAGE_WIDTH}s", "%Self") %></th>
					<th><%= sprintf("%#{TIME_WIDTH}s", "Total") %></th>
					<th><%= sprintf("%#{TIME_WIDTH}s", "Self") %></th>
					<th><%= sprintf("%#{TIME_WIDTH+2}s", "Children") %></th>
					<th><%= sprintf("%#{CALL_WIDTH}s", "Calls") %></th>
					<th>Name</th>
				</tr>

				<% methods.sort.reverse.each do |pair| %>
	        <% name = pair[0] %>
  	      <% method = pair[1] %>
					<% method_total_percent = self.total_percent(method) %>
					<% next if method_total_percent < @min_percent %>
					<% method_self_percent = self.self_percent(method) %>
					
						<!-- Parents -->
						<% for name, call_info in method.parents %> 
							<tr>
								<td>&nbsp;</td>
								<td>&nbsp;</td>
								<td><%= sprintf("%#{TIME_WIDTH}.2f", call_info.total_time*1000.0) %></td>
								<td><%= sprintf("%#{TIME_WIDTH}.2f", call_info.self_time*1000.0) %></td>
								<td><%= sprintf("%#{TIME_WIDTH}.2f", call_info.children_time*1000.0) %></td>
								<% called = "#{call_info.called}/#{method.called}" %>
								<td><%= sprintf("%#{CALL_WIDTH}s", called) %></td>
								<td><%= create_link(thread_id, name) %></td>
							</tr>
						<% end %>

						<tr class="method">
							<td><%= sprintf("%#{PERCENTAGE_WIDTH-1}.2f\%", method_total_percent) %></td>
							<td><%= sprintf("%#{PERCENTAGE_WIDTH-1}.2f\%", method_self_percent) %></td>
							<td><%= sprintf("%#{TIME_WIDTH}.2f", method.total_time*1000.0) %></td>
							<td><%= sprintf("%#{TIME_WIDTH}.2f", method.self_time*1000.0) %></td>
							<td><%= sprintf("%#{TIME_WIDTH}.2f", method.children_time*1000.0) %></td>
							<td><%= sprintf("%#{CALL_WIDTH}i", method.called) %></td>
							<td><a name="<%= link_name(thread_id, method.name) %>"><%= method.name %></a></td>
						</tr>

						<!-- Children -->
						<% for name, call_info in method.children %> 
							<% methods = @result.threads[thread_id] %>
							<% child = methods[name] %>

							<tr>
								<td>&nbsp;</td>
								<td>&nbsp;</td>
								<td><%= sprintf("%#{TIME_WIDTH}.2f", call_info.total_time*1000.0) %></td>
								<td><%= sprintf("%#{TIME_WIDTH}.2f", call_info.self_time*1000.0) %></td>
								<td><%= sprintf("%#{TIME_WIDTH}.2f", call_info.children_time*1000.0) %></td>
								<% called = "#{call_info.called}/#{child.called}" %>
								<td><%= sprintf("%#{CALL_WIDTH}s", called) %></td>
								<td><%= create_link(thread_id, name) %></td>
							</tr>
						<% end %>
						<!-- Create divider row -->
						<tr class="break"><td colspan="7"></td></tr>
				<% end %>
			</table>
		<% end %>
	</body>
</html>'
		end
	end
end
module Devutils
	class ProfileHandler < PageHandler
		declare_handlers("webrequest") {
			area :Internal

			access_level :Any
			handle :GetRequest, :profile_dispatcher, input(String), "profile-page", remain
		}

		# profile-page runs a subrequest and outputs profiler information about it.
		# Requires that you install the ruby-prof gem.
		def profile_dispatcher(host, remain)
			require 'ruby-prof'
			
 			RubyProf.clock_mode = RubyProf::WALL_TIME			

			# Profile the code
			result = RubyProf.profile do
				subrequest(StringIO.new(), request.method, "/webrequest/#{host}/#{remain.join '/'}",
						   params.to_hash, :Internal);
			end

			# Print a graph profile to text
			printer = RubyProf::GraphHtmlPrinter.new(result)
			request.reply.send_headers(); # not sure why I have to do this?
			printer.print($stdout, 10)
		end
	end
end
