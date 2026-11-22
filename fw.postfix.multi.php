<?php
// Post SP10 - 13/10/2026
// Service Pack 9 2026-10-10
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
// awc_table_style() / awc_pager_js(): feuille de style et pager partages des tableaux d'entites
include_once(dirname(__FILE__)."/ressources/class.statistics_appliance.inc");
include_once(dirname(__FILE__)."/ressources/class.sockets.inc");
$GLOBALS["CLASS_SOCKETS"]=new sockets();
include_once(dirname(__FILE__)."/ressources/class.system.network.inc");
include_once(dirname(__FILE__)."/ressources/class.maincf.multi.inc");
include_once(dirname(__FILE__)."/ressources/class.main_cf.inc");
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}


if(isset($_GET["instance-js"])){instance_js();exit;}
if(isset($_GET["instance-popup"])){instance_popup();exit;}
if(isset($_GET["table"])){table_div();exit;}
if(isset($_GET["table-main"])){table();exit;}
if(isset($_POST["instance-id"])){instance_save();exit;}
if(isset($_GET["instance-stop"])){instance_stop();exit;}
if(isset($_GET["instance-start"])){instance_start();exit;}
if(isset($_GET["instance-install"])){instance_install();exit;}
if(isset($_GET["multi-rest-stop"])){multi_rest_stop();exit;}
if(isset($_GET["multi-rest-start"])){multi_rest_start();exit;}
if(isset($_GET["multi-rest-install"])){multi_rest_install();exit;}
if(isset($_GET["multi-rest-uninstall"])){multi_rest_uninstall();exit;}
if(isset($_GET["multi-rest-reconfigure"])){multi_rest_reconfigure();exit;}
if(isset($_GET["instance-row"])){instance_row();exit;}
if(isset($_GET["instance-delete"])){instance_delete();exit;}
if(isset($_POST["instance-delete"])){instance_delete_confirm();exit;}
if(isset($_GET["interface-change-js"])){instance_interface_js();exit;}
if(isset($_GET["interface-change-popup"])){interface_change_popup();exit;}
if(isset($_GET["interface-change-action"])){interface_change_action();exit;}
if(isset($_POST["interface-change"])){interface_change_ok();exit;}
function table_div():bool{
    $page=CurrentPageName();
    echo "<div id='postfix-multi-div' style='margin-top:20px'></div><script>LoadAjax('postfix-multi-div','$page?table-main=yes');</script>";
    return true;
}

// REST triggers: fire the new /postfix/multi/* Go endpoints. The Go handlers
// return immediately and write the SAME progress files the dialogs poll.
function multi_rest_stop():bool{
    $id=intval($_GET["multi-rest-stop"]);
    $instancename=get_instance_name($id);
    admin_tracks("Postfix multi: stop instance $id - $instancename");
    $GLOBALS["CLASS_SOCKETS"]->REST_API_POSTFIX_POST_JSON("/postfix/multi/instance/$id/stop",array());
    return true;
}
function multi_rest_start():bool{
    $id=intval($_GET["multi-rest-start"]);
    $instancename=get_instance_name($id);
    admin_tracks("Postfix multi: start instance $id - $instancename");
    $GLOBALS["CLASS_SOCKETS"]->REST_API_POSTFIX_POST_JSON("/postfix/multi/instance/$id/start",array());
    return true;
}
function multi_rest_install():bool{
    $id=intval($_GET["multi-rest-install"]);
    $instancename=get_instance_name($id);
    admin_tracks("Postfix multi: install instance $id - $instancename");
    $GLOBALS["CLASS_SOCKETS"]->REST_API_POSTFIX_POST_JSON("/postfix/multi/instance/$id/install",array());
    return true;
}
function multi_rest_uninstall():bool{
    $id=intval($_GET["multi-rest-uninstall"]);
    $instancename=get_instance_name($id);
    admin_tracks("Postfix multi: uninstall instance $id - $instancename");
    $GLOBALS["CLASS_SOCKETS"]->REST_API_POSTFIX_POST_JSON("/postfix/multi/instance/$id/uninstall",array());
    return true;
}
function multi_rest_reconfigure():bool{
    admin_tracks("Postfix multi: reconfigure all instances");
    $GLOBALS["CLASS_SOCKETS"]->REST_API_POSTFIX_POST_JSON("/postfix/multi/reconfigure",array());
    return true;
}

