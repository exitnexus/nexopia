lib_require :Vote, "first_vote", "second_vote", "skin_entry"


module Vote
	class Vote < PageHandler

		FIRST_VOTE  = 1
		SECOND_VOTE = 2

		declare_handlers("vote"){
			access_level :LoggedIn
		
			# page :GetRequest, :Full, :vote
			# page :GetRequest, :Full, :gallery, "gallery", input(Integer)
			# handle :PostRequest, :submit, "submit"
			# page :GetRequest, :Full, :done, "done"
			# page :GetRequest, :Full, :dup, "dup"
			# page :GetRequest, :Full, :vote01_thumbs, "vote01_thumbs"
			# page :GetRequest, :Full, :vote01_full, "vote01_full", input(Integer)
			# page :GetRequest, :Full, :vote02_thumbs, "vote02_thumbs"
			# page :GetRequest, :Full, :vote02_full, "vote02_full", input(Integer)

			# Code for testing on beta. This should not go live, as the first handler allows you to see how everyone voted and
			# the second one allows you to clear all the votes!
			
			# page :GetRequest, :Full, :test_results, "test_results"
			# page :GetRequest, :Full, :test_clear, "test_clear"
		}

		# Code for testing on beta. This should not go live, as the first handler allows you to see how everyone voted and
		# the second one allows you to clear all the votes!
		
		# def test_results
		# 	result = SkinEntry.db.query "SELECT * FROM firstvote"
		# 	result.each{|row|
		# 		user = User.find :first, row['userid'].to_i
		# 		skin = SkinEntry.get_skin row['plusskin'].to_i
		# 		puts row['userid'] + "(" + user.username + "):" + row['plusskin'] + "("+skin.filename + "):" + row['nonplusskin'] + ":" + row['ip'] + "<br/>"
		# 		
		# 	};
		# end
		# 
		# def test_clear
		# 	SkinEntry.db.query "DELETE FROM firstvote"
		# end

		def vote()

			# Check if the person has already voted this round
			round = SkinEntry.round()
			if (round == FIRST_VOTE)
				vote_table = FirstVote
			elsif (round == SECOND_VOTE)
				vote_table = SecondVote
			else
				$log.info "Something's fucked up, we shouldn't be here", :error
			end			
		
			if (round == FIRST_VOTE)
				vote01_thumbs()
			elsif (round == SECOND_VOTE)
				vote02_thumbs()
			else
				$log.info "Something's fucked up, we shouldn't be here", :error
			end
			
			
		end

		def vote01_thumbs()
			t = Template.instance("vote", "vote01_thumbs")

			plus_skins = SkinEntry.plus_skins
			non_plus_skins = SkinEntry.non_plus_skins
			
			# Hacky crap to get around the template system not dealing with unclosed tags.
			t.group1 = plus_skins
			
			puts t.display
		end
		
		def vote01_full(skinid=1)
			t = Template.instance("vote", "vote01_full")
			
			skin = SkinEntry.get_skin(skinid)
			prevskin = skin.skinid - 1
			nextskin = skin.skinid + 1
			
			# Awful, ugly, lazy way to do wraping.  Shame, shame on you.
			if ( skin.plus )
			
				if (prevskin == 0)
					prevskin = 10
				end
			
				if (nextskin == 11)
					nextskin = 1
				end
				
			else
				
				if (prevskin == 12)
					prevskin = 22
				end
			
				if (nextskin == 23)
					nextskin = 13
				end
			end
			
			t.skin = skin
			t.prevskin = prevskin
			t.nextskin = nextskin
			
			puts t.display

		end
		
		def vote02_thumbs()
			t = Template.instance("vote", "vote02_thumbs")

			skins = SkinEntry.second_vote_skins
			# Hacky crap to get around the template system not dealing with unclosed tags.
			t.group1 = skins.slice(0,3)
			t.group2 = skins.slice(3,3)
			
			puts t.display
		end

		def vote02_full(skinid=1)
			t = Template.instance("vote", "vote02_full")
			
			skin = SkinEntry.get_skin_round2(skinid)
			prevskin = skin.round2id - 1
			nextskin = skin.round2id + 1
			
			# Awful, ugly, lazy way to do wraping.  Shame, shame on you.			
			if (prevskin == 0)
				prevskin = 6
			end
			
			if (nextskin == 7)
				nextskin = 1
			end
			
			t.skin = skin
			t.prevskin = prevskin
			t.nextskin = nextskin
			
			puts t.display

		end

		def gallery(skinid=1)
			round = SkinEntry.round()
			
			if (round == FIRST_VOTE)
				vote01_full(skinid)
			elsif (round == SECOND_VOTE)
				vote02_full(skinid)
			else
				$log.info "Something's fucked up, we shouldn't be here", :error
			end
			
		end
				
		def submit()

			round = SkinEntry.round()

			if (round == FIRST_VOTE)
				vote_table = FirstVote
			elsif (round == SECOND_VOTE)
				vote_table = SecondVote
			else
				$log.info "Something's fucked up, we shouldn't be here", :error
			end			

			if (vote_table.can_vote?(request.session.user.userid))

				vote = vote_table.new()

				vote.userid = request.session.user.userid
				vote.ip = PageRequest.current.get_ip_as_int()
				
				if (round == FIRST_VOTE)
					vote.plusskin = params['plus_skin', String].to_i
					vote.nonplusskin = params['non_plus_skin', String].to_i
				else
					vote.skin = params['skin_entry', String].to_i
				end			

				vote.store()

				site_redirect(url/:vote/:done)
				
			else
				site_redirect(url/:vote/:dup)
			end

		end

		# Finish message and redirect to nex.
		def done()
			
			round = SkinEntry.round()
			
			if (round == FIRST_VOTE)
				template = "vote01_finished"
			elsif (round == SECOND_VOTE)
				template = "vote02_finished"
			else
				$log.info "Something's fucked up, we shouldn't be here", :error
			end			
			
			
			t = Template.instance("vote", template);
			puts t.display();

		end # done()
		
		# Message if they've already entred the contest
		def dup()
		
			t = Template.instance("vote", "vote01_novote");
			puts t.display();
			
		end # dup()
		

	end # class
end # module