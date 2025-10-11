use Net::Netmask;
use MIME::Words qw(:all);
use Digest::MD5 qw(md5_hex);
use Cache::Memcached;

use POSIX qw(strftime);
$AdminAddress = 'postmaster@localhost';
$AdminName = "MIMEDefang Administrator's Full Name";
$DaemonAddress = 'mimedefang@localhost';
$ClamdSock='/var/run/clamav/clamav.sock';
$GeneralWarning="----------------------------------------------------------------------------\n";
$AddWarningsInline = 0;
$MailArchiverEnabled=0;
$MimeDefangClamav=0;
$MimeDefangDisclaimer=0;
$MimeDefangAutoCompress=0;
$DebugMimeFilter=0;
$MimeDefangSpamAssassin=0;
$MimeDefangSpamMaxSize=0;
$SpamAssBlockWithRequiredScore=10;
$SpamAssassinRequiredScore=0;
$SendIPIsInNet=0;
$isWhitelisted=0;
$isOutgoing=0;
$isAutoCompress=0;
$MimeDefangAutoWhiteList=0;
$MimeDefangFilterExtensions=0;
$MimeDefangFilterExtensionsLocal=0;
$MimeDefangFilterExtensionsRegex='';
$MimeDefangFilterExtensionsNotifs='';
$FinalScoreSpam=0;
$FinalDisclaimer=0;
$FinalBackUp=0;
$FinalInfected=0;
$FinalExtFiltered=0;
$FinalScoreReport='';
$SenderBackuped='';
$MessageSize=0;
$isAutoCompressSize=0;
$isAutoReply=0;
$AntivirusAction=0;
$AntivirusRule='';
$NotTrustLocalNet=0;
$XSpamStatusHeaderScore=0;
$WhiteListedWhy='';
$IsAuthenticated=0;
$MyNetLogs='';
$isNotSpamAssassinWhy='';
$EnableClamavDaemon=0;
$VirusAsSpam=0;
$ScoreWithSpamVirus=0;
$GloballyWhiteListed=0;
$EnableMimedefangUrlsChecker=0;
$MimedefangUrlsCheckerTrustAutoWhitelist=0;
$MimedefangUrlsCheckerHostname="";
$MustScanUrls=1;
$MimeDefangNoTrustMyNets=0;

md_graphdefang_log_enable('mail', 1);
# $MaxMIMEParts = 50;
# copy_or_link("./INPUTMSG", "./Work/INPUTMSG");

$Stupidity{"NoMultipleInlines"} = 0;

detect_and_load_perl_modules();

# This procedure returns true for entities with bad filenames.
sub filter_bad_filename  {
    if($MimeDefangFilterExtensionsLocal==0){return 0;}
    my($entity, $bad_exts) = @_;
    my $re;

    # Bad extensions

 if($DebugMimeFilter==1){md_syslog("info", "filter_bad_filename():: Regex: $bad_exts");}   
   if(length($bad_exts)==0){
	$bad_exts="ade|adp|app|asd|asf|asx|bas|bat|chm|cmd|com|cpl|crt|dll|exe|fxp|hlp|hta|hto|inf|ini|ins|isp|jse?|lib|lnk|mdb|mde|msc|msi|msp|mst|ocx|pcd|pif|prg|reg|scr|sct|sh|shb|shs|sys|url|vb|vbe|vbs|vcs|vxd|wmd|wms|wmz|wsc|wsf|wsh";
   }

     
    $bad_exts = '('.$bad_exts.'|\{[^\}]+\})';

    # Do not allow:
    # - CLSIDs  {foobarbaz}
    # - bad extensions (possibly with trailing dots) at end
    $re = '\.' . $bad_exts . '\.*$';

    return 1 if (re_match($entity, $re));

    # Look inside ZIP files
    if (re_match($entity, '\.zip$') and
	$Features{"Archive::Zip"}) {
	my $bh = $entity->bodyhandle();
	if (defined($bh)) {
	    my $path = $bh->path();
	    if (defined($path)) {
		return re_match_in_zip_directory($path, $re);
	    }
	}
    }
    return 0;
}

#***********************************************************************
# %PROCEDURE: filter_begin
# %ARGUMENTS:
#  $entity -- the parsed MIME::Entity
# %RETURNS:
#  Nothing
# %DESCRIPTION:
#  Called just before e-mail parts are processed
#***********************************************************************
sub filter_begin {
   	my($entity) = @_;
	$FinalScoreSpam=0;
	$FinalDisclaimer=0;
	$FinalBackUp=0;
	$FinalInfected=0;
	$FinalExtFiltered=0;
	$FinalScoreReport='';
	$WhiteListedWhy='';
    $IsAuthenticated=0;
    $isWhitelisted=0;
    $BigDebug=0;
    $VirusAsSpam=0;

    
    # ALWAYS drop messages with suspicious chars in headers
    
    $GloballyWhiteListed=smtpd_milter_maps();
    
    if ($GloballyWhiteListed==1) {return true;}
    
    
    if ($SuspiciousCharsInHeaders) {
        md_graphdefang_log('suspicious_chars');
    	return action_discard();
    }
    $MailArchiverEnabled=int(ReadFileIntoString("/etc/artica-postfix/settings/Daemons/MimeDefangArchiver"));
    $MimeDefangClamav=int(ReadFileIntoString("/etc/artica-postfix/settings/Daemons/MimeDefangClamav"));
    $MimeDefangDisclaimer=int(ReadFileIntoString("/etc/artica-postfix/settings/Daemons/MimeDefangDisclaimer"));
    $MimeDefangSpamAssassin=int(ReadFileIntoString("/etc/artica-postfix/settings/Daemons/MimeDefangSpamAssassin"));
    $DebugMimeFilter=int(ReadFileIntoString("/etc/artica-postfix/settings/Daemons/DebugMimeFilter"));
    $MimeDefangAutoWhiteList=int(ReadFileIntoString("/etc/artica-postfix/settings/Daemons/MimeDefangAutoWhiteList"));
    $MimeDefangFilterExtensions=int(ReadFileIntoString("/etc/artica-postfix/settings/Daemons/MimeDefangFilterExtensions"));
    $ScoreWithSpamVirus=int(ReadFileIntoString("/etc/artica-postfix/settings/Daemons/ScoreWithSpamVirus"));
    $MimeDefangAutoCompress=int(ReadFileIntoString("/etc/artica-postfix/settings/Daemons/MimeDefangAutoCompress"));
    $NotTrustLocalNet=int(ReadFileIntoString("/etc/artica-postfix/settings/Daemons/NotTrustLocalNet"));
    $MimeDefangForged=int(ReadFileIntoString("/etc/artica-postfix/settings/Daemons/MimeDefangForged"));
    $MimeDefangTraces=int(ReadFileIntoString("/etc/artica-postfix/settings/Daemons/MimeDefangTraces"));
    $MimeDefangBlockNullSender=int(ReadFileIntoString("/etc/artica-postfix/settings/Daemons/MimeDefangBlockNullSender"));
    $MimeDefangBlockNullSenderAction=int(ReadFileIntoString("/etc/artica-postfix/settings/Daemons/MimeDefangBlockNullSenderAction"));
    $MimeDefangSpamMaxSize=int(ReadFileIntoString("/etc/artica-postfix/settings/Daemons/MimeDefangSpamMaxSize"));
    $EnableClamavDaemon=int(ReadFileIntoString("/etc/artica-postfix/settings/Daemons/EnableClamavDaemon"));
    $BigDebug=int(ReadFileIntoString("/etc/artica-postfix/settings/Daemons/MimeDefangBigDebug"));
    $EnableMimedefangUrlsChecker=int(ReadFileIntoString("/etc/artica-postfix/settings/Daemons/EnableMimedefangUrlsChecker"));
    $MimedefangUrlsCheckerTrustAutoWhitelist=int(ReadFileIntoString("/etc/artica-postfix/settings/Daemons/MimedefangUrlsCheckerTrustAutoWhitelist"));
    $MimedefangUrlsCheckerHostname=ReadFileIntoString("/etc/artica-postfix/settings/Daemons/MimedefangUrlsCheckerHostname");
    $MimeDefangNoTrustMyNets=ReadFileIntoString("/etc/artica-postfix/settings/Daemons/MimeDefangNoTrustMyNets");
   
    $MimeDefangBlockFromMatchesDomains=$MimeDefangForged;
    if($DebugMimeFilter==1){md_syslog("info", "Checking Forged message: $MimeDefangForged");}
    if($MimeDefangSpamMaxSize<100) {$MimeDefangSpamMaxSize=409600;}
    if($ScoreWithSpamVirus==0){$ScoreWithSpamVirus=20;}
    $MustScanUrls=1;


    if (is_email_base64($Sender)==1) {
        action_add_header('X-Filterz-Sender-Encoded', "Yes");
        $Sender=decode_email_addr($Sender);
    }
    action_add_header('X-Filterz-Sender', $Sender);
    if ($MimeDefangBlockNullSender==1) {
        if ($MimeDefangBlockNullSenderAction==0) {action_add_header('X-Filterz-block-nullSender', "Yes (tempfail)");}
        if ($MimeDefangBlockNullSenderAction==1) {action_add_header('X-Filterz-block-nullSender', "Yes (Quarantine)");}    
    }
    
    if ($MimeDefangBlockNullSender==0) {action_add_header('X-Filterz-block-nullSender', "No");}
    if ($MimeDefangForged==1) { action_add_header('X-Filterz-Check-forged', "Yes"); }
    if ($MimeDefangForged==0) { action_add_header('X-Filterz-Check-forged', "No"); }
    
    $isAutoReply=if_is_AnAutoreply($entity);
    if($MimeDefangNoTrustMyNets==0){ $SendIPIsInNet=is_in_myNetwork(); }
    $isOutgoing=is_outgoing();

    if($EnableMimedefangUrlsChecker==0){$MustScanUrls=0;}
    if($SendIPIsInNet==1){$MustScanUrls=0;}

    if ( defined($SendmailMacros{'auth_type'} ) ) {
        if($DebugMimeFilter==1){md_syslog("info", "Authenticated user...");}
        $isOutgoing=1;
        $IsAuthenticated=1;
        $MustScanUrls=0;
        $MyNetLogs="$MyNetLogs ( Authenticated)";
    }


    if($EnableMimedefangUrlsChecker==1){
        if($IsAuthenticated==0){
            if($SendIPIsInNet==0){
                $urls_filter_action_results=urls_filter_action();
                if($urls_filter_action_results>0){
                    if($DebugMimeFilter==1){md_syslog("info", "[$Sender]: filter() Urls filter urls_filter_action() = $urls_filter_action_results");}
                    if($urls_filter_action_results==1){$MustScanUrls=0;}
                    if($urls_filter_action_results==2){$MustScanUrls=1;}
                }
                if($MustScanUrls==1){ backup_message_in_temp(); }
            }
        }
    }

    if ($MustScanUrls==0) { action_add_header('X-Filterz-ScanUrls', "Yes"); }
    
    if($SendIPIsInNet==1){
       $isWhitelisted=1;
       $WhiteListedWhy="InMyNet";
    }


#-------------------------------------------------------------------------------------------------------------------------------------------------- NULL SENDER    
    if (length($Sender)<3) {
        if ($MimeDefangBlockNullSender==1) {
            if ($isOutgoing==0) {
                if ($IsAuthenticated==0) {
                    md_syslog("info", "NOQUEUE: NullSender: RCPT from $RelayHostname: 554 5.7.1 Message NullSender; Client host [$RelayAddr] blocked using NullSender; from=$Sender to=<$recipt> proto=milter");
                    if ($MimeDefangBlockNullSenderAction==0) { return action_tempfail("Sender Returned a null value");}
                    if ($MimeDefangBlockNullSenderAction==0) {action_quarantine_entire_message();return action_discard();}
                }
            }
        }
    }
    
#--------------------------------------------------------------------------------------------------------------------------------------------------
    if ($MimeDefangQuarteMail==1) {
        if ($SendIPIsInNet==1) {
            foreach (@Recipients) {
                $recipt=lc(canonicalize_email($_));
                if($MimeDefangQuartDest eq $recipt){
                    md_syslog("info", "VIRUS_LOVER: RCPT from $Sender [$RelayHostname] to $MimeDefangQuartDest skip antivirus and antispam");
                    $MimeDefangSpamAssassin=0;
                    $MimeDefangAutoCompress=0;
                    $MimeDefangFilterExtensions=0;
                    $MimeDefangClamav=0;
                    $MimeDefangDisclaimer=0;
                    $isWhitelisted=1;
                    $WhiteListedWhy="Quarantine mailbox";
                }
            }
        }
    }

#--------------------------------------------------------------------------------------------------------------------------------------------------
    if($SendIPIsInNet==0){
        if($IsAuthenticated==0){
            if($MimeDefangForged==1){
                if($DebugMimeFilter==1){md_syslog("info", "Parsing Forged message: is an outgoing message --> $isOutgoing");}
                    if($isOutgoing==1){
                        if($DebugMimeFilter==1){md_syslog("info", "Parsing Forged message: is an outgoing message --> YES -> FORGED");}
                        foreach (@Recipients) {
                            $recipt=canonicalize_email($_);
                            md_syslog("info", "NOQUEUE: Forged: RCPT from $RelayHostname: 554 5.7.1 Message Forged; Client host [$RelayAddr] blocked using Forged; from=$Sender to=<$recipt> proto=milter");
                        }
                        return action_tempfail("Message Forged $RelayHostname is not allowed to send from $Sender");
                    }
            }
        }
        if($isWhitelisted == 0 ){ $isWhitelisted=is_whitelisted_addr(); }
    }
#--------------------------------------------------------------------------------------------------------------------------------------------------
    if($DebugMimeFilter==1){md_syslog("info", "Processing: Next");}
    ($isAutoCompress,$isAutoCompressSize)=is_autocompress();
	

	if($SendIPIsInNet==1 ){action_add_header('X-Filterz-InMyNET', "Yes");}
	if($SendIPIsInNet==0 ){action_add_header('X-Filterz-InMyNET', "No");}
    if($isOutgoing==1){ if($DebugMimeFilter==1){md_syslog("info", "Outgoing message -> TRUE: (Autowhitelist) ");} action_add_header('X-Filterz-Outgoing', "Yes"); auto_whitelist(); }
	if($isOutgoing==0){ if($DebugMimeFilter==1){md_syslog("info", "Outgoing message -> FALSE");} action_add_header('X-Filterz-Outgoing', "No");  }
	if($MimeDefangClamav==1){
        ($AntivirusRule,$AntivirusAction)=Antivirus_action();
        action_add_header('X-Filterz-antivirus', "Enabled: Yes, With Rule ID:$AntivirusRule, Action: $AntivirusAction");
    }else{
        action_add_header('X-Filterz-antivirus', "Enabled: No (disabled)");
    }
	if($isWhitelisted==1){action_add_header('X-Filterz-Whitelisted', "Yes, $WhiteListedWhy"); }
    if($isWhitelisted==0){action_add_header('X-Filterz-Whitelisted', "No");}
	

    if($MimeDefangFilterExtensions==1){
    	my ($ext_zmd5,$ext_regex,$ext_notifs)=is_extension_rule();
	if(length($ext_zmd5)>0){
		$MimeDefangFilterExtensionsLocal=1; 
		$MimeDefangFilterExtensionsRegex=$ext_regex;
		$MimeDefangFilterExtensionsNotifs=$ext_notifs;
		if($DebugMimeFilter==1){md_syslog("info", "is_extension_rule() -> TRUE");}  
	}else{
		if($DebugMimeFilter==1){md_syslog("info", "is_extension_rule() -> FALSE");}   
	}
    }

    my $MIMEDEFANG_COUNTER=int(get_memcached("MIMEDEFANG_COUNTER"));
    $MIMEDEFANG_COUNTER=$MIMEDEFANG_COUNTER+1;
    set_memcached("MIMEDEFANG_COUNTER",$MIMEDEFANG_COUNTER);
                               
    if($DebugMimeFilter==1){md_syslog("info", "filter_begin() $RelayAddr (local:$SendIPIsInNet) isAutoReply($isAutoReply), DebugMimeFilter($DebugMimeFilter), MailArchiverEnabled($MailArchiverEnabled), MimeDefangClamav=$MimeDefangClamav, MimeDefangDisclaimer=$MimeDefangDisclaimer, MimeDefangSpamAssassin($MimeDefangSpamAssassin) AntivirusAction=$AntivirusAction");}
    if ($BigDebug) {md_syslog("info", "filter_begin()....265");}
    
    
    if ($EnableClamavDaemon==0) {
        action_delete_all_headers('X-Virus-Status');
        action_add_header('X-Virus-Status', "No, Uninstalled");
        $MimeDefangClamav=0;
    }else{
        if ($MimeDefangClamav==0) {
            action_delete_all_headers('X-Virus-Status');
            action_add_header('X-Virus-Status', "No, Disabled");
        }
    }
 
    
    
    if($MimeDefangClamav==1){
	    # Copy original message into work directory as an "mbox" file for
	    # virus-scanning
        if ($BigDebug) {md_syslog("info", "md_copy_orig_msg_to_work_dir_as_mbox_file()....284");}
	    md_copy_orig_msg_to_work_dir_as_mbox_file();

	    # Scan for viruses if any virus-scanners are installed
        if ($BigDebug) {md_syslog("info", "message_contains_virus()....284");}
	    my($code, $category, $action) = message_contains_virus();

	    # Lower level of paranoia - only looks for actual viruses
	    if($DebugMimeFilter==1){md_syslog("info", "Virus scanner $code, $category, $action Action:$AntivirusAction");}   
	    $FoundVirus = ($category eq "virus");

	    # Higher level of paranoia - takes care of "suspicious" objects
	    # $FoundVirus = ($action eq "quarantine");

	    if ($FoundVirus) {
            $FinalInfected=1;
            
            if ($VirusName =~ /.*?\.Spam-/) {
                $FinalInfected=0;
                $VirusAsSpam=1;
                foreach (@Recipients) {
                    $recipt=canonicalize_email($_);
                    md_syslog("info", "NOQUEUE: Quarantine: RCPT from $RelayHostname: 554 5.7.1 Message SPAM [$VirusName]; Client host [$RelayAddr] using ClamAV; from=$Sender to=<$recipt> proto=milter");
                }               
                
                return;
            }
            
            

            if($AntivirusAction==3){
               $FinalInfected=0;
               action_delete_all_headers('X-Virus-Status');
               action_add_header('X-Virus-Status', "Yes, name=$VirusName but whitelisted");
               return;
            }
    
            if($AntivirusAction==0){
                action_change_header('Subject', '*** VIRUS *** ' . $Subject);
                action_delete_all_headers('X-Virus-Status');
                action_add_header('X-Virus-Status', "Yes, name=$VirusName");
                return;
            }
    
            if($AntivirusAction==1){
                $FinalInfected=1;
                $myQuarDir=get_quarantine_dir();
                foreach (@Recipients) {
                    $recipt=canonicalize_email($_);
                    md_syslog("info", "NOQUEUE: Quarantine: RCPT from $RelayHostname: 554 5.7.1 Message infected [$VirusName]; Client host [$RelayAddr] blocked using ClamAV; from=$Sender to=<$recipt> proto=milter");
                }
                action_quarantine_entire_message();
                WriteToMysql(); 
                return action_discard();
            }
    
    
            if($AntivirusAction==2){
                $FinalInfected=1;
                foreach (@Recipients) { $recipt=canonicalize_email($_); md_syslog("info", "NOQUEUE: discard: RCPT from $RelayHostname: 554 5.7.1 Message infected [$VirusName]; Client host [$RelayAddr] blocked using ClamAV; from=$Sender to=<$recipt> proto=milter"); }
                md_syslog('warning', "Discarding because of virus $VirusName");
                return action_tempfail("Message infected $category found $VirusName");
            }
	    }



	    if ($action eq "tempfail") {
            # action_tempfail("Problem running virus-scanner");
            action_delete_all_headers('X-Virus-Status');
         	action_add_header('X-Virus-Status', "Unknown, Problem running virus scanner: code=$code");
            md_syslog('warning', "Problem running virus scanner: code=$code, category=$category, action=$action");
	    }
      }
}


