lib_require :Core, 'typeid'
lib_want :Worker, 'post_process_queue';

class ChessGame < Storable
	init_storable(:db, "chessgame")
	
	extend TypeID
	
	def ChessGame.do_computer_turn(userid, moves)
		sleep(5);
		str = `echo #{moves} | ../tscp181/chess -m`
		move = str.split("\n")[-1]
		c = ChessGame.find(:first, :conditions => ["userid = ?", userid]);
		if (c == nil)
			c = ChessGame.new();
			c.userid = userid;
		end
		c.moves = "#{moves} #{move}"
		c.ready = true;
		c.store();
		
	end
	
	def get_board
		`echo #{@moves} | ../tscp181/chess -d`
	end
	
	def submit_move(move)
		self.moves = "#{moves} #{move}"
		self.store();
		if (site_module_loaded?(:Worker))
			WorkerModule.do_task(ChessGame, "do_computer_turn", [self.userid, self.moves]);
		end
	end

end
