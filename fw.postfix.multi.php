<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
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

function instance_stop():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $id=$_GET["instance-stop"];
    header("content-type: application/x-javascript");
    $stop=$tpl->framework_buildjs(
        "postfix2.php?multi-stop=$id",
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
    $page=CurrentPageName();
    $tpl=new template_admin();
    $id=$_GET["instance-start"];
    header("content-type: application/x-javascript");
    $stop=$tpl->framework_buildjs(
        "postfix2.php?multi-start=$id",
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
    $ligne=$q->mysqli_fetch_array("SELECT instancename FROM postfix_instances WHERE id='$ID'");
    return trim($ligne["instancename"]);

}

function instance_delete():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $id=intval($_GET["instance-delete"]);


    $stop=$tpl->framework_buildjs(
        "postfix2.php?multi-uninstall-instance=$id",
        "postfix-multi.$id.progress",
        "postfix-multi.$id.progress.log",
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
    $page=CurrentPageName();
    $tpl=new template_admin();
    $id=$_GET["instance-install"];
    header("content-type: application/x-javascript");
    $stop=$tpl->framework_buildjs(
        "postfix2.php?multi-install-instance=$id",
        "postfix-multi.$id.install.progress",
        "postfix-multi.$id.install.progress.log",
        "instace-prg-$id",
        "Loadjs('$page?instance-row=$id');",
        "Loadjs('$page?instance-row=$id');"
    );
    $instancename=get_instance_name($id);
    admin_tracks("Install new SMTP instance $id - $instancename");
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
    $page=CurrentPageName();
    $tpl=new template_admin();
    $ID=intval($_GET["interface-change-action"]);
    $instancename=get_instance_name($ID);
    $interface=$_GET["interface"];
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
    $html[]="<table id='table-postfix-smtp-rules' class=\"table table-stripped\" data-page-size=\"100\" data-paging=\"true\">";
    $html[]="<thead>";
    $html[]="<tr>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{interfaces}</th>";
    $html[]="</thead>";
    $html[]="<tbody>";

    $nic=new networking();
    $nicZ=$nic->Local_interfaces();
    $TRCLASS=null;
    foreach ($nicZ as $Interface=>$name){
        if(isset($CURS[$Interface])){continue;}
        if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
        $html[]="<tr class='$TRCLASS' id=''>";

        $nic=new system_nic($Interface);
        $name2=$nic->NICNAME . "(".$nic->IPADDR.")";
        $html[]="<td nowrap><strong>$name $name2</strong></td>";

        $action=$tpl->icon_interface("Loadjs('$page?interface-change-action=$instance_id&interface=$Interface')","AsPostfixAdministrator");

        $html[]="<td style='width:1%' class='center'nowrap>$action</td>";
        $html[]="</tr>";


    }

    $html[]="</tbody>";
    $html[]="</table>";
    $html[]="
	<script>
	NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS)."
	</script>";
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}

function instance_row():bool{
    $id=$_GET["instance-row"];
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
    $ID=$_GET["instance-popup"];
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
    $ID=$_POST["instance-id"];
    $instancename=$_POST["instancename"];
    $interface=$_POST["interface"];
    $enabled=$_POST["enabled"];

    $q=new lib_sqlite("/home/artica/SQLITE/postfix.db");

    if($ID==0){
        $ligne=$q->mysqli_fetch_array("SELECT ID,instancename FROM postfix_instances WHERE interface='$interface'");
        if(intval($ligne["ID"])>0){
            echo $tpl->post_error("{nic} $interface: {already_used_byinstance} {$ligne["instancename"]}");
            return false;
        }


        admin_tracks("Create a new SMTP instance $instancename");
        $sql="INSERT INTO postfix_instances (instancename,interface,enabled) 
        VALUES('$instancename','$interface',$enabled)";
    }else{
        admin_tracks_post("Update SMTP instance $instancename interface:$interface");
        $ligne=$q->mysqli_fetch_array("SELECT ID,instancename,interface FROM postfix_instances WHERE ID=$ID");
        $oldinterface=$ligne["interface"];
        if($oldinterface<>$interface){
            $ligne=$q->mysqli_fetch_array("SELECT ID,instancename FROM postfix_instances WHERE interface='$interface'");
            if(intval($ligne["ID"])>0){
                echo $tpl->post_error("{nic} $interface: {already_used_byinstance} {$ligne["instancename"]}");
                return false;
            }
        }

        $sql="UPDATE postfix_instances SET instancename='$instancename',
                             interface='$interface',enabled='$enabled' WHERE ID=$ID";

    }

    $q->QUERY_SQL($sql);
    if(!$q->ok){echo $tpl->post_error($q->mysql_error);return false;}
    return true;

}

