<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
if(!isset($GLOBALS["CLASS_SOCKETS"])){
    if(!class_exists("sockets")){
        include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");
    }
    $GLOBALS["CLASS_SOCKETS"]=new sockets();
}

include_once(dirname(__FILE__)."/ressources/class.tasks.inc");
// taches posees par Artica Meta (politique systemtasks) : badge, lecture seule, bandeau
include_once(dirname(__FILE__)."/ressources/class.systemtasks.meta.inc");
// awc_table_style() / awc_pager_js(): feuille de style et pager partages des tableaux d'entites
include_once(dirname(__FILE__)."/ressources/class.statistics_appliance.inc");
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
// ForceTaskType est un identifiant numerique: on le normalise une seule fois,
// ce qui couvre tous les usages en aval (URL Loadjs() et parametre ?tasktype=
// de l'API, qui a remplace le litteral SQL d'origine).
$_GET["ForceTaskType"]=intval($_GET["ForceTaskType"] ?? 0);
// Pentest 2026-11-04 M-01 : la route renvoyait le HTML recu ; elle n'est
// appelee qu'a vide (pour effacer la zone), elle ne renvoie donc plus rien.
if(isset($_GET["btns-js"])){exit;}
if(isset($_GET["main"])){main();exit;}
if(isset($_GET["AddNewSchedule-js"])){schedule_js();exit;}
if(isset($_GET["AddNewSchedule-popup"])){schedule_popup();exit;}
if(isset($_GET["enable-task-js"])){schedule_enable();exit;}
if(isset($_GET["delete-task-js"])){schedule_delete_js();exit;}
if(isset($_GET["run-task-js"])){schedule_run_task_js();exit;}
if(isset($_POST["ID"])){schedule_save();exit;}
if(isset($_POST["remove"])){schedule_remove();exit;}
if(isset($_POST["execute"])){schedule_execute();exit;}
if(isset($_GET["sub-main"])){sub_page();exit;}
if(isset($_GET["microstart"])){microstart();exit;}
if(isset($_GET["type-catalog-js"])){type_catalog_js();exit;}
if(isset($_GET["type-catalog"])){type_catalog();exit;}

page();

// ---------- Petits relais REST partages par toutes les fonctions ci-dessous ----------

// rest_types(): catalogue {id => ["label"=>..,"explain"=>..,"process"=>..,"direct_run"=>..]}.
// Remplace toute lecture de $tasks->tasks_array/$tasks->tasks_explain_array
// dans ce fichier: un type retire du catalogue ne doit jamais provoquer un
// "Undefined array key" puis un TypeError fatal sous PHP 8.
function rest_types(){
    $j=json_decode((string)$GLOBALS["CLASS_SOCKETS"]->REST_API("/system/tasks/types"),true);
    if(is_array($j) && !empty($j["success"]) && isset($j["data"]["types"]) && is_array($j["data"]["types"])){
        return $j["data"]["types"];
    }
    return array();
}

// schedule_fetch($id): une ligne + son "label"/"explain" deja calcules par
// l'API (avec repli sur les types inconnus) -- jamais d'indexation manuelle
// de tasks_array a partir d'un TaskType lu en base.
function schedule_fetch($id){
    $j=json_decode((string)$GLOBALS["CLASS_SOCKETS"]->REST_API("/system/tasks/schedule/$id"),true);
    if(is_array($j) && !empty($j["success"]) && isset($j["data"])){
        return $j["data"];
    }
    return null;
}

// rest_err(): n'affiche jamais rien en cas de succes (schedule_save() ne doit
// rien echouer que le message d'erreur -- la modale ne se ferme et la liste
// ne se rafraichit que sur une reponse de 3 caracteres ou moins).
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
        // schedule_enable() est la seule fonction a passer une reponse
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

// system_error_text(): message destine aux modales js_confirm_delete/js_confirm_execute.
//
// Elles ne connaissent PAS le protocole "jserror:" de form_outside (le regex
// qui le decode n'existe que la): elles affichent obj.responseText tel quel des
// qu'il depasse 3 caracteres, dans un swal "Oops...". post_error() y afficherait
// donc le prefixe "jserror:" en toutes lettres. Le prefixe "{failed}" resolu
// garantit en outre de rester au-dessus du seuil des 3 caracteres -- en dessous,
// l'echec serait affiche comme un SUCCES.
//
// Jumeau de proxy_error_text() dans fw.proxy.tasks.php: les deux pages doivent
// signaler un echec de la meme facon.
function system_error_text($tpl,$err){
    return $tpl->_ENGINE_parse_body("{failed}").": ".$err;
}


function microstart(){
    $page=CurrentPageName();


    echo "<div class='row'>
		<div id='progress-firehol-restart'></div>
		<div class='ibox-content'>
			<div id='table-system-task-loader'></div>
		</div>
	</div>	<script>
	LoadAjax('table-system-task-loader','$page?main=yes&ForceTaskType={$_GET["ForceTaskType"]}');

	</script>";

}

function page(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $explain="{system_tasks_text}";
    $title="{system_tasks}";
    $ForceTaskType=intval($_GET["ForceTaskType"]);
    if($ForceTaskType>0){
        // Catalogue de l'API, jamais $tt->tasks_array/tasks_explain_array
        // sans isset(): un type retire y laisserait $title/$explain vides en
        // silence pendant que le tableau afficherait le bon libelle.
        $catalog=rest_types();
        if(isset($catalog[$ForceTaskType]["label"]) && $catalog[$ForceTaskType]["label"]!==""){
            $title=(string)$catalog[$ForceTaskType]["label"];
        }
        if(isset($catalog[$ForceTaskType]["explain"]) && $catalog[$ForceTaskType]["explain"]!==""){
            $explain=(string)$catalog[$ForceTaskType]["explain"];
        }
    }



    $html=$tpl->page_header($title,
        "fa fa-clock",$explain,
        "$page?main=yes&ForceTaskType={$_GET["ForceTaskType"]}",
        "system-tasks","progress-firehol-restart",false,"table-system-task-loader"
    );

    $tpl=new template_admin("System Tasks",$html);
    if(isset($_GET["main-page"])){
        echo $tpl->build_firewall();
        return;
    }


    echo $tpl->_ENGINE_parse_body($html);

}

function btns(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $taskz=null;
    $tasks=new system_tasks();
    $ForceTaskType=0;
    if(isset($_GET["ForceTaskType"])) {
        $ForceTaskType = intval($_GET["ForceTaskType"]);
    }
    if($ForceTaskType>0){
        if(isset($tasks->tasks_array[$ForceTaskType ])) {
            $taskz = $tpl->_ENGINE_parse_body($tasks->tasks_array[$ForceTaskType]);
        }
    }

    // /system/tasks/build: RUN_CMD() route vers REST_API() des qu'une commande
    // commence par une barre oblique. Vise sur l'ancien services.php, le
    // framework preremplirait tasks.compile.progress a 2% avant de lancer un
    // worker PHP qui n'existe plus, et le dialogue resterait bloque la.
    $jsrestart=stk_build_js($ForceTaskType);

    $new_entry=$tpl->_ENGINE_parse_body("{new_task}");
    if(strlen($taskz)>1){
            $new_entry = $new_entry . " &raquo; $taskz";
    }

    $btns=$tpl->_ENGINE_parse_body("
			<div class=\"btn-group\" data-toggle=\"buttons\">
			<label class=\"btn btn btn-primary\" OnClick=\"Loadjs('$page?AddNewSchedule-js=yes&ID=0&ForceTaskType=$ForceTaskType');\"><i class='fa fa-plus'></i> $new_entry </label>
			<label class=\"btn btn btn-info\" title=\"{stk_apply_help}\" OnClick=\"$jsrestart\"><i class='fa fa-arrows-rotate'></i> {stk_apply} </label>
			</div>");
    return $btns;
}

/**
 * Fenetre du catalogue des types de tache (bouton du champ d'en-tete).
 *
 * Remplace la liste deroulante de 80 options qui saturait l'URL du TINY (voir le
 * commentaire dans main()). Le catalogue n'est charge que si l'administrateur
 * l'ouvre, et il tient dans un fragment: aucune limite d'URL en jeu.
 */
function type_catalog_js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    return $tpl->js_dialog2("{task_type}",
        "$page?type-catalog=yes&ForceTaskType=".intval($_GET["ForceTaskType"]),720);
}

