<?php
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;$GLOBALS["debug"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.external.ad.inc");
include_once(dirname(__FILE__)."/ressources/class.ActiveDirectory.inc");
include_once(dirname(__FILE__)."/ressources/class.ufdbrules.meta.inc");
// awc_table_style()/awc_pager_js()/awc_search_bar()/awc_filter_js(): kit des tableaux
// d'entites. Inclusion INCONDITIONNELLE au premier niveau -- placee dans un bloc de
// bootstrap elle ne s'executerait jamais et la page mourrait au rendu sur
// « Call to undefined function awc_table_style() ».
include_once(dirname(__FILE__)."/ressources/class.statistics_appliance.inc");

if(isset($_GET["popup"]))          { popup();            exit; }
if(isset($_GET["members-tab"]))    { members_tab();      exit; }
if(isset($_GET["settings-tab"]))   { settings_tab();     exit; }
if(isset($_GET["member-toggle"]))  { member_toggle();    exit; }
if(isset($_GET["member-delete"]))  { member_delete();    exit; }
if(isset($_GET["member-add-js"]))  { member_add_js();    exit; }
if(isset($_GET["member-add-popup"])){ member_add_popup();exit; }
if(isset($_GET["bulk-js"]))        { bulk_js();          exit; }
if(isset($_GET["bulk-popup"]))     { bulk_popup();       exit; }
if(isset($_POST["bulk-items"]))    { bulk_save();        exit; }
if(isset($_POST["newpattern"]))    { member_add_save();  exit; }
if(isset($_POST["ID"]))            { Save();             exit; }

js();

/** ufdbgrp_require_admin — le garde SERVEUR des quatre ecritures de cette page. */
function ufdbgrp_require_admin(){
    $users=new usersMenus();
    return $users->AsDansGuardianAdministrator?true:false;
}

// Verrou Artica Meta (politique ufdbrules) : un groupe metaid<>'' est tenu par l'import du noeud.
// Le groupe d'un membre est relu en base (le gid de l'URL n'est pas une preuve).
// Lecture illisible = tenu (echec ferme).
function ufdbgrp_meta_member_locked($mid):bool{
    $q=new lib_sqlite("/home/artica/SQLITE/webfilter.db");
    $rows=$q->QUERY_SQL_PARAMS("SELECT groupid FROM webfilter_members WHERE ID=?",array(intval($mid)));
    if(!$q->ok){return true;}
    if(!isset($rows[0])){return false;}
    return ufdbrules_meta_locked_group(intval($rows[0]["groupid"]));
}
function ufdbgrp_meta_locked($gid):bool{
    return ufdbrules_meta_locked_group(intval($gid));
}

/** ufdbgrp_types — les six natures de groupe, indexees par webfilter_group.localldap. */
function ufdbgrp_types(){
    return array(
        0=>"{ldap_group}",1=>"{virtual_group}",2=>"{active_directory_group}",
        3=>"{remote_ladp_group}",4=>"{active_directory_group} ({other})",
        // Le type 5 manquait: fw.ufdb.rules.sources.php le connait depuis toujours, et
        // un groupe d'unite d'organisation AD sortait ici en index indefini -- avis
        // PHP 8, sous-titre vide.
        5=>"{active_directory_ou}");
}

/** ufdbgrp_reload — le rechargement du tableau des membres, apres une mutation. */
function ufdbgrp_reload($ID){
    $page=CurrentPageName();
    $ID=intval($ID);
    return "if(document.getElementById('ufdbgrp-$ID-host')){"
        ."LoadAjaxSilent('ufdbgrp-$ID-host','$page?members-tab=$ID');}\n";
}

function js(){
    $ID=intval($_GET["ID"]);
    $q=new lib_sqlite("/home/artica/SQLITE/webfilter.db");
    $tpl=new template_admin();
    $page=CurrentPageName();
    $rows=$q->QUERY_SQL_PARAMS("SELECT groupname FROM webfilter_group WHERE ID=?",array($ID));
    $groupname=isset($rows[0]["groupname"])?(string)$rows[0]["groupname"]:"#$ID";
    // buildADialog() pose le titre dans « title: '$title' » SANS echappement, et
    // javascript_parse_text() ne neutralise les apostrophes que des jetons resolus,
    // pas d'un nom relu en base. htmlspecialchars(ENT_QUOTES) traite le balisage ET
    // l'apostrophe; l'antislash, qu'il laisse passer, ouvrirait une sequence dans ce
    // meme litteral et la modale ne s'ouvrirait jamais, sans un mot a l'ecran.
    $groupname=str_replace("\\","",htmlspecialchars($groupname,ENT_QUOTES));
    // RefreshTable NE VOYAGE PLUS. C'etait un base64 pris dans l'URL, decode, puis
    // insere tel quel dans le $jsafter de form_outside -- donc EXECUTE. N'importe quel
    // lien fabrique faisait tourner le JavaScript de son choix dans la console d'un
    // administrateur. Le rafraichissement est desormais decide ici, pas transmis.
    $tpl->js_dialog1("{group2}: $groupname","$page?popup=$ID");
}

/**
 * popup — deux modes de lecture, deux onglets (skill §3b).
 *
 * Les membres sont une LISTE d'entites, le nom et la description un formulaire court.
 * Ils partageaient un seul ecran, la liste rendue en zone de texte ou chaque ligne
 * portait un « # » de tete pour dire « desactive » -- une convention que rien
 * n'affichait et que l'operateur devait connaitre. Elle reste disponible (l'edition
 * en masse est le vrai usage: on colle 500 adresses d'un coup), mais en modale, a
 * cote d'un tableau qui, lui, montre l'etat de chaque ligne.
 */
function popup():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $ID=intval($_GET["popup"]);

    $array=array();
    $array["{members}"] ="$page?members-tab=$ID";
    $array["{settings}"]="$page?settings-tab=$ID";
    // tabs_default() rend deja son propre _ENGINE_parse_body: ne pas re-analyser.
    echo $tpl->tabs_default($array);
    return true;
}

