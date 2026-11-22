<?php
// POST SP10 20261023-12
// Post SP10 - 13/10/2026
// Service Pack 9 2026-10-10
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.patch.tables.fw.inc");
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}


if(isset($_GET["table"])){table();exit;}
if(isset($_GET["backend-js"])){backend_js();exit;}
if(isset($_GET["backend-popup"])){backend_popup();exit;}
if(isset($_POST["backendname"])){backends_save();exit;}
if(isset($_GET["backend-enable-js"])){backends_enable();exit;}
if(isset($_GET["backend-delete-js"])){backend_delete_js();exit;}
if(isset($_POST["backend-delete"])){backend_delete();exit;}
page();

// ---------------------------------------------------------------------------
// Acces aux donnees par articarest, comme fw.haproxy.backends.php dont cette
// page est l'equivalent pour les services de type Reverse Proxy.
// ---------------------------------------------------------------------------

function haproxy_rest_read($endpoint): array
{
    $raw = $GLOBALS["CLASS_SOCKETS"]->REST_API($endpoint);
    $json = @json_decode($raw, true);
    if (!is_array($json)) { return array("Status"=>false, "Error"=>trim((string)$raw)); }
    if (!isset($json["Status"])) { $json["Status"] = false; }
    if (!isset($json["Error"])) { $json["Error"] = ""; }
    return $json;
}

/** Enveloppe d'ecriture, cote POST + corps JSON. */
function haproxy_rest_write($endpoint, $data = array()): array
{
    $raw = $GLOBALS["CLASS_SOCKETS"]->REST_API_POST_JSON($endpoint, $data);
    $json = @json_decode($raw, true);
    if (!is_array($json)) {
        $raw = trim((string)$raw);
        if ($raw == "") { $raw = "articarest: no answer"; }
        return array("Status"=>false, "Info"=>"", "Error"=>$raw);
    }
    return array("Status"=>!empty($json["Status"]),
        "Info"=>(string)($json["Info"] ?? ""), "Error"=>(string)($json["Error"] ?? ""));
}

/** Extrait une liste d'une enveloppe, avec repli sur la premiere valeur tableau. */
function hap_rest_list($r, $key): array
{
    if (isset($r[$key]) && is_array($r[$key])) { return $r[$key]; }
    foreach ($r as $k => $v) {
        if ($k=="Status" || $k=="Error" || $k=="Info") { continue; }
        if (is_array($v)) { return $v; }
    }
    return array();
}

/** MainConfig est stocke en base64 d'un serialize PHP. */
function hap_mainconfig_decode($raw): array
{
    $raw = trim((string)$raw);
    if ($raw == "") { return array(); }
    $a = @unserialize(@base64_decode($raw));
    return is_array($a) ? $a : array();
}

function hap_mainconfig_encode($array): string
{
    if (!is_array($array)) { $array = array(); }
    $s = base64_encode(serialize($array));
    // La classe d'origine ecrivait "" en dessous de vingt caracteres: un tableau
    // vide serialise fait moins, et on garde le meme comportement.
    return strlen($s) < 20 ? "" : $s;
}

/** Les backends d'un service, mis en cache pour la duree de la requete. */
function hap_backends($servicename): array
{
    static $CACHE = array();
    $k = (string)$servicename;
    if (isset($CACHE[$k])) { return $CACHE[$k]; }
    $r = haproxy_rest_read("/haproxy/backends?servicename=" . urlencode($k));
    $CACHE[$k] = empty($r["Status"]) ? array() : hap_rest_list($r, "backends");
    return $CACHE[$k];
}

/** Un backend precis dans cette liste. */
function hap_backend_row($servicename, $backendname): array
{
    foreach (hap_backends($servicename) as $l) {
        if (!is_array($l)) { continue; }
        if ((string)($l["backendname"] ?? "") === (string)$backendname) { return $l; }
    }
    return array();
}

/** Les types de service, libelles d'interface repris de la classe haproxy. */
function hap_servicetype_array(): array
{
    return array(
        0=>"HTTP/HTTPs Proxy Load-balancer",
        1=>"HTTP Load-balancer",
        2=>"Reverse Proxy",
        3=>"TCP redirect",
        4=>"SMTP redirect");
}

/**
 * Un service, lu par articarest.
 *
 * Remplace haproxy_multi SANS changer les proprietes que la page consulte: la
 * fenetre d'edition et le tableau lisent une quinzaine de champs, les reecrire
 * en acces tableau aurait touche plusieurs centaines de lignes pour aucun gain.
 * Seule la source change.
 */
