<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
include_once(dirname(__FILE__)."/ressources/class.nginx.params.inc");
include_once(dirname(__FILE__)."/ressources/class.nginx.templates.inc");


if(isset($_GET["service-js"])){service_js();exit;}
if(isset($_GET["popup-main"])){popup_main();exit;}
if(isset($_GET["popup-table"])){popup_table();exit;}
if(isset($_GET["popup-table2"])){popup_table2();exit;}
if(isset($_GET["pattern-remove"])){rule_remove();exit;}
if(isset($_GET["pattern-enable"])){rule_enable();exit;}
if(isset($_GET["rule-js"])){rule_js();exit;}
if(isset($_GET["popup-rule"])){rule_popup();exit;}
if(isset($_POST["ruleid"])){rule_save();exit;}
if(isset($_GET["enable-rule-js"])){enable_feature();}
if(isset($_GET["top-buttons"])){top_buttons();exit;}
if(isset($_GET["disableall"])){rule_disable_all();exit;}
if(isset($_GET["enableall"])){rule_enable_all();exit;}
if(isset($_GET["OnlyActive"])){OnlyActive();exit;}



function enable_feature():bool{
    $page       = CurrentPageName();
    $serviceid  = intval($_GET["serviceid"]);
    $enable=intval($_GET["enable-rule-js"]);
    $sockngix=new socksngix(($serviceid));
    $sockngix->SET_INFO("FilterUserAgents",$enable);
    $get_servicename=get_servicename($serviceid);
    $f[]=refresh_global_no_close($serviceid);
    $f[]="LoadAjaxSilent('includecache-nginx-$serviceid','$page?top-buttons=$serviceid');";
    header("content-type: application/x-javascript");
    echo @implode(";",$f);
    return admin_tracks("Turn feature to $enable for deny User-Agents on  $get_servicename reverse-proxy site");

}

function service_js():bool{
    $serviceid  = intval($_GET["service-js"]);
    $tpl        = new template_admin();
    $page       = CurrentPageName();
    return $tpl->js_dialog4("{include_cache}","$page?popup-main=$serviceid");
}
function rule_js():bool{
    $serviceid  = intval($_GET["serviceid"]);
    $rule       = intval($_GET["rule-js"]);
    $tpl        = new template_admin();
    $page       = CurrentPageName();
    $title      = "{rule}: $rule";
    if($rule==0){$title="{new_rule}";}
    $function=$_GET["function"];
    return $tpl->js_dialog5("{include_cache}: $title","$page?popup-rule=$rule&serviceid=$serviceid&function=$function");
}
function compile_js_progress($ID,$final=null):string{
    return "Loadjs('fw.nginx.apply.php?serviceid=$ID&function=NgixSitesReload&addjs=');";

}

function rule_remove():bool{
    $md=$_GET["md"];
    $ID=intval($_GET["pattern-remove"]);
    $serviceid=intval($_GET["serviceid"]);
    $q=new lib_sqlite(NginxGetDB());
    $ligne=$q->mysqli_fetch_array("SELECT pattern FROM enforce_cache WHERE ID=$ID");
    $pattern=$ligne["pattern"];
    $q->QUERY_SQL("DELETE FROM enforce_cache WHERE ID=$ID");
    $site=get_servicename($serviceid);
    echo "$('#$md').remove();\n";
    echo refresh_global_no_close($serviceid);
    return admin_tracks("Remove cache include rule $pattern from reverse-proxy site $site");

}
function get_servicename($ID):string{
    $ID=intval($ID);
    if($ID==0){return "Unknown";}
    $q                          = new lib_sqlite(NginxGetDB());
    $ligne=$q->mysqli_fetch_array("SELECT servicename FROM nginx_services WHERE ID=$ID");
    return strval($ligne["servicename"]);
}
function isHarmpID():bool{
    if(!isset($_SESSION["HARMPID"])){
        return false;
    }
    if(intval($_SESSION["HARMPID"])==0){
        return false;
    }

    return true;
}
function NginxGetDB():string{
    if(!isHarmpID()){
        return "/home/artica/SQLITE/nginx.db";
    }
    $Gpid=$_SESSION["HARMPID"];
    return "/home/artica/SQLITE/nginx.$Gpid.db";
}
function rule_enable():bool{
    $ID=intval($_GET["pattern-enable"]);
    $serviceid=intval($_GET["serviceid"]);

    $q=new lib_sqlite(NginxGetDB());
    $ligne=$q->mysqli_fetch_array("SELECT pattern,enabled FROM enforce_cache WHERE ID=$ID");
    $pattern=$ligne["pattern"];
    if(intval($ligne["enabled"])==1){
       $q->QUERY_SQL("UPDATE enforce_cache SET enabled=0 WHERE ID=$ID");
    }else{
        $q->QUERY_SQL("UPDATE enforce_cache SET enabled=1 WHERE ID=$ID");
    }
    $get_servicename=get_servicename($serviceid);
    echo refresh_global_no_close($serviceid);
    return admin_tracks("Enable/disable For reverse-proxy $get_servicename cache include rule $pattern");
}

