<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
include_once(dirname(__FILE__)."/ressources/class.sockets.inc");
include_once(dirname(__FILE__)."/ressources/class.computers.inc");
include_once(dirname(__FILE__).'/framework/class.unix.inc');
$GLOBALS["CLASS_SOCKETS"]=new sockets();
$GLOBALS["CLASS_UNIX"]=new unix();

if(isset($_GET["popup"])){popup();exit;}
if(isset($_POST["persist"])){Save();exit;}
js();


function js(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    return $tpl->js_dialog10("{persistent_interfaces}","$page?popup=yes");

}

function popup(){
    $tpl=new template_admin();
    $data=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/system/network/devrules/get"));
    $q                  = new lib_sqlite("/home/artica/SQLITE/interfaces.db");
    $res=$q->QUERY_SQL("SELECT * FROM persistent");
    $Main=array();
    $form[]=$tpl->field_hidden("persist","yes");
    $MaxCount=0;
    $free=array();
    foreach ($data->Rules as $Mac=>$dev){
        if(preg_match("#^eth([0-9]+)#",$dev,$re)){
            if(intval($re[1])>$MaxCount){
                $MaxCount=intval($re[1]);
            }
        }
        $nic=new system_nic($dev);
        if(!isset($Main[$dev])) {
            $Main[$dev] = "$dev: $nic->NICNAME - $nic->IPADDR";
            continue;
        }
        $free[$Mac]=true;

    }
    if(count($free)>0){
        foreach ($Main as $mac=>$none){
            $MaxCount++;
            $Main["eth$MaxCount"]="eth$MaxCount";
        }
    }

    $Alredy=array();
    foreach ($res as $index=>$ligne){
        $Mac=$ligne["MacAddr"];
        $dev=$ligne["Iface"];
        $default="";
        if(!isset($Alredy[$dev])){
            $default=$dev;
        }
        $form[]=$tpl->field_array_hash($Main,"MAC_$Mac","$Mac",$default);
        $Alredy[$dev]=true;

    }
    echo $tpl->form_outside(null,$form,"{persistent_interfaces_explain}","{apply}","LoadAjax('network-interfaces-table','fw.network.interfaces.php?table=yes')");

}
function Save(){
    $tpl=new template_admin();
    $tpl->CLEAN_POST();
    $f=array();
    foreach ($_POST as $Key=>$val){
        if(preg_match("#^MAC_(.+)$#",$Key,$m)){
            $f[]="('{$m[1]}','$val')";
        }
    }

    $q = new lib_sqlite("/home/artica/SQLITE/interfaces.db");
    $q->QUERY_SQL("DELETE FROM persistent");
    $sql="INSERT OR IGNORE INTO persistent (MacAddr,Iface) VALUES ".implode(",",$f);
    $q->QUERY_SQL($sql);
    if(!$q->ok){
        echo $tpl->post_error($q->mysql_error." $sql");
        return false;
    }
    $data=json_decode( $GLOBALS["CLASS_SOCKETS"]->REST_API("/system/network/devrules/set"));
    if(!$data->Status){
        echo $tpl->post_error($q->mysql_error);
        return false;
    }
    return admin_tracks("Change Network Persistent interface rules OK");
}