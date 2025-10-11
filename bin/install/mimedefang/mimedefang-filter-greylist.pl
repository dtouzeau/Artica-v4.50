#!/usr/bin/perl

#Settings for greylisting.
#
# For an explanation of what the purpose of this is, and maybe a hint as to
# what values to enter, "check http://projects.puremagic.com/greylisting/".
# I think they recommend something like this:
# $gdb_black = 1*60*60;
# $gdb_grey = 5*60*60;
# $gdb_white = 36*24*60*60;
# $gdb_subnet = 1;
# 
#
# If greylist is 1, greylisting will be used.
#
# Greylisting is done on a triplet of sending hosts IP, mail from: and
# rcpt to:.
#
# When a session with a new triplet arrives, all sessions with that
# triplet will be tempfailed for $gdb_black seconds.
# After $gdb_black seconds it will be white-listed for $gdb_grey
# seconds.
# If a session for the triplet arrives within the $gdb_grey white-listing
# period, it will then be white-listed for $gdb_white seconds.
# If a session for a triplet arrives within the $gdb_white white-listing
# period, it will be white listed for another $gdb_white seconds.
#
# When a mail gets a spam-score above $gdb_reset, the greylist status for it's
# triplet will be reset (wich means the next session with that triplet will be
# treaded as though it's a new triplet).
# If $gdb_reset_host is true, all triplets from the same host IP will be reset
# whenever a spam triggers the reset.
#
# If $gdb_subnet is true, only the first 3 octes of the IP-addresses will be
# used in the greylist.
# If $gdb_from_domain is true, only the domain part of the mail from: address
# will be used in the greylist.
# If $gdb_to_domain is true, only the domain part of the rcpt to: address
# will be used in the greylist.
# If $gdb_from_strip is true, some stuff in the user part of the mail from:
# address will be replaced in order to handle mailinglists and some other
# stuff better.
# If $gdb_to_strip is true, some stuff in the user part of the rcpt to:
# address will be replaced in order to handle use parameters and some other 
# stuff better.
#***********************************************************************
$minute = 60;
$hour = 60*$minute;
$day = 24*$hour;

$greylist = 1;
$gdb_black = 30*$minute;
$gdb_grey = 5*$hour;
$gdb_white = 7*$day;
$gdb_reset = 7;
$gdb_reset_host = 0;
$gdb_subnet = 1;
$gdb_from_domain = 0;
$gdb_from_strip = 1;
$gdb_to_domain = 0;
$gdb_to_strip = 1;
$gdb_log = 0;

use DBI;

my $dsn = ReadFileIntoString("/etc/artica-postfix/settings/Daemons/MySQLPerlDSN");
my $db_user_name = ReadFileIntoString("/etc/artica-postfix/settings/Daemons/MySQLPerlUsername");
my $db_password = ReadFileIntoString("/etc/artica-postfix/settings/Daemons/MySQLPerlPassword");

$greylist_dbh = DBI->connect($dsn, $db_user_name, $db_password,
        { RaiseError => 1, AutoCommit => 0 })
        or die $DBI::errstr;

$select_sql = qq{ SELECT reset, accepted, count
		FROM greylist_data WHERE ip=? AND sender=? AND recipient=? };
$select_sth = $greylist_dbh->prepare($select_sql);

$insert_sql = qq{ INSERT INTO greylist_data(id, ip, sender, recipient, created, modified, reset, accepted, count)
		VALUES (?,?,?,?,?,?,?,?,?) };
$insert_sth = $greylist_dbh->prepare($insert_sql);

$update_sql = qq{ UPDATE greylist_data
		SET modified = ?, reset=?, accepted=?, count=?
		WHERE ip=? AND sender=? AND recipient=? };
$update_sth = $greylist_dbh->prepare($update_sql);

$reset_sql = qq{ UPDATE greylist_data
		SET modified = ?, reset = 0, accepted = 0
		WHERE ip=? AND sender=? AND recipient=?};
$reset_sth = $greylist_dbh->prepare($reset_sql);

$resetip_sql = qq{ UPDATE greylist_data
		SET modified = ?, reset = 0, accepted = 0
		WHERE ip=? };
$resetip_sth = $greylist_dbh->prepare($resetip_sql);

###############################
#Greylist Subroutines  ########
###############################

