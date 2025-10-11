<?php

$GLOBALS["smokeping_types"]["FPing"]="Ping {host}";
$GLOBALS["smokeping_types"]["DNS"]="{dns_query}";
$GLOBALS["smokeping_types"]["HTTP"]="{http_query}";

include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.system.network.inc");
include_once(dirname(__FILE__)."/ressources/class.nginx.params.inc");
include_once(dirname(__FILE__)."/ressources/class.postgres.inc");

if(isset($_GET["table"])){table();exit;}
if(isset($_GET["tabs"])){tabs();exit;}
if(isset($_GET["rules"])){rules();exit;}
if(isset($_GET["rules-start"])){rules_start();exit;}
if(isset($_POST["set_real_ip_from"])){Save();exit;}
if(isset($_GET["updatesoft-js"])){updatesoft_js();exit;}
if(isset($_GET["updatesoft-popup"])){updatesoft_popup();exit;}
if(isset($_GET["new-rule"])){new_rule_js();exit;}
if(isset($_GET["new-rule-popup"])){new_rule_popup();exit;}
if(isset($_POST["FType"])){new_rule_save();exit;}
if(isset($_GET["ruleid-js"])){ruleid_js();exit;}
if(isset($_GET["ruleid-popup"])){ruleid_popup();exit;}
if(isset($_GET["ruleid-enable"])){ruleid_enable();exit;}
if(isset($_POST["ID"])){ruleid_save();exit;}
if(isset($_GET["ruleid-delete"])){ruleid_delete();exit;}
if(isset($_POST["ruleid-delete"])){ruleid_delete_confirm();exit;}

page();

function page(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$SMOKEPING_VERSION=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("SMOKEPING_VERSION");

    $html=$tpl->page_header("{APP_SMOKEPING} v$SMOKEPING_VERSION &raquo;&raquo; {service_status}",
        ico_health_check,"{APP_SMOKEPING_EXPLAIN}","$page?tabs=yes",
        "latency-service",
        "progress-smokeping-restart",false,
        "table-smokeping"
    );
	
	if(isset($_GET["main-page"])){
		$tpl=new template_admin("Artica: {APP_SMOKEPING} {status}",$html);
		echo $tpl->build_firewall();
		return;
	}
	$tpl=new templates();
	echo $tpl->_ENGINE_parse_body($html);
}
function ruleid_enable(){
    $tpl=new template_admin();
    $ID=intval($_GET["ruleid-enable"]);
    $q=new lib_sqlite("/home/artica/SQLITE/smokeping.db");
    $ligne=$q->mysqli_fetch_array("SELECT enabled FROM smokeping WHERE ID='$ID'");
    if(intval($ligne["enabled"])==0){$enabled=1;}else{$enabled=0;}
    $q->QUERY_SQL("UPDATE smokeping SET enabled=$enabled WHERE ID=$ID");
    if(!$q->ok){echo $tpl->js_error_stop($q->mysql_error);return false;}
    admin_tracks("Change latency rule $ID to enabled=$enabled");
    return true;
}
function ruleid_delete(){
    $ID=intval($_GET["ruleid-delete"]);
    $q=new lib_sqlite("/home/artica/SQLITE/smokeping.db");
    $ligne=$q->mysqli_fetch_array("SELECT title FROM smokeping WHERE ID='$ID'");
    $tpl=new template_admin();
    $title=$ligne["title"];
    $md=$_GET["md"];
    $q=new postgres_sql();
    $q->QUERY_SQL("DELETE FROM smokeping WHERE ruleid='$ID'");
    $GLOBALS["CLASS_SOCKETS"]->getFrameWork("smokeping.php?delete-dbs=$ID");
    $tpl->js_confirm_delete("{rule} $title","ruleid-delete",$ID,"$('#$md').remove();");

}

