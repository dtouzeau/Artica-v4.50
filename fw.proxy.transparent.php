<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.computers.inc");

if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}

if(isset($_GET["table"])){table();exit;}
if(isset($_GET["port-js"])){port_js();exit;}
if(isset($_GET["port-popup"])){connected_port_popup();exit;}
if(isset($_POST["SaveConnectedPort"])){post_save();exit;}
if(isset($_GET["port-delete-js"])){port_delete_js();exit;}
if(isset($_POST["port-delete"])){connected_port_delete();exit;}
if(isset($_GET["status"])){status();exit;}
if(isset($_GET["tlimit-js"])){tlimit_js();exit;}
if(isset($_GET["tlimit-popup"])){tlimit_popup();exit;}
table_start();


function table_start(){
    echo "
<table style='width:100%;margin-top:10px'>
<tr>
<td valign='top' width='340px' style='padding:5px'><div id='proxy-transparent-ports-status'></div></td>
<td valign='top' width='80%'><div id='proxy-transparent-ports'></div></td>
</tr>
</table>
<script>LoadAjax('proxy-transparent-ports','fw.proxy.transparent.php?table=yes');</script>
";

}
function port_delete_js(){
    $ID=intval($_GET["port-delete-js"]);
    $page=CurrentPageName();
    $tpl=new template_admin();
    $md=$_GET["md"];
    $q=new lib_sqlite("/home/artica/SQLITE/proxy.db");
    $ligne=$q->mysqli_fetch_array("SELECT * FROM transparent_ports WHERE ID=$ID");
    $title="{listen_port}:  {$ligne["nic"]}:{$ligne["port"]}";
    $jsafter="$('#$md').remove()";
    $tpl->js_confirm_delete($title, "port-delete", $ID,$jsafter);

}
function connected_port_delete(){
    $ID=intval($_POST["port-delete"]);
    $q=new lib_sqlite("/home/artica/SQLITE/proxy.db");
    $q->QUERY_SQL("DELETE FROM transparent_ports WHERE ID=$ID");
    if(!$q->ok){echo $q->mysql_error;}
    $q->QUERY_SQL("DELETE FROM proxy_ports_wbl WHERE portid=$ID");
}

function tlimit_js(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $tpl->js_dialog("{notice}", "$page?tlimit-popup=yes");
}
function tlimit_popup(){
    $page=CurrentPageName();
    $tpl=new template_admin();
   echo  $tpl->div_warning("{transparent_mode_limitations}");
    //
}



