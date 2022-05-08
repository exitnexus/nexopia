class FileChangeMonitor
	def initialize()
		@files = {};
	end

	def statfile(filename)
		stat = nil;
		begin
			stat = File.stat(filename);
		rescue Errno::ENOENT
			begin
				stat = File.stat("#{filename}.rb");
			rescue Errno::ENOENT
			end
		end
	end

	def register_file(filename)
		if (stat = statfile(filename))
			@files[filename] = stat;
		end
	end

	def changes?()
		changed = false;
		@files.each {|filename, oldstat|
			newstat = statfile(filename);
			if(!newstat) #missing, stop monitoring
				$log.info("File #{filename} disappeared since registration.", :info);
				@files.delete(filename);
				changed = true;
			elsif(oldstat.mtime != newstat.mtime) #changed
				$log.info("File #{filename} changed since registration.", :info);
				@files[filename] = newstat;
				changed = true;
			end
		}
		return changed;
	end

	def init_monitoring()
		Kernel.module_eval(%Q{
			alias file_change_monitor_require require;
			def require(name)
				FileChanges.register_file(name);
				return file_change_monitor_require(name);
			end
			alias file_change_monitor_load load;
			def load(name)
				FileChanges.register_file(name);
				return file_change_monitor_load(name);
			end
		});
	end
end

FileChanges = FileChangeMonitor.new();
