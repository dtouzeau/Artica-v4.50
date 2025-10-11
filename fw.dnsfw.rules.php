<?php
$GLOBALS["DNS_ACTIONS"]=array(0=>"{REFUSED}",1=>"{NXDOMAIN}",3=>"{SERVFAIL}",4=>"{NOERROR}",
    5=>"{query_dnssrv}",6=>"{do_nothing_but_logs}");
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.squid.acls.groups.inc");

if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}

if(isset($_POST["DNSFireWallVerbose"])){options_save();exit;}
if(isset($_POST["RULE_CHECKER_DOMAIN"])){rule_checker_save();exit;}

if(isset($_GET["tabs"])){tabs();exit;}
if(isset($_GET["table"])){table();exit;}

if(isset($_GET["js-example"])){js_example();exit;}
if(isset($_GET["js-example-create"])){js_example_create();exit;}

if(isset($_GET["newrule-js"])){new_rule_js();exit;}
if(isset($_GET["rule-delete-js"])){rule_delete_js();exit;}
if(isset($_GET["newrule-popup"])){new_rule_popup();exit;}
if(isset($_POST["newrule"])){new_rule_save();exit;}

if(isset($_GET["rule-id-js"])){rule_js();exit;}
if(isset($_GET["rule-tabs"])){rule_tabs();exit;}

if(isset($_GET["rule-settings"])){rule_settings();exit;}
if(isset($_POST["rule-save"])){rule_save();exit;}
if(isset($_GET["enable-js"])){rule_enable();exit;}
if(isset($_GET["acl-rule-move"])){rule_move();exit;}
if(isset($_POST["none"])){exit;}
if(isset($_GET["explain-this-rule"])){echo EXPLAIN_THIS_RULE($_GET["explain-this-rule"]);exit;}
if(isset($_GET["js-example"])){js_example();exit;}
if(isset($_GET["rule-checker-js"])){rule_checker_js();exit;}
if(isset($_GET["rule-checker-popup"])){rule_checker_popup();exit;}
if(isset($_GET["rule-checker-results"])){rule_checker_results();exit;}
if(isset($_GET["status"])){status();exit;}
if(isset($_GET["table-start"])){table_start();exit;}
if(isset($_GET["options-js"])){options_js();exit;}
if(isset($_GET["options-popup"])){options_popup();exit;}
if(isset($_GET["dashboard"])){dashboard();exit;}
if(isset($_GET["dashboard-top"])){dashboard_top();exit;}
if(isset($_GET["dashboard-left"])){dashboard_left();exit;}

page();


function page(){
	$page=CurrentPageName();
	$tpl=new template_admin();

    $DNSFW_VERSION=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("DNSFW_VERSION");
    $html=$tpl->page_header("{APP_DNS_FIREWALL} v$DNSFW_VERSION","fab fa-free-code-camp","{APP_DNS_FIREWALL_ABOUT}","$page?tabs=yes","dnsfw","progress-dnsfw-restart",false,"table-dnsfw-rules-tabs");


    if(isset($_GET["main-page"])){
        $tpl=new template_admin("{APP_DNS_FIREWALL}",$html);
        echo $tpl->build_firewall();
        return;
    }
    echo $tpl->_ENGINE_parse_body($html);
}

function dashboard(){
    $page=CurrentPageName();
    $html[]="<table style='width:100%;margin-top:15px'>";
    $html[]="<tr>";
    $html[]="<td colspan='2' style='vertical-align: top'><div id='top-dashboard'></div></td>";
    $html[]="</tr>";
    $html[]="<td style='width:50%;vertical-align:top'>";
    $html[]="<div id='left-dashboard'></div>";
    $html[]="</td>";
    $html[]="<td style='width:50%;vertical-align:top'>";
    $html[]="<div id='right-dashboard'></div>";
    $html[]="</td>";
    $html[]="</tr>";
    $html[]="</table>";
    $html[]="<script>";
    $html[]="LoadAjax('top-dashboard','$page?dashboard-top=yes')";
    $html[]="</script>";
    echo @implode("\n",$html);
}
// ARTICASTATISTICS_DISABLED
function dashboard_top(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $q=new lib_sqlite("/home/artica/SQLITE/acls.db");
    $results=$GLOBALS["CLASS_SOCKETS"]->ksrn_sockets("STATS");
    $MAIN=unserialize($results["RESPONSE"]);
    $CORP_LICENSE=intval($MAIN["CORP_LICENSE"]);
    $KSRN_LICENSE=intval($MAIN["KSRN_LICENSE"]);
    $KSRNEnable=intval($MAIN["KSRNEnable"]);
    $EnableUfdbGuard=intval($MAIN["EnableUfdbGuard"]);
    $VERSION=intval($MAIN["VERSION"]);

    $button=null;
    $KSRN_WIDGET= $tpl->widget_h("green", "fas fa-shield", "{active2}", "{KSRN} v.$VERSION",$button);
    $WEBFILTER_WIDGET= $tpl->widget_h("green", "fas fa-shield-virus", "{active2}", "{web_filtering}",$button);

    if($KSRN_LICENSE==0){
        $KSRN_WIDGET= $tpl->widget_h("yellow", "fas fa-shield", "{license_error}", "{KSRN} v.$VERSION",$button);
    }
    if($KSRNEnable==0){
        $KSRN_WIDGET= $tpl->widget_h("grey", "fas fa-shield", "{inactive2}", "{KSRN} v.$VERSION",$button);
    }
    if($CORP_LICENSE==0){
        $WEBFILTER_WIDGET= $tpl->widget_h("yellow", "fas fa-shield-virus", "Community Edition", "{web_filtering}",$button);
    }
    if($EnableUfdbGuard==0){
        $WEBFILTER_WIDGET= $tpl->widget_h("grey", "fas fa-shield-virus", "{inactive2}", "{web_filtering}",$button);
    }

    $results=$q->mysqli_fetch_array("SELECT COUNT(*) as tcount FROM dnsfw_acls WHERE enabled=1");
    $Rules=$results["tcount"];
    $RULES_WIDGET=$tpl->widget_h("green", "fab fa-free-code-camp", "$Rules {current_rules}", "{APP_DNS_FIREWALL}",$button);
    if($Rules==0){
        $RULES_WIDGET=$tpl->widget_h("grey", "fab fa-free-code-camp", "{no_rules}", "{APP_DNS_FIREWALL}",$button);
    }

    $html[]="<table style=width:100%>";
    $html[]="<tr>";
    $html[]="<td style='width:33%;padding:5px'>$KSRN_WIDGET</td>";
    $html[]="<td style='width:33%;padding:5px'>$WEBFILTER_WIDGET</td>";
    $html[]="<td style='width:33%;padding:5px'>$RULES_WIDGET</td>";
    $html[]="</tr>";
    $html[]="</table>";
    $html[]="<center style='margin:10px'><h2>{dansguardian_statistics}</h2></center>";
    $html[]="<script>";
    $html[]="LoadAjax('left-dashboard','$page?dashboard-left=yes')";
    $html[]="</script>";



    echo $tpl->_ENGINE_parse_body($html);
}

function dashboard_left(){


}


