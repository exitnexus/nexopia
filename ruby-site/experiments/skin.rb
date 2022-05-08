#!/usr/bin/env ruby

require 'menu'
require 'blocks'
require 'auth'

$userData = $anon;
if ($session != nil)
    $userData = $session.get_user();
end


class Skin
    def initialize
        @skinWidth="100%";
        @sideWidth=130;
        @cellspacing="12";
        @incCenter=true;
        @blockBorder=2;

        @topBorderSize=0;
        @topBorder="";
        @leftBorderSize=0;
        @leftBorder="";
        @rightBorderSize=0;
        @rightBorder="";
        @bottomBorderSize=0;
        @bottomBorder="";

        @backgroundpic = "";


        @headerpic = "headersmall.jpg";
        @headerheight = "84";

        @menupic = "#606060";
        @menuheight = "19";
        @menudivider = " | ";
        @menuspacer = "#000000";
        @menuspacersize = 2;
        @mainbg = "";

        @banneroffset = "3";
        @bannerborder = "2";
        @bannerbordercolor = "#AAAAAA";
        @bannervalign = "top";

        @floatinglogo = "logo.gif";
        @floatinglogovalign = "bottom";

        @blockheadpic = "blockhead.gif";
        @blockheadpicsize = "21";
        @rightblocks = [];
    end
    attr :skinWidth;
    attr :sideWidth;
    attr :cellspacing;
    attr :incCenter;
    attr :blockBorder;

    attr :topBorderSize;
    attr :topBorder;
    attr :leftBorderSize;
    attr :leftBorder;
    attr :rightBorderSize;
    attr :rightBorder;
    attr :bottomBorderSize;
    attr :bottomBorder;

    attr :backgroundpic;


    attr :headerpic;
    attr :headerheight;

    attr :menupic;
    attr :menuheight;
    attr :menudivider;
    attr :menuspacer;
    attr :menuspacersize;
    attr :mainbg;

    attr :banneroffset;
    attr :bannerborder;
    attr :bannerbordercolor;
    attr :bannervalign;

    attr :floatinglogo;
    attr :floatinglogovalign;

    attr :blockheadpic;
    attr :blockheadpicsize;
    attr :menuends;
    attr :rightblocks, true;
end

$skindata = Skin.new();
$skinloc = "http://localhost/site_data/skins/newsite/";


def makeCheckBox(*args)
    return "Check Box!";
end

def empty(d)
    return true;
end

def implode(a, b)
    str = "";
    for text in b
        str += text + a;
    end
    return str;

end

def getMenuMain()
    $menus['main'].items;
end

def getMenuPersonal()
    $menus['personal'].items;
end

def getMenuBottom()
    $menus['bottom'].items;
end

def getMenuManage()
    $menus['manage'].items;
end

def array()
    return Array[];
end

def blocks(a)
    return "Blocks."
end

def openCenter(width = true)
    output = "";
    output += "<table cellpadding=3 cellspacing=0 width=" + (width == true ? "100%" : "width align=center" ) + " style=\"border-collapse: collapse\" border=1 bordercolor=#000000>";
    output += "<tr><td class=body>";
    return output;
end

def closeCenter()
    return "</td></tr></table>";
end

def openBlock(header,side)

    timeline("- header");
    output = "";
    output += "<tr><td align=center width=" + $skindata.sideWidth.to_s + ">";
    output += "<table cellpadding=0 cellspacing=0 border=0 width=100%>";
    output += "<tr><td colspan=3 background=" + $skinloc + (side=='l' ? "left" : "right") + "" + $skindata.blockheadpic + " height=" + $skindata.blockheadpicsize + " class=sideheader valign=bottom align=" + (side=='l' ? "left" : "right") + ">&nbsp;&nbsp;<b>header</b>&nbsp;&nbsp;</td></tr>";
    output += "<tr>";
    if($skindata.blockBorder > 0)
        output += "<td width=" + $skindata.blockBorder.to_s + " class=border></td>";
    end

    output += "<td class=side valign=top width=" + ($skindata.sideWidth - 2*$skindata.blockBorder).to_s + ">";
    return output;
