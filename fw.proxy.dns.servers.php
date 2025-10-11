<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.system.network.inc");
include_once(dirname(__FILE__)."/ressources/class.dnsdist.inc");
if(isset($_GET["top-status"])){top_status();exit;}
if(isset($_GET["proxy-dns-left-status"])){left_status();exit;}
if(isset($_GET["parameters"])){parameters();exit;}
if(isset($_POST["SquidBackendOutfaceOutface"])){HaClusterProxyUseUnbound_Save();exit;}
if(isset($_POST["PPROXY"])){parameters_proxy_save();exit;}

if(isset($_GET["HaClusterProxyUseUnbound-js"])){HaClusterProxyUseUnbound_js();exit;}
if(isset($_GET["HaClusterProxyUseUnbound-popup"])){HaClusterProxyUseUnbound_popup();exit;}


page();

function page():bool{

    $tpl=new template_admin();
    $page=CurrentPageName();

    $Interval=$tpl->RefreshInterval_js("proxy-dns-left-status",$page,"proxy-dns-left-status=yes");

    $html="<table style='width:100%'>
        <tr>
        <td colspan='2'>
            <div id='top-status'></div>
        </td>
        </tr>
        
         <tr>
            <td style='vertical-align:top;width:336px'><div id='proxy-dns-left-status'></div></td>
            <td style='vertical-align:top'><div id='right-status'></div></td>
        </tr>
        </table>
        <script>".js_reload()."
        $Interval
        </script>
        
        ";
echo $html;
return true;
}

function top_status():bool{
    $tpl=new template_admin();
    $widget_intro=$tpl->widget_h("green",ico_params,"{APP_SQUID}","{proxy_use_its_own_dns}",null,"minheight:119px");


    $HaClusterClient= intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HaClusterClient"));
   VERBOSE("HaClusterClient:$HaClusterClient",__LINE__);
    if($HaClusterClient==1){
        $widget_intro=$tpl->widget_h("green",ico_params,"HaCluster","{proxy_use_its_own_dns}",null,"minheight:119px");

        $HaClusterGBConfig=unserialize($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HaClusterGBConfig"));
        if(!$HaClusterGBConfig){
            $HaClusterGBConfig=array();
        }
        if(!is_array($HaClusterGBConfig)){
            $HaClusterGBConfig=array();
        }
        if(!isset($HaClusterGBConfig["HaClusterUseLBAsDNS"])){$HaClusterGBConfig["HaClusterUseLBAsDNS"]=0;}
        $HaClusterUseLBAsDNS=intval($HaClusterGBConfig["HaClusterUseLBAsDNS"]);
        if($HaClusterUseLBAsDNS==1){
            $widget_intro=$tpl->widget_h("green",ico_params,"HaCluster","{use_load_balancer_as_dns}",null,"minheight:119px");
        }
    }



    $html[]="<table style='width:100%'>";
    $html[]="<tr>";
    $html[]="<td style='width:20%'>$widget_intro</td>";

    if (HaClusterProxyUseUnbound()==1){
        $html=HaClusterProxyUseUnbound_servers($html);
        $html[]="</tr>";
        $html[]="</table>";
        echo $tpl->_ENGINE_parse_body($html);
        return true;
    }


    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/proxy/dns/check"));
    if (json_last_error()> JSON_ERROR_NONE) {
        echo $tpl->div_error("Decoding: ".json_last_error_msg());
        return false;
    }
    if(!$json->Status){
        echo $tpl->div_error($json->Error);
        return false;
    }
    if(property_exists($json->Info,"dnsNameservers")){
        foreach($json->Info->dnsNameservers as $Nameserver=>$error){
            $bg="green";
            if(strlen($error)>1){
                $bg="yellow";
            }
            $SquidNameServer1=$tpl->widget_h( $bg,"fas fa-server",$Nameserver,"{primary_dns}",$error,"minheight:119px");
            $html[]="<td style='padding-left:5px;width:20%'>$SquidNameServer1</td>";

        }

    }


    $html[]="</tr>";
    $html[]="</table>";
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}

function js_reload():string{
    $page=CurrentPageName();
    $js[]="LoadAjaxSilent('right-status','$page?parameters=yes')";
    return @implode(";",$js);
}
function HaClusterProxyUseUnbound_js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    return $tpl->js_dialog2("{parameters}","$page?HaClusterProxyUseUnbound-popup=yes",550);
}




