<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
include_once(dirname(__FILE__)."/ressources/class.sockets.inc");
include_once(dirname(__FILE__)."/ressources/class.smtpd.notifications.inc");

$GLOBALS["CLASS_SOCKETS"]=new sockets();

if(isset($_GET["tabs"])){tabs();exit;}
if(isset($_GET["table"])){table();exit;}
if(isset($_GET["form"])){page_form();exit;}
if(isset($_GET["status"])){status();exit;}

if(isset($_GET["upload-license-js"])){upload_js_license();exit;}
if(isset($_GET["upload-license-popup"])){upload_popup_license();exit;}
if(isset($_GET["file-uploaded"])){upload_save_license();exit;}

if(isset($_GET["unjoin-domain-js"])){unjoin_js();exit;}
if(isset($_POST["unjoin"])){unjoin_confirmed();exit;}
if(isset($_GET["id-popup"])){id_popup();exit;}
if(isset($_GET["id-delete"])){id_delete();exit;}

if(isset($_GET["enable"])){id_enable();exit;}

if(isset($_GET["rules-start"])){rules_start();exit;}
if(isset($_GET["rules"])){rules();exit;}
if(isset($_GET["events"])){events();exit;}
if(isset($_GET["search"])){events_search();exit;}

if(isset($_GET["status-top"])){status_top();exit;}
if(isset($_GET["join-domain-js"])){join_domain_js();exit;}
if(isset($_GET["join-domain-popup"])){join_domain_popup();exit;}
if(isset($_GET["join-domain-perform"])){join_domain_perform();exit;}
if(isset($_POST["ActiveDirectoryConnections"])){join_domain_save();exit;}
if(isset($_GET["test-join"])){test_join_js();exit;}
if(isset($_GET["test-join-popup"])){test_join_popup();exit;}

function tabs():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $array["{status}"]="$page?table=yes";
    $array["{events}"]="$page?events=yes";
    echo $tpl->tabs_default($array);
    return true;
}

page();

function upload_js_license():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $tpl->js_dialog1("modal:{license}:{upload}","$page?upload-license-popup=yes",450);
    return true;
}
function test_join_js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $tpl->js_dialog1("modal:Active Directory:{events}","$page?test-join-popup=yes",950);
    return true;
}
function upload_popup_license():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $html[]="<div id='safeguard-upload-pattern'></div>";
    $html[]="<center style='margin:30px'>";
    $html[]=$tpl->button_upload("{license}",$page);
    $html[]="</center>";
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}
function unjoin_js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();

    $jsafter=$tpl->framework_buildjs(
        "safeguard.php?unjoin=yes",
        "vasd.progress",
        "vasd.log",
        "safeguard-connect-progress",
        "LoadAjaxSilent('safeguard-top','$page?status-top=yes');"
    );

    $tpl->js_confirm_execute("{disconnect_from_activedirectory_explain}","unjoin","true",$jsafter);
    return true;
}
function unjoin_confirmed(){
    admin_tracks("Unjoin the active Directory using SafeGuard...");
}
function join_domain_js(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $tpl->js_dialog1("modal:{join_active_directory}","$page?join-domain-popup=yes",650);
    return true;

}
function test_join_popup(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $html[]="<table class=\"table table-hover\">
	<thead>
    	<tr>
        	<th>{event}</th>
        	<th>{status}</th>
        </tr>
  	</thead>
	<tbody>";

    $SafeGuardAdStatus=unserialize($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SafeGuardAdStatus"));
    foreach ($SafeGuardAdStatus["INFOS"] as $ligne=>$array){
        $status=$array[1];
        $sline=$array[0];
        $ico="<i class='text-danger ".ico_emergency." fa-2x' ></i>";
        if($status){
            $ico="<i class='text-navy fa-solid fa-check fa-2x'></i>";
        }
        $html[]="<tr>
				<td width=99% nowrap>$sline</td>
				<td>$ico</td>
				</tr>";
    }
    $html[]="</table>";
    echo $tpl->_ENGINE_parse_body($html);
}
function join_domain_popup():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $ActiveDirectoryConnections=unserialize($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ActiveDirectoryConnections"));

    if(count($ActiveDirectoryConnections)==0){
        echo $tpl->_ENGINE_parse_body($tpl->div_error("{no_active_directory_connection}"));
        return false;
    }

    foreach ($ActiveDirectoryConnections as $index=>$array){
        $LDAP_SERVER=$array["LDAP_SERVER"];
        $LDAP_SUFFIX=$array["LDAP_SUFFIX"];
        $MAIN[$index]="$LDAP_SERVER ($LDAP_SUFFIX)";

    }

    $form[]=$tpl->field_array_hash($MAIN,"ActiveDirectoryConnections","{connection}");
    echo "<div id='safeguard-connect-progress'></div>";
    echo $tpl->form_outside(null,$form,null,"{join}","Loadjs('$page?join-domain-perform=yes')","AsSystemAdministrator");
    return true;
}
function join_domain_save(){
    $_SESSION["SAFEGUARD_CNX"]=$_POST["ActiveDirectoryConnections"];
}
function join_domain_perform(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $jsafter=$tpl->framework_buildjs(
        "safeguard.php?connect={$_SESSION["SAFEGUARD_CNX"]}",
        "vasd.progress",
        "vasd.log",
         "safeguard-connect-progress",
        "LoadAjaxSilent('safeguard-top','$page?status-top=yes');dialogInstance1.close();"
    );
    header("content-type: application/x-javascript");
    echo $jsafter;
}

function upload_save_license():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $filename=$_GET["file-uploaded"];
    $fileencode=urlencode($filename);
    $GLOBALS["CLASS_SOCKETS"]->getFrameWork("safeguard.php?save-license=$fileencode");
    header("content-type: application/x-javascript");
    $out=PROGRESS_DIR."/vastool-addlicense";
    list($found,$re)=$tpl->ParseFileRegex($out,"^ERROR:");
    if($found){
        $txt=@file_get_contents($out);
        $txt=str_replace("\n","<br>",$txt);
        $txt=str_replace(".","<br>",$txt);
        echo $tpl->js_error($txt);
        @unlink($out);
        return false;
    }


    echo "LoadAjaxSilent('safeguard-top','$page?status-top=yes');";
    return true;
}

