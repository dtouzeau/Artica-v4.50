<?php
// POST SP10 20261023-12
// Post SP10 - 13/10/2026
// Service Pack 9 2026-10-10
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
// awc_table_style() / awc_pager_js(): feuille de style et pager partages des tableaux d'entites
include_once(dirname(__FILE__)."/ressources/class.statistics_appliance.inc");
include_once(dirname(__FILE__)."/ressources/class.ccurl.inc");
include_once(dirname(__FILE__)."/ressources/class.squid.familysites.inc");
// Pentest 2026-11-04 : les adresses IP, noms d'hote, domaines et adresses e-mail recus etaient recopies sans
// encodage dans la page (XSS) et concatenes dans les requetes. On les ramene a leur alphabet reel.
foreach(array("zoom-ip-js","zoom-ip-popup","hostname","blacklist-ip","remove-connections","inform-articatech","firewall","whitelist-hostname") as $k){
    if(isset($_GET[$k])){$_GET[$k]=preg_replace('/[^A-Za-z0-9_.:-]/','',(string)$_GET[$k]);}
}
foreach(array("whitelist-hostname","remove-connections") as $k){
    if(isset($_POST[$k])){$_POST[$k]=preg_replace('/[^A-Za-z0-9_.:-]/','',(string)$_POST[$k]);}
}
if(isset($_GET["domain"])){$_GET["domain"]=preg_replace('/[^A-Za-z0-9_.-]/','',(string)$_GET["domain"]);}
foreach(array("zoom-from-js","zoom-from-popup","whitelist-from","blacklist-from") as $k){
    if(isset($_GET[$k])){$_GET[$k]=preg_replace('/[^A-Za-z0-9_.+*@=-]/','',(string)$_GET[$k]);}
}
foreach(array("spamreport-js","spamreport-popup") as $k){if(isset($_GET[$k])){$_GET[$k]=intval($_GET[$k]);}}
// Le rappel "after" est fabrique par zoom_from_popup() : BootstrapDialog1.close(); suivi d'un Loadjs().
// Pentest 2026-11-04 : second-query/query2 transportaient une clause WHERE en clair (base64) executee
// telle quelle : la clause est desormais signee (HMAC de session) et chaque valeur y est echappee.
function pfxtx_sq($field,$value):string{
    return signed_html_token("WHERE $field='".pg_escape_string2($value)."'");
}
function postfix_transactions_after():string{
    $after=trim((string)($_GET["after"] ?? ""));
    $prefix="BootstrapDialog1.close();";
    if(strpos($after,$prefix)===0){return $prefix.jsafter_sanitize(substr($after,strlen($prefix)));}
    return jsafter_sanitize($after);
}
if(isset($_GET["search"])){search();exit;}
if(isset($_GET["extract"])){extract_query();exit;}
if(isset($_GET["blacklist-ip"])){blacklist_ip();exit;}
if(isset($_GET["zoom-ip-js"])){zoom_ip_js();exit;}
if(isset($_GET["zoom-from-js"])){zoom_from_js();exit;}
if(isset($_GET["zoom-ip-popup"])){zoom_ip_popup();exit;}
if(isset($_GET["infected-js"])){infected_js();exit;}
if(isset($_GET["infected-popup"])){infected_popup();exit;}
if(isset($_GET["infected-white"])){infected_whitelist();exit;}
if(isset($_GET["second-query"])){second_query_start();exit;}
if(isset($_GET["second-query-step1"])){second_query_step1();exit;}
if(isset($_GET["remove-connections"])){remove_connections_js();exit;}
if(isset($_POST["remove-connections"])){remove_connections_save();exit;}
if(isset($_GET["inform-articatech"])){inform_articatech();exit;}
if(isset($_GET["spamreport-js"])){spamreport_js();exit;}
if(isset($_GET["spamreport-popup"])){spamreport_popup();exit;}
if(isset($_GET["whitelist-hostname"])){whitelist_hostname_js();exit;}
if(isset($_POST["whitelist-hostname"])){whitelist_hostname_perform();exit;}
if(isset($_GET["zoom-from-popup"])){zoom_from_popup();exit;}
if(isset($_GET["firewall"])){firewall();exit;}
if(isset($_GET["whitelist-from"])){whitelist_from_perform();exit;}
if(isset($_GET["blacklist-from"])){blacklist_from_perform();exit;}

table();

function spamreport_js(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$id=$_GET["spamreport-js"];
	// Pentest 2026-11-04 : title etait recopie sans encodage dans la page (XSS).
	$title=req_html(req_html($_GET["title"]));
	$tpl->js_dialog($title, "$page?spamreport-popup=$id");
}

function whitelist_hostname_js(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$hostname=$_GET["whitelist-hostname"];
	$ARRAY["PROGRESS_FILE"]="/usr/share/artica-postfix/ressources/logs/smtpd_client_restrictions_progress";
	$ARRAY["LOG_FILE"]="/usr/share/artica-postfix/ressources/logs/smtpd_client_restrictions_progress.log";
	$ARRAY["CMD"]="/postfix/smtpd/restrictions/0";
	$ARRAY["TITLE"]="{smtpd_client_restrictions}";
	$prgress=base64_encode(serialize($ARRAY));
	$jsApply="Loadjs('fw.progress.php?".fw_progress_args_b64($prgress)."&mainid=ip-progress-zoom')";
	$tpl->js_confirm_execute(req_html($hostname).":<br>{global_whitelist_hostname_ask}", "whitelist-hostname", $hostname,$jsApply);
}
function firewall(){
	// Rewired 2026-08 to the artica-postfix daemon: the add + audit go through /postfix/ipset/add
	// (parameterised — the raw INSERT here concatenated $_GET) and the compile is the daemon's own
	// fire-and-track job, not a shell-exec of the old PHP SMTP ipset script via postfix2.php.
	$tpl=new template_admin();
	$tpl->CLEAN_POST();
	$ipaddr=(string)$_GET["firewall"];
	$admin=isset($_SESSION["uid"])?$_SESSION["uid"]:"Manager";
	if($admin==-100){$admin="Manager";}
	$resp=$GLOBALS["CLASS_SOCKETS"]->REST_API_POSTFIX_POST_JSON("/postfix/ipset/add",
		array("ipaddr"=>$ipaddr,"admin"=>(string)$admin));
	$j=json_decode((string)$resp,true);
	if(!is_array($j) || empty($j["success"])){
		$msg=(is_array($j)&&!empty($j["error"]))?$j["error"]:"$ipaddr not an IP/CDIR address";
		$tpl->js_error($msg);return;
	}
	$ARRAY["PROGRESS_FILE"]="/usr/share/artica-postfix/ressources/logs/web/ipset.progress";
	$ARRAY["LOG_FILE"]="/usr/share/artica-postfix/ressources/logs/web/ipset.progress.log";
	$ARRAY["CMD"]="postfix:/postfix/ipset/compile";
	$ARRAY["TITLE"]="{APP_FIREWALL} {compile_rules}";
	$prgress=base64_encode(serialize($ARRAY));
	$jsRestart="Loadjs('fw.progress.php?".fw_progress_args_b64($prgress)."&mainid=ip-progress-zoom')";
	echo $jsRestart.";\n";
}

