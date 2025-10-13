<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
include_once(dirname(__FILE__)."/ressources/class.nginx.params.inc");
include_once(dirname(__FILE__)."/ressources/class.sockets.inc");
$GLOBALS["CLASS_SOCKETS"]=new sockets();
if(isset($_GET["tabs"])){tabs();exit;}
if(isset($_GET["flat"])){flat();exit;}
if(isset($_GET["flat-config"])){flat_config();exit;}
if(isset($_GET["flat-switch"])){flat_switch();exit;}
js();


function js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $serviceid=intval($_GET["serviceid"]);
    $srv=get_servicename($serviceid);
    return $tpl->js_dialog5("$srv: {cache_expert}","$page?tabs=$serviceid");
}
function tabs():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $serviceid=intval($_GET["tabs"]);
    $array["{global_parameters}"]="$page?flat=$serviceid";
    echo $tpl->tabs_default($array);
    return true;
}
function flat():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $serviceid=intval($_GET["flat"]);
    echo "<div id='flat-$serviceid'></div>";
    echo "<script>LoadAjax('flat-$serviceid','$page?flat-config=$serviceid');</script>";
    return true;
}
function flat_config():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $serviceid=intval($_GET["flat-config"]);
    $socknginx=new socksngix($serviceid);
    $AdvancedCaching=intval($socknginx->GET_INFO("AdvancedCaching"));

    if($AdvancedCaching==0){
        $tpl->table_form_field_js("Loadjs('$page?flat-switch=$serviceid')");
        $tpl->table_form_field_bool("{advanced_caching}",0,ico_disabled);
        echo $tpl->table_form_compile();
        return true;
    }

    return true;
}
function flat_switch():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $serviceid=intval($_GET["flat-switch"]);
    $srv=get_servicename($serviceid);
    $socknginx=new socksngix($serviceid);
    $AdvancedCaching=intval($socknginx->GET_INFO("AdvancedCaching"));
    if($AdvancedCaching==0){
        $AdvancedCaching=1;
    }else{
        $AdvancedCaching=0;
    }
    $socknginx->SET_INFO("AdvancedCaching",$AdvancedCaching);
    header("content-type: application/x-javascript");
    echo refresh_global_no_close($serviceid);

    $GLOBALS["CLASS_SOCKETS"]->REST_API_NGINX("/reverse-proxy/singlehup/$serviceid");

    return admin_tracks("Set Caching — Expert Mode enable=$AdvancedCaching for reverse-proxy service $srv");

}
function refresh_global_no_close($serviceid):string{
    $page=CurrentPageName();
    $f[]="LoadAjax('flat-$serviceid','$page?flat-config=$serviceid')";
    $f[]="LoadAjax('www-parameters-$serviceid','fw.nginx.sites.php?www-parameters2=$serviceid');";
    return @implode(";",$f)."\n";

}
function get_servicename($ID):string{
    $ID=intval($ID);
    if($ID==0){return "Unknown";}
    $q                          = new lib_sqlite(NginxGetDB());
    $ligne=$q->mysqli_fetch_array("SELECT servicename FROM nginx_services WHERE ID=$ID");
    return $ligne["servicename"];
}
function NginxGetDB():string{
    if(!isHarmpID()){
        return "/home/artica/SQLITE/nginx.db";
    }
    $Gpid=$_SESSION["HARMPID"];
    return "/home/artica/SQLITE/nginx.$Gpid.db";
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