<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.os.system.tools.inc");
include_once(dirname(__FILE__)."/ressources/class.tcpip.inc");
include_once(dirname(__FILE__)."/ressources/class.hosts.inc");
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}

// Pentest 2026-11-04 : sdate/container/interval/ipaddr etaient recopies sans encodage dans la page (XSS).
if(isset($_GET["sdate"]) && $_GET["sdate"]!==""){$_GET["sdate"]=intval($_GET["sdate"]);}
if(isset($_GET["container"])){$_GET["container"]=preg_replace('/[^A-Za-z0-9_-]/','',(string)$_GET["container"]);}
if(isset($_GET["interval"])){$_GET["interval"]=preg_replace('/[^A-Za-z0-9 _.-]/','',(string)$_GET["interval"]);}
if(isset($_GET["ipaddr"])){$_GET["ipaddr"]=preg_replace('/[^0-9A-Fa-f.:]/','',(string)$_GET["ipaddr"]);}
if(isset($_GET["tabs"])){tabs();exit;}
if(isset($_GET["alias-proxy"])){alias_proxy();exit;}
if(isset($_POST["proxyalias"])){alias_proxy_save();exit;}
if(isset($_GET["bandwidth"])){bandwidth_start();exit;}
if(isset($_GET["bandwidth-1"])){bandwidth_1();exit;}
if(isset($_GET["bandwidth-2"])){bandwidth_2();exit;}

js();

function js(){
    $uid=$_GET["uid"];
    $uidEnc=urlencode($uid);
    $page=CurrentPageName();
    $tpl=new template_admin();
    $sdate=$_GET["sdate"];
    $ipaddr=urlencode($_GET["ipaddr"]);
    if($sdate==null){$sdate=strtotime(date("Y-m-d 00:00:00"));}
    $curetime=$tpl->time_to_date($sdate);
    // Pentest 2026-11-04 : uid etait recopie sans encodage dans la page (XSS) ; le titre de modale est
    // rendu en HTML apres decodage des entites : on y retire les caracteres de balisage.
    $uidTitle=preg_replace('/[<>&\\\\]/','',(string)$uid);
    $tpl->js_dialog1("$uidTitle: $curetime","$page?tabs=$uidEnc&sdate=$sdate&ipaddr=$ipaddr",990);
}
function tabs(){

    $uid=$_GET["tabs"];
    $uidEnc=urlencode($uid);
    $sdate=$_GET["sdate"];
    $ipaddr=urlencode($_GET["ipaddr"]);
    $page=CurrentPageName();
    $tpl=new template_admin();
    if(isAlias($uid)) {
        $array["{proxyalias}"] = "$page?alias-proxy=$uidEnc&sdate=$sdate&ipaddr=$ipaddr";
    }

    $array["{bandwidth}"] = "$page?bandwidth=$uidEnc&sdate=$sdate&ipaddr=$ipaddr";

    //"INSERT INTO access_users (zdate,userid,size,rqs)
    
    echo $tpl->tabs_default($array);

}

function bandwidth_start(){
    $tpl=new template_admin();
    $uid=$_GET["bandwidth"];
    $uidEnc=urlencode($uid);
    $sdate=$_GET["sdate"];
    $ipaddr=urlencode($_GET["ipaddr"]);
    $page=CurrentPageName();
    $md=md5("$uid$sdate");

    $html[]="<table style='width:100%'>";
    $html[]="<tr>";
    $html[]="<td valign='top'><div id='$md-1'></div></td>";
    $html[]="</tr>";
    $html[]="<tr>";
    $html[]="<td valign='top'><div id='$md-2'></div></td>";
    $html[]="</tr>";
    $html[]="</table>";
    $html[]="<script>";
    $html[]="Loadjs('$page?bandwidth-1=$uidEnc&sdate=$sdate&ipaddr=$ipaddr&container=$md-1')";
    $html[]="Loadjs('$page?bandwidth-2=$uidEnc&sdate=$sdate&ipaddr=$ipaddr&container=$md-2')";
    $html[]="</script>";
    echo $tpl->_ENGINE_parse_body($html);
}