/** Contenu de la fenetre: la liste des types, cherchable, une ligne par type. */
function type_catalog(){
    $tpl=new template_admin();
    $cur=intval($_GET["ForceTaskType"]);
    $types=rest_types();

    $items=array(array("id"=>0,"label"=>$tpl->_ENGINE_parse_body("{all}"),"process"=>""));
    foreach($types as $tid=>$t){
        $tid=intval($tid);
        $lbl=isset($t["label"])?(string)$t["label"]:"";
        // ECHAPPER D'ABORD, ANALYSER ENSUITE -- et pas l'inverse. La base de
        // traduction range les accents en entites; echapper une sortie du moteur
        // afficherait « Ao&ucirc;t » en toutes lettres. Un libelle qui EST un
        // {jeton} garde ses accolades a travers htmlspecialchars().
        $txt=trim($tpl->_ENGINE_parse_body(htmlspecialchars($lbl)));
        // Le catalogue livre au moins un type sans libelle (75): afficher son
        // identifiant plutot qu'une ligne vide sur laquelle on ne peut pas cliquer.
        if($txt==""){ $txt="#$tid"; }
        $items[]=array("id"=>$tid,"label"=>$txt,
            "process"=>isset($t["process"])?(string)$t["process"]:"");
    }
    // Tri sur le libelle affiche: une liste qu'on parcourt des yeux, pas des
    // identifiants historiques. « Tous » reste en tete.
    $all=array_shift($items);
    usort($items,function($a,$b){ return strcasecmp($a["label"],$b["label"]); });
    array_unshift($items,$all);

    $rows=array();
    foreach($items as $it){
        $id=intval($it["id"]);
        $badge=($id==$cur)?"<span class='awc-badge awc-badge-on'>{current}</span>":"";
        $meta=($it["process"]<>"")?"<div class='awc-meta'>".htmlspecialchars($it["process"])."</div>":"";
        $h ="<tr>";
        $h.="<td><div class='awc-ident'><span class='awc-ico'><i class='fa fa-clock'></i></span>"
           ."<span><span class='awc-name'><a href=\"javascript:blur();\" OnClick=\"TskTypeSet($id)\">"
           .$it["label"]."</a></span>$meta</span></div></td>";
        $h.="<td class='awc-right awc-nowrap awc-mono'>".(($id>0)?"#$id":"")."</td>";
        $h.="<td class='center awc-nowrap'>$badge</td>";
        $h.="</tr>";
        $rows[]=$h;
    }

    $html=array();
    $html[]="<div style='margin-top:6px'>".awc_search_bar("tsk-cat-search","{search}")."</div>";
    $html[]="<div class='awc-card'><div class='awc-scroll'><table class='awc-tbl'><thead><tr>";
    $html[]="<th>{task_type}</th><th class='awc-right'>ID</th><th class='center'>&nbsp;</th>";
    $html[]="</tr></thead><tbody id='tsk-cat-rows'>";
    $html[]=@implode("\n",$rows);
    $html[]="</tbody></table></div>";
    $html[]="<div class='awc-foot'><span>{showing} ".count($rows)."</span>"
        ."<span class='awc-pg' id='tsk-cat-pager'></span></div></div>";

    // Feuilles et blocs JS concatenes AUTOUR de _ENGINE_parse_body(): sa regex
    // #{(.+?)}# lit chaque corps de regle CSS et chaque bloc d'accolades comme un
    // {jeton}. Le filtre vient APRES le pager, auquel il rend la main.
    echo awc_table_style()
        .$tpl->_ENGINE_parse_body(@implode("\n",$html))
        ."<script>if(typeof NoSpinner==='function'){NoSpinner();}</script>"
        .awc_pager_js(25,"tsk-cat-rows","tsk-cat-pager")
        .awc_filter_js("tsk-cat-search","tsk-cat-rows","tsk-cat-pager");
}

function sub_page(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $taskz="{system_tasks}";$task_explain="{system_tasks_text}";
    $ForceTaskType=intval($_GET["ForceTaskType"]);
    if($ForceTaskType>0){
        // Meme regle que page(): catalogue de l'API, jamais tasks_array/
        // tasks_explain_array sans isset().
        $catalog=rest_types();
        if(isset($catalog[$ForceTaskType]["label"]) && $catalog[$ForceTaskType]["label"]!==""){
            $taskz=$tpl->_ENGINE_parse_body((string)$catalog[$ForceTaskType]["label"]);
        }
        if(isset($catalog[$ForceTaskType]["explain"]) && $catalog[$ForceTaskType]["explain"]!==""){
            $task_explain=$tpl->_ENGINE_parse_body((string)$catalog[$ForceTaskType]["explain"]);
        }
    }


    $btns=btns();
    $TINY_ARRAY["TITLE"]=$taskz;
    $TINY_ARRAY["ICO"]="fa fa-clock";
    $TINY_ARRAY["EXPL"]=$task_explain;
    $TINY_ARRAY["URL"]="system-tasks";
    $TINY_ARRAY["BUTTONS"]=$btns;
    $jstiny="Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";



    echo "<div class='row' style='margin-top:10px'>
		<div id='progress-firehol-restart'></div>
		<div class='ibox-content'>
			<div id='table-system-task-loader'></div>
		</div>
	</div>



	<script>
	LoadAjax('table-system-task-loader','$page?main=yes&ForceTaskType={$_GET["ForceTaskType"]}');
    $jstiny;
	</script>";

}

function schedule_enable(){
    header("content-type: application/x-javascript");
    $tpl=new template_admin();
    $ID=intval($_GET["enable-task-js"]);
    if(tasks_meta_row(schedule_fetch($ID))){tasks_meta_refuse_js();return;}
    $resp=$GLOBALS["CLASS_SOCKETS"]->REST_API("/system/tasks/toggle/$ID");
    $err=rest_err($resp);
    if($err!=""){
        // Loadjs(): la reponse est executee comme du JS -- un alert('$err')
        // brut n'a jamais ete passe par _ENGINE_parse_body et afficherait un
        // {token} litteral dans toutes les langues. js_error() le resout.
        $tpl->js_error($err);
        return;
    }

    // Trace administrateur -- UNIQUEMENT sur succes: la mutation passe par
    // l'API, tracer avant l'appel ou sur le chemin d'erreur consignerait une
    // bascule qui n'a pas eu lieu. L'etat obtenu vient de la reponse
    // (data.enabled), jamais d'une supposition: reactiver une planification
    // que l'operateur avait desactivee doit etre imputable, et le catalogue
    // contient un type qui redemarre l'appliance.
    // admin_tracks() n'ecrit RIEN sur la sortie (POST REST + syslog), donc ne
    // pollue pas le JS que Loadjs() va executer.
    $j=json_decode((string)$resp,true);
    $state="toggled";
    if(is_array($j) && isset($j["data"]["enabled"])){
        $state=(intval($j["data"]["enabled"])==1)?"enabled":"disabled";
    }
    $ligne=schedule_fetch($ID);
    $TaskType=is_array($ligne)?intval($ligne["TaskType"]):0;
    admin_tracks("System task schedule ID $ID (task type $TaskType) as been $state.");
}
function schedule_run_task_js(){
    $ID=intval($_GET["run-task-js"]);
    $tpl=new template_admin();
    $ligne=schedule_fetch($ID);
    $TaskType=is_array($ligne)?intval($ligne["TaskType"]):0;
    $label=is_array($ligne)?(string)$ligne["label"]:"";
    $taskz=$tpl->javascript_parse_text($label);
    $title="<strong>".htmlspecialchars($taskz,ENT_QUOTES,"UTF-8",false)."</strong><br><br>{stk_run_confirm}";
    $tpl->js_confirm_execute($title,"execute",$ID,"setTimeout(function(){if(window.StkReload){StkReload();}},3000);");
}

