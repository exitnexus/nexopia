lib_require :Observations, 'observable_event'
lib_require :Devutils, 'quiz'

class ObservableEventTest < Quiz
	def setup
		Worker::PostProcessQueue.set_enabled(false);
		
		Storable.use_test_db {
			Storable.subclasses().each{|klass|
				if (klass.db.kind_of? SqlBase)
					klass.db.query("TRUNCATE TABLE #{klass.table}");
				end
			}
			
			@user = User.create(
				"Gimli", 
				"pass", 
				"gimli@gimli.com", 
				Time.now()-1, 
				'Male', 
				1, 
				0x7f000001,
				false)
			@user2 = User.create(
				"Gandalf", 
				"pass", 
				"gandalf@frack.com", 
				Time.at(0), 
				'Male', 
				1, 
				0x7f000001,
				false)
			
			friend = Friend.new();
			friend.userid = @user.userid;
			friend.friendid = @user.userid;
			friend.store();

		}
	end
	
	def teardown
	end
	
	
	def test_all_events
		Storable.use_test_db{
			Observations::Observable.send(:remove_method, :after_create)
			Observations::Observable.send(:remove_method, :after_update)
			Observations::Observable.classes.each{|klass|
			
				if klass.respond_to? :mock_instance
					assert_nothing_raised("#{klass} sucks!") {
						Observations::ObservableEvent::UserEvent.db.query("TRUNCATE TABLE usersdb_userevents");
						Observations::ObservableEvent::FriendEvent.db.query("TRUNCATE TABLE usersdb_friendevents");
						
						observable_obj = klass.mock_instance;
						Observations::ObservableEvent.create(observable_obj, 0, @user.userid, Time.now);

						assert_equal(Observations::ObservableEvent.user_events(@user.userid).length, 1);
						assert_equal(Observations::ObservableEvent.friend_events(@user.userid, 1, 1).length, 1);
	
	
						perm = Observations::ObservablePermission.new();
						perm.userid = @user.userid
						perm.typeid = klass.typeid;
						perm.allow = false;
						perm.store;
						
						
						Observations::ObservableEvent.create(observable_obj, 0, @user.userid, Time.now);
						
						#Should be 2 total now... 1 old, plus 1 new.
						assert_equal(Observations::ObservableEvent.user_events(@user.userid).length, 2);
						
						#Should still be 1, because no new one was added.
						assert_equal(Observations::ObservableEvent.friend_events(@user.userid, 1, 1).length, 1);

					}

						
				end
			}
		}
	end

end