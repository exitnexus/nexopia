lib_require :Clusteradmin, 'SnmpTable'

class SnmpProcessList < SnmpTable

	def initialize(args = {})
	@oids = ["hrSWRunIndex", "hrSWRunName", "hrSWRunID", "hrSWRunPath", 
		"hrSWRunParameters", "hrSWRunType", "hrSWRunStatus", "hrSWRunPerfCPU",
		"hrSWRunPerfMem"]
		extra = {:MibModules => ["HOST-RESOURCES-MIB"]}
		super(args.merge(extra))
	end

	def hrSWRunStatus(v)
		case v
			when 1
				"Running"
			when 2
				"Waiting"
			when 3
				"Not Running"
			when 4
				"Invalid"
			else
				"Unknown Status"
		end
	end

	def hrSWRunType(v)
		case v
			when 1
				"Unknown"
			when 2
				"Operating System"
			when 3
				"Device Driver"
			when 4
				"Application"
			else
				"Unknown"
		end
	end
end
