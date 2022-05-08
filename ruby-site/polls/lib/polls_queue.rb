lib_require :Polls, "official_poll"
lib_want :Moderator, "modqueue"

if (site_module_loaded? :Moderator)
	module Polls
		module Official
			class QueueItem < Moderator::ModItem
				relation_singular :poll, :itemid, Official::Poll
			
				def add_poll_uri()
					params = {:action => "add", :question => poll.question}
					i = 0
					poll.answers.each {|answer|
						params["answers[#{i}]"] = answer.answer
						i += 1
					}
					
					return url/"adminpolls.php" & params
				end
			
				def validate()
					return (!poll.nil?)
				end
			end
		
			class Queue < Moderator::QueueBase
				declare_queue("Polls", 61)
				self.item_type = QueueItem

				# on a yes vote, leave the poll alone. On a no vote, delete the poll.
				def self.handle_no(items)
					items.each {|i|
						i.poll.delete
					}
				end
			end
		end
	end
end