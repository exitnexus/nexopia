module Core
	class QueryHandler < PageHandler
		declare_handlers("core") {
			area :Public
			access_level :Any

			handle :GetRequest, :query_location, "query", "location"
			
			access_level :Admin, CoreModule, :createbanners
			handle :GetRequest, :query_sublocations, "query", "sublocations"
		}

		def query_location
			query_path = params["name", String, ""]

			$log.info "Query String: #{query_path}", :debug

			parts = query_path.split(",")
			query_path = parts[0]
			modifier = parts[1]

			# Might want to cache this in memory
			locations = Locs.find(:all, :scan, :conditions => ["name LIKE ?", "#{query_path}%"], 
				:order => "name",
				:limit => 30)
			
			# We're using a generated value to sort these, so we can't do it on the database level
			locations.sort! { |a,b| (a.modifier || "") <=> (b.modifier || "") }

			# Now get the matches where the path is in part of the path. Stop right away when we hit 10 (or if we've already hit it).
			if (modifier)
				modifier.strip!
				matches = []
				locations.each { |location|
					break if matches.length >= 10
					matches << location if location.modifier && !location.modifier.downcase.index(modifier.downcase).nil?
				}
				locations = matches
			end

			export_xml(request, locations)
		end


		def query_sublocations
			location_id = params["id", Integer, nil]
			
			location = Locs.find :first, location_id
			if (location.type == "C" || location.type == "S")
				sublocations = Locs.get_children(location_id)
			else
				# Potentially too large a data set. Just send the location back
				location = Locs.find :first, 75 if location_id == 0 # switch with world if we're using 0 for the id
				sublocations = [location]
			end

			export_xml(request, sublocations)
		end
		
		
		def export_xml(request, locations)
			request.reply.headers['Content-Type'] = PageRequest::MimeType::XMLText

			xml_string = "<?xml version = \"1.0\" encoding=\"UTF-8\" standalone=\"yes\" ?>" + "<locations>"

			locations.each { |location| 
				extra_optional = location.extra ? "<extra>#{location.extra}</extra>" : ""
				query_sublocations = (location.type == "C" || location.type == "S") && Locs.children?(location.id)
				xml_string = xml_string + 
					"<location query_sublocations='#{query_sublocations}'>" +
						"<name>#{location.augmented_name}</name>" + 
						"<id>#{location.id}</id>" +
						extra_optional +
					"</location>"
			}

			xml_string = xml_string + "</locations>"

			puts xml_string			
		end
		private :export_xml
	end
end