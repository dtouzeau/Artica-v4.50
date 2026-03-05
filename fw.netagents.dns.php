<?php
/**
 * DNS configuration for a single remote agent (resolv.conf)
 * Covers: nameservers (up to 3), search, domain, options (all flags), sortlist, hostname.
 *
 * Entry:  Loadjs('fw.netagents.dns.php?dns-js={agent_id}')   — opens dialog
 *         LoadAjax('div','fw.netagents.dns.php?id={agent_id}') — inline form
 * API:    POST /netagents/system/{id}/network
 *         Body: {"dns":{nameservers,search,domain,options,sortlist},"hostname":""}
 */
include_once dirname(__FILE__) . '/ressources/class.template-admin.inc';
if (!isset($GLOBALS["CLASS_SOCKETS"])) { $GLOBALS["CLASS_SOCKETS"] = new sockets(); }

if (isset($_GET["dns-js"])) { dns_js();   exit; }
if (isset($_POST["save"]))  { dns_save(); exit; }
dns_form();

// ── Helpers ──────────────────────────────────────────────────────────────────

function aid(): int {
    return intval($_GET["id"] ?? $_POST["id"] ?? 0);
}
/**
 * Parse a resolv.conf options string into a keyed array.
 * e.g. "ndots:5 timeout:2 rotate edns0"
 */
function dns_parse_options(string $str): array {
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

/** Opens an 820px dialog containing the DNS config form. */
function dns_js(): bool {
    $tpl  = new template_admin();
    $page = CurrentPageName();
    $id   = intval($_GET["dns-js"]);
    return $tpl->js_dialog3("{dns_settings}", "$page?id=$id", 820);
}

/** Renders the full DNS form pre-filled with current agent values. */
function dns_form(): bool {
    $tpl  = new template_admin();
    $id   = aid();
    $page = CurrentPageName();

    $json = json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/netagents/status/$id"));
    if (!is_object($json)) {
        echo $tpl->_ENGINE_parse_body($tpl->div_error("{error}"));
        return false;
    }
    if (isset($json->Status) && !$json->Status) {
        echo $tpl->_ENGINE_parse_body($tpl->div_error(htmlspecialchars($json->Error ?? "{error}")));
        return false;
    }

   if(!isset($json->dns)){
       echo $tpl->_ENGINE_parse_body($tpl->div_error(htmlspecialchars("{unable_to_retrieve_the_settings}<br>{check_agent_version_explain}")));
       return false;
   }

    $dns      = $json->dns ?? null;
    $ns       = is_object($dns) ? ($dns->nameservers ?? []) : [];
    $ns1      = htmlspecialchars($ns[0] ?? '');
    $ns2      = htmlspecialchars($ns[1] ?? '');
    $ns3      = htmlspecialchars($ns[2] ?? '');
    $search   = htmlspecialchars(is_object($dns) ? ($dns->search   ?? '') : '');
    $domain   = htmlspecialchars(is_object($dns) ? ($dns->domain   ?? '') : '');
    $sortlist = htmlspecialchars(is_object($dns) ? ($dns->sortlist ?? '') : '');
    $hostname = htmlspecialchars($json->hostname ?? '');
    $opts     = dns_parse_options(is_object($dns) ? ($dns->options ?? '') : '');

    $h   = [];
    $h[] = "<div id='dns-result-$id' style='margin-top:10px'></div>";
    $h[] = dns_hostname_box($id, $hostname);
    $h[] = dns_ns_box($id, $ns1, $ns2, $ns3);
    $h[] = dns_resolver_box($id, $search, $domain);
    $h[] = dns_options_box($id, $opts);
    $h[] = dns_sortlist_box($id, $sortlist);

    $h[] = "<div class='text-right' style='margin-bottom:20px'>";
    $h[] = "  <button class='btn btn-primary btn-lg' onclick='DnsSave_$id()'>";
    $h[] = "    <i class='fas fa-save'></i> {save}";
    $h[] = "  </button>";
    $h[] = "</div>";
    $h[] = dns_js_block($id, $page);

    echo $tpl->_ENGINE_parse_body(implode("\n", $h));
    return true;
}

/** POSTs DNS + optional hostname to the Go handler. Returns an HTML snippet. */
function dns_save(): void {
    $tpl = new template_admin();
    $id  = aid();

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
    $hn = trim($_POST["hostname"] ?? '');
    if ($hn !== '') $data["hostname"] = $hn;

    $json = json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API_POST_JSON(
        "/netagents/system/$id/network", $data
    ));
    if (!is_object($json)) {
        echo $tpl->_ENGINE_parse_body($tpl->div_error("{error}"));
        return;
    }
    // Agent-level error (networkConfigResponse.success = false)
    if (isset($json->success) && !$json->success) {
        echo $tpl->_ENGINE_parse_body($tpl->div_error(htmlspecialchars($json->error ?? "{error}")));
        return;
    }
    // Middleware-level error (outFalse → Status = false)
    if (isset($json->Status) && !$json->Status) {
        echo $tpl->_ENGINE_parse_body($tpl->div_error(htmlspecialchars($json->Error ?? "{error}")));
        return;
    }
    echo $tpl->_ENGINE_parse_body(
        $tpl->div_success("<i class='fas fa-check-circle'></i> {meta_wait_parameters}")
    );
    admin_tracks("Netagent #$id: DNS configuration updated");
}

// ── UI box builders ──────────────────────────────────────────────────────────

function dns_ns_box(int $id, string $ns1, string $ns2, string $ns3): string {
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
        $h[] = "      <input type='text' id='dns-$key-$id' class='form-control' value='$val' placeholder='x.x.x.x'>";
        $h[] = "    </div>";
    }
    $h[] = "  </div></div></div>";
    return implode("\n", $h);
}

