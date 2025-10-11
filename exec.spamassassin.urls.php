<?php
$GLOBALS["AS_ROOT"]=false;
if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}if(function_exists("posix_getuid")){if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}}
	$GLOBALS["AS_ROOT"]=true;
	if(preg_match("#schedule-id=([0-9]+)#",implode(" ",$argv),$re)){$GLOBALS["SCHEDULE_ID"]=$re[1];}
	include_once(dirname(__FILE__) . '/ressources/class.users.menus.inc');
	include_once(dirname(__FILE__) . '/ressources/class.mysql.inc');
	include_once(dirname(__FILE__).  '/framework/class.unix.inc');
	include_once(dirname(__FILE__).  '/framework/frame.class.inc');
	include_once(dirname(__FILE__).  '/ressources/class.os.system.inc');
	include_once(dirname(__FILE__).  '/ressources/class.system.network.inc');
	include_once(dirname(__FILE__).  "/ressources/class.tcpip.inc");
	include_once(dirname(__FILE__).  "/ressources/class.postgres.inc");

	
	xrun();
	
function GET_SUBRULES($ID,$RULENAME){
		$page=CurrentPageName();
		$q=new lib_sqlite("/home/artica/SQLITE/spamassassin.db");
		$sql="SELECT * FROM `sub_rules` WHERE meta_id=$ID AND enabled=1 ORDER BY ID DESC";
		$tpl=new template_admin();
		$results=$q->QUERY_SQL($sql);
		$c=0;
		foreach ($results as $index=>$ligne){
			$IDrow=$ligne["ID"];
			$md=md5(serialize($ligne));
			$enabled=$ligne["enabled"];
			$ruletype=$ligne["ruletype"];
			$header=$ligne["header"];
			
			$pattern=trim(base64_decode($ligne["pattern"]));
			$pattern=str_replace("\n", "", $pattern);
			if($pattern==null){continue;}
			$pattern=str_replace("@", "\@", $pattern);
			$pattern=str_replace("\\@", "\@", $pattern);
			
			if($ruletype=="header"){
				$c++;
				$RULES["LASTRULE"]="header $RULENAME $header =~ /$pattern/i";
				$RULES["RULES"][]="header {$RULENAME}_{$IDrow}  $header =~ /$pattern/i";
				$RULES["RULESNAME"][]="{$RULENAME}_{$IDrow}";
				continue;
			}
				
			$RULES["LASTRULE"]="$ruletype $RULENAME /$pattern/i";
			$RULES["RULES"][]="$ruletype {$RULENAME}_{$IDrow} /$pattern/i";
			$RULES["RULESNAME"][]="{$RULENAME}_{$IDrow}";
			$c++;
				
				
			}
			
		$RULES["COUNT"]=$c;
		return $RULES;
	
	}	
	
