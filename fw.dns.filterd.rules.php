<?php
// POST SP10 20261023-12
// Post SP10 - 13/10/2026
// Service Pack 9 2026-10-10
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.system.network.inc");
include_once(dirname(__FILE__)."/ressources/class.dnsfilter.inc");
// awc_table_style() / awc_pager_js(): feuille de style et pager partages des tableaux d'entites
include_once(dirname(__FILE__)."/ressources/class.statistics_appliance.inc");

if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
// Sans exit;, page_single() rendait sa sortie PUIS l'execution continuait dans
// les dispatchs suivants et finissait dans page(): deux pages concatenees dans
// une seule reponse.
if(isset($_GET["single-page"])){page_single();exit;}
if(isset($_GET["SearchIpsources"])){sources_search();exit;}
if(isset($_GET["table"])){table();exit;}
if(isset($_POST["delete"])){source_item_delete();exit;}
if(isset($_POST["description"])){source_item_save();exit;}
if(isset($_POST["UfdbReloadBySchedule"])){save();exit;}
if(isset($_GET["source-add-js"])){source_add_js();exit;}
if(isset($_GET["delete-js"])){delete_js();exit;}
if(isset($_POST["delete-rule"])){delete_rule();exit;}
if(isset($_POST["rule-order"])){move_rule();exit;}
if(isset($_GET["sources-js"])){sources_js();exit;}
if(isset($_GET["sources-popup"])){sources_popup();exit;}
if(isset($_POST["sources"])){sources_save();exit;}
if(isset($_GET["sources-add-popup"])){source_add_popup();exit;}
if(isset($_GET["sources-item-js"])){source_item_js();exit;}
if(isset($_GET["sources-item-popup"])){source_item_popup();exit;}
if(isset($_GET["sources-item-delete-js"])){source_item_delete_js();exit;}
page();


/**
 * Le nom de la fonction de rafraichissement transmis dans l'URL.
 *
 * Il repart dans des OnClick et dans des $jsafter, sans echappement.
 * search_block n'engendre jamais autre chose que "ss<time>".
 * Renvoie "" quand il n'y a rien a appeler: "$function();" avec un nom vide
 * produit le litteral "();", une SyntaxError qui avorte tout le bloc.
 */
function dnsfr_function():string{
	return preg_replace('/[^A-Za-z0-9_]/','',(string)(isset($_GET["function"])?$_GET["function"]:""));
}
/** L'appel de rafraichissement, ou une chaine vide. */
function dnsfr_call():string{
	$fname=dnsfr_function();
	return ($fname==="")?"":"$fname();";
}

function delete_js(){
	$tpl=new template_admin();
	// $md repart dans un selecteur jQuery: c'est un md5.
	$md=preg_replace('/[^a-f0-9]/','',(string)($_GET["md"] ?? ""));
	$js=($md==="")?"":"$('#{$md}').remove();";
	// Le nom de la regle est affiche dans la boite de confirmation.
	$rule=htmlspecialchars((string)($_GET["rule"] ?? ""));
	$tpl->js_confirm_delete($rule, "delete-rule", intval($_GET["delete-js"]),$js);
	
}
function delete_rule(){
	$q=new lib_sqlite("/home/artica/SQLITE/dns.db");
	$ID=intval($_POST["delete-rule"]);
	$q->QUERY_SQL("DELETE FROM webfilter_blks WHERE webfilter_id='$ID'");
	if(!$q->ok){echo $q->mysql_error;return;}
	$q->QUERY_SQL("DELETE FROM webfilter_rules WHERE ID='$ID'");
	if(!$q->ok){echo $q->mysql_error;return;}
	$q->QUERY_SQL("DELETE FROM webfilter_ipsources WHERE ruleid='$ID'");
	if(!$q->ok){echo $q->mysql_error;return;}	
	$q->QUERY_SQL("DELETE FROM ufdb_page_rules WHERE webruleid='$ID'");

}

function sources_js(){
	$ID=intval($_GET["sources-js"]);
	$tpl=new template_admin();
	$page=CurrentPageName();
	$q=new lib_sqlite("/home/artica/SQLITE/dns.db");
	$sql="SELECT groupname FROM webfilter_rules WHERE ID=$ID";
	$ligne=$q->mysqli_fetch_array($sql);
	$rulename=$ligne["groupname"];
	$tpl->js_dialog($rulename. "{sources}", "$page?sources-popup=$ID");
}
function source_add_js(){
    $ID=intval($_GET["source-add-js"]);
    $tpl=new template_admin();
    $page=CurrentPageName();
    $q=new lib_sqlite("/home/artica/SQLITE/dns.db");
    $sql="SELECT groupname FROM webfilter_rules WHERE ID=$ID";
    $ligne=$q->mysqli_fetch_array($sql);
    $rulename=$ligne["groupname"];
    $function=dnsfr_function();
    $tpl->js_dialog2($rulename. " {sources} {new_item}", "$page?sources-add-popup=$ID&function=$function",650);
}

