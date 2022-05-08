lib_require :Core, "storable/storable"
lib_require :Core, "pagehandler"

module Modqueue
	class VoteLog < Storable
		init_storable(:moddb, 'testmodvotes');

		class << self

			def update_votes(item_class)
				#list = item_class.find(:conditions => ["ABS(points) >= 6"]);
				#list.each{ |item|
				#	item.delete();
				#}
			end

			def increment(moditem, typeid, amount)
				vote = self.find(:conditions => ["moditemid = ? && typeid = ?", moditem.id.to_i, typeid]).first;
				if (vote == nil)
					vote = new();
					vote.moditemid = moditem.id.to_i;
					vote.typeid = typeid;
					vote.modid = PageHandler.current.session.user.userid;
					vote.vote = false;
					vote.points = -5;
				end

				vote.points += amount;
				vote.store;

				item = ModItem.find(:conditions => ["id = ? && typeid = ?", moditem.id.to_i, typeid]).first();
				$log.info "moderation-system: incrementing #{item.id} : #{item.points} : #{amount}", :debug
				item.points += amount;

				item.store;

				update_votes(moditem.class);
			end

			def delete_in(ids)
				self.db.query("DELETE FROM #{table} where moditemid IN ?", ids)
			end
		end
	end

	class ModItem < Storable
		init_storable(:moddb, 'testmoditem');

		class << self
			def from(storable_item)
				item = self.new
				item.item = Marshal.dump(storable_item.get_primary_key)
				item.points = 0;
				item.lock = 0;
				item.typeid = storable_item.class.typeid;
				item.priority = false;
				item.store();
			end

			def get_by_id(id)
				find(:conditions => ["id = ?", id], :limit => 1).first();
			end

			def update_locks(newlock, ids)
				self.db.query("UPDATE #{self.table} SET `lock` = ? WHERE id IN ?", newlock, ids);
			end

			def delete_in(ids)
				self.db.query("DELETE FROM #{table} where id IN ?", ids)
			end

			def lock(ids, typeid)
				lock_time = Time.now.to_i + (0 * ids.length);
				self.db.query("UPDATE #{self.table} SET `lock` = ?
					WHERE typeid = ? && id IN #", lock_time, typeid, ids);
			end
		end

		def to_s
			return self.item;
		end


	end

	class Moderator < Storable
		init_storable(:moddb, 'testmodqueue');

		class << self

			ERROR_DECAY_RATE = 0.9995;

			def create_mod(userid)
				mod = new;
				mod.xp = 0;
				mod.level = 0;
				mod.userid = userid;
				mod.store;
				return mod;
			end

			def update_mods(id, mod, typeid)
				db.query("UPDATE #{self.table}
					SET `right` = `right` + ?,
					wrong = wrong + ?,
					strict = strict + ?,
					lenient = lenient + ?,
					xp = xp + ?,
					errorrate = (errorrate * ?) + ?,
					time = ?
					WHERE userid = ? && typeid = ?",
					mod.right,
					mod.wrong,
					mod.strict,
					mod.lenient,
					mod.right - (mod.wrong*10),
					ERROR_DECAY_RATE,
					(mod.wrong/(mod.right+1)),
					Time.now.to_i, id, typeid);
			end
		end


	end

	class Queue
		attr :typeid, true;
		attr :data_source, true;
		attr :moderator_class, true;
		attr :vote_log, true;

		def initialize(item_class)
			@typeid = item_class.typeid;
			if (PageHandler.current) and (PageHandler.current.session)
				@moderator = Moderator.find(:first, :conditions => ["userid = ?", PageHandler.current.session.user.userid.to_i]);
				#if (moderator == nil)
				#	Moderator.create_mod(PageHandler.current.session.user.userid);
				#end
				#$log.object(moderator);
			end
		end

		def create_random()
			ModItem.create_random(@typeid);
		end

		def get_finished_items(ids)
			results = ModItem.find(:conditions => ["id IN ? && ABS(points) >= 6", ids]);

			finished_items = Hash.new();
			results.each{ |item|
				finished_items[item.id] = item;
				ids.delete item.id;
			}
			$log.info "Finished items: #{results.length.to_s}", :debug
			return [finished_items, ids];
		end

		def update_scores_on_item(row_id, item, vote_list, user_list)
			#item_key = item.object.get_primary_key;

			#current_votes = Hash.new();
			if item.points > 0
				#items['y'][item_key] = item_key;
				vote_list[item.id].each{ |vote|
					userid = vote.modid;
					#current_votes[userid] = vote.vote;

					if vote.vote == true
						user_list[userid].right += 1;
					else
						user_list[userid].wrong += 1;
						user_list[userid].strict += 1;
					end
				}
			else
				#items['n'][item_key] = item_key;
				[*vote_list[item.id]].each{ |vote|
					userid = vote.modid;
					#current_votes[userid] = vote.vote;

					if vote.vote == true
						user_list[userid].wrong += 1;
						user_list[userid].lenient += 1;
					else
						user_list[userid].right += 1;
					end
				}
			end
		end

		def update_moderator_scores(finished_items)
			results = VoteLog.find(:conditions => ["moditemid IN ?", finished_items.keys]);

			user_list = Hash.new;
			vote_list = Hash.new;

			results.each{ |row|
				if not vote_list.has_key? row.moditemid
					vote_list[row.moditemid] = Array.new();
				end
				vote_list[row.moditemid] << row;

				if not user_list.has_key? row.modid
					user_list[row.modid] = Mod.new();
				end
			}

			votes = Hash.new;

			finished_items.each{ |row_id, item|
				update_scores_on_item(row_id, item, vote_list, user_list);
			}

			time = Time.now.to_i;

			user_list.each{ |id, mod|
				Moderator.update_mods(id, mod, typeid)
			}
		end

		def deal_with_finished_items(ids)
			item_db = ModItem.db;
			item_table = ModItem.table;
			(finished_items,unfinished_ids) = get_finished_items(ids);

			#unlock the remaining ones
			if not unfinished_ids.empty?
				ModItem.update_locks(0, unfinished_ids);
			end

			#done
			if finished_items.empty?
				return;
			end

			update_moderator_scores(finished_items);

			finished_items.each{|id, item|
				TypeID.get_class(@typeid).handle_moderated(item)
			}
			ModItem.delete_in(finished_items.keys);
			VoteLog.delete_in(finished_items.keys);

		end

		def vote(votes)
			ids = Array.new();
			votes.each{ |key, value|
				if (value == "Yes")
					VoteLog.increment(ModItem.get_by_id(key), @typeid, @moderator.level);
					ids << key;
				end
				if (value == "No")
					VoteLog.increment(ModItem.get_by_id(key), @typeid, -@moderator.level);
					ids << key;
				end
			}
			deal_with_finished_items(ids);
		end

		def get_items(num)
			items = {}

			item_table = ModItem.table;
			vote_table = VoteLog.table;
			result = ModItem.db.query("SELECT #{item_table}.item, #{item_table}.id
				FROM #{item_table} LEFT JOIN #{vote_table}
				ON #{item_table}.id=#{vote_table}.moditemid && #{vote_table}.modid = ?
				WHERE #{vote_table}.moditemid IS NULL && #{item_table}.typeid = ?
				&& #{item_table}.lock <= ? ORDER BY priority DESC," +
				" id ASC LIMIT #{num} FOR UPDATE",
				PageHandler.current.session.user.userid,
				@typeid,
				Time.now.to_i);

			ids = [];

			object_klass ||= TypeID.get_class(@typeid);
			result.each(){|row|
				if (row != nil)
					id = row['id'].to_i;
					item = row['item'];
					object_key = Marshal.load(item)
					object = object_klass.find(:first, object_key)

					items[id] = object;
					ids << id;
				end
			}


			if (ids.length > 0)
				ModItem.lock(ids, @typeid);
			end

			return items;
		end

		class << self
			def list
				@modules = Array.new();
				SiteModuleBase.loaded{|name|
					mod = SiteModuleBase.get(name);
					if (mod.respond_to? :moderate_queue_name)
						@modules << mod;
					end
				}
				return @modules
			end
		end

		class Mod
			attr :right, true;
			attr :wrong, true;
			attr :strict, true;
			attr :lenient, true;

			def initialize()
				@right = 0;
				@wrong = 0;
				@strict = 0;
				@lenient = 0;
			end

		end


	end
end
