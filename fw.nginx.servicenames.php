<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
include_once(dirname(__FILE__)."/ressources/class.nginx.params.inc");
include_once(dirname(__FILE__)."/ressources/class.sockets.inc");
include_once(dirname(__FILE__)."/ressources/class.squid.reverse.inc");
include_once(dirname(__FILE__)."/ressources/class.nginx.tpl.inc");
include_once(dirname(__FILE__)."/ressources/class.modsecurity.tools.inc");
$GLOBALS["CLASS_SOCKETS"]=new sockets();
if(isset($_GET["start"])){Start();exit;}
if(isset($_GET["search"])){table();exit;}

if(isset($_GET["top-buttons"])){top_buttons();exit;}
if(isset($_GET["www-hosts2"])){www_hosts2();exit;}
if(isset($_GET["new-js"])){new_js();exit;}
if(isset($_GET["new-popup"])){new_popup();exit;}
if(isset($_GET["edit-js"])){edit_js();exit;}
if(isset($_GET["edit-popup"])){edit_popup();exit;}
if(isset($_GET["host-delete"])){hosts_delete();exit;}
if(isset($_POST["hosts-id"])){www_hosts_save();exit;}
if(isset($_POST["redirect"])){edit_save();exit;}
if(isset($_POST["new"])){new_save();exit;}

if(isset($_POST["hosts-delete"])){hosts_delete_perform();exit;}
function hosts_delete():bool{
    $md=$_GET["md"];
    $ID=$_GET["service-id"];
    $tpl=new template_admin();$tpl->CLUSTER_CLI=true;
    $value=$_GET["host-delete"];
    $servicename=get_servicename($ID);
    $array["service-id"]=$ID;
    $array["value"]=$value;
    $finale=urlencode(base64_encode(serialize($array)));
    return $tpl->js_confirm_delete("$value / $servicename","hosts-delete",$finale,"$('#$md').remove();");
}
function edit_js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();$tpl->CLUSTER_CLI=true;
    $ID=intval($_GET["service-id"]);
    $function=$_GET["function"];
    $servicename=get_servicename($ID);
    $domain=$_GET["edit-js"];
    $domainec=urlencode($domain);
    $title="$servicename $domain";
    return $tpl->js_dialog2($title,"$page?edit-popup=$domainec&service-id=$ID&function=$function",750);
}
function hosts_delete_perform():bool{
    $tpl=new template_admin();$tpl->CLUSTER_CLI=true;
    $tpl->CLEAN_POST();

    $svalue=base64_decode($_POST["hosts-delete"]);
    $array=unserialize($svalue);
    $ID=$array["service-id"];
    $value=$array["value"];
    $MAIN=HostsToArray($ID);
    unset($MAIN[$value]);
    SaveHosts($MAIN,$ID);
    $GLOBALS["CLASS_SOCKETS"]->CLUSTER_NGINX($ID);
    $servicename=get_servicename($ID);
    admin_tracks("Delete $value from reverse-proxy service $servicename");
    return true;
}
function new_js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();$tpl->CLUSTER_CLI=true;
    $ID=intval($_GET["service-id"]);
    $function =$_GET["function"];
    $servicename=get_servicename($ID);
    return $tpl->js_dialog2("$servicename {new_domain}","$page?new-popup=yes&service-id=$ID&function=$function");
}
function new_popup():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();$tpl->CLUSTER_CLI=true;
    $serviceid=intval($_GET["service-id"]);
    $function =$_GET["function"];
    $js[]="dialogInstance2.close()";
    $js[]="$function()";
    $after=@implode(";",$js);
    echo $tpl->BigDomainField("services-id:$serviceid|new", "{domain}", "{servernames_explain}","",$after);
    return true;

}
function get_servicename($ID):string{
    $ID=intval($ID);
    if($ID==0){return "Unknown";}
    $sock=new socksngix($ID);
    return $sock->GetServiceName();
}
function  HostsToArray($serviceid):array{
    $q=new lib_sqlite(NginxGetDB());
    $MAIN=array();
    $ligne=$q->mysqli_fetch_array("SELECT `servicename`,`hosts` FROM nginx_services WHERE ID=$serviceid");
    $Zhosts=explode("||",$ligne["hosts"]);
    foreach ($Zhosts as $domain){
        if(is_null($domain)){continue;}
        $domain=trim(strtolower($domain));
        if(strlen($domain)==0){continue;}

        if(preg_match("#^(.+?)>(.+)#",$domain,$re)){
            $MAIN[$re[1]]=$re[2];
            continue;
        }
        $MAIN[$domain]="";

    }
    return $MAIN;
}
function ArrayToHosts($MAIN=array()):string{
    $F=array();
    foreach($MAIN as $domain=>$redirect){
        if(is_null($domain)){continue;}
        $domain=trim(strtolower($domain));
        if(strpos($domain, ";")>0){continue;}
        if($domain==""){continue;}
        if($redirect<>null){
            if($redirect==$domain){
                $F[]=$domain;
                continue;
            }
            $domain="$domain>$redirect";
            $F[]=$domain;
            continue;
        }
        $F[]=$domain;
    }
    return trim(@implode("||",$F));
}
function SaveHosts($HostsToArray=array(),$serviceid=0):bool{
    $q=new lib_sqlite(NginxGetDB());
    $newval=ArrayToHosts($HostsToArray);
    $q->QUERY_SQL("UPDATE nginx_services SET `hosts`='$newval',`goodconftime`=0 WHERE ID=$serviceid");
    if(!$q->ok){echo $q->mysql_error;return false;}
    $GLOBALS["CLASS_SOCKETS"]->CLUSTER_NGINX($serviceid);
    $servicename=get_servicename($serviceid);
    $GLOBALS["CLASS_SOCKETS"]->REST_API_NGINX("/reverse-proxy/singlehup/$serviceid");
    return admin_tracks_post("Add/Edit domain for reverse-proxy service $servicename");
}

