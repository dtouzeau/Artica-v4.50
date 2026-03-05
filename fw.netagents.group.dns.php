<?php
/**
 * DNS configuration pushed to every enabled agent in a group.
 * Hostname is NOT changed for group operations (DNS-only by API design).
 *
 * Entry:  Loadjs('fw.netagents.group.dns.php?dns-js={group_id}')  — opens dialog
 *         LoadAjax('div','fw.netagents.group.dns.php?id={group_id}') — inline form
 * API:    POST /netagents/system/group/{id}/network
 *         Body: {"dns":{nameservers,search,domain,options,sortlist}}
 *         Response: {group_id, total, success, failed, results:[{agent_id,hostname,success,message}]}
 */
include_once dirname(__FILE__) . '/ressources/class.template-admin.inc';
if (!isset($GLOBALS["CLASS_SOCKETS"])) { $GLOBALS["CLASS_SOCKETS"] = new sockets(); }

if (isset($_GET["dns-js"])) { gdns_js();   exit; }
if (isset($_POST["save"]))  { gdns_save(); exit; }
gdns_form();

// ── Helpers ──────────────────────────────────────────────────────────────────

function gid(): int {
    return intval($_GET["id"] ?? $_POST["id"] ?? 0);
}

function gdns_parse_options(string $str): array {
    $r = [
        'ndots'          => '',
        'timeout'        => '',
        'attempts'       => '',
        'rotate'         => false,
        'edns0'          => false,
        'trust-ad'       => false,
        'use-vc'         => false,
        'no-check-names' => false,
        'inet6'          => false,
        'single-request' => false,
        'no-reload'      => false,
    ];
    foreach (preg_split('/\s+/', trim($str)) as $part) {
        if ($part === '') continue;
        if (strpos($part, ':') !== false) {
            [$k, $v] = explode(':', $part, 2);
            if (array_key_exists($k, $r)) $r[$k] = $v;
        } elseif (array_key_exists($part, $r)) {
            $r[$part] = true;
        }
    }
    return $r;
}

// ── Dispatcher actions ───────────────────────────────────────────────────────

/** Opens a 820px dialog containing the group DNS form. */
function gdns_js(): bool {
    $tpl  = new template_admin();
    $page = CurrentPageName();
    $id   = intval($_GET["dns-js"]);

    $grp       = json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/netagents/groups/$id"));
    $groupName = (is_object($grp) && !empty($grp->name)) ? htmlspecialchars($grp->name) : "Group #$id";
    return $tpl->js_dialog3("{dns_settings} — $groupName", "$page?id=$id", 820);
}

/** Renders the group DNS form with group info header. */
function gdns_form(): bool {
    $tpl  = new template_admin();
    $id   = gid();
    $page = CurrentPageName();

    $grp = json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/netagents/groups/$id"));
    if (!is_object($grp)) {
        echo $tpl->_ENGINE_parse_body($tpl->div_error("{error}"));
        return false;
    }
    if (isset($grp->Status) && !$grp->Status) {
        echo $tpl->_ENGINE_parse_body($tpl->div_error(htmlspecialchars($grp->Error ?? "{error}")));
        return false;
    }

    $groupName  = htmlspecialchars($grp->name ?? "Group #$id");
    $agentCount = intval($grp->agent_count ?? 0);

    // ── Pre-fill from first agent in the group ──
    $ns1 = $ns2 = $ns3 = $search = $domain = $sortlist = '';
    $refHostname = '';
    $opts = gdns_parse_options('');
    $agents = $grp->agents ?? [];
    if (!empty($agents) && is_object($agents[0])) {
        $firstId  = intval($agents[0]->id ?? 0);
        $refHostname = htmlspecialchars($agents[0]->hostname ?? "Agent #$firstId");
        if ($firstId > 0) {
            $st  = json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/netagents/status/$firstId"));
            $dns = (is_object($st) && isset($st->dns) && is_object($st->dns)) ? $st->dns : null;
            if ($dns) {
                $ns  = $dns->nameservers ?? [];
                $ns1 = htmlspecialchars($ns[0] ?? '');
                $ns2 = htmlspecialchars($ns[1] ?? '');
                $ns3 = htmlspecialchars($ns[2] ?? '');
                $search   = htmlspecialchars($dns->search   ?? '');
                $domain   = htmlspecialchars($dns->domain   ?? '');
                $sortlist = htmlspecialchars($dns->sortlist ?? '');
                $opts     = gdns_parse_options($dns->options ?? '');
            }
        }
    }

    $h   = [];

    // ── Group info banner ──
    $h[] = "<div class='alert alert-info' style='margin-bottom:15px;margin-top:10px'>";
    $h[] = "  <i class='fas fa-layer-group'></i> <strong>$groupName</strong>";
    $h[] = "  &nbsp;<span class='badge' style='background:#1c84c6;color:#fff'>$agentCount</span> {agents}";
    $h[] = "  &nbsp;&mdash; {group_dns_explain}";
    if ($refHostname !== '') {
        $h[] = "  <br><small class='text-muted' style='margin-top:4px;display:inline-block'>"
             . "<i class='fas fa-info-circle'></i> {from}: <strong>$refHostname</strong></small>";
    }
    $h[] = "</div>";

    $h[] = "<div id='gdns-result-$id'></div>";
    $h[] = gdns_ns_box($id, $ns1, $ns2, $ns3);
    $h[] = gdns_resolver_box($id, $search, $domain);
    $h[] = gdns_options_box($id, $opts);
    $h[] = gdns_sortlist_box($id, $sortlist);
    $h[] = "<div class='text-right' style='margin-bottom:20px'>";
    $h[] = "  <button class='btn btn-primary btn-lg' onclick='GdnsSave_$id()'>";
    $h[] = "    <i class='fas fa-save'></i> {save}";
    $h[] = "  </button>";
    $h[] = "</div>";
    $h[] = gdns_js_block($id, $page);

    echo $tpl->_ENGINE_parse_body(implode("\n", $h));
    return true;
}