function settings_tab():bool{
    $tpl=new template_admin();
    $q=new lib_sqlite("/home/artica/SQLITE/webfilter.db");
    $ID=intval($_GET["settings-tab"]);
    $rows=$q->QUERY_SQL_PARAMS("SELECT groupname,description,localldap FROM webfilter_group WHERE ID=?",array($ID));
    if(!isset($rows[0])){ echo $tpl->_ENGINE_parse_body("<div class='alert alert-warning'>{no_data}</div>"); return true; }
    $ligne=$rows[0];

    $types=ufdbgrp_types();
    $type=intval($ligne["localldap"]);
    $subtitle=$tpl->_ENGINE_parse_body(isset($types[$type])?$types[$type]:"{unknown}");
    // Le jeton d'explication est compose a partir du type: une nature inconnue
    // produirait un jeton inexistant, affiche en toutes lettres.
    $explain=isset($types[$type])?"{group_explain_proxy_acls_type_$type}":"";

    $form=array();
    // Groupe Artica Meta : affiche, jamais enregistrable ici.
    if(ufdbgrp_meta_locked($ID)){
        $tpl->FORM_LOCKED=true;
        $tpl->this_form_locked_explain="<i class='fa-solid fa-lock'></i>&nbsp;{ufdbrules_meta_group_locked}";
    }
    $form[]=$tpl->field_hidden("ID",$ID);
    $form[]=$tpl->field_text("groupname","{groupname}",$ligne["groupname"],true);
    $form[]=$tpl->field_text("description","{description}",$ligne["description"],true);

    echo $tpl->form_outside(
        htmlspecialchars((string)$ligne["groupname"])." <small>($subtitle)</small>",
        @implode("\n",$form),$explain,"{apply}",
        "LoadAjaxSilent('ufdb-rules-source','fw.ufdb.rules.sources.php?table=$ID');",
        "AsDansGuardianAdministrator");
    return true;
}

/** ufdbgrp_member_row — un membre du groupe: motif, etat, actions. */
function ufdbgrp_member_row($mid,$gid,$pattern,$enabled,$canWrite){
    $page=CurrentPageName();
    $mid=intval($mid); $gid=intval($gid);
    $ico=$enabled?"awc-ico":"awc-ico awc-ico-off";
    $nameCls=$enabled?"awc-name":"awc-name awc-name-off";
    $h ="<tr>";
    $h.="<td><div class='awc-ident' style='gap:10px'>";
    $h.="<span class='$ico' style='width:30px;height:30px;font-size:13px'><i class='fas fa-user'></i></span>";
    $h.="<span class='$nameCls awc-mono' style='font-size:13px'>".htmlspecialchars($pattern)."</span>";
    $h.="</div></td>";
    $h.="<td class='center' style='width:1%' nowrap>"
       .($enabled?"<span class='awc-badge awc-badge-on'>{enabled}</span>"
                 :"<span class='awc-badge awc-badge-off'>{disabled}</span>")."</td>";
    $h.="<td style='width:1%' nowrap><div class='awc-acts'>";
    if($canWrite){
        // Le « # » de tete devient un BOUTON. La convention existait deja dans la zone
        // de texte -- elle n'etait simplement ecrite nulle part dans l'interface.
        $h.="<a class='awc-act' title=\"".($enabled?"{disable}":"{enable}")."\" href=\"javascript:blur();\" "
           ."OnClick=\"Loadjs('$page?member-toggle=$mid&gid=$gid')\">"
           ."<i class='fas ".($enabled?"fa-pause":"fa-play")."'></i></a>";
        $h.="<a class='awc-act awc-act-danger' title=\"{delete}\" href=\"javascript:blur();\" "
           ."OnClick=\"Loadjs('$page?member-delete=$mid&gid=$gid')\"><i class='fas fa-trash'></i></a>";
    }else{ $h.="&nbsp;"; }
    $h.="</div></td></tr>";
    return $h;
}

