<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
include_once(dirname(__FILE__)."/ressources/class.nginx.params.inc");
include_once(dirname(__FILE__)."/ressources/class.nginx.templates.inc");
// awc_table_style() / awc_pager_js(): feuille de style et pager partages des tableaux d'entites
include_once(dirname(__FILE__)."/ressources/class.statistics_appliance.inc");


if(isset($_GET["service-js"])){service_js();exit;}
if(isset($_GET["popup-main"])){popup_main();exit;}
if(isset($_GET["popup-table"])){popup_table();exit;}
if(isset($_GET["popup-table2"])){popup_table2();exit;}
if(isset($_GET["pattern-remove"])){rule_remove();exit;}
if(isset($_GET["pattern-enable"])){rule_enable();exit;}
if(isset($_GET["rule-js"])){rule_js();exit;}
if(isset($_GET["popup-rule"])){rule_popup();exit;}
if(isset($_POST["ruleid"])){rule_save();exit;}
if(isset($_GET["top-buttons"])){top_buttons();exit;}
if(isset($_GET["disableall"])){rule_disable_all();exit;}
if(isset($_GET["enableall"])){rule_enable_all();exit;}
if(isset($_GET["OnlyActive"])){OnlyActive();exit;}

service_js();


function service_js():bool{
    $serviceid  = intval($_GET["serviceid"]);
    $tpl        = new template_admin();
    $page       = CurrentPageName();
    return $tpl->js_dialog4("{ban_clients}","$page?popup-main=$serviceid");
}
function rule_js():bool{
    $serviceid  = intval($_GET["serviceid"]);
    $rule       = intval($_GET["rule-js"]);
    $tpl        = new template_admin();
    $page       = CurrentPageName();
    $title      = "{rule}: $rule";
    if($rule==0){$title="{new_rule}";}
    return $tpl->js_dialog5("{ban_clients}: $title","$page?popup-rule=$rule&serviceid=$serviceid");
}
function compile_js_progress($ID,$final=null):string{
    return "Loadjs('fw.nginx.apply.php?serviceid=$ID&function=NgixSitesReload&addjs=');";

}

function rule_remove():bool{
    $md=$_GET["md"];
    $ID=intval($_GET["pattern-remove"]);
    $serviceid=intval($_GET["serviceid"]);
    $GLOBALS["CLASS_SOCKETS"]->REST_API_NGINX_POST_JSON("/nginx-db/exec", array("action"=>"delete","table"=>"nginx_clients_bans","where"=>array("ID"=>"$ID")));
    $get_servicename=get_servicename($serviceid);
    echo "$('#$md').remove();\n";
    echo refresh_global_no_close($serviceid);
    admin_tracks("Remove Client certificate ban rule $CLientName for reverse-proxy site $get_servicename");
    return ReloadService($serviceid);
}
function get_servicename($ID):string{
    $ID=intval($ID);
    if($ID==0){return "Unknown";}
    $sock=new socksngix($ID);
    return $sock->GetServiceName();
}


function rule_enable():bool{
    $ID=intval($_GET["pattern-enable"]);
    $ligne=fetch_clients_ban_by_id($ID);
    $enabled=isset($ligne["enabled"])?intval($ligne["enabled"]):0;
    $CLientName=isset($ligne["ClientName"])?$ligne["ClientName"]:"#$ID";
    $serviceid=isset($ligne["serviceid"])?intval($ligne["serviceid"]):0;

    $enabled=($enabled==0)?1:0;
    $GLOBALS["CLASS_SOCKETS"]->REST_API_NGINX_POST_JSON("/nginx-db/exec", array("action"=>"update","table"=>"nginx_clients_bans","set"=>array("enabled"=>"$enabled"),"where"=>array("ID"=>"$ID")));
    $get_servicename=get_servicename($serviceid);
    echo refresh_global_no_close($serviceid);
    admin_tracks("Enable=$enabled For reverse-proxy $get_servicename Client certificate ban rule $CLientName");
    return ReloadService($serviceid);
}
function servicesid_withcertificates():array{
    $array=array();
    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API_NGINX("/sites/list"),true);
    if(!is_array($json) || !isset($json["sites"])){return $array;}
    foreach($json["sites"] as $site){
        $serviceid=intval($site["ID"]);
        if($serviceid==0){continue;}
        $sock=new socksngix($serviceid);
        $zvalue=trim($sock->GET_INFO("ssl_client_certificate"));
        if(strlen($zvalue)==0){continue;}
        $array[$serviceid]=$sock->GetServiceName();
    }
    return $array;
}

