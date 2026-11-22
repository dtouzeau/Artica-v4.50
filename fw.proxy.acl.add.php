<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.logfile_daemon.inc");
include_once(dirname(__FILE__)."/ressources/class.squid.familysites.inc");
include_once(dirname(__FILE__)."/ressources/class.mysql.catz.inc");
include_once(dirname(__FILE__)."/ressources/class.ufdbguard-tools.inc");
// awc_table_style() / awc_pager_js(): feuille de style et pager partages des tableaux d'entites
include_once(dirname(__FILE__)."/ressources/class.statistics_appliance.inc");
include_once(dirname(__FILE__)."/ressources/class.tcpip.inc");
include_once(dirname(__FILE__)."/ressources/class.mysql.catz.inc");
include_once(dirname(__FILE__)."/ressources/class.acls.backend.inc");

if(isset($_GET["start"])){start();exit;}
if(isset($_GET["search"])){search();exit;}
if(isset($_GET["add"])){add();exit;}

js();

function js(){
    $page=CurrentPageName();
    $type=(string)($_GET["type"] ?? "");
    $value=(string)($_GET["value"] ?? "");
    $valueencoded=urlencode($value);
    $tpl=new template_admin();
    $tpl->js_dialog9($value,"$page?start=yes&type=$type&value=$valueencoded");
}

function add(){
    $uid=$_SESSION["uid"] ?? -100;
    $tpl=new template_admin();
    if($uid==-100){$uid="Manager";}
    $ID=intval($_GET["add"]);
    $value=(string)($_GET["value"] ?? "");

    $grp=acls_api_get("/proxy/acls/sqlite/group/$ID");
    $GroupName=$grp ? ($grp["groupName"] ?? "") : "";

    list($ok,$data)=acls_api_send("POST","/proxy/acls/sqlite/items",[
        "gpid"=>$ID,
        "items"=>[["pattern"=>$value,"uid"=>$uid]],
    ]);
    if(!$ok){$tpl->js_mysql_alert($data);return false;}
    echo "dialogInstance9.close();\n";
    echo "dialogInstance1.close();\n";
    echo "Loadjs('fw.proxy.acls.bugs.php?refresh=yes');\n";
    admin_tracks("Adding a new record $value to $GroupName ACL group");
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/proxy/acls/parse");
}


function start(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $type=(string)($_GET["type"] ?? "");
    $value=(string)($_GET["value"] ?? "");
    $valueencoded=urlencode($value);
    $html[]=$tpl->search_block($page,"",null,"table-$type","&type=$type&value=$valueencoded");
    echo $tpl->_ENGINE_parse_body($html);


}

function search(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $objects=$tpl->_ENGINE_parse_body("{objects}");
    // Les trois parametres etaient lus sans isset: un chargement de la modale
    // sans "value" -- ce que fait le navigateur au retour arriere -- remplissait
    // le journal d'avertissements PHP 8.
    $search=trim((string)($_GET["search"] ?? ""));
    $type=(string)($_GET["type"] ?? "");
    $value=(string)($_GET["value"] ?? "");
    $valueencoded=urlencode($value);

    $endpoint="/proxy/acls/sqlite/groups/all?type=".urlencode($type)."&search=".urlencode($search);
    $results=acls_api_get($endpoint);
    if(!is_array($results)){
        echo $tpl->FATAL_ERROR_SHOW_128("groups list unavailable");
        return;
    }

    // La colonne "{enabled}" a ete retiree: /groups/all ne renvoie PAS ce champ
    // et la page affichait une coche codee en dur sur chaque ligne -- une
    // colonne d'etat toujours identique n'informe de rien et laisse croire que
    // l'etat a ete verifie.
    $html=[];
    $html[]="<div class='awc-wrap'><div class='awc-card'><div class='awc-scroll'><table class='awc-tbl'>";
    $html[]="<thead><tr>";
    $html[]="<th>$objects (".htmlspecialchars($value).")</th>";
    $html[]="<th>&nbsp;</th>";
    $html[]="</tr></thead><tbody id='acladd-rows'>";
    $c=0;
    foreach($results as $g){
        $html[]=acladd_group_row($g,$valueencoded);
        $c++;
    }
    if($c==0){
        $html[]="<tr><td colspan='2' class='awc-empty'>{no_data}</td></tr>";
    }
    $html[]="</tbody></table></div>";
    $html[]="<div class='awc-foot'><span>{showing} $c</span>"
           ."<span class='awc-pg' id='acladd-pager'></span></div>";
    $html[]="</div></div>";

    // Le bloc <script> est concatene APRES l'analyse: la regex #{(.+?)}# de
    // _ENGINE_parse_body prendrait chaque accolade JavaScript pour un jeton.
    // L'init footable qui s'y trouvait ciblait une classe que le tableau ne
    // porte plus.
    $script="<script>NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS)."\n</script>";

    // awc_pager_js() APRES le tableau: les <tr> doivent exister dans le DOM.
    echo awc_table_style()
	    .$tpl->_ENGINE_parse_body($html)
	    .$script
	    .awc_pager_js($GLOBALS["FOOTABLE_PSIZE"],"acladd-rows","acladd-pager");
}

/**
 * Un groupe d'objets ACL candidat.
 * Le nom vient de la base et partait BRUT dans le HTML.
 */
function acladd_group_row($g,$valueencoded):string{
    $page=CurrentPageName();
    $GroupName=(string)($g["groupName"] ?? "");
    $ID=intval($g["id"] ?? 0);
    $choose="<button class='awc-btn' type='button' "
           ."OnClick=\"Loadjs('$page?add=$ID&value=$valueencoded');\">{select_this_object}</button>";
    $h ="<tr>";
    $h.="<td><div class='awc-ident'>";
    $h.="<span class='awc-ico'><i class='fas fa-cubes'></i></span>";
    $h.="<div class='awc-name'>".htmlspecialchars($GroupName)."</div>";
    $h.="</div></td>";
    $h.="<td><div class='awc-acts' style='opacity:1'>$choose</div></td>";
    $h.="</tr>";
    return $h;
}
