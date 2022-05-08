lib_require :Core, "storable/storable"
lib_require :Moderator, "moderator"

module Moderator
	class ModVote < Storable
		init_storable(:moddb, "modvotes")
	end
	
	class ModItem < Storable
		init_storable(:moddb, "moditems")
		relation_multi :votes, :id, ModVote, :moditemid
		
		def age()
			secs = Time.now - Time.at(self.created)
			hours = secs / 60 / 60
			round = (hours * 100).round / 100
			return round			
		end
		
		# This is run before displaying items to the user, and if it
		# returns false the item is removed from the queue. Use it to dump
		# items that are no longer valid.
		def validate()
			return true
		end
	end

	class QueueBase
		QUEUES = {}
		
		class <<self
			def queues
				return QUEUES
			end
			
			def by_number(num)
				return QUEUES[num]
			end
			
			def declare_queue(pretty_name, queue_number = nil)
				self.extend TypeID
				queue_number ||= self.typeid
				if (QueueBase::QUEUES[queue_number])
					raise "Error: Queue #{pretty_name} tried to register with number #{queue_number}, but #{QueueBase::QUEUES[queue_number].pretty_name} already registered it."
				end
				QueueBase::QUEUES[queue_number] = self 
				@queue_number = queue_number
				@pretty_name = pretty_name
				@lock_base_time = 240
				@lock_per_item_time = 5
				@questionable_queue = nil
				@original_queue = nil
				@item_type = ModItem
				@db = ModItem.db
			end
			
			def set_questionable(questionable_queue)
				@questionable_queue = questionable_queue
				questionable_queue.original_queue = self
			end
			
			attr :queue_number
			attr :pretty_name
			attr :questionable_queue # the queue this queue pushes to in case of questionable items
			attr :original_queue, true # the queue this queue's items originally come from.
			attr :lock_base_time, true # defaults to 120s
			attr :lock_per_item_time, true # defaults to 3s
			attr :item_type, true # set this to a class derived from ModItem that will be used to fetch items by fetch_votable_items
			attr :db
			
			def add_vote(item_id, mod_userid, vote_direction, points)
				vote = ModVote.new()
				vote.moditemid = item_id
				vote.type = queue_number
				vote.modid = mod_userid
				vote.vote = vote_direction
				vote.points = points
				retries = 0
				begin
					vote.store
				rescue SqlBase::DeadlockError
					raise if (retries > 100) # Oh dear, things are REALLY BAD
					$log.info "Detected deadlock, retrying (not fatal)", :debug, :moderator
					#wait between 50 and 250ms (going up each retry)
					sleep((retries + 1) * ((rand(200)+50)/1000.0))
					retries += 1
					retry
				end
				return vote.id
			end

			def add_item(splitid, itemid, priority)
				return if ( ModItem.find(:first, :item, queue_number, itemid, splitid) )
				mod_item = ModItem.new;
				mod_item.type = queue_number;
				mod_item.splitid = splitid;
				mod_item.itemid = itemid;
				mod_item.priority = priority;
				mod_item.created = Time.now.to_i
				retries = 0
				begin
					mod_item.store;
				rescue SqlBase::DeadlockError
					raise if (retries > 100) # Oh dear, things are REALLY BAD
					$log.info "Detected deadlock, retrying (not fatal)", :debug, :moderator
					#wait between 50 and 250ms (going up each retry)
					sleep((retries + 1) * ((rand(200)+50)/1000.0))
					retries += 1
					retry
				end
				return mod_item.id
			end

			# The way this works is that the page that displays the items to the user
			# calls fetch_votable_items to get a set of items that need voting on. It then uses
			# the ModItem objects to properly display the items being voted on.
			# Then, once the user has chosen their votes, it calls vote_on_items with the moditem ids (id column)
			# and whether each was a y or n.
			
			def fetch_votable_items(mod_user, count, for_userid)
				# TODO: fetch these from a description of the item instead of hard coded
				if ($site.config.live)
					lock_per_item = lock_per_item_time
					lock_total = lock_base_time
				else
					lock_per_item = 0 #3
					lock_total = 60 #120
				end
				lvl = mod_user.mod_level(queue_number)
				if (!lvl)
					return []
				end
				retries = 0
				begin
					self.db.query("BEGIN")
					begin
						lock_query = "SELECT moditems.id FROM moditems LEFT JOIN modvotes ON moditems.id=modvotes.moditemid && modvotes.modid = ? WHERE moditems.type = ? AND moditems.`lock` < ? AND modvotes.modid IS NULL"
						lock_params = [mod_user.userid, queue_number, Time.now.to_i]
						if (for_userid)
							lock_query += " AND moditems.splitid = #"
							lock_params.push(for_userid)
						end
						lock_order = "moditems.priority DESC, " + (lvl && lvl >= 6? "moditems.points ASC, " : "") + "moditems.id ASC"
						lock_query += " ORDER BY #{lock_order} "
						lock_query += " LIMIT ?"
						lock_params.push(count)
						res = self.db.query(lock_query + " FOR UPDATE", *lock_params)
						items = res.collect {|i| i['id'] }
						if (!items.empty?)
							self.db.query("UPDATE moditems SET `lock` = ?, lock_userid = ? WHERE id IN ?", Time.now.to_i + items.length * lock_per_item + lock_total, mod_user.userid, items)
						end
					rescue SqlBase::DeadlockError
						raise # reraise to outer block
					rescue SqlBase::CannotFindRowError
						# Silently ignore; this is a 'can't find record'
						# error and indicates that all items have already
						# been modded by someone else.
					rescue
						self.db.query("ROLLBACK")
						$log.info("Failed to fetch items to vote on", :error, :moderator)
						raise
					end
					self.db.query("COMMIT")
				rescue SqlBase::DeadlockError
					raise if (retries > 100) # Oh dear, things are REALLY BAD
					$log.info "Detected deadlock, retrying (not fatal)", :debug, :moderator
					#wait between 50 and 250ms (going up each retry)
					sleep((retries + 1) * ((rand(200)+50)/1000.0))
					retries += 1
					retry
				end
			
				return self.item_type.find(:conditions => ["type = ? AND lock_userid = ? AND `lock` > ?", queue_number, mod_user.userid, Time.now.to_i], :order => lock_order, :limit => count)
			end
		
			# votes is a hash of itemid=>(true|false) for an up or down vote respectively.
			# Votes will only be cast if the item is still locked to the user doing the voting.
			def vote_on_items(mod_user, votes)
				lvl = mod_user.mod_level(queue_number)
				if (!lvl)
					return
				end
				type = queue_number
				retries = 0
				begin
					self.db.query("BEGIN")
					begin
						# Lock and fetch the items the user is elligible to vote for.
						res = self.db.query("SELECT * FROM moditems WHERE id IN ? AND type = ? AND `lock` >= ? AND lock_userid = ? FOR UPDATE", votes.keys, type, Time.now.to_i, mod_user.userid)
						yes_items = []
						no_items = []
						items = res.collect {|item|
							item['id'] = item['id'].to_i
							if (votes[item['id']])
								yes_items.push(item['id'])
								item['points'] = item['points'].to_i + lvl
							else
								no_items.push(item['id'])
								item['points'] = item['points'].to_i - lvl
							end
							add_vote(item['id'], mod_user.userid, votes[item['id']], lvl)
							item
						}

						self.db.query("UPDATE moditems SET points = points + ? WHERE id IN ?", lvl, yes_items) if (!yes_items.empty?)
						self.db.query("UPDATE moditems SET points = points - ? WHERE id IN ?", lvl, no_items) if (!no_items.empty?)

						# separate out the items we're done with from the ones that have passed the post
						unfinished = items.collect {|i| (i['points'].abs < 6) && i }.delete_if {|i| !i }
						finished = items.collect {|i| (i['points'].abs >= 6) && i }.delete_if {|i| !i }

						# process winners
						vote_data = {}
						finished.each {|item|
							vote_data[item['id']] = item
						}
						if (!vote_data.empty?)
							votes = ModVote.find(:moditemid, *vote_data.keys)
						else
							votes = []
						end
						votes.each {|vote|
							vote_data[vote.moditemid]['votes'] ||= []
							vote_data[vote.moditemid]['votes'].push(vote)
						}

						# Update mods' right/wrong for this queue.
						yes_items = []
						no_items = []
						questionable_items = []
						mod_changes = {}
						vote_data.each {|item_id, item|
							questionable = item['votes'].collect {|v| v.vote }.uniq.length > 1
							questionable_items.push(item) if (questionable)

							if (item['points'] > 0)
								yes_items.push(item)

								if (!questionable || !questionable_queue)
									item['votes'].each {|vote|
										mod_changes[vote.modid] ||= {'wrong' => 0, 'right' => 0, 'lenient' => 0, 'strict' => 0}
										if (vote.vote)
											mod_changes[vote.modid]['right'] += 1
										else
											mod_changes[vote.modid]['wrong'] += 1
											mod_changes[vote.modid]['strict'] += 1
										end
									}
								end
							else
								no_items.push(item)

								if (!questionable || !questionable_queue)
									item['votes'].each {|vote|
										mod_changes[vote.modid] ||= {'wrong' => 0, 'right' => 0, 'lenient' => 0, 'strict' => 0}
										if (vote.vote)
											mod_changes[vote.modid]['wrong'] += 1
											mod_changes[vote.modid]['lenient'] += 1
										else
											mod_changes[vote.modid]['right'] += 1
										end
									}
								end
							end
						}
						questionable_items.uniq!

						real_queue = if original_queue
							original_queue.queue_number
						else
							queue_number
						end
						mod_changes.each {|modid, mod|
							self.db.query("UPDATE mods SET `right` = `right` + ?, wrong = wrong + ?, strict = strict + ?, lenient = lenient + ?, time = ? WHERE userid = ? AND type = ?",
								mod['right'], mod['wrong'], mod['strict'], mod['lenient'], Time.now.to_i, modid, real_queue)
							# Uberhack to make it put the changes to their stats into the questionable queue as well if the 
							# vote is from the user who triggered this calculation and we're voting on a questionable queue.
							if (real_queue != queue_number && modid == mod_user.id)
								self.db.query("UPDATE mods SET `right` = `right` + ?, wrong = wrong + ?, strict = strict + ?, lenient = lenient + ?, time = ? WHERE userid = ? AND type = ?",
									mod['right'], mod['wrong'], mod['strict'], mod['lenient'], Time.now.to_i, modid, queue_number)
							end
						}

						# If there are questionable items, and the queue has a questionable queue, do something different with those items.
						if (questionable_items.length > 0)
							if (questionable_queue)
								yes_items -= questionable_items
								no_items -= questionable_items

								# Convert the questionable items to being the type of the questionable queue, and reset points back to 0.
								self.db.query("UPDATE moditems SET type = ?, points = 0 WHERE id IN ?", questionable_queue.queue_number, questionable_items.collect {|i| i['id'] })
								self.db.query("UPDATE modvotes SET type = ? WHERE moditemid IN ?", questionable_queue.queue_number, questionable_items.collect {|i| i['id']})

								# Remove them from the vote_data list to avoid doing any further processing on the questionable items.
								questionable_items.each {|i| vote_data.delete(i['id']) }
							else
								$log.info("Queue '#{pretty_name}' had questionable items, but nowhere to put them.", :warning, :moderator) if (!original_queue)
							end				
						end

						handle_yes(item_type.find(*yes_items.collect{|i| i['id'] })) if (yes_items.length > 0)
						handle_no(item_type.find(*no_items.collect{|i| i['id'] })) if (no_items.length > 0)

						# Remove items from the queue.
						self.db.query("DELETE FROM moditems WHERE id IN ?", vote_data.keys) if (!vote_data.empty?)
						self.db.query("DELETE FROM modvotes WHERE moditemid IN ?", vote_data.keys) if (!vote_data.empty?)

						# Unlock all items voted on
						self.db.query("UPDATE moditems SET `lock` = 0, lock_userid = 0 WHERE id IN ?", items.collect{|i| i['id']}) if (!items.empty?)

						$log.info("Yes votes: #{yes_items.collect{|i|i['id']}.join(',')}", :debug, :moderator) 
						$log.info("No votes: #{no_items.collect{|i|i['id']}.join(',')}", :debug, :moderator)
						$log.info("Questionable votes: #{questionable_items.collect{|i|i['id']}.join(',')}", :debug, :moderator)

					rescue SqlBase::DeadlockError
						raise # reraise to outer block
					rescue SqlBase::CannotFindRowError
						# Silently ignore; this is a 'can't find record'
						# error and indicates that all items have already
						# been modded by someone else.
					rescue
						self.db.query("ROLLBACK")
						$log.info("Had to rollback vote query", :error, :moderator)
						raise
					end
					self.db.query("COMMIT")
				rescue SqlBase::DeadlockError
					raise if (retries > 100) # Oh dear, things are REALLY BAD
					$log.info "Detected deadlock, retrying (not fatal)", :debug, :moderator
					#wait between 50 and 250ms (going up each retry)
					sleep((retries + 1) * ((rand(200)+50)/1000.0))
					retries += 1
					retry
				end
			end
			
			def get_queue_counts(queues)
				out = {}
				res = ModItem.db.query("SELECT type, count(*) AS count FROM moditems WHERE type IN ? AND `lock` <= ? GROUP BY type", queues.collect {|q,v|q.queue_number}, Time.now.to_i)
				res.each {|r|
					out[by_number(r['type'].to_i)] = r['count'].to_i
				}
				return out
			end
			
			def handle_yes(items)
				if (self.original_queue)
					self.original_queue.handle_yes(items)
				end
			end

			def handle_no(items)
				if (self.original_queue)
					self.original_queue.handle_no(items)
				end
			end
		end
	end	

	class TestQueue < QueueBase
		declare_queue("Test")
	end
	
	class TestQuestionableQueue < QueueBase
		declare_queue("Test Questionable")
	end
	class TestWithQuestionableQueue < QueueBase
		declare_queue("Test With Questionable")
		set_questionable(TestQuestionableQueue)
	end
end