function tabs(){
    $page=CurrentPageName();
    $tpl=new template_admin();


    $array["{status}"]="$page?dashboard=yes";
    $array["{statistics}"]="$page?status=yes";
    $array["{firewall_rules}"]="$page?table-start=yes";
    $array["{firewall_events}"]="fw.dnsfw.events.php";
    echo $tpl->tabs_default($array);
}

function table_start(){
    $page=CurrentPageName();
    echo "<div id='table-dnsfw-rules'></div>
    <script>LoadAjax('table-dnsfw-rules','$page?table=yes');</script>";
}


function rule_delete_js(){
	header("content-type: application/x-javascript");
	$ID=$_GET["rule-delete-js"];
	$md=$_GET["md"];
	if(!rule_delete($ID)){return;}
	echo "$('#$md').remove();";
}

function js_example(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $tpl->js_prompt("{example}","{example_create_ask}","fas fa-hat-wizard",$page,null,
        "Loadjs('$page?js-example-create')");
}

function check_tables(){
    $q=new lib_sqlite("/home/artica/SQLITE/acls.db");

    $sql="CREATE TABLE IF NOT EXISTS `dnsfw_acls` (
		`ID` INTEGER PRIMARY KEY AUTOINCREMENT,
		`rulename` TEXT NOT NULL ,
		`description` TEXT NOT NULL ,
		`enabled` INTEGER NOT NULL ,
		`action` INTEGER NOT NULL DEFAULT 0,
		`reply` TEXT,
		`ttl` INTEGER NOT NULL DEFAULT 1800,
		`redirector` TEXT NULL,
		`zorder`  INTEGER NOT NULL DEFAULT 1
		)";
    $q->QUERY_SQL($sql);
    if (!$q->ok) {echo "$q->mysql_error (".__LINE__.")\n$sql\n";}

    $sql="CREATE TABLE IF NOT EXISTS `dnsfw_acls_link` (
			`zmd5` TEXT NOT NULL PRIMARY KEY ,
			`aclid` INTEGER ,
			`negation` INTEGER ,
			`gpid` INT UNSIGNED ,
			`zorder` INTEGER
			)";
    $q->QUERY_SQL($sql);
}

