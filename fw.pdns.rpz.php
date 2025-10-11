<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.mysql.powerdns.inc");
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(isset($_GET["enable"])){enable_js();exit;}
if(isset($_GET["search"])){main();exit;}
if(isset($_GET["main"])){main();exit;}
if(isset($_GET["policy-js"])){policy_js();exit;}
if(isset($_GET["newpolicy-js"])){policy_new_js();exit;}
if(isset($_GET["newpolicy-popup"])){policy_new_popup();exit;}
if(isset($_POST["newpolicy"])){policy_new_save();exit;}

if(isset($_GET["rpz-service"])){rpz_service_js();exit;}
if(isset($_GET["rpz-service-popup"])){rpz_service_popup();exit;}
if(isset($_POST["RPZServiceEnabled"])){rpz_service_save();exit;}

if(isset($_GET["delete-js"])){delete_js();exit;}
if(isset($_POST["delete"])){policy_delete();exit;}
if(isset($_POST["ID"])){policy_save();exit;}
if(isset($_GET["policy-popup"])){policy_popup();exit;}
if(isset($_GET["policy-move"])){policy_move();exit;}
if(isset($_GET["defaults"])){defaults();exit;}
if(isset($_GET["td"])){td_row();exit;}
if(isset($_GET["run-js"])){run_js();exit;}


if(isset($_POST["run"])){exit;}
page();



function policy_new_js(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$function=$_GET["function"];
	$title=$tpl->javascript_parse_text("{new_policy}");
	$tpl->js_dialog($title, "$page?newpolicy-popup=yes&function=$function");
}
function rpz_service_js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $function=$_GET["function"];
    return $tpl->js_dialog("RPZ Service", "$page?rpz-service-popup=yes&function=$function");
}
function rpz_service_popup():bool{
    $tpl=new template_admin();
    $function=$_GET["function"];

    $RPZServiceUseSSL=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("RPZServiceUseSSL"));
    $RPZServicePort=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("RPZServicePort"));
    $RPZServiceHostname=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("RPZServiceHostname");
    $RPZServiceWriteLogs=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("RPZServiceWriteLogs"));
    $RPZServiceEnabled=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("RPZServiceEnabled"));
    $RPZServiceClientError=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("RPZServiceClientError");

    if($RPZServicePort==0){
        $RPZServicePort=9905;
    }
    if($RPZServiceEnabled==1){
        if(strlen($RPZServiceClientError)>2){
            echo $tpl->div_error($RPZServiceClientError);
        }
    }

    $form[] = $tpl->field_checkbox("RPZServiceEnabled","{enabled}",$RPZServiceEnabled,true);
    $form[] = $tpl->field_text("RPZServiceHostname","{remote_server_address}",$RPZServiceHostname);
    $form[] = $tpl->field_numeric("RPZServicePort","{remote_server_port}",$RPZServicePort);
    $form[] = $tpl->field_checkbox("RPZServiceUseSSL","{use_ssl}",$RPZServiceUseSSL);
    $form[] = $tpl->field_checkbox("RPZServiceWriteLogs","{write_logs}",$RPZServiceWriteLogs);
    echo $tpl->form_outside("", $form,"","{apply}","BootstrapDialog1.close();$function();","AsDnsAdministrator");
    return true;
}
function rpz_service_save(){
    $tpl=new template_admin();
    $tpl->CLEAN_POST();
    $tpl->SAVE_POSTs();
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/unbound/reconfigure");

}



function policy_js(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $function=$_GET["function"];
    $ID=intval($_GET["policy-js"]);
    $title=null;
    $tempid=0;
    $q=new lib_sqlite("/home/artica/SQLITE/rpz.db");
    if(isset($_GET["tempid"])){$tempid=intval($_GET["tempid"]);}
    if($tempid>0){
        $ligne=$q->mysqli_fetch_array("SELECT * FROM policies WHERE lastsaved=$tempid");
        $ID=intval($_GET["ID"]);
        $title=$ligne["rpzname"];
        echo "$function();\n";
    }
    if($title==null){
        $ligne=$q->mysqli_fetch_array("SELECT rpzname FROM policies WHERE ID=$ID");
        $title=$ligne["rpzname"];
    }
    $tpl->js_dialog($title, "$page?policy-popup=$ID&function=$function");
}

