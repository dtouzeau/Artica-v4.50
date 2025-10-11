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
if(isset($_POST["method"])){rule_save();exit;}
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
    $sockngix->SET_INFO("FilterPermPolicy",$enable);
    $get_servicename=get_servicename($serviceid);
    $f[]=refresh_global_no_close($serviceid);
    $f[]="LoadAjaxSilent('PermPolicy-nginx-$serviceid','$page?top-buttons=$serviceid');";

    header("content-type: application/x-javascript");
    echo @implode(";",$f);
    return admin_tracks("Turn feature to $enable for deny User-Agents on  $get_servicename reverse-proxy site");

}

function service_js():bool{
    $serviceid  = intval($_GET["service-js"]);
    $tpl        = new template_admin();
    $page       = CurrentPageName();
    return $tpl->js_dialog4("Permissions Policy","$page?popup-main=$serviceid");
}
function rule_js():bool{
    $serviceid  = intval($_GET["serviceid"]);
    $rule       = trim($_GET["rule-js"]);
    if($rule==0){$rule="";}
    $tpl        = new template_admin();
    $page       = CurrentPageName();
    $title      = "{rule}: $rule";
    if($rule==""){$title="{new_rule}";}
    return $tpl->js_dialog5("Permissions Policy: $title","$page?popup-rule=$rule&serviceid=$serviceid",500);
}
function compile_js_progress($ID,$final=null):string{
    return "Loadjs('fw.nginx.apply.php?serviceid=$ID&function=NgixSitesReload&addjs=');";

}

function rule_remove():bool{
    $md=$_GET["md"];
    $UserAgent=$_GET["pattern-remove"];
    $serviceid=intval($_GET["serviceid"]);
    $sock       = new socksngix($serviceid);
    $data       = unserialize(base64_decode($sock->GET_INFO("FPermPolicy")));
    unset($data[$UserAgent]);
    $encoded=serialize($data);
    $sock->SET_INFO("FPermPolicy",base64_encode($encoded));
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
    $data       = unserialize(base64_decode($sock->GET_INFO("FPermPolicy")));
    if(intval($data[$UserAgent])==1){
        $data[$UserAgent]=0;
    }else{
        $data[$UserAgent]=1;
    }
    $encoded=serialize($data);
    $get_servicename=get_servicename($serviceid);
    $sock->SET_INFO("FPermPolicy",base64_encode($encoded));
    echo refresh_global_no_close($serviceid);
    return admin_tracks("Enable={$data[$UserAgent]} For reverse-proxy $get_servicename User-Agent rule $UserAgent");
}

function rule_popup():bool{
    $serviceid  = intval($_GET["serviceid"]);
    $rulestring     = trim($_GET["popup-rule"]);
    $tpl        = new template_admin();
    $bt="{add}";
    $form[]=$tpl->field_hidden("ruleid",$rulestring);
    $form[]=$tpl->field_hidden("serviceid",$serviceid);

    $tooltips["deny"]="{deny}";
    $tooltips["allow"]="{allow} {all}";
    $tooltips["self"]="{allow} {website}";
    $tips="deny";
    $Key="";
    if(strlen($rulestring)>5) {
        if (preg_match("#^(.+?):(.+)#", $rulestring, $re)) {
            $tips = $re[1];
            $Key = $re[2];
        }
        $bt="{apply}";
    }

    $form[]=$tpl->field_array_hash($tooltips,"method","nonull:{rule}",$tips,true);
    $form[]=$tpl->field_array_hash(DefaultArray(true),"key","nonull:{field}",$Key,true);
    $html[]=$tpl->form_outside(null,$form,null,$bt,refresh_global($serviceid),"AsWebMaster");
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}
function rule_save():bool{
    $tpl        = new template_admin();
    $tpl->CLEAN_POST();
    $serviceid  = intval($_POST["serviceid"]);
    $sock       = new socksngix($serviceid);
    $data       = unserialize(base64_decode($sock->GET_INFO("FPermPolicy")));

    $ruleid=trim($_POST["ruleid"]);
    if(trim($_POST["method"])==null){return false;}
    if(trim($_POST["key"])==null){return false;}
    $newrule=$_POST["method"].":".$_POST["key"];


    unset($data[$ruleid]);
    unset($data[$newrule]);
    $data[$newrule]=1;

    $encoded=serialize($data);
    $sock->SET_INFO("FPermPolicy",base64_encode($encoded));
    $get_servicename=get_servicename($serviceid);
    return admin_tracks("Add a new User-Agent {$_POST["UserAgent"]} to deny for reverse-proxy $get_servicename");

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

    $form[]=$tpl->field_textarea("export", "{rules}", $sock->GET_INFO("FPermPolicy"),"664px");
    echo $tpl->form_outside("{export}/{import}", @implode("\n", $form),null,"{apply}",$jsrestart,null);
}
function rule_export_save(){
    $serviceid=intval($_POST["importid"]);
    $tpl        = new template_admin();
    $tpl->CLEAN_POST();
    $sock       = new socksngix($serviceid);
    $sock->SET_INFO("FPermPolicy",$_POST["export"]);
}



function popup_main():bool{
    $serviceid  = intval($_GET["popup-main"]);
    $page       = CurrentPageName();
    echo "<div id='main-popup-$serviceid'></div>
    <script>LoadAjax('main-popup-$serviceid','$page?popup-table=$serviceid')</script>";
    return true;
}