function dns_resolver_box(int $id, string $search, string $domain): string {
    $h   = [];
    $h[] = "<div class='ibox'>";
    $h[] = "  <div class='ibox-title'><h5><i class='fas fa-search'></i>&nbsp; {resolver}</h5></div>";
    $h[] = "  <div class='ibox-content'><div class='row'>";
    $h[] = "    <div class='col-md-6'>";
    $h[] = "      <label>{search_domains}</label>";
    $h[] = "      <input type='text' id='dns-search-$id' class='form-control' value='$search' placeholder='example.com local.lan'>";
    $h[] = "      <p class='help-block'>{search_domains_explain}</p>";
    $h[] = "    </div>";
    $h[] = "    <div class='col-md-6'>";
    $h[] = "      <label>{domain}</label>";
    $h[] = "      <input type='text' id='dns-domain-$id' class='form-control' value='$domain' placeholder='example.com'>";
    $h[] = "      <p class='help-block'>{domain_explain}</p>";
    $h[] = "    </div>";
    $h[] = "  </div></div></div>";
    return implode("\n", $h);
}

function dns_options_box(int $id, array $opts): string {
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
        $h[] = "        <input type='number' id='dns-opt-$k-$id' class='form-control'"
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
        $h[] = "      <div class='col-md-4' style='margin-bottom:15px'>";
        $h[] = "        <div class='checkbox'><label>";
        $h[] = "          <input type='checkbox' id='dns-opt-$key-$id' $checked>";
        $h[] = "          <span style='font-family:monospace;color:#1ab394'>$label</span>";
        $h[] = "        </label></div>";
        $h[] = "        <p class='help-block' style='font-size:11px'>$explain</p>";
        $h[] = "      </div>";
    }
    $h[] = "    </div>";
    $h[] = "  </div></div>";
    return implode("\n", $h);
}

function dns_sortlist_box(int $id, string $sortlist): string {
    $h   = [];
    $h[] = "<div class='ibox'>";
    $h[] = "  <div class='ibox-title'><h5><i class='fas fa-sort-amount-down'></i>&nbsp; sortlist</h5></div>";
    $h[] = "  <div class='ibox-content'>";
    $h[] = "    <input type='text' id='dns-sortlist-$id' class='form-control'"
              . " value='$sortlist' placeholder='130.155.160.0/255.255.240.0 130.155.0.0'>";
    $h[] = "    <p class='help-block'>{sortlist_explain}</p>";
    $h[] = "  </div></div>";
    return implode("\n", $h);
}

function dns_hostname_box(int $id, string $hostname): string {
    $h   = [];
    $h[] = "<div class='ibox'>";
    $h[] = "  <div class='ibox-title'><h5><i class='fas fa-tag'></i>&nbsp; {hostname}</h5></div>";
    $h[] = "  <div class='ibox-content'>";
    $h[] = "    <input type='text' id='dns-hostname-$id' class='form-control'"
              . " value='$hostname' placeholder='{hostname}'>";
    $h[] = "    <p class='help-block'>{hostname_explain}</p>";
    $h[] = "  </div></div>";
    return implode("\n", $h);
}

/** Emits the DnsSave_{id}() JavaScript function. */
function dns_js_block(int $id, string $page): string {
    $l   = [];
    $l[] = "<script>";
    $l[] = "function DnsSave_$id(){";
    $l[] = "  var ns=[];";
    $l[] = "  ['ns1','ns2','ns3'].forEach(function(k){";
    $l[] = "    var v=\$.trim(\$('#dns-'+k+'-$id').val());";
    $l[] = "    if(v) ns.push(v);";
    $l[] = "  });";
    $l[] = "  if(ns.length===0){ alert('{at_least_one_nameserver}'); return; }";
    $l[] = "  var opts=[];";
    $l[] = "  ['ndots','timeout','attempts'].forEach(function(k){";
    $l[] = "    var v=\$.trim(\$('#dns-opt-'+k+'-$id').val());";
    $l[] = "    if(v&&!isNaN(v)&&parseInt(v,10)>0) opts.push(k+':'+v);";
    $l[] = "  });";
    $l[] = "  var bp=[['rotate','rotate'],['edns0','edns0'],['trust-ad','trust_ad'],";
    $l[] = "          ['use-vc','use_vc'],['no-check-names','no_check_names'],['inet6','inet6'],";
    $l[] = "          ['single-request','single_request'],['no-reload','no_reload']];";
    $l[] = "  bp.forEach(function(p){ if(\$('#dns-opt-'+p[1]+'-$id').is(':checked')) opts.push(p[0]); });";
    $l[] = "  \$('#dns-result-$id').html('<div class=\"alert alert-info\"><i class=\"fas fa-spinner fa-spin\"></i></div>');";
    $l[] = "  \$.post('$page',{";
    $l[] = "    save:1, id:$id,";
    $l[] = "    ns1:\$.trim(\$('#dns-ns1-$id').val()),";
    $l[] = "    ns2:\$.trim(\$('#dns-ns2-$id').val()),";
    $l[] = "    ns3:\$.trim(\$('#dns-ns3-$id').val()),";
    $l[] = "    search:\$.trim(\$('#dns-search-$id').val()),";
    $l[] = "    domain:\$.trim(\$('#dns-domain-$id').val()),";
    $l[] = "    hostname:\$.trim(\$('#dns-hostname-$id').val()),";
    $l[] = "    opts:opts.join(' '),";
    $l[] = "    sortlist:\$.trim(\$('#dns-sortlist-$id').val())";
    $l[] = "  },function(r){ \$('#dns-result-$id').html(r); });";
    $l[] = "}";
    $l[] = "</script>";
    return implode("\n", $l);
}