function run_js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $function=$_GET["function"];
    $ID=intval($_GET["run-js"]);
    if($ID==0){
        return $tpl->js_error("ID ==0");
    }
    $q=new lib_sqlite("/home/artica/SQLITE/rpz.db");
    $ligne=$q->mysqli_fetch_array("SELECT * FROM policies WHERE ID=$ID");

    $js=$tpl->framework_buildjs("/dnscache/rpz/download/$ID",
        "rpz.$ID.progress","rpz.$ID.log","rpz-progress-restart","$function()");
    return $tpl->js_confirm_execute("{download} ".$ligne["rpzname"]." {database}","run",$ID,$js);

}

function enable_js():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $ID=intval($_GET["enable"]);
    $q=new lib_sqlite("/home/artica/SQLITE/rpz.db");
    $ligne=$q->mysqli_fetch_array("SELECT enabled FROM policies WHERE ID=$ID");
    if(intval($ligne["enabled"])==1){
        $q->QUERY_SQL("UPDATE policies set enabled=0 WHERE ID=$ID");
        if(!$q->ok){
            return $tpl->js_error($q->mysql_error);
        }
        header("content-type: application/x-javascript");
        $sock=new sockets();
        $sock->REST_API("/dnscache/rpz/build");
        echo "Loadjs('$page?td=$ID');";
        return admin_tracks("Set RBL Policy $ID to disable");
    }
    $q->QUERY_SQL("UPDATE policies set enabled=1 WHERE ID=$ID");
    if(!$q->ok){
        return $tpl->js_error($q->mysql_error);
    }
    header("content-type: application/x-javascript");
    echo "Loadjs('$page?td=$ID');";
    $sock=new sockets();
    $sock->REST_API("/dnscache/rpz/build");
    return admin_tracks("Set RBL Policy $ID to enable");
}

function policy_move(){
    $q=new lib_sqlite("/home/artica/SQLITE/rpz.db");
    $page       = CurrentPageName();
    $tpl        = new template_admin();
    $ID         = $_GET["policy-move"];
    $OrgID      = $ID;
    $dir        = $_GET["dir"];
    $table      = "policies";
    $sql        = "SELECT zOrder FROM `$table` WHERE ID='$ID'";
    $ligne      = $q->mysqli_fetch_array($sql);
    if(!$q->ok){$tpl->js_mysql_alert($q->mysql_error);return;}
    $CurrentOrder=$ligne["zOrder"];

    if($dir==0){
        $NextOrder=$CurrentOrder-1;
    }else{
        $NextOrder=$CurrentOrder+1;
    }

    $sql="UPDATE `$table` SET zOrder='$CurrentOrder' WHERE zOrder='$NextOrder'";
    $q->QUERY_SQL($sql);
    if(!$q->ok){$tpl->js_mysql_alert($q->mysql_error."<br>$sql");return;}


    $sql="UPDATE `$table` SET zOrder=$NextOrder WHERE ID='$ID'";
    $q->QUERY_SQL($sql);
    if(!$q->ok){$tpl->js_mysql_alert($q->mysql_error."<br>$sql");return;}

    $results=$q->QUERY_SQL("SELECT ID FROM `$table` ORDER by zorder");
    if(!$q->ok){$tpl->js_mysql_alert($q->mysql_error."<br>$sql");return;}
    $c=1;
    foreach ($results as $index=>$ligne){
        $ID=$ligne["ID"];
        $sql="UPDATE `$table` SET zOrder='$c' WHERE ID='$ID'";
        $q->QUERY_SQL($sql);
        if(!$q->ok){$tpl->js_mysql_alert($q->mysql_error."<br>$sql");return;}
        $c++;
    }
    $GLOBALS["CLASS_SOCKETS"]->getFrameWork("pdns.php?rpz=yes");

}

