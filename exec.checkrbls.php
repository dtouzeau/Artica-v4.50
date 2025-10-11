<?php
if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}if(function_exists("posix_getuid")){if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}}
ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);
include_once (dirname(__FILE__)."/ressources/class.postgres.inc");
if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["DEBUG"]=true;}

if($argv[1]=="--check"){CheckThisOne($argv[2],$argv[3]);}
if($argv[1]=="--query"){query_by_date();exit;}
if($argv[1]=="--verif"){verif(false);exit;}
if($argv[1]=="--verif-all"){verif(true);exit;}
if($argv[1]=="--mm"){mm(true);exit;}
if($argv[1]=="--badregex"){$GLOBALS["VERBOSE"]=true;isBlack($argv[2]);exit;}
if($argv[1]=="--sorbs"){$GLOBALS["DEBUG"]=true;spam_dnsbl_sorbs_net($argv[2]);new_spam_dnsbl_sorbs_net($argv[2]);exit;}
if($argv[1]=="--regex"){$GLOBALS["VERBOSE"]=true;isBlack($argv[2]);isWhite($argv[2]);exit;}



function CheckThisOne($ipaddr,$hostname=null){
    $GLOBALS["RESULTS"]=null;
    if($hostname=="--verbose"){$hostname=null;}

    if($hostname==null){$hostname=gethostbyaddr($hostname);}
    echo "\nCheck $ipaddr $hostname -->";
    if(isWhite($hostname)){echo "Whitelisted {$GLOBALS["RESULTS"]}\n";return;}
    echo "Whitelited NO;";
    if(CheckRBL($ipaddr)){echo "RBL: {$GLOBALS["RESULTS"]}\n";return;}
    echo "RBL NO;";
    if(isBlack($hostname)){echo "Blacklisted {$GLOBALS["RESULTS"]}\n";return;}
    echo "BLACK NO;\n\n";
}
function PerformWhitelist($ipaddr,$hostname,$results){
    $q=new postgres_sql();
    $date=date("Y-m-d H:i:s");

    $description="$hostname $results";
    $q->QUERY_SQL("UPDATE ip_reputation SET isparsedrbl=1 WHERE ipaddr='$ipaddr'");
    $q->QUERY_SQL("UPDATE ip_reputation SET isUnknown=0 WHERE ipaddr='$ipaddr'");
    $q->QUERY_SQL("DELETE FROM rbl_blacklists WHERE ipaddr='$ipaddr'");
    $q->QUERY_SQL("DELETE FROM rbl_whitelists WHERE ipaddr='$ipaddr'");
    $q->QUERY_SQL("INSERT INTO rbl_whitelists (ipaddr,description,zDate) VALUES ('$ipaddr','$description','$date')");


}
function PerformBlacklist($ipaddr,$hostname,$results){
    $q=new postgres_sql();
    $date=date("Y-m-d H:i:s");

    $description="$hostname $results";
    $q->QUERY_SQL("UPDATE ip_reputation SET isparsedrbl=1 WHERE ipaddr='$ipaddr'");
    $q->QUERY_SQL("UPDATE ip_reputation SET isUnknown=0 WHERE ipaddr='$ipaddr'");
    $q->QUERY_SQL("DELETE FROM rbl_blacklists WHERE ipaddr='$ipaddr'");
    $q->QUERY_SQL("DELETE FROM rbl_whitelists WHERE ipaddr='$ipaddr'");
    $q->QUERY_SQL("INSERT INTO rbl_blacklists (ipaddr,description,zDate) VALUES ('$ipaddr','$description','$date')");


}

function mm(){
    $q=new postgres_sql();
    $results= $q->QUERY_SQL("SELECT * FROM rbl_whitelists WHERE zdate >'2019-01-31 19:00:00' ORDER BY zdate ASC");
    if(!$q->ok){echo $q->mysql_error;}
    while ($ligne = pg_fetch_assoc($results)) {

        $hostname=$ligne["description"];
        if(preg_match("#mandrillapp#",$hostname)){continue;}
        $ipaddr=$ligne["ipaddr"];
        echo "$ipaddr -> $hostname\n";

        if(isBlack($hostname)){
            PerformBlacklist($ipaddr,$hostname,"detected by BLACKREGEX:{$GLOBALS["RESULTS"]}");
            echo "$hostname Blacklisted {$GLOBALS["RESULTS"]}\n";
            $q->QUERY_SQL("DELETE FROM rbl_whitelist WHERE ipaddr='$ipaddr'");
            continue;
        }

        /*if(CheckRBL($ipaddr)){
            echo "RBL: {$GLOBALS["RESULTS"]}\n";
            PerformBlacklist($ipaddr,$hostname,"detected by RBL {$GLOBALS["RESULTS"]}");
            $q->QUERY_SQL("DELETE FROM rbl_whitelist WHERE ipaddr='$ipaddr'");
            continue;}
        */

    }



}