function whitelist_from_perform(){
	$pattern=$_GET["whitelist-from"];
	$patternNone=str_replace("*@", "", $pattern);
	$tpl=new template_admin();
	$q=new postgres_sql();
	// Pentest 2026-11-04 : pattern/uid etaient concatenes dans la requete.
	$q->QUERY_SQL_PARAMS("DELETE FROM miltergreylist_acls WHERE method='blacklist' AND pattern=$1",array($pattern));
	
	$userid=$_SESSION["uid"];
	if($userid==-100){$userid="Manager";}
	
	$sql="SELECT id FROM miltergreylist_acls ORDER BY id desc LIMIT 1";
	$ligne=$q->mysqli_fetch_array($sql);
	$lastid=$ligne["id"];
	$lastid++;
	
	$lastid=intval($lastid);
	$q->QUERY_SQL_PARAMS("INSERT INTO miltergreylist_acls (id,zdate,method,type,pattern,description) VALUES($lastid,NOW(),'whitelist','from',$1,$2)",array($pattern,"By $userid"));
	if(!$q->ok){echo $tpl->js_mysql_alert($q->mysql_error);return;}
	
	$zmd5=md5("$patternNone*");
	$q->QUERY_SQL_PARAMS("INSERT INTO autowhite (zmd5,mailfrom,mailto) VALUES ($1,$2,'*')",array($zmd5,$patternNone));
	if(!$q->ok){echo $tpl->js_mysql_alert($q->mysql_error);return;}
	

	$ARRAY["PROGRESS_FILE"]="/usr/share/artica-postfix/ressources/logs/smtpd_client_restrictions_progress";
	$ARRAY["LOG_FILE"]="/usr/share/artica-postfix/ressources/logs/smtpd_client_restrictions_progress.log";
	$ARRAY["CMD"]="/postfix/smtpd/restrictions/0";
	$ARRAY["TITLE"]="{smtpd_client_restrictions}";
	$ARRAY["AFTER"]=postfix_transactions_after(); // Pentest 2026-11-04 : after etait execute tel quel (XSS).
	$prgress=base64_encode(serialize($ARRAY));
	$jsApply="Loadjs('fw.progress.php?".fw_progress_args_b64($prgress)."&mainid=email-progress-zoom')";
	echo $jsApply;
}