function policy_new_popup(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $function=$_GET["function"];
    $tmpid=time();
    $Types[1]="{rpz_option1}";
    $Types[2]="{rpz_option2}";
    $tpl->field_hidden("newpolicy",$tmpid);
    $form[]=$tpl->field_array_checkboxes2Columns($Types,"rpztype",1);
    $js="BootstrapDialog1.close();$function();";
    echo $tpl->form_outside("{new_policy}", $form,"","{create}","$js","AsDnsAdministrator");
}
function policy_new_save():bool{
    $tpl=new template_admin();
    $tmpid=intval($_POST["newpolicy"]);
    $rpztype=intval($_POST["rpztype"]);

    if($rpztype==0){
        $tpl->post_error("RPZ Type invalid");
        return false;
    }
    if($tmpid==0){
        $tpl->post_error("TimeID invalid");
        return false;
    }

    $q=new lib_sqlite("/home/artica/SQLITE/rpz.db");
    $sql="INSERT INTO policies (rpzname,lastsaved,rpztype) VALUES('Policy.$tmpid',$tmpid,$rpztype)";
    $q->QUERY_SQL($sql);
    if(!$q->ok){
        $tpl->post_error($q->mysql_error);
        return false;
    }
    admin_tracks_post("New RPZ policy");
    return true;
}
function policy_popup(){
    $tpl=new template_admin();
    $q=new lib_sqlite("/home/artica/SQLITE/rpz.db");
    $ID=intval($_GET["policy-popup"]);
    $ligne=$q->mysqli_fetch_array("SELECT * FROM policies WHERE ID=$ID");
    $rpztype=intval($ligne["rpztype"]);
    $function=$_GET["function"];

    $tpl->field_hidden("ID",$ID);
    if($rpztype==1){
        $form[]=$tpl->field_text("rpzurl","{url}",$ligne["rpzurl"],true);
    }else{
        $form[]=$tpl->field_text("rpzurl","{DNS_SERVER}",$ligne["rpzurl"],true);
    }
    $Types[1]="{rpz_option1}";
    $Types[2]="{rpz_option2}";

    $defpol_array["Policy.Custom"]="{SpoofCNAMEAction}";
    $defpol_array["Policy.Drop"]="{drop}";
    $defpol_array["Policy.NoAction"]="{do_nothing}";
    $defpol_array["Policy.NODATA"]="{no_data}";
    $defpol_array["Policy.NXDOMAIN"]="{HEADERS_FROM_OR_TO_NO_DOMAIN}";
    $form[] = $tpl->field_checkbox("enabled","{enabled}",$ligne["enabled"]);
    $form[] = $tpl->field_text("zone","{zone}",$ligne["zone"]);
    $form[] = $tpl->field_text("rpzname","{rulename}",$ligne["rpzname"]);
    $form[] = $tpl->field_array_hash($defpol_array,"defpol","{policy}",$ligne["defpol"]);
    $form[] = $tpl->field_text("defcontent","CNAME",$ligne["defcontent"]);
    echo $tpl->form_outside($ligne["rpzname"]." {type}:$rpztype", $form,$Types[$rpztype],"{apply}","BootstrapDialog1.close();$function();","AsDnsAdministrator");
}
function policy_save(){
    $tpl=new template_admin();
    $tpl->CLEAN_POST();
    $ID=intval($_POST["ID"]);
    $lastsaved=time();
    $sql="UPDATE policies SET
    rpzurl='{$_POST["rpzurl"]}',                    
    enabled='{$_POST["enabled"]}',
    rpzname='{$_POST["rpzname"]}',
    zone='{$_POST["zone"]}',
    defpol='{$_POST["defpol"]}',
    lastsaved='$lastsaved',
    defcontent='{$_POST["defcontent"]}' WHERE ID=$ID";
    $q=new lib_sqlite("/home/artica/SQLITE/rpz.db");
    $q->QUERY_SQL($sql);
    if(!$q->ok){ $tpl->post_error($q->mysql_error);return false;}
    admin_tracks_post("Saving RPZ Policy {$_POST["rpzname"]}");
    $GLOBALS["CLASS_SOCKETS"]->getFrameWork("pdns.php?rpz=yes");
    $sock=new sockets();
    $sock->REST_API("/dnscache/rpz/build");
    return true;

}

