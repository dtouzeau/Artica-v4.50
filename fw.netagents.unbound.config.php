<?php
/**
 * Unbound Parameters form + revision history / rollback for a single remote agent.
 * Operates on the `parameters` object of the full UnboundConfig JSON blob.
 *
 * Entry:  LoadAjax('div','fw.netagents.unbound.config.php?id={agent_id}')
 */
include_once dirname(__FILE__) . '/ressources/class.template-admin.inc';
if (!isset($GLOBALS["CLASS_SOCKETS"])) { $GLOBALS["CLASS_SOCKETS"] = new sockets(); }
$users = new usersMenus();
if (!$users->AsSystemAdministrator) { exit(); }

if (isset($_GET["revisions"]))     { unbound_revisions(); exit; }
if (isset($_POST["save-params"]))  { params_save();       exit; }
if (isset($_POST["rollback"]))     { params_rollback();   exit; }
if (isset($_POST["validate"]))     { params_validate();   exit; }
config_page();

// ── Helpers ──────────────────────────────────────────────────────────────────

function aid(): int {
    return intval($_GET["id"] ?? $_POST["id"] ?? 0);
}

function get_config(int $id) {
    return json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/netagents/unbound/config/$id"));
}

function save_config(int $id, $cfg): ?object {
    $json = json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API_POST_JSON(
        "/netagents/unbound/config/$id", $cfg
    ));
    return is_object($json) ? $json : null;
}

function validate_config_api(int $id, $cfg): ?object {
    $json = json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API_POST_JSON(
        "/netagents/unbound/config/validate/$id", $cfg
    ));
    return is_object($json) ? $json : null;
}

// ── Main page ────────────────────────────────────────────────────────────────

