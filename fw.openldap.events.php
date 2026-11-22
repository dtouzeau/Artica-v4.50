<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
include_once(dirname(__FILE__)."/ressources/class.sockets.inc");
$GLOBALS["CLASS_SOCKETS"]=new sockets();
include_once(dirname(__FILE__)."/ressources/class.statistics_appliance.inc");
$users=new usersMenus();if(!$users->AsDatabaseAdministrator){exit();}
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}

/*
 * Journal du service OpenLDAP.
 *
 * Les lignes viennent de GET /openldap/events/{query}/{max} (module openldap),
 * calquee sur /proxypac/events. Avant, la page appelait
 * framework/openldap.php?searchlogs=..., qui faisait un `grep|tail` shell et
 * ecrivait le resultat dans ressources/logs/web/slapd.log -- UN SEUL fichier
 * pour tous les utilisateurs de la console : deux administrateurs filtrant en
 * meme temps lisaient le resultat l'un de l'autre.
 *
 * Le filtre voyage en base64URL ('-' et '_') : il occupe un segment de CHEMIN,
 * et un '/' issu du base64 standard couperait la route (fasthttp decode %2F
 * avant de router).
 */

if(isset($_GET["build-page"])){build_page();exit;}
if(isset($_GET["search"])){events_table();exit;}
page();


function page(){
    $page=CurrentPageName();
    $tpl=new template_admin();

    $html=$tpl->page_header("{APP_OPENLDAP} {events}","fad fa-eye","{APP_OPENLDAP_EXPLAIN}",
        "$page?build-page=yes","ldap-events","progress-openldap-restart",false,"table-loader-openldap-events");

    if(isset($_GET["main-page"])){
        $tpl=new template_admin("{APP_OPENLDAP} {events}",$html);
        echo $tpl->build_firewall();
        return;
    }
    echo $tpl->_ENGINE_parse_body($html);
}

function build_page(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $html[]="<div style='margin-top:10px'>";
    $html[]=$tpl->search_block($page,"","","","");
    $html[]="</div>";
    echo $tpl->_ENGINE_parse_body($html);
}

/*
 * Styles propres a la page : l'echelle de badges de la table awc-* est
 * binaire (actif/inactif), une severite en a trois. Classes prefixees, la
 * feuille partagee reste intacte pour les autres pages.
 * Emis HORS de _ENGINE_parse_body(), qui lirait chaque bloc CSS comme un {token}.
 */
