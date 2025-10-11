<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.mysql.powerdns.inc");
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(isset($_GET["td"])){td_row();exit;}
if(isset($_GET["search"])){main();exit;}
if(isset($_GET["main"])){main();exit;}
if(isset($_GET["rule-js"])){rule_js();exit;}
if(isset($_GET["rule-popup"])){rule_popup();exit;}
if(isset($_POST["ID"])){rule_save();exit;}
if(isset($_GET["delete-js"])){delete_js();exit;}
if(isset($_POST["delete"])){domain_delete();exit;}
page();



function rule_js():bool{

    $ID=intval($_GET["rule-js"]);
	$page=CurrentPageName();
	$tpl=new template_admin();
	$function=$_GET["function"];
    $title = $tpl->javascript_parse_text("{new_agent}");
    if($ID>0) {
        $q=new lib_sqlite("/home/artica/SQLITE/dns.db");
        $ligne=$q->mysqli_fetch_array("SELECT hostname FROM dnsagents WHERE ID=$ID");
        $title=$ligne["hostname"];
    }
	return $tpl->js_dialog($title, "$page?rule-popup=$ID&function=$function");
}

function rule_popup():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $ID=intval($_GET["rule-popup"]);
    $function=$_GET["function"];
    $js[]="BootstrapDialog1.close()";
    $btn="{add}";


    if($ID>0) {
        $btn="{apply}";
        $q=new lib_sqlite("/home/artica/SQLITE/dns.db");
        $ligne=$q->mysqli_fetch_array("SELECT * FROM dnsagents WHERE ID=$ID");
        $js[]="Loadjs('$page?td=$ID');";
        $js[]=$tpl->framework_buildjs("/dnsagents/scan",
            "articaagents.progress","articaagents.log",
            "artica-agents-progress","Loadjs('$page?td=$ID');");
    }else{
        $js[]=$tpl->framework_buildjs("/dnsagents/scan",
            "articaagents.progress","articaagents.log",
            "artica-agents-progress","Loadjs('$page?td=$ID');$function();");

    }

    if(intval($ligne["port"])<5){
        $ligne["port"]=8000;
    }

    $form[]=$tpl->field_hidden("ID", $ID, null);
    $form[]=$tpl->field_text("hostname", "{agent_address}", $ligne["hostname"], null);
    $form[]=$tpl->field_numeric("port","{listen_port}", $ligne["port"], null);
    $form[]=$tpl->field_checkbox("ssl","{UseSSL}", $ligne["ssl"], null);
    $form[]=$tpl->field_text("apikey","{API_KEY}", $ligne["apikey"], null);


    $html[]=$tpl->form_outside(null, $form,null,$btn,@implode(";",$js),"AsDnsAdministrator");
    echo $tpl->_ENGINE_parse_body(@implode("\n", $html));
    return true;
}
function rule_save():bool{
    $tpl=new template_admin();
    $tpl->CLEAN_POST();
    $ID=intval($_POST["ID"]);
    $hostname=trim($_POST["hostname"]);
    $port=intval($_POST["port"]);
    $apikey=trim($_POST["apikey"]);
    $ssl=intval($_POST["ssl"]);
    $sql="INSERT INTO dnsagents (hostname,port,apikey,ssl) VALUES ('$hostname','$port','$apikey',$ssl)";
    $q=new lib_sqlite("/home/artica/SQLITE/dns.db");

    if(!$q->FIELD_EXISTS("dnsagents","ssl")){
        $q->QUERY_SQL("ALTER TABLE dnsagents ADD ssl INTEGER NOT NULL DEFAULT 0");
    }

    if($ID>0) {
        $sql="UPDATE dnsagents SET port='$port',ssl=$ssl,apikey='$apikey',hostname='$hostname',iserror=0,errortext='' WHERE ID=$ID";
    }
    $q->QUERY_SQL($sql);
    if(!$q->ok){
        echo $tpl->post_error($q->mysql_error);
        return false;
    }

    return admin_tracks("Update/Create a DNS agent $hostname:$port");
}