function query_by_date(){

        $q=new postgres_sql();
        echo "Query PostGreSQL\n";
 /*$results= $q->QUERY_SQL("SELECT * FROM rbl_blacklists WHERE
        zdate>'2019-01-01'
        AND description LIKE '%Detected in blacklists by rblchecked%'
        ORDER BY zdate DESC");
*/

 $results= $q->QUERY_SQL("SELECT * FROM rbl_blacklists WHERE description='nixspamDB' ORDER BY zdate ASC");





if(!$q->ok){echo "$q->mysql_error\n";return;}


    echo "Start loop PostGreSQL\n";
    while ($ligne = pg_fetch_assoc($results)) {

        $ipaddr=$ligne["ipaddr"];

        $ligne2=$q->mysqli_fetch_array("SELECT zdate FROM rbl_whitelists WHERE ipaddr='$ipaddr'");
        if(trim($ligne2["zdate"])<>null){
            echo "$ipaddr already whitelisted\n";
            $q->QUERY_SQL("DELETE FROM rbl_blacklists WHERE ipaddr='$ipaddr'");
            $q->QUERY_SQL("UPDATE ip_reputation SET isUnknown=0 WHERE ipaddr='$ipaddr'");
            continue;
        }

        $zDate=$ligne["zdate"];
        $description=$ligne["description"];
        $hostname=gethostbyaddr($ipaddr);
        echo "$zDate\t$ipaddr\t$hostname\t$description\t";

        if(preg_match("#^[0-9\.]+$#",$hostname)){
            echo "Hostname not found\n";
            PerformBlacklist($ipaddr,$hostname,"nixspamDB: Hostname not found");
            continue;
        }

        if(isWhite($hostname)) {
            echo "Whitelisted {$GLOBALS["RESULTS"]}\n";
            PerformWhitelist($ipaddr, $hostname, "nixspamDB: detected by WHITEREGEX:". $GLOBALS["RESULTS"]);
            continue;
        }

        if(CheckRBL($ipaddr)){
            echo "RBL: {$GLOBALS["RESULTS"]}\n";
            PerformBlacklist($ipaddr,$hostname,"nixspamDB: detected by RBL {$GLOBALS["RESULTS"]}");
            continue;}

        if(isBlack($hostname)){
            echo "BLACKREGEX:{$GLOBALS["RESULTS"]}\n";
            PerformBlacklist($ipaddr,$hostname,"nixspamDB: detected by BLACKREGEX:{$GLOBALS["RESULTS"]}");
            continue;
        }

        echo "WHITELIST!\n";
        PerformWhitelist($ipaddr, $hostname, "NOTHING WAS FOUND");


    }


}