#***********************************************************************
sub filter {
    
    my($entity, $fname, $ext, $type) = @_;
	my $MimeDefangFileHostingSubjectPrepend;
	my $MimeDefangFileHostingText;
	my $MimeDefangFileHostingLink;
	my $MimeDefangFileMaxDaysStore;
	my $path;
	my $urls_filter_action_results=0;
	my $sender_domain=get_domain($Sender);
	$SenderBackuped=$Sender;
	if($DebugMimeFilter==1){md_syslog("info", "[$Sender]: filter() $fname $type Infected:$FinalInfected Clamac:$MimeDefangClamav");}
    return if message_rejected(); # Avoid unnecessary work
    my $size = (stat($entity->bodyhandle->path))[7];
    $bh = $entity->bodyhandle();
    if (defined($bh)) { $path = $bh->path();}


    if($isOutgoing==1){$MustScanUrls=0;}
    if($IsAuthenticated==1){$MustScanUrls=0;}
    if($MimedefangUrlsCheckerTrustAutoWhitelist==1){
        if($MimeDefangAutoWhiteList==1){
	        if($isWhitelisted==1){ $MustScanUrls=0;}
        }
    }



    if($DebugMimeFilter==1){md_syslog("info", "[$Sender]: filter() Urls filter = $MustScanUrls");}

	my $AlwaysScan=1;

	if($type eq "text/plain"){
		if($DebugMimeFilter==1){md_syslog("info", "[$Sender]: $fname: $type SKIP FOR AV");}
		$AlwaysScan=0;
		if($MustScanUrls == 1){
		    if($DebugMimeFilter==1){md_syslog("info", "[$Sender]: $fname: $type -> ScanUrls");}
		    if( ScanUrls($entity,$path)==1 ) {  action_rebuild();}
		}
	}
	if($type eq "text/html"){
		if($DebugMimeFilter==1){md_syslog("info", "[$Sender]: $fname: $type SKIP FOR AV");}
		$AlwaysScan=0;
		if($MustScanUrls == 1){
		    if($DebugMimeFilter==1){md_syslog("info", "[$Sender]: $fname: $type -> ScanUrls");}
		    if( ScanUrls($entity,$path)==1 ) {  action_rebuild();}
		}
	}
	if($type eq "image/png"){
		if($DebugMimeFilter==1){md_syslog("info", "[$Sender]: $fname: $type SKIP FOR AV");}
		$AlwaysScan=0;
	}
	if($type eq "image/gif"){
		if($DebugMimeFilter==1){md_syslog("info", "[$Sender]: $fname: $type SKIP FOR AV");}
		$AlwaysScan=0;
	}



    if($DebugMimeFilter==1){md_syslog("info", "[$Sender]: $fname, $type $size bytes");}
   	MySQLFileNames($fname, $ext, $type,$size);

    if($DebugMimeFilter==1){md_syslog("info", "[$Sender]: $fname: AlwaysScan       == $AlwaysScan");}
    if($DebugMimeFilter==1){md_syslog("info", "[$Sender]: $fname: MimeDefangClamav == $MimeDefangClamav");}
    if($DebugMimeFilter==1){md_syslog("info", "[$Sender]: $fname: FinalInfected == $FinalInfected");}
	
    if($AlwaysScan==1){
		if( $MimeDefangClamav==1 ){
	   		if($FinalInfected==1){
				if($DebugMimeFilter==1){md_syslog("info", "[$Sender]: $fname:  * * * * ANTIVIRUS SCANNING * * * *");}
	    		my($code, $category, $action)=entity_contains_virus($entity);
				if($DebugMimeFilter==1){md_syslog("info", "[$Sender]: $fname: entity_contains_virus(entity) $code / $category / $action / $VirusName");}
				my $FoundVirus = ($category eq "virus");
				if($FoundVirus){return action_drop_with_warning("Virus $VirusName found in $fname type $type ($size bytes)"); }
			}
		}
	}

	if($MimeDefangFilterExtensionsLocal==1){
   	        if($DebugMimeFilter==1){md_syslog("info", "[$Sender]: filter_bad_filename(entity) regex { $MimeDefangFilterExtensionsRegex }");}
		if (filter_bad_filename($entity,$MimeDefangFilterExtensionsRegex)) {
			my $ext_notifs=$MimeDefangFilterExtensionsNotifs;
			$ext_notifs =~ s/%FILE/$fname/ig;
			$FinalExtFiltered=1;
			if($DebugMimeFilter==1){md_syslog("info", "[$Sender]: filter_bad_filename(entity) $fname, $type [MATCHES]!!");}
			return action_drop_with_warning($ext_notifs);
		}
    	}

    if(length($MimeDefangFileHostingLink)>1){
  		if($DebugMimeFilter==1){md_syslog("info", "function -> filter_filehosting()");}   
   		if(filter_filehosting($entity, $fname, $ext, $type)==1){
   			md_graphdefang_log('mail', 'CUSTOM_REPLACE', $fname);
   			my $ReplaceText="\n".($fname? "\"$fname\"" : "Attachment").":\n\n_URL_";
   			if($MimeDefangFileMaxDaysStore==0){$MimeDefangFileMaxDaysStore="unlimited";}
   			if(length($MimeDefangFileHostingText)>1){$ReplaceText=$MimeDefangFileHostingText.$ReplaceText;}
   			$ReplaceText =~ s/%s/$fname/g;
   			$ReplaceText =~ s/%d/$MimeDefangFileMaxDaysStore/g;
			if(length($MimeDefangFileHostingSubjectPrepend)>1){action_change_header("Subject", "[REPLACED] $Subject");}
			return action_replace_with_url($entity,"/var/spool/MIMEDefang_replaced",$MimeDefangFileHostingLink,$ReplaceText,$fname);
     	}
     }
   	

    if($MimeDefangAutoCompress==1){
	if($isAutoCompress==1){
    		if($DebugMimeFilter==1){md_syslog("info", "function -> filter_autocompress() $isAutoCompressSize MB");}
    		if(filter_autocompress($entity, $fname, $ext, $type)==1){
    			if($DebugMimeFilter==1){md_syslog("info", "filter_autocompress() return True, stop next step...");}
    			return 1;
    		}
	}
    }
	
		
    	if($DebugMimeFilter==1){md_syslog("info", "[$Sender]: filter() [OK] Accept");}
    	return action_accept();
}
#***********************************************************************

sub ScanUrls($$){
    my ($entity,$path)=@_;
    my $sizelimit = 1048576;
    my $regexurl  = "((?<=[^a-zA-Z0-9])(?:https?\:\/\/|[a-zA-Z0-9]{1,}\.{1}|\b)(?:\w{1,}\.{1}){1,5}(?:com|website|me|at|org|edu|gov|uk|kz|net|ca|de|jp|fr|au|us|ru|ch|it|nl|se|no|es|mil|iq|io|ac|ly|sm){1}(?:\/[a-zA-Z0-9]{1,})*)";
    my $UrlsIsFound=0;
    if (defined($path)) {
        if (-s $path <= $sizelimit) {
          if ($io = $entity->open("r")) {
            undef $/; # undef the seperator to slurp it in.
            $output = $io->getline;
            $NextOutput=$output;
            $io->close;
            while ($output =~ /((?<=[^a-zA-Z0-9])(?:http.*?\:\/\/|[a-zA-Z0-9]{1,}\.{1}|\b)(?:\w{1,}\.{1}){1,5}(?:com|online|info|net|lb|lt|ke|su|bg|th|tw|br|com\.br|za|uz|biz|ao|co|pro|org|edu|tk|gl|eu|pt|pl|us|gov|uk|net|ca|de|jp|fr|au|us|ru|ch|it|nl|se|no|es|mil|iq|io|ac|ly|sm|ms|[0-9]+){1}(?:\/[a-zA-Z0-9_\-]{1,})*)/gmi) {
                $urlFound=$&;
                if($DebugMimeFilter==1){md_syslog("info", "[$Sender]: URL Found(1):  $urlFound");}
                if ($urlFound =~ /(http|https|ftp|ftps):\/\//) {
                    if($DebugMimeFilter==1){md_syslog("info", "[$Sender]: URL Found(2):  $urlFound");}
                    $hashmd5 = md5_hex("$urlFound$Sender$MsgID");
                    $UrlResult=url_to_postgresql($hashmd5,$urlFound);
                    if($UrlResult==1){
                        if($DebugMimeFilter==1){md_syslog("info", "[$Sender]: Replace url....");}
                        $replaceString="https://$MimedefangUrlsCheckerHostname/smtpurls/$hashmd5";
                        $NextOutput =join( $replaceString, split($urlFound, $NextOutput));
                        $UrlsIsFound=1;
                    }
                }
            }
          }
        }
    }

    if($UrlsIsFound==1){
        if ($io = $entity->open("w")) {
            md_syslog("info", "[$Sender]: ScanUrls() rebuild $path");
            $io->print($NextOutput);
            $io->close;
            return 1;
        }

    }

    return 0;

}
sub url_encode {
    my $rv = shift;
    $rv =~ s/([^a-z\d\Q.-_~ \E])/sprintf("%%%2.2X", ord($1))/geix;
    $rv =~ tr/ /+/;
    return $rv;
}
sub str_replace($$$){
    my ($find,$replace,$str);
    $str = join( $replace, split($find, $str) );
    return $str;
}

