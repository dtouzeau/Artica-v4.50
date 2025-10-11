<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}

if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}


if(isset($_GET["table"])){features();exit;}
if(isset($_POST["KerberosUsername"])){Save();exit;}
if(isset($_GET["file-uploaded"])){file_uploaded();exit;}
page();


function page(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$html="
<div class=\"row border-bottom white-bg dashboard-header\">
	<div class=\"col-sm-12\"><h1 class=ng-binding>Active Directory &raquo;&raquo{virtual_ntlm}</h1>
	<p>{virtual_ntlm_explain}</p>
	
	</div>
</div>                    
<div class='row'><div id='virtual-ntlm-ad-restart' class='white-bg'></div>
	<div class='ibox-content'>
		<div id='table-virtual-ntlm'></div>
     </div>
</div>
<script>
	$.address.state('/');
	$.address.value('/virtual-ntlm');
	LoadAjax('table-virtual-ntlm','$page?table=yes');
</script>";
	
	if(isset($_GET["main-page"])){
		$tpl=new template_admin("Active Directory/{virtual_ntlm}",$html);
		echo $tpl->build_firewall();
		return;
	}
	
	echo $tpl->_ENGINE_parse_body($html);
	
}

function features(){
    $td_style=null;
	$page=CurrentPageName();
	$tpl=new template_admin();
	$users=new usersMenus();
    $FORM_FILLED=true;
	$EnableFakeAuth=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableFakeAuth");

    //<i class="fab fa-windows"></i>
    $html[]="<center style='margin:50px'>";
    if($EnableFakeAuth==0) {

        $check_js=$tpl->framework_buildjs("squid2.php?ntlmfake-enable=yes","squid.access.center.progress","squid.access.center.progress.log","virtual-ntlm-ad-restart","LoadAjax('table-virtual-ntlm','$page?table=yes');LoadAjaxSilent('left-barr','fw-left-menus.php?nothing=yes');");

        $html[]= $tpl->button_autnonome("{enable_fake_ntlm}", $check_js, "fab fa-windows", null, 400,
            "btn-primary");

    }else{

        $check_js=$tpl->framework_buildjs("squid2.php?ntlmfake-disable=yes","squid.access.center.progress","squid.access.center.progress.log","virtual-ntlm-ad-restart","LoadAjax('table-virtual-ntlm','$page?table=yes');LoadAjaxSilent('left-barr','fw-left-menus.php?nothing=yes');");

        $html[]= $tpl->button_autnonome("{disable_fake_ntlm}", $check_js, "fab fa-windows", null, 400,
            "btn-danger");
    }


    $html[]="</center>";


	echo $tpl->_ENGINE_parse_body($html);
	
	
}