function page(){
	$page=CurrentPageName();
	$tpl=new template_admin();

    $APP_SAFEGUARD_AUTH_VERSION=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("APP_SAFEGUARD_AUTH_VERSION");
    $html=$tpl->page_header("{APP_VASD} v$APP_SAFEGUARD_AUTH_VERSION",
        "oneidentity_170x200.png","{APP_VASD_EXPLAIN}",
        "$page?tabs=yes",
        "safeguard","progress-safeguard-restart",
        false,"table-loader-safeguard-pages");



	if(isset($_GET["main-page"])){$tpl=new template_admin(null,$html);echo $tpl->build_firewall();return;}
	echo $tpl->_ENGINE_parse_body($html);

}
function table():bool{
	$tpl=new template_admin();
	$page=CurrentPageName();

	$html="<table style='width:100%'>
	<tr>
	<td style='vertical-align:top;width:240px'><div id='safeguard-status' style='margin-top:15px'></div></td>
	<td	style='vertical-align:top;width:90%;padding-top:13px;padding-left:10px'>
	    <div id='safeguard-top'></div>
	    <div id='safeguard-form'>
	</td>
	</tr>
	</table>
	<script>
	    LoadAjaxSilent('safeguard-status','$page?status=yes');
        LoadAjaxSilent('safeguard-top','$page?status-top=yes');
        LoadAjaxSilent('safeguard-form','$page?form=yes');
	    
	</script>
	";
	echo $tpl->_ENGINE_parse_body($html);
	return true;
}