function blacklist_from_perform(){
	$pattern=$_GET["blacklist-from"];
	$patternNone=str_replace("*@", "", $pattern);
	$tpl=new template_admin();
	$q=new postgres_sql();
	// Pentest 2026-11-04 : pattern/uid etaient concatenes dans la requete.
	$q->QUERY_SQL_PARAMS("DELETE FROM miltergreylist_acls WHERE method='whitelist' AND pattern=$1",array($pattern));
	$q->QUERY_SQL_PARAMS("DELETE FROM autowhite WHERE mailfrom=$1",array($patternNone));
	
	$userid=$_SESSION["uid"];
	if($userid==-100){$userid="Manager";}
	
	$sql="SELECT id FROM miltergreylist_acls ORDER BY id desc LIMIT 1";
	$ligne=$q->mysqli_fetch_array($sql);
	$lastid=$ligne["id"];
	$lastid++;
	
	$lastid=intval($lastid);
	$q->QUERY_SQL_PARAMS("INSERT INTO miltergreylist_acls (id,zdate,method,type,pattern,description) 
			VALUES($lastid,NOW(),'blacklist','from',$1,$2)",array($pattern,"By $userid"));
	if(!$q->ok){echo $tpl->js_mysql_alert($q->mysql_error);return;}
	
	
	
	
	$ARRAY["PROGRESS_FILE"]="/usr/share/artica-postfix/ressources/logs/smtpd_client_restrictions_progress";
	$ARRAY["LOG_FILE"]="/usr/share/artica-postfix/ressources/logs/smtpd_client_restrictions_progress.log";
    $ARRAY["CMD"]="/postfix/smtpd/restrictions/0";
	$ARRAY["TITLE"]="{smtpd_client_restrictions}";
	$ARRAY["AFTER"]=postfix_transactions_after(); // Pentest 2026-11-04 : after etait execute tel quel (XSS).
	$prgress=base64_encode(serialize($ARRAY));
	$jsApply="Loadjs('fw.progress.php?".fw_progress_args_b64($prgress)."&mainid=email-progress-zoom')";
	echo $jsApply;
}


function whitelist_hostname_perform(){
	$tpl=new template_admin();
	$tpl->CLEAN_POST();
	$pattern=$_POST["whitelist-hostname"];
	// Pentest 2026-11-04 : whitelist-hostname etait concatene dans la requete.
	$sql="INSERT INTO smtpd_milter_maps (pattern,enabled) VALUES (?,1)";
	$q=new lib_sqlite("/home/artica/SQLITE/postfix.db");
	$q->QUERY_SQL_PARAMS($sql,array($pattern));
	if(!$q->ok){echo $q->mysql_error;}
	
}

function spamreport_popup(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$q=new postgres_sql();
	$id=$_GET["spamreport-popup"];
	$id=intval($id);
	$ligne=pg_fetch_array($q->QUERY_SQL("SELECT spamreport FROM smtplog WHERE id='$id'"));
	
	$ligne["spamreport"]=str_replace("#012","\n",$ligne["spamreport"]);
	$tp=explode("\n",$ligne["spamreport"]);
	
	// Pentest 2026-11-04 : spamreport (donnee externe) affiche sans encodage (XSS stockee).
	foreach ($tp as $line){
		$line=trim($line);
		if($line==null){continue;}
		if(preg_match("#^([0-9\.]+)\s+(.+)#", $line,$re)){
			$f[]="<strong class='text-danger'>".req_html($re[1])."</strong>: <strong>".req_html($re[2])."</strong>";
			continue;
		}
		
		if(preg_match("#^(Content analysis details):(.+)#",$line,$re)){
			$f[]="<h2 class='text-danger'>".req_html($re[1])." <strong>".req_html($re[2])."</strong></h2><hr>";
			continue;
		}
		
		if(preg_match("#rule\s+name\s+description#",$line)){continue;}
		if(strpos($line, "----------------------------------------------")>0){continue;}
		
		$f[]="<span class='text-muted'>".req_html($line)."</span>";
	}
	
	echo "<div style='margin:20px'>".@implode("<br>", $f)."</div>";
	
}

function inform_articatech(){
	$ipaddr=$_GET["inform-articatech"];
	$tpl=new template_admin();
	$curl=new ccurl("https://rbl.artica.center/api/rest/rbl/set/black/$ipaddr");
	$curl->Timeout=5;
	$curl->NoHTTP_POST=true;
	$mem=new lib_memcached();
	if(!$curl->get()){
		$tpl->js_error_stop("GET {error} $curl->error",@implode("\n", $curl->errors));
		return;
	}
	
	VERBOSE($curl->data,__LINE__);
	
	$json=json_decode($curl->data);
	
	
	if($json->STATUS=="FAILED"){
		$mem->saveKey("RBLQUERY:$ipaddr", $curl->data,1200);
		$tpl->js_display_results("{success_rbl_artica_cloud}\n$json->ERROR",false,"$ipaddr");
		return;
	}
	
	if($json->STATUS=="OK"){
		$mem->saveKey("RBLQUERY:$ipaddr", $curl->data,1200);
		$tpl->js_display_results("{success_rbl_artica_cloud}",false,"$ipaddr");
	}
	
}

function second_query_start(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$query=$_GET["second-query"];
	// Pentest 2026-11-04 : title etait recopie sans encodage dans la page (XSS).
	$title=req_html(req_html($_GET["title"]));
	$title_encoded=urlencode($_GET["title"]);
	// La clause voyage en base64 DANS une URL: sans urlencode, un « + » du base64 arrive comme
	// une espace et la clause est detruite en silence (visible seulement sur les longues).
	$tpl->js_dialog6($title, "$page?second-query-step1=".urlencode($query)."&title=$title_encoded",1200);
}
function second_query_step1(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$query=$_GET["second-query-step1"];
	$md5=md5($query);
	echo $tpl->_ENGINE_parse_body("
	<H2>".req_html($_GET["title"])."</h2>		
	<div id='$md5'></div><script>LoadAjax('$md5','$page?search=yes&query2=".urlencode($query)."');</script>");
}
function remove_connections_js(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$ip=$_GET["remove-connections"];
	$tpl->js_confirm_execute("{remove_connections_logging_from} ".req_html($ip), "remove-connections", $ip);
}
function remove_connections_save(){
	$sock=new sockets();
	$ip=$_POST["remove-connections"];
	$datas=unserializeb64($sock->GET_INFO("PostfixRemoveConnections"));
	$datas[$ip]=true;
	$newdata=base64_encode(serialize($datas));
	$sock->SaveConfigFile($newdata, "PostfixRemoveConnections");
	$sock->getFrameWork("cmd.php?restart-artica-maillog=yes");
	$q=new postgres_sql();
	$q->QUERY_SQL("DELETE FROM smtplog WHERE ipaddr='127.0.0.1'");
	$q->QUERY_SQL_PARAMS("DELETE FROM smtplog WHERE ipaddr=$1",array($ip));
}

function infected_js(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$virus=$_GET["infected-js"];
	$virus_enc=urlencode($virus);
	// Pentest 2026-11-04 : infected-js etait recopie sans encodage dans la page (XSS).
	$tpl->js_dialog(req_html(req_html($virus)), "$page?infected-popup=$virus_enc");
}
function zoom_ip_js(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$ip=$_GET["zoom-ip-js"];
	$hostname=$_GET["hostname"];
	$tpl->js_dialog("$ip/$hostname", "$page?zoom-ip-popup=$ip&hostname=$hostname");	
}
function zoom_from_js(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$email=$_GET["zoom-from-js"];
	$emailenc=urlencode($email);
	$domainenc=urlencode($_GET["domain"]);
	$tpl->js_dialog(req_html(req_html($email)), "$page?zoom-from-popup=$emailenc&domain=$domainenc");
}

function zoom_from_popup(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$ipClass=new IP();
	$From=$_GET["zoom-from-popup"];
	$Domain=$_GET["domain"];

	$DomainPattern=urlencode("*@$Domain");
	
	$FromEnc=urlencode($From);
	$DomainEc=urlencode($Domain);
	$AFTER=urlencode("BootstrapDialog1.close();Loadjs('$page?zoom-from-js=$FromEnc&domain=$DomainEc')");
	
	
	$search_from="Loadjs('$page?second-query=".pfxtx_sq("frommail",$From)."&title=".urlencode("{see_messages_with}:$From")."')";
	$search_domain="Loadjs('$page?second-query=".pfxtx_sq("fromdomain",$Domain)."&title=".urlencode("{see_messages_with}:$Domain")."')";
	
	// Pentest 2026-11-04 : zoom-from-popup etait recopie sans encodage dans la page (XSS).
	$html[]="<H2>".req_html($From)."</h2>";
	$html[]="<div id='email-progress-zoom'></div>";
	// Mise en page en flex. Le <H2>$From</h2 d'origine n'etait pas ferme et les
	// <center> restaient ouverts d'une cellule a l'autre.
	$html[]="<div style='display:flex;flex-wrap:wrap;gap:10px;margin-top:20px;text-align:center'>";
	$html[]="<div style='flex:1 1 280px;min-width:0;padding:10px'>";
	$html[]="<h2>".htmlspecialchars((string)$From)."</h2>";
	$html[]=$tpl->button_autnonome("{search} {email}", $search_from, "fab fa-searchengin",null,250);
	$html[]="</div>";
	$html[]="<div style='flex:1 1 280px;min-width:0;padding:10px'>";
	$html[]="<h2>".htmlspecialchars((string)$Domain)."</h2>";
	$html[]=$tpl->button_autnonome("{search} {domain}", $search_domain, "fab fa-searchengin",null,250);
	$html[]="</div>";
	$html[]="</div>";
	
	
	
	
	
	
	
	$q=new postgres_sql();
	
	$ligne=$q->mysqli_fetch_array("SELECT id FROM miltergreylist_acls WHERE method='whitelist' and pattern='$From'");
	if(!$q->ok){echo $q->mysql_error;}
	
	if(intval($ligne["id"])>0){
		$whitelist_email=$tpl->button_autnonome("{whitelist} Rule.{$ligne["id"]}", null, "fas fa-thumbs-up",null,250,"btn");
	}else{
		$js="Loadjs('$page?whitelist-from=$FromEnc&after=$AFTER')";
		$whitelist_email=$tpl->button_autnonome("{whitelist} {email}", $js, "fas fa-thumbs-up",null,250);
		
	}
	
	$ligne=$q->mysqli_fetch_array("SELECT id FROM miltergreylist_acls WHERE method='whitelist' and pattern='*@$Domain'");
	if(!$q->ok){echo $q->mysql_error;}
	if(intval($ligne["id"])>0){
		$whitelist_domain=$tpl->button_autnonome("{whitelist} Rule.{$ligne["id"]}", null, "fas fa-thumbs-up",null,250,"btn");
	}else{
		$js="Loadjs('$page?whitelist-from=$DomainPattern&after=$AFTER')";
		$whitelist_domain=$tpl->button_autnonome("{whitelist} {domain}", $js, "fas fa-thumbs-up",null,250);
	
	}	
	
	
	
	$ligne=$q->mysqli_fetch_array("SELECT id FROM miltergreylist_acls WHERE method='blacklist' and pattern='$From'");
	if(!$q->ok){echo $q->mysql_error;}
	
	if(intval($ligne["id"])>0){
		$blacklist_email=$tpl->button_autnonome("{blacklist} Rule.{$ligne["id"]}", null, "fas fa-thumbs-down",null,250,"btn");
	}else{
		$js="Loadjs('$page?blacklist-from=$FromEnc&after=$AFTER')";
		$blacklist_email=$tpl->button_autnonome("{blacklist} {email}", $js, "fas fa-thumbs-down",null,250,"btn-danger");
	
	}
	
	$ligne=$q->mysqli_fetch_array("SELECT id FROM miltergreylist_acls WHERE method='blacklist' and pattern='*@$Domain'");
	if(!$q->ok){echo $q->mysql_error;}
	if(intval($ligne["id"])>0){
		$blacklist_domain=$tpl->button_autnonome("{blacklist} Rule.{$ligne["id"]}", null, "fas fa-thumbs-down",null,250,"btn");
	}else{
		$js="Loadjs('$page?blacklist-from=$DomainPattern&after=$AFTER')";
		$blacklist_domain=$tpl->button_autnonome("{blacklist} {domain}", $js, "fas fa-thumbs-down",null,250,"btn-danger");
	
	}	
	$html[]="<tr>";
	$html[]="<td width=50% style='padding:10px'><center>$whitelist_email</td>";
	$html[]="<td width=50% style='padding:10px'><center>$whitelist_domain</td>";
	$html[]="</tr>";
	
	$html[]="<tr>";
	$html[]="<td width=50% style='padding:10px'><center>$blacklist_email</td>";
	$html[]="<td width=50% style='padding:10px'><center>$blacklist_domain</td>";
	$html[]="</tr>";	
	
	$html[]="</table>";
	
	echo $tpl->_ENGINE_parse_body($html);
	
}

function RBL_API_QUERY($ipaddr){
    $curl=new ccurl("https://rbl.artica.center/api/rest/rbl/query/black/$ipaddr");
    $curl->Timeout=2;
    $curl->NoHTTP_POST=true;
    $mem=new lib_memcached();

    if(!$curl->get()){
        error_log("rbl.artica.center Error $curl->error interface=$curl->interface Proxy=$curl->ArticaProxyServerEnabled",0);
        $array["FOUND"]=false;
        $array["TYPE"]="Error";
        $array["DESC"]="$curl->error";
        $array["QUERY"]=$ipaddr;
        $data=json_encode($array);
        $mem->saveKey("RBLQUERY:$ipaddr", base64_encode($data),300);
        return base64_encode($data);
    }

    $mem->saveKey("RBLQUERY:$ipaddr", base64_encode($curl->data),1200);
    return base64_encode($curl->data);
}


function zoom_ip_popup(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$ipClass=new IP();
	$ip=$_GET["zoom-ip-popup"];
	$hostname=$_GET["hostname"];
	if($hostname=="unknown"){$hostname=null;}
	$q=new postgres_sql();
	$q2=new lib_sqlite("/home/artica/SQLITE/postfix.db");
	if($ipClass->isIPAddress($hostname)){$hostname=null;}
	$FireHolEnable=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("FireHolEnable"));
	$TD_WIDTH="0%";
	$TD_WIDTH1="100%";
    // Pentest 2026-11-04 : ip/hostname (relais SMTP, donnee externe) recopies sans encodage dans le JS (XSS stockee).
    $js5="Loadjs('$page?inform-articatech=".req_url($ip)."');";
    $report_blacklist=$tpl->button_autnonome("{report} {blacklist}", $js5, "fas fa-ban",null,250,"btn-danger");

    $mem=new lib_memcached();

    $results=$mem->getKey("RBLQUERY:$ip");
    if($mem->MemCachedFound){
        VERBOSE("RBLQUERY:$ip --> FOUND [$results]",__LINE__);
        $rblart=$results;
        if(strpos($results,'"')==0) {$rblart = base64_decode($results);}
        $jsonArtica=json_decode($rblart);

    }else{
        VERBOSE("RBLQUERY:$ip --> RBL_API_QUERY",__LINE__);
        $results=RBL_API_QUERY($ip);
        $rblart = base64_decode($results);
        $jsonArtica=json_decode($rblart);

    }

    if($jsonArtica->TYPE=="blacklisted"){
        $date=$tpl->time_to_date($jsonArtica->date);
        $report_blacklist=null;
        $prefix="<div style='float:right'><span class='label label-danger'>Artica: {blacklist} ($date)</span></div>";
    }
    if($jsonArtica->TYPE=="whitelisted"){
        $date=$tpl->time_to_date($jsonArtica->date);
        $report_blacklist=null;
        $prefix="<div style='float:right'><span class='label label-primary'>Artica: {whitelist} ($date)</span></div>";
    }

	
	$js2="Loadjs('$page?whitelist-hostname=".req_url($ip)."')";
	$whitelist_ipaddr=$tpl->button_autnonome("{whitelist} {ipaddr}", $js2, "fas fa-thumbs-up",null,250);
	$ligne2=$q2->mysqli_fetch_array("SELECT ID FROM smtpd_milter_maps WHERE enabled=1 AND pattern='$ip'");
	if($ligne2["ID"]>0){
		$whitelist_ipaddr=$tpl->button_autnonome("{ipaddr} Rule.{$ligne2["ID"]}", $js2, "fas fa-thumbs-up",null,250,"btn");
	}
	
	
	$ligne=pg_fetch_array($q->QUERY_SQL("SELECT id FROM miltergreylist_acls WHERE pattern='$ip'"));
	$lastidip=intval($ligne["id"]);
	
	
	// Firewall membership now comes from the daemon's enabled-set (loaded once), not a direct PG read.
	$FIREWALL=false;
	$fwset=pfxtx_fwset();
	if(isset($fwset[$ip])){$FIREWALL=True;}



	
	
	if($hostname<>null){
		$TD_WIDTH="50%";
		$TD_WIDTH1="50%";
		$js2="Loadjs('$page?second-query=".pfxtx_sq("relay_s",$hostname)."&title=".urlencode("{see_messages_with}:$hostname")."')";
		$search_hostname=$tpl->button_autnonome("{search}", $js2, "fab fa-searchengin",null,250);
		$dom=new squid_familysite();
		
		$js2="Loadjs('$page?whitelist-hostname=".req_url($hostname)."')";
		$whitelist_hostname=$tpl->button_autnonome("{whitelist} {hostname}", $js2, "fas fa-thumbs-up",null,250);
		
		$ligne2=$q2->mysqli_fetch_array("SELECT ID FROM smtpd_milter_maps WHERE enabled=1 AND pattern='$hostname'");
		if($ligne2["ID"]>0){
			$whitelist_hostname=$tpl->button_autnonome("{hostname} Rule.{$ligne2["ID"]}", $js2, "fas fa-thumbs-up",null,250,"btn");
		}
		
		$domain=$dom->GetFamilySites($hostname);
		$js2="Loadjs('$page?whitelist-hostname=".req_url($domain)."')";
		$whitelist_domain=$tpl->button_autnonome("{whitelist} {domain}", $js2, "fas fa-thumbs-up",null,250);
		$ligne2=$q2->mysqli_fetch_array("SELECT ID FROM smtpd_milter_maps WHERE enabled=1 AND pattern='$domain'");
		if($ligne2["ID"]>0){
			$whitelist_domain=$tpl->button_autnonome("{domain} Rule.{$ligne2["ID"]}", $js2, "fas fa-thumbs-up",null,250,"btn");
		}
		
		
		
	}
	
	$T_TTILE[]=$ip;
	if($hostname<>null){$T_TTILE[]=$hostname;}
	
	// Pentest 2026-11-04 : ip/hostname (relais SMTP) (donnee externe) affiche sans encodage (XSS stockee).
	$html[]="<H2>".@implode("&nbsp;|&nbsp;", array_map("req_html",$T_TTILE))."$prefix</h2>";
	$html[]="<div id='ip-progress-zoom'></div>";
	// Mise en page en flex, meme correction: <H2>$ip</h2 n'etait pas ferme.
	$html[]="<div style='display:flex;flex-wrap:wrap;gap:10px;margin-top:20px;text-align:center'>";
	$html[]="<div style='flex:1 1 280px;min-width:0;padding:10px'><h2>".htmlspecialchars((string)$ip)."</h2></div>";
	$html[]="<div style='flex:1 1 280px;min-width:0;padding:10px'><h2>".htmlspecialchars((string)$hostname)."</h2></div>";
	$html[]="</div>";
	$html[]="<div style='display:flex;flex-wrap:wrap;gap:10px;text-align:center'>";
	$js1="Loadjs('$page?second-query=".pfxtx_sq("ipaddr",$ip)."&title=".urlencode("{see_messages_with}:$ip")."')";
	
	$js3="Loadjs('$page?blacklist-ip=".req_url($ip)."&hostname=".req_url($hostname)."')";
	$js4="Loadjs('$page?remove-connections=".req_url($ip)."');";

	$html[]="<div style='flex:1 1 280px;min-width:0;padding:10px'>".$tpl->button_autnonome("{search} $ip", $js1, "fab fa-searchengin",null,250)."</div>";
	$html[]="<div style='flex:1 1 280px;min-width:0;padding:10px'>$search_hostname</div>";
	if($lastidip==0){
		$html[]="<div style='flex:1 1 280px;min-width:0;padding:10px'>".$tpl->button_autnonome("{blacklist} $ip", $js3, "fas fa-ban",null,250,"btn-danger")."</div>";
	}else{
		$js3="Loadjs('fw.postfix.blackwite.php?record-js=$lastidip');";
		$html[]="<div style='flex:1 1 280px;min-width:0;padding:10px'>".$tpl->button_autnonome("{blacklisted} {rule} $lastidip", $js3, "fas fa-ban",null,250,"btn-info")."</div>";
	}
	$html[]="<div style='flex:1 1 280px;min-width:0;padding:10px'>$whitelist_hostname</div>";
	
	//-----------------------------------------------------------------------------------------------------
	$html[]="</tr>";
	$html[]="<tr>";
	
	if($FireHolEnable==1){
		if(!$FIREWALL){
			$html[]="<td width=50% style='padding:10px'><center>".$tpl->button_autnonome("{APP_FIREWALL} $ip", "Loadjs('$page?firewall=".req_url($ip)."');", "fas fa-ban",null,250,"btn-danger")."</td>";
		}else{
			$html[]="<td width=50% style='padding:10px'><center>".$tpl->button_autnonome("{APP_FIREWALL} {blocked}", null, "fas fa-ban",null,250,"btn")."</td>";
		}
		
	}else{
		$html[]="<td width=50% style='padding:10px'><center>".$tpl->button_autnonome("{APP_FIREWALL} {disabled}", null, "fas fa-ban",null,250,"btn")."</td>";
	}
	
	$html[]="<td width=50% style='padding:10px'><center>&nbsp;</center></td>";
	
	
	$html[]="</tr>";
	$html[]="<tr>";
	$html[]="<td width=50% style='padding:10px'><center>$report_blacklist</td>";
	$html[]="<td width=50% style='padding:10px'><center>$whitelist_domain</center></td>";
	$html[]="</tr>";
	
	$html[]="</tr>";
	$html[]="<tr>";
	$html[]="<td width=50% style='padding:10px'><center>$whitelist_ipaddr</td>";
	$html[]="<td width=50% style='padding:10px'><center>&nbsp;</center></td>";
	$html[]="</tr>";
	
	
	
	
	$html[]="<tr>";
	$html[]="<td width=50% style='padding:10px'><center>".$tpl->button_autnonome("{remove_connections}", $js4, "fas fa-ban",null,250,"btn-warning")."</td>";
	$html[]="<td width=50% style='padding:10px'></td>";
	$html[]="</tr>";
	$html[]="</table>";
	
	echo $tpl->_ENGINE_parse_body($html);
}

function blacklist_ip(){
	$q=new postgres_sql();
	$sql="SELECT id FROM miltergreylist_acls ORDER BY id desc LIMIT 1";
	$ligne=$q->mysqli_fetch_array($sql);
	$lastid=$ligne["id"];
	$lastid++;
	$instance="master";
	$date=date("Y-m-d H:i:s");
	$ip=$_GET["blacklist-ip"];
	$uid=$_SESSION["uid"];
	if($uid==-100){$uid="Manager";}
	$hostname=$_GET["hostname"];
	
	// Pentest 2026-11-04 : blacklist-ip/hostname/uid etaient concatenes dans la requete.
	$lastid=intval($lastid);
	$sql="INSERT INTO miltergreylist_acls (id,zdate,instance,method,type,pattern,description)
	VALUES($lastid,'$date','$instance','blacklist','addr',$1,$2)";
	$q->QUERY_SQL_PARAMS($sql,array($ip,"by $uid ($hostname)"));
	if(!$q->ok){echo $q->mysql_error;return;}
	$ARRAY["PROGRESS_FILE"]="/usr/share/artica-postfix/ressources/logs/smtpd_client_restrictions_progress";
	$ARRAY["LOG_FILE"]="/usr/share/artica-postfix/ressources/logs/smtpd_client_restrictions_progress.log";
    $ARRAY["CMD"]="/postfix/smtpd/restrictions/0";
	$ARRAY["TITLE"]="{smtpd_client_restrictions}";
	$prgress=base64_encode(serialize($ARRAY));
	$jsApply="Loadjs('fw.progress.php?".fw_progress_args_b64($prgress)."&mainid=ip-progress-zoom')";
	echo "$jsApply\n";
}


function infected_whitelist(){
	$tpl=new template_admin();
	$uid=$_SESSION["uid"];
	if($uid==-100){$uid="Manager";}
	$VirusName=$_GET["infected-white"];
	$json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API_POST("/clamd/whitelist",array("VirusName"=>$VirusName,"uid"=>$uid)),true);
	if(is_array($json) && isset($json["Error"]) && $json["Error"]<>""){$tpl->js_mysql_alert($json["Error"]);return;}

    $jsrestart=$tpl->framework_buildjs(
        "/clamd/reconfigure",
        "clamd.progress",
        "clamd.progress.logs","transcat-progress-vir");

	echo "$jsrestart\n";
}