function ldapev_style(){
    return "<style>
.ldapev-badge-err{background:#ba1a1a;color:#fff}
.ldapev-badge-warn{background:#865300;color:#fff}
.ldapev-badge-info{background:#e8ecec;color:#3d4947}
.ldapev-ico-err{background:rgba(186,26,26,.1);color:#ba1a1a}
.ldapev-ico-warn{background:rgba(134,83,0,.1);color:#865300}
.ldapev-ico-info{background:rgba(0,106,98,.1);color:#006a62}
.ldapev-msg{font-size:13px;color:#111d23;word-break:break-word}
</style>";
}

/*
 * Severite deduite du texte, avec les memes motifs que la page precedente.
 * Retourne array(cle, libelle, icone).
 */
function ldapev_severity($message){
    if(preg_match("#(does not exist|Error while|Can not|error|failed|slapd stopped|slapd shutdown|daemon: shutdown)#i",$message)){
        return array("err","{error}","fa-circle-exclamation");
    }
    if(preg_match("#connections_destroy#i",$message)){
        return array("warn","{warning}","fa-triangle-exclamation");
    }
    return array("info","{information}","fa-circle-info");
}

function events_table(){
    $page=CurrentPageName();
    $tpl=new template_admin();

    // Whitelist obligatoire : la valeur finit dans du JS executable (OnClick).
    $function=preg_replace('/[^A-Za-z0-9_]/','',(string)(isset($_GET["function"])?$_GET["function"]:""));

    // query_pattern comprend "limit N" / "N events" dans la chaine de recherche.
    $raw=trim((string)(isset($_GET["search"])?$_GET["search"]:""));
    $search=$tpl->query_pattern(strtolower($raw));
    $s=trim((string)$search["S"]);
    $max=intval($search["MAX"]);
    if($max<1){$max=200;}
    $_SESSION["OPENLDAP_SEARCH"]=$s;

    // base64URL, et "." pour "pas de filtre" : un segment vide ferait un 404.
    $q=($s=="")?"Lg==":strtr(base64_encode($s),'+/','-_');
    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/openldap/events/$q/".intval($max)));

    $rows=array();
    $errortext="";
    if(!is_object($json)){
        $errortext="{error}";
    }elseif(isset($json->Status) && !$json->Status){
        $errortext=isset($json->Error)?$json->Error:"{error}";
    }elseif(isset($json->Logs) && is_array($json->Logs)){
        foreach($json->Logs as $line){
            $line=trim((string)$line);
            if($line==""){continue;}
            // Format syslog BSD, celui qu'Artica impose
            // ($ActionFileDefaultTemplate RSYSLOG_TraditionalFileFormat).
            // Une ligne qui n'y repond pas est affichee TELLE QUELLE, jamais
            // jetee : la page precedente faisait un `continue`, si bien qu'un
            // changement de format aurait vide la table sans rien expliquer.
            $datetext="-"; $pid="";
            if(preg_match("#^(.+?)\s+([0-9]+)\s+([0-9:]+)\s+.*?(\[([0-9]+)\]|(slapd)):\s+(.+)#",$line,$re)){
                $date=strtotime($re[1]." ".$re[2]." ".$re[3]);
                if($date>0){$datetext=$tpl->time_to_date($date,true);}
                $pid=$re[5];
                $message=$re[7];
            }else{
                $message=$line;
            }
            list($sev,$sevlabel,$sevico)=ldapev_severity($message);

            $h ="<tr>";
            $h.="<td><div class='awc-ident'>";
            $h.="<span class='awc-ico ldapev-ico-$sev'><i class='fas $sevico'></i></span>";
            $h.="<span><span class='awc-name'>".htmlspecialchars($datetext)."</span>";
            if($pid<>""){$h.="<div class='awc-meta'>PID ".htmlspecialchars((string)$pid)."</div>";}
            $h.="</span></div></td>";
            $h.="<td class='center'><span class='awc-badge ldapev-badge-$sev'>$sevlabel</span></td>";
            $h.="<td><span class='ldapev-msg'>".htmlspecialchars($message)."</span></td>";
            $h.="</tr>";
            $rows[]=$h;
        }
    }

    $count=count($rows);
    $html=array();
    if($errortext<>""){
        $html[]=$tpl->div_error($errortext);
    }
    $html[]="<div class='awc-wrap'><div class='awc-card'><div class='awc-scroll'>";
    $html[]="<table class='awc-tbl'><thead><tr>";
    $html[]="<th>{date}</th><th class='center'>{severity}</th><th style='width:99%'>{message}</th>";
    $html[]="</tr></thead><tbody id='awc-rows'>";
    if($count==0){
        $html[]="<tr><td colspan='3'><div class='awc-empty'>{no_data}</div></td></tr>";
    }else{
        $html[]=@implode("\n",$rows);
    }
    $html[]="</tbody></table></div>";
    $html[]="<div class='awc-foot'>";
    $html[]="<span>{showing} $count {items}</span>";
    $html[]="<span class='awc-pg' id='awc-pager'></span>";
    $html[]="</div></div></div>";

    // Le rafraichissement passe par la fonction generee par search_block, ce qui
    // preserve le filtre courant ; repli sur un rechargement du fragment.
    $refreshjs="LoadAjaxSilent('table-loader-openldap-events','$page?build-page=yes');";
    if($function<>""){$refreshjs="$function();";}
    $topbuttons=array();
    $topbuttons[]=array($refreshjs,ico_refresh,"{refresh}");

    $TINY_ARRAY=array();
    $TINY_ARRAY["TITLE"]="{APP_OPENLDAP} {events}";
    $TINY_ARRAY["ICO"]="fad fa-eye";
    $TINY_ARRAY["EXPL"]="{APP_OPENLDAP_EXPLAIN}";
    $TINY_ARRAY["URL"]="ldap-events";
    $TINY_ARRAY["BUTTONS"]=$tpl->table_buttons($topbuttons);

    $script ="<script>";
    $script.="NoSpinner();\n".$tpl->ICON_SCRIPTS_COMPILE();
    $script.="Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";
    $script.="</script>";

    // CSS et JS du pager concatenes AUTOUR du parse : leurs blocs {...} seraient
    // lus comme des {tokens}.
    echo awc_table_style().ldapev_style().$tpl->_ENGINE_parse_body(@implode("\n",$html)).$script.awc_pager_js(25);
}
