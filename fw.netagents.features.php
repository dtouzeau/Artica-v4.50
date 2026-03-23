<?php
// Display comprehensive network information for a remote agent
// Called from fw.netagents.list.php agent_info_tabs() with ?id={agent_id}
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
include_once(dirname(__FILE__)."/ressources/class.netagent.artica.inc");
if(!isset($GLOBALS["CLASS_SOCKETS"])){
    if(!class_exists("sockets")){
        include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");
    }
    $GLOBALS["CLASS_SOCKETS"]=new sockets();
}
if(isset($_GET["unbound-install"])){unbound_install_js();exit;}
if(isset($_POST["unbound-install"])){unbound_install_confirm();exit;}

if(isset($_GET["unbound-uninstall"])){unbound_uninstall_js();exit;}
if(isset($_POST["unbound-uninstall"])){unbound_uninstall_confirm();exit;}



if(isset($_GET["dhcp-uninstall"])){dhcp_uninstall_js();exit;}
if(isset($_POST["dhcp-uninstall"])){dhcp_uninstall_confirm();exit;}
if(isset($_GET["dhcp-install"])){dhcp_install_js();exit;}
if(isset($_POST["dhcp-install"])){dhcp_install_confirm();exit;}
if(isset($_GET["table"])){table();exit;}
start();

function unbound_install_confirm():bool{
    $id=intval($_POST["unbound-install"]);
    $netagents=new ArticaNetAgents($id);
    $hostname=$netagents->GetAgentHostname();
    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API_POST("/netagents/unbound/install/$id",array()),true);
    if(!$json["success"]){
        echo $json["message"];
        return false;
    }
    $_SESSION["META_INSTALL_UNBOUND_$id"]=true;
    return admin_tracks("Meta: Install the DNS Cache feature on $hostname");
}

