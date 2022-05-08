lib_require :Core, 'users/user';
lib_require :Core, 'privilege'
lib_require :Moderator, "modqueue"

module Moderator
	class Moderator < Storable
		init_storable(:moddb, 'mods');
						
		def Moderator.mod_dump
			mods = $site.cache.get("ruby-moddump", 60*60) {
				mod_dump = Hash.new;
		
				moderators = Moderator.find(:all, :scan);
				moderators.each { |m|
					mod_dump[m.userid] ||= {}
					mod_dump[m.userid][m.type] = m.level;
				};
				
				mod_admins = Privilege::Privilege.who_has?(CoreModule, 'moderator')
				mod_queues = QueueBase.queues.keys
				mod_admins.each {|uid|
					mod_dump[uid] ||= {}
					mod_queues.each {|queue|
						if (!mod_dump[uid][queue] || mod_dump[uid][queue] < 10)
							mod_dump[uid][queue] = 10
						end
					}
				}
				mod_dump
			}
			
			return mods;
		end

		class << self

			ERROR_DECAY_RATE = 0.9995;

			def create_mod(userid, typeid)
				mod = new;
				mod.level = 0;
				mod.userid = userid;
				mod.typeid = typeid;
				mod.store;
				return mod;
			end

			def update_mods(id, mod, typeid)
				db.query("UPDATE #{self.table}
					SET `right` = `right` + ?,
					wrong = wrong + ?,
					strict = strict + ?,
					lenient = lenient + ?,
					errorrate = (errorrate * ?) + ?,
					time = ?
					WHERE userid = ? && typeid = ?",
					mod.right,
					mod.wrong,
					mod.strict,
					mod.lenient,
					ERROR_DECAY_RATE,
					(mod.wrong/(mod.right+1)),
					Time.now.to_i, id, typeid);
			end
		end

	end
end

class User < Cacheable
	def mod_level(type)
		mod_types = Moderator::Moderator.mod_dump[self.userid]
		
		if (mod_types)
			if (type.respond_to?(:queue_number))
				return mod_types[type.queue_number]
			else
				return mod_types[type]
			end
		end
		return nil
	end
	
	def mod_levels()
		out = {}
		Moderator::Moderator.mod_dump[self.userid].each {|k,v|
			q = Moderator::QueueBase.by_number(k)
			if (q)
				out[q] = v
			end
		} if (Moderator::Moderator.mod_dump[self.userid])
		return out
	end
	
	def get_queue_counts()
		levels = mod_levels
		counts = Moderator::QueueBase.get_queue_counts(levels)
		
		res = Moderator::ModVote.db.query("SELECT type, count(*) AS count FROM modvotes WHERE modid = ? AND type IN ? GROUP BY type", self.userid, counts.keys.collect {|q|q.queue_number})
		res.each {|r|
			queue = Moderator::QueueBase.by_number(r['type'].to_i)
			counts[queue] -= r['count'].to_i
			counts[queue] = 0 if counts[queue] < 0
		}
		return counts
	end
	
	# Gets the moderator object for this user and the queue type specified. If the user is not a member of the queue, and
	# generate is true, returns an initial object that can be store()ed or used. Otherwise, returns nil
	def mod_prefs(type)
		type = type.queue_number if (type.respond_to?(:queue_number))
		if (lvl = mod_level(type))
			mod = Moderator::Moderator.find(:first, :userid, userid, type)
			if (!mod)
				mod = Moderator::Moderator.new
				mod.userid = userid
				mod.type = type
				mod.autoscroll = true
				mod.picsperpage = 35
				mod.level = lvl
			end
			return mod
		else
			return nil
		end
	end
end