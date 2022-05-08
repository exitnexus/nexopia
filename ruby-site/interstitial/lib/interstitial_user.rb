class User < Cacheable
	self.postchain_method(:after_create, &lambda { 
		self.lastnotification = Wiki::from_address("/SiteText/sitenotifications").active_revision;
	});	
end