function port_js(){
    $ID=intval($_GET["port-js"]);
    $page=CurrentPageName();
    $tpl=new template_admin();
    $q=new lib_sqlite("/home/artica/SQLITE/proxy.db");
    $title=$tpl->javascript_parse_text("{new_port}");

    if($ID>0){
        $ligne=$q->mysqli_fetch_array("SELECT PortName,port FROM transparent_ports WHERE ID=$ID");
        $title="#{$ID} {listen_port}: {$ligne["PortName"]}:{$ligne["port"]}";

    }

    $tpl->js_dialog($title, "$page?port-popup=$ID");

}
function connected_port_popup(){
    $ID=intval($_GET["port-popup"]);
    $page=CurrentPageName();
    $tpl=new template_admin();
    $q=new lib_sqlite("/home/artica/SQLITE/proxy.db");
    $btname="{add}";
    $title=$tpl->javascript_parse_text("{new_port}");
    $jsafter="LoadAjax('proxy-transparent-ports','$page?table=yes');BootstrapDialog1.close();";

    if($ID>0){
        $ligne=$q->mysqli_fetch_array("SELECT * FROM transparent_ports WHERE ID=$ID");
        $title="{$ligne["nic"]}:{$ligne["port"]}";
        if($ligne["nic"]==null){$title="{listen_port}: {$ligne["port"]}";}
        $btname="{apply}";
    }
    $ip=new networking();
    $interfaces=$ip->Local_interfaces();
    unset($interfaces["lo"]);

    $array[null]        = "{all}";
    $array2[null]       = "{all}";
    $CountOfInterfaces  = 0;
    foreach ($interfaces as $eth){
        if(preg_match("#^(gre|dummy)#", $eth)){continue;}
        if($eth=="lo"){continue;}
        $nic=new system_nic($eth);
        if($nic->enabled==0){continue;}
        $array[$eth]="$eth $nic->IPADDR - $nic->NICNAME";
        $array2[$eth]="$eth $nic->IPADDR - $nic->NICNAME";
        $CountOfInterfaces++;
    }



    include_once(dirname(__FILE__)."/ressources/class.squid.reverse.inc");
    $squid_reverse=new squid_reverse();
    $sslcertificates=$squid_reverse->ssl_certificates_list();

    if($ligne["localport"]==0){$ligne["localport"]=rand(1024,63000);}
    if(!is_numeric($ligne["enabled"])){$ligne["enabled"]=1;}
    if(intval($ligne["port"])<10){$ligne["port"]=80;}
    $others_ports=trim($ligne["others_ports"]);

    if($others_ports<>null){
        $ligne["port"]=$ligne["port"].",$others_ports";
    }

    $form[]=$tpl->field_hidden("SaveConnectedPort",$ID);
    $form[]=$tpl->field_checkbox("enabled","{enabled}",$ligne["enabled"],true);
    $form[]=$tpl->field_text("port","{destination_port}",$ligne["port"],true,"{muliple_port_comma_explain}");
    $form[]=$tpl->field_numeric("localport","{proxy_port}",$ligne["localport"]);

    $form[]=$tpl->field_checkbox("dnat", "DNAT",  $ligne["dnat"]);
    $form[]=$tpl->field_checkbox("TProxy", "{use_tproxy_mode}",  $ligne["TProxy"]);
    $form[]=$tpl->field_text("PortName", "{service_name2}",  $ligne["PortName"]);
    $form[]=$tpl->field_checkbox("NoCache","{disable_cache}",$ligne["NoCache"]);
    $form[]=$tpl->field_checkbox("NoFilter","{disable_webfiltering}",$ligne["NoFilter"]);
    if($CountOfInterfaces>1) {
        $form[] = $tpl->field_interfaces("nic", "{listen_interface}", $ligne["nic"]);
        $form[] = $tpl->field_interfaces("outgoing_nic", "{forward_interface}", $ligne["outgoing_nic"]);
    }else{
        $tpl->field_hidden("nic",null);
        $tpl->field_hidden("outgoing_nic",null);
    }
    $form[]=$tpl->field_array_hash($sslcertificates, "sslcertificate", "{use_certificate_from_certificate_center}", $ligne["sslcertificate"]);

    $security="AsSquidAdministrator";
    $html[]=$tpl->form_outside($title, @implode("\n", $form),null,$btname,$jsafter,$security);
    echo $tpl->_ENGINE_parse_body(@implode("\n", $html));

}