function members_tab():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $q=new lib_sqlite("/home/artica/SQLITE/webfilter.db");
    $ID=intval($_GET["members-tab"]);
    $canWrite=ufdbgrp_require_admin() && !ufdbgrp_meta_locked($ID);

    $results=$q->QUERY_SQL_PARAMS("SELECT ID,pattern,enabled FROM webfilter_members WHERE groupid=? ORDER BY pattern",array($ID));
    if(!$q->ok){
        // Le message SQLite nomme tables et colonnes: journal, pas ecran.
        writelogs("webfilter_members: ".$q->mysql_error,__FUNCTION__,__FILE__,__LINE__);
        $results=array();
    }
    if(!is_array($results)){ $results=array(); }

    $html=array();
    // L'hote porte l'identifiant que les mutations rechargent; il vit DANS le fragment,
    // donc il disparait avec lui quand l'onglet est vide par tabs_default().
    $html[]="<div id='ufdbgrp-$ID-host'>";
    if($canWrite){
        $html[]="<div class='awc-search' style='margin-top:0'><div class='awc-search-actions'>";
        $html[]="<a class='awc-btn' href=\"javascript:blur();\" OnClick=\"Loadjs('$page?member-add-js=$ID')\">"
               ."<i class='fas fa-plus'></i> {add}</a>";
        $html[]="<a class='awc-btn' href=\"javascript:blur();\" OnClick=\"Loadjs('$page?bulk-js=$ID')\">"
               ."<i class='fas fa-pen-to-square'></i> {items}</a>";
        $html[]="</div></div>";
    }
    $html[]=awc_search_bar("ufdbgrp-$ID-search","{search}");
    $html[]="<div class='awc-wrap' style='margin:0;padding:0'><div class='awc-card'><div class='awc-scroll'>";
    $html[]="<table class='awc-tbl awc-tbl-compact'><thead><tr>";
    $html[]="<th>{pattern}</th>";
    $html[]="<th class='center' style='width:1%'>{status}</th>";
    $html[]="<th style='width:1%;text-align:right'>{actions}</th>";
    $html[]="</tr></thead><tbody id='ufdbgrp-$ID-rows'>";

    $n=0; $off=0;
    foreach($results as $ligne){
        $enabled=(intval($ligne["enabled"])!=0);
        if(!$enabled){ $off++; }
        $html[]=ufdbgrp_member_row($ligne["ID"],$ID,(string)$ligne["pattern"],$enabled,$canWrite);
        $n++;
    }
    if($n==0){ $html[]="<tr><td colspan='3'><div class='awc-empty'>{no_data}</div></td></tr>"; }

    $html[]="</tbody></table></div>";
    $html[]="<div class='awc-foot'><span>{showing} $n / $n {members}"
           .($off>0?" &middot; $off {disabled}":"")."</span>"
           ."<span class='awc-pg' id='ufdbgrp-$ID-pager'></span></div>";
    $html[]="</div></div></div>";

    echo awc_table_style()
        .$tpl->_ENGINE_parse_body(@implode("\n",$html))
        .awc_pager_js(100,"ufdbgrp-$ID-rows","ufdbgrp-$ID-pager")
        .awc_filter_js("ufdbgrp-$ID-search","ufdbgrp-$ID-rows","ufdbgrp-$ID-pager");
    return true;
}