function verif($all=false){

    $q=new postgres_sql();
    $sql="SELECT zdate,hostname,ipaddr FROM ip_reputation WHERE isparsedrbl=0";
    if($all){$sql="SELECT zdate,hostname,ipaddr FROM ip_reputation WHERE isUnknown=1 order by zdate DESC";}
    $results=$q->QUERY_SQL($sql);
    while ($ligne = pg_fetch_assoc($results)) {

        $ipaddr=$ligne["ipaddr"];

        $ligne2=$q->mysqli_fetch_array("SELECT zdate FROM rbl_whitelists WHERE ipaddr='$ipaddr'");
        if(trim($ligne2["zdate"])<>null){
            echo "$ipaddr already whitelisted\n";
            $q->QUERY_SQL("DELETE FROM rbl_blacklists WHERE ipaddr='$ipaddr'");
            $q->QUERY_SQL("UPDATE ip_reputation SET isUnknown=0 WHERE ipaddr='$ipaddr'");
            $q->QUERY_SQL("UPDATE ip_reputation SET isparsedrbl=1 WHERE ipaddr='$ipaddr'");
            continue;
        }

        $zdate=$ligne["zdate"];
        if($GLOBALS["DEBUG"]){echo "Resolving $ipaddr....";}
        $hostname=gethostbyaddr($ipaddr);
        if($GLOBALS["DEBUG"]){echo "$hostname\n";}
        echo "$zdate\t$ipaddr\t$hostname\t";

        if(preg_match("#^[0-9\.]+$#",$hostname)){
            echo "Hostname not found\n";
            PerformBlacklist($ipaddr,$hostname,"Hostname not found");
            continue;
        }

        if(isWhite($hostname)) {
            echo "Whitelisted {$GLOBALS["RESULTS"]}\n";
            PerformWhitelist($ipaddr, $hostname, "detected by WHITEREGEX:". $GLOBALS["RESULTS"]);
            continue;
        }
        if($GLOBALS["DEBUG"]){echo "CheckRBL($ipaddr)\n";}
        if(CheckRBL($ipaddr)){
            echo "RBL: {$GLOBALS["RESULTS"]}\n";
            PerformBlacklist($ipaddr,$hostname,"detected by RBL {$GLOBALS["RESULTS"]}");
            continue;
        }

        if(fresh30_spameatingmonkey_net($ipaddr)){
            echo "RBL: fresh30.spameatingmonkey.net\n";
            PerformBlacklist($ipaddr,$hostname,"detected by RBL fresh30.spameatingmonkey.net");
            continue;

        }

        if(free_v4bl_org($ipaddr)){
            echo "RBL: free.v4bl.org\n";
            PerformBlacklist($ipaddr,$hostname,"detected by RBL free.v4bl.org");
            continue;
        }

        if(spam_dnsbl_sorbs_net($ipaddr)){
            echo "RBL: spam.dnsbl.sorbs.net\n";
            PerformBlacklist($ipaddr,$hostname,"detected by RBL spam.dnsbl.sorbs.net");
            continue;
        }

        if(new_spam_dnsbl_sorbs_net($ipaddr)){
            echo "RBL: new.spam.dnsbl.sorbs.net\n";
            PerformBlacklist($ipaddr,$hostname,"detected by RBL new.spam.dnsbl.sorbs.net");
            continue;
        }

        if(dnsbl_justspam_org($ipaddr)){
            echo "RBL: dnsbl.justspam.org\n";
            PerformBlacklist($ipaddr,$hostname,"detected by RBL dnsbl.justspam.org");
            continue;

        }

        if(dbl_spamhaus_org($ipaddr)){
            echo "RBL: dbl.spamhaus.org\n";
            dbl_spamhaus_org($ipaddr,$hostname,"detected by RBL dsbl.spamhaus.org");
            continue;
         }

        if(bl_suomispam_net($ipaddr)){
            echo "RBL: bl.suomispam.net\n";
            dbl_spamhaus_org($ipaddr,$hostname,"detected by RBL bl.suomispam.net");
            continue;

        }


        if(truncate_gbudb_net($ipaddr)){
            echo "Blacklisted truncate.gbudb.net\n";
            PerformBlacklist($ipaddr,$hostname,"detected by RBL truncate.gbudb.net");
            continue;
        }

        if(zen_spamhaus_org($ipaddr)){
            echo "Blacklisted zen.spamhaus.org\n";
            PerformBlacklist($ipaddr,$hostname,"detected by RBL zen.spamhaus.org {$GLOBALS["ZEN"]}");
            continue;
        }

        if(hostcarma($ipaddr)){
            echo "Blacklisted hostkarma.junkemailfilter.com\n";
            PerformBlacklist($ipaddr,$hostname,"detected by RBL hostkarma.junkemailfilter.com");
            continue;
        }
        if(isBlack($hostname)){
            PerformBlacklist($ipaddr,$hostname,"detected by BLACKREGEX:{$GLOBALS["RESULTS"]}");
            echo "Blacklisted {$GLOBALS["RESULTS"]}\n";
            continue;
        }


        echo "NOTHING\n";
        $q->QUERY_SQL("UPDATE ip_reputation SET isparsedrbl=1 WHERE ipaddr='$ipaddr'");





    }
}

function bl_suomispam_net($ipaddr){
    $rbls="bl.suomispam.net";
    $ip = $ipaddr;
    $rev = join('.', array_reverse(explode('.', trim($ip))));
    $lookup = sprintf('%s.%s', $rev, $rbls);
    $results = gethostbyname($lookup);
    if($GLOBALS["DEBUG"]){echo "bl.suomispam.net $results\n";}
    if($results=="127.0.1.2"){return true;};

}

function dbl_spamhaus_org($ipaddr){
    $rbls="free.v4bl.org";
    $ip = $ipaddr;
    $rev = join('.', array_reverse(explode('.', trim($ip))));
    $lookup = sprintf('%s.%s', $rev, $rbls);
    $results = gethostbyname($lookup);
    if($GLOBALS["DEBUG"]){echo "dbl.spamhaus.org $results\n";}
    if($results=="127.0.1.2"){return true;};

}

function free_v4bl_org($ipaddr){
    $rbls="free.v4bl.org";
    $ip = $ipaddr;
    $rev = join('.', array_reverse(explode('.', trim($ip))));
    $lookup = sprintf('%s.%s', $rev, $rbls);
    $results = gethostbyname($lookup);
    if($GLOBALS["DEBUG"]){echo "free.v4bl.org $results\n";}
    if($results=="127.0.0.2"){return true;};

}

function dnsbl_justspam_org($ipaddr){
    $rbls="dnsbl.justspam.org";
    $ip = $ipaddr;
    $rev = join('.', array_reverse(explode('.', trim($ip))));
    $lookup = sprintf('%s.%s', $rev, $rbls);
    $results = gethostbyname($lookup);
    if($GLOBALS["DEBUG"]){echo "dnsbl.justspam.org $results\n";}
    if($results=="127.0.0.2"){return true;};

}

