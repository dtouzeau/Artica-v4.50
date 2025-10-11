<?php

include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
include_once(dirname(__FILE__)."/ressources/class.system.network.inc");
include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");
$GLOBALS["CLASS_SOCKETS"]=new sockets();
if(isset($_POST["interface"])){save();exit;}
if(isset($_GET["popup"])){popup();exit;}

js();

function js(){
    $Interface      = $_GET["interface"];
    $tpl            = new template_admin();
    $page           = CurrentPageName();
    $tpl->js_dialog1("{APP_PROXY_ARP}: $Interface","$page?popup=$Interface");

}

function popup(){
    $tpl            = new template_admin();
    $page           = CurrentPageName();
    $Interfacesrc   = $_GET["popup"];
    $nicz           = new system_nic($Interfacesrc);
    $buttonname     = "{apply}";
    $security       = "ASDCHPAdmin";
    $jsreload       = "LoadAjax('netz-interfaces-status','fw.network.interfaces.php?status2=yes');";

    $data=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/system/network/listnics"));
    $zPhysicalsInterfaces=unserialize(base64_decode($data->nics));

    foreach ($zPhysicalsInterfaces as $Interface){$PhysicalsInterfaces[$Interface]=true;}


    $parprouted_cf=$nicz->parprouted_cf;
    $qlite=new lib_sqlite("/home/artica/SQLITE/interfaces.db");
    $sql="SELECT Interface,IPADDR FROM nics WHERE enabled=1";
    $results=$qlite->QUERY_SQL($sql);

    $form[]=$tpl->field_hidden("interface",$Interfacesrc);
    $form[]=$tpl->field_checkbox("parprouted","{enable}",$nicz->parprouted,true,"");

    foreach($results as $index=>$ligne) {
        $Interface  = $ligne["Interface"];

        if (!isset($PhysicalsInterfaces[$Interface])) {
            continue;
        }
        if($Interfacesrc==$Interface){continue;}

        $IPADDR     = $ligne["IPADDR"];
        $form[]     = $tpl->field_checkbox("next_$Interface",
            "{forward_to} $Interface ($IPADDR)", $parprouted_cf["next_$Interface"],
            false, "");

    }
    $html[]=$tpl->form_outside("{APP_PROXY_ARP}: $nicz->NICNAME", @implode("\n", $form),"{parprouted_about}",
        $buttonname,$jsreload,$security);
    echo $tpl->_ENGINE_parse_body($html);
}

function save(){
    $tpl=new template_admin();
    $tpl->CLEAN_POST();
    $nicz           = new system_nic($_POST["interface"]);
    $nicz->parprouted=$_POST["parprouted"];
    $nicz->parprouted_cf=$_POST;
    $nicz->SaveNic();
    $sock=new sockets();
    $sock->REST_API("/parprouted/check");
}