<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");
$GLOBALS["CLASS_SOCKETS"]=new sockets();
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}

if(isset($_GET["table"])){table();exit;}
if(isset($_GET["show-conf"])){show_conf();exit;}
if(isset($_GET["route-dump"])){route_dump();exit;}
if(isset($_GET["route-dump2"])){route_dump2();exit;}
if(isset($_GET["route-dump-eth-js"])){route_dump_eth_js();exit;}
if(isset($_GET["route-dump-eth-popup"])){route_dump_eth_popup();exit;}
if(isset($_GET["route-dump-table-js"])){route_dump_table_js();exit;}
if(isset($_GET["route-dump-table-popup"])){route_dump_table_popup();exit;}
if(isset($_POST["titi0"])){exit;}
if(isset($_GET["explain-this-rule"])){echo EXPLAIN_THIS_RULE($_GET["explain-this-rule"],$_GET["eth"]);exit;}
page();


function route_dump_eth_js():bool{
    $Interface=$_GET["route-dump-eth-js"];
    $page=CurrentPageName();
    $tpl=new template_admin();
    return $tpl->js_dialog5("{routes} >> $Interface", "$page?route-dump-eth-popup=$Interface");
}
function route_dump_table_js():bool{
    $Table=$_GET["route-dump-table-js"];
    $page=CurrentPageName();
    $tpl=new template_admin();
    return $tpl->js_dialog6("{routes} >> $Table", "$page?route-dump-table-popup=$Table");
}

function route_dump_table_popup(){

    $Table=$_GET["route-dump-table-popup"];
    $tpl=new template_admin();

    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/system/network/routes"));
    if (json_last_error()> JSON_ERROR_NONE) {
        echo $tpl->div_error(json_last_error_msg());
        return false;
    }
    if (!$json->Status) {
        echo $tpl->div_error( $json->Error);
        return false;
    }
    if(!property_exists($json,"routes")){
        echo $tpl->div_error( "Undefined property routes" );
        return false;
    }

    if(!property_exists($json->routes->tables,$Table)){
        echo $tpl->div_warning( "Table $Table <strong>{no_data}</strong>" );
        return false;
    }
    $html[]="<table id='table-routing-$Table' class=\"table table-stripped\" data-page-size=\"100\" data-paging=\"true\">";
    $html[]="<thead>";
    $html[]="<tr>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{type}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text' nowrap>{src}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text' nowrap>{dst}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{gateway}</th>";
    $html[]="</tr>";
    $html[]="</thead>";
    $html[]="<tbody>";
    $TRCLASS="";

    foreach ($json->routes->tables->{$Table} as $route){
        if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
        $md=md5(serialize($route));
        $prefsrc="-";
        $gateway="-";
        if(property_exists($route,"prefsrc")){
            $prefsrc=$route->prefsrc;
        }
        if(property_exists($route,"gateway")){
            $gateway=$route->gateway;
        }
        $html[]="<tr class='$TRCLASS' id='$md'>";
        $html[]="<td style='width:1%' nowrap>$route->type</td>";
        $html[]="<td style='width:1%' nowrap>$prefsrc</td>";
        $html[]="<td style='width:1%' nowrap>$route->dst</a></td>";
        $html[]="<td style='width:1%' nowrap><strong>$gateway</strong></td>";
        $html[]="</tr>";

    }
    $html[]="</tbody>";
    $html[]="<tfoot>";
    $html[]="<tr>";
    $html[]="<td colspan='5'>";
    $html[]="<ul class='pagination pull-right'></ul>";
    $html[]="</td>";
    $html[]="</tr>";
    $html[]="</tfoot>";
    $html[]="</table>";
    echo $tpl->_ENGINE_parse_body($html);

    return true;
}

