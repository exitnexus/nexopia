lib_require :Core, 'storable/cacheable';

module Jobs
	class AssetType < Cacheable
		init_storable(:jobsdb, 'assettypes');
		
		ErrorTitle = "Asset Type Error";
		InvalidType = "The asset type provided does not exist";
		
		def add_job_request(job_id)
			req_asset = RequestedAsset.new();
			
			req_asset.jobid = job_id;
			req_asset.assettypeid = self.assettypeid;
			
			req_asset.store();
		end
		
		class << self
			def create_default_entries()
				resume_type = AssetType.new();
				resume_type.multistep = false;
				resume_type.name = "Resume";
				
				resume_type.store();
				
				cover_letter_type = AssetType.new();
				cover_letter_type.multistep = false;
				cover_letter_type.name = "Cover Letter";
				
				cover_letter_type.store();
			end
		end
	end
end
