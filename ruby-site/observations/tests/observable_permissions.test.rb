lib_require :Observations, 'observable_permissions', 'observable_event', 'status'
lib_require :Devutils, 'quiz'

module Observations
	class TestObservablePermissions < Quiz
		def setup
			@user = stub(:username => "Gandalf", :userid => 999999999)
		end
		
		def teardown
			ObservablePermission.use_test_db {
				ObservablePermission.db.query("DELETE FROM #{ObservablePermission.table} WHERE 1=1")
			}
		end
		
		def test_create
			ObservablePermission.use_test_db {
				assert_nothing_raised {
					foo = ObservablePermission.new
					foo.store
				}
				assert_nothing_raised {
					foo = ObservablePermissions.new(@user.userid)
				}
			}
		end
		
		def test_allow_event?
			ObservablePermission.use_test_db {
				permissions = ObservablePermissions.new(@user.userid)
				permissions.filters = {
					Status.typeid => false
				}
				foo = ObservableEvent::UserEvent.new
				foo.classtypeid = Status.typeid
				assert_equal(false, permissions.allow_event?(foo))
				permissions.filters = {
					Status.typeid => true
				}
				assert_equal(true, permissions.allow_event?(foo))
				permissions.filters = {}
				assert_equal(Status::EVENT_SHOW, permissions.allow_event?(foo))
			}
		end
		
		def test_filter
			ObservablePermission.use_test_db {
				permissions = ObservablePermissions.new(@user.userid)
				permissions.filters = {
					Status.typeid => false
				}
				foo = ObservableEvent::UserEvent.new
				foo.classtypeid = Status.typeid
				assert(permissions.filter([foo]).empty?)
				permissions.filters = {
					Status.typeid => true
				}
				assert_equal([foo], permissions.filter([foo]))
				permissions.filters = {}
			}
		end
		
		def test_adjust_permissions
			ObservablePermission.use_test_db {
				desired_allowed_ids = []
				allowed_ids = []
				denied_ids = []
				#assign some random permissions to some observable classes
				Observable.classes.each {|observable_class|
					perm = ObservablePermission.new
					perm.userid = @user.userid
					perm.typeid = observable_class.typeid
					if (rand > 0.5)
						perm.allow = true
					else
						perm.allow = false
					end
					if (rand > 0.5) #lets make a new desired set
						desired_allowed_ids << perm.typeid
					end
					if (rand > 0.3)
						perm.store
						allowed_ids << perm.typeid if perm.allow
						denied_ids << perm.typeid if !perm.allow
						assert(ObservablePermission.find(:first, perm.userid, perm.typeid)) #make sure it actually stored
					end
				}
				ObservablePermission.find(:all).each {|perm|
					if (perm.allow)
						assert(allowed_ids.index(perm.typeid), "We should have decided to allow this in the initial setup.")
					else
						assert(denied_ids.index(perm.typeid), "We should have decided to deny this in the initial setup.")
					end
				}
				ObservablePermissions.adjust_permissions(@user.userid, desired_allowed_ids)
				permissions = ObservablePermission.find(:all)
				Observable.classes.each {|observable_class|
					permission = permissions.find {|permission| permission.typeid == observable_class.typeid}
					assert(permission, "Couldn't find a permission for #{observable_class}")
					assert_equal(permission.allow, desired_allowed_ids.index(permission.typeid) ? true : false, 
						"Incorrect permission for #{observable_class}")
				}
			}
		end
	end
end