function route_dump_eth_popup(){
    $Interface=$_GET["route-dump-eth-popup"];
    $tpl=new template_admin();

    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/system/network/routes"));
    if (json_last_error()> JSON_ERROR_NONE) {
        echo $tpl->div_error(json_last_error_msg());
        return false;
    }
    if (!$json->Status) {
        echo $tpl->div_error( $json->Error);
        return false;
    }

    if(!property_exists($json,"routes")){
        echo $tpl->div_error( "Undefined property routes" );
        return false;
    }
    if(!property_exists($json->routes->interfaces,$Interface)){
        echo $tpl->div_error( "Undefined property $Interface" );
        return false;
    }
    $page=CurrentPageName();
    $html[]="<table id='table-routing_rules' class=\"table table-stripped\" data-page-size=\"100\" data-paging=\"true\">";
    $html[]="<thead>";
    $html[]="<tr>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{type}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text' nowrap>{src}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text' nowrap>{dst}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{table}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text' nowrap></th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{gateway}</th>";
    $html[]="</tr>";
    $html[]="</thead>";
    $html[]="<tbody>";
    $TRCLASS="";

    if(!property_exists($json->routes->rules_interface,$Interface)){
        if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
        $md=md5(serialize("none"));
        $html[]="<tr class='$TRCLASS' id='$md'>";
        $html[]="<td style='width:1%' nowrap>{rule}</td>";
        $html[]="<td style='width:1%' nowrap>-</td>";
        $html[]="<td style='width:1%' nowrap>-</a></td>";
        $html[]="<td>-</td>";
        $html[]="<td style='width:1%' nowrap></td>";
        $html[]="<td style='width:1%' nowrap>-</td>";
        $html[]="</tr>";
    }
    $icofl=ico_arrow_right;
    if(property_exists($json->routes->rules_interface,$Interface)){
        foreach ($json->routes->rules_interface->{$Interface} as $rule){
            if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
            $md=md5(serialize($rule));
            $table="";
            $icotable="";
            $src="$rule->src/$rule->srclen";
            if(property_exists($rule,"iif")){
                $src="$src&nbsp;<i class='$icofl'></i>&nbsp;$rule->iif";
            }

            if(strlen($rule->table)>1){
                $table=$tpl->td_href($rule->table,"","Loadjs('$page?route-dump-table-js=$rule->table')");
                $icotable="<i class='$icofl'></i>";
            }


            $html[]="<tr class='$TRCLASS' id='$md'>";
            $html[]="<td style='width:1%' nowrap>{rule}</td>";
            $html[]="<td style='width:1%' nowrap>$src</td>";
            $html[]="<td style='width:1%' nowrap>-</a></td>";
            $html[]="<td>$icotable&nbsp;<strong>$table</strong></td>";
            $html[]="<td style='width:1%' nowrap></td>";
            $html[]="<td style='width:1%' nowrap>-</td>";
            $html[]="</tr>";
        }
    }

    foreach ($json->routes->interfaces->{$Interface} as $route){
        if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
        $md=md5(serialize($route));
        $prefsrc="-";
        $gateway="-";
        $icog="";
        $icotable="";
        $table="";
        if(property_exists($route,"prefsrc")){
            $prefsrc=$route->prefsrc;
        }
        if(property_exists($route,"gateway")){
            $icog="<i class='$icofl'></i>";
            $gateway=$route->gateway;
        }
        if(strlen($rule->table)>1){
            $table=$tpl->td_href($rule->table,"","Loadjs('$page?route-dump-table-js=$rule->table')");
            $icotable="<i class='$icofl'></i>";
        }
        $html[]="<tr class='$TRCLASS' id='$md'>";
        $html[]="<td style='width:1%' nowrap>$route->type</td>";
        $html[]="<td style='width:1%' nowrap>$prefsrc</td>";
        $html[]="<td style='width:1%' nowrap>$route->dst</a></td>";
        $html[]="<td>$icotable&nbsp;<strong>$table</strong></td>";
        $html[]="<td style='width:1%' nowrap>$icog</td>";
        $html[]="<td style='width:1%' nowrap><strong>$gateway</strong></td>";
        $html[]="</tr>";
    }
    $html[]="</tbody>";
    $html[]="<tfoot>";
    $html[]="<tr>";
    $html[]="<td colspan='7'>";
    $html[]="<ul class='pagination pull-right'></ul>";
    $html[]="</td>";
    $html[]="</tr>";
    $html[]="</tfoot>";
    $html[]="</table>";
    echo $tpl->_ENGINE_parse_body($html);

    return true;
}