end

def closeBlock()
    output = "";
    output += "</td>";
    if($skindata.blockBorder>0)
        output += "<td width=" + $skindata.blockBorder.to_s + " class=border></td>";
    end
    output += "</tr>";
    if($skindata.blockBorder>0)
        output += "<tr><td colspan=3 height=" + $skindata.blockBorder.to_s + " class=border></td></tr>";
    end
    output += "</table>";
    output += "</td></tr>";
    output += "<tr><td height=" + $skindata.cellspacing.to_s + "></td></tr>\n";
    return output;
end

def createHeader()
    return "Report this if you see it+";
end #exists purely so a race condition at login time doesn't put stuff in the error log. It doesn't get used anyway.

def timeline(str)
end

def incHeader(incCenter=true, incLeftBlocks=false, incRightBlocks=false)
    output = "";
#    $skindata.incCenter = incCenter;
    $skindata.rightblocks = incRightBlocks;

#    $skindata.admin=false;
#    if($session != nil=='y')
#        $skindata.admin = mods.isAdmin($userData.userid);
#    end

    rows = 5;
    if($session != nil) #user menu
        rows+=1;
    end

#    if($skindata.admin) #admin menu
#        rows+=1;
#    end

    output += "<html><head><title>config[title]</title><script src=config[jsloc]general+js?rev=reporev></script>\n";
    output += "<link rel=stylesheet href='" + $skinloc + "default.css'>\n";
#    if(_SERVER['PHP_SELF'] == "/index+php")
#            output += "<meta name=\"description\" content=\"config[metadescription]\">\n";
#            output += "<meta name=\"keywords\" content=\"config[metakeywords]\">\n";
#    end

    output += "</head>\n";
    output += "<body " + ($skindata.backgroundpic ? "background='" + $skinloc + $skindata.backgroundpic + "' " : "" ) + "onLoad='init()'>\n";

    output += "<table cellspacing=0 cellpadding=0 width=" + $skindata.skinWidth + "" + ($skindata.skinWidth == '100%' ? "" : " align=center") + ">";

    if($skindata.topBorderSize > 0)
        colspan = 1;
        if($skindata.leftBorderSize > 0)
            colspan+=1;
        end
        if($skindata.rightBorderSize > 0)
            colspan+=1;
        end
        if($skindata.topBorder[0,1]=="#")
            output += "<tr><td height=" + $skindata.topBorderSize.to_s + " bgcolor=" + $skindata.topBorder + " colspan=colspan></td></tr>";
        else
            output += "<tr><td height=" + $skindata.topBorderSize.to_s + " background='" +skinloc + $skindata.topBorder + "' colspan=colspan></td></tr>";
        end
    end

    output += "<tr>";

    if($skindata.leftBorderSize > 0)
        if($skindata.leftBorder[0,1]=="#")
            output += "<td rowspan=" + rows.to_s + " width=" + $skindata.leftBorderSize.to_s + " bgcolor=" + $skindata.leftBorder + "></td>";
        else
            output += "<td rowspan=" + rows.to_s + " width=" + $skindata.leftBorderSize.to_s + " background='" + $skinloc + $skindata.leftBorder + "'></td>";
        end
    end

    output += "<td bgcolor=#000000 background='" + $skinloc  + $skindata.headerpic + "' align=right height=" + $skindata.headerheight + " valign=" + $skindata.bannervalign + ">";
        if($skindata.floatinglogo!="")
            output += "<img src='" + $skinloc  + $skindata.floatinglogo + "' align=" + $skindata.floatinglogovalign + ">";
        end

        bannertext = "BANNER_HERE";#banner.getbanner(BANNER_BANNER);#'468x60');
    output += "</td>";

    if($skindata.rightBorderSize > 0)
        if($skindata.rightBorder[0,1]=="#")
            output += "<td rowspan=" + rows.to_s + " width=" + $skindata.rightBorderSize.to_s + " bgcolor=" + $skindata.rightBorder + "></td>";
        else
            output += "<td rowspan=" + rows.to_s + " width=" + $skindata.rightBorderSize.to_s + " background='" + $skinloc  + $skindata.rightBorder + "'></td>";
        end
    end

    output += "</tr>\n";

    output += "<tr>";
    if($skindata.menupic[0,1]=="#")
        output += "<td height=" + $skindata.menuheight + " bgcolor=" + $skindata.menupic + ">";
    else
        output += "<td height=" + $skindata.menuheight + " background='" + $skinloc  + $skindata.menupic + "'>";
    end