function unbound_install_js(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $id=intval($_GET["unbound-install"]);
    $netagents=new ArticaNetAgents($id);
    $hostname=$netagents->GetAgentHostname();
    $jsAfter="setTimeout(function(){ LoadAjaxSilent('feature-$id','$page?table=$id'); }, 3000);";
    return $tpl->js_confirm_execute("$hostname {install_feature} {APP_UNBOUND}","unbound-install",$id,$jsAfter);
}
function unbound_uninstall_confirm():bool{
    $id=intval($_POST["unbound-uninstall"]);
    $netagents=new ArticaNetAgents($id);
    $hostname=$netagents->GetAgentHostname();
    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/netagents/unbound/uninstall/$id",array()),true);

    if(isset($json["Status"])){
        if(!$json["Status"]){
            echo $json["Error"];
        }
    }
    if(isset($json["success"])) {
        if (!$json["success"]) {
            echo $json["message"];
            return false;
        }
    }
    $_SESSION["META_UNINSTALL_UNBOUND_$id"]=true;
    return admin_tracks("Meta: uninstall the DNS Cache feature on $hostname");
}
function dhcp_install_js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $id=intval($_GET["dhcp-install"]);
    $netagents=new ArticaNetAgents($id);
    $hostname=$netagents->GetAgentHostname();
    $jsAfter="setTimeout(function(){ LoadAjaxSilent('feature-$id','$page?table=$id'); }, 3000);";
    return $tpl->js_confirm_execute("$hostname {install_feature} {APP_DHCP}","dhcp-install",$id,$jsAfter);
}
function unbound_uninstall_js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $id=intval($_GET["unbound-uninstall"]);
    $netagents=new ArticaNetAgents($id);
    $hostname=$netagents->GetAgentHostname();
    $jsAfter="setTimeout(function(){ LoadAjaxSilent('feature-$id','$page?table=$id'); }, 3000);";
    return $tpl->js_confirm_execute("$hostname {uninstall_feature} {APP_UNBOUND}","unbound-uninstall",$id,$jsAfter);
}
function dhcp_uninstall_js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $id=intval($_GET["dhcp-uninstall"]);
    $netagents=new ArticaNetAgents($id);
    $hostname=$netagents->GetAgentHostname();
    $jsAfter="setTimeout(function(){ LoadAjaxSilent('feature-$id','$page?table=$id'); }, 3000);";
    return $tpl->js_confirm_execute("$hostname {uninstall_feature} {APP_DHCP}","dhcp-uninstall",$id,$jsAfter);
}
function dhcp_uninstall_confirm():bool{
    $id=intval($_POST["dhcp-uninstall"]);
    $netagents=new ArticaNetAgents($id);
    $hostname=$netagents->GetAgentHostname();
    $GLOBALS["CLASS_SOCKETS"]->REST_API_POST("/netagents/dhcp/$id/uninstall",array());
    return admin_tracks("Meta: uninstall the DHCP feature on $hostname");
}
function dhcp_install_confirm():bool{
    $id=intval($_POST["dhcp-install"]);
    $netagents=new ArticaNetAgents($id);
    $hostname=$netagents->GetAgentHostname();
    $GLOBALS["CLASS_SOCKETS"]->REST_API_POST("/netagents/dhcp/$id/install",array());
    return admin_tracks("Meta: install the DHCP feature on $hostname");
}
function start():bool{
    $id=$_GET["id"];
    $page=CurrentPageName();
    echo "<div id='feature-$id'></div>";
    $tpl=new template_admin();
    $js=$tpl->RefreshInterval_js("feature-$id",$page,"table=$id");

    echo "<script>$js;</script>";
    return true;

}
function table():bool{
    $page=CurrentPageName();
    $id=$_GET["table"];
    $netagents=new ArticaNetAgents($id);
    $tpl=new template_admin();
    $status=$netagents->Status();

    $t=time();
    $html[]="<table id='table-$t' class=\"table table-stripped\" data-page-size=\"100\" data-paging=\"true\">";
    $html[]="<thead>";
    $html[]="<tr>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>&nbsp;</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{product}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'></th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{install}</th>";
    $html[]="</tr>";
    $html[]="</thead>";
    $html[]="<tbody>";
    $TRCLASS=null;
    if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
    $html[]=features_dhcpd($status,$TRCLASS);
    if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
    $html[]=features_unbound($status,$TRCLASS);

    $html[]="</tbody>";
    $html[]="<tfoot>";

    $html[]="<tr>";
    $html[]="<td colspan='4'>";
    $html[]="<ul class='pagination pull-right'></ul>";
    $html[]="</td>";
    $html[]="</tr>";
    $html[]="</tfoot>";
    $html[]="</table><script>NoSpinner();\n";
    $html[]=@implode("\n",$tpl->ICON_SCRIPTS);
    $html[]="</script>";
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}

