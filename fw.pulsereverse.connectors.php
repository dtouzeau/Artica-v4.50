<?php
include_once(dirname(__FILE__) . "/ressources/class.template-admin.inc");
include_once(dirname(__FILE__) . "/ressources/class.sockets.inc");
$GLOBALS["CLASS_SOCKETS"] = new sockets();
if (isset($_GET["verbose"])) {
    $GLOBALS["VERBOSE"] = true;
    ini_set('display_errors', 1);
    ini_set('error_reporting', E_ALL);
    ini_set('error_prepend_string', null);
    ini_set('error_append_string', null);
}
if(isset($_GET["table"])){table();exit;}
if(isset($_GET["pulse-js"])){pulse_js();exit;}


page();
function page():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();

    $version=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("HAPROXY_VERSION");
    $html=$tpl->page_header("PulseReverse {connectors}",
        ico_server,
        "{APP_PULSE_REVERSE_CONNECTORS}",
        "$page?table=yes",
        "pulsereverse-connectors",
        "progress-pulsereverse-connectors");

    if(isset($_GET["main-page"])){
        $tpl=new template_admin("PulseReverse v$version",$html);
        echo $tpl->build_firewall();
        return true;
    }

    $tpl=new templates();
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}

function td_status($ligne,$FrontendStatus):string{
    $tpl=new templates();
    $ID=intval($ligne["ID"]);
    if(!isset($FrontendStatus[$ID])){
        return $tpl->_ENGINE_parse_body("<span class='label label-default'>{inactive2}</span>");
    }
    if($FrontendStatus[$ID]["STATUS"]<>"OPEN"){
        return $tpl->_ENGINE_parse_body("<span class='label label-danger'>{stopped}</span>");
    }
    return $tpl->_ENGINE_parse_body("<span class='label label-primary'>{in_production}</span>");
}
function pulse_js():bool{
    $list=explode(",",$_GET["pulse-js"]);
    $q          = new lib_sqlite("/home/artica/SQLITE/PulseReverse.db");
    $GlobalStatus=FrontendStatus();

    foreach ($list as $ID) {
        $ligne=$q->mysqli_fetch_array("SELECT * FROM connectors WHERE ID=$ID");
        $td_backendname=base64_encode(td_backendname($ligne,$GlobalStatus));
        $td_frontendname=base64_encode(td_frontendname($ligne));
        $td_domains=base64_encode(td_domains($ligne));
        $td_status=base64_encode(td_status($ligne,$GlobalStatus));
        $td_in_out=base64_encode(td_in_out($ligne,$GlobalStatus));
        $td_cnx=base64_encode(td_cnx($ligne,$GlobalStatus));
        $f[]="if(document.getElementById('ApulseF-$ID')){";
        $f[]="document.getElementById('ApulseF-$ID').innerHTML=base64_decode('$td_frontendname');";
        $f[]="}";
        $f[]="if(document.getElementById('ApulseB-$ID')){";
        $f[]="document.getElementById('ApulseB-$ID').innerHTML=base64_decode('$td_backendname');";
        $f[]="}";
        $f[]="if(document.getElementById('ApulseD-$ID')){";
        $f[]="document.getElementById('ApulseD-$ID').innerHTML=base64_decode('$td_domains');";
        $f[]="}";
        $f[]="if(document.getElementById('ApulseS-$ID')){";
        $f[]="document.getElementById('ApulseS-$ID').innerHTML=base64_decode('$td_status');";
        $f[]="}";
        $f[]="if(document.getElementById('ApulseI-$ID')){";
        $f[]="document.getElementById('ApulseI-$ID').innerHTML=base64_decode('$td_in_out');";
        $f[]="}";
        $f[]="if(document.getElementById('ApulseC-$ID')){";
        $f[]="document.getElementById('ApulseC-$ID').innerHTML=base64_decode('$td_cnx');";
        $f[]="}";
    }
    header("content-type: application/x-javascript");
    echo implode("\n",$f);
    return true;
}
function table():bool{
    $tpl        = new template_admin();
    $search     = $_GET["search"];
    $q          = new lib_sqlite("/home/artica/SQLITE/PulseReverse.db");
    $LIMIT      = 250;
    $page       = CurrentPageName();
    $function   = $_GET["function"];
    $FrontendStatus=FrontendStatus();

    $sql="SELECT * FROM connectors ORDER BY port ASC LIMIT $LIMIT";

    $results=$q->QUERY_SQL($sql);
    if(!$q->ok){echo $tpl->div_error($q->mysql_error);}
    $t=time();
    $html[]="<table class=\"table table-hover\" id='ReversePulse$t'>
	<thead>
    	<tr>
        	<th nowrap>{status}</th>
        	<th nowrap>{frontends}</th>
        	<th nowrap>IN/OUT</th>
        	<th nowrap>Cnx</th>
        	<th nowrap>{backends}</th>
        	<th nowrap>Del</th>
        </tr>
  	</thead>
	<tbody>
";
    $ids=array();
    $width1="style='width:1%' nowrap";
    $width99="style='width:99%'";

    foreach($results as $index=>$ligne) {
        $ID=$ligne["ID"];
        $md=md5(serialize($ligne));


        $color      = "text-default";
        $td_status=td_status($ligne,$FrontendStatus);
        $td_backendname=td_backendname($ligne,$FrontendStatus);
        $td_frontendname=td_frontendname($ligne);
        $td_in_out=td_in_out($ligne,$FrontendStatus);
        $td_cnx=td_cnx($ligne,$FrontendStatus);
        $del=$tpl->icon_delete("Loadjs('$page?backend-delete=$ID&md=$md')","AsSquidAdministrator");

        $ids[]=$ID;
        $html[]="<tr id='$md'>
				<td $width1 class='$color'><span id='ApulseS-$ID'>$td_status</span></td>
				<td $width99 class='$color'><span id='ApulseF-$ID'>$td_frontendname</span></td>
				<td $width1 class='$color'><span id='ApulseI-$ID'>$td_in_out</span></td>
				<td $width1 class='$color'><span id='ApulseC-$ID'>$td_cnx</span></td>
				<td $width1 class='$color'><span id='ApulseB-$ID'>$td_backendname</span></td>
				<td $width1 class='$color'>$del</td>
				</tr>";

    }

    $topbuttons[]=array("Loadjs('$page?backend-js=0&function=$function')", ico_plus,"{new_backend}");

    $TINY_ARRAY["TITLE"]="PulseReverse: {connectors}";
    $TINY_ARRAY["ICO"]=ico_server;
    $TINY_ARRAY["EXPL"]="{APP_PULSE_REVERSE_CONNECTORS}";
    $TINY_ARRAY["BUTTONS"]=$tpl->table_buttons($topbuttons);
    $headsjs="Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";
    $idss=implode(",",$ids);
    $pinger=$tpl->RefreshInterval_Loadjs("ReversePulse$t",$page,"pulse-js=$idss&function=$function");

    $html[]="</tbody></table>";
    $html[]="<script>";
    $html[]="$headsjs";
    $html[]=$pinger;
    $html[]=@implode("\n",$tpl->ICON_SCRIPTS);
    $html[]="</script>";
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}
function td_frontendname($ligne):string {
    $tpl=new template_admin();
    $page=CurrentPageName();
    $ID=intval($ligne["ID"]);

    $servicename=$ligne["servicename"];
    $iface=$ligne["iface"];
    $port=$ligne["port"];
    if($iface==""){
        $iface="0.0.0.0";
    }
    $ico="fas fa-code-branch";
    $servicename=$tpl->td_href($servicename,"","Loadjs('$page?frontend-js=$ID')");
    $text="<i class=\"$ico\"></i><strong>$servicename</strong> ($iface:$port)";
    return $tpl->_ENGINE_parse_body($text);

}
function td_domains($ligne):string {
    $tpl=new template_admin();
    return "";
}