function instance_stop():bool{
    if(!fw_progress_page_priv("AsPostfixAdministrator|AsSystemAdministrator")){return false;}   // task link signed for this session: built only for whom may run it
    $page=CurrentPageName();
    $tpl=new template_admin();
    $id=intval($_GET["instance-stop"]);
    header("content-type: application/x-javascript");
    $stop=$tpl->framework_buildjs(
        "$page?multi-rest-stop=$id",
        "postfix-multi.$id.progress",
        "postfix-multi.$id.progress.log",
        "instace-prg-$id",
        "Loadjs('$page?instance-row=$id');",
        "Loadjs('$page?instance-row=$id');"
    );
    echo $stop;
    return true;
}
function instance_start():bool{
    if(!fw_progress_page_priv("AsPostfixAdministrator|AsSystemAdministrator")){return false;}   // task link signed for this session: built only for whom may run it
    $page=CurrentPageName();
    $tpl=new template_admin();
    $id=intval($_GET["instance-start"]);
    header("content-type: application/x-javascript");
    $stop=$tpl->framework_buildjs(
        "$page?multi-rest-start=$id",
        "postfix-multi.$id.progress",
        "postfix-multi.$id.progress.log",
        "instace-prg-$id",
        "Loadjs('$page?instance-row=$id');",
        "Loadjs('$page?instance-row=$id');"
    );
    echo $stop;
    return true;
}

function get_instance_name($ID):string{
    $q=new lib_sqlite("/home/artica/SQLITE/postfix.db");
    // Pentest 2026-11-04 : l'identifiant d'instance (issu de l'URL) etait concatene dans la requete.
    $ID=intval($ID);
    $ligne=$q->mysqli_fetch_array("SELECT instancename FROM postfix_instances WHERE id='$ID'");
    return trim($ligne["instancename"]);

}

function instance_delete():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $id=intval($_GET["instance-delete"]);


    $stop=$tpl->framework_buildjs(
        "$page?multi-rest-uninstall=$id",
        "postfix-multi.$id.install.progress",
        "postfix-multi.$id.install.progress.log",
        "instace-prg-$id",
        "Loadjs('$page?instance-row=$id');",
        "Loadjs('$page?instance-row=$id');"
    );
    $instancename=get_instance_name($id);
    echo $tpl->js_confirm_delete("{servicename} $instancename","instance-delete",$id,$stop);
    return true;
}
function instance_delete_confirm():bool{
    $id=intval($_POST["instance-delete"]);
    $instancename=get_instance_name($id);
    admin_tracks("Remove SMTP instance ID $id - $instancename");
    return true;
}

function instance_install():bool{
    if(!fw_progress_page_priv("AsPostfixAdministrator|AsSystemAdministrator")){return false;}   // task link signed for this session: built only for whom may run it
    $page=CurrentPageName();
    $tpl=new template_admin();
    $id=intval($_GET["instance-install"]);
    header("content-type: application/x-javascript");
    $stop=$tpl->framework_buildjs(
        "$page?multi-rest-install=$id",
        "postfix-multi.$id.install.progress",
        "postfix-multi.$id.install.progress.log",
        "instace-prg-$id",
        "Loadjs('$page?instance-row=$id');",
        "Loadjs('$page?instance-row=$id');"
    );
    echo $stop;
    return true;
}

function instance_js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $ID=$_GET["instance-js"];
    if($ID==0){
        $tpl->js_dialog1("{NEW_SMTP_INSTANCE}", "$page?instance-popup=0");
        return true;
    }
    $instancename=get_instance_name($ID);
    $tpl->js_dialog1("$instancename", "$page?instance-popup=$ID");
    return true;
}

