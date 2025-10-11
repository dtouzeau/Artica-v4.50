<?php
	include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.main_cf.inc');
	if(isset($_GET["tabs"])){tabs();exit;}
	if(isset($_GET["stats"])){stats();exit;}
	if(isset($_GET["boots"])){boots();exit;}
js();


function js(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$tpl->js_dialog1("{uptime}:{statistics}", "$page?tabs=yes");
	
}

function stats(){
	$t=time();
	$path="/var/cache/munin/www/localdomain/localhost.localdomain";
	$f[]="uptime-day.png";
	$f[]="uptime-week.png";	
	$f[]="uptime-month.png";
	$f[]="uptime-year.png";
	$OUTPUT=false;
	foreach ($f as $image){
		if(is_file("$path/$image")){
			$OUTPUT=true;
			echo "<center style='margin-top:10px;padding:5px;background-color:#F0F0F0;border:1px solid #CCCCCC;border-radius: 5px 5px 5px 5px;'><img src='munin-images/$image?$t'></center>";
		}
		
		if(!$OUTPUT){
			$tpl=new template_admin();
			echo $tpl->_ENGINE_parse_body("<div class='alert alert-danger' style='margin-top:10px'>{error_no_generated_graphs}</div>");
		}	
	}
	
	
}

function tabs(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$sock=new sockets();

	$array["{statistics}"]="$page?stats=yes";
	$array["{boots}"]="$page?boots=yes";
	echo $tpl->tabs_default($array);

}


function boots(){
	$tpl=new template_admin();
	$page=CurrentPageName();
	$q=new mysql();
	$eth_sql=null;
	$token=null;
	$class=null;
	
	$js="OnClick=\"javascript:LoadAjax('table-loader','$page?table=yes&eth=');\"";
	if($_GET["eth"]==null){$class=" active";}
	$BTS2[]="<label class=\"btn btn-sm btn-white$class\" $js> <input type=\"radio\"  name=\"options\" id=\"ALL\"> {all} </label>";
	
	$q=new lib_sqlite("/home/artica/SQLITE/sys.db");
	
	
	
	
	$t=time();
	$add="Loadjs('$page?ruleid-js=0$token',true);";
	
	$html[]=$tpl->_ENGINE_parse_body("
			<table id='table-system-lstaboot' class=\"footable table table-stripped\" data-page-size=\"100\" data-paging=\"true\">");
	$html[]="<thead>";
	$html[]="<tr>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{date}</th>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text' nowrap>{infos}</th>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text' nowrap>{ttl}</th>";
	$html[]="</tr>";
	$html[]="</thead>";
	$html[]="<tbody>";
	
	$results=$q->QUERY_SQL("SELECT * FROM last_boot ORDER by zDate DESC");
	$TRCLASS=null;
	foreach ($results as $index=>$ligne){
		if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
		$square_class="text-navy";
		$text_class=null;
		$square="fa-check-square-o";
		$color="black";
		
		$time=$ligne["ztime"];
		$timedistantce=$tpl->_ENGINE_parse_body(distanceOfTimeInWords($time,time(),true));
		$time2=$ligne["ztime2"];
		$date2=$tpl->_ENGINE_parse_body(distanceOfTimeInWords($time,time(),true));
		if($time2>0){
			$date2=$tpl->_ENGINE_parse_body(distanceOfTimeInWords($time2,$time,true));
		}
			
		$date=$tpl->time_to_date($time,true);
		$subject=$ligne["subject"];
	
	
		 $html[]="<tr class='$TRCLASS'>";
		 $html[]="<td class=\"$text_class\" nowrap>$date ($timedistantce)</td>";
		 $html[]="<td class=\"$text_class\" style='width:1%' nowrap>$subject</a></td>";
		 $html[]="<td class=\"$text_class\" style='width:1%' nowrap>$time2</td>";
		 $html[]="</tr>";
	
	
		}
	
			$html[]="</tbody>";
			$html[]="<tfoot>";
	
			$html[]="<tr>";
			$html[]="<td colspan='3'>";
			$html[]="<ul class='pagination pull-right'></ul>";
			$html[]="</td>";
			$html[]="</tr>";
			$html[]="</tfoot>";
			$html[]="</table>";
			$html[]="
			<script>
			NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS)."
			$(document).ready(function() { $('#table-system-lstaboot').footable( { \"filtering\": { \"enabled\": true },\"sorting\": {\"enabled\": true } } ); });
	
			
	</script>";
	
			echo $tpl->_ENGINE_parse_body(@implode("\n", $html));	
	
	
}
