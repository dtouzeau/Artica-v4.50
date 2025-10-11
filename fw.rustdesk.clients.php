<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.system.network.inc");
include_once(dirname(__FILE__)."/ressources/class.upload.handler.inc");

if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(isset($_GET["search"])){search();exit;}
if(isset($_POST["ip_addr"])){net_save();exit;}
if(isset($_GET["net-js"])){net_js();exit;}
if(isset($_GET["net-popup"])){net_popup();exit;}
if(isset($_GET["netmask-unlink"])){net_del();exit;}
if(isset($_GET["report-js"])){report_js();exit;}
if(isset($_GET["report-popup"])){report_popup();exit;}
if(isset($_GET["ping-report-js"])){ping_report_js();exit;}
if(isset($_GET["ping-report-popup"])){ping_report_popup();exit;}
if(isset($_GET["ping-report-table"])){ping_report_table();exit;}
if(isset($_GET["enable"])){enable();exit;}
if(isset($_GET["enabled-trusted"])){enable_trusted();exit;}

page();

function page(){
	$page=CurrentPageName();
	$tpl=new template_admin();


    $html=$tpl->page_header("{clients}",ico_computer,
        "{APP_RUSTDESK_EXPLAIN}",null,"rustdesk-clients","progress-rustdesk-restart",
        true,"table-rustdesk-clients");

    if(isset($_GET["main-page"])){
        $tpl=new template_admin(null,$html);
        echo $tpl->build_firewall();
        return true;
    }
    echo $tpl->_ENGINE_parse_body($html);
    return true;

}

function search():bool{
	$page=CurrentPageName();
	$tpl=new template_admin();
	$q=new lib_sqlite("/home/artica/SQLITE/nmapping.db");
	$items=$q->COUNT_ROWS("nmapping");

	$table[]="<table class='table table-hover'><thead>
	<tr>
	<th style='width:1%' nowrap></th>
	<th style='width:1%' nowrap>ID</th>
	<th style='width:1%' nowrap>{created_at}</th>
	<th style='width:1%' nowrap>{ipaddr}</th>
	<th>{hostname}</th>
	</tr>
	</thead>
	<tbody>
	";

    $sql="SELECT * FROM peer ORDER BY created_at DESC";
    $q=new lib_sqlite("/home/artica/SQLITE/rustdesk.db");
    if(isset($_GET["search"])){
        $search=trim($_GET["search"]);
        if(strlen($search)>1) {
            $search = "*$search*";
            $search = str_replace("**", "*", $search);
            $search = str_replace("**", "*", $search);
            $search = str_replace("*", "%", $search);
            $sql="SELECT * FROM peer WHERE ( id LIKE '$search' OR info LIKE '$search') ORDER BY created_at DESC";
        }
    }
    $td1=$tpl->table_td1prc();
    $results=$q->QUERY_SQL($sql);
    foreach ($results as $index=>$ligne){

    $uuid=$ligne["uuid"];
    $id=$ligne["id"];
    $created_at=$ligne["created_at"];
    $user=$ligne["user"];
    $status=$ligne["status"];
    $note=$ligne["note"];
    $info=$ligne["info"];
    $zdate=strtotime($created_at);
    $CurrentDate=$tpl->time_to_date($zdate,true);
    $json=json_decode($info);
    $ipAddr=$tpl->icon_nothing();
    $ipAddr_name=$tpl->icon_nothing();
    if(property_exists($json,"ip")){
        $ipAddr=$json->ip;
        $ipAddr=str_replace("::ffff:",null,$ipAddr);
        if(!isset($_SESSION["gethostbyaddr1"][$ipAddr])){
            $_SESSION["gethostbyaddr1"][$ipAddr]=gethostbyaddr($ipAddr);
        }
        $ipAddr_name=$_SESSION["gethostbyaddr1"][$ipAddr];
        if($ipAddr_name==$ipAddr){
            $ipAddr_name=$tpl->icon_nothing();
        }
    }



	$table[]="<tr id='$uuid'>";
	$table[]="<td $td1><li class='".ico_computer."'></li></td>";
	$table[]="<td $td1><strong>$id</strong></td>";
    $table[]="<td $td1>$CurrentDate</td>";
    $table[]="<td $td1>$ipAddr</td>";
    $table[]="<td>$ipAddr_name</td>";
	$table[]="</tr>";
	
	}
	$table[]="</tbody></table><script>NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS)."</script>";
	echo $tpl->_ENGINE_parse_body(@implode("\n", $table));
    return true;
}



