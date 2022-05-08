lib_require :search, "usersearch"


categories = { 'Celebrity' => "Clubbing
Current Affairs
Dancing
Drinking
Partying
Reading
Shopping
Traveling
Acting
Clothing design
DJing
Photography
Singing
Surfing the net
Teen
Hip-Hop
Indie
Pop
R & B
Rap
Going to the beach
Suntanning
Magazines
Aerobics
Basketball
Baseball
Football
Golf
Hockey
Pilates
Skateboarding
Snowboarding
Soccer
Listening to music
Volunteering",

'Music' => "Clubbing
Dancing
Drinking
Karaoke
Listening to music
Partying
Raving
Reading
DJing
Singing
Song Writing
Writing
Audio
Surfing the net
Musicals
Teen
Acoustic
Alternative
Bachata
Blues
Breakbeat
Brit Pop
Classic Rock
Classical
Country
Death Metal
Drum & Bass
Electronica
Emo
Folk
Funk
Garage
Gospel
Goth
Happy Hardcore
Hardcore
Hip-Hop
House
Indie
Industrial
Jazz
Lounge
Merengue
Metal
Pop
Progressive
Punk
R & B
Rap
Rapcore
Rave
Reggae
Reggaeton
Rock
Salsa
Ska
Soul
Techno
Trance
World
Acoustic guitar
Bass guitar
Electric Guita
Flute
Keyboard
Kit Drums
Saxophone
Trumpet
Violin
Piano
Magazines
Other Drums",

'Movies' => "Acting
Film/Video Making
Photography
Theatre Directing
Writing
Surfing the net
Action
Animated
Anime
Classic
Comedy
Documentaries
Drama
Foreign
Historical dramas
Horror
Independent
Musicals
Psychological Thrillers
Romantic Comedies
Science Fiction
Silent
Spy/Political Thrillers
Tearjerkers
Teen
Westerns
Comic books
Fantasy
Magazines",

"Television" => "Current Affairs
Acting
Film/Video Making
Photography
Theatre Directing
Writing
Surfing the net
Action
Animated
Anime
Classic
Comedy
Documentaries
Drama
Foreign
Historical dramas
Horror
Independent
Musicals
Psychological Thrillers
Romantic Comedies
Science Fiction
Silent
Spy/Political Thrillers
Tearjerkers
Teen
Westerns
Comic books
Fantasy
Magazines
Cooking
Traveling",

'Games' => "Gambling
Poker
Pool/Billiards
Cartooning
Graphic Design
Web Design
Gaming
Graphics
Surfing the net
Animated
Teen
Comic books
Fantasy
Fiction
Magazines
Sci-fi
Baseball
Basketball
Bowling
Boxing
Football
Golf
Hockey
Martial Arts
Paintball
Skateboarding
Snowboarding
Soccer
Tennis
Wrestling
Fighting
First person shooter
Puzzles
Racing
Role Playing
Simulations
Sports
Strategy
Drifting
Nascar",

"News" => "Current Affairs
Reading
Religion/Spirituality
Traveling
Volunteering
Writing
Surfing the net
Documentaries
Foreign
Historical dramas
Teen
Classical
Sightseeing
Magazines
Newspapers
Photography",

"Speak Up" => "Current Affairs
Reading
Religion/Spirituality
Traveling
Volunteering
Film/Video Making
Journal Writing
Writing
Surfing the net
Documentaries
Teen
Sightseeing
Magazines
Newspapers",

"Xtreme" => "Partying
Body Art
Motorbikes
Offroad
Surfing the net
Teen
Alternative
Classic Rock
Metal
Punk
Rap
Rock
Backpacking
Camping
Hiking
Magazines
BMX
Boxing
Car racing
Inline Skating
Kickboxing
Motocross
Mountain Biking
Rock Climbing
Skateboarding
Skiing
Sky Diving
Snowboarding
Surfing
Wakeboarding
Water-skiing",

"Automotive" => "Driving
Audio
Car Clubs
Classics
Domestic
Drag Racing
Drifting
Formula 1
Imports
Modifications
Motorbikes
Nascar
Offroad
Rally
Tuning
Surfing the net
Teen
Classic Rock
Magazines
Car racing
Motocross
Snowmobiling
Racing",

"Sporting" => "Darts
Pool/Billiards
Nascar
Formula 1
Rally
Surfing the net
Teen
Backpacking
Camping
Exploring
Fishing
Hiking
Hunting
Orienteering
Sightseeing
Magazines
Aerobics
BMX
Badminton
Baseball
Basketball
Bicycling
Body Building
Bowling
Boxing
Car racing
Cheerleading
Cricket
Curling
Dance
Fencing
Field Hockey
Figure Skating
Football
Golf
Gymnastics
Handball
Hockey
Horseback Riding
Inline Skating
Jogging
Kayaking
Kickboxing
Lacrosse
Martial Arts
Motocross
Mountain Biking
Paintball
Pilates
Ringette
Rock Climbing
Rollerskating
Rowing
Rugby
Running
Sailing
Scuba
Skateboarding
Skiing
Sky Diving
Snorkeling
Snowboarding
Snowmobiling
Soccer
Softball
Surfing
Swimming
Tennis
Track and Field
Ultimate Frisbee
Volleyball
Wakeboarding
Water-skiing
Weight lifting
Windsurfing
Wrestling
Yoga
Sports" }



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

all_ids = []

categories.each {|key, val|
	interest_ids = val.split("\n").map{|name| Interests.get_id_by_name(name) }.compact

	all_ids << interest_ids

	total, month, week = run_search(interest_ids)
	puts "#{key.rjust(15)} => total: #{total}, month: #{month}, week: #{week}"
}

total, month, week = run_search(all_ids.flatten.uniq)
puts "#{'Total Interests'.rjust(15)} => total: #{total}, month: #{month}, week: #{week}"

total, month, week = run_search(0)
puts "#{'Everyone'.rjust(15)} => total: #{total}, month: #{month}, week: #{week}"



