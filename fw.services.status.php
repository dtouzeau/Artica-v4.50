<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");
include_once("/usr/share/artica-postfix/ressources/class.monit.xml.inc");



$GLOBALS["CLASS_SOCKETS"]=new sockets();
if(isset($_GET["popup"])){popup();exit;}
if(isset($_GET["status"])){status();exit;}
if(isset($_GET["monitor"])){monitor();exit;}
if(isset($_GET["restart"])){restart();exit;}

js();

function js(){
	
	$page=CurrentPageName();
	$tpl=new template_admin();
	$tpl->js_dialog("{services_status}", "$page?popup=yes");
	
	
}

function monitor(){
	$page=CurrentPageName();
	$APP=$_GET["monitor"];
	$sock=new sockets();
	$sock->getFrameWork("monit.php?monitor=$APP");
	echo "LoadAjaxSilent('monit-status','$page?status=yes');";
}
function restart(){
	$page=CurrentPageName();
	$APP=$_GET["restart"];
	$sock=new sockets();
	$sock->getFrameWork("monit.php?restart-app=$APP");
	echo "LoadAjaxSilent('monit-status','$page?status=yes');";
}

function popup(){
	$sock=new sockets();
	$sock->getFrameWork("monit.php?chock-status=yes");
	$page=CurrentPageName();
	$html="<div id='monit-status'></div>
	<script>
		LoadAjaxSilent('monit-status','$page?status=yes');
	</script>		
			
	";
	echo $html;
	
	
	
}


function status(){
	$tpl=new template_admin();
	$page=CurrentPageName();
	$services=$tpl->_ENGINE_parse_body("{services}");
	$status=$tpl->_ENGINE_parse_body("{status}");
	$uptime=$tpl->_ENGINE_parse_body("{uptime}");
	$children=$tpl->_ENGINE_parse_body("{children}");
	$memory=$tpl->_ENGINE_parse_body("{memory}");
	$cpu=$tpl->_ENGINE_parse_body("{cpu}");
	$title=$tpl->javascript_parse_text("{APP_MONIT}");
	$monitored=$tpl->javascript_parse_text("{monitored}");
	$initializing=$tpl->javascript_parse_text("{initializing}");
	$stopped=$tpl->_ENGINE_parse_body("{stopped}");

	$data=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/system/monit/jsonstatus"));

	if(strlen($data->Info)<5){
		echo $tpl->div_error("{NO_DATA_COME_BACK_LATER}");
		return false;
	}

	$Info=json_decode($data->Info);


	if(!property_exists($Info,"Server")){
		echo $tpl->div_error("{NO_DATA_COME_BACK_LATER}");
		return false;
	}

	$Uptime=$Info->Server->Uptime;
	$UptimeText=distanceOfTimeInWords(time()-$Uptime,time());
	$html[]=$tpl->_ENGINE_parse_body("<H2>$title v{$Info->Server->Version} <small>({running_since} $UptimeText</small></H2>");
	$html[]=$tpl->_ENGINE_parse_body("
			<table id='table-firewall-rules' class=\"footable table table-stripped\" data-page-size=\"100\" data-paging=\"true\">");
	$html[]="<thead>";
	$html[]="<tr>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text'>$services</th>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text'>$status</th>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text'>$uptime</th>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text'>$children</th>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text'>$memory</th>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text'>$cpu</th>";
	$html[]="<th data-sortable=true class='text-capitalize center' data-type='text'>ACTION</center></th>";
	$html[]="</tr>";
	$html[]="</thead>";
	$html[]="<tbody>";
	

	$TRCLASS=null;
	$users=new usersMenus();


	foreach ($Info->Services as $index=>$class){
		//var_dump($class);

		$product=$class->Name;
		$uptime=$class->Uptime;

		if(trim($product)==null){continue;}
		if(preg_match("#[a-z]+#",$product)){continue;}
		if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}

		$label_text=$tpl->icon_nothing();
		$text_class=null;
		$PRODUCT_NAME=$product;
		$product=$tpl->_ENGINE_parse_body("{{$product}}");
		$suptime=intval(floor($uptime));
		$days = floor($suptime/60/60/24);
		$hours = intval(floor($suptime/60/60) %24);
		$mins = intval( floor($suptime/60) %60);
		$uptime_text=null;
		if($days>0){
			$uptime_text=$days.'d ';
		}
		if($hours>0){
			$uptime_text=$uptime_text.$hours.'h ';
		}
		if($mins>0){
			$uptime_text=$uptime_text.$mins.'m';
		}
		$children =$class->Children;

		$cpu=round($class->CPU->Percent,2)."%";
		$mem=FormatBytes($class->Memory->KilobyteTotal);


		$icons=null;
		$label="danger";
		$monitor=$class->Monitor;



		if($monitor==2){
			$label="warning";
			$label_text=$initializing;
		}
		if($monitor==1){
			$label="primary";
			$label_text=$monitored;
			$icons="<a href=\"javascript:blur();\" OnClick=\"Loadjs('$page?restart=$PRODUCT_NAME')\">
			<i class='fal fa-sync-alt'></i></a>";
		}
		
		if($monitor==0){
			$label_text=$stopped;
			$label="danger";
			$icons="<a href=\"javascript:blur();\" OnClick=\"Loadjs('$page?monitor=$PRODUCT_NAME')\">
			<i class='fa text-danger fa-play'></i></a>";
		}
		if(!$users->AsDebianSystem){
			$icons=$tpl->icon_nothing();
		}

		$html[]="<tr class='$TRCLASS'>";
		$html[]="<td class=\"$text_class\">$product</td>";
		$html[]="<td class=\"$text_class\"><span class='label label-$label'>$label_text</span></td>";
		$html[]="<td class=\"$text_class\">$uptime_text</td>";
		$html[]="<td class=\"$text_class'\">$children</td>";
		$html[]="<td class=\"$text_class'\">$mem</td>";
		$html[]="<td class=\"$text_class'\">$cpu</td>";
		$html[]="<td class=\"$text_class'\"><center>$icons</center></td>";
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
	$html[]="<div style='text-align:right;float-right;'>".$tpl->icon_refresh("LoadAjaxSilent('monit-status','$page?status=yes');")."</div>";
	$html[]="<script>
	NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS)."
	$(document).ready(function() { $('.footable').footable(
	{
	\"filtering\": {
	\"enabled\": true
	},
	\"sorting\": {
	\"enabled\": true
	}
	
	}
	
	
	); });
	</script>";
	$sock=new sockets();
	$sock->getFrameWork("monit.php?chock-status=yes");
	echo @implode("\n", $html);
}
?>

