<?php
// Service Pack 9 2026-10-10 // Service Pack 9
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.system.network.inc");
// L'amorce de $GLOBALS["CLASS_SOCKETS"] etait ecrite DEUX fois, a l'identique.
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
include_once(dirname(__FILE__).'/ressources/class.support-tracker.inc');
// awc_table_style() / awc_pager_js() / awc_search_bar(): tableaux d'entites partages
include_once(dirname(__FILE__)."/ressources/class.statistics_appliance.inc");
if(isset($_GET["table"])){table();exit;}
if(isset($_POST["none"])){exit();}
// "if(isset($_POST["EnableDNSRootInts"])){save();exit;}" visait une fonction
// save() qui n'existe pas ici, sur un reglage DNS qui n'a rien a faire sur la
// page du support: copie oubliee de fw.dns.unbound.php, et un fatal
// « Call to undefined function » pour qui postait cette cle.
if(isset($_GET["parameters-js"])){parameters_js();exit;}
if(isset($_GET["recover-js"])){recover_js();exit;}
if(isset($_GET["unlink-js"])){unlink_js();exit;}

if(isset($_GET["parameters"])){parameters();exit;}
if(isset($_GET["recover"])){recover();exit;}
// ORDRE: "supportEmail1" est le champ cache que recover() ajoute POUR SE
// DISTINGUER de parameters(). Mais les deux formulaires postent aussi
// "supportEmail", qui etait teste EN PREMIER: la reinitialisation de compte
// tombait donc toujours dans parameters_save(), et recover_save() n'a jamais
// ete atteinte. Le discriminant specifique passe devant.
if(isset($_POST["supportEmail1"])){recover_save();exit;}
if(isset($_POST["supportEmail"])){parameters_save();exit;}
if(isset($_GET["create-js"])){create_ticket_js();exit;}
if(isset($_GET["create-popup"])){create_ticket_popup();exit;}
if(isset($_POST["new-ticket"])){create_ticket_save();exit;}
if(isset($_GET["delete-bug-js"])){delete_bug_js();exit;}
if(isset($_GET["show-js"])){bug_js();exit;}
if(isset($_GET["show-popup"])){bug_popup();exit;}
if(isset($_GET["show-comment-js"])){comment_js();exit;}
if(isset($_GET["show-comment-popup"])){comment_popup();exit;}
if(isset($_GET["reply-js"])){reply_js();exit;}
if(isset($_GET["reply-popup"])){reply_popup();exit;}
if(isset($_POST["reply"])){reply_save();exit;}
//if(isset($_GET["spt-js"])){support_tool_js();exit;}
if(isset($_GET["timeline"])){bug_timeline();exit;}
// "if(isset($_GET["tabs"])){tabs();exit;}" visait lui aussi une fonction
// inexistante: la page n'a pas d'onglets.
page();

/**
 * GET a /system/support/db/* endpoint (articarest). Returns the decoded
 * `data` payload (object or array) on success, or null on any failure.
 * Replaces the former direct lib_sqlite reads of support.db.
 */
// Masquer un bouton ne protege rien: le repartiteur en tete de fichier repond a
// toute session authentifiee. Les ecritures de cette page se gardent donc cote
// serveur, avec le meme drapeau que les form_outside qui les declenchent.
function support_require_admin($tpl):bool{
    $users=new usersMenus();
    if($users->AsSystemAdministrator){return true;}
    $tpl->js_no_privileges();
    return false;
}

function support_db_get($path){
    $resp=json_decode(strval($GLOBALS["CLASS_SOCKETS"]->REST_API($path)),true);
    if(!is_array($resp) || empty($resp["Status"])){return null;}
    return $resp["data"] ?? null;
}

function page()
{
    $page = CurrentPageName();
    $tpl = new template_admin();

    $html = $tpl->page_header("{technical_support}",
        ico_support, "{technical_support_explain}", "$page?table=yes", "support", "progress-support-restart", false, "table-loader-support");


    if (isset($_GET["main-page"])) {
        $tpl = new template_admin(null, $html);
        // Le titre de la page autonome annoncait "{snapshots}" -- le libelle
        // d'une page de sauvegardes, sans rapport avec le support.
        echo $tpl->build_firewall("{technical_support}");
        return true;
    }

    echo $tpl->_ENGINE_parse_body($html);
    return true;
}


function reply_js(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $bugid=intval($_GET["reply-js"]);

    $ligne=support_db_get("/system/support/db/ticket/$bugid");
    $id_temp=intval(is_array($ligne)?($ligne["id"] ?? 0):0);
    if($id_temp==0){
        $tpl->js_error("{already_removed}");
        return;
    }
    // Titre court, comme pour les deux autres modales: l'en-tete de la reponse
    // porte deja « #numero - sujet », et la barre l'affichait a l'identique
    // quelques pixels plus haut.
    $tpl->js_dialog2("{reply}", "$page?reply-popup=$bugid");

}

function reply_popup():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $bugid=intval($_GET["reply-popup"]);

    $ligne=support_db_get("/system/support/db/ticket/$bugid");
    if(!is_array($ligne)){$ligne=array();}
    $number=preg_replace("/[^0-9]/","",(string)($ligne["number"] ?? ""));
    $subject=trim((string)($ligne["subject"] ?? ""));
    $status=strtolower(trim((string)($ligne["status"] ?? "")));

    // L'identifiant du textarea est calcule par field_textarea_normal a partir
    // de suffixid, tire au sort par le CONSTRUCTEUR de template_admin. Il est
    // donc stable pour cette instance-ci, et connu de nous.
    $editorId=md5("replycontent".$tpl->suffixid);

    $h ="<div class='stk-head stk-head-rep'>";
    $h.="<h1 class='stk-h1'>#".htmlspecialchars($number);
    if($subject!==""){$h.=" - ".htmlspecialchars($subject);}
    $h.="</h1>";
    $h.="<span class='ak-pill stk-chip ".($status==="closed"?"ak-off":"ak-info")."'>"
       .($status==="closed"?"{closed}":"{open}")."</span>";
    $h.="</div>";

    // Le dernier message, cite: on repond a quelque chose, et l'operateur ne
    // doit pas avoir a refermer la modale pour se le rappeler.
    $last=support_last_thread($bugid);
    if(is_array($last) && count($last)>0){
        $who=trim((string)($last["createdBy"] ?? ""));
        $ini=support_initials($who);
        $who=($who==="")?"{not_defined}":htmlspecialchars($who);
        $when=htmlspecialchars((string)$tpl->time_to_date($last["createdAt"] ?? 0,true));
        list($human,)=support_split_sysinfo($last["body"] ?? "");
        $txt=support_thread_body($human);
        $h.="<div class='stk-quote'>";
        $h.="<span class='stk-quote-av'>".htmlspecialchars($ini)."</span>";
        $h.="<div class='stk-quote-card'>";
        $h.="<div class='stk-card-h'><span class='stk-from'>$who</span>"
           ."<span class='stk-when'>$when</span></div>";
        if(trim(strip_tags($txt))!==""){$h.="<div class='stk-txt'>$txt</div>";}
        $h.="</div></div>";
    }

    $ARRAY=array();
    $ARRAY["PROGRESS_FILE"]=PROGRESS_DIR."/support.progress";
    $ARRAY["LOG_FILE"]=PROGRESS_DIR."/support.progress.txt";
    $ARRAY["CMD"]="/system/support/run/reply";
    $ARRAY["TITLE"]="{reply_ticket}";
    $ARRAY["AFTER"]="LoadAjax('table-loader-support','$page?table=yes');";
    $prgress=base64_encode(serialize($ARRAY));
    $sync="Loadjs('fw.progress.php?".fw_progress_args_b64($prgress)."&mainid=progress-support-restart')";

    $f=array();
    $f[]=$tpl->field_hidden("reply", $bugid);
    // form_outside insere son contenu DIRECTEMENT dans un <table>: un <div> brut
    // n'y reste pas, le parseur HTML l'expulse hors du tableau (regles de modele
    // de contenu). Mesure sur 192.168.90.88: le bloc « Upload the support tool »
    // remontait AU-DESSUS de l'editeur et l'interrupteur se retrouvait orphelin
    // en bas. Tout bloc de mise en page est donc une vraie ligne de tableau.
    $f[]="<tr><td colspan='2'><div class='stk-lab2'>{your_reply}</div></td></tr>";
    // field_textareacode() monte un CodeMirror -- un editeur de CODE, avec
    // numeros de ligne, pour ecrire un message a un humain. On prend le
    // textarea nu et on lui greffe summernote.
    $f[]=$tpl->field_textarea_normal("replycontent","","","100%","190px");
    // Le commutateur reste le controle natif: sa ligne est un <tr>, l'enfermer
    // dans la carte de la maquette imposerait un <tr> dans un <td> -- invalide,
    // et donc expulse a son tour. La carte se limite au texte explicatif.
    $f[]=support_tool_card($tpl,0,"{include_diagnostic_data}");
    $f=array_merge($f,support_attachments_rows($tpl));

    // Le bloc JS est concatene HORS de l'analyse: #{(.+?)}# lirait chaque
    // accolade JavaScript comme un {jeton}.
    $js="<script>
function stkReplyEditor(){
    var ta=document.getElementById('$editorId');
    if(!ta){return;}
    stkEnsureSummernote(function(ok){
        // Sans editeur, le textarea reste visible et utilisable tel quel: une
        // reponse au support ne doit pas devenir impossible parce qu'un
        // greffon ne s'est pas charge.
        if(!ok){return;}
        try{
            jQuery(ta).summernote({
                height:190,
                placeholder:'".addslashes($tpl->javascript_parse_text("{type_your_reply}"))."',
                toolbar:[['style',['bold','italic','underline','clear']],
                         ['para',['ul','ol']],
                         ['insert',['link']]]
            });
        }catch(e){return;}
        // On NE se fie PAS aux rappels de summernote: cette version expose a la
        // fois l'API 0.6 (onChange a la racine) et 0.8 (callbacks), et le
        // formulaire lit le textarea par document.getElementById(id).value au
        // moment de l'envoi. On recopie donc le contenu editable a la source.
        // On repere l'editeur A PARTIR DU TEXTAREA: summernote l'insere a cote
        // de lui, dans la meme cellule. Un conteneur pose autour du champ ne
        // survit pas -- form_outside ecrit dans un <table>, le parseur en
        // ejecte les <div>.
        var zone=jQuery(ta).parent();
        var sync=function(){
            var ed=zone.find('.note-editable').first();
            if(ed.length){ta.value=ed.html();}
        };
        zone.on('keyup blur paste input','.note-editable',sync);
        zone.on('click','.note-toolbar',function(){setTimeout(sync,50);});
        sync();
    });
}
setTimeout(stkReplyEditor,120);
</script>";

    echo support_ticket_css()
        ."<div class='stk stk-rep'>"
        .$tpl->_ENGINE_parse_body($h)
        .$tpl->form_outside("", @implode("\n",$f), null, "{send_reply}",
             "dialogInstance2.close();dialogInstance1.close();$sync;","AsSystemAdministrator")
        ."</div>"
        .support_editor_js()
        .support_attachments_js($tpl)
        .$js;
    return true;
}