function config_page(): void {
    $tpl  = new template_admin();
    $page = CurrentPageName();
    $id   = aid();

    $cfg = get_config($id);
    if (!is_object($cfg) || (isset($cfg->Status) && !$cfg->Status)) {
        $err = is_object($cfg) ? htmlspecialchars($cfg->Error ?? "{error}") : "{error}";
        echo $tpl->_ENGINE_parse_body($tpl->div_error($err));
        return;
    }

    $p = $cfg->parameters ?? new stdClass();
    $h = [];
    $h[] = "<div id='params-result-$id' style='margin-bottom:10px'></div>";

    // ── Listening & Interfaces ──
    $listenAddr = $p->PowerDNSListenAddr ?? '';
    $listenList = array_filter(array_map('trim', explode("\n", $listenAddr)));
    $outIface   = htmlspecialchars($p->UnboundOutGoingInterface ?? '');

    // Fetch network interfaces from agent status
    $statusJson = json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/netagents/status/$id"));
    $netIfaces = [];
    if (is_object($statusJson) && isset($statusJson->network_interfaces) && is_array($statusJson->network_interfaces)) {
        $netIfaces = $statusJson->network_interfaces;
    }

    $h[] = "<div class='ibox'><div class='ibox-title'><h5><i class='fas fa-network-wired'></i>&nbsp; {listening_interfaces}</h5></div>";
    $h[] = "<div class='ibox-content'>";
    $h[] = "  <div class='form-group'><label>{listen_addresses}</label>";
    $h[] = "  <input type='hidden' id='p-listen-$id' value='" . htmlspecialchars($listenAddr) . "'>";
    if (!empty($netIfaces)) {
        foreach ($netIfaces as $ni) {
            $niName = htmlspecialchars($ni->name ?? '');
            if ($niName === 'lo') continue;
            $ipv4 = (array)($ni->ipv4_addresses ?? []);
            $ipv6 = (array)($ni->ipv6_addresses ?? []);
            if (empty($ipv4) && empty($ipv6)) continue;
            $h[] = "  <div style='margin-bottom:8px;padding:8px 12px;background:#f9f9f9;border-radius:4px'>";
            $h[] = "    <strong><i class='fas fa-ethernet' style='color:#1c84c6'></i> $niName</strong>";
            foreach ($ipv4 as $a4) {
                $ip = htmlspecialchars($a4->address ?? '');
                if ($ip === '' || $ip === '127.0.0.1') continue;
                $chk = in_array($ip, $listenList) ? 'checked' : '';
                $h[] = "    <div class='checkbox' style='margin:2px 0 2px 20px'><label>";
                $h[] = "      <input type='checkbox' class='listen-chk-$id' value='$ip' $chk> <span style='font-family:monospace'>$ip</span>";
                $h[] = "    </label></div>";
            }
            foreach ($ipv6 as $a6) {
                $ip6 = htmlspecialchars($a6->address ?? '');
                if ($ip6 === '' || $ip6 === '::1') continue;
                $chk6 = in_array($ip6, $listenList) ? 'checked' : '';
                $h[] = "    <div class='checkbox' style='margin:2px 0 2px 20px'><label>";
                $h[] = "      <input type='checkbox' class='listen-chk-$id' value='$ip6' $chk6> <span style='font-family:monospace'>$ip6</span>";
                $h[] = "    </label></div>";
            }
            $h[] = "  </div>";
        }
        $h[] = "  <script>\$('.listen-chk-$id').on('change',function(){ var v=[];";
        $h[] = "    \$('.listen-chk-$id:checked').each(function(){ v.push(\$(this).val()); });";
        $h[] = "    \$('#p-listen-$id').val(v.join('\\n'));";
        $h[] = "  });</script>";
    } else {
        $h[] = "  <div class='text-muted' style='padding:8px'><i class='fas fa-exclamation-circle'></i> {no_interfaces_available}</div>";
        $h[] = "  <textarea id='p-listen-fallback-$id' class='form-control' rows='2' style='margin-top:5px'" ;
        $h[] = "    onchange=\"\$('#p-listen-$id').val(this.value)\">" . htmlspecialchars($listenAddr) . "</textarea>";
    }
    $h[] = "  </div>";
    $h[] = param_checkbox($id, 'ListenOnlyLoopBack', '{listen_only_loopback}', intval($p->ListenOnlyLoopBack ?? 0));
    $h[] = param_checkbox($id, 'UnboundAutomaticInterface', '{interface_automatic}', intval($p->UnboundAutomaticInterface ?? 0),"","{interface_automatic_unbound}");
    $h[] = param_checkbox($id, 'EnableipV6', '{enable_ipv6}', intval($p->EnableipV6 ?? 0));
    $h[] = param_checkbox($id, 'EnableDNSRootInts', '{use_root_hints}', intval($p->EnableDNSRootInts ?? 0),"","{use_root_hints_explain}");
    $h[] = "  <div class='form-group'><label>{outgoing_interface}</label>";
    if (!empty($netIfaces)) {
        $h[] = "    <select id='p-outgoing-$id' class='form-control'>";
        $h[] = "      <option value=''>{none}</option>";
        foreach ($netIfaces as $ni) {
            $niName = htmlspecialchars($ni->name ?? '');
            if ($niName === 'lo') continue;
            foreach ((array)($ni->ipv4_addresses ?? []) as $a4) {
                $ip = htmlspecialchars($a4->address ?? '');
                if ($ip === '' || $ip === '127.0.0.1') continue;
                $sel = ($ip === $outIface) ? " selected" : "";
                $h[] = "      <option value='$ip'$sel>$niName — $ip</option>";
            }
            foreach ((array)($ni->ipv6_addresses ?? []) as $a6) {
                $ip6 = htmlspecialchars($a6->address ?? '');
                if ($ip6 === '' || $ip6 === '::1') continue;
                $sel6 = ($ip6 === $outIface) ? " selected" : "";
                $h[] = "      <option value='$ip6'$sel6>$niName — $ip6</option>";
            }
        }
        $h[] = "    </select>";
    } else {
        $h[] = "    <input type='text' id='p-outgoing-$id' class='form-control' value='$outIface'>";
    }
    $h[] = "  </div>";
    $h[] = "</div></div>";

    // ── Cache ──
    $h[] = "<div class='ibox'><div class='ibox-title'><h5><i class='fas fa-database'></i>&nbsp; {cache_settings}</h5></div>";
    $h[] = "<div class='ibox-content'><div class='row'>";
    $h[] = param_number($id, 'UnBoundCacheMinTTL', '{cache-ttl} (Min)', intval($p->UnBoundCacheMinTTL ?? 0), 4);
    $h[] = param_number($id, 'UnBoundCacheMAXTTL', '{cache-ttl} (Max)', intval($p->UnBoundCacheMAXTTL ?? 86400), 4);
    $h[] = param_number($id, 'UnBoundCacheNEGTTL', '{negquery-cache-ttl}', intval($p->UnBoundCacheNEGTTL ?? 3600), 4);
    $h[] = "</div><div class='row'>";
    $h[] = param_number($id, 'UnBoundUnwantedReplyThreshold', '{UnBoundUnwantedReplyThreshold}', intval($p->UnBoundUnwantedReplyThreshold ?? 0), 6,"{UnBoundUnwantedReplyThreshold_explain}");
    $h[] = param_number($id, 'UNBOUND_OUTGOING_RANGE', '{outgoing_range}', intval($p->UNBOUND_OUTGOING_RANGE ?? 4096), 6);
    $h[] = "</div></div></div>";

    // ── Security ──
    $h[] = "<div class='ibox'><div class='ibox-title'><h5><i class='fas fa-shield-alt'></i>&nbsp; {security_settings}</h5></div>";
    $h[] = "<div class='ibox-content'>";
    $h[] = param_checkbox($id, 'UnBoundDNSSEC', '{DNSSEC}', intval($p->UnBoundDNSSEC ?? 0));
    $h[] = param_checkbox($id, 'UnboundEnableQNAMEMini', '{UnboundEnableQNAMEMini}', intval($p->UnboundEnableQNAMEMini ?? 0));
    $h[] = param_checkbox($id, 'EnableUseCapsForID', '{EnableUseCapsForID}', intval($p->EnableUseCapsForID ?? 0));
    $h[] = param_checkbox($id, 'EnableDNSRebindingAttacks', 'DNS Rebinding Prevention', intval($p->EnableDNSRebindingAttacks ?? 0));
    $h[] = param_checkbox($id, 'RefuseDNSRoot', '{refuse_dns_root}', intval($p->RefuseDNSRoot ?? 0));
    $h[] = param_checkbox($id, 'DisableLocalReservation', '{DisableLocalReserv}', intval($p->DisableLocalReservation ?? 0));
    $h[] = param_checkbox($id, 'UnboundAccessControl', '{access_control}', intval($p->UnboundAccessControl ?? 0));
    $h[] = "</div></div>";

    // ── EDNS Client Subnet ──
    $edns        = intval($p->UnboundEDNS ?? 0);
    $ednsNets    = htmlspecialchars($p->UnboundEDNSNetworks ?? '');
    $csAlwaysFwd = intval($p->ClientSubnetAlwaysForward ?? 0);
    $ednsDisplay = $edns ? '' : 'display:none';
    $h[] = "<div class='ibox'><div class='ibox-title'><h5><i class='fas fa-sitemap'></i>&nbsp; {edns_client_subnet}</h5></div>";
    $h[] = "<div class='text-muted' style='margin-top:-9px;margin-left: 36px;'>{edns_client_subnet_expl}</div>";

    $h[] = "<div class='ibox-content'>";
    $h[] = param_checkbox($id, 'UnboundEDNS', 'EDNS', $edns, "EDNSChange_$id()");
    $h[] = "<div id='p-edns-box-$id' style='$ednsDisplay;margin-left:20px'>";
    $h[] = "  <div class='form-group'><label>{edns_networks}</label>";
    $h[] = "    <textarea id='p-edns-nets-$id' class='form-control' rows='2'>$ednsNets</textarea></div>";
    $h[] = param_checkbox($id, 'ClientSubnetAlwaysForward', '{ClientSubnetAlwaysForward}', $csAlwaysFwd);
    $h[] = "</div>";
    $h[] = "</div></div>";

    // ── Safe Search ──
    $h[] = "<div class='ibox'><div class='ibox-title'><h5><i class='fas fa-search'></i>&nbsp; SafeSearchs</h5></div>";
    $h[] = "<div class='text-muted' style='margin-top:-9px;margin-left: 36px;'>{app_safesearch_explains}</div>";
    $h[] = "<div class='ibox-content'><div class='row'>";
    $safeSearchKeys = [
        'EnableGoogleSafeSearch'     => 'Google',
        'EnableYoutubeSafeSearch'    => 'YouTube',
        'EnableBingSafeSearch'       => 'Bing',
        'EnableDuckduckgoSafeSearch' => 'DuckDuckGo',
        'EnableYandexSafeSearch'     => 'Yandex',
        'EnableQwantSafeSearch'      => 'Qwant',
    ];
    foreach ($safeSearchKeys as $key => $label) {
        $val = intval($p->$key ?? 0);
        $chk = $val ? 'checked' : '';
        $h[] = "<div class='col-md-4' style='margin-bottom:8px'><div class='checkbox'><label>";
        $h[] = "  <input type='checkbox' id='p-$key-$id' $chk> $label";
        $h[] = "</label></div></div>";
    }
    $h[] = "</div></div></div>";

    // ── DNS-over-TLS ──
    $tlsEnable = intval($p->UnboundTLSEnable ?? 0);
    $tlsDisplay = $tlsEnable ? '' : 'display:none';
    $h[] = "<div class='ibox'><div class='ibox-title'><h5><i class='fas fa-lock'></i>&nbsp; {tls_settings}</h5></div>";
    $h[] = "<div class='ibox-content'>";
    $h[] = param_checkbox($id, 'UnboundTLSEnable', '{tls_settings}', $tlsEnable, "TLSChange_$id()");
    $h[] = "<div id='p-tls-box-$id' style='$tlsDisplay;margin-left:20px'>";
    $h[] = param_number($id, 'UnboundTLSPort', '{tls_port}', intval($p->UnboundTLSPort ?? 853), 6);
    $h[] = "  <div class='form-group'><label>{tls_certificate}</label>";
    $h[] = "    <input type='text' id='p-UnboundTLSCertificate-$id' class='form-control' value='" . htmlspecialchars($p->UnboundTLSCertificate ?? '') . "'></div>";
    $h[] = "</div>";
    $h[] = "</div></div>";

    // ── DNS-over-HTTPS ──
    $dohEnable = intval($p->UnboundDOHEnable ?? 0);
    $dohDisplay = $dohEnable ? '' : 'display:none';
    $h[] = "<div class='ibox'><div class='ibox-title'><h5><i class='fas fa-globe'></i>&nbsp; {doh_settings}</h5></div>";
    $h[] = "<div class='ibox-content'>";
    $h[] = param_checkbox($id, 'UnboundDOHEnable', '{doh_settings}', $dohEnable, "DOHChange_$id()");
    $h[] = "<div id='p-doh-box-$id' style='$dohDisplay;margin-left:20px'>";
    $h[] = param_number($id, 'UnboundDOHPort', '{listen_port}', intval($p->UnboundDOHPort ?? 443), 6);
    $h[] = "  <div class='form-group'><label>{certificate}</label>";
    $h[] = "    <input type='text' id='p-UnboundDOHCertificate-$id' class='form-control' value='" . htmlspecialchars($p->UnboundDOHCertificate ?? '') . "'></div>";
    $h[] = param_number($id, 'UnboundHttpMaxStreams', '{http_max_streams}', intval($p->UnboundHttpMaxStreams ?? 100), 6);
    $h[] = "</div>";
    $h[] = "</div></div>";

    // ── Redis Backend ──
    $redisEnable = intval($p->UnboundRedisEnabled ?? 0);
    $redisDisplay = $redisEnable ? '' : 'display:none';
    $h[] = "<div class='ibox'><div class='ibox-title'><h5><i class='fas fa-server'></i>&nbsp; {redis_cache}</h5></div>";
    $h[] = "<div class='ibox-content'>";
    $h[] = param_checkbox($id, 'UnboundRedisEnabled', '{redis_cache}', $redisEnable, "RedisChange_$id()");
    $h[] = "<div id='p-redis-box-$id' style='$redisDisplay;margin-left:20px'>";
    $h[] = param_number($id, 'UnBoundRedisDBTTL', '{redis_ttl}', intval($p->UnBoundRedisDBTTL ?? 3600), 6);
    $h[] = "</div>";
    $h[] = "</div></div>";

    // ── Save + Validate buttons ──
    $h[] = "<div style='margin-bottom:20px;text-align:right'>";
    $h[] = "  <button class='btn btn-default' style='margin-right:10px' onclick='ParamsValidate_$id()'>";
    $h[] = "    <i class='fas fa-check'></i> {validate_config}</button>";
    $h[] = "  <button class='btn btn-primary btn-lg' onclick='ParamsSave_$id()'>";
    $h[] = "    <i class='fas fa-save'></i> {save}</button>";
    $h[] = "</div>";

    // ── Revisions section ──
    $h[] = "<div class='ibox'><div class='ibox-title'><h5><i class='fas fa-history'></i>&nbsp; {revision_history}</h5></div>";
    $h[] = "<div class='ibox-content'><div id='unbound-revisions-$id'></div></div></div>";

    $h[] = params_js_block($id, $page);
    $h[] = "<script>\$(document).ready(function(){ LoadAjaxSilent('unbound-revisions-$id','$page?id=$id&revisions=1'); });</script>";

    echo $tpl->_ENGINE_parse_body(implode("\n", $h));
}