function td_backendname($ligne,$GlobalStatus):string {
    $ConnectorID=intval($ligne["ID"]);
    $tpl=new template_admin();
    $sql="SELECT backends.ID,backends.backendname,backends.enabled FROM connectors_backends,backends
                 WHERE backendid=backends.ID
                 AND connectors_backends.connectorid=$ConnectorID";

    $q          = new lib_sqlite("/home/artica/SQLITE/PulseReverse.db");
    $results=$q->QUERY_SQL($sql);
    if(count($results)==0){
        return "";
    }
    $ico=ico_server;
    foreach($results as $index=>$ligne) {
        $backendname=$ligne["backendname"];
        $enabled=$ligne["enabled"];
        if($enabled==0){
            $html[]="<div style='margin-top: 5px;color:#C5C5C5'><span class='label label-default'>{disabled}</i>&nbsp;<i class='$ico'></i>&nbsp;<strong>$backendname</strong></div>";
            continue;
        }
        $label=td_backend_status($ligne,$GlobalStatus);
        $html[]="<div style='margin-top: 5px'>$label&nbsp;<i class='$ico'></i>&nbsp;<strong>$backendname</strong></div>";

    }

    return $tpl->_ENGINE_parse_body(implode("",$html));
}
function td_backend_status($ligne,$GlobalStatus):string{
    $tpl=new template_admin();
    $ID=$ligne["ID"];
    if(!isset($GlobalStatus["BACKENDS"][$ID])){
        return $tpl->_ENGINE_parse_body("<span class='label label-default'>{inactive2}</span>");
    }
    if(!isset($GlobalStatus["BACKENDS"][$ID]["status"])){
        return $tpl->_ENGINE_parse_body("<span class='label label-default'>{inactive2}</span>");
    }
    $Status=$GlobalStatus["BACKENDS"][$ID]["status"];
    switch ($Status) {
        case "UP":
            return $tpl->_ENGINE_parse_body("<span class='label label-primary'>{in_production}</span>");
        case "DOWN":
            return $tpl->_ENGINE_parse_body("<span class='label label-danger'>{stopped}</span>");

        case "MAINT":
            return $tpl->_ENGINE_parse_body("<span class='label label-warning'>{maintenance}</span>");

        default:
            return $tpl->_ENGINE_parse_body("<span class='label label-default'>$Status</span>");
    }

}
function FrontendStatus():array{

    $data=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/pulsereverse/info"));
    if(!$data->Status){
        return array();
    }
    if(!property_exists($data,"Info")){
        return array();
    }
    $Main=$data->Info;
    if(property_exists($Main,"Backends")){
    foreach ($Main->Backends as $backend=>$class) {
        if(!preg_match("#Backend([0-9]+)#",$backend,$m)){
            continue;
        }
        $ID=$m[1];
        $srv_op_state=$class->srv_op_state;
            switch ($srv_op_state) {
                case 1:
                    $MyStats["BACKENDS"][$ID]["status"]="MAINT";
                    break;
                case 2:
                    $MyStats["BACKENDS"][$ID]["status"]="UP";
                    break;
                case 0:
                    $MyStats["BACKENDS"][$ID]["status"]="DOWN";
                    break;
                case 3:
                    $MyStats["BACKENDS"][$ID]["status"]="MAINT";
                    break;
                default:
                    $MyStats["BACKENDS"][$ID]["status"]="CODE $srv_op_state?";
            }
        }
    }


    if(!property_exists($Main,"AllStats")){
        return array();
    }



        foreach ($Main->AllStats as $index=>$class) {
            $pxname=$class->pxname;
            $bin=$class->bin;
            $bout=$class->bout;
            $stot=$class->stot;
            $status=$class->status;
            if(!preg_match("#frontend-([0-9]+)#",$pxname,$m)){
                continue;

            }
            $ID=$m[1];
            $MyStats[$ID]["IN"]=$bin;
            $MyStats[$ID]["OUT"]=$bout;
            $MyStats[$ID]["CNX"]=$stot;
            $MyStats[$ID]["STATUS"]=$status;
        }


    return $MyStats;
}
function td_in_out($ligne,$GlobalStatus):string{
    $ID=$ligne["ID"];
    if(!isset($GlobalStatus[$ID])){
        return "0&nbsp;/&nbsp;0";
    }
    if(!isset($GlobalStatus[$ID]["IN"])){
        return "0&nbsp;/&nbsp;0";
    }
    $in=FormatBytes($GlobalStatus[$ID]["IN"]/1024);
    $out=FormatBytes($GlobalStatus[$ID]["OUT"]/1024);
    return "$in&nbsp;/&nbsp;$out";

}
function td_cnx($ligne,$GlobalStatus):string{
    $ID=$ligne["ID"];
    if(!isset($GlobalStatus[$ID])){
        return "0";
    }
    if(!isset($GlobalStatus[$ID]["CNX"])){
        return "0";
    }
    $tpl=new template_admin();
    return $tpl->FormatNumber($GlobalStatus[$ID]["CNX"]);
}