function source_item_delete_js(){
    $tpl=new template_admin();
    $page=CurrentPageName();

    $ipaddr=$_GET["sources-item-delete-js"];
    $ruleid=$_GET["ruleid"];
    $md=$_GET["md"];

    $value=base64_encode(serialize($_GET));

    $tpl->js_confirm_delete($ipaddr,"delete",$value,"$('#$md').remove();LoadAjax('table-loader-dnsfilterd-rules','$page?table=yes');");

}
function source_item_delete(){

    // allowed_classes=false (PHP 7.0+): sans cette option unserialize() instancie
    // toute classe nommee dans le parametre. Le retour est teste: false["..."]
    // est un avertissement PHP 8 suivi d'une valeur nulle.
    $raw=base64_decode((string)($_POST["delete"] ?? ""),true);
    $GET=($raw===false)?false:@unserialize($raw,array("allowed_classes"=>false));
    if(!is_array($GET)){$GET=array();}
    $ipaddr=(string)($GET["sources-item-delete-js"] ?? "");
    $ruleid=intval($GET["ruleid"] ?? 0);
    if($ipaddr==null){
        echo "IP Address is null!";
        return;
    }
    $q=new lib_sqlite("/home/artica/SQLITE/dns.db");
    // $ipaddr partait BRUT dans le WHERE.
    $ipaddrsql=$q->sqlite_escape_string2($ipaddr);
    $q->QUERY_SQL("DELETE FROM webfilter_ipsources WHERE ipaddr='$ipaddrsql' AND ruleid='$ruleid'");
    if(!$q->ok){echo "$q->mysql_error";}
}

function source_item_js(){

    $tpl=new template_admin();
    $page=CurrentPageName();

    $ipaddr=(string)($_GET["sources-item-js"] ?? "");
    // $ruleid partait BRUT dans le WHERE.
    $ruleid=intval($_GET["ruleid"] ?? 0);
    $function=dnsfr_function();
    $ipaddrenc=urlencode($ipaddr);

    $q=new lib_sqlite("/home/artica/SQLITE/dns.db");
    $ligne=$q->mysqli_fetch_array("SELECT groupname FROM webfilter_rules WHERE ID=$ruleid");
    if(!is_array($ligne)){$ligne=array();}
    // Nom de regle et adresse partaient BRUTS dans le titre de la modale.
    $rulename=htmlspecialchars((string)($ligne["groupname"] ?? ""));

    $tpl->js_dialog2($rulename." {sources} ".htmlspecialchars($ipaddr),
        "$page?sources-item-popup=$ipaddrenc&function=$function&ruleid=$ruleid",650);

}

function sources_popup(){
	$tpl=new template_admin();
	$page=CurrentPageName();
	$ruleid=intval($_GET["sources-popup"]);
	$q=new lib_sqlite("/home/artica/SQLITE/dns.db");

	if(!$q->FIELD_EXISTS("webfilter_ipsources","description")){
	    $q->QUERY_SQL("ALTER TABLE webfilter_ipsources ADD description TEXT");
    }
	
	$sql="SELECT groupname FROM webfilter_rules WHERE ID=$ruleid";
	$ligne=$q->mysqli_fetch_array($sql);
	$rulename=$ligne["groupname"];


	$html[]="<H2>$rulename</H2>";
	$html[]=$tpl->search_block($page,null,null,"webfilter-ipsources-$ruleid","&SearchIpsources=$ruleid");
	$html[]="<div id='webfilter-ipsources-$ruleid'></div>";
	echo $tpl->_ENGINE_parse_body($html);
	return;
}
function source_item_popup(){
    $tpl=new template_admin();
    $page=CurrentPageName();
    $ruleid=intval($_GET["ruleid"]);
    $ipaddr=(string)($_GET["sources-item-popup"] ?? "");
    $q=new lib_sqlite("/home/artica/SQLITE/dns.db");
    // $ipaddr partait BRUT dans le WHERE.
    $ipaddrsql=$q->sqlite_escape_string2($ipaddr);
    $ligne=$q->mysqli_fetch_array("SELECT description FROM webfilter_ipsources WHERE ruleid=$ruleid AND ipaddr='$ipaddrsql'");
    if(!is_array($ligne)){$ligne=array();}

    // Sans nom de fonction, "$function();" produisait "();" -- une SyntaxError
    // qui emportait aussi la fermeture de la modale et le rechargement.
    $after="dialogInstance2.close();LoadAjax('table-loader-dnsfilterd-rules','$page?table=yes');".dnsfr_call();

    $form[]=$tpl->field_hidden("ruleid", $ruleid);
    $form[]=$tpl->field_hidden("ipaddr", $ipaddr);
    $form[]=$tpl->field_text("description", "{description}", (string)($ligne["description"] ?? ""),true);
    echo $tpl->_ENGINE_parse_body(
        $tpl->form_outside(htmlspecialchars($ipaddr), $form,null,"{apply}",$after,"AsDnsAdministrator",true)
    );

}