function rule_popup():bool{
    $serviceid  = intval($_GET["serviceid"]);
    $ruleid     = intval($_GET["popup-rule"]);
    $tpl        = new template_admin();
    $bt="{add}";
    $q=new lib_sqlite(NginxGetDB());
    $ligne=$q->mysqli_fetch_array("SELECT * FROM enforce_cache WHERE ID=$ruleid");
    if($ruleid>0){
        $bt="{apply}";
    }
    if($ligne["maxmins"]==0){$ligne["maxmins"]=15;}
    $form[]=$tpl->field_hidden("ruleid",$ruleid);
    $form[]=$tpl->field_hidden("serviceid",$serviceid);
    $form[]=$tpl->field_numeric("maxmins","{child_ttl} ({minutes})",$ligne["maxmins"]);
    $form[]=$tpl->field_checkbox("regex","{regex}",$ligne["regex"]);
    $form[]=$tpl->field_text("pattern","{path}",$ligne["pattern"],true);
    $html[]=$tpl->form_outside(null,$form,null,$bt,refresh_global($serviceid),"AsWebMaster");
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}

function refresh_global_no_close($serviceid):string{
    $f[]="LoadAjax('www-parameters-$serviceid','fw.nginx.sites.php?www-parameters2=$serviceid');";
    return @implode(";",$f)."\n";

}

function refresh_global($serviceid):string{
    $page=CurrentPageName();
    $f[]=refresh_global_no_close($serviceid);
    if(isset($_GET["function"])){
        $function=$_GET["function"];
        if(strlen($function)>1){
            $f[]="$function();";
        }
    }
    $f[]="LoadAjax('main-popup-$serviceid','$page?popup-table=$serviceid');";
    $f[]="dialogInstance5.close();";
    return @implode(";",$f);
}

function rule_export_js(){
    $serviceid=intval($_GET["rule-export-js"]);
    $tpl        = new template_admin();
    $page       = CurrentPageName();
    $tpl->js_dialog6("{http_user_agent}: {export}/{import}","$page?rule-export-popup=$serviceid",950);
}

function rule_export_popup(){
    $tpl        = new template_admin();
    $page       = CurrentPageName();
    $serviceid=intval($_GET["rule-export-popup"]);
    $sock       = new socksngix($serviceid);
    $tpl->field_hidden("importid","$serviceid");
    $jsrestart="dialogInstance6.close();LoadAjax('main-popup-$serviceid','$page?popup-table=$serviceid');LoadAjax('nginx-options-$serviceid','fw.nginx.reverse-options.php?main=yes&service=$serviceid');";

    $form[]=$tpl->field_textarea("export", "{rules}", $sock->GET_INFO("FUserAgents"),"664px");
    echo $tpl->form_outside("{export}/{import}", @implode("\n", $form),null,"{apply}",$jsrestart,null);
}
function rule_export_save(){
    $serviceid=intval($_POST["importid"]);
    $tpl        = new template_admin();
    $tpl->CLEAN_POST();
    $sock       = new socksngix($serviceid);
    $sock->SET_INFO("FUserAgents",$_POST["export"]);
}

