<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");


if(isset($_GET["service-js"])){service_js();exit;}
if(isset($_GET["popup-main"])){popup_main();exit;}
if(isset($_GET["popup-table"])){popup_table();exit;}
if(isset($_GET["pattern-remove"])){rule_remove();exit;}
if(isset($_GET["pattern-enable"])){rule_enable();exit;}
if(isset($_GET["rule-js"])){rule_js();exit;}
if(isset($_GET["popup-rule"])){rule_popup();exit;}
if(isset($_POST["ruleid"])){rule_save();exit;}


// ---------------------------------------------------------------------------
// Acces aux donnees par articarest. Meme enveloppe que les autres pages du
// module; seule la gestion des certificats differe, voir save().
// ---------------------------------------------------------------------------

/** Enveloppe de lecture articarest (GET). */
function haproxy_rest_read($endpoint): array
{
    $raw = $GLOBALS["CLASS_SOCKETS"]->REST_API($endpoint);
    $json = @json_decode($raw, true);
    if (!is_array($json)) { return array("Status"=>false, "Error"=>trim((string)$raw)); }
    if (!isset($json["Status"])) { $json["Status"] = false; }
    if (!isset($json["Error"])) { $json["Error"] = ""; }
    return $json;
}

/** Enveloppe d'ecriture (POST + corps JSON). */
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

/** MainConfig et certificates voyagent en base64 d'un serialize PHP. */
function hap_blob_decode($raw): array
{
    $raw = trim((string)$raw);
    if ($raw == "") { return array(); }
    $a = @unserialize(@base64_decode($raw));
    return is_array($a) ? $a : array();
}

function hap_blob_encode($array): string
{
    if (!is_array($array) || count($array) == 0) {
        // Un tableau vide se serialise en "a:0:{}", que le point d'entree
        // refuse ("do not decode to a php list"). La colonne vide se dit par
        // une chaine vide, ce qui est aussi ce que la base contient quand rien
        // n'a jamais ete enregistre.
        return "";
    }
    return base64_encode(serialize($array));
}

/**
 * Un service, lu et ecrit par articarest.
 *
 * Remplace haproxy_multi SANS changer les proprietes que la page consulte: les
 * fenetres de reglages en lisent une vingtaine, les reecrire en acces tableau
 * aurait touche plusieurs centaines de lignes pour aucun gain. Seule la source
 * change, et save()/DeleteService() gardent leur signature.
 */
class hap_rest_service
{
    public $algo = array();
    public $servicename;
    public $listen_ip = null;
    public $listen_port = null;
    public $loadbalancetype = 0;
    public $dispatch_mode = null;
    public $http_reuse_array = array();
    public $http_reuse = null;
    public $MainConfig = array();
    public $enabled = 0;
    public $transparent = 0;
    public $transparentsrcport = 0;
    public $servicetype = 0;
    public $checkup_interval = 0;
    public $client_timout = 0;
    public $crtype = 0;
    public $wakeup_interval = 0;
    public $ssl;
    public $certificates = array();
    public $certificates_raw = null;   // la valeur telle qu'elle est stockee
    public $tunnel_mode = 0;
    public $found = false;
    public $error = "";
    public $servicetype_array = array(
        0=>"HTTP/HTTPs Proxy Load-balancer",
        1=>"HTTP Load-balancer",
        2=>"Reverse Proxy",
        3=>"TCP redirect",
        4=>"SMTP redirect");

