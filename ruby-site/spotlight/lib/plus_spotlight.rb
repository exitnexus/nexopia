lib_require :Observations, "observable"

module Spotlight
	class PlusSpotlight
		include Observations::Observable
		OBSERVABLE_NAME = "Plus Spotlight"
	end
end