<?php
include_once(dirname(__FILE__) . "/ressources/class.template-admin.inc");
include_once(dirname(__FILE__) . "/ressources/class.sockets.inc");
include_once(dirname(__FILE__) . "/ressources/class.computers.inc");
$GLOBALS["CLASS_SOCKETS"] = new sockets();

if(isset($_POST["OSPFRouterIdent"])){Save();exit;}
if(isset($_GET["tabs"])){tabs();exit;}
if(isset($_GET["status"])){status();exit;}
if(isset($_GET["status2"])){status2();exit;}
if(isset($_GET["quagga-status"])){quagga_status();exit;}
if(isset($_GET["infos"])){infos();exit;}
if(isset($_GET["events"])){events();exit;}
if(isset($_GET["search"])){search();exit;}

page();

function page(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $html[]="
	<div class=\"row border-bottom white-bg dashboard-header\">
		<div class=\"col-sm-12\">
		    <h1 class=ng-binding>{APP_OSPF}</h1>
		    <p>{APP_OSPF_EXPLAIN}</p></div>
	</div>
	<div class='row'>
	<div id='progress-firehol-restart'></div>
	";
    $html[]="
    </div><div class='row'>
                <div class='ibox-content'>";
    $html[]="        <div id='table-loader-ospf'></div>
	            </div>
	</div>
<script>
	$.address.state('/');
	$.address.value('/ospf');
	LoadAjax('table-loader-ospf','$page?tabs=yes');
</script>";
    if(isset($_GET["main-page"])){
        $tpl=new template_admin(null,@implode("\n", $html));
        echo $tpl->build_firewall();
        return;
    }
    echo $tpl->_ENGINE_parse_body(@implode("\n", $html));
}

function tabs(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $array["{status}"]="$page?status=yes";
    $array["{infos}"]="$page?infos=yes";
    $array["{events}"]="$page?events=yes";
   // $array["{nic_infos}"]="$page?table-start=yes";
   // $array["{open_ports}"]="fw.openports.php";
    echo $tpl->tabs_default($array);
}

