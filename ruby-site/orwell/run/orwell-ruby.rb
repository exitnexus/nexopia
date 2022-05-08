lib_require :Orwell, 'process_users'

# Want to parallelise based on serverid in accounts table
# Each thread will take a set of servers and will only process users from those servers.

# This will be the number of parallel tasks we want to run.
ORWELL_PARALLEL = 8

# This should get us the IDs of the databases we want to use.
server_ids = $site.dbs[:usersdb].dbs.keys

index = 0
server_ids_splits = Array.new()

# for each parallel task we want to create a new array that will
# hold the IDs of the servers that will be handled by a thread.
for i in (0...ORWELL_PARALLEL)
	server_ids_splits << Array.new
end

# for each of the server ids put them into one of the arrays based
# on the id mod the number of threads.
while (index < server_ids.length)
	server_ids_splits[index % ORWELL_PARALLEL] << server_ids[index]
	index += 1
end

# for each of the lists of servers start a deferred orwell process that
# will deal with that set of servers.
server_ids_splits.each { |server_ids_split|
	unless (server_ids_split.empty?)
		$log.info "Adding Orwell task to deferred_tasks :start_id => 1, :server_ids => [#{server_ids_split.join(', ')}]"
		Orwell::ProcessUsers::run_and_perpetuate_defer(:start_id => 1,
			:server_ids => server_ids_split)
	end
}