function ruleid_delete_confirm(){
    $ID=intval($_POST["ruleid-delete"]);
    $q=new lib_sqlite("/home/artica/SQLITE/smokeping.db");
    $q->QUERY_SQL("DELETE FROM smokeping WHERE ID='$ID'");
    if(!$q->ok){echo $q->mysql_error;return false;}
    $ligne=$q->mysqli_fetch_array("SELECT title FROM smokeping WHERE ID='$ID'");
    $title=$ligne["title"];
    admin_tracks("Remove Latency rule id $ID $title");
    return true;
}

function new_rule_js(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $tpl->js_dialog1("{new_rule}","$page?new-rule-popup=yes",550);
}
function new_rule_popup(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $form[]=$tpl->field_array_hash($GLOBALS["smokeping_types"],"FType","nonull:{type}","FPing");
    $security="AsSystemAdministrator";
    $jsafter="Loadjs('$page?ruleid-js=0')";
    echo $tpl->form_outside("{new_rule}",$form,null,"{next}",$jsafter,$security);
}
function ruleid_js(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $ID=intval($_GET["ruleid-js"]);
    if($ID==0){
        $ftype=$_SESSION["smokeping_new_rule"];
        $title="{new_rule}: ".$GLOBALS["smokeping_types"][$_SESSION["smokeping_new_rule"]];
    }else{
        $q=new lib_sqlite("/home/artica/SQLITE/smokeping.db");
        $ligne=$q->mysqli_fetch_array("SELECT * FROM smokeping WHERE ID=$ID");
        $title="$ID] ".$ligne["title"];
    }
    $tpl->js_dialog1($title,"$page?ruleid-popup=$ID&ftype=$ftype",990);
}
function new_rule_save(){
    $_SESSION["smokeping_new_rule"]=$_POST["FType"];
}
function tabs(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$array["{status}"]="$page?table=yes";
	$array["{monitoring_rules}"]="$page?rules-start=yes";
	//$array["{statistics} {messages}"]="$page?stats-mailstats=yes";
	//$array["{statistics} {volumes}"]="$page?stats-mailvolume=yes";
	echo $tpl->tabs_default($array);
}
function ruleid_popup(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $ID=intval($_GET["ruleid-popup"]);
    $ftype=$_GET["ftype"];
    $btname="{apply}";
    $security="AsSystemAdministrator";
    $q=new lib_sqlite("/home/artica/SQLITE/smokeping.db");
    if(!$q->FIELD_EXISTS("smokeping","proxyaddress")) {
        $q->QUERY_SQL("ALTER TABLE smokeping ADD `proxyaddress` TEXT NOT NULL DEFAULT ''");
        $q->QUERY_SQL("ALTER TABLE smokeping ADD `proxyport` INTEGER NOT NULL DEFAULT 3128");
    }

    if($ID>0){
        $ligne=$q->mysqli_fetch_array("SELECT * FROM smokeping WHERE ID=$ID");
        $jsafter="LoadAjax('smokeping-rules-div','$page?rules=yes');";
    }else{
        $ligne["title"]="{new_rule} ".$GLOBALS["smokeping_types"][$ftype];
        $ligne["probe"]=$ftype;
        $ligne["enabled"]=1;
        $btname="{create_rule}";
        $jsafter="LoadAjax('smokeping-rules-div','$page?rules=yes');dialogInstance1.close();";
    }
    $probe_text=$GLOBALS["smokeping_types"][$ligne["probe"]];
    $tpl->field_hidden("ID",$ID);
    $tpl->field_hidden("probe",$ligne["probe"]);
    $form[]=$tpl->field_checkbox("enabled","{enable}",$ligne["enabled"],"title,host,request");
    $form[]=$tpl->field_text("title","{rulename}",$ligne["title"],true);
    if($ligne["probe"]=="FPing"){
        $form[]=$tpl->field_text("host","{host}",$ligne["host"]);
        $tpl->field_hidden("request",$ligne["request"]);
    }
    if($ligne["probe"]=="DNS"){
        $form[]=$tpl->field_text("host","{DNSServer}",$ligne["host"]);
        $form[]=$tpl->field_text("host","{host}",$ligne["request"]);
    }
    if($ligne["probe"]=="HTTP"){
        $form[]=$tpl->field_text("host","{url}",$ligne["host"]);
        $form[]=$tpl->field_text("proxyaddress","{UseProxyServer}",$ligne["proxyaddress"]);
        $form[]=$tpl->field_numeric("proxyport","{proxy_http_port}",$ligne["proxyport"]);
        $tpl->field_hidden("request",$ligne["request"]);
    }


    echo $tpl->form_outside($ligne["title"]." ($probe_text)",$form,null,$btname,$jsafter,$security);

}