function rule_popup():bool{
    $serviceid  = intval($_GET["serviceid"]);
    $ruleid     = intval($_GET["popup-rule"]);
    $tpl        = new template_admin();
    $bt="{add}";
    $ligne=array();

    if($ruleid>0) {
        $ligne=fetch_clients_ban_by_id($ruleid);
        $bt="{apply}";
    }
    $form[]=$tpl->field_hidden("ruleid",$ruleid);
    $Array=servicesid_withcertificates();
    $form[] = $tpl->field_array_hash($Array,"serviceid","{website}",$ligne["serviceid"]);
    $form[]=$tpl->field_text("ClientName","{client_name}",$ligne["ClientName"],true);
    $html[]=$tpl->form_outside(null,$form,null,$bt,refresh_global($serviceid),"AsWebMaster");

    echo $tpl->_ENGINE_parse_body($html);
    return true;
}
function rule_save():bool{
    $tpl        = new template_admin();
    $tpl->CLEAN_POST();
    $serviceid  = intval($_POST["serviceid"]);
    $ruleid=intval($_POST["ruleid"]);
    if(trim($_POST["ClientName"])==null){return false;}
    $ClientName=$_POST["ClientName"];
    $fields[]="serviceid";
    $fields[]="ClientName";
    $fields[]="enabled";
    $values[]=$serviceid;
    $values[]="'$ClientName'";
    $values[]=1;
    $edit[]="serviceid=$serviceid";
    $edit[]="ClientName='$ClientName'";
    if($ruleid==0){
        $GLOBALS["CLASS_SOCKETS"]->REST_API_NGINX_POST_JSON("/nginx-db/exec", [
            "action"=>"insert","table"=>"nginx_clients_bans",
            "values"=>["serviceid"=>$serviceid,"ClientName"=>$ClientName,"enabled"=>1]
        ]);
    }else{
        $GLOBALS["CLASS_SOCKETS"]->REST_API_NGINX_POST_JSON("/nginx-db/exec", [
            "action"=>"update","table"=>"nginx_clients_bans",
            "set"=>["serviceid"=>$serviceid,"ClientName"=>$ClientName],"where"=>["ID"=>$ruleid]
        ]);
    }
    $get_servicename=get_servicename($serviceid);

    ReloadService($serviceid);
    return admin_tracks("Ban a client certificate $ClientName for reverse-proxy for website $get_servicename");


}

function ReloadService($serviceid):bool{
    if ($serviceid>0){
        $GLOBALS["CLASS_SOCKETS"]->REST_API_NGINX("/reverse-proxy/singlehup/$serviceid");
    }else{
        $GLOBALS["CLASS_SOCKETS"]->REST_API_NGINX("/reverse-proxy/hupreconfigure");
    }
    return true;
}

function refresh_global_no_close($serviceid):string{
    $f[]="LoadAjax('www-parameters-$serviceid','fw.nginx.sites.php?www-parameters2=$serviceid');";
    return @implode(";",$f)."\n";

}

function refresh_global($serviceid):string{
    $page=CurrentPageName();
    $f[]=refresh_global_no_close($serviceid);
    $f[]="LoadAjax('main-popup-$serviceid','$page?popup-table=$serviceid');";
    $f[]="dialogInstance5.close();";
    return @implode(";",$f);
}

function popup_main():bool{
    $serviceid  = intval($_GET["popup-main"]);
    $page       = CurrentPageName();
    echo "<div id='main-popup-$serviceid'></div>
    <script>LoadAjax('main-popup-$serviceid','$page?popup-table=$serviceid')</script>";
    return true;
}



function popup_table():bool{
    $page       = CurrentPageName();
    $serviceid  = intval($_GET["popup-table"]);
    $tpl        = new template_admin();
    echo "<div id='banclients-nginx-$serviceid' style='margin-bottom:10px'></div>";
    echo $tpl->search_block($page,null,null,null,"&popup-table2=$serviceid");

    return true;
}
function OnlyActive():bool{
    $function=$_GET["function"];
    $Key=basename(__FILE__)."OnlyActive";
    if(!isset($_SESSION[$Key])){
        $_SESSION[$Key]=true;
    }else{
        unset($_SESSION[$Key]);
    }
    header("content-type: application/x-javascript");
    echo "$function();";
    return true;
}

