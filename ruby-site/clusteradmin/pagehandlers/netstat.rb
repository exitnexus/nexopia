require 'clusteradmin/lib/SnmpNetstat.rb'

class SnmpNetstatView < PageHandler
	declare_handlers("netstat") {
		area :Public
		access_level :Any
		handle :GetRequest, :snmp_netstat
	}

	def snmp_netstat(*args)
		a = SnmpNetstat.new(:Host => "localhost")
		puts a.to_html
	end
end