class hap_rest_service
{
    public $servicename = null;
    public $listen_ip = null;
    public $listen_port = null;
    public $loadbalancetype = 0;
    public $servicetype = 0;
    public $enabled = 0;
    public $transparent = 0;
    public $ssl = null;
    public $certificates = array();
    public $MainConfig = array();
    public $servicetype_array = array();
    public $found = false;

    function __construct($servicename = null)
    {
        $this->servicetype_array = hap_servicetype_array();
        $this->servicename = $servicename;
        if ($servicename == null) { return; }

        $r = haproxy_rest_read("/haproxy/service?servicename=" . urlencode($servicename));
        if (empty($r["Status"])) { return; }
        $row = $r["service"] ?? null;
        if (!is_array($row)) { $row = hap_rest_list($r, "service"); }
        if (!is_array($row) || count($row) == 0) { return; }

        $this->found = true;
        foreach (array("listen_ip","listen_port","loadbalancetype","servicetype",
                     "enabled","transparent","ssl") as $k) {
            if (isset($row[$k])) { $this->$k = $row[$k]; }
        }
        if (isset($row["certificates"]) && is_array($row["certificates"])) {
            $this->certificates = $row["certificates"];
        }
        // MainConfig voyage tel qu'il est stocke: base64 d'un serialize PHP.
        $this->MainConfig = hap_mainconfig_decode($row["MainConfig"] ?? null);
    }
}


/**
 * Un backend, lu et ecrit par articarest.
 *
 * Meme principe que hap_rest_service: les proprietes et save()/DeleteBackend()
 * gardent la signature de haproxy_backends.
 */
class hap_rest_backend
{
    public $servicename = null;
    public $backendname = null;
    public $sitename = null;
    public $listen_ip = null;
    public $listen_port = null;
    public $bweight = 1;
    public $enabled = 0;
    public $localInterface = null;
    public $MainConfig = array();
    public $found = false;
    public $error = "";

    function __construct($servicename, $backendname)
    {
        $this->servicename = $servicename;
        $this->backendname = $backendname;
        if ($backendname == null) { return; }
        $row = hap_backend_row($servicename, $backendname);
        if (count($row) == 0) { return; }
        $this->found = true;
        foreach (array("sitename","listen_ip","listen_port","bweight","enabled",
                     "localInterface") as $k) {
            if (isset($row[$k])) { $this->$k = $row[$k]; }
        }
        $this->MainConfig = hap_mainconfig_decode($row["MainConfig"] ?? null);
    }

    /**
     * $reload=false par defaut: enregistrer un reglage n'applique plus la
     * configuration. Le repartiteur n'est reconstruit que sur un geste
     * explicite -- le bouton {apply_configuration}, ou une action dont l'effet
     * n'aurait aucun sens differe (activation, suppression).
     */
    public function save($reload = false): bool
    {
        // Le point d'entree prend la ligne entiere: un champ omis serait ecrit
        // a vide. La normalisation du nom (minuscules, accents, espaces) reste
        // cote Go, qui possede la table.
        $r = haproxy_rest_write("/haproxy/backend/save", array(
            "servicename"    => (string)$this->servicename,
            "backendname"    => (string)$this->backendname,
            "sitename"       => (string)$this->sitename,
            "listen_ip"      => (string)$this->listen_ip,
            "listen_port"    => (string)$this->listen_port,
            // Chaine et non nombre: le point d'entree rend "bweight":"1" et sa
            // structure la declare en string -- un entier JSON s'y refuse.
            "bweight"        => (string)intval($this->bweight),
            "enabled"        => intval($this->enabled),
            "localInterface" => (string)$this->localInterface,
            "MainConfig"     => hap_mainconfig_encode($this->MainConfig),
            "reload"         => (bool)$reload,
        ));
        $this->error = $r["Error"];
        return !empty($r["Status"]);
    }

    public function DeleteBackend(): bool
    {
        $r = haproxy_rest_write("/haproxy/backend/delete", array(
            "servicename" => (string)$this->servicename,
            "backendname" => (string)$this->backendname,
            "reload"      => false,
        ));
        $this->error = $r["Error"];
        return !empty($r["Status"]);
    }
}