function status_top_connected():string{
    $page=CurrentPageName();
    $tpl=new template_admin();

    $SafeGuardAdCnxID=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SafeGuardAdCnxID"));
    if($SafeGuardAdCnxID==0){
        $btn[0]["name"] = "{join_active_directory}";
        $btn[0]["icon"] = "fab fa-windows";
        $btn[0]["js"] = "Loadjs('$page?join-domain-js=yes');";
        return $tpl->widget_grey("Active Directory", "{not_configured}", $btn,"fab fa-windows");

    }


    $GLOBALS["CLASS_SOCKETS"]->getFrameWork("safeguard.php?test-join=yes");
    $SafeGuardAdStatus=unserialize($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SafeGuardAdStatus"));
    if(!$SafeGuardAdStatus["STATUS"]){
        $btn[0]["name"] = "{events}";
        $btn[0]["icon"] = "fas fa-eye";
        $btn[0]["js"] = "Loadjs('$page?test-join=yes');";
        $btn[1]["name"] = "{join}";
        $btn[1]["icon"] = "fab fa-windows";
        $btn[1]["js"] = "Loadjs('$page?join-domain-js=yes');";
        return $tpl->widget_jaune("Active Directory", "{failed}", $btn,"fab fa-windows");
    }
    $btn[0]["name"] = "{events}";
    $btn[0]["icon"] = "fas fa-eye";
    $btn[0]["js"] = "Loadjs('$page?test-join=yes');";
    $btn[1]["name"] = "{disconnect}";
    $btn[1]["icon"] = "fad fa-unlink";
    $btn[1]["js"] = "Loadjs('$page?unjoin-domain-js=yes');";
    return $tpl->widget_vert("Active Directory", "{connected}", $btn,"fab fa-windows");

}

function status_top():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $out=PROGRESS_DIR."/vastool-license";
    $GLOBALS["CLASS_SOCKETS"]->getFrameWork("safeguard.php?license=yes");
    $LICENSE=$tpl->widget_grey("{license_status}", "{unknown}", null,"fa-duotone fa-file-certificate");


    list($found, $re)=$tpl->ParseFileRegex($out,"No valid licenses");
    if($found){
        $btn[0]["name"] = "{upload}";
        $btn[0]["icon"] = "far fa-shield-check";
        $btn[0]["js"] = "Loadjs('$page?upload-license-js=yes');";
        $LICENSE=$tpl->widget_grey("{license_status}", "{license_invalid}", $btn,"fa-duotone fa-file-certificate");

    }
    list($found, $re)=$tpl->ParseFileRegex($out,"Status:\s+Valid License");
    if($found){
        $btn=array();
        list($found, $re)=$tpl->ParseFileRegex($out,"ExpiryDate:\s+(.+)");
        $expire="{active2}";
        if($found){
            $sdate=$re[1]." 00:00:00";
            $time=strtotime($sdate);
            $distance=distanceOfTimeInWords(time(),$time);
            $expire="{expire}:$distance";
        }

        $LICENSE=$tpl->widget_vert("{license_status}", $expire, $btn,"fa-duotone fa-file-certificate");

    }

    $SafeGuardAdCnxID=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SafeGuardAdCnxID"));
    if($SafeGuardAdCnxID==0){
        $LICENSE=$tpl->widget_grey("{license_status}", "{not_configured}", null,"fa-duotone fa-file-certificate");
    }

    $html[]="<table style='width:100%'>";
	$html[]="<tr>";
    $html[]="<td valign='top' style='width:33%;padding-left:10px'>".status_top_connected()."</td>";
    $html[]="<td valign='top' style='width:33%'>$LICENSE</td>";
    $html[]="</tr>";
    $html[]="</table>";
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}

function restart_js():string{
    $page=CurrentPageName();
    $ARRAY["PROGRESS_FILE"]=PROGRESS_DIR."/ksrn.progress";
    $ARRAY["LOG_FILE"]=PROGRESS_DIR."/ksrn.log";
    $ARRAY["CMD"]="ksrn.php?restart=yes";
    $ARRAY["TITLE"]="{KSRN} {restarting_service}";
    $ARRAY["AFTER"]="LoadAjaxSilent('ksrn-status','$page?ksrn-status=yes');";
    $prgress=base64_encode(serialize($ARRAY));
    return "Loadjs('fw.progress.php?content=$prgress&mainid=progress-notifications-restart')";
}


function status():bool{

    $tpl            = new template_admin();
    $jsRestart      = restart_js();
    $page=CurrentPageName();
    $GLOBALS["CLASS_SOCKETS"]->getFrameWork("safeguard.php?status=yes");
    $bsini = new Bs_IniHandler(PROGRESS_DIR."/vasd.status");
    echo $tpl->SERVICE_STATUS($bsini, "APP_VASD");
    return true;

}

function page_form():bool{
    
    return true;
}