function delete_js(){
	
	header("content-type: application/x-javascript");
	$page=CurrentPageName();
	$tpl=new templates();
	$t=time();
	$ask=$tpl->javascript_parse_text("{pdns_delete_domain_ask}");	
	$id=$_GET["delete-js"];
	
	$q=new mysql_pdns();
	$sql="SELECT name FROM domains WHERE id=$id";
	$ligne=mysqli_fetch_array($q->QUERY_SQL($sql));
	$domain=$ligne["name"];
	$sql="SELECT COUNT(id) AS tcount FROM records WHERE domain_id=$id";
	$ligne=mysqli_fetch_array($q->QUERY_SQL($sql));
	$items=intval($ligne["tcount"]);
	
	$ask=str_replace("%d", "$domain", $ask);
	$ask=str_replace("%c", "$items", $ask);
	
	
	$html="			
var xSave$t=function (obj) {
	var results=obj.responseText;
	if(results.length>0){alert(results);return;}	
	$('#{$_GET["id"]}').remove();
}
function Save$t(){
	if(!confirm('$ask')){return;}
	var XHR = new XHRConnection();
	XHR.appendData('delete','$id');
	XHR.sendAndLoad('$page', 'POST',xSave$t);	
}
	
Save$t()";
	echo $html;
	
}




function newdomain_save(){
    $tpl=new template_admin();
    $tpl->CLEAN_POST();
	$q=new mysql_pdns();
    $MyHostname=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("myhostname"));
    $q->QUERY_SQL("ALTER TABLE `records` ALTER `articasrv` SET DEFAULT '$MyHostname'");
    $q->QUERY_SQL("ALTER TABLE `records` ALTER `explainthis` SET DEFAULT ''");
    $domain=trim(strtolower($_POST["newdomain"]));
	$domain=url_decode_special_tool($domain);
    if(strpos(" $domain","%")>0){
        echo $tpl->post_error("[$domain] {invalid}");
        return false;
    }
    if(strpos(" $domain",";")>0){
        echo $tpl->post_error("[$domain] {invalid}");
        return false;
    }
    if(strpos(" $domain",",")>0){
        echo $tpl->post_error("[$domain] {invalid}");
        return false;
    }


	if($domain==null){
        echo $tpl->post_error("{no_data}");
		return false;
	}
	$q->AddDomain($domain);
    return true;
}
function domain_delete(){
	$id=$_POST["delete"];
	$q=new mysql_pdns();

	$Domain=$q->GetDomainName($id);

	$q->QUERY_SQL("DELETE FROM records WHERE domain_id=$id");
	if(!$q->ok){echo $q->mysql_error;return;}
	$q->QUERY_SQL("DELETE FROM domains WHERE id=$id");
	if(!$q->ok){echo $q->mysql_error;return;}
	$q->QUERY_SQL("DELETE FROM domainmetadata WHERE id=$id");
	if(!$q->ok){echo $q->mysql_error;return;}
	
	$q->QUERY_SQL("DELETE FROM pdnsutil_dnssec WHERE domain_id=$id");
	$q->QUERY_SQL("DELETE FROM pdnsutil_chkzones WHERE domain_id=$id");
	
	$q=new lib_sqlite("/home/artica/SQLITE/dns.db");
	$q->QUERY_SQL("DELETE FROM dnsinfos WHERE domain_id=$id");

	admin_tracks("Removed all datas from domain $Domain");

	$sock=new sockets();
	$sock->getFrameWork("pdns.php?dnssec=yes");
	

}

function title_extention():string{
    $Key="PowerDNSEnableClusterSlave";
    $keydate="PowerDNSClusterClientDate";
    $ClusterSlave=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO($Key));
    if($ClusterSlave==1){return "";}
    $ClusterClientDate=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO($keydate));
    if($ClusterClientDate==0){return "";}
    $tpl=new template_admin();
    $text_date=$tpl->time_to_date($ClusterClientDate,true);
    return "&nbsp;<small>{last_sync}: $text_date</small>";

}

function page(){
    $page       = CurrentPageName();
	$tpl        = new template_admin();
	$title_add  = title_extention();


    $html=$tpl->page_header("{APP_ARTICA_AGENT}","fas fa-project-diagram",
    "{artica_agent_dns_explain}","$page?main=yes","artica-agents-table","artica-agents-progress",true,"table-local-agents");



    if(isset($_GET["main-page"])){
        $tpl=new template_admin(null,$html);
        echo $tpl->build_firewall();
        return true;
    }


	echo $tpl->_ENGINE_parse_body($html);
    return true;
}

