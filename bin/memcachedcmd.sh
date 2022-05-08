#!/bin/sh

for i in `cat /home/nexopia/servers/php`
do
echo -e "\e[36;1m$i:\e[0m ";
echo -e "$1\r\nquit" | nc $i 11211;
done