function schedule_execute(){
    $ID=intval($_POST["execute"]);
    $tpl=new template_admin();
    // Le type est lu AVANT l'execution: la trace doit nommer ce qui a ete
    // lance, et /system/tasks/run ne renvoie que {"accepted":true,"ID":N}.
    $ligne=schedule_fetch($ID);
    $TaskType=is_array($ligne)?intval($ligne["TaskType"]):0;
    $resp=$GLOBALS["CLASS_SOCKETS"]->REST_API("/system/tasks/run/$ID");
    // Rien n'est affiche EN SUCCES (une reponse de 3 caracteres ou moins ferme
    // la modale sur le dialogue de reussite). En ECHEC, le message est affiche:
    // la modale montre toute reponse de plus de 3 caracteres dans un swal
    // "Oops...", ce qui est exactement le but. Rendre la main en silence
    // affichait le dialogue de SUCCES sur une execution que le daemon venait de
    // refuser -- tache introuvable, type desactive au catalogue, ou deja en
    // cours (il repond 400 dans ces trois cas).
    $err=rest_err($resp);
    if($err!=""){ echo system_error_text($tpl,$err); return; }
    // Uniquement sur succes: le daemon repond des que la tache est ACCEPTEE,
    // et refuse (400) une tache introuvable, desactivee au catalogue ou deja
    // en cours -- tracer avant l'appel consignerait ces refus comme des
    // executions.
    admin_tracks("System task schedule ID $ID (task type $TaskType) as been executed on demand.");
}


function schedule_delete_js(){
    $ID=intval($_GET["delete-task-js"]);
    $tpl=new template_admin();
    $page=CurrentPageName();
    $ligne=schedule_fetch($ID);
    if(tasks_meta_row($ligne)){tasks_meta_refuse_js();return;}
    $TaskType=is_array($ligne)?intval($ligne["TaskType"]):0;
    $label=is_array($ligne)?(string)$ligne["label"]:"";
    $taskz=$tpl->javascript_parse_text($label);
    $title="{delete_this_item}:<br><strong>".htmlspecialchars($taskz,ENT_QUOTES,"UTF-8",false)."</strong> (ID $ID)<br><br>{stk_delete_confirm}";
    $tpl->js_confirm_delete($title,"remove",$ID,"LoadAjax('table-system-task-loader','$page?main=yes&ForceTaskType={$_GET["ForceTaskType"]}');",true);
}
function schedule_remove(){
    $ID=intval($_POST["remove"]);
    $tpl=new template_admin();
    // Le type est lu AVANT la suppression: apres, la ligne n'existe plus et la
    // trace ne pourrait plus nommer ce qui a ete supprime.
    $ligne=schedule_fetch($ID);
    $TaskType=is_array($ligne)?intval($ligne["TaskType"]):0;
    $resp=$GLOBALS["CLASS_SOCKETS"]->REST_API_DELETE("/system/tasks/schedule/$ID");
    // L'echec DOIT etre affiche: rendre la main en silence laissait la modale
    // annoncer le SUCCES sur une suppression qui n'avait pas eu lieu -- et la
    // ligne, toujours en base, reapparaissait au rafraichissement suivant sans
    // que rien n'explique pourquoi.
    $err=rest_err($resp);
    if($err!=""){ echo system_error_text($tpl,$err); return; }
    // Uniquement sur succes: supprimer une planification retire silencieusement
    // la sauvegarde ou le watchdog de l'appliance.
    admin_tracks("System task schedule ID $ID (task type $TaskType) as been deleted.");
}

function schedule_js(){
    header("content-type: application/x-javascript");
    $page=CurrentPageName();
    $tpl=new template_admin();
    $title=$tpl->javascript_parse_text("{new_schedule}");
    $id=intval($_GET["ID"]);
    if($id>0){
        $ligne=schedule_fetch($id);
        if(tasks_meta_row($ligne)){
            echo "alert(".json_encode(tasks_meta_err(tasks_meta_locked_message())).");";
            return;
        }
        if(is_array($ligne)){
            $TaskType=intval($ligne["TaskType"]);
            $taskz=$tpl->javascript_parse_text((string)$ligne["label"]);
            $title="{set_schedule}::$id::$TaskType ($taskz)";
        }

    }

    $tpl->js_dialog1($title, "$page?AddNewSchedule-popup=yes&ID=$id&ForceTaskType={$_GET["ForceTaskType"]}&ForceType={$_GET["ForceTaskType"]}");
}
function schedule_popup(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $users=new usersMenus();
    $explain_text="";
    $explain=array();
    $taskz=array();
    $no_schedule_set=null;
    $buttontext="{add}";
    if(isset($_GET["ForceTaskType"])){
        if(intval($_GET["ForceTaskType"])>0){$_GET["ForceType"]=$_GET["ForceTaskType"];}
    }
    if(!isset($_GET["ForceType"])){$_GET["ForceType"]=0;}
    if(!is_numeric($_GET["ForceType"])){$_GET["ForceType"]=0;}
    // Pentest 2026-11-04 : ForceType etait recopie sans encodage dans la page (XSS) ; is_numeric() tolere des blancs.
    $_GET["ForceType"]=intval($_GET["ForceType"]);
    $ID=intval($_GET["ID"]);
    $titleplus=null;
    $ligne=array("TaskType"=>0,"TimeText"=>"","TimeDescription"=>"");

    // Catalogue: {id => label/explain}, jamais $tasks->tasks_array (repli
    // deja applique cote API pour un type retire).
    $catalog=rest_types();
    $task_type=array();
    $task_explain=array();
    foreach ($catalog as $tid=>$def){
        $task_type[intval($tid)]=isset($def["label"])?(string)$def["label"]:"";
        $task_explain[intval($tid)]=isset($def["explain"])?(string)$def["explain"]:"";
    }

    if(!$users->UPDATE_UTILITYV2_INSTALLED){
        unset($task_type[13]);
    }

    $jsafter="dialogInstance1.close();LoadAjax('table-system-task-loader','$page?main=yes&ForceTaskType={$_GET["ForceTaskType"]}');";

    if($ID>0){
        $buttontext="{apply}";
        $row=schedule_fetch($ID);
        if(is_array($row)){
            $ligne["TaskType"]=intval($row["TaskType"]);
            $ligne["TimeText"]=(string)$row["TimeText"];
            $ligne["TimeDescription"]=$tpl->utf8_encode((string)$row["TimeDescription"]);
        }
        $titleplus="ID:$ID, {type} {$ligne["TaskType"]} &laquo;{$ligne["TimeText"]}&raquo;";
        $jsafter="LoadAjax('table-system-task-loader','$page?main=yes&ForceTaskType={$_GET["ForceTaskType"]}');";
        $no_schedule_set="<div class='alert alert-danger'>".$tpl->javascript_parse_text("{no_schedule_set}")."</div>";

    }

    if(!is_numeric($ligne["TaskType"])){$ligne["TaskType"]=0;}

    foreach ($task_type as $TaskType=>$content){
        $taskz[$TaskType]="[$TaskType] ".$tpl->_ENGINE_parse_body($content);

    }
    if($_GET["ForceType"]>0){
        $ligne["TaskType"]=$_GET["ForceType"];
        unset($taskz);
        $taskz[$_GET["ForceType"]]=isset($task_type[$_GET["ForceType"]])?$tpl->_ENGINE_parse_body($task_type[$_GET["ForceType"]]):"";
        $titleplus=": <strong>".(isset($task_type[$_GET["ForceType"]])?$task_type[$_GET["ForceType"]]:"")."</strong>";
    }

    if($ligne["TimeText"]<>null){$no_schedule_set=null;}


    $html[]=$no_schedule_set;
    $form[]=$tpl->field_hidden("ID", $ID);
    if($_GET["ForceType"]==0){
        $form[]=$tpl->field_array_hash($taskz, "TaskType", "{task_type}", $ligne["TaskType"],true);
    }else{
        $form[]=$tpl->field_hidden("TaskType", $_GET["ForceType"]);
        if($ligne["TimeDescription"]==null || $ligne["TimeDescription"]==""){
            $lbl=isset($task_type[$_GET["ForceType"]])?$task_type[$_GET["ForceType"]]:"";
            $ligne["TimeDescription"]="Task:{$_GET["ForceType"]} ".$tpl->_ENGINE_parse_body($lbl)." {time}:".date("Y-m-d H:i");
        }
    }
    $form[]=$tpl->field_text("TimeDescription", "{description}", $ligne["TimeDescription"]);
    $form[]=$tpl->field_schedule("TimeText", "{schedule}", $ligne["TimeText"],true);



    $explain[]= isset($task_explain[$ligne["TaskType"]])?$task_explain[$ligne["TaskType"]]:"";
    if($ligne["TimeText"]<>null){
        $tsk=new system_tasks();
        $explain[]="<strong>".$tsk->PatternToHuman($ligne["TimeText"],true)."</strong>";
    }

    if(count($explain)>0) {
        $explain_text = @implode("<br>", $explain);
    }

    if(!is_null($titleplus) && strlen($titleplus)>1){
        $html[]=$tpl->div_explain("$titleplus||$explain_text");
    }


    $html[]=$tpl->form_outside("",  $form,""
       ,$buttontext,$jsafter,"AsSystemAdministrator");
    echo $tpl->_ENGINE_parse_body(@implode("\n", $html));
}

