lib_require :Chess, "chess_game"

class ChessHandler < PageHandler
	declare_handlers("chess") {
		area :Self

		page :GetRequest, :Full, :index
		page :GetRequest, :Full, :submit_move, "submit_move"
	}
	
	def submit_move(*args)
		game = ChessGame.find(:first, :conditions => ["userid = #", request.session.user.userid]);
		str = params['move', String];
		wait_object = game.submit_move(str);
		index(*args);
=begin
		puts "<div style='background-color:#FFFFFF'>"
		puts "Past moves: #{game.moves}";
		puts "<script>"
		puts <<EOF
function changeBG(e, color){
	var targ;
	if (!e) var e = window.event;
	if (e.target) targ = e.target;
	else if (e.srcElement) targ = e.srcElement;
	if (targ.nodeType == 3) // defeat Safari bug
		targ = targ.parentNode;
	targ.style.backgroundColor = color;
}
EOF
		puts "</script>"
		str = game.get_board();
		puts "<table>"
		str.split("\n").each{|line|
			puts "<tr>"
				line.each_char{|c|
					puts <<EOF
<td id="#{}" style='width: 32px; border: 1px solid;' onmouseover='changeBG(event, "#FF0000")' 
onmouseout='changeBG(event, "#FFFFFF")' 
onclick=''>#{""<<c}</td>
EOF
				}
			puts "</tr>"
		}
		puts "</table>"
		puts "</div>"
=end

	end
	
	def index(*args)
		game = ChessGame.find(:first, :refresh, :conditions => ["userid = #", request.session.user.userid]);
		if (game == nil)
			game = ChessGame.new()
			game.userid = request.session.user.userid;
			game.moves = "";
			game.store;
		end
		
		puts "<div style='background-color:#FFFFFF'>"
		puts "Past moves: #{game.moves}";
		puts "<script>"
		puts <<EOF
function changeBG(e, color){
	var targ;
	if (!e) var e = window.event;
	if (e.target) targ = e.target;
	else if (e.srcElement) targ = e.srcElement;
	if (targ.nodeType == 3) // defeat Safari bug
		targ = targ.parentNode;
	targ.style.backgroundColor = color;
}
EOF
		puts "</script>"
		str = game.get_board();
		board = str[-64..-1];
		puts "<table>"
		(0...8).each{|column|
			puts "<tr><td style='width:24px;'>#{8-column}</td>"
				line = board[(column*8)...((column+1)*8)]
				(0...8).each{|row|
					c = line[row..row];
					color = ((row+column)%2 == 0)?"#000000":"#FFFFFF";
					#fgcolor = ((row+column)%2 == 0)?"#FFFFF":"#000000";
					puts <<EOF
<td id="#{}"  style='width: 24px; border: 1px solid; color:#777777; background-color: #{color};' onmouseover='changeBG(event, "#FF0000")' 
onmouseout='changeBG(event, "#{color}")' 
onclick=''>#{c}</td>
EOF
				}
			puts "</tr>"
		}
		puts "<tr>";
		" abcdefgh".each_char{|c|
			puts "<td>#{""<<c}</td>"
		}
		puts "</tr>"
		puts "</table>"
		puts "<form action='/my/chess/submit_move' method='GET'>"
		puts "<input type='text' name='move'></input><input type='submit' value='Submit move'></input>"
		puts "</form>"
		puts "</div>"
		
	end
	
end