function features_unbound($status,$TRCLASS){
    $package=ico_package;
    $id=$status["agent_id"];
    $debian_version=$status["debian_version"];
    $version="";
    $button_manage="";
    $md = md5("APP_UNBOUND");
    if(!isset($status["unbound"])){
        $status["unbound"]["installed"]=false;
        $status["unbound"]["enabled"]=false;
    }
    if(!$status["unbound"]["installed"]) {
        $install = "Loadjs('fw.netagents.free-install.php?js=$id&product=APP_UNBOUND&debver=$debian_version');";
        $button_install = "<button OnClick=\"$install\" class='btn btn-primary btn-xs' type='button'>{install_upgrade2}</button>";

        $html[] = "<tr class='$TRCLASS' id='$md'>";
        $html[] = "<td style='width: 1%'><i class='$package'></i></td>";
        $html[] = "<td style='width: 99%'><strong>{APP_UNBOUND}$version</strong><br><i><small>{didyouknow_unbound}</small></i></strong></td>";
        $html[] = "<td style='width: 1%'>$button_manage</td>";
        $html[] = "<td style='width: 1%'>$button_install</td>";
        $html[] = "</tr>";
        return @implode("\n",$html);
    }
    $page=currentPageName();

    if(!$status["unbound"]["enabled"]){

        if(isset($_SESSION["META_UNINSTALL_UNBOUND_$id"])){
            unset($_SESSION["META_UNINSTALL_UNBOUND_$id"]);
        }
        $install = "Loadjs('$page?unbound-install=$id');";
        $button_install = "<button OnClick=\"$install\" class='btn btn-warning btn-xs' type='button'>{install_feature}</button>";
        if(isset($_SESSION["META_INSTALL_UNBOUND_$id"])){
            $button_install="<i class='".ico_refresh_animate."'></i>";
        }
        $html[] = "<tr class='$TRCLASS' id='$md'>";
        $html[] = "<td style='width: 1%'><i class='$package'></i></td>";
        $html[] = "<td style='width: 99%'><strong>{APP_UNBOUND}$version</strong><br><i><small>{didyouknow_unbound}</small></i></strong>$errtext</td>";
        $html[] = "<td style='width: 1%'>$button_manage</td>";
        $html[] = "<td style='width: 1%'>$button_install</td>";
        $html[] = "</tr>";
        return @implode("\n",$html);
    }

    $install = "Loadjs('$page?unbound-uninstall=$id');";
    $manage = "Loadjs('fw.netagents.unbound.php?unbound-js=$id');";
    $button_install = "<button OnClick=\"$install\" class='btn btn-danger btn-xs' type='button'>{uninstall_feature}</button>";
    $button_manage = "<button OnClick=\"$manage\" class='btn btn-primary btn-xs' type='button'>{manage}</button>";

    if(isset($_SESSION["META_INSTALL_UNBOUND_$id"])){
        unset($_SESSION["META_INSTALL_UNBOUND_$id"]);
    }

    if(isset($_SESSION["META_UNINSTALL_UNBOUND_$id"])){
            $button_install="<i class='".ico_refresh_animate."'></i>";
            $button_manage="";
        }


    $html[] = "<tr class='$TRCLASS' id='$md'>";
    $html[] = "<td style='width: 1%'><i class='$package'></i></td>";
    $html[] = "<td style='width: 99%'><strong>{APP_UNBOUND}$version</strong><br><i><small>{didyouknow_unbound}</small></i></strong></td>";
    $html[] = "<td style='width: 1%'>$button_manage</td>";
    $html[] = "<td style='width: 1%'>$button_install</td>";
    $html[] = "</tr>";
    return @implode("\n",$html);
}
function features_dhcpd($status,$TRCLASS):string{
    $page=currentPageName();
    $package=ico_package;

    $id=$status["agent_id"];
    $version="";
    $button_manage="";

    $debian_version=$status["debian_version"];
    if(!isset($status["dhcp3"])){
        $status["dhcp3"]["exists"]=false;
        $status["dhcp3"]["installed"]=false;
        $status["dhcp3"]["version"]="";

    }


    if(!$status["dhcp3"]["exists"]) {
        $install = "Loadjs('fw.netagents.free-install.php?js=$id&product=APP_DHCP&debver=$debian_version');";
        $button_install = "<button OnClick=\"$install\" class='btn btn-primary btn-xs' type='button'>{install_upgrade2}</button>";
    }else{
        if(strlen($status["dhcp3"]["version"])>1) {
            $version = "&nbsp;v{$status["dhcp3"]["version"]}";
        }
        if(!$status["dhcp3"]["installed"]){
            $install = "Loadjs('$page?dhcp-install=$id');";
            $button_install = "<button OnClick=\"$install\" class='btn btn-warning btn-xs' type='button'>{install_feature}</button>";
        }else{
            $install = "Loadjs('$page?dhcp-uninstall=$id');";
            $manage = "Loadjs('fw.netagents.dhcp.php?dhcp-js=$id');";
            $button_install = "<button OnClick=\"$install\" class='btn btn-danger btn-xs' type='button'>{uninstall_feature}</button>";
            $button_manage = "<button OnClick=\"$manage\" class='btn btn-primary btn-xs' type='button'>{manage}</button>";
        }
    }

    $md=md5("APP_DHCP");
    $html[]="<tr class='$TRCLASS' id='$md'>";
    $html[]="<td style='width: 1%'><i class='$package'></i></td>";
    $html[]="<td style='width: 99%'><strong>{APP_DHCP}$version</strong><br><i><small>{EnableDHCPServer_text}</small></i></strong></td>";
    $html[]="<td style='width: 1%'>$button_manage</td>";
    $html[]="<td style='width: 1%'>$button_install</td>";
    $html[]="</tr>";
    return @implode("\n",$html);
}