function schedule_save(){
    $tpl=new template_admin();
    $tpl->CLEAN_POST();

    $TaskType=intval($_POST["TaskType"]);
    $catalog=rest_types();
    $label=isset($catalog[$TaskType]["label"])?(string)$catalog[$TaskType]["label"]:"";
    $info=$tpl->javascript_parse_text($label);
    $defaultdesc=replace_accents($info);
    if($_POST["TimeDescription"]==null){$_POST["TimeDescription"]=$defaultdesc ." : {$_POST["TimeText"]}";}

    $_POST["TimeDescription"]=$tpl->CLEAN_BAD_XSS($_POST["TimeDescription"]);

    $ID=intval($_POST["ID"]);

    // TimeText/TimeDescription decodent cote Go dans des *string (Schedule):
    // on poste des chaines PHP, forme JSON native. TaskType decode dans un
    // *int -- verifie sur SystemTasks/http_handlers.go (handleCreate/
    // handleUpdate), pas de tag `,string` -- donc il reste un entier JSON, pas
    // strval()e comme les deux autres champs. `enabled` n'est JAMAIS poste:
    // absent du body de handleUpdate (l'API ne reecrit pas cette colonne, une
    // edition ne doit jamais desactiver une tache active), et handleCreate le
    // defaut deja a 1 quand le champ est omis.
    $data=array(
        "TimeText"=>(string)$_POST["TimeText"],
        "TimeDescription"=>(string)$_POST["TimeDescription"],
        "TaskType"=>$TaskType,
    );

    if($ID>0){
        $resp=$GLOBALS["CLASS_SOCKETS"]->REST_API_PUT_JSON("/system/tasks/schedule/$ID",$data);
    }else{
        $resp=$GLOBALS["CLASS_SOCKETS"]->REST_API_POST_JSON("/system/tasks/schedule",$data);
    }

    // Rien en cas de succes: la modale ne se ferme et la liste ne se
    // rafraichit que sur une reponse de 3 caracteres ou moins (form_outside,
    // res.length>3 cote JS -- un "KO"/"-1" brut passerait SOUS ce seuil et
    // serait traite comme une reussite). post_error() prefixe "jserror:" qui
    // declenche une vraie popup, resout les {tokens}, convertit les \n et
    // neutralise les apostrophes -- passer $err tel quel, sans
    // htmlspecialchars().
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
    admin_tracks("System task schedule ID $savedID (task type $TaskType) as been $what: \"{$_POST["TimeText"]}\".");
}


// Liste des taches (maquette stitch_system_tasks_console_redesign). Prefixe stk_ (tokens, CSS, JS).
// Donnees : /system/tasks/list (taches), /system/tasks/types (processus), /system/tasks/events?limit=2000
// (derniere execution : le runner ecrit "<label> (task <ID>, <source>): success in 3s" ou
// "... failed after 12s: <erreur>"). Aucune "prochaine execution" : aucune route ne la donne.
function main(){
    $tpl=new template_admin();
    $page=CurrentPageName();
    $ForceTaskType=intval($_GET["ForceTaskType"] ?? 0);
    $m=stk_model($ForceTaskType);
    $h=array();
    $h[]="<div class='stk' id='stk-root'>";
    if($m["error"]!==""){
        $h[]=stk_banner_error($m["error"]);
    }else{
        $h[]=stk_kpis($m);
        $h[]=tasks_meta_banner("system",$tpl,$m["meta"]>0);
        $h[]=stk_banner_notinstalled($m);
        if(count($m["tasks"])==0){
            $h[]=stk_empty($ForceTaskType);
        }else{
            $h[]=stk_toolbar();
            $h[]=stk_table($m,$ForceTaskType);
        }
    }
    $h[]="</div>";
    echo stk_style().$tpl->_ENGINE_parse_body(implode("\n",$h)).stk_js().stk_tiny_js($m,$ForceTaskType);
}

function stk_model(int $type):array{
    $m=array("error"=>"","tasks"=>array(),"events_known"=>false,"meta"=>0,"total"=>0,"scheduled"=>0,"failed"=>0,"notinstalled"=>0,"types"=>rest_types());
    $j=json_decode((string)$GLOBALS["CLASS_SOCKETS"]->REST_API("/system/tasks/list?tasktype=$type"),true);
    if(!is_array($j) || empty($j["success"])){
        $m["error"]=is_array($j)?(string)($j["error"] ?? "articarest"):"articarest";
        return $m;
    }
    $last=stk_last_runs($m);
    $sched=new system_tasks();
    $list=is_array($j["data"] ?? null)?$j["data"]:array();
    foreach($list as $t){
        if(!is_array($t)){continue;}
        $id=intval($t["ID"] ?? 0);
        $tt=intval($t["TaskType"] ?? 0);
        $en=intval($t["enabled"] ?? 0)==1;
        $inst=!empty($t["installed"]);
        $cron=trim((string)($t["TimeText"] ?? ""));
        $desc=trim((string)($t["TimeDescription"] ?? ""));
        if(preg_match('#^\S+\s+\S+\s+\S+\s+\S+\s+\S+$#',$desc)){$desc="";}
        $tplh=new template_admin();
        $human=$cron!==""?trim(preg_replace('/\s+/',' ',strip_tags(str_replace("<br>"," ",$tplh->_ENGINE_parse_body($sched->PatternToHuman($cron)))))):"";
        // skip_reason (daemon) : le generateur ecarte cette ligne expres (pas de commande, inutile sur
        // cette appliance...). Seule une ligne SANS raison et sans fichier cron est "non installee" (RWE 15/11).
        $skip=trim((string)($t["skip_reason"] ?? ""));
        $st=!$en?"disabled":($inst?"scheduled":($skip!==""?"notapplicable":"notinstalled"));
        $r=array("id"=>$id,"type"=>$tt,"label"=>stk_tr($tplh,(string)($t["label"] ?? "#$tt")),"explain"=>stk_tr($tplh,(string)($t["explain"] ?? "")),"skip"=>$skip,
            "process"=>(string)($m["types"][$tt]["process"] ?? ""),"desc"=>$desc,"cron"=>$cron,"human"=>$human,
            "enabled"=>$en,"status"=>$st,"unknown"=>!empty($t["unknown_type"]),"last"=>$last[$id] ?? null,
            "meta"=>tasks_meta_row($t));
        if($r["meta"]){$m["meta"]++;}
        $m["total"]++;
        if($st=="scheduled"){$m["scheduled"]++;}
        if($st=="notinstalled"){$m["notinstalled"]++;}
        if(is_array($r["last"]) && !$r["last"]["ok"]){$m["failed"]++;}
        $m["tasks"][]=$r;
    }
    return $m;
}