function ruleid_save(){
    $tpl=new template_admin();
    $tpl->CLEAN_POST();
    $q=new lib_sqlite("/home/artica/SQLITE/smokeping.db");
    $ID=intval($_POST["ID"]);
    foreach ($_POST as $key=>$val){
        if($key=="ID"){continue;}
        $ffields[]="`$key`";
        $ffvals[]="'".$q->sqlite_escape_string2($val)."'";
        $ffedit[]="`$key`='".$q->sqlite_escape_string2($val)."'";
        $fflog[]="$key with value $val";
    }

    $sqladd="INSERT INTO smokeping (".@implode(",",$ffields).") VALUES (".@implode(",",$ffvals).")";
    $sqledit="UPDATE smokeping SET ".@implode(",",$ffedit)." WHERE ID=$ID";
    if($ID>0){
        $q->QUERY_SQL($sqledit);
        if(!$q->ok){echo $tpl->post_error($q->mysql_error);return false;}
        admin_tracks("Edit latency rule id $ID ".@implode(" ",$fflog));
        return true;
    }

    $q->QUERY_SQL($sqladd);
    if(!$q->ok){echo $tpl->post_error($q->mysql_error);return false;}
    admin_tracks("Add new latency rule ".@implode(" ",$fflog));
    return true;
}


function rules_start(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    echo "<div id='smokeping-rules-div' style='margin-top:15px'></div>
    <script>
        LoadAjax('smokeping-rules-div','$page?rules=yes');
    </script>
    ";

}

function build_rules(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    return $tpl->framework_buildjs("smokeping.php?build=yes",
        "smokeping.progress", "smokeping.log",
    "progress-smokeping-restart"," LoadAjax('smokeping-rules-div','$page?rules=yes');",null,null,"AsSystemAdministrator");
}