    function __construct($servicename = null)
    {
        $this->algo["source"]="{strict-hashed-ip}";
        $this->algo["roundrobin"]="{round-robin}";
        $this->algo["leastconn"]="{leastconn}";
        $this->algo["uri"]="HTTP URL";
        $this->http_reuse_array["Disable"]="Disable";
        $this->http_reuse_array["never"]="never";
        $this->http_reuse_array["safe"]="safe";
        $this->http_reuse_array["aggressive"]="aggressive";
        $this->http_reuse_array["always"]="always";
        if ($servicename == null) { return; }
        $this->servicename = $servicename;

        $r = haproxy_rest_read("/haproxy/service?servicename=" . urlencode($servicename));
        if (empty($r["Status"])) { $this->defaults(); return; }
        $row = (isset($r["service"]) && is_array($r["service"])) ? $r["service"] : hap_rest_list($r, "service");
        if (!is_array($row) || count($row) == 0) { $this->defaults(); return; }

        $this->found = true;
        foreach (array("listen_ip","listen_port","dispatch_mode","transparent",
                     "transparentsrcport","tunnel_mode") as $k) {
            if (isset($row[$k])) { $this->$k = $row[$k]; }
        }
        $this->enabled          = intval($row["enabled"] ?? 0);
        $this->loadbalancetype  = $row["loadbalancetype"] ?? 0;
        $this->servicetype      = intval($row["servicetype"] ?? 0);
        $this->crtype           = intval($row["crtype"] ?? 0);
        $this->client_timout    = intval($row["client_timout"] ?? 0);
        $this->checkup_interval = intval($row["checkup_interval"] ?? 0);
        $this->wakeup_interval  = intval($row["wakeup_interval"] ?? 0);
        $this->ssl              = intval($row["ssl"] ?? 0);
        $this->MainConfig       = hap_blob_decode($row["MainConfig"] ?? null);
        $this->certificates_raw = (string)($row["certificates"] ?? "");
        $this->certificates     = hap_blob_decode($this->certificates_raw);
        $this->defaults();
    }

    /** Memes valeurs de repli que la classe d'origine. */
    private function defaults()
    {
        if (!is_numeric($this->tunnel_mode)) { $this->tunnel_mode = 0; }
        if ($this->client_timout == 0)    { $this->client_timout = 60; }
        if ($this->checkup_interval == 0) { $this->checkup_interval = 10; }
        if ($this->wakeup_interval == 0)  { $this->wakeup_interval = 5; }
    }

    /**
     * $reload=false par defaut: enregistrer un reglage n'applique plus la
     * configuration. Le repartiteur n'est reconstruit que sur un geste
     * explicite -- le bouton {apply_configuration}, ou une action dont l'effet
     * n'aurait aucun sens differe (activation, suppression).
     */
    public function save($reload = false): bool
    {
        $r = haproxy_rest_write("/haproxy/service/save", array(
            "servicename"        => (string)$this->servicename,
            "servicetype"        => intval($this->servicetype),
            "listen_ip"          => (string)$this->listen_ip,
            "listen_port"        => (string)$this->listen_port,
            "ipaddrport"         => $this->listen_ip . ":" . $this->listen_port,
            "loadbalancetype"    => (string)$this->loadbalancetype,
            "dispatch_mode"      => (string)$this->dispatch_mode,
            "transparent"        => intval($this->transparent),
            "transparentsrcport" => intval($this->transparentsrcport),
            "tunnel_mode"        => intval($this->tunnel_mode),
            "crtype"             => intval($this->crtype),
            "client_timout"      => intval($this->client_timout),
            "checkup_interval"   => intval($this->checkup_interval),
            "wakeup_interval"    => intval($this->wakeup_interval),
            "enabled"            => intval($this->enabled),
            "ssl"                => intval($this->ssl),
            "MainConfig"         => hap_blob_encode($this->MainConfig),
            // Cette page EST celle qui edite les certificats: on renvoie la
            // liste re-encodee, pas la valeur stockee -- l'inverse de
            // fw.haproxy.services.php, qui ne fait que l'afficher.
            "certificates"       => hap_blob_encode($this->certificates),
            "reload"             => (bool)$reload,
        ));
        $this->error = $r["Error"];
        return !empty($r["Status"]);
    }

    public function DeleteService(): bool
    {
        $r = haproxy_rest_write("/haproxy/service/delete", array(
            "servicename" => (string)$this->servicename, "reload" => false));
        $this->error = $r["Error"];
        return !empty($r["Status"]);
    }
}

