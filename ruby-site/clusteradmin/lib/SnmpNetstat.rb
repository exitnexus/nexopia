require 'clusteradmin/lib/SnmpTable.rb'

class SnmpNetstat < SnmpTable
	def initialize(args = {})
		@oids = [ "tcpConnState", "tcpConnLocalAddress", "tcpConnLocalPort",
			"tcpConnRemAddress", "tcpConnRemPort" ]
		extra = {:MibModules => ["TCP-MIB"]}
		super(args.merge(extra))
	end

	def ignore?(r)
		false	# Never ignore rows
	end

	def tcpConnState(v)
		# I could use a hash map here but I figure it'll be cleaner to just
		# define common ones rather then a bunch of obscure RFC defined ones
		case v
			when 1
				"closed"
			when 2
				"listen"
			when 3
				"synSent"
			when 4
				"synReceived"
			when 5
				"established"
			when 6
				"finWait1"
			when 7
				"finWait2"
			when 8
				"closeWait"
			when 9
				"lastAck"
			when 10
				"closing"
			when 11
				"timeWait"
			when 12
				"deleteTCB"
			else
				"Unknown (#{v})"
		end
	end
end