function delete_js(){
	header("content-type: application/x-javascript");
	$page=CurrentPageName();
	$tpl=new template_admin();
	$md=$_GET["md"];
    $ID=intval($_GET["delete-js"]);
    $q=new lib_sqlite("/home/artica/SQLITE/rpz.db");
    $ligne=$q->mysqli_fetch_array("SELECT * FROM policies WHERE ID=$ID");
    $tpl->js_confirm_delete($ligne["rpzname"],"delete",$ID,"$('#$md').remove();");

}
function policy_delete(){
    $ID=intval($_POST["delete"]);
    $q=new lib_sqlite("/home/artica/SQLITE/rpz.db");
    $ligne=$q->mysqli_fetch_array("SELECT rpzname FROM policies WHERE ID=$ID");
    $name=$ligne["rpzname"];
    $q->QUERY_SQL("DELETE FROM policies WHERE ID=$ID");
    admin_tracks("Remove RPZ Policy $name $ID");

    $sock=new sockets();
    $sock->REST_API("/dnscache/rpz/build");
    $GLOBALS["CLASS_SOCKETS"]->getFrameWork("pdns.php?rpz=yes");
}



function page(){
    $page       = CurrentPageName();
	$tpl        = new template_admin();



    $html=$tpl->page_header("{POLICIES_ZONES}","fa-solid fa-shield",
    "{RPZ_EXPLAIN}","$page?main=yes","rpz-local-domains","rpz-progress-restart",true,"rpz-local-domains");

    if(isset($_GET["main-page"])){
        $tpl=new template_admin(null,$html);
        echo $tpl->build_firewall();
        return true;
    }

	echo $tpl->_ENGINE_parse_body($html);
    return true;
}

function btns():string{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $PowerDNSEnableClusterSlave=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("PowerDNSEnableClusterSlave"));
    $topbuttons=array();

    if($PowerDNSEnableClusterSlave==0) {
        $topbuttons[] = array("Loadjs('$page?newpolicy-js=yes&function={$_GET["function"]}');",
            ico_trash, "{new_policy}");
    }

    if($PowerDNSEnableClusterSlave==0) {
        $topbuttons[] = array("Loadjs('$page?rpz-service=yes&function={$_GET["function"]}');",
            ico_clouds, "RPZ Service");
    }

    $topbuttons[] = array("Loadjs('$page?defaults=yes&function={$_GET["function"]}');",
        ico_plus, "{default_rules}");

    return $tpl->table_buttons($topbuttons);


}

function defaults(){
    CreateDefaultsPolicies();
    $function=$_GET["function"];
    echo "$function();\n";
}