function rule_save():bool{
    $tpl        = new template_admin();
    $tpl->CLEAN_POST();
    $serviceid  = intval($_POST["serviceid"]);
    $ruleid=intval($_POST["ruleid"]);
    $pattern=trim($_POST["pattern"]);
    $regex=intval($_POST["regex"]);
    if($ruleid==0){

    if($serviceid==0){
        echo $tpl->post_error("No service ID!");
        return false;
    }

    $sql="INSERT INTO enforce_cache (serviceid,pattern,regex) VALUES ('$serviceid','$pattern','$regex');)";

    }else{
        $sql="UPDATE enforce_cache SET pattern='$pattern', regex='$regex' WHERE ID=$ruleid;";
    }

    $q=new lib_sqlite(NginxGetDB());
    $q->QUERY_SQL($sql);
    if(!$q->ok){
        echo $tpl->post_error($q->mysql_error);
        return false;
    }
    $get_servicename=get_servicename($serviceid);
    return admin_tracks("Add or edit cache exclusion rule {$_POST["pattern"]} for reverse-proxy $get_servicename");

}

function popup_main():bool{
    $q=new lib_sqlite(NginxGetDB());
    $q->QUERY_SQL("CREATE TABLE IF NOT EXISTS enforce_cache (ID INTEGER PRIMARY KEY AUTOINCREMENT,serviceid INTEGER NOT NULL DEFAULT 0, enabled INTEGER NOT NULL DEFAULT 1,pattern TEXT NOT NULL,regex INTEGER NOT NULL DEFAULT 1 )");

    $serviceid  = intval($_GET["popup-main"]);
    $page       = CurrentPageName();
    echo "<div id='main-popup-$serviceid'></div>
    <script>LoadAjax('main-popup-$serviceid','$page?popup-table=$serviceid')</script>";
    return true;
}
function popup_table():bool{
    $nginxsock=new socksngix(0);
    $nginxCachesDir=intval($nginxsock->GET_INFO("nginxCachesDir"));

    $page       = CurrentPageName();
    $serviceid  = intval($_GET["popup-table"]);
    $tpl        = new template_admin();
    echo "<div id='includecache-nginx-$serviceid' style='margin-bottom:10px;margin-top:10px'></div>";
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
    if($serviceid==0){
        echo $tpl->div_error("ServiceID === 0");
        return true;
    }

    $topbuttons[] = array("Loadjs('$page?rule-js=0&serviceid=$serviceid&function=$function')", ico_plus, "{new_rule}");
    $topbuttons[] = array("Loadjs('$page?OnlyActive=yes&serviceid=$serviceid&function=$function')", ico_filter, "{OnlyActive}");
    $topbuttons[] = array("Loadjs('$page?disableall=yes&serviceid=$serviceid&function=$function')", ico_disabled, "{disable_all}");
    $topbuttons[] = array("Loadjs('$page?enableall=yes&serviceid=$serviceid&function=$function')", ico_check, "{enable_all}");
   // $topbuttons[] = array("Loadjs('$page?rule-export-js=$serviceid')", ico_import, "{export}/{import}");


    if(!isHarmpID()) {
        $compile_js_progress=compile_js_progress($serviceid);
        $topbuttons[] = array($compile_js_progress, ico_save, "{apply}");
    }
    echo $tpl->_ENGINE_parse_body($tpl->th_buttons($topbuttons));
    return true;
}