function member_toggle(){
    header("content-type: application/x-javascript");
    $tpl=new template_admin();
    if(!ufdbgrp_require_admin()){
        echo "alert(".json_encode($tpl->javascript_parse_text("{access_denied}")).");"; return;
    }
    $mid=intval($_GET["member-toggle"]);
    $gid=intval($_GET["gid"]);
    if(ufdbgrp_meta_member_locked($mid)){ echo "alert(".json_encode(ufdbrules_meta_text()).");"; return; }
    $q=new lib_sqlite("/home/artica/SQLITE/webfilter.db");
    // Bascule en UNE operation: le couple lecture-puis-ecriture laisse deux clics
    // rapides se croiser et rendre l'etat inverse de ce que la ligne affichait.
    $q->QUERY_SQL_PARAMS("UPDATE webfilter_members SET enabled=CASE WHEN enabled=1 THEN 0 ELSE 1 END WHERE ID=?",array($mid));
    if(!$q->ok){
        writelogs("member_toggle($mid): ".$q->mysql_error,__FUNCTION__,__FILE__,__LINE__);
        echo "alert(".json_encode($tpl->javascript_parse_text("{database_error}")).");"; return;
    }
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/ufdb/recompile");
    admin_tracks("Web-filter group #$gid: toggled member #$mid");
    echo ufdbgrp_reload($gid);
}

function member_delete(){
    header("content-type: application/x-javascript");
    $tpl=new template_admin();
    if(!ufdbgrp_require_admin()){
        echo "alert(".json_encode($tpl->javascript_parse_text("{access_denied}")).");"; return;
    }
    $mid=intval($_GET["member-delete"]);
    $gid=intval($_GET["gid"]);
    if(ufdbgrp_meta_member_locked($mid)){ echo "alert(".json_encode(ufdbrules_meta_text()).");"; return; }
    $q=new lib_sqlite("/home/artica/SQLITE/webfilter.db");
    $q->QUERY_SQL_PARAMS("DELETE FROM webfilter_members WHERE ID=?",array($mid));
    if(!$q->ok){
        writelogs("member_delete($mid): ".$q->mysql_error,__FUNCTION__,__FILE__,__LINE__);
        echo "alert(".json_encode($tpl->javascript_parse_text("{database_error}")).");"; return;
    }
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/ufdb/recompile");
    admin_tracks("Web-filter group #$gid: removed member #$mid");
    echo ufdbgrp_reload($gid);
}

function member_add_js(){
    $tpl=new template_admin();
    $page=CurrentPageName();
    $ID=intval($_GET["member-add-js"]);
    $tpl->js_dialog2("{members}","$page?member-add-popup=$ID",480);
}

function member_add_popup(){
    $tpl=new template_admin();
    $ID=intval($_GET["member-add-popup"]);
    $form=array();
    $form[]=$tpl->field_hidden("addgroupid",$ID);
    $form[]=$tpl->field_text("newpattern","{pattern}","",true);
    echo $tpl->form_outside("{members}",@implode("\n",$form),null,"{add}",
        "dialogInstance2.close();".ufdbgrp_reload($ID),"AsDansGuardianAdministrator");
}

function member_add_save(){
    if(!ufdbgrp_require_admin()){ echo "{access_denied}"; return; }
    $tpl=new template_admin();
    $tpl->CLEAN_POST();
    $ID=intval($_POST["addgroupid"]);
    $pattern=trim((string)$_POST["newpattern"]);
    if($ID<=0 || $pattern===""){ echo "{error}"; return; }
    if(ufdbgrp_meta_locked($ID)){ ufdbrules_meta_refuse_post(); return; }
    $q=new lib_sqlite("/home/artica/SQLITE/webfilter.db");
    $q->QUERY_SQL_PARAMS("INSERT INTO webfilter_members (groupid,enabled,pattern) VALUES (?,1,?)",array($ID,$pattern));
    if(!$q->ok){
        writelogs("member_add_save($ID): ".$q->mysql_error,__FUNCTION__,__FILE__,__LINE__);
        echo "{database_error}"; return;
    }
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/ufdb/recompile");
    admin_tracks("Web-filter group #$ID: added member $pattern");
}

function bulk_js(){
    $tpl=new template_admin();
    $page=CurrentPageName();
    $ID=intval($_GET["bulk-js"]);
    $tpl->js_dialog2("{members} &raquo; {items}","$page?bulk-popup=$ID",760);
}

/**
 * bulk_popup — l'edition en masse, conservee telle quelle.
 *
 * C'est le vrai usage de cette page: on colle une liste entiere d'adresses. Le tableau
 * sert a LIRE et a corriger une ligne; cette zone de texte sert a remplacer le lot.
 * La convention du « # » de tete y est desormais ECRITE, au lieu d'etre supposee connue.
 */