sub filter_relay {
   my ($ip, $name) =  @_;
  if($DebugMimeFilter==1){md_syslog("info", "filter_relay() $ip, $name");}
   if($ip =~ /\A(?:127\.0\.0\.1|10\.0\.1\.|192\.168\.1\.)/) {
     return('ACCEPT_AND_NO_MORE_FILTERING', "ok");
   } else {
     return ('CONTINUE', "ok");
  }
}
#***********************************************************************
sub if_is_AnAutoreply() {
    	my($entity) = @_;
	my $value=lc($entity->head->get('Precedence', 0)) ;
	if($DebugMimeFilter==1){ md_syslog("info", "if_is_AnAutoreply() Head: Precedence = '$value'");}
	if($value =~ /^bulk$/) { return 1;}

	my $value=lc($entity->head->get('Preference', 0)) ;
	if($value =~ /^auto_reply$/) { return 1;}
	my $value=lc($entity->head->get('X-Precedence', 0)) ;
	if($value =~ /^auto_reply$/) { return 1;}
	my $value=lc($entity->head->get('X-Autoreply', 0)) ;
	if($value =~ /[a-z0-9]+/) { return 1;}
	my $value=lc($entity->head->get('X-Autorespond', 0)) ;
	if($value =~ /[a-z0-9]+/) { return 1;}
	my $value=lc($entity->head->get('X-Auto-Response-Suppress', 0)) ;
	if($value =~ /^oof$/) { return 1;}
	my $value=lc($entity->head->get('Auto-Submitted', 0)) ;
	if($value =~ /^auto-generated$/) { return 1;}
	my $value=lc($entity->head->get('Delivered-To', 0)) ;
	if($value =~ /^autoresponder$/) { return 1;}
	my $value=lc($entity->head->get('Auto-Submitted', 0)) ;
	if($value =~ /^auto-replied$/) { return 1;}
	my $value=lc($entity->head->get('X-Autogenerated', 0)) ;
	if($value =~ /^(forward|group|letter|mirror|redirect|reply)$/) { return 1;}
	my $value=lc($entity->head->get('X-AutoReply-From', 0)) ;
	if($value =~ /[a-z0-9]+/) { return 1;} 
	my $value=lc($entity->head->get('X-Mail-Autoreply', 0)) ;
	if($value =~ /[a-z0-9]+/) { return 1;}
	my $value=lc($entity->head->get('X-POST-MessageClass', 0)) ;
	if($value =~ /autoresponder/) { return 1;}
	my $value=lc($entity->head->get('X-Facebook-Notify', 0)) ;
	if($value =~ /[a-z0-9]+/) { return 1;} 
	my $value=lc($entity->head->get('Delivered-To', 0)) ;
	if($value =~ /autoresponder/) { return 1;}
	my $value=lc($entity->head->get('X-Autogenerated', 0)) ;
	if($value =~ /[a-z0-9]+/) { return 1;} 

	if($DebugMimeFilter==1){ md_syslog("info", "if_is_AnAutoreply() * * * * NO * * * *");}
	return 0;



}

sub is_filehosting_rule(){
	my $sender_domain=get_domain($Sender);
	my %hashLocalDomains = ();
	my %hashFileHostingRules = ();
	my $currentSender=canonicalize_email($Sender);
	my $EnableFileHosting=0;
	my $recipt;
	my $recipt_domain;
	
	if($DebugMimeFilter==1){
		md_syslog("info", "is_filehosting_rule() Checks localdomain for \"$sender_domain\" =>".$hashLocalDomains{ $sender_domain });
		
		}
	
	my $firstpattern="**";
	if($hashFileHostingRules{ $firstpattern }){
		if($DebugMimeFilter==1){md_syslog("info", "is_filehosting_rule() OK to create filehosting  \"$sender_domain\" => everyone");}
		return $hashFileHostingRules{ $firstpattern };
	}
	
	if($EnableFileHosting==0){return 0;}	
	
	
	foreach (@Recipients) {
        $recipt=canonicalize_email($_);
        $recipt_domain=get_domain($_);
        
        if($DebugMimeFilter==1){
        	md_syslog("info", "is_filehosting_rule() From $currentSender to $recipt");
        }
        
        $firstpattern="$currentSender$recipt";
        if($hashFileHostingRules{ $firstpattern }){
        	if($DebugMimeFilter==1){md_syslog("info", "is_filehosting_rule() OK compress  \"$currentSender\" => \"$recipt\"");}
       	 	return $hashFileHostingRules{ $firstpattern };
        }
             
        
       $firstpattern="$currentSender$recipt_domain";
	   if($hashFileHostingRules{ $firstpattern }){
        	if($DebugMimeFilter==1){md_syslog("info", "is_filehosting_rule() OK compress  \"$currentSender\" => \"$recipt_domain\"");}
       	 	return $hashFileHostingRules{ $firstpattern };
        }
        
       $firstpattern="$sender_domain$recipt";
	   if($hashFileHostingRules{ $firstpattern }){
        	if($DebugMimeFilter==1){md_syslog("info", "is_filehosting_rule() OK compress  \"$sender_domain\" => \"$recipt\"");}
       	 	return $hashFileHostingRules{ $firstpattern };
        } 
        
       $firstpattern="$sender_domain$recipt_domain";
	   if($hashFileHostingRules{ $firstpattern }){
        	if($DebugMimeFilter==1){md_syslog("info", "is_filehosting_rule() OK compress  \"$sender_domain\" => \"$recipt_domain\"");}
       	 	return $hashFileHostingRules{ $firstpattern };
        }                    
               
        
    }
    
    my $firstpattern="$currentSender*";
	if($hashFileHostingRules{ $firstpattern }){
		if($DebugMimeFilter==1){md_syslog("info", "is_filehosting_rule() OK compress  \"$currentSender\" => everyone");}
		return $hashFileHostingRules{ $firstpattern };
	}
	
	
	my $firstpattern="$sender_domain*";
	if($hashFileHostingRules{ $firstpattern }){
		if($DebugMimeFilter==1){md_syslog("info", "is_filehosting_rule() OK to create disclaimer  \"$sender_domain\" => everyone");}
		return $hashFileHostingRules{ $firstpattern };
	}
	
	if($DebugMimeFilter==1){md_syslog("info", "is_filehosting_rule() No rule match");}
	return 0;
}
#***********************************************************************






sub is_extension_rule(){
   my $ref;
   my %hashLocalDomains = ();
   my $currentSender;
   my $sender_domain;
   my $dbh;
   my $zmd5;
   my $extensions;
   my $notification;
   my $sql;
   
   $currentSender=canonicalize_email($Sender);
   $sender_domain=get_domain($Sender);
   if($hashLocalDomains{ $sender_domain } ==1){ return 1; }
   $dbh=DB_CONNECT();
   if(!$dbh){return ('','','','');}

   my $sql="SELECT zmd5,extensions,notification FROM mimedefang_extensions WHERE mailfrom='*' AND mailto='*'";
   if($DebugMimeFilter==1){md_syslog("info", "is_extension_rule: $sql");}
   $sth = $dbh->prepare("SELECT * FROM mimedefang_extensions WHERE mailfrom='*' AND mailto='*'");
   $sth->execute();
	while( $ref = $sth->fetchrow_hashref) {
		my $zmd5=trim($ref->{zmd5});
		if(length($zmd5)==0){next;}
		my $extensions=$ref->{extensions};
		my $notification=$ref->{notification};
	
		$sth->finish();
		$dbh->disconnect();
		if($DebugMimeFilter==1){md_syslog("info", "is_extension_rule: [OK]");}
		return ($zmd5,$extensions,$notification);
	
	
	  }
	foreach (@Recipients) {
		$recipt=canonicalize_email($_);
		$recipt_domain=get_domain($_);


  		$sql="SELECT zmd5,extensions,notification FROM mimedefang_extensions WHERE mailfrom='*' AND mailto='$recipt_domain'";
   		if($DebugMimeFilter==1){md_syslog("info", "is_extension_rule: $sql");}
		$sth = $dbh->prepare($sql);
		$sth->execute();
	  	while( $ref = $sth->fetchrow_hashref) {
			$zmd5=trim($ref->{zmd5});
			if(length($zmd5)==0){next;}
			$extensions=$ref->{extensions};
			$notification=$ref->{notification};
	
			$sth->finish();
			$dbh->disconnect();
			if($DebugMimeFilter==1){md_syslog("info", "is_extension_rule: [OK] { $extensions }");}
			return ($zmd5,$extensions,$notification);
		}
  		$sql="SELECT zmd5,extensions,notification FROM mimedefang_extensions WHERE mailfrom='*' AND mailto='$recipt'";
   		if($DebugMimeFilter==1){md_syslog("info", "is_extension_rule: $sql");}
		$sth = $dbh->prepare($sql);
		$sth->execute();
	  	while( $ref = $sth->fetchrow_hashref) {
			my $zmd5=trim($ref->{zmd5});
			if(length($zmd5)==0){next;}
			$extensions=$ref->{extensions};
			$notification=$ref->{notification};
	
			$sth->finish();
			$dbh->disconnect();
			if($DebugMimeFilter==1){md_syslog("info", "is_extension_rule: [OK] { $extensions }");}
			return ($zmd5,$extensions,$notification);
		}

  		$sql="SELECT zmd5,extensions,notification FROM mimedefang_extensions WHERE mailfrom='$sender_domain' AND mailto='*'";
   		if($DebugMimeFilter==1){md_syslog("info", "is_extension_rule: $sql");}
		$sth = $dbh->prepare($sql);
		$sth->execute();
	  	while( $ref = $sth->fetchrow_hashref) {
			my $zmd5=trim($ref->{zmd5});
			if(length($zmd5)==0){next;}
			$extensions=$ref->{extensions};
            $notification=$ref->{notification};
	
			$sth->finish();
			$dbh->disconnect();
			if($DebugMimeFilter==1){md_syslog("info", "is_extension_rule: [OK] { $extensions }");}
			return ($zmd5,$extensions,$notification);
		}

  		$sql="SELECT zmd5,extensions,notification FROM mimedefang_extensions WHERE mailfrom='$sender_domain' AND mailto='$recipt_domain'";
   		if($DebugMimeFilter==1){md_syslog("info", "is_extension_rule: $sql");}
		$sth = $dbh->prepare($sql);
		$sth->execute();
	  	while( $ref = $sth->fetchrow_hashref) {
			my $zmd5=trim($ref->{zmd5});
			if(length($zmd5)==0){next;}
			my $extensions=$ref->{extensions};
			my $notification=$ref->{notification};
	
			$sth->finish();
			$dbh->disconnect();
			if($DebugMimeFilter==1){md_syslog("info", "is_extension_rule: [OK] { $extensions }");}
			return ($zmd5,$extensions,$notification);
		}
  		$sql="SELECT zmd5,extensions,notification FROM mimedefang_extensions WHERE mailfrom='$sender_domain' AND mailto='$recipt'";
   		if($DebugMimeFilter==1){md_syslog("info", "is_extension_rule: $sql");}
		$sth = $dbh->prepare($sql);
		$sth->execute();
	  	while( $ref = $sth->fetchrow_hashref) {
			$zmd5=trim($ref->{zmd5});
			if(length($zmd5)==0){next;}
			$extensions=$ref->{extensions};
			$notification=$ref->{notification};
	
			$sth->finish();
			$dbh->disconnect();
			if($DebugMimeFilter==1){md_syslog("info", "is_extension_rule: [OK] { $extensions }");}
			return ($zmd5,$extensions,$notification);
		}



  		$sql="SELECT zmd5,extensions,notification FROM mimedefang_extensions WHERE mailfrom='$currentSender' AND mailto='*'";
   		if($DebugMimeFilter==1){md_syslog("info", "is_extension_rule: $sql");}
		$sth = $dbh->prepare($sql);
		$sth->execute();
	  	while( $ref = $sth->fetchrow_hashref) {
			$zmd5=trim($ref->{zmd5});
			if(length($zmd5)==0){next;}
			$extensions=$ref->{extensions};
			$notification=$ref->{notification};
	
			$sth->finish();
			$dbh->disconnect();
			if($DebugMimeFilter==1){md_syslog("info", "is_extension_rule: [OK] { $extensions }");}
			return ($zmd5,$extensions,$notification);
		}

  		$sql="SELECT zmd5,extensions,notification FROM mimedefang_extensions WHERE mailfrom='$currentSender' AND mailto='$recipt_domain'";
   		if($DebugMimeFilter==1){md_syslog("info", "is_extension_rule: $sql");}
		$sth = $dbh->prepare($sql);
		$sth->execute();
	  	while( $ref = $sth->fetchrow_hashref) {
			$zmd5=trim($ref->{zmd5});
			if(length($zmd5)==0){next;}
			$extensions=$ref->{extensions};
			$notification=$ref->{notification};
	
			$sth->finish();
			$dbh->disconnect();
			if($DebugMimeFilter==1){md_syslog("info", "is_extension_rule: [OK] { $extensions }");}
			return ($zmd5,$extensions,$notification);
		}
  		
        $sql="SELECT zmd5,extensions,notification FROM mimedefang_extensions WHERE mailfrom='$currentSender' AND mailto='$recipt'";
   		if($DebugMimeFilter==1){md_syslog("info", "is_extension_rule: $sql");}
		$sth = $dbh->prepare($sql);
		$sth->execute();
	  	while( $ref = $sth->fetchrow_hashref) {
			$zmd5=trim($ref->{zmd5});
			if(length($zmd5)==0){next;}
			$extensions=$ref->{extensions};
			$notification=$ref->{notification};
	
			$sth->finish();
			$dbh->disconnect();
			if($DebugMimeFilter==1){md_syslog("info", "is_extension_rule: [OK] { $extensions }");}
			return ($zmd5,$extensions,$notification);
		}

	  }


  if($DebugMimeFilter==1){md_syslog("info", "is_extension_rule: Checking $currentSender [FALSE]");}
  $sth->finish();
  $dbh->disconnect();
  return('','','','');
}
#***********************************************************************
sub filter_filehosting(){
	my($entity, $fname, $ext, $type) = @_;
	
	my $EnableFileHosting=1;
	my $MaxFileSize=0;
	if($DebugMimeFilter==1){md_syslog("info", "filter_filehosting()  Enabled ? = $EnableFileHosting");}
	
	if($type eq 'text/plain'){
		$sizebytes = (stat($entity->bodyhandle->path))[7];
		$sizeKo=$sizebytes/1024;
		$size=($sizeKo/1000);
		if($size<1){
			if($DebugMimeFilter==1){md_syslog("info", "filter_filehosting() text/plain skipped ($sizebytes bytes - $sizeKo Ko)..");}
			return 0;
		}
	}
	
	if($type eq 'text/html'){if($DebugMimeFilter==1){md_syslog("info", "filter_filehosting() text/html skipped..");}return 0;}
	$MaxFileSize=is_filehosting_rule();
	
	if($MaxFileSize==0){
		if($DebugMimeFilter==1){md_syslog("info", "filter_filehosting()  no rule set...");}
		return 0;	
	}
	
	if (defined($fname)) {
		$size = (stat($entity->bodyhandle->path))[7];
	    $size=($size/1024)/1000;
	    $Features{"Math::Round"} = load_modules('Math::Round') unless ($Features{"Math::Round"});
		if($Features{"Math::Round"}){$rounded = round( $size );}else{$rounded = sprintf "%.0f", $size;}
	}
	
	if($DebugMimeFilter==1){md_syslog("info", "filter_filehosting() $fname (ext) (size M about ($rounded) ) type: $type");}	
	
	return 1;
	
}


