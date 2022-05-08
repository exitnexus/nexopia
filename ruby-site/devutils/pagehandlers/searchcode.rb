class SearchCode < PageHandler
	declare_handlers("searchcode") {
		page :GetRequest, :Full, :new_search;
		page :GetRequest, :Full, :run_search, "search";
	}

	@@types = [ ['lib', 'Libraries'],
	           ['pagehandlers', 'Page Handlers'],
	           ['templates', 'Templates'],
	           ['tests', 'Unit Tests'],
	           ['other', 'Other'],
	         ]

	def new_search()

		puts "<html><head><title>Code Search</title></head><body>";

		puts "<form action='/searchcode/search'>";
		puts "<table align='center'>";
		puts "<tr><td colspan='2' align='center'><b>Search Code</b></td></tr>";
		puts "<tr><td colspan='2'>Find: <input type='text' name='search' /></td></tr>";

		puts "<tr><td colspan='2'>";
		puts "<input type='radio' name='allfiles' value='yes' checked='checked' /> All Files";
		puts "<input type='radio' name='allfiles' value='no' /> Only Below";
		puts"</td></tr>";

		puts "<tr>";

		puts "<td valign='top'>Types:<br />";
		@@types.each { |type|
			name, description = type;
			puts "<input type='checkbox' name='types' value='#{name}' checked='checked' /> #{description}<br />";
		}
		puts "</td>";

		modules = find_modules().sort;

		puts "<td valign='top'>Modules:<br />";
		puts "<input type='checkbox' name='nomodule' value='On' checked='checked' /> None<br />";
		modules.each { |name|
			puts "<input type='checkbox' name='modules' value='#{name}' checked='checked' /> #{name}<br />";
		}
		puts "</td>";

		puts "</tr>";

		puts "<tr><td><input type='submit' value='Search' /></td></tr>";
		puts "</table>";
		puts "</form>";

		puts "</body></html>";
	end

	def run_search()
		search = params['search', String, ''];

		allfiles = params['allfiles', String, ''];

		types = params['types', [String], [] ];
		modules = params['modules', [String], [] ];
		nomodule = params['nomodule', String, ''];


		file_list = [];

		if(allfiles == 'yes')
			file_list = find_files(".") + find_files("../ruby-test");
		else
			if(types.include?('other') && nomodule == 'On')
				Dir["./*.rb"].each {|file|
					file_list.push(file);
				}
			end

			modules.each { |modname|
				types.each { |type|
					file_list += find_files("./#{modname}/#{type}");
				}
			}

			if(types.include?('tests'))
				modules.each { |modname|
					file_list += find_files("../ruby-test/#{modname}");
				}
			end
		end

		results = [];

		file_list.each { |file|
			fh = File.new(file)

			i = 1;
			fh.each { |line|
				matched = line.match(search);
				if(matched)
					results.push( [ file, i, line, matched ]);
				end
				i += 1;
			}
		}


		puts "<html><head><title>Paste Bin</title>";
		puts "<style>";

		puts "td { background-color: #F6F6F6; }";
		puts "th { background-color: #DDDDDD; }";
		puts "td > pre { display: inline; }";

		puts "</style>";
		puts "</head><body>";

		curfile = "";

		puts "<table align='center' cellspacing='1' cellpadding='1'>";

		results.each { |row|
			file, linenum, line, matched = row;

			if(file != curfile)
				puts "<tr><th colspan='2' align='left'>#{file}</th></tr>";
				curfile = file;
			end

			str = CGI::escapeHTML(line);

			puts "<tr>";
			puts "<td align='right'>#{linenum}: </td>";
			puts "<td><pre>#{str}</pre></td>";
			puts "</tr>";
		}
		puts "</table>";
		puts "</body></html>";
	end

	def find_modules
		modules = [];
		Dir["*"].each {|file|
			if (File.ftype(file) == 'directory')
				modules.push(file);
			end
		}
		return modules;
	end

	def find_files(path)
		files = [];
		Dir["#{path}/*"].each {|file|
			if (File.ftype(file) == 'directory')
				files += find_files(file);
			else
				files.push(file);
			end
		}
		return files;
	end
end
