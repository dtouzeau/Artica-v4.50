<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
if(isset($_POST["eth"])){Save();exit;}

popup();

function popup():bool{
//explain_mirror_nics
    $eth=$_GET["eth"];
    $tpl=new template_admin();
    $nic=new system_nic($eth);

    $form[]=$tpl->field_hidden("eth",$eth);
    $form[]=$tpl->field_checkbox("mirror","{mirror}",$nic->mirror,true);
    $form[]=$tpl->field_ipv4("mirrorgateway","{destination_address}",$nic->mirrorgateway);
    echo $tpl->form_outside(null,$form,"{explain_mirror_nics}",
        "{apply}",
        "LoadAjax('network-interfaces-table','fw.network.interfaces.php?table=yes');");
    return true;
}
function Save():bool{

    $tpl=new template_admin();
    $tpl->CLEAN_POST();
    $eth=$_POST["eth"];
    $nic=new system_nic($eth);
    $nic->mirror=$_POST["mirror"];
    $nic->mirrorgateway=$_POST["mirrorgateway"];
    if(!$nic->SaveNic()){
        echo $tpl->post_error($nic->mysql_error);
        return false;
    }
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/system/network/interface/mirror");
    return admin_tracks_post("Save Interface mirror settings");

}