<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
include_once(dirname(__FILE__)."/ressources/class.sockets.inc");
$GLOBALS["CLASS_SOCKETS"]=new sockets();

if(isset($_GET["start-popup"])){start();exit;}
if(isset($_GET["search"])){search();exit;}
if(isset($_GET["buttons"])){buttons();exit;}
if(isset($_GET["add-js"])){add_js();exit;}
if(isset($_GET["add-popup"])){add_popup();exit;}
if(isset($_POST["new-network"])){add_save();exit;}
if(isset($_GET["negative"])){negative_switch();exit;}
if(isset($_GET["enabled"])){enabled_switch();exit;}
if(isset($_GET["delete"])){delete();exit;}


js();

function js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
   return  $tpl->js_dialog("{IDS}: {HOME_NET}","$page?start-popup=yes");
}
function start():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    echo "<div id='dialog-ids-button' style='margin-bottom: 10px'></div>";
    echo $tpl->search_block($page);
    return true;
}
function buttons():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $function=$_GET["function"];
    $topbuttons[] = array("Loadjs('$page?add-js=yes&function=$function');", ico_plus, "{new_network}");
    echo $tpl->_ENGINE_parse_body( $tpl->th_buttons($topbuttons));
    return true;
}
function add_js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $function=$_GET["function"];
    return  $tpl->js_dialog2("{IDS}: {HOME_NET} {new_network}","$page?add-popup=yes&function=$function");
}
function add_save():bool{
    $tpl=new template_admin();
    $net=urlencode($_POST["new-network"]);
    $jsonStatus=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API_SURICATA("/config/homenet/$net/0/1"));
    if(!$jsonStatus->Status){
        $html[]=$tpl->div_error("{error} API||$jsonStatus->Error");
        echo $tpl->_ENGINE_parse_body(@implode("\n", $html));
        return false;
    }
   return admin_tracks_post("Create a new IDS HomeNet {$_POST["new-network"]}");
}
function delete():bool{
    $net=urlencode($_GET["delete"]);
    $netEnc=urlencode($net);
    $md5=$_GET["md"];
    $GLOBALS["CLASS_SOCKETS"]->REST_API_SURICATA("/config/homenet-del/$netEnc");
    echo "$('#$md5').remove();";
    return admin_tracks_post("Delete IDS HomeNet $net");
}
function negative_switch():bool{
    $tpl=new template_admin();
    $net=$_GET["negative"];
    $netEnc=urlencode($net);

    $jsonStatus=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API_SURICATA("/status"));
    if(!$jsonStatus->Status){
        $html[]=$tpl->div_error("{error} API||$jsonStatus->Error");
        echo $tpl->_ENGINE_parse_body(@implode("\n", $html));
        return false;
    }
    $GlobalConfig=$jsonStatus->Info;
    $HomeNets=$GlobalConfig->HomeNets->{$net};
    if($HomeNets->Negative==1){
        $GLOBALS["CLASS_SOCKETS"]->REST_API_SURICATA("/config/homenet/$netEnc/0/$HomeNets->Enabled");
        return admin_tracks_post("Switch negative IDS HomeNet $net");
    }
    $GLOBALS["CLASS_SOCKETS"]->REST_API_SURICATA("/config/homenet/$netEnc/1/$HomeNets->Enabled");
    return admin_tracks_post("Switch postive IDS HomeNet $net");
}
function enabled_switch():bool{
    $tpl=new template_admin();
    $net=$_GET["enabled"];
    $netEnc=urlencode($net);
    $jsonStatus=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API_SURICATA("/status"));
    if(!$jsonStatus->Status){
        $html[]=$tpl->div_error("{error} API||$jsonStatus->Error");
        echo $tpl->_ENGINE_parse_body(@implode("\n", $html));
        return false;
    }
    $GlobalConfig=$jsonStatus->Info;
    $HomeNets=$GlobalConfig->HomeNets->{$net};
    writelogs("IDS HomeNet $net Enabled: $HomeNets->Enabled",__FUNCTION__,__FILE__,__LINE__);
    if($HomeNets->Enabled==1){
        writelogs("IDS HomeNet $net Enabled: --> 0",__FUNCTION__,__FILE__,__LINE__);
        $GLOBALS["CLASS_SOCKETS"]->REST_API_SURICATA("/config/homenet/$netEnc/$HomeNets->Negative/0");
        return admin_tracks_post("Switch to disable IDS HomeNet $net");
    }
    writelogs("IDS HomeNet $net Enabled: --> 1",__FUNCTION__,__FILE__,__LINE__);
    $GLOBALS["CLASS_SOCKETS"]->REST_API_SURICATA("/config/homenet/$netEnc/$HomeNets->Negative/1");
    return admin_tracks_post("Switch to enable IDS HomeNet $net");
}