function instance_interface_js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $ID=intval($_GET["interface-change-js"]);
    $instancename=get_instance_name($ID);
    $tpl->js_dialog1("$instancename", "$page?interface-change-popup=$ID",500);
    return true;
}
function interface_change_action():bool{
    if(!fw_progress_page_priv("AsPostfixAdministrator|AsSystemAdministrator")){return false;}   // task link signed for this session: built only for whom may run it
    $page=CurrentPageName();
    $tpl=new template_admin();
    $ID=intval($_GET["interface-change-action"]);
    $instancename=get_instance_name($ID);
    $interface=fw_progress_seg($_GET["interface"] ?? "");
    if($interface===""){return fw_progress_bad_value("interface");}
    $value="Change interface of $instancename ($ID) to $interface";

    $restart=$tpl->framework_buildjs("postfix2.php?instance-interface=$ID&interface=$interface",
        "postfix-multi.$ID.interface.progress",
        "postfix-multi.$ID.interface.log",
        "interface-change-$ID",
        "Loadjs('$page?instance-row=$ID');dialogInstance1.close();"
   );

    $tpl->js_dialog_confirm_action("$instancename ($ID) {interface} $interface","interface-change",$value,$restart);
    return true;
}
function interface_change_ok():bool{
    $tpl=new template_admin();
    $tpl->CLEAN_POST();
    admin_tracks($_POST["interface-change"]);
    return true;
}
function interface_change_popup():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $CURS=array();
    $instance_id=intval($_GET["interface-change-popup"]);
    $q=new lib_sqlite("/home/artica/SQLITE/postfix.db");
    $results=$q->QUERY_SQL("SELECT interface FROM postfix_instances");
    foreach ($results as $index=>$ligne){
        $CURS[$ligne["interface"]]=true;
    }

    $html[]="<div id='interface-change-$instance_id'></div>";
    // L'en-tete d'origine ouvrait <tr> sans le fermer avant </thead>, et la ligne
    // ne declarait qu'une colonne pour deux cellules: le balisage etait invalide.
    $html[]="<div class='awc-wrap'><div class='awc-card'><div class='awc-scroll'>";
    $html[]="<table class='awc-tbl'><thead><tr>";
    $html[]="<th>{interfaces}</th>";
    $html[]="<th style='text-align:right'>{actions}</th>";
    $html[]="</tr></thead><tbody id='pfxmul-nic-rows'>";

    $nic=new networking();
    $nicZ=$nic->Local_interfaces();
    $rows=array();
    foreach ($nicZ as $Interface=>$name){
        if(isset($CURS[$Interface])){continue;}
        $rows[]=pfxmul_nic_row($tpl,$page,$instance_id,$Interface,$name);
    }
    if(count($rows)==0){
        $html[]="<tr><td colspan='2'><div class='awc-empty'>{no_data}</div></td></tr>";
    }else{
        $html[]=@implode("\n",$rows);
    }
    $html[]="</tbody></table></div></div></div>";
    $html[]="<script>NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS)."</script>";
    echo awc_table_style().$tpl->_ENGINE_parse_body($html);
    return true;
}

function instance_row():bool{
    // Pentest 2026-11-04 : identifiant recopie dans un nom de fonction JS et dans le SQL (XSS/SQLi) : entier seulement.
    $id=intval($_GET["instance-row"]);
    $status=instance_line($id,array());
    $ico_status=base64_encode($status[0]);
    $action=base64_encode($status[1]);
    $servicename=base64_encode($status[2]);
    $addresses=base64_encode($status[3]);
    $time=base64_encode($status[4]);

    $html[]="function fillAll$id(){";
    $html[]="icon_status=base64_decode('$ico_status');";
    $html[]="servname=base64_decode('$servicename');";
    $html[]="addresses=base64_decode('$addresses');";
    $html[]="action=base64_decode('$action');";
    $html[]="stime=base64_decode('$time');";
    $html[]="$( '#instace-status-$id' ).html(icon_status);";
    $html[]="$( '#instace-ip-$id' ).html(addresses);";
    $html[]="$( '#instance-action-$id' ).html(action);";
    $html[]="$( '#instace-time-$id' ).html(stime);";
    $html[]="$( '#instace-prg-$id').html(servname);";
    $html[]="}";
    $html[]="fillAll$id();";
    echo @implode("\n",$html);
    return true;
}