function xrun(){
	$unix=new unix();
	$php=$unix->LOCATE_PHP5_BIN();
	$sock=new sockets();
	$SpamAssassinUrlScore=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SpamAssassinUrlScore"));
	$SpamAssassinScrapScore=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SpamAssassinScrapScore"));
	$SpamAssassinSubjectsScore=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SpamAssassinSubjectsScore"));
	$SpamAssassinBodyScore=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SpamAssassinBodyScore"));
	if($SpamAssassinUrlScore==0){$SpamAssassinUrlScore=9;}
	if($SpamAssassinScrapScore==0){$SpamAssassinScrapScore=6;}
	if($SpamAssassinSubjectsScore==0){$SpamAssassinSubjectsScore=3;}
	if($SpamAssassinBodyScore==0){$SpamAssassinBodyScore=3;}
	$TargetFilename="/etc/spamassassin/ArticaUrlsRules.cf";
	$TargetFilename2="/etc/spamassassin/ArticaEscrapRules.cf";
	$TargetFilename3="/etc/spamassassin/ArticaSubjectsRules.cf";
	
	/*	`ID` INTEGER PRIMARY KEY AUTOINCREMENT,
	 `rulename` TEXT ,
	`describe` TEXT,
	`enabled` INTEGER NOT NULL DEFAULT 1,
	`finalscore` INTEGER NOT NULL,
	`calculation` INTEGER )";
	
	*/
	build_progress(40, "Building rules...");
	$q=new lib_sqlite("/home/artica/SQLITE/spamassassin.db");
	$sql="SELECT * FROM `meta_rules` WHERE enabled=1 ORDER BY rulename";
	$results=$q->QUERY_SQL($sql);
	foreach ($results as $index=>$ligne){
		$ID=$ligne["ID"];
		$rulename=$ligne["rulename"];
		$describe=$ligne["describe"];
		$finalscore=$ligne["finalscore"];
		$calculation=$ligne["calculation"];
		$RULESS=GET_SUBRULES($ID,$rulename);
		
		$f[]="# Meta Rule ID $ID subrules:{$RULESS["COUNT"]} item(s)";
		if($RULESS["COUNT"]==1){
			$f[]="{$RULESS["LASTRULE"]}";
			$f[]="score $rulename $finalscore";
			$f[]="describe $rulename $describe";
			continue;
		}
		$f[]=@implode("\n", $RULESS["RULES"]);
		
		$calculation[1]="{all_rules_matches}";
		$calculation[2]="{one_of_rule_matches}";
		
		if($calculation==1){$f[]="meta $rulename  (".@implode(" && ", $RULESS["RULESNAME"]).")";}
		if($calculation==2){$f[]="meta $rulename ((".@implode(" + ", $RULESS["RULESNAME"]).") > 0)";}
		
		$f[]="score $rulename $finalscore";
		$f[]="describe $rulename $describe";
		
	}
	
	
	
	
	
	$sql="SELECT * FROM spamasssin_baddomains";
	$results = $q->QUERY_SQL($sql);
	if(!$q->ok){
		build_progress(110, "MySQL Error");
		return;
	}
	$f=array();
	$f2=array();
	build_progress(50, "Building rules...");
    $f[]="# ----------------------------- RULES -----------------------------";
	foreach ($results as $index=>$ligne){
		$ID=$ligne["ID"];
		$pattern=pattern_replace($ligne["pattern"]);
		if($pattern==null){continue;}
		$RuleName="ARTICA_BAD_URLS_$ID";
		$RuleName2="ARTICATECH_BAD_URLS_$ID";
		build_progress(50, "Building rule $pattern...");
		
		$f[]="uri $RuleName /$pattern/";
		$f[]="describe $RuleName Urls found in spam messages by Messaging Team.";
		$f[]="score $RuleName $SpamAssassinUrlScore";
		
		$f2[]="uri $RuleName2 /$pattern/";
		$f2[]="describe $RuleName2 Urls found in spam messages - by Artica Team.";
		$f2[]="score $RuleName2 9";
		
	}
    $f[]="# ----------------------------- RULES END -----------------------------";


    $f[]="# ----------------------------- DEFAULT RULES -----------------------------";
    $f[]="header __LR_LEGALSPAM_1         exists:X-EMV-CampagneId";
    $f[]="header __LR_LEGALSPAM_2         exists:X-Mw-Campaign-Uid";
    $f[]="header __LR_LEGALSPAM_3         exists:X-campaign-id";
    $f[]="header __LR_LEGALSPAM_4         exists:X-campaign_id";
    $f[]="header __LR_LEGALSPAM_5         exists:X-uid-id-m";
    $f[]="header __LR_LEGALSPAM_6         exists:X-Mailin-Client";
    $f[]="header __LR_LEGALSPAM_7         exists:X-rpcampaign";
    $f[]="header __LR_LEGALSPAM_8         exists:X-Campaign";
    $f[]="header __LR_LEGALSPAM_9         exists:X-MC-User";
    $f[]="header __LR_LEGALSPAM_10        exists:X-eCircle-Complaints";
    $f[]="meta LR_LEGALSPAM_HEADER        (__LR_LEGALSPAM_1 || __LR_LEGALSPAM_2 || __LR_LEGALSPAM_3 || __LR_LEGALSPAM_4 || __LR_LEGALSPAM_5 || __LR_LEGALSPAM_6 || __LR_LEGALSPAM_7 || __LR_LEGALSPAM_8 || __LR_LEGALSPAM_9 || __LR_LEGALSPAM_10)";
    $f[]="describe LR_LEGALSPAM_HEADER    \"Legal\" spam (emailing)";
    $f[]="score LR_LEGALSPAM_HEADER       4.0";
    $f[]="";
    $f[]="header LR_AMAZON                exists:X-SES-Outgoing";
    $f[]="describe LR_AMAZON              Sent from Amazon spam network";
    $f[]="score LR_AMAZON                 0.5";
    $f[]="";
    $f[]="header LR_SUSPECT_MAILER        X-Mailer =~ /(PHPMailer|php|mailing)/i";
    $f[]="describe LR_SUSPECT_MAILER      Suspect mailer agent";
    $f[]="score LR_SUSPECT_MAILER         0.5";
    $f[]="";
    $f[]="header LR_LEGALSPAM_MAILER      X-Mailer =~ /(delosmail|cabestan|ems|mp6|wamailer|eMailink|Accucast|Benchmail|ACEM|Sendinblue|Streamsend|Edatis|Mailchimp)/i";
    $f[]="describe LR_LEGALSPAM_MAILER    Mailer agent often used by \"legal\" spammers.";
    $f[]="score LR_LEGALSPAM_MAILER       4.0";
    $f[]="";
    $f[]="header LR_CSA_SENDER            exists:X-CSA-Complaints";
    $f[]="describe LR_CSA_SENDER          Certified Senders Alliance (mostly spam)";
    $f[]="score LR_CSA_SENDER             0.5";

-
    $f[]="header VADERETRO_SAYS_CLEAN        X-VADE-SPAMSTATE =~ /clean/i";
    $f[]="describe VADERETRO_SAYS_CLEAN      Header VadeRetro says clean.";
    $f[]="score VADERETRO_SAYS_CLEAN         -2";


    $f[]="# Spam is legal in France !";
    $f[]="body FR_SPAMISLEGAL                     /\b(Conform.+ment|En vertu).{0,5}(article.{0,4}34.{0,4})?la loi\b/i";
    $f[]="describe FR_SPAMISLEGAL                 French: pretends spam is (l)awful.";
    $f[]="lang fr describe FR_SPAMISLEGAL		Invoque la loi informatique et libertes.";
    $f[]="score FR_SPAMISLEGAL                    2.5";
    $f[]="";
    $f[]="body FR_SPAMISLEGAL_2                   /\bdroit d.acc.+s.{1,3}(de modification)?.{0,5}de rectification\b/i";
    $f[]="describe FR_SPAMISLEGAL_2               French: pretends spam is (l)awful.";
    $f[]="lang fr describe FR_SPAMISLEGAL_2	Invoque le droit de rectification cnil.";
    $f[]="score FR_SPAMISLEGAL_2                  2.5";
    $f[]="";
    $f[]="#####";
    $f[]="# yeah, sure.";
    $f[]="body FR_NOTSPAM                         /\b(ceci|ce).{1,9} n.est pas.{1,5}spam\b/i";
    $f[]="describe FR_NOTSPAM                     French: claims not to be spam.";
    $f[]="lang fr describe FR_NOTSPAM		Affirme ne pas etre du spam.";
    $f[]="score FR_NOTSPAM                        4.0";
    $f[]="";
    $f[]="#####";
    $f[]="## I can pay my taxes";
    $f[]="body FR_PAYLESSTAXES                    /\b(paye|calcul|simul|r.+dui|investi).{1,7}(moins|vo|ses).{0,5}imp.+t(s)?\b/i";
    $f[]="describe FR_PAYLESSTAXES                French: Pay less taxes";
    $f[]="lang fr describe FR_PAYLESSTAXES	Simulateurs et reductions d'impots.";
    $f[]="score FR_PAYLESSTAXES                   2.0";
    $f[]="";
    $f[]="body FR_REALESTATE_INVEST               /\b(loi)? (de.robien|girardin).{1,15}(neuf|recentr.+|ancien|IR|IS|imp.+t(s)?|industriel(le)?)\b/i";
    $f[]="describe FR_REALESTATE_INVEST           French: Invest in real-estate with tax-reductions";
    $f[]="lang fr describe FR_REALESTATE_INVEST	Reduction impots immobilier. ";
    $f[]="score FR_REALESTATE_INVEST              2.5";
    $f[]="";
    $f[]="#####";
    $f[]="# I won at the casino";
    $f[]="body FR_ONLINEGAMBLING                  /\b(casino(s)?|jeu(x)?|joueur(s)?) (en ligne|de grattage)\b/i";
    $f[]="describe FR_ONLINEGAMBLING              French: Online gambling";
    $f[]="lang fr describe FR_ONLINEGAMBLING	Jeux en ligne.";
    $f[]="score FR_ONLINEGAMBLING                 2.0";
    $f[]="";
    $f[]="#####";
    $f[]="# Baby, did you forget to take your meds ?";
    $f[]="body FR_ONLINEMEDS                      /\bpharmacie(s)? (en ligne|internet)\b/i";
    $f[]="describe FR_ONLINEMEDS                  French: Online meds ordering";
    $f[]="lang fr describe FR_ONLINEMEDS		Achat de medicaments en ligne.";
    $f[]="score FR_ONLINEMEDS                     3.0";
    $f[]="";
    $f[]="######";
    $f[]="# Tell me why";
    $f[]="body FR_REASON_SUBSCRIBE                /\bVous recevez ce(t|tte)? (message|mail|m.+l|lettre|news.+) (car|parce que)\b/i";
    $f[]="describe FR_REASON_SUBSCRIBE            French: you subscribed to my spam.";
    $f[]="lang fr describe FR_REASON_SUBSCRIBE	Indique pourquoi vous recevez le courrier.";
    $f[]="score FR_REASON_SUBSCRIBE               1.5";
    $f[]="";
    $f[]="#####";
    $f[]="# How to unsubscribe";
    $f[]="body FR_HOWTOUNSUBSCRIBE                /\b(souhaitez|d.+sirez|pour).{1,10}(plus.{1,}recevoir|d.+sincrire|d.+sinscription|d.+sabonner).{0,10}(information|email|mail|mailing|newsletter|lettre|liste|message|offre|promotion|programme)(s)?\b/i";
    $f[]="describe FR_HOWTOUNSUBSCRIBE            French: how to unsubscribe";
    $f[]="lang fr describe FR_HOWTOUNSUBSCRIBE	Indique comment se desabonner.";
    $f[]="score FR_HOWTOUNSUBSCRIBE               2.0";
    $f[]="";
    $f[]="####";
    $f[]="# Various \"CRM\" (Could Remove Me)";
    $f[]="#####";
    $f[]="header FR_MAILER_1                      X-Mailer =~ /(delosmail|cabestan|ems|mp6|wamailer|phpmailer|eMailink|Accucast|Benchmail)/i";
    $f[]="describe FR_MAILER_1                    French spammy X-Mailer";
    $f[]="lang fr describe FR_MAILER_1		X-Mailer couramment employe pour des spams en francais.";
    $f[]="score FR_MAILER_1                       4.0";
    $f[]="";
    $f[]="header FR_MAILER_2                      X-EMV-CampagneId =~ /.+/";
    $f[]="describe FR_MAILER_2                    French spammy mailer header";
    $f[]="lang fr describe FR_MAILER_2		X-Mailer couramment employe pour des spams en francais.";
    $f[]="score FR_MAILER_2                       4.0";
    $f[]="";


    $f[]="body LOC_NO_EXTORT1_ALL	/You have [0-9]+ hours in order to make the payment/i";
    $f[]="score LOC_NO_EXTORT1_ALL	9.9";
    $f[]="body LOC_NO_EXTORT2_ALL	/I made a split-screen video/i";
    $f[]="score LOC_NO_EXTORT2_ALL	9.9";
    $f[]="body LOC_NO_EXTORT3_ALL	/I (attached|placed|uрlоaded).*?(virus|malware|mаliсiоus).*?(porn|pornography|рrоgrаm)/i";
    $f[]="score LOC_NO_EXTORT3_ALL	9.9";
    $f[]="body LOC_NO_EXTORT4_ALL	/I will send your video to all of your contacts including/i";
    $f[]="score LOC_NO_EXTORT4_ALL	9.9";
    $f[]="body LOC_NO_EXTORT5_ALL	/I give you [0-9]+ (hоurs|days) аftеr yоu орen my mеssage/i";
    $f[]="score LOC_NO_EXTORT5_ALL	9.9";
    $f[]="body LOC_NO_EXTORT6_ALL /(роrn videо|mаsturbаtеd|solitаry sex|Yоur lifе can bе ruinеd|chance to save your life)/i";
    $f[]="score LOC_NO_EXTORT6_ALL	9.9";
    $f[]="";
    $f[]="body BAD_INTRODUCTION_1 /Hi, my prey/";
    $f[]="describe BAD_INTRODUCTION_1 this a suspicious introduction.";
    $f[]="score BAD_INTRODUCTION_1 1.2";
    $f[]="";
    $f[]="header __HAS_USER_AGENT         exists:User-Agent";
    $f[]="meta MISSING_USER_AGENT         !__HAS_USER_AGENT";
    $f[]="describe MISSING_USER_AGENT     Missing User-Agent: header";
    $f[]="score MISSING_USER_AGENT        0.8";
    $f[]="";

    $f[]="uri TECHSMITH_URL \.techsmith\.com\/";
    $f[]="describe TECHSMITH_URL techsmith.com, reduce score - by Artica Team.";
    $f[]="score TECHSMITH_URL -20";

    $f[]="uri ARTICA_MICRO_LINK /\/\/goo\.gl\//";
    $f[]="describe ARTICA_MICRO_LINK goo.gl found in message - by Artica Team.";
    $f[]="score ARTICA_MICRO_LINK 2";

    $f[]="uri UNSUBSCRIBE_NEWS_LETTER  /\/unsubscribe\.php\?/";
    $f[]="score UNSUBSCRIBE_NEWS_LETTER 1";
    $f[]="describe UNSUBSCRIBE_NEWS_LETTER Probably a news letter - by Artica Team.";

    $f[]="uri ARTICA_DYNALINK_INTEGER_1 /\/(link|unsubscribe)\.php\?M=[0-9]+/";
    $f[]="describe ARTICA_DYNALINK_INTEGER_1 Probably a tracker that point to an integer value - by Artica Team.";
    $f[]="score ARTICA_DYNALINK_INTEGER_1 2";


    $f[]="uri ARTICA_URI_EXE /\.(?:exe|scr|dll|pif|vbs|wsh|cmd|bat)(?:\W{0,20}$|\?)/i";
    $f[]="describe ARTICA_URI_EXE link contains executables files - by Artica Team.";
    $f[]="score ARTICA_URI_EXE 3";

    $f[]="header ARTICA_PAYPAL From =~ /(service|member)\@paypal\.(fr|com|de|pl|pt|es|co\.uk|in|ru|pe|bl|it|cz|il|re)/i";
    $f[]="describe ARTICA_PAYPAL Whitelisted paypal Sender.";
    $f[]="score ARTICA_PAYPAL -10";

    $f[]="header ARTICA_MAIL_NUMERIC From =~ /[a-z\-\.]+[0-9]+\@/i";
    $f[]="describe ARTICA_MAIL_NUMERIC Numeric characters in from email - by Artica Team";
    $f[]="score ARTICA_MAIL_NUMERIC 1";



    $f[]="header ARTICA_WBL19 From =~ /\@(dell|cic|techsmith|parkingorly|cybercartes|services\.boulanger)\.(fr|com|de|pl|pt|es|co\.uk|in|ru|pe|bl|it|cz|il|re)/i";
    $f[]="describe ARTICA_WBL19 Whitelisted everything@dell/cic/techsmith/parkingorly/cybercartes. Sender.";
    $f[]="score ARTICA_WBL19 -20";

    $f[]="header ARTICA_WBL20 From =~ /\@.*?\.(crucial|ubisoft|leboncoin|wetransfer|mercedes|showroomprive)\.(fr|com|de|pl|pt|es|co\.uk|in|ru|pe|bl|it|cz|il|re)/i";
    $f[]="describe ARTICA_WBL20 Whitelisted \.crucial,ubisoft. Sender.";
    $f[]="score ARTICA_WBL20 -20";

    $f[]="header ARTICA_WBL22 From =~ /\@verdieopenclass\.com /i";
    $f[]="describe ARTICA_WBL22 Whitelisted \.verdieopenclass.com . Sender.";
    $f[]="score ARTICA_WBL22 -20";



    $f[]="header SMF_BRACKETS_TO To:raw =~ /<<[^<>]+>>/";
    $f[]="describe SMF_BRACKETS_TO Double-brackets around To header address";
    $f[]="score SMF_BRACKETS_TO 1.5";
    $f[]="";
    $f[]="header RCVD_IN_FABEL rbleval:check_rbl('fabel', 'spamsources.fabel.dk.')";
    $f[]="describe RCVD_IN_FABEL Blacklisted from spamsources.fabel.dk";
    $f[]="tflags RCVD_IN_FABEL net";
    $f[]="score  RCVD_IN_FABEL 1.2";
    $f[]="";
    $f[]="header RCVD_IN_ANONMAILS        eval:check_rbl('anonmails-lastexternal', 'spam.dnsbl.anonmails.de.')";
    $f[]="describe RCVD_IN_ANONMAILS      Blacklisted FROM spam.dnsbl.anonmails.de";
    $f[]="tflags RCVD_IN_ANONMAILS        net";
    $f[]="score RCVD_IN_ANONMAILS         1.2";
    $f[]="# ----------------------------- DEFAULT RULES END -----------------------------";
		
	@unlink($TargetFilename);
	@file_put_contents($TargetFilename, @implode("\n", $f)."\n");
	
	@unlink("/etc/artica-postfix/spamassassin-rules1.cf");
	@file_put_contents("/etc/artica-postfix/spamassassin-rules1.cf", @implode("\n", $f2)."\n");
	
	$f=array();
	$f2=array();
	
	$pattern="\@[a-z\.]+\.(gouv|service-public)\.fr$";
	$f[]="header GOUV_FR From =~ /$pattern/i";
	$f[]="score GOUV_FR  -9";
	$f[]="describe GOUV_FR From ACLs - Whitelisted";

	
	$f2[]="header GOUV_FR2 From =~ /$pattern/i";
	$f2[]="score GOUV_FR2  -9";
	$f2[]="describe GOUV_FR2 From ACLs - Whitelisted by Artica Team";
	
	
// ****************************************************************************************************************************************************************	
	$sql="SELECT * FROM spamasssin_escrap";
	$results = $q->QUERY_SQL($sql);
	if(!$q->ok){build_progress(110, "MySQL Error");return;}
	
	$f2[]="# # # e-Scrap From Artica Team, builded on ". date("Y-m-d H:i:s")."\n";
	
	
	foreach ($results as $index=>$ligne){
		$ID=$ligne["ID"];
		$pattern=pattern_replace($ligne["pattern"]);
		if($pattern==null){continue;}
		$RuleName="ARTICA_SCRAP_$ID";
		$RuleName2="ARTICATECH_SCRAP_$ID";
		build_progress(60, "Building rules $pattern...");

		
		$f[]="header $RuleName From =~ /$pattern/i";
		$f[]="score $RuleName  $SpamAssassinScrapScore";
		$f[]="describe $RuleName From e-scrap messages - non sollicted mails";

	
		$f2[]="header $RuleName2 From =~ /$pattern/i";
		$f2[]="score $RuleName2  6";
		$f2[]="describe $RuleName2 From e-scrap messages - non sollicted mails by Artica Team";
	
	}
// ****************************************************************************************************************************************************************
	$sql="SELECT * FROM spamasssin_raw";
	$results = $q->QUERY_SQL($sql,"artica_backup");
	if(!$q->ok){build_progress(110, "MySQL Error");return;}
	
	foreach ($results as $index=>$ligne){
		$ID=$ligne["ID"];
		$pattern=pattern_replace($ligne["pattern"]);
		if($pattern==null){continue;}
		$RuleName="ARTICA_BODY_$ID";
		
		build_progress(70, "Building Body rules $pattern...");
		$f[]="rawbody $RuleName /$pattern/i";
		$f[]="score $RuleName  $SpamAssassinBodyScore";
		$f[]="describe $RuleName From Body rules";

	
	}
	// ****************************************************************************************************************************************************************
	
	
	
	
	$f2[]="header FROM_BLANK_NAME From =~ /(?:\s|^)\"\" <\S+>/i";
	$f2[]="score FROM_BLANK_NAME  2";
	$f2[]="describe FROM_BLANK_NAME The From: header contains a blank name, This is legal, but rare and pointless. ";
	
	$f2[]="header BOUNCE_NEWS_ADDR From =~ /\@bounce\.news\./i";
	$f2[]="score BOUNCE_NEWS_ADDR  3";
	$f2[]="describe BOUNCE_NEWS_ADDR From e-scrap messages - @bounce.news. in mail addr by Artica Team";
	
	$f2[]="header INVITATION_INADDR From =~ /\@invitation\..*?\.(com|fr|net)/i";
	$f2[]="score INVITATION_INADDR  3";
	$f2[]="describe INVITATION_INADDR From e-scrap messages - @invitation. something in mail addr by Artica Team";
	
	$f2[]="header WEBMASTER_INADDR From =~ /(bounce|noreply|webmaster|www-data)\@/i";
	$f2[]="score WEBMASTER_INADDR  2";
	$f2[]="describe WEBMASTER_INADDR From e-scrap messages - bounce,noreply,WebMaster,www-data is a generic mail address by Artica Team";
	
	
	$f2[]="header LEGETIMATE_BANK From =~ /\@bnpparibas\.com/i";
	$f2[]="score LEGETIMATE_BANK  -20";
	$f2[]="describe LEGETIMATE_BANK From legetimate bank - by Artica Team";
	
	$f2[]="header LEGETIMATE_GOOGLE From =~ /noreply\@youtube\.com/i";
	$f2[]="score LEGETIMATE_GOOGLE  -20";
	$f2[]="describe LEGETIMATE_GOOGLE From Google mailing lists - by Artica Team";
	
	$f2[]="header LEGETIMATE_TWITTER From =~ /\@twitter.com/i";
	$f2[]="score LEGETIMATE_TWITTER  -20";
	$f2[]="describe LEGETIMATE_TWITTER From twitter mailing lists - by Artica Team";

	$f2[]="header LEGETIMATE_GOOGLE From =~ /googlealerts-noreply\@google\.com/i";
	$f2[]="score LEGETIMATE_GOOGLE  -20";
	$f2[]="describe LEGETIMATE_GOOGLE From Google mailing lists - by Artica Team";
	
	$f2[]="header LEGETIMATE_PAYPAL From =~ /[a-z]+\@paypal\.(fr|com|de|it|es|pt|pl)/i";
	$f2[]="score LEGETIMATE_PAYPAL  -20";
	$f2[]="describe LEGETIMATE_PAYPAL From PayPal mailing lists - by Artica Team";
	
	$f2[]="header LEGETIMATE_VIADEO From =~ /\@[a-z]+\.viadeo\.com/i";
	$f2[]="score LEGETIMATE_VIADEO  -20";
	$f2[]="describe LEGETIMATE_VIADEO From viadeo mailing lists - by Artica Team";
	
	$f2[]="header LEGETIMATE_MICROSOFTSTORE From =~ /\DO-NOT-REPLY\@microsoftstore\.com/i";
	$f2[]="score LEGETIMATE_MICROSOFTSTORE  -20";
	$f2[]="describe LEGETIMATE_MICROSOFTSTORE From microsoftstore lists - by Artica Team";
	
	$f2[]="header LEGETIMATE_AIRFRANCE1 From =~ /\@(account|infos|info|ticket|service|enews)-airfrance\.(com|fr)/i";
	$f2[]="score LEGETIMATE_AIRFRANCE1  -20";
	$f2[]="describe LEGETIMATE_AIRFRANCE1 From Air France legetimates lists - by Artica Team";
	
	$f2[]="header LEGETIMATE_AIRFRANCE2 From =~ /\@(email|xmedia|service)\.airfrance\.(com|fr)/i";
	$f2[]="score LEGETIMATE_AIRFRANCE2  -20";
	$f2[]="describe LEGETIMATE_AIRFRANCE2 From Air France legetimates lists - by Artica Team";
	
	$f2[]="header LEGETIMATE_AIRFRANCE3 From =~ /\@(airfrance|info-flyingblue|gocadservices|connect-passengers)\.(com|fr)/i";
	$f2[]="score LEGETIMATE_AIRFRANCE3  -20";
	$f2[]="describe LEGETIMATE_AIRFRANCE3 From Air France legetimates lists - by Artica Team";
	
	$f2[]="header LEGETIMATE_FRANCE_GOUV From =~ /\@.*?\.gouv\.fr/i";
	$f2[]="score LEGETIMATE_FRANCE_GOUV  -50";
	$f2[]="describe LEGETIMATE_FRANCE_GOUV From french gouvernment legetimates lists - by Artica Team";
	
	$f2[]="header LEGETIMATE_FRANCE_AFNOR From =~ /\@afnor\.org/i";
	$f2[]="score LEGETIMATE_FRANCE_AFNOR  -50";
	$f2[]="describe LEGETIMATE_FRANCE_AFNOR From french gouvernment legetimates lists - by Artica Team";
	
	
	
	
	$f2[]="header VENTE_FLASH Subject =~ /vente flash/i";
	$f2[]="score VENTE_FLASH  3";
	$f2[]="describe VENTE_FLASH Subject - Seems a flash sales - by Artica Team";
	
	$f2[]="header X_ACCORHOTELS_PRESENT		   exists:X-Accorhotels-ReservationDate";
	$f2[]="describe X_ACCORHOTELS_PRESENT      Message has X-Accorhotels-ReservationDate";
	$f2[]="score X_ACCORHOTELS_PRESENT         -8";	
	
	$f2[]="header X_IRONPORT_PRESENT		exists:X-IronPort-AV";
	$f2[]="describe X_IRONPORT_PRESENT      Message has X-IronPort-AV";
	$f2[]="score X_IRONPORT_PRESENT         -10";
	
	
	$f2[]="header X_NODEMAILER	X-Mailer =~ /nodemailer/";
	$f2[]="describe X_NODEMAILER      NodeMailer client used";
	$f2[]="score X_NODEMAILER         5";
	
	
	$f2[]="header X_LINKEDIN_PRESENT		exists:X-LinkedIn-Id";
	$f2[]="describe X_LINKEDIN_PRESENT      Message has X-LinkedIn-Id";
	$f2[]="score X_LINKEDIN_PRESENT         -9";
		
	$f2[]="header X_BEVERLYMAIL_PRESENT        exists:X-BeverlyMail-Recipient";
	$f2[]="describe X_BEVERLYMAIL_PRESENT      Message has X-BeverlyMail-Recipient";
	$f2[]="score X_BEVERLYMAIL_PRESENT         3";
	
	$f2[]="header X_MAILINCAMPAIGN_PRESENT        exists:X-Mailin-Campaign";
	$f2[]="describe X_MAILINCAMPAIGN_PRESENT      Message has X-Mailin-Campaign";
	$f2[]="score X_MAILINCAMPAIGN_PRESENT         3";
	
	
	$f2[]="header X_MWCAMPAIGN_PRESENT        exists:X-Mw-Campaign-Uid";
	$f2[]="describe X_MWCAMPAIGN_PRESENT      Message has X-Mw-Campaign-Uid";
	$f2[]="score X_MWCAMPAIGN_PRESENT         3";	
	
	$f2[]="header X_XCAMPAIGNID_PRESENT        exists:X-Campaign-Id";
	$f2[]="describe X_MWCAMPAIGN_PRESENT      Message has X-Campaign-Id";
	$f2[]="score X_XCAMPAIGNID_PRESENT         3";

	$f2[]="header X_MAILERSID_PRESENT        exists:X-Mailer-SID";
	$f2[]="describe X_MAILERSID_PRESENT      Message has X-Mailer-SID";
	$f2[]="score X_MAILERSID_PRESENT         3";
	$f2[]="score FREEMAIL_FROM 1";
	
	
	$f2[]="header X_ARTICA_HELLO   Subject =~ /Hello/i";
	$f2[]="score X_ARTICA_HELLO  1";
	$f2[]="describe X_ARTICA_HELLO Hello in subject, not professional";
	
	$f2[]="rawbody X_ARTICA_DEFAULT_PHISH1 /\/INVOICE-[0-9]+-[0-9]+\//i";
	$f2[]="score X_ARTICA_DEFAULT_PHISH1  3";
	$f2[]="describe X_ARTICA_DEFAULT_PHISH1 Phishing URI - non sollicted mails";
	
	$f2[]="rawbody X_ARTICA_DEFAULT_PHISH2 /\/CUST\.-Document-[A-Z]+-[0-9]+-[0-9A-Z]+\//i";
	$f2[]="score X_ARTICA_DEFAULT_PHISH2  3";
	$f2[]="describe X_ARTICA_DEFAULT_PHISH2 Phishing URI - non sollicted mails";	
	
	$f2[]="rawbody X_ARTICA_DEFAULT_PHISH3 /\/Cust-Document-[0-9]+\//i";
	$f2[]="score X_ARTICA_DEFAULT_PHISH3  3";
	$f2[]="describe X_ARTICA_DEFAULT_PHISH3 Phishing URI - non sollicted mails";	
	
	
	$f2[]="rawbody X_ARTICA_LOTOFMONEY /(?:(?i:sum\sof\s)[\(\[]?|<CURRENCY>\s?)[\s\.]?\d[\d,\sOo]{5,20}[\dOo](?<!\.00)/";
	$f2[]="score X_ARTICA_LOTOFMONEY  0.8";
	$f2[]="describe X_ARTICA_LOTOFMONEY A lots of money - Artica Team";
	
	$f2[]="rawbody X_ARTICA_DONATION_CODE /THIS IS YOUR DONATION CODE.*?[0-9]+/i";
	$f2[]="score X_ARTICA_DONATION_CODE  1";
	$f2[]="describe X_ARTICA_LOTOFMONEY Donation code - Artica Team";
	
	
	$f2[]="rawbody X_ARTICA_DONATION /decided to donate the sum of/i";
	$f2[]="score X_ARTICA_DONATION  1";
	$f2[]="describe X_ARTICA_DONATION Donation - Artica Team";
	
	$f[]="score ADVANCE_FEE_2_NEW_MONEY 0.9";
	$f[]="score NO_RELAYS 0.8";
	$f[]="score MISSING_MID 0.9";
	
	$f[]="rawbody UNSUBSCRIBE_DEFAULT /Unsubscribe:\s+/i";
	$f[]="score UNSUBSCRIBE_DEFAULT 0.8";
	$f[]="describe UNSUBSCRIBE_DEFAULT Unsubscribe proposal in messaging exchange";
	
	$f[]="header LEGETIMATE_FACEBOOK From =~ /\@(facebook|support\.facebook)\.com/i";
	$f[]="score LEGETIMATE_FACEBOOK  -9";
	$f[]="describe LEGETIMATE_FACEBOOK From ACLs - from facebook...";
	
	$f[]="header MAILIGEN_SOFTWARE X-Mailer=~ /(Mailigen|MailChimp) Mailer/i";
	$f[]="score MAILIGEN_SOFTWARE  1.0";
	$f[]="describe MAILIGEN_SOFTWARE From ACLs - Email Marketing Software Mailigen used...";
	
	
	@unlink($TargetFilename2);
	@file_put_contents($TargetFilename2, @implode("\n", $f)."\n");
	
	@unlink("/etc/artica-postfix/spamassassin-rules3.cf");
	@file_put_contents("/etc/artica-postfix/spamassassin-rules3.cf", @implode("\n", $f2)."\n");
	
	
	$sql="SELECT * FROM spamasssin_subjects";
	$results = $q->QUERY_SQL($sql);
	if(!$q->ok){
		build_progress(110, "MySQL Error");
		return;
	}
	$f=array();
	$f2=array();
	$f2[]="# # # Subjects From Artica Team, builded on ". date("Y-m-d H:i:s")."\n";
	
	
	foreach ($results as $index=>$ligne){
		$ID=$ligne["ID"];
		$pattern=pattern_replace($ligne["pattern"]);
		if($pattern==null){continue;}
		$RuleName="ARTICA_SUBJECT_$ID";
		$RuleName2="ARTICATECH_SUBJECT_$ID";
		build_progress(60, "Building rules $pattern...");
	
	
		$f[]="header $RuleName Subject =~ /$pattern/i";
		$f[]="score $RuleName  $SpamAssassinSubjectsScore";
		$f[]="describe $RuleName Subject - non sollicted mails";
	
	
		$f2[]="header $RuleName2 Subject =~ /$pattern/i";
		$f2[]="score $RuleName2  3";
		$f2[]="describe $RuleName2 Subject - non sollicted mails by Artica Team";
	
	}

	@unlink($TargetFilename3);
	@file_put_contents($TargetFilename3, @implode("\n", $f)."\n");
	
	@unlink("/etc/artica-postfix/spamassassin-rules4.cf");
	@file_put_contents("/etc/artica-postfix/spamassassin-rules4.cf", @implode("\n", $f2)."\n");	
	
	build_progress(70, "Building rules {done}...");
	Reload();
	build_progress(95, "{exporting_rules}...");
	shell_exec("$php /usr/share/artica-postfix/exec.milter-greylist.cloud.php >/dev/null 2>&1");
	build_progress(100, "{done}...");
}