#***********************************************************************
sub filter_autocompress(){
	my($entity, $fname, $ext, $type) = @_;
	
	my $EnableCompression=1;
	my($comp_exts, $re);
	my $MaxCompress=0;
	
	if($DebugMimeFilter==1){md_syslog("info", "filter_autocompress()  $type $fname ($ext) Compress file more than $isAutoCompressSize MB");}
	
	if(!$Features{"Archive::Zip"}){
		if($DebugMimeFilter==1){md_syslog("info", "filter_autocompress() Archive::Zip is not installed, auto-compression will be not available...");}
		return 0;
	}
	if($type eq 'text/plain'){if($DebugMimeFilter==1){md_syslog("info", "filter_autocompress() text/plain skipped..");}return 0; }
	if($type eq 'text/x-vCard'){if($DebugMimeFilter==1){md_syslog("info", "filter_autocompress() text/x-vCard skipped..");}return 0; }
	if($type eq 'text/calendar'){if($DebugMimeFilter==1){md_syslog("info", "filter_autocompress() text/calendar skipped..");}return 0; }
	if($type eq 'text/html'){if($DebugMimeFilter==1){md_syslog("info", "filter_autocompress() text/html skipped..");}return 0;}
	if($type eq 'application/x-pkcs7-mime'){if($DebugMimeFilter==1){md_syslog("info", "filter_autocompress() x-pkcs7-mime skipped..");}return 0;}
	if($type eq 'multipart/signed'){if($DebugMimeFilter==1){md_syslog("info", "filter_autocompress() signed skipped..");}return 0;}
	if($type eq 'application/ms-tnef'){if($DebugMimeFilter==1){md_syslog("info", "filter_autocompress() ms-tnef skipped..");}return 0;}
	if($type eq 'application/pgp-signature'){if($DebugMimeFilter==1){md_syslog("info", "filter_autocompress() ms-tnef skipped..");}return 0;}

	if($type eq 'text/plain'){
		$sizebytes = (stat($entity->bodyhandle->path))[7];
		$sizeKo=$sizebytes/1024;
		$size=($sizeKo/1000);
		if($size<1){
			if($DebugMimeFilter==1){md_syslog("info", "filter_autocompress() text/plain skipped ($sizebytes bytes - $sizeKo Ko)..");}
			return 0;
		}
	}

	if($type eq 'image/jpeg'){
		$sizebytes = (stat($entity->bodyhandle->path))[7];
		$sizeKo=$sizebytes/1024;
		if($sizeKo <= 200){
			if($DebugMimeFilter==1){md_syslog("info", "filter_autocompress() image/jpeg skipped $sizeKo < 200 Ko..");}
			return 0;
		}
	}


	if($type eq 'image/png'){
		$sizebytes = (stat($entity->bodyhandle->path))[7];
		$sizeKo=$sizebytes/1024;
		if($sizeKo <= 200){
			if($DebugMimeFilter==1){md_syslog("info", "filter_autocompress() image/png skipped $sizeKo < 200 Ko..");}
			return 0;
		}
	}
	if($type eq 'image/gif'){
		$sizebytes = (stat($entity->bodyhandle->path))[7];
		$sizeKo=$sizebytes/1024;
		if($sizeKo <= 200){
			if($DebugMimeFilter==1){md_syslog("info", "filter_autocompress() image/gif skipped $sizeKo < 200 Ko..");}
			return 0;
		}
	}


	
		
	$comp_exts = '(zip|rar|7z|chm|dmg|hfs|lzh|lzma|nsis|udf|wim|xar|zip64|cab|arj|ace|tar|cpio|rpm|deb|iso|nrg|gz|tgz|bz2|Z|\{[^\}]+\})';
	$re = '\.' . $comp_exts . '\.*$';	
	
	if(re_match($entity, $re)){if($DebugMimeFilter==1){md_syslog("info", "filter_autocompress()  $fname is already compressed...");}return 0;}
	
	$MaxCompress=$isAutoCompressSize;
	
	if($size<$MaxCompress){
	   if($DebugMimeFilter==1){md_syslog("info", "filter_autocompress()  $size < $MaxCompress skip");}
	   return 0;
	}
	
	if (defined($fname)) {
	    $size = (stat($entity->bodyhandle->path))[7];
	    $size=($size/1024)/1000;
	    $Features{"Math::Round"} = load_modules('Math::Round') unless ($Features{"Math::Round"});
	    if($Features{"Math::Round"}){$rounded = round( $size );}else{$rounded = sprintf "%.0f", $size;}
	}
		
	
	if($DebugMimeFilter==1){md_syslog("info", "filter_autocompress() $fname (ext) (size M about ($rounded) ) type: $type");}

	my $zip = Archive::Zip->new();
	my $member;
	$member = $zip->addFile($entity->bodyhandle->path, $fname);
	$member->desiredCompressionMethod( COMPRESSION_DEFLATED );
	$member->desiredCompressionLevel( 9 );
	$member->setLastModFileDateTimeFromUnix( 318211200 );
	$fname = "$fname.zip" unless $fname =~ s/\.[^.]*$/\.zip/;
	$zip->writeToFileNamed("./Work/CUSTOM_$fname");
	custom_action_replace_with_file($entity, "./Work/CUSTOM_$fname", $fname);
	if($DebugMimeFilter==1){md_syslog("info", "filter_autocompress() $fname -> CUSTOM_$fname done...");}
	return 1;
}
#***********************************************************************
sub custom_action_replace_with_file ($$;$$$$$) {
   my($entity, $nfpath, $nfname, $nftype, $nfencode, $nfdispo) = @_;
   return 0 if (!in_filter_context("custom_action_replace_with_file"));
   $Actions{'replace_with_file'}++;
   $Action = "replace";
   $nftype = "application/octet-stream" unless defined($nftype);
   $nfname = "" unless defined($nfname);
   $nfencode = "base64" unless defined($nfencode);
   $nfdispo = "attachment" unless defined($nfdispo);
   $ReplacementEntity = MIME::Entity->build(Type => $nftype,
					     Encoding => $nfencode,
					     Path => $nfpath,
					     Filename => $nfname,
					     Disposition => $nfdispo);
   copy_or_link($nfpath, $entity->bodyhandle->path) or return 0;
   return 1;
}
#***********************************************************************

sub canonicalize_email ($) {
    my ($email) = @_;
    $email=trim($email);
    if (is_email_base64($email)==1) {$email=decode_email_addr($email); $email=trim($email); }
    $email =~ s/"//;
    $email =~ s/'//;
    $email =~ s/^<//;
    $email =~ s/>$//;
    return lc($email);
}
#***********************************************************************
sub get_domain ($) {
    my ($email) = @_;
    $email = canonicalize_email($email);
    $email =~ s/.*\@//;
    return $email;
}
#***********************************************************************
sub DB_CONNECT() {
	use DBI;
   	my $dbh;
    
    
	$dbh = DBI->connect("dbi:SQLite:/home/artica/SQLITE/spamassassin.db", "", "",{ AutoCommit => 1,RaiseError => 0,sqlite_see_if_its_a_number => 1 });
	
    if(!$dbh){
		my $error = $sth->err+" : "+$sth->errstr;
		md_syslog("info", "SQLITE Error: $error (963)");
		return false;
	}
	return $dbh;
}
#***********************************************************************
sub PG_CONNECT() {
	use DBI;
    my $dbh;
    my $InfluxUseRemoteIpaddr="";
    my $InfluxUseRemotePort=0;
    my $InfluxUseRemote=0;
    my $db_user_name ="";
    my $db_password="";
	my $dsn = "";
    
    $dsn =  "DBI:Pg:dbname=proxydb;host='/var/run/ArticaStats'";
    $db_user_name = "ArticaStats";
	$db_password = "";
    if($DebugMimeFilter==1){md_syslog("info", "PostGreSQL Connect: '$dsn'");}
	my $dbh = DBI->connect($dsn, $db_user_name, $db_password,{ RaiseError => 0 });
	if(!$dbh){
		my $error = $DBI::errstr;
		md_syslog("info", "PostGreSQL error: $error on DSN $dsn");
		return ($dbh,0);
	}
	return ($dbh,1);
}
#***********************************************************************


# %PROCEDURE: CopyMessage
# %ARGUMENTS:
#  src -- source filename
#  dest -- destination filename
# %RETURNS:
#  1 on success; 0 on failure.
# %DESCRIPTION:
#  Copies a file: reads the file and copies the data.
#***********************************************************************
sub CopyMessage {
	my($src, $dst) = @_;
 	open(IN, "<$src") or return 0;
 	if (!open(OUT, ">$dst")) {
		close(IN);
		return 0;
	}
	my($n, $string);
	while (($n = read(IN, $string, 4096)) > 0) {
		print OUT $string;
	}
	close(IN);
	close(OUT);
	return 1;
}
#***********************************************************************

sub url_to_postgresql($$){
    my ($hashmd5,$urlFound)=@_;
    my $currentSender=trim(canonicalize_email($Sender));
    my $message_id=$MsgID;
    my ($dbG,$Success)=PG_CONNECT();

    if($Success==0){return 0;}

    my $sql="INSERT INTO mimedefang_urls (urlmd5,zdate,sender,msgid,urlsource,scanned) VALUES ('$hashmd5',NOW(),'$currentSender','$MsgID','$urlFound',0) ON CONFLICT DO NOTHING";
    $sth = $dbG->prepare($sql);
    if(!$sth->execute() ){
        $error=$DBI::errstr;
        md_syslog("info", "SQL error: $error $sql function url_to_postgresql()");
        return 0
    }
    $sth->finish();
    $dbG->disconnect();
    return 1;

}
#***********************************************************************
sub auto_whitelist(){
   if($MimeDefangAutoWhiteList==0){return 0;}
   if($isAutoReply==1){return 0;}
   my $dbG;
   my $Success;

   my $currentSender=trim(canonicalize_email($Sender));
   if( length($currentSender)==0){return 0;}
   if($currentSender =~ /(root|postmaster|webmaster|www-data|robot|bounce|support|commercial|sales|contact|no-reply)(@|$)/){return 0;}

   my ($dbG,$Success)=PG_CONNECT();
   my $ref;
   if($Success==0){return 0;}
  
   

   foreach (@Recipients) {
      $recipt=canonicalize_email($_);
      if($recipt =~ /(root|postmaster|webmaster|robot|sales|contact|no-reply|bounce)(@|$)/){next;}
      $recipt_domain=get_domain($_);
      if($hashLocalDomains{ $recipt_domain } ==1){ next;}
      $hash = md5_hex("$currentSender$recipt");
      my $sql="INSERT INTO autowhite (zmd5,mailfrom,mailto) VALUES ('$hash','$recipt','$currentSender') ON CONFLICT DO NOTHING";
      $sth = $dbG->prepare($sql);
      if(!$sth->execute() ){
        $error=$DBI::errstr;
        md_syslog("info", "SQL error: $error $sql function auto_whitelist()");
      }
      $sth->finish();
    }
    
    $dbG->disconnect();
}
#***********************************************************************

sub is_whitelisted_addr(){
   my $ref;
   my %hashLocalDomains = ();
   my $currentSender=canonicalize_email($Sender);
   my $sender_domain=get_domain($Sender);
   my $dbG;
   my $pattern;
   my $Success;
   
    if($IsAuthenticated==1){
        if($hashLocalDomains{ $sender_domain } ==1){
            $WhiteListedWhy="$sender_domain - Internal / Authenticated";
            return 1;
        }
    }

   if($RelayAddr eq "127.0.0.1"){
	$WhiteListedWhy="$WhiteListedWhy;Loopback";
	if($DebugMimeFilter==1){md_syslog("info", "is_whitelisted_addr: Checking $RelayAddr matches \"127.0.0.1\" [OK]");}
	return 1; 
   }

if($SendIPIsInNet==0){
	if($hashLocalDomains{ $sender_domain } == 1){ 
		if($DebugMimeFilter==1){md_syslog("info", "Suspicious Sender: $Sender, $sender_domain is our local domain but was not sended from local net");}
		return 0;
	}
}


   if($hashLocalDomains{ $sender_domain } ==1){
        $WhiteListedWhy="$sender_domain - Internal";
        if($DebugMimeFilter==1){md_syslog("info", "$sender_domain is our local domain [OK]");}
        return 1;
    }
   ($dbG,$Success)=PG_CONNECT();
   
   if($Success==0){
        md_syslog("info", "PG_CONNECT: Failed! [FALSE]");
        return 0;
   }




	if($MimeDefangAutoWhiteList==0){
	  if($DebugMimeFilter==1){md_syslog("info", "is_whitelisted_addr: MimeDefangAutoWhiteList == 0; Checking $RelayAddr [FALSE]");}
	  return 0;
	}
    
    if($DebugMimeFilter==1){md_syslog("info", "is_whitelisted_addr: Checking $sender_domain against ALL (*)");}
    if($DebugMimeFilter==1){md_syslog("info", "is_whitelisted_addr: SELECT zmd5 FROM autowhite WHERE mailfrom='$sender_domain' AND mailto='*'");}
	$sth = $dbG->prepare("SELECT zmd5 FROM autowhite WHERE mailfrom='$sender_domain' AND mailto='*'");
	$sth->execute();
	while( $ref = $sth->fetchrow_hashref) {
        $pattern=trim($ref->{zmd5});
		if(length($pattern)>1){
			$WhiteListedWhy="$WhiteListedWhy;AUTOWHITE:ALL";
			if($DebugMimeFilter==1){md_syslog("info", "is_whitelisted_addr: Checking $sender_domain against ALL [OK]");}
			md_syslog("info", "[$MsgID]: AUTOWHITELIST from=<$Sender> to=<>");
			$sth->finish();$dbG->disconnect();return 1;
		}
	}
    
    if($DebugMimeFilter==1){md_syslog("info", "is_whitelisted_addr: Checking $currentSender against ALL (*)");}
    if($DebugMimeFilter==1){md_syslog("info", "is_whitelisted_addr: SELECT zmd5 FROM autowhite WHERE mailfrom='$currentSender' AND mailto='*'");}
	$sth = $dbG->prepare("SELECT zmd5 FROM autowhite WHERE mailfrom='$currentSender' AND mailto='*'");
	$sth->execute();
	while( $ref = $sth->fetchrow_hashref) {
        $pattern=trim($ref->{zmd5});
		if(length($pattern)>1){
			$WhiteListedWhy="$WhiteListedWhy;AUTOWHITE:ALL";
			if($DebugMimeFilter==1){md_syslog("info", "is_whitelisted_addr: Checking $currentSender against ALL [OK]");}
			md_syslog("info", "[$MsgID]: AUTOWHITELIST from=<$Sender> to=<>");
			$sth->finish();$dbG->disconnect();return 1;
		}
	}    

	foreach (@Recipients) {
		$recipt=canonicalize_email($_);
		$recipt_domain=get_domain($_);
		if($hashLocalDomains{ $recipt_domain } == 0){ next;}

		if($DebugMimeFilter==1){md_syslog("info", "is_whitelisted_addr: Checking $sender_domain against $recipt_domain");}
        if($DebugMimeFilter==1){md_syslog("info", "is_whitelisted_addr: SELECT zmd5 FROM autowhite WHERE mailfrom='$sender_domain' AND mailto='$recipt_domain'");}
		$sth = $dbG->prepare("SELECT zmd5 FROM autowhite WHERE mailfrom='$sender_domain' AND mailto='$recipt_domain'");
		$sth->execute();
	  	while( $ref = $sth->fetchrow_hashref) {
			$pattern=trim($ref->{zmd5});
			if(length($pattern)>1){
				$WhiteListedWhy="$WhiteListedWhy;AUTOWHITE:$recipt_domain";
				md_syslog("info", "[$MsgID]: AUTOWHITELIST from=<$Sender> to=<$recipt>");
				if($DebugMimeFilter==1){md_syslog("info", "is_whitelisted_addr: Checking $currentSender against $recipt [OK]");}
				$sth->finish();$dbG->disconnect();return 1;
			}
		}

		if($DebugMimeFilter==1){md_syslog("info", "is_whitelisted_addr: Checking $currentSender against $recipt");}
		$sth = $dbG->prepare("SELECT zmd5 FROM autowhite WHERE mailfrom='$currentSender' AND mailto='$recipt'");
		$sth->execute();
	  	while( $ref = $sth->fetchrow_hashref) {
			$pattern=trim($ref->{zmd5});
			if(length($pattern)>1){
				$WhiteListedWhy="$WhiteListedWhy;AUTOWHITE:$recipt";
				if($DebugMimeFilter==1){md_syslog("info", "is_whitelisted_addr: Checking $currentSender against $recipt [OK]");}
				md_syslog("info", "[$MsgID]: AUTOWHITELIST from=<$Sender> to=<$recipt>");
				$sth->finish();$dbG->disconnect();return 1;
			}
		}
	  }


  if($DebugMimeFilter==1){md_syslog("info", "is_whitelisted_addr: Not in whitelist database $currentSender/$RelayAddr [FALSE]");}
  $sth->finish();
  $dbG->disconnect();
  return 0;
}
#***********************************************************************

