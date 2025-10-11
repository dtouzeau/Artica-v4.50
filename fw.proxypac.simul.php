<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.system.network.inc");
include_once(dirname(__FILE__)."/ressources/class.squid.acls.groups.inc");
$users=new usersMenus();if(!$users->AsProxyMonitor){exit();}

if(isset($_GET["start"])){start();exit;}
if(isset($_GET["form"])){form();exit;}
if(isset($_GET["step1"])){step1();exit;}
if(isset($_POST["URL"])){save();exit;}
js();


function js(){
    $tpl=new template_admin();
    $page=CurrentPageName();


    $APP_PACTESTER_INSTALLED=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("APP_PACTESTER_INSTALLED"));

    if($APP_PACTESTER_INSTALLED==0){
        $tpl->js_error("{APP_PACTESTER_NOT_INSTALLED}");
        exit;
    }

    $tpl->js_dialog2("{test_your_rules}","$page?start=yes",990);
}

function save(){
    $tpl=new template_admin();
    $tpl->CLEAN_POST();

    foreach ($_POST as $key=>$val){
        $_SESSION["SIMULPACK_{$key}"]=$val;
    }

}

function start(){
    $tpl=new template_admin();
    $page=CurrentPageName();
    $grey=$tpl->widget_grey("{results}","{none}");
    $html[]="";
    $html[]="<table style='width:100%'><tr><td valign='top'><div id='simulate-proxy-results' style='width:335px'>$grey</div></td>
<td style='width: 95%;padding-left: 20px'><H1>{test_your_rules}</H1><div id='simulate-proxy-pac'></div></td></tr></table>";
    $html[]="";
    $html[]="<script>";
    $html[]="LoadAjax('simulate-proxy-pac','$page?form=yes');";
    $html[]="</script>";
    echo $tpl->_ENGINE_parse_body($html);

}

function form(){
    include_once(dirname(__FILE__)."/ressources/class.squid.acls.useragents.inc");
    $tpl=new template_admin();
    $page=CurrentPageName();
    $q=new lib_sqlite("/home/artica/SQLITE/acls.db");
    $useragents=new useragents(false);
    $USERAGENT=$_SERVER["HTTP_USER_AGENT"];
    $regex=$useragents->PatternToRegex($USERAGENT);
    $USERAGENT=$q->sqlite_escape_string2($USERAGENT);
    $regex=$q->sqlite_escape_string2($regex);
    $sql="INSERT OR IGNORE INTO UserAgents(source,regex) VALUES ('$USERAGENT','$regex')";
    $q->QUERY_SQL($sql);

    if(!isset($_SESSION["SIMULPACK_USERAGENT"])){$_SESSION["SIMULPACK_USERAGENT"]=null;}
    if(!isset($_SESSION["SIMULPACK_IPADDR"])){$_SESSION["SIMULPACK_IPADDR"]=$_SERVER["REMOTE_ADDR"];}
    if(!isset($_SESSION["SIMULPACK_URL"])){$_SESSION["SIMULPACK_URL"]="https://www.ibm.com";}

    $proxy_pac_addr=proxy_pac_addr();
    $form[]=$tpl->field_text("URL","{link}",$_SESSION["SIMULPACK_URL"],true);
    $form[]=$tpl->field_ipaddr("IPADDR","{ipaddr}",$_SESSION["SIMULPACK_IPADDR"],true);
    $form[]=$tpl->field_browse_UserAgent("USERAGENT","{http_user_agent}",$_SESSION["SIMULPACK_USERAGENT"]);
    $form[]=$tpl->field_text("PAC_URL","PAC {url}",$proxy_pac_addr,true);


    $html[]=$tpl->form_outside("{identity}",$form,null,"{simulate}","LoadAjax('simulate-proxy-results','$page?step1=yes');",null,true);
    echo $tpl->_ENGINE_parse_body($html);

}

function proxy_pac_addr():string{
    $ProxyPacListenInterface=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ProxyPacListenInterface"));
    $ProxyPacListenPort=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ProxyPacListenPort"));
    if($ProxyPacListenPort==0){$ProxyPacListenPort=80;}
    $EnableNginx=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableNginx"));
    if($EnableNginx==1){
        return "http://127.0.0.1:9505/proxy.pac";
    }
    $nic=new system_nic($ProxyPacListenInterface);
    if($nic->IPADDR==null){$nic->IPADDR="127.0.0.1";}
    return "http://".$nic->IPADDR.":$ProxyPacListenPort/proxy.pac";

}

function step1(){
    $tpl=new template_admin();
    $ch = curl_init();

    $arrayHeaders[]="X-Real-IP: {$_SESSION["SIMULPACK_IPADDR"]}";
    $arrayHeaders[]="Pragma: no-cache,must-revalidate";




    curl_setopt($ch, CURLOPT_FORBID_REUSE, TRUE);
    curl_setopt($ch, CURLOPT_FRESH_CONNECT, TRUE);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $arrayHeaders);
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
    curl_setopt($ch, CURLOPT_URL, $_SESSION["SIMULPACK_PAC_URL"]);
    curl_setopt($ch, CURLOPT_POST, 0);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    if($_SESSION["SIMULPACK_USERAGENT"]<>null) {
        curl_setopt($ch, CURLOPT_USERAGENT, $_SESSION["SIMULPACK_USERAGENT"]);
    }else{
        curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/5.0 (compatible; MSIE 9.0; Win32; Trident/5.0)");
    }


    $data=curl_exec($ch);

    $curl_errno=curl_errno($ch);
    $curl_strerr=curl_strerror($curl_errno);
    $CURLINFO_HTTP_CODE=curl_getinfo($ch,CURLINFO_HTTP_CODE);

    if($curl_errno>0){
        curl_close($ch);
        echo $tpl->widget_rouge($curl_strerr,"{error} $curl_errno {$_SESSION["SIMULPACK_PAC_URL"]}");
        return;

    }

    if($CURLINFO_HTTP_CODE<>200){
        echo $tpl->widget_rouge("{protocol} {error}","{error} $CURLINFO_HTTP_CODE {$_SESSION["SIMULPACK_PAC_URL"]}");
        return;
    }



    curl_close($ch);
    $md5=md5(serialize($_SESSION));
    @file_put_contents("/usr/share/artica-postfix/ressources/logs/web/$md5.pac",$data);

    exec("/usr/bin/pactester -p /usr/share/artica-postfix/ressources/logs/web/$md5.pac -u \"{$_SESSION["SIMULPACK_URL"]}\" -c {$_SESSION["SIMULPACK_IPADDR"]} 2>&1",$results);

    $tb=explode(";",$results[0]);
    $pproxies=array();
    foreach ($tb as $line){

        if(preg_match("#PROXY\s+(.+)#",$line,$re)){
            $pproxies[]=$re[1];
        }
        if(preg_match("#DIRECT#",$line,$re)){
            if(count($pproxies)>0){
                $pproxies[]="{direct_to_internet}";
            }
        }
    }

    if(count($pproxies)>0){
        echo $tpl->widget_vert("{use_proxy}",@implode(" {or} ",$pproxies));
    }else{
        echo $tpl->widget_vert("{success}","{direct_to_internet}");
    }
}