function table(){

    $page=CurrentPageName();
    $tpl=new template_admin();
    $main=new main_cf();



    $reconfigure=$tpl->framework_buildjs(
        "postfix2.php?multi-reconfigure=yes",
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
    $html[]="<table id='table-postfix-smtp-rules' class=\"table table-stripped\" data-page-size=\"100\" data-paging=\"true\">";
    $html[]="<thead>";
    $html[]="<tr>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{status}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{servicename}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text' nowrap>{nic}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text' nowrap>&nbsp;</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text' nowrap>{execution_time}</th>";
    $html[]="<th data-sortable=true class='text-capitalize center' data-type='text' >- </th>";
    $html[]="<th data-sortable=true class='text-capitalize center' data-type='text' >{action} </th>";
    $html[]="<th data-sortable=true class='text-capitalize center' data-type='text' >{enabled} </th>";
    $html[]="<th data-sortable=true class='text-capitalize center' data-type='text' >{delete} </th>";
    $html[]="</tr>";
    $html[]="</thead>";
    $html[]="<tbody>";

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
        if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
        $status=instance_line($id,$ligne);
        $action=$status[1];
        $servicename=$status[2];
        $exectime=$status[4];
        $refresh=$tpl->icon_refresh("Loadjs('$page?instance-row=$id')");

        $nic_edit=$tpl->icon_interface("Loadjs('$page?interface-change-js=$id')","AsPostfixAdministrator");

        $html[]="<tr class='$TRCLASS' id='$iddiv'>";
        $html[]="<td style='width:1%' nowrap><center id='instace-status-$id'>{$status[0]} </td>";
        $html[]="<td nowrap><span id='instace-prg-$id'>$servicename</span></td>";
        $html[]="<td nowrap width='1%' nowrap><span id='instace-ip-$id'>{$status[3]}</span></td>";
        $html[]="<td style='vertical-align:middle' width=1% class='center'>$nic_edit</td>";
        $html[]="<td nowrap width='1%' nowrap><span id='instace-time-$id'>$exectime</span></td>";
        $html[]="<td style='vertical-align:middle' width=1% class='center'>$refresh</td>";
        $html[]="<td style='vertical-align:middle' width=1% class='center' id='instance-action-$id'>$action</td>";
        $html[]="<td style='vertical-align:middle' width=1% class='center'>".$tpl->icon_check($ligne["enabled"],"Loadjs('$page?instance-enable=$id')",null,"AsPostfixAdministrator")."</td>";
        $html[]="<td style='vertical-align:middle' width=1% class='center'>".$tpl->icon_delete("Loadjs('$page?instance-delete=$id')","AsPostfixAdministrator")."</td>";
        $html[]="</tr>";


    }

    $html[]="</tbody>";
    $html[]="<tfoot>";

    $html[]="<tr>";
    $html[]="<td colspan='8'>";
    $html[]="<ul class='pagination pull-right'></ul>";
    $html[]="</td>";
    $html[]="</tr>";
    $html[]="</tfoot>";
    $html[]="</table>";

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

    echo $tpl->_ENGINE_parse_body(@implode("\n", $html));
    return true;

}
function instance_line($id,$ligne=array()):array{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $STATUS_LABEL[0]="<span class='label label-danger'>";
    $STATUS_LABEL[1]="<span class='label label-warning'>";
    $STATUS_LABEL[2]="<span class='label label-primary'>";

    $GLOBALS["CLASS_SOCKETS"]->getFrameWork("postfix2.php?multi-status=$id");
    $STATUS=unserialize(@file_get_contents(PROGRESS_DIR."/postmulti-$id.status"));
    $STATUSA=$STATUS["STATUS"];
    $STATUSB=$STATUS["STATUS_TEXT"];
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

    if(isset($STATUS["NOT_INSTALLED"])){
        $action=$tpl->button_medium("{install}",
            "red","Loadjs('$page?instance-install=$id')");
    }

    if(count($ligne)<2){
        $q=new lib_sqlite("/home/artica/SQLITE/postfix.db");
        $ligne=$q->mysqli_fetch_array("SELECT * from postfix_instances WHERE id='$id'");

    }
    $servicename=$ligne["instancename"];
    $nic=$ligne["interface"];
    $servicename=$tpl->td_href("$servicename",null,"Loadjs('$page?instance-js=$id')");
    $snic=new system_nic($nic);
    $addresses="$nic $snic->NICNAME - $snic->IPADDR";
    $stime=$tpl->_ENGINE_parse_body($STATUS["TIME"]);

    return array($iconstatus,$action,$servicename,$addresses,$stime);

}