// Derniere execution par ID de tache, tiree du sujet structure des evenements (le plus recent d'abord).
function stk_last_runs(array &$m):array{
    $out=array();
    $j=json_decode((string)$GLOBALS["CLASS_SOCKETS"]->REST_API("/system/tasks/events?limit=2000"),true);
    if(!is_array($j) || empty($j["success"])){return $out;}
    $m["events_known"]=true;
    $list=is_array($j["data"] ?? null)?$j["data"]:array();
    foreach($list as $e){
        if(!is_array($e)){continue;}
        $s=(string)($e["subject"] ?? "");
        if(!preg_match('/\(task (\d+), ([a-z0-9_-]+)\): (success in (\S+)|failed after (\S+?):\s*(.*))$/s',$s,$re)){continue;}
        $id=intval($re[1]);
        if(isset($out[$id])){continue;}
        $ok=strpos($re[3],"success")===0;
        $out[$id]=array("ts"=>intval($e["zdate"] ?? 0),"source"=>$re[2],"ok"=>$ok,
            "took"=>$ok?$re[4]:$re[5],"err"=>$ok?"":trim((string)($re[6] ?? "")));
    }
    return $out;
}

function stk_t(string $k,array $args=array()):string{
    $tpl=new template_admin();
    $s=trim(preg_replace('/\s+/',' ',str_replace(array("[br]","<br>")," ",$tpl->_ENGINE_parse_body("{".$k."}"))));
    foreach($args as $a){
        $p=strpos($s,"%s");
        if($p===false){break;}
        $s=substr($s,0,$p).$a.substr($s,$p+2);
    }
    return $s;
}

// Libelle du catalogue ("{check_system_updates}") -> texte de la langue. stk_h() echappe ensuite les
// accolades : sans cette traduction prealable, la page affichait les jetons bruts (RWE 15/11).
function stk_tr($tpl,string $s):string{
    if(strpos($s,"{")===false){return $s;}
    return trim(html_entity_decode(strip_tags(str_replace(array("[br]","<br>")," ",$tpl->_ENGINE_parse_body($s))),ENT_QUOTES,"UTF-8"));
}

// Raison du daemon (anglais) -> phrase traduite pour les cas connus, sinon la raison telle quelle.
function stk_skip_text(string $r):string{
    // raisons Artica Meta (le nom du script est echappe par stk_h() a l'affichage)
    list($tk,$arg)=tasks_meta_skip_token($r);
    if($tk!==""){return html_entity_decode(stk_t($tk,array($arg)),ENT_QUOTES,"UTF-8");}
    $map=array("no process"=>"stk_skip_no_process","disabled by default"=>"stk_skip_default",
        "unknown task type"=>"stk_skip_unknown","proxy scheduling restricted"=>"stk_skip_restricted");
    return isset($map[$r])?html_entity_decode(stk_t($map[$r]),ENT_QUOTES,"UTF-8"):$r;
}

function stk_h($s):string{
    return str_replace(array("{","}"),array("&#123;","&#125;"),htmlspecialchars((string)$s,ENT_QUOTES));
}

// Duree Go ("3s", "1m12s", "1h2m3s") -> "3 s", "1 min 12 s".
function stk_dur(string $d):string{
    if(!preg_match('/^(?:(\d+)h)?(?:(\d+)m(?!s))?(?:([\d.]+)s)?$/',$d,$re) || $d===""){return stk_h($d);}
    $p=array();
    if(!empty($re[1])){$p[]=intval($re[1])." h";}
    if(!empty($re[2])){$p[]=intval($re[2])." min";}
    if(isset($re[3]) && $re[3]!==""){$p[]=round(floatval($re[3]))." s";}
    return count($p)?implode(" ",$p):"0 s";
}

function stk_when(int $ts):string{
    if($ts<=0){return "&mdash;";}
    if(date("Y-m-d",$ts)==date("Y-m-d")){return stk_t("stk_today",array(date("H:i",$ts)));}
    if(date("Y-m-d",$ts)==date("Y-m-d",strtotime("yesterday"))){return stk_t("stk_yesterday",array(date("H:i",$ts)));}
    return date("Y-m-d H:i",$ts);
}

function stk_banner_error(string $err):string{
    $page=CurrentPageName();
    return "<div class='stk-ban stk-ban-err' role='alert'><i class='fas fa-circle-exclamation' aria-hidden='true'></i>"
        ."<span class='stk-grow'><b>{stk_list_error}</b> ".stk_h($err)."</span>"
        ."<button type='button' class='stk-bbtn' OnClick=\"LoadAjax('table-system-task-loader','$page?main=yes&ForceTaskType=".intval($_GET["ForceTaskType"] ?? 0)."');\">{stk_retry}</button></div>";
}

function stk_kpi(string $f,string $cls,string $ico,int $n,string $label,string $help):string{
    $dim=($n==0 && $f!=="all")?" stk-dim":"";   // pas "dim" : button.dim du theme (angular/style.css) casse la tuile
    return "<button type='button' class='stk-kpi stk-kpi-$cls$dim' data-f='$f' aria-pressed='false' title=\"$help\" OnClick=\"StkFilter('$f');\">"
        ."<span class='stk-kpi-i'><i class='fas $ico' aria-hidden='true'></i></span>"
        ."<span class='stk-kpi-t'><span class='stk-kpi-n'>$n</span><span class='stk-kpi-l'>$label</span></span></button>";
}

function stk_kpis(array $m):string{
    $failed=$m["events_known"]?stk_kpi("failed","err","fa-circle-xmark",$m["failed"],"{stk_k_failed}",stk_t("stk_k_failed_help"))
        :"<div class='stk-kpi stk-kpi-off'><span class='stk-kpi-i'><i class='fas fa-circle-question' aria-hidden='true'></i></span><span class='stk-kpi-t'><span class='stk-kpi-n'>&mdash;</span><span class='stk-kpi-l'>{stk_k_failed}</span></span></div>";
    return "<div class='stk-kpis'>"
        .stk_kpi("all","all","fa-list-check",$m["total"],"{stk_k_tasks}",stk_t("stk_k_tasks_help"))
        .stk_kpi("scheduled","ok","fa-circle-check",$m["scheduled"],"{stk_k_scheduled}",stk_t("stk_st_scheduled_help"))
        .$failed
        .stk_kpi("notinstalled","warn","fa-triangle-exclamation",$m["notinstalled"],"{stk_k_notinstalled}",stk_t("stk_st_notinstalled_help"))
        ."</div>";
}

function stk_build_js(int $type):string{
    $tpl=new template_admin();
    $page=CurrentPageName();
    return $tpl->framework_buildjs("/system/tasks/build","tasks.compile.progress","tasks.compile.txt","progress-firehol-restart",
        "LoadAjax('table-system-task-loader','$page?main=yes&ForceTaskType=$type');LoadAjaxSilent('artica-notifs-barr','fw.icon.top.php?notifs=yes');document.getElementById('progress-firehol-restart').innerHTML='';");
}