function DefaultArray($Field=false):array{


    $Def[]="microphone";
    $Def[]="camera";
    $Def[]="battery";
    $Def[]="gamepad";
    $Def[]="usb";

    $disabled="accelerometer,ambient-light-sensor,display-capture,encrypted-media,execution-while-not-rendered,execution-while-out-of-viewport,gyroscope,hid,idle-detection,local-fonts,magnetometer,midi,otp-credentials,picture-in-picture,publickey-credentials-create,publickey-credentials-get,screen-wake-lock,serial,storage-access,web-share,xr-spatial-tracking";

    $self[]="geolocation";
    $self[]="fullscreen";
    $self[]="speaker-selection";
    $self[]="identity-credentials-get";

    $allow[]="payment";
    $allow[]="autoplay";

    foreach ($allow as $item) {
        $Fields[$item]=$item;
        $array["allow:$item"] = 1;
    }

    foreach ($self as $item) {
        $Fields[$item]=$item;
        $array["self:$item"] = 1;
    }

    foreach ($Def as $item) {
        $Fields[$item]=$item;
         $array["deny:$item"] = 1;
    }
    $tb=explode(",",$disabled);
    foreach ($tb as $item) {
        $Fields[$item]=$item;
        $array["deny:$item"] = 0;
    }

    if($Field){
        return $Fields;
    }

    return $array;
}

function popup_table():bool{
    $page       = CurrentPageName();
    $serviceid  = intval($_GET["popup-table"]);
    $tpl        = new template_admin();
    echo "<div id='PermPolicy-nginx-$serviceid' style='margin-bottom:10px'></div>";
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
    $sock       = new socksngix($serviceid);
    $FilterPermPolicy=intval($sock->GET_INFO("FilterPermPolicy"));
    if($FilterPermPolicy==1){
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
    $data=unserialize(base64_decode($sock->GET_INFO("FPermPolicy")));
    foreach ($data as $UserAgent=>$none){
        $data[$UserAgent]=0;
    }
    $encoded=serialize($data);
    $get_servicename=get_servicename($serviceid);
    $sock->SET_INFO("FPermPolicy",base64_encode($encoded));
    echo refresh_global_no_close($serviceid);
    echo "$function();";
    return admin_tracks("Disable all Permissions Policy Headers For reverse-proxy $get_servicename");
}
function rule_enable_all():bool{
    $function=$_GET["function"];
    $serviceid=$_GET["serviceid"];
    $sock       = new socksngix($serviceid);
    $data=unserialize(base64_decode($sock->GET_INFO("FPermPolicy")));
    foreach ($data as $UserAgent=>$none){
        $data[$UserAgent]=1;
    }
    $encoded=serialize($data);
    $get_servicename=get_servicename($serviceid);
    $sock->SET_INFO("FPermPolicy",base64_encode($encoded));
    echo refresh_global_no_close($serviceid);
    echo "$function();";
    return admin_tracks("Enable all Permissions Policy Headers For reverse-proxy $get_servicename");
}
function popup_table2():bool{
    $serviceid  = intval($_GET["popup-table2"]);
    $tpl        = new template_admin();
    $page       = CurrentPageName();
    $sock       = new socksngix($serviceid);
    $function =$_GET["function"];
    $tableid    = time();
    $html[]="<div id='progress-compile-replace-$serviceid'></div>";
    $html[]="</div>";

    $search=$_GET["search"];
    $OnlyActiveText="";
    $OnlyActive=false;
    $KeyActive=basename(__FILE__)."OnlyActive";
    if(isset($_SESSION[$KeyActive])){
        $OnlyActive=true;
        $OnlyActiveText="&nbsp;({OnlyActive})";
    }

    $html[]="
<table class=\"table table-hover\" id='$tableid'>
	<thead>
    	<tr>
    	    <th nowrap>{status}</th>
        	<th nowrap>{policy}$OnlyActiveText</th>
        	<th nowrap>{enable}</small></th>
        	<th nowrap>{delete}</small></th>
        </tr>
  	</thead>
	<tbody>
";

    $data=unserialize(base64_decode($sock->GET_INFO("FPermPolicy")));
    if(!is_array($data)){
        $data=DefaultArray();
        $sock->SET_INFO("FPermPolicy",base64_encode(serialize($data)));
    }
    if(count($data)==0){
        $data=DefaultArray();
        $sock->SET_INFO("FPermPolicy",base64_encode(serialize($data)));
    }
    ksort($data);
    $c=0;



    $tooltips["deny"]="<span class='label label-danger'>{deny}</span>";
    $tooltips["allow"]="<span class='label label-primary'>{allow} {all}</span>";
    $tooltips["self"]="<span class='label label-info'>{allow} {website}</span>";

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

        if(!preg_match("#^(.+?):(.+)#",$pattern,$re)){
            continue;
        }
        $status=$tooltips[$re[1]];
        $policy=$re[2];
        $md=md5($pattern);

        $enable=$tpl->icon_check($enable,"Loadjs('$page?pattern-enable=$encoded&serviceid=$serviceid')","","AsWebMaster");
        $delete=$tpl->icon_delete("Loadjs('$page?pattern-remove=$encoded&serviceid=$serviceid&md=$md')","AsWebMaster");

        $c++;

        $pattern=$tpl->td_href($policy,null,"Loadjs('$page?rule-js=$encoded&serviceid=$serviceid&md=$md')");

        if($c>250){break;}

    $html[]="<tr id='$md'>
                <td width=1%  nowrap >$status</td>
				<td width=100%>$pattern</td>
				<td width=1%  nowrap >$enable</td>
				<td width=1%  nowrap >$delete</td>
				</tr>";

    }

        $html[]="</tbody>";
        $html[]="</table>";
        $html[]="<script>NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS);
        $html[]="LoadAjax('PermPolicy-nginx-$serviceid','$page?top-buttons=$serviceid&function=$function');";
        $html[]="</script>";
        echo $tpl->_ENGINE_parse_body($html);
        return true;
        }