function td_row():bool{

    $ID=intval($_GET["td"]);
    $tpl=new template_admin();
    $page=CurrentPageName();
    $q=new lib_sqlite("/home/artica/SQLITE/dns.db");
    $ligne=$q->mysqli_fetch_array("SELECT * FROM dnsagents WHERE ID=$ID");
    $hostname=$ligne["hostname"];
    $port=$ligne["port"];
    $apikey=$ligne["apikey"];
    $scantime=$ligne["scantime"];
    $content=$ligne["content"];
    $enable=$ligne["enabled"];
    $records=$ligne["records"];

    $ID=intval($ligne["ID"]);
    $status=$tpl->_ENGINE_parse_body(getStatus($ligne));
    $status_Enc=base64_encode($status);
    $GetHostname=base64_encode(AgentGetHostname($ligne));
    $f[]="if (document.getElementById('agent-status-$ID')) {";
    $f[]="document.getElementById('agent-status-$ID').innerHTML=base64_decode('$status_Enc');";
    $f[]="}";
    $f[]="if (document.getElementById('agent-name-$ID')) {";
    $f[]="document.getElementById('agent-name-$ID').innerHTML=base64_decode('$GetHostname');";
    $f[]="}";


    header("content-type: application/x-javascript");
    echo @implode("\n",$f);
    return true;

}

function getStatus($ligne){
    $iserror=intval($ligne["iserror"]);
    $ID=intval($ligne["ID"]);
    $records=$ligne["records"];
    $enabled=intval($ligne["enabled"]);


    $status="<span class='label label-default'>{inactive2}</span>";
    if($enabled==0){
        return $status;
    }
    if ($iserror==1){
        $status="<span class='label label-danger'>{error}</span>";
        return $status;
    }
    if($records>0){
        $status="<span class='label label-primary'>{active2}</span>";
    }

    return $status;

}
function AgentGetHostname($ligne):string{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $hostname=$ligne["hostname"];
    $port=$ligne["port"];
    $Version=$ligne["version"];
    $iserror=intval($ligne["iserror"]);
    $errortext=$ligne["errortext"];
    if(strlen($Version)>0){
        $version=" ($Version)";
    }
    if ($iserror==1){
        $error="<br><small class='text-danger'>{$errortext}</small>";

    }

    return "$hostname:$port$version$error";
}