/** Pushes DNS config to all agents in the group. Returns an HTML result snippet. */
function gdns_save(): void {
    $tpl = new template_admin();
    $id  = gid();

    $nameservers = array_values(array_filter([
        trim($_POST["ns1"] ?? ''),
        trim($_POST["ns2"] ?? ''),
        trim($_POST["ns3"] ?? ''),
    ]));
    if (empty($nameservers)) {
        echo $tpl->_ENGINE_parse_body($tpl->div_warning("{at_least_one_nameserver}"));
        return;
    }

    $data = [
        "dns" => [
            "nameservers" => $nameservers,
            "search"      => trim($_POST["search"]   ?? ''),
            "domain"      => trim($_POST["domain"]   ?? ''),
            "options"     => trim($_POST["opts"]     ?? ''),
            "sortlist"    => trim($_POST["sortlist"] ?? ''),
        ],
    ];

    $json = json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API_POST_JSON(
        "/netagents/system/group/$id/network", $data
    ));
    if (!is_object($json)) {
        echo $tpl->_ENGINE_parse_body($tpl->div_error("{error}"));
        return;
    }
    if (isset($json->Status) && !$json->Status) {
        echo $tpl->_ENGINE_parse_body($tpl->div_error(htmlspecialchars($json->Error ?? "{error}")));
        return;
    }

    $total   = intval($json->total   ?? 0);
    $success = intval($json->success ?? 0);
    $failed  = intval($json->failed  ?? 0);
    $results = $json->results ?? [];

    $h   = [];

    // ── Summary badges ──
    $h[] = "<div style='margin-bottom:12px'>";
    $h[] = "  <span class='badge' style='background:#676a6c;color:#fff;font-size:13px;padding:5px 10px;margin-right:5px'>{total}: $total</span>";
    $h[] = "  <span class='badge' style='background:#1ab394;color:#fff;font-size:13px;padding:5px 10px;margin-right:5px'>{success}: $success</span>";
    if ($failed > 0) {
        $h[] = "  <span class='badge' style='background:#ed5565;color:#fff;font-size:13px;padding:5px 10px'>{failed}: $failed</span>";
    }
    $h[] = "</div>";

    // ── Per-agent results table ──
    if (!empty($results)) {
        $h[] = "<table class='table table-striped table-condensed'>";
        $h[] = "  <thead><tr style='background:#f5f5f5'>";
        $h[] = "    <th style='width:1%'></th>";
        $h[] = "    <th>{hostname}</th>";
        $h[] = "    <th>{status}</th>";
        $h[] = "  </tr></thead><tbody>";
        foreach ($results as $r) {
            $hostname = htmlspecialchars($r->hostname ?? "Agent #{$r->agent_id}");
            $msg      = htmlspecialchars($r->message ?? '');
            if (!empty($r->success)) {
                $ico  = "<i class='fas fa-check-circle' style='color:#1ab394'></i>";
                $cell = "<span style='color:#1ab394'>{success}</span>";
            } else {
                $ico  = "<i class='fas fa-times-circle' style='color:#ed5565'></i>";
                $cell = "<span style='color:#ed5565'>{failed}</span>"
                      . ($msg !== '' ? " &mdash; <small>$msg</small>" : '');
            }
            $h[] = "    <tr><td>$ico</td><td><strong>$hostname</strong></td><td>$cell</td></tr>";
        }
        $h[] = "  </tbody></table>";
    }

    echo $tpl->_ENGINE_parse_body(implode("\n", $h));
    admin_tracks("Group #$id: DNS configuration pushed to $total agents ($success success, $failed failed)");
}

