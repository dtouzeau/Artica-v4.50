<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
$users=new usersMenus();if(!$users->AsFirewallManager){exit();}
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(isset($_GET["search"])){search();exit;}
page();


function page(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$t=time();
	if(!isset($_SESSION["HACLUSTER_SEARCH"])){$_SESSION["HACLUSTER_SEARCH"]="";}
	
	$html="

	<div class=\"row\"> 
		<div class='ibox-content'>
		<div class=\"input-group\">
      		<input type=\"text\" class=\"form-control\" value=\"{$_SESSION["HACLUSTER_SEARCH"]}\" placeholder=\"{search}\" id='search-this-$t' OnKeyPress=\"Search$t(event);\">
      		<span class=\"input-group-btn\">
       		 <button style=\"text-transform: capitalize;\" class=\"btn btn-default\" type=\"button\" OnClick=\"ss$t();\">Go!</button>
      	</span>
     </div>
    </div>
</div>
	<div class='row'><div id='progress-firehol-restart'></div>
	<div class='ibox-content'>

	<div id='table-loader'></div>

	</div>
	</div>
	<script>
	$.address.state('/');
	$.address.value('/hacluster-events');
		function Search$t(e){
			if(!checkEnter(e) ){return;}
			ss$t();
		}
		
		function ss$t(){
			var ss=encodeURIComponent(document.getElementById('search-this-$t').value);
			LoadAjax('table-loader','$page?search='+ss);
		}
		
		function Start$t(){
			var ss=document.getElementById('search-this-$t').value;
			ss$t();
			
		}
		Start$t();
	</script>";

	if(isset($_GET["main-page"])){
		$tpl=new template_admin("{APP_HACLUSTER}",$html);
		echo $tpl->build_firewall();
		return;
	}
	
	echo $tpl->_ENGINE_parse_body($html);

}