function new_save():bool{
    $tpl=new template_admin();$tpl->CLUSTER_CLI=true;
    $tpl->CLEAN_POST();
    $serviceid=intval($_POST["services-id"]);
    $value=trim(strtolower($_POST["new"]));

    $MAIN=HostsToArray($serviceid);
    if(isset($MAIN[$value])){return false;}
    $MAIN[$value]="";
    return SaveHosts($MAIN,$serviceid);

}
function www_hosts_save():bool{
    $tpl=new template_admin();$tpl->CLUSTER_CLI=true;
    $ID=$_POST["hosts-id"];
    $F=array();
    $tpl->CLEAN_POST();
    $q=new lib_sqlite(NginxGetDB());
    if(!isset($_POST["redirect"])){$_POST["redirect"]="";}
    $ligne=$q->mysqli_fetch_array("SELECT `servicename`,`hosts` FROM nginx_services WHERE ID=$ID");
    $Zhosts=explode("||",$ligne["hosts"]);

    foreach ($Zhosts as $domain){
        $domain=trim(strtolower($domain));
        if($domain==null){continue;}
        if(preg_match("#^(.+?)>(.+)#",$domain,$re)){
            $MAIN[$re[1]]=$re[2];
            continue;
        }
        $MAIN[$domain]="";

    }
    $domain=trim(strtolower($_POST["hosts"]));
    $redirect=trim(strtolower($_POST["redirect"]));
    $MAIN[$domain]=$redirect;


    foreach($MAIN as $domain=>$redirect){
        $domain=trim(strtolower($domain));
        if(strpos($domain, ";")>0){continue;}
        if($domain==null){continue;}
        if($redirect<>null){
            $domain="$domain>$redirect";
        }
        $F[]=$domain;
    }

    $newval=trim(@implode("||",$F));
    $q->QUERY_SQL("UPDATE nginx_services SET `hosts`='$newval',`goodconftime`=0 WHERE ID=$ID");
    if(!$q->ok){echo $q->mysql_error;return false;}
    $GLOBALS["CLASS_SOCKETS"]->CLUSTER_NGINX($ID);
    $servicename=get_servicename($ID);
    admin_tracks_post("Add/Edit domain for reverse-proxy service  $servicename");
    $GLOBALS["CLASS_SOCKETS"]->REST_API_NGINX("/reverse-proxy/singlehup/$ID");
    return true;
}
function edit_popup():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();$tpl->CLUSTER_CLI=true;
    $ID=intval($_GET["service-id"]);
    $servicename=get_servicename($ID);
    $Type=get_ServiceType($ID);
    $domain=$_GET["edit-popup"];
    $function=$_GET["function"];

    if($Type==13){
       echo $tpl->_ENGINE_parse_body($tpl->div_warning("{nginx_no_redir_option}"));
       return false;
    }
    $MAIN=HostsToArray($ID);
    $redirect=$MAIN[$domain];
    $js[]="dialogInstance2.close()";
    $js[]="$function()";
    $html[]=$tpl->BigUriField("service-id:$ID|hosts:$domain|redirect","$servicename: {redirect_uri}","{nginx_redirect_uri_explain}",$redirect,implode(";",$js));

    echo $tpl->_ENGINE_parse_body($html);
    return true;
}
function edit_save():bool{
    $tpl=new template_admin();
    $tpl->CLEAN_POST();
    $serviceid=intval($_POST["service-id"]);
    $MAIN=HostsToArray($serviceid);
    $domain=trim(strtolower($_POST["hosts"]));
    $redirect=trim(strtolower($_POST["redirect"]));
    $MAIN[$domain]=$redirect;
    return SaveHosts($MAIN,$serviceid);
}
function Start():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $serviceid=intval($_GET["start"]);
    $html[]="<div id='servernames-buttons-$serviceid' style='margin-top:10px;margin-bottom: 10px'></div>";
    $html[]=$tpl->search_block($page,"",null,null,"&service-id=$serviceid");
    echo implode("\n",$html);
    return true;

}