function infected_popup(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$virus=$_GET["infected-popup"];
	$virus_enc=urlencode($virus);
	$virusq=$virus;
	$wjson=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/clamd/whitelist"),true);
	$wrows=(is_array($wjson) && isset($wjson["data"]) && is_array($wjson["data"]))?$wjson["data"]:array();
	$IDVir=0;
	if(strpos($virusq, "}")>0){
		$virusq=str_replace("{HEX}", "", $virusq);
		foreach($wrows as $wr){ if($virusq!="" && substr($wr["VirusName"],-strlen($virusq))===$virusq){$IDVir=intval($wr["ID"]);break;} }
	}else{
		foreach($wrows as $wr){ if($wr["VirusName"]===$virus){$IDVir=intval($wr["ID"]);break;} }
	}
	
	// Pentest 2026-11-04 : infected-popup etait recopie sans encodage dans la page (XSS).
	$html[]="<h2>".req_html($virus)." $IDVir</H2>";
	$html[]="<div id='transcat-progress-vir'></div>";
	$js1="Loadjs('$page?second-query=".pfxtx_sq("reason",$virus)."&title=$virus_enc')";
	$js2="Loadjs('$page?infected-white=$virus_enc');";
	$html[]="<div style='display:flex;flex-wrap:wrap;gap:10px;margin-top:20px;text-align:center'>";
	$html[]="<div style='flex:1 1 280px;min-width:0'>".$tpl->button_autnonome("{see_messages_with}", $js1, "fab fa-searchengin",null,0)."</div>";
	if($IDVir==0){
		$html[]="<div style='flex:1 1 280px;min-width:0'>".$tpl->button_autnonome("{exclude_virus}", $js2, "fas fa-thumbs-up",null,0)."</div>";
	}
	$html[]="</div>";
	
	echo $tpl->_ENGINE_parse_body($html);
}