function bulk_popup(){
    $tpl=new template_admin();
    $q=new lib_sqlite("/home/artica/SQLITE/webfilter.db");
    $ID=intval($_GET["bulk-popup"]);
    $results=$q->QUERY_SQL_PARAMS("SELECT pattern,enabled FROM webfilter_members WHERE groupid=? ORDER BY pattern",array($ID));
    if(!is_array($results)){ $results=array(); }
    $LINES=array();
    foreach($results as $ligne){
        $LINES[]=(intval($ligne["enabled"])==0?"#":"").$ligne["pattern"];
    }
    $form=array();
    $form[]=$tpl->field_hidden("bulkgroupid",$ID);
    $form[]=$tpl->field_textareacode("bulk-items","{items}",@implode("\n",$LINES));
    echo $tpl->form_outside("{items}",@implode("\n",$form),"{ufdbgrp_bulk_explain}","{apply}",
        "dialogInstance2.close();".ufdbgrp_reload($ID),"AsDansGuardianAdministrator");
}

function bulk_save(){
    if(!ufdbgrp_require_admin()){ echo "{access_denied}"; return; }
    $tpl=new template_admin();
    $tpl->CLEAN_POST();
    $ID=intval($_POST["bulkgroupid"]);
    if($ID<=0){ echo "{error}"; return; }
    if(ufdbgrp_meta_locked($ID)){ ufdbrules_meta_refuse_post(); return; }
    $q=new lib_sqlite("/home/artica/SQLITE/webfilter.db");

    $LINES=explode("\n",(string)$_POST["bulk-items"]);
    $rows=array();
    foreach($LINES as $line){
        $enabled=1;
        $line=trim($line);
        // Comparaison STRICTE a la chaine vide: « $line==null » etait vrai pour "0" en
        // PHP 7, si bien qu'un motif valant zero disparaissait du groupe.
        if($line===""){ continue; }
        if(preg_match("#^\#(.*)$#",$line,$re)){ $line=trim($re[1]); $enabled=0; }
        if($line===""){ continue; }
        $rows[]=array($ID,$enabled,$line);
    }

    $q->QUERY_SQL_PARAMS("DELETE FROM webfilter_members WHERE groupid=?",array($ID));
    if(!$q->ok){
        writelogs("bulk_save($ID) delete: ".$q->mysql_error,__FUNCTION__,__FILE__,__LINE__);
        echo "{database_error}"; return;
    }
    // Une insertion par ligne, en parametres lies. L'ancienne version concatenait un
    // seul INSERT geant a partir des lignes echappees a la main -- correct tant que
    // sqlite_escape_string2() est appelee sur CHAQUE valeur, ce qui n'est pas une
    // propriete que la relecture peut verifier d'un coup d'oeil.
    foreach($rows as $r){
        $q->QUERY_SQL_PARAMS("INSERT INTO webfilter_members (groupid,enabled,pattern) VALUES (?,?,?)",$r);
        if(!$q->ok){
            writelogs("bulk_save($ID) insert: ".$q->mysql_error,__FUNCTION__,__FILE__,__LINE__);
            echo "{database_error}"; return;
        }
    }
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/ufdb/recompile");
    admin_tracks("Web-filter group #$ID: replaced ".count($rows)." members");
}

function Save(){
    if(!ufdbgrp_require_admin()){ echo "{access_denied}"; return; }
    $tpl=new template_admin();
    $tpl->CLEAN_POST();
    $ID=intval($_POST["ID"]);
    if($ID<=0){ echo "{error}"; return; }
    if(ufdbgrp_meta_locked($ID)){ ufdbrules_meta_refuse_post(); return; }
    $groupname=trim((string)$_POST["groupname"]);
    $description=trim((string)$_POST["description"]);
    $q=new lib_sqlite("/home/artica/SQLITE/webfilter.db");
    // Parametres lies plutot que sqlite_escape_string2() appelee a la main sur chaque
    // valeur: la protection ne depend plus de se souvenir de l'appeler.
    $q->QUERY_SQL_PARAMS("UPDATE webfilter_group SET groupname=?, description=? WHERE ID=?",
        array($groupname,$description,$ID));
    if(!$q->ok){
        writelogs("ufdb group Save($ID): ".$q->mysql_error,__FUNCTION__,__FILE__,__LINE__);
        echo "{database_error}"; return;
    }
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/ufdb/recompile");
    admin_tracks("Web-filter group #$ID renamed to $groupname");
}
