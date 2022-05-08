lib_require :Core, 'storable/storable'

class SkinEntry < Cacheable

	init_storable(:contestdb, "skins")
	
	relation :singular, :designername, [:designerid], UserName

	def self.plus_skins()
		return SkinEntry.find(:conditions => "plus = 'y'", :order => "skinid")
	end

	def self.non_plus_skins()
		return SkinEntry.find(:conditions => "plus = 'n'", :order => "skinid")
	end

	def self.second_vote_skins()
		return SkinEntry.find(:conditions => "round2id IS NOT NULL",  :order => "round2id")
	end
	
	def self.get_skin(skinid)
		return SkinEntry.find(:first, skinid)
	end
	
	def self.get_skin_round2(round2id)
		return SkinEntry.find(:first, :conditions => "round2id = #{round2id}")
	end
		
	def self.round()
	
		entry = SkinEntry.find(:first, :conditions => "round2id IS NOT NULL")
		
		if (entry.nil?())
			return 1
		else
			return 2
		end
		
	end
	
	def to_s()
		return self.id.to_s
	end
end