function fresh30_spameatingmonkey_net($ipaddr){
    $rbls="fresh30.spameatingmonkey.net";
    $ip = $ipaddr;
    $rev = join('.', array_reverse(explode('.', trim($ip))));
    $lookup = sprintf('%s.%s', $rev, $rbls);
    $results = gethostbyname($lookup);
    if($GLOBALS["DEBUG"]){echo "fresh30.spameatingmonkey.net $results\n";}
    if($results=="127.0.0.2"){return true;};

}

//

function spam_dnsbl_sorbs_net($ipaddr){
    $rbls="spam.dnsbl.sorbs.net";
    $ip = $ipaddr;
    $rev = join('.', array_reverse(explode('.', trim($ip))));
    $lookup = sprintf('%s.%s', $rev, $rbls);
    $results = gethostbyname($lookup);
    if($GLOBALS["DEBUG"]){echo "spam.dnsbl.sorbs.net $results\n";}
    if($results=="127.0.0.6"){return true;};
}

function new_spam_dnsbl_sorbs_net($ipaddr){
    $rbls="new.spam.dnsbl.sorbs.net";
    $ip = $ipaddr;
    $rev = join('.', array_reverse(explode('.', trim($ip))));
    $lookup = sprintf('%s.%s', $rev, $rbls);
    $results = gethostbyname($lookup);
    if($GLOBALS["DEBUG"]){echo "new.spam.dnsbl.sorbs.net $results\n";}
    if($results=="127.0.0.6"){return true;};
}







function zen_spamhaus_org($ipaddr){
    $GLOBALS["ZEN"]=null;
    $rbls="zen.spamhaus.org";

        $ip = $ipaddr;
        $rev = join('.', array_reverse(explode('.', trim($ip))));
        $lookup = sprintf('%s.%s', $rev, $rbls);
        $results = gethostbyname($lookup);
        if($results=="127.0.0.1"){return false;}
        if($results=="127.0.0.2"){$GLOBALS["ZEN"]="Direct UBE sources, spam operations & spam services";return true;}
        if($results=="127.0.0.3"){$GLOBALS["ZEN"]="Direct snowshoe spam sources detected via automation";return true;}
        if($results=="127.0.0.4"){$GLOBALS["ZEN"]="CBL (3rd party exploits such as proxies, trojans, etc.)";return true;}
        if($results=="127.0.0.5"){$GLOBALS["ZEN"]="CBL (3rd party exploits such as proxies, trojans, etc.)";return true;}
        if($results=="127.0.0.6"){$GLOBALS["ZEN"]="CBL (3rd party exploits such as proxies, trojans, etc.)";return true;}
        if($results=="127.0.0.7"){$GLOBALS["ZEN"]="CBL (3rd party exploits such as proxies, trojans, etc.)";return true;}
        if($results=="127.0.0.8"){$GLOBALS["ZEN"]="Direct UBE sources, spam operations & spam services";return true;}
        if($results=="127.0.0.9"){$GLOBALS["ZEN"]="Direct UBE sources, spam operations & spam services";return true;}
        if($results=="127.0.0.10"){$GLOBALS["ZEN"]="End-user Non-MTA IP addresses set by ISP outbound mail policy";return true;}
        if($results=="127.0.0.11"){$GLOBALS["ZEN"]="End-user Non-MTA IP addresses set by ISP outbound mail policy";return true;}
        return false;
}


function truncate_gbudb_net($ipaddr){
    $rbls="truncate.gbudb.net";

    $ip = $ipaddr;
    $rev = join('.', array_reverse(explode('.', trim($ip))));
    $lookup = sprintf('%s.%s', $rev, $rbls);
    $results = gethostbyname($lookup);
    if($results=="127.0.0.2"){return true;}
    return false;
}
function hostcarma($ipaddr){


    $rbls="hostkarma.junkemailfilter.com";

    $ip = $ipaddr;
    $rev = join('.', array_reverse(explode('.', trim($ip))));
    $lookup = sprintf('%s.%s', $rev, $rbls);
    $results = gethostbyname($lookup);
    if($results=="127.0.0.2"){return true;}
    if($results=="127.0.0.3"){return true;}
    if($results=="127.0.0.4"){return true;}
    if($results=="127.0.0.5"){return false;}

    return false;

}