// ── UI box builders ──────────────────────────────────────────────────────────

function gdns_ns_box(int $id, string $ns1 = '', string $ns2 = '', string $ns3 = ''): string {
    $h   = [];
    $h[] = "<div class='ibox'>";
    $h[] = "  <div class='ibox-title'><h5><i class='fas fa-server'></i>&nbsp; {nameservers}</h5></div>";
    $h[] = "  <div class='ibox-content'><div class='row'>";
    foreach ([
        ['ns1', $ns1, '#1ab394', 'DNS 1'],
        ['ns2', $ns2, '#1c84c6', 'DNS 2'],
        ['ns3', $ns3, '#676a6c', 'DNS 3'],
    ] as [$key, $val, $color, $label]) {
        $h[] = "    <div class='col-md-4'>";
        $h[] = "      <label style='color:$color'><i class='fas fa-circle' style='font-size:8px;margin-right:5px'></i> $label</label>";
        $h[] = "      <input type='text' id='gdns-$key-$id' class='form-control' value='$val' placeholder='x.x.x.x'>";
        $h[] = "    </div>";
    }
    $h[] = "  </div></div></div>";
    return implode("\n", $h);
}

function gdns_resolver_box(int $id, string $search = '', string $domain = ''): string {
    $h   = [];
    $h[] = "<div class='ibox'>";
    $h[] = "  <div class='ibox-title'><h5><i class='fas fa-search'></i>&nbsp; {resolver}</h5></div>";
    $h[] = "  <div class='ibox-content'><div class='row'>";
    $h[] = "    <div class='col-md-6'>";
    $h[] = "      <label>{search_domains}</label>";
    $h[] = "      <input type='text' id='gdns-search-$id' class='form-control' value='$search' placeholder='example.com local.lan'>";
    $h[] = "      <p class='help-block'>{search_domains_explain}</p>";
    $h[] = "    </div>";
    $h[] = "    <div class='col-md-6'>";
    $h[] = "      <label>{domain}</label>";
    $h[] = "      <input type='text' id='gdns-domain-$id' class='form-control' value='$domain' placeholder='example.com'>";
    $h[] = "      <p class='help-block'>{domain_explain}</p>";
    $h[] = "    </div>";
    $h[] = "  </div></div></div>";
    return implode("\n", $h);
}

function gdns_options_box(int $id, array $opts): string {
    $h   = [];
    $h[] = "<div class='ibox'>";
    $h[] = "  <div class='ibox-title'><h5><i class='fas fa-sliders-h'></i>&nbsp; {advanced_options}</h5></div>";
    $h[] = "  <div class='ibox-content'>";

    // ── Numeric options ──
    $h[] = "    <div class='row' style='margin-bottom:20px'>";
    foreach ([
        ['ndots',    $opts['ndots'],    '1', '15', '{ndots_explain}'],
        ['timeout',  $opts['timeout'],  '1', '30', '{timeout_explain}'],
        ['attempts', $opts['attempts'], '1', '5',  '{attempts_explain}'],
    ] as [$k, $v, $min, $max, $explain]) {
        $h[] = "      <div class='col-md-4'>";
        $h[] = "        <label><span style='font-family:monospace;color:#1ab394'>$k</span></label>";
        $h[] = "        <input type='number' id='gdns-opt-$k-$id' class='form-control'"
                      . " value='" . htmlspecialchars($v) . "'"
                      . " min='$min' max='$max' placeholder='{default}'>";
        $h[] = "        <p class='help-block' style='font-size:11px'>$explain</p>";
        $h[] = "      </div>";
    }
    $h[] = "    </div>";

    // ── Boolean options ──
    $h[] = "    <div class='row'>";
    foreach ([
        ['rotate',         'rotate',         '{rotate_explain}'],
        ['edns0',          'edns0',          '{edns0_explain}'],
        ['trust-ad',       'trust_ad',       '{trust_ad_explain}'],
        ['use-vc',         'use_vc',         '{use_vc_explain}'],
        ['no-check-names', 'no_check_names', '{no_check_names_explain}'],
        ['inet6',          'inet6',          '{inet6_explain}'],
        ['single-request', 'single_request', '{single_request_explain}'],
        ['no-reload',      'no_reload',      '{no_reload_explain}'],
    ] as [$label, $key, $explain]) {
        $checked = !empty($opts[$label]) ? 'checked' : '';
        $h[] = "      <div class='col-md-3' style='margin-bottom:15px'>";
        $h[] = "        <div class='checkbox'><label>";
        $h[] = "          <input type='checkbox' id='gdns-opt-$key-$id' $checked>";
        $h[] = "          <span style='font-family:monospace;color:#1ab394'>$label</span>";
        $h[] = "        </label></div>";
        $h[] = "        <p class='help-block' style='font-size:11px'>$explain</p>";
        $h[] = "      </div>";
    }
    $h[] = "    </div>";
    $h[] = "  </div></div>";
    return implode("\n", $h);
}

