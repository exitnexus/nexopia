class LogFragments < PageHandler
	declare_handlers("log") {
		area :Internal;
		handle :GetRequest, :timeline_xml, 'timeline', 'xml';
		handle :GetRequest, :timeline_html, 'timeline', 'html';
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
					puts(%Q{#{' '*depth}<request path="#{htmlencode(item.realstr.this_req.to_s(false))}" time="#{item.time.to_f}">\n});
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
					puts(%Q{#{' '*depth}<sql db="#{htmlencode(item.realstr.db.to_s)}">\n});
					depth += 1;
					puts(%Q{#{' '*depth}<query>#{htmlencode(item.realstr.query.tr('^' << 32 << '-' << 128, ''))}</query>\n});
					if (item.realstr.backtrace)
						puts(%Q{#{' '*depth}<backtrace>});
						depth += 1;
						count = 5;
						item.realstr.backtrace.each {|line|
							if (!/^.+core\/lib\/sql.+\.rb:/.match(line) &&
								!/^.+core\/lib\/storable.+.rb:/.match(line) &&
								!/lazy\.rb/.match(line))
								count -= 1;
								puts(%Q{#{' '*depth}#{htmlencode(line)}\n});
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
							puts(%Q{#{' '*depth}<info name="#{field}">#{htmlencode(value.to_s)}</info>\n});
						}
						depth -= 1;
						puts(%Q{#{' '*depth}</explain>\n});
					end
					puts(%Q{#{' '*depth}<timing end-time="#{item.time.to_f + item.realstr.time}" total-time="#{item.realstr.time}" self-time="#{item.realstr.time}"/>\n});
					depth -= 1;
					puts(%Q{#{' '*depth}</sql>\n});
					child_times.push(item.realstr.time + child_times.pop);
				else
					puts(%Q{#{' '*depth}<log-item time="#{item.time.to_f}">#{htmlencode(item.realstr.to_s)}</log-item>\n});
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
	
	def in_ms(seconds)
		"#{(seconds * 1000).floor} ms"
	end
	
	def timeline_html()
		puts(%Q{<table><tr><td class="header" colspan=4 nowrap>Ruby Timeline</td></tr>});
		puts(%Q{<tr><td class="header">Start Time</td><td class="header">Self Time</td><td class="header">Total Time</td><td class="header">Info</td></tr>})
		depth = 0;
		time_stack = [];
		child_times = [0];
		now = Time.now;
		first_time = nil
		if (request.log[:timeline])
			request.log[:timeline].each {|item|
				# TODO: Move this behaviour out to where it belongs.
				if (!first_time)
					first_time = item.time
				end
				case item.realstr
				when PageRequest::LogStartRequest
					puts(%Q{<tr><td class="body">#{in_ms(item.time.to_f - first_time.to_f)}</td><td class="body">&nbsp;</td><td class="body">&nbsp;</td>})
					puts(%Q{<td class="body"><div style="margin-left:#{depth*3}px">#{htmlencode(item.realstr.this_req.to_s(false))}</div></td></tr>})
					time_stack.push(item.time);
					child_times.push(0);
					depth += 1;
				when PageRequest::LogEndRequest
					total_time = item.time - time_stack.pop;
					child_time = child_times.pop;
					depth -= 1;
					puts(%Q{<tr><td class="body">#{in_ms(item.time.to_f - first_time.to_f)}</td><td class="body">#{in_ms(total_time - child_time)}</td><td class="body">#{in_ms(total_time)}</td>})
					puts(%Q{<td class="body"><div style="margin-left:#{depth*3}px">Request Done</div></td></tr>})
					child_times.push(total_time + child_times.pop);
				when SqlBase::Log
					puts(%Q{<tr><td class="body">#{in_ms(item.time.to_f - first_time.to_f)}</td><td class="body">#{in_ms(item.realstr.time)}</td><td class="body">#{in_ms(item.realstr.time)}</td>})
					puts(%Q{<td class="body"><table style="margin-left:#{depth*3}px">})
					puts(%Q{<tr><td class="header">#{htmlencode(item.realstr.db.to_s)}</td></tr>})
					puts(%Q{<tr><td class="body">#{htmlencode(item.realstr.query.tr('^' << 32 << '-' << 128, ''))}</td></tr>})
					body_type = ['body2', 'body']
					if (item.realstr.backtrace)
						first_not_sql = item.realstr.backtrace.first
						item.realstr.backtrace.each {|line|
							if (!%r{^\./core/lib/sql}.match(line))
								first_not_sql = line
							end
						}
						puts(%Q{<tr><td class="#{body_type.first}">Called From: #{htmlencode(first_not_sql)}</td></tr>})
						body_type.reverse!
					end
					if (explain = item.realstr.explain)
						puts(%Q{<tr><td class="#{body_type.first}">})
						explain_items = ['id', 'select_type', 'table', 'type', 'possible_keys', 'key', 'key_len', 'ref', 'rows', 'Extra']
						puts(%Q{<table><tr>})
						explain_items.each {|i|
							puts(%Q{<td class="header">#{i}</td>})
						}
						puts(%Q{</tr><tr>})
						explain_items.each {|i|
							puts(%Q{<td class="#{body_type.first}">#{htmlencode(explain[i].to_s)}</td>})
						}
						puts(%Q{</tr></table>})
						puts(%Q{</td></tr>})
					end
					puts(%Q{</td></tr></table></td></tr>})
					child_times.push(item.realstr.time + child_times.pop);
				else
					puts(%Q{<tr><td class="body">#{in_ms(item.time.to_f - first_time.to_f)}</td><td class="body">&nbsp;</td><td class="body">&nbsp;</td>})
					puts(%Q{<td class="body"><div style="margin-left:#{depth*3}px">#{htmlencode(item.realstr.to_s)}</div></td></tr>})
				end
			}
			# make sure we've backed out all the <request> tags
			while (depth > 0)
				total_time = now - time_stack.pop;
				child_time = child_times.pop;
				depth -= 1;
				puts(%Q{<tr><td class="body">#{in_ms(now.to_f - first_time.to_f)}</td><td class="body">#{in_ms(total_time - child_time)}</td><td class="body">#{in_ms(total_time)}</td>})
				puts(%Q{<td class="body"><div style="margin-left:#{depth*3}px">Request Done</div></td></tr>})
				child_times.push(total_time + child_times.pop);
			end
		end
		puts(%Q{</table>});
	end
end
