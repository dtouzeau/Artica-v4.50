<?php
// Post SP10 - 13/10/2026
// awc_*() (awc_table_style/awc_pager_js/awc_filter_js): helpers partages des
// tableaux d'entites, definis dans class.statistics_appliance.inc.
// L'include DOIT rester INCONDITIONNEL. Place dans le bloc
// if(!class_exists("sockets")){...}, il n'etait jamais evalue -- class.template-admin.inc
// charge deja class.sockets.inc -- d'ou un fatal "Call to undefined function
// awc_table_style()" des que la page rendait son tableau.
include_once(dirname(__FILE__)."/ressources/class.statistics_appliance.inc");
if(isset($_GET["VERBOSE"])){ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string','');ini_set('error_append_string','');}
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string','');ini_set('error_append_string','');}

include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once('ressources/class.ldap.inc');
include_once('ressources/class.users.menus.inc');
include_once('ressources/class.dansguardian.inc');
include_once('ressources/class.ActiveDirectory.inc');
include_once("ressources/class.ldap-extern.inc");
include_once(dirname(__FILE__)."/ressources/class.dnsfilter.inc");

if(isset($_GET["list"])){table();exit;}
if(isset($_GET["period-add-js"])){period_add_js();exit;}
if(isset($_GET["period-add"])){period_add();exit;}
if(isset($_GET["period-del-js"])){period_del_js();exit;}
if(isset($_GET["category-post-js"])){categoy_post();exit;}
if(isset($_GET["category-del-js"])){category_del();exit;}	
if(isset($_POST["RuleMatchTime"])){rule_time_main_save();exit;}
if(isset($_POST["TIMEID"])){period_save();exit;}



	//category-del-js=$categorykey&ID={$_GET["ID"]}&modeblk={$_GET["modeblk"]}&md=$md

page();

/**
 * Les valeurs que toutes les fonctions de cette page recoivent par l'URL.
 * Elles partaient BRUTES dans des requetes SQL, des URL et des chaines
 * JavaScript. Ce sont des entiers.
 */
function dnstime_ruleid():int{ return intval($_GET["ID"] ?? 0); }
function dnstime_timeid():int{ return intval($_GET["TIMEID"] ?? 0); }
/** L'id de ligne a retirer du DOM: un md5, donc hexadecimal. */
function dnstime_md():string{ return preg_replace('/[^a-f0-9]/','',(string)($_GET["md"] ?? "")); }
/** Deserialisation sure: pas d'instanciation de classe, retour toujours tableau. */
function dnstime_unserialize($raw):array{
	$v=@unserialize((string)$raw,array("allowed_classes"=>false));
	return is_array($v)?$v:array();
}

function period_add_js(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	// $_GET["title"] partait BRUT dans le titre de la modale.
	// Pentest 2026-11-04 : title etait recopie sans encodage dans la page (XSS).
	// Le titre est decode une fois (html_entity_decode) par javascript_parse_text puis rendu en HTML.
	$title=req_html(req_html((string)($_GET["title"] ?? "")));
	$tpl->js_dialog2("{period} $title", "$page?period-add=yes&ID=".dnstime_ruleid()."&TIMEID=".dnstime_timeid());
	
	
}
function period_del_js(){
	header("content-type: application/x-javascript");
	// $ID et $TIMEID partaient BRUTS dans le SELECT et dans l'UPDATE.
	$ID=dnstime_ruleid();
	$TIMEID=dnstime_timeid();
	$md=dnstime_md();
	$tpl=new template_admin();
	$q=new lib_sqlite("/home/artica/SQLITE/dns.db");
	$ligne=array();
	if($ID>0){
		$ligne=$q->mysqli_fetch_array("SELECT TimeSpace FROM webfilter_rules WHERE ID=$ID");
		if(!is_array($ligne)){$ligne=array();}
	}else{
		$sock=new dnsfiltersocks();
		$ligne=dnstime_dnstime_unserialize($sock->GET_INFO("DansGuardianDefaultMainRule"));
	}

	$TimeSpace=dnstime_unserialize(base64_decode((string)($ligne["TimeSpace"] ?? ""),true));
	unset($TimeSpace["TIMES"][$TIMEID]);
	$TimeSpaceNew=base64_encode(serialize($TimeSpace));

	if($ID==0){
		$ligne["TimeSpace"]=$TimeSpaceNew;
		$sock=new dnsfiltersocks();
		$sock->SET_INFO("DansGuardianDefaultMainRule",serialize($ligne));
	}else{
		$q->QUERY_SQL_PARAMS("UPDATE webfilter_rules SET TimeSpace=? WHERE ID=$ID",array($TimeSpaceNew));
		if(!$q->ok){$tpl->js_error($q->mysql_error);return;}
	}
	admin_tracks("DNS filtering rule $ID: removed period $TIMEID");
	if($md!==""){echo "$('#{$md}').remove();\n";}
	echo "LoadAjax('table-loader-dnsfilterd-rules','fw.dns.filterd.rules.php?table=yes')";
}


function category_del(){
	header("content-type: application/x-javascript");
	// Ces trois valeurs partaient BRUTES dans un DELETE.
	$category=intval($_GET["category-del-js"] ?? 0);
	$ID=dnstime_ruleid();
	$modeblk=intval($_GET["modeblk"] ?? 0);
	$md=dnstime_md();
	$tpl=new template_admin();
	$q=new lib_sqlite("/home/artica/SQLITE/dns.db");
	$q->QUERY_SQL("DELETE FROM webfilter_blks WHERE category='$category' AND modeblk='$modeblk' AND webfilter_id='$ID'");
	if(!$q->ok){$tpl->js_error($q->mysql_error);return;}
	if($md!==""){echo "$('#{$md}').remove();\n";}
	echo "LoadAjax('table-loader-dnsfilterd-rules','fw.dns.filterd.rules.php?table=yes')";
}

function categoy_post(){
	header("content-type: application/x-javascript");
	// Memes injections, et une syntaxe MySQL sur une base SQLite: "INSERT IGNORE"
	// n'existe pas en SQLite, qui ecrit "INSERT OR IGNORE" -- ce gestionnaire
	// echouait donc systematiquement. La page soeur
	// (fw.dns.filterd.rules.categories.php) porte deja la bonne forme.
	$category=intval($_GET["category-post-js"] ?? 0);
	$ID=dnstime_ruleid();
	$modeblk=intval($_GET["modeblk"] ?? 0);
	$md=dnstime_md();
	$tpl=new template_admin();
	$q=new lib_sqlite("/home/artica/SQLITE/dns.db");
	$ligne=$q->mysqli_fetch_array("SELECT ID FROM webfilter_blks WHERE category='$category' AND modeblk='$modeblk' AND webfilter_id='$ID'");
	if(!is_array($ligne)){$ligne=array();}
	if(intval($ligne["ID"] ?? 0)>0){return;}

	$q->QUERY_SQL("INSERT OR IGNORE INTO webfilter_blks (webfilter_id,category,modeblk) VALUES ('$ID','$category','$modeblk')");
	if(!$q->ok){$tpl->js_error($q->mysql_error);return;}
	admin_tracks("DNS filtering rule $ID: added category $category (modeblk=$modeblk)");

	if($md!==""){echo "$('#{$md}').remove();\n";}
	echo "RefreshUfdbCategoriesList();\n";
	echo "LoadAjax('table-loader-dnsfilterd-rules','fw.dns.filterd.rules.php?table=yes')";
	
	
}

function page(){
	$page=CurrentPageName();
	$page=CurrentPageName();
	$tpl=new template_admin();
	
	$explain=$tpl->_ENGINE_parse_body("<div class='alert alert-info'>{ufdbguardTimeSpaceExplain}</div>");
	
	
	// $_GET["ID"] partait BRUT dans cette chaine JavaScript.
	$ID=dnstime_ruleid();
	echo "<div style='margin-top:10px'></div>$explain<div id='ufdb-time-list' style='margin-top:20px'></div>
	<script>
		function RefreshUfdbTimeList(){
			LoadAjax('ufdb-time-list','$page?list=yes&ID=$ID');
		}
		RefreshUfdbTimeList();
	</script>	
	";
	
	
}

function period_add(){
	include_once('ressources/class.cron.inc');
	// Pentest 2026-11-04 : ID et TIMEID etaient recopies sans encodage dans la page (XSS).
	$ID=dnstime_ruleid();
	$TIMEID=dnstime_timeid();
	$tpl=new template_admin();
	$page=CurrentPageName();
	$buttonname="{apply}";
	$close=null;
	
	if($ID>0){
		$q=new lib_sqlite("/home/artica/SQLITE/dns.db");		
		$sql="SELECT TimeSpace FROM webfilter_rules WHERE ID=$ID";
		$ligne=$q->mysqli_fetch_array($sql);
	}
	
	if($ID==0){
		$sock=new dnsfiltersocks();
		$ligne=dnstime_unserialize($sock->GET_INFO("DansGuardianDefaultMainRule"));		
	}
	
	$TimeSpace=dnstime_unserialize(base64_decode((string)($ligne["TimeSpace"] ?? ""),true));
	$days=array("m"=>"Monday","t"=>"Tuesday","w"=>"Wednesday","h"=>"Thursday","f"=>"Friday","a"=>"Saturday","s"=>"Sunday");
	$cron=new cron_macros();
	$buttonname="{apply}";
	if($TIMEID==-1){
		$close="dialogInstance2.close();";
		$buttonname="{add}";}
	$Config=$TimeSpace["TIMES"][$TIMEID];
	// each() a ete SUPPRIME de PHP 8.0: cette boucle etait une erreur fatale sur
	// la moitie du parc.
	if(!is_array($Config)){$Config=array();}
	if(!isset($Config["DAYS"]) || !is_array($Config["DAYS"])){$Config["DAYS"]=array();}
	foreach ($days as $num=>$val){
		$form[]=$tpl->field_checkbox("day_{$num}","{{$val}}",intval($Config["DAYS"][$num] ?? 0));
	}
	
	
	$TT1=intval($Config["BEGINH"]).":".intval($Config["BEGINM"]);
	$TT2=intval($Config["ENDH"]).":".intval($Config["ENDM"]);
	
	$form[]=$tpl->field_hidden("ID", $ID);
	$form[]=$tpl->field_hidden("TIMEID", $TIMEID);
	
	
	
	$form[]=$tpl->field_clock("BEGINH", "{hourBegin}", $TT1);
	$form[]=$tpl->field_clock("ENDH", "{hourEnd}", $TT2);

	echo $tpl->form_outside("{set_the_period}", @implode("\n", $form),null,$buttonname,"RefreshUfdbTimeList();LoadAjax('table-loader-dnsfilterd-rules','fw.dns.filterd.rules.php?table=yes')$close","AsDnsAdministrator",true);
	return;

}
function table(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$q=new lib_sqlite("/home/artica/SQLITE/dns.db");
	$daysARR=array("m"=>"Monday","t"=>"Tuesday","w"=>"Wednesday","h"=>"Thursday","f"=>"Friday","a"=>"Saturday","s"=>"Sunday");
	// $ID partait BRUT dans trois WHERE de cette fonction.
	$ID=dnstime_ruleid();

	$ligne=array();
	if($ID==0){
		$sock=new dnsfiltersocks();
		$ligne=dnstime_dnstime_unserialize($sock->GET_INFO("DansGuardianDefaultMainRule"));
	}else{
		$ligne=$q->mysqli_fetch_array("SELECT TimeSpace FROM webfilter_rules WHERE ID=$ID");
		if(!is_array($ligne)){$ligne=array();}
	}

	$rule=$tpl->_ENGINE_parse_body("{rule}");
	$RuleBH=array("inside"=>"{inside_time}","outside"=>"{outside_time}","none"=>"{disabled}");
	$TimeSpace=dnstime_unserialize(base64_decode((string)($ligne["TimeSpace"] ?? ""),true));
	if(!isset($TimeSpace["RuleMatchTime"]) || $TimeSpace["RuleMatchTime"]==null){$TimeSpace["RuleMatchTime"]="none";}
	if(!isset($TimeSpace["RuleAlternate"]) || $TimeSpace["RuleAlternate"]==null){$TimeSpace["RuleAlternate"]="none";}

	$RULESS=array();
	$RULESS["none"]="{none}";
	$RULESS[0]="{default}";
	// lib_sqlite::QUERY_SQL() rend un TABLEAU: le passer a mysqli_fetch_array()
	// est une TypeError sous PHP 8 et rendait false sous PHP 7. La liste des
	// regles alternatives etait donc TOUJOURS vide -- on ne pouvait choisir que
	// "aucune" ou "par defaut".
	$results=$q->QUERY_SQL("SELECT ID,enabled,groupmode,groupname FROM webfilter_rules WHERE enabled=1 ORDER BY groupname");
	if(!$q->ok){
		echo $tpl->_ENGINE_parse_body($tpl->div_error(htmlspecialchars((string)$q->mysql_error)));
		return;
	}
	if(!is_array($results)){$results=array();}
	foreach($results as $r){
		if(intval($r["ID"])==$ID){continue;}
		$RULESS[$r["ID"]]=$r["groupname"];
	}
	$form[]=$tpl->field_hidden("ID", $ID);
	$form[]=$tpl->field_array_hash($RuleBH, "RuleMatchTime","{match}", $TimeSpace["RuleMatchTime"]);
	$form[]=$tpl->field_array_hash($RULESS, "RuleAlternate","{alternate_rule}", $TimeSpace["RuleAlternate"]);
	// form_outside() n'analyse QUE le libelle de son bouton: sans cet appel, le
	// titre et les libelles de champ s'affichaient en {tokens} litteraux.
	$html[]=$tpl->_ENGINE_parse_body(
		$tpl->form_outside("{behavior}", @implode("\n", $form),null,"{apply}",
			"LoadAjax('table-loader-dnsfilterd-rules','fw.dns.filterd.rules.php?table=yes')","AsDnsAdministrator",true)
	);

	$html[]="
			<div class=\"btn-group\" data-toggle=\"buttons\">
			<label class=\"btn btn btn-primary\" OnClick=\"Loadjs('$page?period-add-js=yes&ID=$ID&TIMEID=-1')\"><i class='fa fa-plus'></i> {add_a_period} </label>
			</div>";
	$html[]="<div class='awc-wrap'><div class='awc-card'><div class='awc-scroll'><table class='awc-tbl awc-tbl-compact'>";
	$html[]="<thead><tr>";
	$html[]="<th style='width:1%'>ID</th>";
	$html[]="<th>$rule</th>";
	$html[]="<th style='text-align:right'>{actions}</th>";
	$html[]="</tr></thead><tbody id='dnstime-rows'>";

	// $TimeSpace est deja charge plus haut: il etait relu ICI par deux requetes
	// et deux unserialize supplementaires, pour la meme valeur.
	$TIMES=(isset($TimeSpace["TIMES"]) && is_array($TimeSpace["TIMES"]))?$TimeSpace["TIMES"]:array();

	$rows=array();
	// each() a ete SUPPRIME de PHP 8.0: ces deux boucles etaient des erreurs
	// fatales sur la moitie du parc -- ce tableau ne s'affichait tout
	// simplement pas.
	foreach ($TIMES as $TIMEID=>$array){
		if(!is_array($array)){continue;}
		$md=md5(serialize($array).$TIMEID);
		$dd=array();
		if(isset($array["DAYS"]) && is_array($array["DAYS"])){
			foreach ($array["DAYS"] as $day=>$val){
				if($val==1 && isset($daysARR[$day])){$dd[]="{{$daysARR[$day]}}";}
			}
		}
		// $daysText etait reutilise d'un tour a l'autre quand DAYS n'etait pas
		// un tableau: la ligne heritait des jours de la precedente.
		$daysText=@implode(", ", $dd);

		foreach(array("BEGINH","BEGINM","ENDH","ENDM") as $k){
			$array[$k]=(string)($array[$k] ?? "0");
			if(strlen($array[$k])==1){$array[$k]="0".$array[$k];}
		}
		$daysText=$daysText." {from} {$array["BEGINH"]}:{$array["BEGINM"]} {to} {$array["ENDH"]}:{$array["ENDM"]}";

		$textfinal=$tpl->javascript_parse_text("{each} $daysText");
		$textfinal_enc=urlencode($textfinal);
		$js="Loadjs('$page?period-add-js=yes&ID=$ID&TIMEID=".intval($TIMEID)."&title=$textfinal_enc')";
		$js_delete="Loadjs('$page?period-del-js=yes&ID=$ID&TIMEID=".intval($TIMEID)."&md=$md')";

		$rows[]="<tr id='$md'>"
			."<td style='width:1%' nowrap><div class='awc-ident'>"
			."<span class='awc-ico'><i class='fas fa-clock'></i></span>"
			."<span class='awc-name'>".htmlspecialchars((string)$TIMEID)."</span></div></td>"
			."<td>".$tpl->td_href(htmlspecialchars($textfinal),"{click_to_edit}",$js)."</td>"
			."<td><div class='awc-acts'>".$tpl->icon_delete($js_delete,"AsDnsAdministrator")."</div></td>"
			."</tr>";
	}
	$c=count($rows);
	if($c==0){
		$html[]="<tr><td colspan='3'><div class='awc-empty'>{no_data}</div></td></tr>";
	}else{
		$html[]=@implode("\n",$rows);
	}
	// Le <tfoot> portait un pager footable dont l'init ciblait
	// #table-all-webftimes, un id qu'aucun <table> ne porte -- et dont le bloc
	// <script> n'etait meme jamais ferme (il manquait le </script>).
	$html[]="</tbody></table></div>";
	$html[]="<div class='awc-foot'>";
	$html[]="<span>{showing} $c {items}</span>";
	$html[]="<span class='awc-pg' id='dnstime-pager'></span>";
	$html[]="</div></div></div>";

	$script="<script>NoSpinner();\n".$tpl->ICON_SCRIPTS_COMPILE()."</script>";
	echo awc_table_style()
		.$tpl->_ENGINE_parse_body(@implode("\n", $html))
		.$script
		.awc_pager_js(50,"dnstime-rows","dnstime-pager");
}

function rule_time_main_save(){
	// Pentest 2026-11-04 : ID etait concatene dans la requete.
	$ID=intval($_POST["ID"] ?? 0);
	$tpl=new templates();
	$page=CurrentPageName();

	if($ID==0){
		$sock=new dnsfiltersocks();
		$ligne=dnstime_unserialize($sock->GET_INFO("DansGuardianDefaultMainRule"));
		$TimeSpace=dnstime_unserialize(base64_decode((string)($ligne["TimeSpace"] ?? ""),true));
		$TimeSpace["RuleMatchTime"]=$_POST["RuleMatchTime"];
		$TimeSpace["RuleAlternate"]=$_POST["RuleAlternate"];
		$TimeSpaceNew=base64_encode(serialize($TimeSpace));
		$ligne["TimeSpace"]=$TimeSpaceNew;
		$sock->SET_INFO("DansGuardianDefaultMainRule", serialize($ligne));
		return;
	}


	$q=new lib_sqlite("/home/artica/SQLITE/dns.db");
	$sql="SELECT TimeSpace FROM webfilter_rules WHERE ID=$ID";
	$ligne=$q->mysqli_fetch_array($sql);
	$TimeSpace=dnstime_unserialize(base64_decode((string)($ligne["TimeSpace"] ?? ""),true));
	$TimeSpace["RuleMatchTime"]=$_POST["RuleMatchTime"];
	$TimeSpace["RuleAlternate"]=$_POST["RuleAlternate"];
	$TimeSpaceNew=base64_encode(serialize($TimeSpace));
	$sql="UPDATE webfilter_rules SET TimeSpace='$TimeSpaceNew' WHERE ID=$ID";
	$q->QUERY_SQL($sql);
	if(!$q->ok){echo $q->mysql_error;return;}
	$sock=new dnsfiltersocks();
	
}
function period_save(){
	include_once('ressources/class.cron.inc');
	// Pentest 2026-11-04 : ID etait concatene dans la requete.
	$ID=intval($_POST["ID"] ?? 0);
	$TIMEID=intval($_POST["TIMEID"] ?? 0);
	$tpl=new template_admin();
	$page=CurrentPageName();

	if($ID==0){
		$sock=new dnsfiltersocks();
		$ligne=dnstime_unserialize($sock->GET_INFO("DansGuardianDefaultMainRule"));
	}
	if($ID>0){
		$q=new lib_sqlite("/home/artica/SQLITE/dns.db");
		$sql="SELECT TimeSpace FROM webfilter_rules WHERE ID=$ID";
		$ligne=$q->mysqli_fetch_array($sql);
	}
	
	$pp=explode(":",$_POST["ENDH"]);
	$_POST["ENDH"]=$pp[0];
	$_POST["ENDM"]=$pp[1];
	
	
	$pp=explode(":",$_POST["BEGINH"]);
	$_POST["BEGINH"]=$pp[0];
	$_POST["BEGINM"]=$pp[1];

	

	$TimeSpace=dnstime_unserialize(base64_decode((string)($ligne["TimeSpace"] ?? ""),true));
	$Config["ENDH"]=$_POST["ENDH"];
	$Config["ENDM"]=$_POST["ENDM"];
	$Config["BEGINH"]=$_POST["BEGINH"];
	$Config["BEGINM"]=$_POST["BEGINM"];

    foreach ($_POST as $index=>$value){
		if(preg_match("#day_([a-z])#", $index,$re)){
			$Config["DAYS"][$re[1]]=$value;
		}
	}


	if($TIMEID==-1){
		$TimeSpace["TIMES"][]=$Config;
	}else{
		$TimeSpace["TIMES"][$TIMEID]=$Config;
	}



	if($ID==0){
		$TimeSpaceNew=base64_encode(serialize($TimeSpace));
		$ligne["TimeSpace"]=$TimeSpaceNew;
		$sock=new dnsfiltersocks();
		$sock->SET_INFO("DansGuardianDefaultMainRule",serialize($ligne));
		return;
	}

	$TimeSpaceNew=base64_encode(serialize($TimeSpace));
	$sql="UPDATE webfilter_rules SET TimeSpace='$TimeSpaceNew' WHERE ID=$ID";
	$q->QUERY_SQL($sql);
	if(!$q->ok){echo $q->mysql_error;return;}


}