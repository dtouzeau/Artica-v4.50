<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
include_once(dirname(__FILE__)."/ressources/class.sockets.inc");
include_once(dirname(__FILE__)."/ressources/class.tasks.inc");
// taches posees par Artica Meta (politique proxytasks) : badge, lecture seule, bandeau
include_once(dirname(__FILE__)."/ressources/class.systemtasks.meta.inc");
// awc_table_style() / awc_pager_js(): feuille de style et pager partages des tableaux d'entites
include_once(dirname(__FILE__)."/ressources/class.statistics_appliance.inc");
$GLOBALS["CLASS_SOCKETS"]=new sockets();

if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}

// ForceTaskType est un identifiant numerique: on le normalise une seule fois,
// ce qui couvre tous ses usages en aval (URL Loadjs() et parametre ?tasktype=
// de l'API, qui a remplace le litteral SQL "AND TaskType='...'" d'origine).
$_GET["ForceTaskType"]=intval($_GET["ForceTaskType"] ?? 0);

if(isset($_GET["main"])){main();exit;}
if(isset($_GET["AddNewSchedule-js"])){schedule_js();exit;}
if(isset($_GET["AddNewSchedule-popup"])){schedule_popup();exit;}
if(isset($_GET["enable-task-js"])){schedule_enable();exit;}
if(isset($_GET["delete-task-js"])){schedule_delete_js();exit;}
if(isset($_GET["run-task-js"])){schedule_run_task_js();exit;}
if(isset($_POST["ID"])){schedule_save();exit;}
if(isset($_POST["remove"])){schedule_remove();exit;}
if(isset($_POST["execute"])){schedule_execute();exit;}
if(isset($_GET["microstart"])){microstart();exit;}

page();

// ---------- Relais REST partages par toutes les fonctions ci-dessous ----------
//
// Cette page n'ouvre plus /home/artica/SQLITE/proxy.db: la table
// webfilters_schedules et le catalogue des types de taches sont servis par
// /proxy/tasks/* (module Go SystemTasks, famille "proxy").

// proxy_catalog(): {"types"=>{id=>["label"=>..,"explain"=>..,"process"=>..]},
// "disabled"=>{id=>raison}}. Cache statique par requete: la page appelle les
// helpers ci-dessous plusieurs fois par affichage, et sans memorisation chaque
// libelle serait un aller-retour de plus vers le daemon.
//
// "disabled" est conserve tel que l'API le rend, mais AUCUN appelant ne s'en
// sert: filtrer la liste deroulante sur cette carte retirerait du formulaire
// le type d'une planification existante, et field_array_hash afficherait alors
// une valeur absente de ses options. La desactivation est appliquee la ou elle
// a un sens -- a la generation des lignes cron, cote daemon.
function proxy_catalog(){
    static $cache=null;
    if($cache!==null){return $cache;}
    $j=json_decode((string)$GLOBALS["CLASS_SOCKETS"]->REST_API("/proxy/tasks/types"),true);
    $cache=array("types"=>array(),"disabled"=>array());
    if(is_array($j) && !empty($j["success"]) && isset($j["data"]["types"]) && is_array($j["data"]["types"])){
        $cache["types"]=$j["data"]["types"];
        if(isset($j["data"]["disabled"]) && is_array($j["data"]["disabled"])){
            $cache["disabled"]=$j["data"]["disabled"];
        }
    }else{
        writelogs("/proxy/tasks/types: reponse inexploitable - catalogue vide",
            __FUNCTION__,__FILE__,__LINE__);
    }
    return $cache;
}

// Libelle d'un type, avec repli. NE JAMAIS indexer le catalogue directement:
// un type retire produit un "Undefined array key" sous PHP 8, et le null qui
// en resulte remonte jusqu'a un TypeError fatal en aval -- le motif exact qui
// a tue fw.3proxy.acls.php lors du portage Debian 13.
function proxy_task_label($TaskType){
    $c=proxy_catalog();
    $TaskType=intval($TaskType);
    if(isset($c["types"][$TaskType]["label"]) && $c["types"][$TaskType]["label"]!==""){
        return (string)$c["types"][$TaskType]["label"];
    }
    return "{unknown_task_type} ($TaskType)";
}

