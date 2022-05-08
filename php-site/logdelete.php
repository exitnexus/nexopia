<?

/*
apachelog
access.2004.02.06.log
error.2004.02.06.log

mathopd
log.20040211
*/

//apache
unlink(strftime("/home/nexopia/logs/access.%Y.%m.%d.log"),time()-3*86400); //keep 3 days
unlink(strftime("/home/nexopia/logs/error.%Y.%m.%d.log"),time()-3*86400); //keep 3 days

//mathopd
unlink(strftime("/var/mathopd/log.%Y%m%d"),time()-2*86400); //keep 2 days