function CheckRBL($ipaddr){

    $GLOBALS["RESULTS"]=null;

    //hostkarma.junkemailfilter.com "dnsrbl.org",

    $rbls = array(
        "b.barracudacentral.org",
        "bl.spamcop.net",
        "dnsbl.cobion.com",
        "bl.suomispam.net",
        "bl.drmx.org",
        "bl.nosolicitado.org",
        "dnsbl-1.uceprotect.net",
        "dnsbl-2.uceprotect.net",
        "dnsbl-3.uceprotect.net",
        "ubl.unsubscore.com",
        "dyna.spamrats.com",
        "spam.spamrats.com","cbl.abuseat.org","ips.backscatterer.org"


    );

    $ip = $ipaddr;
    $rev = join('.', array_reverse(explode('.', trim($ip))));
    $i = 1;
    $rbl_count = count($rbls);
    $listed_rbls = [];

    foreach ($rbls as $rbl) {

        $lookup = sprintf('%s.%s', $rev, $rbl);
        if($GLOBALS["DEBUG"]){echo "gethostbyname($lookup)....";}
        $listed = gethostbyname($lookup) !== $lookup;
        if($GLOBALS["DEBUG"]){echo "$listed\n";}
        if ($listed) {
            $GLOBALS["RESULTS"]="$rbl";
            return true;
            $listed_rbls[] = $rbl;
        }
        $i++;
    }

    return false;


}

function isBlack($hostname){

    if(!isset($GLOBALS["BADREGEX"])) {
        $GLOBALS["BADREGEX"] = explode("\n", @file_get_contents("/usr/share/artica-postfix/ressources/databases/badomain_regex.txt"));
    }

    reset($GLOBALS["BADREGEX"]);

    $c=0;
    foreach ($GLOBALS["BADREGEX"] as $line){
        $c++;
        $line=trim($line);
        if($line==null){continue;}
        if(strpos("   $line","#")>0){continue;}



        if(preg_match("#$line#i",$hostname)){
            if($GLOBALS["VERBOSE"]){echo "#$line# -> $hostname MATCHES\n";}
            $GLOBALS["RESULTS"]="regex Pattern line $c";
            return true;
        }
        if (preg_last_error() == PREG_NO_ERROR) {continue;}
        echo "line $c $line ERROR\n";


    }
    if(!isset($GLOBALS["BADDOMS"])) {
        $GLOBALS["BADDOMS"] = explode("\n", @file_get_contents("/usr/share/artica-postfix/ressources/databases/badomains.txt"));
    }

    if($GLOBALS["VERBOSE"]){echo "badomain_regex $c rules\n";}
    $c=0;

    reset($GLOBALS["BADDOMS"]);
    foreach ($GLOBALS["BADDOMS"] as $line){
        $c++;

        $line=trim($line);
        if($line==null){continue;}
        if(strpos("   $line","#")>0){continue;}
        if(preg_match("#^\.(.+)#",$line,$re)){$line=$re[1];}

        $REGEX=false;
        if(strpos($line,"]")>0){$REGEX=true;}
        if(strpos($line,")")>0){$REGEX=true;}
        if(strpos($line,"?")>0){$REGEX=true;}

        if($REGEX){
            if(preg_match("#$line#i",trim($hostname))) {
                if($GLOBALS["VERBOSE"]){echo "#$line# -> $hostname MATCHES\n";}
                $GLOBALS["RESULTS"] = "domain Pattern line $c";
                return true;
            }
            continue;
        }

        $line=str_replace(".","\.",$line);

        if(preg_match("#(\.|^)$line$#i",trim($hostname))) {
            if($GLOBALS["VERBOSE"]){echo "#(\.|^)$line$#i -->[$hostname] MATCHES\n";}
            $GLOBALS["RESULTS"] = "domain Pattern line $c";
            return true;
        }
        if (preg_last_error() == PREG_NO_ERROR) {continue;}
        echo "line $c $line ERROR\n";

    }

    if($GLOBALS["VERBOSE"]){ echo "badomains.txt -> $c rules\n";}
    return false;
}