function add_popup():bool{
    $tpl=new template_admin();
    $function=$_GET["function"];
    $js[]="dialogInstance2.close()";
    $js[]="$function();";
   echo  $tpl->BigNetworkField("new-network","{new_network}","{HOME_NET_SURICATA}",
       "192.168.0.0/16",@implode(";",$js));
   return true;
}
function search(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $function=$_GET["function"];
    $jsonStatus=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API_SURICATA("/status"));
    if(!$jsonStatus->Status){
        $html[]=$tpl->div_error("{error} API||$jsonStatus->Error");
        echo $tpl->_ENGINE_parse_body(@implode("\n", $html));
        return;
    }
    $GlobalConfig=$jsonStatus->Info;
    $HomeNets=$GlobalConfig->HomeNets;
    $th="data-sortable=true class='text-capitalize' data-type='text'";
    $html[]="<table id='table-ids-homes' class=\"footable table table-stripped\" data-page-size=\"100\" data-paging=\"true\">";
    $html[]="<thead>";
    $html[]="<tr>";
    $html[]="<th $th>{HOME_NET}</th>";
    $html[]="<th $th>{negation}</th>";
    $html[]="<th $th>{enabled}</th>";
    $html[]="<th $th>DEL</center></th>";
    $html[]="</tr>";
    $html[]="</thead>";
    $html[]="<tbody>";
    $ss="style='width:1%' nowrap";
    $TRCLASS="";

    $search=$_GET["search"];
    if(strlen($search)>0){
        $search="*$search*";
        $search=str_replace("**","*",$search);
        $search=str_replace("**","*",$search);
        $search=str_replace("*",".*?",$search);
    }

    foreach ($HomeNets as $net=>$main){
        if(strlen($search)>1){
            if(!preg_match("#$search#",$net)){continue;}
        }
        $id=md5($net);
        if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
        $netEnc=urlencode($net);
        $negative=$tpl->icon_check($main->Negative,"Loadjs('$page?negative=$netEnc');");
        $enabled=$tpl->icon_check($main->Enabled,"Loadjs('$page?enabled=$netEnc');");
        $delete=$tpl->icon_delete("Loadjs('$page?delete=$netEnc&md=$id');");
        $html[]="<tr class='$TRCLASS' id='$id'>";
        $html[]="<td ><strong style='font-size:16px'>$net</strong></td>";
        $html[]="<td $ss>$negative</td>";
        $html[]="<td $ss>$enabled</span></td>";
        $html[]="<td $ss>$delete</td>";
        $html[]="</tr>";
    }
    $html[]="</tbody>";
    $html[]="<tfoot>";

    $html[]="<tr>";
    $html[]="<td colspan='3'>";
    $html[]="<ul class='pagination pull-right'></ul>";
    $html[]="</td>";
    $html[]="</tr>";
    $html[]="</tfoot>";
    $html[]="</table><div></div>";
    $html[]="
	<script>
	LoadAjax('dialog-ids-button','$page?buttons=yes&function=$function');
	NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS)."
$(document).ready(function() { $('.footable').footable(
{\"filtering\": {\"enabled\": false},\"sorting\": {\"enabled\": false}}); });
</script>";
    echo $tpl->_ENGINE_parse_body(@implode("\n", $html));
}