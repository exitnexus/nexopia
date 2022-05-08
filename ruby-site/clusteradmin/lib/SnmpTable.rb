#!/usr/bin/ruby

require 'snmp'

class SnmpTable < SNMP::Manager
	@oids = []

	def fetch
		@values = []
		walk(@oids) do |r|
			if (respond_to? 'ignore?')
				if (ignore?(r))
					next
				end
			end

			v = {}
			r.each_index do |vidx|
				if (respond_to? @oids[vidx])
					translator = method("#{@oids[vidx]}")
					v[@oids[vidx]] = translator.call(r[vidx].value)
				else
					v[@oids[vidx]] = r[vidx].value
				end
			end
			@values.push(v)
		end
		@values
	end

	# not sure if I should even have this
	def to_html
		out = "<table border=1 cellpadding=1 cellspacing=1><tr>"
		@oids.each do |h|
			out += "<th><b>#{h}</b></th>"
		end
		out += "</tr>"
		
		fetch.each do |row|
			out += "<tr>"
			@oids.each do |col|
				out += "<td>#{row[col]}</td>"
			end
			out += "</tr>"
		end
		out += "</table>"
	end
end