#start menu
    output += "<table cellspacing=0 cellpadding=0 width=100%><tr>";
    if(!empty($skindata.menuends))
        output += "<td class=menu align=left width=1><img src='" + $skinloc + "left" + $skindata.menuends + "'></td>";
    end

    output += "<td class=menu align=left>&nbsp;&nbsp;&nbsp;&nbsp;";


    menu = array();
    for item in getMenuMain()
        menu.push "<a href='#{item.addr}'" + (item.target ? "" : " target='" + item.target.to_s + "'" ) + ">" + item.name + "</a>";
    end

    output += implode($skindata.menudivider,menu);

    output += "</td><td class=menu align=right>";
    output += "Online: ";
    if($session != nil)
        output += "<a href=/friends+php>Friends " + $userData.friendsonline.to_s + "</a> | ";
    end
    output += "<a href='/profile+php?requestType=onlineByPrefs'>Users siteStats[onlineusers]</a> | Guests siteStats[onlineguests] &nbsp;";

    output += "</td>";
    if(!empty($skindata.menuends))
        output += "<td class=menu align=right width=1><img src='" + $skinloc  + "right" + $skindata.menuends + "'></td>";
    end
    output += "</tr></table>";

    #end menu
    output += "</td>";
    output += "</tr>\n";

#personal menu
    if($session != nil)
        if($skindata.menuspacersize > 0)
            if($skindata.menuspacer[0,1]=="#")
                output += "<tr><td height=" + $skindata.menuspacersize.to_s + " bgcolor=" + $skindata.menuspacer + "></td></tr>";
            else
                output += "<tr><td height=" + $skindata.menuspacersize.to_s + " background='" + $skinloc  + $skindata.menuspacer + "'></td></tr>";
            end

        end

        output += "<tr>";
        if($skindata.menupic[0,1]=="#")
            output += "<td height=" + $skindata.menuheight + " bgcolor=" + $skindata.menupic + ">";
        else
            output += "<td height=" + $skindata.menuheight + " background='" + $skinloc  + $skindata.menupic + "'>";
        end


#start menu
        output += "<table cellspacing=0 cellpadding=0 width=100%><tr>";
        if(!empty($skindata.menuends))
                output += "<td class=menu align=left width=1><img src='" + $skinloc  + "left" + $skindata.menuends + "'></td>";
        end
        output += "<td class=menu align=left>&nbsp;&nbsp;&nbsp;&nbsp;";

        menu = array();
        for item in getMenuPersonal()
                menu.push "<a href='item.addr'" + (item.target ? "" : " target='" + item.target.to_s + "'" ) + ">" + item.name + "</a>";
        end

        output += implode($skindata.menudivider,menu);

        output += "</td><td class=menu align=right>";

        output += "<a href='/messages+php'>Messages</a><a href=/messages+php?action=viewnew> " + $userData.newmsgs.to_s + " New</a>"; #&k=" + makekey('newmsgs') + "

        #userblog = new userblog(weblog, $userData.userid);
        #newreplies = userblog.getNewReplyCountTotal();
        #ending = (newreplies==1? 'y' : 'ies');
        #output += " | <a href='/weblog+php?uid=" + $userData.userid + "'>Blog</a>";
        #if(newreplies)
        #        output += " <a href='/weblog+php?newreplies=1'>newreplies Replending</a>";
        #end


        if($userData.enablecomments == 'y')
                output += " | <a href='/usercomments+php'>Comments " + $userData.newcomments.to_s + "</a>";
        end


        output += " &nbsp;";

        output += "</td>";
        if(!empty($skindata.menuends))
                output += "<td class=menu align=right width=1><img src='" + $skinloc  + "right" + $skindata.menuends + "'></td>";
        end

        output += "</tr></table>";
