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
	if(!isset($_SESSION["HACLUSTER_BACKENDS_SEARCH"])){$_SESSION["HACLUSTER_BACKENDS_SEARCH"]="50 events";}
	
	$html="

	<div class=\"row\"> 
		<div class='ibox-content'>
		<div class=\"input-group\">
      		<input type=\"text\" class=\"form-control\" value=\"{$_SESSION["HACLUSTER_BACKENDS_SEARCH"]}\" placeholder=\"{search}\" id='search-this-$t' OnKeyPress=\"Search$t(event);\">
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
	$.address.value('/hacluster-backend-events');
		function Search$t(e){";

    $html=$html."if(!checkEnter(e) ){return;}
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

function search():bool{
	$sock=new sockets();
	$tpl=new template_admin();
	$max=0;$date=null;$c=0;
	
	$MAIN=$tpl->format_search_protocol($_GET["search"]);
	
	$line=base64_encode(serialize($MAIN));
	$sock->getFrameWork("hacluster.php?syslog-backends=$line");
	$filename=PROGRESS_DIR."/hacluster-clients.syslog";
	$date_text=$tpl->_ENGINE_parse_body("{date}");
	$events=$tpl->_ENGINE_parse_body("{events}");
	$html[]="
<table class=\"table table-hover\">
	<thead>
    	<tr>
        	<th>$date_text</th>
        	<th>PID</th>
        	<th>{backend}</th>
        	<th>$events</th>
        </tr>
  	</thead>
	<tbody>
";
	
	$data=explode("\n",@file_get_contents($filename));
	if(count($data)>3){$_SESSION["HACLUSTER_BACKENDS_SEARCH"]=$_GET["search"];}
	krsort($data);
    $tpl=new template_admin();
	
	foreach ($data as $line){
		$line=trim($line);
		$ruleid=0;
		$rulename=null;
		$ACTION=null;
		$FF=false;
		if(!preg_match("#^([A-Za-z]+)\s+([0-9]+)\s+([0-9:]+)\s+(.+?)\s+hacluster-client\[([0-9]+)\]:(.+)#", $line,$re)){
			echo "<strong style='color:red'>$line</strong><br>";
			continue;}

		$xtime=strtotime($re[1] ." ".$re[2]." ".$re[3]);
		$FTime=date("Y-m-d H:i:s",$xtime);
		$curDate=date("Y-m-d");
		$FTime=trim(str_replace($curDate, "", $FTime));
		$hostname=$re[4];
		$pid=$re[5];
		$line=trim($re[6]);
        $label=null;
        $label_class="label-default";


		if(preg_match("#success#i", $line)){
            $line="<span class='text-success'>$line</span>";
            $label_class="label-primary";
        }
        $STAMP=false;
		if(preg_match("#(ALERT|CPU Usage high|DOWN|fatal|corrupted|copy_failed|unable_to_copy|missing|failed|Cannot contact|\[ERROR|Error)#i", $line)){
            $label_class="label-danger";
			$line="<span class='text-danger'>$line</span>";
            $STAMP=true;
		}
        if(!$STAMP) {
            if (preg_match("#(Emergency mode)#i", $line)) {
                $label_class="label-danger";
                $line = "<span class='text-danger'>$line</span>";
            }
        }
        if(strpos("  $line","[WARNING]")>0){
            $label_class="label-warning";
            $line=str_replace("[WARNING]","",$line);
        }


        if(!$STAMP) {
            if (preg_match("#(CPU Usage medium|triggered)#i", $line)) {
                $label_class="label-warning";
                $line = "<span class='text-warning'>$line</span>";
            }
        }
        if(!$STAMP){
            if (preg_match("#(Status to UP)#i", $line)) {
                $label_class="label-info";
                $line = "<span class='text-info'>$line</span>";
            }

        }

        if(preg_match("#HaClusterClient#",$line)){
            $label="<span class='label $label_class'>HaCluster Client</span>&nbsp;";
            $line=str_replace("HaClusterClient","",$line);

        }
        if(strpos($line,"ClusterClientHTTP.go")>0){
            $line=str_replace("ClusterClientHTTP.go","",$line);
            $line="<span class='label label-success'>Cluster</span>&nbsp;$line";
        }
        if(strpos("  $line","[CLUSTER_CLIENT]:")>0){
            $line=str_replace("[CLUSTER_CLIENT]:","",$line);
            $line="<span class='label label-success'>Cluster</span>&nbsp;$line";
        }


        if(strpos($line,"Proxy service:")>0){
            $line=str_replace("Proxy service:","",$line);
            $line="<span class='label $label_class'>{APP_SQUID}</span>&nbsp;$line";
        }
        if(strpos($line,"[exec.squid.php.storedir.php}]:")>0){
            $line=str_replace("[exec.squid.php.storedir.php}]:","",$line);
            $line="<span class='label $label_class'>{APP_SQUID}</span>&nbsp;$line";
        }


        $line=str_replace("[SERVICE]:","<span class='label label-info'>Service</span>&nbsp;",$line);
        $line=str_replace("[ERROR] ","",$line);
        $line=str_replace("SquidTools.","",$line);
        $line=str_replace("Operations.go","",$line);
        $line=str_replace("SquidTools.go","",$line);
        $line=str_replace("InetChecks.go","",$line);
        $line=str_replace("go[main.main.func2:","",$line);
        $line=str_replace("acluster/ClusterClient.","",$line);
        $line=str_replace("acluster/ClusterClientHTTP.","",$line);
        $line=str_replace("InetChecks.","",$line);
        $line=str_replace("main.","",$line);
        $line=str_replace("HaClusterTool.","",$line);
        $line=str_replace("CpuCheck.go","",$line);
        $line=str_replace("[CpuCheck.","[",$line);

        
        $line=str_replace("[HACLIENT]:","<span class='label $label_class'>HaCluster Client</span>&nbsp;",$line);
        $line=str_replace("[HaCluster Client]:","<span class='label $label_class'>HaCluster Client</span>&nbsp;",$line);
        $line=str_replace("[exec.go.shield.server.php}]:","<span class='label $label_class'>Go Web-filtering</span>&nbsp;",$line);
        $line=str_replace("HaClusterTool.go","",$line);
        $line=str_replace("ClusterClient.go","",$line);
        $line=str_replace("[exec.arpscan.php}]:","<span class='label $label_class'>ARP Scanner</span>&nbsp;",$line);
        $line=str_replace("[exec.lighttpd.php}]:","<span class='label $label_class'>Web Console</span>&nbsp;",$line);
		$line=$tpl->_ENGINE_parse_body($line);
		$html[]="<tr>
				<td width=1% nowrap>$FTime</td>
				<td width=1% nowrap>$pid</td>
				<td width=1% nowrap>{$hostname}</td>
				<td>$label$line</td>
				</tr>";
		
	}
	
	$html[]="</tbody></table>";
	$html[]="<div><i>".@file_get_contents(PROGRESS_DIR."/hacluster-clients.syslog.query")."</i></div>";
	echo $tpl->_ENGINE_parse_body($html);
	return true;
	
	
}