// Le message le plus recent du fil: c'est celui auquel on repond.
function support_last_thread($bugid):array{
    $rows=support_db_get("/system/support/db/ticket/$bugid/threads");
    if(!is_array($rows)){return array();}
    $best=array();$bestAt=-1;
    foreach($rows as $r){
        if(!is_array($r)){continue;}
        $at=intval($r["createdAt"] ?? 0);
        if($at>=$bestAt){$bestAt=$at;$best=$r;}
    }
    return $best;
}

// Initiales de l'avatar. Un nom vide donnerait une pastille muette: on retombe
// sur un caractere neutre plutot que sur du blanc.
function support_initials($name):string{
    $name=trim(preg_replace("/\s+/"," ",(string)$name));
    if($name===""){return "?";}
    $parts=explode(" ",$name);
    $out=mb_substr($parts[0],0,1,"UTF-8");
    if(count($parts)>1){$out.=mb_substr($parts[count($parts)-1],0,1,"UTF-8");}
    return mb_strtoupper($out,"UTF-8");
}

function reply_save(){
    $tpl=new template_admin();
    if(!support_require_admin($tpl)){return;}
    $tpl->CLEAN_POST();

    // Les trois controles APPELAIENT _ENGINE_parse_body sans rien echo: la
    // fonction RENVOIE une chaine. Le corps de reponse restait donc VIDE --
    // c'est la branche de SUCCES du xSave de form_outside (<= 3 caracteres) :
    // la modale se fermait proprement et annoncait une reponse envoyee alors
    // qu'aucune ne partait. Toute erreur passe desormais par post_error(), le
    // seul chemin qui produit le prefixe "jserror:" et donc la fenetre rouge.
    // Et l'ordre etait inverse: strlen() etait evalue AVANT le isset.
    if(!isset($_POST["replycontent"])){
        echo $tpl->post_error("{comment}: {required}");
        return;
    }
    $content=(string)$_POST["replycontent"];
    if(trim($content)===""){
        echo $tpl->post_error("{comment}: {required}");
        return;
    }
    if(strlen($content)>65535){
        echo $tpl->post_error("{comment}: 65535 {characters}");
        return;
    }

    // serialize($_POST) expediait TOUTE cle postee au demon. La liste blanche qui
    // l'a remplace a ete ecrite d'apres le SOUVENIR du formulaire et non d'apres
    // le formulaire: elle cherchait "bugid", un nom qui n'existe nulle part dans
    // cette page, et oubliait "SupportTool". reply_popup() poste TROIS cles --
    // reply (champ cache portant l'ID), SupportTool (1/0) et replycontent -- et
    // Reply() cote demon lit exactement ces trois noms (tickets.go).
    //
    // Resultat mesure sur 192.168.90.88: supportReply contenait
    // a:1:{s:12:"replycontent";...} -- l'ID du ticket etait perdu, donc TOUTE
    // reponse echouait sur « bugID/body missing », case cochee ou non, et la
    // piece jointe n'a jamais pu partir.
    $bugid=intval($_POST["reply"]);
    if($bugid<1){
        // Sans cette garde l'echec ne se voit qu'APRES la fermeture de la modale,
        // en barre rouge, et le texte saisi est perdu. post_error() le montre
        // dans la modale, qui reste ouverte avec son contenu.
        echo $tpl->post_error("{ticket}: {error}");
        return;
    }
    $payload=array(
        "reply"        => $bugid,
        "replycontent" => $content,
        // Le helper poste toujours la cle, a 1 ou a 0; on ne se fie pas a isset.
        "SupportTool"  => (intval($_POST["SupportTool"] ?? 0)==1)?"1":"0",
        // Liste JSON des NOMS de fichiers deposes. Cette liste blanche est
        // justement ce qui avait fait perdre l'ID du ticket: toute cle que le
        // formulaire poste et que le demon lit doit y figurer.
        "attachments"  => (string)($_POST["attachments"] ?? "[]"),
    );
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("supportReply",base64_encode(serialize($payload)));
    return admin_tracks("Support: replied to ticket $bugid");
}

function comment_js(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $id=intval($_GET["show-comment-js"]);
    $bugid=intval($_GET["bugid"]);
    $ligne=support_db_get("/system/support/db/ticket/$bugid");
    $id_temp=intval(is_array($ligne)?($ligne["id"] ?? 0):0);
    if($id_temp==0){
        $tpl->js_error("{already_removed}");
        return;
    }
    $number=$ligne["number"];
    $subject=$ligne["subject"];
    $tpl->js_dialog2("#$number - $subject", "$page?show-comment-popup=$id");

}
function comment_popup(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $id=intval($_GET["show-comment-popup"]);
    $ligne=support_db_get("/system/support/db/thread/$id");
    if(!is_array($ligne)){$ligne=array();}
    // Meme provenance que le fil: le corps vient du portail et passe par
    // l'assainisseur; le nom d'auteur, lui, est du texte et s'echappe.
    // Ici le corps n'est PAS tronque -- c'est la vue « message entier ».
    $content=support_safe_html($ligne["body"] ?? "");
    $content=preg_replace("/[\r\n]+/", "\n", $content);
    $content=trim(preg_replace("/^(<br \/>)/", "",  $content, 1));
    $content=nl2br($content);
    $creator=htmlspecialchars(trim((string)($ligne["createdBy"] ?? "")));
    $time=$ligne["createdAt"] ?? 0;
    $when=htmlspecialchars((string)$tpl->time_to_date($time,true));
    // Le corps porte desormais des <div> (liste blanche elargie): un <p>
    // englobant serait invalide et le parseur en expulserait le contenu --
    // meme regle de modele de contenu que les blocs du formulaire. La feuille
    // est emise ici car cette modale est une reponse HTTP a part, et c'est elle
    // qui contient les images a la largeur de la fenetre.
    echo support_ticket_css()."<div class=\"social-feed-box\">
       			<div class=\"social-avatar\">
	                <div class=\"media-body\">
	                	<strong>$creator</strong>
	                    <small class=\"text-muted\">$when</small>
	                </div>
                 </div>
                 <div class=\"social-body\">
                     <div class=\"stk-html\">{$content}</div>
                 </div>
                 <div class=\"social-footer\"></div>
            </div><script>NoSpinner();</script>";
}

function bug_js(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $id=intval($_GET["show-js"]);
    $ligne=support_db_get("/system/support/db/ticket/$id");
    $id_temp=intval(is_array($ligne)?($ligne["id"] ?? 0):0);
    if($id_temp==0){
        $tpl->js_error("{already_removed}");
        return;
    }
    $subject=$ligne["subject"];
    $number=$ligne["number"];

    // Titre court: l'en-tete de la modale porte deja le numero et le sujet, et
    // les repeter donnait deux fois la meme ligne a 40px d'ecart. Largeur
    // portee a 1180: a 900 la colonne du fil tombait a 498px, sous la mesure
    // de lecture confortable pour des messages de support.
    $tpl->js_dialog1("{technical_support}", "$page?show-popup=$id",1180);
}

// ── Refonte « Luminous Support System » (maquette Stitch) ───────────────────
//
// Feuille de style LOCALE et portee au prefixe stk-. La maquette d'origine
// s'appuyait sur le play-CDN Tailwind et trois polices Google (Hanken Grotesk,
// Inter, JetBrains Mono): inutilisable ici. Une appliance est souvent sur un
// reseau ferme -- la modale s'afficherait alors sans aucun style -- et le CDN
// Tailwind reecrirait la mise en page de Bootstrap/INSPINIA qui l'entoure. Les
// classes utilitaires sont donc traduites en CSS classique, comme le fait deja
// awc_table_style().
//
// La typographie suit la meme logique: le corps HERITE de la police du theme
// (regle 1 des tableaux awc-*: fixer une font-family est ce qui fait qu'un
// bloc a l'air etranger a la console). Seul le monospace est conserve, pour
// les blocs techniques -- c'est la convention du depot, et c'est justement ce
// que la maquette demande pour eux.
// Carte « Upload the support tool » de la maquette: interrupteur a gauche,
// titre et explication a droite, le tout sur un fond bleute.
//
// field_checkbox() rend une LIGNE de tableau (<tr><td>libelle</td><td>champ</td>),
// qu'on ne peut pas enfermer dans la carte -- un <tr> dans un <td> est invalide
// et le parseur l'en expulserait, comme il l'a fait des <div> du formulaire.
//
// On appelle donc quand meme le helper, pour l'unique effet qui nous interesse:
// il enregistre la ligne de collecte du champ (XHR est PRIVEE, on ne peut pas
// l'alimenter autrement). Son balisage est jete, et on pose le notre avec le
// MEME identifiant -- md5("<nom><suffixid>"), suffixid etant tire au sort par
// le constructeur donc stable pour cette instance. La collecte lit ce champ par
// getElementById: elle trouve le notre.
function support_tool_card($tpl,$default,$explain):string{
    $id=md5("SupportTool".$tpl->suffixid);
    $tpl->field_checkbox("SupportTool","",intval($default)); // pour la collecte seule
    $checked=(intval($default)==1)?"checked":"";
    $sw="<div class='onoffswitch'>"
       ."<input type='checkbox' name='SupportTool' class='onoffswitch-checkbox' id='$id' $checked>"
       ."<label class='onoffswitch-label' for='$id'>"
       ."<span class='onoffswitch-inner'></span><span class='onoffswitch-switch'></span>"
       ."</label></div>";
    return "<tr><td colspan='2'><div class='stk-toolcard'>"
          ."<div class='stk-toolcard-sw'>$sw</div>"
          ."<div><div class='stk-tool-t'>{attach_support_tool}</div>"
          ."<div class='stk-tool-s'>".$explain."</div></div>"
          ."</div></td></tr>";
}

// ── Pieces jointes ─────────────────────────────────────────────────────────
//
// Le televersement passe par /fw.upload.php (greffon jQuery fileupload, charge
// globalement par class.heads.builder.inc), qui depose dans
// ressources/conf/upload/ et rend le NOM du fichier.
//
// On ne transmet QUE ce nom, jamais un chemin. Le helper historique
// field_upload_file() poste le chemin serveur complet: le daemon lirait alors
// un chemin choisi par le client, et une requete forgee nommant
// /home/artica/SQLITE/admins.db enverrait la base des comptes administrateurs
// dans un ticket. Le daemon recolle le nom a un repertoire fixe (attachments.go),
// ce qui rend la traversee impossible par construction.
//
// La liste est portee en JSON, et non separee par des virgules: un nom de
// fichier peut en contenir une.
function support_attachments_rows($tpl):array{
    $rows=array();
    $rows[]="<tr><td colspan='2'>"
        ."<div class='stk-lab2'>{attachments}</div>"
        ."<div id='stk-att-list' class='stk-att-list'></div>"
        ."<span class='btn btn-white btn-sm fileinput-button stk-att-btn'>"
        ."<i class='fas fa-paperclip'></i>&nbsp;<span id='stk-att-bt'>{upload_a_file}</span>"
        ."<input id='stk-att-input' type='file' name='files' multiple "
        ."accept='".support_attachments_accept()."'>"
        ."</span>"
        ."<div class='stk-tool-s'>{attachments_hint}</div>"
        ."</td></tr>";
    // Champ accumulateur: form_outside lit sa valeur AU MOMENT DE L'ENVOI
    // (document.getElementById(id).value), ce qu'un field_hidden ne ferait pas
    // -- celui-la fige sa valeur au rendu, donc une liste construite en JS ne
    // partirait jamais.
    $rows[]=$tpl->field_textarea_normal("attachments","","[]","100%","30px");
    return $rows;
}

