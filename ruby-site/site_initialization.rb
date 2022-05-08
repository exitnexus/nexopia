def initialize_site(init_pagehandlers = true, close_dbs = false)
	begin
		require "core/lib/site";
	
		$site = Site.new($config_name);
		$site.create_dbs();
		$site.load_modules();
		$site.load_page_handlers() if init_pagehandlers;
		$site.load_templates() if init_pagehandlers;
		$site.close_dbs() if close_dbs;
		$log.info("Loading done...", :critical)
	rescue Object
		$stderr.puts $!;
		$stderr.puts $!.backtrace.join("\n");
		exit;
	end
end
