lib_require :Scoop, 'event', 'story'

describe Scoop::Event do
	before do
	end
	
	it "should be possible to create a new event" do
		lambda {
			event = Scoop::Event.new
			event.store
		}.should_not raise_error
	end
	
	it "should return the reporting object for an event when you call reporter" do
		event = Scoop::Event.new
		event.typeid = rand(1000)
		event.primaryid = rand(1000)
		event.secondaryid = rand(1000)

		fake_storable_class = mock("Storable")
		fake_storable_class.should_receive(:indexes).and_return({:PRIMARY => [:primaryid, :secondaryid]})
		fake_storable_class.should_receive(:find).with(event.primaryid, event.secondaryid, :promise, :first).and_return(:reporter)
		TypeID.should_receive(:get_class).with(event.typeid).and_return(fake_storable_class)

		event.reporter.should == :reporter #normally returns an instance of the Storable class but we have it mocked out
	end
	
	it "should distribute an event to a user when you call distribute! if reporter.can_see_event?(userid) returns true" do
		userid = rand(1000)
		id = rand(1000)
		
		event = Scoop::Event.new
		event.typeid = rand(1000)
		event.primaryid = rand(1000)
		event.secondaryid = rand(1000)
		event.userid = rand(1000)
		event.id = rand(1000)
		
		mock_story = mock "Scoop::Story"
		mock_story.should_receive(:userid=).with(userid)
		mock_story.should_receive(:id=).with(id)
		mock_story.should_receive(:sourceuserid=).with(event.userid)
		mock_story.should_receive(:sourceid=).with(event.id)
		mock_story.should_receive(:typeid=).with(event.typeid)
		mock_story.should_receive(:primaryid=).with(event.primaryid)
		mock_story.should_receive(:secondaryid=).with(event.secondaryid)
		mock_story.should_receive(:store)
		
		Scoop::Story.should_receive(:new).and_return(mock_story)
		Scoop::Story.should_receive(:get_seq_id).with(userid).and_return(id)
		
		
		mock_reporter = mock("Scoop::Reporter")
		mock_reporter.should_receive(:can_view_event?).with(userid).and_return(true)
		event.should_receive(:reporter).and_return(mock_reporter)

		event.distribute!(userid)
	end
	
	it "should not distribute an event to a user when you call distribute! if reporter.can_see_event?(userid) returns false" do
		userid = rand(1000)
		
		Scoop::Story.should_not_receive(:new)
		event = Scoop::Event.new
		mock_reporter = mock("Scoop::Reporter")
		mock_reporter.should_receive(:can_view_event?).with(userid).and_return(false)
		event.should_receive(:reporter).and_return(mock_reporter)

		event.distribute!(userid)
	end
	
	it "should distribute an event to all the people who have friended a user when we call Scoop::Event.distribute_event(userid)" do
		ids_list = [rand(1000), rand(1000), rand(1000)]
		userid = rand(1000)
		
		mock_user = mock("User")
		mock_user.should_receive(:friends_of_ids).and_return(ids_list)
		User.should_receive(:find).with(:first, :promise, userid).and_return(mock_user)

		id_index = 0
		mock_event = mock("Scoop::Event")
		mock_event.should_receive(:distribute!) {|arg|
			arg.should == ids_list[id_index]
			id_index += 1
		}.exactly(ids_list.length).times
		mock_event.should_receive(:userid).at_least(1).times.and_return(userid)
		
		Scoop::Event.distribute_event(mock_event)
	end
	
	after(:all) do
		Scoop::Event.db.query("TRUNCATE scoop_event")
	end
end