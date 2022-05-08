require 'clusteradmin/lib/SnmpTable.rb'

class SnmpMacAddr < SnmpTable
	def initialize(args = {})
		@oids = [ "ipNetToMediaIfIndex", "ipNetToMediaPhysAddress", 
			"ipNetToMediaNetAddress", "ipNetToMediaType" ]
			
		extra = {:MibModules => ["IP-MIB"]}
		super(args.merge(extra))
	end

	def ignore?(r)
		false
	end

	def ipNetToMediaPhysAddress(v)
		String.new(v).unpack('H16')[0].upcase
	end
end