// Meme regle pour le texte d'explication: repli sur la chaine vide, jamais une
// indexation nue (deux types du catalogue proxy portent un explain vide, et le
// type 2 porte en plus un libelle vide -- l'API lui applique alors le libelle
// de repli, comme a un type absent).
function proxy_task_explain($TaskType){
    $c=proxy_catalog();
    $TaskType=intval($TaskType);
    if(isset($c["types"][$TaskType]["explain"])){
        return (string)$c["types"][$TaskType]["explain"];
    }
    return "";
}

// proxy_schedule_fetch($id): une ligne de webfilters_schedules accompagnee de
// son "label"/"explain" deja calcules par l'API (avec repli sur les types
// inconnus) -- jamais d'indexation manuelle du catalogue a partir d'un
// TaskType lu en base.
function proxy_schedule_fetch($id){
    $j=json_decode((string)$GLOBALS["CLASS_SOCKETS"]->REST_API("/proxy/tasks/schedule/".intval($id)),true);
    if(is_array($j) && !empty($j["success"]) && isset($j["data"]) && is_array($j["data"])){
        return $j["data"];
    }
    return null;
}

// rest_err(): rend "" quand l'appel a reussi, le message d'erreur sinon.
// Repris tel quel de fw.system.tasks.php (phase 1).
function rest_err($resp){
    $r=json_decode((string)$resp,true);
    if(is_array($r)){
        if(isset($r["success"]) && !$r["success"]){
            return tasks_meta_err(isset($r["error"])?(string)$r["error"]:"error");
        }
        if(!empty($r["error"])){ return (string)$r["error"]; }
        // REST_API() (le GET simple, class.sockets.inc ~l.983) repond en cas
        // d'echec de connexion/404/JSON invalide avec des cles CAPITALISEES --
        // Status/Error/JsonFailed -- forme distincte de {"success":false,...}
        // que produisent les handlers Go et les variantes POST/PUT/DELETE.
        // schedule_enable() et schedule_execute() passent des reponses
        // REST_API() dans rest_err(): sans ce test, un daemon injoignable
        // faisait echouer la bascule actif/inactif sans le moindre message.
        if(isset($r["Status"]) && !$r["Status"]){
            return isset($r["Error"])?(string)$r["Error"]:"error";
        }
        if(!empty($r["JsonFailed"])){
            return isset($r["Error"])?(string)$r["Error"]:"error";
        }
        return "";
    }
    // Corps non-JSON: route absente du binaire deploye ("Not Found" en texte
    // brut), 502/504 d'un proxy intermediaire, etc. json_decode() renvoie
    // null, is_array() est faux, et sans cette branche l'appelant n'affichait
    // rien -- la modale se fermait sur une sauvegarde qui n'avait jamais eu
    // lieu. La chaine finit dans un litteral JS (alert()/popup_error()):
    // neutraliser apostrophes et antislashs, et borner la longueur.
    $raw=trim((string)$resp);
    if($raw!=""){
        $raw=str_replace(array("'","\\"), " ", $raw);
        return substr($raw,0,300);
    }
    return "";
}

// proxy_error_text(): message destine aux modales js_confirm_delete/js_confirm_execute.
//
// Elles ne connaissent PAS le protocole "jserror:" de form_outside (le regex
// qui le decode n'existe que la): elles affichent obj.responseText tel quel des
// qu'il depasse 3 caracteres. post_error() y afficherait donc le prefixe
// "jserror:" en toutes lettres. Le prefixe "{failed}" resolu garantit en outre
// de rester au-dessus du seuil des 3 caracteres -- en dessous, l'echec serait
// affiche comme un SUCCES.
function proxy_error_text($tpl,$err){
    return $tpl->_ENGINE_parse_body("{failed}").": ".$err;
}


function microstart():bool{
	$page=CurrentPageName();
    $ForceTaskType=intval($_GET["ForceTaskType"]);
    $jstiny="";

	if($ForceTaskType>0){
        // Catalogue de l'API, jamais $qProxy->tasks_array[$ForceTaskType] sans
        // isset(): un type retire du catalogue laissait un "Undefined array
        // key" puis un titre vide.
        $TINY_ARRAY["TITLE"]="{tasks}: ".proxy_task_label($ForceTaskType);
        $TINY_ARRAY["ICO"]="fa fa-clock";
        $TINY_ARRAY["EXPL"]=proxy_task_explain($ForceTaskType);
        $TINY_ARRAY["URL"]=null;
        $jstiny="Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";
    }

    echo "	
<div class='row'>
		<div id='progress-firehol-restart'></div>
		<div class='ibox-content'>
			<div id='table-proxy-task-loader'></div>
		</div>
</div>	
<script>
	LoadAjax('table-proxy-task-loader','$page?main=yes&ForceTaskType=$ForceTaskType');
    $jstiny
</script>";
	return true;
}


