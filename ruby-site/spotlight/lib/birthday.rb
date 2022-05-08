lib_require :Observations, "observable"

class User < Cacheable
	include Observations::Observable;
	observable_event :birthday, proc{ "#{link} turned #{@age} today!."}
end