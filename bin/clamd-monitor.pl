#!/usr/bin/perl -w

#	Parts Copyright (C) 1998, Jim Trocki
#	Parts Copyright (C) 2004 Nigel Horne <njh@bandsman.co.uk>
#	Parts Copyright (C) Ed Ravin
#	Some other parts might be Copyright (C) Jon Meek
#	Additional Code Mangling Copyright (C) 2008-2011 Nathan Gibbs nathan@cmpublishers.com
#
#   This program is free software; you can redistribute it and/or modify
#   it under the terms of the GNU General Public License as published by
#   the Free Software Foundation; either version 2 of the License, or
#   (at your option) any later version.
#
#   This program is distributed in the hope that it will be useful,
#   but WITHOUT ANY WARRANTY; without even the implied warranty of
#   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
#   GNU General Public License for more details.
#
#   You should have received a copy of the GNU General Public License
#   along with this program; if not, write to the Free Software
#   Foundation, Inc., 675 Mass Ave, Cambridge, MA 02139, USA.

eval 'exec /usr/bin/perl -w -S $0 ${1+"$@"}' if 0;

use English;
use Getopt::Long;
use IO::Socket::INET;
use IO::Socket::UNIX;

my @failures = ();
my @details = ();
my $state;
my $tmp;
my %opt;
($ME = $0) =~ s-.*/--;
my $usage = "$ME [-d [ level ] ] [-e DB Expire] [-o] [-p port] [-list] [-listall] [-socket socket] [-t timeout] [-v] [-x] host [host...]\n";

# parse the commandline if any
GetOptions (\%opt, "debug:i", "o", "port=i", "e=i", "x", "list", "listall", "socket=s", "timeout=i", "virus") || die $usage;
die "$ME: no host arguments\n" if (@ARGV == 0);
my $DEBUG	= $opt{"debug"} || 0;
my $EXPIRE	= $opt{"e"} || 7;
my $OFC		= $opt{"o"} || 0;
my $LIST	= $opt{"list"} || $opt{"listall"} || 0;
my $LISTALL	= $opt{"listall"} || 0;
my $PORT	= $opt{"port"} || 3310;
my $SOCKET	= $opt{"socket"} || "";
my $TIMEOUT	= $opt{"timeout"} || 30;
my $XEC		= $opt{"x"} || 0;
my $VT		= $opt{"virus"} || 0;

foreach $host (@ARGV) {
	if (! &pinger($host)) {
		push (@failures, $host);
	}else{
		if ($DEBUG) {
			print "EXT Pinger RTN: OK\n";
		}
	}
}
print join (" ", sort @failures), "\n";
if ( $LIST && scalar @details > 0 ) {
	print "System\t\tStatus Message\tEngine\tDB\tDate\n";
	print @details;
}
if (@failures == 0) {
	exit 0;
}
exit 1;