function page(){
	$page=CurrentPageName();
	$tpl=new template_admin();

    $html=$tpl->page_header("{proxy_tasks}","fa fa-clock",
        "{proxy_tasks_explain}","$page?main=yes&ForceTaskType={$_GET["ForceTaskType"]}",
        "proxy-tasks","progress-firehol-restart",false,"table-proxy-task-loader");


	$tpl=new templates();
	echo $tpl->_ENGINE_parse_body($html);

}

function schedule_enable(){
	header("content-type: application/x-javascript");
	$tpl=new template_admin();
	$ID=intval($_GET["enable-task-js"]);
	if(tasks_meta_row(proxy_schedule_fetch($ID))){tasks_meta_refuse_js();return;}
	$resp=$GLOBALS["CLASS_SOCKETS"]->REST_API("/proxy/tasks/toggle/$ID");
	$err=rest_err($resp);
	if($err!=""){
        // Loadjs(): la reponse est executee comme du JS -- un alert('$err')
        // brut n'a jamais ete passe par _ENGINE_parse_body et afficherait un
        // {token} litteral dans toutes les langues. js_error() le resout.
		$tpl->js_error($err);
		return;
	}

    // Trace administrateur, UNIQUEMENT sur succes: la mutation passe par
    // l'API, tracer avant l'appel ou sur le chemin d'erreur consignerait une
    // bascule qui n'a pas eu lieu. L'etat obtenu vient de la reponse
    // (data.enabled), jamais d'une supposition.
    // admin_tracks() n'ecrit RIEN sur la sortie (POST REST + syslog), donc ne
    // pollue pas le JS que Loadjs() va executer.
    $j=json_decode((string)$resp,true);
    $state="toggled";
    if(is_array($j) && isset($j["data"]["enabled"])){
        $state=(intval($j["data"]["enabled"])==1)?"enabled":"disabled";
    }
    $ligne=proxy_schedule_fetch($ID);
    $TaskType=is_array($ligne)?intval($ligne["TaskType"]):0;
    admin_tracks("Proxy task schedule ID $ID (task type $TaskType) as been $state.");
}
function schedule_run_task_js(){
	$ID=intval($_GET["run-task-js"]);
	$tpl=new template_admin();
	$ligne=proxy_schedule_fetch($ID);
	$TaskType=is_array($ligne)?intval($ligne["TaskType"]):0;
	$label=is_array($ligne)?(string)$ligne["label"]:"";
	$taskz=$tpl->javascript_parse_text($label);
	$title="{schedule}::$ID::$TaskType ($taskz)";
	$tpl->js_confirm_execute($title,"execute",$ID);
}

function schedule_execute(){
	$ID=intval($_POST["execute"]);
	$tpl=new template_admin();
	// Le type est lu AVANT l'execution: la trace doit nommer ce qui a ete
	// lance, et /proxy/tasks/run ne renvoie que {"accepted":true,"ID":N}.
	$ligne=proxy_schedule_fetch($ID);
	$TaskType=is_array($ligne)?intval($ligne["TaskType"]):0;

	// Remplace getFrameWork("squid.php?run-scheduled-task=..."), qui passait par
	// exec.squid.php::run_schedules(): celui-ci prefixe inconditionnellement
	// "php <repertoire>/" au processus du type, et rendait donc inoperants les
	// 8 types dont le processus est un binaire (/usr/sbin/artica-launch,
	// /usr/sbin/artica-phpfpm-service) -- une ligne de commande absurde dont la
	// sortie partait dans /dev/null.
	$resp=$GLOBALS["CLASS_SOCKETS"]->REST_API("/proxy/tasks/run/$ID");
	$err=rest_err($resp);
	if($err!=""){ echo proxy_error_text($tpl,$err); return; }

	// Uniquement sur succes: le daemon repond des que la tache est ACCEPTEE, et
	// refuse (400) une tache introuvable, sans processus, desactivee au
	// catalogue ou deja en cours -- tracer avant l'appel consignerait ces refus
	// comme des executions.
	admin_tracks("Proxy task schedule ID $ID (task type $TaskType) as been executed on demand.");
}