function show_conf(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$tpl->js_dialog5("{tables}", "$page?route-dump=yes");	
	
}
function page(){
	$tpl=new template_admin();

   $html=$tpl->page_header("{routing_rules}","fa fa-road",
        "{routing_rules_explain}</strong>
                <br>{routing_rules_explain2}",
    "fw.network.routing.php?table=yes","routing","progress-iproute-restart",false,"table-loader-iprule");



	if(isset($_GET["main-page"])){
        $tpl=new template_admin("{routing_rules}",$html);
        echo $tpl->build_firewall();
        return;
	}

	echo $tpl->_ENGINE_parse_body($html);

}

function table(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$q=new lib_sqlite("/home/artica/SQLITE/interfaces.db");
	$nic=$tpl->_ENGINE_parse_body("{nic}");
	$table=$tpl->_ENGINE_parse_body("{routing_table}");
	$title=$tpl->_ENGINE_parse_body("{routing_rules_explain}");

    $NetworkAdvancedRouting=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("NetworkAdvancedRouting"));
    $NetworkAdvancedRoutingHErmetic=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("NetworkAdvancedRoutingHErmetic"));
    if($NetworkAdvancedRouting==0){$NetworkAdvancedRoutingHErmetic=0;}



    $jsCheck=$tpl->framework_buildjs("/iproute/checks",
        "routes.check.progress","routes.check.progress.txt",
        "progress-iproute-restart",
        "LoadAjax('table-loader-iprule','$page?table=yes');"
    );
  $jsBuild=$tpl->framework_buildjs("/system/network/reconfigure-restart",
        "reconfigure-newtork.progress",
        "exec.virtuals-ip.php.html","progress-iproute-restart",
        "LoadAjax('table-loader-iprule','$page?table=yes');"
    ); // apply_network_configuration

    $DisableNetworking=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DisableNetworking"));
	$users=new usersMenus();
	
	$btn[]="<div class=\"btn-group\" data-toggle=\"buttons\">";
	if($DisableNetworking==0) {
        if ($users->AsSystemAdministrator) {
            $btn[] = "<label class=\"btn btn btn-primary\" OnClick=\"$jsBuild\">
                        <i class='fa fa-save'></i> {apply_network_configuration}
                       </label>";
        }
    }
    $btn[]="<label class=\"btn btn btn-warning\" OnClick=\"Loadjs('fw.network.gateway.php');\"><i class='fa fa-exchange'></i> {gateway_mode} </label>";
    if($DisableNetworking==0) {
        if ($users->AsSystemAdministrator) {
            $btn[] = "<label class=\"btn btn btn-primary\" OnClick=\"$jsCheck\"><i class='fa fa-save'></i> {verify_routes} </label>";
        }
    }
    $btn[]="<label class=\"btn btn btn-info\" OnClick=\"Loadjs('$page?show-conf=yes');\"><i class='fa fas fa-file-code'></i> {show_ip_tables} </label>";
    if($DisableNetworking==0) {
        $btn[] = "<label class=\"btn btn btn-warning\" OnClick=\"Loadjs('fw.network.advrouting.php');\"><i class='fa fa-exchange'></i> {advanced_routing} </label>";
    }


    $btn[]="</div>";


	if($DisableNetworking==1){
        $html[]=$tpl->FATAL_ERROR_SHOW_128("{DisableNetworking_explain}");
        echo $tpl->_ENGINE_parse_body(@implode("\n", $html));
        return;
    }

	$html[]="<table id='table-routing_rules' class=\"footable table table-stripped\" data-page-size=\"100\" data-paging=\"true\">";
	$html[]="<thead>";
	$html[]="<tr>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{status}</th>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text' nowrap>$nic</th>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text' nowrap>$table</th>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text'>$title</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{view2}</th>";
	$html[]="</tr>";
	$html[]="</thead>";
	$html[]="<tbody>";
	
	
	$sql="SELECT *  FROM `routing_rules`";
	$results = $q->QUERY_SQL($sql);
	
	$TRCLASS=null;
	$types[1]=$tpl->_ENGINE_parse_body("{network_nic}");
	$types[2]=$tpl->_ENGINE_parse_body("{host}");

    $EnableTailScaleService = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableTailScaleService"));
    if($EnableTailScaleService>0){
        $results[]=array(
            "RouteName"=>"TailScale routing",
            "nic"=>"tailscale0",
            "enabled"=>1
        );
    }
    $DOUBLE_tailscale=false;
	foreach ($results as $index=>$ligne){
        $lock=null;
        $Interface=trim($ligne["nic"]);
		$RouteName=trim($ligne["RouteName"]);
		if($Interface==null){continue;}
		if($RouteName==null){$RouteName="RouteFor$Interface";}
		$td_class=null;
		$md=md5(serialize($ligne));
        if(!isset($ligne["gateway"])){
            $ligne["gateway"]=null;
        }
        if($ligne["ID"]==999999){
            if($NetworkAdvancedRouting==0){continue;}
        }

		if($NetworkAdvancedRouting==0){
            $RouteName="{system_table_route}";
        }

		
		$status="<span class='label label-primary'>{active2}</span>";
        $jsCMD="Loadjs('fw.network.routing.rule.php?ID={$ligne["ID"]}');";


        if($Interface=="tailscale0"){
            if($DOUBLE_tailscale){continue;}
            $DOUBLE_tailscale=true;
            $RouteName="TailScale VPN Service routing";
            $jsCMD="Loadjs('fw.network.interfaces.php?nic-config-deny=tailscale0');";
        }

        $js="<a href=\"javascript:blur();\" style='font-weight:bold' OnClick=\"$jsCMD\">";
	
		if($ligne["enabled"]==0){
			$status="<span class='label'>{disabled}</span>";
			$td_class="class='rowDisabled'";
		}
		if($ligne["gateway"]==null){$ligne["gateway"]=$ligne["nic"];}
		$EXPLAIN_THIS_RULE=EXPLAIN_THIS_RULE($ligne["ID"],$ligne["nic"]);

		if($GLOBALS["EXPLAIN_THIS_RULE_ANSWER"]==0){
			$status="<span class='label'>{disabled}</span>";
			$td_class="class='rowDisabled'";
		}

		if($NetworkAdvancedRoutingHErmetic==1){
		    $lock="<i class=\"fad fa-lock\"></i>&nbsp;&nbsp;";
        }

        $Show=$tpl->icon_loupe(true,"Loadjs('$page?route-dump-eth-js=$Interface')");
		if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
		$html[]="<tr class='$TRCLASS' id='$md'>";
		$html[]="<td style='width:1%' nowrap>$status</td>";
		$html[]="<td style='width:1%' nowrap $td_class>$lock{$ligne["nic"]}</td>";
		$html[]="<td style='width:1%' nowrap $td_class>$js$RouteName</a></td>";
		$html[]="<td $td_class>$EXPLAIN_THIS_RULE</td>";
        $html[]="<td style='width:1%' nowrap>$Show</td>";
		$html[]="</tr>";

	
	}
    if($NetworkAdvancedRoutingHErmetic==1){
        $js="<a href=\"javascript:blur();\" 
        style='font-weight:bold' 
        OnClick=\"Loadjs('fw.network.routing.rule.php?ID=999999');\">";
        $EXPLAIN_THIS_RULE=EXPLAIN_THIS_RULE(999999);
        $Show=$tpl->icon_loupe(true,"Loadjs('$page?route-dump-eth-js=$Interface')");
        if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
        $html[]="<tr class='$TRCLASS' id='$md'>";
        $html[]="<td style='width:1%' nowrap>$status</td>";
        $html[]="<td style='width:1%' nowrap $td_class>{kernel}</td>";
        $html[]="<td style='width:1%' nowrap $td_class>$js{global_rules}</a></td>";
        $html[]="<td $td_class>$EXPLAIN_THIS_RULE</td>";
        $html[]="<td style='width:1%' nowrap>$Show</td>";
        $html[]="</tr>";

    }

	$html[]="</tbody>";
	$html[]="<tfoot>";
	$html[]="<tr>";
	$html[]="<td colspan='5'>";
	$html[]="<ul class='pagination pull-right'></ul>";
	$html[]="</td>";
	$html[]="</tr>";
	$html[]="</tfoot>";
	$html[]="</table>";


    $TINY_ARRAY["TITLE"]="{routing_rules}";
    $TINY_ARRAY["ICO"]="fa fa-road";
    $TINY_ARRAY["EXPL"]="{routing_rules_explain}</strong><br>{routing_rules_explain2}";
    $TINY_ARRAY["BUTTONS"]=@implode("",$btn);
    $jstiny="Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";


    $html[]="
	<script>
	NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS)."
	$(document).ready(function() { $('#table-routing_rules').footable( { 	\"filtering\": { \"enabled\": true }, \"sorting\": { \"enabled\": true },\"paging\": { \"size\": {$GLOBALS["FOOTABLE_PSIZE"]} } } ); });
	$jstiny
	</script>";
	
	echo $tpl->_ENGINE_parse_body(@implode("\n", $html));
	
}

function EXPLAIN_THIS_RULE_TAILSCALE(){
    $GLOBALS["CLASS_SOCKETS"]->getFrameWork("tailscale.php?status=yes");
    $S[]="{when_packets_processed_by_this_interface}: &laquo;tailscale0&raquo; TailScale VPN Interface";
    $INFOS=unserialize(@file_get_contents(PROGRESS_DIR."/tailscale.infos"));
    if(function_exists("json_decode")) {
        $json = json_decode($INFOS["ROUTES"]);
        foreach ($json as $index => $class) {
            if (property_exists($class, "dst")) {
                $S[] = "{or} {to} <strong>$class->dst</strong> {then} <strong>{listen_interface} tailscale0</strong>";
            }

        }
    }

    return @implode("<br>",$S);

}

function EXPLAIN_THIS_RULE($ruleid,$eth=null):string{
    if($eth=="tailscale0"){return EXPLAIN_THIS_RULE_TAILSCALE();}

	$q=new lib_sqlite("/home/artica/SQLITE/interfaces.db");
	$tpl=new template_admin();
	$D=array();
	$types[1]=$tpl->_ENGINE_parse_body("{network_nic}");
	$types[2]=$tpl->_ENGINE_parse_body("{host}");
	$types[3]=$tpl->_ENGINE_parse_body("NAT");
	$types[4]=$tpl->_ENGINE_parse_body("{blackhole}");
	$types[5]=$tpl->_ENGINE_parse_body("{iprouteprohibit}");

	if($ruleid<>999999) {
        if ($eth == null) {
            $results = $q->QUERY_SQL("SELECT nic FROM routing_rules WHERE ID='$ruleid'");
            $ligne = $results[0];
            $nicClass = new system_nic($ligne["nic"]);
            $eth = $ligne["nic"];
        } else {
            $nicClass = new system_nic($eth);
        }

        if ($nicClass->enabled == 0) {
            $GLOBALS["EXPLAIN_THIS_RULE_ANSWER"] = 0;
            return $tpl->_ENGINE_parse_body("{do_nothing} $nicClass->NICNAME {disabled}");
        }
        if ($nicClass->UseSPAN == 1) {
            return $tpl->_ENGINE_parse_body("{do_nothing} $nicClass->NICNAME {free_mode}");
        }

    }



	$results=$q->QUERY_SQL("SELECT * FROM routing_rules_src WHERE ruleid='$ruleid' ORDER BY zOrder");
    if($ruleid<>999999) {
        $S[]="{when_packets_processed_by_this_interface}: &laquo;$eth&raquo; $nicClass->NICNAME";
    }
	$IP=new IP();
    $scount=0;
    $S=array();

	foreach ($results as $index=>$ligne){
        $scount++;
        if($ruleid==999999) {
            $eth=$ligne["nic"];
            $nicClass = new system_nic($eth);
            $S[]="{when_packets_processed_by_this_interface}: &laquo;$eth&raquo; $nicClass->NICNAME";
        }


        if($ligne["pattern"]=="0.0.0.0/0"){$ligne["pattern"]="0.0.0.0/0.0.0.0";}
        if($ligne["pattern"]=="0.0.0.0/0.0.0.0"){$ligne["pattern"]="{all_networks}";}

		if($ligne["type"]==3){
			$S[]="{butif} {from} {$ligne["pattern"]} {then} <strong>{$types[$ligne["type"]]}</strong>";
			continue;
		}
		
		
		if($ligne["type"]>3){
			$S[]="{butif} {from} {$ligne["pattern"]} {then} <strong>{$types[$ligne["type"]]}</strong>";
			continue;
		}
		
		if($ligne["gateway"]<>null){
			$S[]="{butif} {from} {$ligne["pattern"]} {then} {use_gateway} <strong>{$ligne["gateway"]}</strong>";
			continue;
		}
		
		$S[]="{or} {from} {$types[$ligne["type"]]} {$ligne["pattern"]}";

	}

    $q->QUERY_SQL("DELETE FROM routing_rules_dest WHERE pattern LIKE '127.0.0%'");
	$results=$q->QUERY_SQL("SELECT * FROM routing_rules_dest WHERE ruleid='$ruleid' AND gateway='' ORDER BY zOrder");
    $iconnic=ico_nic;

	foreach ($results as $index=>$ligne){
        $scount++;

        if($ruleid==999999) {
            $eth=$ligne["nic"];
            $nicClass = new system_nic($eth);
        }
        $ifaceName="<i class='$iconnic'></i>&nbsp;{listen_interface} $eth";
        $outface=$ligne["outiface"];
        if(strlen($outface)>1){
            $ifaceName="<i class='$iconnic'></i>&nbsp;{interface} $outface";
        }

        VERBOSE("Type:{$ligne["type"]} {$ligne["pattern"]} --- $ifaceName",__LINE__);
		$S[]="{or} {to} {$types[$ligne["type"]]} {$ligne["pattern"]} {then} <strong>$ifaceName</strong>";

	}
    if($ruleid<>999999) {
        if ($nicClass->GATEWAY <> null) {
            if ($nicClass->GATEWAY <> "0.0.0.0") {
                $S[] = "{or} {from}/{to} <strong>{all_networks}</strong> {then_use_default_gateway} <strong>{$nicClass->GATEWAY}</strong>";
            }
        }
    }


	if(count($S)==0){
        if($ruleid<>999999) {
            $S[] = "{from}: <strong>{all_networks}</strong>";
        }
	}
    $results=$q->QUERY_SQL("SELECT * FROM routing_rules_dest WHERE ruleid='$ruleid' ORDER BY zOrder,metric");

	foreach ($results as $index=>$ligne){
		if($ligne["type"]==3){continue;}
		if($ligne["gateway"]==null){continue;}
        $ethText=null;
        if($ruleid==999999) {
            $eth=$ligne["nic"];
            $nicClass = new system_nic($eth);
            $ethText=" {listen_interface} $eth ($nicClass->NICNAME)";
        }
        $ID=$ligne["ID"];
        $js="Loadjs('fw.network.routing.rule.destinations.php?rule-js=$ID&ruleid=$ruleid');";

        $scount++;
        if($ligne["pattern"]=="0.0.0.0/0"){$ligne["pattern"]="{all_networks} ({other})";}
        if($ligne["pattern"]=="0.0.0.0/0.0.0.0"){$ligne["pattern"]="{all_networks} ({other})";}
        if(preg_match("#([0-9\.]+)\/([0-9\.]+)#",$ligne["pattern"],$re)){
            if(strlen($re[2])>2) {
                $ligne["pattern"] = $ligne["pattern"] . " (" . $IP->maskTocdir($re[1], $re[2]) . ")";
            }
        }
        $ligne["pattern"]=$tpl->td_href($ligne["pattern"],null,$js);
		$S[]="{or} {to} {$types[$ligne["type"]]} {$ligne["pattern"]} {use_the_gateway} <strong>{$ligne["gateway"]}</strong>{$ethText}
		";

	}


	$results=$q->QUERY_SQL("SELECT * FROM routing_rules_dest WHERE ruleid='$ruleid' AND type=3 ORDER BY zOrder,metric");


	foreach ($results as $index=>$ligne){
        $scount++;
        $ethText=null;
        if($ruleid==999999) {
            $eth=$ligne["nic"];
            $nicClass = new system_nic($eth);
            $ethText=" {listen_interface} $eth ($nicClass->NICNAME)";
        }

		$S[]="{or} {then} {default_gateway}: {$ligne["gateway"]}{$ethText}";

	}
	$GLOBALS["EXPLAIN_THIS_RULE_ANSWER"]=1;

	if($scount==0){
        return $tpl->_ENGINE_parse_body("{do_nothing}");
    }

    if($ruleid<>999999) {
        if (count($S) < 2) {
            $S[] = "{do_nothing}";
        }
    }

	$THEN_TEXT=@implode("<br>", $S);

    if(isset($_GET["explain-this-rule"])){
        return $tpl->_ENGINE_parse_body($THEN_TEXT);
    }

    $page=CurrentPageName();
    return  $tpl->_ENGINE_parse_body("<span id='explain-this-rule-$ruleid' data='$page?explain-this-rule=$ruleid&eth=$eth'>$THEN_TEXT</span>");




}
function route_dump(){
	$page=CurrentPageName();
	echo "<div id='route-dump-fields'></div>
	<script>
	function RouteDumpFields(){
	LoadAjax('route-dump-fields','$page?route-dump2={$_GET["route-dump"]}');
}
RouteDumpFields();
</script>";
}


function route_dump2(){
	$tpl=new template_admin();
	$t=explode("\n",@file_get_contents("/etc/iproute2/rt_tables"));
	
	$c=0;
	exec("/sbin/ip rule 2>&1",$results);
	$html[]=$tpl->field_section("{rules}");
	$html[]=$tpl->field_textareacode("titi0", null, @implode("\n", $results));
	
	foreach ($t as $line){
		if(!preg_match("#^([0-9]+)\s+(.+)#", $line,$re)){continue;}
		$tablename=trim($re[2]);
		$results=array();
		exec("/sbin/ip route show table $tablename 2>&1",$results);
		if(count($results)==0){continue;}
		$c++;
		$html[]=$tpl->field_section("{table} $tablename");
		$html[]=$tpl->field_textareacode("titi$c", null, @implode("\n", $results));
		
	}
	
	echo $tpl->form_outside("{configuration}", @implode("\n", $html),null,"{apply_network_configuration}","Loadjs('fw.network.apply.php')","AsSystemAdministrator");
	
}