function instance_popup():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $q=new lib_sqlite("/home/artica/SQLITE/postfix.db");
    // Pentest 2026-11-04 : instance-popup etait concatene dans la requete.
    $ID=intval($_GET["instance-popup"]);
    $ligne=$q->mysqli_fetch_array("SELECT * from postfix_instances WHERE id='$ID'");
    if($ID==0){
        $btn="{add}";
        $title="{NEW_SMTP_INSTANCE}";
        $jsadd="LoadAjax('postfix-multi-div','$page?table-main=yes');LoadAjaxSilent('left-barr','fw-left-menus.php?nothing=yes');";
        $ligne["enabled"]=1;
    }else{
        $btn="{apply}";
        $title="{servicename}: {$ligne["instancename"]}";
        $jsadd=null;
    }


    $form[]=$tpl->field_hidden("instance-id", "$ID");
    $form[]=$tpl->field_checkbox("enabled","{enabled}",$ligne["enabled"]);
    $form[]=$tpl->field_text("instancename", "{servicename}", $ligne["instancename"],true);
    if($ID==0) {
        $form[] = $tpl->field_interfaces("interface", "nooloopNoDef:{nic}", $ligne["interface"]);
    }else{
        $form[] = $tpl->field_hidden("interface",$ligne["interface"]);
    }
    echo $tpl->form_outside($title,$form,null,$btn,"dialogInstance1.close();$jsadd");
    return true;

}
function instance_save(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $tpl->CLEAN_POST();
    // Pentest 2026-11-04 : instance-id, instancename, interface et enabled etaient concatenes dans la requete.
    $ID=intval($_POST["instance-id"]);
    $instancename=$_POST["instancename"];
    $interface=$_POST["interface"];
    $enabled=intval($_POST["enabled"]);

    $q=new lib_sqlite("/home/artica/SQLITE/postfix.db");

    if($ID==0){
        $results=$q->QUERY_SQL_PARAMS("SELECT ID,instancename FROM postfix_instances WHERE interface=?",array((string)$interface));
        $ligne=isset($results[0])?$results[0]:array();
        if(intval($ligne["ID"] ?? 0)>0){
            echo $tpl->post_error("{nic} $interface: {already_used_byinstance} {$ligne["instancename"]}");
            return false;
        }


        admin_tracks("Create a new SMTP instance $instancename");
        $sql="INSERT INTO postfix_instances (instancename,interface,enabled) 
        VALUES(?,?,$enabled)";
        $params=array((string)$instancename,(string)$interface);
    }else{
        admin_tracks_post("Update SMTP instance $instancename interface:$interface");
        $ligne=$q->mysqli_fetch_array("SELECT ID,instancename,interface FROM postfix_instances WHERE ID=$ID");
        $oldinterface=$ligne["interface"];
        if($oldinterface<>$interface){
            $results=$q->QUERY_SQL_PARAMS("SELECT ID,instancename FROM postfix_instances WHERE interface=?",array((string)$interface));
            $ligne=isset($results[0])?$results[0]:array();
            if(intval($ligne["ID"] ?? 0)>0){
                echo $tpl->post_error("{nic} $interface: {already_used_byinstance} {$ligne["instancename"]}");
                return false;
            }
        }

        $sql="UPDATE postfix_instances SET instancename=?,
                             interface=?,enabled='$enabled' WHERE ID=$ID";
        $params=array((string)$instancename,(string)$interface);

    }

    $q->QUERY_SQL_PARAMS($sql,$params);
    if(!$q->ok){echo $tpl->post_error($q->mysql_error);return false;}
    return true;

}