function status(){
    $page=CurrentPageName();
    $GLOBALS["CLASS_SOCKETS"]->getFrameWork("ospfd.php?refresh=yes");
    echo "<div id='ospfd-section-progress' style='margin-top:10px'></div>";
    echo "<div id='ospfd-section-status'></div>
    <script>LoadAjax('ospfd-section-status','$page?status2=yes');</script>";
}
function status2(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $OSPFInfo           = unserialize($GLOBALS["CLASS_SOCKETS"]->GET_INFO("OSPFInfo"));
    $OSPFRouterIdent    = $GLOBALS["CLASS_SOCKETS"]->GET_INFO("OSPFRouterIdent");
    if(!isset($OSPFInfo["ROUTERS"])){$OSPFInfo["ROUTERS"]=array();}
    $CountRouters       = count($OSPFInfo["ROUTERS"]);
    $CountRoutes        = count($OSPFInfo["ROUTES"]);
    $security           = "AsSystemAdministrator";
    if($CountRouters==0) {
        $RT = $tpl->widget_h("gray", "fad fa-router", 0, "{routers}");
    }else{
        $RT = $tpl->widget_h("green", "fad fa-router", $CountRouters, "{routers}");
    }

    //<i class="fas fa-road"></i>
    if($CountRoutes==0) {
        $RTs = $tpl->widget_h("gray", "fas fa-road", 0, "{routing_rules}");
    }else{
        $RTs = $tpl->widget_h("green", "fas fa-road", $CountRoutes, "{routing_rules}");
    }

    $topstatus="<table style='width:100%'>
	    <tr>
	    <td style='vertical-align:top;width:200px;padding:8px'>$RT</td>
	    <td style='vertical-align:top;width:200px;padding:8px'>$RTs</td>
	    <td style='vertical-align:top;width:200px;padding:8px'>&nbsp;</td>
	    </tr>
	   </table>";

    $q=new lib_sqlite("/home/artica/SQLITE/interfaces.db");
    $sql="SELECT Interface FROM nics WHERE enabled=1";
    $results=$q->QUERY_SQL($sql);
    $main=array();
    foreach ($results as $index=>$ligne){
        $Interface  = $ligne["Interface"];
        $nicz=new system_nic($Interface);
        if($nicz->enabled==0){continue;}
        if($nicz->IPADDR=="0.0.0.0"){continue;}
        $main[$Interface]="$nicz->IPADDR - $nicz->NICNAME";
        $form[]=$tpl->field_checkbox("ENABLE_INT_$Interface","{inform_from} $Interface <small>($nicz->NICNAME/$nicz->IPADDR)</small>",$nicz->ospf_enable);

    }

    $form[]=$tpl->field_array_hash($main,"OSPFRouterIdent","{cisco_asa_address}",$OSPFRouterIdent);
    $q              = new lib_sqlite("/home/artica/SQLITE/interfaces.db");
    $CountOfNets    = $q->COUNT_ROWS("ospf_networks");
    $form[]=$tpl->field_info("OSPFNoneInfo", "{propagate_routes}",

        array("VALUE"=>null,
            "BUTTON"=>true,
            "BUTTON_CAPTION"=>"$CountOfNets {areas}",
            "BUTTON_JS"=>"Loadjs('fw.ospf.network.php')"

        ),"{ospf_network_explain}");


    $formfinal=$tpl->form_outside("{general_settings}",
        @implode("\n", $form),null,"{apply}",
        restart_script(),$security);

    $html[]="<table style='width:100%'>";
    $html[]="<tr>";
    $html[]="<td valign='top' style='width:240px'><div id='quagga-status'></div></td>";
    $html[]="<td valign='top' style='width:99%;padding-left:20px'>$topstatus 
    <div style='margin-left:50px;margin-right: 50px '>$formfinal</div></td>";
    $html[]="</tr>";
    $html[]="</table>";
    $html[]="<script>";
    $html[]="LoadAjax('quagga-status','$page?quagga-status=yes');";
    $html[]="</script>";
    echo $tpl->_ENGINE_parse_body($html);
}
function quagga_status(){
    $GLOBALS["CLASS_SOCKETS"]->getFrameWork('ospfd.php?status=yes');
    $tpl        = new template_admin();
    $statusf    = PROGRESS_DIR."/quagga.status";
    $ini        = new Bs_IniHandler($statusf);
    $status1    = $tpl->SERVICE_STATUS($ini, "APP_OSPF",restart_script());
    $status2    = $tpl->SERVICE_STATUS($ini, "APP_ZEBRA");

    echo "<table style='width:100%'><tr><td>$status1</td></tr><tr><td>&nbsp;</td></tr><tr><td>$status2</td></tr></table>";

}
function restart_script(){
    $page=CurrentPageName();
    $ARRAY["PROGRESS_FILE"]=PROGRESS_DIR ."/ospfd.progress";
    $ARRAY["LOG_FILE"]=PROGRESS_DIR ."/ospfd.log";
    $ARRAY["CMD"]="ospfd.php?restart=yes";
    $ARRAY["TITLE"]="{restarting}";
    $ARRAY["AFTER"]="LoadAjax('ospfd-section-status','$page?status2=yes');";
    $prgress=base64_encode(serialize($ARRAY));
    $jsafter="Loadjs('fw.progress.php?content=$prgress&mainid=ospfd-section-progress')";
    return $jsafter;
}

