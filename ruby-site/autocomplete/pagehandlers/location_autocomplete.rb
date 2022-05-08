lib_require :Core, 'users/locs'

class LocationAutocomplete < PageHandler
	
	declare_handlers("autocomplete/location") {
		area :Public
		
		page :GetRequest, :Full, :location		
		page :GetRequest, :Full, :location, input(Integer)
	}

	def location(location_id=0)
		t = Template.instance('autocomplete', 'location_autocomplete')

		t.location_name_field_id = params['location_name_field_id', String, 'location_name']
		t.location_id_field_id = params['location_id_field_id', String, 'location']
		t.location_matches_div_id = params['location_matches_div_id', String, 'location_matches']
		t.query_sublocations_field_id = params['query_sublocations_field_id', String, 'query_sublocations']
		t.location_name = location_id == 0 ? "All Locations" : Locs.find(:first, location_id).augmented_name
		t.location_id = location_id
		
		puts t.display
	end
end