function source_item_save(){
    $tpl=new template_admin();
    $tpl->CLEAN_POST();
    $ruleid=intval($_POST["ruleid"]);
    $ipaddr=(string)($_POST["ipaddr"] ?? "");
    $description=(string)($_POST["description"] ?? "");


    $q=new lib_sqlite("/home/artica/SQLITE/dns.db");
    // Seul $description etait echappe: $ipaddr et $ruleid partaient bruts dans
    // le meme UPDATE.
    $description=$q->sqlite_escape_string2($description);
    $ipaddrsql=$q->sqlite_escape_string2($ipaddr);
    $q->QUERY_SQL("UPDATE webfilter_ipsources SET description='$description' WHERE ipaddr='$ipaddrsql' AND ruleid='$ruleid'");
    if(!$q->ok){$tpl=new template_admin();$q->mysql_error=$tpl->javascript_parse_text($q->mysql_error);echo (new template_admin())->post_error("$q->mysql_error");}

}


function source_add_popup(){
    $tpl=new template_admin();
    $page=CurrentPageName();
    // Pentest 2026-11-04 : sources-add-popup etait concatene dans la requete.
    $ruleid=intval($_GET["sources-add-popup"]);
    $q=new lib_sqlite("/home/artica/SQLITE/dns.db");

    $sql="SELECT groupname FROM webfilter_rules WHERE ID=$ruleid";
    $ligne=$q->mysqli_fetch_array($sql);
    $rulename=$ligne["groupname"];
    $function=dnsfr_function();

    $form[]=$tpl->field_hidden("ruleid", $ruleid);
    $form[]=$tpl->field_textareacode("sources", null, null);
    echo $tpl->form_outside("$rulename {sources}", $form,"{DNSFILTERD_SOURCES_EXPLAIN}","{add}",
        "dialogInstance2.close();LoadAjax('table-loader-dnsfilterd-rules','$page?table=yes');$function();","AsDnsAdministrator",true);
}

