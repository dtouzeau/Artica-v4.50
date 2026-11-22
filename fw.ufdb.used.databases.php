<?php
if(isset($_SESSION["uid"])){header("content-type: application/x-javascript");echo "document.location.href='logoff.php'";exit();}
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
// awc_table_style() / awc_search_bar() / awc_pager_js() / awc_sort_js() /
// awc_filter_js(): le kit des tableaux d'entites. L'include doit rester au
// PREMIER NIVEAU -- glisse dans le bootstrap "if(!class_exists('sockets'))" il
// ne s'executerait jamais (class.template-admin.inc a deja charge la classe) et
// la page mourrait sur "Call to undefined function awc_table_style()".
include_once(dirname(__FILE__)."/ressources/class.statistics_appliance.inc");
$tpl=new template_admin();if(!$tpl->xPrivs()){exit();}

if(isset($_GET["popup"])){popup();exit;}
js();

function js(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$tpl->js_dialog("{used_databases}", "$page?popup=yes");
}

function popup() {
	$page=CurrentPageName();
	$tpl=new template_admin();

	$UfdbUsedDatabases=unserialize($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UfdbUsedDatabases"));
    if(!is_array($UfdbUsedDatabases)){$UfdbUsedDatabases=array();}
    if(!isset($UfdbUsedDatabases["INSTALLED"])){
        $UfdbUsedDatabases["INSTALLED"]=array();
    }
    if(!isset($UfdbUsedDatabases["MISSING"])){
        $UfdbUsedDatabases["MISSING"]=array();
    }

    $INSTALLED=$UfdbUsedDatabases["INSTALLED"];
    $MISSING=$UfdbUsedDatabases["MISSING"];
    if(!is_array($MISSING)){$MISSING=array();}
    if(!is_array($INSTALLED)){$INSTALLED=array();}
    $html[]=awc_search_bar("ufdbdb-search","{search}");

    $html[]="<div class='awc-wrap'><div class='awc-card'><div class='awc-scroll'>";
    $html[]="<table class='awc-tbl awc-tbl-compact'>";
    $html[]="<thead>";
    $html[]="<tr>";
    $html[]="<th>{database}/{categories}</th>";
    // L'entete de taille est cliquable (awc_sort_js), d'ou son id. La fleche est
    // un <span> ORDINAIRE: Font Awesome tourne en mode SVG+JS et remplace tout
    // <i class='fa'> par un <svg> dont on ne peut plus ecrire l'innerHTML.
    $html[]="<th id='ufdbdb-th-size' class='awc-nowrap'>{size} <span id='ufdbdb-th-size-arrow'></span></th>";
    $html[]="<th class='awc-nowrap'>{status}</th>";
    $html[]="</tr>";
    $html[]="</thead>";
    $html[]="<tbody id='ufdbdb-rows'>";

    $catz=new mysql_catz();
    $COUNT_MISSING=0;
    $COUNT_INSTALLED=0;

    // Les manquantes d'abord: c'est ce sur quoi l'administrateur doit agir.
    foreach ($MISSING as $category_id=>$none){
        $COUNT_MISSING++;
        $md=md5(serialize($category_id));
        $category_name=$catz->CategoryIntToStr($category_id);
        // data-sort est obligatoire meme ici: awc_sort_js() IGNORE une ligne qui
        // n'en porte pas, elle resterait figee pendant que les autres bougent.
        $html[]="<tr id='$md' data-sort='0'>";
        $html[]="<td><div class='awc-ident'>"
               ."<span class='awc-ico awc-ico-off'><i class='fas fa-triangle-exclamation'></i></span>"
               ."<span class='awc-name awc-name-off'>$category_name"
               ."<span class='awc-muted'>&nbsp;($category_id)</span></span></div></td>";
        $html[]="<td class='awc-nowrap awc-mono awc-muted'>-</td>";
        $html[]="<td class='awc-nowrap'><span class='awc-badge awc-badge-alert'>{not_installed}</span></td>";
        $html[]="</tr>";
    }

    foreach ($INSTALLED as $index=>$array){
        if(!is_array($array)){continue;}
        $COUNT_INSTALLED++;
        $md=md5(serialize($array));
        $category_id=isset($array["DB"])?$array["DB"]:0;
        $category_name=$catz->CategoryIntToStr($category_id);
        $bytes=isset($array["SIZE"])?floatval($array["SIZE"]):0;
        $size=FormatBytes($bytes/1024);
        $html[]="<tr id='$md' data-sort='".sprintf('%.0f',$bytes)."'>";
        $html[]="<td><div class='awc-ident'>"
               ."<span class='awc-ico'><i class='fas fa-database'></i></span>"
               ."<span class='awc-name'>$category_name</span></div></td>";
        $html[]="<td class='awc-nowrap awc-mono'>$size</td>";
        $html[]="<td class='awc-nowrap'><span class='awc-badge awc-badge-on'>{installed}</span></td>";
        $html[]="</tr>";
    }

    if(($COUNT_MISSING+$COUNT_INSTALLED)==0){
        $html[]="<tr><td colspan='3'><div class='awc-empty'>{no_data}</div></td></tr>";
    }

    $html[]="</tbody>";
    $html[]="</table></div>";

    $foot=$tpl->FormatNumber($COUNT_INSTALLED)." {installed}";
    if($COUNT_MISSING>0){
        $foot.="&nbsp;&middot;&nbsp;<span class='text-danger'>"
              .$tpl->FormatNumber($COUNT_MISSING)." {not_installed}</span>";
    }
    $html[]="<div class='awc-foot'><span>$foot</span>"
           ."<span class='awc-pg' id='ufdbdb-pager'></span></div>";
    $html[]="</div></div>";
    // Le <script> n'etait JAMAIS referme dans la version FooTable.
    $html[]="<script>NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS)."</script>";

    // CSS et JS se concatenent AUTOUR de _ENGINE_parse_body(): sa regex
    // #{(.+?)}# lirait chaque corps de regle CSS et chaque bloc JS comme un
    // {token}. Ordre impose: pager, puis tri (il rend la main par
    // tb.__awcRedraw), puis filtre.
    echo awc_table_style()
        .$tpl->_ENGINE_parse_body(@implode("\n", $html))
        .awc_pager_js($GLOBALS["FOOTABLE_PSIZE"],"ufdbdb-rows","ufdbdb-pager")
        .awc_sort_js("ufdbdb-th-size","ufdbdb-rows","desc")
        .awc_filter_js("ufdbdb-search","ufdbdb-rows","ufdbdb-pager");
}
