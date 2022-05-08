require 'clusteradmin/lib/SnmpStorage.rb'

class SnmpStorageView < PageHandler
	declare_handlers("storage") {
		area :Public
		access_level :Any
		handle :GetRequest, :snmp_storage
	}

	def snmp_storage(*args)
		a = SnmpStorage.new(:Host => "localhost")
		puts a.to_html
	end
end