function page(){
    $page=CurrentPageName();
    $servicenameenc=urlencode($_GET["servicename"]);
    echo "<div id='backend-list'></div><script>LoadAjax('backend-list','$page?table=$servicenameenc')</script>";
}
function backend_js(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $new_backend=$tpl->_ENGINE_parse_body("{new_backend}");
    // Pentest 2026-11-04 : servicename/backendname etaient recopies sans encodage dans la page (XSS).
    // Le titre est decode (html_entity_decode) puis rendu en HTML : double encodage.
    $xssSrv=req_html(req_html($_GET["servicename"] ?? ""));
    if($_GET["backend-js"]==null){$title="$xssSrv&raquo;$new_backend";}else{$title="$xssSrv&raquo;".req_html(req_html($_GET["backendname"] ?? ""));}
    $backendenc=urlencode($_GET["backend-js"]);
    $servicename=urlencode($_GET["servicename"]);
    $tpl->js_dialog2($title, "$page?backend-popup=$backendenc&servicename=$servicename");
}
function backends_enable(){
    $tpl=new template_admin();
    $hap=new hap_rest_backend($_GET["servicename"], $_GET["backend-enable-js"]);
    // Etat voulu transmis par la ligne quand il est la, sinon bascule sur ce
    // qu'on vient de lire: une requete rejouee ecrit alors la meme valeur au
    // lieu de revenir a son point de depart.
    if(isset($_GET["state"])){
        $hap->enabled=intval($_GET["state"])==1 ? 1 : 0;
    }else{
        $hap->enabled=intval($hap->enabled)==1 ? 0 : 1;
    }
    // Rien n'est applique ici: seul {apply_configuration} reconstruit.
    if(!$hap->save()){
        header("content-type: application/x-javascript");
        echo $tpl->js_mysql_alert($hap->error);
    }
}


function backend_delete_js(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $servicename=$_GET["servicename"];
    $backendname=$_GET["backend-delete-js"];
    $value="$servicename|$backendname";

    $js[]="$('#{$_GET["md"]}').remove()";
    $js[]="LoadAjax('table-haproxy-services','fw.haproxy.services.php?table=yes');";
    // Pentest 2026-11-04 : servicename/backend-delete-js etaient recopies sans encodage dans la page (XSS).
    $tpl->js_confirm_delete(req_html("$servicename/$backendname") , "backend-delete", $value,@implode(";", $js));

}

function backend_delete(){
    $tpl=new template_admin();
    $tpl->CLEAN_POST();
    $f=explode("|",$_POST["backend-delete"]);
    $hap=new hap_rest_backend($f[0], $f[1]);
    if(!$hap->DeleteBackend()){
        echo $tpl->_ENGINE_parse_body("{failed}: ").htmlspecialchars($hap->error);
    }

}

function backend_popup(){
    $users=new usersMenus();
    $page=CurrentPageName();
    $tpl=new template_admin();
    $servicename=$_GET["servicename"];
    $servicenameenc=urlencode($servicename);
    $backendname=$_GET["backend-popup"];
    $smtp_disable=0;
    $hapServ=new hap_rest_service($servicename);
    if($hapServ->loadbalancetype==2){$smtp_disable=1;}
    $UseSMTPProto=$hapServ->MainConfig["UseSMTPProto"];
    if(!is_numeric($UseSMTPProto)){$UseSMTPProto=0;}

    $hapMaster=new hap_rest_service($servicename);

    $hap=new hap_rest_backend($servicename,$backendname);
    $remove_this_backend=$tpl->javascript_parse_text("{remove_this_backend}");

    $jsafter[]="LoadAjax('table-haproxy-services','fw.haproxy.services.php?table=yes');";
    $jsafter[]="LoadAjax('backend-list','$page?table=$servicenameenc');";

    $buttonname="{apply}";
    if($backendname==null){$buttonname="{add}";$toolbox=null;}
    if(!is_numeric($hap->MainConfig["inter"])){$hap->MainConfig["inter"]=60000;}
    if(!is_numeric($hap->MainConfig["fall"])){$hap->MainConfig["fall"]=3;}
    if(!is_numeric($hap->MainConfig["rise"])){$hap->MainConfig["rise"]=2;}
    if(!is_numeric($hap->MainConfig["maxconn"])){$hap->MainConfig["maxconn"]=10000;}
    if(!is_numeric($hap->MainConfig["asSquidArtica"])){$hap->MainConfig["asSquidArtica"]=0;}

    $ip=new networking();
    $Interfaces=$ip->Local_interfaces();
    $Interfaces[null]="{default}";
    unset($Interfaces["lo"]);


    // Pentest 2026-11-04 : servicename/backend-popup etaient recopies sans encodage dans la page (XSS).
    $form[]=$tpl->field_info("servicename", "{servicename}", $servicename,true);
    if($backendname<>null){
        $form[]=$tpl->field_info("backendname", "{backendname}", $backendname);
        $form[]=$tpl->field_info("sitename", "{sitename}", $hap->sitename);
        $title=req_html($backendname);

    }else{
        $form[]=$tpl->field_text("backendname", "{sitename}", null,true,"{haproxy_website_explain}");

        $title="{new_backend}";
        $jsafter[]="dialogInstance2.close();";
    }

    $jsafter[]="if( document.getElementById('table-haproxy-bckstatus') ){ LoadAjaxSilent('table-haproxy-bckstatus','fw.haproxy.monitor.php?table=yes');}";

    if($hap->listen_port<2){$hap->listen_port=80;}



    $form[]=$tpl->field_array_hash($Interfaces, "localInterface", "{outgoing_address}", $hap->localInterface,false,"{haproxy_local_interface_help}");
    $form[]=$tpl->field_ipaddr("listen_ip", "{destination_address}", $hap->listen_ip,true);
    $form[]=$tpl->field_numeric("listen_port","{destination_port}", $hap->listen_port);
    $form[]=$tpl->field_text("HostHeader", "{HostHeader}", $hap->MainConfig["HostHeader"]);




    $form[]=$tpl->field_section("{timeouts}");
    $form[]=$tpl->field_numeric("bweight","{weight}", $hap->bweight);
    $form[]=$tpl->field_numeric("maxconn","{max_connections}", $hap->MainConfig["maxconn"]);
    $form[]=$tpl->field_numeric("inter","{check_interval} ({milliseconds})", $hap->MainConfig["inter"]);
    $form[]=$tpl->field_numeric("fall","{failed_number} ({attempts})", $hap->MainConfig["fall"]);
    $form[]=$tpl->field_numeric("rise","{success_number} ({attempts})", $hap->MainConfig["rise"]);
    $html=$tpl->form_outside($title, @implode("\n", $form),null,$buttonname,@implode("", $jsafter),"AsSquidAdministrator");
    echo $tpl->_ENGINE_parse_body($html);

}