// ── Parameter UI helpers ─────────────────────────────────────────────────────

function param_checkbox(int $id, string $key, string $label, int $val, string $onchange = '',$Explain=''): string {
    $chk = $val ? 'checked' : '';
    $oc  = $onchange ? "onchange=\"$onchange\"" : '';
    $tooltip="";
    if(strlen($Explain)>2){
        $tpl=new template_admin();
        list($tooltip,$none)=$tpl->Tooltips("p-label-$key-$id",$Explain);
    }

    return "<div class='checkbox' style='margin-bottom:8px'><label $tooltip id='p-label-$key-$id'>"
         . "<input type='checkbox' id='p-$key-$id' $chk $oc> $label"
         . "</label></div>";
}

function param_number(int $id, string $key, string $label, int $val, int $colWidth = 4,$explain=null): string {

    $labelId=md5($id.$key.$colWidth.$label.$explain.$label);
    $Expl="";
    if(strlen($explain)>2){
        $tpl=new template_admin();
        list($Expl,$none)=$tpl->Tooltips("$labelId",$explain);
    }

    return "<div class='col-md-$colWidth'><div class='form-group'><label id='$labelId' $Expl>$label</label>"
         . "<input type='number' id='p-$key-$id' class='form-control' value='$val' min='0'>"
         . "</div></div>";
}

