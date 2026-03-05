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

if(isset($_GET["dhcp-uninstall"])){dhcp_uninstall_js();exit;}
if(isset($_POST["dhcp-uninstall"])){dhcp_uninstall_confirm();exit;}
if(isset($_GET["dhcp-install"])){dhcp_install_js();exit;}
if(isset($_POST["dhcp-install"])){dhcp_install_confirm();exit;}
if(isset($_GET["table"])){table();exit;}
start();


function dhcp_install_js(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $id=intval($_GET["dhcp-install"]);
    $netagents=new ArticaNetAgents($id);
    $hostname=$netagents->GetAgentHostname();
    $jsAfter="setTimeout(function(){ LoadAjaxSilent('feature-$id','$page?table=$id'); }, 3000);";
    return $tpl->js_confirm_execute("$hostname {install_feature} {APP_DHCP}","dhcp-install",$id,$jsAfter);
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
function start(){
    $id=$_GET["id"];
    $page=CurrentPageName();
    echo "<div id='feature-$id'></div>";
    echo "<script>LoadAjaxSilent('feature-$id','$page?table=$id');</script>";
}
function table():bool{
    $page=CurrentPageName();
    $id=$_GET["table"];
    $netagents=new ArticaNetAgents($id);
    $tpl=new template_admin();
    $status=$netagents->Status();
    $debian_version=$status["debian_version"];
    if(!isset($status["dhcp3"])){
        $status["dhcp3"]["exists"]=false;
        $status["dhcp3"]["installed"]=false;
        $status["dhcp3"]["version"]="";

    }
    $t=time();
    $html[]="<table id='table-$t' class=\"table table-stripped\" data-page-size=\"100\" data-paging=\"true\">";
    $html[]="<thead>";
    $html[]="<tr>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>&nbsp;</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{product}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{install}</th>";
    $html[]="</tr>";
    $html[]="</thead>";
    $html[]="<tbody>";
    $TRCLASS=null;
    $package=ico_package;

    $version="";


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
            $button_install = "<button OnClick=\"$install\" class='btn btn-danger btn-xs' type='button'>{uninstall_feature}</button>";
        }
    }
    if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
    $md=md5("APP_DHCP");


    $html[]="<tr class='$TRCLASS' id='$md'>";
    $html[]="<td style='width: 1%'><i class='$package'></i></td>";
    $html[]="<td style='width: 99%'><strong>{APP_DHCP}$version</strong><br><i><small>{EnableDHCPServer_text}</small></i></strong></td>";
    $html[]="<td style='width: 1%'>$button_install</td>";
    $html[]="</tr>";


    $html[]="</tbody>";
    $html[]="<tfoot>";

    $html[]="<tr>";
    $html[]="<td colspan='3'>";
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
