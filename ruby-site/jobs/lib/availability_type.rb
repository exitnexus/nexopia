module Jobs
	class AvailabilityType < Cacheable
		init_storable(:jobsdb, "availabilitytypes");
	end
end