function top_buttons():bool{
    $serviceid  = intval($_GET["top-buttons"]);
    $function=$_GET["function"];
    $tpl        = new template_admin();
    $page       = CurrentPageName();
    $topbuttons[] = array("Loadjs('$page?rule-js=0&serviceid=$serviceid&function=$function')", ico_plus, "{new_rule}");
    $topbuttons[] = array("Loadjs('$page?OnlyActive=yes&serviceid=$serviceid&function=$function')", ico_filter, "{OnlyActive}");
   // $topbuttons[] = array("Loadjs('$page?disableall=yes&serviceid=$serviceid&function=$function')", ico_disabled, "{disable_all}");
   // $topbuttons[] = array("Loadjs('$page?enableall=yes&serviceid=$serviceid&function=$function')", ico_check, "{enable_all}");

    if(!isHarmpID()) {
        if($serviceid>0) {
            $compile_js_progress = compile_js_progress($serviceid);
            $topbuttons[] = array($compile_js_progress, ico_save, "{apply}");
        }
    }
    echo $tpl->_ENGINE_parse_body($tpl->th_buttons($topbuttons));
    return true;
}

function rule_disable_all(): bool{
    $function=$_GET["function"];
    $serviceid=$_GET["serviceid"];
    $sock       = new socksngix($serviceid);
    $data=unserialize(base64_decode($sock->GET_INFO("FUserAgents")));
    foreach ($data as $UserAgent=>$none){
        $data[$UserAgent]=0;
    }
    $encoded=serialize($data);
    $get_servicename=get_servicename($serviceid);
    $sock->SET_INFO("FUserAgents",base64_encode($encoded));
    echo refresh_global_no_close($serviceid);
    echo "$function();";
    return admin_tracks("Disable all user-Agent Deny For reverse-proxy $get_servicename");
}
function rule_enable_all(): bool{
    $function=$_GET["function"];
    $serviceid=$_GET["serviceid"];
    $sock       = new socksngix($serviceid);
    $data=unserialize(base64_decode($sock->GET_INFO("FUserAgents")));
    foreach ($data as $UserAgent=>$none){
        $data[$UserAgent]=1;
    }
    $encoded=serialize($data);
    $get_servicename=get_servicename($serviceid);
    $sock->SET_INFO("FUserAgents",base64_encode($encoded));
    echo refresh_global_no_close($serviceid);
    echo "$function();";
    return admin_tracks("Enable all user-Agent Deny For reverse-proxy $get_servicename");
}
function popup_table2():bool{
    $serviceid  = intval($_GET["popup-table2"]);
    $tpl        = new template_admin();
    $page       = CurrentPageName();
    $function =$_GET["function"];
    $tableid    = time();
    $html[]="<div id='progress-compile-replace-$serviceid'></div>";
    $html[]="</div>";

    $search=$_GET["search"];

    $html[]="
<div class='awc-wrap'><div class='awc-card'><div class='awc-scroll'>
<table class='awc-tbl awc-tbl-compact'>
	<thead>
    	<tr>
        	<th nowrap colspan='2'>{clients}</th>
        	<th nowrap>{website}</th>
        	<th nowrap>{enable}</th>
        	<th nowrap>{delete}</th>
        </tr>
  	</thead>
	<tbody>
";
    $results=fetch_clients_bans_search($serviceid,$search);

    $c=0;

    $OnlyActive=false;
    $KeyActive=basename(__FILE__)."OnlyActive";
    if(isset($_SESSION[$KeyActive])){
        $OnlyActive=true;
    }

    foreach ($results as $index=>$ligne){
        $enable=$ligne["enabled"];
        $ID=$ligne["ID"];
        $ClientName=$ligne["ClientName"];
        if($OnlyActive){
            if($enable==0){continue;}
        }

        $md=md5(serialize($ligne));
        $enable=$tpl->icon_check($enable,"Loadjs('$page?pattern-enable=$ID&serviceid=$serviceid')","","AsWebMaster");
        $delete=$tpl->icon_delete("Loadjs('$page?pattern-remove=$ID&serviceid=$serviceid&md=$md')","AsWebMaster");

        // Pentest 2026-11-04 : nom du site (donnee stockee) affiche sans encodage (XSS stockee).
        $service=get_servicename($ligne["serviceid"]);
        $ClientName=$tpl->td_href($ClientName,"","Loadjs('$page?rule-js=$ID&serviceid=$serviceid&function=$function')");

    $html[]="<tr id='$md'>
                <td style='width:1%' nowrap><i class='".ico_user." fa-2x'></i></td>
				<td style='width:100%;font-size:18px'>$ClientName</td>
				<td style='width:1%;font-size:18px' nowrap >".req_html($service)."</td>
				<td style='width:1%' nowrap>$enable</td>
				<td style='width:1%' nowrap>$delete</td>
				</tr>";

    }

        $html[]="</tbody></table></div></div></div>";
        $html[]="<script>NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS);
        $html[]="LoadAjax('banclients-nginx-$serviceid','$page?top-buttons=$serviceid&function=$function');";
        $html[]="</script>";
        echo awc_table_style().$tpl->_ENGINE_parse_body($html);
        return true;
        }