function pattern_replace($pattern){
	$pattern=trim($pattern);
	$pattern=str_replace(".", "\.", $pattern);
	$pattern=str_replace("/", "\/", $pattern);
	$pattern=str_replace("$", "\$", $pattern);
	$pattern=str_replace("*", ".*?", $pattern);
	$pattern=str_replace("@", "\@", $pattern);
	
	$pattern=str_replace("#END", "$", $pattern);
	$pattern=str_replace("#ALPHANUM#", "[a-z0-9]+", $pattern);
	$pattern=str_replace("#ALPHANUM", "[a-z0-9]+", $pattern);
	$pattern=str_replace("#", "\#", $pattern);
	return $pattern;
	
}

function Reload(){
	$unix=new unix();
	$php=$unix->LOCATE_PHP5_BIN();
	build_progress(60, "{reloading_service} 1/4...");
	system("/etc/init.d/spamassassin reload");
	build_progress(70, "{reloading_service} 2/4...");
	system("/etc/init.d/spamass-milter restart");
	build_progress(71, "{reloading_service} 3/4...");
	@copy("/usr/share/artica-postfix/bin/install/mimedefang/mimedefang-filter.pl", "/etc/mail/mimedefang-filter");
	system("$php /usr/share/artica-postfix/exec.mimedefang.php --parse");
	if(is_file("/etc/init.d/mimedefang")){
		system("/etc/init.d/mimedefang reload");
	}
	build_progress(72, "{reloading_service} 4/4...");
	system("/etc/init.d/postfix-logger restart");	
	build_progress(73, "{reloading_service} {done}...");
}

