lib_require :Modqueue, 'queue';
lib_require :Core, 'template/template'
require 'erb'
require 'stringio'

module Modqueue
	class TestQueueHandler < PageHandler
	
		declare_handlers("moderate") {
			area :Public
			access_level :LoggedIn
			page :GetRequest, :Full, :moderate, input(String)
			page :GetRequest, :Full, :generate_items, input(String), 'generate'
			page :GetRequest, :Full, :list_queues, 'list'
			page :PostRequest, :Full, :vote, input(String), 'vote'
		}
	
		def list_queues
	
			Queue.list.each{|queue|
				puts "<a href='/moderate/#{queue.moderate_queue_name.to_s}'>
					#{queue.moderate_queue_name.to_s}</a><br/>";
			};
		end
	
		def generate_items(typeid_str)
			queue = get_queue(typeid_str)
			(1...50).each{
				queue.create_random();
			}
		end
	
		def get_queue(typeid_str)
			Queue.new(TypeIDItem.get_by_name(typeid_str));
		end
	
		def moderate(typeid_str)
			queue = get_queue(typeid_str);
			t = Template::instance("modqueue", "testqueue");
			t.action = "/moderate/#{typeid_str}/vote"
			t.items = [*queue.get_items(35)];
			puts t.display();
		end
	
		def vote(typeid_str)
			queue = get_queue(typeid_str);
			votes = Array.new;
			params.each{ |key|
				value = params[key, String, ""];
				if (value == "Yes" or value == "No")
					votes << [key, value];
				end
			}
			queue.vote(votes);
			PageHandler.current.rewrite(:GetRequest, "/moderate/#{typeid_str}");
	
		end
	
	end
end