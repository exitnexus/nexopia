lib_require :core, "text_manip"

module MonitoredContent
	@@content_monitors = Hash.new
	@@content_monitor_defaults = Hash.new
	
	module MonitoredContentString
		attr_accessor(:monitors)
		def monitor
			MonitoredContent.content_monitors.each_pair { |monitor, method|
				monitor = false
				if (self.monitors[monitor].respond_to? :call)
					monitor = self.monitors[monitor].call
				else
					monitor = self.monitors[monitor]
				end
				if (monitor)
					result = method.call(result)
				end
			}
		end
	end
	
	def monitor_content(accessor, *args)
		hashes = []
		monitors = @@content_monitor_defaults
		args.each {|conversion|
			if (conversion.kind_of?(Hash))
				hashes << conversion
			else
				monitors[conversion] = true
			end
		}
		hashes.each {|hash|
			hash.each_pair{|conversion, value|
				if (value.kind_of? Symbol)
					monitors[conversion] = lambda {return self.send(value)}
				else
					monitors[conversion] = value
				end
			}
		}

		postchain_method(accessor, &lambda { |original|
			original.extend MonitoredContentString
			original.monitors = monitors
			original
		})
	end
	
	class << self
		def register_monitor(name, method, default=false)
			@@content_monitor_defaults[name] = default
			@@content_monitors[name] = method
		end
		
		def content_monitors()
			return @@content_monitors;
		end
	end
	register_monitor(:nl2br, lambda {|string|	return string.gsub("\n", "<br/>")}, true)
end