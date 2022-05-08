# finish initialization so we're in a more useful environment
initialize_site(true)

# reinitializing the site cleared out the established context. Build a new one.
$site.cache.use_context({}) {
	require 'irb'
	IRB.start
}
