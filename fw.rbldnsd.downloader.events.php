<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
include_once(dirname(__FILE__)."/ressources/class.sockets.inc");
include_once(dirname(__FILE__)."/ressources/class.modsectools.inc");
$GLOBALS["CLASS_SOCKETS"]=new sockets();


$users=new usersMenus();if(!$users->AsDnsAdministrator){exit();}
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(isset($_GET["search"])){search();exit;}
page();
function page(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$t=time();

    $html="
	<div class=\"row\"> 
		<div class='ibox-content'>
			<div class=\"input-group\">
	      		<input type=\"text\" class=\"form-control\" value=\"\" placeholder=\"{search}\" id='search-this-$t' OnKeyPress=\"javascript:Search$t(event);\">
	      		<span class=\"input-group-btn\">
	       		 <button style=\"text-transform: capitalize;\" class=\"btn btn-default\" type=\"button\" OnClick=\"javascript:ss$t();\">Go!</button>
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
	$.address.value('/rbl-requests');
	$.address.title('Artica: RBL requests');	
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
		$tpl=new template_admin("Artica: RBL Requests",$html);
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
    $q=new postgres_sql();
    $results = $q->QUERY_SQL("SELECT * FROM rbl_sources ORDER BY id ASC");
    $SOURCES=array();
    while ($ligne = pg_fetch_assoc($results)) {
        $description = $ligne["description"];
        $SOURCES[$ligne["id"]]=$description;
    }
	
	$MAIN=$tpl->format_search_protocol($_GET["search"]);
    $rp=intval($MAIN["MAX"]);
    if($rp==0){$rp=250;}
    $search=trim($MAIN["TERM"]);
    if(strlen($search)<3){$search="NONE";}

	$html[]="
<table class=\"table table-hover\">
	<thead>
    	<tr>
        	<th colspan='2'>{date}</th>
        	<th>{events}</th>
        </tr>
  	</thead>
	<tbody>
";
    $text["error"]="text-danger";
    $text["warn"]="text-warning font-bold";
    $text["info"]="text-muted";
    $json=json_decode($sock->REST_API("/rbldnsd-injecter/events/$rp/$search"));
    $td1prc=$tpl->table_td1prc();
    $td1prcL=$tpl->table_td1prcLeft();
	foreach ($json->Events as $sJson){
		$lineJS=json_decode($sJson);

        $level=$lineJS->level;
        $sdate=$tpl->time_to_date($lineJS->time,true);
        $ico="<label class='label label-default'>$level</label>";
        $line=$lineJS->message;
        if(isset($tooltips[$level])){
            $ico=$tooltips[$level];
        }
        if(isset($text[$level])){
            $textclass=$text[$level];
        }
        $line=str_replace("Feeds.go","",$line);

        if(preg_match("#^Add\s+([0-9]+)\s+.*?from source\s+(.+)#i",$line,$m)){
            $ico="<span class='label label-primary'>{update2}</span>";
            $line="{add} <strong>$m[1]</strong> {records} {from} <strong>$m[2]</strong>";
        }
        if(preg_match("#^Warning: Cannot find correct headers#i",$line,$m)){
            $ico="<span class='label label-warning'>{warning}</span>";
        }
        if(preg_match("#compiling ([0-9]+) records ([0-9]+) skipped#",$line,$m)){
            $ico="<span class='label label-primary'>{compiling}</span>";
            $line=" <strong>".$tpl->FormatNumber($m[1])."</strong> {records}";
            if (intval($m[2])>0){
                $line=$line ." {and}
             <strong".$tpl->FormatNumber($m[2])."</strong> {whitelist} {ipaddr}";
            }
        }
        if(preg_match("#\[COMPILE\]:#",$line,$m)){
            $ico="<span class='label label-primary'>{compiling}</span>";
            $line=str_replace("[COMPILE]:","",$line);
        }
        if(preg_match("#Compiled\s+([0-9]+)\s+#",$line,$m)){
            $ico="<span class='label label-primary'>{compiling}</span>";
            $line=str_replace($m[1],$tpl->FormatNumber($m[1]),$line);
        }
        if(preg_match("#rbldnsd version\s+([0-9\.]+)#",$line,$m)) {
            $ico = "<span class='label label-info'>{starting}</span>";
            $line = "{starting} DNSBL/RBL service v$m[1]";
        }
        if(preg_match("#zones reloaded, time#",$line,$m)) {
            $ico = "<span class='label label-info'>{reloading}</span>";
        }
        if(preg_match("#(listening on\s+|generic:|ip4set:)#",$line,$m)) {
            $ico = "<span class='label label-info'>{starting}</span>";
        }
        if(preg_match("#zone will not be serviced#",$line,$m)) {
            $ico = "<span class='label label-danger'>{reload} {error}</span>";
        }

        if(strpos(" $line","[DATA]:")>0){
            $ico = "<span class='label label-info'>{data}</span>";
            $line=str_replace("[DATA]:","",$line);
        }
        if(strpos(" $line","[POSTP]:")>0){
            $ico = "<span class='label label-primary'>post-process</span>";
            $line=str_replace("[POSTP]:","",$line);
        }


        if(strpos(" $line","[ERROR]:")>0){
            $ico = "<span class='label label-danger'>{error}</span>";
            $line=str_replace("[ERROR]:","",$line);
        }
        if(strpos(" $line","[STATS]:")>0){
            $ico = "<span class='label label-primary'>{statistics}</span>";
            $line=str_replace("[STATS]:","",$line);
        }



        if(preg_match("#stats for#",$line,$m)) {
            $ico = "<span class='label label-default'>{statistics}</span>";
        }

        if(preg_match("#\[STARTING\]:(.+)#",$line,$m)){
            $ico = "<span class='label label-info'>{starting}</span>";
            $line=$m[1];
        }


        if(preg_match("#Restarting#i",$line,$m)){
            $ico="<span class='label label-warning'>{restarting}</span>";
        }

        if(preg_match("#rbldnsd.DownloadSources sourceid\[([0-9]+)] Error (.+)#i",$line,$m)){
            $ico="<span class='label label-danger'>{error}</span>";
            $des="Source $m[1]";
            if(isset($SOURCES[$m[1]])){
                $des=$SOURCES[$m[1]];
            }
            $line=" HTTP error <strong>$m[2]</strong> From <strong>$des</strong>";

        }


		$html[]="<tr>
				<td $td1prc>$sdate</td>
				<td $td1prcL>$ico</td>
				<td>$line</td>
				</tr>";
		
	}
	
	$html[]="</tbody></table>";
	echo $tpl->_ENGINE_parse_body(@implode("\n", $html));
	
	
	
}
