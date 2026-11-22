<?php
// Post SP10 - 13/10/2026
if(isset($_GET["VERBOSE"])){ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string','');ini_set('error_append_string','');}
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string','');ini_set('error_append_string','');}

include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once('ressources/class.ldap.inc');
include_once('ressources/class.users.menus.inc');
include_once('ressources/class.dansguardian.inc');
include_once('ressources/class.ActiveDirectory.inc');
include_once("ressources/class.ldap-extern.inc");
include_once(dirname(__FILE__)."/ressources/class.dnsfilter.inc");

if(isset($_POST["ID"])){rule_edit_save();exit;}
if(isset($_GET["popup"])){popup();exit;}
if(isset($_GET["tab"])){tabs();exit;}

js();

function js(){
	$page=CurrentPageName();
	// $ID partait BRUT dans le WHERE. La colonne est un entier, et les deux
	// valeurs speciales (-1 = nouvelle regle, 0 = regle par defaut) survivent a
	// intval().
	$ID=intval($_GET["ID"]);
	$rulename="";
	if($ID==0){$rulename="{default}";}
	if($ID==-1){$rulename="{new_rule}";}
	if($ID>0){
		$q=new lib_sqlite("/home/artica/SQLITE/dns.db");
		$ligne=$q->mysqli_fetch_array("SELECT groupname FROM webfilter_rules WHERE ID=$ID");
		if(!is_array($ligne)){$ligne=array();}
		// Le nom vient de la base et partait BRUT dans le titre de la modale.
		$rulename=htmlspecialchars((string)($ligne["groupname"] ?? ""));
	}
	$tpl=new template_admin();
	$tpl->js_dialog($rulename, "$page?tab=$ID");
	
}

function tabs(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$ID=intval($_GET["tab"]);
	$array["{rule}"]="$page?popup=$ID";
	if($ID>-1){
		$array["{blacklists}"]="fw.dns.filterd.rules.categories.php?ID=$ID&modeblk=0";
		$array["{whitelists}"]="fw.dns.filterd.rules.categories.php?ID=$ID&modeblk=1";
		$array["{period}"]="fw.dns.filterd.rules.time.php?ID=$ID";
	}
	echo $tpl->tabs_default($array);
}

function popup(){
	
	// $ID partait BRUT dans le WHERE plus bas.
	$ID=intval($_GET["popup"]);
	$tpl=new template_admin();
	$users=new usersMenus();
	$page=CurrentPageName();
	$q=new lib_sqlite("/home/artica/SQLITE/dns.db");
	if(!$q->FIELD_EXISTS("webfilter_rules", "UseSecurity")){$q->QUERY_SQL("ALTER TABLE `webfilter_rules` ADD `UseSecurity` smallint(1),ADD INDEX ( `UseSecurity` )");}
	$t=time();
	$DISABLE_DANS_FIELDS=0;
	$groupmode[0]="{banned}";
	$groupmode[1]="{filtered}";
	$groupmode[2]="{exception}";
	$button_name="{apply}";
	$close=null;
	// ($t etait ecrase par $_GET["t"] puis jamais relu.)
	if($ID<0){$button_name="{add}";}
	$sock=new sockets();
	$CheckLicense=false;
	$EnableGoogleSafeSearch=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableGoogleSafeSearch"));
	if(!is_numeric($EnableGoogleSafeSearch)){$EnableGoogleSafeSearch=1;}

	
	$ENDOFRULES[null]="{select}";
	$ENDOFRULES["any"]="{ufdb_any}";
	$ENDOFRULES["none"]="{ufdb_none}";
	$AsDefaultRule=false;	
	$FORM_EXPLAIN=null;
	
	
	$ligne=array();
	if($ID>0){
		$ligne=$q->mysqli_fetch_array("SELECT * FROM webfilter_rules WHERE ID=$ID");
		if(!is_array($ligne)){$ligne=array();}
		$CheckLicense=true;
	
	}else{
		if($ID==0){
			$sock=new dnsfiltersocks();
			// unserialize() rend false sur une valeur absente ou tronquee, et
			// $ligne["..."] sur false est un avertissement PHP 8 suivi d'un null.
			$ligne=@unserialize((string)$sock->GET_INFO("DansGuardianDefaultMainRule"),array("allowed_classes"=>false));
			if(!is_array($ligne)){$ligne=array();}
			$ligne["groupname"]="default";
			$DefaultPosition=$ligne["defaultPosition"];
			if(!is_numeric($DefaultPosition)){$DefaultPosition=0;}
			$AsDefaultRule=true;
			$CheckLicense=false;
		}
		
		if($ID==-1){
			$CheckLicense=true;
			$ligne["groupname"]="Rule_".time();
			$ligne["enabled"]=1;
			$close="BootstrapDialog1.close();";
		}
	}

	if(!isset($ligne["endofrule"])){$ligne["endofrule"]="any";}
	if(!is_numeric($ligne["enabled"])){$ligne["enabled"]=1;}
	if(!is_numeric($ligne["searchtermlimit"])){$ligne["searchtermlimit"]=30;}
	if(!is_numeric($ligne["bypass"])){$ligne["bypass"]=0;}
	if(!is_numeric($ligne["groupmode"])){$ligne["groupmode"]=1;}
	if(!is_numeric($ligne["naughtynesslimit"])){$ligne["naughtynesslimit"]=50;}
	if(!is_numeric($ligne["embeddedurlweight"])){$ligne["embeddedurlweight"]=0;}
	if(!is_numeric($ligne["GoogleSafeSearch"])){$ligne["GoogleSafeSearch"]=0;}
	if(!is_numeric($ligne["UseExternalWebPage"])){$ligne["UseExternalWebPage"]=0;}
	if(!is_numeric($ligne["UseSecurity"])){$ligne["UseSecurity"]=0;}
	
	if(!isset($ligne["zOrder"])){$ligne["zOrder"]=0;}
	if(!isset($ligne["AllSystems"])){$ligne["AllSystems"]=0;}
	if(!isset($ligne["enabled"])){$ligne["enabled"]=1;}
	
	
	$rawWeb=base64_decode((string)($ligne["ExternalWebPage"] ?? ""),true);
	$DNS_PARAMS=($rawWeb===false)?false:@unserialize($rawWeb,array("allowed_classes"=>false));
	if(!is_array($DNS_PARAMS)){$DNS_PARAMS=array();}
	if(!isset($DNS_PARAMS["dns_use_default"])){$DNS_PARAMS["dns_use_default"]=1;}
	if(!isset($DNS_PARAMS["dns_ipaddr"])){$DNS_PARAMS["dns_ipaddr"]="127.0.0.1";}
	if(!isset($DNS_PARAMS["dns_neg_ttl"])){$DNS_PARAMS["dns_neg_ttl"]=3600;}
	
	if($ligne["AllSystems"]==1){
		$FORM_EXPLAIN="{AllSystemsDansExpl}";
	}

	if($ligne["groupmode"]==0){
		echo $tpl->_ENGINE_parse_body("<div class='alert alert-danger'><strong>{navigation_banned}</strong><br>{navigation_banned_text}</div>");
	}
	
	$form[]=$tpl->field_hidden("ID", $ID);
	// Le nom vient de la base et partait BRUT dans le titre du formulaire.
	$FORM_TITLE="{rule}: ".htmlspecialchars((string)($ligne["groupname"] ?? ""));
	if($AsDefaultRule){
		$arrayPos[0]="{at_the_top_rules}";
		$arrayPos[1]="{at_the_end_of_rules}";
		
		$form[]=$tpl->field_hidden("groupname", "default");
		$form[]=$tpl->field_hidden("enabled", "1");
		$form[]=$tpl->field_hidden("AllSystems", "0");
		$FORM_TITLE="{rule_name}: {default}";
		$form[]=$tpl->field_array_hash($arrayPos, "defaultPosition", "{position}", $DefaultPosition);
	}
	
	
	if(!$AsDefaultRule){
		$form[]=$tpl->field_text("groupname", "{rule_name}", $ligne["groupname"]);
		$form[]=$tpl->field_checkbox("enabled","{enabled}",$ligne["enabled"],true);
		$form[]=$tpl->field_numeric("zOrder","{order}",$ligne["zOrder"]);
		$form[]=$tpl->field_checkbox("AllSystems","{AllSystems}",$ligne["AllSystems"],false);
	}
	
	
	
	
	$form[]=$tpl->field_array_hash($groupmode, "groupmode", "{rule_behavior}", $ligne["groupmode"]);
	$form[]=$tpl->field_array_hash($ENDOFRULES, "endofrule", "{finish_rule_by}", $ligne["endofrule"]);
	
	
	$form[]=$tpl->field_section("{dns_behavior}");
	$form[]=$tpl->field_checkbox("dns_use_default","{use_default_settings}",$DNS_PARAMS["dns_use_default"],false);
	$form[]=$tpl->field_ipaddr("dns_ipaddr", "{ipaddr_on_blocked_domains}", $DNS_PARAMS["dns_ipaddr"]);
	$form[]=$tpl->field_numeric("dns_neg_ttl", "{negative_dns_ttl} ({seconds})", $DNS_PARAMS["dns_neg_ttl"]);
	
	
	
	
	// form_outside() n'analyse QUE le libelle de son bouton: sans cet appel, le
	// titre, l'explication et tous les libelles de champ s'affichaient en
	// {tokens} litteraux.
	echo $tpl->_ENGINE_parse_body(
		$tpl->form_outside($FORM_TITLE, @implode("\n", $form),$FORM_EXPLAIN,$button_name,
			"LoadAjax('table-loader-dnsfilterd-rules','fw.dns.filterd.rules.php?table=yes');$close",
			"AsDnsAdministrator",$CheckLicense)
	);

}
/**
 * Les SEULES colonnes que ce formulaire a le droit d'ecrire.
 *
 * La boucle d'origine construisait le nom de colonne ET la valeur par
 * concatenation, a partir de $_POST:
 *
 *     $fieldsEDIT[]="`$num`='".utf8_encode($ligne)."'";
 *
 * La CLE etait donc un nom de colonne fourni par l'appelant, et la VALEUR
 * partait sans echappement -- soit une primitive d'ecriture complete sur
 * webfilter_rules. Un nom de colonne ne peut jamais etre lie par un parametre
 * prepare: il se valide, et la seule validation sure est la liste des colonnes
 * que ce formulaire possede reellement.
 */
function dnsfr_edit_columns(){
	return array(
		"groupname"=>true,
		"enabled"=>true,
		"zOrder"=>true,
		"AllSystems"=>true,
		"groupmode"=>true,
		"endofrule"=>true,
		"ExternalWebPage"=>true,
	);
}

function rule_edit_save(){
	// $ID partait BRUT dans "WHERE ID=$ID".
	$ID=intval($_POST["ID"]);
	$tpl=new template_admin();
	$tpl->CLEAN_POST();
	$q=new lib_sqlite("/home/artica/SQLITE/dns.db");
	$sock=new sockets();

	
	
	writelogs("Save ruleid `$ID`",__FUNCTION__,__FILE__,__LINE__);

	$DEFAULTARRAY=array();
	if($ID==0){
		$sock=new dnsfiltersocks();
		$DEFAULTARRAY=@unserialize((string)$sock->GET_INFO("DansGuardianDefaultMainRule"),array("allowed_classes"=>false));
		if(!is_array($DEFAULTARRAY)){$DEFAULTARRAY=array();}
	}
	unset($_POST["ID"]);
	$build=false;

	if(strtolower($_POST["groupname"])=="default"){$_POST["groupname"]=null;}

	if($_POST["groupname"]==null){$_POST["groupname"]=time();}
	$_POST["groupname"]=strtolower(replace_accents($_POST["groupname"]));
	$_POST["groupname"]=str_replace("$", "", $_POST["groupname"]);
	$_POST["groupname"]=str_replace("(", "", $_POST["groupname"]);
	$_POST["groupname"]=str_replace(")", "", $_POST["groupname"]);
	$_POST["groupname"]=str_replace("[", "", $_POST["groupname"]);
	$_POST["groupname"]=str_replace("]", "", $_POST["groupname"]);
	$_POST["groupname"]=str_replace("%", "", $_POST["groupname"]);
	$_POST["groupname"]=str_replace("!", "", $_POST["groupname"]);
	$_POST["groupname"]=str_replace(":", "", $_POST["groupname"]);
	$_POST["groupname"]=str_replace(";", "", $_POST["groupname"]);
	$_POST["groupname"]=str_replace(",", "", $_POST["groupname"]);
	$_POST["groupname"]=str_replace("£", "", $_POST["groupname"]);
	$_POST["groupname"]=str_replace("~", "", $_POST["groupname"]);
	$_POST["groupname"]=str_replace("`", "", $_POST["groupname"]);
	$_POST["groupname"]=str_replace('\\', "_", $_POST["groupname"]);
	$_POST["groupname"]=str_replace('/', "_", $_POST["groupname"]);
	$_POST["groupname"]=str_replace('+', "_", $_POST["groupname"]);
	$_POST["groupname"]=str_replace('=', "_", $_POST["groupname"]);
	$_POST["groupname"]=str_replace('*', "", $_POST["groupname"]);
	$_POST["groupname"]=str_replace('&', "", $_POST["groupname"]);
	$_POST["groupname"]=str_replace('"', "", $_POST["groupname"]);
	$_POST["groupname"]=str_replace('{', "", $_POST["groupname"]);
	$_POST["groupname"]=str_replace('}', "", $_POST["groupname"]);
	$_POST["groupname"]=str_replace('|', "", $_POST["groupname"]);
	// L'apostrophe ne figurait dans AUCUN des vingt-quatre str_replace ci-dessus:
	// elle traversait le nettoyage et sortait de la chaine SQL juste en dessous.
	$_POST["groupname"]=str_replace("'", "", $_POST["groupname"]);

	$results=$q->QUERY_SQL_PARAMS("SELECT ID FROM webfilter_rules WHERE `groupname`=? AND ID != $ID",
		array($_POST["groupname"]));
	if(!is_array($results)){$results=array();}
	$mysql_num_rows=intval(count($results));

	if($mysql_num_rows>0){
		$Groupname=$_POST["groupname"];
		$_POST["groupname"] = "$Groupname - ".(intval($mysql_num_rows)+1);

	}
	
	
	$DNS_PARAMS["dns_use_default"]=$_POST["dns_use_default"];
	$DNS_PARAMS["dns_ipaddr"]=$_POST["dns_ipaddr"];
	$DNS_PARAMS["dns_neg_ttl"]=$_POST["dns_neg_ttl"];
	
	unset($_POST["dns_use_default"]);
	unset($_POST["dns_ipaddr"]);
	unset($_POST["dns_neg_ttl"]);
	
	$_POST["ExternalWebPage"]=base64_encode(serialize($DNS_PARAMS));
	
	

	// La regle par defaut ne vit pas en base: c'est un blob serialise dans
	// Valkey, et il garde bien tous les champs postes (dont defaultPosition,
	// qui n'est pas une colonne).
	foreach ($_POST as $num=>$ligne){
		$DEFAULTARRAY[$num]=$ligne;
	}
	if($ID==0){
		$sock=new dnsfiltersocks();
		$sock->SET_INFO("DansGuardianDefaultMainRule",serialize($DEFAULTARRAY));
		return;
	}

	// Cote SQL: le nom de colonne est valide contre la liste blanche, la valeur
	// est LIEE. utf8_encode() a disparu: il convertit de l'ISO-8859-1 vers
	// l'UTF-8, or la console est deja en UTF-8 -- il double-encodait donc les
	// accents des noms de regle. Il est de surcroit deprecie depuis PHP 8.2.
	$allowed=dnsfr_edit_columns();
	$cols=array();$marks=array();$sets=array();$binds=array();
	foreach ($_POST as $num=>$ligne){
		if(!isset($allowed[$num])){continue;}
		$cols[]="`$num`";
		$marks[]="?";
		$sets[]="`$num`=?";
		$binds[]=$ligne;
	}
	if(count($cols)==0){echo $tpl->post_error("{error}");return;}

	if($ID<0){
		$q->QUERY_SQL_PARAMS("INSERT INTO webfilter_rules (".@implode(",",$cols).") VALUES (".@implode(",",$marks).")",$binds);
		$build=true;
	}else{
		$q->QUERY_SQL_PARAMS("UPDATE webfilter_rules SET ".@implode(",",$sets)." WHERE ID=$ID",$binds);
	}

	// L'ancien message renvoyait l'erreur SQL EN DOUBLE et la requete complete:
	// le schema partait a l'ecran a chaque echec.
	if(!$q->ok){echo $tpl->post_error($q->mysql_error);return;}
	admin_tracks("DNS filtering rule saved ID=$ID (".@implode(", ",$cols).")");
		$c=0;
		$results = $q->QUERY_SQL("SELECT ID FROM webfilter_rules ORDER BY zOrder");
		if(!is_array($results)){$results=array();}

		foreach ($results as $index=>$ligne){
			$q->QUERY_SQL("UPDATE webfilter_rules SET zOrder=$c WHERE `ID`={$ligne["ID"]}");
			$c++;
		}



}