function CreateDefaultsPolicies(){

    $zOrder=1;
    // rpzname / zone / rpztype / enabled / zOrder / rpzurl / defdesc
    $zOrder++;
    /*
    $f[]="('drop.ip.dtq','zonefiles/drop.ip.dtq',2,1,$zOrder,'Policy.NXDOMAIN','US.spamhaus.zone,EU.spamhaus.zone','Do Not Route or Peer list.<br>IPs that have been identified as being hijacked, belonging to either bullet proof hosters, or are being leased by professional malicious organizations. The very worst of the worst.')";
    $zOrder++;
    $f[]="('coinblocker.srv','zonefiles/coinblocker.srv',2,1,$zOrder,'Policy.NXDOMAIN','US.spamhaus.zone,EU.spamhaus.zone','BitCoin Blocker')";
    $zOrder++;
    $f[]="('torblock.srv','zonefiles/torblock.srv',2,1,$zOrder,'Policy.NXDOMAIN','US.spamhaus.zone,EU.spamhaus.zone','TOR Gateways')";
    $zOrder++;
     $f[]="('porn.host.srv','zonefiles/porn.host.srv',2,1,$zOrder,'Policy.NXDOMAIN','US.spamhaus.zone,EU.spamhaus.zone','Domains identified as Porn sites')";
    $f[]="('phish.host.dtq','zonefiles/phish.host.dtq',2,1,$zOrder,'Policy.NXDOMAIN','US.spamhaus.zone,EU.spamhaus.zone','Domains identified as hosting a phishing site(s).')";
    $zOrder++;
    $f[]="('malware.host.dtq','zonefiles/malware.host.dtq',2,1,$zOrder,'Policy.NXDOMAIN','US.spamhaus.zone,EU.spamhaus.zone','Domains identified as hosting malware.')";
    $zOrder++;
    $f[]="('adware.host.dtq','zonefiles/adware.host.dtq',2,1,$zOrder,'Policy.NXDOMAIN','US.spamhaus.zone,EU.spamhaus.zone','Domains identified as hosting adware.')";
    $zOrder++;
    $f[]="('botnet.host.dtq','zonefiles/botnet.host.dtq',2,1,$zOrder,'Policy.NXDOMAIN','US.spamhaus.zone,EU.spamhaus.zone','Domains identified as hosting a botnet resource (not a botnet C&C).')";
    $zOrder++;
    $f[]="('dga.host.dtq','zonefiles/dga.host.dtq',2,1,$zOrder,'Policy.NXDOMAIN','US.spamhaus.zone,EU.spamhaus.zone','Domains created from multiple domain generated algorithms (DGA).<br> Domains that are automatically generated and usually associated with malware.')";
    $zOrder++;
    $f[]="('badrep.host.dtq','zonefiles/badrep.host.dtq',2,1,$zOrder,'Policy.NXDOMAIN','US.spamhaus.zone,EU.spamhaus.zone','Uncategorized Domains identified as having a bad reputation.<br>This includes hosts owned by known spammers, payload URLs, malicious tracking domains and domains associated with low reputation networks, amongst other factors.')";
    */
    $f[]="('urlhaus','zonefiles/urlhaus.zone',1,1,$zOrder,'Policy.NXDOMAIN','https://urlhaus.abuse.ch/downloads/rpz','URLhaus is a project from abuse.ch with the goal of sharing malicious URLs that are being used for malware distribution.')";

    $f[]="('Curben','zonefiles/curben.phishing.zone',1,1,$zOrder,'Policy.NXDOMAIN','https://curbengh.github.io/malware-filter/phishing-filter-rpz.conf','phishtank.com, openphish.com, phishunt.io Compilation')";

    $f[]="('hblock','zonefiles/hblock.molinero.dev.zone',1,1,$zOrder,'Policy.NXDOMAIN','https://hblock.molinero.dev/hosts_rpz.txt','hBlock is a POSIX-compliant shell script that gets a list of domains that serve ads, tracking scripts and malware from multiple sources')";

    $f[]="('yoyo','zonefiles/yoyo.adservers.zone',1,1,$zOrder,'Policy.NXDOMAIN','https://pgl.yoyo.org/adservers/serverlist.php?hostformat=rpz&showintro=1&mimetype=plaintext','Advertising Servers')";

    $f[]="('StevenBlack.Porn','zonefiles/StevenBlack.porn.zone',1,1,$zOrder,'Policy.NXDOMAIN',
    'https://raw.githubusercontent.com/StevenBlack/hosts/master/alternates/porn/hosts',
    'StevenBlack/hosts with the porn extension')";

    $q=new lib_sqlite("/home/artica/SQLITE/rpz.db");

    foreach ($f as $query){
        $q->QUERY_SQL("INSERT INTO policies (rpzname,zone,rpztype,enabled,zOrder,defpol,rpzurl,defdesc) VALUES $query");
    }
    $sock=new sockets();
    $sock->REST_API("/dnscache/rpz/build");

}
function td_row():bool{
    $tpl=new template_admin();
    $ID=intval($_GET["td"]);
    $status_ico="<span class='label label-primary'>{active2}</span>";
    $q=new lib_sqlite("/home/artica/SQLITE/rpz.db");
    $ligne=$q->mysqli_fetch_array("SELECT enabled FROM policies WHERE ID=$ID");
    $rpzname=$ligne["rpzname"];
    $rpztype=$ligne["rpztype"];
    $defpol=$ligne["defpol"];
    $rpzurl=$ligne["rpzurl"];
    $status=$ligne["status"];
    $enabled=intval($ligne["enabled"]);
    $Types[1]="URL / {download}";
    $Types[2]="{dns_query}";
    $rpztype_text="<br><i>$rpzurl</i>";

    if($rpztype==1){
        $rpztype_text="$rpztype_text<br><i>$rpzurl</i>";
        if($status==0) {
            $status_ico = "<span class='label label'>{inactive}</span>";
        }
        if($status>1) {
            $status_ico = "<span class='label label-danger'>{error}</span>";
        }
        if($status==1) {
           // $elements=$tpl->FormatNumber($items);
        }
    }



    if($enabled==0){
        $status_ico = "<span class='label label'>{disabled}</span>";
    }
    $status_ico=base64_encode($tpl->_ENGINE_parse_body($status_ico));
    $f[]="if( document.getElementById('status-ico-$ID') ){";
    $f[]="\tdocument.getElementById('status-ico-$ID').innerHTML=base64_decode('$status_ico');";
    $f[]="}";
    echo @implode("\n",$f);
    return true;
}
function main(){
	$tpl=new template_admin();
	$page=CurrentPageName();
    $function=$_GET["function"];
    $TRCLASS=null;
    $w1="style='width:1%' nowrap ";

	$html[]="<table id='table-rpz-policies' class=\"table table-stripped\" data-page-size=\"100\" data-paging=\"true\">";
	$html[]="<thead>";
	$html[]="<tr>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{status}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text' nowrap>{rulename}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{type}</th>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{items}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{enable}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'></th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'></th>";
    $html[]="<th data-sortable=true class='text-capitalize center' data-type='text'>DEL</th>";
	$html[]="</tr>";
	$html[]="</thead>";
	$html[]="<tbody>";

    $search="*{$_GET["search"]}*";
    $search=str_replace("**","*",$search);
    $search=str_replace("**","*",$search);
    $search=str_replace("*","%",$search);

	$q=new lib_sqlite("/home/artica/SQLITE/rpz.db");
    $PoliciesNum=$q->COUNT_ROWS("policies");

    if($PoliciesNum==0){
        CreateDefaultsPolicies();
    }

    $sql="CREATE TABLE IF NOT EXISTS `policies` (
				`ID` INTEGER PRIMARY KEY AUTOINCREMENT,
				`rpzname` TEXT NOT NULL DEFAULT 'policy.rpz',
				`zone` TEXT NOT NULL DEFAULT 'policy.zone',
				`rpztype` INTEGER NOT NULL DEFAULT '0',
				`defpol` TEXT NOT NULL DEFAULT 'Policy.Custom',
				`defcontent` TEXT NOT NULL DEFAULT 'localhost.localdomain',
				`defdesc` TEXT NOT NULL DEFAULT '',
				`deferr` TEXT NOT NULL DEFAULT '',
				`enabled` INTEGER NOT NULL DEFAULT '1',
				`items` INTEGER NOT NULL DEFAULT '0',
				`status` INTEGER NOT NULL DEFAULT '0',
				`lastsaved` INTEGER NOT NULL DEFAULT '0',
				`zOrder` INTEGER NOT NULL DEFAULT '1',
				`rpzurl` TEXT NULL
		)";

    if(!$q->FIELD_EXISTS("policies","zone")){
        $q->QUERY_SQL("ALTER TABLE policies ADD COLUMN zone  TEXT NOT NULL DEFAULT 'policy.zone'");
        $q->QUERY_SQL("ALTER TABLE policies ADD COLUMN defdesc  TEXT NOT NULL DEFAULT ''");
        $q->QUERY_SQL("ALTER TABLE policies ADD COLUMN deferr  TEXT NOT NULL DEFAULT ''");
    }
    if(!$q->FIELD_EXISTS("policies","deferr")){
        $q->QUERY_SQL("ALTER TABLE policies ADD COLUMN deferr  TEXT NOT NULL DEFAULT ''");
    }
    if(!$q->FIELD_EXISTS("policies","lastsaved")){
        $q->QUERY_SQL("ALTER TABLE policies ADD COLUMN lastsaved INTEGER NOT NULL DEFAULT '0'");
    }
    $q->QUERY_SQL($sql);
    if(!$q->ok){echo $tpl->div_error("SQL Error||$q->mysql_error");}


    $sql="SELECT * FROM policies WHERE ( (rpzname LIKE '$search') OR  (defcontent LIKE '$search') OR  (rpzurl LIKE '$search')  ) ORDER BY zOrder";
    writelogs($sql,__FUNCTION__,__FILE__,__LINE__);
    $results = $q->QUERY_SQL($sql);
    if(!$q->ok){echo $tpl->div_error("SQL Error||$q->mysql_error");}

    $Types[1]="URL / {download}";
    $Types[2]="{dns_query}";

    $defpol_array["Policy.Custom"]="{SpoofCNAMEAction}";
    $defpol_array["Policy.Drop"]="{drop}";
    $defpol_array["Policy.NoAction"]="{do_nothing}";
    $defpol_array["Policy.NODATA"]="{no_data}";
    $defpol_array["Policy.NXDOMAIN"]="{HEADERS_FROM_OR_TO_NO_DOMAIN}";

    $RPZServiceEnabled=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("RPZServiceEnabled"));
    $RPZServiceClientError=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("RPZServiceClientError");

    if($RPZServiceEnabled==1){
        if(strlen($RPZServiceClientError)>1){
            if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
            $html[]="<tr class='$TRCLASS' id='none'>";
            $html[]="<td><span class='label label-danger'>ERROR</span></td>";
            $html[]="<td >RPZ Service:$RPZServiceClientError</td>";
            $html[]="<td></td>";
            $html[]="<td>-</td>";
            $html[]="<td class='center' $w1>-</td>";
            $html[]="<td class='center' $w1>-</td>";
            $html[]="<td class='center' $w1>-</td>";
            $html[]="<td class='center' $w1>-</td>";
            $html[]="</tr>";
        }
        $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/unbound/rpzclient/status"));
        if(property_exists($json,"Info")) {
            foreach ($json->Info as $status) {
                $status_ico = "<span class='label label'>{inactive2}</span>";
                $ZoneName=$status->ZoneName;
                if($status->Active==1){
                    $status_ico = "<span class='label label-primary'>{active2}</span>";
                }
                if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
                $html[]="<tr class='$TRCLASS' id='none'>";
                $html[]="<td $w1>$status_ico</td>";
                $html[]="<td ><strong>$ZoneName</strong></td>";
                $html[]="<td>RPZ Service</td>";
                $html[]="<td>-</td>";
                $html[]="<td class='center' $w1>-</td>";
                $html[]="<td class='center' $w1>-</td>";
                $html[]="<td class='center' $w1>-</td>";
                $html[]="<td class='center' $w1>-</td>";
                $html[]="</tr>";

            }

        }
    }

	
	foreach ($results as $index=>$ligne){
		if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
		$md=md5(serialize($ligne));
        $ID=$ligne["ID"];
        $rpzname=$ligne["rpzname"];
        $rpztype=$ligne["rpztype"];
        $defpol=$ligne["defpol"];
        $rpzurl=$ligne["rpzurl"];
        $status=$ligne["status"];
        $enabled=intval($ligne["enabled"]);
        $elements=$tpl->icon_nothing();
        $items=intval($ligne["items"]);
        $zOrder=intval($ligne["zOrder"]);
        $status_ico="<span class='label label-primary'>{active2}</span>";


        $rpztype_text=$tpl->td_href($Types[$rpztype],null,"Loadjs('$page?policy-js=$ID&function=$function')");
        $rpztype_text="$rpztype_text<br><i>$rpzurl</i>";
        $defpol_text=$defpol_array[$defpol];
        $delete_icon=$tpl->icon_delete("Loadjs('$page?delete-js=$ID&md=$md')","AsDnsAdministrator");

        if($defpol=="Policy.Custom"){
            $defpol_text=$defpol_text. " <strong>".$ligne["defcontent"]."</strong>";
        }

        if($rpztype==1){
            if($status==0) {
                $status_ico = "<span class='label label'>{inactive}</span>";
            }
            if($status>1) {
                $status_ico = "<span class='label label-danger'>{error}</span>";
            }

            if(strlen($rpzurl)<3){
                $status_ico = "<span class='label label-danger'>{url} {error}</span>";
            }

            if($status==1) {
                $elements=$tpl->FormatNumber($items);
            }
        }

        $enabled_ico=$tpl->icon_check($enabled,"Loadjs('$page?enable=$ID');",null,"AsDnsAdministrator");

        if($enabled==0){
            $status_ico = "<span class='label label'>{disabled}</span>";
        }

        $mv_up=$tpl->icon_up("Loadjs('$page?policy-move={$ligne["ID"]}&dir=0')","AsDnsAdministrator");
        $mv_down=$tpl->icon_down("Loadjs('$page?container-move={$ligne["ID"]}&dir=1')","AsDnsAdministrators");
        if($zOrder<2){$mv_up=null;}

        $run=$tpl->icon_run("Loadjs('$page?run-js=$ID&function=$function')","AsDnsAdministrator");
        $html[]="<tr class='$TRCLASS' id='$md'>";
        $html[]="<td $w1><span id='status-ico-$ID'>$status_ico</span></td>";
        $html[]="<td >".$tpl->td_href($rpzname,null,"Loadjs('$page?policy-js=$ID&function=$function')")."</td>";
		$html[]="<td $w1>$rpztype_text<br>$defpol_text</td>";
        $html[]="<td $w1>$elements</td>";
        $html[]="<td class='center' $w1>$enabled_ico</td>";
        $html[]="<td class='center' $w1>$run</td>";
        $html[]="<td class='center' $w1>$mv_up&nbsp;&nbsp;$mv_down</td>";
		$html[]="<td class='center' $w1>$delete_icon</td>";
		$html[]="</tr>";
	}

	$html[]="</tbody>";
	$html[]="</table>";


    $TINY_ARRAY["TITLE"]="{POLICIES_ZONES}";
    $TINY_ARRAY["ICO"]="fa-solid fa-shield";
    $TINY_ARRAY["EXPL"]="{RPZ_EXPLAIN}";
    $TINY_ARRAY["URL"]="rpz-local-domains";
    $TINY_ARRAY["BUTTONS"]=btns();
    $jstiny="Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";


    $html[]="
	<script>
	$jstiny
	NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS)."
	
