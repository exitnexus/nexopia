require 'mocha'
require 'stubba'
require 'preferences/pagehandlers/preferences.rb'
require 'preferences/lib/preference.rb'

lib_require :Devutils, 'quiz'

class PreferencesTest < Quiz
	def setup
	end
	
	def teardown		
	end
	
	def test_close 

		User.mock_storable{
			mock_user = User.get_by_id(203);
			mock_params = mock;
			mock_params.expects(:[]).with('closepass', String, "").returns("").at_least_once
			mock_params.stubs(:include?).returns(false)
			
			prefs = Preference.new()
			prefs.update(mock_user, mock_params)
			
			mock_user    = stub_everything
			mock_user.stubs(:params).returns(Time.now.to_i - 99)
			mock_user.stubs(:params).returns(Time.now.to_i - 99)
	
			mock_params  = stub_everything
			mock_request = mock
			
			mock_request.expects(:params).with('closepass', String, "").returns(true).at_least_once
			mock_request.expects(:params).with('reason'   , String, "").returns(true).at_least_once
			
			prefs = Preference.new()
		prefs.update(mock_user, mock_params)
		}
		
	end
	
	def update_setup(include_value)
		mock_user    = stub_everything(:profile => stub_everything)
		mock_params  = stub_everything
		mock_request = mock
		
		mock_params.expects(:include?).returns(include_value).at_least_once
		mock_request.expects(:user    ).returns(mock_user    ).at_least_once
		mock_request.expects(:params  ).returns(mock_params  ).at_least_once    
		
		return [mock_user, mock_params, mock_request]
	end
	
	def update_teardown(mock_user, mock_params, mock_request, include_value)
		if (include_value)
			mock_params.expects(:[]).with('defaultminage'        , Integer).at_least_once
			mock_params.expects(:[]).with('defaultmaxage'        , Integer).at_least_once
			mock_params.expects(:[]).with('defaultloc'           , Integer).at_least_once
			mock_params.expects(:[]).with('forumpostsperpage'    , Integer).at_least_once
			mock_params.expects(:[]).with('timezone'             , Integer).at_least_once
			
			mock_params.expects(:[]).with('defaultsex'           ,  String).at_least_once
			mock_params.expects(:[]).with('forumsort'            ,  String).at_least_once
			mock_params.expects(:[]).with('skin'                 ,  String).at_least_once
			
			mock_params.expects(:[]).with('showjointime'         ,  Boolean).at_least_once
			mock_params.expects(:[]).with('showactivetime'       ,  Boolean).at_least_once
			mock_params.expects(:[]).with('showprofileupdatetime',  Boolean).at_least_once
			mock_params.expects(:[]).with('showbday'             ,  Boolean).at_least_once
			mock_params.expects(:[]).with('showlastblogentry'    ,  Boolean).at_least_once
			mock_params.expects(:[]).with('fwmsgs'               ,  Boolean).at_least_once
			mock_params.expects(:[]).with('enablecomments'       ,  Boolean).at_least_once
			mock_params.expects(:[]).with('autosubscribe'        ,  Boolean).at_least_once
			mock_params.expects(:[]).with('forumjumplastpost'    ,  Boolean).at_least_once
			mock_params.expects(:[]).with('showpostcount'        ,  Boolean).at_least_once
			mock_params.expects(:[]).with('showsigs'             ,  Boolean).at_least_once
			mock_params.expects(:[]).with('showrightblocks'      ,  Boolean).at_least_once
			mock_params.expects(:[]).with('trustjstimezone'      ,  Boolean).at_least_once
			
			mock_params.expects(:[]).with('replyjump'            ,  Boolean).at_least_once
			mock_params.expects(:[]).with('ignorebyagemsgs'      ,  Boolean).at_least_once
			mock_params.expects(:[]).with('ignorebyagecomments'  ,  Boolean).at_least_once
			mock_params.expects(:[]).with('onlyfriendsmsgs'      ,  Boolean).at_least_once
			mock_params.expects(:[]).with('onlyfriendscomments'  ,  Boolean).at_least_once
		else
			mock_params.expects(:[]).with('defaultminage'        , Integer).never
			mock_params.expects(:[]).with('defaultmaxage'        , Integer).never
			mock_params.expects(:[]).with('defaultloc'           , Integer).never
			mock_params.expects(:[]).with('forumpostsperpage'    , Integer).never
			mock_params.expects(:[]).with('timezone'             , Integer).never
			
			mock_params.expects(:[]).with('defaultsex'           ,  String).never
			mock_params.expects(:[]).with('forumsort'            ,  String).never
			mock_params.expects(:[]).with('skin'                 ,  String).never
			
			mock_params.expects(:[]).with('showjointime'         ,  Boolean).never
			mock_params.expects(:[]).with('showactivetime'       ,  Boolean).never
			mock_params.expects(:[]).with('showprofileupdatetime',  Boolean).never
			mock_params.expects(:[]).with('showbday'             ,  Boolean).never
			mock_params.expects(:[]).with('showlastblogentry'    ,  Boolean).never
			mock_params.expects(:[]).with('fwmsgs'               ,  Boolean).never
			mock_params.expects(:[]).with('enablecomments'       ,  Boolean).never
			mock_params.expects(:[]).with('autosubscribe'        ,  Boolean).never
			mock_params.expects(:[]).with('forumjumplastpost'    ,  Boolean).never
			mock_params.expects(:[]).with('showpostcount'        ,  Boolean).never
			mock_params.expects(:[]).with('showsigs'             ,  Boolean).never
			mock_params.expects(:[]).with('showrightblocks'      ,  Boolean).never
			mock_params.expects(:[]).with('trustjstimezone'      ,  Boolean).never
			
			mock_params.expects(:[]).with('replyjump'            ,  Boolean).never
			mock_params.expects(:[]).with('ignorebyagemsgs'      ,  Boolean).never
			mock_params.expects(:[]).with('ignorebyagecomments'  ,  Boolean).never
			mock_params.expects(:[]).with('onlyfriendsmsgs'      ,  Boolean).never
			mock_params.expects(:[]).with('onlyfriendscomments'  ,  Boolean).never        
		end
		
		mock_user.expects(:store).at_least_once
		mock_user.profile.expects(:store).at_least_once
		
		myPrefs = Preferences.new(mock_request)
		myPrefs.update
	end
	
	def test_update_plus(include_value)
		data         = update_setup(include_value)
		mock_user    = data[0]
		mock_params  = data[1]
		mock_request = data[2]
		
		mock_user.expects(:premiumexpiry ).returns(Time.new.to_i + 99).at_least_once
		
		if (include_value)
			mock_params.expects(:[]).with('anonymousviews'       ,  String).at_least_once
			mock_params.expects(:[]).with('showpremium'          ,  Boolean).at_least_once
			mock_params.expects(:[]).with('spotlight'            ,  Boolean).at_least_once
			mock_params.expects(:[]).with('hideprofile'          ,  Boolean).at_least_once
			mock_params.expects(:[]).with('friendsauthorization' ,  Boolean).at_least_once
			mock_params.expects(:[]).with('friendslistthumbs'    ,  Boolean).at_least_once
			mock_params.expects(:[]).with('recentvisitlistthumbs',  Boolean).at_least_once
			mock_params.expects(:[]).with('recentvisitlistanon'  ,  Boolean).at_least_once
			mock_params.expects(:[]).with('limitads'             ,  Boolean).at_least_once
		end
		
		update_teardown(mock_user, mock_params, mock_request, include_value)
	end
	
	def test_update_no_plus(include_value)
		data         = update_setup(include_value)
		mock_user    = data[0]
		mock_params  = data[1]
		mock_request = data[2]
		
		mock_user.expects(:premiumexpiry ).returns(Time.new.to_i - 99).at_least_once
		
		mock_params.expects(:[]).with('anonymousviews'       ,  String).never
		mock_params.expects(:[]).with('showpremium'          ,  Boolean).never
		mock_params.expects(:[]).with('spotlight'            ,  Boolean).never
		mock_params.expects(:[]).with('hideprofile'          ,  Boolean).never
		mock_params.expects(:[]).with('friendsauthorization' ,  Boolean).never
		mock_params.expects(:[]).with('friendslistthumbs'    ,  Boolean).never
		mock_params.expects(:[]).with('recentvisitlistthumbs',  Boolean).never
		mock_params.expects(:[]).with('recentvisitlistanon'  ,  Boolean).never
		mock_params.expects(:[]).with('limitads'             ,  Boolean).never
		
		update_teardown(mock_user, mock_params, mock_request, include_value)
	end
	
	def test_update
		test_update_plus(true)
		test_update_plus(false)
		
		test_update_no_plus(true)
		test_update_no_plus(false)
	end
end
