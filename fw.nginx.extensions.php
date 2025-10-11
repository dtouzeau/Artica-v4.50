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
    $sockngix->SET_INFO("LimitExtensions",$enable);
    $get_servicename=get_servicename($serviceid);
    $f[]=refresh_global_no_close($serviceid);
    $f[]="LoadAjaxSilent('fexts-nginx-$serviceid','$page?top-buttons=$serviceid');";

    header("content-type: application/x-javascript");
    echo @implode(";",$f);
    return admin_tracks("Turn feature to $enable for deny User-Agents on  $get_servicename reverse-proxy site");

}

function service_js():bool{
    $serviceid  = intval($_GET["service-js"]);
    $tpl        = new template_admin();$tpl->CLUSTER_CLI=true;
    $page       = CurrentPageName();
    return $tpl->js_dialog4("{allowed_extensions}","$page?popup-main=$serviceid",650);
}
function rule_js():bool{
    $serviceid  = intval($_GET["serviceid"]);
    $rule       = intval($_GET["rule-js"]);
    $tpl        = new template_admin();$tpl->CLUSTER_CLI=true;
    $page       = CurrentPageName();
    $title      = "{rule}: $rule";
    if($rule==0){$title="{new_rule}";}
    return $tpl->js_dialog5("{allowed_extensions}: $title","$page?popup-rule=$rule&serviceid=$serviceid");
}
function compile_js_progress($ID):string{
    return "Loadjs('fw.nginx.apply.php?serviceid=$ID&function=NgixSitesReload&addjs=');";

}

function rule_remove():bool{
    $md=$_GET["md"];
    $UserAgent=$_GET["pattern-remove"];
    $serviceid=intval($_GET["serviceid"]);
    $sock       = new socksngix($serviceid);
    $data       = $GLOBALS["CLASS_SOCKETS"]->unserializeb64($sock->GET_INFO("AllowedExtensions"));
    unset($data[$UserAgent]);
    $encoded=serialize($data);
    $sock->SET_INFO("AllowedExtensions",base64_encode($encoded));
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
    $data       =$GLOBALS["CLASS_SOCKETS"]->unserializeb64($sock->GET_INFO("AllowedExtensions"));
    if(intval($data[$UserAgent])==1){
        $data[$UserAgent]=0;
    }else{
        $data[$UserAgent]=1;
    }
    $encoded=serialize($data);
    $get_servicename=get_servicename($serviceid);
    $sock->SET_INFO("AllowedExtensions",base64_encode($encoded));
    echo refresh_global_no_close($serviceid);
    return admin_tracks("Enable=$data[$UserAgent] For reverse-proxy $get_servicename User-Agent rule $UserAgent");
}

