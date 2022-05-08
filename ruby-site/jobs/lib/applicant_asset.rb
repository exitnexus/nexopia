lib_require :Core, 'storable/cacheable';
lib_require :Jobs, 'asset_type';

module Jobs
	class ApplicantAsset < Cacheable
		init_storable(:jobsdb, 'applicantassets');
		
		relation_singular(:asset_type, :assettype, AssetType);
		
		def uri_info(mode = 'self')
			case mode
			when 'self'
				return [self.userfilename, "#{$site.image_url}/uploads/#{self.mogilefilename}/"];
			end
		end
		
		def selected?(avail_id)
			if(self.availid == avail_id)
				return "selected";
			end
			return false;
		end
	end
end
