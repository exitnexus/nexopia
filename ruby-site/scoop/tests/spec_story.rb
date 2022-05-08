lib_require :Scoop, 'story'

describe Scoop::Story do
	before do
	end
	
	it "should be possible to create a new story" do
		lambda {
			story = Scoop::Story.new
			story.store
		}.should_not raise_error
	end
	
	it "should return the reporting object for a story when you call reporter" do
		story = Scoop::Event.new
		story.typeid = rand(1000)
		story.primaryid = rand(1000)
		story.secondaryid = rand(1000)

		fake_storable_class = mock("Storable")
		fake_storable_class.should_receive(:indexes).and_return({:PRIMARY => [:primaryid, :secondaryid]})
		fake_storable_class.should_receive(:find).with(story.primaryid, story.secondaryid, :promise, :first).and_return(:reporter)
		TypeID.should_receive(:get_class).with(story.typeid).and_return(fake_storable_class)

		story.reporter.should == :reporter #normally returns an instance of the Storable class but we have it mocked out
	end
	
	after(:all) do
		Scoop::Story.db.query("TRUNCATE scoop_story")
	end
	
end