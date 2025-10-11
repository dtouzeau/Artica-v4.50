<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.system.network.inc");
include_once(dirname(__FILE__)."/ressources/class.nginx.params.inc");
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(isset($_GET["popup"])){popup();exit;}
if(isset($_GET["operation"])){operation();exit;}
if(isset($_GET["search"])){search();exit;}
js();

function js(){
	$page=CurrentPageName();
	$title=$_GET["title"];
	$field_id=$_GET["field-id"];
	$tpl=new template_admin();
	$title=$tpl->javascript_parse_text("$title");
	$tpl->js_dialog9($title, "$page?popup=yes&field-id=$field_id",750);
	
	
}

function popup(){
    $tpl = new template_admin();
    $page = CurrentPageName();
    $q = new lib_sqlite("/home/artica/SQLITE/acls.db");
    $field_id = $_GET["field-id"];
    $t = time();
    echo $tpl->search_block($page, null, null, null, "&field-id=$field_id");
}
function search(){
    $tpl = new template_admin();
    $page = CurrentPageName();
    $search=$_GET["search"];


    $TRCLASS=null;
    $q = new lib_sqlite("/home/artica/SQLITE/nginx.db");
    $field_id = $_GET["field-id"];
    $t = time();
    $fid=$_GET["field-id"];
    $id_string="$fid-string";

    $sql = "SELECT ID,hosts,servicename FROM nginx_services ORDER BY zorder";
    $results=$q->QUERY_SQL($sql);
    $all_websites=$tpl->_ENGINE_parse_body("{all_websites}");
	$html[]="<table id='table-$t' class=\"footable table table-stripped\" data-page-size=\"100\" data-paging=\"true\">";
	$html[]="<thead>";
	$html[]="<tr>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text' nowrap>{websites}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' width=1%>{select}</th>";
	$html[]="</tr>";
	$html[]="</thead>";
	$html[]="<tbody>";
    if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
    $html[]="<tr class='$TRCLASS' id='md0'>";
    $html[]="<td width=99% nowrap><strong>$all_websites</strong></td>";

    $js="document.getElementById('$fid').value='0';document.getElementById('$id_string').value='$all_websites';  dialogInstance9.close();";

    $html[]="<td width=1% class='center' nowrap>". $tpl->icon_select($js,"AsProxyMonitor")."</center></td>";
    $html[]="</tr>";

    if($search<>null) {
        $search = str_replace(".", "\.", $search);
        $search = str_replace("*", ".*?", $search);
    }
    foreach ($results as $index=>$ligne){
        $ID=$ligne["ID"];
        $WAF=null;
        $sockngix=new socksngix($ID);
        $EnableModSecurity=intval($sockngix->GET_INFO("EnableModSecurity"));
        if($EnableModSecurity==1){
            $WAF="&nbsp;<span class='label label-info'>WAF</span>";
        }

        $md=md5(serialize($ligne));
        $Hosts=trim($ligne["hosts"]);
        if($Hosts==null){continue;}

        $servicename=$ligne["servicename"];
        $servicename=str_replace("'","`",$servicename);

        $js="document.getElementById('$fid').value='$ID';document.getElementById('$id_string').value='$servicename';  dialogInstance9.close();";
        if (strpos($Hosts, "||") > 0) {
            $Zhosts = explode("||", $Hosts);
            foreach ($Zhosts as $www) {
                if($search<>null){
                    if(!preg_match("#$search#",$www)){
                        VERBOSE("$search ->NOT FOUND IN $www",__LINE__);
                        continue;}
                }
                if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
                $html[]="<tr class='$TRCLASS' id='$md'>";
                $html[]="<td width=99% nowrap><strong>$www{$WAF}</strong></td>";
                $html[]="<td width=1% class='center' nowrap>". $tpl->icon_select($js,"AsProxyMonitor")."</center></td>";
                $html[]="</tr>";
            }
            continue;
        }

        if($search<>null){
            if(!preg_match("#$search#",$Hosts)){continue;}
        }

		if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
		$html[]="<tr class='$TRCLASS' id='$md'>";
        $html[]="<td width=99% nowrap><strong>$Hosts</strong></td>";
		$html[]="<td width=1% class='center' nowrap>". $tpl->icon_select($js,"AsWebMaster")."</center></td>";
		$html[]="</tr>";
	}
	$html[]="</tbody>";
	$html[]="<tfoot>";
	$html[]="<tr>";
	$html[]="<td colspan='2'>";
	$html[]="<ul class='pagination pull-right'></ul>";
	$html[]="</td>";
	$html[]="</tr>";
	$html[]="</tfoot>";
	$html[]="</table>";
	$html[]="
	<script>
	NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS)."
	$(document).ready(function() { $('#table-$t').footable({\"filtering\": { \"enabled\": false },\"sorting\": { \"enabled\": true },\"paging\": { \"size\": {$GLOBALS["FOOTABLE_PSIZE"]} } } ); });
	</script>";
	
	echo $tpl->_ENGINE_parse_body(@implode("\n", $html));
	
	
}


