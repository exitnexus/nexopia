lib_require :Core, 'storable/cacheable';
lib_require :Jobs, 'asset_type';

module Jobs
	class RequestedAsset < Cacheable
		init_storable(:jobsdb, 'requestedassets');
		
		class << self
			def associate_defaults(job_id)
				default_types = AssetType.find(:conditions => ["name = 'Resume' OR name = 'Cover Letter'"]);
				
				for type in default_types
					temp = RequestedAsset.new();
					temp.jobid = job_id;
					temp.assettypeid = type.assettypeid;
					
					temp.store();
				end
			end
		end
	end
end
