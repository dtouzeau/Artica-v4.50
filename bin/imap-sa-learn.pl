#!/usr/bin/perl

# Imap Interface to SpamAssassin Learn     v0.04
# ------------------------------------     -----
#
# Connects to an imap server, and filters the messages from the INBOX
# and SpamTrap (unless otherwise told) through sa-learn
#
#  usage:
#    imap-sa-learn.pl <-hamfolder HAM> <-spamfolder SPAM>
#
#  Other options:
#    -skips nnn		skips over the first nnn messages in the folder(s)
#    -deletespam	after learning from a spam message, delete it
#    -delete-spam	(as above)
#    -dangerous-delete-ham	after learning from a ham (real email),
#    				delete it. Most people don't want this...
#    -dangerous-delete-all	after learning from any message, delete
#    				it, spam or ham
#
# Uses Mail::IMAPClient and SpamAssassin (sa-learn)
#
# Needs a version of SpamAssassin with the Bayesian filtering support,
# i.e. 2.50 or later
#
#      Nick Burch <nick@tirian.magd.ox.ac.uk>
#           25/06/2003

use Mail::IMAPClient;

# Define our server and credentials here
# * Really ought to able to have several accounts defined
#
#  ** fix me **    your details go below
my $username = '';
my $password = '';
my $server = '127.0.0.1:143';

# Define where to find messages
my $defspamfolder = 'Junk E-mail';
my $defhamfolder = 'Non-spam';

my $deletespam = 1;
my $deleteham = 0;
my $default = 1;

my $skips = 0;
my $error = 0;

# Normal (1), Debugging (2), Debugging-SA (3), or silent(0)?
my $debug = 2;

my @spams;
my @hams;

while(my $arg = shift) {
   if($arg eq "-username") {
	$username = shift;
	print "Using $username\n";
   }
   if($arg eq "-password") {
	$password = shift;
   }
   if($arg eq "-spamfolder") {
     my $spam = shift;
     push @spams,$spam;
     print "Using spam folder $spam\n";
     $default = 0;
   }
   if($arg eq "-hamfolder") {
     my $ham = shift;
     push @hams,$ham;
     print "Using normal (ham) folder $ham\n";
     $default = 0;
   }
   if($arg eq "-deletespam" || $arg eq "-deletespams" || $arg eq "-delete-spam" || $arg eq "-delete-spams") {
     $deletespam = 1;
   }
   if($arg eq "-dangerous-delete-ham" || $arg eq "-dangerous-delete-hams") {
     $deleteham = 1;
   }
   if($arg eq "-dangerous-delete-all") {
     $deletespam = 1;
     $deleteham = 1;
   }
   if($arg eq "-skips" || $arg eq "-skip") {
     $skips = shift;
   }
   if($arg eq "-?" || $arg eq "-h") {
     print "Usage:\n";
     print "  imap-sa-learn.pl [-spamfolder f]* [-hamfolder f]*\n\n";
     print "with no argumnets, uses default folders\n";
     print "(a few other options exist, see the header of the program)\n";
     exit;
   }
}

if($username eq '') {
	print "Username must be provided\n";
	$error = 1;
}
if($password eq '') {
	print "password must be provided\n";
	$error = 1;
}
if($error> 0) {
	exit;
}

if($default) {
   push @hams,$defhamfolder;
   push @spams,'Courrier ind&AOk-sirable',$defspamfolder;
}

my %folders;
$folders{'spam'} = \@spams;
$folders{'ham'} = \@hams;

my $debugsa = "";

if($debug > 2) {
	print "About to connect to $server as $username\n";
}

if($debug == 3) {
	$debugsa = "-D";
}

# Connect to the IMAP server in peek (i.e. don't set read flag) mode
my $imap = Mail::IMAPClient->new(Server   => $server,
				 User     => $username,
				 Password => $password,
				 Peek     => 1);

# Check we were able to connect to the server
unless($imap) {
	if($@) { die($@."\n"); }
	else { die("Unable to connect to $server, with an unknown error\n"); }
}
if($debug > 2) {
	print "Connected to server, looking for emails\n";
}


# Process our folders
foreach my $type(keys %folders) {
   foreach my $folder (@{$folders{$type}}) {
      print "\nLooking in $type folder $folder\n";

      # Pick the folder
      $imap->select($folder);

      # Enable peek mode
      $imap->Peek(1);

      # Fetch messages
      my @mails = ($imap->seen(),$imap->unseen);

      my $count = 0;

      foreach my $id (@mails) {
         $count++;
         if($count < $skips) { next; }

         print " Learning on $type message $id\n";
         my $mail = $imap->message_string($id);
         open SA, "| sa-learn $debugsa --no-sync --$type --single";
         print SA $mail;
         close SA;

         if($type eq "spam" && $deletespam) {
            # If you want to move the message rather than deleting it,
            # uncomment the line below, change the folder, but _don't_
            # remove the delete line!
            #$imap->append('TrashBin', $mail );

            print "Deleting Spam Message $id\n";
            $imap->delete_message($id);
         }
         if($type eq "ham" && $deleteham) {
            print "Deleting Ham (normal email) Message $id\n";
            $imap->delete_message($id);
         }
      }
      if($deleteham || $deletespam) {
         # Only expunge now, rather than on every message
         $imap->expunge();
      }
   }
}

print "Now rebuilding the Baysean filters\n";
`sa-learn --sync`;

$imap->close;
exit;