function sources_search(){
    $tpl=new template_admin();
    $page=CurrentPageName();
    $ruleid=intval($_GET["SearchIpsources"]);
    $q=new lib_sqlite("/home/artica/SQLITE/dns.db");
    $function=dnsfr_function();
    $t=time();

    $html[]="
		<div class=\"btn-group\" data-toggle=\"buttons\" style='margin-top:10px'>
			<label class=\"btn btn btn-primary\" OnClick=\"Loadjs('$page?source-add-js=$ruleid&function=$function')\"><i class='fa fa-plus'></i> {new_item} </label> </label>
		</div>
		";




    // Le terme partait TEL QUEL dans deux LIKE: une simple quote suffisait a
    // detourner la requete.
    $search=trim((string)($_GET["search"] ?? ""));
    if($search===""){
        $results = $q->QUERY_SQL("SELECT * FROM webfilter_ipsources WHERE ruleid='$ruleid' ORDER BY ipaddr");
    }else{
        $like=$q->sqlite_escape_string2(str_replace("*","%",$search));
        $results = $q->QUERY_SQL("SELECT * FROM webfilter_ipsources WHERE (ruleid='$ruleid') AND
        ( (ipaddr LIKE '%$like%') OR (description LIKE '%$like%') ) ORDER BY ipaddr");
    }
    if(!is_array($results)){$results=array();}

    $rows=array();
    foreach ($results as $ligne){
        $rows[]=dnsfr_source_row($ligne,$ruleid,$function,$tpl,$page);
    }
    $c=count($rows);

    $html[]="<div class='awc-wrap' style='margin:0;padding:0'><div class='awc-card'><div class='awc-scroll'><table class='awc-tbl awc-tbl-compact'>";
    $html[]="<thead><tr>";
    $html[]="<th nowrap>{ipaddr}</th>";
    $html[]="<th>{description}</th>";
    $html[]="<th style='text-align:right'>{actions}</th>";
    $html[]="</tr></thead><tbody id='dnsfrsrc-rows'>";
    if($c==0){
        $html[]="<tr><td colspan='3'><div class='awc-empty'>{no_data}</div></td></tr>";
    }else{
        $html[]=@implode("\n",$rows);
    }
    // Le <tfoot> portait un pager footable jamais alimente: l'init ciblait
    // #table-<time>, un id qu'aucun <table> de cette page ne porte. Et son
    // bloc <script> n'etait JAMAIS FERME -- il manquait le </script>.
    $html[]="</tbody></table></div>";
    $html[]="<div class='awc-foot'>";
    $html[]="<span>{showing} $c {items}</span>";
    $html[]="<span class='awc-pg' id='dnsfrsrc-pager'></span>";
    $html[]="</div></div></div>";

    // Le <script> est concatene APRES l'analyse: la regex #{(.+?)}# de
    // _ENGINE_parse_body lisait la configuration JSON de footable comme des
    // {tokens}.
    $script="<script>NoSpinner();\n".$tpl->ICON_SCRIPTS_COMPILE()."</script>";

    echo awc_table_style()
        .$tpl->_ENGINE_parse_body(@implode("\n",$html))
        .$script
        .awc_pager_js(50,"dnsfrsrc-rows","dnsfrsrc-pager");
}

/**
 * Une source IP autorisee pour une regle.
 *
 * L'id md5 de la <tr> est conserve: la suppression le reutilise.
 * Adresse et description viennent de la base et partaient BRUTES dans le HTML.
 */
function dnsfr_source_row($ligne,$ruleid,$function,$tpl,$page):string{
    $description=(string)($ligne["description"] ?? "");
    $ipaddr=(string)($ligne["ipaddr"] ?? "");
    $ipaddrenc=urlencode($ipaddr);
    $md=md5(serialize($ligne));

    $h ="<tr id='$md'>";
    $h.="<td style='width:1%' nowrap><div class='awc-ident'>";
    $h.="<span class='awc-ico'><i class='fas fa-network-wired'></i></span>";
    $h.="<span class='awc-name'>"
       .$tpl->td_href(htmlspecialchars($ipaddr),"{click_to_edit}","Loadjs('$page?sources-item-js=$ipaddrenc&function=$function&ruleid=$ruleid')")
       ."</span></div></td>";
    $h.="<td>".($description!==""?htmlspecialchars($description):"<span class='awc-none'>&mdash;</span>")."</td>";
    $h.="<td><div class='awc-acts'>"
       .$tpl->icon_delete("Loadjs('$page?sources-item-delete-js=$ipaddrenc&ruleid=$ruleid&md=$md')","AsDnsAdministrator")
       ."</div></td>";
    $h.="</tr>";
    return $h;
}





function sources_save(){
	$tpl=new template_admin();
	$tpl->CLEAN_POST();
	$td=explode("\n",$_POST["sources"]);
	$ruleid=$_POST["ruleid"];
	$q=new lib_sqlite("/home/artica/SQLITE/dns.db");

	$ipClass=new IP();
	
	foreach ($td as $line){
        $description="Saved ".date("Y-m-d H:i:s");

        if(strpos($line,"#")>0){
            $zline=explode("#",$line);
            $line=$zline[0];
            unset($zline[0]);
            $description=@implode(" ",$zline);
        }

	    if(preg_match("#^(.+?)\s+(.+)#",$line,$re)){
            $line=$re[1];
	        $description=$re[2];
        }

		if(!$ipClass->isIPAddressOrRange($line)){continue;}
		// $line est valide par isIPAddressOrRange(), mais $description sort du
		// texte saisi et partait BRUTE dans l'INSERT.
		$descsql=$q->sqlite_escape_string2($description);
		$q->QUERY_SQL("INSERT INTO webfilter_ipsources (ruleid,ipaddr,description) VALUES (".intval($ruleid).",'$line','$descsql')");
		if(!$q->ok){echo $q->mysql_error;return;}
		
	}
	
}

function page_single(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $APP_DNSFILTERD_VERSION=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("APP_DNSFILTERD_VERSION");


    $html="
	<div class=\"row border-bottom white-bg dashboard-header\">
		<div class=\"col-sm-12\"><h1 class=ng-binding>{APP_DNSFILTERD} $APP_DNSFILTERD_VERSION </h1>
			<p>{filtering_rules}</p>
		</div>
	</div>
	<div class='row'>
	    <div id='dnsfilterd-progress' style=''></div>		
		<div id='progress-dnsfilter-restart'></div>
		<div class='ibox-content' style='min-height:600px'>
			<div id='table-dnsfilterd'>
			
			
	<div id='table-loader-dnsfilterd-rules'>
            </div>
		</div>
	</div>



	<script>
	LoadAjax('table-loader-dnsfilterd-rules','$page?table=yes');
	$.address.state('/');
	$.address.value('/dnsfilter-rules');
	$.address.title('Artica: DNS Filter Parameters');
	</script>";

    if(isset($_GET["main-page"])){
        $tpl=new template_admin("Artica: DNS Filter Rules",$html);
        echo $tpl->build_firewall();
        return;
    }


    echo $tpl->_ENGINE_parse_body($html);


}


function page(){
	$page=CurrentPageName();
	$tpl=new template_admin();

	
	$html="
	<div id='dnsfilterd-progress' style='margin-top:5px'></div>		
	<div id='table-loader-dnsfilterd-rules'></div>
	<script>
	LoadAjax('table-loader-dnsfilterd-rules','$page?table=yes');

	</script>";
	
	$tpl=new templates();
	echo $tpl->_ENGINE_parse_body($html);

}

function table(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$q=new lib_sqlite("/home/artica/SQLITE/dns.db");
	$rule_text=$tpl->_ENGINE_parse_body("{rule}");
	//$TimeSpace=$webfilter->TimeToText(unserialize(base64_decode($ligne["TimeSpace"])));
	$EnableWebProxyStatsAppliance=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableWebProxyStatsAppliance"));
	if(!is_numeric($EnableWebProxyStatsAppliance)){$EnableWebProxyStatsAppliance=0;}
	$groups=$tpl->_ENGINE_parse_body("{sources}");
	$blacklists=$tpl->_ENGINE_parse_body("{blacklists}");
	$whitelists=$tpl->_ENGINE_parse_body("{whitelists}");
	$delete=$tpl->_ENGINE_parse_body("{delete}");
	$order=$tpl->javascript_parse_text("{order}");
	$TRCLASS=null;
	
	$ARRAY["PROGRESS_FILE"]="/usr/share/artica-postfix/ressources/logs/dnsfilterd.progress";
	$ARRAY["LOG_FILE"]="/usr/share/artica-postfix/ressources/logs/dnsfilterd.progress.log";
	$ARRAY["CMD"]="dnsfilterd.php?compile-rules=yes";
	$ARRAY["TITLE"]="{apply_webiltering_rules}";
	$prgress=base64_encode(serialize($ARRAY));
	$jsrestart="Loadjs('fw.progress.php?".fw_progress_args_b64($prgress)."&mainid=dnsfilterd-progress')";
	
	$add="Loadjs('fw.dns.filterd.rules.edit.php?ID=-1')";
	
	$html[]=$tpl->_ENGINE_parse_body("
		<div class=\"btn-group\" data-toggle=\"buttons\" style='margin-top:10px'>
			<label class=\"btn btn btn-primary\" OnClick=\"$add\"><i class='fa fa-plus'></i> {new_rule} </label>
			<label class=\"btn btn btn-info\" OnClick=\"$jsrestart\"><i class='fa fa-save'></i> {apply_dnsfiltering_rules} </label>
			<label class=\"btn btn btn-primary\" OnClick=\"Loadjs('fw.ufdb.databases.update.php')\"><i class='".ico_download."'></i> {update_webfiltering_artica_databases} </label>
		</div>
		<div class=\"btn-group\" data-toggle=\"buttons\"></div>");



	
	$html[]="<div class='awc-wrap' style='margin:0;padding:0'><div class='awc-card'><div class='awc-scroll'><table class='awc-tbl awc-tbl-compact'>";
	$html[]="<thead>";
	$html[]="<tr>";
	$html[]="<th data-sortable=true class='text-capitalize' >$order</th>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text'>$rule_text</th>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text'>$groups</th>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text'>". $tpl->_ENGINE_parse_body($whitelists)."</th>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text'>". $tpl->_ENGINE_parse_body($blacklists)."</th>";
	$html[]="<th data-sortable=true class='text-capitalize' width=1%>". $tpl->_ENGINE_parse_body("{duplicate}")."</th>";
	$html[]="<th data-sortable=true class='text-capitalize' width=1%>". $tpl->_ENGINE_parse_body("{move}")."</th>";
	$html[]="<th data-sortable=false width=1%>$delete</th>";
	$html[]="</tr>";
	$html[]="</thead>";
	$html[]="<tbody id='dnsfrules-rows'>";
	
	
	$sock=new dnsfiltersocks();
	$ligne=unserialize($sock->GET_INFO("DansGuardianDefaultMainRule"));
	$DefaultPosition=$ligne["defaultPosition"];
	if(!is_numeric($DefaultPosition)){$DefaultPosition=0;}
	
	if($DefaultPosition==0){
		$TRCLASS=null;
		$html[]=DefaultRule($TRCLASS);
	}
	
	$no_category_has_been_added=$tpl->_ENGINE_parse_body("{no_category_has_been_added}");
	$endofrule_TEXTS["any"]="<i class='text-info'>".$tpl->_ENGINE_parse_body("{ufdb_explain_any}")."</i>";
	$endofrule_TEXTS["none"]="<i class='text-danger'>".$tpl->_ENGINE_parse_body("{ufdb_explain_none}")."</i>";
	
	$sql="SELECT * FROM webfilter_rules ORDER BY zOrder";
	$results=$q->QUERY_SQL($sql);
	$webfilter=new webfilter_rules("dns.db");
	$t=time();
	
	foreach ($results as $index=>$ligne){
		$TRCLASS=null;
		$ID=$ligne["ID"];
		$md5=md5($ligne["ID"]);
		$endofrule_text=null;
		$EnableGoogleSafeSearch_text=null;
		$ligne["groupname"]=utf8_encode($ligne["groupname"]);
		$endofrule=$ligne["endofrule"];
		if($endofrule==null){$endofrule="any";}
		$md=md5(serialize($ligne));
	
		$MAIN_EXPLAIN_TR=array();
	
	
	
		$js="DansGuardianEditRule('{$ligne["ID"]}','{$ligne["groupname"]}');";
		if($GLOBALS["VERBOSE"]){echo "<HR>webfilter->rule_time_list_from_ruleid({$ligne["ID"]})<HR><br>\n";}
	
		$CountDeBlack=intval($webfilter->COUNTDEGBLKS($ligne["ID"]));
		$CountDewhite=intval($webfilter->COUNTDEGBWLS($ligne["ID"]));
	
		$CountDeAll=intval($CountDeBlack+$CountDewhite);
		if($CountDeAll==0){
		
			$MAIN_EXPLAIN_TR[]="<i class='text-danger'>$no_category_has_been_added</i>";
		}
	
		$color="black";
		if($ligne["enabled"]==0){$color="#8a8a8a";}
	
	
		if($ligne["groupmode"]==0){
			$MAIN_EXPLAIN_TR[]="<i class='text-danger'>{all_websites_are_banned}</span>";
		}
		if($ligne["groupmode"]==2){
			$MAIN_EXPLAIN_TR[]="<i class='text-info'>{everything_is_allowed}</span>";
		}
	
	
	
	
		
		$jsGroups="<a href=\"javascript:blur();\"
		OnClick=\"javascript:document.getElementById('anim-img-{$ligne["ID"]}').innerHTML='<img src=img/wait.gif>';Loadjs('dansguardian2.edit.php?js-groups={$ligne["ID"]}&ID={$ligne["ID"]}&t=$t');\"
		style='text-decoration:underline;font-weight:bold'>";
	

	
	
		$TimeSpace=$webfilter->rule_time_list_explain($ligne["TimeSpace"],$ligne["ID"],$t);
		$TimeSpace=str_replace('\n\n', "<br>", $TimeSpace);
	
		$styleupd="style='border:0px;margin:0px;padding:0px;background-color:transparent'";
		$up=imgsimple("arrow-up-32.png","","RuleDansUpDown('{$ligne['ID']}',1)");
		$down=imgsimple("arrow-down-32.png","","RuleDansUpDown('{$ligne['ID']}',0)");
		// Un <table> imbrique servait a coller les deux fleches, avec un
		// "</td $styleupd>" -- des attributs sur une balise FERMANTE.
		$zorder="<span class='awc-nowrap'>$down&nbsp;$up</span>";
	
	
		$LigneCount=$q->mysqli_fetch_array("SELECT count(*) as tcount FROM webfilter_ipsources WHERE ruleid={$ligne['ID']}");
		
		$CountDeGroups=intval($LigneCount["tcount"]);
	
		if($ligne["AllSystems"]==1){$jsGroups="*";$CountDeGroups=$tpl->icon_nothing();}
	
		$MAIN_EXPLAIN_TR[]=$endofrule_TEXTS[$endofrule];
		if($TimeSpace<>null){$MAIN_EXPLAIN_TR[]=$TimeSpace;}
	
	
		$MAIN_EXPLAIN_TEXT=$tpl->_ENGINE_parse_body("<br>".@implode("<br>", $MAIN_EXPLAIN_TR));
		if($ligne["enabled"]==0){$MAIN_EXPLAIN_TEXT=null;}
		if(trim($ligne["groupname"])==null){$ligne["groupname"]="noname";}
		
		if($CountDewhite<2){$CountDewhite="$CountDewhite</strong> {category}";}else{$CountDewhite="$CountDewhite</strong> {categories}";}
		if($CountDeBlack<2){$CountDeBlack="$CountDeBlack</strong> {category}";}else{$CountDeBlack="$CountDeBlack</strong> {categories}";}
		$groupnameenc=urlencode($ligne["groupname"]);
		
		
		$html[]="<tr class='$TRCLASS' id='$md'>";
		$html[]="<td>{$ligne["zOrder"]}</td>";
		$html[]="<td>". $tpl->td_href($ligne["groupname"],null,"Loadjs('fw.dns.filterd.rules.edit.php?ID={$ligne['ID']}')")."&nbsp;&nbsp;$MAIN_EXPLAIN_TEXT</td>";
		$html[]="<td>". $tpl->td_href("$CountDeGroups {sources}",null,"Loadjs('$page?sources-js={$ligne['ID']}')")."</td>";
		
		$html[]="<td><strong>".$tpl->_ENGINE_parse_body($CountDewhite)."</strong></td>";
		$html[]="<td><strong>".$tpl->_ENGINE_parse_body($CountDeBlack)."</strong></td>";
		
		$html[]="<td>". $tpl->icon_copy("Loadjs('fw.dns.filterd.rules.duplicate.php?from={$ligne['ID']}&t=$t')","AsDnsAdministrator")."</td>";
		$html[]="<td>". $tpl->icon_up("RuleGroupUpDown$t({$ligne['ID']},1);").$tpl->icon_down("RuleGroupUpDown$t({$ligne['ID']},0);","AsDnsAdministrator")."</td>";
		$html[]="<td>". $tpl->icon_delete("Loadjs('$page?delete-js={$ligne['ID']}&rule=$groupnameenc&md=$md')","AsDnsAdministrator")."</td>";
		$html[]="</tr>";
	

		}
	
		if($DefaultPosition==1){
			$TRCLASS=null;
			$html[]=DefaultRule($TRCLASS);
		}
	
	// Le <tfoot> portait un pager footable jamais alimente: l'init ciblait
	// #table-filtragewebrules-rules, un id qu'aucun <table> de cette page ne
	// porte.
	$html[]="</tbody></table></div>";
	$html[]="<div class='awc-foot'>";
	$html[]="<span class='awc-pg' id='dnsfrules-pager'></span>";
	$html[]="</div></div></div>";

	// Le <script> est concatene APRES l'analyse: la regex #{(.+?)}# de
	// _ENGINE_parse_body lisait la configuration JSON de footable ET les corps
	// des deux fonctions ci-dessous comme des {tokens}. RuleGroupUpDown est
	// appelee par les fleches de chaque ligne: elle DOIT etre definie.
	$js=array();
	$js[]="NoSpinner();";
	$js[]=$tpl->ICON_SCRIPTS_COMPILE();
	$js[]="var xRuleGroupUpDown$t= function (obj) {";
	$js[]="\tvar res=obj.responseText;";
	$js[]="\tif(res.length>3){alert(res);return;}";
	$js[]="}";
	$js[]="function RuleGroupUpDown$t(ID,direction){";
	$js[]="\tvar XHR = new XHRConnection();";
	$js[]="\tXHR.appendData('rule-order', ID);";
	$js[]="\tXHR.appendData('direction', direction);";
	$js[]="\tXHR.sendAndLoad('$page', 'POST',xRuleGroupUpDown$t);";
	$js[]="}";
	$script="<script>".@implode("\n",$js)."</script>";

	// La sortie n'etait PAS analysee: les en-tetes et libelles en {token} de ce
	// tableau s'affichaient litteralement. Et depuis que le <script> est sorti
	// de $html, il faut l'emettre explicitement -- sans quoi les fleches
	// monter/descendre appelleraient une fonction inexistante.
	echo awc_table_style()
		.$tpl->_ENGINE_parse_body(@implode("\n", $html))
		.$script
		.awc_pager_js(50,"dnsfrules-rows","dnsfrules-pager");

}

function save(){
	$sock=new sockets();
	foreach ($_POST as $key=>$value){
		$sock->SET_INFO($key, $value);
	}
}

function DefaultRule($TRCLASS){
	$t=$_GET["t"];
	$sock=new sockets();
	$page=CurrentPageName();
	$webfilter=new webfilter_rules("dns.db");
	$tpl=new template_admin();
	$tmplate=$tpl->_ENGINE_parse_body("{template}");

	$color="black";
	$sock=new dnsfiltersocks();
	$ligne=unserialize($sock->GET_INFO("DansGuardianDefaultMainRule"));
	$EnableGoogleSafeSearch=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableGoogleSafeSearch"));
	if(!is_numeric($EnableGoogleSafeSearch)){$EnableGoogleSafeSearch=1;}
	if(!is_numeric($ligne["groupmode"])){$ligne["groupmode"]=1;}

	$endofrule_TEXTS["any"]="<i class='text-info'>".$tpl->_ENGINE_parse_body("{ufdb_explain_any}")."</i>";
	$endofrule_TEXTS["none"]="<i class='text-danger'>".$tpl->_ENGINE_parse_body("{ufdb_explain_none}")."</i>";

	if($ligne["endofrule"]==null){$ligne["endofrule"]="any";}
	$endofrule_text=null;

	$CountDeBlack=intval($webfilter->COUNTDEGBLKS(0));
	$CountDewhite=intval($webfilter->COUNTDEGBWLS(0));
	$CountDeAll=intval($CountDeBlack+$CountDewhite);

	if($CountDeAll==0){
		$MAINTR[]=$tpl->_ENGINE_parse_body("<i class='text-danger'>{no_category_has_been_added}</i>");
	}

	$MAINTR[]="<i>{ufdb_explain_default_rule}</i>";


	if($EnableGoogleSafeSearch==0){
		if($ligne["GoogleSafeSearch"]==1){
			$EnableGoogleSafeSearch_text=$tpl->javascript_parse_text(
					"<i>{EnableGoogleSafeSearch}</i>");
		}
			
	}

	if($ligne["groupmode"]==0){
		$MAINTR[]="<i class='text-danger'>{all_websites_are_banned}</span>";
	}
	if($ligne["groupmode"]==2){
		$MAINTR[]="<i class='text-info'>{everything_is_allowed}</span>";
	}



	$js="DansGuardianEditRule('0','default')";
	$jsblack="<a href=\"javascript:blur();\"
	OnClick=\"javascript:document.getElementById('anim-img-0').innerHTML='<img src=img/wait.gif>';Loadjs('dansguardian2.edit.php?js-blacklist-list=yes&RULEID=0&modeblk=0&group=&TimeID=&t=$t');\"
	style='text-decoration:underline;font-weight:bold;color:$color'>";


	$jswhite="<a href=\"javascript:blur();\"
	OnClick=\"javascript:document.getElementById('anim-img-0').innerHTML='<img src=img/wait.gif>';Loadjs('dansguardian2.edit.php?js-blacklist-list=yes&RULEID=0&modeblk=1&group=&TimeID=&t=$t');\"
	style='text-decoration:underline;font-weight:bold;color:$color'>";




	$delete="&nbsp;";
	$sock=new dnsfiltersocks();
	$ligne=unserialize($sock->GET_INFO("DansGuardianDefaultMainRule"));
	$TimeSpace=$webfilter->rule_time_list_explain($ligne["TimeSpace"],0,$t);
	$TimeSpace=str_replace('\n\n', "<br>", $TimeSpace);
	$MAINTR[]=$endofrule_TEXTS[$ligne["endofrule"]];
	if($EnableGoogleSafeSearch_text<>null){$MAINTR[]=$EnableGoogleSafeSearch_text;}
	if($TimeSpace<>null){$MAINTR[]=$TimeSpace;}

	$MAINTRTEXT=$tpl->_ENGINE_parse_body(@implode("<br>", $MAINTR));

	if($CountDewhite<2){$CountDewhite="$CountDewhite</strong> {category}";}else{$CountDewhite="$CountDewhite</strong> {categories}";}
	if($CountDeBlack<2){$CountDeBlack="$CountDeBlack</strong> {category}";}else{$CountDeBlack="$CountDeBlack</strong> {categories}";}
	$MAINTRTEXT=str_replace("<br>\n<br>", "<br>", $MAINTRTEXT);
	$MAINTRTEXT=str_replace("<br><br>", "<br>", $MAINTRTEXT);
	$html[]="<tr class='$TRCLASS'>";
	$html[]="<td>". $tpl->icon_nothing()."</td>";
	$html[]="<td>". $tpl->td_href("{default}",null,"Loadjs('fw.dns.filterd.rules.edit.php?ID=0')")."&nbsp;&nbsp;$MAINTRTEXT</td>";
	$html[]="<td>". $tpl->icon_nothing()."</td>";
	$html[]="<td><strong>$CountDewhite</strong></td>";
	$html[]="<td><strong>$CountDeBlack</strong></td>";
	$html[]="<td>". $tpl->icon_copy("Loadjs('fw.dns.filterd.rules.duplicate.php?from=0')")."</td>";
	$html[]="<td>". $tpl->icon_nothing()."</td>";
	$html[]="<td>". $tpl->icon_nothing()."</td>";
	$html[]="</tr>";

	return $tpl->_ENGINE_parse_body(@implode("\n", $html));

}
function move_rule(){

	$q=new lib_sqlite("/home/artica/SQLITE/dns.db");
	// Pentest 2026-11-04 : rule-order etait concatene dans la requete.
	$ruleOrderID=intval($_POST["rule-order"]);
	$sql="SELECT zOrder FROM webfilter_rules WHERE `ID`='$ruleOrderID'";
	$ligne=$q->mysqli_fetch_array($sql);
	$xORDER_ORG=$ligne["zOrder"];
	$xORDER=$xORDER_ORG;
	if($_POST["direction"]==1){$xORDER=$xORDER_ORG-1;}
	if($_POST["direction"]==0){$xORDER=$xORDER_ORG+1;}
	if($xORDER<0){$xORDER=0;}
	$sql="UPDATE webfilter_rules SET zOrder=$xORDER WHERE `ID`='$ruleOrderID'";
	$q->QUERY_SQL($sql);
	if(!$q->ok){echo $q->mysql_error;;return;}
	

	if($_POST["direction"]==1){
		$xORDER2=$xORDER+1;
		if($xORDER2<0){$xORDER2=0;}
		$sql="UPDATE webfilter_rules SET zOrder=$xORDER2 WHERE `ID`<>'$ruleOrderID' AND zOrder=$xORDER";
		$q->QUERY_SQL($sql);
		//echo $sql."\n";
		if(!$q->ok){echo $q->mysql_error;return;}
	}
	if($_POST["direction"]==0){
		$xORDER2=$xORDER-1;
		if($xORDER2<0){$xORDER2=0;}
		$sql="UPDATE webfilter_rules SET zOrder=$xORDER2 WHERE `ID`<>'$ruleOrderID' AND zOrder=$xORDER";
		$q->QUERY_SQL($sql);
		//echo $sql."\n";
		if(!$q->ok){echo $q->mysql_error;return;}
	}

	$c=0;
	$sql="SELECT ID FROM webfilter_rules ORDER BY zOrder";
	$results = $q->QUERY_SQL($sql);

	foreach ($results as $index=>$ligne){
		$q->QUERY_SQL("UPDATE webfilter_rules SET zOrder=$c WHERE `ID`={$ligne["ID"]}");
		$c++;
	}


}