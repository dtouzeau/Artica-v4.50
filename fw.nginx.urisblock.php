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
if(isset($_POST["FURL"])){rule_save();exit;}
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
    $sockngix->SET_INFO("FilterUris",$enable);
    $get_servicename=get_servicename($serviceid);
    $f[]=refresh_global_no_close($serviceid);
    $f[]="LoadAjaxSilent('furils-nginx-$serviceid','$page?top-buttons=$serviceid');";

    header("content-type: application/x-javascript");
    echo @implode(";",$f);
    return admin_tracks("Turn feature to $enable for deny User-Agents on  $get_servicename reverse-proxy site");

}

function service_js():bool{
    $serviceid  = intval($_GET["service-js"]);
    $tpl        = new template_admin();$tpl->CLUSTER_CLI=true;
    $page       = CurrentPageName();
    return $tpl->js_dialog4("{urls} {deny}","$page?popup-main=$serviceid");
}
function rule_js():bool{
    $serviceid  = intval($_GET["serviceid"]);
    $rule       = intval($_GET["rule-js"]);
    $tpl        = new template_admin();$tpl->CLUSTER_CLI=true;
    $page       = CurrentPageName();
    $title      = "{rule}: $rule";
    if($rule==0){$title="{new_rule}";}
    return $tpl->js_dialog5("{urls} {deny}: $title","$page?popup-rule=$rule&serviceid=$serviceid");
}
function compile_js_progress($ID,$final=null):string{
    return "Loadjs('fw.nginx.apply.php?serviceid=$ID&function=NgixSitesReload&addjs=');";

}

function rule_remove():bool{
    $md=$_GET["md"];
    $UserAgent=$_GET["pattern-remove"];
    $serviceid=intval($_GET["serviceid"]);
    $sock       = new socksngix($serviceid);
    $data       =$GLOBALS["CLASS_SOCKETS"]->unserializeb64($sock->GET_INFO("FUris"));
    unset($data[$UserAgent]);
    $encoded=serialize($data);
    $sock->SET_INFO("FUris",base64_encode($encoded));
    echo "$('#$md').remove();\n";
    echo refresh_global_no_close($serviceid);
    return true;

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
    $UserAgent=$_GET["pattern-enable"];
    $serviceid=intval($_GET["serviceid"]);
    $sock       = new socksngix($serviceid);
    $data       =$GLOBALS["CLASS_SOCKETS"]->unserializeb64($sock->GET_INFO("FUris"));
    if(intval($data[$UserAgent])==1){
        $data[$UserAgent]=0;
    }else{
        $data[$UserAgent]=1;
    }
    $encoded=serialize($data);
    $get_servicename=get_servicename($serviceid);
    $sock->SET_INFO("FUris",base64_encode($encoded));
    echo refresh_global_no_close($serviceid);
    return admin_tracks("Enable={$data[$UserAgent]} For reverse-proxy $get_servicename User-Agent rule $UserAgent");
}

function rule_popup():bool{
    $serviceid  = intval($_GET["serviceid"]);
    $ruleid     = intval($_GET["popup-rule"]);
    $tpl        = new template_admin();$tpl->CLUSTER_CLI=true;
    $bt="{add}";
    $form[]=$tpl->field_hidden("ruleid",$ruleid);
    $form[]=$tpl->field_hidden("serviceid",$serviceid);
    $form[]=$tpl->field_text("FURL","{url}","",true);
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
    $f[]="LoadAjax('main-popup-$serviceid','$page?popup-table=$serviceid');";
    $f[]="dialogInstance5.close();";
    return @implode(";",$f);
}

function rule_export_js(){
    $serviceid=intval($_GET["rule-export-js"]);
    $tpl        = new template_admin();$tpl->CLUSTER_CLI=true;
    $page       = CurrentPageName();
    $tpl->js_dialog6("{http_user_agent}: {export}/{import}","$page?rule-export-popup=$serviceid",950);
}

