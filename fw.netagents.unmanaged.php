<?php
// Network Agents Management Page
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
include_once(dirname(__FILE__)."/ressources/class.netagent.artica.inc");
if(!isset($GLOBALS["CLASS_SOCKETS"])){
    if(!class_exists("sockets")){
        include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");
    }
    $GLOBALS["CLASS_SOCKETS"]=new sockets();
}
if(isset($_GET["popup"])){popup();exit;}
if(isset($_POST["managed"])){save();exit;}
js();
function js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $id=intval($_GET["id"]);
    $AgentObj=new ArticaNetAgents($id);
    $hostname=$AgentObj->GetAgentHostname();
    if(strlen($hostname)<1){$hostname="#$id";}
    return $tpl->js_dialog12("<i class='".ico_admin."'></i> $hostname — {managed}","$page?popup=yes&id=$id",650);
}

function popup(){
    $tpl=new template_admin();
    $id=intval($_GET["id"]);
    $AgentObj=new ArticaNetAgents($id);
    $Crustatus=$AgentObj->Status();

    $unmanaged=$Crustatus["unmanaged"];
    if($unmanaged) {
        $unmanaged_int = 0;
    }else{
        $unmanaged_int=1;
    }

    echo $tpl->BigCircleCheckbox("managed|id:$id","{managed}",
        "{netagents_managed}",$unmanaged_int,"dialogInstance12.close();");


    return true;
}
function save():bool{
    $tpl=new template_admin();
    $users=new usersMenus();
    if(!meta_can_view($users)){
        echo $tpl->post_error("{ERROR_NO_PRIVS2}");
        return false;
    }
    $Managed=intval($_POST["managed"]);
    $id=intval($_POST["id"]);
    if($Managed==1){
        $unmanaged=false;
    }else{
        $unmanaged=true;
    }

    $PP["unmanaged"]=$unmanaged;

    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API_POST_JSON("/netagents/admin/unmanaged/$id",$PP));

    if(isset($json->Status)&&!$json->Status){
        $err=addslashes($json->Error??"{error}");
        echo $tpl->post_error($err);
        return false;
    }
    $AgentObj=new ArticaNetAgents($id);
    $hostname=$AgentObj->GetAgentHostname();
    return admin_tracks("Set Unmanaged to $unmanaged for remote agent $hostname");
}