function search(){
	$time=null;
	$sock=new sockets();
	$tpl=new template_admin();
	$max=0;$date=null;$c=0;
	
	$MAIN=$tpl->format_search_protocol($_GET["search"]);
	
	$line=base64_encode(serialize($MAIN));
	$sock->getFrameWork("hacluster.php?syslog=$line");
	$filename=PROGRESS_DIR."/hacluster.syslog";
	$date_text=$tpl->_ENGINE_parse_body("{date}");
	$events=$tpl->_ENGINE_parse_body("{events}");
	$html[]="
<table class=\"table table-hover\">
	<thead>
    	<tr>
        	<th>$date_text</th>
        	<th>PID</th>
        	<th>$events</th>
        </tr>
  	</thead>
	<tbody>
";

    $q=new lib_sqlite("/home/artica/SQLITE/haproxy.db");
    $results=$q->QUERY_SQL("SELECT ID,backendname FROM hacluster_backends");

    foreach ($results as $index=>$ligne) {
        $ID = intval($ligne["ID"]);
        if (strpos($ligne["backendname"],".")>0){
            $tb=explode(".",$ligne["backendname"]);
            $ligne["backendname"]=$tb[0];
        }
        $BACKENDS[$ID] = $ligne["backendname"];
    }


    $data=explode("\n",@file_get_contents($filename));
	if(count($data)>3){$_SESSION["HACLUSTER_SEARCH"]=$_GET["search"];}
	krsort($data);


	
	foreach ($data as $line){
		$line=trim($line);
		$ruleid=0;
		$rulename=null;
		$ACTION=null;
		$FF=false;
		if(!preg_match("#^([A-Za-z]+)\s+([0-9]+)\s+([0-9:]+)\s+(.+?)\s+.*?\[([0-9]+)\]:(.+)#", $line,$re)){
			if($GLOBALS["VERBOSE"]) {
                echo "<strong style='color:red'>$line</strong><br>";
            }
			continue;}

        if($GLOBALS["VERBOSE"]) {
            print_r($re);
        }

		$xtime=strtotime($re[1] ." ".$re[2]." ".$re[3]);
		$FTime=date("Y-m-d H:i:s",$xtime);
		$curDate=date("Y-m-d");
		$FTime=trim(str_replace($curDate, "", $FTime));
		$pid=$re[5];
		$line=$re[6];
        $span="<span class='text-muted'>";

		if(preg_match("#(is UP)#i", $line)){
            $span="<span class='text-success'>";
        }

        VERBOSE("FTime=$FTime",__LINE__);



		if(preg_match("#( DOWN|no server available)i#", $line)){
            $span="<span class='font-bold text-danger' id='".__LINE__."'>";
		}
        if(preg_match("#(stopped|reloading)#i", $line)){
            $span="<span class='text-warning'>";
        }
        if(preg_match("#Error#i", $line)) {
            $span = "<span class='font-bold text-danger' id='".__LINE__."'>";
        }
        if(preg_match("#Unable to post#i", $line)) {
            $span = "<span class='font-bold text-danger' id='".__LINE__."'>";
        }


        $line=str_replace("[STOPPING]:","<span class='label label-warning'>Stopping</span>&nbsp;",$line);
        $line=str_replace("[STOPPING]","<span class='label label-warning'>Stopping</span>&nbsp;",$line);
        $line=str_replace("[STARTING]","<span class='label label-default'>Starting</span>&nbsp;",$line);
        $line=str_replace("[RECONFIGURE]:","<span class='label label-default'>Configure</span>&nbsp;",$line);
        $line=str_replace("[RESTART]:","<span class='label label-warning'>Restart</span>&nbsp;",$line);
        $line=str_replace("[MICRONODE]:","<span class='label label-default'>MicroNode</span>&nbsp;",$line);
        $line=str_replace("[STANDARD]:","<span class='label label-default'>Info</span>&nbsp;",$line);
        $line=str_replace("[WATCHDOG]:","<span class='label label-danger'>Watchdog</span>&nbsp;",$line);
        $line=str_replace("SIGHUP:","<span class='label label-warning'>Reload</span>&nbsp;",$line);
        $line=str_replace("SIGHUP received","<span class='label label-warning'>Reload</span>&nbsp;",$line);


        if(strpos($line,"no server available")>0){
            $line=str_replace("label-warning","label-danger",$line);
        }



        $STOP=false;
        $line=str_replace("ReplicMaster.go","<span class='label label-default'>Cluster</span>&nbsp;",$line);
        $line=str_replace("ReplicMaster.go","<span class='label label-default'>Cluster</span>&nbsp;",$line);
        $line=str_replace("WatchDog.go","",$line);
        $line=str_replace("HaClusterWatchdog.go[haclusterserv/HaClusterWatchdog.Watchdog:","[",$line);
        $line=str_replace("HaClusterServer.go","",$line);
        $line=str_replace("haclusterserv.","",$line);
        $line=str_replace("hacluster/","",$line);

        if(preg_match("#Cluster package#", $line)){
            $line="<span class='label label-default'>Package</span>&nbsp;$line";
        }
        if(strpos($line,"[CERTIFICATE]")>0){
            $line=str_replace("[CERTIFICATE]","",$line);
            $line="<span class='label label-warning'>Certificate</span>&nbsp;$line";
        }
        if(strpos($line,"PushCommands.go")>0){
            $line=str_replace("PushCommands.go","",$line);
            $line="<span class='label label-default'>Communication</span>&nbsp;$line";
        }


        if(preg_match("#server ([a-zA-Z]+)/([a-zA-Z]+)([0-9]+) failed, reason: Layer4 connection problem, info:.*?Connection refused#", $line, $re)){
            $name=$BACKENDS[intval($re[3])];
            $line=str_replace("$re[1]/$re[2]$re[3]","",$line);
            $line=str_replace("for server failed","",$line);
            $line="<span class='label label-danger'>$name DOWN</span>&nbsp;<span class='label label-danger'>Connection refused</span>&nbsp;$line";
            $STOP=true;
        }
        if(preg_match("#Agent  for server ([a-zA-Z]+)/([a-zA-Z]+)([0-9]+) failed#", $line, $re)){
            $name=$BACKENDS[intval($re[3])];
            $line=str_replace("$re[1]/$re[2]$re[3]","",$line);
            $line=str_replace("for server failed","",$line);
            $line="<span class='label label-danger'>$name DOWN</span>&nbsp;<span class='label label-default'>Agent</span>&nbsp;$line";
            $STOP=true;
        }

        if(preg_match("#\[(.+?):([0-9]+)\]#",$line)){
            $line=trim(str_replace("[".$re[1].":".$re[2]."]","",$line));
            if(intval($re[2])>0) {
                $line = $line . "&nbsp;<small>$re[1] line:$re[2]</small>";
            }
        }
        if(strpos("  $line","HaClusterDNS.go")>0){
            $line=str_replace("HaClusterDNS.go","<span class='label label-success'>DNS</span>&nbsp;",$line);
        }
        if(strpos("  $line","[HaClusterDNS.Reload:")>0){
            $line=str_replace("[HaClusterDNS.Reload:","&nbsp;<span class='label label-warning'>{reload}</span>&nbsp;[",$line);
            $line=$tpl->_ENGINE_parse_body($line);
        }
        
        if(!$STOP) {
            if (preg_match("#Server ([a-zA-Z]+)/([a-zA-Z]+)([0-9]+)\s+is UP.#i", $line, $re)) {
                $name = $BACKENDS[intval($re[3])];
                $line=str_replace("$re[1]/$re[2]$re[3]","",$line);
                $line = "<span class='label label-primary'>$name UP</span>&nbsp;$line";
            }
            if (preg_match("#Server ([a-zA-Z]+)/([a-zA-Z]+)([0-9]+)\s+is DOWN.#i", $line, $re)) {
                 $name = $BACKENDS[intval($re[3])];
                $line=str_replace("$re[1]/$re[2]$re[3]","",$line);
                $line=str_replace("for server failed","",$line);
                $span = "<span class='font-bold text-danger'>";
                $line = "<span class='label label-danger'>$name DOWN</span>&nbsp;$line";
            }
            if (preg_match("#check for server ([a-zA-Z]+)/([a-zA-Z]+)([0-9]+)\s+succeeded#i", $line, $re)) {
                $name = $BACKENDS[intval($re[3])];
                $line=str_replace("$re[1]/$re[2]$re[3]","",$line);
                $line=str_replace("for server failed","",$line);
                $line = "<span class='label label-primary'>$name OK</span>&nbsp;$line";
            }
            if (preg_match("#backend proxys has no server available", $line, $re)) {
                $line = "<span class='label label-danger'>FATAL</span>&nbsp;$line";
                $span = "<span class='font-bold text-danger'>";
            }
            if (preg_match("#check for server ([a-zA-Z]+)/([a-zA-Z]+)([0-9]+)\s+failed#i", $line, $re)) {
                $line=str_replace("$re[1]/$re[2]$re[3]","",$line);
                $line=str_replace("for server failed","",$line);
                $name = $BACKENDS[intval($re[3])];
                $line = "<span class='label label-danger'>$name DOWN</span>&nbsp;$line";
            }
            $line = str_replace("Agent check", "<span class='label label-default'>Agent</span>&nbsp;", $line);
            $line = str_replace("Connection refused", "<span class='label label-danger'>Connection refused</span>&nbsp;", $line);
            $line = str_replace("127.0.0.1:3128 Error 52 Connection timed out", "<span class='label label-danger'>Load-balancer</span>&nbsp; Connection Timed out!", $line);

        }

        $line=str_replace("Agent check for server failed,","<span class='label label-default'>Agent</span>",$line);
        $line=str_replace("&nbsp; Agent check &nbsp;,","<span class='label label-default'>Agent</span>",$line);
        $line=str_replace("for server  failed","&nbsp;",$line);
        $line=str_replace("&nbsp; &nbsp;,","",$line);
            $html[]="<tr>
				<td width=1% nowrap>$span$FTime</span></td>
				<td width=1% nowrap>$span$pid</span></td>
				<td>$span$line</span></td>
				</tr>";
		
	}
	
	$html[]="</tbody></table>";
	$html[]="<div><i>".@file_get_contents(PROGRESS_DIR."/hacluster.syslog.query")."</i></div>";
	echo @implode("\n", $html);
	
	
	
}