function isWhite($hostname){

    sftp://root@rbl.artica.center/usr/share/artica-postfix/ressources/databases/badomains.txt
    $f=explode("\n",@file_get_contents("/usr/share/artica-postfix/ressources/databases/goodomains.txt"));
    $c=0;
    foreach ($f as $line){
        $c++;
        $line=trim($line);
        $logthis=false;
        if($line==null){continue;}
        if(strpos("   $line","#")>0){continue;}

       

        if(preg_match("#^\.(.+)#",$line,$re)){$line=$re[1];}

        $REGEX=false;
        if(strpos($line,"]")>0){$REGEX=true;}
        if(strpos($line,")")>0){$REGEX=true;}
        if(strpos($line,"?")>0){$REGEX=true;}

        if($REGEX){
            if(preg_match("#$line#i",trim($hostname))) {
                $GLOBALS["RESULTS"] = "domain Pattern line $c";
                return true;
            }
            continue;
        }


        $line=str_replace(".","\.",$line);
        if($logthis){echo "#(\.|^)$line$#i - > ".trim($hostname)."\n";}
        if(preg_match("#(\.|^)$line$#i",trim($hostname))){
            $GLOBALS["RESULTS"]="domain Pattern line $c";
            return true;
        }
        if (preg_last_error() == PREG_NO_ERROR) {continue;}
        echo "line $c $line ERROR\n";

    }
    if($GLOBALS["VERBOSE"]){echo "goodomains $c rules\n";}


    $Regex[]="\.(idcontact|atos|mcdlv|mcsv|rsgsv|secureserver|sendgrid)\.net";
    $Regex[]="\.activemailer\.pro";
    $Regex[]="\.(laredoute|groupemoniteur|iroquois|certilience)\.fr$";
    $Regex[]="\.(mail\..*?\.yahoo|medallia|srvsaas|stoneshot|credit-suisse|5asec|lufthansa|twitter|google|capgemini|lastminute|mimecast|msgapp|smtp25|bnpparibas|officedepot)\.com$";
    $Regex[]="\.(expertsender|planetinfos)\.(fr|com)";
    $Regex[]="\.(smtpcorp.com|smtp2go|teamviewer|wetransfer|lafourchette|jd|aa|linkedin|placedestendances|ags-backup|meyclub|exaprobe|saint-gobain|3m|worldline)\.com$";
    $Regex[]="\.(mailin|cap-audience|leboncoin|espace-feminin|aspinfo|planetinfos|powermail|idweb|sgautorepondeur|services\.sfr|easiware|la-boite-immo|allianz)\.fr";
    $Regex[]="\.(volvo|sendlabs|mlflow|263xmail|fedex|trendmicro|messagingengine|hostedemail)\.com";
    $Regex[]="\.(marketvolt|iphmx)\.com";
    $Regex[]="\.(mail-out\.ovh|msg\.oleane|kickboxio|odiso|materiel|neolane|security-mail|emsecure|mail\.maxns|open-mailing|emm29|oxi-dedi)\.(net|de)";
    $Regex[]="\.(virtual-expo|indeed|epsl1|data-crypt|iphmx|webex|cisco|eurostar)\.com";
    $Regex[]="\.(emarsys|publicisgroupe)\.net";
    $Regex[]="\.outspot\.be";
    $Regex[]="mail\.(maxony|gandi|1e100|hubspotstarter)\.net";
    $Regex[]="\.(victorypackaging|salesforce|sparkpost|hubspotemail|hubspot|mailinblack|outbound\.protection\.outlook|canva|bitrix24|fnac|bazarchic)\.com";
    $Regex[]="\.(meteoconsult|pulsation|net-entreprises|caissedesdepots|groupe-rueducommerce|promod|agoramail)\.fr";
    $Regex[]="(advice\.hmrc\.gov\.uk|avonandsomerset\.police\.uk|blaby\.gov\.uk|braintree\.gov\.uk|bromley\.gov\.uk|bromsgroveandredditch\.gov\.uk|buckfastleigh\.gov\.uk|calderbridge\.n-lanark\.sch\.uk|calderdale\.gov\.uk|cambridgeshire\.gov\.uk|carmarthenshire\.gov\.uk|causewaycoastandglens\.gov\.uk|cheshire\.gov\.uk|cheshireeast\.gov\.uk|cheshiresharedservices\.gov\.uk|cheshirewestandchester\.gov\.uk|chesterfield\.gov\.uk|cjsm\.net|colerainebc\.gov\.uk|companieshouse\.gov\.uk|crawley\.gov\.uk|cumbria\.police\.uk|daventrydc\.gov\.uk|dumgal\.gov\.uk|dyobmr\.btconnect\.com|dyslmr\.btconnect\.com|eastbourne\.gov\.uk|eastdunbarton\.gov\.uk|eastleigh\.gov\.uk|eastrenfrewshire\.gov\.uk|eastriding\.gov\.uk|email\.education\.gov\.uk|epims\.ogc\.gov\.uk|eppingforestdc\.gov\.uk|eprompts\.hmrc\.gov\.uk|ext\.ons\.gov\.uk|gloucester\.gov\.uk|greenwich\.gov\.uk|gro-extranet\.homeoffice\.gov\.uk|halton\.gov\.uk|hanover\.org\.uk|harrogate\.gov\.uk|herefordshire\.gov\.uk|hinckley-bosworth\.gov\.uk|ipswich\.gov\.uk|kettering\.gov\.uk|leeds\.gov\.uk|lewes-eastbourne\.gov\.uk|lookinglocal\.gov\.uk|luton\.gov\.uk|medway\.gov\.uk|metoffice\.gov\.uk|milton-keynes\.gov\.uk|mk-mx-1\.mail\.tiscali\.co\.uk|mk-mx-2\.mail\.tiscali\.co\.uk|moray-edunet\.gov\.uk|nafn\.gov\.uk|newport\.gov\.uk|news\.local\.gov\.uk|nics\.gov\.uk|northampton\.gov\.uk|northamptonshire\.gov\.uk|north-herts\.gov\.uk|northlan\.gov\.uk|oldham\.gov\.uk|orkney\.gov\.uk|outmail\.warwickdc\.gov\.uk|plymouth\.gcsx\.gov\.uk|plymouthmuseum\.gov\.uk|qualitylifestyleltd\.co\.uk|rdobmr\.btconnect\.com|rdslmr\.btconnect\.com|redditchbc\.gov\.uk|resilience\.gov\.uk|rother\.gov\.uk|royalgreenwich\.gov\.uk|shropshire\.gov\.uk|smtp-out\.passportapplication\.service\.gov\.uk|southend\.gov\.uk|southribble\.gov\.uk|southsomerset\.gov\.uk|sstaffs\.gov\.uk|stalbans\.gov\.uk|tameside\.gov\.uk|telford\.gov\.uk|tendringdc\.gov\.uk|tfl\.gov\.uk|torridge\.gov\.uk|trafford\.gov\.uk|ukho\.gov\.uk|walthamabbey-tc\.gov\.uk|wirral\.gov\.uk|woking\.gov\.uk|worcestershire\.gov\.uk)$";
    $Regex[]="(c05-ltd\.co\.uk|gdfsuezenergiesfrance2\.fr|gdfsuezenergiesfrance1\.fr|gdfsuezpro-formulaireopposition\.fr|amigoscartujacenter\.com|comunicacionsilviamarso\.com|conews04\.com|conew03\.com|eservices-laposte\.fr|cabestan\.de|cabestan\.eu|selectour-voyages\.fr|services-bpifrance\.fr|comunicacion-golflamoraleja\.com|lapieshoppeuse\.com|emailccmb\.com|cealiberico\.info|offre-oseo\.com|newslettereducacion\.com|newsletter-aprendemas\.com|newsbarceloviajes\.com|purpleparking-offers\.com|information-oseo\.com|dedicated-marketing\.com|com-jepenseauxautres\.com|enquete-emailetvous\.com|enquetecourrier\.com|emm1\.com|dms39\.com|dms38\.com|dms37\.com|dms36\.com|dms35\.com|dms34\.com|dms33\.com|dms32\.com|dms31\.com|dms30\.com|clubconsommateur\.com|fournisseursexpress\.com|astuclicmail\.com|astucliccourriel\.com|blue4mobility\.com|bleu-ciel-edf\.com|axm1\.com|fondationarc\.org|fondationarc\.net|florajet-news\.com|email-buyshopping\.com|sociabilimel\.com|services-euroquity\.com|events-euroquity\.com|contacts-euroquity\.com|email-rossellbooks\.com|email-wuachin\.com|envio-emails\.info|news-selectour-afat\.com|performingway\.com|dskbank\.info|gdfsuezdolcevita3\.com|gdfsuezpro-formulaireopposition\.net|gdfsuezpro-formulaireopposition\.com|3w-mistergooddeal\.com|actuassurances\.com|emailrtarget\.com|chainedelespoir\.info|ubepro\.com|news-t-a-o\.com|iloveroommate\.com|gdfsuezenergiesfrance1\.com|gdfsuezenergiesfrance\.com|gdfsuezdolcevita1\.com|gdfsuezdolcevita\.com|gdfsuezcegibat2\.com|gdfsuezcegibat\.com|emailetvous\.com|dms-04\.net|crm-citroen-retail\.com|com-emailetvous\.com|cab04\.net|cab02\.net|air-austral\.net|yvesrocher\.ci|xpoonlinepro\.com|magique-promo\.com|melodie-des-offres\.com|monenviedujour\.com|mhm-email\.com|atrevia\.info|psa-corporate-solutions\.com|actu-assurances\.com|3w-webrivage\.com|3w-wanimo\.com|3w-tf1\.com|3w-privileges\.com|3w-cdiscount\.com|crm-peugeot-retail\.com|news-selectour\.com|news-reactivpub\.com|communication-edf\.com)$";
    $Regex[]="(hm1315\.locaweb|voegol)\.com.br";
    $Regex[]="(yapikredi)\.com\.tr$";


    $Regex[]="\.contactlab\.it";
    $Regex[]="\.(dms-01|avgcloud)\.net$";

    $Regex[]="mail\.business\.static\.orange\.";
    $Regex[]="\.smtp-out\.amazonses\.com";
    $Regex[]="\.(gouv|alcatraz)\.fr$";
    $Regex[]="\.pphosted\.com$";
    $Regex[]="\.mail\.[a-z][a-z][0-9]\.yahoo\.com$";
    $Regex[]="\.smtp-out\..*?\.amazonses\.com";


    $Regex[]="\.orange-business\.com$";
    $Regex[]="smtp\.gateway[0-9]+\.(negloo|visoox)\.com$";
    $Regex[]="\.(acceo-info)\.eu$";
    $Regex[]="sonic.*?\.mail\.bf[0-9]+\.yahoo\.com";
    $Regex[]="\.(saremail|atosorigin|indeed|pimkie|service-now|junomx|myaccessweb)\.com";
    $Regex[]="\.(azimailing|hubspotemail|mcsv|emd01|francite|clonemail|rp01|emailverify|rsgsv|jsmtp|emm23|emm24|e-i)\.net$";
    $Regex[]="\.citobi\.be";
    $Regex[]="eu-smtp-delivery-[0-9]+\.mimecast\.com";

    # ************* COM WHITE
    $Regex[]="\.(aprilasia|b2wdigital|messagestream|adobesystems|cision|scaleway|apple|vimeo|websitewelcome|mailchimp|camaieu|devisprox|soopix|cisco|mycloudmailbox|jobtomealert|playstation)\.com";
    $Regex[]="\.(joob24|dms31|ryanairemail|blablacar|hugavenue|envois-emailing|portablenorthpole|nespresso|jobrapidoalert|mon-financier|inbound\.protection\.outlook|bandcamp|mpsa)\.com$";
    $Regex[]="\.(sparkpostmail|me|tabeci|newsletter-wbp|bilendi|acquia|alibaba|booking|voyageprive|dpam|aquaray|lexisnexis|sonicwall|zdsys|mailissimo|socgen|messagelabs|motorolasolutions|ebay|siteprotect)\.com$";
    $Regex[]="\.(oxi-dedi|mailjet|journaldunet|magical-ears|pitneybowes|msgfocus|hivebrite|hpe|exacttarget|mcafee|enews-airfrance|antispamcloud|eventbrite|ups|dinaserver|pokerstars|fazae|barracuda|gm)\.com$";
    $Regex[]="\.(immo-facile|fiatgroup|key|rbs|sailthru|altospam|vdm|uswitch|volagratis|sodexo|sage|appriver|microsoftemail|antispameurope|dell)\.com$";
    # *********** FR WHITE
    $Regex[]="\.(ikea|eroutage|fiducial|iliad|conforama|bernard|total|idline|maildata|happymails|ingdirect|banque-france|sncf)\.fr$";
    # *************** COM.BR WHITE
    $Regex[]="\.(hoteldaweb)\.com\.br$";

    $Regex[]="\.(avaaz)\.org$";
    $Regex[]="\.santor\.biz";
    $Regex[]="\.rightmove\.co\.uk";
    $Regex[]="\.(jpg|ancv|meteo|inbox|paris|credit-agricole|pro-smtp|contact-everyone|asso|jabatus|artfmprod|evercard|newstank|gpsante|img-adtrans)\.fr$";
    $Regex[]="\.(hub-score|smile-hosting|eml-vinc|eventsoftware|axevision|01m|02m|03m|ecoledirecte|cachecache|renater)\.fr$";
    $Regex[]="\.(groupemagiconline|carrefour|cornut|lcl|newsco|vtech|bva|bnpparibas|lexisnexis|bdv|emailvalide|e-marketing|cartecitroenexclusive)\.fr$";
    $Regex[]="\.(bayer|ispgateway|inxserver|rmx|smtp\.rzone|rapidsoft|volkswagen)\.de$";
    $Regex[]="\.(be-mail|register)\.it$";
    $Regex[]="\.mail\.ru$";
    $Regex[]="\.(mgrt|efm-solution)\.net$";
    $Regex[]="\.(mailcamp)\.nl$";
    $Regex[]="\.(gls-group|flexmail|opentext)\.eu$";
    $Regex[]="\.(gls-group|flexmail|jm-bruneau)\.be$";
    $Regex[]="\.(ate)\.info$";
    $Regex[]=".socketlabs.";
    $Regex[]="\.(briteverify|onvasortir)\.com$";
    $Regex[]="\.sinamail\.sina\.com\.cn$";

    $Regex[]="\.(rakuten)\.tv$";

    $Regex[]="\.(protonmail)\.ch$";
    $Regex[]="dvs[0-9]+\.produhost\.net$";
    $Regex[]="smtp[0-9]+\.msg\.oleane\.net$";
    $Regex[]="\.(exchangedefender|spamtitan|schneider-electric|vadesecure|phplist|cybercartes|accor-mail|jetairways|msn|premierinn|f-secure|arconic|avg|dowjones|marksandspencer|azuresend|yellohvillage|gardnerweb|ctrip|yotpo|xwiki|jpmchase|microsoft|softwaregrp)\.com$";
    $Regex[]="\.static\.cnode\.io";
    $Regex[]="[0-9]+\.cab01\.net";
    $Regex[]="mail-out\.ovh\.net";
    $Regex[]="\.(neolane|everyone|ods2|turbo-smtp|pvmailer|mailchannels|electric|gorgu|as8677|1e100)\.net$";
    $Regex[]="mta[0-9\-]+\.(teneo|msdp1)\.(be|com)$";

    $i=0;
    foreach ($Regex as $pattern){
        $i++;
        if(preg_match("#$pattern#",$hostname)){
            $GLOBALS["RESULTS"]="Pattern $i";
            return true;
        }


    }

    if($GLOBALS["VERBOSE"]){echo "Regex $i rules\n";}

    return false;

}

