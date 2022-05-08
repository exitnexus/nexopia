#!/bin/sh

echo -n "`hostname`: ";
if [ -n "`pidof lighttpd`"  ] ; then echo -ne "\e[32m"; else echo -n -e "\e[31;1m"; fi; echo -ne "lighttpd: `ps aux | grep lighttpd | grep -v grep | awk '{ print $5 / 1024}' | cut -d '.' -f 1` MB\e[0m, ";
if [ -n "`pidof mydns`"     ] ; then echo -ne "\e[32m"; else echo -n -e "\e[31;1m"; fi; echo -ne "mydns\e[0m, ";

echo -n "Disk: ";
DISK='df -lkP'
echo -n "`$DISK | grep dev | awk '{ sum+=\$3 / 1024 / 1024}; END { print sum }' | cut -c 1-4`/";
echo -n "`$DISK | grep dev | awk '{ sum+=\$2 / 1024 / 1024}; END { print sum }'| cut -c 1-3` G, ";

echo "`uptime | sed 's/ average//'`";

