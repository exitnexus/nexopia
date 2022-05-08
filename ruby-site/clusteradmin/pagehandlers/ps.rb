require 'clusteradmin/lib/SnmpProcessList.rb'

class SnmpProcList < PageHandler
	declare_handlers("ps") {
		area :Public
		access_level :Any
		handle :GetRequest, :snmp_proc_list
	}

	def snmp_proc_list(*args)
		a = SnmpProcessList.new(:Host => "localhost")
		puts a.to_html
	end
end