function rule_disable_all():bool{
    $function=$_GET["function"];
    $serviceid=$_GET["serviceid"];

    $q=new lib_sqlite(NginxGetDB());
    $q->QUERY_SQL("UPDATE enforce_cache SET enabled=0 WHERE serviceid=$serviceid");
    $get_servicename=get_servicename($serviceid);
    echo refresh_global_no_close($serviceid);
    echo "$function();";
    return admin_tracks("Disable all cache include rules For reverse-proxy $get_servicename");
}
function rule_enable_all():bool{
    $function=$_GET["function"];
    $serviceid=$_GET["serviceid"];
    $q=new lib_sqlite(NginxGetDB());
    $q->QUERY_SQL("UPDATE enforce_cache SET enabled=1 WHERE serviceid=$serviceid");
    $get_servicename=get_servicename($serviceid);
    echo refresh_global_no_close($serviceid);
    echo "$function();";
    return admin_tracks("Enable all cache include rules For reverse-proxy $get_servicename");
}
function popup_table2():bool{
    $serviceid  = intval($_GET["popup-table2"]);
    $tpl        = new template_admin();
    $page       = CurrentPageName();
    $function =$_GET["function"];
    $searchQ="";
    $tableid    = time();
    $html[]="<div id='progress-compile-includecache-$serviceid'></div>";
    $html[]="</div>";

    $search=$_GET["search"];

    $html[]="
<table class=\"table table-hover\" id='$tableid'>
	<thead>
    	<tr>
    	    <th nowrap colspan='2'>{duration}</small></th>
        	<th nowrap>{paths}</th>
        	<th nowrap>{enable}</small></th>
        	<th nowrap>{delete}</small></th>
        </tr>
  	</thead>
	<tbody>
";


    $c=0;

    $OnlyActive=false;
    $KeyActive=basename(__FILE__)."OnlyActive";
    if(isset($_SESSION[$KeyActive])){
        $OnlyActive=true;
    }
    if($search<>null){
        $search="*$search*";
        $search=str_replace("**","*",$search);
        $search=str_replace("**","*",$search);
        $search=str_replace("*","%",$search);
        $searchQ=" AND pattern LIKE '$search'";
    }

    $q=new lib_sqlite(NginxGetDB());
    $searchE="";
    if($OnlyActive){
        $searchE=" AND enabled=1";
    }


    $query="SELECT * FROM enforce_cache WHERE serviceid=$serviceid$searchQ$searchE";

    $results=$q->QUERY_SQL($query);

    VERBOSE($query,__LINE__);

    if(!$q->ok){
        echo $tpl->div_error($q->mysql_error);
    }

    foreach ($results as $index=>$ligne){

        $ID=$ligne["ID"];
        $pattern=$ligne["pattern"];
        $enable=$ligne["enabled"];
        if(strlen($pattern)>128){$pattern=substr($pattern,0,125)."...";}
        $pattern=htmlentities($pattern);
        $md=md5(serialize($ligne));
        $regex=$ligne["regex"];

        $enable=$tpl->icon_check($enable,"Loadjs('$page?pattern-enable=$ID&serviceid=$serviceid')","","AsWebMaster");
        $delete=$tpl->icon_delete("Loadjs('$page?pattern-remove=$ID&serviceid=$serviceid&md=$md')","AsWebMaster");

        $c++;

        if($c>250){break;}

        $regex_span="";
        if($regex==1){
            $regex_span="<span class=\"label label-success\">regex</span>";
        }
        $icoset=$tpl->icon_parameters("Loadjs('$page?rule-js=$ID&serviceid=$serviceid&function=$function')","AsWebMaster");

        $maxmins=intval($ligne["maxmins"]);
        if($maxmins==0){$maxmins=15;}

    $html[]="<tr id='$md'>
                <td style='width:1%' nowrap>{$maxmins}mn</td>
                <td style='width:1%'  nowrap>$icoset</td>
				<td style='width:100%'><code style='font-size: 14px;font-weight: bold;'>$pattern</code>&nbsp;&nbsp;&nbsp;$regex_span</td>
				<td style='width:1%'  nowrap >$enable</td>
				<td style='width:1%'  nowrap >$delete</td>
				</tr>";

    }
        $html[]="</tbody>";
        $html[]="</table>";
        $html[]="<script>NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS);
        $html[]="LoadAjax('includecache-nginx-$serviceid','$page?top-buttons=$serviceid&function=$function');";
        $html[]="</script>";
        echo $tpl->_ENGINE_parse_body($html);
        return true;
        }