#end menu
        output += "</td>";
        output += "</tr>\n";
    end
#end personal menu

    output += "<tr>";
    output += "<td class=header2" +
        ($skindata.mainbg == "" ? "" : ($skindata.mainbg == '#' ? "" : " background='" + $skinloc + $skindata.mainbg + "'")) + ">";
        # bgcolor=" + $skindata.mainbg + ", is the correct way, but skins don't expect it+
    output += "<table cellpadding=0 cellspacing=" + $skindata.cellspacing + " width=100%>";
    output += "<tr>";

    # incBlocks
    if(incLeftBlocks)
        output += "<td width=" + $skindata.sideWidth.to_s + " valign=top>";
        output += "<table width=100% cellpadding=0 cellspacing=0>\n";
        for funcname in incLeftBlocks
                begin
                    output += method(funcname).call('l');
#                    eval(funcname + "('l')");
                rescue
                    output += funcname + "<br>" + $!.to_s + $@.to_s;
                ensure
                end

        end

        output += "</table>\n";
        output += "</td>\n";
    end
    # end incBlocks

    output += "<td valign=top>";
#leaderboard
=begin ---_SERVER is not defined
    if(!$userData.limitads && _SERVER['PHP_SELF'] != '/index+php')
        timeline('get banner');
        if(!incLeftBlocks)
                bannertext = banner.getbanner(BANNER_LEADERBOARD);
        else
                bannertext = banner.getbanner(BANNER_BANNER);
        end

        if(bannertext!="")
                output += "<table cellspacing=0 cellpadding=0 align=center><tr><td>bannertext</td></tr><tr><td height=" + $skindata.cellspacing + "></td></tr></table>";
        end

    end
=end

    if($skindata.incCenter)
            openCenter($skindata.incCenter);
    end

    timeline('end header');
    output += "\n\n\n";
    return output;

end


def incFooter()
    output = "";
    timeline('start footer');
    output += "\n\n\n";

    if($skindata.incCenter)
        closeCenter();
    end

    output += "</td>";

#start right bar
    if($skindata.rightblocks || ($session != nil && $userData.showrightblocks=='y'))
            output += "<td width=" + $skindata.sideWidth.to_s + " height=100% valign=top>\n";
                    output += "<table cellpadding=0 cellspacing=0>\n";
                    if($skindata.rightblocks)
                            for funcname in $skindata.rightblocks
                                begin
                                    output += method(funcname).call('r');
                                    #local_method(funcname + "('r')";
                                rescue
                                    print funcname + "<br>" + $!.to_s + $@.to_s;
                                end
                            end
                    end
                    blocks('r');
                    output += "</table>\n";
            output += "</td>\n";
    end