function post_save(){
    $q=new lib_sqlite("/home/artica/SQLITE/proxy.db");


    if(!$q->FIELD_EXISTS("transparent_ports","others_ports")){
        $q->QUERY_SQL("ALTER TABLE transparent_ports ADD `others_ports` TEXT");
    }
    $others_ports=null;
    $tpl=new template_admin();
    $SQLAR=$tpl->CLEAN_POST("SaveConnectedPort");
    $ID=$_POST["SaveConnectedPort"];

    $enabled=$_POST["enabled"];
    $portPost=trim($_POST["port"]);
    $portPost=str_replace(" ","",$portPost);
    $portPost=str_replace("-","",$portPost);
    $portPost=str_replace("*","",$portPost);
    $portPost=str_replace(";",",",$portPost);
    $port=0;
    if(strpos($portPost,",")>0){
        $tbr=explode(",",$portPost);
        foreach ($tbr as $index=>$lport){
            if($lport==80){$port=80;unset($tbr[$index]);}
            if($lport==443){$port=443;unset($tbr[$index]);}
        }
        if($port==0){$port=$tbr[0];unset($tbr[0]);}

        $others_ports=@implode(",",$tbr);
    }else{
        $port=$portPost;
    }


    $localport=intval($_POST["localport"]);

    if($localport==0){echo $tpl->_ENGINE_parse_body("{proxy_port} $localport false value!");return;}

    $PortName=$q->sqlite_escape_string2($_POST["PortName"]);
    $NoCache=intval($_POST["NoCache"]);
    $NoFilter=intval($_POST["NoFilter"]);
    $dnat=intval($_POST["dnat"]);
    $nic=$_POST["nic"];
    $outgoing_nic=$_POST["outgoing_nic"];
    $sslcertificate=$_POST["sslcertificate"];
    $TProxy=intval($_POST["TProxy"]);

    if($port==443){
        if($sslcertificate==null){
            echo "jserror:".$tpl->_ENGINE_parse_body("{error_443_without_certificate}");
            return;
        }
    }


    if($ID==0) {
        $Text="Create a new proxy transparent port $PortName";
        $sql = "INSERT INTO transparent_ports (PortName,nic,outgoing_nic,sslcertificate,enabled,NoCache,NoFilter,TProxy,localport,port,others_ports,dnat) VALUES ('$PortName','$nic','$outgoing_nic','$sslcertificate','$enabled',$NoCache,$NoFilter,$TProxy,$localport,$port,'$others_ports','$dnat')";
    }else{
        $text="Update the transparent port $PortName";
        $sql="UPDATE transparent_ports SET 
        PortName='$PortName',
        nic='$nic',
        outgoing_nic='$outgoing_nic',
        sslcertificate='$sslcertificate',
        enabled='$enabled',
        NoCache='$NoCache',
        NoFilter='$NoFilter',
        TProxy='$TProxy',
        localport='$localport',
        port='$port',
        others_ports='$others_ports',
        dnat='$dnat'
        WHERE ID=$ID";


    }

    $q->QUERY_SQL($sql);
    if(!$q->ok){echo $q->mysql_error."\n";}
    return admin_tracks_post($text);
}