function stk_banner_notinstalled(array $m):string{
    if($m["notinstalled"]==0){return "";}
    $type=intval($_GET["ForceTaskType"] ?? 0);
    return "<div class='stk-ban stk-ban-warn' role='status'><i class='fas fa-triangle-exclamation' aria-hidden='true'></i>"
        ."<span class='stk-grow'>".stk_t($m["notinstalled"]==1?"stk_notinstalled_one":"stk_notinstalled_n",array("<b>".$m["notinstalled"]."</b>"))."</span>"
        ."<button type='button' class='stk-bbtn2' title=\"".stk_t("stk_apply_help")."\" OnClick=\"".stk_build_js($type)."\"><i class='fas fa-arrows-rotate' aria-hidden='true'></i> {stk_apply}</button></div>";
}

function stk_empty(int $type):string{
    $page=CurrentPageName();
    return "<div class='stk-empty'><span class='stk-empty-i'><i class='fas fa-clock' aria-hidden='true'></i></span>"
        ."<div class='stk-empty-h'>{stk_empty}</div><div class='stk-empty-s'>{stk_empty_help}</div>"
        ."<button type='button' class='stk-btn' OnClick=\"Loadjs('$page?AddNewSchedule-js=yes&ID=0&ForceTaskType=$type');\"><i class='fas fa-plus' aria-hidden='true'></i> {new_task}</button></div>";
}

function stk_toolbar():string{
    $ph=stk_t("stk_search");
    $b=array();
    foreach(array("all"=>"{stk_f_all}","failed"=>"{stk_f_failed}","notinstalled"=>"{stk_k_notinstalled}","notapplicable"=>"{stk_st_notapplicable}","disabled"=>"{stk_st_disabled}") as $k=>$l){
        $on=$k=="all";
        $b[]="<button type='button' class='stk-seg".($on?" on":"")."' data-f='$k' aria-pressed='".($on?"true":"false")."' OnClick=\"StkFilter('$k');\">$l</button>";
    }
    return "<div class='stk-bar'><div class='stk-search'><i class='fas fa-magnifying-glass' aria-hidden='true'></i>"
        ."<input type='search' id='stk-q' placeholder=\"$ph\" aria-label=\"$ph\" autocomplete='off' OnInput=\"StkApply();\"></div>"
        ."<div class='stk-segs' role='group' aria-label=\"".stk_t("stk_filter")."\">".implode("",$b)."</div><span class='stk-grow'></span>"
        ."<span class='stk-legend'><i class='fas fa-circle-info' aria-hidden='true'></i> {stk_legend}</span></div>";
}

function stk_cell_task(array $t,int $type):string{
    $page=CurrentPageName();
    $ico=$t["status"]=="notinstalled"?"fa-triangle-exclamation":($t["status"]=="notapplicable"?"fa-ban":"fa-clock");
    $cls=($t["status"]=="disabled"||$t["status"]=="notapplicable")?" off":($t["status"]=="notinstalled"?" warn":"");
    $meta="ID ".$t["id"].($t["process"]!==""?" &middot; <span class='stk-mono'>".stk_h($t["process"])."</span>":"");
    $unk=$t["unknown"]?" <span class='stk-chip stk-chip-err'><i class='fas fa-circle-question' aria-hidden='true'></i> {stk_unknown_type}</span>":"";
    $sub=$t["desc"]!==""?$t["desc"]:$t["explain"];
    $subH=$sub!==""?"<div class='stk-desc' title=\"".stk_h(strip_tags($sub))."\">".stk_h(strip_tags($sub))."</div>":"";
    $name="<a href=\"javascript:blur();\" class='stk-name' OnClick=\"Loadjs('$page?AddNewSchedule-js=yes&ID={$t["id"]}&ForceTaskType=$type');\">".stk_h($t["label"])."</a>";
    if(!empty($t["meta"])){
        // tache Artica Meta : lecture seule, pas de lien d'edition
        $name="<span class='stk-name'>".stk_h($t["label"])."</span>".tasks_meta_badge(array("metaid"=>"meta"));
    }
    return "<td><div class='stk-task'><span class='stk-tile$cls' aria-hidden='true'><i class='fas $ico'></i></span><div class='stk-task-t'>"
        .$name.$unk
        ."<div class='stk-meta'>$meta</div>$subH</div></div></td>";
}

function stk_cell_schedule(array $t):string{
    if($t["cron"]===""){return "<td><span class='stk-muted'>&mdash;</span></td>";}
    return "<td><div>".str_replace(array("{","}"),array("&#123;","&#125;"),htmlspecialchars($t["human"],ENT_QUOTES,"UTF-8",false))."</div><div class='stk-mono stk-cron'>".stk_h($t["cron"])."</div></td>";
}

function stk_cell_last(array $t,bool $known):string{
    if(!$known){return "<td><span class='stk-muted'>{stk_history_unavailable}</span></td>";}
    $l=$t["last"];
    if(!is_array($l)){return "<td><span class='stk-muted'><i>{stk_no_run}</i></span></td>";}
    $chip=$l["ok"]?"<span class='stk-chip stk-chip-ok'><i class='fas fa-circle-check' aria-hidden='true'></i> {stk_succeeded}</span>"
        :"<span class='stk-chip stk-chip-err'><i class='fas fa-circle-xmark' aria-hidden='true'></i> {stk_failed}</span>";
    $src=$l["source"]=="cron"?"":" <span class='stk-tag'>".stk_h($l["source"]=="rest"?stk_t("stk_manual"):$l["source"])."</span>";
    $err=(!$l["ok"] && $l["err"]!=="")?"<div class='stk-err' title=\"".stk_h($l["err"])."\"><i class='fas fa-circle-exclamation' aria-hidden='true'></i> ".stk_h($l["err"])."</div>":"";
    return "<td><div class='stk-last'>$chip <span>".stk_when($l["ts"])." &middot; ".stk_dur($l["took"])."</span>$src</div>$err</td>";
}

function stk_cell_status(array $t):string{
    $map=array("scheduled"=>array("ok","fa-calendar-check","{stk_st_scheduled}","stk_st_scheduled_help"),
        "notinstalled"=>array("warn","fa-triangle-exclamation","{stk_k_notinstalled}","stk_st_notinstalled_help"),
        "notapplicable"=>array("off","fa-ban","{stk_st_notapplicable}","stk_st_notapplicable_help"),
        "disabled"=>array("off","fa-power-off","{stk_st_disabled}","stk_st_disabled_help"));
    $x=$map[$t["status"]];
    $why=($t["status"]=="notapplicable" && $t["skip"]!=="")?"<div class='stk-why'>".stk_h(stk_skip_text($t["skip"]))."</div>":"";
    return "<td><span class='stk-pill stk-pill-{$x[0]}' title=\"".stk_t($x[3])."\"><i class='fas {$x[1]}' aria-hidden='true'></i> {$x[2]}</span>$why</td>";
}

function stk_cell_actions(array $t,int $type):string{
    $page=CurrentPageName();
    $name=stk_h($t["label"]);
    $run=$t["enabled"]?"<button type='button' class='stk-ico' aria-label=\"".stk_t("stk_run_aria",array($name))."\" title=\"".stk_t("stk_run_help")."\" OnClick=\"Loadjs('$page?run-task-js={$t["id"]}&ForceTaskType=$type');\"><i class='fas fa-play' aria-hidden='true'></i></button>"
        :"<span class='stk-ico-sp'></span>";
    $edit=stk_t("stk_edit_aria",array($name));
    $del=stk_t("stk_delete_aria",array($name));
    $sw=$t["enabled"]?"true":"false";
    if(!empty($t["meta"])){
        // tache Artica Meta : seul "Executer" reste (modifier, supprimer, activer : 423 cote demon)
        return "<td><div class='stk-acts'>$run</div></td>";
    }
    return "<td><div class='stk-acts'>$run"
        ."<button type='button' class='stk-ico' aria-label=\"$edit\" title=\"$edit\" OnClick=\"Loadjs('$page?AddNewSchedule-js=yes&ID={$t["id"]}&ForceTaskType=$type');\"><i class='fas fa-pen' aria-hidden='true'></i></button>"
        ."<button type='button' class='stk-ico stk-del' aria-label=\"$del\" title=\"$del\" OnClick=\"Loadjs('$page?delete-task-js={$t["id"]}&ForceTaskType=$type');\"><i class='fas fa-trash-can' aria-hidden='true'></i></button>"
        ."<button type='button' role='switch' class='stk-sw' aria-checked='$sw' aria-label=\"".stk_t("stk_enable_aria",array($name))."\" title=\"".stk_t("stk_enable_help")."\" OnClick=\"StkToggle(this,{$t["id"]});\"><span class='stk-sw-k' aria-hidden='true'></span></button>"
        ."</div></td>";
}

