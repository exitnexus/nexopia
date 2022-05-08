lib_require :Core, 'typeid';
lib_require :Core, 'storable/cacheable'

# Maps to an account object in the database
class Account < Cacheable
	init_storable(:masterdb, "accounts");
	set_prefix("ruby_serverid_user");
	
	ACCOUNT_STATE_NEW = 1;
	ACCOUNT_STATE_ACTIVE = 2;
	ACCOUNT_STATE_FROZEN = 11;
	ACCOUNT_STATE_DELETED = 15;
	
	attr :object;

	# Loads the full account-specific account object (ie. Forum or User)
	# from the accountid.
	def load()
		typeclass = TypeID.get_class(type);
		if (typeclass.include?(AccountType))
			return typeclass.load(id);
		end
		return nil;
	end

	def after_load()
		@object = promise { self.load(); }
	end

	def after_delete()
		# TODO: Delete accountmap entries -- does storable have a way to
		# do this in batches? (ie. with a where clause).

		masterdb = $site.dbs[:masterdb];
		masterdb.query('UPDATE serverbalance SET realaccounts = realaccounts - 1 WHERE serverid = ? AND type = ?', serverid, type);
	end
	
	def frozen?
		return self.state == ACCOUNT_STATE_FROZEN
	end
	
	def active?
		return self.state == ACCOUNT_STATE_ACTIVE
	end
	
	
	def make_new!()
		self.state = ACCOUNT_STATE_NEW;
		self.store;
	end


	def make_active!()
		self.state = ACCOUNT_STATE_ACTIVE;
		self.store;
	end
end

#just defining the constant here so that we can setup the relation.
class User < Cacheable
end
# Maps group accounts to their members and vice versa.
class AccountMap < Storable
	init_storable(:masterdb, "accountmap");
	#relation_singular(:user, "accountid", User)
end

# This class is automatically extended when AccountType is included and defines
# the class methods that an account type should have.
module AccountTypeClass
	def self.extend_object(other)
		super(other);
		other.extend(TypeID);
	end

	# Loads an instance of this accounttype by accountid. Assumes it's being
	# imported into a Storable-type class and that it's just the primary key.
	# Overload if this is not the case.
	def load(accountid)
		return find(:first, accountid);
	end

	# Creates the basic database entries necessary to map an accountid to
	# a particular server. The class that mixes this in should use this to get
	# an accountid number to use in creating its database entries.
	def create_account()
		masterdb = $site.dbs[:masterdb];
		res = masterdb.query('SELECT serverid FROM serverbalance WHERE weight > 0 AND type = ? ORDER BY (totalaccounts/weight) ASC LIMIT 1', self.typeid);

		accountid = nil;
		if (server = res.fetch())
			serverid = server['serverid'];
			account = Account.new();
			account.type = self.typeid;
			account.serverid = serverid;
			account.store();
			accountid = account.id;

			accountmap = AccountMap.new();
			accountmap.primaryid = accountid;
			accountmap.accountid = accountid;
			accountmap.store();

			masterdb.query('UPDATE serverbalance SET totalaccounts = totalaccounts + 1, realaccounts = realaccounts + 1 WHERE serverid = ? AND type = ?', serverid, account.type);
		else
			raise SiteError.new(500), "Could not get serverid for account creation.";
		end

		return accountid;
	end
end

# Classes that manage a particular account type should include this class
# in order to get the basic features of accounts (getting a full account object
# by id, account creation, account listing, etc).
# The class being mixed in to should implement uri_info so that it can be linked
# to.
# The class should define an id method that returns the accountid of the account.
module AccountType
	def self.included(other)
		other.extend(AccountTypeClass);
	end

	# returns an array of accountids that are members of this account.
	def members()
		maps = AccountMap.find(:primaryid, id);
		if (block_given?)
			maps.each {|map| yield map.accountid; }
		else
			return maps.collect {|map| map.accountid; }
		end
	end

	# Adds the userid to the member list
	def add_member(accountid)
		account = Account.find(:first, id);
		if (account)
			map = AccountMap.new();
			map.primaryid = id;
			map.accountid = accountid;

			begin
				map.store();
			rescue # TODO: make more specific later
				# duplicates are ok.
			end
		end
	end

	# Removes the userid from the member list
	def del_member(accountid)
		map = AccountMap.find(:first, :primaryid, id, accountid);
		if (map && id != accountid)
			map.delete();
		end
	end


	# propagates the delete of the account-specific data to the account
	# table and relations. This should be called if the account is not derived
	# from storable, or the after_delete method is re-overridden by the class
	# this is mixed in to so that it actually happens. If it IS storable derived,
	# this will be called automatically by the after_delete method.
	def account_type_after_delete()
		account = Account.find(:first, id);
		if (account)
			serverid = account.serverid;
			type = account.type;

			account.delete();
		end
	end

	# Storable hook that calls account_type_after_delete. Just calls
	# account_type_after_delete().
	def after_delete()
		account_type_after_delete();
	end
end

# Add a function for user splitting.
class SqlDBStripe
	# Takes ids and maps them to the server based on the account table's serverid
	# column.
	def map_servers_account(ids, writeop)
		result = Account.find(*ids)
		return result.map {|idrow| idrow.serverid.to_i };
	end

=begin TODO: At some point, this implementation needs to do everything the PHP implementation below does.
function split_db_user($dbobj, $keys, $writeop){
	global $masterdb, $cache, $lockSplitWriteOps;

//local cache of mappings
	static $usermap = array(0 => 0); //special value -1 means user doesn't exit, -2 means user being moved

//keys to get
	$keys = array_combine($keys, $keys);

//ids to return
	$ids = array();

//grab from local cache if allowed
	if(!$lockSplitWriteOps){
		foreach($keys as $k){
			if(isset($usermap[$k])){
				if($usermap[$k] != -1)
					$ids[$k] = $usermap[$k];
				unset($keys[$k]);
			}
		}
	}

	if(!$keys)
		return $ids;

//grab from memcache

//spin on entries that are marked as moving.
//If they still aren't grabbed from memcache, fall back to the db, where it will wait for the lock
//Grabbing from the db will fix errors of entries being left as moving when they shouldn't be.
	$i = 20;
	while($i--){
		$moving = array();

		$cached = $cache->get_multi($keys, 'serverid-user-');

		foreach($cached as $uid => $serverid){
			if($serverid == -2){ //moving, try again
				$moving[$uid] = $uid;
				continue;
			}

			if($serverid != -1)
				$ids[$uid] = $serverid;
			$usermap[$uid] = $serverid;
			unset($keys[$uid]);
		}

		if(!$moving)
			break;

		usleep(100000); //sleep 100ms
	}

	if(!$keys)
		return $ids;

//grab from database
	if($lockSplitWriteOps)
		$masterdb->begin();

	$result = $masterdb->prepare_query("SELECT id, serverid FROM accounts WHERE id IN (#)" . ($lockSplitWriteOps ? " FOR UPDATE" : ''), $keys);
	while($row = $result->fetchrow()){
		$ids[$row['id']] = $row['serverid'];

		$usermap[$row['id']] = $row['serverid'];
		unset($keys[$row['id']]);

		$cache->put("serverid-user-$row[id]", $row['serverid'], 7*24*60*60);
	}
	if($lockSplitWriteOps)
		$masterdb->commit();

//don't exist, mark as such
	if($keys){
		foreach($keys as $uid){
			$usermap[$uid] = -1;
			$cache->put("serverid-user-$uid", -1, 7*24*60*60);
		}
	}

	return $ids;
}
	
=end
end