// ── Revisions ────────────────────────────────────────────────────────────────

function unbound_revisions(): void {
    $tpl  = new template_admin();
    $page = CurrentPageName();
    $id   = aid();

    $raw = $GLOBALS["CLASS_SOCKETS"]->REST_API("/netagents/unbound/revisions/$id");
    $revisions = json_decode($raw);

    if (!is_array($revisions) || empty($revisions)) {
        echo $tpl->_ENGINE_parse_body("<p class='text-muted'>{no_revisions_available}</p>");
        return;
    }

    $h = [];
    $h[] = "<table class='table table-striped table-condensed'>";
    $h[] = "<thead><tr style='background:#f5f5f5'>";
    $h[] = "  <th>{date}</th><th style='width:1%'></th>";
    $h[] = "</tr></thead><tbody>";

    foreach ($revisions as $rev) {
        $name = htmlspecialchars($rev->name ?? '');
        $path = htmlspecialchars($rev->path ?? '');

        // Extract timestamp from filename: unbound.json.20260309T100800Z → 20260309T100800Z
        $dateStr = $name;
        if (preg_match('/(\d{8}T\d{6}Z?)/', $name, $m)) {
            try {
                $dt = new DateTime($m[1]);
                $dt->setTimezone(new DateTimeZone(date_default_timezone_get()));
                $dateStr = $dt->format('Y-m-d H:i:s');
            } catch (Exception $e) {
                // Keep raw name
            }
        }

        $pathAttr = htmlspecialchars(json_encode($path), ENT_QUOTES);
        $h[] = "<tr>";
        $h[] = "  <td><i class='fas fa-file-code' style='color:#1c84c6;margin-right:5px'></i> $dateStr</td>";
        $h[] = "  <td><button class='btn btn-warning btn-xs' onclick='ParamsRollback_$id($pathAttr)'>";
        $h[] = "    <i class='fas fa-undo'></i> Rollback</button></td>";
        $h[] = "</tr>";
    }
    $h[] = "</tbody></table>";

    echo $tpl->_ENGINE_parse_body(implode("\n", $h));
}