function stk_table(array $m,int $type):string{
    $h=array();
    $h[]="<div class='stk-card'><table class='stk-tbl'><thead><tr><th>{task}</th><th>{stk_th_schedule}</th><th>{stk_th_last}</th><th>{status}</th><th class='stk-end'><span class='stk-sr'>{stk_th_actions}</span></th></tr></thead><tbody id='stk-rows'>";
    foreach($m["tasks"] as $t){
        $q=strtolower(implode(" ",array($t["label"],$t["process"],$t["desc"],$t["human"],$t["cron"],"id ".$t["id"])));
        $failed=(is_array($t["last"]) && !$t["last"]["ok"])?1:0;
        $h[]="<tr class='stk-r stk-r-{$t["status"]}' data-q=\"".stk_h($q)."\" data-st='{$t["status"]}' data-failed='$failed'>"
            .stk_cell_task($t,$type).stk_cell_schedule($t).stk_cell_last($t,$m["events_known"]).stk_cell_status($t).stk_cell_actions($t,$type)."</tr>";
    }
    $h[]="</tbody></table>";
    $h[]="<div class='stk-nomatch' hidden>{stk_no_match}</div>";
    $h[]="<div class='stk-foot'><span id='stk-count'>".stk_t("stk_showing",array(count($m["tasks"]),count($m["tasks"])))."</span></div></div>";
    return implode("\n",$h);
}

// TINY : selecteur de type (pick) + boutons, inchanges sur le fond.
function stk_tiny_js(array $m,int $type):string{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $curLabel="{all}";
    if($type>0){
        $curLabel=isset($m["types"][$type]["label"])?(string)$m["types"][$type]["label"]:"#$type";
        if(trim($curLabel)==""){$curLabel="#$type";}
    }
    $fields=array();
    $fields[]=array("TYPE"=>"pick","ID"=>"tsk-type-label","LABEL"=>"{task_type}",
        "VALUE"=>$curLabel,"BUTTON"=>"{choose}","JS"=>"TskTypePick","WIDE"=>true,
        "HIDDEN"=>array("tsk-type"=>strval($type)));
    $TINY=array();
    $TINY["TITLE"]=$type>0?$curLabel:"{stk_title}";
    $TINY["ICO"]="fa fa-clock";
    $TINY["EXPL"]="{stk_intro}";
    $TINY["URL"]="system-tasks";
    $TINY["BUTTONS"]=siem_tiny_filters($fields,btns());
    $total=stk_t("stk_showing",array("%N%","%T%"));
    return siem_tiny_filters_style()."<script>
if(typeof NoSpinner==='function'){NoSpinner();}
function TskType(v){ LoadAjax('table-system-task-loader','$page?main=yes&ForceTaskType='+encodeURIComponent(v)); }
function TskTypePick(){ Loadjs('$page?type-catalog-js=yes&ForceTaskType=$type'); }
function TskTypeSet(v){ if(typeof dialogInstance2==='object'){dialogInstance2.close();} TskType(v); }
window.StkCountTpl='".str_replace("'","\\'",html_entity_decode($total,ENT_QUOTES,"UTF-8"))."';
window.StkReload=function(){ LoadAjaxSilent('table-system-task-loader','$page?main=yes&ForceTaskType=$type'); };
Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY)))."');
</script>";
}

function stk_js():string{
    $page=CurrentPageName();
    return "<script>
window.StkF='all';
window.StkApply=function(){
  var R=document.getElementById('stk-root'); if(!R){return;}
  var e=document.getElementById('stk-q'), q=e?e.value.trim().toLowerCase():'', n=0, rows=R.querySelectorAll('.stk-r');
  for(var i=0;i<rows.length;i++){
    var r=rows[i], ok=(q==='' || r.getAttribute('data-q').indexOf(q)>=0);
    if(StkF==='failed' && r.getAttribute('data-failed')!=='1'){ok=false;}
    if(StkF==='scheduled' || StkF==='notinstalled' || StkF==='notapplicable' || StkF==='disabled'){ if(r.getAttribute('data-st')!==StkF){ok=false;} }
    r.hidden=!ok; if(ok){n++;}
  }
  var nm=R.querySelector('.stk-nomatch'); if(nm){nm.hidden=(n>0);}
  var c=document.getElementById('stk-count'); if(c && window.StkCountTpl){c.textContent=StkCountTpl.replace('%N%',n).replace('%T%',rows.length);}
};
window.StkFilter=function(f){
  var R=document.getElementById('stk-root'); if(!R){return;}
  if(f===StkF && f!=='all'){f='all';}
  StkF=f;
  var b=R.querySelectorAll('.stk-seg,.stk-kpi');
  for(var i=0;i<b.length;i++){ var on=(b[i].getAttribute('data-f')===f && f!=='all') || (b[i].classList.contains('stk-seg') && b[i].getAttribute('data-f')===f); b[i].classList.toggle('on',on); b[i].setAttribute('aria-pressed',on?'true':'false'); }
  StkApply();
};
window.StkToggle=function(b,id){
  b.setAttribute('aria-checked',b.getAttribute('aria-checked')==='true'?'false':'true');
  Loadjs('$page?enable-task-js='+id);
  setTimeout(function(){ if(window.StkReload){StkReload();} },800);
};
</script>";
}

