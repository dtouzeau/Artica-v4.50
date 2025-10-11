<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}


if(isset($_POST["portid"])){Save();exit;}
if(isset($_GET["exit"])){page_final();exit;}


js();

function js(){
    $tpl=new template_admin();
    $page=CurrentPageName();
    $portid=intval($_GET["portid"]);
    $tpl->js_dialog8("TCP Keepalive Timeout","$page?exit=$portid");


}

function Save(){
    $tpl=new template_admin();
    $ID=intval($_POST["portid"]);
    $q=new lib_sqlite("/home/artica/SQLITE/proxy.db");
    $ligne=$q->mysqli_fetch_array("SELECT Params FROM proxy_ports WHERE ID=$ID");
    $Params=unserialize($ligne["Params"]);
    $Params["tcpkeepalive"]=$_POST;
    $data=$q->sqlite_escape_string2(serialize($Params));
    $q->QUERY_SQL("UPDATE proxy_ports SET `Params`='$data' WHERE ID=$ID");
    if(!$q->ok){
        echo "jserror:".$tpl->javascript_parse_text($q->mysql_error);
        return false;
    }
    $tt=array();
    foreach ($_POST as $num=>$value){
        $tt[]="$num $value";
    }
    admin_tracks("Proxy Port id $ID TCP Keepalive Timeout modified ".@implode(", ",$tt));
    return true;
}

function page_final(){
    $tpl=new template_admin();
    $sock=new sockets();
    $sock->getFrameWork("squid2.php?tcpkeepalive-defaults=yes");
    $Defaults=unserialize(@file_get_contents(PROGRESS_DIR."/kernel_tcp_keepalive"));
    $ID=intval($_GET["exit"]);
    $q=new lib_sqlite("/home/artica/SQLITE/proxy.db");
    $ligne=$q->mysqli_fetch_array("SELECT `Params` FROM proxy_ports WHERE ID=$ID");
    $Params=unserialize($ligne["Params"]);

    if(!isset($Params["tcpkeepalive"]["enabled"])){
        $Params["tcpkeepalive"]["enabled"]=0;
    }

    if(!isset($Params["tcpkeepalive"]["idle"])){
        $Params["tcpkeepalive"]["idle"]=$Defaults["ide"];
    }
    if(!isset($Params["tcpkeepalive"]["interval"])){
        $Params["tcpkeepalive"]["interval"]=$Defaults["interval"];
    }
    if(!isset($Params["tcpkeepalive"]["timeout"])){
        $Params["tcpkeepalive"]["timeout"]=$Defaults["probe"];
    }
    $tpl->field_hidden("portid",$ID);
    $form[]=$tpl->field_checkbox("enabled","{enabled}", $Params["tcpkeepalive"]["enabled"]);
    $form[]=$tpl->field_numeric("idle","{idle} ({seconds})",$Params["tcpkeepalive"]["idle"],"{tcp_keepalive_time}");
    $form[]=$tpl->field_numeric("interval","{interval} ({seconds})",$Params["tcpkeepalive"]["interval"],"{tcp_keepalive_intvl}");
    $form[]=$tpl->field_numeric("timeout","{timeout} ({seconds})",$Params["tcpkeepalive"]["timeout"],"{tcp_keepalive_probes}");

    $security="AsSquidAdministrator";
    $jsafter="LoadAjax('connected-port-popup-$ID','fw.proxy.ports.php?port-popup=$ID');dialogInstance8.close();";

    $html[]=$tpl->form_outside("[$ID]: TCP Keepalive", @implode("\n", $form),null,"{apply}",$jsafter,$security);
    echo $tpl->_ENGINE_parse_body(@implode("\n", $html));



}