function dns_perfs_to_ico($Score):string{
    VERBOSE("SCORE: $Score",__LINE__);
    $Score=intval($Score);
    VERBOSE("SCORE: $Score",__LINE__);
    $icon="<span class='label label-danger'>{very_low}</span>&nbsp;";
    if($Score==0){
        VERBOSE("SCORE: $Score -> FAILED!!",__LINE__);
        return "<span class='label label-danger'>{failed}</span>&nbsp;";
    }
    if($Score>20){
        $icon="<span class='label label-warning'>{poor}</span>&nbsp;";
    }
    if($Score>30){
        $icon="<span class='label label-warning'>{medium}</span>&nbsp;";
    }

    if($Score>45){
        $icon="<span class='label label-primary'>{good}</span>&nbsp;";
    }
    if($Score>2300){
         return "<span class='label label-warning'>{medium}</span>&nbsp;";
    }


    return $icon;

}

function parameters_proxy():bool{
    $tpl=new template_admin();
    $tpl->CLUSTER_CLI=true;
    $SquidNameServer1=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidNameServer1");
    $SquidNameServer2=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidNameServer2");
    $SquidNameServer3=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidNameServer3");
    $ProxyUseOwnDNS=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ProxyUseOwnDNS"));
    if(strlen(trim("$SquidNameServer1$SquidNameServer2$SquidNameServer3"))<3){
        $ProxyUseOwnDNS=0;
    }

    $tpl->table_form_field_js("Loadjs('fw.proxy.general.php?dns-real=yes');");


    if($ProxyUseOwnDNS==0){
        $tpl->table_form_field_bool("{proxy_use_its_own_dns}",false,ico_params);
    }else{
        $tpl->table_form_field_bool("{proxy_use_its_own_dns}",true,ico_params);
        $tpl->table_form_field_text("{primary_dns}",$SquidNameServer1,ico_server);
        if(strlen($SquidNameServer2)>2) {
            $tpl->table_form_field_text("{secondary_dns}", $SquidNameServer2, ico_server);
        }
        if(strlen($SquidNameServer3)>2) {
            $tpl->table_form_field_text("{nameserver} 3", $SquidNameServer3, ico_server);
        }
    }

    echo $tpl->_ENGINE_parse_body($tpl->table_form_compile());

    $TINY_ARRAY["TITLE"]="{dns_settings}";
    $TINY_ARRAY["ICO"]="fas fa-database";
    $TINY_ARRAY["EXPL"]="{dns_settings_proxy_explain}";
    $TINY_ARRAY["BUTTONS"]=null;
    $headsjs= "Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";
    echo "<script>$headsjs</script>";

    return true;

}
function parameters_HaClusterProxyUseUnbound():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $tpl->table_form_field_js("Loadjs('$page?HaClusterProxyUseUnbound-js=yes');");
    $SquidBackendOutfaceOutface=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidBackendOutfaceOutface");

    $HaClusterProxyUseDNSCacheDenyPTR=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HaClusterProxyUseDNSCacheDenyPTR"));

    $tpl->table_form_field_text("{outgoing_interface}",$SquidBackendOutfaceOutface,ico_nic);

    $tpl->table_form_field_js("");
    $tpl->table_form_field_bool("{deny} PTR",$HaClusterProxyUseDNSCacheDenyPTR,ico_params);
    echo  $tpl->_ENGINE_parse_body($tpl->table_form_compile());

    return true;
}
function HaClusterProxyUseUnbound_popup():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $SquidBackendOutfaceOutface=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidBackendOutfaceOutface");
    $form[]=$tpl->field_interfaces("SquidBackendOutfaceOutface",$SquidBackendOutfaceOutface);

    $jsrestart=$tpl->framework_buildjs("/hacluster/client/dns/backend/restart",
        "HaClusterBackendDNS.progress",
        "HaClusterBackendDNS.progress.txt",
        "proxydns-progress"
    );

    $html=$tpl->form_outside("", $form,"","{apply}","dialogInstance2.close();LoadAjaxSilent('right-status','$page?parameters=yes');$jsrestart");
    echo $html;
    return true;
}
function HaClusterProxyUseUnbound_Save():bool{
    $tpl=new template_admin();
    $tpl->CLEAN_POST();
    $tpl->SAVE_POSTs();
    return admin_tracks_post("Save DNS Proxy load-balancer backend parameters");
}


function parameters(){
    //
    if (HaClusterProxyUseUnbound()==1){
        return parameters_HaClusterProxyUseUnbound();
    }
    return parameters_proxy();

}