// ── Save params ──────────────────────────────────────────────────────────────

function params_save(): void {
    $tpl = new template_admin();
    $id  = aid();
    $page = CurrentPageName();

    $cfg = get_config($id);
    if (!is_object($cfg) || (isset($cfg->Status) && !$cfg->Status)) {
        echo $tpl->_ENGINE_parse_body($tpl->div_error("{error}"));
        return;
    }

    $cfgArr = json_decode(json_encode($cfg), true);

    // Merge posted parameters
    $params = $cfgArr["parameters"] ?? [];
    $posted = json_decode($_POST["params_json"] ?? '{}', true);
    if (is_array($posted)) {
        foreach ($posted as $k => $v) {
            $params[$k] = $v;
        }
    }
    $cfgArr["parameters"] = $params;

    // Validate first
    $vResult = validate_config_api($id, $cfgArr);
    if (is_object($vResult) && isset($vResult->valid) && !$vResult->valid) {
        $errs = implode("<br>", array_map('htmlspecialchars', $vResult->errors ?? ['{config_invalid}']));
        echo $tpl->_ENGINE_parse_body($tpl->div_error($errs));
        return;
    }

    $result = save_config($id, $cfgArr);
    if (!is_object($result) || (isset($result->Status) && !$result->Status)) {
        $err = is_object($result) ? htmlspecialchars($result->Error ?? "{error}") : "{error}";
        echo $tpl->_ENGINE_parse_body($tpl->div_error($err));
        return;
    }

    echo $tpl->_ENGINE_parse_body($tpl->div_success("<i class='fas fa-check-circle'></i> {params_saved}"));
    admin_tracks("Netagent #$id: Unbound parameters saved");
}