function table(){
	$viarbl=null;
	$page=CurrentPageName();
	$tpl=new template_admin();
	$t=time();
	$urltoadd=null;
	$html[]="

	</div>
	<div class='ibox-content'>
	<div id='postfix-transactions'></div>

	</div>
	</div>
	";
	
	$script="<script>
	$.address.state('/');
	$.address.value('postfix-transactions');
	$.address.title('Artica: SMTP refused messages');
</script>";
	
	
if(isset($_GET["refused"])){
	$urltoadd="&refused=yes";
	$script="<script>
	$.address.state('/');
	$.address.value('postfix-refused');
	$.address.title('Artica: SMTP refused messages');
</script>";
}

	$html[]=$script;
	$html[]=$tpl->search_block($page,"postgres","smtplog","milter-greylits-database",$urltoadd);
	
	if(isset($_GET["main-page"])){
		$tpl=new template_admin(null,$html);
		echo $tpl->build_firewall();
		return;
	}

	echo $tpl->_ENGINE_parse_body($html);

}

// Pentest 2026-11-04 : search (recherche saisie) etait concatene dans la requete.
function IntelligentSearch($search_local){
	$fam=new squid_familysite();
	$ip=new IP();

    if(preg_match("#[a-z\*\.]+#i",$search_local,$re)){
        if(strpos("    $search_local","*")==0){$search_local="*$search_local*";}
        $search_local=str_replace("**","*",$search_local);
        VERBOSE("OK FOUND alpha with stars",__LINE__);
        $search_local=str_replace("*", ".*?", $search_local);
        $esc=pg_escape_string2($search_local);
        $querys="WHERE (frommail ~'$esc') OR (tomail ~'$esc') OR (relay_s~'$esc')";
        return $querys;
    }
	
	if(preg_match("#max(\s+|=)([0-9]+)#i",$search_local,$re)){
		VERBOSE("OK FOUND #max(\s+|=)([0-9]+)#i in $search_local",__LINE__);
		$search=str_ireplace("max{$re[1]}{$re[2]}", "", $search_local);
		$MAX=$re[2];
	}
	$search_local=trim($search_local);
	
	if(preg_match("#^\*@(.+)#", $search_local,$re)){
		$esc1=pg_escape_string2($re[1]);
		$querys="WHERE (fromdomain='$esc1') OR (todomain='$esc1')";
		if(strpos("    ".$re[1], "*")>0){
			$search_local=str_replace("*", ".*?", $re[1]);
			$esc=pg_escape_string2($search_local);
			$querys="WHERE (fromdomain ~'$esc') OR (todomain ~'$esc')";
		}

		return $querys;
	}
	if(preg_match("#^@(.+)#", $search_local,$re)){
		$esc1=pg_escape_string2($re[1]);
		$querys="WHERE (fromdomain='$esc1') OR (todomain='$esc1')";
		if(strpos("    ".$re[1], "*")>0){
			$search_local=str_replace("*", ".*?", $re[1]);
			$esc=pg_escape_string2($search_local);
			$querys="WHERE (fromdomain ~'$esc') OR (todomain ~'$esc')";
		}
	
		return $querys;
	}	
	
	if(preg_match("#^(.+?)@(.+)#", $search_local,$re)){
		$esc=pg_escape_string2($search_local);
		$querys="WHERE (frommail='$esc') OR (tomail='$esc')";
		if(strpos("    ".$search_local, "*")>0){
			$search_local=str_replace("*", ".*?", $re[1]);
			$esc=pg_escape_string2($search_local);
			$querys="WHERE (frommail ~'$esc') OR (tomail ~'$esc')";
		}
		
		return $querys;
	}
	
	if($ip->IsACDIR($search_local)){
		$esc=pg_escape_string2($search_local);
		return "WHERE ipaddr <<'$esc'";
	
	}
	
	if($ip->isIPAddress($search_local)){
		$esc=pg_escape_string2($search_local);
		return "WHERE ipaddr ='$esc'";
	}
	 


	if(preg_match("#^([a-z0-9\.\-]+)$#i", $search_local,$re)){
			if(strpos("    ".$re[1], "*")>0){
				$re[1]=str_replace("*", ".*?", $re[1]);
			}else{
				$re[1]=".*?{$re[1]}.*?";
			}
			$esc1=pg_escape_string2($re[1]);
			$querys="WHERE (frommail ~'$esc1') OR (tomail ~'$esc1') OR (fromdomain ~'$esc1') OR (todomain~'$esc1') OR (relay_s~'$esc1')";
		
	
		return $querys;
	}

	
}

