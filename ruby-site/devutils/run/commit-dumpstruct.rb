if false
depends_on "svnupdate";

tables = {};

$site.dbs.each {|dbname, dbobj|
	dbobj = dbobj.get_split_dbs()[0];
	dbobj.list_tables().each {|table|
		tname = table['Name'];
		tableindex = ($site.config.dumpstruct_include_dbname ? "#{dbname}.#{tname}" : tname);

		engine = (table.key?('Engine') ? table['Engine'] :  table['Type']);
		rowformat = table['Row_format'];
		options = table['Create_options'];

		tablestring = ['table', tableindex, engine, rowformat, options].join(':') + "\n";
		columnstring = "";
		dbobj.query("SHOW COLUMNS FROM `#{tname}`").each {|column|
			columnstring << ['column', tableindex, column['Field'], column['Type']].join(':');
			columnstring << " ";
			if (column['Null'] != 'YES')
				columnstring << "NOT NULL ";
			end

			# The extra conditions, which make '' and '0' non-specified defaults
			# may not be correct. It most likely comes from a normal behaviour of php
			# where they evaluate to false. Is this implicit in mysql?
			if (column['Default'] && column['Default'] != '' && column['Default'] != '0')
				columnstring << dbobj.prepare("DEFAULT ? ", column['Default']);
			end

			if (column['Extra'])
				columnstring << column['Extra'];
			end

			columnstring << "\n";
		}

		keys = {};
		keydetails = {};
		pkey = [];
		ukeys = [];
		ikeys = [];

		dbobj.query("SHOW INDEX FROM `#{tname}`").each {|key|
			keyname = key['Key_name'];
			if (!keys.key?(keyname))
				keys[keyname] = [];
			end

			keys[keyname][key['Seq_in_index'].to_i - 1] = "#{key['Column_name']} #{key['Collation'] == 'A' ? 'ASC' : 'DESC'}";

			if (!keydetails[keyname])
				keydetails[keyname] = {
					:unique => (key['Non_unique'] == '0'),
					:type => key['Index_type']
				};
				if (keyname == "PRIMARY")
					pkey.push(keyname);
				elsif (key['Non_unique'] == '1')
					ikeys.push(keyname);
				else
					ukeys.push(keyname);
				end
			end
		}

		ukeys.sort();
		ikeys.sort();

		keynames = pkey + ukeys + ikeys;

		keystring = "";
		keynames.each {|keyname|
			columns = keys[keyname];

			keystring << ['key', tableindex, keyname,
						  keydetails[keyname][:unique]? 'unique' : 'nonunique',
						  keydetails[keyname][:type], columns.join(',')].join(':');
			keystring << "\n";
		}

		tablestring << columnstring + keystring;

		if (tables.key?(tableindex) && tablestring != tables[tableindex])
			echo "Duplicate but non-compatible table found: #{dbname}.#{tname}";
		else
			tables[tableindex] = tablestring;
		end
	}
}

keys = tables.keys.sort;
lines = [];

keys.each {|key|
	lines.push(tables[key]);
}

File.open("#{$site.config.svn_base_dir}/schema/struct.new", "w") {|f|
	f.print(lines.join(""));
}
end