function main(){
	$tpl=new template_admin();
	$page=CurrentPageName();
	$q=new lib_sqlite("/home/artica/SQLITE/dns.db");
    $function=$_GET["function"];
    $TRCLASS=null;


	$html[]="<table id='table-dns-forward-zones' class=\"footable table table-stripped\" data-page-size=\"100\" data-paging=\"true\">";
	$html[]="<thead>";
	$html[]="<tr>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{status}</th>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{hostname}</th>";
    $html[]="<th class='text-capitalize' data-type='text'>{records}</center></th>";
    $html[]="<th class='text-capitalize' data-type='text' nowrap>{last_scan}</center></th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>&nbsp;</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>DEL</th>";
	$html[]="</tr>";
	$html[]="</thead>";
	$html[]="<tbody>";

    $search="*{$_GET["search"]}*";
    $search=str_replace("**","*",$search);
    $search=str_replace("**","*",$search);
    $search=str_replace("*","%",$search);



	$q->QUERY_SQL("CREATE TABLE IF NOT EXISTS dnsagents (ID INTEGER PRIMARY KEY AUTOINCREMENT,iserror INTEGER DEFAULT 0,errortext TEXT NOT NULL DEFAULT '',hostname TEXT NOT NULL DEFAULT '',port INTEGER NOT NULL DEFAULT 8000, apikey TEXT NOT NULL DEFAULT '', scantime INTEGER NOT NULL DEFAULT 0, content TEXT NOT NULL DEFAULT '',enabled INTEGER NOT NULL DEFAULT 1, 
datamd5 TEXT NOT NULL DEFAULT '',records INTEGER NOT NULL DEFAULT 0,ssl INTEGER NOT NULL DEFAULT 0,version TEXT NOT NULL DEFAULT '',dhcpdata TEXT NOT NULL DEFAULT '',dhcprecords INTEGER NOT NULL DEFAULT 0)");

    $results=$q->QUERY_SQL("SELECT * FROM dnsagents WHERE hostname LIKE '$search'");


	if(!$q->ok){echo $tpl->FATAL_ERROR_SHOW_128($q->mysql_error);}
	

	foreach ($results as $index=>$ligne){
        if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
        $hostname=$ligne["hostname"];
        $dhcprecords="";
        $port=$ligne["port"];
        $apikey=$ligne["apikey"];
        $scantime=$ligne["scantime"];
        $content=$ligne["content"];
        $enable=$ligne["enabled"];
        $records=$ligne["records"];
        $iserror=intval($ligne["iserror"]);
        $errortext=$ligne["errortext"];

        $delete_icon=$tpl->icon_delete("Loadjs('$page?delete=ID')","AsDnsAdministrator");
        $enable_icon=$tpl->icon_check($enable,"Loadjs('$page?enable=ID')","","AsDnsAdministrator");

        if(intval($ligne["dhcprecords"])>0) {
            $dhcprecords ="&nbsp;/&nbsp;". FormatNumber($ligne["dhcprecords"]);
        }
        $ID=intval($ligne["ID"]);
        $status=getStatus($ligne);
        $hostnam=$tpl->td_href("<span id='agent-name-$ID'>".AgentGetHostname($ligne)."</span>",null,"Loadjs('$page?rule-js=$ID')");

        $time=$tpl->time_to_date($scantime,true);
		$md=md5(serialize($ligne));
        $html[]="<tr class='$TRCLASS' id='$md'>";
        $html[]="<td style='width:1%'><span id='agent-status-$ID'>$status</span></td>";
        $html[]="<td style='width:99%'>$hostnam</td>";
		$html[]="<td style='width:1%;text-align: right' nowrap>".FormatNumber($records)."</td>";
        $html[]="<td style='width:1%' nowrap><span id='agent-time-$ID'>$time</span></td>";
        $html[]="<td style='width:1%;vertical-align:middle;' class='center' nowrap>$enable_icon</center></td>";
		$html[]="<td style='width:1%;vertical-align:middle;' class='center' nowrap>$delete_icon</center></td>";
		$html[]="</tr>";
	}

	$html[]="</tbody>";
	$html[]="<tfoot>";
	


	$html[]="<tr>";
	$rows=6;
	$html[]="<td colspan='$rows'>";
	$html[]="<ul class='pagination pull-right'></ul>";
	$html[]="</td>";
	$html[]="</tr>";
	$html[]="</tfoot>";
	$html[]="</table>";


    $help_url="https://wiki.articatech.com/en/dns/artica-activedirectory-dns-agent";
    $js_help="s_PopUpFull('$help_url','1024','900');";

    $scanjs=$tpl->framework_buildjs("/dnsagents/scan",
        "articaagents.progress","articaagents.log","artica-agents-progress","$function()");

    $topbuttons[] = array("Loadjs('$page?rule-js=0&function=$function')", ico_plus, "{new_agent}");
    $topbuttons[] = array($scanjs, ico_refresh, "{launch_scan}");
    $topbuttons[] = array($js_help, ico_support, "WIKI");
    $download="s_PopUp('https://artica-dns-dhcp-agent.b-cdn.net/artica-dns-dhcp-agent.exe',600,300);";
    $topbuttons[] = array($download, ico_download, "{download} {APP_ARTICA_AGENT}");

    $TINY_ARRAY["TITLE"]="{APP_ARTICA_AGENT}";
    $TINY_ARRAY["ICO"]="fas fa-project-diagram";
    $TINY_ARRAY["EXPL"]="{artica_agent_dns_explain}";
    $TINY_ARRAY["URL"]="artica-dns-agents";
    $TINY_ARRAY["BUTTONS"]=$tpl->_ENGINE_parse_body($tpl->table_buttons($topbuttons));
    $jstiny="Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";


    $html[]="
	<script>
	$jstiny
	NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS)."
	$(document).ready(function() { $('#table-dns-forward-zones').footable( { \"filtering\": { \"enabled\": false }, \"sorting\": { \"enabled\": true },\"paging\": { \"size\": {$GLOBALS["FOOTABLE_PSIZE"]} } } ); });
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

    $q      = new lib_sqlite("/home/artica/SQLITE/dns.db");
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