#end right bar

    output += "</tr>";
    output += "</table>";
    output += "</td>";
    output += "</tr>\n";

    #closeAllDBs();

    #start admin menu
    admin = false;
    if(admin)
        output += "<tr>";
        if($skindata.menupic[0,1]=="#")
                output += "<td height=" + $skindata.menuheight + " bgcolor=" + $skindata.menupic + ">";
        else
                output += "<td height=" + $skindata.menuheight + " background='" + $skinloc  + $skindata.menupic + "'>";
        end

        output += "<table cellspacing=0 cellpadding=0 width=100%><tr>";
        if(!empty($skindata.menuends))
                output += "<td class=menu align=left width=1><img src='" + $skinloc  + "left" + $skindata.menuends + "'></td>";
        end


        output += "<td class=menu align=left>&nbsp;&nbsp;&nbsp;&nbsp;";


        menu = array();
        for item in menus.admin.getMenu()
                menu[] = "<a href='item.addr'" + (item.target=='' ? "" : " target='" + item.target + "'" ) + ">" + item.name + "</a>";
        end

        output += implode($skindata.menudivider,menu);

        output += "</td>";
        if(!empty($skindata.menuends))
                output += "<td class=menu align=right width=1><img src='" + $skinloc  + "right" + $skindata.menuends + "'></td>";
        end

        output += "</tr></table>";
        output += "</td>";
        output += "</tr>";
        if($skindata.menuspacersize > 0)
            if($skindata.menuspacer[0,1]=="#")
                output += "<tr><td height=" + $skindata.menuspacersize + " bgcolor=" + $skindata.menuspacer + "></td></tr>";
            else
                output += "<tr><td height=" + $skindata.menuspacersize + " background='" + $skinloc  + $skindata.menuspacer + "'></td></tr>";
            end
        end
        output += "\n";
    end
#end admin menu



#start menu2
    output += "<tr>";
    if($skindata.menupic[0,1]=="#")
            output += "<td height=" + $skindata.menuheight + " bgcolor=" + $skindata.menupic + ">";
    else
            output += "<td height=" + $skindata.menuheight + " background='" + $skinloc  + $skindata.menupic + "'>";
    end


    output += "<table cellspacing=0 cellpadding=0 width=100%><tr>";
    if(!empty($skindata.menuends))
            output += "<td class=menu align=left width=1><img src='" + $skinloc  + "left" + $skindata.menuends + "'></td>";
    end

    output += "<td class=menu align=left>&nbsp;&nbsp;&nbsp;&nbsp;";

    menu = array();
    for item in getMenuBottom()
            menu.push "<a href='item.addr'" + (item.target ? "" : " target='" + item.target.to_s + "'" ) + ">" + item.name + "</a>";
    end

    output += implode($skindata.menudivider,menu);

    output += "</td><td class=menu align=right>";

    #output += "Hits " + number_format(siteStats.hitstotal) + " | Users " + number_format(siteStats.userstotal) + " &nbsp; ";
    output += "Hits 10982098234 | Users 3.14159268 &nbsp; ";

    output += "</td>";
    if(!empty($skindata.menuends))
        output += "<td class=menu align=right width=1><img src='" + $skinloc  + "right" + $skindata.menuends + "'></td>";
    end

    output += "</tr></table>";
    output += "</td>";
    output += "</tr>\n";
#end menu2

    output += "<tr>";
    output += "<td class=footer align=center" + ($skindata.mainbg == "" ? "" : ($skindata.mainbg{0} == "#" ? "" : " background='" + $skinloc  + $skindata.mainbg + "'")) + ">";
    output += "COPYRIGHT!!!";
    output += "</td>";
    output += "</tr>";

    if($skindata.bottomBorderSize > 0)
        colspan = 1;
        if($skindata.leftBorderSize > 0)
                colspan+=1;
        end
        if($skindata.rightBorderSize > 0)
                colspan+=1;
        end
        if($skindata.bottomBorder[0,1]=="#")
                output += "<tr><td height=" + $skindata.bottomBorderSize + " bgcolor=" + $skindata.bottomBorder + " colspan=colspan></td></tr>";
        else
                output += "<tr><td height=" + $skindata.bottomBorderSize + " background='" + $skinloc  + $skindata.bottomBorder + "' colspan=colspan></td></tr>";
        end
    end
    output += "</table>\n";
    output += "</body></html>";
    return output;
end