function schedule_delete_js(){
	$ID=intval($_GET["delete-task-js"]);
	$tpl=new template_admin();
	$page=CurrentPageName();
	$ligne=proxy_schedule_fetch($ID);
	if(tasks_meta_row($ligne)){tasks_meta_refuse_js();return;}
	$TaskType=is_array($ligne)?intval($ligne["TaskType"]):0;
	$label=is_array($ligne)?(string)$ligne["label"]:"";
	$taskz=$tpl->javascript_parse_text($label);
	$title="{schedule}::$ID::$TaskType ($taskz)";
	$tpl->js_confirm_delete($title,"remove",$ID,"LoadAjax('table-proxy-task-loader','$page?main=yes&ForceTaskType={$_GET["ForceTaskType"]}');");
}
function schedule_remove(){
	$ID=intval($_POST["remove"]);
	$tpl=new template_admin();
	// Le type est lu AVANT la suppression: apres, la ligne n'existe plus et la
	// trace ne pourrait plus nommer ce qui a ete supprime.
	$ligne=proxy_schedule_fetch($ID);
	$TaskType=is_array($ligne)?intval($ligne["TaskType"]):0;
	$resp=$GLOBALS["CLASS_SOCKETS"]->REST_API_DELETE("/proxy/tasks/schedule/$ID");
	$err=rest_err($resp);
	if($err!=""){ echo proxy_error_text($tpl,$err); return; }
	// Uniquement sur succes: supprimer une planification retire silencieusement
	// une mise a jour de categories ou un redemarrage de service.
	admin_tracks("Proxy task schedule ID $ID (task type $TaskType) as been deleted.");
}