sub pinger {
	my $machine = shift;
	my ( $mess, $OK, $TransactionOK, $API, $Status, $fd );
	my $SMT = 0;
	my @ddetails = ();
	my $fmt = "%-16s%-16s%-8s%-8s%-25s";
	my $efmt = "%-16s%-16s";
	my $lfmt = "\t%-24s%-16s%-25s";
	($API,$SMT, $CCEE) = &Verproc($machine);
	if( $API > 0 ) {
		if ($DEBUG) {
			print "INT Pinger MSG: API Chk OK\n";
		}
		$TransactionOK= eval {
			# Catch them stinking timeouts
			local $SIG{ALRM} = sub { die "Timeout Alarm" };
			alarm $TIMEOUT;
			my $sock = &getsock($machine);
			if($sock) {
				if ($DEBUG) {
					print "INT Pinger MSG: Socket  OK\n";
				}
				select($sock); $| = 1;
				select(STDOUT);
				print $sock "nIDSESSION\n";
				print $sock "nPING\n";
				$mess = &resproc($sock);
				if(!$mess || ($mess ne "PONG")) {
					$Status = "Failed";
					$OK = 0;
				} else {
					if ($DEBUG) {
						print "INT Pinger MSG: Ping    OK\n";
					}
					$Ex = &dateproc($DBDate,$sock);
					if ( $Ex == 1 ) {
						if ($DEBUG) {
							print "INT Pinger MSG: DB Chk  OK\n";
						}
						if ( $VT ) {	# Run Virus Test
							$mess = &virusproc($sock);
						}else{
							$mess = 1;
						}
						if ( $mess == 1 ) {
							if ($DEBUG && $VT ) {
								print "INT Pinger MSG: VC Chk  OK\n";
							}
							$Status = "Running";
							$OK = 1;
							if ( $LISTALL ) {
								# Get more Info
								$mess = &statproc($sock);
								if ( !$mess ) {
									push(@ddetails, "\tDetailed Status\t\tFail\n");
									$OK = 0;
								}else{
									push(@ddetails, "\tDetailed Status\t\tSubSys\tInfo$mess\n");
									$OK = 1;
								}
								if ( $API == 2 ) {
									$mess = &detproc($machine);
									if ( !$mess ) {
										push(@ddetails, "\tDetection Status\tNone\n");
									}else{
										push(@ddetails, "\tDetection Status\tCount\tVirus$mess\n");
									}
								}
								if ( $CCEE == 1 ) {
									push(@ddetails, "\tCCEE Status\t\tTotal\n");
									print $sock "nSIGCOUNT\n";
									my $sc = &resproc($sock);
									print $sock "nDBCOUNT\n";
									my $dc = &resproc($sock);
									push(@ddetails, "\t Databases:\t\t$dc\n");
									push(@ddetails, "\tSignatures:\t\t$sc\n");
									$mess = &dblproc("",$sock);
									if ($mess) {
										push(@ddetails, "\tCCEE Database Status\n");
										push (@ddetails, sprintf ( $lfmt, "Database","DB Ver","Date" ) . "\n");
										my @DBL = split (/-/, $mess);
										foreach my $tmp (@DBL) {
											my ($DB_Name, $DB_Ver, $DB_SC, $DB_BD) = split (/\//, $tmp);
											push (@ddetails, sprintf ( $lfmt, $DB_Name,$DB_Ver,$DB_BD ) . "\n");
										}
									}
								}
							}
						}elsif ( $mess == 0 ) {
							$Status = "Virus Test Fail";
							$OK = 0;
						}else{
							$Status = "Unknown Error";
							$OK = 0;
						}
					}elsif ( $Ex == -2 ) {
						$Status = "DB Chk Fail";
						$OK = 0;
					}else{
						if ( $Ex ) {
							$EM = "Engine";
						}else{
							$EM = "Virus DB";
						}
						$Status = "Outdated $EM";
						$OK = 0;
					}
				}
				if ($DEBUG) {
					print "Pre Pinger RTN: ";
					if ( $OK ) {
						print "OK ";
					}else{
						print "Error "
					}
					print "with message $mess\n";
				}
				if (!$OK && $mess eq "UNKNOWN COMMAND" ) {
					# Something went very very wrong.
					# Don't close the session, just run!
				}else{
					print $sock "nEND\n";
				}
				close($sock);
			} else {
				$Status = "SCX Unreachable";
				$OK = 0;
			}
		};
		alarm 0; # Cancel the alarm
		if ($EVAL_ERROR and ($EVAL_ERROR =~ /^Timeout Alarm/)) {
			$Status = "SCX timeout($TIMEOUT)";
		}
	}else{
		if ( $API == 0 ) {
			$Status = "Unsupported API";
		}elsif ( $API == -1 ) {
			$Status = "Unreachable";
		}elsif ( $API == -2 ) {
			$Status = "ICX timeout($TIMEOUT)";
		}else{
			$Status = "ICX Invalid";
		}
	}
	if ( !$TransactionOK) {
		$OK = 0;
	}
	if ( $OK == 1 ) {
		$fd = sprintf ( $fmt, $machine,$Status,$Engine,$DB,$DBDate );
	}else{
		if ( $SMT == 1 ) {
			$fd = sprintf ( $fmt, $machine,$Status,$Engine,$DB,$DBDate );
		}else{
			$fd = sprintf ( $efmt, $machine,$Status );
		}
	}
	push (@details, "$fd\n");
	if ( $LISTALL ) {
		push (@details, @ddetails);
	}
	if ($DEBUG) {
		print "INT Pinger RTN: $OK\n";
	}
	return($OK);
}

sub statproc {
	my $sock = shift;
	my ( $SSN, $SSS );
	my $tmp = 0;
	my $mess = "";
	my %SD;
	print $sock "nSTATS\n";
	until ( $mess eq "END") {
		$mess = &resproc($sock);
		next if ($mess eq "" );
		if ($DEBUG == 3 ) {
			print "INT StatProc Line $tmp\t$mess\n";
		}
		if ( $mess =~m/\:/ ) {
			($SSN, $SSS ) = split (/\: /, $mess);
			chomp ($SSN);
			chomp ($SSS);
			$SD{$SSN}=$SSS;
		}
		$tmp++;
	}
	my $Hdr = "\n"."\t" x4;
	$mess = $Hdr . "State:\t" . $SD{"STATE"} . $Hdr . "Queue:\t" . $SD{"QUEUE"} . $Hdr . "Thread:\t" . $SD{"THREADS"} ;
	return($mess);
}

sub detproc {
	my $machine = shift;
	my ( $mess, $TransactionOK, $TS, $MD5, $SZ, $VN, $FN );
	my $tmp = 0;
	my %VT;
	my @lines;
	$TransactionOK= eval {
		# Catch them stinking timeouts
		local $SIG{ALRM} = sub { die "Timeout Alarm" };
		alarm $TIMEOUT;
		my $sock = &getsock($machine);
		if($sock) {
			select($sock); $| = 1;
			select(STDOUT);
			print $sock "nDETSTATS\n";
			while ( <$sock> ) {
				&resproc($sock);
				$lines [ $tmp ] = $_;
				$tmp++;
			}
			close($sock);
		}else{
			$mess = "Socket Error";
		}
	};
	alarm 0; # Cancel the alarm
	if ( !$TransactionOK ) {
		if ($EVAL_ERROR and ($EVAL_ERROR =~ /^Timeout Alarm/)) {
			$mess = "Timeout";
		}else{
			$mess = "Other";
		}
	}else{
		foreach $tmp ( @lines ) {
			chomp ($tmp);
			($TS, $MD5, $SZ, $VN, $FN ) = split (/\:/, $tmp);
			$VT{$VN}++;
		}
		$mess = "";
		if ($DEBUG == 3 ) {
			print "INT DetProc Start\n";
			foreach $VN ( keys %VT ) {
				print "$VN " . $VT{$VN} . "\n";
			}
			print "INT DetProc End\n";
		}
		foreach $VN ( keys %VT ) {
			$mess = $mess . "\n" . "\t" x4 . $VT{$VN} . "\t$VN\n";
		}
	}
	if ($DEBUG == 3 ) {
		print "INT DetProc RTN: $mess\n";
	}
	return($mess);
}

sub resproc {
	my $sock = shift;
	my $mess = <$sock>;
	if ( $mess ) {
		chomp $mess;
		# Strip Session ID
		if ( $mess=~m/(^\d{1,5}\:\s.*)/) {
			$mess=~s/^\d{1,5}\:\s//;
		}
		# Strip Strem
		if ( $mess=~m/(^stream\:\s.*)/) {
			$mess=~s/^stream\:\s//;
		}
		# Strip Extended Detection Info
		if ( $mess=~m/(\(.*\))/) {
			$mess=~s/(\(.*\))//;
		}
	}
	return($mess);
}

sub virusproc {
	my $sock = shift;
	my $mess;
	# Standard "test" virus - broken up into two lines to avoid
	# triggering anti-virus systems (cough, cough)
	$mess = 'X5O!P%@AP[4\PZX54(P^)7CC)7}$EICAR-STANDARD-' .
	'ANTIVIRUS-TEST-FILE!$H+H*';
	# New API Implementation.
	print $sock "nINSTREAM\n";
	&sendstream ( $sock, $mess );
	&sendstream ( $sock, "" );
	$mess = &resproc($sock);
	if ( !$mess || $mess ne "Eicar-Test-Signature FOUND") {
		$mess = 0;
	}else{
		$mess = 1;
	}
	return($mess);
}

sub sendstream{
	my ($sock, $mess) = @_;
	my $len = pack 'N', length($mess);
	print $sock "$len$mess";
}

sub dateproc {
	my ($Date, $sock) = @_;
	my $State = 1;
	my ( $tmp, $dmon, $dday, $dyear );
	my ( $lday, $lmon, $lyear, $cday, $cmon, $cyear );
	my ( $TDB );
	my ( $DB_Name, $DB_Ver, $DB_SC, $DB_BD );
	my @ODBL = qw ( bytecode main daily safebrowsing );
	if ( $OFC || $XEC ) {
		# Get Official Engine & DB Info
		use Net::DNS;
		$res = Net::DNS::Resolver->new();
		$query = $res->query("current.cvd.clamav.net", "TXT");
		if ( $query ) {
			foreach my $rr ($query->answer) {
				next unless $rr->type eq "TXT";
				$EV = $rr->rdatastr;
				$EV =~s/\"//;
				$EV =~s/\"//;
				($EV,$MV,$DV,$DNSAGE,$SBV,$BCV) = ( split (/:/,$EV)) [0,1,2,3,6,7];
				if ($DEBUG > 1 && $OFC ) {
					print "INT DateProc MSG: DNS Time Stamp " . localtime($DNSAGE) . "\n";
					print "INT DateProc MSG: Official Engine Version $EV\n";
					print "INT DateProc MSG: Official Main DB Version $MV\n";
					print "INT DateProc MSG: Official Daily DB Version $DV\n";
					print "INT DateProc MSG: Official ByteCode DB Version $BCV\n";
					print "INT DateProc MSG: Official SafeBrowsing DB Version $SBV\n";
				}
			}
		}else{
			$State = -2;
		}
	}
	if ( $State != -2 ){
		if ( $OFC ){
			# Check DB by Version
			if ($DEBUG > 1 ) {
				print "INT DateProc MSG: DB Version Check\n";
			}
			if ( $CCEE == 1 ) {
				if ($DEBUG > 1 ) {
					print "INT DateProc MSG: CCEE supported\n";
				}
				foreach $tmp ( @ODBL ) {
					$TDB = &dblproc($tmp,$sock);
					if ( $TDB ) {
						($DB_Name, $DB_Ver, $DB_SC, $DB_BD) = split (/\//, $TDB);
						if ( $DB_Name=~m/(main)/) {
							if ( $MV > ( $DB_Ver + ( $EXPIRE ))) {
								$State = 0;
							}else{
								$State = 1;
							}
						}elsif ($DB_Name=~m/(daily)/) {
							if ( $DV > ( $DB_Ver + ( $EXPIRE ))) {
								$State = 0;
							}else{
								$State = 1;
							}
						}elsif ($DB_Name=~m/(bytecode)/) {
							if ( $BCV > ( $DB_Ver + ( $EXPIRE ))) {
								$State = 0;
							}else{
								$State = 1;
							}
						}elsif ($DB_Name=~m/(safebrowsing)/) {
							if ( $SBV > ( $DB_Ver + ( $EXPIRE ))) {
								$State = 0;
							}else{
								$State = 1;
							}
						}
						if ( $State == 0 ) {
							if ($DEBUG > 1 ) {
								print "INT DateProc MSG: $DB_Name Failed\n";
							}
							last;
						}else{
							if ($DEBUG > 1 ) {
								print "INT DateProc MSG: $DB_Name Passed\n";
							}
						}
						($DB_Name, $DB_Ver, $DB_SC, $DB_BD) = "";
					}else{
						if ($DEBUG > 1 ) {
							print "INT DateProc MSG: $tmp not Loaded\n";
						}
					}
				}
			}else{
				if ($DEBUG > 1 ) {
					print "INT DateProc MSG: CCEE not supported\n";
				}
				if ( $DV > ( $DB + ( $EXPIRE ))) {
					$State = 0;
				}else{
					$State = 1;
				}
			}
		}
		if ( $State == 1 ){
			# Check DB by Days
			if ($DEBUG > 1 ) {
				print "INT DateProc MSG: DB Date Check\n";
			}
			my @year_months = 	('Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec');
			# Get Expiration Date.
			( $tmp, $tmp, $tmp,$lday,$lmon,$lyear,$tmp,$tmp,$tmp ) = localtime ( time - (60*60*24*$EXPIRE) );
			$lyear = 1900 + $lyear;
			$lmon = $lmon + 1;
			# Get Current Date.
			( $tmp, $tmp, $tmp,$cday,$cmon,$cyear,$tmp,$tmp,$tmp ) = localtime;
			$cyear = 1900 + $cyear;
			$cmon = $cmon + 1;
			# Get Object Date
			( $dmon,$dday,$dyear ) = ( split (/\s{1,2}/, $Date)) [1,2,4];
			# Switch month from char to num.
			my $ctr = 0;
			foreach $tmp (@year_months) {
				if ($tmp eq $dmon) {
					$dmon = $ctr;
				}
				$ctr ++;
			}
			$dmon = $dmon + 1;
			# Check for out of Date DB.
			if ($DEBUG > 1 ) {
				print "INT DateProc MSG: VDB Date: $dmon-$dday-$dyear \n";
				print "INT DateProc MSG: Exp Date: $lmon-$lday-$lyear \n";
			}
			$State=2;	# Innocent until proven guilty.
			my $TExpire = $EXPIRE;
			until ( $State != 2 ) {
				if ($DEBUG > 1 ) {
					print "INT DateProc MSG: Try Expire Date: $lmon-$lday-$lyear \n";
				}
				if ( $lyear == $dyear && $lday == $dday && $lmon == $dmon ) {
					# Found Object with $Date.
					$State = 1;
				}elsif ( $lyear == $cyear && $lday == $cday && $lmon == $cmon) {
					# It is today!
					# No Object newer then $EXPIRE days where found.
					$State = 0;
				}else{
					# No Object with $Date found.
					# Look for it in tomorrow.
					$TExpire --;
					( $tmp, $tmp, $tmp,$lday,$lmon,$lyear,$tmp,$tmp,$tmp ) = localtime ( time - (60*60*24*$TExpire) );
					$lyear = 1900 + $lyear;
					$lmon = $lmon + 1;
				}
			}
		}
		# Engine Check
		if ( $XEC && $EV ne $Engine ) {
			if ($DEBUG > 1 ) {
				print "INT DateProc MSG: Engine Check\n";
			}
			$State = -1;
		}
	}
	if ($DEBUG > 1 ) {
		print "INT DateProc MSG: Expire Date Stop: $lmon-$lday-$lyear \n";
		print "INT DateProc RTN: $State\n";
	}
	return($State);
}

sub Verproc {
	my $machine = shift;
	my ( $CV, $CMDS, $mess );
	my $API = 0;
	my $SMT = 0;
	my $CCEE = 0;
	my $TransactionOK= eval {
		# Catch them stinking timeouts
		local $SIG{ALRM} = sub { die "Timeout Alarm" };
		alarm $TIMEOUT;
		my $sock = &getsock($machine);
		if($sock) {
			select($sock); $| = 1;
			select(STDOUT);
			print $sock "nVERSIONCOMMANDS\n";
			$mess = &resproc($sock);
			close($sock);
		}else{
			if ($DEBUG > 1 ) {
				print "INT VerProc MSG: Can't get socket.\n";
			}
			$API = -1;
		}
	};
	alarm 0; # Cancel the alarm
	if ($EVAL_ERROR and ($EVAL_ERROR =~ /^Timeout Alarm/)) {
		$API = -2;
		if ($DEBUG > 1 ) {
			print "INT VerProc MSG: Timeout\n";
		}
	}
	if ( $TransactionOK && $API == 0 ) {
		if ( !$mess ) {
			$API = -3;
		}else{
			if ($DEBUG > 1 ) {
				print "INT VerProc MSG: $mess\n";
			}
			chomp $mess;
			if ( $mess=~m/(|)/) {
				($CV, $CMDS) = split (/\|/, $mess);
				if ( $CMDS=~m/(DETSTATS)/) {
					# 0.96.5+
					$API = 2;
				}else{
					# 0.95x+
					$API = 1;
				}
				if ( $CMDS=~m/(DBLIST)/) {
					# CCEE is in the house. :-)
					$CCEE = 1
				}else{
					$CCEE = 0
				}
			}else{
				# 0.94x
				$CV = $mess;
				$API = 0;
			}
			if ($DEBUG > 1 ) {
				print "INT VerProc MSG: CV $CV\n";
			}
			# Split out version Info
			if ( $CV=~m/(\/)/) {
				($Engine, $DB, $DBDate) = split (/\//, $CV);
				$Engine=~s/ClamAV\s//;
				$SMT = 1;
			}else{
				$SMT = 0;
			}
		}
	}else{
		if ($DEBUG > 1 ) {
			print "INT VerProc MSG: Other Error: $EVAL_ERROR\n";
		}
		$API = -1;
	}
	if ($DEBUG > 1 ) {
		print "INT VerProc RTN: $API $SMT $CCEE\n";
	}
	return ($API,$SMT,$CCEE);
}

sub getsock {
	my $machine = shift;
	my $sock;
	if (($SOCKET) && (lc($machine) eq "localhost")) {
		if ($DEBUG > 1 ) {
			print "INT GetSock MSG: UNIX socket: $SOCKET\n";
		}
		$sock = IO::Socket::UNIX->new(
			Peer => $SOCKET,
			Type => SOCK_STREAM);
	} else {
		if ($DEBUG > 1 ) {
			print "INT GetSock MSG: INET socket: $machine:$PORT\n";
		}
		$sock = IO::Socket::INET->new(
			PeerPort => $PORT,
			PeerAddr => $machine,
			Proto => 'tcp',
			Timeout => $TIMEOUT,
			Type => SOCK_STREAM);
	}
	return($sock);
}

sub dblproc {
	my ($DB, $sock) = @_;
	my ( $DB_Name, $DB_Ver, $DB_SC, $DB_BD, $RET ) = "";
	my $mess = "";
	print $sock "nDBLIST\n";
	until ( $mess eq "END") {
		$mess = &resproc($sock);
		if ( $mess eq "Command invalid inside IDSESSION. ERROR") {
			$mess = "NS";
			last;
		}
		if ( $mess ne "END") {
			($DB_Name, $DB_Ver, $DB_SC, $DB_BD) = split (/\//, $mess);
			if ( $DB_Name ne "" && $DB_Ver ne "" && $DB_SC ne "" ) {
				if ( $DB eq "" ) {
					if ($DEBUG > 2 ) {
						print "INT DBLProc DB-in  $mess\n";
					}
					if ( $DB_Name=~m/-\[3rd Party\]/) {
						# 3rd Party DB Output Cleanup
						$DB_Name=~s/-\[3rd Party\]//;
						$DB_Ver = "[3rd Party]";
						if ($DB_BD eq "") {
							# Might not have a build date.
							$DB_BD = "NA";
						}
						$mess = join ( '/', ( $DB_Name, $DB_Ver, $DB_SC, $DB_BD) );
					}
					if ($DEBUG > 2 ) {
						print "INT DBLProc DB-out $mess\n";
					}
					if (!$RET) {
						$RET = $mess;
					}else{
						$RET = "$mess-$RET";
					}
				}else{
					if ( $DB_Name=~m/($DB)/ ) {
						if ($DEBUG > 2 ) {
							print "INT DBLProc DB $mess\n";
						}
						$RET = $mess;
					}else{
						next;
					}
				}
			}
			($DB_Name, $DB_Ver, $DB_SC, $DB_BD) = "";
		}
	}
	if ( $mess eq "END" ) {
		if ( $DB ne "" ) {
			if ($DEBUG > 1 ) {
				print "INT DBLProc DB $DB ";
				if ($RET) {
					print "Found\n";
				}else{
					print "Not Found\n";
				}
			}
		}
	}else{
		if ($DEBUG > 1 ) {
			print "INT DBLProc ERROR";
		}
		$RET = "";
	}
	return($RET);
}
