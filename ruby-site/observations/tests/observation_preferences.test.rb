lib_require :Observations, 'observation_preferences'
lib_require :Devutils, 'quiz'

module Observations
	class TestObservationPreferences < Quiz
		def setup
			@user = stub(:username => "Gandalf", :userid => 999999999)
		end
		
		def teardown
			ObservationPreference.use_test_db {
				ObservationPreference.db.query("DELETE FROM #{ObservationPreference.table} WHERE 1=1")
			}
		end
	
		def test_create
			ObservationPreference.use_test_db {
				assert_nothing_raised {
					foo = ObservationPreference.new
					foo.store
				}
			}
		end
	end
end