function schedule_js(){
	header("content-type: application/x-javascript");
	$page=CurrentPageName();
	$tpl=new template_admin();
	$title=$tpl->javascript_parse_text("{new_schedule}");
	$id=intval($_GET["ID"] ?? 0);
	if($id>0){
		$ligne=proxy_schedule_fetch($id);
		if(tasks_meta_row($ligne)){
			echo "alert(".json_encode(tasks_meta_err(tasks_meta_locked_message())).");";
			return;
		}
		if(is_array($ligne)){
			$TaskType=intval($ligne["TaskType"]);
			$taskz=$tpl->javascript_parse_text((string)$ligne["label"]);
			$title="{schedule}::$id::$TaskType ($taskz)";
		}
	}
	
	$tpl->js_dialog1($title, "$page?AddNewSchedule-popup=yes&ID=$id&ForceTaskType={$_GET["ForceTaskType"]}&ForceType={$_GET["ForceTaskType"]}");
}
function schedule_popup(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$users=new usersMenus();
	$no_schedule_set=null;
	$buttontext="{add}";
	$html=array();
	$form=array();
	$taskz=array();
	if(intval($_GET["ForceTaskType"])>0){$_GET["ForceType"]=$_GET["ForceTaskType"];}
	$_GET["ForceType"]=intval($_GET["ForceType"] ?? 0);
	$ID=intval($_GET["ID"] ?? 0);
	$titleplus=null;
	// $ligne etait lu sans jamais avoir ete defini quand ID vaut 0 (creation):
	// trois "Undefined array key" a chaque ouverture de << nouvelle tache >>
	// sous PHP 8. Les valeurs par defaut sont celles d'une ligne vierge.
	$ligne=array("TaskType"=>0,"TimeText"=>"","TimeDescription"=>"");

	$jsafter="dialogInstance1.close();LoadAjax('table-proxy-task-loader','$page?main=yes&ForceTaskType={$_GET["ForceTaskType"]}');";
	if($ID>0){
		$buttontext="{apply}";
		$row=proxy_schedule_fetch($ID);
		if(is_array($row)){
			$ligne["TaskType"]=intval($row["TaskType"]);
			$ligne["TimeText"]=(string)$row["TimeText"];
			$ligne["TimeDescription"]=$tpl->utf8_encode((string)$row["TimeDescription"]);
		}
		$titleplus="ID:$ID, {type} {$ligne["TaskType"]} &laquo;{$ligne["TimeText"]}&raquo;";
		$jsafter="LoadAjax('table-proxy-task-loader','$page?main=yes&ForceTaskType={$_GET["ForceTaskType"]}');";
		$no_schedule_set="<div class='alert alert-danger'>".$tpl->javascript_parse_text("{no_schedule_set}")."</div>";
	}

	// Catalogue: {id => libelle}, jamais $qProxy->tasks_array (le repli sur un
	// type retire est deja applique cote API).
	$task_type=array();
	foreach(proxy_catalog()["types"] as $tid=>$def){
		$task_type[intval($tid)]=isset($def["label"])?(string)$def["label"]:"";
	}
	unset($task_type[5]);
	unset($task_type[12]);

	if(!$users->UPDATE_UTILITYV2_INSTALLED){
		unset($task_type[13]);
	}


	foreach ($task_type as $TaskType=>$content){
		$taskz[$TaskType]="[{$TaskType}] ".$tpl->_ENGINE_parse_body($content);

	}
	// Une ligne existante dont le type a ete retire du catalogue (ou masque
	// par les unset ci-dessus) ne doit pas se voir retyper par la simple
	// ouverture du formulaire. On reinjecte donc le type courant.
	//
	// Le symptome DEPEND DU RENDU, et c'est le chemin a tester:
	//   - rendu par defaut: field_array_hash est appele ici avec
	//     $notnull=true, ce qui emet un controle client (class.template-admin
	//     .inc l.12326) qui AVORTE la sauvegarde sur "{cannot_be_a_null_value}".
	//     Pas de retypage, mais une impasse pour l'operateur -- le formulaire
	//     refuse de partir sans qu'il puisse choisir son type d'origine;
	//   - StandardDropDown=1: field_array_hash sort par un `return` anticipe
	//     (l.12235) vers field_array_hash_simple($hash,$name,$label,$value,
	//     $OnChange) -- $OnChange (nul ici) atterrit dans le parametre
	//     $notnull, le controle disparait, et la premiere option de cette
	//     fonction est un `<option value="">` que le navigateur selectionne
	//     faute de correspondance. La sauvegarde poste alors TaskType=""
	//     -> intval 0, EN SILENCE.
	// Tester ce garde-fou sous le rendu par defaut ne prouve donc rien.
	if($ID>0 && $ligne["TaskType"]>0 && !isset($taskz[$ligne["TaskType"]])){
		$taskz[$ligne["TaskType"]]="[{$ligne["TaskType"]}] "
			.$tpl->_ENGINE_parse_body(proxy_task_label($ligne["TaskType"]));
	}
	if($_GET["ForceType"]>0){
		$ligne["TaskType"]=$_GET["ForceType"];
		$lbl=isset($task_type[$_GET["ForceType"]])?$task_type[$_GET["ForceType"]]:proxy_task_label($_GET["ForceType"]);
		$taskz=array($_GET["ForceType"]=>$tpl->_ENGINE_parse_body($lbl));
		$titleplus=": <strong>$lbl</strong>";
	}
	
	if($ligne["TimeText"]<>null){$no_schedule_set=null;}
	
	
	$html[]=$no_schedule_set;
	$form[]=$tpl->field_hidden("ID", $ID);
	if($_GET["ForceType"]==0){
		$form[]=$tpl->field_array_hash($taskz, "TaskType", "{task_type}", $ligne["TaskType"],true);
	}else{
		$form[]=$tpl->field_hidden("TaskType", $_GET["ForceType"]);
	}
	$form[]=$tpl->field_text("TimeDescription", "{description}", $ligne["TimeDescription"]);
	$form[]=$tpl->field_schedule("TimeText", "{schedule}", $ligne["TimeText"],true);
	$html[]=$tpl->form_outside("{set_schedule} $titleplus", @implode("\n", $form),
	proxy_task_explain($ligne["TaskType"]),$buttontext,$jsafter,"AsSystemAdministrator");
	echo $tpl->_ENGINE_parse_body(@implode("\n", $html));
}

