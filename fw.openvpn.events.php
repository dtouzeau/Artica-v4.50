<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
$users=new usersMenus();if(!$users->AsVPNManager){exit();}
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}

if(isset($_GET["search"])){search();exit;}
if(isset($_GET["client-js"])){client_js();exit;}
if(isset($_GET["ruleid-js"])){rule_js();exit;}
if(isset($_GET["rule-popup"])){rule_popup();exit;}
if(isset($_GET["main"])){main();exit;}
if(isset($_GET["main-start"])){main_start();exit;}

page();

function client_js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $md5=$_GET["client-js"];
    return $tpl->js_dialog2("{events}","$page?main-start=yes&zmd5=$md5",1024);
}

function page():bool{
	$page=CurrentPageName();
	$tpl=new template_admin();

    $html=$tpl->page_header("{APP_OPENVPN}",
        ico_eye,"{APP_OPENVPN_SEARCH_TEXT}","$page?main-start=yes",
        "openvpn-events","progress-openvpn-events",false,"table-loader");

    if(isset($_GET["main-page"])){
        $tpl=new template_admin(null,$html);
        echo $tpl->build_firewall();
        return true;
    }
    $tpl=new templates();
    echo $tpl->_ENGINE_parse_body($html);
    return true;

}
function main_start():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $zmd5="";
    if(isset($_GET["zmd5"])){
        $zmd5=sprintf("&zmd5=%s",$_GET["zmd5"]);
    }
    echo $tpl->search_block($page,"","","","&main=yes$zmd5");
    return true;
}

function xPreg($line):array{
    $line=trim($line);

    if(preg_match("#^([A-Za-z]+)\s+([0-9]+)\s+([0-9:]+)\s+(.*?)\s+openvpn\[([0-9]+)\]:\s+(.+)#",$line,$re)){
       return $re;
    }

    VERBOSE("NO MATCH 1",__LINE__);
    if(preg_match("#^([0-9\-]+)\s+([0-9:]+)\s+(.+)#",$line,$re)){
        $re[5]="000";
        $re[6]=$re[3];
        $re[3]="";
        return $re;
    }


    VERBOSE("NO MATCH 2",__LINE__);
    if(preg_match("#^([A-Za-z]+)\s+([A-Za-z]+)\s+([0-9]+)\s+([0-9:]+)\s+([0-9]+)\s+(.+)#",$line,$re)){
        $re[3]=sprintf("%s %s",$re[3],$re[4]);
        $re[4]="Client";
        $re[5]="Client";
        return $re;
    }

    VERBOSE("NO MATCH 3",__LINE__);
    return array();
}

function search(){
	$tpl=new template_admin();
	$sock=new sockets();
    $t=time();
	$html[]="<table id='table-$t' class=\"footable table table-stripped\" data-page-size=\"100\" data-paging=\"true\">";
	$html[]="<thead>";
	$html[]="<tr>";
	
	
	$TRCLASS=null;
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{date}</th>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text'>PID</th>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{type}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{events}</th>";
	
	$html[]="</tr>";
	$html[]="</thead>";
	$html[]="<tbody>";
	
	$search=$_GET["search"];

    if(strlen($search)<2){$search=".";}
	$line=base64_encode($search);
    $EndPoint="/openvpn/service/grep/$line";
    if(isset($_GET["zmd5"])){
        $EndPoint=sprintf("/openvpn/client/events/%s/%s",$_GET["zmd5"],$line);
    }

	$data=$sock->REST_API($EndPoint);

    $json=json_decode($data);
    if (json_last_error()> JSON_ERROR_NONE) {
        echo $tpl->div_error("Decoding: ".strlen($data)." bytes<hr>$data".json_last_error_msg());
        return false;
    }
    if(!$json->Status){
        echo $tpl->div_error($json->Error);
        return false;
    }

	foreach ($json->Logs as $line){
		$line=trim($line);
		if($line==null){continue;}
        $re=xPreg($line);
        if(count($re)<2){
            VERBOSE("$line NO MATCH",__LINE__);
            continue;}
        $date=sprintf("%s %s %s",$re[1],$re[2],$re[3]);
        $pid=$re[5];
        $content=$re[6];

        $color=null;
        $TYPE="<span class='label label-default font-bold'>SERVER</span>";
        if($re[4]=="Client"){
            $TYPE="<span class='label label-default font-bold'>CLIENT</span>";
        }

        if(preg_match("#\[PLUGIN\]:(.+)#",$content,$re)){
            $TYPE="<span class='label label-default font-bold'>PLUGIN</span>";
            $content=$re[1];
        }
        if(preg_match("#MANAGEMENT:(.+)#",$content,$re)){
            $TYPE="<span class='label label-default font-bold'>MGR</span>";
            $content=$re[1];
        }
        if(preg_match("#WARNING:\s+(.+)#i",$content,$re)){
            $TYPE=str_replace("label-default","label-warning",$TYPE);
            $content=$re[1];
        }
        if(preg_match("#(process exiting)#i",$content,$re)){
            $TYPE=str_replace("label-default","label-warning",$TYPE);

        }
        if(preg_match("#nitialization Sequence Completed#i",$content,$re)){
            $TYPE=str_replace("label-default","label-primary",$TYPE);
            $TYPE=str_replace(">SERVER<",">Success<",$TYPE);

        }
        if(strpos(" $content","VERIFY OK: depth")>0){
            $TYPE=str_replace("label-default","label-primary",$TYPE);
            $TYPE=str_replace(">SERVER<",">Certificate<",$TYPE);
        }
        if(strpos(" $content","soft,connection-reset")>0){
            $TYPE=str_replace("label-default","label-danger",$TYPE);
            $TYPE=str_replace(">SERVER<",">Reset<",$TYPE);
        }
        if(strpos(" $content","restarting [")>0){
            $TYPE=str_replace("label-default","label-danger",$TYPE);
            $TYPE=str_replace(">SERVER<",">Restart<",$TYPE);
        }


        if(preg_match("#(fatal error|connection failed|Connection timed out)#i",$content,$re)){
            $TYPE=str_replace("label-default","label-danger",$TYPE);

        }

		$html[]="<tr class='$TRCLASS$color' id=''>";
		$html[]="<td nowrap width='1%' nowrap><span class='fa fa-clock'> </span>&nbsp;$date</td>";
		$html[]="<td nowrap width='1%'>$pid</td>";
        $html[]="<td nowrap width='1%'>$TYPE</td>";
		$html[]="<td>$content</td>";
		$html[]="</tr>";
	}
	
	$html[]="</tbody>";
	$html[]="<tfoot>";
	
	$html[]="<tr>";
	$html[]="<td colspan='4'>";
	$html[]="<ul class='pagination pull-right'></ul>";
	$html[]="</td>";
	$html[]="</tr>";
	$html[]="</tfoot>";
	$html[]="</table>";
	$html[]="
	<script>
	NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS)."
	$(document).ready(function() { $('#table-$t').footable( { \"filtering\": { \"enabled\": false }, \"sorting\": { \"enabled\": true } } ); });
	</script>";
	
	echo $tpl->_ENGINE_parse_body($html);
    return true;
}

