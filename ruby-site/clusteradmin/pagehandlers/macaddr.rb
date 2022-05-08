require 'clusteradmin/lib/SnmpMacAddr.rb'

class SnmpMacAddrView < PageHandler
	declare_handlers("mac") {
		area :Public
		access_level :Any
		handle :GetRequest, :snmp_mac
	}

	def snmp_mac(*args)
		a = SnmpMacAddr.new(:Host => "localhost")
		puts a.to_html
	end
end