sub is_outgoing(){
   my $currentSender=canonicalize_email($Sender);
   my $sender_domain=get_domain($Sender);
   my %hashLocalDomains = ();
   if($hashLocalDomains{ $sender_domain } ==1){ return 1; }
   return 0;
}
# ------------------------------------------------------- Backup rules checking -------------------------------------------------------
sub backup_rules(){
    my %backup_rules=();
    my $currentSender=canonicalize_email($Sender);
    my $sender_domain=get_domain($Sender);

    foreach (@Recipients) {
        $recipt=canonicalize_email($_);
        $recipt_domain=get_domain($_);
        if($DebugMimeFilter==1){md_syslog("info", "function -> backup_rules() CHECKING $currentSender/$sender_domain to $recipt/$recipt_domain");}
        
        if (exists $backup_rules{$currentSender}{$recipt}) {
            $retentiontime=$backup_rules{$currentSender}{$recipt}{RETENTION};
            if($DebugMimeFilter==1){md_syslog("info", "function -> backup_rules() $currentSender -> $recipt matches retention in minutes:$retentiontime (1275)");}
            return $retentiontime;
        }
        
        if (exists $backup_rules{$currentSender}{$recipt_domain}) {
            $retentiontime=$backup_rules{$currentSender}{$recipt_domain}{RETENTION};
            if($DebugMimeFilter==1){md_syslog("info", "function -> backup_rules() $currentSender -> $recipt_domain retention in minutes:$retentiontime (1275)");}
            return $retentiontime;
        }         
        
        if (exists $backup_rules{$sender_domain}{$recipt}) {
            $retentiontime=$backup_rules{$currentSender}{$recipt}{RETENTION};
            if($DebugMimeFilter==1){md_syslog("info", "function -> backup_rules() $sender_domain -> $recipt matches retention in minutes:$retentiontime (1335)");}
            return $retentiontime;
        }
        
        if (exists $backup_rules{"*"}{$recipt}) {
            $retentiontime=$backup_rules{"*"}{$recipt}{RETENTION};
            if($DebugMimeFilter==1){md_syslog("info", "function -> backup_rules() * -> $recipt matches retention in minutes:$retentiontime (1346)");}
            return $retentiontime;
        }
        if (exists $backup_rules{"*"}{$recipt_domain}) {
            $retentiontime=$backup_rules{"*"}{$recipt_domain}{RETENTION};
            if($DebugMimeFilter==1){md_syslog("info", "function -> backup_rules() * -> $recipt_domain matches (1356) retention in minutes:$retentiontime (1289)");}
            return $retentiontime;
        }        
 

    }
       
    if (exists $backup_rules{$currentSender}{"*"}) {
        $retentiontime=$backup_rules{$currentSender}{"*"}{RETENTION};
        if($DebugMimeFilter==1){md_syslog("info", "function -> backup_rules() $currentSender -> * matches retention in minutes:$retentiontime (1370)");}
        return $retentiontime;
    }
    if (exists $backup_rules{$sender_domain}{"*"}) {
        $retentiontime=$backup_rules{$sender_domain}{"*"}{RETENTION};
        if($DebugMimeFilter==1){md_syslog("info", "function -> backup_rules() $sender_domain -> * matches retention in minutes:$retentiontime (1380)");}
        return $retentiontime;
    }
    if (exists $backup_rules{"*"}{"*"}) {
        $retentiontime=$backup_rules{"*"}{"*"}{RETENTION};
        if($DebugMimeFilter==1){md_syslog("info", "function -> backup_rules() * -> * matches retention in minutes:$retentiontime (1380)");}
        return $retentiontime;
    }   
    if($DebugMimeFilter==1){md_syslog("info", "function -> backup_rules() NOTHING MATCHES RULES default action = do nothing");}
    return 0;

}
# ------------------------------------------------------- URLS rules settings -------------------------------------------------------
sub urls_filter_action(){
    if($EnableMimedefangUrlsChecker==0){ return 0; }
    my %urls_rules=();
    my $currentSender=canonicalize_email($Sender);
    my $sender_domain=get_domain($Sender);
    my $ID;
    my $type;

    foreach (@Recipients) {
        $recipt=canonicalize_email($_);
        $recipt_domain=get_domain($_);
        if($DebugMimeFilter==1){md_syslog("info", "function -> urls_filter_action() CHECKING $currentSender/$sender_domain to $recipt/$recipt_domain");}

        if (exists $urls_rules{$currentSender}{$recipt}) {
            $ID=$urls_rules{$currentSender}{$recipt}{MD5};
            $type=$urls_rules{$currentSender}{$recipt}{TYPE};
            if($DebugMimeFilter==1){md_syslog("info", "function -> urls_filter_action() $currentSender -> $recipt matches $ID action = $type (1268)");}
            return $type;
        }

        if (exists $urls_rules{$currentSender}{$recipt_domain}) {
            $ID=$urls_rules{$currentSender}{$recipt_domain}{MD5};
            $type=$urls_rules{$currentSender}{$recipt_domain}{TYPE};
            if($DebugMimeFilter==1){md_syslog("info", "function -> urls_filter_action() $currentSender -> $recipt_domain matches $ID action = $type (1275)");}
            return $type;
        }

        if (exists $urls_rules{$sender_domain}{$recipt}) {
            $ID=$urls_rules{$sender_domain}{$recipt}{MD5};
            $type=$urls_rules{$sender_domain}{$recipt}{TYPE};
            if($DebugMimeFilter==1){md_syslog("info", "function -> urls_filter_action() $sender_domain -> $recipt matches $ID action = $type (1282)");}
            return $type;
        }

        if (exists $urls_rules{"*"}{$recipt}) {
            $ID=$urls_rules{"*"}{$recipt}{MD5};
            $type=$urls_rules{"*"}{$recipt}{TYPE};
            if($DebugMimeFilter==1){md_syslog("info", "function -> urls_filter_action() * -> $recipt matches $ID action = $type (1289)");}
            return $type;
        }
        if (exists $urls_rules{"*"}{$recipt_domain}) {
            $ID=$urls_rules{"*"}{$recipt_domain}{MD5};
            $type=$urls_rules{"*"}{$recipt_domain}{TYPE};
            if($DebugMimeFilter==1){md_syslog("info", "function -> urls_filter_action() * -> $recipt_domain matches $ID action = $type (1289)");}
            return $type;
        }


    }

    if (exists $urls_rules{$currentSender}{"*"}) {
        $ID=$urls_rules{$currentSender}{"*"}{MD5};
        $type=$urls_rules{$currentSender}{"*"}{TYPE};
        if($DebugMimeFilter==1){md_syslog("info", "function -> urls_filter_action() $currentSender -> * matches $ID action = $type (1305)");}
        return $type;
    }
    if (exists $urls_rules{$sender_domain}{"*"}) {
        $ID=$urls_rules{$sender_domain}{"*"}{MD5};
        $type=$urls_rules{$sender_domain}{"*"}{TYPE};
        if($DebugMimeFilter==1){md_syslog("info", "function -> urls_filter_action() $sender_domain -> * matches $ID action = $type (1311)");}
        return $type;
    }
    if (exists $urls_rules{"*"}{"*"}) {
        $ID=$urls_rules{"*"}{"*"}{MD5};
        $type=$urls_rules{"*"}{"*"}{TYPE};
        if($DebugMimeFilter==1){md_syslog("info", "function -> urls_filter_action() * -> * matches $ID action = $type (1311)");}
        return $type;
    }
    if($DebugMimeFilter==1){md_syslog("info", "function -> urls_filter_action() NOTHING MATCHES RULES default action = 0");}
    return 0;

}


# ------------------------------------------------------- Antivirus rules settings -------------------------------------------------------
sub Antivirus_action(){
    if($MimeDefangClamav==0){
        if($DebugMimeFilter==1){md_syslog("info", "Antivirus_action() MimeDefangClamav == $MimeDefangClamav SKIP");}
        return (0,0);
    }
    my %antivirus_rules=();
    my $currentSender=canonicalize_email($Sender);
    my $sender_domain=get_domain($Sender);
    my $ID;
    my $type;
  
    foreach (@Recipients) {
        $recipt=canonicalize_email($_);
        $recipt_domain=get_domain($_);
        if($DebugMimeFilter==1){md_syslog("info", "function -> Antivirus_action() CHECKING $currentSender/$sender_domain to $recipt/$recipt_domain");}
        
        if (exists $antivirus_rules{$currentSender}{$recipt}) {
            $ID=$antivirus_rules{$currentSender}{$recipt}{MD5};
            $type=$antivirus_rules{$currentSender}{$recipt}{TYPE};
            if($DebugMimeFilter==1){md_syslog("info", "function -> Antivirus_action() $currentSender -> $recipt matches $ID action = $type (1268)");}
            return ($ID,$type);
        }
        
        if (exists $antivirus_rules{$currentSender}{$recipt_domain}) {
            $ID=$antivirus_rules{$currentSender}{$recipt_domain}{MD5};
            $type=$antivirus_rules{$currentSender}{$recipt_domain}{TYPE};
            if($DebugMimeFilter==1){md_syslog("info", "function -> Antivirus_action() $currentSender -> $recipt_domain matches $ID action = $type (1275)");}
            return ($ID,$type);
        }         
        
        if (exists $antivirus_rules{$sender_domain}{$recipt}) {
            $ID=$antivirus_rules{$sender_domain}{$recipt}{MD5};
            $type=$antivirus_rules{$sender_domain}{$recipt}{TYPE};
            if($DebugMimeFilter==1){md_syslog("info", "function -> Antivirus_action() $sender_domain -> $recipt matches $ID action = $type (1282)");}
            return ($ID,$type);
        }
        
        if (exists $antivirus_rules{"*"}{$recipt}) {
            $ID=$antivirus_rules{"*"}{$recipt}{MD5};
            $type=$antivirus_rules{"*"}{$recipt}{TYPE};
            if($DebugMimeFilter==1){md_syslog("info", "function -> Antivirus_action() * -> $recipt matches $ID action = $type (1289)");}
            return ($ID,$type);
        }
        if (exists $antivirus_rules{"*"}{$recipt_domain}) {
            $ID=$antivirus_rules{"*"}{$recipt_domain}{MD5};
            $type=$antivirus_rules{"*"}{$recipt_domain}{TYPE};
            if($DebugMimeFilter==1){md_syslog("info", "function -> Antivirus_action() * -> $recipt_domain matches $ID action = $type (1289)");}
            return ($ID,$type);
        }        
 

    }
       
    if (exists $antivirus_rules{$currentSender}{"*"}) {
        $ID=$antivirus_rules{$currentSender}{"*"}{MD5};
        $type=$antivirus_rules{$currentSender}{"*"}{TYPE};
        if($DebugMimeFilter==1){md_syslog("info", "function -> Antivirus_action() $currentSender -> * matches $ID action = $type (1305)");}
        return ($ID,$type);
    }
    if (exists $antivirus_rules{$sender_domain}{"*"}) {
        $ID=$antivirus_rules{$sender_domain}{"*"}{MD5};
        $type=$antivirus_rules{$sender_domain}{"*"}{TYPE};
        if($DebugMimeFilter==1){md_syslog("info", "function -> Antivirus_action() $sender_domain -> * matches $ID action = $type (1311)");}
        return ($ID,$type);
    }
    if (exists $antivirus_rules{"*"}{"*"}) {
        $ID=$antivirus_rules{"*"}{"*"}{MD5};
        $type=$antivirus_rules{"*"}{"*"}{TYPE};
        if($DebugMimeFilter==1){md_syslog("info", "function -> Antivirus_action() * -> * matches $ID action = $type (1311)");}
        return ($ID,$type);
    }   
    if($DebugMimeFilter==1){md_syslog("info", "function -> Antivirus_action() NOTHING MATCHES RULES default action = 0");}
    return ('',0);

}
# ------------------------------------------------------- Global Whitelist rule -------------------------------------------------------
sub smtpd_milter_maps(){
    my %smtpd_milter_maps_rules=();
    my $currentSender=canonicalize_email($Sender);
    my $sender_domain=get_domain($Sender);
    my $hostname=$RelayHostname;
    my ($RelayDomain) = $hostname =~ m/([^.]+\.[^.]+$)/;
    $RelayDomain=lc($RelayDomain);
    $hostname=lc($hostname);
    if($DebugMimeFilter==1){md_syslog("info", "function -> smtpd_milter_maps() <$currentSender> ?");}
    if (exists $smtpd_milter_maps_rules{$currentSender} ) {
        md_syslog("info", "NOQUEUE: accept: from $RelayHostname ip=<$RelayAddr> Globally Whitelisted; from=$Sender proto=milter");
        return 1;
    }
    if($DebugMimeFilter==1){md_syslog("info", "function -> smtpd_milter_maps() <$sender_domain> ?");}
    if (exists $smtpd_milter_maps_rules{$sender_domain} ) {
        md_syslog("info", "NOQUEUE: accept: from $RelayHostname ip=<$RelayAddr> Globally Whitelisted; from=$Sender proto=milter");
        return 1;
    }
    
    if($DebugMimeFilter==1){md_syslog("info", "function -> smtpd_milter_maps() <$hostname> ?");}
    if (exists $smtpd_milter_maps_rules{$hostname} ) {
        md_syslog("info", "NOQUEUE: accept: from $RelayHostname ip=<$RelayAddr> Globally Whitelisted; from=$Sender proto=milter");
        return 1;
    }
    if($DebugMimeFilter==1){md_syslog("info", "function -> smtpd_milter_maps() <$RelayDomain> ?");}
    if (exists $smtpd_milter_maps_rules{$RelayDomain} ) {
        md_syslog("info", "NOQUEUE: accept: from $RelayHostname ip=<$RelayAddr> Globally Whitelisted; from=$Sender proto=milter");
        return 1;
    }
    
    if($DebugMimeFilter==1){md_syslog("info", "function -> smtpd_milter_maps() NOTHING TO WHITELIST ");}
    return 0;
}



