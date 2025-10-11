<?php
	include_once(dirname(__FILE__).'/ressources/class.templates.inc'); 
	include_once('ressources/class.users.menus.inc');
	if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
	$user=new usersMenus();
	if(!$user->AsAnAdministratorGeneric){die("DIE " .__FILE__." Line: ".__LINE__);}
	
	if(isset($_GET["table"])){table();exit;}
	if(isset($_GET["service-status"])){services_status();exit;}
	if(isset($_GET["service-toolbox"])){services_toolbox();exit;}
    if(isset($_GET["system-clean"])){system_clean_js();exit;}
    if(isset($_POST["system-clean"])){system_clean_perform();exit;}

    if(isset($_POST["RustDeskEncryptedOnly"])){section_global_save();exit;}

    if(isset($_GET["config-locked"])){config_locked();exit;}
    if(isset($_GET["crypted-js"])){section_crypted_js();exit;}
    if(isset($_GET["crypted-popup"])){section_crypted_popup();exit;}

    if(isset($_GET["change-key-js"])){section_change_key_js();exit;}
    if(isset($_GET["change-key-popup"])){section_change_key_popup();exit;}
    if(isset($_GET["change-key-ask"])){section_change_key_ask();exit;}
    if(isset($_POST["change-key"])){section_change_key_save();exit;}
    if(isset($_POST["DockerExportTime"])){section_export_save();exit;}
    if(isset($_POST["WorkDir"])){section_workingdir_save();exit;}
	
page();

function system_clean_js():bool{
    $tpl=new template_admin();
    return $tpl->js_confirm_execute("{docker_system_prune}","system-clean","yes");
}
function system_clean_perform():bool{
    $GLOBALS["CLASS_SOCKETS"]->getFrameWork("docker.php?clean-system=yes");
    return admin_tracks("Clean docker prune system cache");
}

function page():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();


    $html=$tpl->page_header("{APP_HAMRP} &raquo;&raquo; {status}",
        ico_load_balancer,
        "{APP_HAMRP_ABOUT}",
        "$page?table=yes","hamrp-status","progress-harmp-restart",false,"table-hamrp-status");


    if(isset($_GET["main-page"])){
        $tpl=new template_admin("Artica: {APP_HAMRP} status",$html);
        echo $tpl->build_firewall();
        return true;
    }

    $tpl=new templates();
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}


function table():bool{
	$tpl=new template_admin();
	$page=CurrentPageName();
	$t=time();
	$html="<table style='width:100%;margin-top:10px'>
	<tr>
		<td valign='top' style='width:1%' nowrap>
			<div id='$t-status'></div>
        </td>
		<td valign='top' style='width:98%;padding-left:15px'>
		    <div id='$t-toolbox'></div>
		    <div id='harmp-config-locked'></div>
		
		</td>
	</tr>
	</table>
	<script>

		LoadAjaxTiny('$t-status','$page?service-status=$t');
		LoadAjaxTiny('$t-toolbox','$page?service-toolbox=yes');
        LoadAjaxTiny('harmp-config-locked','$page?config-locked=yes&t=$t');		
	</script>
	
	";
	
	
	echo $tpl->_ENGINE_parse_body($html);
    return true;
}
function config_locked():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $t=$_GET["t"];

    list($PUBKEY,$SECKEY)=GetKeys();
    $tpl->table_form_field_js("Loadjs('$page?change-key-js=yes&t=$t')","AsFirewallManager");
    $tpl->table_form_field_text("{key}", $PUBKEY, ico_key);

    $RustDeskEncryptedOnly=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("RustDeskEncryptedOnly"));
    $tpl->table_form_field_js("Loadjs('$page?crypted-js=yes&t=$t')","AsFirewallManager");
    $tpl->table_form_field_bool("{accept_only_encrypted_sessions}",$RustDeskEncryptedOnly, ico_ssl);

    echo $tpl->table_form_compile();
    return true;
}
function section_change_key_js():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $t=$_GET["t"];
   return $tpl->js_dialog("{key}: {change}","$page?change-key-popup=yes&t=$t");
}
function section_crypted_js():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $t=$_GET["t"];
    return $tpl->js_dialog("{parameters}","$page?crypted-popup=yes&t=$t");
}
function section_export_js():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    return $tpl->js_dialog("{export} {ttl}","$page?section-export-popup=yes");
}
function section_js():string{
    $page=CurrentPageName();
    return "BootstrapDialog1.close();LoadAjaxTiny('docker-config-locked','$page?docker-config-locked=yes');";
}
function section_change_key_ask():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $t=$_GET["t"];
    $js[] = "BootstrapDialog1.close();";
    $js[] = $tpl->framework_buildjs("rustdesk.php?change-key-perform=yes",
        "rustdesk.install.progress", "rustdesk.install.progress.log",
        "progress-harmp-restart",
        "LoadAjaxTiny('$t-toolbox','$page?service-toolbox=yes');LoadAjaxTiny('harmp-config-locked','$page?config-locked=yes&t=$t');", null, null, "AsFirewallManager");

    return $tpl->js_confirm_execute("{rustdesk_change_key}","change-key","yes",@implode(";",$js));

}
function section_change_key_save():bool{
    return admin_tracks("Request to change the RustDesk Public key server");
}
function section_change_key_popup():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $t=$_GET["t"];
    list($pub,$sec)=GetKeys();
    $html[]="<textarea style='height:90px;width:90%;font-size:22px;padding:25px'>$pub</textarea>";
    $btn_flush=$tpl->button_autnonome("{change}", "Loadjs('$page?change-key-ask=yes&t=$t')",
        ico_key,"AsFirewallManager",335,"btn-warning");
    $html[]="<div class='center' style='margin:30px'>$btn_flush</div>";
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}
function section_export_save():bool{
    $tpl=new template_admin();
    $tpl->SAVE_POSTs();
    return true;
}
function section_crypted_popup():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $t=$_GET["t"];
    $RustDeskEncryptedOnly=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("RustDeskEncryptedOnly"));

    $jsafter=$tpl->framework_buildjs("rustdesk.php?restart=yes",
        "rustdesk.restart.progress","rustdesk.restart.progress.log",
        "progress-harmp-restart","LoadAjaxTiny('$t-status','$page?service-status=$t');",null,null,"AsFirewallManager");

    $form[]=$tpl->field_checkbox("RustDeskEncryptedOnly","{accept_only_encrypted_sessions}",$RustDeskEncryptedOnly);
    $html[]=$tpl->form_outside(null,$form,null,"{apply}","BootstrapDialog1.close();$jsafter","AsFirewallManager");
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}
function section_global_save():bool{
    $tpl=new template_admin();
    $tpl->SAVE_POSTs();
    admin_tracks_post("Saving RustDesk parameters");
    return true;
}



