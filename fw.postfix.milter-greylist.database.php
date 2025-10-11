<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.milter.greylist.inc");
if(isset($_GET["search"])){search();exit;}

table();

function table(){
	$viarbl=null;
	$page=CurrentPageName();
	$tpl=new template_admin();
	$t=time();
	$html[]="

	</div>
	<div class='ibox-content'>
	<div id='milter-greylits-database'></div>

	</div>
	</div>
	";

	
	$html[]=$tpl->search_block($page,"postgres","greylist_turples","milter-greylits-database","");



	echo $tpl->_ENGINE_parse_body($html);

}



function search(){
	$tpl=new template_admin();
	$page=CurrentPageName();
	$sock=new sockets();
	$q=new postgres_sql();
	$table="greylist_turples";
	$method=$_GET["method"];

	$ARRAY["PROGRESS_FILE"]="/usr/share/artica-postfix/ressources/logs/milter-greylist.scan.progress";
	$ARRAY["LOG_FILE"]="/usr/share/artica-postfix/ressources/logs/milter-greylist.scan.log";
	$ARRAY["CMD"]="milter-greylist.php?scandb=yes";
	$ARRAY["TITLE"]="{analyze_database}";
	$prgress=base64_encode(serialize($ARRAY));
	$jsApply="Loadjs('fw.progress.php?content=$prgress&mainid=progress-greylist-restart')";



	$html[]=$tpl->_ENGINE_parse_body("
			<div class=\"btn-group\" data-toggle=\"buttons\">
			<label class=\"btn btn btn-info\" OnClick=\"javascript:$jsApply\">
			<i class='fa fa-save'></i> {analyze_database} </label>
			</div>");

			$search=$_GET["search"];
			$querys=$tpl->query_pattern($search);
			$MAX=$querys["MAX"];
			if($MAX==0){$MAX=150;}

			$sql="SELECT * FROM $table {$querys["Q"]} LIMIT $MAX";
			//zmd5,ip_addr,mailfrom,mailto,stime,hostname,whitelisted


			$results = $q->QUERY_SQL($sql);
			if(!$q->ok){echo $tpl->FATAL_ERROR_SHOW_128("LINE ".__LINE__." $sql<br>$q->mysql_error");return;}

			if(pg_num_rows($results)==0){
				echo $tpl->_ENGINE_parse_body($html);
				echo $tpl->FATAL_ERROR_SHOW_128("{no_data}");
				return;
			}

			$TRCLASS=null;
			$html[]="<table id='table-miltergrey-list-db' class=\"footable table table-stripped\" data-page-size=\"100\" data-paging=\"true\" style='margin-top:0px'>";
			$html[]="<thead>";
			$html[]="<tr>";
			$html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{status}</th>";
			$html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{time}</th>";
			$html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{ipaddr}</center></th>";
			$html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{sender}</center></th>";
			$html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{recipients}</center></th>";
			$html[]="</tr>";
			$html[]="</thead>";
			$html[]="<tbody>";
			
			$STATUS[1]="<span class='label label-primary'>{whitelisted}</span>";
			$STATUS[0]="<span class='label'>{greylisted}</span>";

			while ($ligne = pg_fetch_assoc($results)) {
				if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}

				$md=md5(serialize($ligne));
				$id=$ligne["id"];
				$color="#000000";
				$zdate=$tpl->time_to_date(strtotime($ligne["stime"]),true);
				
				$ipaddr=$ligne["ip_addr"];
				$mailfrom=$ligne["mailfrom"];
				$mailto=$ligne["mailto"];
				$whitelisted=$ligne["whitelisted"];

				
				$html[]="<tr class='$TRCLASS' id='$md'>";
				$html[]="<td style='width:1%'>{$STATUS[$whitelisted]}</td>";
				$html[]="<td style='width:1%' nowrap>{$ligne["stime"]}</td>";
				$html[]="<td style='width:1%' nowrap>$ipaddr</td>";
				$html[]="<td>$mailfrom</td>";
				$html[]="<td style='width:1%' nowrap>$mailto</td>";
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
			$html[]="<small>$sql</small>
			<script>
			NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS)."
			$(document).ready(function() { $('#table-miltergrey-list-db').footable( { \"filtering\": { \"enabled\": true }, \"sorting\": { \"enabled\": true },\"paging\": { \"size\": {$GLOBALS["FOOTABLE_PSIZE"]} } } ); });
			</script>";

	echo $tpl->_ENGINE_parse_body($html);
}