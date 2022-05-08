lib_require :Forums, 'category', 'thread', 'forum', 'post';

module ForumHelper
        
        PageControl = Struct.new("PageControl", :page_link_list, :page_jump_list, :current_page);
        PageLink = Struct.new("PageLink", :enable, :label, :selected);
        PageJump = Struct.new("PageJump", :enable, :label, :value);

        def createPageViews(t, page, total_rows, page_count)
            # Examples Pages:
            #   1 2 3 4 [5] 6 7 .. 10
            #   1 .. 4 5 [6] 7 8 9 10
            #   1 .. 18 19 [20] 21 22 .. 60
            #
            # Jump:
            #   Only Show when at least one of them is available
            #   Link disabled when not reachable
            #   -100 -10 +10 +100
            #
            # Testing:
            #   total_rows = 200
            #   page      = 50
            #   page_count = 1

            t.pageViews = true;
            
            page_views_total = ( (total_rows / page_count.to_f).ceil );

            page += 1; # Convert to visual index style, instead of array index style.

            #################
            # Pages
            ###############################
            rangeNumbersValues = Array.new(); # [enable_link, label, display_[]_around_label]

            if (page > 1)
                if (page < 6)
                    for thisPage in ( 1 .. page - 1 )
                        rangeNumbersValues << [ true, "#{thisPage}", 'none' ];
                    end
                else
                    rangeNumbersValues << [ true , "1"            , 'none' ];
                    rangeNumbersValues << [ false, ".."           , 'none' ];
                    rangeNumbersValues << [ true , (page - 2).to_s, 'none' ];
                    rangeNumbersValues << [ true , (page - 1).to_s, 'none' ];
                end
            end

            rangeNumbersValues << [ true, "#{page}", '' ];

            if (page_views_total > page)
                if (page_views_total < page + 5)
                    for thisPage in ( page + 1 .. page_views_total )
                        rangeNumbersValues << [ true, "#{thisPage}", 'none' ];
                    end
                else
                    rangeNumbersValues << [ true , (page + 1).to_s      , 'none' ];
                    rangeNumbersValues << [ true , (page + 2).to_s      , 'none' ];
                    rangeNumbersValues << [ false, ".."                 , 'none' ];
                    rangeNumbersValues << [ true , "#{page_views_total}", 'none' ];
                end
            end

            t.pageViewsLinks = rangeNumbersValues;

            #################
            # Jump
            ###############################

            rangeNumbersJumps  = Array.new(); # [enable_link, label, real_link_value]
            rangeNumbersJumps << [ (page-100 >= 1)               , "-100", page - 100 ];
            rangeNumbersJumps << [ (page-10  >= 1)               , "-10" , page -  10 ];
            rangeNumbersJumps << [ (page+10  <= page_views_total), "+10" , page +  10 ];
            rangeNumbersJumps << [ (page+100 <= page_views_total), "+100", page + 100 ];

            t.pageViewsJumps = rangeNumbersJumps;

            (0...rangeNumbersJumps.length).each {|number| t.jumpViews = true if (rangeNumbersJumps[number][0] == true) }
        end
        
        def create_page_views(t, req_page, total_rows, rows_per_page)
            # Examples Pages:
            #   1 2 3 4 [5] 6 7 .. 10
            #   1 .. 4 5 [6] 7 8 9 10
            #   1 .. 18 19 [20] 21 22 .. 60
            #
            # Jump:
            #   Only Show when at least one of them is available
            #   Link disabled when not reachable
            #   -100 -10 +10 +100
            #
            # Testing:
            #   total_rows = 200
            #   page      = 50
            #   page_count = 1

            #t.page_views = true;
            
            #calculate the total number of pages required to display the number of rows provided
            total_pages = ( (total_rows / rows_per_page.to_f).ceil );
            
            #if the total number of pages is 1, there is no need to have a page controller, so we
            #can just return and have nothing display for paging.
            if(total_pages <= 1)
                #t.page_control = nil;
                return;
            end

            #################
            # Pages
            ###############################
            page_links = Array.new();

            if (req_page > 1)
                if (req_page < 6)
                    for i in ( 1 .. req_page - 1 )
                        page_links << PageLink.new(true, i.to_s, false);
                    end
                else
                    page_links << PageLink.new(true, "1", false);
                    page_links << PageLink.new(false, "..", false);
                    page_links << PageLink.new(true, (req_page -2).to_s, false);
                    page_links << PageLink.new(true, (req_page -1).to_s, false);
                end
            end

            page_links << PageLink.new(false, req_page.to_s, true);

            if (total_pages > req_page)
                if (total_pages < req_page + 5)
                    for i in ( req_page + 1 .. total_pages )
                        page_links << PageLink.new(true, i.to_s, false);
                    end
                else
                    page_links << PageLink.new(true, (req_page+1).to_s, false);
                    page_links << PageLink.new(true, (req_page+2).to_s, false);
                    page_links << PageLink.new(false, "..", false);
                    page_links << PageLink.new(true, total_pages.to_s, false);
                end
            end
            
            #t.page_links = page_links;

            #################
            # Jump
            ###############################

            page_jumps  = Array.new(); # [enable_link, label, real_link_value]
            page_jumps << PageJump.new((page-100 >= 1), "-100", (page - 100));
            page_jumps << PageJump.new((page-10  >= 1), "-10" , (page -  10));
            page_jumps << PageJump.new((page+10  <= page_views_total), "+10" , (page +  10));
            page_jumps << PageJump.new((page+100 <= page_views_total), "+100", (page + 100));

            #t.page_jumps = page_jumps;
            #
            #t.page_control = PageControl.new(page_links, page_jumps, req_page);

            #(0...rangeNumbersJumps.length).each {|number| t.jumpViews = true if (rangeNumbersJumps[number][0] == true) }
        end
end