function search()
{
    $tpl = new template_admin();
    $page = CurrentPageName();
    $sock = new sockets();

    $q=new postgres_sql();
    $q->SMTP_TABLES();

    if(!$q->TABLE_EXISTS("smtplog_query")){
        echo $tpl->FATAL_ERROR_SHOW_128("smtplog_query no such table!");
        return;
    }

    $t = time();
    $table = "smtplog";
    $tomail_column = true;
    $reason_column = true;
    $curl = new ccurl();
    $mem = new lib_memcached();
    $IntelligentSearch = null;
    $search = trim($_GET["search"]);
    $IntelligentSearch = IntelligentSearch($search);
    $FireHolEnable = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("FireHolEnable"));
    $ipclass = new IP();
    $fields="id,zdate,
		fromdomain ,
		relay_s ,
		relay_r ,
		todomain ,
		frommail ,
		tomail ,
		size ,
		aclid ,
		smtp_code,
		ipaddr::inet,
		refused,
		rbl,
		filtered,
		spamscore,
		sent,
		subject ,
		msgid ,
		rblart,
		whitelisted,
		disclaimer,
		infected,
		maintenance,
		reason";
    $MAX = 150;
    if ($IntelligentSearch <> null) {
        $querys["Q"] = $IntelligentSearch;
    }


    /*$html[]=$tpl->_ENGINE_parse_body("
            <div class=\"btn-group\" data-toggle=\"buttons\">
            <label class=\"btn btn btn-info\" OnClick=\"javascript:$jsApply\">
            <i class='fa fa-save'></i> {analyze_database} </label>
            </div>");
    */

    if (!isset($querys["Q"])) {
        $querys = $tpl->query_pattern($search);
        $MAX = $querys["MAX"];
        if ($MAX == 0) {
            $MAX = 150;
        }
    }

    if (isset($_GET["refused"])) {
        if ($querys["Q"] <> null) {
            $querys["Q"] = $querys["Q"] . " AND ( (refused=1) OR (infected=1) )";
        } else {
            $querys["Q"] = "WHERE ( (refused=1) OR (infected=1) )";
        }
    }

    $sql = "SELECT $fields FROM $table {$querys["Q"]} ORDER BY id DESC LIMIT $MAX";

    if (isset($_GET["query2"])) {
        // Pentest 2026-11-04 : query2 (clause SQL venue de l'URL) etait concatene dans la requete.
        $query = signed_html_decode($_GET["query2"]);
        if (!preg_match("#^WHERE\s#", $query)) {
            echo $tpl->FATAL_ERROR_SHOW_128("{error}: invalid query");
            return;
        }
        if (preg_match("#tomail=#", $query)) {
            $tomail_column = false;
        }
        if (preg_match("#reason=#", $query)) {
            $reason_column = false;
        }
        $sql = "SELECT $fields  FROM $table $query ORDER BY id DESC LIMIT 250";
    }

    //zmd5,ip_addr,mailfrom,mailto,stime,hostname,whitelisted

    $sqlencoded = base64_encode($sql);
    $tfile = md5($sqlencoded);
    $ARRAY["PROGRESS_FILE"] = "/usr/share/artica-postfix/ressources/logs/web/smtp.transactions.$tfile.progress";
    $ARRAY["LOG_FILE"] = "/usr/share/artica-postfix/ressources/logs/web/smtp.transactions.$tfile.log";
    $ARRAY["CMD"] = "postfix2.php?smtp-transactions=" . urlencode($sqlencoded) . "&tfile=$tfile";
    $ARRAY["TITLE"] = "{building}";
    $ARRAY["AFTER"] = "LoadAjax('extracted-$t','$page?extract=$tfile');";
    $prgress = base64_encode(serialize($ARRAY));
    $jsrestart = "Loadjs('fw.progress.php?".fw_progress_args_b64($prgress)."&mainid=progress-$t')";

    $html[] = "<div id='extracted-$t' style='width: 100%'>";
    $html[] = "<div id='progress-$t' style='width: 100%'></div>";
    $html[] = "<script>";
    $html[] = "$jsrestart";
    $html[] = "</script>";
    $html[] = "</div>";

    echo $tpl->_ENGINE_parse_body($html);

}
function extract_query(){
    $tpl = new template_admin();
    $page = CurrentPageName();
    $t=time();
    $tomail_column = true;
    $reason_column = true;
    $curl = new ccurl();
    $mem = new lib_memcached();
    $q = new postgres_sql();
	$results = $q->QUERY_SQL("SELECT * FROM smtplog_query");
	if(!$q->ok){echo $tpl->FATAL_ERROR_SHOW_128("LINE ".__LINE__." <br>$q->mysql_error");return;}

	if(pg_num_rows($results)==0){
		
		echo $tpl->FATAL_ERROR_SHOW_128("{no_data}");
		return;
	}

	$html[]="<div class='awc-wrap'><div class='awc-card'><div class='awc-scroll'>";
	$html[]="<table class='awc-tbl'><thead><tr>";
	$html[]="<th>{sender}</th>";
	$html[]="<th class='center'>{status}</th>";
	if($reason_column){$html[]="<th>{reason}</th>";}
	$html[]="<th class='center'>Artica RBL</th>";
	$html[]="<th>{time}</th>";
	$html[]="</tr></thead><tbody id='pfxtx-rows'>";
    $ipclass=new IP();
    $FireHolEnable=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("FireHolEnable"));

	while ($ligne = pg_fetch_assoc($results)) {
		$TRCLASS=null;
		$INFOS=array();
		$smtp_code_text=null;
		$md=md5(serialize($ligne));
		$color="#000000";
		$status=null;
		$zdate=$tpl->time_to_date(strtotime($ligne["zdate"]),true);
		$hostname=$ligne["relay_s"];
		$ipaddr=$ligne["ipaddr"];
		$mailfrom=$ligne["frommail"];
		$mailto=trim($ligne["tomail"]);
		$refused=$ligne["refused"];
		$infected=intval($ligne["infected"]);
		$spamscore=$ligne["spamscore"];
		$subject=$ligne["subject"];
		$msgid=$ligne["msgid"];
		$sent=intval($ligne["sent"]);
		$reason=$ligne["reason"];
		$class_text="text-muted";
		$smtp_code=intval($ligne["smtp_code"]);
		if($reason=="Relay access denied"){$refused=1;}
		$spamreport=$ligne["spamreport"];
		$spamscore=$ligne["spamscore"];
		$fromdomain=$ligne["fromdomain"];
		$aclid=intval($ligne["aclid"]);
		$smtp_code_text=null;
		$aclid_text=null;
		
		$ArticaRBL=ArticaRBL($ligne);
		$hostname=str_replace("[", "", $hostname);
		$hostname=str_replace("]", "", $hostname);
		
		if($ipaddr=="0.0.0.0"){
			$ipaddr=$tpl->icon_nothing();
		}else{
			// Pentest 2026-11-04 : relay_s/ipaddr (donnee externe) recopies sans encodage dans le JS (XSS stockee).
			$js="Loadjs('$page?zoom-ip-js=".req_url($ipaddr)."&hostname=".req_url($hostname)."')";
			$ipaddr=$tpl->td_href($ipaddr,$hostname,$js);
		}
		if($msgid==null){$msgid=$tpl->icon_nothing();}else{
			$js_msgid="Loadjs('$page?second-query=".pfxtx_sq("msgid",$msgid)."&title=".urlencode("{see_messages_with}:$msgid")."')";
			$msgid=$tpl->td_href($msgid,"{see_messages_with}:$msgid",$js_msgid);
		}
		if($smtp_code==2){$smtp_code_text="Filter";}
		
		
		$ParseReason=ParseReason($ligne);
		$class_text=$ParseReason[0];
		$status=$ParseReason[1];
		$reason=$ParseReason[2];

		// Pentest 2026-11-04 : subject/relay_s (donnee externe) affiches sans encodage (XSS stockee).
		if($subject<>null){$subject="<br><small>".req_html($subject)."</small>";}
		if($hostname<>null){$hostname="<br><small>".req_html($hostname)."</small>";}
		
		$mailto_text=$mailto;
		$strlen_mailto=strlen($mailto_text);
		$maifrom_text=$mailfrom;
		$mailfromenc=urlencode($mailfrom);
		$fromdomainenc=urlencode($fromdomain);
		
		$strlen_mailfrom=strlen($maifrom_text);


        if($strlen_mailfrom>40){$maifrom_text="...@$fromdomain";}
		
		
		if(isset($_GET["query2"])){
			if($strlen_mailfrom>26){$maifrom_text=substr($maifrom_text, 0,26)."...";}
			if($strlen_mailto>26){$mailto_text=substr($mailto_text, 0,26)."...";}
			$zdate=date("m/d H:i:s",strtotime($ligne["zdate"]));
			
		}
		if($mailfrom<>null){
			$mailfrom=$tpl->td_href($maifrom_text,$mailfrom,
                "Loadjs('$page?zoom-from-js=$mailfromenc&domain=$fromdomainenc')");
		}



		if($mailto<>null){
			$js2="Loadjs('$page?second-query=".pfxtx_sq("tomail",$mailto)."&title=".urlencode("{see_messages_with}:$mailto")."')";
			$mailto=$tpl->td_href($mailto_text,$mailto,$js2);}
		if($mailfrom==null){$mailfrom=$tpl->icon_nothing();}
		if($mailto==null){$mailto=$tpl->icon_nothing();}
		

		
		if($smtp_code_text==null){$smtp_code_text=isFireWalled($ligne,$ipclass,$FireHolEnable);}
		
		if($aclid>0){
			$aclid_js="Loadjs('fw.postfix.blackwite.php?record-js=$aclid&method=blacklist');";
			$aclid_text="&nbsp;".$tpl->td_href("<small>(ACL:$aclid)</small>",null,$aclid_js);
		}
		
		$html[]=pfxtx_row($md,$mailfrom,$subject,$mailto,$ipaddr,$hostname,$msgid,
			$status,$reason.$aclid_text,$ArticaRBL,$smtp_code_text,$zdate,
			$reason_column,$tomail_column,$class_text);
	}

	$html[]="</tbody></table></div>";
	$html[]="<div class='awc-foot'>";
	$html[]="<span class='awc-pg' id='pfxtx-pager'></span>";
	$html[]="</div></div></div>";
	// La requete SQL etait affichee sous le tableau (<small>$sql</small>): reste de
	// mise au point qui exposait le schema et le filtre en clair.
	$html[]="
	<script>
	NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS)."
	</script>";

	echo awc_table_style()
		.$tpl->_ENGINE_parse_body($html)
		.awc_pager_js(100,"pfxtx-rows","pfxtx-pager");

}