#Strip strings
sub address_strip ($) {
	my($a) = @_;
	$a = "" if (!defined($a));
	$a =~ s/^[<\[]//;
	$a =~ s/[>\]]$//;
	return lc($a);
}

# return a time string...
sub time_string($) {
	my ($time) = @_;
	my $h = int($time / (60*60));
	$time = $time % (60*60);
	my $m = int($time / 60);
	my $s = $time % 60;
	my $r = "";
	$r.="$h hours, " if ($h);
	$r.="$m minutes and " if ($h || $m);
	$r.="$s seconds";
	return $r;
}


#Strip strings for use in the greylist.
sub greylist_strip ($) {
	my($a) = @_;
	$a =~ s/;/:/g;
	return $a;
}

sub greylist_strip_mail($$$) {
	my($a,$d,$s) = @_;
	$a = address_strip($a);
	my $au = $a;
	my $ad = $a;
	$ad =~ s/.*@([^@]*)$/$1/;
	$au =~ s/@[^@]*$//;
	if ($d) {
		$au = "*";
	} elsif ($s) {
		$au =~ s/(.+)\+.*$/$1/;
		my $aut;
		my $autt = $au;
		do {
			$aut = $autt;
			$autt =~ s/^(|.*[^a-z0-9])[a-f0-9]*\d[a-f0-9]*(|[^a-z0-9].*)$/$1#$2/;
		} until ($autt eq $aut);
		$au = $aut if ($aut =~ /[a-z0-9]/);
		#$au =~ s/[^-a-z0-9_.#]/?/g;
	}
	return greylist_strip($au."@".$ad);
}


sub greylist_strip_ip($) {
	my($a) = @_;
	$a =~ s/(.*)\.[0-9]+$/$1\.*/ if (defined($gdb_subnet) && $gdb_subnet);
	return greylist_strip(address_strip($a));
}

sub greylist_strip_triplet(@) {
	my(@p) = @_;
	my($i,$s,$r) = @p;
	my $sr;
	my $sn;
	$s = greylist_strip_mail($s,(defined($gdb_from_domain) && $gdb_from_domain),(defined($gdb_from_strip) && $gdb_from_strip)) if $s;
	$r = greylist_strip_mail($r,(defined($gdb_to_domain) && $gdb_to_domain),(defined($gdb_to_strip) && $gdb_to_strip)) if $r;
	$i = greylist_strip_ip($i);
	return ($i,$s,$r);
}

# Checks authentication
sub check_authenticated () {
	open(COMM, "<./COMMANDS") or return 0;
	while(<COMM>) {
		if (/^=auth_authen/) {
			close(COMM);
			return 1;
		}
	}
	close(COMM);
	return 0;
}

