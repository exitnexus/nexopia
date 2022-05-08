#!/bin/sh

echo -n "`hostname`: ";
if [ -n "`pidof popa3d`"  ] ; then echo -ne "\e[32m"; else echo -n -e "\e[31;1m"; fi; echo -ne "popa3d\e[0m, ";
if [ -n "`pidof master`"  ] ; then echo -ne "\e[32m"; else echo -n -e "\e[31;1m"; fi; echo -ne "postfix\e[0m, ";
echo "`uptime | sed 's/ average//'`";