# ------------------------------------------------------- AntiSpam rules -------------------------------------------------------
sub antispam_rules(){
    my %antispam_rules=();
    my $currentSender=canonicalize_email($Sender);
    my $sender_domain=get_domain($Sender);
    $XSpamStatusHeaderScore=int(ReadFileIntoString("/etc/artica-postfix/settings/Daemons/XSpamStatusHeaderScore"));
    $SpamAssBlockWithRequiredScore=int(ReadFileIntoString("/etc/artica-postfix/settings/Daemons/SpamAssBlockWithRequiredScore"));
    $SpamAssassinRequiredScore=int(ReadFileIntoString("/etc/artica-postfix/settings/Daemons/SpamAssassinRequiredScore"));
    $MimeDefangQuarteMail=int(ReadFileIntoString("/etc/artica-postfix/settings/Daemons/MimeDefangQuarteMail"));
    $MimeDefangMaxQuartime=int(ReadFileIntoString("/etc/artica-postfix/settings/Daemons/MimeDefangMaxQuartime"));
    $MimeDefangQuartDest=lc(trim(ReadFileIntoString("/etc/artica-postfix/settings/Daemons/MimeDefangQuartDest")));
    
    
    if($MimeDefangMaxQuartime==0){$MimeDefangMaxQuartime=129600;}
    if($XSpamStatusHeaderScore==0){$XSpamStatusHeaderScore=4;}    
    if($SpamAssBlockWithRequiredScore==0){$SpamAssBlockWithRequiredScore=15;}
    if($SpamAssassinRequiredScore==0){$SpamAssassinRequiredScore=8;}
  
    foreach (@Recipients) {
        $recipt=canonicalize_email($_);
        $recipt_domain=get_domain($_);
        if($DebugMimeFilter==1){md_syslog("info", "function -> antispam_action() CHECKING $currentSender/$sender_domain to $recipt/$recipt_domain");}
        
        if (exists $antispam_rules{$currentSender}{$recipt}) {
            $XSpamStatusHeaderScore=$antispam_rules{$currentSender}{$recipt}{XSpamStatusHeaderScore};
            $SpamAssBlockWithRequiredScore=$antispam_rules{$currentSender}{$recipt}{SpamAssBlockWithRequiredScore};
            $SpamAssassinRequiredScore=$antispam_rules{$currentSender}{$recipt}{SpamAssassinRequiredScore};
            $MimeDefangQuarteMail=$antispam_rules{$currentSender}{$recipt}{MimeDefangQuarteMail};
            $MimeDefangMaxQuartime=$antispam_rules{$currentSender}{$recipt}{MimeDefangMaxQuartime};
            $MimeDefangQuartDest=$antispam_rules{$currentSender}{$recipt}{MimeDefangQuartDest};
            if($DebugMimeFilter==1){md_syslog("info", "function -> antispam_action() $currentSender -> $recipt matches Header score=$XSpamStatusHeaderScore, block with $SpamAssBlockWithRequiredScore Forward to email $MimeDefangQuarteMail (1275)");}
            return ($XSpamStatusHeaderScore,$SpamAssBlockWithRequiredScore,$SpamAssassinRequiredScore,$MimeDefangQuarteMail,$MimeDefangQuartDest,$MimeDefangMaxQuartime);
        }
        
        if (exists $antispam_rules{$currentSender}{$recipt_domain}) {
            $XSpamStatusHeaderScore=$antispam_rules{$currentSender}{$recipt_domain}{XSpamStatusHeaderScore};
            $SpamAssBlockWithRequiredScore=$antispam_rules{$currentSender}{$recipt_domain}{SpamAssBlockWithRequiredScore};
            $SpamAssassinRequiredScore=$antispam_rules{$currentSender}{$recipt_domain}{SpamAssassinRequiredScore};
            $MimeDefangQuarteMail=$antispam_rules{$currentSender}{$recipt_domain}{MimeDefangQuarteMail};
            $MimeDefangMaxQuartime=$antispam_rules{$currentSender}{$recipt_domain}{MimeDefangMaxQuartime};
            $MimeDefangQuartDest=$antispam_rules{$currentSender}{$recipt_domain}{MimeDefangQuartDest};
            if($DebugMimeFilter==1){md_syslog("info", "function -> antispam_action() $currentSender -> $recipt_domain Header score=$XSpamStatusHeaderScore, block with $SpamAssBlockWithRequiredScore Forward to email $MimeDefangQuarteMail (1275)");}
            return ($XSpamStatusHeaderScore,$SpamAssBlockWithRequiredScore,$SpamAssassinRequiredScore,$MimeDefangQuarteMail,$MimeDefangQuartDest,$MimeDefangMaxQuartime);
        }         
        
        if (exists $antispam_rules{$sender_domain}{$recipt}) {
            $XSpamStatusHeaderScore=$antispam_rules{$currentSender}{$recipt}{XSpamStatusHeaderScore};
            $SpamAssBlockWithRequiredScore=$antispam_rules{$currentSender}{$recipt}{SpamAssBlockWithRequiredScore};
            $SpamAssassinRequiredScore=$antispam_rules{$currentSender}{$recipt}{SpamAssassinRequiredScore};
            $MimeDefangQuarteMail=$antispam_rules{$currentSender}{$recipt}{MimeDefangQuarteMail};
            $MimeDefangMaxQuartime=$antispam_rules{$currentSender}{$recipt}{MimeDefangMaxQuartime};
            $MimeDefangQuartDest=$antispam_rules{$currentSender}{$recipt}{MimeDefangQuartDest};
            if($DebugMimeFilter==1){md_syslog("info", "function -> antispam_action() $sender_domain -> $recipt matches (1335)");}
            return ($XSpamStatusHeaderScore,$SpamAssBlockWithRequiredScore,$SpamAssassinRequiredScore,$MimeDefangQuarteMail,$MimeDefangQuartDest,$MimeDefangMaxQuartime);
        }
        
        if (exists $antispam_rules{"*"}{$recipt}) {
            $XSpamStatusHeaderScore=$antispam_rules{"*"}{$recipt}{XSpamStatusHeaderScore};
            $SpamAssBlockWithRequiredScore=$antispam_rules{"*"}{$recipt}{SpamAssBlockWithRequiredScore};
            $SpamAssassinRequiredScore=$antispam_rules{"*"}{$recipt}{SpamAssassinRequiredScore};
            $MimeDefangQuarteMail=$antispam_rules{"*"}{$recipt}{MimeDefangQuarteMail};
            $MimeDefangMaxQuartime=$antispam_rules{"*"}{$recipt}{MimeDefangMaxQuartime};
            $MimeDefangQuartDest=$antispam_rules{"*"}{$recipt}{MimeDefangQuartDest};
            if($DebugMimeFilter==1){md_syslog("info", "function -> antispam_action() * -> $recipt matches (1346)");}
            return ($XSpamStatusHeaderScore,$SpamAssBlockWithRequiredScore,$SpamAssassinRequiredScore,$MimeDefangQuarteMail,$MimeDefangQuartDest,$MimeDefangMaxQuartime);
        }
        if (exists $antispam_rules{"*"}{$recipt_domain}) {
            $XSpamStatusHeaderScore=$antispam_rules{"*"}{$recipt_domain}{XSpamStatusHeaderScore};
            $SpamAssBlockWithRequiredScore=$antispam_rules{"*"}{$recipt_domain}{SpamAssBlockWithRequiredScore};
            $SpamAssassinRequiredScore=$antispam_rules{"*"}{$recipt_domain}{SpamAssassinRequiredScore};
            $MimeDefangQuarteMail=$antispam_rules{"*"}{$recipt_domain}{MimeDefangQuarteMail};
            $MimeDefangMaxQuartime=$antispam_rules{"*"}{$recipt_domain}{MimeDefangMaxQuartime};
            $MimeDefangQuartDest=$antispam_rules{"*"}{$recipt_domain}{MimeDefangQuartDest};
            if($DebugMimeFilter==1){md_syslog("info", "function -> antispam_action() * -> $recipt_domain matches (1356) Header score=$XSpamStatusHeaderScore, block with $SpamAssBlockWithRequiredScore Forward to email $MimeDefangQuarteMail (1289)");}
            return ($XSpamStatusHeaderScore,$SpamAssBlockWithRequiredScore,$SpamAssassinRequiredScore,$MimeDefangQuarteMail,$MimeDefangQuartDest,$MimeDefangMaxQuartime);
        }        
 

    }
       
    if (exists $antispam_rules{$currentSender}{"*"}) {
        $XSpamStatusHeaderScore=$antispam_rules{$currentSender}{"*"}{XSpamStatusHeaderScore};
        $SpamAssBlockWithRequiredScore=$antispam_rules{$currentSender}{"*"}{SpamAssBlockWithRequiredScore};
        $SpamAssassinRequiredScore=$antispam_rules{$currentSender}{"*"}{SpamAssassinRequiredScore};
        $MimeDefangQuarteMail=$antispam_rules{$currentSender}{"*"}{MimeDefangQuarteMail};
        $MimeDefangMaxQuartime=$antispam_rules{$currentSender}{"*"}{MimeDefangMaxQuartime};
        $MimeDefangQuartDest=$antispam_rules{$currentSender}{"*"}{MimeDefangQuartDest};
        if($DebugMimeFilter==1){md_syslog("info", "function -> antispam_action() $currentSender -> * matches (1370)");}
        return ($XSpamStatusHeaderScore,$SpamAssBlockWithRequiredScore,$SpamAssassinRequiredScore,$MimeDefangQuarteMail,$MimeDefangQuartDest,$MimeDefangMaxQuartime);
    }
    if (exists $antispam_rules{$sender_domain}{"*"}) {
        $XSpamStatusHeaderScore=$antispam_rules{$sender_domain}{"*"}{XSpamStatusHeaderScore};
        $SpamAssBlockWithRequiredScore=$antispam_rules{$sender_domain}{"*"}{SpamAssBlockWithRequiredScore};
        $SpamAssassinRequiredScore=$antispam_rules{$sender_domain}{"*"}{SpamAssassinRequiredScore};
        $MimeDefangQuarteMail=$antispam_rules{$sender_domain}{"*"}{MimeDefangQuarteMail};
        $MimeDefangMaxQuartime=$antispam_rules{$sender_domain}{"*"}{MimeDefangMaxQuartime};
        $MimeDefangQuartDest=$antispam_rules{$sender_domain}{"*"}{MimeDefangQuartDest};
        if($DebugMimeFilter==1){md_syslog("info", "function -> antispam_action() $sender_domain -> * matches (1380)");}
        return ($XSpamStatusHeaderScore,$SpamAssBlockWithRequiredScore,$SpamAssassinRequiredScore,$MimeDefangQuarteMail,$MimeDefangQuartDest,$MimeDefangMaxQuartime);
    }
    if (exists $antispam_rules{"*"}{"*"}) {
        $XSpamStatusHeaderScore=$antispam_rules{"*"}{"*"}{XSpamStatusHeaderScore};
        $SpamAssBlockWithRequiredScore=$antispam_rules{"*"}{"*"}{SpamAssBlockWithRequiredScore};
        $SpamAssassinRequiredScore=$antispam_rules{"*"}{"*"}{SpamAssassinRequiredScore};
        $MimeDefangQuarteMail=$antispam_rules{"*"}{"*"}{MimeDefangQuarteMail};
        $MimeDefangMaxQuartime=$antispam_rules{"*"}{"*"}{MimeDefangMaxQuartime};
        $MimeDefangQuartDest=$antispam_rules{"*"}{"*"}{MimeDefangQuartDest};
        if($DebugMimeFilter==1){md_syslog("info", "function -> antispam_action() * -> * matches (1311)");}
        return ($XSpamStatusHeaderScore,$SpamAssBlockWithRequiredScore,$SpamAssassinRequiredScore,$MimeDefangQuarteMail,$MimeDefangQuartDest,$MimeDefangMaxQuartime);
    }   
    if($DebugMimeFilter==1){md_syslog("info", "function -> antispam_action() NOTHING MATCHES RULES default action = Header score=$XSpamStatusHeaderScore Quarantine score:$SpamAssassinRequiredScore Block Score:$SpamAssBlockWithRequiredScore");}
    return ($XSpamStatusHeaderScore,$SpamAssBlockWithRequiredScore,$SpamAssassinRequiredScore,$MimeDefangQuarteMail,$MimeDefangQuartDest,$MimeDefangMaxQuartime);

}

sub is_autocompress(){
   if($MimeDefangAutoCompress==0){
	if($DebugMimeFilter==1){md_syslog("info", "is_autocompress() MimeDefangAutoCompress == $MimeDefangAutoCompress SKIP");}
	return (0,0);
   }

   my $currentSender=canonicalize_email($Sender);
   my $sender_domain=get_domain($Sender);
   my $dbh=DB_CONNECT();
   my $ref;
   if(!$dbh){return (0,0);}

   foreach (@Recipients) {
        $recipt=canonicalize_email($_);
        $recipt_domain=get_domain($_);

    	$sth = $dbh->prepare("SELECT zmd5,maxsize FROM mimedefang_autocompress WHERE mailfrom = '*'  AND mailto='*'");
   	$sth->execute();
  	while( $ref = $sth->fetchrow_hashref) { my $zmd5=$ref->{zmd5}; if( length($zmd5)>5 ){$sth->finish();$dbh->disconnect(); return (1,$ref->{maxsize});} } 
	$sth->finish();

  	$sth = $dbh->prepare("SELECT zmd5,maxsize FROM mimedefang_autocompress WHERE mailfrom = '*'  AND mailto='$recipt_domain'");
   	$sth->execute();
  	while( $ref = $sth->fetchrow_hashref){ my $zmd5=$ref->{zmd5}; if( length($zmd5)>5 ){$sth->finish();$dbh->disconnect();return (1,$ref->{maxsize});} }
	$sth->finish();

  	$sth = $dbh->prepare("SELECT zmd5,maxsize FROM mimedefang_autocompress WHERE mailfrom = '$sender_domain'  AND mailto='*'");
   	$sth->execute();
  	while( $ref = $sth->fetchrow_hashref){ my $zmd5=$ref->{zmd5}; if( length($zmd5)>5 ){$sth->finish();$dbh->disconnect(); return (1,$ref->{maxsize});} }
	$sth->finish();

  	$sth = $dbh->prepare("SELECT zmd5,maxsize FROM mimedefang_autocompress WHERE mailfrom = '$currentSender'  AND mailto='$recipt_domain'");
   	$sth->execute();
  	while( $ref = $sth->fetchrow_hashref){ my $zmd5=$ref->{zmd5}; if( length($zmd5)>5 ){$sth->finish();$dbh->disconnect(); return (1,$ref->{maxsize});} } 
	$sth->finish();

  	$sth = $dbh->prepare("SELECT zmd5,maxsize FROM mimedefang_autocompress WHERE mailfrom = '$currentSender'  AND mailto='*'");
   	$sth->execute();
  	while( $ref = $sth->fetchrow_hashref){ my $zmd5=$ref->{zmd5}; if( length($zmd5)>5 ){$sth->finish();$dbh->disconnect(); return (1,$ref->{maxsize});} } 
	$sth->finish();

  }
  $dbh->disconnect();
  return (0,0);

}