function infos(){
    $tpl        = new template_admin();
    $OSPFInfo   = unserialize($GLOBALS["CLASS_SOCKETS"]->GET_INFO("OSPFInfo"));
    $thdef      = "data-sortable=true class='text-capitalize'";
    $TRCLASS    = null;
    $icort      = "<i class='fad fa-router'></i>&nbsp;";
    $icors      = "<i class='fas fa-road'></i>&nbsp;";
    $t          = time();
    $t2         = $t."-rand";
    $thead      = "class=\"table table-stripped\"";


    $html[]="<H2>{routers}</H2>";
    $html[]="<table id='table-$t' $thead>";
    $html[]="<thead>";
    $html[]="<tr>";
    $html[]="<th $thdef nowrap>{cisco_asa_address}</th>";
    $html[]="<th $thdef nowrap>{expire}</th>";
    $html[]="<th $thdef nowrap>PRIO</th>";
    $html[]="<th $thdef nowrap>{status}</th>";
    $html[]="</tr>";
    $html[]="</thead>";
    $html[]="<tbody>";
    $routers=$OSPFInfo["ROUTERS"];
    foreach ($routers as $routerid=>$ligne){
        $md         = md5(serialize($ligne));
        $prio       = $ligne["PRIO"];
        $expire     = $ligne["EXPIRE"];
        $status     = $ligne["STATUS"];
        $interface  = $ligne["localiface"];
        $router_ip  = $ligne["routerip"];
        $localip    = $ligne["localip"];
        if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
        $html[]="<tr class='$TRCLASS' id='$md'>";
        $html[]="<td class=\"\" width='100%' nowrap>$icort<strong>$routerid/$router_ip</strong> ($interface - $localip ) </td>";
        $html[]="<td class=\"\" width=1% nowrap>$expire</td>";
        $html[]="<td class=\"\" width=1% nowrap>$prio</td>";
        $html[]="<td class=\"\" width=1% nowrap>$status</td>";
        $html[]="</tr>";
    }
    $html[]="</tbody>";
    $html[]="</table>";
    $html[]="<p>&nbsp;</p>";

    $ROUTES=$OSPFInfo["ROUTES"];
    $TRCLASS    = null;
    $html[]="<H2>{routing_rules}</H2>";
    $html[]="<table id='table-$t2' $thead>";
    $html[]="<thead>";
    $html[]="<tr>";
    $html[]="<th $thdef nowrap>{routing_rules}</th>";
    $html[]="<th $thdef nowrap>{interface}</th>";
    $html[]="<th $thdef nowrap>{time}</th>";
    $html[]="<th $thdef nowrap>{info}</th>";
    $html[]="</tr>";
    $html[]="</thead>";
    $html[]="<tbody>";

    foreach ($ROUTES as $net=>$ligne){
        $md         = md5(serialize($ligne));
        $text       = $ligne["text"];
        $INTERFACE  = $ligne["INTERFACE"];
        $TIME       = $ligne["TIME"];
        if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
        $html[]="<tr class='$TRCLASS' id='$md'>";
        $html[]="<td class=\"\" width=1% nowrap>$icors<strong>$net</strong></td>";
        $html[]="<td class=\"\" width=1% nowrap>$INTERFACE</td>";
        $html[]="<td class=\"\" width=1% nowrap>$TIME</td>";
        $html[]="<td class=\"\" width=100% nowrap>$text</td>";
        $html[]="</tr>";
    }

    $html[]="</tbody>";
    $html[]="</table>";
    $html[]="<p>&nbsp;</p>";

    echo $tpl->_ENGINE_parse_body($html);

}

function events(){
    $tpl        = new template_admin();
    $page       = CurrentPageName();

    echo $tpl->search_block($page);


}
function search(){

    $tpl        = new template_admin();
    $MAIN       = $tpl->format_search_protocol($_GET["search"]);
    $line       = base64_encode(serialize($MAIN));
    $tfile      = PROGRESS_DIR."/ospfd.syslog";
    $pat        = PROGRESS_DIR."/ospfd.pattern";

    $GLOBALS["CLASS_SOCKETS"]->getFrameWork("ospfd.php?syslog=$line");
    $data=explode("\n",@file_get_contents($tfile));
    krsort($data);
    $html[]="
<table class=\"table table-hover\">
	<thead>
    	<tr>
        	<th>{date}</th>
        	<th>PID</th>
        	<th>{events}</th>
        </tr>
  	</thead>
	<tbody>
";


    foreach ($data as $line){
       if(!preg_match("#^(.*?)\s+([0-9]+)\s+([0-9:]+)\s+.*?\[([0-9]+)\]:\s+(.+)#",$line,$re)){
           continue;
       }
       $class=null;
       $Month=$re[1];
       $Day=$re[2];
       $time=$re[3];
       $pid=$re[4];
       $event=$re[5];

       if(preg_match("#(no Link|abort|Terminating|Shutdown)#i",$line)){
           $class="text-warning";
       }
        if(preg_match("#(failed|fatal|error)#i",$line)){
            $class="text-danger";
        }

        $html[]="<tr>
				<td style='width:1%;' nowrap class='$class'>$Month $Day $time</td>
				<td style='width:1%;' nowrap class='$class'>$pid</td>
				<td class='$class'>$event</td>
				</tr>";

    }

    $html[]="</tbody></table>";
    $html[]="<div><i>".@file_get_contents($pat)."</i></div>";
    echo $tpl->_ENGINE_parse_body($html);

}

function Save(){
    $tpl=new template_admin();
    $tpl->SAVE_POSTs();
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("OSPFRouterIdent",$_POST["OSPFRouterIdent"]);

    foreach ($_POST as $key=>$val){
        if(!preg_match("#ENABLE_INT_(.+)#",$key,$re)){
            writelogs("REFUSED: $key",__FUNCTION__,__FILE__,__LINE__);
            continue;}
        writelogs("ACCEPTED: $re[1] for $val",__FUNCTION__,__FILE__,__LINE__);
        $nicz=new system_nic($re[1]);
        $nicz->ospf_enable=$val;
        $nicz->NoReboot=true;
        $nicz->SaveNic();
    }

}