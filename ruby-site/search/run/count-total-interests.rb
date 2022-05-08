lib_require :search, "usersearch"


def run_search(interest_ids)
	opts = {
		'agemin' => 13,
		'agemax' => 80,
		'pic' => 0,
		'interests' => interest_ids,
	}

	user_search = Search::UserSearch.new;

	opts['active'] = 0
	total = user_search.search(opts, 0, 1)['totalrows']
	opts['active'] = 1
	month = user_search.search(opts, 0, 1)['totalrows']
	opts['active'] = 2
	week = user_search.search(opts, 0, 1)['totalrows']
	return [total, month, week]
end

all_ids = Interests.get_children_ids

total, month, week = run_search(all_ids.flatten.uniq)
puts "#{'Chose an Interest'.rjust(20)} => total: #{total}, month: #{month}, week: #{week}"

total, month, week = run_search(0)
puts "#{'Everyone'.rjust(20)} => total: #{total}, month: #{month}, week: #{week}"



