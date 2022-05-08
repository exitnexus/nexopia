#/bin/sh

echo -n "`hostname`: ";
if [ -n "`pidof memcached`" ] ; then echo -ne "\e[32m"; else echo -n -e "\e[31;1m"; fi; echo -ne "memcached\e[0m, ";
if [ -n "`pidof php`"       ] ; then echo -ne "\e[32m"; else echo -n -e "\e[31;1m"; fi; echo -ne "php: `pidof php | wc -w`\e[0m, ";
echo "`uptime | sed 's/ average//'`";

