#!/usr/bin/perl
$|=1;
use Time::HiRes qw/gettimeofday tv_interval/;
use Socket;
my @i;
my $p = getprotobyname('tcp');
my $b;
foreach (8..31) {
	my $ls = [gettimeofday];
	my $sa = sockaddr_in(80, inet_aton("66.51.127.$_"));
	socket(S, PF_INET, SOCK_STREAM, $p) || next;
	my $li = [gettimeofday];
	connect(S, $sa);
	my $lc = [gettimeofday];
	send S, "GET /index.php HTTP/1.1\r\nHost: www.nexopia.com\r\nConnection: close\r\n\r\n", 0;
	my $lw = [gettimeofday];
	while (defined($line = <S>)) { }
	my $lr = [gettimeofday];
	close(S);
	my $lf = [gettimeofday];

	if (tv_interval($ls, $lf) >= 1) {
		printf("%s\n\tinit:    %7.5f\n\tconnect: %7.5f\n\twrite:   %7.5f\n\tread:    %7.5f\n\tclose:   %7.5f\n\ttotal:   %7.5f\n",
			"66.51.127.$_", tv_interval($ls, $li), tv_interval($li, $lc), 
			tv_interval($lc, $lw), tv_interval($lw, $lr), tv_interval($lr, $lf),
			tv_interval($ls, $lf));
	} else {
		printf("%s : %7.5fs\n", "66.51.127.$_",tv_interval($ls, $lf));
	}
}