function top_buttons():bool{
    $tpl        = new template_admin();$tpl->CLUSTER_CLI=true;
    $page=CurrentPageName();
    $serviceid=intval($_GET["top-buttons"]);
    $function=$_GET["function"];
    $topbuttons[]=array("Loadjs('$page?new-js=yes&service-id=$serviceid&function=$function');", ico_plus,"{new_domain}");
    $topbuttons[]=array("Loadjs('fw.nginx.sites.dynamics.php?service-id=$serviceid&function=$function')",ico_routes,"{APP_OSPF}");

    $topbuttons[]=array("Loadjs('fw.nginx.sites.letsencrypt.php?service-id=$serviceid&function=$function')",ico_certificate,"{certificate} Let's Encrypt");

    echo $tpl->th_buttons($topbuttons);
    return true;
}

function table():bool{
    $page       = CurrentPageName();
    $tpl        = new template_admin();$tpl->CLUSTER_CLI=true;
    $q          = new lib_sqlite(NginxGetDB());
    $ID         = intval($_GET["service-id"]);
    $function   = $_GET["function"];
    $socksngix  = new socksngix($ID);

    if(!$q->FIELD_EXISTS("nginx_services","ResolvErrDetail")){
        $q->QUERY_SQL("ALTER TABLE nginx_services ADD ResolvErrDetail TEXT NOT NULL DEFAULT ''");
    }
    $ligne      = $q->mysqli_fetch_array("SELECT servicename,hosts,ResolvErrDetail FROM nginx_services WHERE ID=$ID");

    $StyleRow="font-size:18px";
    $ExpireCert="";


    $ResolvErrDetail=unserialize($ligne["ResolvErrDetail"]);
    $ssl_certificate = $socksngix->GET_INFO("ssl_certificate");
    $ssl_certificates = array();
    if(strlen($ssl_certificate)>3){
        $sock=new sockets();
        $json=json_decode($sock->REST_API_NGINX("/reverse-proxy/certinfo/$ID"));
        if(!$json->Status){
            $html[]=$tpl->div_error($json->Error);
        }

        if(is_array($json->data->DNSNames)) {
            foreach ($json->data->DNSNames as $index => $domain) {
                $ssl_certificates[$domain] = true;
            }
        }
        $ExpireCert=distanceOfTimeInWords($json->data->ExpireDate,time());
    }



    $t=time();
    $html[]="<table id=\"table-$t\" class=\"footable table table-stripped\" style='margin-top:20px'>";
    $html[]="<thead>";
    $html[]="<tr>";
    $html[]="<th></th>";
    $html[]="<th>{domains}</th>";
    $html[]="<th>{certificate}</th>";
    $html[]="<th>{RESOLVED}</th>";
    $html[]="<th>{available}</th>";
    $html[]="<th></th>";
    $html[]="</tr>";
    $html[]="</thead>";
    $html[]="<tbody>";
    $TRCLASS=null;
    $trc="style='width:1%;text-align:center' nowrap";
    $ForwardServersDynamics =   intval($socksngix->GET_INFO("ForwardServersDynamics"));
    if($ForwardServersDynamics==1) {
        $arrow="&nbsp;&nbsp;<i class='fa-solid fa-arrow-right-to-line'></i>&nbsp;&nbsp;";
        $FSDynamicsExt = intval($socksngix->GET_INFO("FSDynamicsExt"));
        $FSDynamicsSrc = trim($socksngix->GET_INFO("FSDynamicsSrc"));
        $FSDynamicsDst = trim($socksngix->GET_INFO("FSDynamicsDst"));
        if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}

        if($FSDynamicsExt==1){
            if(preg_match("#\.(.*?)$#",$FSDynamicsSrc,$re)){
                $FSDynamicsSrc=str_replace(".".$re[1],".*",$FSDynamicsSrc);
            }else{
                $FSDynamicsSrc=$FSDynamicsSrc.".*";
            }
            if(preg_match("#\.(.*?)$#",$FSDynamicsDst,$re)){
                $FSDynamicsDst=str_replace(".".$re[1],".*",$FSDynamicsDst);
            }else{
                $FSDynamicsDst=$FSDynamicsDst.".*";
            }
        }

        $md=md5($FSDynamicsSrc.$FSDynamicsDst);
        $FSDynamicsSrc=$tpl->td_href($FSDynamicsSrc,null,"Loadjs('fw.nginx.sites.dynamics.php?service-id=$ID&md=$md&function=$function');");
        $html[]="<tr class='$TRCLASS' id='a00'>";
        $html[]="<td $trc><i class='".ico_routes." fa-2x'></i></td>";
        $html[]="<td style='width:100%;'><strong style='$StyleRow'>*.$FSDynamicsSrc$arrow*.$FSDynamicsDst</strong></td>";
        $html[]="<td $trc>&nbsp;</td>";
        $html[]="</tr>";
    }
    $icon_certif=ico_certificate;
    $c=0;
    $Zhosts=HostsToArray($ID);
    foreach ($Zhosts as $domains=>$redirect){
        if (trim($domains)==null){continue;}
        $c++;
        if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
        $md=md5($domains.$c.$redirect);
        $arrow=null;$redir=null;
        $domainsenc=urlencode($domains);
        $ResolvErr="<span class='text-danger'><i class='text-danger fa-2x fas fa-times'></i></span>";
        $SSLText="";
        $resolvedIp="";
        $LetsEncrypt=false;
        $infs="";
        $Subtext=array();
        if(isset($ResolvErrDetail[$domains])){
            VERBOSE("ResolvErrDetail:$ResolvErrDetail[$domains]",__LINE__);
            if(preg_match("#^ERROR:(.+)#",$ResolvErrDetail[$domains],$re)){
                $ResolvErr="<span class='text-danger'><i class='text-danger fa-2x fas fa-times'></i></span>";
                $Subtext[]="<small class='text-danger'>$re[1]</small>";
            }else{

                $ResolvedInfo=$ResolvErrDetail[$domains];
                VERBOSE("ResolvedInfo:$ResolvedInfo",__LINE__);
                if(strpos($ResolvedInfo,"||")){
                    $ResolvedInfoSplitted=explode("||",$ResolvedInfo);
                    if(preg_match("#^RESOLVED:([a-z:0-9\.]+)#",$ResolvedInfoSplitted[0],$re)){
                        $resolvedIp=$re[1];
                        $Subtext[]="<small>$resolvedIp</small>";
                        $ResolvErr="<i style='color:#18a689' class='fa-2x fas fa-check-circle'></i>";
                    }
                    if(preg_match("#TRUE:(.+?):#",$ResolvedInfoSplitted[1],$re)){
                        $LetsEncrypt=true;
                    }
                    if(preg_match("#^ERROR:(.+?):(.+)#",$ResolvedInfoSplitted[1],$re)){
                        $Subtext[]="<small>$resolvedIp</small>";
                        $ResolvErr="<i style='color:#18a689' class='fa-2x fas fa-check-circle'></i>";
                        $Subtext[]="<small class='text-danger'>$re[2]</small>";
                    }
                }



            }
        }




        if(strlen($redirect)>0){
            $arrow="&nbsp;&nbsp;&nbsp;&nbsp;<i class='fa-solid fa-arrow-right-to-line fa-1x'></i>&nbsp;&nbsp;&nbsp;&nbsp;";
            $redir=$redirect;
        }
        if(count($ssl_certificates)>0){
            if(isset($ssl_certificates[$domains])){
                $SSLText="<i class='fa-2x $icon_certif' style='color:#18a689'></i><span style='$StyleRow'>&nbsp;{expire}: $ExpireCert</span>";
            }else{
                $SSLText="<i class='fa-2x $icon_certif' style='color:#ed5565'></i><span style='$StyleRow'>&nbsp;{unsigned}</span>";
            }
        }


        $domains=$tpl->td_href($domains,null,"Loadjs('$page?edit-js=$domainsenc&service-id=$ID&md=$md&function=$function');");

        $LetsEncrypt_ico="<i class='text-danger fa-2x fas fa-times'></i>";
        if($LetsEncrypt){
            $LetsEncrypt_ico="<i style='color:#18a689' class='fa-2x fas fa-check-circle'></i>";
        }

        if(count($Subtext)>0){
            $infs=@implode(", ",$Subtext);
        }

        $delete=$tpl->icon_delete("Loadjs('$page?host-delete=$domainsenc&md=$md&service-id=$ID')","AsWebMaster");

        $html[]="<tr class='$TRCLASS' id='$md'>";
        $html[]="<td $trc><i class='".ico_earth." fa-2x'></i></td>";
        $html[]="<td style='width:100%;'><div style='$StyleRow'><strong>$domains$arrow$redir</strong></div>$infs</td>";
        $html[]="<td>$SSLText</td>";
        $html[]="<td $trc>$ResolvErr</td>";
        $html[]="<td $trc>$LetsEncrypt_ico</td>";
        $html[]="<td $trc>$delete</td>";
        $html[]="</tr>";

    }
    $html[]="</tbody>";
    $html[]="<tfoot>";
    $html[]="<tr>";
    $html[]="<td colspan='6'><ul class='pagination pull-right'></ul></td>";
    $html[]="</tr>";
    $html[]="</tfoot>";
    $html[]="</table>";


    $html[]="<script>NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS);
    $html[]="$(document).ready(function() { $('#table-$t').footable( { \"filtering\": { \"enabled\": false";
    $html[]="},\"sorting\": { \"enabled\": true },";
    $html[]="\"paging\": { \"size\": {$GLOBALS["FOOTABLE_PSIZE"]} } }); });";
    $html[]="LoadAjax('servernames-buttons-$ID','$page?top-buttons=$ID&function=$function');";
    $html[]="</script>";
    echo $tpl->_ENGINE_parse_body($html);
    return true;


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
function get_ServiceType($ID):int{
    $ID=intval($ID);
    if($ID==0){return 0;}
    $q                          = new lib_sqlite(NginxGetDB());
    $ligne=$q->mysqli_fetch_array("SELECT type FROM nginx_services WHERE ID=$ID");
    return intval($ligne["type"]);
}