function ArticaRBL($ligne){
	$ipaddr=$ligne["ipaddr"];
	if(isset($GLOBALS["ArticaRBL:$ipaddr"])){
		if($GLOBALS["ArticaRBL:$ipaddr"]<>null){
			VERBOSE("return GLOBALS['ArticaRBL:$ipaddr']",__LINE__);
			return $GLOBALS["ArticaRBL:$ipaddr"];}
	}
	$tpl=new template_admin();
	$Artica_RBL["blacklisted"]="<span class='label label-danger'>{blacklist}</span>";
	$Artica_RBL["whitelisted"]="<span class='label label-primary'>{whitelist}</span>";
	$Artica_RBL["Error"]="<span class='label label'>{error}!</span>";
	$Artica_RBL["unknown"]="<span class='label label'>{unknown}</span>";
	$Artica_RBL["NONE"]=$tpl->icon_nothing();
	$Artica_RBL[null]=$tpl->icon_nothing();
	$mem=new lib_memcached();

    $results=$mem->getKey("RBLQUERY:$ipaddr");

    if($mem->MemCachedFound) {
        if (strpos($results, '"') == 0) {
            $ligne["rblart"] = $results;
        }else{
            $ligne["rblart"]=base64_encode($results);
        }
    }




	VERBOSE("$ipaddr: rblart:".base64_decode($ligne["rblart"]), __LINE__);
	
	if(strlen($ligne["rblart"])>50){
		$rblart=base64_decode($ligne["rblart"]);
		VERBOSE("$ipaddr: rblart:$rblart", __LINE__);
		$jsonArtica=json_decode($rblart);
		VERBOSE("TYPE = ".$jsonArtica->TYPE, __LINE__);
		$GLOBALS["ArticaRBL:$ipaddr"]=$Artica_RBL[$jsonArtica->TYPE];
		return $GLOBALS["ArticaRBL:$ipaddr"];
	}
	
	

	
	if($ligne["rblart"]==null){return $tpl->icon_nothing();}
	$jsonArtica=json_decode(base64_decode($ligne["rblart"]));
	$GLOBALS["ArticaRBL:$ipaddr"]=$Artica_RBL[$jsonArtica->TYPE];
	return $GLOBALS["ArticaRBL:$ipaddr"];
}

// pfxtx_fwset loads the enabled SMTPDeny patterns from the daemon ONCE per request into a lookup map.
// The transactions table calls isFireWalled() per row; a socket round-trip per row would be far too
// slow, so the whole enabled set is fetched a single time and membership checked locally — an exact
// match, faithful to the previous `WHERE pattern='ip'` behaviour.
function pfxtx_fwset(){
	if(isset($GLOBALS["FW_SET"])){return $GLOBALS["FW_SET"];}
	$GLOBALS["FW_SET"]=array();
	$resp=$GLOBALS["CLASS_SOCKETS"]->REST_API_POSTFIX("/postfix/ipset/list?limit=100000");
	$j=json_decode((string)$resp,true);
	if(is_array($j) && !empty($j["success"]) && isset($j["entries"]) && is_array($j["entries"])){
		foreach($j["entries"] as $e){
			if(intval($e["enabled"] ?? 0)==1){$GLOBALS["FW_SET"][(string)($e["pattern"] ?? "")]=1;}
		}
	}
	return $GLOBALS["FW_SET"];
}

function isFireWalled($ligne,$ipclass,$FireHolEnable){
	$tpl=new template_admin();
	$ip=(string)$ligne["ipaddr"];
	if(isset($GLOBALS["isFireWalled"][$ip])){return $GLOBALS["isFireWalled"][$ip];}
	if(!$ipclass->isValid($ip)){return;}
	if($FireHolEnable==0){
		$GLOBALS["isFireWalled"][$ip]=$tpl->icon_nothing();
		return $GLOBALS["isFireWalled"][$ip];
	}
	$set=pfxtx_fwset();
	if(isset($set[$ip])){
		$GLOBALS["isFireWalled"][$ip]="<center><i class='fas fa-ban'></i></center>";
	}else{
		$GLOBALS["isFireWalled"][$ip]=$tpl->icon_nothing();
	}
	return $GLOBALS["isFireWalled"][$ip];
}

