#!/bin/sh

echo -n "`hostname`: ";
if [ -n "`pidof mysqld`"    ] ; then echo -ne "\e[32m"; else echo -n -e "\e[31;1m"; fi; echo -ne "mysql\e[0m, ";

echo -n "Disk: ";
DISK='df -lkP'
echo -n "`$DISK | grep dev | awk '{ sum+=\$3 / 1024 / 1024}; END { print sum }' | cut -c 1-4`/";
echo -n "`$DISK | grep dev | awk '{ sum+=\$2 / 1024 / 1024}; END { print sum }'| cut -c 1-3` G, ";

echo "`uptime | sed 's/ average//'`";