function rules(){
	$page=CurrentPageName();
	$tpl=new template_admin();
    $GLOBALS["CLASS_SOCKETS"]->getFrameWork("smokeping.php?status=yes");
    $t=time();
    $jsrestart=build_rules();
    $html[]="<div class=\"btn-group\" data-toggle=\"buttons\">";
    $html[]="<label class=\"btn btn btn-primary\" OnClick=\"Loadjs('$page?new-rule=yes');\"><i class='fa fa-plus'></i> {new_rule} </label>";
    $html[]="<label class=\"btn btn btn-info\" OnClick=\"$jsrestart\"><i class='fa fa-save'></i> {build_rules} </label>";
    $html[]="</div>";
    $html[]="<table id='table-$t' class=\"footable table table-stripped\" data-page-size=\"100\" data-paging=\"true\">";


    $html[]="<thead>";
    $html[]="<tr>";
    $html[]="<th data-sortable=true class='text-capitalize'>id</th>";
    $html[]="<th data-sortable=true class='text-capitalize'>{loss}</th>";
    $html[]="<th data-sortable=true class='text-capitalize'>{status}</th>";
    $html[]="<th data-sortable=true class='text-capitalize'>{type}</th>";
    $html[]="<th data-sortable=true class='text-capitalize'>{explain}</th>";
    $html[]="<th data-sortable=false>&nbsp;</th>";
    $html[]="<th data-sortable=false>DEL</th>";
    $html[]="</tr>";
    $html[]="</thead>";
    $html[]="<tbody>";


    $q=new lib_sqlite("/home/artica/SQLITE/smokeping.db");

    if(!$q->FIELD_EXISTS("smokeping","proxyaddress")) {
        $q->QUERY_SQL("ALTER TABLE smokeping ADD `proxyaddress` TEXT NOT NULL DEFAULT ''");
        $q->QUERY_SQL("ALTER TABLE smokeping ADD `proxyport` INTEGER NOT NULL DEFAULT 3128");
    }


    if(!$q->FIELD_EXISTS("smokeping","scount")){
        $q->QUERY_SQL("ALTER TABLE smokeping ADD `scount` INTEGER NOT NULL DEFAULT 0");
    }

    $MAIN=unserialize(@file_get_contents(PROGRESS_DIR."/smoke_ping.targets"));
    $results=$q->QUERY_SQL("SELECT * FROM smokeping ORDER BY scount DESC");
    $TRCLASS=null;
    foreach ($results as $index=>$ligne){
        $text_class     = null;
        $ID=$ligne["ID"];
        $IDSrc=$ID;
        $title=$ligne["title"];
        $host=$ligne["host"];
        $probe=$ligne["probe"];
        $type=$GLOBALS["smokeping_types"][$probe];
        $request=$ligne["request"];
        $status="<span class='label label-primary'>{active2}</span>";
        $scount=$ligne["scount"];
        $explain="???";
        if($ligne["enabled"]==0){
            $text_class=" text-muted";
            $color="#8a8a8a";

        }
        if(!isset($MAIN[$ID])){
            $status= "<span class='label'>{inactive}</span>";
        }

        if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}

        $tclass         = "class='$text_class' style='vertical-align:middle;color:$color'";
        $t1perc         = "width='1%' style='vertical-align:middle;color:$color' nowrap";
        $js             = "Loadjs('$page?ruleid-js={$ligne["ID"]}',true);";
        $ID             = $tpl->td_href($ID,null,$js);
        $pproxy         = null;

        if($probe=="DNS"){
            $explain="<strong>$title</strong><br><small>{DNSServer} <strong>$host</strong> <i class=\"fas fa-arrow-right\"></i> <i class=\"fas fa-arrow-right\"></i> {query}:<strong>$request</strong></small>";
        }
        if($probe=="FPing"){
            $explain="<strong>$title</strong><br>
            <small>Ping <i class=\"fas fa-arrow-right\"></i> <i class=\"fas fa-arrow-right\"></i>&nbsp;<strong>$host</strong></small>";
        }
        if($probe=="HTTP"){
            if($ligne["proxyaddress"]<>null){
                $pproxy="&nbsp;<strong>{$ligne["proxyaddress"]}:{$ligne["proxyport"]}</strong><i class=\"fas fa-arrow-right\"></i> <i class=\"fas fa-arrow-right\"></i>";
            }

            $explain="<strong>$title</strong><br><small>{http_query} <i class=\"fas fa-arrow-right\"></i> <i class=\"fas fa-arrow-right\"></i>$pproxy&nbsp;<strong>$host</strong></small>";
        }

        $md=md5(serialize($ligne));
        $enabled=$tpl->icon_check($ligne["enabled"],"Loadjs('$page?ruleid-enable=$IDSrc')",null,"AsSystemAdministrator");

        $delete         = $tpl->icon_delete("Loadjs('$page?ruleid-delete=$IDSrc&md=$md')");


        $xloss=$tpl->FormatNumber($scount);
        $explaintext=$tpl->td_href($explain,null,$js);


        $html[]= "<tr class='$TRCLASS' id='$md'>";
        $html[]= "<td $t1perc>$ID</td>";
        $html[]= "<td $t1perc>$xloss</td>";
        $html[]="<td $t1perc>$status</td>";
        $html[]="<td $t1perc>$type</td>";
        $html[]="<td>$explaintext</td>";
        $html[]= "<td $t1perc>$enabled</td>";
        $html[]= "<td $t1perc>$delete</td>";
        $html[]= "</tr>";

    }

    $html[]="</tbody>";
    $html[]="<tfoot>";

    $html[]="<tr>";
    $html[]="<td colspan='6'>";
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

    echo $tpl->_ENGINE_parse_body($html);
	
}