function rule_export_popup(){
    $tpl        = new template_admin();$tpl->CLUSTER_CLI=true;
    $page       = CurrentPageName();
    $serviceid=intval($_GET["rule-export-popup"]);
    $sock       = new socksngix($serviceid);
    $tpl->field_hidden("importid","$serviceid");
    $jsrestart="dialogInstance6.close();LoadAjax('main-popup-$serviceid','$page?popup-table=$serviceid');LoadAjax('nginx-options-$serviceid','fw.nginx.reverse-options.php?main=yes&service=$serviceid');";

    $form[]=$tpl->field_textarea("export", "{rules}", $sock->GET_INFO("FUris"),"664px");
    echo $tpl->form_outside("{export}/{import}", @implode("\n", $form),null,"{apply}",$jsrestart,null);
}
function rule_export_save(){
    $serviceid=intval($_POST["importid"]);
    $tpl        = new template_admin();$tpl->CLUSTER_CLI=true;
    $tpl->CLEAN_POST();
    $sock       = new socksngix($serviceid);
    $sock->SET_INFO("FUris",$_POST["export"]);
}

function rule_save():bool{
    $tpl        = new template_admin();$tpl->CLUSTER_CLI=true;
    $tpl->CLEAN_POST();
    $serviceid  = intval($_POST["serviceid"]);
    $sock       = new socksngix($serviceid);
    $data       =$GLOBALS["CLASS_SOCKETS"]->unserializeb64($sock->GET_INFO("FUris"));

    if(trim($_POST["FURL"])==null){return false;}
    $data[$_POST["FURL"]]=1;
    $encoded=serialize($data);
    $sock->SET_INFO("FUris",base64_encode($encoded));
    $get_servicename=get_servicename($serviceid);
    return admin_tracks("Add a new User-Agent {$_POST["FURL"]} to deny for reverse-proxy $get_servicename");

}

function popup_main():bool{
    $serviceid  = intval($_GET["popup-main"]);
    $page       = CurrentPageName();
    echo "<div id='main-popup-$serviceid'></div>
    <script>LoadAjax('main-popup-$serviceid','$page?popup-table=$serviceid')</script>";
    return true;
}

