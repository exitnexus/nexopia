#!/bin/sh

for i in `cat /home/nexopia/servers/php`
do
echo -ne "\e[36;1m$i:\e[0m ";
echo -e "flush_all\r\nquit" | nc $i 11211;
done

