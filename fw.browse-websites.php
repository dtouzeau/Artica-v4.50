<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
// Kit de tables awc_*. Inclusion INCONDITIONNELLE : dans le bootstrap
// if(!class_exists("sockets")) elle ne s'executerait jamais, et la page mourrait
// sur "Call to undefined function awc_table_style()" -- un spinner infini.
include_once(dirname(__FILE__)."/ressources/class.statistics_appliance.inc");
include_once(dirname(__FILE__)."/ressources/class.sockets.inc");
$GLOBALS["CLASS_SOCKETS"]=new sockets();
include_once(dirname(__FILE__)."/ressources/class.system.network.inc");
include_once(dirname(__FILE__)."/ressources/class.nginx.params.inc");
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(isset($_GET["popup"])){popup();exit;}
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
    $field_id = $_GET["field-id"];
    $t = time();
    echo $tpl->search_block($page, null, null, null, "&field-id=$field_id");
}
function search(){
    $tpl = new template_admin();
    $page = CurrentPageName();
    $search=$_GET["search"];


    $TRCLASS=null;
    $field_id = $_GET["field-id"];
    $t = time();
    $fid=$_GET["field-id"];
    $id_string="$fid-string";

    // Daemon API: list nginx_services rows ordered by zorder.
    $resp=$GLOBALS["CLASS_SOCKETS"]->REST_API_NGINX("/nginx-db/list/nginx_services?order=zorder");
    $data=json_decode($resp,true);
    $results=(is_array($data) && !empty($data["Status"]) && isset($data["rows"]) && is_array($data["rows"])) ? $data["rows"] : [];
    $all_websites=$tpl->_ENGINE_parse_body("{all_websites}");
	$html[]="<div class='awc-wrap'><div class='awc-card'><div class='awc-scroll'>";
	$html[]="<table class='awc-tbl'>";
	$html[]="<thead>";
	$html[]="<tr>";
	$html[]="<th>{websites}</th>";
    $html[]="<th>{select}</th>";
	$html[]="</tr>";
	$html[]="</thead>";
	// L'id du <tbody> ancre awc_pager_js() : sans lui le pager rend vide et
	// toutes les lignes s'affichent d'un bloc, sans que rien ne le signale.
	$html[]="<tbody id='bweb-rows'>";
    $html[]="<tr id='md0'>";
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
                $html[]="<tr id='$md'>";
                $html[]="<td width=99% nowrap><strong>$www{$WAF}</strong></td>";
                $html[]="<td width=1% class='center' nowrap>". $tpl->icon_select($js,"AsProxyMonitor")."</center></td>";
                $html[]="</tr>";
            }
            continue;
        }

        if($search<>null){
            if(!preg_match("#$search#",$Hosts)){continue;}
        }

		$html[]="<tr id='$md'>";
        $html[]="<td width=99% nowrap><strong>$Hosts</strong></td>";
		$html[]="<td width=1% class='center' nowrap>". $tpl->icon_select($js,"AsWebMaster")."</center></td>";
		$html[]="</tr>";
	}
	$html[]="</tbody></table></div>";
	$html[]="<div class='awc-foot'><span>$c {items}</span>";
	$html[]="<span class='awc-pg' id='bweb-pager'></span></div>";
	$html[]="</div></div>";
	$html[]="
	<script>
	NoSpinner();\n".$tpl->ICON_SCRIPTS_COMPILE()."
	</script>";
	
	// Feuille et pager concatenes AUTOUR du parsing : la regex de
	// _ENGINE_parse_body lit chaque regle CSS et chaque bloc JS comme un jeton.
	echo awc_table_style().$tpl->_ENGINE_parse_body(@implode("\n", $html)).awc_pager_js(25,"bweb-rows","bweb-pager");
	
	
}