function schedule_save(){
	$tpl=new template_admin();
	
	$tpl->CLEAN_POST();
	
	// ?? "" sur les trois champs du formulaire: un POST partiel (ou un champ
	// retire du formulaire) produirait sinon trois "Undefined array key" sous
	// PHP 8, dans /var/log/php.log, sur le chemin de sauvegarde.
	$ID=intval($_POST["ID"]);
	$TaskType=intval($_POST["TaskType"] ?? 0);
	$_POST["TimeText"]=(string)($_POST["TimeText"] ?? "");
	$_POST["TimeDescription"]=(string)($_POST["TimeDescription"] ?? "");

	$info=$tpl->javascript_parse_text(proxy_task_label($TaskType));
	$defaultdesc=replace_accents($info);
	if($_POST["TimeDescription"]==null){$_POST["TimeDescription"]=$defaultdesc ." : {$_POST["TimeText"]}";}
	
	// sqlite_escape_string2() n'a plus lieu d'etre: la valeur ne finit plus
	// dans une requete concatenee mais dans un corps JSON, insere cote daemon
	// par une requete parametree. La reussirait un double echappement.
	$_POST["TimeDescription"]=$tpl->CLEAN_BAD_XSS($_POST["TimeDescription"]);

	// TimeText/TimeDescription decodent cote Go dans des *string, TaskType dans
	// un *int (handleCreate/handleUpdate, SystemTasks/http_handlers.go): pas de
	// tag `,string`, donc TaskType reste un entier JSON. `enabled` n'est JAMAIS
	// poste: absent du corps de handleUpdate (une edition ne doit pas
	// reactiver une tache desactivee), et handleCreate le defaut a 1 quand le
	// champ est omis.
	$data=array(
		"TimeText"=>(string)$_POST["TimeText"],
		"TimeDescription"=>(string)$_POST["TimeDescription"],
		"TaskType"=>$TaskType,
	);

	if($ID>0){
		$resp=$GLOBALS["CLASS_SOCKETS"]->REST_API_PUT_JSON("/proxy/tasks/schedule/$ID",$data);
	}else{
		$resp=$GLOBALS["CLASS_SOCKETS"]->REST_API_POST_JSON("/proxy/tasks/schedule",$data);
	}

	// Rien en cas de succes: la modale ne se ferme et la liste ne se rafraichit
	// que sur une reponse de 3 caracteres ou moins (form_outside, res.length>3
	// cote JS -- un "KO"/"-1" brut passerait SOUS ce seuil et serait traite
	// comme une reussite). post_error() prefixe "jserror:", qui declenche une
	// vraie popup, resout les {tokens}, convertit les \n et neutralise les
	// apostrophes -- passer $err tel quel, sans htmlspecialchars().
	$err=rest_err($resp);
	if($err!=""){ echo $tpl->post_error($err); return; }

	// Trace administrateur, uniquement sur succes. Sur une CREATION, l'ID vient
	// de la reponse (handleCreate repond {"data":{"ID":n}}) -- $ID vaut 0 ici,
	// et une trace sans identifiant ne permettrait pas de retrouver la ligne.
	// admin_tracks() n'ECHO rien (POST /localadmins/admintracks + syslog): la
	// regle du "rien en cas de succes" est preservee, une reponse de plus de 3
	// caracteres fermerait la modale sur un faux echec.
	$savedID=$ID;
	if($savedID<1){
		$j=json_decode((string)$resp,true);
		if(is_array($j) && isset($j["data"]["ID"])){$savedID=intval($j["data"]["ID"]);}
	}
	$what=($ID>0)?"modified":"created";
	admin_tracks("Proxy task schedule ID $savedID (task type $TaskType) as been $what: \"{$_POST["TimeText"]}\".");
}


