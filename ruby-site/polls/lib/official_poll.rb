module Polls
	module Official
		class Answer < Storable
			init_storable(:polldb, "pollans")
		end
		class Poll < Storable
			init_storable(:polldb, "polls")
			
			relation_multi :answers, "id", Answer, :pollid, :order => "id ASC", :extra_columns => []
			
			def after_delete()
				Answer.db.query("DELETE FROM pollans WHERE pollid = ?", id)
			end
		end
	end
end