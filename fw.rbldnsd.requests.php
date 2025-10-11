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
        	<th colspan='2'>{date}</th>
        	<th>{ipaddr}</th>
        	<th>{query}</th>
        	<th>{answer}</th>
        </tr>
  	</thead>
	<tbody>
";

    $json=json_decode($sock->REST_API("/rbldnsd/requests/$rp/$search"));
    $td1prc=$tpl->table_td1prc();
	foreach ($json->Events as $line){
		$line=trim($line);

        if(!preg_match("#^([0-9]+)\s+(.+?)\s+(.+?)\s+([A-Z]+)\s+IN:\s+([A-Z]+)\/([0-9]+)\/([0-9]+)#",$line,$re)){
            continue;
        }
        if(intval($re[1])==0){continue;}
        $time=$tpl->time_to_date($re[1],true);
        $ipaddr=$re[2];
        $query=trim($re[3]." ($re[4])");
        $answer=$re[5];
        $Found=intval($re[6]);
        $ms=$re[7];
        $ico="<span class='label label-default'>{unknown}</span>";
        if($Found==1){
            $ico="<span class='label label-primary'>{found}</span>";
        }

        if(preg_match("#^([0-9\.]+)\.([0-9\.]+)\.([0-9\.]+)\.([0-9\.]+)\.#",$query,$ri)){
            $ipaddr2="$ri[4].$ri[3].$ri[2].$ri[1]";
            $modtools=new modesctools();
            $modtools->hostinfo($ipaddr2,true);
            $flag="";
            if(strlen($modtools->flag)>3){
                $flag="<img src='img/".$modtools->flag."'>&nbsp;&nbsp;";
            }
            $ipaddr2=$tpl->td_href($ipaddr2,"","Loadjs('fw.modsecurity.threats.php?zoom-ip-js=$ipaddr2')");

            $query="$flag $query&nbsp;<i class='".ico_arrow_right."'></i>&nbsp;$ipaddr2";
        }

        if($answer=="REFUSED"){
            $ico="<span class='label label-danger'>{deny}</span>";
        }

        $html[]="<tr>
				<td $td1prc>$time</td>
				<td $td1prc>$ico</td>
				<td $td1prc>$ipaddr</td>
				<td nowrap>$query</td>
				<td $td1prc>$answer ({$ms}ms)</td>
				</tr>";
		
	}
	
	$html[]="</tbody></table>";
	echo $tpl->_ENGINE_parse_body(@implode("\n", $html));
	
	
	
}