function Save(){
	$tpl=new template_admin();
	$tpl->CLEAN_POST();
	
	
	$nginxsock=new socksngix(0);
	foreach ($_POST as $key=>$val){
		$nginxsock->SET_INFO($key,$val);
	}
}


function table(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$ini=new Bs_IniHandler();
    $status_file=PROGRESS_DIR."/smokeping.status";
	$GLOBALS["CLASS_SOCKETS"]->getFrameWork("smokeping.php?status=yes");
	$ini->loadFile($status_file);


    $service_restart=$tpl->framework_buildjs("smokeping.php?restart=yes",
        "smokeping.progress","smokeping.log","progress-smokeping-restart",
        "LoadAjax('latency-service','$page?tabs=yes');"
    );

	
	$html[]="<table style='width:100%'>";
	$html[]="<tr>";
	$html[]="<td style='width:260px'>";
	$html[]="<table style='width:100%'>";
	$html[]="<tr><td>
	<div class=\"ibox\" style='border-top:0px'>
    	<div class=\"ibox-content\" style='border-top:0px'>".
        $tpl->SERVICE_STATUS($ini, "APP_SMOKEPING",$service_restart)."</div>
    </div></td></tr>";
	$html[]="</table></td>";

   $q=new postgres_sql();
   $events=$q->COUNT_ROWS_LOW("smokeping");

    $q=new lib_sqlite("/home/artica/SQLITE/smokeping.db");
    $rules=$q->COUNT_ROWS("smokeping");
    $fulluri="s_PopUp('smokeping/smokeping.cgi?target=_charts','1024','800')";
	
	$html[]="<td style='width:99%;vertical-align:top'>";
	$html[]="<table style='width:100%'>";
	$html[]="<tr>";
	$html[]="<td style='padding-left:10px;padding-top:20px'>";
    $html[]="<table>";
    $html[]="<tr>";
    $html[]="<td style=''>";
    if($events>0) {
        $html[] = $tpl->widget_jaune("{lost_connections}", $tpl->FormatNumber($events));
    }else{
        $html[] = $tpl->widget_vert("{lost_connections}", "{none}");
    }
    $html[]="</td>";
    $html[]="<td style='padding-left:10px;'>";
    $html[] = $tpl->widget_vert("{monitoring_rules}", $tpl->FormatNumber($rules));
    $html[]="</td>";
    $html[]="</tr>";
    $html[]="</table>";
			

	$html[]="</td>";
	$html[]="</tr>";
    $html[]="<tr>";
    $html[]="<td style='padding-left:10px' colspan='2'>";
    $html[]=$tpl->button_autnonome("{statistics}",$fulluri,"fas fa-chart-area",null,679);
    $html[]="</td>";
    $html[]="</tr>";
	$html[]="</table>";
	$html[]="</td>";
	$html[]="</tr>";
	
	$html[]="</table>";
	echo $tpl->_ENGINE_parse_body(@implode("\n", $html));
	
}

function FormatNumber($number, $decimals = 0, $thousand_separator = '&nbsp;', $decimal_point = '.'){$tmp1 = round((float) $number, $decimals); while (($tmp2 = preg_replace('/(\d+)(\d\d\d)/', '\1 \2', $tmp1)) != $tmp1)$tmp1 = $tmp2; return strtr($tmp1, array(' ' => $thousand_separator, '.' => $decimal_point));}