function services_status():bool{

    $tpl=new template_admin();
    $q = new lib_sqlite("/home/artica/SQLITE/hamrp.db");
    $ligne=$q->mysqli_fetch_array("SELECT count(*) as tcount FROM hamrp WHERE enabled=1");
    $tcount=intval($ligne["tcount"]);


    if($tcount>0){
        $html[]=$tpl->widget_vert("{managed_nodes}",$tcount,null,ico_computer);
    }else{
        $html[]=$tpl->widget_grey("{managed_nodes}","{none}",null,ico_computer);
    }

    echo $tpl->_ENGINE_parse_body($html);
    return true;
}



function GetKeys():array{
    $Data=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("RustDeskKeys");
    if(!is_null($Data)){
        if(strlen($Data)>6){
            $sMain=unserialize(base64_decode($Data));
            if(isset($sMain["PUBKEY"])){
                $PUBKEY=$sMain["PUBKEY"];
                $SECKEY=$sMain["SECKEY"];
                return array($PUBKEY,$SECKEY);
            }
        }
    }
   return array("","");
}

function services_toolbox():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $users=new usersMenus();
    $wbutton[0]["name"]="{online_help}:wiki";
    $wbutton[0]["icon"]="fa-solid fa-square-question";
    $wbutton[0]["js"]="s_PopUpFull('https://wiki.articatech.com/en/network/remote-control/rustdesk',1024,768,'Wiki');";


    $topbuttons=array();
    $FireHolEnable=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("FireHolEnable"));
    if($FireHolEnable==0){
        $FirewallWidget=$tpl->widget_vert("Firewall","{disabled}",$wbutton,ico_firewall);
    }else{
        $q=new lib_sqlite("/home/artica/SQLITE/firewall.db");
        $ligne = $q->mysqli_fetch_array("SELECT ID,rulename FROM iptables_main 
                WHERE enabled='1' AND service='RustDesk' AND accepttype='ACCEPT'");
        if(!isset($ligne["ID"])){$ligne["ID"]=0;}
        if(!isset($ligne["rulename"])){$ligne["rulename"]="";}
        $ID=intval($ligne["ID"]);
        if($ID==0){
            $FirewallWidget=$tpl->widget_rouge("Firewall","{not_set}",$wbutton,ico_firewall);
        }else{
            $FirewallWidget=$tpl->widget_vert("{$ligne["rulename"]}","{defined}",$wbutton,ico_firewall);
        }
    }

    $q=new lib_sqlite("/home/artica/SQLITE/rustdesk.db");
    $Rows=$q->COUNT_ROWS("peer");

    list($PUBKEY,$SECKEY)=GetKeys();
    if($Rows==0){
        $ClientWidget=$tpl->widget_grey("{clients}","{none}",$wbutton,ico_laptop_down);

    }else{
        $ClientWidget=$tpl->widget_vert("{clients}",$Rows,$wbutton,ico_laptop_down);
    }

    if(strlen($PUBKEY)==0){
            $btn[0]["js"] = "Loadjs('$page?change-key-js=yes');";
            $btn[0]["name"] = "{change}";
            $btn[0]["icon"] = ico_key;
            $KeyWidget=$tpl->widget_rouge("{key}","{error}",$btn);

    }else{

            $btn[0]["js"] = "Loadjs('$page?change-key-js=yes');";
            $btn[0]["name"] = "{change}";
            $btn[0]["icon"] = ico_key;

        $KeyWidget=$tpl->widget_vert("{key}","{success}",$btn,ico_key);
    }


    $TINY_ARRAY["TITLE"]="{APP_HAMRP} &raquo;&raquo; {status}";
    $TINY_ARRAY["ICO"]=ico_load_balancer;
    $TINY_ARRAY["EXPL"]="{APP_HAMRP_ABOUT}";
    $TINY_ARRAY["BUTTONS"]=$tpl->table_buttons($topbuttons);
    $jstiny="Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";

    $html[]="<table style='width:100%'>";
    $html[]="<tr>";
    $html[]="<td style='width:33%'>$ClientWidget</td>";
    $html[]="<td style='width:33%;padding-left:5px'>$KeyWidget</td>";
    $html[]="<td style='width:33%;padding-left:5px'>$FirewallWidget</td>";
    $html[]="</tr>";
    $html[]="<table>";
    $html[]="<script>$jstiny</script>";
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}