</script>";

echo $tpl->_ENGINE_parse_body(@implode("\n", $html));
}
function FormatNumber($number, $decimals = 0, $thousand_separator = '&nbsp;', $decimal_point = '.'){$tmp1 = round((float) $number, $decimals); while (($tmp2 = preg_replace('/(\d+)(\d\d\d)/', '\1 \2', $tmp1)) != $tmp1)$tmp1 = $tmp2; return strtr($tmp1, array(' ' => $thousand_separator, '.' => $decimal_point));}
function DistanceInMns($time){
	$data1 = $time;
	$data2 = time();
	$difference = ($data2 - $data1);
	return round($difference/60);
}

function isPdnsError($domain_id):string{
    VERBOSE(__FUNCTION__,__LINE__);
    $q          = new mysql_pdns();
    $tpl        = new template_admin();

    if($q->TABLE_EXISTS("pdnsutil_chkzones")) {
        $sql = "SELECT COUNT(*) AS tcount FROM pdnsutil_chkzones WHERE domain_id=$domain_id";
        $ligne2 = mysqli_fetch_array($q->QUERY_SQL($sql));


        if (!$q->ok) {
            return "<td width=1% nowrap><i class=\"fas fa-exclamation-circle\"></i><span class='text-danger'>" . $tpl->td_href("MySQL Error", $q->mysql_error) . "</span></td>";
        }

        $zcount = $ligne2["tcount"];
        if ($zcount > 0) {
            return "<td width=1% nowrap><a href=\"javascript:blur();\" 
						OnClick=\"Loadjs('fw.pdns.domains.status.php?domain_id=$domain_id');\"
						><span class='label label-warning'>{$zcount} {errors}</span></a></td>";
        }
    }

    $q      = new lib_sqlite("/home/artica/SQLITE/rpz.db");
    $ligne  = $q->mysqli_fetch_array("SELECT * FROM dnsinfos WHERE domain_id=$domain_id");

    if(!$q->ok){
        return "<td width=1% nowrap><i class=\"fas fa-exclamation-circle\"></i><span class='text-danger'>".
            $tpl->td_href("MySQL Error",$q->mysql_error)."</span></td>";
    }



    if($ligne["renewdate"]>0){
        $renewdate=date("Y-m-d",$ligne["renewdate"]);
    }

    $zinfo=unserialize(base64_decode($ligne["zinfo"]));

    if(count($zinfo)>0) {
        foreach ($zinfo as $line) {
            $line = trim($line);
            if ($line == null) {
                continue;
            }
            VERBOSE($line,__LINE__);

            if (preg_match("#Backend launched with banner#i", $line)) {
                continue;
            }
            if (preg_match("#UeberBackend destructor#i", $line)) {
                continue;
            }
            if (preg_match("#Error:(.+)#", $line, $re)) {
                if (trim($re[1]) == null) {
                    continue;
                }
                return "<td width=1% nowrap><span class='label label-danger'>{error}</span></a></td>";

            }
            if (preg_match("#[0-9]+\s+Error(.+)#i", $line)) {
                if (trim($re[1]) == null) {
                    continue;
                }
                return "<td width=1% nowrap><span class='label label-danger'>{error}</span></a></td>";

            }
        }
    }

    return "<td width=1% nowrap><span class='label label-primary'>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;OK&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</span></a></td>";
}


?>