// ── Validate only ────────────────────────────────────────────────────────────

function params_validate(): void {
    $tpl = new template_admin();
    $id  = aid();

    $cfg = get_config($id);
    if (!is_object($cfg) || (isset($cfg->Status) && !$cfg->Status)) {
        echo $tpl->_ENGINE_parse_body($tpl->div_error("{error}"));
        return;
    }

    $cfgArr = json_decode(json_encode($cfg), true);
    $params = $cfgArr["parameters"] ?? [];
    $posted = json_decode($_POST["params_json"] ?? '{}', true);
    if (is_array($posted)) {
        foreach ($posted as $k => $v) {
            $params[$k] = $v;
        }
    }
    $cfgArr["parameters"] = $params;

    $vResult = validate_config_api($id, $cfgArr);
    if (!is_object($vResult)) {
        echo $tpl->_ENGINE_parse_body($tpl->div_error("{error}"));
        return;
    }

    if (!empty($vResult->valid)) {
        echo $tpl->_ENGINE_parse_body($tpl->div_success("<i class='fas fa-check-circle'></i> {config_valid}"));
    } else {
        $errs = implode("<br>", array_map('htmlspecialchars', $vResult->errors ?? ['{config_invalid}']));
        echo $tpl->_ENGINE_parse_body($tpl->div_error("<i class='fas fa-times-circle'></i> {config_invalid}<br>$errs"));
    }
}

