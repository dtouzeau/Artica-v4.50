<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
// Inclusion INCONDITIONNELLE: awc_table_style()/awc_pager_js()/awc_search_bar() vivent ici.
include_once(dirname(__FILE__)."/ressources/class.statistics_appliance.inc");
include_once(dirname(__FILE__)."/ressources/class.patch.tables.fw.inc");
include_once(dirname(__FILE__)."/ressources/class.postgres.inc");
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}

clean_xss_deep();
if(isset($_GET["popup"])){popup();exit;}
if(isset($_GET["search"])){table();exit;}
js();



function js(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$OPEN=true;
	$users=new usersMenus();
	if(!$users->IsDBAdmin()){$OPEN=false;}
	if(!$OPEN){$tpl->js_no_privileges();exit();}
	$table=$_GET["table"];
	$tpl->js_dialog6($table, "$page?popup=".urlencode($table),2000);
}

function popup(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$table=$_GET["popup"];
	$md=md5(time().$table);
	$t=time();

	// Meme barre que les tableaux awc (awc_search_bar); elle porte ici la REQUETE SQL
	// et non un filtre, d'ou la valeur initiale posee en JavaScript -- la barre ne
	// prend qu'un texte de substitution, pas de valeur.
	$html=array();
	$html[]=awc_search_bar("pgq-sql-$t","SELECT ...");
	$html[]="<div id='$md'></div>";

	// Valeur injectee dans un litteral JS: json_encode + HEX_TAG, pour que le nom de
	// table (issu de $_GET) ne puisse pas sortir de la chaine ni du <script>.
	$initial=json_encode("SELECT * FROM $table LIMIT 200",JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_QUOT);

	// Script concatene APRES le parsing: _ENGINE_parse_body lirait chaque bloc {...}
	// du JavaScript comme un jeton a traduire.
	$js="<script>
	(function(){
		var inp=document.getElementById('pgq-sql-$t');
		if(!inp){return;}
		inp.value=$initial;
		function ss$t(){
			var ss=encodeURIComponent(base64_encode(inp.value));
			LoadAjax('$md','$page?search='+ss);
		}
		inp.onkeypress=function(e){ if(checkEnter(e)){ ss$t(); } };
		var go=document.getElementById('pgq-sql-$t-go');
		if(go){ go.onclick=function(){ ss$t(); return false; }; }
		window.ss$t=ss$t;
		ss$t();
	})();
	</script>";

	echo $tpl->search_block_css().$tpl->_ENGINE_parse_body(@implode("\n", $html)).$js;
}

function table(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$sql=base64_decode($_GET["search"]);
	$q=new postgres_sql();
    admin_tracks("Launching PostreSQL query [$sql] using the SQL Browser");
	$results=$q->QUERY_SQL($sql);
	if(!$q->ok){echo $tpl->FATAL_ERROR_SHOW_128($q->mysql_error);return;}

	// En-tete: les noms de colonnes viennent de la requete, donc echappes.
	$head=array();
	$fieldTotal=0;
	while ($fieldTotal < pg_num_fields($results)){
		$head[]="<th>".htmlspecialchars(pg_field_name($results, $fieldTotal))."</th>";
		$fieldTotal++;
	}
	if($fieldTotal<1){$fieldTotal=1;}

	// Les valeurs sont echappees: elles sortent de la base et etaient jusqu'ici
	// injectees telles quelles dans la cellule.
	$rows=array();
	while ($row = pg_fetch_row($results)){
		$cells=array();
		foreach($row as $c_row){
			$cells[]="<td class='awc-mono'>".htmlspecialchars((string)$c_row)."</td>";
		}
		$rows[]="<tr>".@implode("",$cells)."</tr>";
	}
	pg_free_result($results);
	$total=count($rows);

	$html=array();
	$html[]="<div class='awc-wrap'><div class='awc-card'><div class='awc-scroll'>";
	$html[]="<table class='awc-tbl'><thead><tr>";
	$html[]=@implode("",$head);
	$html[]="</tr></thead><tbody id='pgq-rows'>";
	if($total==0){
		$html[]="<tr><td colspan='$fieldTotal'><div class='awc-empty'>{no_data}</div></td></tr>";
	}else{
		$html[]=@implode("\n",$rows);
	}
	$html[]="</tbody></table></div>";
	$html[]="<div class='awc-foot'>";
	$html[]="<span>{showing} $total / $total {records}</span>";
	$html[]="<span class='awc-pg' id='pgq-pager'></span>";
	$html[]="</div></div></div>";

	// Feuille et scripts concatenes AUTOUR du parsing: la regex #{(.+?)}# de
	// _ENGINE_parse_body lit chaque corps de regle CSS et chaque bloc JS comme un jeton.
	$foot="<script>NoSpinner();\n".$tpl->ICON_SCRIPTS_COMPILE()."</script>";
	echo awc_table_style()
		.$tpl->_ENGINE_parse_body(@implode("\n", $html))
		.awc_pager_js(50,"pgq-rows","pgq-pager")
		.$foot;

}
