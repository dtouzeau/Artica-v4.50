<?php
//SP119
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
include_once(dirname(__FILE__)."/ressources/class.sockets.inc");
include_once(dirname(__FILE__)."/ressources/class.system.network.inc");
$GLOBALS["CLASS_SOCKETS"]=new sockets();
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(isset($_GET["table"])){table();exit;}
if(isset($_GET["table1"])){table1();exit;}
if(isset($_GET["ping"])){amilter_ping();exit;}
if(isset($_POST["ArticaMilterDebug"])){save();exit;}

if(isset($_GET["status"])){amilter_status();exit;}
if(isset($_GET["tabs"])){tabs();exit;}
if(isset($_GET["config-file-js"])){config_file_js();exit;}
if(isset($_GET["config-file-popup"])){config_file_popup();exit;}

if(isset($_GET["section-service-js"])){section_service_js();exit;}
if(isset($_GET["section-service-popup"])){section_service_popup();exit;}

if(isset($_GET["section-features-js"])){section_features_js();exit;}
if(isset($_GET["section-features-popup"])){section_features_popup();exit;}

if(isset($_GET["section-auth-js"])){section_auth_js();exit;}
if(isset($_GET["section-auth-popup"])){section_auth_popup();exit;}
if(isset($_GET["restart"])){restart();exit;}
if(isset($_GET["reload"])){reload();exit;}
if(isset($_GET["events-start"])){events_start();exit;}
if(isset($_GET["search"])){events_search();exit;}
if(isset($_GET["download-logs"])){events_download();exit;}
page();
function page(){
    //
    $page=CurrentPageName();

    $instance_id=intval($_GET["instance-id"]);
    $tpl=new template_admin();

    $ARTICA_MILTER_VERSION=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("ARTICA_MILTER_VERSION");

    $html=$tpl->page_header("Artica Milter v$ARTICA_MILTER_VERSION",
        ico_plug,"{artica_milter_explain}",
        "$page?tabs=$instance_id","/artica-milter-$instance_id","progress-webapi-restart",false,"table-loader-webapi");

    if(isset($_GET["main-page"])){
        $tpl=new template_admin(null,$html);
        echo $tpl->build_firewall();
        return;
    }
    echo $tpl->_ENGINE_parse_body($html);

}
function config_file_js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $users=new usersMenus();
    if(!$users->AsPostfixAdministrator){$tpl->js_no_privileges();return false;}
    return $tpl->js_dialog1("{APP_UNBOUND} >> {config_file}", "$page?config-file-popup=yes");

}
function section_service_js():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    return $tpl->js_dialog("{parameters}","$page?section-service-popup=yes");
}
function section_features_js():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    return $tpl->js_dialog("{features}","$page?section-features-popup=yes");
}
function section_auth_js():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    return $tpl->js_dialog("{test_authentication}","$page?section-auth-popup=yes");
}

function section_js_form():string{
    $page=CurrentPageName();
    return "BootstrapDialog1.close();LoadAjaxSilent('progress-amilter-start','$page?table1=yes');";
}
function restart():bool{
    $page=CurrentPageName();
    $sock=new sockets();
    $sock->REST_API("/postfix/articamilter/restart");
    header("content-type: application/x-javascript");
    echo "LoadAjaxSilent('progress-amilter-start','$page?table1=yes');";
    return admin_tracks("Launch order to restart Artica Milter service");
}
function reload(){
    $tpl=new template_admin();
    $page=CurrentPageName();
    $sock=new sockets();
    $data=$sock->MILTER_API("/reload");
    $json=json_decode($data);
    if (json_last_error()> JSON_ERROR_NONE) {
        echo $tpl->js_error("{error}");
        return true;
    }


    $tpl->js_executed_background("{reloading_service}");
    echo "LoadAjaxSilent('progress-amilter-start','$page?table1=yes');";
    return true;
}
function amilter_ping(){
    header("content-type: application/x-javascript");
    $page=CurrentPageName();
    $interval=3000;
    $ping=intval($_GET["ping"]);
    $f[]="function amilterRefresh(){";
    $f[]="\tvar srcping=document.getElementById('artica-milter-ping').value;";
    $f[]="\tLoadAjaxSilent('amilter-status','$page?status=yes');";
    $f[]="}";
    $f[]="";
    $f[]="function amilterPing(){";
    $f[]="\tif(!document.getElementById('artica-milter-ping')){ return;}";
    $f[]="\tvar currentPing='$ping';";
    $f[]="\tvar srcping=document.getElementById('artica-milter-ping').value;";
    $f[]="\tif (srcping.length==0){";
    $f[]="\t\tdocument.getElementById('artica-milter-ping').value=currentPing;";
    $f[]="\t\tsetTimeout('amilterRefresh()',$interval);";
    $f[]="\t\treturn;";
    $f[]="\t}";
    $f[]="\tvar now = new Date().getTime();";
    $f[]="\tvar unixMilliseconds = srcping * 1000;";
    $f[]="\tvar differenceMilliseconds = now - unixMilliseconds;";
    $f[]="\tSecs = Math.floor(differenceMilliseconds / 1000);";
    $f[]="\tif (Secs < 3) { return; }";
    $f[]="\tdocument.getElementById('artica-milter-ping').value=currentPing;";
    $f[]="\tsetTimeout('amilterRefresh()',$interval);";
    $f[]="}";
    $f[]="amilterPing();\n";
    echo @implode("\n",$f);
    return true;
}