sub is_disclaimer(){
   my $ref;
   my %hashLocalDomains = ();
   my $currentSender=canonicalize_email($Sender);
   my $sender_domain=get_domain($Sender);
   my $dbh=DB_CONNECT();
   my $ref;
   if(!$dbh){return ('','','','');}

   my $sql="SELECT zmd5,textcontent,htmlcontent FROM mimedefang_disclaimer WHERE mailfrom='*' AND mailto='*'";
   if($DebugMimeFilter==1){md_syslog("info", "is_disclaimer: $sql");}
   $sth = $dbh->prepare("SELECT * FROM mimedefang_disclaimer WHERE mailfrom='*' AND mailto='*'");
   $sth->execute();
	while( $ref = $sth->fetchrow_hashref) {
		my $zmd5=trim($ref->{zmd5});
		if(length($zmd5)==0){next;}
		my $textcontent=$ref->{textcontent};
		my $htmlcontent=$ref->{htmlcontent};
	
		$sth->finish();
		$dbh->disconnect();
		if($DebugMimeFilter==1){md_syslog("info", "is_disclaimer: [OK]");}
		return ($zmd5,$textcontent,$htmlcontent);
	
	
	  }
	foreach (@Recipients) {
		$recipt=canonicalize_email($_);
		$recipt_domain=get_domain($_);


  		my $sql="SELECT zmd5,textcontent,htmlcontent FROM mimedefang_disclaimer WHERE mailfrom='*' AND mailto='$recipt_domain'";
   		if($DebugMimeFilter==1){md_syslog("info", "is_disclaimer: $sql");}
		$sth = $dbh->prepare($sql);
		$sth->execute();
	  	while( $ref = $sth->fetchrow_hashref) {
			my $zmd5=trim($ref->{zmd5});
			if(length($zmd5)==0){next;}
			my $textcontent=$ref->{textcontent};
			my $htmlcontent=$ref->{htmlcontent};
	
			$sth->finish();
			$dbh->disconnect();
			if($DebugMimeFilter==1){md_syslog("info", "is_disclaimer: [OK] { $extensions }");}
			return ($zmd5,$textcontent,$htmlcontent);
		}
  		my $sql="SELECT zmd5,textcontent,htmlcontent FROM mimedefang_disclaimer WHERE mailfrom='*' AND mailto='$recipt'";
   		if($DebugMimeFilter==1){md_syslog("info", "is_disclaimer: $sql");}
		$sth = $dbh->prepare($sql);
		$sth->execute();
	  	while( $ref = $sth->fetchrow_hashref) {
			my $zmd5=trim($ref->{zmd5});
			if(length($zmd5)==0){next;}
			my $textcontent=$ref->{textcontent};
			my $htmlcontent=$ref->{htmlcontent};
	
			$sth->finish();
			$dbh->disconnect();
			if($DebugMimeFilter==1){md_syslog("info", "is_disclaimer: [OK] { $extensions }");}
			return ($zmd5,$textcontent,$htmlcontent);
		}

  		my $sql="SELECT zmd5,textcontent,htmlcontent FROM mimedefang_disclaimer WHERE mailfrom='$sender_domain' AND mailto='*'";
   		if($DebugMimeFilter==1){md_syslog("info", "is_disclaimer: $sql");}
		$sth = $dbh->prepare($sql);
		$sth->execute();
	  	while( $ref = $sth->fetchrow_hashref) {
			my $zmd5=trim($ref->{zmd5});
			if(length($zmd5)==0){next;}
			my $textcontent=$ref->{textcontent};
			my $htmlcontent=$ref->{htmlcontent};
	
			$sth->finish();
			$dbh->disconnect();
			if($DebugMimeFilter==1){md_syslog("info", "is_disclaimer: [OK] { $extensions }");}
			return ($zmd5,$textcontent,$htmlcontent);
		}

  		my $sql="SELECT zmd5,textcontent,htmlcontent FROM mimedefang_disclaimer WHERE mailfrom='$sender_domain' AND mailto='$recipt_domain'";
   		if($DebugMimeFilter==1){md_syslog("info", "is_disclaimer: $sql");}
		$sth = $dbh->prepare($sql);
		$sth->execute();
	  	while( $ref = $sth->fetchrow_hashref) {
			my $zmd5=trim($ref->{zmd5});
			if(length($zmd5)==0){next;}
			my $textcontent=$ref->{textcontent};
			my $htmlcontent=$ref->{htmlcontent};
	
			$sth->finish();
			$dbh->disconnect();
			if($DebugMimeFilter==1){md_syslog("info", "is_disclaimer: [OK] { $extensions }");}
			return ($zmd5,$textcontent,$htmlcontent);
		}
  		my $sql="SELECT zmd5,textcontent,htmlcontent FROM mimedefang_disclaimer WHERE mailfrom='$sender_domain' AND mailto='$recipt'";
   		if($DebugMimeFilter==1){md_syslog("info", "is_disclaimer: $sql");}
		$sth = $dbh->prepare($sql);
		$sth->execute();
	  	while( $ref = $sth->fetchrow_hashref) {
			my $zmd5=trim($ref->{zmd5});
			if(length($zmd5)==0){next;}
			my $textcontent=$ref->{textcontent};
			my $htmlcontent=$ref->{htmlcontent};
	
			$sth->finish();
			$dbh->disconnect();
			if($DebugMimeFilter==1){md_syslog("info", "is_disclaimer: [OK] { $extensions }");}
			return ($zmd5,$textcontent,$htmlcontent);
		}



  		my $sql="SELECT zmd5,textcontent,htmlcontent FROM mimedefang_disclaimer WHERE mailfrom='$currentSender' AND mailto='*'";
   		if($DebugMimeFilter==1){md_syslog("info", "is_disclaimer: $sql");}
		$sth = $dbh->prepare($sql);
		$sth->execute();
	  	while( $ref = $sth->fetchrow_hashref) {
			my $zmd5=trim($ref->{zmd5});
			if(length($zmd5)==0){next;}
			my $textcontent=$ref->{textcontent};
			my $htmlcontent=$ref->{htmlcontent};
	
			$sth->finish();
			$dbh->disconnect();
			if($DebugMimeFilter==1){md_syslog("info", "is_disclaimer: [OK] { $extensions }");}
			return ($zmd5,$textcontent,$htmlcontent);
		}

  		my $sql="SELECT zmd5,textcontent,htmlcontent FROM mimedefang_disclaimer WHERE mailfrom='$currentSender' AND mailto='$recipt_domain'";
   		if($DebugMimeFilter==1){md_syslog("info", "is_disclaimer: $sql");}
		$sth = $dbh->prepare($sql);
		$sth->execute();
	  	while( $ref = $sth->fetchrow_hashref) {
			my $zmd5=trim($ref->{zmd5});
			if(length($zmd5)==0){next;}
			my $textcontent=$ref->{textcontent};
			my $htmlcontent=$ref->{htmlcontent};
	
			$sth->finish();
			$dbh->disconnect();
			if($DebugMimeFilter==1){md_syslog("info", "is_disclaimer: [OK] { $extensions }");}
			return ($zmd5,$textcontent,$htmlcontent);
		}
  		my $sql="SELECT zmd5,textcontent,htmlcontent FROM mimedefang_disclaimer WHERE mailfrom='$currentSender' AND mailto='$recipt'";
   		if($DebugMimeFilter==1){md_syslog("info", "is_disclaimer: $sql");}
		$sth = $dbh->prepare($sql);
		$sth->execute();
	  	while( $ref = $sth->fetchrow_hashref) {
			my $zmd5=trim($ref->{zmd5});
			if(length($zmd5)==0){next;}
			my $textcontent=$ref->{textcontent};
			my $htmlcontent=$ref->{htmlcontent};
	
			$sth->finish();
			$dbh->disconnect();
			if($DebugMimeFilter==1){md_syslog("info", "is_disclaimer: [OK] { $extensions }");}
			return ($zmd5,$textcontent,$htmlcontent);
		}

	  }


  if($DebugMimeFilter==1){md_syslog("info", "is_disclaimer: Checking $currentSender [FALSE]");}
  $sth->finish();
  $dbh->disconnect();
  return('','','','');
}
#***********************************************************************

#  This is called for multipart "container" parts such as message/rfc822.
#  You cannot replace the body (because multipart parts have no body),
#  but you should check for bad filenames.
#***********************************************************************



sub filter_multipart {
    my($entity, $fname, $ext, $type) = @_;
    if($DebugMimeFilter==1){md_syslog("info", "filter_multipart() start -> $entity, $fname, $ext, $type");}
    return if message_rejected(); # Avoid unnecessary work


    if (filter_bad_filename($entity)) {
        action_notify_administrator("A MULTIPART attachment of type $type, named $fname was dropped.\n");
        return action_drop_with_warning("An attachment of type $type, named $fname was removed from this document as it\nconstituted a security hazard.  If you require this document, please contact\nthe sender and arrange an alternate means of receiving it.\n");
    }

    # Block message/partial parts
    if (lc($type) eq "message/partial") {
        action_bounce("MIME type message/partial not accepted here");
        return;
    }
    
    if($DebugMimeFilter==1){md_syslog("info", "filter_multipart() -> return action_accept()");}
    return action_accept();
}

#***********************************************************************\n";
sub filter_recipient ($$$$$$$$$) {
	my($recipient, $sender, $ip, $hostname, $first, $helo, $rcpt_mailer, $rcpt_host, $rcpt_addr) = @_;
	my $now = time();

	$SendIPIsInNet=is_in_myNetwork();
    	$isOutgoing=is_outgoing();
    	if($SendIPIsInNet==1){$isWhitelisted=1; }else{ $isWhitelisted=is_whitelisted_addr(); }
    
	
    if (  defined($SendmailMacros{'auth_type'} ) ) {
        if($DebugMimeFilter==1){md_syslog("info", "filter_recipient:: Authenticated user...");}
        $isOutgoing=1;
        $IsAuthenticated=1;
    }

    if($SendIPIsInNet==1){return ("CONTINUE", "ok");}
    if($isWhitelisted==1){return ("CONTINUE", "ok");}
    if($isOutgoing==1){return ("CONTINUE", "ok");}
    if($IsAuthenticated==1){return ("CONTINUE", "ok");}
    
    return ("CONTINUE", "ok");

}
#***********************************************************************\n\n";


#***********************************************************************
# %PROCEDURE: defang_warning
# %ARGUMENTS:
#  oldfname -- the old file name of an attachment
#  fname -- the new "defanged" name
# %RETURNS:
#  A warning message
# %DESCRIPTION:
#  This function customizes the warning message when an attachment
#  is defanged.
#***********************************************************************
sub defang_warning {
    my($oldfname, $fname) = @_;
    md_syslog("info", "defang_warning() start -> $oldfname $fname");
    return
	"An attachment named '$oldfname' was converted to '$fname'.\n" .
	"To recover the file, right-click on the attachment and Save As\n" .
	"'$oldfname'\n";
}
#***********************************************************************
# If SpamAssassin found SPAM, append report.  We do it as a separate
# attachment of type text/plain
sub filter_end {
	my $DisclaimerMd5;
	my $disclength=0;
    my($entity) = @_;
   # my $head = $entity->head;
    my $DisclaimerMd5Head;
    my $IsSpanEgine=0;
    my $recipt='';
    my $reportLenght=0;
	my %hashLocalDomains = ();

    # If you want quarantine reports, uncomment next line
    # send_quarantine_notifications();

    # IMPORTANT NOTE:  YOU MUST CALL send_quarantine_notifications() AFTER
    # ANY PARTS HAVE BEEN QUARANTINED.  SO IF YOU MODIFY THIS FILTER TO
    # QUARANTINE SPAM, REWORK THE LOGIC TO CALL send_quarantine_notifications()
    # AT THE END!!!
    
    if ($GloballyWhiteListed==1) {return true;}    

    $MessageSize = (-s './INPUTMSG');

    if ( message_rejected() ){
        if($DebugMimeFilter==1){md_syslog("info", "filter_end() rejected message aborting...");}
        WriteToMysql();
        return;
    }

    	# Spam checks if SpamAssassin is installed
	$FinalScoreReport='';
	action_change_header("X-Scanned-By","Policies v1.0");
    if ($isWhitelisted==1) {
        action_change_header("X-Filterz-white","Policies v1.0");
    }
    $isNotSpamAssassinWhy="";
	$IsSpanEgine=isSpamAssassin();
	if($DebugMimeFilter==1){md_syslog("info", "filter_end() MimeDefangForged=$MimeDefangForged; MimeDefangSpamAssassin:$MimeDefangSpamAssassin, SendIPIsInNet:$SendIPIsInNet, isWhitelisted:$isWhitelisted Size=$MessageSize");}

	if($SendIPIsInNet==1){ if($NotTrustLocalNet==0){ action_add_header('X-Filterz-TrustedNet','yes'); } }
        
    if($SendIPIsInNet==0){
        if ($isWhitelisted==0 ) {
            if($MimeDefangForged == 1 ){
                my $header_from_domain='';
                my $header_from=$entity->head->get('From', 0);
                if($DebugMimeFilter==1){md_syslog("info", "filter_end() Not in my Net, Not header_from: '$header_from'");}
                if ( $header_from =~ /<(.+?)@(.+?)>/) { $header_from_domain=lc($2); }
                if (length($header_from_domain)>0) {
                    if($DebugMimeFilter==1){md_syslog("info", "filter_end() header_from Domain: '$header_from_domain'");}
                    if($hashLocalDomains{ $header_from_domain } ==1){
                        md_syslog("info", "NOQUEUE: Reject: RCPT from $RelayHostname: 554 5.7.1 BadFromMyDomains ; Client host [$RelayAddr] blocked by using $header_from_domain; from=$Sender to=<$recipt> proto=milter");
                        $MIMEDEFANG_SPAM=int(get_memcached("MIMEDEFANG_SPAM"));
                        $MIMEDEFANG_SPAM=$MIMEDEFANG_SPAM+1;
                        set_memcached("MIMEDEFANG_SPAM",$MIMEDEFANG_SPAM);      
                        return action_discard();
                    }
                    if($DebugMimeFilter==1){md_syslog("info", "filter_end() header_from Domain: '$header_from_domain' not in my local domains...Continue");}
                }
                
            }
        }
    }



	action_add_header('X-Filterz-RunAS',"$IsSpanEgine $isNotSpamAssassinWhy");
	if($IsSpanEgine==1){
		my $MySender=canonicalize_email($Sender);
		if(length($MySender)<2){$MySender=canonicalize_email($SenderBackuped);}
    	my($hits, $req, $names, $report) = spam_assassin_check("/etc/spamassassin/local.cf");
    	$FinalScoreSpam=$hits;
        my $FinalScoreExch=$hits;
        if ($FinalScoreExch>9) {$FinalScoreExch=9;}
        if($VirusAsSpam==1){  $FinalScoreSpam=$FinalScoreSpam+$ScoreWithSpamVirus;} 
    	$FinalScoreReport=$report;
		$reportLenght=length($FinalScoreReport);
		($XSpamStatusHeaderScore,$SpamAssBlockWithRequiredScore,$SpamAssassinRequiredScore,$MimeDefangQuarteMail,$MimeDefangQuartDest,$MimeDefangMaxQuartime)=antispam_rules();
		
		
        
		if($DebugMimeFilter==1){md_syslog("info", "SpamAssassin hit: $hits/$SpamAssassinRequiredScore Ban:$SpamAssBlockWithRequiredScore size=$MessageSize from=$Sender");}
		action_add_header('X-Filterz-SpamScore', "$hits Header:$XSpamStatusHeaderScore Quarantine:$SpamAssassinRequiredScore Ban:$SpamAssBlockWithRequiredScore report:$reportLenght bytes");
		action_delete_header("X-Spam-Score");
		action_add_header('X-Spam-Score',$hits);
        
        if ($hits>2) {
            action_add_header('X-MS-Exchange-Organization-SCL',$FinalScoreExch);
            action_add_header('X-Microsoft-Antispam',"UriScan:;BCL:$FinalScoreExch;PCL:0;RULEID:;SRVR:NONE;");
        }


        
    	if (int($hits) >= $SpamAssBlockWithRequiredScore ) {
			foreach (@Recipients) {
				$recipt=canonicalize_email($_);
				md_syslog("info", "NOQUEUE: reject: RCPT from $RelayHostname: 554 5.7.1 Message score $hits; Client host [$RelayAddr] blocked using Spamassassin; from=$Sender to=<$recipt> proto=milter");
			}
			md_syslog("info", "SCRINFO: $RelayHostname from=$Sender to=<$recipt>  $hits > = $SpamAssBlockWithRequiredScore -> ACTION DISCARD!");
            $MIMEDEFANG_SPAM=int(get_memcached("MIMEDEFANG_SPAM"));
            $MIMEDEFANG_SPAM=$MIMEDEFANG_SPAM+1;
            set_memcached("MIMEDEFANG_SPAM",$MIMEDEFANG_SPAM);
			WriteToMysql(); 
			return action_discard();
		}


		if (int($hits) >= $SpamAssassinRequiredScore) {
			action_change_header("X-Spam-Score", "$hits $names");
			action_add_header("X-Quarantine", "Yes");
			$myQuarDir=get_quarantine_dir();
			foreach (@Recipients) {
				$recipt=canonicalize_email($_);
				md_syslog("info", "NOQUEUE: Quarantine: RCPT from $RelayHostname: 554 5.7.1 Message score $hits; Client host [$RelayAddr] blocked using Spamassassin; from=$Sender to=<$recipt> proto=milter");
			}
			md_syslog("info", "ARTICA-ACTION: QUARANTINE <$myQuarDir>");
			WriteToMysql();
			action_quarantine_entire_message();
            $MIMEDEFANG_SPAM=int(get_memcached("MIMEDEFANG_SPAM"));
            $MIMEDEFANG_SPAM=$MIMEDEFANG_SPAM+1;
            WriteToFile("$myQuarDir/REPORT",$report);
            WriteToFile("$myQuarDir/MimeDefangQuartDest",$MimeDefangQuartDest);
            WriteToFile("$myQuarDir/MimeDefangQuarteMail",$MimeDefangQuarteMail);
            WriteToFile("$myQuarDir/MimeDefangMaxQuartime",$MimeDefangMaxQuartime);
            set_memcached("MIMEDEFANG_SPAM",$MIMEDEFANG_SPAM);            
			return action_discard();
		}

		if(int($hits) >= $XSpamStatusHeaderScore ){
			md_syslog("info", "SCRINFO: $RelayHostname from=$Sender to=<$recipt>  $hits > = $XSpamStatusHeaderScore -> X-Spam-Status");
            action_add_header('X-CustomSpam','Bulk Mail');
			action_add_header('X-Spam-Status','yes');
            action_add_header('X-Spam-Flag','Yes');
			$hits=0;

		}

    }
    
# --------------------- Check and add disclaimer ------------- START
	if($DebugMimeFilter==1){md_syslog("info", "[$Sender]: MimeDefangDisclaimer = $MimeDefangDisclaimer");}
	if($MimeDefangDisclaimer==1){
		my ($DisclaimerMd5,$textcontent,$htmlcontent)=is_disclaimer();
		if($DebugMimeFilter==1){md_syslog("info", "[$Sender]: is_disclaimer() return $DisclaimerMd5");}
		if(length($DisclaimerMd5)>5){
			$FinalDisclaimer=1;
			if($DebugMimeFilter==1){md_syslog("debug", "[$Sender]: TEXT ".length($textcontent)." bytes");}
			if(length($textcontent)>0){append_text_boilerplate($entity, $textcontent, 0);}
			if($DebugMimeFilter==1){md_syslog("info", "[$Sender]: HTML ".length($htmlcontent)." bytes");}
			if(length($htmlcontent)>0){append_html_boilerplate($entity, $htmlcontent, 0);}
			
		}
		 
	}
# --------------------- Check and add disclaimer ------------- END

	foreach (@Recipients) {
	  $recipt=$recipt.";".canonicalize_email($_);
	}

    my $rententiontime=0;
    if ($MailArchiverEnabled==1) {
        $rententiontime=backup_rules();
        if($DebugMimeFilter==1){md_syslog("info", "[$Sender]: isBackup == $rententiontime minutes, [$MsgID]");}
    }
	
	if( $rententiontime > 0 ){
		if ( -e "./INPUTMSG" ) {
            my $totalsize = -s "./INPUTMSG";
            my $headersize = 0;
            my $ctx = Digest::SHA1->new;
            open(FILE, "<./INPUTMSG") && do {
                binmode(FILE);
                while (<FILE>) {
                    $ctx->add($_);
                    $headersize += length;
                    last if ( /^$/ );
                }
            $header_hash = $ctx->b64digest;
            $ctx->addfile(*FILE);
            $body_hash = $ctx->b64digest;
            close(FILE);    
            $body_length = $totalsize - $headersize;
                
            
            }
		}
		$MessageSize=$body_length;
  		my  $wd = supported MIME::WordDecoder 'UTF-8';
  		my $perlSubject = $wd->decode($Subject);
	    $FinalLog="$perlSubject|||$Sender|||$recipt|||$body_hash|||$body_length|||$rententiontime";
		WriteToFile("/var/spool/MIMEDefang/BACKUP/$MsgID.email",$FinalLog);
		CopyMessage("./INPUTMSG","/var/spool/MIMEDefang/BACKUP/$MsgID.msg");
		$FinalBackUp=1;
		if($DebugMimeFilter==1){md_syslog("info", "Backup Message: ID: $backupID, $body_hash, $recipt length $body_length retention time $rententiontime minutes");}
        md_syslog("info", "BACKUP: hostname=<$RelayHostname> ip=<$RelayAddr> message backuped file=</var/spool/MIMEDefang/BACKUP/$MsgID.email> from=$Sender to=<$recipt> proto=milter");
	}
	 
    WriteToMysql(); 

}