function stk_style():string{
    $ac=array("accent"=>"#0E7C66","accent_dark"=>"#0b6654","accent_soft"=>"rgba(14,124,102,.12)");
    if(function_exists("brand_ui_accents") && function_exists("brand_palette")){
        $b=brand_ui_accents(brand_palette());
        $ac["accent"]=$b["accent"];$ac["accent_dark"]=$b["accent_dark"];$ac["accent_soft"]=$b["accent_soft"];
    }
    $A=$ac["accent"];$AD=$ac["accent_dark"];$AS=$ac["accent_soft"];
    return "<style>
.stk{font-size:13px;color:#111827;margin-top:10px;display:flex;flex-direction:column;gap:12px}
.stk *{box-sizing:border-box}
.stk-grow{flex:1}
.stk-sr{position:absolute!important;width:1px;height:1px;overflow:hidden;clip:rect(0 0 0 0)}
.stk-mono{font-family:'JetBrains Mono',SFMono-Regular,Menlo,Consolas,monospace;font-size:12px}
.stk-muted{color:#9CA3AF}
.stk-kpis{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:16px}
.stk-kpi{display:flex;align-items:center;gap:14px;min-height:72px;padding:12px 16px;border:1px solid #E5E7EB;border-radius:8px;background:#fff;cursor:pointer;text-align:left;box-shadow:0 1px 2px rgba(0,0,0,.05)}
.stk-kpi.stk-dim{opacity:.55}
.stk-kpi.on{box-shadow:0 0 0 2px currentColor}
.stk-kpi-i{display:inline-flex;align-items:center;justify-content:center;width:40px;height:40px;border-radius:8px;font-size:17px;flex:none}
.stk-kpi-t{display:flex;flex-direction:column}
.stk-kpi-n{font-size:24px;font-weight:600;line-height:1.1}
.stk-kpi-l{font-size:13px;color:#4B5563}
.stk-kpi-all{color:#374151}.stk-kpi-all .stk-kpi-i{background:#F3F4F6;color:#4B5563}
.stk-kpi-ok{color:#047857}.stk-kpi-ok .stk-kpi-i{background:#ECFDF5}
.stk-kpi-err{color:#DC2626}.stk-kpi-err .stk-kpi-i{background:#FEF2F2}
.stk-kpi-warn{color:#B45309}.stk-kpi-warn .stk-kpi-i{background:#FFFBEB}
.stk-kpi-off{color:#9CA3AF;cursor:default}.stk-kpi-off .stk-kpi-i{background:#F3F4F6}
.stk-ban{display:flex;align-items:center;gap:12px;padding:12px 16px;border-radius:0 8px 8px 0;font-size:13px}
.stk-ban>svg,.stk-ban>i{flex:none;font-size:16px}
.stk-ban-err{background:#FEF2F2;border-left:4px solid #DC2626;color:#7F1D1D}.stk-ban-err>svg{color:#DC2626}
.stk-ban-warn{background:#FFFBEB;border-left:4px solid #D97706;color:#92400E}.stk-ban-warn>svg{color:#D97706}
.stk-bbtn{height:32px;padding:0 12px;border:0;border-radius:6px;background:#DC2626;color:#fff;font-weight:600;font-size:12px;cursor:pointer}
.stk-bbtn2{display:inline-flex;align-items:center;gap:6px;height:32px;padding:0 12px;border:1px solid #FCD34D;border-radius:6px;background:#fff;color:#92400E;font-weight:600;font-size:12px;cursor:pointer;white-space:nowrap}
.stk-bar{display:flex;align-items:center;gap:12px;flex-wrap:wrap}
.stk-search{position:relative;width:340px}
.stk-search>svg,.stk-search>i{position:absolute;left:12px;top:50%;transform:translateY(-50%);color:#9CA3AF;width:14px;height:14px}
.stk-search input{width:100%;height:36px;padding:0 12px 0 34px;border:1px solid #E5E7EB;border-radius:8px;background:#fff;font-size:13px}
.stk-segs{display:inline-flex;padding:3px;background:#F3F4F6;border:1px solid #E5E7EB;border-radius:8px;white-space:nowrap}
.stk-seg{border:0;background:transparent;padding:5px 12px;border-radius:6px;font-size:13px;font-weight:500;color:#4B5563;cursor:pointer}
.stk-seg.on{background:$A;color:#fff}
.stk-legend{font-size:12px;color:#6B7280}
.stk-card{background:#fff;border:1px solid #E5E7EB;border-radius:8px;box-shadow:0 1px 2px rgba(0,0,0,.05);overflow:hidden}
.stk-tbl{width:100%;border-collapse:collapse;color:#111827}
.stk-tbl thead th{background:#F9FAFB;border-bottom:1px solid #E5E7EB;font-size:12px;font-weight:600;text-transform:uppercase;letter-spacing:.05em;color:#6B7280;padding:10px 14px;text-align:left}
.stk-tbl td{padding:12px 14px;border-bottom:1px solid #F1F5F9;vertical-align:top;color:#111827}
.stk-r[hidden]{display:none}
.stk-r-notinstalled td{background:#FFFDF5}
.stk-r-disabled .stk-name,.stk-r-disabled td>div,.stk-r-notapplicable .stk-name{opacity:.75}
.stk-task{display:flex;gap:12px;min-width:0}
.stk-tile{display:inline-flex;align-items:center;justify-content:center;width:32px;height:32px;border-radius:6px;background:$AS;color:$AD;flex:none}
.stk-tile.off{background:#F3F4F6;color:#9CA3AF}.stk-tile.warn{background:#FFFBEB;color:#B45309}
.stk-task-t{min-width:0}
.stk-name{font-size:14px;font-weight:600;color:#111827!important;text-decoration:none}
.stk-name:hover{color:$AD!important}
.stk-meta{font-size:12px;color:#6B7280;margin-top:2px}
.stk-desc{font-size:12px;color:#6B7280;margin-top:2px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;max-width:340px}
.stk-cron{color:#6B7280;margin-top:2px;white-space:nowrap}
.stk-last{display:flex;align-items:center;gap:8px;flex-wrap:wrap}
.stk-chip{display:inline-flex;align-items:center;gap:4px;padding:1px 8px;border-radius:999px;font-size:12px;font-weight:600;white-space:nowrap}
.stk-chip-ok{background:#ECFDF5;color:#047857;border:1px solid #A7F3D0}
.stk-chip-err{background:#FEF2F2;color:#B91C1C;border:1px solid #FECACA}
.stk-tag{font-size:11px;font-weight:600;text-transform:uppercase;color:#4B5563;background:#F3F4F6;border-radius:4px;padding:0 6px}
.stk-err{font-size:12px;color:#B91C1C;margin-top:4px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;max-width:360px}
.stk-why{font-size:12px;color:#6B7280;margin-top:4px;white-space:normal;max-width:200px;line-height:1.35}
.stk-pill{display:inline-flex;align-items:center;gap:5px;padding:2px 10px;border-radius:999px;font-size:12px;font-weight:600;white-space:nowrap}
.stk-pill-ok{background:#ECFDF5;color:#047857;border:1px solid #A7F3D0}
.stk-pill-warn{background:#FFFBEB;color:#B45309;border:1px solid #FDE68A}
.stk-pill-off{background:#F3F4F6;color:#6B7280;border:1px solid #E5E7EB}
.stk-end{text-align:right;width:170px}
.stk-tbl td:last-child{white-space:nowrap}
.stk-acts{display:flex;align-items:center;justify-content:flex-end;gap:4px}
.stk-ico,.stk-ico-sp{width:32px;height:32px;flex:none}
.stk-ico{border:0;border-radius:6px;background:transparent;color:#4B5563;cursor:pointer;display:inline-flex;align-items:center;justify-content:center}
.stk-ico:hover{background:#F3F4F6;color:#111827}
.stk-del:hover{background:#FEF2F2;color:#DC2626}
.stk-sw{position:relative;width:36px;height:20px;border-radius:10px;border:0;background:#D1D5DB;cursor:pointer;flex:none;padding:0;margin-left:6px;transition:background .15s}
.stk-sw-k{position:absolute;top:2px;left:2px;width:16px;height:16px;border-radius:50%;background:#fff;box-shadow:0 1px 2px rgba(0,0,0,.2);transition:left .15s}
.stk-sw[aria-checked=true]{background:$A}.stk-sw[aria-checked=true] .stk-sw-k{left:18px}
.stk button:focus-visible,.stk a:focus-visible,.stk input:focus-visible{outline:2px solid $A;outline-offset:2px}
.stk-nomatch{padding:24px;text-align:center;color:#6B7280}.stk-nomatch[hidden]{display:none}
.stk-foot{padding:10px 14px;font-size:12px;color:#6B7280;background:#F9FAFB;border-top:1px solid #E5E7EB}
.stk-empty{padding:56px 16px;text-align:center;background:#fff;border:1px solid #E5E7EB;border-radius:8px}
.stk-empty-i{display:inline-flex;align-items:center;justify-content:center;width:56px;height:56px;border-radius:50%;background:#F3F4F6;color:#9CA3AF;font-size:24px;margin-bottom:12px}
.stk-empty-h{font-weight:600;color:#374151}
.stk-empty-s{font-size:12px;color:#6B7280;margin:4px auto 16px}
.stk-btn{display:inline-flex;align-items:center;gap:8px;height:36px;padding:0 16px;border:0;border-radius:6px;background:$A;color:#fff;font-weight:600;cursor:pointer}
@media (prefers-reduced-motion:reduce){.stk-sw,.stk-sw-k{transition:none}}
@media (max-width:1200px){.stk-kpis{grid-template-columns:repeat(2,minmax(0,1fr))}}
</style>";
}