function section_service_popup():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $ArticaMilterDebug   = trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ArticaMilterDebug"));
    $form[] = $tpl->field_checkbox("ArticaMilterDebug", "{debug}", $ArticaMilterDebug);
    $security="AsPostfixAdministrator";

    $times[10080]=$tpl->javascript_parse_text("7 {days}");
    $times[14400]=$tpl->javascript_parse_text("10 {days}");
    $times[21600]=$tpl->javascript_parse_text("15 {days}");
    $times[43200]=$tpl->javascript_parse_text("1 {month}");
    $times[129600]=$tpl->javascript_parse_text("3 {months}");

    $MimeDefangMaxQuartime=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("MimeDefangMaxQuartime"));
    if($MimeDefangMaxQuartime==0){$MimeDefangMaxQuartime=129600;}

    $form[]=$tpl->field_array_hash($times, "MimeDefangMaxQuartime", "{retention} ({backup})", $MimeDefangMaxQuartime);


    $html[]=$tpl->form_outside(null, @implode("\n", $form),null,"{apply}", section_js_form(),$security);
    echo $tpl->_ENGINE_parse_body(@implode("\n", $html));
    return true;
}
function section_features_popup():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $ActiveDirectoryRestShellEnable = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ActiveDirectoryRestShellEnable"));
    $ActiveDirectoryRestShellPass   = trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ActiveDirectoryRestShellPass"));
    $ActiveDirectoryRestSnapsEnable = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ActiveDirectoryRestSnapsEnable"));

    $form[] = $tpl->field_checkbox("ActiveDirectoryRestShellEnable","{allow_execute_scripts}",$ActiveDirectoryRestShellEnable,
        "ActiveDirectoryRestShellPass");
    $form[] = $tpl->field_text("ActiveDirectoryRestShellPass","{passphrase} X-Auth-Token",$ActiveDirectoryRestShellPass);
    $form[] = $tpl->field_checkbox("ActiveDirectoryRestSnapsEnable","{allow_snapshots}",$ActiveDirectoryRestSnapsEnable);


    $security="AsPostfixAdministrator";
    $html[]=$tpl->form_outside(null, @implode("\n", $form),null,"{apply}", section_js_form(),$security);
    echo $tpl->_ENGINE_parse_body(@implode("\n", $html));
    return true;
}
function section_auth_popup():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $ActiveDirectoryRestTestUser    = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ActiveDirectoryRestTestUser"));
    $ActiveDirectoryRestUser        = trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ActiveDirectoryRestUser"));
    $ActiveDirectoryRestPass        = trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ActiveDirectoryRestPass"));
    $ActiveDirectoryRestTestURL     = trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ActiveDirectoryRestTestURL"));

    $form[] = $tpl->field_checkbox("ActiveDirectoryRestTestUser","{enable_feature}",$ActiveDirectoryRestTestUser,
        "ActiveDirectoryRestTestURL,ActiveDirectoryRestUser,ActiveDirectoryRestPass");
    $form[] = $tpl->field_text("ActiveDirectoryRestTestURL","{uri_test}",$ActiveDirectoryRestTestURL);
    $form[] = $tpl->field_text("ActiveDirectoryRestUser","{username}",$ActiveDirectoryRestUser);
    $form[] = $tpl->field_password2("ActiveDirectoryRestPass","{password}",$ActiveDirectoryRestPass);


    $security="AsPostfixAdministrator";
    $html[]=$tpl->form_outside(null, @implode("\n", $form),null,"{apply}", section_js_form(),$security);
    echo $tpl->_ENGINE_parse_body(@implode("\n", $html));
    return true;
}
function tabs():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $array["{status}"]="$page?table=yes";
    $array["{events}"]="$page?events-start=yes";
    echo $tpl->tabs_default($array);
    return true;
}
function events_start():bool{
    $tpl    = new template_admin();
    $page=CurrentPageName();
    echo "<div style='margin-top:10px'>";
    echo $tpl->search_block($page);
    echo "</div>";
    return true;
}