function ParseReason($ligne){
	$tpl=new template_admin();
	$page=CurrentPageName();
	$infected=$ligne["infected"];
	$reason=$ligne["reason"];
	$refused=$ligne["refused"];
	$smtp_code=$ligne["smtp_code"];
	$spamreport=$ligne["spamreport"];
	$spamscore=$ligne["spamscore"];
	$id=$ligne["id"];
	$XSpamStatusHeaderScore=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("XSpamStatusHeaderScore"));
	if($XSpamStatusHeaderScore==0){$XSpamStatusHeaderScore=4;}
	$SpamAssassinRequiredScore=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SpamAssassinRequiredScore"));
	if($SpamAssassinRequiredScore==0){$SpamAssassinRequiredScore=8;}
	$block_with_required_score=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SpamAssBlockWithRequiredScore"));
	if($block_with_required_score==0){$block_with_required_score=15;}
	
	$status=null;
	
	VERBOSE("$reason, refused=$refused", __LINE__);

	if(strtolower($reason)=="sent"){
	    $status="<span class='label label-success'>{sent}</span>";
        $js1="Loadjs('$page?second-query=".pfxtx_sq("reason",$reason)."&title=".urlencode("{see_messages_with}:$reason")."')";
        $reason=$tpl->td_href("{sent}","{see_messages_with}:$reason",$js1);
        return array(null,$status,$reason);

    }
	
	if($smtp_code==2){
		$status="<span class='label label-primary'>FILTER</span>";
		if($refused==1){
			$status="<span class='label label-danger'>FILTER</span>";
		}
	}
	if($smtp_code==55){
		$status="<span class='label label-primary'>ADMIN</span>";
		if($refused==1){
			$status="<span class='label label-danger'>ADMIN</span>";
		}
	}
	
	
	
	if($spamscore>$XSpamStatusHeaderScore){
		$class_text="text-warning";
		$status="<span class='label label-warning'>SPAM</span>";
		if($spamscore>$SpamAssassinRequiredScore){
			$class_text="text-danger";
			$status="<span class='label label-danger'>SPAM</span>";
		}	
		if($spamscore>$block_with_required_score){
			$class_text="text-danger";
			$status="<span class='label label-danger'>SPAM</span>";
		}

	
		$reason=$reason." - Score $spamscore";
		if(strlen($spamreport)>10){
			$js1="Loadjs('$page?spamreport-js=$id&title=".urlencode("$reason")."')";
			$reason=$tpl->td_href($reason,"{view_report}",$js1);
		}
		return array($class_text,$status,$reason);
	}
	
	if($infected==1){
		$class_text="text-danger";
		$INFOVIR="Virus";
		if(preg_match("#\.Spam#", $reason)){
			$status="<span class='label label-danger'>SPAM</span>";
			$INFOVIR="Spam";
		}else{
			$status="<span class='label label-danger'>Virus</span>";
			$INFOS[]=$INFOVIR;
		}
			
		if($reason<>"Content Filter"){
			$reason=$tpl->td_href($reason,null,"Loadjs('$page?infected-js=".urlencode($reason)."')");
		}
		
		
		return array($class_text,$status,$reason);
			
	}
	
	if(preg_match("#Message backuped#i", $reason)){
		$status="<span class='label label-primary'>BACK</span>";
		$js1="Loadjs('$page?second-query=".signed_html_token("WHERE reason='Message backuped'")."&title=".urlencode("{see_messages_with}:Message backuped")."')";
		$reason=$tpl->td_href($reason,"{see_messages_with}:$reason",$js1);
		return array(null,$status,$reason);
	}
	
	if(preg_match("#(Transaction accepted|Whitelisted|Connection accepted|Transaction Connected|Connection start|Sender)#",$reason)){
		$status="<span class='label label-primary'>SMTP</span>";
		$js1="Loadjs('$page?second-query=".pfxtx_sq("reason",$reason)."&title=".urlencode("{see_messages_with}:$reason")."')";
		$reason=$tpl->td_href($reason,"{see_messages_with}:$reason",$js1);
		return array(null,$status,$reason);
	}
	if(preg_match("#(queued as|removed|delivered to mailbox)#",$reason)){
		$status="<span class='label label-success'>&nbsp;END&nbsp;</span>";
		
		if(preg_match("#queued as#", $reason)){
			$js1="Loadjs('$page?second-query=".signed_html_token("WHERE reason LIKE 'queued as%'")."&title=".urlencode("{see_messages_with}:$reason")."')";
			$reason=$tpl->td_href($reason,"{see_messages_with}:$reason",$js1);
			return array(null,$status,$reason);
		}
		$js1="Loadjs('$page?second-query=".signed_html_token("WHERE reason = 'queued as%'")."&title=".urlencode("{see_messages_with}:$reason")."')";
		$reason=$tpl->td_href($reason,"{see_messages_with}:$reason",$js1);
		return array(null,$status,$reason);
	}
	
	
	if(preg_match("#^queue\s+(.+)#",$reason)){
		$status="<span class='label label-primary'>QUEUE</span>";
		$js1="Loadjs('$page?second-query=".pfxtx_sq("reason",$reason)."&title=".urlencode("{see_messages_with}:$reason")."')";
		$reason=$tpl->td_href($reason,"{see_messages_with}:$reason",$js1);
		return array(null,$status,$reason);
	}
	
	if(preg_match("#non-delivery#", $reason)){
		$status="<span class='label label-warning'>NOTIF</span>";
		$js1="Loadjs('$page?second-query=".pfxtx_sq("reason",$reason)."&title=".urlencode("{see_messages_with}:$reason")."')";
		$reason=$tpl->td_href($reason,"{see_messages_with}:$reason",$js1);
		return array("text-warning",$status,$reason);
		
		
	}
	if(preg_match("#(Relay access denied|Reverse not found|Sender rejected)#i", $reason)){
		$status="<span class='label label-danger'>{rejected}</span>";
		$js1="Loadjs('$page?second-query=".pfxtx_sq("reason",$reason)."&title=".urlencode("{see_messages_with}:$reason")."')";
		$reason=$tpl->td_href($reason,"{see_messages_with}:$reason",$js1);
		return array("text-danger",$status,$reason);
	}	
	
	if($refused==1){
		VERBOSE("REFUSED", __LINE__);
		$INFOS[]="{rejected}";
		$status="<span class='label label-danger'>".@implode("/", $INFOS)."</span>";
		$class_text="text-danger";
			
		if(preg_match("#Rbl:(.+)#i", $reason,$re)){
			VERBOSE("RBL FOUND {$re[1]}", __LINE__);
			$js1="Loadjs('$page?second-query=".pfxtx_sq("reason",$reason)."&title=".urlencode("{see_messages_with}:{$re[1]}")."')";
			$status="<span class='label label-danger'>RBL</span>";
			$reason=$tpl->td_href($re[1],"{see_messages_with}:$reason",$js1);
			return array("text-danger",$status,$reason);
		}
		
		VERBOSE("REFUSED -> CONTINUE", __LINE__);
			
	}
	if(preg_match("#Rbl:(.+)#i", $reason,$re)){
		$js1="Loadjs('$page?second-query=".pfxtx_sq("reason",$reason)."&title=".urlencode("{see_messages_with}:{$re[1]}")."')";
		$status="<span class='label label-danger'>RBL</span>";
		$reason=$tpl->td_href($re[1],"{see_messages_with}:$reason",$js1);
		return array("text-danger",$status,$reason);
	}
	
	VERBOSE("FINALY: $reason..",__LINE__);
	$js1="Loadjs('$page?second-query=".pfxtx_sq("reason",$reason)."&title=".urlencode("{see_messages_with}:{$reason}")."')";
	$reason=$tpl->td_href($reason,"{see_messages_with}:$reason",$js1);
	return array($class_text,$status,$reason);
	
	
}

/**
 * Une transaction SMTP: expediteur en identite, sujet / destinataire / IP+hote /
 * identifiant de message en sous-titre. Neuf colonnes dont sept en width:1% et
 * nowrap ne tenaient pas: seule celle de l'expediteur pouvait s'etirer.
 * Les fragments recus sont deja mis en forme par l'appelant (liens de zoom,
 * pastilles d'etat), d'ou l'absence d'echappement supplementaire ici.
 */
function pfxtx_row($md,$mailfrom,$subject,$mailto,$ipaddr,$hostname,$msgid,
	$status,$reason,$ArticaRBL,$smtp_code_text,$zdate,
	$reason_column,$tomail_column,$class_text):string{

	$h ="<tr id='$md'>";
	$h.="<td><div class='awc-ident'>";
	$h.="<span class='awc-ico'><i class='fas fa-envelope'></i></span>";
	$h.="<span><span class='awc-name $class_text'>$mailfrom</span>";
	if(trim(strip_tags((string)$subject))!==""){$h.="<div class='awc-meta'>$subject</div>";}
	if($tomail_column && trim(strip_tags((string)$mailto))!==""){$h.="<div class='awc-meta'>&rarr; $mailto</div>";}
	$h.="<div class='awc-meta'>$ipaddr$hostname</div>";
	if(trim(strip_tags((string)$msgid))!==""){$h.="<div class='awc-meta'>$msgid</div>";}
	if(trim(strip_tags((string)$smtp_code_text))!==""){$h.="<div class='awc-meta'>$smtp_code_text</div>";}
	$h.="</span></div></td>";
	$h.="<td class='center'>$status</td>";
	if($reason_column){$h.="<td class='awc-mono'>$reason</td>";}
	$h.="<td class='center'>$ArticaRBL</td>";
	$h.="<td class='awc-mono'>$zdate</td>";
	$h.="</tr>";
	return $h;
}