function events(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $t=time();
    if(!isset($_SESSION["SMTP_NOTIFS_SEARCH"])){$_SESSION["SMTP_NOTIFS_SEARCH"]="today this hour 50 events";}

    $html[]="

	<div class=\"row\"> 
		<div class='ibox-content'>
		<div class=\"input-group\">
      		<input type=\"text\" class=\"form-control\" value=\"{$_SESSION["SMTP_NOTIFS_SEARCH"]}\" placeholder=\"{search}\" id='search-this-$t' OnKeyPress=\"javascript:Search$t(event);\">
      		<span class=\"input-group-btn\">
       		 <button style=\"text-transform: capitalize;\" class=\"btn btn-default\" type=\"button\" OnClick=\"javascript:ss$t();\">Go!</button>
      	</span>
     </div>
    </div>
</div>
	<div class='row'><div id='progress-firehol-restart'></div>
	<div class='ibox-content'>

	<div id='table-loader'></div>

	</div>
	</div>
	<script>";
    $html[]="function Search$t(e){";
    $html[]="\tif(!checkEnter(e) ){return;}";
    $html[]="ss$t();";
    $html[]="}";
    $html[]="function ss$t(){
			var ss=encodeURIComponent(document.getElementById('search-this-$t').value);
			LoadAjax('table-loader','$page?search='+ss);
		}
		
		function Start$t(){
			var ss=document.getElementById('search-this-$t').value;
			ss$t();
		}
		Start$t();
	</script>";


    echo $tpl->_ENGINE_parse_body($html);

}

function events_search(){
    $time=null;
    $sock=new sockets();
    $tpl=new template_admin();
    $date=null;
    $MAIN=$tpl->format_search_protocol($_GET["search"],false,true);
    $line=base64_encode(serialize($MAIN));
    $sock->getFrameWork("articasmtp.php?artica-notifs-events=$line");
    $filename=PROGRESS_DIR."/smtpd.syslog";
    $date_text=$tpl->_ENGINE_parse_body("{date}");
    $events=$tpl->_ENGINE_parse_body("{events}");
    $html[]="
<table class=\"table table-hover\">
	<thead>
    	<tr>
        	<th>$date_text</th>
        	<th>$events</th>
        </tr>
  	</thead>
	<tbody>
";

    $data=explode("\n",@file_get_contents($filename));
    if(count($data)>3){$_SESSION["SMTP_NOTIFS_SEARCH"]=$_GET["search"];}
    rsort($data);


    foreach ($data as $line){
        $MAIN=array();
        $msg=array();
        $text_class="text-success";
        if(!preg_match_all("#([a-z]+)=[\"|](.+?)[\"|]#",$line,$sarray)){continue;}
        foreach ($sarray[1] as $index=>$line){
            $MAIN[$line]=$sarray[2][$index];
        }
        if(preg_match("#from=(.+?)\s+#",$line,$re)){
            $MAIN["from"]=$re[1];
        }

        $FTime=$tpl->time_to_date(strtotime($MAIN["time"]),true);
        if(isset($MAIN["error"])){
            $text_class="text-danger";
            $msg[]="<strong>{error}: {$MAIN["error"]}</strong>";
        }



        if(isset($MAIN["msg"])){
            $msg[]=$MAIN["msg"];
        }
        if(isset($MAIN["to"])){
            $msg[]="<br>{recipient}: <strong>".$MAIN["to"]."</strong>";
        }
        if(isset($MAIN["host"])){
            $msg[]="&nbsp;/&nbsp;{smtp_server_name}: <strong>".$MAIN["host"]."</strong>";
        }
        if(isset($MAIN["address"])){
            $msg[]="{listen}: ".$MAIN["address"];
        }
        if(isset($MAIN["from"])){
            $msg[]="&nbsp;/&nbsp;{smtp_sender}: ".$MAIN["from"];
        }



        $line="<span class='$text_class'>".@implode(" ",$msg)."</span>";


        $html[]="<tr>
				<td width=1% nowrap>$FTime</td>
				<td>$line</td>
				</tr>";

    }

    $html[]="</tbody></table>";
    $html[]="<div><i>".@file_get_contents(PROGRESS_DIR."/smtpd.syslog.pattern")."</i></div>";
    echo $tpl->_ENGINE_parse_body($html);



}