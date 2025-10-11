#!/usr/bin/perl -w

use strict 'vars';
package main;
my $minor_num;
my $Verbosity               = 0;# 0:Silent; 1:Normal; 2:Verbose; 10:debug; 100:heavy debug 
my $Rttable;
########################################################################################################################
sub CreateRTTable() {
my $found_tab = 0;
my $LastID=1;
my $DetectedID=1;
if($Verbosity==1){ print "open /etc/iproute2/rt_tables\n"; }

open RTTABLES, "/etc/iproute2/rt_tables";



while (<RTTABLES>) {

    chomp $_;
    if($Verbosity==1){ print "Line '$_'\n"; }

    if ($_ =~ m/^([0-9]+)\s+$Rttable/) {
	$found_tab = 1;
	print "$1";
	last;
    }

    if ($_ =~ /^#/) { next; }
    if ($_ =~ m/^([0-9]+)\s+/) {
	if( $1 eq "255" ) { next;}
	if( $1 eq "256" ) { next;}	 
	if( $1 eq "254" ) { next;}
	if( $1 eq "253" ) { next;}
	if( $1 eq "0") { next;}
	$DetectedID=$1;
        if($DetectedID>$LastID){$LastID=$DetectedID;}
    }
    

}


if (!$found_tab) {
    $LastID=$LastID+1;
    print "$LastID";
    my $cmd = "echo \'$LastID $Rttable\' >> /etc/iproute2/rt_tables";
    system($cmd);
}

}

sub removeTable { 
    my $name = shift; 

    open( FILE, "/etc/iproute2/rt_tables" ); 
    my @LINES = <FILE>; 
    close( FILE ); 
    open( FILE, ">/etc/iproute2/rt_tables" ); 
    foreach my $LINE ( @LINES ) { 
        print FILE $LINE unless ( $LINE =~ m/$name/ ); 
    } 
    close( FILE ); 
    print( "Table successfully removed\n" );     
}

########################################################################################################################
# 
# Parse the command line
#
########################################################################################################################

sub ParseArgs() {
  my $ArgvAsString =  join(" ",@ARGV);

  # --config-file is a mandatory argument.
  if ($ArgvAsString =~ m/--verbose/si) {
    $Verbosity = 1;
    $ArgvAsString = $` . $';	# The matched stuff removed.
  }


  if ($ArgvAsString =~ m/--table-name\s+([\w\/\\~\.\-]+)/si) {
    $Rttable =  $1;
    $ArgvAsString = $` . $';	# The matched stuff removed.
    CreateRTTable();
    exit 0;
  }

  if ($ArgvAsString =~ m/--remove-name\s+([\w\/\\~\.\-]+)/si) {
    $ArgvAsString = $` . $';	# The matched stuff removed.
    removeTable($1);
    exit;
  }
  
}

########################################################################################################################
sub Display($%) {
	my $Text = shift;
	my %Args = (MinVerbosity=> 0,stderr=> 0,@_);

  # stderr messages are under no circumstances suppressed.
	if ($Args{'stderr'}) {
		print STDERR $Text;
		return;
	}

	# Filter out the ones for which the verbosity is too high.
	return if ($Args{'MinVerbosity'} > $Verbosity);

	# And finally print ;-)
  # Stdout is flushed immediate , not to miss error messages.
  my $WasSelected = select(STDOUT);
  $|=1;
  select($WasSelected);

	print STDOUT $Text;

	return;
}

########################################################################################################################


ParseArgs();