function rule_popup():bool{
    $serviceid  = intval($_GET["serviceid"]);
    $ruleid     = intval($_GET["popup-rule"]);
    $tpl        = new template_admin();$tpl->CLUSTER_CLI=true;
    $bt="{add}";
    $form[]=$tpl->field_hidden("ruleid",$ruleid);
    $form[]=$tpl->field_hidden("serviceid",$serviceid);
    $form[]=$tpl->field_text("FURL","{extension}","",true);
    $html[]=$tpl->form_outside(null,$form,"{allowed_extensions_explain}<br>{example}:<strong>*.php</strong><br><strong>*.php,*.php3</strong><br><strong>php</strong>",$bt,refresh_global($serviceid),"AsWebMaster");
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

function rule_export_js():bool{
    $serviceid=intval($_GET["rule-export-js"]);
    $tpl        = new template_admin();$tpl->CLUSTER_CLI=true;
    $page       = CurrentPageName();
    return $tpl->js_dialog6("{allowed_extensions}: {export}/{import}","$page?rule-export-popup=$serviceid",950);
}

function rule_export_popup():bool{
    $tpl        = new template_admin();$tpl->CLUSTER_CLI=true;
    $page       = CurrentPageName();
    $serviceid=intval($_GET["rule-export-popup"]);
    $sock       = new socksngix($serviceid);
    $tpl->field_hidden("importid","$serviceid");
    $jsrestart="dialogInstance6.close();LoadAjax('main-popup-$serviceid','$page?popup-table=$serviceid');LoadAjax('nginx-options-$serviceid','fw.nginx.reverse-options.php?main=yes&service=$serviceid');";

    $f=array();
    $data=unserialize(base64_decode($sock->GET_INFO("AllowedExtensions")));
    foreach($data as $k=>$v){
        if($v==0){
            $f[]="# $k";
            continue;
        }
        $f[]=$k;
    }

    $form[]=$tpl->field_textarea("export", "{rules}", @implode("\n",$f),"664px");
    echo $tpl->form_outside("{export}/{import}", @implode("\n", $form),null,"{apply}",$jsrestart,null);
    return true;
}
function rule_export_save():bool{
    $serviceid=intval($_POST["importid"]);
    $get_servicename=get_servicename($serviceid);
    $tpl        = new template_admin();$tpl->CLUSTER_CLI=true;
    $tpl->CLEAN_POST();
    $sock       = new socksngix($serviceid);

    $ARR=array();
    $tb=explode("\n",$_POST["export"]);
    foreach($tb as $t){
        $t=trim($t);
        $val=1;

        if(preg_match("#^\#(.+)#",$t,$m)){
            $val=0;
            $t=$m[1];
        }

        $t=CleanEx($t);
        if($t==""){continue;}
        $ARR[$t]=$val;
    }
    $count=count($ARR);
    $Base=base64_encode(serialize($ARR));
    $sock->SET_INFO("AllowedExtensions",$Base);
    return admin_tracks_post("Import $count allowed extensions for $get_servicename in reverse-proxy");
}
function CleanEx($val):string{
    $val=trim($val);
    $val=strtolower($val);
    $val=replace_accents($val);

    $val=str_replace("*.","",$val);
    if(preg_match("#^\.(.+)$#",$val,$m)){
        $val=$m[1];
    }
    if(detectSpecialCharacters($val)){
        return "";
    }
    return $val;
}
function detectSpecialCharacters($input) {
    $specialCharacters = ['[', ']', '(', ')', '+', '"', '~', '&', "'", '|', '\\', '/', ':', '!', '§', ',', '*', '£', '}', '{', '#', '²', '-',"=",">","<","?"];


    foreach ($specialCharacters as $char) {
        if (strpos($input, $char) !== false) {
            return true; // Special character detected
        }
    }
    return false; // No special characters detected
}
function rule_save():bool{
    $tpl        = new template_admin();$tpl->CLUSTER_CLI=true;
    $tpl->CLEAN_POST();
    $serviceid  = intval($_POST["serviceid"]);
    $sock       = new socksngix($serviceid);
    $data       =$GLOBALS["CLASS_SOCKETS"]->unserializeb64($sock->GET_INFO("AllowedExtensions"));

    if(trim($_POST["FURL"])==null){return false;}

    $posted=$_POST["FURL"].",";
    $posted=str_replace(";",",",$posted);
    $posted=str_replace(" ",",",$posted);
    $tb=explode(",",$posted);
    foreach($tb as $t){
        $t=CleanEx($t);
        if($t==""){continue;}
        $data[$t]=1;
    }
    $encoded=serialize($data);
    $sock->SET_INFO("AllowedExtensions",base64_encode($encoded));
    $get_servicename=get_servicename($serviceid);
    return admin_tracks("Add a new allowed extensions {$_POST["FURL"]} for reverse-proxy $get_servicename");

}

function popup_main():bool{
    $serviceid  = intval($_GET["popup-main"]);
    $page       = CurrentPageName();
    echo "<div id='main-popup-$serviceid'></div>
    <script>LoadAjax('main-popup-$serviceid','$page?popup-table=$serviceid')</script>";
    return true;
}

function DefaultArray():array{
        $f= array("html","htm","css","js","jpg","jpeg","png","gif","svg","ico","webp","txt","pdf","php","asp","aspx","jsp","py","rb","pl","xml","json","ejs","pug","twig","hbs","mustache","mp3","mp4","webm","ogg","wav","avi","mov","zip","tar","gz","rar","7z","dbf","exe","woff","woff2","ttf","otf");

    foreach ($f as $item) {
         $array[$item] = 1;
    }


    return $array;
}

function popup_table():bool{
    $page       = CurrentPageName();
    $serviceid  = intval($_GET["popup-table"]);
    $tpl        = new template_admin();$tpl->CLUSTER_CLI=true;
    echo "<div id='fexts-nginx-$serviceid' style='margin-bottom:10px'></div>";
    echo $tpl->search_block($page,null,null,null,"&popup-table2=$serviceid");

    return true;
}
function OnlyActive():bool{
    $function="";
    if(isset($_GET["function"])){
        $function=$_GET["function"];
    }
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
    $LimitExtensions=intval($sock->GET_INFO("LimitExtensions"));
    if($LimitExtensions==1){
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

function rule_disable_all():bool{
    $function=$_GET["function"];
    $serviceid=$_GET["serviceid"];
    $sock       = new socksngix($serviceid);
    $data=unserialize(base64_decode($sock->GET_INFO("AllowedExtensions")));
    foreach ($data as $UserAgent=>$none){
        $data[$UserAgent]=0;
    }
    $encoded=serialize($data);
    $get_servicename=get_servicename($serviceid);
    $sock->SET_INFO("AllowedExtensions",base64_encode($encoded));
    echo refresh_global_no_close($serviceid);
    echo "$function();";
    return admin_tracks("Disable all allowed extensions for reverse-proxy $get_servicename");
}
function rule_enable_all():bool{
    $function=$_GET["function"];
    $serviceid=$_GET["serviceid"];
    $sock       = new socksngix($serviceid);
    $data=unserialize(base64_decode($sock->GET_INFO("AllowedExtensions")));
    foreach ($data as $UserAgent=>$none){
        $data[$UserAgent]=1;
    }
    $encoded=serialize($data);
    $get_servicename=get_servicename($serviceid);
    $sock->SET_INFO("AllowedExtensions",base64_encode($encoded));
    echo refresh_global_no_close($serviceid);
    echo "$function();";
    return admin_tracks("Enable all allowed extensions For reverse-proxy $get_servicename");
}
function popup_table2():bool{
    $serviceid  = intval($_GET["popup-table2"]);
    $tpl        = new template_admin();$tpl->CLUSTER_CLI=true;
    $page       = CurrentPageName();
    $sock       = new socksngix($serviceid);
    $function =$_GET["function"];
    $tableid    = time();
    $html[]="<div id='progress-compile-allexts-$serviceid'></div>";
    $html[]="</div>";

    $search=$_GET["search"];

    $html[]="
<table class=\"table table-hover\" id='$tableid'>
	<thead>
    	<tr>
        	<th nowrap>{extensions}</th>
        	<th nowrap>{enable}</small></th>
        	<th nowrap>{delete}</small></th>
        </tr>
  	</thead>
	<tbody>
";

    $data=$GLOBALS["CLASS_SOCKETS"]->unserializeb64($sock->GET_INFO("AllowedExtensions"));
    if(!is_array($data)){
        $data=DefaultArray();
        $sock->SET_INFO("AllowedExtensions",base64_encode(serialize($data)));
    }
    if(count($data)==0){
        $data=DefaultArray();
        $sock->SET_INFO("AllowedExtensions",base64_encode(serialize($data)));
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
				<td style='width: 100%'>$pattern</td>
				<td style='width:1%'  nowrap >$enable</td>
				<td style='width:1%' nowrap >$delete</td>
				</tr>";

    }

        $html[]="</tbody>";
        $html[]="</table>";
        $html[]="<script>NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS);
        $html[]="LoadAjax('fexts-nginx-$serviceid','$page?top-buttons=$serviceid&function=$function');";
        $html[]="</script>";
        echo $tpl->_ENGINE_parse_body($html);
        return true;
        }