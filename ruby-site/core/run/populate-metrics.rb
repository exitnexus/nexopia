# Populate the metrics table with some sample data

for metricid in (1..100)
	for column in (1..2)
		from = Time.now.to_i - 100
		to = Time.now.to_i
		for date in (from..to)
			$site.dbs[:masterdb].query("INSERT INTO metrics (metricid, `column`, `date`, `value`) VALUES(?, ?, ?, ?)", metricid, column, date, 1+ rand(100000))
		end
	end
end