function table(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $q=new lib_sqlite("/home/artica/SQLITE/proxy.db");

    $TRCLASS=null;
    $TTRANSPARENTS=array();$TTPROXY=array();
    $t=time();
    $SquidSSLUrgency=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidSSLUrgency"));
    $data=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/firewall/iptablesave"));
    $lines=explode("\n",$data->Data);

    foreach ($lines as $line){
        if(strpos("   $line","-A ")==0){continue;}
        VERBOSE($line,__LINE__);
        if(preg_match("#REDIRECT.*?--to-port.*?([0-9]+)#",$line,$re)){
            $TTRANSPARENTS[$re[1]]=true;
            continue;
        }

        if(preg_match("#DNAT\s+--to-destination.*?:([0-9]+)#" ,$line,$re)){
            $TTRANSPARENTS[$re[1]]=true;
            continue;
        }

        if(preg_match("#TPROXY.*?--on-port.*?([0-9]+)#",$line,$re)){
            $TTPROXY[intval($re[1])]=true;
            VERBOSE("{$re[1]} OK",__LINE__);
            continue;
        }

    }

    $jsrestart="Loadjs('fw.proxy.apply.ports.php');";
    $jsinfo="Loadjs('$page?tlimit-js=yes')";

    $HaClusterClient = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HaClusterClient"));

    if($HaClusterClient==0) {
        $topbuttons[] = array("Loadjs('$page?port-js=0')", ico_plus, "{new_port}");
    }
    $topbuttons[] = array("LoadAjax('proxy-transparent-ports','fw.proxy.transparent.php?table=yes');", ico_refresh, "{refresh}");
    $topbuttons[] = array($jsrestart, ico_save, "{reconfigure_proxy_ports_restart}");
    $topbuttons[] = array($jsinfo, "fa fa-question-circle", "{notice}: {limits}");



    $html[]="<table id='table-$t' class=\"footable table table-stripped\" data-page-size=\"100\" data-paging=\"true\">";
    $html[]="<thead>";
    $html[]="<tr>";
    $html[]=$tpl->_ENGINE_parse_body("<th data-sortable=true class='text-capitalize' data-type='text' nowrap>{status}</th>");
    $html[]=$tpl->_ENGINE_parse_body("<th data-sortable=true class='text-capitalize' data-type='text' nowrap>{tcp_address}</th>");
    $html[]=$tpl->_ENGINE_parse_body("<th data-sortable=true class='text-capitalize' data-type='text' nowrap>{remote_port}</th>");
    $html[]=$tpl->_ENGINE_parse_body("<th data-sortable=true class='text-capitalize' data-type='text' nowrap>{proxy_port}</th>");
    $html[]=$tpl->_ENGINE_parse_body("<th data-sortable=true class='text-capitalize' data-type='text' nowrap>{outgoing_address}</th>");
    $html[]=$tpl->_ENGINE_parse_body("<th data-sortable=true class='text-capitalize' data-type='text' nowrap>{PortName}</th>");
    $html[]=$tpl->_ENGINE_parse_body("<th data-sortable=true class='text-capitalize' data-type='text' class='center' nowrap>WL SRC</th>");
    $html[]=$tpl->_ENGINE_parse_body("<th data-sortable=true class='text-capitalize' data-type='text' class='center' nowrap>WL DST</th>");

    $html[]=$tpl->_ENGINE_parse_body("<th data-sortable=true class='text-capitalize' data-type='text' class='center'>HTTPS</th>");
    $html[]=$tpl->_ENGINE_parse_body("<th data-sortable=true class='text-capitalize' data-type='text' class='center'>{cache}</th>");
    $html[]=$tpl->_ENGINE_parse_body("<th data-sortable=true class='text-capitalize' data-type='text' class='center'>{filter}</th>");
    $html[]=$tpl->_ENGINE_parse_body("<th data-sortable=true class='text-capitalize' data-type='text' class='center'>{enabled}</th>");
    $html[]=$tpl->_ENGINE_parse_body("<th data-sortable=true class='text-capitalize' data-type='text' class='center'>{delete}</th>");
    $html[]="</tr>";
    $html[]="</thead>";
    $html[]="<tbody>";


    if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
    $text_class=null;

    $sql="SELECT * FROM transparent_ports";
    $results=$q->QUERY_SQL($sql);
    $ALL_TEXT=$tpl->_ENGINE_parse_body("{all_interfaces}");

    foreach ($results as $index=>$ligne){
        if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
        $PortName=$tpl->javascript_parse_text($ligne["PortName"]);
        $hiddenID=intval($ligne["hiddenID"]);
        $nic=$ligne["nic"];
        $localport=$ligne["localport"];
        $TProxy=intval($ligne["TProxy"]);
        $tooltip_error=null;
        $dnat=intval($ligne["dnat"]);
        $intercept_label="label-primary";
        $status="<span class='label label-primary'>OK</span>";
        if($ligne["enabled"]==0){$status="<span class='label label'>{disabled}</span>";}

        if($nic==null){$addr="127.0.0.1";}
        if($nic<>null){
            $net=new system_nic($nic);
            $addr=$net->IPADDR;
        }
        if($ligne["enabled"]==1) {
            $fp = @fsockopen($addr, $localport, $errno, $errstr, 1);
            if (!$fp) {
                $status = "<span class='label label-danger'>{failed}</span>";
                $tooltip_error = "<div style='margin-top:5px'><span class='label label-danger'>$errstr</span></div>";
            }else {
                fclose($fp);
            }
        }


        $outgoing_nic=$ligne["outgoing_nic"];

        $others_ports=trim($ligne["others_ports"]);
        if($others_ports<>null){$ligne["port"]=$ligne["port"].",$others_ports";}
        $port=$ligne["port"];
        $ligne["UseSSL"]=0;
        $sslcertificate=$ligne["sslcertificate"];
        if($sslcertificate<>null){
            $ligne["UseSSL"]=1;
        }
        $dantico="";
        if($dnat==1){
            $dantico="&nbsp;<span class='label label-warning'>DNAT</span>";
        }

        $md=md5(serialize($ligne));

        if($nic==null){$ipaddr=null;}else{
            $znic=new system_nic($nic);
            $ipaddr=$znic->IPADDR;
        }
        if($outgoing_nic==null){
            $outgoing_ipaddr=null;
        }else{
            $znic=new system_nic($outgoing_nic);
            $outgoing_ipaddr=$znic->IPADDR;
        }


        $ipaddr=str_replace("0.0.0.0", $ALL_TEXT, $ipaddr);

        $UseSSL="&nbsp;";
        $xnote="&nbsp;";

        $NoCache="<i class='fas fa-check'></i>";
        $NoFilter="<i class='fas fa-check'></i>";
        $enabled="<i class='fas fa-check'></i>";
        if($ligne["UseSSL"]==1){
            if($SquidSSLUrgency==1){$text_class="text-danger";$tooltip_error="$tooltip_error<div style='margin-top:5px'><span class='label label-danger'>{proxy_in_ssl_emergency_mode}</span></div>";}
            $UseSSL="<i class='fas fa-check'></i>";
        }


        if($ligne["NoCache"]==1){$NoCache="&nbsp;";}
        if($ligne["NoFilter"]==1){$NoFilter="&nbsp;";}
        if($ligne["enabled"]==0){$enabled="&nbsp;";$intercept_label=null;}

        if($outgoing_ipaddr==null){$outgoing_ipaddr=$ALL_TEXT;}
        if($ipaddr==null){$ipaddr=$ALL_TEXT;}
        $ID=$ligne["ID"];

        $js="Loadjs('$page?port-js=$ID');";
        $ipaddr_lnk=$tpl->td_href($ipaddr,null,$js);
        $PortName_lnk=$tpl->td_href("$PortName",null,$js);
        $delete_lnk=$tpl->icon_delete("Loadjs('$page?port-delete-js=$ID&md=$md')","AsSquidAdministrator");


        $sql="SELECT count(pattern) as tcount FROM `proxy_ports_wbl` WHERE include=0 AND portid=$ID";
        $ligne2=$q->mysqli_fetch_array($sql);
        $srcount=intval($ligne2["tcount"]);

        $sql="SELECT count(pattern) as tcount  FROM `proxy_ports_wbl` WHERE include=1 AND portid=$ID";
        $ligne2=$q->mysqli_fetch_array($sql);
        $dscount=intval($ligne2["tcount"]);
        if($dscount==0){$dscount=3;}


        $type="&nbsp;&nbsp;<span class='label $intercept_label'>intercept</span>";
        if($hiddenID==10){ $type="&nbsp;&nbsp;<span class='label $intercept_label'>intercept WCCP</span>";}
        if($hiddenID==20){ $type="&nbsp;&nbsp;<span class='label $intercept_label'>intercept WCCP</span>";}

        if($TProxy==0) {
            if (!isset($TTRANSPARENTS[$localport])) {
                $status = "<span class='label label-danger'>{firewall}!</span>";
            }
        }

        if($TProxy==1){
            $type="&nbsp;<span class='label label-warning'>Tproxy</span>";
            if(!isset($TTPROXY[$localport])){
                VERBOSE("TTPROXY[{$localport}] FAILED",__LINE__);
                $status = "<span class='label label-danger'>{firewall}!</span>";
            }
        }

        if($ligne["enabled"]==0){
            $status = "<span class='label'>{disabled}</span>";
        }

        $witelist_src=$tpl->td_href("$srcount {items}",null,"Loadjs('fw.proxy.ports.php?transparent-wbl-js=yes&include=0&portid=$ID')");
        $witelist_dst=$tpl->td_href("$dscount {items}",null,"Loadjs('fw.proxy.ports.php?transparent-wbl-js=yes&include=1&portid=$ID')");


        $html[]="<tr class='$TRCLASS' id='$md'>";

        $td1p=$tpl->table_td1prc();

        $html[]=$tpl->_ENGINE_parse_body("<td $td1p>$status</td>");
        $html[]=$tpl->_ENGINE_parse_body("<td $td1p>$ipaddr_lnk</td>");
        $html[]=$tpl->_ENGINE_parse_body("<td $td1p>$port</td>");
        $html[]=$tpl->_ENGINE_parse_body("<td $td1p>$localport$type$dantico</td>");
        $html[]=$tpl->_ENGINE_parse_body("<td $td1p>$outgoing_ipaddr</td>");
        $html[]=$tpl->_ENGINE_parse_body("<td class=\"left\">$PortName_lnk $xnote$tooltip_error</td>");
        $html[]=$tpl->_ENGINE_parse_body("<td $td1p>$witelist_src</td>");
        $html[]=$tpl->_ENGINE_parse_body("<td $td1p>$witelist_dst</td>");
        $html[]=$tpl->_ENGINE_parse_body("<td $td1p>$UseSSL</td>");
        $html[]=$tpl->_ENGINE_parse_body("<td $td1p>$NoCache</center></td>");
        $html[]=$tpl->_ENGINE_parse_body("<td $td1p>$NoFilter</center></td>");
        $html[]=$tpl->_ENGINE_parse_body("<td $td1p>$enabled</></td>");
        $html[]=$tpl->_ENGINE_parse_body("<td $td1p>$delete_lnk</center></td>");
        $html[]="</tr>";

    }

    $html[]="</tbody>";
    $html[]="<tfoot>";

    $html[]="<tr>";
    $html[]="<td colspan='13'>";
    $html[]="<ul class='pagination pull-right'></ul>";
    $html[]="</td>";
    $html[]="</tr>";
    $html[]="</tfoot>";
    $html[]="</table>";
    $html[]="<p></p>";
    $html[]="<script>
	NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS)."
	$(document).ready(function() { $('#table-$t').footable( { \"filtering\": { \"enabled\": true },\"sorting\": { \"enabled\": true },\"paging\": { \"size\": {$GLOBALS["FOOTABLE_PSIZE"]} } }); });";

    $TINY_ARRAY["TITLE"]="{listen_ports} ({transparent_ports})";
    $TINY_ARRAY["ICO"]="fad fa-plug";
    $TINY_ARRAY["EXPL"]="{transparent_ports_explain_v4}";
    $TINY_ARRAY["BUTTONS"]=$tpl->table_buttons($topbuttons);
    $jstiny="Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";
    $html[]="LoadAjax('proxy-transparent-ports-status','$page?status=yes');";
    $html[]=$jstiny;
    $html[]="</script>";

    echo $tpl->_ENGINE_parse_body($html);

}

function status(){
    $page=CurrentPageName();
    $tpl=new template_admin();

    $q=new lib_sqlite("/home/artica/SQLITE/proxy.db");
    $ligne=$q->mysqli_fetch_array("SELECT COUNT(*) as tcount FROM transparent_ports WHERE enabled=1");
    VERBOSE("tcount={$ligne["tcount"]}",__LINE__);
    $sCount=intval($ligne["tcount"]);
    if(!$q->ok){
        echo   $tpl->widget_jaune("{status}","SQL Error");
        return;
    }


    if($sCount==0){
        echo   $tpl->widget_grey("{status}","{disabled}");
        return;

    }

    $FireHolEnable=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("FireHolEnable"));

    $jsrestart=$tpl->framework_buildjs("/proxy/general/nohup/restart",
        "squid.articarest.nohup","squid.articarest.nohup.log","progress-squid-ports-restart","LoadAjax('proxy-transparent-ports-status','$page?status=yes');RefreshSecondInterfaceBarrs()");


    $button[1]["name"]="{reconfigure}";
    $button[1]["js"]=$jsrestart;
    $button[1]["icon"]="fa fa-save";

    $sock=new sockets();
    $sock->getFrameWork("firehol.php?transparent-status=yes");
    $Status=intval(@file_get_contents(PROGRESS_DIR."/ArticaSquidTransparent"));

    if($Status==0){
        echo   $tpl->widget_rouge("{status}","{not_configured}",$button);
        return;


    }
    echo   $tpl->widget_vert("{status}","{linked}");

}