sub backup_message_in_temp(){
    foreach (@Recipients) {
	  $recipt=$recipt.";".canonicalize_email($_);
	}

    $FinalLog="$Sender|||$recipt";
    WriteToFile("/var/spool/MIMEDefang/BACKUP/$MsgID.BAK",$FinalLog);
    CopyMessage("./INPUTMSG","/var/spool/MIMEDefang/BACKUP/$MsgID.backup");

}

sub isSpamAssassin(){
	if($MimeDefangSpamAssassin==0){ 
	if($DebugMimeFilter==1){md_syslog("info", "[$Sender]: SpamAssassin not enabled, skipping Spamassassin");$isNotSpamAssassinWhy="Feature disabled";}
	return 0;}

	if($isOutgoing==1){
		if($DebugMimeFilter==1){md_syslog("info", "isSpamAssassin(): [$Sender]: $RelayHostname Outgoing message, skipping Spamassassin");$isNotSpamAssassinWhy="$RelayHostname Outgoing message";}
		return 0;
	}

	if ($MessageSize > $MimeDefangSpamMaxSize) {
		if($DebugMimeFilter==1){md_syslog("info", "isSpamAssassin(): [$Sender]: $RelayHostname $MessageSize > $MimeDefangSpamMaxSize, skipping Spamassassin");$isNotSpamAssassinWhy="$MessageSize > $MimeDefangSpamMaxSize";}
		return 0;
	 }

	if($SendIPIsInNet==1){
	  	if($NotTrustLocalNet==0){
			if($DebugMimeFilter==1){md_syslog("info", "isSpamAssassin(): [$Sender]: $RelayHostname Sended by trusted network, skipping Spamassassin");$isNotSpamAssassinWhy="$RelayHostname trusted network";}
			return 0;
		}
	}
	if($isWhitelisted==1){
		if($DebugMimeFilter==1){md_syslog("info", "isSpamAssassin(): [$Sender]: $RelayHostname is whitelisted, skipping Spamassassin");$isNotSpamAssassinWhy="$RelayHostname whitelisted";}
		return 0;
	}

	if(!$Features{"SpamAssassin"} ) {
		if($DebugMimeFilter==1){md_syslog("info", "isSpamAssassin(): [$Sender]: $RelayHostname Not installed, skipping Spamassassin");$isNotSpamAssassinWhy="Spamassassin Libraries not loaded";}
		return 0;
	}
    $isNotSpamAssassinWhy="OK launch it";
	return 1;

}

#***********************************************************************
# Write to Event to MySQL temporary table
#

sub MySQLFileNames(){
    my($fname, $ext, $type,$size)=@_;
    $fname=trim($fname);
    if(length($fname)==0){return;}
    my ($dbG,$Success)=PG_CONNECT();
    my $fileback='';
    

    my $currentSender=canonicalize_email($Sender);
    $currentSender=~ s/"//g;	
    $currentSender=~ s/'//g;
    $fname=~ s/"//g;	
    $fname=~ s/'//g;
    my $sender_domain=get_domain($Sender);

   foreach (@Recipients) {
        $recipt=canonicalize_email($_);
        $recipt_domain=get_domain($recipt);
        if($DebugMimeFilter==1){md_syslog("info", "PostGreSQL: from=<$currentSender> to=<$recipt> $fname, $ext, $type, $size");}
        
        if($Success==0){
            $fileback = md5_hex($sql);
            if($DebugMimeFilter==1){md_syslog("info", "PostgreSQL failed to connect --> backup sql /home/artica/MIMEDEFANG/MYSQL_FAILED/$fileback.sql");}
            WriteToFile("/home/artica/MIMEDEFANG/MYSQL_FAILED/$fileback.sql",$sql);
            continue;
        }
        
        $sql="INSERT INTO mimedefang_parts (zdate,mailfrom,domainfrom,mailto,domainto,fname,ext,contenttype,size) VALUES (NOW(),'$currentSender','$sender_domain','$recipt','$recipt_domain','$fname','$ext','$type','$size')";
        if($DebugMimeFilter==1){md_syslog("info", "MySQLFileNames::[$MsgID] $sql");}
        $sth = $dbG->prepare($sql);
        if(!$sth->execute() ){
            $error=$DBI::errstr;
            md_syslog("info", "PostGreySQL error: $error L.1927");
        }
        $sth->finish();

   }	

}

sub WriteToMysql(){
   my $currentSender=canonicalize_email($Sender);

   if(length($currentSender)<3){
	if(length($SenderBackuped)>3){
		$currentSender=canonicalize_email($SenderBackuped);
	}
   }
   

	my $sender_domain=get_domain($Sender);	
	my $zSubject;
	my $ASReport='';
    my $fileback='';

	if(length($FinalScoreReport)>5){
	   $ASReport = trim($FinalScoreReport);
	   $ASReport =~ s/"/`/g;
	   $ASReport =~ s/'/`/g;
	   $ASReport =~ s/<<//g;
	   $ASReport =~ s/>>//g;
	   $ASReport =~ s/\r\n/\n/g;
	}

	$currentSender=~ s/"//g;	
	$currentSender=~ s/'//g;

  	my  $wd = supported MIME::WordDecoder 'UTF-8';
  	my $perl_string = $wd->decode($Subject);
   	$zSubject = trim($perl_string);
   	$zSubject =~ s/"/`/g;
   	$zSubject =~ s/'/`/g;
    

   

   foreach (@Recipients) {
        $recipt=canonicalize_email($_);
        $recipt_domain=get_domain($recipt);
        $sql="MIMEDEFANG=<NOW()|||'$MsgID'|||'$RelayAddr'|||'$RelayHostname'|||'$currentSender'|||'$sender_domain'|||'$recipt'|||'$recipt_domain'|||'$zSubject'|||'$MessageSize'|||'$FinalScoreSpam'|||'$ASReport'|||'$FinalDisclaimer'|||'$FinalBackUp'|||'$FinalInfected'|||'$FinalExtFiltered'|||'$isWhitelisted'|||'0'|||'0'|||'2'>";
        md_syslog("info", "$sql");
        
       
    }
   
   

}


#***********************************************************************
sub is_in_myNetwork(){
	if($RelayAddr eq "127.0.0.1"){return 1;}
	if(open(DAT, "/etc/artica-postfix/settings/Daemons/MimeDefangPostFixNetwork")){
		while( <DAT> ) {
			my $net=trim($_);
			if (length($net)==0){next;}
			if($RelayAddr eq $_){
				if($DebugMimeFilter==1){md_syslog("info", "is_in_myNetwork(): Checking $RelayAddr matches $_ [OK]");}
				$WhiteListedWhy="WhiteListedWhy;$RelayAddr matches $_";
				$MyNetLogs="$RelayAddr matches $_";
				return 1;
			}
			if($DebugMimeFilter==1){md_syslog("info", "is_in_myNetwork(): Checking $RelayAddr against $_");}
			$block = Net::Netmask->new($net);
			if ( $block->match($RelayAddr) ) {
				$WhiteListedWhy="WhiteListedWhy;$RelayAddr matches block $_";
				$MyNetLogs="$RelayAddr matches block $_";
				if($DebugMimeFilter==1){md_syslog("info", "is_in_myNetwork(): Checking $RelayAddr matches $_ [OK]");}
				return 1;
			}
		}

	}
if($DebugMimeFilter==1){md_syslog("info", "is_in_myNetwork(): $RelayAddr [FALSE]");}
return 0;

}


sub ReadFileIntoString{
	my $results;
	my $data_file=shift;
	if(open(DAT, $data_file)){
		while( <DAT> ) {
			$results=$results.$_;
		}		
		close(DAT);
	}
	return $results;
}
#***********************************************************************
sub WriteToFile{
	my($fname,$data) = @_;
	if(open(DAT, ">$fname")){
	 print DAT $data;
	 close DAT;
	}else{
		md_syslog("info", "$fname unable to write, permission denied");
	}
}
#***********************************************************************

$overlongheader = 0;
sub check_header_length {
	my ($h,$s) = @_;
	return 0 unless (defined($h) && defined($s) && $h ne '' && $s ne '');
	if (length($h)>127 || length($s)>7*1024) {
		debug_log(-1,'overlong header %s: %s',$h,$s);
		$overlongheader ++;
		return 0;
	}
	return 1;
}
#***********************************************************************
sub is_email_base64{
    my $data = shift;
    my $pos=index($data,"@");
    if ($pos>0) { return(0); }
    return(0) unless ($data =~ /[A-Za-z0-9+\/=]/); #test for valid Base64 characters
    if (length ($data)%4==0){
        return( 1 );
    } else {
        return(0);
    }
}
#***********************************************************************
sub decode_email_addr{
    my $data = shift;
    my $decoded = decode_mimewords($data);
    if (length($decoded)>1) { return($decoded);}
    return $data;
}
#***********************************************************************
sub set_memcached{
    my($key,$value) = @_;
    $memd = new Cache::Memcached {
    'servers' => [ "/var/run/memcached.sock"],
    'debug' => 0,
    'compress_threshold' => 10_000,
    };
    
    $memd->set($key, $value);
}

sub get_memcached{
    my $key = shift;
        $memd = new Cache::Memcached {
    'servers' => [ "/var/run/memcached.sock"],
    'debug' => 0,
    'compress_threshold' => 10_000,
    };
        
    return $memd->get($key);
}



sub load_modules{
	
	foreach my $mn (@_) {
		my $mnk = $mn;
		$mnk =~ s/^\s+//;
		$mnk =~ s/[\s\(].*$//;
		return 0 if (defined($loaded_modules{$mnk}) && !$loaded_modules{$mnk});
		unless (defined($loaded_modules{$mnk}) && $loaded_modules{$mnk}) {
			if($DebugMimeFilter==1){md_syslog("info", "load_modules() $mn $mnk");}
			eval("use $mn");
			if ($@) {
				if($DebugMimeFilter==1){md_syslog("info", "load_modules() $mn ! $!");}
				$loaded_modules{$mnk} = 0;
				return 0;
			}
			$loaded_modules{$mnk} = 1;
		}
	}
	return 1;
}
sub  trim { my $s = shift; $s =~ s/^\s+|\s+$//g; return $s };
#***********************************************************************
# DO NOT delete the next line, or Perl will complain.
1;

