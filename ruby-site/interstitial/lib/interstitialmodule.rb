lib_require :Wiki, "wiki"
lib_require :Core, "storable/cacheable"
lib_require :Interstitial, "interstitial_user";

class InterstitialModule < SiteModuleBase
	def self.show
		interstitial = Wiki::from_address("/SiteText/sitenotifications");
		user = PageRequest.current.session.user

		if (interstitial.active_revision > user.lastnotification)
			user.lastnotification = interstitial.active_revision;
			user.store;
			
			t = Template::instance("interstitial", "interstitial");
			t.text = interstitial.get_revision().content;
			return t.display
		end
		return "#{interstitial.active_revision} ----> #{user.lastnotification}"
	end
	
end

