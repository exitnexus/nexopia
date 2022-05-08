require 'clusteradmin/lib/SnmpTable.rb'

class SnmpStorage < SnmpTable
	def initialize(args = {})
		@oids = [ "hrStorageIndex", "hrStorageType", "hrStorageDescr",
			"hrStorageAllocationUnits", "hrStorageSize", "hrStorageUsed",
			"hrStorageAllocationFailures" ] 
		extra = {:MibModules => ["HOST-RESOURCES-MIB"]}
		super(args.merge(extra))
	end

	def ignore?(r)
		false	# Never ignore rows
	end

end
