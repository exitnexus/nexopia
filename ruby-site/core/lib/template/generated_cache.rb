class GeneratedCodeCache
	
	# Gets all files in this and subdirectories
	def self.find_templates(path)
		Dir["#{path}/*"].each {|file|
			if (File.ftype(file) == 'directory')
				find_templates(file) {|inner_file|
					yield(inner_file);
				}
			else
				yield(file);
			end
		}
	end

	# Gets the age of the library files.
	def self.library_age()
		maxtime = Time.at(0);
		library.each {|file|
			time = File.stat(file).mtime;
			if (time > maxtime)
				maxtime = time;
			end
		}
		return maxtime;
	end

	def self.statfile(filename)
		stat = nil;
		begin
			stat = File.stat(filename);
		rescue Errno::ENOENT => e
			raise e;
		end
	end

	def self.check_valid(ruby_file, file, build_time)
		file_time = statfile(ruby_file).mtime;
		return ((file_time > statfile(file).mtime) and
				(file_time > build_time))
	end
	
	def self.check_dependencies(ruby_file, file, lib_build_time)
		dependencies = nil;
		File.open(ruby_file, "r"){|f|
			dependencies = f.readline[0...-1];
		}
		if (!dependencies.index('#dependencies='))
			return false;
		end
		if (dependencies.split("=").size <= 1)
			return true
		end
		dependencies.split("=")[1].split(",").each{|dependency|
			if (dependency.size > 0)
				source_file = parse_source_file(*parse_dependency(dependency))
				ruby_dep = output_file(*parse_dependency(dependency));
				$log.info "Checking dependency '#{dependency}'", :debug, :template
			 	if !File.exists?(ruby_dep) or !check_valid(ruby_dep, source_file, lib_build_time)
			 		$log.info "Failed.", :debug, :template
					return false;
				end
			end
		}
		return true;
	end
	
	def self.get_cached(*args)
		ruby_file = output_file(*args);
		$log.info("Looking for #{ruby_file}.", :debug, :template);
	 	if File.exists?(ruby_file)
			return ruby_file
		end
		$log.info("Not found.", :debug, :template);
		return nil
	end
	
	def self.check_cached_file(source_file, ruby_file, lib_build_time)
		$log.info("Found #{ruby_file}, checking...", :debug, :template);
	 	if check_valid(ruby_file, source_file, lib_build_time) and check_dependencies(ruby_file, source_file, lib_build_time)
			return true;
		else
			$log.info("#{ruby_file} too old.", :debug, :template);
			File.delete(ruby_file);
		end
		return false
	end
	
	def self.load_template(source_file, source_descriptor, lib_build_time)
		ruby_file = get_cached(*source_descriptor)
		return if ruby_file.nil?
		$log.info "Found a cached copy, now checking.", :debug, :template
		if (check_cached_file(source_file, ruby_file, lib_build_time))
			begin
				$log.info("Requiring... #{ruby_file}", :debug, :template);
				require(ruby_file);
			rescue SyntaxError => e
				$log.info "In file: #{ruby_file}", :error;
				$log.info $!, :error;
				$log.info $!.backtrace.join("\n"), :error;
				return;
			rescue
				$log.info "In file: #{ruby_file}", :error;
				$log.info $!, :error;
				$log.info $!.backtrace.join("\n"), :error;
				return;
			end
			name = class_name(*source_descriptor);
			instantiatedClasses[source_descriptor] = Template.const_get(name);
		end
	end
	
	def self.load_templates()
		$log.info("Searching Modules for template files.", :debug, :template);
		build_time = library_age();

		site_modules() {|mod|
			source_dirs(mod).each{|dir|
				find_templates(dir) {|file|
					if (file =~ source_regexp)
						template_name = $1.to_s;
						load_template(file, [mod, template_name], build_time);
					end
				}
			}
		}
	end
	
end