function gdns_sortlist_box(int $id, string $sortlist = ''): string {
    $h   = [];
    $h[] = "<div class='ibox'>";
    $h[] = "  <div class='ibox-title'><h5><i class='fas fa-sort-amount-down'></i>&nbsp; sortlist</h5></div>";
    $h[] = "  <div class='ibox-content'>";
    $h[] = "    <input type='text' id='gdns-sortlist-$id' class='form-control'"
              . " value='$sortlist' placeholder='130.155.160.0/255.255.240.0 130.155.0.0'>";
    $h[] = "    <p class='help-block'>{sortlist_explain}</p>";
    $h[] = "  </div></div>";
    return implode("\n", $h);
}

/** Emits the GdnsSave_{id}() JavaScript function. */
function gdns_js_block(int $id, string $page): string {
    $l   = [];
    $l[] = "<script>";
    $l[] = "function GdnsSave_$id(){";
    $l[] = "  var ns=[];";
    $l[] = "  ['ns1','ns2','ns3'].forEach(function(k){";
    $l[] = "    var v=\$.trim(\$('#gdns-'+k+'-$id').val());";
    $l[] = "    if(v) ns.push(v);";
    $l[] = "  });";
    $l[] = "  if(ns.length===0){ alert('{at_least_one_nameserver}'); return; }";
    $l[] = "  var opts=[];";
    $l[] = "  ['ndots','timeout','attempts'].forEach(function(k){";
    $l[] = "    var v=\$.trim(\$('#gdns-opt-'+k+'-$id').val());";
    $l[] = "    if(v&&!isNaN(v)&&parseInt(v,10)>0) opts.push(k+':'+v);";
    $l[] = "  });";
    $l[] = "  var bp=[['rotate','rotate'],['edns0','edns0'],['trust-ad','trust_ad'],";
    $l[] = "          ['use-vc','use_vc'],['no-check-names','no_check_names'],['inet6','inet6'],";
    $l[] = "          ['single-request','single_request'],['no-reload','no_reload']];";
    $l[] = "  bp.forEach(function(p){ if(\$('#gdns-opt-'+p[1]+'-$id').is(':checked')) opts.push(p[0]); });";
    $l[] = "  \$('#gdns-result-$id').html('<div class=\"alert alert-info\"><i class=\"fas fa-spinner fa-spin\"></i> {saving}...</div>');";
    $l[] = "  \$.post('$page',{";
    $l[] = "    save:1, id:$id,";
    $l[] = "    ns1:\$.trim(\$('#gdns-ns1-$id').val()),";
    $l[] = "    ns2:\$.trim(\$('#gdns-ns2-$id').val()),";
    $l[] = "    ns3:\$.trim(\$('#gdns-ns3-$id').val()),";
    $l[] = "    search:\$.trim(\$('#gdns-search-$id').val()),";
    $l[] = "    domain:\$.trim(\$('#gdns-domain-$id').val()),";
    $l[] = "    opts:opts.join(' '),";
    $l[] = "    sortlist:\$.trim(\$('#gdns-sortlist-$id').val())";
    $l[] = "  },function(r){ \$('#gdns-result-$id').html(r); });";
    $l[] = "}";
    $l[] = "</script>";
    return implode("\n", $l);
}