// ── Rollback ─────────────────────────────────────────────────────────────────

function params_rollback(): void {
    $tpl  = new template_admin();
    $id   = aid();
    $path = trim($_POST["rollback"] ?? '');

    if (empty($path)) {
        echo $tpl->_ENGINE_parse_body($tpl->div_error("{error}"));
        return;
    }

    $json = json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API_POST_JSON(
        "/netagents/unbound/rollback/$id", ["path" => $path]
    ));
    if (!is_object($json) || (isset($json->Status) && !$json->Status)) {
        $err = is_object($json) ? htmlspecialchars($json->Error ?? "{error}") : "{error}";
        echo $tpl->_ENGINE_parse_body($tpl->div_error($err));
        return;
    }

    echo $tpl->_ENGINE_parse_body($tpl->div_success("<i class='fas fa-check-circle'></i> {rollback_successful}"));
    admin_tracks("Netagent #$id: Unbound config rolled back to $path");
}

// ── JS block ─────────────────────────────────────────────────────────────────

function params_js_block(int $id, string $page): string {
    // Build the list of all parameter keys to collect from the form
    $checkboxKeys = [
        'ListenOnlyLoopBack','UnboundAutomaticInterface','EnableipV6','EnableDNSRootInts',
        'UnBoundDNSSEC','UnboundEnableQNAMEMini','EnableUseCapsForID',
        'EnableDNSRebindingAttacks','RefuseDNSRoot','DisableLocalReservation','UnboundAccessControl',
        'UnboundEDNS','ClientSubnetAlwaysForward',
        'EnableGoogleSafeSearch','EnableYoutubeSafeSearch','EnableBingSafeSearch',
        'EnableDuckduckgoSafeSearch','EnableYandexSafeSearch','EnableQwantSafeSearch',
        'UnboundTLSEnable','UnboundDOHEnable','UnboundRedisEnabled',
    ];
    $numberKeys = [
        'UnBoundCacheMinTTL','UnBoundCacheMAXTTL','UnBoundCacheNEGTTL',
        'UnBoundUnwantedReplyThreshold','UNBOUND_OUTGOING_RANGE',
        'UnboundTLSPort','UnboundDOHPort','UnboundHttpMaxStreams','UnBoundRedisDBTTL',
    ];
    $textKeys = ['UnboundTLSCertificate','UnboundDOHCertificate'];

    $cbJson = json_encode($checkboxKeys);
    $numJson = json_encode($numberKeys);
    $txtJson = json_encode($textKeys);

    $l = [];
    $l[] = "<script>";

    // Collect all params into a JSON object
    $l[] = "function _collectParams_$id(){";
    $l[] = "  var p={};";
    $l[] = "  p['PowerDNSListenAddr']=\$('#p-listen-$id').val();";
    $l[] = "  p['UnboundOutGoingInterface']=\$.trim(\$('#p-outgoing-$id').val());";
    $l[] = "  p['UnboundEDNSNetworks']=\$('#p-edns-nets-$id').val();";
    $l[] = "  var cbs=$cbJson;";
    $l[] = "  cbs.forEach(function(k){ p[k]=\$('#p-'+k+'-$id').is(':checked')?1:0; });";
    $l[] = "  var nums=$numJson;";
    $l[] = "  nums.forEach(function(k){ p[k]=parseInt(\$('#p-'+k+'-$id').val())||0; });";
    $l[] = "  var txts=$txtJson;";
    $l[] = "  txts.forEach(function(k){ p[k]=\$.trim(\$('#p-'+k+'-$id').val()); });";
    $l[] = "  return p;";
    $l[] = "}";

    // Save
    $l[] = "function ParamsSave_$id(){";
    $l[] = "  \$('#params-result-$id').html('<div class=\"alert alert-info\"><i class=\"fas fa-spinner fa-spin\"></i></div>');";
    $l[] = "  \$.post('$page',{'save-params':1,id:$id,params_json:JSON.stringify(_collectParams_$id())},function(r){";
    $l[] = "    \$('#params-result-$id').html(r);";
    $l[] = "    LoadAjaxSilent('unbound-revisions-$id','$page?id=$id&revisions=1');";
    $l[] = "  });";
    $l[] = "}";

    // Validate
    $l[] = "function ParamsValidate_$id(){";
    $l[] = "  \$('#params-result-$id').html('<div class=\"alert alert-info\"><i class=\"fas fa-spinner fa-spin\"></i></div>');";
    $l[] = "  \$.post('$page',{validate:1,id:$id,params_json:JSON.stringify(_collectParams_$id())},function(r){";
    $l[] = "    \$('#params-result-$id').html(r);";
    $l[] = "  });";
    $l[] = "}";

    // Rollback
    $l[] = "function ParamsRollback_$id(path){";
    $l[] = "  swal({title:'{confirm_rollback}',type:'warning',showCancelButton:true,";
    $l[] = "    confirmButtonColor:'#f8ac59',confirmButtonText:'{yes}',cancelButtonText:'{cancel}'},";
    $l[] = "  function(ok){ if(!ok) return;";
    $l[] = "    \$('#params-result-$id').html('<div class=\"alert alert-info\"><i class=\"fas fa-spinner fa-spin\"></i></div>');";
    $l[] = "    \$.post('$page',{rollback:path,id:$id},function(r){";
    $l[] = "      \$('#params-result-$id').html(r);";
    $l[] = "      if(r.indexOf('alert-success')>-1){ setTimeout(function(){ LoadAjax(\$('#params-result-$id').closest('.tab-pane').attr('id'),'$page?id=$id'); },500); }";
    $l[] = "    });";
    $l[] = "  });";
    $l[] = "}";

    // Toggle handlers
    $l[] = "function EDNSChange_$id(){ if(\$('#p-UnboundEDNS-$id').is(':checked')){\$('#p-edns-box-$id').show();}else{\$('#p-edns-box-$id').hide();} }";
    $l[] = "function TLSChange_$id(){ if(\$('#p-UnboundTLSEnable-$id').is(':checked')){\$('#p-tls-box-$id').show();}else{\$('#p-tls-box-$id').hide();} }";
    $l[] = "function DOHChange_$id(){ if(\$('#p-UnboundDOHEnable-$id').is(':checked')){\$('#p-doh-box-$id').show();}else{\$('#p-doh-box-$id').hide();} }";
    $l[] = "function RedisChange_$id(){ if(\$('#p-UnboundRedisEnabled-$id').is(':checked')){\$('#p-redis-box-$id').show();}else{\$('#p-redis-box-$id').hide();} }";

    $l[] = "</script>";
    return implode("\n", $l);
}
