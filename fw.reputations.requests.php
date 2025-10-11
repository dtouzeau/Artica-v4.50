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
	
	$MAIN=$tpl->format_search_protocol($_GET["search"]);
    $rp=intval($MAIN["MAX"]);
    if($rp==0){$rp=250;}
    $search=trim($MAIN["TERM"]);
    if(strlen($search)<3){$search="NONE";}

	$html[]="
<table class=\"table table-hover\">
	<thead>
    	<tr>
        	<th colspan='3'>{date}</th>
        	<th>{service}</th>
        	<th>{ipaddr}</th>
        	<th>{rule}</th>
        </tr>
  	</thead>
	<tbody>
";
    $q=new lib_sqlite("/home/artica/SQLITE/firewall.db");
    $results=$q->QUERY_SQL("SELECT rulename,ID FROM rbl_reputations");

    foreach($results as $index=>$ligne) {
        $ID = $ligne["ID"];
        $rulename = $ligne["rulename"];
        $RULES[$ID]=$rulename;
    }


    $json=json_decode($sock->REST_API("/reputations/requests/$rp/$search"));
    $td1prc=$tpl->table_td1prc();
    $td1L=$tpl->table_td1prcLeft();
	foreach ($json->Events as $line){
		$line=trim($line);
        if(strlen($line)<20){continue;}
        preg_match("#^([A-Za-z]+)\s+([0-9]+)\s+([0-9:]+)\s+.*?\]:(.+)#",$line,$re);
        $Month=$tpl->MonthToInteger($re[1]);
        $Day=$re[2];
        $time=$re[3];
        $line=$re[4];
        $xTime=strtotime(date("Y")."-".$Month."-".$Day." ".$time);
        $time=$tpl->time_to_date($xTime,true);
        $ipaddr="";
        $Service="";
        $FOUND="";
        $RuleName="";
        $CACHE="<span class='label label-default'>{not_cached}</span>";

        if (strpos($line," HIT")>0){
            $CACHE="<span class='label label-success'>&nbsp;&nbsp;&nbsp;{cached}&nbsp;&nbsp;&nbsp;</span>";
        }

        if(preg_match("#Service\s+(.+?)\s+IP:([0-9\.]+)\s+([A-Z]+)(.+)#",$line,$re)){
            $ipaddr=$re[2];
            $Service=$re[1];
            $FOUND=$re[3];
            $line=$re[4];
        }
        $ico="<span class='label label-default'>{unknown}</span>";
        if($FOUND=="FOUND"){
            $ico="<span class='label label-danger'>&nbsp;&nbsp;&nbsp;{found}&nbsp;&nbsp;&nbsp;</span>";
            $modtools=new modesctools();
            $modtools->hostinfo($ipaddr,true);
            $flag="";
            if(strlen($modtools->flag)>3){
                $flag="<img src='img/".$modtools->flag."'>&nbsp;&nbsp;";
            }
            $ipaddr2=$tpl->td_href($ipaddr,"","Loadjs('fw.modsecurity.threats.php?zoom-ip-js=$ipaddr')");
            $hostname=$modtools->hostname;
            $ipaddr="$flag&nbsp;<i class='".ico_arrow_right."'></i>&nbsp;$ipaddr2&nbsp;$hostname";
        }
        if(preg_match("#rule:([0-9]+)#",$line,$re)){
            $RuleName=$RULES[$re[1]];
        }


        $html[]="<tr>
                <td $td1prc>$ico</td>
                <td $td1prc>$CACHE</td>
				<td $td1L>$time</td>
				<td $td1L>$Service</td>
				<td style='width:99%'>$ipaddr</td>
				<td $td1L>$RuleName</td>
				</tr>";
		
	}
	
	$html[]="</tbody></table>";
	echo $tpl->_ENGINE_parse_body(@implode("\n", $html));
	
	
	
}
