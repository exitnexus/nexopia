if false
depends_on "dumpstructsql"
SEPERATOR = "\n--------------------------------------------------------\n"
TESTDB = 'generatedtest'

tables = []
File.open("#{$site.config.svn_base_dir}/schema/struct.sql", "r") {|f|
	f.each(SEPERATOR) {|line|
		line.gsub!(SEPERATOR, '')
		dbname = "unnamed_db"
		unless (Regexp.new("-- #{TESTDB}.") =~ line)
			dbname = line.match(/-- (.*?)\./)[1];
			line.gsub!(/CREATE TABLE \`/, "CREATE TABLE \`#{dbname}_")
			tables << line
		end
	}
}

#Drops all the old tables
result = $site.dbs[:generatedtestdb].query("SHOW TABLES")
result.each {|row|
	row.each_pair {|junk, table_name|
		$site.dbs[:generatedtestdb].query("DROP TABLE `#{table_name}`")
	}
}

#Create new table
tables.each {|table|
	begin
		$site.dbs[:generatedtestdb].query(table.gsub("DEFAULT CHARSET=latin1", ""))
	rescue SqlBase::QueryError
		$log.info $!
	end
}
end