function amilter_status():bool{
    $t=time();
    $tpl    = new template_admin();
    $page=CurrentPageName();
    $sock=new sockets();
    $data=$sock->REST_API("/postfix/articamilter/status");
    $json=json_decode($data);
    if (json_last_error()> JSON_ERROR_NONE) {
        echo $tpl->widget_rouge("{error}",json_last_error_msg());
        return true;
    }
    $bsini=new Bs_IniHandler();
    $bsini->loadString($json->Info);
    $jsRestart="Loadjs('$page?restart=yes');";

    $final[]=$tpl->SERVICE_STATUS($bsini, "ARTICA_MILTER",$jsRestart);
    $final[]="<script>Loadjs('$page?ping=$t');</script>";
    echo $tpl->_ENGINE_parse_body($final);
    return true;

}
function table():bool{
    $page=CurrentPageName();
    echo "<div style='margin-top:10px' id='progress-amilter-start'></div>
<script>LoadAjaxSilent('progress-amilter-start','$page?table1=yes')</script>";
    return true;

}

function table1(){

    $tpl                            = new template_admin();
    $page                           = CurrentPageName();

    $times[10080]=$tpl->javascript_parse_text("7 {days}");
    $times[14400]=$tpl->javascript_parse_text("10 {days}");
    $times[21600]=$tpl->javascript_parse_text("15 {days}");
    $times[43200]=$tpl->javascript_parse_text("1 {month}");
    $times[129600]=$tpl->javascript_parse_text("3 {months}");

    $MimeDefangMaxQuartime=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("MimeDefangMaxQuartime"));
    if($MimeDefangMaxQuartime==0){$MimeDefangMaxQuartime=129600;}




    $ArticaMilterDebug   = trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ArticaMilterDebug"));
    $tpl->table_form_field_js("Loadjs('$page?section-service-js=yes')");
    $tpl->table_form_field_text("{retention} ({backup})",$times[$MimeDefangMaxQuartime],ico_database);
    $tpl->table_form_field_bool("{debug}",$ArticaMilterDebug,ico_bug);


    $myform=$tpl->table_form_compile();

    $topbuttons[] = array("Loadjs('$page?restart=yes');", ico_refresh, "{restart_service}");
    $topbuttons[] = array("Loadjs('$page?reload=yes');", ico_refresh, "{reload_service}");
    $TINY_ARRAY["BUTTONS"]=$tpl->table_buttons($topbuttons);
    $jstiny="Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";

    $html="<input type='hidden' value='' id='artica-milter-ping'><table style='width:100%'>
	<tr>
		<td style='vertical-align:top;width:240px'><div id='amilter-status' style='margin-top:15px'></div></td>
		<td	style='vertical-align:top;width:90%'>$myform</td>
	</tr>
	</table>
	<script>LoadAjaxSilent('amilter-status','$page?status=yes');$jstiny</script>	
	";


    echo $tpl->_ENGINE_parse_body($html);
}




function save(){
    $tpl=new template_admin();
    $tpl->CLEAN_POST();
    $UnboundEnabled=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UnboundEnabled"));
    if($UnboundEnabled==0){$_POST["EnableUnboundBlackLists"]=0;}
    $EnableUnboundBlackLists=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableUnboundBlackLists"));
    $EnableUnBoundSNMPD=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableUnBoundSNMPD"));

    if(isset($_POST["InComingInterfaces"])){
        $array=explode(",",$_POST["InComingInterfaces"]);
        $GLOBALS["CLASS_SOCKETS"]->SaveConfigFile(@implode("\n", $array), "PowerDNSListenAddr");
        unset($_POST["InComingInterfaces"]);
    }

    $tpl->SAVE_POSTs();

    if($_POST["EnableUnboundBlackLists"]<>$EnableUnboundBlackLists){

        if($_POST["EnableUnboundBlackLists"]==1){
            $GLOBALS["CLASS_SOCKETS"]->getFrameWork("unbound.php?blacklists-enable=yes");
        }else{
            $GLOBALS["CLASS_SOCKETS"]->getFrameWork("unbound.php?blacklists-disable=yes");
        }

    }


    if($_POST["EnableUnBoundSNMPD"]<>$EnableUnBoundSNMPD){$GLOBALS["CLASS_SOCKETS"]->REST_API("/snmpd/restart");}
}
function events_download():bool{
    $sock=new sockets();
    $data=$sock->REST_API("/postfix/articamilter/events/compress");
    $json=json_decode($data);
    if (json_last_error()> JSON_ERROR_NONE) {
        die();
    }
    if(!$json->Status){
        header('Content-type: '."text/plain");
        header('Content-Transfer-Encoding: binary');
        header("Content-Disposition: attachment; filename=\"artica-milter.txt\"");
        header("Cache-Control: no-cache, must-revalidate"); // HTTP/1.1
        header("Expires: Sat, 26 Jul 1997 05:00:00 GMT"); // Date dans le passé
        header("Content-Length: ".strlen($json->Error));
        ob_clean();
        flush();
        echo $json->Error;
        return false;
    }
    $content=base64_decode($json->Content);

    header('Content-type: '."application/x-gzip");
    header('Content-Transfer-Encoding: binary');
    header("Content-Disposition: attachment; filename=\"artica-milter.gz\"");
    header("Cache-Control: no-cache, must-revalidate"); // HTTP/1.1
    header("Expires: Sat, 26 Jul 1997 05:00:00 GMT"); // Date dans le passé
    header("Content-Length: ".strlen($content));
    ob_clean();
    flush();
    echo $content;

    return true;
}