function table(){

    $page=CurrentPageName();
    $tpl=new template_admin();
    $servicename=$_GET["table"];
    $servicenameenc=urlencode($_GET["table"]);


    $ARRAY["PROGRESS_FILE"]=PROGRESS_DIR."/recusor.restart.progress";
    $ARRAY["LOG_FILE"]=PROGRESS_DIR."/recusor.restart.log";
    $ARRAY["CMD"]="pdns.php?restart-recusor=yes";
    $ARRAY["TITLE"]="{reconfigure_service} {APP_PDNS_RECURSOR}";

    $prgress=base64_encode(serialize($ARRAY));
    $jsrestart="Loadjs('fw.progress.php?".fw_progress_args_b64($prgress)."&mainid=progress-firehol-restart')";

    $html[]="<div class=\"btn-group\" data-toggle=\"buttons\" style='margin-top:10px'>";
    $html[]="<label class=\"btn btn btn-primary\" OnClick=\"Loadjs('$page?backend-js=&servicename=$servicenameenc');\">";
    $html[]="<i class='fa fa-plus'></i> {new_backend} </label>";
    $html[]="</div>";
    $html[]="<table id='table-haproxy-backends' class=\"footable table table-stripped\" data-page-size=\"100\" data-paging=\"true\">";
    $html[]="<thead>";
    $html[]="<tr>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text' nowrap>{backends}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text' nowrap>{sitename}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{destination}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text' nowrap>{weight}</th>";
    $html[]="<th data-sortable=false class='text-capitalize' data-type='text'>{active2}</th>";
    $html[]="<th data-sortable=false class='text-capitalize' data-type='text'>Del</th>";
    $html[]="</tr>";
    $html[]="</thead>";
    $html[]="<tbody>";

    // Le tri par poids est celui qu'attend l'affichage; on ne le refait que si
    // le champ est bien la, pour ne pas dependre d'une garantie non ecrite.
    $results=hap_backends($servicename);
    $sorted=true;
    foreach($results as $l){ if(!isset($l["bweight"])){$sorted=false;break;} }
    if($sorted){
        usort($results,function($a,$b){return intval($a["bweight"])-intval($b["bweight"]);});
    }

    $TRCLASS=null;$ligne=null;
    foreach ($results as $ligne) {
        if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}

        $md=md5(serialize($ligne));
        $listen_ip=$ligne["listen_ip"];
        $listen_port=$ligne["listen_port"];
        $sitename=$ligne["sitename"];
        $interface="$listen_ip:$listen_port";
        $backendnameenc=urlencode($ligne["backendname"]);
        // Etat voulu transmis: une requete rejouee ecrit la meme valeur au lieu
        // de revenir a son point de depart.
        $disable=$tpl->icon_check($ligne["enabled"],"Loadjs('$page?backend-enable-js=$backendnameenc&servicename=$servicenameenc&state=".(intval($ligne["enabled"])?0:1)."')",null,"AsSquidAdministrator");
        $delete=$tpl->icon_delete("Loadjs('$page?backend-delete-js=$backendnameenc&servicename=$servicenameenc&md=$md')","AsSquidAdministrator");
        $interface=$tpl->td_href($interface,"{$ligne["backendname"]}","Loadjs('$page?backend-js=$backendnameenc&servicename=$servicenameenc')");
        $backendname=$tpl->td_href($ligne["backendname"],"$listen_ip:$listen_port","Loadjs('$page?backend-js=$backendnameenc&servicename=$servicenameenc')");
        $html[]="<tr class='$TRCLASS' id='$md'>";
        $html[]="<td><strong>$backendname</strong></td>";
        $html[]="<td><strong>$sitename</strong></td>";
        $html[]="<td style='width:1%' nowrap>$interface</td>";
        $html[]="<td style='width:1%' nowrap>{$ligne['bweight']}</a></td>";
        $html[]="<td style='width:1%' nowrap>$disable</td>";
        $html[]="<td style='width:1%' nowrap>$delete</td>";
        $html[]="</tr>";


    }
    $html[]="</tbody>";
    $html[]="<tfoot>";
    $html[]="<tr>";
    $html[]="<td colspan='5'>";
    $html[]="<ul class='pagination pull-right'></ul>";
    $html[]="</td>";
    $html[]="</tr>";
    $html[]="</tfoot>";
    $html[]="</table>";
    $html[]="
	<script>
	NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS)."
	$(document).ready(function() { $('#table-haproxy-backends').footable( { 	\"filtering\": { \"enabled\": true }, \"sorting\": { \"enabled\": true },\"paging\": { \"size\": {$GLOBALS["FOOTABLE_PSIZE"]} } } ); });
	</script>";

    echo $tpl->_ENGINE_parse_body(@implode("\n", $html));


}
function backends_save(){
    $tpl=new template_admin();
    $tpl->CLEAN_POST();
    if($_POST["backendname"]==null){
        $_POST["backendname"]=$_POST["sitename"];
    }
    if(!isset($_POST["sitename"])){$_POST["sitename"]=$_POST["backendname"];}
    $_POST["backendname"]=str_replace(".", "_", $_POST["backendname"]);
    $_POST["backendname"]=str_replace(" ", "_", $_POST["backendname"]);
    $_POST["backendname"]=str_replace("/", "_", $_POST["backendname"]);
    if($_POST["backendname"]=="*"){$_POST["backendname"]="_ALL_";}

    $hap=new hap_rest_backend($_POST["servicename"],$_POST["backendname"] );
    if(isset($_POST["listen_ip"])){$hap->listen_ip=$_POST["listen_ip"];}
    if(isset($_POST["listen_port"])){$hap->listen_port=$_POST["listen_port"];}
    if(isset($_POST["bweight"])){$hap->bweight=$_POST["bweight"];}
    if(isset($_POST["localInterface"])){$hap->localInterface=$_POST["localInterface"];}
    if(isset($_POST["inter"])){$hap->MainConfig["inter"]=$_POST["inter"];}
    if(isset($_POST["fall"])){$hap->MainConfig["fall"]=$_POST["fall"];}
    if(isset($_POST["rise"])){$hap->MainConfig["rise"]=$_POST["rise"];}
    if(isset($_POST["sitename"])){$hap->sitename=$_POST["sitename"];}
    if(isset($_POST["maxconn"])){$hap->MainConfig["maxconn"]=$_POST["maxconn"];}
    if(isset($_POST["asSquidArtica"])){$hap->MainConfig["asSquidArtica"]=$_POST["asSquidArtica"];}
    if(isset($_POST["FailOverOnly"])){$hap->MainConfig["FailOverOnly"]=$_POST["FailOverOnly"];}
    if(isset($_POST["postfix-send-proxy"])){$hap->MainConfig["postfix-send-proxy"]=$_POST["postfix-send-proxy"];}
    if(isset($_POST["HostHeader"])){$hap->MainConfig["HostHeader"]=$_POST["HostHeader"];}


    if(!$hap->save()){
        echo $tpl->_ENGINE_parse_body("{failed}: ").htmlspecialchars($hap->error);
    }
}