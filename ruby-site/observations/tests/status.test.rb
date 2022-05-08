lib_require :Observations, 'status'
lib_require :Devutils, 'quiz'

module Observations
	class TestStatusUpdates < Quiz
		def setup
			@user = stub(:username => "Gandalf", :userid => 999999999)
		end
		
		def teardown
			Status.use_test_db {
				Status.db.query("DELETE FROM #{Status.table} WHERE 1=1")
			}
		end
		
		def test_creation
			assert_nothing_raised() {
				Status.new
			}
			Status.use_test_db {
				foo = Status.new
				foo.store
				assert_not_equal(0, foo.creation)
			}
		end
		
		def test_display
			message = "a b c d e"
			#This should be changed to reflect any changes in display output
			expected_output = {
				:general => "#{@user.username} #{message}",
				:tonight => "Tonight #{@user.username} is #{message}",
				:weekend => "This weekend #{@user.username} is #{message}",
				:listening => "#{@user.username} is listening to #{message}",
			}
			foo = Status.new()
			foo.message = message
			foo.stubs(:user).returns(@user)
			expected_output.each_pair {|type, message|
				foo.type = type
				assert_equal(message, foo.status_message)
			}
		end
	
		def test_latest
			foo = Status.latest(@user.userid)
			assert(!foo.nil?)
			assert_equal(0, foo.creation) #if it doesn't come from the database creation time is 0
			assert_equal(:general, foo.type)
			Status.use_test_db {
				foo.store
				foo2 = Status.latest(@user.userid)
				assert_equal(foo, foo2)
				foo3 = Status.latest(@user.userid, :general)
				assert_equal(foo, foo3)
			}
			assert_equal(:general, Status.latest(@user.userid, :general).type)
			assert_equal(:tonight, Status.latest(@user.userid, :tonight).type)
			assert_equal(:weekend, Status.latest(@user.userid, :weekend).type)
			assert_equal(:listening, Status.latest(@user.userid, :listening).type)
		end
		
		def test_calculate_expiry
			now = Time.now.to_i
			assert(Status.calculate_expiry(:general) < Time.now.to_i + 86400*14+20)
			assert(Status.calculate_expiry(:general) > Time.now.to_i + 86400*14-20)
			assert_equal(6, Time.at(Status.calculate_expiry(:tonight)).hour)
			assert_equal((Time.now.day+1)%366, Time.at(Status.calculate_expiry(:tonight)).day)
			assert_equal(6, Time.at(Status.calculate_expiry(:weekend)).hour)
			assert_equal(1, Time.at(Status.calculate_expiry(:weekend)).wday)
			assert(Status.calculate_expiry(:listening) < Time.now.to_i + 3600*2+20)
			assert(Status.calculate_expiry(:listening) > Time.now.to_i + 3600*2-20)
		end
		
		def test_since_creation
			foo = Status.new
			foo.creation = Time.now.to_i
			assert_equal("moments", foo.since_creation)
			foo.creation = Time.now.to_i - 90
			assert_equal("1 minute", foo.since_creation)
			foo.creation = Time.now.to_i - 150
			assert_equal("2 minutes", foo.since_creation)
			foo.creation = Time.now.to_i - 3630
			assert_equal("1 hour", foo.since_creation)
			foo.creation = Time.now.to_i - 7230
			assert_equal("2 hours", foo.since_creation)
			foo.creation = Time.now.to_i - 86430
			assert_equal("1 day", foo.since_creation)
			foo.creation = Time.now.to_i - (86430*2)
			assert_equal("2 days", foo.since_creation)
		end
	end
end