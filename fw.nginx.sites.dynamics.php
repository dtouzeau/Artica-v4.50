<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
include_once(dirname(__FILE__)."/ressources/class.nginx.params.inc");
include_once(dirname(__FILE__)."/ressources/class.sockets.inc");
$GLOBALS["CLASS_SOCKETS"]=new sockets();

if(isset($_GET["popup"])){popup();exit;}
if(isset($_POST["service_id"])){Save();exit;}

js();
function js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();$tpl->CLUSTER_CLI=true;
    $ID=intval($_GET["service-id"]);
    $servicename=get_servicename($ID);
    return $tpl->js_dialog3("$servicename:{APP_OSPF}","$page?popup=$ID&service-id=$ID");
}
function get_servicename($ID):string{
    $ID=intval($ID);
    if($ID==0){return "Unknown";}
    $q                          = new lib_sqlite(NginxGetDB());
    $ligne=$q->mysqli_fetch_array("SELECT servicename FROM nginx_services WHERE ID=$ID");
    return strval($ligne["servicename"]);
}

function popup():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();$tpl->CLUSTER_CLI=true;
    $ID=intval($_GET["popup"]);
    $socksngix=new socksngix($ID);
    $ForwardServersDynamics =   intval($socksngix->GET_INFO("ForwardServersDynamics"));
    $FSDynamicsExt          =   intval($socksngix->GET_INFO("FSDynamicsExt"));
    $FSDynamicsSrc          =   trim($socksngix->GET_INFO("FSDynamicsSrc"));
    $FSDynamicsDst          =   trim($socksngix->GET_INFO("FSDynamicsDst"));
    $form[]=$tpl->field_hidden("service_id",$ID);
    $form[]=$tpl->field_checkbox("ForwardServersDynamics","{enable_feature}",$ForwardServersDynamics,true);
    $form[]=$tpl->field_text("FSDynamicsSrc","{source_domain}",$FSDynamicsSrc);
    $form[]=$tpl->field_text("FSDynamicsDst","{destination_domain}",$FSDynamicsDst);
    $form[]=$tpl->field_checkbox("FSDynamicsExt","{dynamic_extensions}",$FSDynamicsExt);
    echo $tpl->form_outside("",$form,"{nginx_dynamic_forward_explain}","{apply}","dialogInstance3.close();LoadAjaxSilent('nginx-hosts-$ID','fw.nginx.sites.php?www-hosts2=$ID');NgixSitesReload();","AsSystemWebMaster");
    return true;
}
function Save():bool{
    $tpl=new template_admin();$tpl->CLUSTER_CLI=true;
    $tpl->CLEAN_POST();
    $service_id=intval($_POST["service_id"]);
    unset($_POST["service_id"]);
    $socksngix=new socksngix($service_id);

    foreach ($_POST as $key=>$val){
        $socksngix->SET_INFO($key,$val);
    }
    return admin_tracks_post("Set Reverse Proxy dynamic routing");
}
function isHarmpID():bool{
    if(!isset($_SESSION["HARMPID"])){
        return false;
    }
    if(intval($_SESSION["HARMPID"])==0){
        return false;
    }

    return true;
}
function NginxGetDB():string{
    if(!isHarmpID()){
        return "/home/artica/SQLITE/nginx.db";
    }
    $Gpid=$_SESSION["HARMPID"];
    return "/home/artica/SQLITE/nginx.$Gpid.db";
}