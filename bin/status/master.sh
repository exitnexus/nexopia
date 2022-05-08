#/bin/sh

echo -n "`hostname`: ";
if [ -n "`pidof mysqld`"  ] ; then echo -ne "\e[32m"; else echo -n -e "\e[31;1m"; fi; echo -ne "mysql\e[0m, ";
if [ -n "`pidof lighttpd`"  ] ; then echo -ne "\e[32m"; else echo -n -e "\e[31;1m"; fi; echo -ne "lighttpd\e[0m, ";
if [ -n "`pidof php-cgi`"       ] ; then echo -ne "\e[32m"; else echo -n -e "\e[31;1m"; fi; echo -ne "php: `pidof php-cgi | wc -w`\e[0m, ";
echo "`uptime | sed 's/ average//'`";

