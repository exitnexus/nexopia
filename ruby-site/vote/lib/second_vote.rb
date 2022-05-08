lib_require :Core, 'storable/storable'

class SecondVote < Storable

	init_storable(:contestdb, "secondvote")
		
	def self.can_vote?(userid)
		return SecondVote.find(:first, :conditions => "userid = #{userid}") ? false : true
	end
		
		
	def to_s()
		return self.id.to_s
	end
end