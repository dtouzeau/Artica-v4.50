<?php
// POST SP10 20261023-12
// Post SP10 - 13/10/2026
// Service Pack 9 2026-10-10
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.milter.greylist.inc");
// awc_table_style() / awc_pager_js(): feuille de style et pager partages des tableaux d'entites
include_once(dirname(__FILE__)."/ressources/class.statistics_appliance.inc");
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
	$jsApply="Loadjs('fw.progress.php?".fw_progress_args_b64($prgress)."&mainid=progress-greylist-restart')";



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

			$rows=array();
			while ($ligne = pg_fetch_assoc($results)) {
				$rows[]=pfxgl_row($tpl,$ligne);
			}

			$html[]="<div class='awc-wrap'><div class='awc-card'><div class='awc-scroll'>";
			$html[]="<table class='awc-tbl'><thead><tr>";
			$html[]="<th>{sender}</th>";
			$html[]="<th class='center'>{status}</th>";
			$html[]="<th>{ipaddr}</th>";
			$html[]="<th>{time}</th>";
			$html[]="</tr></thead><tbody id='pfxgl-rows'>";
			if(count($rows)==0){
				$html[]="<tr><td colspan='4'><div class='awc-empty'>{no_data}</div></td></tr>";
			}else{
				$html[]=@implode("\n",$rows);
			}
			$shown=count($rows);
			$html[]="</tbody></table></div>";
			$html[]="<div class='awc-foot'>";
			$html[]="<span>{showing} $shown / $shown</span>";
			$html[]="<span class='awc-pg' id='pfxgl-pager'></span>";
			$html[]="</div></div></div>";

			// La requete SQL etait affichee sous le tableau: reste de mise au point.
			$html[]="<script>NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS)."</script>";

	echo awc_table_style()
		.$tpl->_ENGINE_parse_body(@implode("\n",$html))
		.awc_pager_js(50,"pfxgl-rows","pfxgl-pager");
}

/**
 * Un tuple de la base de liste grise: expediteur en identite, destinataire en
 * sous-titre. Adresses et IP viennent de la session SMTP -- donc d'un tiers -- et
 * etaient rendues brutes.
 */
function pfxgl_row($tpl,$ligne):string{
	$md=md5(serialize($ligne));
	$wl=intval($ligne["whitelisted"])==1;
	$mailto=(string)$ligne["mailto"];

	$h ="<tr id='$md'>";
	$h.="<td><div class='awc-ident'>";
	$h.="<span class='".($wl?"awc-ico":"awc-ico awc-ico-off")."'><i class='fas fa-hourglass-half'></i></span>";
	$h.="<span><span class='awc-name'>".htmlspecialchars((string)$ligne["mailfrom"])."</span>";
	if($mailto!==""){$h.="<div class='awc-meta'>&rarr; ".htmlspecialchars($mailto)."</div>";}
	$h.="</span></div></td>";
	$h.="<td class='center'>".($wl
		?"<span class='awc-badge awc-badge-on'>{whitelisted}</span>"
		:"<span class='awc-badge awc-badge-off'>{greylisted}</span>")."</td>";
	$h.="<td class='awc-mono'>".htmlspecialchars((string)$ligne["ip_addr"])."</td>";
	$h.="<td class='awc-mono'>".htmlspecialchars($tpl->time_to_date(strtotime((string)$ligne["stime"]),true))."</td>";
	$h.="</tr>";
	return $h;
}