function table(){

    $page=CurrentPageName();
    $tpl=new template_admin();
    $main=new main_cf();



    $reconfigure=$tpl->framework_buildjs(
        "$page?multi-rest-reconfigure=yes",
        "postfix-multi.progress",
        "postfix-multi.progress.log",
        "progress-postfix-mainconf",
        "LoadAjax('postfix-multi-div','$page?table-main=yes');"
    );


    $btn[]="<div class=\"btn-group\" data-toggle=\"buttons\" style=''>";
    $btn[]="<label class=\"btn btn btn-primary\" OnClick=\"Loadjs('$page?instance-js=0')\">";
    $btn[]="<i class='fa fa-plus'></i> {NEW_SMTP_INSTANCE} </label>";

    $btn[]="<label class=\"btn btn btn-info\" OnClick=\"javascript:LoadAjax('postfix-multi-div','$page?table-main=yes');\">";
    $btn[]="<i class='".ico_refresh."'></i> {reload} </label>";

    $btn[]="<label class=\"btn btn btn-primary\" OnClick=\"javascript:$reconfigure;\">";
    $btn[]="<i class='fa fa-save'></i> {apply_configuration} </label>";


    $btn[]="</div>";
    $html[]="<div class='awc-wrap'><div class='awc-card'><div class='awc-scroll'>";
    $html[]="<table class='awc-tbl'><thead><tr>";
    $html[]="<th>{servicename}</th>";
    $html[]="<th class='center'>{status}</th>";
    $html[]="<th>{execution_time}</th>";
    $html[]="<th style='text-align:right'>{actions}</th>";
    $html[]="</tr></thead><tbody id='pfxmul-rows'>";

    $TRCLASS=null;


    $q=new lib_sqlite("/home/artica/SQLITE/postfix.db");

    $sql="CREATE TABLE IF NOT EXISTS `postfix_instances` (
        `ID` INTEGER PRIMARY KEY AUTOINCREMENT,
        `instancename` TEXT NOT NULL,
         interface TEXT NOT NULL,
        `enabled` INTEGER NOT NULL DEFAULT 1);";
    $q->QUERY_SQL($sql);


    if(!$q->ok){
        echo $tpl->FATAL_ERROR_SHOW_128($q->mysql_error."<br><strong>$sql</strong>");
        @chmod("/home/artica/SQLITE/postfix.db", 0777);
        return false;
    }


    $results=$q->QUERY_SQL("SELECT * FROM postfix_instances ORDER by instancename");
    if(!$q->ok){
        echo $tpl->FATAL_ERROR_SHOW_128($q->mysql_error);
        return false;
    }


    foreach ($results as $num=>$ligne){
        $id=$ligne["ID"];


        $enabled=$ligne["enabled"];
        $iddiv=md5(serialize($ligne));
        $TRCLASS=null;
        $status=instance_line($id,$ligne);
        $action=$status[1];
        $servicename=$status[2];
        $exectime=$status[4];
        $refresh=$tpl->icon_refresh("Loadjs('$page?instance-row=$id')");

        $nic_edit=$tpl->icon_interface("Loadjs('$page?interface-change-js=$id')","AsPostfixAdministrator");

        $check=$tpl->icon_check($ligne["enabled"],"Loadjs('$page?instance-enable=$id')",null,"AsPostfixAdministrator");
        $del=$tpl->icon_delete("Loadjs('$page?instance-delete=$id')","AsPostfixAdministrator");
        $html[]=pfxmul_instance_row($id,$iddiv,$servicename,$status,$exectime,
            $nic_edit.$refresh."<span id='instance-action-$id'>$action</span>".$check.$del,
            intval($ligne["enabled"]));
    }

    $html[]="</tbody></table></div>";
    $html[]="<div class='awc-foot'>";
    $html[]="<span class='awc-pg' id='pfxmul-pager'></span>";
    $html[]="</div></div></div>";

    $POSTFIX_VERSION=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("POSTFIX_VERSION");
    $TINY_ARRAY["TITLE"]="{POSTFIX_MULTI_INSTANCE} v$POSTFIX_VERSION";
    $TINY_ARRAY["ICO"]="fa-solid fa-rectangle-vertical-history";
    $TINY_ARRAY["EXPL"]="{POSTFIX_MULTI_INSTANCE_TEXT}";
    $TINY_ARRAY["URL"]="postfix-multi";
    $TINY_ARRAY["BUTTONS"]=@implode("\n",$btn);
    $jstiny="Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";


    $html[]="
	<script>
	$jstiny
	NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS)."
	</script>";

    echo awc_table_style()
        .$tpl->_ENGINE_parse_body(@implode("\n", $html))
        .awc_pager_js(50,"pfxmul-rows","pfxmul-pager");
    return true;

}
function instance_line($id,$ligne=array()):array{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $STATUS_LABEL[0]="<span class='label label-danger'>";
    $STATUS_LABEL[1]="<span class='label label-warning'>";
    $STATUS_LABEL[2]="<span class='label label-primary'>";

    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API_POSTFIX("/postfix/multi/instance/$id/status"));
    $STATUSA=0;
    $STATUSB="{not_installed}";
    $NOT_INSTALLED=true;
    if(is_object($json)){
        $STATUSA=intval($json->status);
        $STATUSB=$json->status_text;
        // status 0 + {not_installed} text means the instance is not installed yet;
        // status 0 + {stopped} means installed but stopped.
        $NOT_INSTALLED=($STATUSA==0 && trim($STATUSB)=="{not_installed}");
    }
    $action="&nbsp;";
    $iconstatus= $tpl->_ENGINE_parse_body($STATUS_LABEL[$STATUSA].$STATUSB."</span>");
    if($STATUSA==0){
        $action=$tpl->button_medium("{start}",
            "green","Loadjs('$page?instance-start=$id')");


    }
    if($STATUSA==2){

        $action=$tpl->button_medium("{stop}",
            "red","Loadjs('$page?instance-stop=$id')");


    }

    if($STATUSA==1){
        $action=$tpl->button_medium("{start}",
            "yellow","Loadjs('$page?instance-start=$id')");

    }

    if($NOT_INSTALLED){
        $action=$tpl->button_medium("{install}",
            "red","Loadjs('$page?instance-install=$id')");
    }

    if(count($ligne)<2){
        $q=new lib_sqlite("/home/artica/SQLITE/postfix.db");
        // Pentest 2026-11-04 : id etait concatene dans la requete.
        $id=intval($id);
        $ligne=$q->mysqli_fetch_array("SELECT * from postfix_instances WHERE id='$id'");

    }
    $servicename=$ligne["instancename"];
    $nic=$ligne["interface"];
    $servicename=$tpl->td_href("$servicename",null,"Loadjs('$page?instance-js=$id')");
    $snic=new system_nic($nic);
    // Pentest 2026-11-04 : interface (donnee de la base) affichee sans encodage (XSS stockee).
    $addresses=req_html("$nic $snic->NICNAME - $snic->IPADDR");
    $stime="&nbsp;";

    return array($iconstatus,$action,$servicename,$addresses,$stime);

}

