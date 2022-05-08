#!/bin/sh

echo -n "`hostname`: ";
if [ -n "`pidof lighttpd`"  ] ; then echo -ne "\e[32m"; else echo -n -e "\e[31;1m"; fi; echo -ne "lighttpd\e[0m, ";
echo "`uptime | sed 's/ average//'`";

