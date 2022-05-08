class LogFragments < PageHandler
	declare_handlers("log") {
		area :Internal;
		handle :GetRequest, :timeline_xml, 'timeline', 'xml';
	}

	def timeline_xml()
		puts(%Q{<timeline xmlns="http://www.nexopia.com/dev/timeline">});
		depth = 0;
		time_stack = [];
		child_times = [0];
		now = Time.now;
		if (request.log[:timeline])
			request.log[:timeline].each {|item|
				# TODO: Move this behaviour out to where it belongs.
				case item.realstr
				when PageRequest::LogStartRequest
					puts(%Q{#{' '*depth}<request path="#{CGI::escapeHTML(item.realstr.this_req.to_s(false))}" time="#{item.time.to_f}">\n});
					time_stack.push(item.time);
					child_times.push(0);
					depth += 1;
				when PageRequest::LogEndRequest
					total_time = item.time - time_stack.pop;
					child_time = child_times.pop;
					puts(%Q{#{' '*depth}<timing end-time="#{item.time.to_f}" total-time="#{total_time}" self-time="#{total_time - child_time}" children-time="#{child_time}"/>\n});
					depth -= 1;
					puts(%Q{#{' '*depth}</request>\n});
					child_times.push(total_time + child_times.pop);
				when SqlBase::Log
					puts(%Q{#{' '*depth}<sql db="#{CGI::escapeHTML(item.realstr.db.to_s)}">\n});
					depth += 1;
					puts(%Q{#{' '*depth}<query>#{CGI::escapeHTML(item.realstr.query.tr('^' << 32 << '-' << 128, ''))}</query>\n});
					if (item.realstr.backtrace)
						puts(%Q{#{' '*depth}<backtrace>});
						depth += 1;
						count = 5;
						item.realstr.backtrace.each {|line|
							if (!/^.+core\/lib\/sql.+\.rb:/.match(line) &&
								!/^.+core\/lib\/storable.+.rb:/.match(line) &&
								!/lazy\.rb/.match(line))
								count -= 1;
								puts(%Q{#{' '*depth}#{CGI::escapeHTML(line)}\n});
							end
							if (count <= 0)
								break;
							end
						}
						depth -= 1;
						puts(%Q{#{' '*depth}</backtrace>\n});
					end
					if (explain = item.realstr.explain)
						puts(%Q{#{' '*depth}<explain>\n});
						depth += 1;
						explain.each {|field, value|
							puts(%Q{#{' '*depth}<info name="#{field}">#{CGI::escapeHTML(value.to_s)}</info>\n});
						}
						depth -= 1;
						puts(%Q{#{' '*depth}</explain>\n});
					end
					puts(%Q{#{' '*depth}<timing end-time="#{item.time.to_f + item.realstr.time}" total-time="#{item.realstr.time}" self-time="#{item.realstr.time}"/>\n});
					depth -= 1;
					puts(%Q{#{' '*depth}</sql>\n});
					child_times.push(item.realstr.time + child_times.pop);
				else
					puts(%Q{#{' '*depth}<log-item time="#{item.time.to_f}">#{CGI::escapeHTML(item.realstr)}</log-item>\n});
				end
			}
			# make sure we've backed out all the <request> tags
			while (depth > 0)
				total_time = now - time_stack.pop;
				child_time = child_times.pop;
				puts(%Q{#{' '*depth}<timing finished="no" end-time="#{now.to_f}" total-time="#{total_time}" self-time="#{total_time - child_time}" children-time="#{child_time}"/>\n});
				depth -= 1;
				puts(%Q{#{' '*depth}</request>\n});
				child_times.push(total_time + child_times.pop);
			end
		end
		puts(%Q{</timeline>});
	end
end
