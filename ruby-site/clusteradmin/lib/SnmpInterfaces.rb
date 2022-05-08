require 'clusteradmin/lib/SnmpTable.rb'

class SnmpInterfaces < SnmpTable
	def initialize(args = {})
		@oids = ["ifIndex", "ifDescr", "ifType", "ifMtu", "ifSpeed",
			"ifPhysAddress", "ifAdminStatus", "ifOperStatus", "ifLastChange",
			"ifInOctets", "ifInUcastPkts", "ifInNUcastPkts", "ifInDiscards",
			"ifInErrors", "ifInUnknownProtos", "ifOutOctets", "ifOutUcastPkts",
			"ifOutNUcastPkts", "ifOutDiscards", "ifOutErrors", "ifOutQLen",
			"ifSpecific"]
		extra = {:MibModules => ["IF-MIB"]}
		super(args.merge(extra))
	end

	def ignore?(r)
		if (r[7].value == 1)
			false	# Interface is 'up', don't ignore it
		else
			true
		end
	end

	def ifType(v)
		# I could use a hash map here but I figure it'll be cleaner to just
		# define common ones rather then a bunch of obscure RFC defined ones
		case v
			when 1
				"Other"
			when 6
				"Ethernet"
			when 24
				"Loopback"
			else
				"Unknown (#{v})"
		end
	end

	def ifPhysAddress(v)
		String.new(v).unpack('H16')[0].upcase
	end
end
