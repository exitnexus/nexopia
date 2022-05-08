if false
#depends_on "svnupdate";

tables = {};

$site.dbs.each {|dbname,dbobj|
	dbobj.list_tables().each {|row|
		name = ($site.config.dumpstruct_include_dbname ? "#{dbname}.#{row['Name']}" : row['Name']);
		create_table = dbobj.get_split_dbs[0].query("SHOW CREATE TABLE `#{row['Name']}`").fetch['Create Table'];
		tables[name] = "-- #{name}\n#{create_table}";
	}
}

keys = tables.keys.sort;
lines = [];

keys.each {|key|
	lines.push(tables[key]);
}

lines.each {|line|
	line.gsub!(/AUTO_INCREMENT\=\d+ /, '')
}

File.open("#{$site.config.svn_base_dir}/schema/struct.sql", "w") {|f|
	f.print(lines.join("\n\n--------------------------------------------------------\n\n"));
	f.print("\n\n");
}
end
