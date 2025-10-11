<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.logfile_daemon.inc");
include_once(dirname(__FILE__)."/ressources/class.squid.familysites.inc");
include_once(dirname(__FILE__)."/ressources/class.mysql.catz.inc");
include_once(dirname(__FILE__)."/ressources/class.tcpip.inc");
include_once(dirname(__FILE__)."/ressources/class.geoip-db.inc");
if(!ifisright()){exit();}
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(isset($_GET["search"])){search();exit;}
if(isset($_GET["zoom-js"])){zoom_js();exit;}
if(isset($_GET["button"])){button_white();exit;}
if(isset($_GET["whitelist-js"])){whitelist_js();exit;}


page();

function ifisright(){
    $users=new usersMenus();
    if($users->AsProxyMonitor){return true;}
    if($users->AsWebStatisticsAdministrator){return true;}
    if($users->AsDnsAdministrator){return true;}
    if($users->AsFirewallManager){return true;}
}
function page(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $t=time();
    if(!isset($_SESSION["WEBF_SEARCH"])){$_SESSION["WEBF_SEARCH"]="50 events";}
    if(isset($_GET["logfile"])){$addPLUS="&logfile=".urlencode($_GET["logfile"]);}

    $html="
	<div class=\"row border-bottom white-bg dashboard-header\">
		<div class=\"col-sm-8\"><h1 class=ng-binding>{DNS_QUERIES}</h1></div>
	</div>
	<div class=\"row\"> 
		<div class='ibox-content'>
		<div class=\"input-group\">
      		<input type=\"text\" class=\"form-control\" value=\"{$_SESSION["WEBF_SEARCH"]}\" placeholder=\"{search}\" id='search-this-$t' OnKeyPress=\"javascript:Search$t(event);\">
      		<span class=\"input-group-btn\">
       		 <button style=\"text-transform: capitalize;\" class=\"btn btn-default\" type=\"button\" OnClick=\"javascript:ss$t();\">Go!</button>
      	</span>
     </div>
    </div>
</div>
	<div class='row' id='spinner'>
		<div id='progress-firehol-restart'></div>
		<div  class='ibox-content'>
		<div class='sk-spinner sk-spinner-wave'>
			<div class='sk-rect1'></div>
			<div class='sk-rect2'></div>
			<div class='sk-rect3'></div>
			<div class='sk-rect4'></div>
			<div class='sk-rect5'></div>
		</div>
		
		
			<div id='table-loader'></div>
		</div>
	</div>
	</div>
	<script>
	$.address.state('/');
	$.address.value('/dns-queries');
		function Search$t(e){
			if(!checkEnter(e) ){return;}
			ss$t();
		}
		
		function ss$t(){
			var ss=encodeURIComponent(document.getElementById('search-this-$t').value);
			LoadAjax('table-loader','$page?search='+ss+'$addPLUS');
		}
		
		function Start$t(){
			var ss=document.getElementById('search-this-$t').value;
			ss$t();
		}
		Start$t();
	</script>";

    if(isset($_GET["main-page"])){
        $tpl=new template_admin(null,$html);
        echo $tpl->build_firewall();
        return;
    }


    echo $tpl->_ENGINE_parse_body($html);

}


