<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
include_once(dirname(__FILE__)."/ressources/class.sockets.inc");
$GLOBALS["CLASS_SOCKETS"]=new sockets();

if(isset($_GET["popup"])){popup();exit;}
if(isset($_GET["run"])){Run();exit;}
js();

function js():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $title=$tpl->_ENGINE_parse_body("{optimizations_for_vm}");
    $VM_DISK_SLOW=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("VM_DISK_SLOW"));
    if($VM_DISK_SLOW==1){
        $VM_DISK_SLOW_DEVICE=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("VM_DISK_SLOW_DEVICE");
        $title=$tpl->_ENGINE_parse_body("{slow_storage_detected_on} $VM_DISK_SLOW_DEVICE");
    }

    return $tpl->js_dialog10($title,"$page?popup=yes",800);

}
function popup():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();

    $VM_DISK_SLOW=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("VM_DISK_SLOW"));
    if($VM_DISK_SLOW==1){
        $VM_DISK_SLOW_DEVICE=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("VM_DISK_SLOW_DEVICE");
        $Para1=$tpl->_ENGINE_parse_body("{slow_storage_explain}");
        $Para1=str_replace("%s","<strong>$VM_DISK_SLOW_DEVICE</strong>",$Para1);
        $f[]="<div style='border: 1px solid #CCCCCC;padding: 0px 10px 0px 20px;border-radius: 5px;margin-bottom: 10px'>";
        $f[]="<p>$Para1</p>";
        $f[]="</div>";
    }

    $js="Loadjs('$page?run=yes')";
    $btn=$tpl->button_autnonome("{optimize}",$js,ico_run,"AsSystemAdministrator",350,"btn-primary",80);

    $f[]="<div style='border: 1px solid #CCCCCC;padding: 0px 10px 0px 20px;border-radius: 5px;'>";
    $f[]="<H2>{optimizations_for_vm}</H2>";
    $f[]="<div style='margin-top: -10px;margin-bottom: 20px;'><small>{optimizations_for_vm_exp}</small></div>";
    $f[]="<p style='font-size:14px'>{optimizations_for_vm_perform}</p>";
    $f[]="<div style='text-align: right;margin-top: 20px;margin-bottom: 20px;margin-right: 64px;'>$btn</div>";
    $f[]="</div>";
    echo $tpl->_ENGINE_parse_body($f);
    return true;

}
function Run():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $users=new usersMenus();
    header("content-type: application/x-javascript");
    if(!$users->AsSystemAdministrator){
        $tpl->js_error("{ERROR_NO_PRIVS}");
        return admin_tracks("Unprivilegied Access on $page");
    }
    // Un refus du daemon (route absente, accès) ne doit pas faire disparaître
    // l'alerte : les clés ne sont remises à zéro qu'une fois l'appel accepté.
    $json=json_decode((string)$GLOBALS["CLASS_SOCKETS"]->REST_API("/vmtools/savediskparams"));
    if(!is_object($json) || !property_exists($json,"Status") || !$json->Status){
        $err=(is_object($json) && property_exists($json,"Error"))?(string)$json->Error:"";
        $tpl->js_error($err==""?"{error}":$err);
        return false;
    }
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("VM_DISK_SLOW",0);
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("DiskVMMustOptimize",0);
    echo "dialogInstance10.close();\n";
    // La cloche n'a pas de rafraîchissement périodique : sans ce rechargement,
    // l'alerte et son compteur restaient affichés jusqu'au rechargement de la page.
    echo "LoadAjaxSilent('artica-notifs-barr','fw.icon.top.php?notifs=yes');";
    return admin_tracks("Saving Disk optimizations for Virtual machines");
}