function events_search(){
    clean_xss_deep();
    $tpl=new template_admin();
    $page=CurrentPageName();
    $MAIN=$tpl->format_search_protocol($_GET["search"]);
    $sock=new sockets();
    $rp=intval($MAIN["MAX"]);
    $search=trim($MAIN["TERM"]);
    if(strlen($search)<3){$search="NONE";}

    $data=$sock->REST_API("/postfix/articamilter/events/$rp/$search");

    $json=json_decode($data);
    if (json_last_error()> JSON_ERROR_NONE) {
        echo $tpl->div_error("{error}<hr>".json_last_error_msg());
    }
    if(!$json->Status){
        echo $tpl->div_error("{error}<br>Framework return false!<hr>$json->Error");
    }


    $html[]="
<table class=\"table table-hover\">
	<thead>
    	<tr>
        	<th>{date}</th>
        	<th>PID</th>
        	<th>{events}</th>
        </tr>
  	</thead>
	<tbody>
";






    foreach ($json->Logs as $line){
        $line=trim($line);

        if(!preg_match("#^([A-Za-z]+)\s+([0-9]+)\s+([0-9:]+)\s+.*?\[([0-9]+)\]:\s+(.+)#", $line,$re)){
            //echo "<strong style='color:red'>$line</strong><br>";
            continue;}

        $xtime=strtotime($re[1] ." ".$re[2]." ".$re[3]);
        $FTime=date("Y-m-d H:i:s",$xtime);
        $curDate=date("Y-m-d");
        $FTime=trim(str_replace($curDate, "", $FTime));
        $pid=$re[4];
        $line=$re[5];


        $line=str_replace("[DEBUG]:","<span class='label label-default'>DEBUG</span>&nbsp;",$line);
        $line=str_replace("[DEBUG] ","<span class='label label-default'>DEBUG</span>&nbsp;",$line);
        $line=str_replace("[RESTAPI]:","<span class='label label-info'>API</span>&nbsp;",$line);
        $line=str_replace("[START]:","<span class='label label-info'>START</span>&nbsp;",$line);
        $line=str_replace("[INFO]:","<span class='label label-info'>INFO</span>&nbsp;",$line);
        $line=str_replace("[ERROR]:","<span class='label label-danger'>ERROR</span>&nbsp;",$line);
        $line=str_replace("Starting ","<span class='label label-info'>START</span>&nbsp;",$line);
        $line=str_replace("WARNING:","<span class='label label-warning'>WARN</span>&nbsp;",$line);


        if(preg_match("#(fatal|Err)#i", $line)){
            $line="<span class='text-danger'>$line</span>";
        }


        $html[]="<tr>
				<td width=1% nowrap>$FTime</td>
				<td width=1% nowrap>$pid</td>
				<td>$line</td>
				</tr>";

    }

    $topbuttons[] = array("document.location.href='$page?download-logs=yes';", ico_download, "{download} {LogTypeM}");


    if($_GET["search"]==null){$_GET["search"]="*";}
    $TINY_ARRAY["TITLE"]="Artica Milter Service {events} &laquo;{$_GET["search"]}&raquo;";
    $TINY_ARRAY["ICO"]=ico_eye;
    $TINY_ARRAY["EXPL"]="{artica_milter_explain}";
    $TINY_ARRAY["BUTTONS"]=$tpl->table_buttons($topbuttons);
    $jstiny="Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";

    $html[]="</tbody></table>";
    $html[]="<script>$jstiny</script>";
    echo $tpl->_ENGINE_parse_body($html);



}