// Meme liste que la liste blanche du daemon (attachments.go). Cote navigateur
// c'est un CONFORT: le vrai refus est cote daemon, qui ne fait confiance a rien
// de ce qui lui arrive.
function support_attachments_exts():array{
    return array("png","jpg","jpeg","gif","webp","bmp","svg",
                 "txt","log","conf","cfg","ini","csv","json","xml",
                 "pdf","zip","gz","tgz","bz2");
}
function support_attachments_accept():string{
    $out=array();
    foreach(support_attachments_exts() as $e){$out[]=".".$e;}
    return @implode(",",$out);
}

function support_attachments_js($tpl):string{
    $idAcc=md5("attachments".$tpl->suffixid);
    $btname=addslashes($tpl->javascript_parse_text("{upload_a_file}"));
    $uploading=addslashes($tpl->javascript_parse_text("{uploading}"));
    $failed=addslashes($tpl->javascript_parse_text("{failed}"));
    $tooMany=addslashes($tpl->javascript_parse_text("{too_many_files}"));
    $badType=addslashes($tpl->javascript_parse_text("{file_type_not_allowed}"));
    $exts=json_encode(support_attachments_exts());
    return "<script>
function stkAttachments(){
    var acc=document.getElementById('$idAcc');
    if(!acc || typeof jQuery=='undefined'){return;}
    // Le champ accumulateur n'a pas a etre vu: l'operateur manipule la liste
    // au-dessus, form_outside lit ce champ a l'envoi.
    jQuery(acc).closest('tr').hide();
    var EXT=$exts, MAX=5, names=[];
    try{names=JSON.parse(acc.value)||[];}catch(e){names=[];}
    function esc(s){return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;')
                                    .replace(/>/g,'&gt;').replace(/\"/g,'&quot;');}
    function render(){
        acc.value=JSON.stringify(names);
        var h='';
        for(var i=0;i<names.length;i++){
            h+=\"<span class='stk-att'><i class='fas fa-paperclip'></i> \"+esc(names[i])
              +\" <a href=\\\"javascript:blur();\\\" class='stk-att-x' data-i='\"+i+\"'>&times;</a></span>\";
        }
        document.getElementById('stk-att-list').innerHTML=h;
    }
    jQuery('#stk-att-list').off('click','.stk-att-x').on('click','.stk-att-x',function(){
        names.splice(parseInt(this.getAttribute('data-i'),10),1);render();return false;
    });
    jQuery('#stk-att-input').fileupload({
        url:'/fw.upload.php', dataType:'json',
        add:function(e,data){
            var n=(data.files && data.files[0])?data.files[0].name:'';
            var ext=n.split('.').pop().toLowerCase();
            if(EXT.indexOf(ext)<0){
                swal({title:'$badType',text:n,type:'error',confirmButtonColor:'#ed5565'});
                return;
            }
            if(names.length>=MAX){
                swal({title:'$tooMany',text:'$badType',type:'error',confirmButtonColor:'#ed5565'});
                return;
            }
            document.getElementById('stk-att-bt').innerHTML='$uploading....';
            data.submit();
        },
        progressall:function(e,data){
            var pr=parseInt(data.loaded/data.total*100,10);
            document.getElementById('stk-att-bt').innerHTML='$uploading '+pr+'%';
        },
        done:function(e,data){
            document.getElementById('stk-att-bt').innerHTML='$btname';
            // files[0].name est rendu MEME quand l'ecriture a echoue (disque
            // plein -> error): sans ce test on ajoute un fichier inexistant,
            // que le daemon journaliserait ensuite comme illisible.
            var f=(data.result && data.result.files)?data.result.files[0]:null;
            if(f==null || f.error){
                swal({title:'$failed',text:(f&&f.error)?f.error:'$failed',type:'error',confirmButtonColor:'#ed5565'});
                return;
            }
            if(names.indexOf(f.name)<0){names.push(f.name);}
            render();
        }
    });
    render();
}
setTimeout(stkAttachments,160);
</script>";
}

// Chargeur de summernote, partage par les deux modales qui portent un editeur.
// Il etait defini DANS le script de reply_popup(): ouvrir la creation de ticket
// sans avoir ouvert une reponse laissait stkEnsureSummernote introuvable, et
// l'editeur ne montait pas. Chaque modale est une reponse HTTP a part, donc
// chacune emet le chargeur; le redefinir est sans consequence.
function support_editor_js():string{
    return "<script>
function stkEnsureSummernote(cb){
    if(typeof jQuery=='undefined'){cb(false);return;}
    if(jQuery.fn && jQuery.fn.summernote){cb(true);return;}
    if(!document.getElementById('stk-sn-css')){
        var l=document.createElement('link');l.id='stk-sn-css';l.rel='stylesheet';
        l.href='angular/plugins/summernote/summernote.css';document.head.appendChild(l);
        var l2=document.createElement('link');l2.rel='stylesheet';
        l2.href='angular/plugins/summernote/summernote-bs3.css';document.head.appendChild(l2);
    }
    var s=document.createElement('script');
    s.src='angular/js/plugins/summernote/summernote.min.js';
    s.onload=function(){cb(true);};
    s.onerror=function(){cb(false);};
    document.head.appendChild(s);
}
</script>";
}

function support_ticket_css():string{
    if(isset($GLOBALS["STK_CSS_DONE"])){return "";}
    $GLOBALS["STK_CSS_DONE"]=true;
    return "<style>
.stk{background:#f8f9ff;color:#0b1c30}
.stk *{box-sizing:border-box}
.stk-head{display:flex;align-items:center;justify-content:space-between;gap:16px;flex-wrap:wrap;padding:18px 24px;background:#fff;border-bottom:1px solid #bec8d2}
.stk-h1{font-size:24px;font-weight:700;line-height:32px;letter-spacing:-0.02em;color:#0b1c30;margin:0}
.stk-chip{margin-left:10px;letter-spacing:.05em;text-transform:uppercase;vertical-align:middle}
.stk-body{display:flex;flex-wrap:wrap;align-items:stretch}
.stk-aside{flex:0 0 320px;min-width:250px;padding:24px;border-right:1px solid #bec8d2}
.stk-main{flex:1 1 420px;min-width:300px;padding:24px}
.stk-blk{margin:0 0 24px}
.stk-blk:last-child{margin-bottom:0}
.stk-lab{font-size:12px;font-weight:700;line-height:16px;letter-spacing:.05em;text-transform:uppercase;color:#3e4850;margin:0 0 8px}
.stk-who{display:flex;align-items:center;gap:12px}
.stk-av{width:44px;height:44px;border-radius:9999px;background:#fd761a;color:#fff;display:inline-flex;align-items:center;justify-content:center;font-size:17px;flex:0 0 auto}
.stk-name{font-size:15px;font-weight:700;line-height:20px;color:#0b1c30;word-break:break-word}
.stk-val{display:flex;align-items:center;gap:8px;font-size:14px;line-height:20px;color:#0b1c30}
.stk-val i{color:#3e4850;font-size:13px}
.stk-code{background:#fff;border:1px solid #bec8d2;border-radius:8px;padding:12px 14px;font-family:monospace;font-size:12px;line-height:18px;color:#3e4850;overflow-x:auto;white-space:pre-wrap;word-break:break-word;margin:0}
.stk-row{display:flex;gap:14px;align-items:flex-start;margin:0 0 16px}
.stk-row>.stk-card{flex:1 1 auto;min-width:0}
.stk-card-in{padding:16px 18px}
.stk-card-h{display:flex;align-items:baseline;justify-content:space-between;gap:12px;flex-wrap:wrap;margin:0 0 6px}
.stk-from{font-size:14px;font-weight:700;color:#0b1c30}
.stk-when{font-size:12px;line-height:16px;color:#3e4850;white-space:nowrap}
.stk-txt{font-size:14px;line-height:21px;color:#0b1c30;word-break:break-word}
.stk-txt img,.stk-html img{max-width:100%;height:auto;border-radius:6px;margin:6px 0}
.stk-txt table,.stk-html table{max-width:100%;border-collapse:collapse}
.stk-txt td,.stk-txt th,.stk-html td,.stk-html th{border:1px solid #bec8d2;padding:4px 8px}
.stk-txt pre,.stk-html pre{white-space:pre-wrap;word-break:break-word}
.stk-html{font-size:14px;line-height:21px;color:#0b1c30;word-break:break-word}
.stk-acts{display:flex;justify-content:flex-end;gap:18px;flex-wrap:wrap;padding:10px 18px;border-top:1px solid rgba(190,200,210,.55);background:#eff4ff;border-radius:0 0 8px 8px}
.stk-act{display:inline-flex;align-items:center;gap:6px;font-size:13px;font-weight:700;color:#006591;text-decoration:none !important;cursor:pointer}
.stk-act:hover{color:#004c6e;text-decoration:underline !important}
.stk-sys{background:#e5eeff;border:1px solid #bec8d2;border-radius:12px;padding:16px 18px;margin:0 0 16px}
.stk-sys-h{display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap;margin:0 0 10px}
.stk-sys-t{font-size:13px;color:#3e4850}
.stk-sys-b{display:inline-flex;align-items:center;gap:8px;font-size:13px;font-weight:700;color:#006591}
.stk-empty{padding:28px;text-align:center;color:#6e7881;font-size:14px}
.stk-rep{padding:0 0 4px}
.stk-head-rep{border-bottom:1px solid #bec8d2;margin:0 0 20px}
.stk-quote{display:flex;gap:14px;align-items:flex-start;margin:0 0 22px}
.stk-quote-av{width:40px;height:40px;border-radius:9999px;background:#d3e4fe;color:#004c6e;display:inline-flex;align-items:center;justify-content:center;font-size:13px;font-weight:700;flex:0 0 auto}
.stk-quote-card{flex:1 1 auto;min-width:0;background:#eff4ff;border:1px solid #bec8d2;border-radius:8px;padding:14px 16px}
.stk-lab2{font-size:14px;font-weight:700;line-height:20px;color:#0b1c30;margin:14px 0 8px}
.stk-sub{font-size:14px;line-height:20px;color:#3e4850;margin:4px 0 0}
.stk-att-list{display:flex;flex-wrap:wrap;gap:8px;margin:0 0 8px}
.stk-att{display:inline-flex;align-items:center;gap:6px;background:#eff4ff;border:1px solid #bec8d2;border-radius:6px;padding:4px 10px;font-size:13px;color:#0b1c30}
.stk-att-x{color:#ba1a1a;font-weight:700;text-decoration:none !important;font-size:15px;line-height:1}
.stk-toolcard{display:flex;align-items:center;gap:16px;background:#e5eeff;border:1px solid #bec8d2;border-radius:8px;padding:16px 18px;margin:18px 0 0}
.stk-toolcard-sw{flex:0 0 auto}
.stk-toolcard .stk-tool-t{font-size:15px;font-weight:700;line-height:20px;color:#0b1c30}
.stk-toolcard .stk-tool-s{margin:2px 0 0}
.stk-att-btn{position:relative;overflow:hidden;display:inline-flex;align-items:center;gap:8px;background:#fff;border:1px solid #bec8d2;border-radius:8px;padding:9px 18px;font-size:14px;font-weight:600;color:#0b1c30}
.stk-att-btn:hover{background:#eff4ff;border-color:#006591;color:#0b1c30}
.stk-att-btn input[type=file]{position:absolute;top:0;right:0;opacity:0;cursor:pointer;font-size:100px}
.stk-tool-s{font-size:13px;line-height:18px;color:#3e4850;margin:-6px 0 4px}
.stk-rep .note-editor{border-color:#bec8d2;border-radius:8px}
.stk-rep .note-toolbar{background:#eff4ff;border-bottom:1px solid #bec8d2}
@media(max-width:820px){.stk-aside{flex:1 1 100%;border-right:0;border-bottom:1px solid #bec8d2}.stk-head{padding:14px 16px}.stk-aside,.stk-main{padding:16px}}
</style>";
}

function bug_popup(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $bugid=intval($_GET["show-popup"]);

    $ligne=support_db_get("/system/support/db/ticket/$bugid");
    if(!is_array($ligne)){$ligne=array();}
    $number=preg_replace("/[^0-9]/","",(string)($ligne["number"] ?? ""));
    $subject=trim((string)($ligne["subject"] ?? ""));
    $createdBy=trim((string)($ligne["createdBy"] ?? ""));
    $createdBy=($createdBy==="")?"{not_defined}":htmlspecialchars($createdBy);
    $createdAt=htmlspecialchars((string)$tpl->time_to_date($ligne["createdAt"] ?? 0,true));
    $updatedAt=htmlspecialchars((string)$tpl->time_to_date($ligne["updatedAt"] ?? 0,true));

    $h=array();
    $h[]="<div class='stk'>";

    // En-tete. La maquette dessinait aussi une croix de fermeture: js_dialog1
    // en fournit deja une dans sa barre de titre, la redoubler donnerait deux
    // croix a 40px d'ecart.
    $h[]="<div class='stk-head'>";
    $h[]="<h1 class='stk-h1'>{ticket} #".htmlspecialchars($number);
    if($subject!==""){$h[]="<span class='ak-pill ak-info stk-chip'>".htmlspecialchars($subject)."</span>";}
    $h[]="</h1>";
    $h[]="<a href=\"javascript:blur();\" class='ak-btn ak-btn-primary ak-btn-lg' OnClick=\"Loadjs('$page?reply-js=$bugid');\">"
        ."<i class='fas fa-reply'></i> {reply}</a>";
    $h[]="</div>";

    $h[]="<div class='stk-body'>";

    // Colonne de gauche: qui, quand, et sur quelle machine.
    $h[]="<div class='stk-aside'>";
    $h[]="<div class='stk-blk'><div class='stk-lab'>{requester}</div>"
        ."<div class='stk-who'><span class='stk-av'><i class='fas fa-user'></i></span>"
        ."<span class='stk-name'>$createdBy</span></div></div>";
    $h[]="<div class='stk-blk'><div class='stk-lab'>{created}</div>"
        ."<div class='stk-val'><i class='fas fa-calendar-day'></i>$createdAt</div></div>";
    $h[]="<div class='stk-blk'><div class='stk-lab'>{last_update}</div>"
        ."<div class='stk-val'><i class='fas fa-clock'></i>$updatedAt</div></div>";
    $snap=support_ticket_snapshot($bugid);
    if(count($snap)>0){
        $lines=array();
        foreach($snap as $k=>$v){$lines[]=htmlspecialchars($k).": ".htmlspecialchars($v);}
        $h[]="<div class='stk-blk'><div class='stk-lab'>{system_snapshot}</div>"
            ."<div class='stk-code'>".@implode("\n",$lines)."</div></div>";
    }
    $h[]="</div>";

    // Colonne de droite: le fil, charge a part -- il peut etre long et il est
    // le seul morceau que la reponse d'un ticket doit rafraichir.
    $h[]="<div class='stk-main'>";
    $h[]="<div id='support-tool-progress'></div>";
    $h[]="<div id='bug-time-line'></div>";
    $h[]="</div>";

    $h[]="</div></div>";

    // La feuille est concatenee HORS de l'analyse: #{(.+?)}# lirait chaque
    // corps de regle CSS comme un {jeton}, soit une interrogation memcached
    // par regle.
    echo support_ticket_css()
        .$tpl->_ENGINE_parse_body(@implode("\n",$h))
        ."<script>LoadAjax('bug-time-line','$page?timeline=$bugid');</script>";
}

// Instantane systeme de la colonne de gauche.
//
// Aucune API ne le fournit: le demon COLLE ce bloc au corps de chaque message
// (supporttool/tickets.go, systemInfoFooter). On lit donc le plus recent -- il
// decrit l'appliance telle qu'elle est aujourd'hui, les anciens decrivent ce
// qu'elle etait au moment du message.
function support_ticket_snapshot($bugid):array{
    $rows=support_db_get("/system/support/db/ticket/$bugid/threads");
    if(!is_array($rows)){return array();}
    $best=null;$bestAt=-1;
    foreach($rows as $r){
        if(!is_array($r)){continue;}
        $b=(string)($r["body"] ?? "");
        if(stripos($b,"SYSTEM INFO")===false){continue;}
        $at=intval($r["createdAt"] ?? 0);
        if($at>=$bestAt){$bestAt=$at;$best=$b;}
    }
    if($best===null){return array();}
    return support_sysinfo_fields($best);
}

// Le bloc est du HTML ("- <b>Artica Version: </b>4.50 SP7 Hotfix:<br>- ..."):
// on retablit les sauts de ligne AVANT strip_tags, sans quoi tout se retrouve
// sur une seule ligne et une valeur avale la suivante.
function support_sysinfo_fields($body):array{
    $t=preg_replace("#<br\s*/?>#i","\n",(string)$body);
    $t=strip_tags($t);
    $t=html_entity_decode($t,ENT_QUOTES,"UTF-8");
    $out=array();
    foreach(array("Artica Version","OS Version","Proxy Version","Postfix Version","License") as $k){
        if(!preg_match("/".preg_quote($k,"/")."\s*:\s*([^\n]+)/i",$t,$m)){continue;}
        $v=trim($m[1]);
        // « Hotfix: » en fin de ligne quand il n'y en a aucun: une etiquette
        // sans valeur n'apprend rien.
        $v=trim(preg_replace("/\s*Hotfix\s*:\s*$/i","",$v));
        if($v===""){continue;}
        if(strlen($v)>58){$v=substr($v,0,58)."...";}
        $out[$k]=$v;
    }
    return $out;
}

// ── Fil de discussion d'un ticket ──────────────────────────────────────────
//
// Le corps d'un message vient du PORTAIL DE SUPPORT et contient du HTML: le
// code d'origine le retirait d'un preg_replace("/^(<br \/>)/") puis d'un
// nl2br(), ce qui ne se comprend que si l'on attend du balisage. L'echapper
// entierement afficherait les balises a l'ecran; le laisser passer tel quel
// donne une XSS stockee a quiconque ecrit dans le portail. On assainit donc:
// liste blanche de balises de mise en forme, puis retrait des gestionnaires
// d'evenements et des URI executables qui survivent a strip_tags().
function support_safe_html($raw):string{
    $html=strip_tags((string)$raw,
        "<p><br><b><strong><i><em><u><s><ul><ol><li><blockquote><pre><code><a>"
        ."<div><span><img><hr><h1><h2><h3><h4><h5><h6>"
        ."<table><thead><tbody><tfoot><tr><td><th><font>");
    // on...="..." / on...='...' / on...=valeur
    $html=preg_replace('#\son[a-z]+\s*=\s*"[^"]*"#i','',$html);
    $html=preg_replace("#\son[a-z]+\s*=\s*'[^']*'#i",'',$html);
    $html=preg_replace('#\son[a-z]+\s*=\s*[^\s>]+#i','',$html);
    // href="javascript:..." / data:... , y compris obfusque par des espaces
    $html=preg_replace('#(href|src)\s*=\s*("|\x27)?\s*(javascript|data|vbscript)\s*:#i','$1="#',$html);
    return $html;
}

// Le corps sort du portail avec des sauts de ligne bruts: on les normalise
// avant nl2br, et on retire le <br> de tete que le portail ajoute parfois.
function support_thread_body($raw):string{
    $c=support_safe_html($raw);
    $c=preg_replace("/[\r\n]+/","\n",$c);
    $c=trim(preg_replace("/^(<br \/>)/","",$c,1));
    $c=support_balance_tags($c);
    return nl2br($c);
}

// La coupure au marqueur SYSTEM INFO tombe au MILIEU du HTML du portail: le
// marqueur est enveloppe dans <b>...</b>, la partie humaine finit donc sur un
// <b> jamais referme.
//
// Un navigateur n'ignore pas une balise de mise en forme non fermee: il la
// RECREE dans les elements suivants (algorithme d'adoption du parseur HTML).
// Mesure sur 192.168.90.88: le <b> orphelin d'une carte ressurgissait comme
// unique enfant de .stk-sys-h deux blocs plus bas, qui n'avait donc plus deux
// enfants a repartir -- « System update » et « Support tool » se collaient.
// Le symptome etait une mise en page, la cause un balisage deseqilibre
// ailleurs: on repare a la source, pas par du CSS.
function support_balance_tags($html):string{
    // Une balise tranchee par la troncature ne se referme pas: elle s'enleve.
    $html=preg_replace('#<[^>]*$#','',(string)$html);
    // <br> est vide, il n'entre pas dans le compte.
    // Cette liste doit couvrir TOUTE balise non vide de la liste blanche de
    // support_safe_html: elargir l'une sans l'autre laisse fuir des balises
    // ouvertes. Vecu: <div> autorise mais pas equilibre, et le corps reel
    // ("<div>texte<div><br><div><img...>") ouvre trois <div> avant le marqueur
    // SYSTEM INFO -- tout ce qui suivait s'imbriquait dedans, chaque carte du
    // fil se decalant un peu plus vers la droite.
    foreach(array("b","strong","i","em","u","s","a","li","ul","ol","blockquote",
                  "pre","code","p","div","span","font",
                  "table","thead","tbody","tfoot","tr","td","th",
                  "h1","h2","h3","h4","h5","h6") as $t){
        $open=preg_match_all("#<$t(\s[^>]*)?>#i",$html);
        $close=preg_match_all("#</$t\s*>#i",$html);
        if($open>$close){$html.=str_repeat("</$t>",$open-$close);}
    }
    return $html;
}

// Le bloc systeme est colle a CHAQUE message et noie le texte humain, qui tient
// souvent en une ligne. La maquette le sort dans une carte a lui: on coupe donc
// le corps au marqueur. Les balises ouvertes laissees en amont de la coupure
// sont retirees par la liste blanche de support_safe_html.
function support_split_sysinfo($raw):array{
    $s=(string)$raw;
    $pos=stripos($s,"############ SYSTEM INFO");
    if($pos===false){return array($s,"");}
    return array(substr($s,0,$pos),substr($s,$pos));
}

// json_decode rend NULL sur une chaine vide ou malformee. Le foreach sur ce
// NULL etait une TypeError FATALE en PHP 8: tout le fil disparaissait des
// qu'UN message n'avait pas de piece jointe.
function support_thread_attachments($raw):string{
    $attachments=json_decode((string)$raw);
    if(!is_array($attachments)){return "";}
    $out="";
    foreach($attachments as $val){
        if(!is_scalar($val)){continue;}
        $url=trim((string)$val);
        if($url===""){continue;}
        // L'URL venait du portail et partait CRUE dans un href.
        if(!preg_match('#^https?://#i',$url)){continue;}
        $out.="<a href=\"".htmlspecialchars($url,ENT_QUOTES)."\" class='stk-act'"
             ." target=\"_NEW\" rel=\"noopener noreferrer\">"
             ."<i class='fas fa-paperclip'></i> {attachment}</a>";
    }
    return $out;
}

// Un bloc du fil: la carte du message humain, puis -- si le demon y a colle son
// bloc -- la carte technique qui le porte.
function support_thread_block($tpl,$page,$ligne,$bugid):string{
    $id=intval($ligne["id"] ?? 0);
    $creator=trim((string)($ligne["createdBy"] ?? ""));
    $creator=($creator==="")?"{not_defined}":htmlspecialchars($creator);
    $when=htmlspecialchars((string)$tpl->time_to_date($ligne["createdAt"] ?? 0,true));

    list($human,$sys)=support_split_sysinfo($ligne["body"] ?? "");
    $body=support_thread_body($human);
    $btattch=support_thread_attachments($ligne["attachments"] ?? "");

    $h ="<div class='stk-row'>";
    $h.="<span class='stk-av'><i class='fas fa-user'></i></span>";
    $h.="<div class='ak-card stk-card'><div class='stk-card-in'>";
    $h.="<div class='stk-card-h'><span class='stk-from'>$creator</span>"
       ."<span class='stk-when'>$when</span></div>";
    // Un message dont le corps n'etait QUE le rapport systeme se retrouve sans
    // texte humain une fois la coupure faite: on n'emet pas un bloc vide, qui
    // laisse un blanc se lisant comme un defaut d'affichage.
    if(trim(strip_tags($body))!==""){$h.="<div class='stk-txt'>$body</div>";}
    $h.="</div><div class='stk-acts'>$btattch"
       ."<a href=\"javascript:blur();\" class='stk-act'"
       ." OnClick=\"Loadjs('$page?show-comment-js=$id&bugid=$bugid');\">"
       ."<i class='fas fa-magnifying-glass'></i> {view_message}</a>";
    $h.="</div></div></div>";

    if(trim($sys)!==""){
        $txt=html_entity_decode(strip_tags(preg_replace("#<br\s*/?>#i","\n",$sys)),ENT_QUOTES,"UTF-8");
        $txt=trim(preg_replace("/\n{3,}/","\n\n",$txt));
        if(strlen($txt)>900){$txt=substr($txt,0,900)."\n<...";}
        $h.="<div class='stk-sys'><div class='stk-sys-h'>"
           ."<span class='stk-sys-t'>{system_update}</span>"
           ."<span class='stk-sys-b'><i class='fas fa-robot'></i> {support_tool}</span></div>"
           ."<div class='stk-code'>".htmlspecialchars($txt)."</div></div>";
    }
    return $h;
}

function bug_timeline(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $bugid=intval($_GET["timeline"]);

    $results=support_db_get("/system/support/db/ticket/$bugid/threads");
    if(!is_array($results)){$results=array();}

    // Les derniers messages en premier: sur un fil de support, ce qu'on ouvre
    // le ticket pour lire est la reponse qui vient d'arriver, pas la demande
    // d'origine.
    //
    // On TRIE sur createdAt plutot que de renverser le tableau: l'ordre rendu
    // par l'API (ORDER BY createdAt ASC, sur une colonne TEXTE) est une
    // convention qu'on ne veut pas avoir a supposer ici.
    usort($results,function($a,$b){
        if(!is_array($a) || !is_array($b)){return 0;}
        $x=intval(isset($a["createdAt"])?$a["createdAt"]:0);
        $y=intval(isset($b["createdAt"])?$b["createdAt"]:0);
        if($x===$y){
            // Le portail horodate a la SECONDE: deux messages d'un meme envoi
            // portent la meme valeur. Sans ce departage leur ordre relatif
            // dependrait de l'implementation du tri.
            return intval(isset($b["id"])?$b["id"]:0) <=> intval(isset($a["id"])?$a["id"]:0);
        }
        return $y <=> $x;
    });

    $blocks=array();
    foreach ($results as $ligne){
        if(!is_array($ligne)){continue;}
        $blocks[]=support_thread_block($tpl,$page,$ligne,$bugid);
    }

    $html=array();
    if(count($blocks)==0){
        $html[]="<div class='stk-empty'>{no_data}</div>";
    }else{
        $html[]=@implode("\n",$blocks);
    }

    // Le fragment est charge par LoadAjax: sans NoSpinner() le voile de
    // chargement de la modale ne se leve jamais. La feuille est reemise ici
    // car ce fragment est une reponse HTTP a part -- le garde
    // $GLOBALS["STK_CSS_DONE"] est par requete, pas par page.
    echo support_ticket_css()
        .$tpl->_ENGINE_parse_body(@implode("\n",$html))
        ."<script>NoSpinner();</script>";
}


function delete_bug_js(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $id=intval($_GET["delete-bug-js"]);
    // $_GET["md"] repartait tel quel dans $('#...').remove(): c'est un md5.
    $md=preg_replace('/[^a-f0-9]/','',strtolower((string)($_GET["md"] ?? "")));
    if($id<1||$md==""){return;}
    $ligne=support_db_get("/system/support/db/ticket/$id");
    if(!is_array($ligne)){$ligne=array();}
$number=$ligne["number"] ?? 0;
    $id_temp=intval($ligne["id"] ?? 0);
    if($id_temp==0){
        $tpl->js_error("{already_removed}");
        echo "$('#$md').remove();\n";
        return;
    }

    $ARRAY["PROGRESS_FILE"]=PROGRESS_DIR."/support.progress";
    $ARRAY["LOG_FILE"]=PROGRESS_DIR."/support.progress.txt";
    $ARRAY["CMD"]="/system/support/run/delete-bug/$id";
    $ARRAY["TITLE"]="{delete} ticket #$number";
    $ARRAY["AFTER"]="$('#$md').remove();";
    $prgress=base64_encode(serialize($ARRAY));
    $jsrestart="Loadjs('fw.progress.php?".fw_progress_args_b64($prgress)."&mainid=progress-support-restart')";


    $subject=$ligne["subject"] ?? "";
    $tpl->js_confirm_delete("#$number - $subject", "none", $subject,$jsrestart);

}

function create_ticket_js(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    // Titre court: l'en-tete de la modale porte deja « New Ticket » et son
    // sous-titre. La barre affichait « Artica Technical Support <mail> New
    // Ticket », soit la meme information deux fois a 40px d'ecart.
    $tpl->js_dialog1("{technical_support}", "$page?create-popup=yes");

}
function create_ticket_popup(){
    $page=CurrentPageName();
    $tpl=new template_admin();

    // unserialize() rend FALSE sur une valeur absente ou illisible. Le code
    // d'origine enchainait sur $DEF["priority"] et $DEF["subject"]: ecrire une
    // cle dans un booleen est deprecie en PHP 8.1 et devient une erreur fatale
    // ensuite, et les lectures sans isset() emettaient un avertissement a
    // chaque ouverture de la modale.
    $DEF=@unserialize(base64_decode(trim((string)$GLOBALS["CLASS_SOCKETS"]->GET_INFO("supportCreateBug"))));
    if(!is_array($DEF)){$DEF=array();}
    $defPriority=isset($DEF["priority"])?$DEF["priority"]:7;
    $defSubject =isset($DEF["subject"]) ?(string)$DEF["subject"]:"";
    $defComment =isset($DEF["comment"]) ?(string)$DEF["comment"]:"";
    $defTool    =isset($DEF["SupportTool"])?intval($DEF["SupportTool"]):0;

    // Ces sept valeurs sont celles que le portail attend: on ne touche pas aux
    // libelles, les changer changerait ce qui part dans le ticket.
    $PRIORITY=array("1"=>"System Down","2"=>"Critical","3"=>"Urgent","4"=>"Important",
                    "5"=>"Low","6"=>"Monitor","7"=>"Informational");

    $ARRAY=array();
    $ARRAY["PROGRESS_FILE"]=PROGRESS_DIR."/support.progress";
    $ARRAY["LOG_FILE"]=PROGRESS_DIR."/support.progress.txt";
    $ARRAY["CMD"]="/system/support/run/create-bug";
    $ARRAY["TITLE"]="{new_ticket}";
    $ARRAY["AFTER"]="LoadAjax('table-loader-support','$page?table=yes');";
    $prgress=base64_encode(serialize($ARRAY));
    $sync="Loadjs('fw.progress.php?".fw_progress_args_b64($prgress)."&mainid=progress-support-restart')";

    // Les identifiants sont calcules par les helpers a partir de suffixid, tire
    // au sort par le CONSTRUCTEUR: ils sont donc stables pour cette instance.
    $idComment=md5("comment".$tpl->suffixid);
    $idSubject=md5("subject".$tpl->suffixid);

    $h ="<div class='stk-head stk-head-rep'>";
    $h.="<div><h1 class='stk-h1'>{new_ticket}</h1>";
    $h.="<div class='stk-sub'>{new_ticket_explain}</div></div>";
    $h.="</div>";

    // form_outside insere son contenu DIRECTEMENT dans un <table>: un <div> brut
    // en serait expulse par le parseur. Chaque libelle empile est donc une vraie
    // ligne, et les champs recoivent un libelle VIDE pour ne pas le repeter a
    // gauche.
    $form=array();
    $form[]=$tpl->field_hidden("new-ticket", 1);
    $form[]="<tr><td colspan='2'><div class='stk-lab2'>{priority}</div></td></tr>";
    $form[]=$tpl->field_array_hash($PRIORITY,"priority","",$defPriority,true);
    $form[]="<tr><td colspan='2'><div class='stk-lab2'>{subject}</div></td></tr>";
    $form[]=$tpl->field_text("subject","",$defSubject,true);
    $form[]="<tr><td colspan='2'><div class='stk-lab2'>{explain} / {comment}</div></td></tr>";
    // field_textareacode() monte un CodeMirror -- un editeur de CODE pour
    // decrire un probleme a un humain. Textarea nu + summernote.
    $form[]=$tpl->field_textarea_normal("comment","",$defComment,"100%","190px");
    // Ordre de la maquette: l'outil de support d'abord, les pieces jointes ensuite.
    $form[]=support_tool_card($tpl,$defTool,"{include_diagnostic_full}");
    $form=array_merge($form,support_attachments_rows($tpl));

    // Bloc JS concatene HORS de l'analyse: #{(.+?)}# lirait chaque accolade
    // JavaScript comme un {jeton}.
    $js="<script>
function stkNewTicket(){
    var sub=document.getElementById('$idSubject');
    // field_text() n'expose pas de placeholder: on le pose ici, l'identifiant
    // etant calculable.
    if(sub){sub.placeholder='".addslashes($tpl->javascript_parse_text("{ticket_subject_hint}"))."';}
    var ta=document.getElementById('$idComment');
    if(!ta){return;}
    stkEnsureSummernote(function(ok){
        // Sans editeur, le textarea reste visible et utilisable: ouvrir un
        // ticket ne doit pas devenir impossible parce qu'un greffon manque.
        if(!ok){return;}
        try{
            jQuery(ta).summernote({
                height:190,
                placeholder:'".addslashes($tpl->javascript_parse_text("{ticket_comment_hint}"))."',
                toolbar:[['style',['bold','italic','underline','clear']],
                         ['para',['ul','ol']],
                         ['insert',['link']]]
            });
        }catch(e){return;}
        // Meme raison que pour la reponse: on ne se fie pas aux rappels de
        // summernote (deux API selon la version) et form_outside lit le
        // textarea par son id au moment de l'envoi. On repere l'editeur A
        // PARTIR du textarea -- un conteneur pose autour ne survivrait pas au
        // <table> de form_outside.
        var zone=jQuery(ta).parent();
        var sync=function(){
            var ed=zone.find('.note-editable').first();
            if(ed.length){ta.value=ed.html();}
        };
        zone.on('keyup blur paste input','.note-editable',sync);
        zone.on('click','.note-toolbar',function(){setTimeout(sync,50);});
        sync();
    });
}
setTimeout(stkNewTicket,120);
</script>";

    echo support_ticket_css()
        ."<div class='stk stk-rep'>"
        .$tpl->_ENGINE_parse_body($h)
        .$tpl->form_outside("", @implode("\n",$form), null, "{create_ticket}",
             "dialogInstance1.close();$sync;","AsSystemAdministrator")
        ."</div>"
        .support_editor_js()
        .support_attachments_js($tpl)
        .$js;
}

function create_ticket_save(){
    $tpl=new template_admin();
    $tpl->CLEAN_POST();

    if(strlen($_POST["comment"])>65535){
        echo $tpl->post_error("{explain} Exceed 65535 {characters}");
        return;
    }

    if(!isset($_POST["comment"])){
        echo $tpl->post_error("{explain} comment missing");
        return;
    }


    if(empty($_POST["comment"])){
        echo $tpl->post_error("{explain} comment empty");
        return;
    }

    if(!isset($_POST["subject"])){
        echo $tpl->post_error("{explain} subject missing");
        return;
    }


    if(empty($_POST["subject"])){
        echo $tpl->post_error("{explain} subject empty");
        return;
    }


    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("supportCreateBug",base64_encode(serialize($_POST)));
    // Le sujet est borne: une ligne d'audit n'a pas a porter un champ libre
    // entier. admin_tracks() se charge des apostrophes (elles finissent dans un
    // litteral JavaScript).
    $sujet=substr(trim((string)$_POST["subject"]),0,80);
    return admin_tracks("Support: created ticket $sujet");
}

function unlink_js()
{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $ARRAY["PROGRESS_FILE"]=PROGRESS_DIR."/support.progress";
    $ARRAY["LOG_FILE"]=PROGRESS_DIR."/support.progress.txt";
    $ARRAY["CMD"]="/system/support/run/delete-account";
    $ARRAY["TITLE"]="{delete} {account}";
    $ARRAY["AFTER"]="LoadAjax('table-loader-support','$page?table=yes');";
    $prgress=base64_encode(serialize($ARRAY));


    $jsrestart="Loadjs('fw.progress.php?".fw_progress_args_b64($prgress)."&mainid=progress-support-restart')";




    $tpl->js_confirm_delete("{delete} {account}", "none", "",$jsrestart);
}
function recover_js()
{
    $page=CurrentPageName();
    $tpl=new template_admin();
    //$SupportTracker=new SupportTracker();
    $tpl->js_dialog1("{technical_support} {reset} {account}", "$page?recover=yes");
}

function parameters_js(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $SupportTracker=new SupportTracker();
    $tpl->js_dialog1("{technical_support} $SupportTracker->supportEmail", "$page?parameters=yes");
}

function recover()
{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $supportProxyName=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("supportProxyName"));
    $supportProxyPort=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("supportProxyPort"));
    $supportUseProxy=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("supportUseProxy"));
    $SupportTracker=new SupportTracker();
    $supportName=$SupportTracker->supportName;
    $supportEmail=$SupportTracker->supportEmail;
    if(intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SQUID_INSTALLED"))==1){
        if(intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SQUIDEnable"))==1){
            if($supportProxyName==null){$supportProxyName="127.0.0.1";}
            if($supportProxyName=="127.0.0.1"){$supportProxyPort=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidMgrListenPort"));}
        }
    }
    $ARRAY["PROGRESS_FILE"]=PROGRESS_DIR."/support.progress";
    $ARRAY["LOG_FILE"]=PROGRESS_DIR."/support.progress.txt";
    $ARRAY["CMD"]="/system/support/run/recover";
    $ARRAY["TITLE"]="{member}";
    $ARRAY["AFTER"]="LoadAjax('table-loader-support','$page?table=yes');LoadAjaxSilent('top-barr','fw-top-bar.php');";
    $prgress=base64_encode(serialize($ARRAY));
    $jsafter="dialogInstance1.close();Loadjs('fw.progress.php?".fw_progress_args_b64($prgress)."&mainid=progress-support-restart')";
    $form[]=$tpl->field_hidden("supportEmail1","");
    $form[]=$tpl->field_text("supportName", "{name}", $supportName,true);
    $form[]=$tpl->field_text("supportEmail", "{email}", $supportEmail,true);
    $form[]=$tpl->field_checkbox("supportUseProxy","{UseProxyServer}",$supportUseProxy,"supportProxyName,supportProxyPort");
    $form[]=$tpl->field_text("supportProxyName", "{proxyname}", $supportProxyName);
    $form[]=$tpl->field_numeric("supportProxyPort", "{listen_port}", $supportProxyPort);
    echo $tpl->form_outside("{technical_support} {reset} {account}", $form,null,"{apply}",$jsafter,"AsSystemAdministrator");

}


function parameters(){
    $page=CurrentPageName();
    $tpl=new template_admin();

    $supportProxyName=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("supportProxyName"));
    $supportProxyPort=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("supportProxyPort"));
    $supportUseProxy=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("supportUseProxy"));

    $SupportTracker=new SupportTracker();
    $supportName=$SupportTracker->supportName;
    $supportEmail=$SupportTracker->supportEmail;


    if(intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SQUID_INSTALLED"))==1){
        if(intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SQUIDEnable"))==1){
            if($supportProxyName==null){$supportProxyName="127.0.0.1";}
            if($supportProxyName=="127.0.0.1"){$supportProxyPort=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidMgrListenPort"));}
        }
    }




    $ARRAY["PROGRESS_FILE"]=PROGRESS_DIR."/support.progress";
    $ARRAY["LOG_FILE"]=PROGRESS_DIR."/support.progress.txt";
    $ARRAY["CMD"]="/system/support/run/check";
    $ARRAY["TITLE"]="{member}";
    $ARRAY["AFTER"]="LoadAjax('table-loader-support','$page?table=yes');LoadAjaxSilent('top-barr','fw-top-bar.php');";
    $prgress=base64_encode(serialize($ARRAY));
    $jsafter="dialogInstance1.close();Loadjs('fw.progress.php?".fw_progress_args_b64($prgress)."&mainid=progress-support-restart')";

    $form[]=$tpl->field_text("supportEmail", "{email}", $supportEmail,true);
    $form[]=$tpl->field_text("supportName", "{name}", $supportName,true);
    $form[]=$tpl->field_hidden("supportSecCode","");
    $form[]=$tpl->field_checkbox("supportUseProxy","{UseProxyServer}",$supportUseProxy,"supportProxyName,supportProxyPort");
    $form[]=$tpl->field_text("supportProxyName", "{proxyname}", $supportProxyName);
    $form[]=$tpl->field_numeric("supportProxyPort", "{listen_port}", $supportProxyPort);
    echo $tpl->form_outside("{technical_support} {parameters}", $form,null,"{apply}",$jsafter,"AsSystemAdministrator");
}

// ═══ Ecran d'inscription (aucun compte enregistre) ═════════════════════════
//
// Reprend stitch_support_service_registration: deux panneaux, le discours a
// gauche sur fond quadrille, la carte de saisie a droite. La palette est celle
// de la maquette (primary #006b58, accent #1abc9c, surface #f7f9ff,
// surface-container-high #d9eaff, outline-variant #bbcac3).
//
// DEUX CHAMPS DE LA MAQUETTE N'ONT PAS DE BACKEND, ils ne sont donc pas rendus:
//
//  * « Mot de passe » -- il ne s'en choisit aucun. Le code de securite est
//    GENERE par le portail (createCustomerAccount -> newSecCode) puis envoye
//    par courriel; ce que l'operateur taperait a l'inscription serait jete. Le
//    champ existe quand meme, mais a sa vraie place: seconde etape, rempli avec
//    ce qui arrive par courriel, et son libelle le dit.
//  * « Organisation / Entreprise » -- aucun reglage, aucune route, aucun champ
//    du portail ne la porte. Un champ qui n'enregistre rien annonce un succes
//    pour une saisie perdue: il est retire plutot qu'affiche en decor.
//
// L'inscription est un aller-retour en DEUX temps, et l'ecran le dit parce que
// le premier tour a l'air d'un echec: CheckAccount() interroge mailboxes?, le
// portail repond 401 CreateCustomer, handle401() cree le compte et poste le
// code par courriel -- mais supportCurl rend !ok, donc la progression se ferme
// sur « {check_account} {failed} » ALORS QUE le compte vient d'etre cree.
function support_register_css():string{
    if(isset($GLOBALS["SUPREG_CSS_DONE"])){return "";}
    $GLOBALS["SUPREG_CSS_DONE"]=true;
    return "<style>
.supreg{display:flex;flex-wrap:wrap;align-items:stretch;background:#f7f9ff;border:1px solid #bbcac3;border-radius:12px;overflow:hidden;margin-top:10px;max-width: 1024px}
.supreg-pitch{flex:1 1 380px;min-width:320px;position:relative;padding:40px;background:#d9eaff;border-right:1px solid #bbcac3;display:flex;flex-direction:column;justify-content:flex-start}
.supreg-grid{position:absolute;inset:0;pointer-events:none;background-image:linear-gradient(to right,rgba(128,128,128,.07) 1px,transparent 1px),linear-gradient(to bottom,rgba(128,128,128,.07) 1px,transparent 1px);background-size:32px 32px}
.supreg-pitch-in{position:relative;z-index:1;max-width:520px}
.supreg-logo{width:64px;height:64px;border-radius:14px;background:#1abc9c;color:#fff;display:inline-flex;align-items:center;justify-content:center;font-size:30px;margin-bottom:24px}
.supreg-h1{font-size:32px;line-height:40px;font-weight:600;color:#091d2e;margin:0 0 16px}
.supreg-lead{font-size:16px;line-height:26px;color:#3c4a45;margin:0}
.supreg-form{flex:1 1 380px;min-width:320px;padding:40px;display:flex;flex-direction:column;justify-content:center;background:#f7f9ff}
.supreg-card{width:100%;max-width:520px;margin:0 auto}
.supreg-h2{font-size:24px;line-height:32px;font-weight:600;color:#091d2e;margin:0 0 6px}
.supreg-sub{font-size:14px;line-height:20px;color:#3c4a45;margin:0 0 4px}
.supreg-steps{font-size:13px;line-height:19px;color:#3c4a45;background:#e3efff;border:1px solid #bbcac3;border-radius:8px;padding:10px 14px;margin:14px 0 0}
.supreg-card .ibox{background:#fff;border:1px solid #bbcac3;border-radius:12px;box-shadow:none;margin:16px 0 0;padding:4px 20px 16px}
.supreg-card .ibox-title{border-top:0;border-bottom:0;background:transparent;padding-bottom:0}
/* La gouttiere interieure du panneau venait du padding de la colonne Bootstrap
   qui l'enveloppe. Neutraliser cette colonne (ci-dessous) collait les champs au
   bord. form_outside ne pose PAS de .ibox-content -- le <form> et sa <table>
   remplissent l'ibox bord a bord -- c'est donc l'ibox lui-meme qui porte le
   padding. Mesure: sans lui, les champs finissaient a 1px de la bordure.
   Cette meme colonne porte en outre un border-right/bottom #eaeaea du theme:
   comme l'ibox descend de 16px sous elle, ce filet depassait de 10px au-dessus
   du coin arrondi, en petit trait vertical. D'ou le border:0. */
/* form_outside emet sa .row avec des marges EN LIGNE et ASYMETRIQUES
   (margin-left:10px, margin-right:-15px) et un .col-lg-12 en padding 0 15px:
   dans une carte a largeur contrainte, la rangee DEPASSE de 10px a droite et le
   panneau blanc se retrouve avec 25px de retrait a gauche pour 6px a droite --
   visuellement colle au bord. Mesure sur 192.168.1.20: carte 786..1209,
   rangee 796..1219, ibox 811..1203.
   !important est indispensable ici: ces marges sont des styles EN LIGNE, qu'un
   selecteur seul ne peut pas surcharger. La portee reste .supreg-card, donc
   aucun autre form_outside de la console n'est touche. */
.supreg-msg{margin-top:14px}
.supreg-card > .row{margin-left:0 !important;margin-right:0 !important}
.supreg-card > .row > [class*='col-']{padding-left:0 !important;padding-right:0 !important;margin-top:0 !important;border:0 !important}
@media(max-width:900px){.supreg-pitch{border-right:0;border-bottom:1px solid #bbcac3;padding:24px}.supreg-form{padding:24px}}
</style>";
}

// Le panneau de gauche: le discours. Aucune donnee, aucune saisie.
function support_register_pitch():string{
    $h ="<div class='supreg-pitch'><div class='supreg-grid'></div><div class='supreg-pitch-in'>";
    $h.="<div class='supreg-logo'><i class='fas fa-headset'></i></div>";
    $h.="<h1 class='supreg-h1'>{technical_support}</h1>";
    $h.="<p class='supreg-lead'>{support_register_intro}</p>";
    $h.="</div></div>";
    return $h;
}

// Compte rendu de la derniere tentative.
//
// CheckAccount() vide SupportRegisterError a chaque debut de passe et y depose,
// en cas d'echec, le CORPS BRUT rendu par le portail (tickets.go:336 et :342).
// C'est donc du JSON tiers: il est decode pour n'afficher que le message, et
// echappe dans tous les cas.
//
// Trois de ces "erreurs" n'en sont pas: sur un 401 le portail repond
// CreateCustomer ou CreateSecCode, et handle401() a DEJA cree le compte ou
// poste un code par courriel -- la passe se termine malgre tout sur
// « {check_account} {failed} {error} ». Les annoncer comme des echecs
// enverrait l'operateur chercher une panne alors que son code est en route.
function support_register_error_text($raw):array{
    // -> array(variante, message). variante: "err" ou "info".
    $j=json_decode($raw,true);
    $msg=is_array($j) && isset($j["message"]) ? trim((string)$j["message"]) : "";
    if($msg==="Not Authorized"){ return array("err","{support_err_not_authorized}"); }
    if($msg==="CreateSecCode"){  return array("info","{support_err_code_sent}"); }
    if($msg==="CreateCustomer"){ return array("info","{support_err_account_created}"); }
    // Message inconnu, ou reponse qui n'est pas du JSON (page d'erreur HTML,
    // coupure): on rend ce que le portail a dit, tronque et echappe.
    $body=($msg!=="")?$msg:trim(strip_tags($raw));
    if(strlen($body)>400){$body=substr($body,0,400)."...";}
    if($body===""){return array("","");}
    return array("err","{support_err_portal_said}<br><span style='font-family:monospace'>"
        .htmlspecialchars($body)."</span>");
}

function support_register_error():string{

    $raw=trim((string)$GLOBALS["CLASS_SOCKETS"]->GET_INFO("SupportRegisterError"));
    if($raw===""){return "";}
    list($variant,$msg)=support_register_error_text($raw);
    if($msg===""){return "";}
    if($variant==="info"){
        return "<div class='ak-banner ak-info supreg-msg'>"
            ."<i class='fas fa-circle-info'></i><div class='ak-banner-m'>".$msg."</div></div>";
    }
    return "<div class='ak-banner ak-err supreg-msg'>"
        ."<i class='fas fa-circle-exclamation'></i>"
        ."<div class='ak-banner-m'><strong>{support_err_title}</strong><br>".$msg."</div></div>";
}

// La carte de droite. Les champs sont EXACTEMENT ceux que CheckAccount()
// consomme: nom et courriel pour la creation, code de securite pour la seconde
// passe, et les reglages de proxy sans lesquels le portail est injoignable
// depuis un reseau ferme.
function support_register_form():string{
    $page=CurrentPageName();
    $tpl=new template_admin();

    $SupportTracker=new SupportTracker();
    $supportName=$SupportTracker->supportName;
    $supportEmail=$SupportTracker->supportEmail;

    $supportProxyName=trim((string)$GLOBALS["CLASS_SOCKETS"]->GET_INFO("supportProxyName"));
    $supportProxyPort=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("supportProxyPort"));
    $supportUseProxy=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("supportUseProxy"));
    if(intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SQUID_INSTALLED"))==1){
        if(intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SQUIDEnable"))==1){
            if($supportProxyName==""){$supportProxyName="127.0.0.1";}
            if($supportProxyName=="127.0.0.1"){$supportProxyPort=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidMgrListenPort"));}
        }
    }

    // Le meme aller-retour que le bouton {parameters}: enregistrer, puis
    // declencher /system/support/run/check, dont la barre de progression dit
    // ce que le portail a repondu.
    $ARRAY=array();
    $ARRAY["PROGRESS_FILE"]=PROGRESS_DIR."/support.progress";
    $ARRAY["LOG_FILE"]=PROGRESS_DIR."/support.progress.txt";
    $ARRAY["CMD"]="/system/support/run/check";
    $ARRAY["TITLE"]="{creating_account}";
    $ARRAY["AFTER"]="LoadAjax('table-loader-support','$page?table=yes');LoadAjaxSilent('top-barr','fw-top-bar.php');";
    // fw.progress.php n'execute AFTER que sur POURC==100; au-dela de 100 il
    // n'execute QUE AFTER_FAILED (fw.progress.php:733-760). Sans cette seconde
    // cle, un echec laissait la barre rouge et l'ecran INCHANGE: le message que
    // le daemon venait d'ecrire dans SupportRegisterError n'apparaissait qu'au
    // rechargement manuel de la page.
    $ARRAY["AFTER_FAILED"]="LoadAjax('table-loader-support','$page?table=yes');";
    $prgress=base64_encode(serialize($ARRAY));
    $jsafter="Loadjs('fw.progress.php?".fw_progress_args_b64($prgress)."&mainid=progress-support-restart')";

    $f=array();
    $f[]=$tpl->field_text("supportName","{name}",$supportName,true);
    $f[]=$tpl->field_text("supportEmail","{email}",$supportEmail,true);
    // Le code de securite n'est plus saisi: le daemon l'obtient lui-meme
    // (engendre en CreateCustomer, rendu par le portail en CreateSecCode) et le
    // persiste des que le portail l'a accepte. Le champ reste POSTE mais VIDE:
    // support_save_keys ignore les valeurs vides, il n'ecrase donc jamais le
    // code enregistre. Le retirer tout a fait serait imprudent -- le
    // repartiteur en tete de page s'accroche a un nom de champ, et deplacer ce
    // contrat sans le voir donne un formulaire qui s'envoie sans rien ecrire.
    $f[]=$tpl->field_hidden("supportSecCode","");
    $f[]=$tpl->field_checkbox("supportUseProxy","{UseProxyServer}",$supportUseProxy,"supportProxyName,supportProxyPort");
    $f[]=$tpl->field_text("supportProxyName","{proxyname}",$supportProxyName);
    $f[]=$tpl->field_numeric("supportProxyPort","{listen_port}",$supportProxyPort);

    $h ="<div class='supreg-form'><div class='supreg-card'>";
    $h.="<h2 class='supreg-h2'>{register}</h2>";
    $h.="<p class='supreg-sub'>{support_register_subtitle}</p>";
    $h.="<div class='supreg-steps'>{support_register_steps}</div>";
    // Le compte rendu de la derniere tentative, juste au-dessus du formulaire
    // qu'il faut corriger. Une fonction, pas un echo: support_register_form()
    // RENVOIE sa chaine -- un echo ici sortirait le message hors de la carte,
    // avant meme le <div class='supreg'>.
    $h.=support_register_error();
    // form_outside porte le contrat d'enregistrement (corps vide = succes,
    // post_error = fenetre rouge): la carte l'habille, elle ne le remplace pas.
    $h.=$tpl->form_outside("",@implode("\n",$f),"","{create_account}",$jsafter,"AsSystemAdministrator");
    $h.="</div></div>";
    return $h;
}

function support_register_screen():string{
    return "<div class='supreg'>".support_register_pitch().support_register_form()."</div>";
}

function table(){

    $page=CurrentPageName();
    $tpl=new template_admin();
    $supportID=intval(base64_decode($GLOBALS["CLASS_SOCKETS"]->GET_INFO("supportMID")));

    $ARRAY["PROGRESS_FILE"]=PROGRESS_DIR."/support.progress";
    $ARRAY["LOG_FILE"]=PROGRESS_DIR."/support.progress.txt";
    $ARRAY["CMD"]="/system/support/run/sync";
    $ARRAY["TITLE"]="{synchronize}";
    $ARRAY["AFTER"]="LoadAjax('table-loader-support','$page?table=yes');";
    $prgress=base64_encode(serialize($ARRAY));
    $sync="Loadjs('fw.progress.php?".fw_progress_args_b64($prgress)."&mainid=progress-support-restart')";


    $jshelp="s_PopUpFull('https://wiki.articatech.com/en/maintenance/link-support-portal',1024,768,'Stress Tool')";

    // Sans compte, la barre ne proposait que des actions SUR un compte:
    // parametres, reinitialiser, supprimer -- trois boutons qui n'ont pas
    // d'objet, sur un ecran dont le seul geste est de s'inscrire. Il ne reste
    // que l'aide en ligne; le formulaire, lui, est au centre de l'ecran.
    $topbuttons=array();
    if($supportID>0){
        $topbuttons[] = array("Loadjs('$page?create-js=yes');", ico_plus, "{new_ticket}");
        $topbuttons[] = array($sync, ico_refresh, "{synchronize_tickets}");
        $topbuttons[] = array("Loadjs('$page?parameters-js=yes');", ico_configure, "{parameters}");
        $topbuttons[] = array("Loadjs('$page?recover-js=yes');", ico_keys, "{reset} {account}");
        $topbuttons[] = array("Loadjs('$page?unlink-js=yes');", ico_trash, "{remove} {account}");
    }
    $topbuttons[] = array($jshelp, ico_support, "{online_help}");

    $TINY_ARRAY["TITLE"]="{technical_support}";
    $TINY_ARRAY["ICO"]=ico_support;
    $TINY_ARRAY["EXPL"]="{technical_support_explain}";
    $TINY_ARRAY["BUTTONS"]=$tpl->table_buttons($topbuttons);
    $jstiny="Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";




    // Le <div class="btn-group"> qui s'ouvrait ici n'a JAMAIS rien contenu: les
    // boutons partent par le TINY, pas par $html.
    if($supportID==0){
        // Sans compte, la page n'affichait qu'une banniere rouge disant qu'il
        // fallait s'enregistrer, sans dire ou ni comment: l'ecran d'inscription
        // prend sa place et porte le formulaire.
        $html[]=support_register_screen();
        // La feuille de style est concatenee APRES l'analyse: la regex
        // #{(.+?)}# de _ENGINE_parse_body lit chaque corps de regle CSS comme
        // un {token}, soit une interrogation memcached par regle.
        echo support_register_css()
            .$tpl->_ENGINE_parse_body(@implode("\n",$html))
            ."<script>$jstiny\nNoSpinner();</script>";
        return;
    }



    // Les neuf colonnes d'origine -- dont quatre a "width:1%" -- se disputaient
    // la largeur au point que le sujet, seule donnee vraiment lisible, tenait sur
    // deux mots. Numero, date de creation, auteur et etiquettes descendent en
    // sous-titre du sujet; ne restent en colonnes que ce qui se compare d'une
    // ligne a l'autre.
    $html[]=awc_search_bar("sup-search","{search}");
    $html[]="<div class='awc-wrap'><div class='awc-card'><div class='awc-scroll'>";
    $html[]="<table class='awc-tbl'>";
    $html[]="<thead><tr>";
    $html[]="<th>{subject}</th>";
    $html[]="<th class='awc-nowrap'>{status}</th>";
    $html[]="<th class='awc-nowrap'>{priority}</th>";
    $html[]="<th class='awc-nowrap'>{updated}</th>";
    $html[]="<th class='awc-nowrap'>{assigned_to}</th>";
    $html[]="<th>&nbsp;</th>";
    $html[]="</tr></thead>";
    $html[]="<tbody id='sup-rows'>";

    $results=support_db_get("/system/support/db/tickets");
    if(!is_array($results)){$results=array();}

    $c=0;
    foreach ($results as $ligne){
        if(!is_array($ligne)){continue;}
        $html[]=support_ticket_row($ligne);
        $c++;
    }
    if($c==0){
        $html[]="<tr><td colspan='6' class='awc-empty'>{no_data}</td></tr>";
    }

    $html[]="</tbody></table></div>";
    $html[]="<div class='awc-foot'><span>{showing} $c</span>"
           ."<span class='awc-pg' id='sup-pager'></span></div>";
    $html[]="</div></div>";

    // Le bloc de script est concatene APRES l'analyse: la regex #{(.+?)}# de
    // _ENGINE_parse_body prendrait chaque accolade JavaScript pour un jeton.
    // ICON_SCRIPTS_COMPILE() et non $tpl->ICON_SCRIPTS: support_ticket_row()
    // fabrique chaque ligne avec SA PROPRE instance de template_admin, si bien
    // que la pile d'instance lue ici est celle d'un autre objet -- vide. Seule
    // la version COMPILE draine aussi $GLOBALS["TPL_ICON_SCRIPTS"].
    $script="<script>\n$jstiny\nNoSpinner();\n".$tpl->ICON_SCRIPTS_COMPILE()."\n</script>";

    // awc_pager_js() APRES le tableau: les <tr> doivent exister dans le DOM.
    // awc_filter_js() APRES le pager, dont il consomme le tb.__awcRedraw.
    echo awc_table_style()
        .$tpl->_ENGINE_parse_body(@implode("\n",$html))
        .$script
        .awc_pager_js($GLOBALS["FOOTABLE_PSIZE"],"sup-rows","sup-pager")
        .awc_filter_js("sup-search","sup-rows","sup-pager");

}

/**
 * Pastille d'etat d'un ticket.
 * Les quatre etats etaient tous rendus en "label-info" -- une table de
 * correspondance ou toutes les valeurs sont identiques ne distingue rien. Un
 * ticket clos se lit desormais au premier coup d'oeil.
 * Un etat inconnu n'est plus une cle absente (avertissement PHP 8) mais retombe
 * sur le gris neutre.
 */
function support_status_badge($status){
    $s=strtolower(trim((string)$status));
    $map=array("active"=>"#26a69a","pending"=>"#865300","closed"=>"#263238","deleted"=>"#ba1a1a");
    $bg=isset($map[$s])?$map[$s]:"#263238";
    return "<span class='awc-badge' style='background:$bg'>".htmlspecialchars((string)$status)."</span>";
}

/**
 * Pastille de priorite d'un ticket (champ customFields du portail).
 * Le portail peut renvoyer une priorite non repertoriee: l'ancien code lisait
 * alors une cle absente du tableau et emettait un avertissement a chaque ligne.
 */
function support_priority_badge($priority){
    $p=trim((string)$priority);
    if($p===""){return "";}
    $map=array(
        "System Down"=>"#ba1a1a","Critical"=>"#ba1a1a","Urgent"=>"#865300",
        "Important"=>"#006a62","Low"=>"#263238","Monitor"=>"#26a69a",
        "Informational"=>"#26a69a");
    $bg=isset($map[$p])?$map[$p]:"#263238";
    return "<span class='awc-badge' style='background:$bg'>".htmlspecialchars($p)."</span>";
}

/**
 * Etiquettes d'un ticket.
 * tags arrive en JSON depuis le portail: json_decode rend NULL sur une chaine
 * vide ou malformee, et l'ancien foreach sur ce NULL etait une TypeError fatale
 * en PHP 8 -- toute la liste des tickets disparaissait pour un seul ticket
 * sans etiquette.
 */
function support_tags_html($raw){
    $tags=json_decode((string)$raw,true);
    if(!is_array($tags)){return "";}
    $out=array();
    foreach($tags as $val){
        if(!is_scalar($val)){continue;}
        $v=trim((string)$val);
        if($v===""){continue;}
        $out[]="<span class='awc-tag'>".htmlspecialchars($v)."</span>";
    }
    if(count($out)==0){return "";}
    return "&nbsp;".@implode("&nbsp;",$out);
}

/**
 * Une ligne de ticket.
 * Toutes les valeurs viennent du portail de support (donnees exterieures) et
 * sont donc echappees: l'ancienne version injectait sujet, etiquettes et nom
 * d'assigne bruts dans le HTML.
 */
function support_ticket_row($ligne){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $id=(string)($ligne["id"] ?? "");
    $subject=trim((string)($ligne["subject"] ?? ""));
    if($subject===""){$subject="#".(string)($ligne["number"] ?? "");}
    $number="#".(string)($ligne["number"] ?? "");
    $createdBy=trim((string)($ligne["createdBy"] ?? ""));
    $createdAt=$tpl->time_to_date($ligne["createdAt"] ?? 0,true);
    $updatedAt=$tpl->time_to_date($ligne["updatedAt"] ?? 0,true);
    $assignee=trim((string)($ligne["assignee"] ?? ""));
    if(strlen($assignee)<2){$assignee="{not_defined}";}
    else{$assignee=htmlspecialchars($assignee);}
    $threads=intval($ligne["threads"] ?? 0);
    $mkey=md5(serialize($ligne));
    $view="Loadjs('$page?show-js=".urlencode($id)."')";

    $meta=$number." &middot; ".$createdAt;
    if($createdBy!==""){$meta.=" &middot; ".htmlspecialchars($createdBy);}

    $h ="<tr id='$mkey'>";
    $h.="<td><div class='awc-ident'>";
    $h.="<span class='awc-ico'><i class='fas fa-life-ring'></i></span>";
    $h.="<div><div class='awc-name'><a href='javascript:blur();' OnClick=\"$view\">"
       .htmlspecialchars($subject)."</a>";
    if($threads>0){
        $h.="&nbsp;<span class='awc-tag'><i class='fas fa-envelope'></i>&nbsp;$threads</span>";
    }
    $h.="</div><div class='awc-meta'>".$meta.support_tags_html($ligne["tags"] ?? "")."</div>";
    $h.="</div></div></td>";
    $h.="<td class='awc-nowrap'>".support_status_badge($ligne["status"] ?? "")."</td>";
    $h.="<td class='awc-nowrap'>".support_priority_badge($ligne["customFields"] ?? "")."</td>";
    $h.="<td class='awc-nowrap awc-mono'>".$updatedAt."</td>";
    $h.="<td class='awc-nowrap awc-mono'>".$assignee."</td>";
    $h.="<td><div class='awc-acts'>"
       .$tpl->icon_reply("Loadjs('$page?reply-js=".urlencode($id)."')","AsSystemAdministrator")
       .$tpl->icon_delete("Loadjs('$page?delete-bug-js=".urlencode($id)."&md=$mkey')","AsSystemAdministrator")
       ."</div></td>";
    $h.="</tr>";
    return $h;
}
// SAVE_POSTs() sans argument ecrit TOUTE cle postee dans les reglages, et les
// champs etaient lus sans isset (avertissement PHP 8, puis base64 d'une valeur
// nulle ecrase le reglage en place). Les deux formulaires sont gardes par
// "AsSystemAdministrator": la meme exigence cote serveur.
// $b64: identite du compte, stockee encodee comme le fait SupportTracker a la
// lecture. $raw: les reglages de proxy, que SAVE_POSTs enregistrait en clair --
// les encoder les rendrait illisibles a leur consommateur.
function support_save_keys($b64,$raw){
    foreach($b64 as $k){
        if(!isset($_POST[$k])||is_array($_POST[$k])){continue;}
        $v=trim((string)$_POST[$k]);
        // Une valeur vide n'ecrase jamais un reglage existant.
        if($v===""){continue;}
        $GLOBALS["CLASS_SOCKETS"]->SET_INFO($k,base64_encode($v));
    }
    foreach($raw as $k){
        if(!isset($_POST[$k])||is_array($_POST[$k])){continue;}
        $GLOBALS["CLASS_SOCKETS"]->SET_INFO($k,trim((string)$_POST[$k]));
    }
}
function support_proxy_keys():array{
    return array("supportUseProxy","supportProxyName","supportProxyPort");
}
function recover_save(){
    $tpl=new template_admin();
    if(!support_require_admin($tpl)){return;}
    $tpl->CLEAN_POST();
    support_save_keys(array("supportEmail","supportName"),support_proxy_keys());
    return admin_tracks("Support: reset account");
}
function parameters_save(){
    $tpl=new template_admin();
    if(!support_require_admin($tpl)){return;}
    $tpl->CLEAN_POST();
    support_save_keys(array("supportEmail","supportName","supportSecCode"),support_proxy_keys());
    return admin_tracks("Support: saved account parameters");
}

