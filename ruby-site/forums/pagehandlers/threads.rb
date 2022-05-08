lib_require :Forums, 'category', 'thread', 'forum', 'post', 'forumhelper';
#lib_require :Core, 'bbcode/bbcode.ErrorStream', 'bbcode/bbcode.Parser', 'bbcode/bbcode.Scanner';

module Forum

    PollResponse = Struct.new("PollResponse", :id, :response);

    class ForumThreadHelper < PageHandler

        include ForumHelper

        declare_handlers("forums") {
    		area :Public
    		access_level :Any

            page   :GetRequest, :Full, :thread_display, "forum", input(Integer), "thread", input(Integer);
            page   :GetRequest, :Full, :thread_display, "forum", input(Integer), "thread", input(Integer), "page", input(Integer);

			#access_level :LoggedIn

            page   :GetRequest, :Full, :thread_subscribe, "subscribe", "forum", input(Integer), "thread", input(Integer);
            page   :GetRequest, :Full, :thread_subscribe, "subscribe", "forum", input(Integer), "thread", input(Integer), "page", input(Integer);

            page   :GetRequest, :Full, :thread_unsubscribe, "unsubscribe", "forum", input(Integer), "thread", input(Integer);
            page   :GetRequest, :Full, :thread_unsubscribe, "unsubscribe", "forum", input(Integer), "thread", input(Integer), "page", input(Integer);

    		page   :GetRequest, :Full, :display_thread_subscribed, "thread", "subscribed"
    		page   :GetRequest, :Full, :new_thread,      "forum", input(Integer), "thread", "new";
    		page   :GetRequest, :Full, :new_poll,        "forum", input(Integer), "thread", "new", "poll";
    		page   :GetRequest, :Full, :new_reply,       "forum", input(Integer), "thread", input(Integer), "reply";
    		page   :GetRequest, :Full, :new_quote_reply, "forum", input(Integer), "thread", input(Integer), "quote", input(Integer);

    		handle :PostRequest, :create_thread, "forum", input(Integer), "thread", "new", "submit";
    		handle :PostRequest, :create_reply,  "forum", input(Integer), "thread", input(Integer), "reply", "submit";
    		handle :PostRequest, :create_preview, "forum", input(Integer), "preview";
    		page   :PostRequest, :Full, :new_reply_with_preview, "forum", input(Integer), "page", "preview";
        }

        def new_poll(forum_id)
            t = Template.instance("forums", "forums_thread_poll");

            thread_post = params['thread_post_text', String];
            thread_title = params['thread_subject', String];
            thread_subscribe = params['thread_subscribe', String];

            #Since this function is used for non javascript clients for both displaying the
            #initial poll page from the thread creation page and to add additional rows to
            #the poll on the poll page, the poll_response_rows input may be nil since it is
            #not be present on the poll creation page. There is a hidden input called
            #poll_response_row_count on that page, which will contain the number of inputs
            #on that are displayed.
            if(params.has_key?('poll_response_rows'))
                requested_row_count = params['poll_response_rows', Integer];
                requested_row_count -= 1;
            else
                requested_row_count = params['poll_response_row_count', Integer]
            end

            t.poll_question = params['poll_question', String];

            t.poll_display = "";
            t.add_row_button_type = "submit";
            t.poll_rows = Array.new();

            for i in 0 .. (requested_row_count)
                poll_response_id = "poll_response_#{i}";
                poll_response = params[poll_response_id, String];
                temp = PollResponse.new(poll_response_id, poll_response);
                t.poll_rows << temp;
            end
            t.forum_id = forum_id;
            t.thread_title = thread_title;
            t.thread_post = thread_post;
            t.thread_subscribe = thread_subscribe;

            print t.display;
        end

        def create_thread(forum_id)
            post_text = params['post_text', String];
            thread_title = params['thread_subject', String];
            thread_poll = params['thread_poll', String];
            thread_subscribe = params['thread_subscribe', String];

            #$log.info("The thread_title is: #{thread_title}");
            #$log.info("The thread_poll value is: #{thread_poll}");
            #$log.info("The subscribe value is: #{thread_subscribe}");

            poll = nil;

            #polls are not complete as of Jan 18, 2007. Code should be close to consistent with final version.
            if(thread_poll != nil)
                poll_responses = Array.new();
                requested_row_count = params['poll_response_rows', Integer];
                if(requested_row_count != nil || params.has_key?('poll_add_response'))
                    out = StringIO.new();
                    subrequest(out, :PostRequest, "/forums/forum/#{forum_id}/thread/new/poll/", request.params.to_hash());
                    print out.string;
                    return;
                else
                    poll_question = params['poll_question', String];
                    #poll = Poll.new();
                    #poll.question = poll_question;
                    #poll.date = Date.now.to_i();
                    #poll.store();

                    params.each_match(/poll_response_[0-9]/, String) {|match,value|
                                                                        poll_responses << value};
                    poll_responses.each {|response|
                                        #poll_ans = PollAnswer.new();
                                        #poll_ans.pollid = poll.id;
                                        #poll_ans.answer = response;
                                        #poll_ans.store();
                                        }
                    #thread.pollid = poll.id;
                end
            end

            thread = Thread.new();
            post = Post.new();

            if(poll != nil)
                thread.pollid = poll.id;
            end

            #$log.info("I don't want to see this on my first click");

            thread.id = Thread.get_seq_id(forum_id);
            thread.forumid = forum_id;
            thread.title = thread_title;
            thread.time =  Time.now.to_i;
            thread.authorid = request.session.user.userid;

            post.id = Post.get_seq_id(forum_id);
            post.forumid = forum_id;
            post.authorid = request.session.user.userid;

            post.msg = post_text;
            post.time = thread.time;

            thread.store();

            if(thread.id)
                post.threadid = thread.id;
                post.store();
            end

            if(thread_subscribe != nil)
                #call thread subscription function
            end

            thread_read_by_user = ForumRead.new();
            thread_read_by_user.userid = thread.authorid;
            thread_read_by_user.threadid = thread.id;
            thread_read_by_user.time = thread.time;
            thread_read_by_user.readtime = thread.time;

            thread_read_by_user.store();

            #redirect the user to the appropriate spot. If they have their preference
            #set to jump to the last post we will enter the thread and display the last post.
            #Otherwise it will jump out to the thread list.
            if(request.session.user.forumjumplastpost)
                site_redirect("/forums/forum/#{forum_id}/thread/#{thread.id}/page/last#end");
            else
                site_redirect("/forums/forum/#{forum_id}/");
            end
        end

        def new_thread(forum_id)
            t = Template.instance("forums", "forums_new_thread");
            t.forum_id = forum_id;
            t.show_new_thread_fields = true;
            t.post_render_show = false;

            t.poll_question = "";
            t.poll_display = "none";
            t.add_row_button_type = "button";
            t.poll_rows = Array.new();

            for i in 0 .. 3
                poll_response_id = "poll_response_#{i}";
                temp = PollResponse.new(poll_response_id, "");
                t.poll_rows << temp;
            end

            puts t.display;
        end

        def new_reply(forum_id, thread_id)

            #if(Thread.find(forum_id, thread_id, :first, :selection => :thread_exist).nil?)
            #   t_err = Template.instance("forums", "forums_error");
            #   if(Forum.find(forum_id, :first, :selection => :forum_exist).nil?)
            #
            #   end
            #   t_err.error_text = "The thread #{thread_id} does not exist."
            #
            #   print t_err.display;
            #   return;
            #end

            t = Template.instance("forums", "forums_new_reply");
            t.forum_id = forum_id;
            t.thread_id = thread_id;
            t.show_new_thread_fields = false;
            t.post_render_show = false;

            print t.display;
        end

        def new_quote_reply(forum_id, thread_id, post_id)
            t = Template.instance("forums", "forums_new_reply");
            t.forum_id = forum_id;
            t.thread_id = thread_id;
            t.show_new_thread_fields = false;
            t.post_render_show = false;
            quoted_post = Post.find(:first, :conditions => ["forumid = ? AND threadid = ? AND id = ?", forum_id, thread_id, post_id]);

            quote_text = "[quote][i]Originally posted by: [b]#{quoted_post.author.username}[/b][/i] \n#{quoted_post.msg}[/quote]";
            t.post_text = quote_text;
            print t.display;
        end

        #This function will attempt to add a reply to the desired thread.
        def create_reply(forum_id, thread_id)
            post_text = params['post_text', String];
            thread_subscribe = params['thread_subscribe', String];

            #if(Thread.find(forum_id, thread_id, :first, :selection => :thread_exist).nil?)
            #   site_redirect("/forums/");
            #   return;
            #end

            if(params.has_key?('Preview'))
                #$log.info("I'm in the Preview if statement");
                out = StringIO.new();
                subrequest(out, :PostRequest, "/forums/forum/#{forum_id}/page/preview/", request.params.to_hash());
                print out.string;
                return;
            end

            post = Post.new();

            post.id = Post.get_seq_id(forum_id);
            post.forumid = forum_id;
            post.authorid = request.session.user.userid;
            post.threadid = thread_id;
            post.msg = post_text;
            post.time = Time.now.to_i;

            post.store();

            #Update number of replies
            thread = Thread.find(:first, :conditions => ["forumid = ? AND id = ?", forum_id, thread_id ]);
            thread.posts += 1;
            thread.store();

            #Update the forumread table. The else should almost never be executed since it would
            #require the user to go directly to the reply page without ever reading the thread.
            #A user may attempt this however, as such we will create an entry in the forumread table.
            thread_read_by_user = ForumRead.find(:first, :conditions => ["userid = ? AND threadid = ?", request.session.user.userid, thread_id]);
            if(thread_read_by_user != nil)
                thread_read_by_user.time = post.time;
            else
                thread_read_by_user = ForumRead.new();
                thread_read_by_user.userid = request.session.user.userid;
                thread_read_by_user.threadid = thread_id;
                thread_read_by_user.time = post.time;
                thread_read_by_user.readtime = post.time;
                thread_read_by_user.posts = thread.posts;
            end
            thread_read_by_user.store();

            #redirect based on user preferences
            if(request.session.user.forumjumplastpost)
                site_redirect("/forums/forum/#{forum_id}/thread/#{thread_id}/page/last#end");
            else
                site_redirect("/forums/forum/#{forum_id}");
            end
        end

        def create_preview(forum_id)
            post_text = params['post_text', String];
            thread_subject = params['thread_subject', String];

            params.each_key(){|key1| $log.info("#{key1} is present")};

            t = Template.instance("forums", "forums_post_preview");

            if(thread_subject != nil)
                t.thread_subject = thread_subject;
                t.thread_subject_present = true;
            else
                #$log.info("The thread_subject is nil!!!!");
                t.thread_subject_present = false;
            end

            reply.headers['Content-Type'] = PageRequest::MimeType::XHTML;

            e_str = BBCode::ErrorStream.new();
            bb_scan = BBCode::Scanner.new();
            bb_scan.InitFromStr(post_text, e_str);
            bb_parser = BBCode::Parser.new(bb_scan);

            t.post_render = bb_parser.Parse();

            print t.display;
        end

        def new_reply_with_preview(forum_id)
            t = Template.instance("forums", "forums_new_reply");

            if(params.has_key?('thread_subscribe'))
                t.thead_subscribe = "checked";
            end
            #$log.info("************************ I'm in the new_reply_with_preview *****************");
            post_text = params['post_text', String];
            thread_id = params['thread_id', String];
            t.post_text = post_text;
            t.forum_id = forum_id;
            t.thread_id = thread_id;
            t.show_new_thread_fields = false;
            t.post_render_show = true;

            out = StringIO.new();
            subrequest(out, :PostRequest, "/forums/forum/#{forum_id}/preview/", request.params.to_hash());
            t.post_render =  out.string;
            print t.display;
        end

        def new_thread_with_preview(forum_id)
            t = Template.instance("forums", "forums_new_thread");
            t.forum_id = forum_id;
            t.show_new_thread_fields = true;
            t.post_render_show = false;

            t.poll_question = "";
            t.poll_display = "none";
            t.add_row_button_type = "button";
            t.poll_rows = Array.new();

            for i in 0 .. 3
                poll_response_id = "poll_response_#{i}";
                temp = PollResponse.new(poll_response_id, "");
                t.poll_rows << temp;
            end

            print t.display;
        end

        def thread_subscribe(forum_id, thread_id, page = nil, subscription_status = true)
            timeNow  = Time.new.to_i;

            the_thread = ForumRead.find(:first, :conditions => ["userid = # && threadid = ?", request.session.user.userid, thread_id])

            the_thread.subscribe = subscription_status;
            the_thread.time      = timeNow;
            the_thread.readtime  = timeNow;
            the_thread.store();

            redirect_url  = "/forums/forum/#{forum_id}/thread/#{thread_id}";
            redirect_url += "/page/#{page}" if (page)

            site_redirect(redirect_url);
        end

        def thread_unsubscribe(forum_id, thread_id, page = nil)
            thread_subscribe(forum_id, thread_id, page, false);
        end

        def display_thread_subscribed
            t = Template.instance("forums", "thread_subscribed");

            forum_read_all = ForumRead.find(:all, :conditions => ["userid = # && subscribe = 'y'", request.session.user.userid])

            threads = OrderedMap.new();

            forum_read_all.each { |forum_read|
                                threads << Thread.find(:first, :conditions => ["id = ?", forum_read.threadid ]);
                                };

            t.threads = threads;
            puts t.display();
        end

        def thread_display(forum_id, thread_id, page = nil )
    		t = Template.instance("forums", "forums_thread_front");
            t.logged = request.session;

            t.posttypeid = Post.typeid;

            thread   = Thread.find(:first, :conditions => ["forumid = ? AND id = ?", forum_id, thread_id ]);
            forum    = Forum.find(:first, thread.forumid);
    		category = Category::list(forum.categoryid);

            t.category_id    = category.id;
            t.category_name  = category.name;
            t.forum_id       = forum.id;
            t.forum_name     = forum.name;
            t.forum_official = forum.official;
            t.thread_id      = thread.id;
            t.thread_title   = thread.title;

            was_read   = (request.session.anonymous?())? nil : ForumRead.find(:first, :conditions => ["userid = # && threadid = ?", request.session.user.userid, thread_id])
           	page_count = (request.session.anonymous?())? 10 : request.session.user.forumpostsperpage
            total_rows = 0;

            # The output will be as array index style
            case page
                when nil
                    # Check on ForumRead for the last post the user read
                    page = (was_read == nil)? 0 : (was_read.posts / page_count.to_f).ceil - 1
                when "last"
                    # Retreives the number of the last page available
            		thread.posts_list(0, 1, []).each {|post| total_rows = post.total_rows }

            		page = (total_rows / page_count.to_f).ceil - 1;
                else
                    # Converts from visual index style to array index style
                    page = (page.to_i > 0) ? page.to_i - 1 : 0;
            end

            t.page = page + 1; # Receives visual index style

            posts_list = Post.find(:all, :total_rows, :offset => page * page_count, :limit => page_count, :conditions => ["forumid = ? AND threadid = ?", forum_id, thread_id]);
            total_rows = posts_list.length;

             if (total_rows > 0)
#               ForumHelper.createPageViews(t, page, total_rows, page_count);

                t.posts_list = posts_list;

                # Finds out what was the last post the user read
                page_views_total  = ( (total_rows / page_count.to_f).ceil );
                last_post_on_page = (page + 1 == page_views_total)? total_rows : (page + 1) * page_count

                # Insert / Update ForumRead
                if (!request.session.anonymous())
                    timeNow = Time.new.to_i;

                    if (was_read == nil)
                        was_read           = ForumRead.new();
                        was_read.userid    = request.session.user.userid;
                        was_read.threadid  = thread_id;
                        was_read.subscribe = false;
                        was_read.posts     = last_post_on_page;
                        was_read.store();

                        thread.reads += 1;
                    else
                        was_read.posts    = [was_read.posts, last_post_on_page].max;
                    end

                    was_read.time     = timeNow;
                    was_read.readtime = timeNow;
                    was_read.store();
                end
            end

            t.subscribe = was_read.subscribe if (was_read)

    		puts t.display();
    	end
    end
end
