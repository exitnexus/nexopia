lib_require :Core, 'storable/storable'

class FirstVote < Storable

	init_storable(:contestdb, "firstvote")

	relation :singular, :owner, [:userid], User
	

	def self.can_vote?(userid)
		return FirstVote.find(:first, :conditions => "userid = #{userid}") ? false : true
	end

	def jointime
		return self.owner.jointime
	end

	def to_s()
		return self.id.to_s
	end
end