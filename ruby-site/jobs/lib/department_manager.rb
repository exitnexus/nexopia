lib_require :Core, 'storable/cacheable';

module Jobs
	class DepartmentManager < Cacheable
		init_storable(:jobsdb, 'departmentmanagers');
	end
end