sub greylist_ip_whitelist($) {

	# checks if a given ip number or block is free from whitelisting
	# it checks only the part of the ip number that is sent, so if
	# you whitelist here 192.168.0, then that's all it will check
	# against
	my $ip = shift;
	my %greylist_whitelist = {};

	$greylist_whitelist{'127.0.0'} = 1;
	$greylist_whitelist{'192.168.100'} = 1;
	#$greylist_whitelist{'208.180.20.6'} = 1;
	$greylist_whitelist{'216.198.0.26'} = 1; # stic.net's badly behaving mail server

	# from http://cvs.puremagic.com/viewcvs/*checkout*/greylisting/schema/whitelist_ip.txt?rev=
	$greylist_whitelist{'12.5.136.141'} = 1;    # Southwest Airlines (unique sender, no retry)
	$greylist_whitelist{'12.5.136.142 '} = 1;   # Southwest Airlines
	$greylist_whitelist{'64.12.136'} = 1;       # AOL (common pool)
	$greylist_whitelist{'64.12.137'} = 1;       # AOL
	$greylist_whitelist{'64.12.138'} = 1;       # AOL
	$greylist_whitelist{'64.125.132.254'} = 1;  # collab.net (unique sender per attempt)
	$greylist_whitelist{'66.135.209'} = 1;      # Ebay (for time critical alerts)
	$greylist_whitelist{'66.135.197'} = 1;      # Ebay
	$greylist_whitelist{'66.218.66'} = 1;       # Yahoo Groups servers (common pool, no retry)
	$greylist_whitelist{'152.163.225'} = 1;     # AOL
	$greylist_whitelist{'195.238.2.105'} = 1;   # skynet.be (wierd retry pattern)
	$greylist_whitelist{'195.238.2.124'} = 1;   # skynet.be 
	$greylist_whitelist{'195.238.3.12'} = 1;    # skynet.be
	$greylist_whitelist{'195.238.3.13'} = 1;    # skynet.be
	$greylist_whitelist{'204.107.120.10'} = 1;  # Ameritrade (no retry)
	$greylist_whitelist{'205.188.156'} = 1;     # AOL
	$greylist_whitelist{'205.206.231'} = 1;     # SecurityFocus.com (unique sender per attempt)
	$greylist_whitelist{'207.115.63'} = 1;      # Prodigy - broken software that retries continually (no delay)
	$greylist_whitelist{'207.171.168'} = 1;     # Amazon.com
	$greylist_whitelist{'207.171.180'} = 1;     # Amazon.com
	$greylist_whitelist{'207.171.187'} = 1;     # Amazon.com
	$greylist_whitelist{'207.171.188'} = 1;     # Amazon.com
	$greylist_whitelist{'207.171.190'} = 1;     # Amazon.com
	$greylist_whitelist{'213.136.52.31'} = 1;   # Mysql.com (unique sender)
	$greylist_whitelist{'217.158.50.178'} = 1;  # AXKit mailing list (unique sender per attempt)

	if ($ip =~ m/^(\d+)\.(\d+)\.(\d+)\.(\d+)$/) {
		return 1 if ($greylist_whitelist{"$1.$2.$3.$4"});
		return 1 if ($greylist_whitelist{"$1.$2.$3"});
		return 1 if ($greylist_whitelist{"$1.$2"});
		return 1 if ($greylist_whitelist{"$1"});
	} 
	return 0;
}


#Checks if a triplet is in the grey-list.
# Returns seconds until the triplet will be accepted, or -1 for error.
sub greylist_check($$$) {
	my ($ip,$sender,$recipient) = greylist_strip_triplet(@_);
	my $result = -1;

	my $now = time();
	my $event = "";

	# Get data from DB
	my ($reset,$accepted,$count) = (0,0,0);
	$select_sth->execute($ip, $sender, $recipient);
	$select_sth->bind_columns( \$reset, \$accepted, \$count );
	if ($select_sth->fetch) {

		if ($now < $reset+$gdb_black) {
			$result = ($reset+$gdb_black)-$now;
			$event = 'black';
		} elsif (($now < $reset+$gdb_grey) || 
			  (($accepted > 0) && ($now < $accepted + $gdb_white))) {
			$count++;
			$update_sth->execute($now, $reset, $now, $count, $ip, $sender, $recipient);
			$greylist_dbh->commit();

			$result = 0;
			$event = 'white';
		} else {
			$update_sth->execute($now, $now, 0, $count, $ip, $sender, $recipient);
			$greylist_dbh->commit();

			$result = $gdb_black;
			$event = 'old';
		}

	} else {
		# insert new row in database
		$insert_sth->execute(undef,$ip, $sender, $recipient, $now, $now, $now, 0, 0);
		$greylist_dbh->commit();

		$result = $gdb_black;
		$event = 'new';
	}
	md_syslog('info', "MDLOG,$MsgID,grey_$event,$result,$RelayAddr,$sender,$recipient,?");
	return $result;
}

#Resets record(s) in the grey list.
sub greylist_reset($$$) {
	my ($p_ip,$p_sender,$p_recipient) = greylist_strip_triplet(@_);

	my $now = time();
	if ($p_sender && $p_recipient) {

		# update db with new values
		$reset_sth->execute($now,$p_ip, $p_sender, $p_recipient);
		$greylist_dbh->commit();
		md_syslog('info', "greylist: reset; -; $p_ip; $p_sender; $p_recipient") if (defined($gdb_log) && $gdb_log);

	} else {
		$resetip_sth->execute($now,$p_ip);
		$greylist_dbh->commit();
		md_syslog('info', "greylist: resetip; -, $p_ip; $p_sender; $p_recipient") if (defined($gdb_log) && $gdb_log);
	}
}

# Do not remove this next line
1;

