require 'clusteradmin/lib/SnmpIpAddr.rb'

class SnmpProcList < PageHandler
	declare_handlers("ipaddr") {
		area :Public
		access_level :Any
		handle :GetRequest, :snmp_ipaddr
	}

	def snmp_ipaddr(*args)
		a = SnmpIpAddr.new(:Host => "localhost")
		puts a.to_html
	end
end
