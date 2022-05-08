require 'core/lib/dispatcher';
require 'site_initialization';
initialize_site(false, false);

if (!site_module_loaded?(:Worker))
	$log.info("Worker module not loaded, going down", :critical)
	exit(1);
end

Worker.dispatch_deferred_tasks()