function service_js(){
    $serviceid  = trim($_GET["service-js"]);
    $serviceidC = urlencode($serviceid);
    $tpl        = new template_admin();
    $page       = CurrentPageName();
    $tpl->js_dialog4("{certificates} $serviceid","$page?popup-main=$serviceidC");
}
function rule_js(){
    $serviceid  = trim($_GET["serviceid"]);
    $serviceidC = urlencode($serviceid);
    $rule       = intval($_GET["rule-js"]);
    $tpl        = new template_admin();
    $page       = CurrentPageName();

    $title      = "{rule}: $rule";
    if($rule==0){$title="{new_rule}";}

    $tpl->js_dialog5("{certificates}: $title","$page?popup-rule=$rule&serviceid=$serviceidC");
}
function compile_js_progress($ID,$final=null):string{
    return "Loadjs('fw.nginx.apply.php?serviceid=$ID&function=NgixSitesReload&addjs=');";

}


function rule_remove():bool{
    $ruleid=intval($_GET["pattern-remove"]);
    $serviceid=trim($_GET["serviceid"]);
    $sock       = new hap_rest_service($serviceid);
    unset($sock->certificates[$ruleid]);
    $sock->save();
    echo "$('#$ruleid').remove();\n";
    echo "HaProxyBalancerParametersMain();";
    return true;

}

function rule_enable(){
    $ruleid=intval($_GET["pattern-enable"]);
    $serviceid=trim($_GET["serviceid"]);
    $sock       = new hap_rest_service($serviceid);
    $data       = $sock->certificates;
    if(intval($data[$ruleid]["enable"])==1){
        $data[$ruleid]["enable"]=0;
    }else{
        $data[$ruleid]["enable"]=1;
    }
    $sock->certificates=$data;
    $sock->save();
}

function rule_popup(){
    $serviceid  = $_GET["serviceid"];
    $ruleid     = intval($_GET["popup-rule"]);
    $tpl        = new template_admin();
    $sock       = new hap_rest_service($serviceid);
    $data       = $sock->certificates;
    $ligne["enable"] = 1;
    $bt="{add}";

    $sslmin_ver_ar=array(
        "SSLv3"=>"SSLv3",
        "TLSv1.0"=>"TLSv1.0",
        "TLSv1.1"=>"TLSv1.1",
        "TLSv1.2"=>"TLSv1.2",
        "TLSv1.3"=>"TLSv1.3"
    );

    $ciphers=$ligne["ciphers"];
    if($ciphers==null){$ciphers="none";}
    if($ruleid>0){ $ligne=$data[$ruleid];$bt="{apply}"; }
    $jsrestart="dialogInstance5.close();LoadHaProxyCertificates();HaProxyBalancerParametersMain();";
    $form[]=$tpl->field_hidden("ruleid",$ruleid);
    $form[]=$tpl->field_hidden("serviceid",$serviceid);
    $form[]=$tpl->field_checkbox("enable","{enable}",$ligne["enable"]);
    $form[]=$tpl->field_text("description","{description}",$ligne["description"]);
    $form[]=$tpl->field_certificate("certificate","{certificate}",$ligne["certificate"]);
    $form[]=$tpl->field_array_hash($sslmin_ver_ar,"ssl-min-ver","{aboveeq}/{eq2}",$ligne["ssl-min-ver"]);
    $form[]=$tpl->field_text("ciphers","{ssl_ciphers}",$ciphers);
    $form[]=$tpl->field_text("snifilter","{snifilter}",$ligne["snifilter"]);






    $html[]=$tpl->form_outside("{certificate} $ruleid",$form,null,$bt,$jsrestart,"AsSquidAdministrator");
    echo $tpl->_ENGINE_parse_body($html);

}

function rule_save(){
    $tpl        = new template_admin();
    $tpl->CLEAN_POST();
    $serviceid  = trim($_POST["serviceid"]);
    $ruleid     = intval($_POST["ruleid"]);
    $sock       = new hap_rest_service($serviceid);
    $data       = $sock->certificates;
    if($ruleid==0){
        $ruleid=time()+rand(0,5);
    }
    $data[$ruleid]=$_POST;
    $sock->certificates=$data;
    $sock->save();
}

