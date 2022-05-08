require 'clusteradmin/lib/SnmpInterfaces.rb'

class SnmpInterfacespage < PageHandler
	declare_handlers("interfaces") {
		area :Public
		access_level :Any
		handle :GetRequest, :snmp_interfaces
	}

	def snmp_interfaces(*args)
		a = SnmpInterfaces.new(:Host => "localhost")
		puts a.to_html
	end
end
