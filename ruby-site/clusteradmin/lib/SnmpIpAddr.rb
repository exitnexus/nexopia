require 'clusteradmin/lib/SnmpTable.rb'

class SnmpIpAddr < SnmpTable
	def initialize(args = {})
		@oids = [ "ipAdEntAddr", "ipAdEntIfIndex", "ipAdEntNetMask", "ipAdEntBcastAddr",
			"ipAdEntReasmMaxSize" ] 
		extra = {:MibModules => ["IP-MIB"]}
		super(args.merge(extra))
	end

	def ignore?(r)
		false	# Never ignore rows
	end

end