function popup_main(){
    $serviceid  = urlencode($_GET["popup-main"]);
    $servicemd  = md5($serviceid);
    $page       = CurrentPageName();
    echo "<div id='main-popup-$servicemd'></div>
    <script>
        function LoadHaProxyCertificates(){ LoadAjax('main-popup-$servicemd','$page?popup-table=$serviceid');}
        LoadHaProxyCertificates();
        </script>";
}

function popup_table(){
    $serviceid  = trim($_GET["popup-table"]);
    $serviceidC = urlencode($serviceid);
    $tpl        = new template_admin();
    $page       = CurrentPageName();
    $sock       = new hap_rest_service($serviceid);
    $tableid    = time();

    $compile_js_progress=compile_js_progress($serviceid);

    $html[]="<div id='progress-compile-replace-$serviceid'></div>";
    $html[]="<div class=\"btn-group\" data-toggle=\"buttons\" style='margin-top:20px'>";
    $html[]="<label class=\"btn btn btn-primary\" OnClick=\"Loadjs('$page?rule-js=0&serviceid=$serviceidC');\">
	<i class='fa fa-plus'></i> {add} {certificate} </label>";
    //$html[]="<label class=\"btn btn btn-warning\" OnClick=\"$compile_js_progress\"><i class='fa fa-save'></i> {apply_parameters_to_the_system} </label>";
    $html[]="</div>";

    $html[]="
<table class=\"table table-hover\" id='$tableid'>
	<thead>
    	<tr>
        	<th nowrap>{certificates}</th>
        	<th nowrap>{enable}</small></th>
        	<th nowrap>{delete}</small></th>
        </tr>
  	</thead>
	<tbody>
";

    $data=$sock->certificates;

    foreach ($data as $num=>$ligne){
        $ss_min_ver_text=null;
        $enable=intval($ligne["enable"]);
        $description=trim($ligne["description"]);
        $pattern=$ligne["certificate"];
        if(strlen($pattern)>128){$pattern=substr($pattern,0,125)."...";}
        $pattern=htmlentities($pattern);
        $ss_min_ver=$ligne["ssl-min-ver"];
        if($ss_min_ver<>null){$ss_min_ver_text="&nbsp;<small>{aboveeq}/{eq2} <strong>$ss_min_ver</strong>/small>";}
        if($description<>null){ $description="<br><small>$description</small>"; }

        $enable=$tpl->icon_check($enable,"Loadjs('$page?pattern-enable=$num&serviceid=$serviceidC')","","AsSquidAdministrator");
        $delete=$tpl->icon_delete("Loadjs('$page?pattern-remove=$num&serviceid=$serviceidC')","AsSquidAdministrator");
        $pattern=$tpl->td_href($pattern,"","Loadjs('$page?rule-js=$num&serviceid=$serviceidC');");

        $html[]="<tr id='$num'>
				<td width=50%>$pattern{$ss_min_ver_text}{$description}</td>
				<td style='width:1%' nowrap>$enable</td>
				<td style='width:1%' nowrap>$delete</td>
				</tr>";

    }

    $html[]="</tbody>";
    $html[]="<tfoot>";

    $html[]="<tr>";
    $html[]="<td colspan='4'>";
    $html[]="<ul class='pagination pull-right'></ul>";
    $html[]="</td>";
    $html[]="</tr>";
    $html[]="</tfoot>";
    $html[]="</table>";
    $html[]="<script>NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS);
    $html[]="$(document).ready(function() { $('#$tableid').footable( { \"filtering\": { \"enabled\": true";
    $html[]="},\"sorting\": { \"enabled\": true },";
    $html[]="\"paging\": { \"size\": {$GLOBALS["FOOTABLE_PSIZE"]} } }); });";
    $html[]="</script>";
    echo $tpl->_ENGINE_parse_body($html);
}