function bandwidth_1(){
    $q=new postgres_sql();
    $tpl=new template_admin();
    $uid=$_GET["bandwidth-1"];
    $ipaddr=urlencode($_GET["ipaddr"]);
    $today=date("Y-m-d 00:00:00",$_GET["sdate"]);
    // Pentest 2026-11-04 : bandwidth-1 (uid) etait concatene dans la requete.
    $results=$q->QUERY_SQL_PARAMS("SELECT size, zdate FROM \"access_users\" WHERE zdate>'$today' AND userid=$1 order by zdate ASC ",array(strval($uid)));

    $daytext=$tpl->time_to_date($_GET["sdate"]);


    while($ligne=@pg_fetch_assoc($results)){
        $size=$ligne["size"];

        $sizeKB=$size/1024;
        $size=round($sizeKB/1024);
        $time=strtotime($ligne["zdate"]);
        if($GLOBALS["VERBOSE"]){echo "{$ligne["zdate"]}: {$ligne["size"]}bytes {$sizeKB}KB $size<br>\n";}
        $xdata[]=date("H:i",$time);
        $ydata[]=$size;
    }


    $title="{downloaded_flow} (MB) $daytext";
    $timetext=$_GET["interval"];
    $highcharts=new highcharts();
    $highcharts->container=$_GET["container"];
    $highcharts->xAxis=$xdata;
    $highcharts->Title=$title;
    $highcharts->TitleFontSize="14px";
    $highcharts->AxisFontsize="12px";
    $highcharts->yAxisTtitle="MB";
    $highcharts->xAxis_labels=true;

    $highcharts->LegendSuffix="MB";
    $highcharts->xAxisTtitle=$timetext;
    $highcharts->datas=array("{size}"=>$ydata);
    echo $highcharts->BuildChart();
}
function bandwidth_2(){
    $q=new postgres_sql();
    $tpl=new template_admin();
    $uid=$_GET["bandwidth-2"];
    $ipaddr=urlencode($_GET["ipaddr"]);
    $today=date("Y-m-d 00:00:00",$_GET["sdate"]);
    // Pentest 2026-11-04 : bandwidth-2 (uid) etait concatene dans la requete.
    $results=$q->QUERY_SQL_PARAMS("SELECT rqs, zdate FROM \"access_users\" WHERE zdate>'$today' AND userid=$1 order by zdate ASC ",array(strval($uid)));

    $daytext=$tpl->time_to_date($_GET["sdate"]);


    while($ligne=@pg_fetch_assoc($results)){
        $rqs=$ligne["rqs"];
        $time=strtotime($ligne["zdate"]);
        if($GLOBALS["VERBOSE"]){echo "{$ligne["zdate"]}: {$ligne["$rqs"]}<br>\n";}
        $xdata[]=date("H:i",$time);
        $ydata[]=$rqs;
    }


    $title="{requests} $daytext";
    $timetext=$_GET["interval"];
    $highcharts=new highcharts();
    $highcharts->container=$_GET["container"];
    $highcharts->xAxis=$xdata;
    $highcharts->Title=$title;
    $highcharts->TitleFontSize="14px";
    $highcharts->AxisFontsize="12px";
    $highcharts->AreaColor="#ed5565";
    $highcharts->yAxisTtitle="{requests}";
    $highcharts->xAxis_labels=true;

    $highcharts->LegendSuffix="{requests}";
    $highcharts->xAxisTtitle=$timetext;
    $highcharts->datas=array("{requests}"=>$ydata);
    echo $highcharts->BuildChart();



}

function isAlias($value){
    $ipClass=new IP();
    $q=new postgres_sql();
    if($ipClass->isValid($value)){return true;}
    if($ipClass->IsvalidMAC($value)){return true;}

    // Pentest 2026-11-04 : l'alias etait concatene dans la requete.
    $res=$q->QUERY_SQL_PARAMS("SELECT * FROM hostsnet WHERE proxyalias=$1",array(strval($value)));
    $ligne=$res?@pg_fetch_assoc($res):array();
    if(!is_array($ligne)){$ligne=array();}
    if($ligne["ipaddr"]<>null){return true;}
    return false;
}

function alias_proxy(){
    $ipClass=new IP();
    $q=new postgres_sql();
    $tpl=new template_admin();
    $key=$_GET["alias-proxy"];
    $FOUND=false;

    if($ipClass->IsvalidMAC($key)){
        $sql="SELECT * FROM hostsnet WHERE mac='$key'";
        $ligne=$q->mysqli_fetch_array($sql);
        $mac=$key;
        $ipaddr=$ligne["ipaddr"];
        $alias=$ligne["proxyalias"];
        $FOUND=true;
    }


    if(!$FOUND) {
        if ($ipClass->isValid($key)) {
            $FOUND = true;
            $sql = "SELECT * FROM hostsnet WHERE ipaddr='$key'";
            $ligne=$q->mysqli_fetch_array($sql);
            $ipaddr = $key;
            $mac = $ligne["mac"];
            $alias = $ligne["proxyalias"];
        }
    }

    if(!$FOUND) {
        // Pentest 2026-11-04 : alias-proxy etait concatene dans la requete.
        $res=$q->QUERY_SQL_PARAMS("SELECT * FROM hostsnet WHERE proxyalias=$1",array(strval($key)));
        $ligne=$res?@pg_fetch_assoc($res):array();
        if(!is_array($ligne)){$ligne=array();}
        $ipaddr=$ligne["ipaddr"];
        $mac = $ligne["mac"];
        $alias=$key;
    }

    if(trim($ipaddr)==null){$ipaddr=$_GET["ipaddr"];}

    $form[]=$tpl->field_ipv4("ipaddr","{ipaddr}",$ipaddr);
    $form[]=$tpl->field_MacAddress("mac","{MAC}",$mac,true);
    $form[]=$tpl->field_text("proxyalias","{proxy_alias}",$alias,true);
    echo $tpl->form_outside(null,$form,"{my_proxy_aliases_text}","{apply}",null,"AsProxyMonitor",false);


}


function alias_proxy_save(){
    $tpl=new template_admin();
    $tpl->CLEAN_POST();


    if($_POST["proxyalias"]==null){
        return;
    }


    $host=new hosts($_POST["mac"]);
    $host->ipaddr=$_POST["ipaddr"];
    $host->proxyalias=$_POST["proxyalias"];
    $host->Save();


    $redis = new Redis();
    try {
        $redis->connect('/var/run/redis/redis.sock');
    } catch (Exception $e) {
        echo "Redis : Fatal connection to Redis server ".$e->getMessage();
        return;
    }
    $redis->set("usrmac:{$_POST["mac"]}",$_POST["proxyalias"]);
    $redis->set("usrmac:{$_POST["ipaddr"]}",$_POST["proxyalias"]);

    $memcached=new lib_memcached();
    $memcached->saveKey($_POST["mac"].":alias", $_POST["proxyalias"]);
    $memcached->saveKey($_POST["ipaddr"].":alias", $_POST["proxyalias"]);
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/hostnets/aliases/rebuild");

}