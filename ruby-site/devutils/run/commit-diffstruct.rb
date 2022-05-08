if false
depends_on "svnupdate";
depends_on "dumpstruct";

# should schema dir be a config var?
left = "#{$site.config.svn_base_dir}/schema/struct.cur";
right = "#{$site.config.svn_base_dir}/schema/struct.new";

leftlines = File.readlines(left);
rightlines = File.readlines(right);

added = rightlines - leftlines;
removed = leftlines - rightlines;

newtables = {};
newcols = {};
newkeys = {};

added.each {|addline|
	addline.rstrip!();
	addinfo = addline.split(':');
	type = addinfo[0];
	tableindex = addinfo[1];

	if (type == 'table')
		newtables[tableindex] = {
			:type => addinfo[2],
			:format => addinfo[3],
			:extra => addinfo[4],
			:columns => {},
			:keys => {}
		};
		next;
	end

	itemname = addinfo[2];
	itemindex = [tableindex, itemname].join(':');
	if (newtables.key?(tableindex))
		newtables[tableindex]["#{type}s".to_sym][itemindex] = addline;
		next; # since this is (likely) a new table, we just move on.
	end

	if (type == 'column')
		newcols[itemindex] = addline;
	elsif
		newkeys[itemindex] = addline;
	end
}

altertables = {};
altercols = {};

deltables = {};
delcols = {};
delkeys = {};

removed.each {|delline|
	delline.rstrip!();
	delinfo = delline.split(':');
	type = delinfo[0];
	tableindex = delinfo[1];

	if (type == 'table')
		if (newtables.key?(tableindex))
			# if we get here, a table is actually being ALTERed
			tableinfo = newtables[tableindex];
			altertables[tableindex] = tableinfo;
			newcols.update(tableinfo[:columns]);
			newkeys.update(tableinfo[:keys]);
			newtables.delete(tableindex);
		else
			deltables[tableindex] = delline;
		end
		next;
	end

	itemname = delinfo[2];
	itemindex = [tableindex, itemname].join(':');
	if (!deltables.key?(tableindex))
		if (type == 'column')
			if (newcols.key?(itemindex))
				altercols[itemindex] = newcols[itemindex];
				newcols.delete(itemindex);
			else
				delcols[itemindex] = delline;
			end
		elsif (type == 'key')
			delkeys[itemindex] = delline;
		end
	end
}

File.open("#{$site.config.svn_base_dir}/schema/struct.diff", "a") {|f|
	newtables.each {|tableindex, newtable|
		f.print("CREATE TABLE #{tableindex} (\n");
		count = newtable[:columns].length + newtable[:keys].length;
		sofar = 0;

		newtable[:columns].each {|columnindex, linestr|
			name = columnindex.split(':');
			name = name.pop();

			line = linestr.split(':');
			f.print(" #{name} #{line[3]}");

			sofar += 1;
			if (sofar < count)
				f.print(",");
			end
			f.print("\n");
		}
		newtable[:keys].each {|keyindex, linestr|
			name = keyindex.split(':');
			name = name.pop();
			line = linestr.split(':');

			if (name == "PRIMARY")
				f.print(" PRIMARY KEY #{line[4]} (#{line[5]})");
			else
				f.print(" " + (line[3] == 'unique'? 'UNIQUE' : 'INDEX') + " #{name} #{line[4]} (#{line[5]})");
			end
			sofar += 1;
			if (sofar < count)
				f.print(",");
			end
			f.print("\n");
		}
		f.print(") TYPE = #{newtable[:type]} ROW_FORMAT = #{newtable[:format]} #{newtable[:extra]};\n");
	}

	newcols.each {|columnindex, linestr|
		name = columnindex.split(':');
		line = linestr.split(':');

		# TODO? This used to get the previous line and do a FIRST or AFTER, but
		# that's not really very important:
		# $prevlinestr = rtrim($rightlines[$linenum-1]);
		# $prevline = explode(':', $prevlinestr);
		# $prevname = explode(':', $prevline[2]);
		# $prevname = array_pop($prevname);

		f.print("ALTER TABLE #{name[0]} ADD COLUMN #{name[1]} #{line[3]}\n");
		# if ($prevline[0] == 'table')
		# 	f.print(" FIRST;\n");
		# else
		#	f.print(" AFTER $prevname;\n");
	}

	deltables.each {|tableindex, linestr|
		f.print("DROP TABLE #{tableindex}");
	}

	delcols.each {|colindex, linestr|
		name = colindex.split(':');
		f.print("ALTER TABLE #{name[0]} DROP COLUMN #{name[1]};\n");
	}

	delkeys.each {|keyindex, linestr|
		name = keyindex.split(':');

		f.print("ALTER TABLE #{name[0]} DROP ");
		if (name[1] == 'PRIMARY')
			f.print("PRIMARY KEY;\n");
		else
			f.print("INDEX #{name[1]};\n");
		end
	}

	newkeys.each {|keyindex, linestr|
		name = keyindex.split(':');
		line = linestr.split(':');

		f.print("ALTER TABLE #{name[0]} ADD ");
		if (name[1] == "PRIMARY")
			f.print("PRIMARY KEY /* #{line[4]} */ (#{line[5]});\n");
		else
			f.print((line[3] == 'unique'? 'UNIQUE' : 'INDEX') + " #{name[1]} /* #{line[4]} */ (#{line[5]});\n");
		end
	}

	altertables.each {|tableindex, newtable|
		f.print("ALTER TABLE #{tableindex} TYPE = #{newtable[:type]} ROW_FORMAT = #{newtable[:format]} #{newtable[:extra]};\n");
	}

	altercols.each {|colindex, linestr|
		name = colindex.split(':');
		line = linestr.split(':');

		f.print("ALTER TABLE #{name[0]} MODIFY COLUMN #{name[1]} #{line[3]};\n");
	}
}

# renaming struct.new -> struct.cur.
File.unlink("#{$site.config.svn_base_dir}/schema/struct.cur");
File.rename("#{$site.config.svn_base_dir}/schema/struct.new", "#{$site.config.svn_base_dir}/schema/struct.cur");

# check in changes
`svn ci -m "Changes to Database Schema" #{$site.config.svn_base_dir}/schema`;


end