function HaClusterProxyUseUnbound_servers($html=array()):array{
    $tpl=new template_admin();
    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/hacluster/client/dns/backend/lb"));

    if(!$json->Status) {
        $html[]="<td style='padding-left:5px;width:20%'>";
        $html[]=$tpl->div_error($json->Error);
        $html[]="</td>";
        return $html;

    }
    if(!property_exists($json, "Data")){
        $html[]="<td style='padding-left:5px;width:20%'>";
        $html[]=$tpl->div_error("No Data Struct.");
        $html[]="</td>";
        return $html;
    }
    if(!property_exists($json->Data, "servers")){
        $html[]="<td style='padding-left:5px;width:20%'>";
        $html[]=$tpl->div_error("No servers Struct.");
        $html[]="</td>";
        return $html;
    }

    foreach($json->Data->servers as $index=>$jsArray){
        $bg="green";
        if($jsArray->state <> "up"){
            $bg="red";
        }
        $Addr=$jsArray->name;
        $queries=$tpl->FormatNumber($jsArray->queries);
        $SquidNameServer1=$tpl->widget_h( $bg,"fas fa-server",$queries,$Addr,"minheight:119px");
        $html[]="<td id=$index style='padding-left:5px;width:20%'>$SquidNameServer1</td>";

    }
    return $html;
}

function parameters_proxy_save(){
    $tpl=new template_admin();
    $tpl->CLEAN_POST();
    admin_tracks("Saved Proxy DNS parameters");
    if($_POST["PPROXY"]==1){
        $GLOBALS["CLASS_SOCKETS"]->SET_INFO("SquidDNSUseSystem",0);
    }else{
        $GLOBALS["CLASS_SOCKETS"]->SET_INFO("SquidDNSUseSystem",1);
    }
    unset($_POST["PPROXY"]);
    $tpl->SAVE_POSTs();

}
function HaClusterProxyUseUnbound():int {
    $EnableHaClusterClient = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HaClusterClient"));
	$SQUIDEnable = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SQUIDEnable"));
	$HaClusterProxyUseUnbound = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HaClusterProxyUseUnbound"));
	$ProxyUseOwnDNS = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ProxyUseOwnDNS"));
	if($SQUIDEnable == 0 ){
        $HaClusterProxyUseUnbound = 0;
	}

	if ($ProxyUseOwnDNS == 0 ){
        $HaClusterProxyUseUnbound = 0;
	}
	if ($EnableHaClusterClient == 0) {
        $HaClusterProxyUseUnbound = 0;
	}
	return $HaClusterProxyUseUnbound;
}

function left_status_hacluster():bool{

    $tpl=new template_admin();
    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/hacluster/client/dns/backend/status"));

    $jsrestart=$tpl->framework_buildjs("/hacluster/client/dns/backend/restart",
        "HaClusterBackendDNS.progress",
        "HaClusterBackendDNS.progress.txt",
        "proxydns-progress"
    );

    if(!$json->Status){
        $html[]=$tpl->widget_rouge($json->Error,"{error}");
    }else{
        $ini=new Bs_IniHandler();
        $ini->loadString($json->Info);
        $html[]=$tpl->SERVICE_STATUS($ini, "APP_HACLUSTER_BACKEND_DNS",$jsrestart);
    }


     $TitleTiny="{dns_settings}";
    $TINY_ARRAY["TITLE"]=$TitleTiny;
    $TINY_ARRAY["ICO"]="fas fa-database";
    $TINY_ARRAY["EXPL"]="{dns_settings_proxy_explain}";
    $TINY_ARRAY["BUTTONS"]="";
    $headsjs= "Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";
    $page=CurrentPageName();
    echo "<script>
    $headsjs
    LoadAjaxSilent('top-status','$page?top-status=yes');
    </script>";

    echo $tpl->_ENGINE_parse_body($html);
    return true;

}

function left_status():bool{
    $HaClusterClient= intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HaClusterClient"));
    if($HaClusterClient==1){
        return left_status_hacluster();
    }
    $tpl=new template_admin();
    $jsreload=js_reload();
    $TitleTiny="{dns_settings}";
    $btns[] = "<div class=\"btn-group\" data-toggle=\"buttons\">";

    $jsbut=$tpl->framework_buildjs(
            "squid2.php?proxy-lb-install=yes",
            "proxydns.progress",
            "proxydns.log",
            "top-status",
            $jsreload);

        $button["name"]="{install2}";
        $button["js"]=$jsbut;
        $html[]=$tpl->widget_h("gray","far fa-times-circle","{disabled}","{load_balancer}",$button);



    $btns[] = "</div>";

    $TINY_ARRAY["TITLE"]=$TitleTiny;
    $TINY_ARRAY["ICO"]="fas fa-database";
    $TINY_ARRAY["EXPL"]="{dns_settings_proxy_explain}";
    $TINY_ARRAY["BUTTONS"]=@implode("",$btns);
    $headsjs= "Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";
    $page=CurrentPageName();
    echo "<script>$headsjs
    LoadAjaxSilent('top-status','$page?top-status=yes');
    </script>";

    echo $tpl->_ENGINE_parse_body($html);
    return true;
}