function search(){

    $time=null;
    $page=CurrentPageName();
    $sock=new sockets();
    $tpl=new template_admin();
    $GLOBALS["TPLZ"]=$tpl;

    $MAIN=$tpl->format_search_protocol($_GET["search"]);
    $sock->getFrameWork("unbound.php?fw-requests=yes&rp={$MAIN["MAX"]}&query=".urlencode($MAIN["TERM"]));

    $ipaddr=$tpl->javascript_parse_text("{ipaddr}");
    $zdate=$tpl->_ENGINE_parse_body("{zDate}");

    $html[]="

<table class=\"table table-hover\">
	<thead>
    	<tr>
        	<th>$zdate</th>
        	<th nowrap>$ipaddr</th>
			<th>{type}</th>
            <th>{query}</th>
        </tr>
  	</thead>
	<tbody>
";

    $filename="/usr/share/artica-postfix/ressources/logs/unbound.log.tmp";
    $data=explode("\n",@file_get_contents($filename));

    krsort($data);


    $COUNTRIES=GEO_IP_COUNTRIES_LIST();
    $q=new lib_sqlite("/home/artica/SQLITE/acls.db");
    $results=$q->QUERY_SQL("SELECT ID,rulename FROM dnsfw_acls");
    foreach ($results as $ligne){
        $FWRULES[$ligne["ID"]]=$ligne["rulename"];
    }




    foreach ($data as $line){
        $TR=preg_split("/[\s]+/", $line);
        $md=md5($line);
        $color="rgb(103, 106, 108);";
        if(count($TR)<5){continue;}
        $date=$tpl->time_to_date(strtotime($TR[0]." ".$TR[1]. " ".$TR[2]),true);
        $strong=true;
        $fullconcat=false;
        if($TR[0]=='TypeError:' || $TR[0]=='NameError:'){$fullconcat=true;}

        $query=$TR[8];
        $type=$TR[9];
        $CLIENT_IP=$TR[7];
        $category=null;
        VERBOSE($line,__LINE__);
        VERBOSE("query=$query type=$type [$CLIENT_IP]",__LINE__);
        VERBOSE("TR[10]={$TR[10]}",__LINE__);

        if(isset($TR[11])){
            $rulename=null;
            $fpattern="{$TR[11]} {$TR[12]}";
            if(preg_match("#\[([A-Z]+):([0-9]+)\]:(.+)#",$fpattern,$re)){
                $strong=false;
                $red="rgb(237, 85, 101)";
                $action=$re[1];
                $ruleid=$re[2];
                $direction=$re[3];

            }

                $tb=explode(";",$TR[11]);
                $COUNTRY=$COUNTRIES[$tb[0]];
                $AS=$tb[1];
                $ISP=$tb[2];
                if(isset($FWRULES[$ruleid])){$rulename=$FWRULES[$ruleid];}
                $sst=array();
                $addinfo=null;
                if($COUNTRY<>null){$sst[]=$COUNTRY;}
                if($AS<>"-"){$sst[]="AS$AS";}
                if($ISP<>"-"){$sst[]="$ISP";}
                if($category<>null){$sst[]=$category;}
                if(count($sst)>0){$addinfo="(".@implode(" / ",$sst).")";}

                $query="$query <strong>$addinfo</strong> $direction <strong style='color:$red'>[$action] $rulename</strong>";

            }



        $concat=false;
        if(preg_match("#_out(){2375}#",$TR[5])){
            $concat=true;
        }

        if($TR[5]=="init" || $TR[5]=="start" || $TR[5]=="service" || $TR[5]=="DNS" || $TR[5]=="_sasl_plugin_load" || $TR[5]=="module" || $TR[5]=="MODULE_INIT:" || $TR[5]=="error:" || $TR[5]=="pythonmod"){
            $concat=true;

        }

        if($TR[6]=="ERROR"){$concat=True;$color="rgb(237, 85, 101)";}

        if(preg_match("#^[A-Z\_]+:#",$TR[5])){

            $type=$TR[5];
            $CLIENT_IP=$TR[6];
            $CLIENT_IP=str_replace(array('[',']'),"",$CLIENT_IP);

            if(preg_match("#^([A-Z]+):(.+)#",$TR[7],$re)){
                $type=$re[1];
            }

            $squery=null;
                for($i=6;$i< (count($TR));$i++){
                    $squery="$squery ".$TR[$i];
                }
                $query=trim($re[2]." {$TR[5]} $squery");

        }

        if($concat){
            $type="-";
            $CLIENT_IP="-";
            $squery=null;
            $strong=false;
            for($i=5;$i< (count($TR));$i++){
                $squery="$squery ".$TR[$i];
            }
            $query=$squery;
        }


        if($TR[5]=="--------------:"){
            $strong=false;
            $query=null;
            for($i=6;$i< (count($TR));$i++){
                $query="$query ".$TR[$i];
            }
            $CLIENT_IP="-";
            $type="DEBUG";
            $query="<code>$query</code>";

        }

        if($type=="BLOCK"){
            $color="rgb(237, 85, 101)";
        }

        if($strong){
            $query="<strong>{$query}</strong>";
        }

        if($fullconcat){
            $query=null;
            foreach ($TR as $line){
                $query="$query $line";
            }
            $query=trim($query);
            $date="-";
            $CLIENT_IP="-";
            $type="-";
            $color="rgb(237, 85, 101)";
        }
        if(preg_match("#(error|Exception)#",$query)){
            $color="rgb(237, 85, 101)";
        }

        $html[]="<tr id='$md'>
				<td style='color:$color' width=1% nowrap>$date</span></td>
				<td style='color:$color' width=1% nowrap>$CLIENT_IP</span></td>
				<td style='color:$color' width=1% nowrap>$type</span></td>
                <td style='color:$color' width=99%>$query</td>            
 

                </tr>";

    }
    $html[]="<tfoot>";

    $html[]="<tr>";
    $html[]="<td colspan='4'>";
    $html[]="<ul class='pagination pull-right'></ul>";
    $html[]="</td>";
    $html[]="</tr>";
    $html[]="</tfoot>";
    $html[]="</tbody></table>";
    $html[]="<div>".@file_get_contents("/usr/share/artica-postfix/ressources/logs/unbound.log.cmd")."</div>
	<script>
		NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS)."
		$(document).ready(function() { $('.footable').footable( { \"filtering\": { \"enabled\": true }, \"sorting\": { \"enabled\": true },\"paging\": { \"size\": {$GLOBALS["FOOTABLE_PSIZE"]} } } ); });
	</script>";
    echo $tpl->_ENGINE_parse_body($html);



}