function js_example_create(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    check_tables();
    $q=new lib_sqlite("/home/artica/SQLITE/acls.db");
    $FF[]="`rulename`";
    $FF[]="`description`";
    $FF[]="`action`";
    $FF[]="`redirector`";
    $FF[]="`reply`";
    $FF[]="`ttl`";

    $GLOBALS["DNS_ACTIONS"]=array(0=>"{REFUSED}",1=>"{NXDOMAIN}",3=>"{SERVFAIL}",4=>"{NOERROR}",
        5=>"{query_dnssrv}",6=>"{do_nothing_but_logs}");

    $fintro="INSERT OR IGNORE INTO dnsfw_acls (ID,`rulename`,`description`,`action`,`redirector`,`reply`,`ttl`,`enabled`) VALUES";

    for($i=1;$i<10;$i++){
        $q->QUERY_SQL("DELETE FROM dnsfw_acls_link WHERE aclid='$i'");
    }

    $q->QUERY_SQL("$fintro (1,'Suspicious TLD domains','Block tld that are mostly non-professional',0,'','',1800,1)");
    if(!$q->ok){echo $tpl->js_mysql_alert($q->mysql_error);return;}


    $q->QUERY_SQL("$fintro (2,'Google forwarders','Redirects Google domains to Google redirectors',5,'8.8.8.8,8.8.4.4','',1800,1)");
    if(!$q->ok){echo $tpl->js_mysql_alert($q->mysql_error);return;}

    $q->QUERY_SQL("$fintro (3,'DNS Filter','Block suspicious domains (license required)',4,'','127.0.0.1',1800,0)");
    if(!$q->ok){echo $tpl->js_mysql_alert($q->mysql_error);return;}

    $q->QUERY_SQL("$fintro (4,'Who Query MX','If there is something that query MX ?',6,'','',1800,1)");
    if(!$q->ok){echo $tpl->js_mysql_alert($q->mysql_error);return;}

    $q->QUERY_SQL("$fintro (5,'To Active Directory','domain.tld, redirects to Active Directory',5,'10.10.1.254','',1800,0)");
    if(!$q->ok){echo $tpl->js_mysql_alert($q->mysql_error);return;}

    $q->QUERY_SQL("$fintro (6,'0-day reputation service','Use the Shields object as reputation service (license required)',1,'','',1800,0)");
    if(!$q->ok){echo $tpl->js_mysql_alert($q->mysql_error);return;}

    $ligne=$q->mysqli_fetch_array("SELECT ID FROM webfilters_sqgroups WHERE params='WIZARDDNSFW_1'");
    $querty_default_gpid=intval($ligne["ID"]);
    if($querty_default_gpid==0){
        $sql="INSERT INTO webfilters_sqgroups (GroupName,GroupType,enabled,params,tplreset) 
        VALUES ('Common DNS Queries','dnsquerytype','1','WIZARDDNSFW_1',0)";
        $q->QUERY_SQL($sql);
        $ligne=$q->mysqli_fetch_array("SELECT ID FROM webfilters_sqgroups WHERE params='WIZARDDNSFW_1'");
        $querty_default_gpid=intval($ligne["ID"]);
    }
    $date=date("Y-m-d H:i:s");
    $uid=$_SESSION["uid"];
    if($uid==-100){$uid="Manager";}

    $ligne=$q->mysqli_fetch_array("SELECT ID FROM webfilters_sqgroups WHERE params='WIZARDDNSFW_2'");
    $querty_mx_gpid=intval($ligne["ID"]);
    if($querty_mx_gpid==0){
        $sql="INSERT INTO webfilters_sqgroups (GroupName,GroupType,enabled,params,tplreset) 
        VALUES ('MX DNS Query','dnsquerytype','1','WIZARDDNSFW_2',0)";
        $q->QUERY_SQL($sql);
        $ligne=$q->mysqli_fetch_array("SELECT ID FROM webfilters_sqgroups WHERE params='WIZARDDNSFW_2'");
        $querty_mx_gpid=intval($ligne["ID"]);
    }

    //Categories
    $ligne=$q->mysqli_fetch_array("SELECT ID FROM webfilters_sqgroups WHERE params='WIZARDDNSFW_3'");
    $querty_catz_gpid=intval($ligne["ID"]);
    if($querty_catz_gpid==0){
        $sql="INSERT INTO webfilters_sqgroups (GroupName,GroupType,enabled,params,tplreset) 
        VALUES ('Bad categories','categories','1','WIZARDDNSFW_3',0)";
        $q->QUERY_SQL($sql);
        $ligne=$q->mysqli_fetch_array("SELECT ID FROM webfilters_sqgroups WHERE params='WIZARDDNSFW_3'");
        $querty_catz_gpid=intval($ligne["ID"]);
    }

    //Google
    $ligne=$q->mysqli_fetch_array("SELECT ID FROM webfilters_sqgroups WHERE params='WIZARDDNSFW_4'");
    $querty_google_gpid=intval($ligne["ID"]);
    if($querty_google_gpid==0){
        $sql="INSERT INTO webfilters_sqgroups (GroupName,GroupType,enabled,params,tplreset) 
        VALUES ('Google Domains','dstdom_regex','1','WIZARDDNSFW_4',0)";
        $q->QUERY_SQL($sql);
        $ligne=$q->mysqli_fetch_array("SELECT ID FROM webfilters_sqgroups WHERE params='WIZARDDNSFW_4'");
        $querty_google_gpid=intval($ligne["ID"]);
    }
    //Tld domains
    $ligne=$q->mysqli_fetch_array("SELECT ID FROM webfilters_sqgroups WHERE params='WIZARDDNSFW_5'");
    $querty_tld_gpid=intval($ligne["ID"]);
    if($querty_tld_gpid==0){
        $sql="INSERT INTO webfilters_sqgroups (GroupName,GroupType,enabled,params,tplreset) 
        VALUES ('Suspicious TLD domains','dstdom_regex','1','WIZARDDNSFW_5',0)";
        $q->QUERY_SQL($sql);
        $ligne=$q->mysqli_fetch_array("SELECT ID FROM webfilters_sqgroups WHERE params='WIZARDDNSFW_5'");
        $querty_tld_gpid=intval($ligne["ID"]);
    }
    $ligne=$q->mysqli_fetch_array("SELECT ID FROM webfilters_sqgroups WHERE params='WIZARDDNSFW_6'");
    $querty_ad_gpid=intval($ligne["ID"]);
    if($querty_ad_gpid==0){
        $sql="INSERT INTO webfilters_sqgroups (GroupName,GroupType,enabled,params,tplreset) 
        VALUES ('AD domain.tld','dstdomain','1','WIZARDDNSFW_6',0)";
        $q->QUERY_SQL($sql);
        $ligne=$q->mysqli_fetch_array("SELECT ID FROM webfilters_sqgroups WHERE params='WIZARDDNSFW_6'");
        $querty_ad_gpid=intval($ligne["ID"]);
    }

    $ligne=$q->mysqli_fetch_array("SELECT ID FROM webfilters_sqgroups WHERE params='WIZARDDNSFW_DSHIELDS'");
    $querty_dhsields_gpid=intval($ligne["ID"]);
    if($querty_dhsields_gpid==0){
        $sql="INSERT INTO webfilters_sqgroups (GroupName,GroupType,enabled,params,tplreset) 
        VALUES ('O-Day reputation','the_shields','1','WIZARDDNSFW_DSHIELDS',0)";
        $q->QUERY_SQL($sql);
        $ligne=$q->mysqli_fetch_array("SELECT ID FROM webfilters_sqgroups WHERE params='WIZARDDNSFW_DSHIELDS'");
        $querty_dhsields_gpid=intval($ligne["ID"]);
    }

    $ligne=$q->mysqli_fetch_array("SELECT ID FROM webfilters_sqgroups WHERE params='WIZARDDNSFW_INTERNAL");
    $querty_localnet_gpid=intval($ligne["ID"]);
    if($querty_localnet_gpid==0){
        $sql="INSERT INTO webfilters_sqgroups (GroupName,GroupType,enabled,params,tplreset) 
        VALUES ('Local network','src','1','WIZARDDNSFW_INTERNAL',0)";
        $q->QUERY_SQL($sql);
        if(!$q->ok){echo $tpl->js_mysql_alert($q->mysql_error);return;}
        $ligne=$q->mysqli_fetch_array("SELECT ID FROM webfilters_sqgroups WHERE params='WIZARDDNSFW_INTERNAL'");
        if(!$q->ok){echo $tpl->js_mysql_alert($q->mysql_error);return;}
        $querty_localnet_gpid=intval($ligne["ID"]);
        if($querty_localnet_gpid==0){echo $tpl->js_mysql_alert("WIZARDDNSFW_INTERNAL == 0");return;}
    }
    linkrule(1,$querty_localnet_gpid);
    linkrule(1,$querty_default_gpid);
    linkrule(1,$querty_tld_gpid);

    linkrule(2,$querty_default_gpid);
    linkrule(2,$querty_google_gpid);


    linkrule(3,$querty_localnet_gpid);
    linkrule(3,$querty_default_gpid);
    linkrule(3,$querty_catz_gpid);

    linkrule(4,$querty_localnet_gpid);
    linkrule(4,$querty_mx_gpid);

    linkrule(4,$querty_localnet_gpid);
    linkrule(5,$querty_ad_gpid);

    linkrule(6,$querty_localnet_gpid);
    linkrule(6,$querty_default_gpid);
    linkrule(6,$querty_dhsields_gpid);

    $q->QUERY_SQL("DELETE FROM webfilters_sqitems WHERE gpid=$querty_catz_gpid");
    $q->QUERY_SQL("DELETE FROM webfilters_sqitems WHERE gpid=$querty_mx_gpid");
    $q->QUERY_SQL("DELETE FROM webfilters_sqitems WHERE gpid=$querty_default_gpid");
    $q->QUERY_SQL("DELETE FROM webfilters_sqitems WHERE gpid=$querty_google_gpid");
    $q->QUERY_SQL("DELETE FROM webfilters_sqitems WHERE gpid=$querty_ad_gpid");
    $q->QUERY_SQL("DELETE FROM webfilters_sqitems WHERE gpid=$querty_tld_gpid");
    $q->QUERY_SQL("DELETE FROM webfilters_sqitems WHERE gpid=$querty_dhsields_gpid");
    $q->QUERY_SQL("DELETE FROM webfilters_sqitems WHERE gpid=$querty_localnet_gpid");


    $q->QUERY_SQL("INSERT INTO webfilters_sqitems (gpid,pattern,zdate,uid,enabled) 
        VALUES ('$querty_catz_gpid','92','$date','$uid',1)");
    $q->QUERY_SQL("INSERT INTO webfilters_sqitems (gpid,pattern,zdate,uid,enabled) 
        VALUES ('$querty_catz_gpid','105','$date','$uid',1)");
    $q->QUERY_SQL("INSERT INTO webfilters_sqitems (gpid,pattern,zdate,uid,enabled) 
        VALUES ('$querty_catz_gpid','135','$date','$uid',1)");
    $q->QUERY_SQL("INSERT INTO webfilters_sqitems (gpid,pattern,zdate,uid,enabled) 
        VALUES ('$querty_catz_gpid','111','$date','$uid',1)");
    $q->QUERY_SQL("INSERT INTO webfilters_sqitems (gpid,pattern,zdate,uid,enabled) 
        VALUES ('$querty_catz_gpid','64','$date','$uid',1)");
    $q->QUERY_SQL("INSERT INTO webfilters_sqitems (gpid,pattern,zdate,uid,enabled) 
        VALUES ('$querty_catz_gpid','149','$date','$uid',1)");



    $q->QUERY_SQL("INSERT INTO webfilters_sqitems (gpid,pattern,zdate,uid,enabled) 
        VALUES ('$querty_mx_gpid','MX','$date','$uid',1)");

    $q->QUERY_SQL("INSERT INTO webfilters_sqitems (gpid,pattern,zdate,uid,enabled) 
        VALUES ('$querty_ad_gpid','domain.tld','$date','$uid',1)");



    $q->QUERY_SQL("INSERT INTO webfilters_sqitems (gpid,pattern,zdate,uid,enabled) 
        VALUES ('$querty_google_gpid','(^|\.)(google|googlevideo|googleapis|googleusercontent)\.[a-z]+$','$date','$uid',1)");


    $querty_default["A"]=true;
    $querty_default["NS"]=true;
    foreach ($querty_default as $item=>$none) {
        $q->QUERY_SQL("INSERT INTO webfilters_sqitems (gpid,pattern,zdate,uid,enabled) 
        VALUES ('$querty_default_gpid','$item','$date','$uid',1)");

    }

    $sustld["club"]=true;
    $sustld["xxx"]=true;
    $sustld["xyz"]=true;
    $sustld["bid"]=true;
    $sustld["live"]=true;
    $sustld["date"]=true;
    $sustld["name"]=true;
    $sustld["top"]=true;
    $sustld["link"]=true;
    $sustld["(co|web)\.ve"]=true;

    foreach ($sustld as $item=>$none) {
        $q->QUERY_SQL("INSERT INTO webfilters_sqitems (gpid,pattern,zdate,uid,enabled) 
        VALUES ('$querty_tld_gpid','\.$item$','$date','$uid',1)");

    }

    $localnet["10.0.0.0/8"]=true;
    $localnet["172.16.0.0/12"]=true;
    $localnet["192.168.0.0/16"]=true;
    foreach ($localnet as $item=>$none) {
        $q->QUERY_SQL("INSERT INTO webfilters_sqitems (gpid,pattern,zdate,uid,enabled) 
        VALUES ('$querty_localnet_gpid','$item','$date','$uid',1)");

    }


    header("content-type: application/x-javascript");
    echo "LoadAjax('table-dnsfw-rules','$page?table=yes');";

}

function linkrule($aclid,$gpid){
    $tpl=new template_admin();
    if($gpid==0){
        $tpl->js_mysql_alert("linkrule -- $aclid - gpid = 0");
        die();
    }

    $md5=md5($aclid.$gpid);

    $q=new lib_sqlite("/home/artica/SQLITE/acls.db");
    $q->QUERY_SQL("DELETE FROM dnsfw_acls_link WHERE zmd5='$md5'");
    $q->QUERY_SQL("INSERT INTO dnsfw_acls_link (zmd5,aclid,gpid,zOrder) VALUES('$md5','$aclid','$gpid',1)");
    if(!$q->ok){echo $tpl->js_mysql_alert($q->mysql_error);die();}

}

function rule_enable(){
	$q=new lib_sqlite("/home/artica/SQLITE/acls.db");
	header("content-type: application/x-javascript");
	$ligne=$q->mysqli_fetch_array("SELECT enabled FROM dnsfw_acls WHERE ID='{$_GET["enable-js"]}'");
	if(intval($ligne["enabled"])==0){
		
		$js="$( \"#acl-{$_GET["enable-js"]}\" ).removeClass( \"text-muted\" );";
		$enabled=1;
		}
		else{
			$js="$( \"#acl-{$_GET["enable-js"]}\" ).addClass( \"text-muted\" );";
			$enabled=0;
		}
	
	$q->QUERY_SQL("UPDATE dnsfw_acls SET enabled='$enabled' WHERE ID='{$_GET["enable-js"]}'");
	if(!$q->ok){echo "alert('".$q->mysql_error."')";return;}
    admin_tracks("Set a DNS Firewall ACL rule ID {$_GET["enable-js"]} to enable=$enabled");
	$libmem=new lib_memcached();
	$libmem->Delkey("DNSFWOBJS");
	echo $js;
}

function rule_js(){
    //LoadAjax('table-dnsfw-rules','$page?table=yes');
	$page=CurrentPageName();
	$tpl=new template_admin();
	$ID=intval(trim($_GET["rule-id-js"]));
	$q=new lib_sqlite("/home/artica/SQLITE/acls.db");
	$ligne=$q->mysqli_fetch_array("SELECT rulename FROM dnsfw_acls WHERE ID='$ID'");
	$tpl->js_dialog5("{rule}: $ID {$ligne["rulename"]}","$page?rule-tabs=$ID");
}

function rule_checker_js(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $tpl->js_dialog2("{rules_checker}","$page?rule-checker-popup=yes",950);
}
function rule_checker_popup(){

    $QUERY["A"]="[A] RFC 1035 (Address Record)";
    $QUERY["NS"]="[NS] RFC 1035 (Name Server Record)";
    $QUERY["CNAME"]="[CNAME] RFC 1035 (Canonical Name Record (Alias))";
    $QUERY["SOA"]="[SOA] RFC 1035 (Start of Authority Record)";
    $QUERY["PTR"]="[PTR] RFC 1035 (Pointer Record)";
    $QUERY["MX"]="[MX] RFC 1035 (Mail eXchanger Record)";
    $QUERY["TXT"]="[TXT] RFC 1035 (Text Record)";
    $QUERY["RP"]="[RP] RFC 1183 (Responsible Person)";
    $QUERY["AFSDB"]="[AFSDB] RFC 1183 (AFS Database Record)";
    $QUERY["SIG"]="[SIG] RFC 2535";
    $QUERY["KEY"]="[KEY] RFC 2535 & RFC 2930 ";
    $QUERY["AAAA"]="[AAAA] RFC 3596 (IPv6 Address)";
    $QUERY["LOC"]="[LOC] RFC 1876 (Geographic Location)";
    $QUERY["SRV"]="[SRV] RFC 2782 (Service Locator)";
    $QUERY["NAPTR"]="[NAPTR] RFC 3403 (Naming Authority Pointer)";
    $QUERY["KX"]="[KX] RFC 2230 (Key eXchanger)";
    $QUERY["CERT"]="[CERT] RFC 4398 (Certificate Record, PGP etc)";
    $QUERY["DNAME"]="[DNAME] RFC 2672 (Delegation Name Record, wildcard alias)";
    $QUERY["APL"]="[APL] RFC 3123 (Address Prefix List (Experimental)";
    $QUERY["DS"]="[DS] RFC 4034 (Delegation Signer (DNSSEC)";
    $QUERY["SSHFP"]="[SSHFP] RFC 4255 (SSH Public Key Fingerprint)";
    $QUERY["IPSECKEY"]="[IPSECKEY] RFC 4025 (IPSEC Key)";
    $QUERY["RRSIG"]="[RRSIG] RFC 4034 (DNSSEC Signature)";
    $QUERY["NSEC"]="[NSEC] RFC 4034 (Next-secure Record (DNSSEC))";
    $QUERY["DNSKEY"]="[DNSKEY] RFC 4034 (DNS Key Record (DNSSEC))";
    $QUERY["DHCID"]="[DHCID] RFC 4701 (DHCP Identifier)";
    $QUERY["NSEC3"]="[NSEC3] RFC 5155 (NSEC Record v3 (DNSSEC Extension))";
    $QUERY["NSEC3PARAM"]="[NSEC3PARAM] RFC 5155 (NSEC3 Parameters (DNSSEC Extension))";
    $QUERY["HIP"]="[HIP] RFC 5205 (Host Identity Protocol)";
    $QUERY["SPF"]="[SPF] RFC 4408 (Sender Policy Framework)";
    $QUERY["TKEY"]="[TKEY] RFC 2930 (Secret Key)";
    $QUERY["TSIG"]="[TSIG] RFC 2845 (Transaction Signature)";
    $QUERY["IXFR"]="[IXFR] RFC 1995 (Incremental Zone Transfer)";
    $QUERY["AXFR"]="[AXFR] RFC 1035 (Authoritative Zone Transfer)";
    $QUERY["ANY"]="[ANY] RFC 1035 AKA "*" (Pseudo Record)";
    $QUERY["TA"]="[TA] (DNSSEC Trusted Authorities)";
    $QUERY["DLV"]="[DLV] RFC 4431 (DNSSEC Lookaside Validation)";

    $page=CurrentPageName();
    $tpl=new template_admin();
    $t=time();

    if(!isset($_SESSION["RULE_CHECKER_DOMAIN"])){$_SESSION["RULE_CHECKER_DOMAIN"]="www.ibm.com";}
    if(!isset($_SESSION["RULE_CHECKER_SRC"])){$_SESSION["RULE_CHECKER_SRC"]=$_SERVER["REMOTE_ADDR"];}
    if(!isset($_SESSION["RULE_CHECKER_QTYPE"])){$_SESSION["RULE_CHECKER_QTYPE"]="A";}
    if($_SESSION["RULE_CHECKER_SRC"]==null){$_SESSION["RULE_CHECKER_SRC"]=$_SERVER["REMOTE_ADDR"];}
    $tpl->field_hidden("t",$t);
    $form[]=$tpl->field_text("RULE_CHECKER_DOMAIN","{domain}",$_SESSION["RULE_CHECKER_DOMAIN"],true);
    $form[]=$tpl->field_ipv4("RULE_CHECKER_SRC","{src}",$_SESSION["RULE_CHECKER_SRC"],true);
    $form[]=$tpl->field_array_hash($QUERY,"RULE_CHECKER_QTYPE","{type}",$_SESSION["RULE_CHECKER_QTYPE"]);
    $jsafter="LoadAjax('query-$t','$page?rule-checker-results=yes&t=$t')";
    $html[]=$tpl->form_outside("{your_query}", @implode("\n", $form),null,"{verify}",$jsafter,"AsDnsAdministrator");
    $html[]="<div id='query-$t' style='margin:10px'></div>";
    echo $tpl->_ENGINE_parse_body($html);

}
function rule_checker_save(){
    $tpl=new template_admin();
    $tpl->CLEAN_POST();
    $serialize=base64_encode(serialize($_POST));

    foreach ($_POST as $key=>$val){
        if($key=="t"){continue;}
        $_SESSION[$key]=$val;
    }
    $GLOBALS["CLASS_SOCKETS"]->getFrameWork("unbound.php?dnsfw-checker=$serialize");
    
}
function rule_checker_results(){
    $tpl=new template_admin();
    $t=$_GET["t"];
    $tfile=PROGRESS_DIR."/$t.html";
    if(!is_file($tfile)){
        echo $tpl->div_error("$tfile no such file");
        return false;
    }
    $html=@file_get_contents($tfile);
    echo $tpl->_ENGINE_parse_body($html);
}



function rule_tabs(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$ID=intval($_GET["rule-tabs"]);



	$array["{rule}"]="$page?rule-settings=$ID";
	$RefreshTable=base64_encode("LoadAjax('table-dnsfw-rules','$page?table=yes');");
	$array["{objects}"]="fw.proxy.acls.objects.php?rule-id=$ID&TableLink=dnsfw_acls_link&RefreshTable=$RefreshTable";
	echo $tpl->tabs_default($array);
}




function rule_settings(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$ID=intval($_GET["rule-settings"]);
	$tpl->CLUSTER_CLI=true;
	if($ID==0){echo $tpl->FATAL_ERROR_SHOW_128("WRONG ID NUMBER");return;}
	$q=new lib_sqlite("/home/artica/SQLITE/acls.db");
	$ligne=$q->mysqli_fetch_array("SELECT * FROM dnsfw_acls WHERE ID='$ID'");
    $form[]=$tpl->field_hidden("rule-save", "$ID");
    $form[]=$tpl->field_text("rulename", "{rule_name}", $ligne["rulename"],true);
    $form[]=$tpl->field_text("description", "{description}", $ligne["description"],false);
    $form[]=$tpl->field_array_hash($GLOBALS["DNS_ACTIONS"],"action","{action}",$ligne["action"]);
    $form[]=$tpl->field_numeric("ttl","{ttl} <small>({seconds})</small>",$ligne["ttl"]);
    $form[]=$tpl->field_text("redirector","{dns_server}",$ligne["redirector"],null);
    $form[]=$tpl->field_text("reply","{reply}",$ligne["reply"],null);

	$jsafter="LoadAjax('table-dnsfw-rules','$page?table=yes');";

	$html=$tpl->form_outside(utf8_encode($ligne["rulename"]), @implode("\n", $form),"{form_dnsfirewalr_explain}","{apply}",$jsafter,"AsDnsAdministrator");
	echo $tpl->_ENGINE_parse_body($html);
	
}

function rule_save(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$tpl->CLEAN_POST_XSS();
	$q=new lib_sqlite("/home/artica/SQLITE/acls.db");

    if(!$q->FIELD_EXISTS("dnsfw_acls",'description')) {
        $q->QUERY_SQL("ALTER TABLE dnsfw_acls add `description` TEXT NULL");
    }
	
	$ID=$_POST["rule-save"];
	$rulename=$q->sqlite_escape_string2(utf8_decode($_POST["rulename"]));


    $FF[]="`rulename`";
    $FF[]="`description`";
    $FF[]="`action`";
    $FF[]="`redirector`";
    $FF[]="`reply`";
    $FF[]="`ttl`";


    $FA[]="'$rulename'";
    $FA[]="'{$_POST["description"]}'";
    $FA[]="'{$_POST["action"]}'";
    $FA[]="'{$_POST["redirector"]}'";
    $FA[]="'{$_POST["reply"]}'";
    $FA[]="'{$_POST["ttl"]}'";


	
	foreach ($FF as $index=>$key){$FB[]="$key={$FA[$index]}";}
	
	$sql="UPDATE dnsfw_acls SET ".@implode(",", $FB)." WHERE ID=$ID";
	$q->QUERY_SQL($sql);
	if(!$q->ok){echo $q->mysql_error_html(true);return;}
    admin_tracks("update $rulename DNS Firewall ACL rule settings");
    $libmem=new lib_memcached();
    $libmem->Delkey("DNSFWOBJS");

    $c=0;
	$sql="SELECT ID FROM dnsfw_acls ORDER BY zorder";
	$results = $q->QUERY_SQL($sql);
	
	foreach($results as $index=>$ligne) {
		$q->QUERY_SQL("UPDATE dnsfw_acls SET zorder=$c WHERE `ID`={$ligne["ID"]}");
		if(!$q->ok){echo $q->mysql_error_html(true);return;}
		$c++;
	}
	
	
}

function new_rule_js(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$title="{new_rule}";
	$tpl->js_dialog($title,"$page?newrule-popup=yes");
}

function HEADERS_ARRAY(){
	$ql=new lib_sqlite("/home/artica/SQLITE/acls.db");
	$sql="SELECT * FROM http_headers ORDER BY HeaderName";
	$resultsHeaders=$ql->QUERY_SQL($sql);
	foreach ($resultsHeaders as $index=>$ligneHeaders){
		$HEADERSZ[$ligneHeaders["HeaderName"]]=$ligneHeaders["HeaderName"];
	}
	return $HEADERSZ;
}

function new_rule_popup(){
	$page=CurrentPageName();
	$tpl=new template_admin();
    $tpl->CLUSTER_CLI=true;
	$form[]=$tpl->field_hidden("newrule", "yes");
	$form[]=$tpl->field_text("rulename", "{rule_name}", null,true);
    $form[]=$tpl->field_text("description", "{description}", null,false);
	$form[]=$tpl->field_array_hash($GLOBALS["DNS_ACTIONS"],"action","{action}",0);
    $form[]=$tpl->field_numeric("ttl","{ttl} <small>({seconds})</small>",1800);
    $form[]=$tpl->field_text("redirector","{dns_server}",null,null);
    $form[]=$tpl->field_text("reply","{reply}",null,null);
    $jsafter="LoadAjax('table-dnsfw-rules','$page?table=yes');BootstrapDialog1.close();";
	
	$html=$tpl->form_outside("{new_rule}", @implode("\n", $form),"{form_dnsfirewalr_explain}","{add}",$jsafter,"AsDnsAdministrator");
	echo $tpl->_ENGINE_parse_body($html);

	
}



function rule_move(){
	$tpl=new template_admin();
	$ID=$_GET["acl-rule-move"];
	$q=new lib_sqlite("/home/artica/SQLITE/acls.db");
	$sql="SELECT zorder FROM dnsfw_acls WHERE `ID`='$ID'";
	$ligne=$q->mysqli_fetch_array($sql);
	if($GLOBALS["VERBOSE"]){echo "$ID, order={$ligne["xORDER"]};\n";}
	$xORDER_ORG=intval($ligne["zorder"]);
	$xORDER=$xORDER_ORG;
	
	
	if($_GET["acl-rule-dir"]==1){$xORDER=$xORDER_ORG-1;}
	if($_GET["acl-rule-dir"]==0){$xORDER=$xORDER_ORG+1;}
	if($xORDER<0){$xORDER=0;}
	$sql="UPDATE dnsfw_acls SET zorder=$xORDER WHERE `ID`='$ID'";
	$q->QUERY_SQL($sql);
	if(!$q->ok){echo "alert('".$tpl->javascript_parse_text($q->mysql_error)."');";return;}
	if($GLOBALS["VERBOSE"]){echo "$sql\n";}

	if($_GET["acl-rule-dir"]==1){
		$xORDER2=$xORDER+1;
		if($xORDER2<0){$xORDER2=0;}
		$sql="UPDATE dnsfw_acls SET zorder=$xORDER2 WHERE `ID`<>'$ID' AND zorder=$xORDER";
		$q->QUERY_SQL($sql);
		if($GLOBALS["VERBOSE"]){echo "$sql\n";}

	if(!$q->ok){echo "alert('".$tpl->javascript_parse_text($q->mysql_error)."');";return;}
	}
	if($_GET["acl-rule-dir"]==0){
		$xORDER2=$xORDER-1;
		if($xORDER2<0){$xORDER2=0;}
		$sql="UPDATE dnsfw_acls SET zorder=$xORDER2 WHERE `ID`<>'{$_GET["acl-rule-move"]}'";
		$q->QUERY_SQL($sql);
		if(!$q->ok){echo "alert('".$tpl->javascript_parse_text($q->mysql_error)."');";return;}
		if($GLOBALS["VERBOSE"]){echo "$sql\n";}
	}

	$c=0;
	$sql="SELECT ID FROM dnsfw_acls ORDER BY zorder";
	$results = $q->QUERY_SQL($sql);

	foreach($results as $index=>$ligne) {
		$q->QUERY_SQL("UPDATE dnsfw_acls SET zorder=$c WHERE `ID`={$ligne["ID"]}");
		if($GLOBALS["VERBOSE"]){echo "UPDATE dnsfw_acls SET zorder=$c WHERE `ID`={$ligne["ID"]}\n";}
		$c++;
	}
    $sql="SELECT ID FROM dnsfw_acls WHERE enabled=1 ORDER BY zorder";
    $results = $q->QUERY_SQL($sql);
    $c=1;
    foreach($results as $index=>$ligne) {
        $q->QUERY_SQL("UPDATE dnsfw_acls SET delay_pool_number=$c WHERE `ID`={$ligne["ID"]}");
        if($GLOBALS["VERBOSE"]){echo "UPDATE dnsfw_acls SET delay_pool_number=$c WHERE `ID`={$ligne["ID"]}\n";}
        $c++;
    }
    $libmem=new lib_memcached();
    $libmem->Delkey("DNSFWOBJS");

}


function table(){
	$tpl=new template_admin();
	$page=CurrentPageName();
	$order=$tpl->_ENGINE_parse_body("{order}");
	$enabled=$tpl->_ENGINE_parse_body("{enabled}");
    $q=new lib_sqlite("/home/artica/SQLITE/acls.db");
    $PowerDNSEnableClusterSlave=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("PowerDNSEnableClusterSlave"));
    $examples=null;
    $jsexample="Loadjs('$page?js-example=yes');";


	if($PowerDNSEnableClusterSlave==0) {
        $ligne = $q->mysqli_fetch_array("SELECT * FROM dnsfw_acls WHERE ID=1");
        if (strlen(trim($ligne["rulename"])) == 0) {
            $examples = "<label class=\"btn btn btn-warning\" OnClick=\"$jsexample\"><i class='fas fa-hat-wizard'></i> {example} </label>";
        }
    }

    $jsapply=$tpl->framework_buildjs("/unbound/restart",
        "unbound.restart.progress","unbound.restart.log","progress-dnsfw-restart","LoadAjax('table-dnsfw-rules','$page?table=yes');");

	
	
	$t=time();
	$add="Loadjs('$page?newrule-js=yes',true);";
    $jchecker="Loadjs('$page?rule-checker-js=yes',true);";
    $jshelp="s_PopUpFull('https://wiki.articatech.com/dns-firewall',1024,768,'Bandwidth Limiting')";
	$html[]="<div class=\"btn-group\" data-toggle=\"buttons\" style='margin-top: 15px'>";
	if($PowerDNSEnableClusterSlave==0) {
        $html[] = "<label class=\"btn btn btn-primary\" OnClick=\"$add\"><i class='fa fa-plus'></i> {new_rule} </label>";
    }
    $html[]="$examples";
    $html[]="<label class=\"btn btn btn-info\" OnClick=\"$jsapply\"><i class='fas fa-file-check'></i> {apply_configuration}</label>";

    $html[]="<label class=\"btn btn btn-primary\" OnClick=\"$jchecker\"><i class='fas fa-search'></i> {rules_checker} </label>";

    $html[]="<label class=\"btn btn btn-info\" OnClick=\"Loadjs('$page?options-js=yes')\"><i class='fas fas fa-cogs'></i> {advanced_options} </label>";

    $html[]="<label class=\"btn btn btn-warning\" OnClick=\"$jshelp\"><i class='fas fa-question-circle'></i> Wiki </label>";
    $html[]="</div>";

    $html[]="<table id='table-$t' class=\"footable table table-stripped\" data-page-size=\"100\" data-paging=\"true\">";
	$html[]="<thead>";
	$html[]="<tr>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='number' style='width:1%'>$order</th>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text' nowrap>{rulename}</th>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{description}</th>";
	if($PowerDNSEnableClusterSlave==0) {
        $html[] = "<th data-sortable=true class='text-capitalize center'>$enabled</th>";
        $html[] = "<th data-sortable=false></th>";
        $html[] = "<th data-sortable=false></th>";

    }
	$html[]="</tr>";
	$html[]="</thead>";
	$html[]="<tbody>";
	
	$jsAfter="LoadAjax('table-proxyheader-rules','$page?table=yes');";
	$GLOBALS["jsAfterEnc"]=base64_encode($jsAfter);

	
	$results=$q->QUERY_SQL("SELECT * FROM dnsfw_acls ORDER BY zorder");
	$TRCLASS=null;
	
	
	foreach($results as $index=>$ligne) {
		if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
		$MUTED=null;
		$md=md5(serialize($ligne));
		$ID=$ligne["ID"];
		$ligne['rulename']=$tpl->utf8_encode($ligne['rulename']);
		
		$delete=$tpl->icon_delete("Loadjs('$page?rule-delete-js=$ID&md=$md')","AsDnsAdministrator");
		$js="Loadjs('$page?rule-id-js=$ID')";
		if($ligne["enabled"]==0){$MUTED=" text-muted";}
		
	
		$explain=EXPLAIN_THIS_RULE($ID);
		$up=$tpl->icon_up("Loadjs('$page?acl-rule-move=$ID&acl-rule-dir=1');");
		$down=$tpl->icon_down("Loadjs('$page?acl-rule-move=$ID&acl-rule-dir=0');");
		$enable_js="Loadjs('$page?enable-js=$ID')";
		
		$html[]="<tr class='$TRCLASS{$MUTED}' id='$md'>";
		$html[]="<td class='center' style='width:1%' nowrap>{$ligne["zorder"]}</td>";
		$html[]="<td style='vertical-align:middle;width:1%' nowrap>". $tpl->td_href($ligne["rulename"],"{click_to_edit}",$js)."</td>";
		$html[]="<td style='vertical-align:middle'>$explain</td>";
        if($PowerDNSEnableClusterSlave==0) {
            $html[] = "<td class='center' style='width:1%' nowrap>" . $tpl->icon_check($ligne["enabled"], $enable_js) . "</td>";
            $html[] = "<td style='vertical-align:middle;;width:1%' class='center' nowrap>$up&nbsp;&nbsp;$down</center></td>";
            $html[] = "<td style='vertical-align:middle;width:1%' class='center' nowrap>$delete</td>";
        }
		$html[]="</tr>";
	
	}


    if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
    $html[]="<tr class='$TRCLASS' id='fegthi5678'>";
    $html[]="<td class='center' style='width:1%' nowrap>&nbsp;-&nbsp;</td>";
    $html[]="<td style='vertical-align:middle;width:1%'  nowrap>". $tpl->td_href("{default}","{click_to_edit}","Loadjs('$page?options-js=yes')")."</td>";
    $html[]="<td style='vertical-align:middle'>{dnsfw_explain_theshields}</td>";
    $html[] = "<td class='center' style='width:1%' nowrap>&nbsp;</td>";
    $html[] = "<td style='vertical-align:middle;width:1%' nowrap>&nbsp;&nbsp;&nbsp;&nbsp</td>";
    $html[] = "<td style='vertical-align:middle;width:1%' class='center'nowrap>&nbsp;</td>";

    $html[]="</tr>";
	
	
	$html[]="</tbody>";
	$html[]="<tfoot>";
	
	$html[]="<tr>";
	$html[]="<td colspan='7'>";
	$html[]="<ul class='pagination pull-right'></ul>";
	$html[]="</td>";
	$html[]="</tr>";
	$html[]="</tfoot>";
	$html[]="</table>";
	$html[]="
<script> 
NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS)."
$(document).ready(function() { $('#table-$t').footable( { \"filtering\": { \"enabled\": true }, \"sorting\": { \"enabled\": true },\"paging\": { \"size\": {$GLOBALS["FOOTABLE_PSIZE"]} } } ); }); 
</script>";
	
	echo $tpl->_ENGINE_parse_body(@implode("\n", $html));
}

function new_rule_save(){
	$tpl=new template_admin();
	$tpl->CLEAN_POST();
	$q=new lib_sqlite("/home/artica/SQLITE/acls.db");


	if(!$q->FIELD_EXISTS("dnsfw_acls",'redirector')){
        $q->QUERY_SQL("ALTER TABLE dnsfw_acls add `redirector` TEXT NULL");

    }
    if(!$q->FIELD_EXISTS("dnsfw_acls",'ttl')) {
        $q->QUERY_SQL("ALTER TABLE dnsfw_acls add `ttl` INTEGER NOT NULL DEFAULT 1800");
    }
    if(!$q->FIELD_EXISTS("dnsfw_acls",'description')) {
        $q->QUERY_SQL("ALTER TABLE dnsfw_acls add `description` TEXT NULL");
    }

	$TempName=md5(time());
	$rulename=sqlite_escape_string2(utf8_decode($_POST["rulename"]));

	if($_POST["description"]==null){
        $_POST["description"]="{added}". date("Y d l f H:i");
    }

	$ligne=$q->mysqli_fetch_array("SELECT ID FROM dnsfw_acls ORDER BY ID LIMIT 1" );
    $ID=intval($ligne["ID"]);
	if($ID==0){
        $FF[]="`ID`";
    }


	$FF[]="`rulename`";
	$FF[]="`action`";
	$FF[]="`description`";
	$FF[]="`reply`";
	$FF[]="`redirector`";
	$FF[]="`ttl`";
	$FF[]="`enabled`";
	$FF[]="`zorder`";

	if($ID==0){
        $FA[]="10";
    }
	$FA[]="'$TempName'";
	$FA[]="'{$_POST["action"]}'";
    $FA[]="'{$_POST["description"]}'";
	$FA[]="'{$_POST["reply"]}'";
    $FA[]="'{$_POST["redirector"]}'";
	$FA[]="'{$_POST["ttl"]}'";
	$FA[]=1;
	$FA[]=0;
	

	
	$sql="INSERT INTO dnsfw_acls (".@implode(",", $FF).") VALUES (".@implode(",", $FA).")";
	$q->QUERY_SQL($sql);
	if(!$q->ok){echo $q->mysql_error_html(true);return;}
	admin_tracks("Create a new $rulename DNS Firewall ACL rule ");
	
	$ligne=$q->mysqli_fetch_array("SELECT ID FROM dnsfw_acls WHERE rulename='$TempName'");
	$LASTID=$ligne["ID"];
	
	$q->QUERY_SQL("UPDATE dnsfw_acls SET rulename='$rulename' WHERE ID='$LASTID'");
	
	$c=0;
	$sql="SELECT ID FROM dnsfw_acls WHERE enabled=1 ORDER BY zorder";
	$results = $q->QUERY_SQL($sql);
	foreach($results as $index=>$ligne) {
		$q->QUERY_SQL("UPDATE dnsfw_acls SET zorder=$c WHERE `ID`={$ligne["ID"]}");
		if(!$q->ok){echo $q->mysql_error_html(true);return;}
		$c++;
	}

    admin_tracks("Created/Updated DNS Firewall rule $rulename");
}

function rule_delete($ID){
	$q=new lib_sqlite("/home/artica/SQLITE/acls.db");
    $ligne=$q->mysqli_fetch_array("SELECT rulename FROM dnsfw_acls WHERE aclid='$ID'");
    $rulename=$ligne["rulename"];

	$q->QUERY_SQL("DELETE FROM dnsfw_acls_link WHERE aclid='$ID'");
	if(!$q->ok){echo "alert('".$q->mysql_error."');";return false;}
	$q->QUERY_SQL("DELETE FROM dnsfw_acls WHERE ID='$ID'");
	if(!$q->ok){echo "alert('".$q->mysql_error."');";return false;}
    admin_tracks("Delete a DNS Firewall ACL rule ID $ID");
    $libmem=new lib_memcached();
    $libmem->Delkey("DNSFWOBJS");
    admin_tracks("Removed DNS Firewall Rule ID $ID $rulename");
    return true;
	
}
function EXPLAIN_THIS_RULE($ID){
    $tpl=new templates();
    $UnBoundCacheMinTTL = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UnBoundCacheMinTTL"));
    $UnBoundCacheMAXTTL = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UnBoundCacheMAXTTL"));
    if($UnBoundCacheMinTTL==0){$UnBoundCacheMinTTL=3600;}
    if($UnBoundCacheMAXTTL==0){$UnBoundCacheMAXTTL=172800;}

	$page=CurrentPageName();
	$acls=new squid_acls_groups();
	$q=new lib_sqlite("/home/artica/SQLITE/acls.db");
	$ligne=$q->mysqli_fetch_array("SELECT * FROM dnsfw_acls WHERE ID=$ID");
    $objects=$acls->getobjectsNameFromAclrule($ligne['ID'],"black","dnsfw_acls_link");
    $description=$ligne["description"];
    if(count($objects)==0){
        $THEN_TEXT="{error_acl_no_object}";

        return  $tpl->_ENGINE_parse_body("<span id='explain-this-rule-$ID' data='$page?explain-this-rule=$ID'>$THEN_TEXT</span>");

        if(isset($_GET["explain-this-rule"])){
            return $tpl->_ENGINE_parse_body($THEN_TEXT);
        }

    }
    if(count($objects)>0){$THEN[]="{for_objects} ". @implode(", {and} ", $objects)." {then}";}


    $action=intval($ligne["action"]);
	$reply=trim($ligne["reply"]);
	$ttl=intval($ligne["ttl"]);
	$redirector=$ligne["redirector"];

	if($action<>4 || $action<>5 ){$THEN[]=$GLOBALS["DNS_ACTIONS"][$action];}
	if($action==4){
        $THEN[]="{then} {NOERROR} {with} <strong>$reply (TTL:{$ttl}s)</strong>";
        if($ttl<$UnBoundCacheMinTTL){
            $THEN[]="<br><span class='text-danger font-bold'>{notice}: {cache-ttl} (Min) {$UnBoundCacheMinTTL}s</span>";
        }
        if($ttl>$UnBoundCacheMAXTTL){
            $THEN[]="<br><span class='text-danger font-bold'>{notice}: {cache-ttl} (Max) {$UnBoundCacheMAXTTL}s</span>";
        }
    }
    if($action==5){$THEN[]="{then} {query_dnssrv} {with} <strong>$redirector</strong>";}
    if($description<>null){$THEN[]="<br><small>($description)</small>";}
    $THEN_TEXT=@implode(" ",$THEN);

    if(isset($_GET["explain-this-rule"])){
        return $tpl->_ENGINE_parse_body($THEN_TEXT);
    }


    return  $tpl->_ENGINE_parse_body("<span id='explain-this-rule-$ID' data='$page?explain-this-rule=$ID'>$THEN_TEXT</span>");



}

function status(){
    $tpl=new template_admin();
    $time=time();

    if(!is_file("img/squid/dnsfw_cnx-day.png")){

        $html=$tpl->div_error("{no_data}||{error_graphic_is_not_generated}");
        echo  $tpl->_ENGINE_parse_body($html);
        return true;
    }

    $html[]="<center style='margin:30px'>
    <a href='javascript:blur()' OnClick=\"javascript:Loadjs('fw.rrd.php?img=dnsfw_cnx');\">
    <img src='img/squid/dnsfw_cnx-day.png?t=$time'></a>
    </center>";
    $html[]="<center style='margin:30px'>
<a href='javascript:blur()' OnClick=\"javascript:Loadjs('fw.rrd.php?img=dnsfw_users');\">
<img src='img/squid/dnsfw_users-day.png?t=$time'></a></center>";



    echo  $tpl->_ENGINE_parse_body($html);
    return true;
}

function options_popup(){
    $tpl=new template_admin();
    $DNSFireWallVerbose=intval(trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DNSFireWallVerbose")));
    $DNSFireWallDefaultReply=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DNSFireWallDefaultReply"));
    $DNSFireWallDefaultTTL=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DNSFireWallDefaultTTL"));
    if($DNSFireWallDefaultReply==null){$DNSFireWallDefaultReply="0.0.0.0";}
    if($DNSFireWallDefaultTTL==0){$DNSFireWallDefaultTTL=1800;}
    $DNSFireWallStatsRetention=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DNSFireWallStatsRetention"));
    if($DNSFireWallStatsRetention==0){$DNSFireWallStatsRetention=5;}


    if(!$GLOBALS["CLASS_SOCKETS"]->CORP_LICENSE()){
        $DNSFireWallStatsRetention=5;
        echo $tpl->_ENGINE_parse_body($tpl->div_warning("{statistics_retention_parameters}||{retention_time_limited_license}"));
    }

    $form[]=$tpl->field_checkbox("DNSFireWallVerbose","{verbose_mode}",$DNSFireWallVerbose,false);

    $form[]=$tpl->field_ipv4("DNSFireWallDefaultReply","{NOERROR} ({default})",$DNSFireWallDefaultReply);
    $form[]=$tpl->field_numeric("DNSFireWallDefaultTTL","{ttl} {seconds} ({default})",$DNSFireWallDefaultTTL);
    $form[]=$tpl->field_numeric("DNSFireWallStatsRetention","{retention_days} ({statistics})",$DNSFireWallStatsRetention,"{SuricataPurges}");




    $hml[]=$tpl->form_outside("{options}", @implode("\n", $form),null,"{apply}","blur();","AsSystemAdministrator");
    echo @implode("\n", $hml);
}

function options_js(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $title="{advanced_options}";
    $tpl->js_dialog($title,"$page?options-popup=yes");

}

function options_save(){
    $tpl=new template_admin();
    $tpl->CLEAN_POST();
    $tpl->SAVE_POSTs();
    $GLOBALS["CLASS_SOCKETS"]->getFrameWork("unbound.php?reload=yes");
}