function build_progress($pourc,$text){
	$array["POURC"]=$pourc;
	$array["TEXT"]=$text;
	echo "[$pourc]: $text\n";
	@file_put_contents("/usr/share/artica-postfix/ressources/logs/spamassassin.urls.progress", serialize($array));
	@chmod("/usr/share/artica-postfix/ressources/logs/spamassassin.urls.progress",0755);
}

function CheckSecuritiesFolders(){
	if(is_dir("/etc/mail/spamassassin")){
		shell_exec("/bin/chmod -R 666 /etc/mail/spamassassin");
		shell_exec("/bin/chown -R postfix:postfix /etc/mail/spamassassin");
		shell_exec("/bin/chmod 755 /etc/mail/spamassassin");		
	}
	if(is_dir("/etc/spamassassin")){
		shell_exec("/bin/chmod -R 666 /etc/spamassassin");
		shell_exec("/bin/chmod 755 /etc/spamassassin");
	}
	
	if(is_dir("/var/lib/spamassassin")){
		shell_exec("/bin/chmod -R 755 /var/lib/spamassassin");
		shell_exec("/bin/chown -R postfix:postfix /etc/spamassassin");
		shell_exec("/bin/chown -R postfix:postfix /var/lib/spamassassin");
	}	
	
}







?>