function DefaultArray():array{
    $f[]="/wp-content/plugins/secure-file-manager/vendor/elfinder/php/connector.minimal.php";
    $f[]="/wordpress/wp-includes/wlwmanifest.xml";
    $f[]="/wp-admin/install.php";
    $f[]="/wp-login.php";
    $f[]="/wp-content/plugins/blog-designer-pack/assets/js/post-vticker.min.js";
    $f[]="/wp-content/plugins/superstorefinder-wp/css/ssf-wp-pop.css";
    $f[]="/wp-head.php";
    $f[]="/wp-content/uploads/wpr-addons/forms/rytjo.php";
    $f[]="/wak.php";
    $f[]="/wp-content/uploads/workreap-temp/63.php";
    $f[]="/wp-admin/bumi.php";
    $f[]="/wp-content/uploads/wpr-addons/forms/z.php";
    $f[]="/info.php";
    $f[]="/config.json";
    $f[]="/debug/default/view";
    $f[]="/frontend_dev.php/$";
    $f[]="/.env";
    $f[]="/.env.development.local";
    $f[]="/.env.production";
    $f[]="/git/config";
    $f[]="/.git";
    $f[]="/wp-includes/images/include.php";
    $f[]="/feed/rss";
    $f[]="/buglist.cgi";
    $f[]="/plus/flink_add.php";
    $f[]="/plus/ad_js.php";
    $f[]="/include/ckeditor/plugins/smiley/images/angel_smile.gif";
    $f[]="/admin/login.php";
    $f[]="/login.php";
    $f[]="/login";
    $f[]="/wp-login.php";
    $f[]="/wp-22.php";
    $f[]="/wp-load.php";
    $f[]="/versions.php";
    $f[]="/cf_scripts/scripts/ajax/ckeditor/ckeditor.js";
    $f[]="/aaa9";
    $f[]="/woh.php";
    $f[]="/vendor/phpunit/phpunit/src/Util/PHP/eval-stdin.php";
    $f[]="/CSCOSSLC/config-auth";
    $f[]="/DeviceInformation";
    $f[]="/CGI/Java/Serviceability";
    $f[]="/sito/wp-includes/wlwmanifest.xml";
    $f[]="/cms/wp-includes/wlwmanifest.xml";
    $f[]="/site/wp-includes/wlwmanifest.xml";
    $f[]="/wp2/wp-includes/wlwmanifest.xml";
    $f[]="/media/wp-includes/wlwmanifest.xml";
    $f[]="/shop/wp-includes/wlwmanifest.xml";
    $f[]="/actuator/gateway/routes";
    $f[]="/boaform/admin/formLogin";
    $f[]="/admin/";
    $f[]="/vpn/index.html";
    $f[]="/wh/glass.php";
    $f[]="/jquery.js";
    $f[]="/new/login";
    $f[]="/Gmail/UnityPlayer.txt";
    $f[]="/UnityPlayer.dll";
    $f[]="/news.php";
    $f[]="/is-bin";
    $f[]="/wp-content/themes/twentytwentyone/inc/block-css.php";
    $f[]="/categories/Yud";
    $f[]="/jquery-3.3.1.min.js";
    $f[]="/home/main/getConfig.do";
    $f[]="/home/index/getkdata/intval/1day/type/btcusdt";
    $f[]="/Home/SendSMSCodeToPhoneFromAdmin";
    $f[]="/wp-admin/includes/themes.php";
    $f[]="/simple.php";
    $f[]="/wp-json/wp/v2/plugins/wp-console/wp-console/";
    $f[]="/wp-content/plugins/forminator/readme.txt";
    $f[]="/.well-knownold/";
    $f[]="/_css.php";
    $f[]="/wp-content/upgrade/";
    $f[]="/.well-known/traffic-advice";
    $f[]="/adminer-4.6.1.php";
    $f[]="/api/common/pool";




    foreach ($f as $item) {
         $array[$item] = 1;
    }


    return $array;
}

function popup_table():bool{
    $page       = CurrentPageName();
    $serviceid  = intval($_GET["popup-table"]);
    $tpl        = new template_admin();$tpl->CLUSTER_CLI=true;
    echo "<div id='furils-nginx-$serviceid' style='margin-bottom:10px'></div>";
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
    $tpl        = new template_admin();$tpl->CLUSTER_CLI=true;
    $page       = CurrentPageName();
    $sock       = new socksngix($serviceid);
    $FilterUris=intval($sock->GET_INFO("FilterUris"));
    if($FilterUris==1){
        $topbuttons[] = array("Loadjs('$page?enable-rule-js=0&serviceid=$serviceid')", ico_check, "{active2}");
    }else{
        $topbuttons[] = array("Loadjs('$page?enable-rule-js=1&serviceid=$serviceid')", ico_disabled, "{disabled}");

    }
    $topbuttons[] = array("Loadjs('$page?rule-js=0&serviceid=$serviceid')", ico_plus, "{new_rule}");

    $topbuttons[] = array("Loadjs('$page?OnlyActive=yes&serviceid=$serviceid&function=$function')", ico_filter, "{OnlyActive}");

    $topbuttons[] = array("Loadjs('$page?disableall=yes&serviceid=$serviceid&function=$function')", ico_disabled, "{disable_all}");
    $topbuttons[] = array("Loadjs('$page?enableall=yes&serviceid=$serviceid&function=$function')", ico_check, "{enable_all}");

    $topbuttons[] = array("Loadjs('$page?rule-export-js=$serviceid')", ico_import, "{export}/{import}");


    if(!isHarmpID()) {
        $compile_js_progress=compile_js_progress($serviceid);
        $topbuttons[] = array($compile_js_progress, ico_save, "{apply}");
    }
    echo $tpl->_ENGINE_parse_body($tpl->th_buttons($topbuttons));
    return true;
}

