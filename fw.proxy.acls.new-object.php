<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.system.network.inc");
include_once(dirname(__FILE__)."/ressources/class.squid.inc");
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(isset($_GET["popup"])){popup();exit;}

js();


function build_params():string{
    if(!isset($_GET["DnsDist"])){$DnsDist=0;}else{$DnsDist=intval($_GET["DnsDist"]);}
    if(!isset($_GET["ProxyPac"])){$ProxyPac=0;}else{$ProxyPac=$_GET["ProxyPac"];}
    if(!isset($_GET["firewall"])){$firewall=0;}else{$firewall=intval($_GET["firewall"]);}
    if(!isset($_GET["TableLink"])){$TableLink=null;}else{$TableLink=trim($_GET["TableLink"]);}
    if(!isset($_GET["function"])){$function=null;}else{$function=trim($_GET["function"]);}
    $fastacls=intval($_GET["fastacls"]);
    return "&ProxyPac=$ProxyPac&DnsDist=$DnsDist&firewall=$firewall&TableLink=$TableLink&fastacls=$fastacls&function=$function";
}

function js(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $build_params=build_params();
    $tpl->js_dialog4("{new_object}", "$page?popup=yes&id={$_GET["id"]}$build_params");
}


function popup(){
    $dnsfw=false;
    $Smtp=0;
    $SSLERROR=0;
    $fastacls=intval($_GET["fastacls"]);
    if(!isset($_GET["DnsDist"])){$DnsDist=0;}else{$DnsDist=intval($_GET["DnsDist"]);}
    if(!isset($_GET["ProxyPac"])){$ProxyPac=0;}else{$ProxyPac=$_GET["ProxyPac"];}
    if(!isset($_GET["firewall"])){$firewall=0;}else{$firewall=intval($_GET["firewall"]);}
    $EnableKerbAuth = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableKerbAuth"));
    $WindowsActiveDirectoryKerberos=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("WindowsActiveDirectoryKerberos"));
    $LockActiveDirectoryToKerberos=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("LockActiveDirectoryToKerberos"));
    $EnableActiveDirectoryFeature=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableActiveDirectoryFeature"));
    $HaClusterClient= intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HaClusterClient"));
    if($HaClusterClient==1){$LockActiveDirectoryToKerberos=1;}
    if($WindowsActiveDirectoryKerberos==1){$EnableKerbAuth=1;}
    if($LockActiveDirectoryToKerberos==1){$EnableKerbAuth=1;}

    $acl_allowdeny=false;
    if($_GET["TableLink"]=="webfilters_sqacllinks"){$acl_allowdeny=true;}
    if($_GET["TableLink"]=="dnsfw_acls_link"){$dnsfw=true;}
    if($_GET["TableLink"]=="dnsdist_sqacllinks"){$DnsDist=1;}
    if($_GET["TableLink"]=="wpad_sources_link"){$ProxyPac=1;}
    if($_GET["TableLink"]=="postfix_sqacllinks"){$Smtp=1;}
    if($_GET["TableLink"]=="sslproxy_cert_error_sqacllinks"){$SSLERROR=1;}


    $js=array();
    $tpl=new template_admin();
    $qProxy=new mysql_squid_builder(true);
    $proxy_object=$tpl->_ENGINE_parse_body("{proxy_objects}");
    $description=$tpl->_ENGINE_parse_body("{description}");
    $html=array();
    $html[]="<table id='table-wizardnew-objects' class=\"footable table table-stripped\" data-page-size=\"100\" data-paging=\"true\">";
    $html[]="<thead>";
    $html[]="<tr>";
    $html[]="<th data-sortable=true class='text-capitalize center'>&nbsp;</th>";
    $html[]="<th data-sortable=true class='text-capitalize center'>$proxy_object</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>$description</th>";
    $html[]="<th data-sortable=false></th>";
    $html[]="</tr>";
    $html[]="</thead>";
    $html[]="<tbody>";
    $id=$_GET["id"];
    reset($qProxy->acl_GroupType);

    if($SSLERROR==0) {
        unset($qProxy->acl_GroupType["ssl_error"]);
    }

    $TRCLASS=null;
    $method=$tpl->_ENGINE_parse_body("{method}").":";
    $select=$tpl->_ENGINE_parse_body("{select}");

    if($dnsfw){
        foreach ($qProxy->acl_GroupType_DNSFW as $num=>$value){
            $qProxy->acl_GroupType[$num]=$value;
        }
    }

    if($Smtp==1){
        foreach ($qProxy->acl_GroupType_SMTP as $num=>$value){
            $qProxy->acl_GroupType[$num]=$value;
        }

    }




    if($ProxyPac==0){
        unset($qProxy->acl_GroupType["srcproxy"]);
        unset($qProxy->acl_GroupType["rgexsrc"]);
        unset($qProxy->acl_GroupType["rgexdst"]);
    }

    if($acl_allowdeny){
        unset($qProxy->acl_GroupType["rep_mime_type"]);
        unset($qProxy->acl_GroupType["rep_header_filename"]);
        if($EnableKerbAuth==0){
            unset($qProxy->acl_GroupType["proxy_auth_ads"]);
            unset($qProxy->acl_GroupType["proxy_auth_adou"]);
            unset($qProxy->acl_GroupType["proxy_auth_statad"]);
            unset($qProxy->acl_GroupType["proxy_auth_tagad"]);
            unset($qProxy->acl_GroupType["proxy_auth_multiad"]);
            unset($qProxy->acl_GroupType["proxy_auth_authenticated"]);

        }
    }
    $dnsdist_acls_errors=array();
    if($DnsDist==1){
        $qProxy->acl_GroupType["netbiosname"]=$tpl->_ENGINE_parse_body("{acl_netbiosname}");
        $qProxy->acl_GroupType["ptr"]=$tpl->_ENGINE_parse_body("{ptr_dst}");
        $qProxy->acl_GroupType["doh"]=$tpl->_ENGINE_parse_body("{APP_DOH_BACKEND}");
        $qProxy->acl_GroupType["webfilter"]=$tpl->_ENGINE_parse_body("{webfiltering}");
        $qProxy->acl_GroupType["dnsquerytype"]=$tpl->_ENGINE_parse_body("{dnsquerytype}");
        $qProxy->acl_GroupType["the_shields"]=$tpl->_ENGINE_parse_body("{SRN}");
        $qProxy->acl_GroupType["geoipsrc"]=$tpl->_ENGINE_parse_body("{geoipsrc}");
        $qProxy->acl_GroupType["dst"]=$tpl->_ENGINE_parse_body("{dns addresses}");
        $qProxy->acl_GroupType["opendns"]="OpenDNS";
        $qProxy->acl_GroupType["opendnsf"]="OpenDNS Family";
        $qProxy->acl_GroupType["reputation"]=$tpl->_ENGINE_parse_body("{use_reput_service}");
        $dnsdist_acls_errors=$qProxy->dnsdist_acls_errors();
    }

    if($EnableActiveDirectoryFeature==0){
        unset($qProxy->acl_GroupType_SMTP["adfrom"]);
        unset($qProxy->acl_GroupType_SMTP["adto"]);
    }


    foreach ($qProxy->acl_GroupType as $num=>$line){
        $choose=null;
        if($fastacls==1){if(!isset($qProxy->acl_GroupType_fast[$num])){continue;}}
        if($ProxyPac==1){if(!isset($qProxy->acl_GroupType_WPAD[$num])){continue;}}
        if($DnsDist==1){
            if(!isset($qProxy->acl_GroupType_DNSDIST[$num])){
                continue;
            }
        }
        if($Smtp==1){
            if(!isset($qProxy->acl_GroupType_SMTP[$num])){
                continue;
            }
        }

        if($firewall==1){if(!isset($qProxy->acl_GroupType_iptables[$num])){continue;}}
        if($dnsfw){if(!isset($qProxy->acl_GroupType_DNSFW[$num])){continue;}}
        $funct="Select".md5($num);
        $explain=null;

        if(isset($qProxy->acl_GroupType_explain[$num])) {
            $explain = $tpl->javascript_parse_text($qProxy->acl_GroupType_explain[$num]);
            $explain = str_replace("\\n", " ", $explain);
            if (strlen($explain) > 83) {
                $explain = substr($explain, 0, 80) . "...";
            }
        }

        if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
        $choose="<button class='btn btn-primary btn-xs' type='button' OnClick=\"$funct();\">$select</button>";

        $js[]="
		function $funct(){
			document.getElementById('$id').value='$num';
			dialogInstance4.close();
			}";

        if(isset($dnsdist_acls_errors[$num])){
            $choose="<button class='btn btn-default btn-xs' type='button' OnClick=\"blur();\">{unavailable}</button>";
            $explain="$explain<br><strong class='text-danger'>$dnsdist_acls_errors[$num]</strong>";
        }

        $html[]="<tr class='$TRCLASS' id='$num'>";
        $html[]="<td class=\"center\"><i class='fa {$qProxy->acl_GroupTypeIcon[$num]}'></i></td>";
        $html[]="<td nowrap>$line</span></td>";
        $html[]="<td><strong>$method&nbsp;$num</strong><div><small>$explain</small></div></td>";
        $html[]="<td class=center>$choose</center></span></td>";
        $html[]="</tr>";


    }


    $html[]="</tbody>";
    $html[]="<tfoot>";

    $html[]="<tr>";
    $html[]="<td colspan='4'>";
    $html[]="<ul class='pagination pull-right'></ul>";
    $html[]="</td>";
    $html[]="</tr>";
    $html[]="</tfoot>";
    $html[]="</table>";

    $html[]="<script>";
    $html[]="NoSpinner();\n".@implode("\n",$js)."";

    $html[]="$(document).ready(function() { $('#table-wizardnew-objects').footable( { \"filtering\": { \"enabled\": true }, \"sorting\": { \"enabled\": true },\"paging\": { \"size\": {$GLOBALS["FOOTABLE_PSIZE"]} } } ); });
</script>";
    echo $tpl->_ENGINE_parse_body(@implode("\n", $html));

}