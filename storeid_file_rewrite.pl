#!/usr/bin/perl
use IO::File;
use warnings;
use Pod::Usage;
my @rules; # array of [regex, replacement string]


sub writelogs($){
	return;
	my $text = shift;
	open FH, ">>/var/log/squid/storeid.log" or return;
	print FH "$text\n";
	close FH;
	
}

# read config file
open RULES, "/etc/squid3/storeid_rewrite" or die "Error opening /etc/squid3/storeid_rewrite: $!";
while (<RULES>) {
	chomp;
	next if /^\s*#?$/;
	if (/^\s*([^\t]+?)\s*\t+\s*([^\t]+?)\s*$/) {
		push(@rules, [qr/$1/, $2]);
	} else {
		print STDERR "$0: Parse error in $ARGV[0] (line $.)\n";
	}
}

close RULES;

$|=1;
# read urls from squid and do the replacement
URL: while (<STDIN>) {
	chomp;
	last if $_ eq 'quit';

	my $channel = "";
	my $url=$_;

	my @X = split(" ",$url);
	my $a = $X[0]; ## channel id
	my $b = $X[1]; ## url
	my $c = $X[2]; ## ip address
	my $u = $b; ## url
  
	if (s/^(\d+\s+)//o) {
	  $channel = $1;
	}
	
	if($u=~ m/\.sstatic\.net\/(.*?)\?v=/){
		writelogs("$url [OK]");
		print $channel, "OK store-id=http://sstatic.net.SQUIDINTERNAL/" . $1 ."\n";
		next;
	}
  
	if($u=~ m/http.*\.(fbcdn|akamaihd)\.net\/h(profile|photos).*[\d\w].*\/([\w]\d+x\d+\/.*\.[\d\w]{3}).*/){
		writelogs("$url [OK]");
		print $channel, "OK store-id=http://fbcdn.net.SQUIDINTERNAL/" . $2 . "/" . $3 ."\n";
		next;
	}
  
	if ($u=~ m/^http(.*)static(.*)(akamaihd|fbcdn).net\/rsrc.php\/(.*\/.*\/(.*).(js|css|png|gif))(\?(.*)|$)/) {
		writelogs("$url [OK]");
		print $channel, "OK store-id=http://fbcdn.net.SQUIDINTERNAL/static/" . $5 . "." . $6 ."\n";
		next;
	}   

	if ($u=~ m/^https?\:\/\/.*utm.gif.*/) {
		writelogs("$url [OK]");
		print $channel, "OK store-id=http://google-analytics.SQUIDINTERNAL/__utm.gif\n";
		next;
	}
	
	if ($u=~ m/^https?\:\/\/.*\/speedtest\/(.*\.(jpg|txt)).*/) {
		writelogs("$url [OK]");
		print $channel, "OK store-id=http://speedtest.SQUIDINTERNAL/" . $1."\n";
		next;
	}
	
	if ($u=~ m/^https?\:\/\/.*\/(.*\..*(mp4|3gp|flv))\?.*/) {
		writelogs("$url [OK]");
		print $channel, "OK store-id=http://video-file.SQUIDINTERNAL/" . $1."\n";
		next;
	}
	
	if ($u=~ m/^https?\:\/\/c2lo\.reverbnation\.com\/audio_player\/ec_stream_song\/(.*)\?.*/) {
		writelogs("$url [OK]");
		print $channel, "OK store-id=http://reverbnation.SQUIDINTERNAL/" . $1."\n";
		next;
	} 	
	if ($u=~ m/^https?\:\/\/.*\.c\.android\.clients\.google\.com\/market\/GetBinary\/GetBinary\/(.*\/.*)\?.*/) {
		writelogs("$url [OK]");
		print $channel, "OK store-id=http://playstore-android.SQUIDINTERNAL/" . $1."\n";
		next;
	}
	
	if ($u=~ m/^https?\:\/\/.*youtube.*ptracking.*/){
		my @video_id = m/[&?]video_id\=([^\&\s]*)/;
		my @cpn = m/[&?]cpn\=([^\&\s]*)/;
		unless (-e "/home/squid/youtubeStoreID/@cpn"){
			open FILE, ">//home/squid/youtubeStoreID/@cpn";
			print FILE "@video_id";
			close FILE;
		}
		writelogs("$url [OK]");
		print $channel, "ERR\n";
		next;
	}
	if ($u=~ m/^https?\:\/\/.*youtube.*stream_204.*/){
		my @docid = m/[&?]docid\=([^\&\s]*)/;
		my @cpn = m/[&?]cpn\=([^\&\s]*)/;
		unless (-e "/home/squid/youtubeStoreID/@cpn"){
			open FILE, ">/home/squid/youtubeStoreID/@cpn";
			print FILE "@docid";
			close FILE;
		}
		writelogs("$url [OK]");
		print $channel, "ERR\n";
		next;
	 
	}
	
	if ($u=~ m/^https?\:\/\/.*youtube.*player_204.*/){
		@v = m/[&?]v\=([^\&\s]*)/;
		@cpn = m/[&?]cpn\=([^\&\s]*)/;
		unless (-e "/home/squid/youtubeStoreID/@cpn"){
			open FILE, ">/home/squid/youtubeStoreID/@cpn";
			print FILE "@v";
			close FILE;
		}
		writelogs("$url [OK]");
		print $channel, "ERR\n";
		next;
	 
	}	
	if ($u=~ m/^https?\:\/\/.*(youtube|googlevideo).*videoplayback.*/){
		my @itag = m/[&?](itag\=[0-9]*)/;
		my @range = m/[&?](range\=[^\&\s]*)/;
		my @cpn = m/[&?]cpn\=([^\&\s]*)/;
		my @mime = m/[&?](mime\=[^\&\s]*)/;
		my @id = m/[&?]id\=([^\&\s]*)/;
	 
		if (defined($cpn[0])){
			if (-e "/home/squid/youtubeStoreID/@cpn"){
				open FILE, "/home/squid/youtubeStoreID/@cpn";
				my @id = <FILE>;
				close FILE;
			}
		}
		writelogs("$url [OK]");
		print $channel, "OK store-id=http://video-srv.SQUIDINTERNAL/id=@id@mime@range\n";
		next;
	}  

	foreach my $rule (@rules) {
		if (my @match = /$rule->[0]/) {
			$_ = $rule->[1];
			
			for (my $i=1; $i<=scalar(@match); $i++) {
				s/\$$i/$match[$i-1]/g;
			}
			writelogs("$url [OK] -> $_");
			print $channel, "OK store-id=$_\n";
			next URL;
		}
	}
	writelogs("$url [FAILED]");
	print $channel, "ERR\n";
}