function main():bool{
	$tpl=new template_admin();
	$page=CurrentPageName();

	$description=$tpl->_ENGINE_parse_body("{description}");
	$run=$tpl->_ENGINE_parse_body("{run}");
	$build_config=$tpl->_ENGINE_parse_body("{apply_all_schedules}");
	$enabled=$tpl->_ENGINE_parse_body("{enabled}");
	// intval() est deja fait en tete de fichier; reutilise ici pour la requete
	// API ET pour les URL de la page (aucune reinjection de la valeur brute).
	$ForceTaskType=intval($_GET["ForceTaskType"]);


    // Le journal de details est "<base>.txt", jamais "<progress>.log": c'est le
    // nom que le daemon purge au debut de chaque generation
    // (SystemTasks/builder.go, notifs.BuildLogFile sur
    // strings.TrimSuffix(progressFile,".progress")+".txt") et la convention de la
    // famille systeme ("tasks.compile.progress" / "tasks.compile.txt"). Avec
    // ".progress.log", le bouton "Details" pointait un fichier que personne
    // n'ecrit ni ne purge -- vide en permanence, ou porteur d'une erreur d'une
    // autre epoque.
    $jsrestart=$tpl->framework_buildjs("/proxy/schedule/apply",
        "squid.databases.schedules.progress",
    "squid.databases.schedules.txt",
    "progress-firehol-restart","LoadAjax('table-proxy-task-loader','$page?main=yes&ForceTaskType=$ForceTaskType');");

    //

	if($ForceTaskType==1){
		$DisableCategoriesDatabasesUpdates=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DisableCategoriesDatabasesUpdates"));
		if($DisableCategoriesDatabasesUpdates==1){
			echo $tpl->FATAL_ERROR_SHOW_128("{feature_disabled}");
			return false;
		}
	}

    $jsonStatus=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/proxy/schedule/status"));
	$delete=$tpl->javascript_parse_text("{delete}");
	$new_entry=$tpl->_ENGINE_parse_body("{new_task}");

	$html[]="<div class='awc-wrap'><div class='awc-card'><div class='awc-scroll'><table class='awc-tbl'>";
	$html[]="<thead>";
	$html[]="<tr>";
	$TRCLASS=null;
	$html[]="<th>&nbsp;</th>";
	$html[]="<th class='center'>$description</th>";
	$html[]="<th class='center'>$run</th>";
	$html[]="<th class='center'>$enabled</th>";
	$html[]="<th class='center'>$delete</th>";
	$html[]="</tr>";
	$html[]="</thead>";
	$html[]="<tbody id='tasks-rows'>";


	// Remplace "SELECT * FROM webfilters_schedules WHERE 1 AND TaskType='$x'":
	// le filtre part desormais dans ?tasktype=, sur un entier deja normalise.
	$json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/proxy/tasks/list?tasktype=$ForceTaskType"));
	$results=array();
	if(is_object($json) && isset($json->success) && $json->success && isset($json->data) && is_array($json->data)){
		$results=$json->data;
	}

	$metaRows=0;
	foreach ($results as $ligne){
		$TRCLASS=null;
		// tache Artica Meta : lecture seule (ni modifier, ni supprimer, ni activer), "Executer" garde
		$isMeta=tasks_meta_row($ligne);
		$color="#676A6C";
        $ID=intval($ligne->ID);
		$md=md5(serialize($ligne));
		$enabledRow=intval($ligne->enabled);
		// "label" vient de la reponse (deja calcule + replie cote API): jamais
		// $qProxy->tasks_array[$TaskType], qui donnerait un "Undefined array
		// key" puis un TypeError fatal sur un type retire du catalogue.
        $explainTXT=$tpl->_ENGINE_parse_body($ligne->label);
		$TimeDescription=$ligne->TimeDescription;

		$enable=$tpl->icon_check($enabledRow,"Loadjs('$page?enable-task-js=$ID&ForceTaskType=$ForceTaskType')",$md,"AsSystemAdministrator");
		
		$delete=$tpl->icon_delete("Loadjs('$page?delete-task-js=$ID&ForceTaskType=$ForceTaskType')","AsSystemAdministrator");

		
		$run=$tpl->icon_run("Loadjs('$page?run-task-js=$ID&ForceTaskType=$ForceTaskType')");

        if($enabledRow==0){
            $color="#A0A0A0";
            $run="&nbsp;";
        }
        if($isMeta){
            $metaRows++;
            $enable="&nbsp;";
            $delete="&nbsp;";
        }
		
		// system_tasks::PatternToHuman() est appelee STATIQUEMENT: elle est pure,
		// tandis que le constructeur de la classe va chercher le catalogue de la
		// famille SYSTEME sur /system/tasks/types -- un aller-retour HTTP a chaque
		// rendu de ce tableau, pour un catalogue dont cette page n'utilise rien.
		$TimeText=$tpl->_ENGINE_parse_body(system_tasks::PatternToHuman($ligne->TimeText));
		$TimeText=str_replace("<br>", "", $TimeText);
		// Description stockee (saisie locale ou tache Artica Meta, ecrite sans CLEAN_BAD_XSS) : toujours
		// echappee. ISO-8859-1 = transparent aux octets (UTF-8 ou Latin-1 rendus tels quels), seuls
		// < > & " ' sont convertis ; accolades neutralisees (aucun {jeton} injecte).
		$TimeDescription=str_replace(array("{","}"),array("&#123;","&#125;"),htmlspecialchars((string)$TimeDescription,ENT_QUOTES,"ISO-8859-1"));
		if(preg_match("#(.+?)\s+(.+?)\s+(.+?)\s+(.+?)\s+(.+?)#", $TimeDescription)){$TimeDescription=$TimeText;$TimeText=null;}
		
		$js="Loadjs('$page?AddNewSchedule-js=yes&ID=$ID&ForceTaskType=$ForceTaskType');";

        $explainTXT=$tpl->utf8_encode($explainTXT);
        $status="<span class='label label-default'>N.$ID {inactive2}</span>";
        // is_object() en garde: un daemon injoignable rend null, et
        // property_exists(null,...) est une TypeError fatale sous PHP 8.
        if(is_object($jsonStatus) && property_exists($jsonStatus,"Tasks")) {
            if (isset($jsonStatus->Tasks->{$ID})) {
                $status="<span class='label label-primary'>N.$ID {active2}</span>";
            }
        }
        // skip_reason (daemon) : ligne activee que le generateur ne planifie pas expres (tache Artica Meta
        // de meme type et horaire, script manquant...) -> "non applicable" avec la raison en infobulle.
        $skip=trim((string)($ligne->skip_reason ?? ""));
        if($enabledRow==1 && $skip!=="" && strpos($status,"label-primary")===false){
            list($tk,$arg)=tasks_meta_skip_token($skip);
            $why=$tk!==""?str_replace("%s",$arg,html_entity_decode(strip_tags($tpl->_ENGINE_parse_body("{".$tk."}")),ENT_QUOTES,"UTF-8")):$skip;
            $why=str_replace(array("{","}"),array("&#123;","&#125;"),htmlspecialchars($why,ENT_QUOTES|ENT_SUBSTITUTE,"UTF-8"));
            $status="<span class='label label-default' title=\"$why\">N.$ID {stk_st_notapplicable}</span>";
        }
        if($isMeta){
            // ni td_href ni lien d'edition : jetons resolus ici (la sortie n'est pas reparsee)
            $explainTXT.=$tpl->_ENGINE_parse_body(tasks_meta_badge($ligne));
            $status=$tpl->_ENGINE_parse_body($status);
        }else{
            $explainTXT=$tpl->td_href($explainTXT,"",$js);
            $status=$tpl->td_href($status,"",$js);
        }
		$html[]="<tr class='$TRCLASS' id='$md'>";
		$html[]="<td style='color:$color;width:1%' nowrap>$status</td>";
		$html[]="<td style='color:$color'>$explainTXT<br><i>$TimeDescription</i></td>";
		$html[]="<td style='color:$color;width:1%' class=center nowrap>$run</td>";
		$html[]="<td style='color:$color;width:1%' class=center nowrap>$enable</td>";
		$html[]="<td style='color:$color;width:1%' class=center nowrap>$delete</td>";
		$html[]="</tr>";

	}

	$html[]="</tbody>";
	$html[]="";
	$banner=tasks_meta_banner("proxy",$tpl,$metaRows>0);
	if($banner!==""){array_unshift($html,$banner);}
	$html[]="</table></div><div class='awc-foot'><span>&nbsp;</span><span class='awc-pg' id='tasks-pager'></span></div></div></div>";


    $topbuttons[] = array("Loadjs('$page?AddNewSchedule-js=yes&ID=0&ForceTaskType=$ForceTaskType');", ico_plus, $new_entry);
    $topbuttons[] = array($jsrestart, ico_save, $build_config);
    $TINY_ARRAY["TITLE"]="{proxy_tasks}";
    $TINY_ARRAY["ICO"]=ico_clock_desk;
    $TINY_ARRAY["EXPL"]="{proxy_tasks_explain}";
    $TINY_ARRAY["BUTTONS"]=$tpl->table_buttons($topbuttons);
    $jstiny="Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";

	$html[]="
	<script>
	NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS)."
    $jstiny
</script>";

	echo awc_table_style()
	    .@implode("\n", $html)
	    .awc_pager_js($GLOBALS["FOOTABLE_PSIZE"],"tasks-rows","tasks-pager");
    return true;
}
