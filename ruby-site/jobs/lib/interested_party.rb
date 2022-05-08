lib_require :Core, 'storable/cacheable';

module Jobs
	class InterestedParty < Cacheable
		init_storable(:jobsdb, 'interestedparties');
	end
end