function rule_disable_all(){
    $function=$_GET["function"];
    $serviceid=$_GET["serviceid"];
    $sock       = new socksngix($serviceid);
    $data=unserialize(base64_decode($sock->GET_INFO("FUris")));
    foreach ($data as $UserAgent=>$none){
        $data[$UserAgent]=0;
    }
    $encoded=serialize($data);
    $get_servicename=get_servicename($serviceid);
    $sock->SET_INFO("FUris",base64_encode($encoded));
    echo refresh_global_no_close($serviceid);
    echo "$function();";
    return admin_tracks("Disable all user-Agent Deny For reverse-proxy $get_servicename");
}
function rule_enable_all(){
    $function=$_GET["function"];
    $serviceid=$_GET["serviceid"];
    $sock       = new socksngix($serviceid);
    $data=unserialize(base64_decode($sock->GET_INFO("FUris")));
    foreach ($data as $UserAgent=>$none){
        $data[$UserAgent]=1;
    }
    $encoded=serialize($data);
    $get_servicename=get_servicename($serviceid);
    $sock->SET_INFO("FUris",base64_encode($encoded));
    echo refresh_global_no_close($serviceid);
    echo "$function();";
    return admin_tracks("Enable all user-Agent Deny For reverse-proxy $get_servicename");
}
function popup_table2():bool{
    $serviceid  = intval($_GET["popup-table2"]);
    $tpl        = new template_admin();$tpl->CLUSTER_CLI=true;
    $page       = CurrentPageName();
    $sock       = new socksngix($serviceid);
    $function =$_GET["function"];
    $tableid    = time();
    $html[]="<div id='progress-compile-replace-$serviceid'></div>";
    $html[]="</div>";

    $search=$_GET["search"];

    $html[]="
<table class=\"table table-hover\" id='$tableid'>
	<thead>
    	<tr>
        	<th nowrap>{urls}</th>
        	<th nowrap>{enable}</small></th>
        	<th nowrap>{delete}</small></th>
        </tr>
  	</thead>
	<tbody>
";

    $data=unserialize(base64_decode($sock->GET_INFO("FUris")));
    if(!is_array($data)){
        $data=DefaultArray();
        $sock->SET_INFO("FUris",base64_encode(serialize($data)));
    }
    if(count($data)==0){
        $data=DefaultArray();
        $sock->SET_INFO("FUris",base64_encode(serialize($data)));
    }
    ksort($data);
    $c=0;

    $OnlyActive=false;
    $KeyActive=basename(__FILE__)."OnlyActive";
    if(isset($_SESSION[$KeyActive])){
        $OnlyActive=true;
    }

    foreach ($data as $pattern=>$enable){

        if($search<>null){
            $search=str_replace(".","\.",$search);
            $search=str_replace("*",".*?",$search);
            if(!preg_match("#$search#",$pattern)){
                continue;
            }
        }

        if($OnlyActive){
            if($enable==0){continue;}
        }

        $encoded=urlencode($pattern);
        if(strlen($pattern)>128){$pattern=substr($pattern,0,125)."...";}
        $pattern=htmlentities($pattern);
        $md=md5($pattern);

        $enable=$tpl->icon_check($enable,"Loadjs('$page?pattern-enable=$encoded&serviceid=$serviceid')","","AsWebMaster");
        $delete=$tpl->icon_delete("Loadjs('$page?pattern-remove=$encoded&serviceid=$serviceid&md=$md')","AsWebMaster");

        $c++;

        if($c>250){break;}

    $html[]="<tr id='$md'>
				<td width=100%>$pattern</td>
				<td width=1%  nowrap >$enable</td>
				<td width=1%  nowrap >$delete</td>
				</tr>";

    }

        $html[]="</tbody>";
        $html[]="</table>";
        $html[]="<script>NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS);
        $html[]="LoadAjax('furils-nginx-$serviceid','$page?top-buttons=$serviceid&function=$function');";
        $html[]="</script>";
        echo $tpl->_ENGINE_parse_body($html);
        return true;
        }