/** Une interface disponible pour une nouvelle instance. */
function pfxmul_nic_row($tpl,$page,$instance_id,$Interface,$name):string{
    $nic=new system_nic($Interface);
    $h ="<tr>";
    $h.="<td><div class='awc-ident'>";
    $h.="<span class='awc-ico'><i class='fas fa-ethernet'></i></span>";
    $h.="<span><span class='awc-name'>".htmlspecialchars((string)$name)."</span>";
    $h.="<div class='awc-meta'>".htmlspecialchars($nic->NICNAME." (".$nic->IPADDR.")")."</div>";
    $h.="</span></div></td>";
    $h.="<td><div class='awc-acts'>"
        .$tpl->icon_interface("Loadjs('$page?interface-change-action=$instance_id&interface=$Interface')","AsPostfixAdministrator")
        ."</div></td>";
    $h.="</tr>";
    return $h;
}

/**
 * Une instance Postfix: nom de service en identite, adresse d'ecoute en
 * sous-titre. Les identifiants de cellule (instace-prg-N, instace-ip-N,
 * instace-time-N, instace-status-N) sont conserves: le rafraichissement
 * periodique de la page les cible individuellement.
 */
function pfxmul_instance_row($id,$iddiv,$servicename,$status,$exectime,$actions,$enabled):string{
    $h ="<tr id='$iddiv'>";
    $h.="<td><div class='awc-ident'>";
    $h.="<span class='".($enabled?"awc-ico":"awc-ico awc-ico-off")."'><i class='fas fa-server'></i></span>";
    $h.="<span><span class='".($enabled?"awc-name":"awc-name awc-name-off")."' id='instace-prg-$id'>$servicename</span>";
    $h.="<div class='awc-meta' id='instace-ip-$id'>{$status[3]}</div>";
    $h.="</span></div></td>";
    $h.="<td class='center' id='instace-status-$id'>{$status[0]}</td>";
    $h.="<td class='awc-mono' id='instace-time-$id'>$exectime</td>";
    $h.="<td><div class='awc-acts'>$actions</div></td>";
    $h.="</tr>";
    return $h;
}

