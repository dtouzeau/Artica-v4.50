<?php
/**
 * Let's Encrypt DNS Providers Management
 *
 * This page provides a web interface for managing DNS provider configurations
 * stored in the DNSProviders table. Each provider configuration can be used
 * when requesting Let's Encrypt certificates.
 *
 * Features:
 * - List all configured DNS providers
 * - Create new provider configurations
 * - Edit existing provider configurations
 * - Delete provider configurations
 * - View certificates using each provider
 */
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
if(!isset($GLOBALS["CLASS_SOCKETS"])){
    if(!class_exists("sockets")){
        include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");
    }
    $GLOBALS["CLASS_SOCKETS"]=new sockets();
}

// Route requests
if(isset($_POST["SaveProvider"])){save_provider();exit;}
if(isset($_POST["UpdateProvider"])){update_provider();exit;}
if(isset($_GET["providers-list"])){providers_list();exit;}
if(isset($_GET["provider-form"])){provider_form();exit;}
if(isset($_GET["provider-form-js"])){provider_form_js();exit;}
if(isset($_GET["provider-edit"])){provider_edit_form();exit;}
if(isset($_GET["provider-edit-js"])){provider_edit_js();exit;}
if(isset($_GET["provider-certs"])){provider_certificates();exit;}
if(isset($_GET["provider-certs-js"])){provider_certificates_js();exit;}
if(isset($_GET["provider-delete-js"])){provider_delete_js();exit;}
if(isset($_POST["provider-delete"])){provider_delete_perform();exit;}

if(isset($_GET["popup"])){popup();exit;}
// Default: show main page
providers_js();

/**
 * Get available provider types from the articarest API
 */
function get_provider_types():array{
    $sock = new sockets();
    $response = $sock->REST_API("/letsencrypt/providers");
    $json = json_decode($response);
    if(json_last_error() !== JSON_ERROR_NONE || !isset($json->providers)){
        return array(
            "manual" => "Manual DNS",
            "cloudflare" => "Cloudflare",
            "route53" => "AWS Route53",
            "digitalocean" => "DigitalOcean",
            "ovh" => "OVH",
            "godaddy" => "GoDaddy",
            "namecheap" => "Namecheap",
            "gandi" => "Gandi",
            "hetzner" => "Hetzner",
            "linode" => "Linode",
            "vultr" => "Vultr",
            "gcloud" => "Google Cloud DNS",
            "azure" => "Azure DNS",
            "dnsimple" => "DNSimple",
            "ns1" => "NS1",
            "bookmyname" => "BookMyName",
            "facilemanager" => "FacileManager",
            "infomaniak" => "Infomaniak",
            "ionos" => "IONOS"
        );
    }

    $types = array();
    foreach($json->providers as $p){
        $types[$p->type] = $p->name;
    }

    return $types;
}

/**
 * Get provider fields based on provider type
 */
function get_provider_fields(string $providerType):array{
    $fields = array(
        "manual" => array(),
        "cloudflare" => array(
            "api_token" => array("label" => "API Token", "type" => "password", "required" => true),
            "zone_id" => array("label" => "Zone ID", "type" => "text", "required" => false)
        ),
        "route53" => array(
            "access_key_id" => array("label" => "Access Key ID", "type" => "text", "required" => true),
            "secret_access_key" => array("label" => "Secret Access Key", "type" => "password", "required" => true),
            "region" => array("label" => "Region", "type" => "text", "required" => false, "default" => "us-east-1"),
            "hosted_zone_id" => array("label" => "Hosted Zone ID", "type" => "text", "required" => false)
        ),
        "digitalocean" => array(
            "api_token" => array("label" => "API Token", "type" => "password", "required" => true)
        ),
        "ovh" => array(
            "application_key" => array("label" => "Application Key", "type" => "text", "required" => true),
            "application_secret" => array("label" => "Application Secret", "type" => "password", "required" => true),
            "consumer_key" => array("label" => "Consumer Key", "type" => "text", "required" => true),
            "endpoint" => array("label" => "Endpoint", "type" => "text", "required" => false, "default" => "ovh-eu")
        ),
        "godaddy" => array(
            "api_key" => array("label" => "API Key", "type" => "text", "required" => true),
            "api_secret" => array("label" => "API Secret", "type" => "password", "required" => true)
        ),
        "namecheap" => array(
            "api_user" => array("label" => "API User", "type" => "text", "required" => true),
            "api_key" => array("label" => "API Key", "type" => "password", "required" => true),
            "client_ip" => array("label" => "Client IP", "type" => "text", "required" => true)
        ),
        "gandi" => array(
            "api_key" => array("label" => "API Key", "type" => "password", "required" => true)
        ),
        "hetzner" => array(
            "api_token" => array("label" => "API Token", "type" => "password", "required" => true)
        ),
        "linode" => array(
            "api_token" => array("label" => "API Token", "type" => "password", "required" => true)
        ),
        "vultr" => array(
            "api_key" => array("label" => "API Key", "type" => "password", "required" => true)
        ),
        "gcloud" => array(
            "project" => array("label" => "Project ID", "type" => "text", "required" => true),
            "service_account_json" => array("label" => "Service Account JSON", "type" => "textarea", "required" => true)
        ),
        "azure" => array(
            "client_id" => array("label" => "Client ID", "type" => "text", "required" => true),
            "client_secret" => array("label" => "Client Secret", "type" => "password", "required" => true),
            "subscription_id" => array("label" => "Subscription ID", "type" => "text", "required" => true),
            "tenant_id" => array("label" => "Tenant ID", "type" => "text", "required" => true),
            "resource_group" => array("label" => "Resource Group", "type" => "text", "required" => true)
        ),
        "dnsimple" => array(
            "api_token" => array("label" => "API Token", "type" => "password", "required" => true)
        ),
        "ns1" => array(
            "api_key" => array("label" => "API Key", "type" => "password", "required" => true)
        ),
        "bookmyname" => array(
            "login" => array("label" => "Login", "type" => "text", "required" => true),
            "password" => array("label" => "Password", "type" => "password", "required" => true)
        ),
        "facilemanager" => array(
            "server_url" => array("label" => "Server URL", "type" => "text", "required" => true),
            "auth_key" => array("label" => "Auth Key", "type" => "text", "required" => true),
            "api_key" => array("label" => "API Key", "type" => "text", "required" => true),
            "api_secret" => array("label" => "API Secret", "type" => "password", "required" => true),
            "zone_id" => array("label" => "Zone ID", "type" => "text", "required" => true)
        ),
        "infomaniak" => array(
            "access_token" => array("label" => "Access Token", "type" => "password", "required" => true)
        ),
        "ionos" => array(
            "api_key" => array("label" => "API Key", "type" => "password", "required" => true)
        )
    );

    return isset($fields[$providerType]) ? $fields[$providerType] : array();
}

function providers_js():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $function=isset($_GET["function"]) ? $_GET["function"] : "";
    $function2=isset($_GET["function2"]) ? $_GET["function2"] : "";
    return $tpl->js_dialog3("{dns_providers}", "$page?popup=yes&function=$function&function2=$function2", 900, 600);
}

function popup():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $function=isset($_GET["function"]) ? $_GET["function"] : "";

    $html=array();
    $html[]="<div style='width:98%;margin-top:10px' id='dnsproviders-main'>";

    // Explanation
    $html[]=$tpl->div_explain("{dns_providers_explain}");

    // Add button and list
    $html[]="<div style='text-align:right;margin-bottom:10px;margin-top:10px'>";
    $html[]=$tpl->button_inline("{add_provider}", "Loadjs('$page?provider-form-js=yes');", ico_plus, null, 150);
    $html[]="</div>";

    $html[]="<div id='providers-list-container'>";
    $html[]="</div>";

    $html[]="<script>LoadAjax('providers-list-container', '$page?providers-list=yes');</script>";
    $html[]="</div>";

    echo $tpl->_ENGINE_parse_body($html);
    return true;
}

function providers_list():bool{
    $tpl = new template_admin();
    $page = CurrentPageName();

    $sock = new sockets();
    $response = $sock->REST_API("/letsencrypt/dnsproviders");

    $json = json_decode($response);
    if(json_last_error() !== JSON_ERROR_NONE || !is_object($json)){
        echo $tpl->div_error("{failed_to_fetch_providers}");
        return false;
    }

    $providers = isset($json->providers) ? $json->providers : array();

    if(count($providers) == 0){
        echo $tpl->div_warning("{no_providers_configured}");
        return true;
    }

    $providerTypes = get_provider_types();

    $html = array();
    $html[] = "<table class='table table-bordered table-striped'>";
    $html[] = "<thead><tr>";
    $html[] = "<th>{name}</th>";
    $html[] = "<th></th>";
    $html[] = "<th></th>";
    $html[] = "</tr></thead>";
    $html[] = "<tbody>";

    $td1="style='width:1%' nowrap";
    foreach($providers as $provider){
        $id = $provider->id;
        $providerName = isset($provider->provider_name) ? $provider->provider_name : "unknown";
        $providerLabel = isset($providerTypes[$providerName]) ? $providerTypes[$providerName] : $providerName;

        $md=md5($providerLabel.$id);
        $topbuttons = array();

        $topbuttons[] = array("Loadjs('$page?provider-certs-js=$id');", ico_certificate, "{certificates}");
        $delete=$tpl->icon_delete("Loadjs('$page?provider-delete-js=$id&md=$md');");
        $btsn=$tpl->th_buttons($topbuttons);
        $providerLabel=$tpl->td_href($providerLabel,"","Loadjs('$page?provider-edit-js=$id');");
        $html[] = "<tr id='$md'>";
        $html[] = "<td style='width:99%'><strong>#$id] $providerLabel ($providerName)</strong></td>";
        $html[] = "<td $td1>$btsn</td>";
        $html[] = "<td $td1>$delete</td>";
        $html[] = "</tr>";
    }

    $html[] = "</tbody></table>";

    // JavaScript for delete
    $html[] = "<script>
    function dns_provider_delete(id){
        if(!confirm('{confirm_delete_provider}')){
            return;
        }
        LoadAjax('providers-list-container', '$page?delete-provider=' + id);
    }
    </script>";

    echo $tpl->_ENGINE_parse_body($html);
    return true;
}

function provider_form_js():bool{
    $tpl = new template_admin();
    $page = CurrentPageName();
    return $tpl->js_dialog5("{add_provider}", "$page?provider-form=yes", 600, 500);
}

function provider_form():bool{
    $tpl = new template_admin();
    $page = CurrentPageName();

    $providerTypes = get_provider_types();

    $html = array();
    $html[] = "<div id='provider-form-container'>";

    // Provider type selection
    $html[] = "<table class='table'>";
    $html[] = "<tr>";
    $html[] = "<td style='width:150px'><strong>{provider_type}</strong></td>";
    $html[] = "<td>";
    $html[] = "<select id='ProviderType' class='form-control' onchange='provider_type_changed();'>";
    foreach($providerTypes as $key => $label){
        $html[] = "<option value='$key'>$label</option>";
    }
    $html[] = "</select>";
    $html[] = "</td>";
    $html[] = "</tr>";
    $html[] = "</table>";

    // Dynamic fields container
    $html[] = "<div id='provider-fields'></div>";

    // Buttons
    $html[] = "<div style='text-align:right;margin-top:20px'>";
    $html[] = $tpl->button_inline("{save}", "provider_save();", ico_save, null, 100);
    $html[] = "</div>";

    $html[] = "</div>";

    // Build fields JSON for JavaScript
    $allFields = array();
    foreach(array_keys($providerTypes) as $type){
        $allFields[$type] = get_provider_fields($type);
    }
    $fieldsJson = json_encode($allFields);

    // JavaScript
    $html[] = "<script>
    var providerFields = $fieldsJson;

    function provider_type_changed(){
        var type = document.getElementById('ProviderType').value;
        var fields = providerFields[type] || {};
        var html = '<table class=\"table\">';

        for(var key in fields){
            var field = fields[key];
            var required = field.required ? ' <span style=\"color:red\">*</span>' : '';
            var inputType = field.type || 'text';
            var defaultVal = field.default || '';

            html += '<tr>';
            html += '<td style=\"width:150px\"><strong>' + field.label + required + '</strong></td>';
            html += '<td>';

            if(inputType == 'textarea'){
                html += '<textarea id=\"field_' + key + '\" class=\"form-control\" rows=\"4\">' + defaultVal + '</textarea>';
            } else {
                html += '<input type=\"' + inputType + '\" id=\"field_' + key + '\" class=\"form-control\" value=\"' + defaultVal + '\">';
            }

            html += '</td>';
            html += '</tr>';
        }

        html += '</table>';
        document.getElementById('provider-fields').innerHTML = html;
    }

    function provider_save(){
        var type = $('#ProviderType').val();
        var fields = providerFields[type] || {};
        var params = {};

        for(var key in fields){
            var elem = $('#field_' + key);
            if(elem.length){
                params[key] = elem.val();
            }
        }

        $.post('$page', {
            SaveProvider: 1,
            ProviderName: type,
            Params: JSON.stringify(params)
        }, function(data){
            if(data.success){
                alert('OK: ' + (data.message || 'Provider saved successfully'));
                dialogInstance5.close();
                LoadAjaxSilent('providers-list-container', '$page?providers-list=yes');
            } else {
                alert('Error: ' + (data.error || 'Failed to save provider'));
            }
        }, 'json').fail(function(xhr, status, error){
            alert('Error: ' + error);
            console.log('Response:', xhr.responseText);
        });
    }

    // Initialize
    provider_type_changed();
    </script>";

    echo $tpl->_ENGINE_parse_body($html);
    return true;
}

function save_provider(){
    // Clear any buffered output
    while(ob_get_level() > 0){
        ob_end_clean();
    }

    header('Content-Type: application/json');

    $providerName = isset($_POST["ProviderName"]) ? $_POST["ProviderName"] : "";
    $paramsJson = isset($_POST["Params"]) ? $_POST["Params"] : "{}";

    if(empty($providerName)){
        echo json_encode(array("success" => false, "error" => "Provider name is required"));
        return;
    }

    $params = json_decode($paramsJson, true);
    if(json_last_error() !== JSON_ERROR_NONE){
        $params = array();
    }

    // Cast to object to ensure JSON encoding produces {} not [] for empty params
    $payload = array(
        "provider_name" => $providerName,
        "params" => (object)$params
    );

    $response = $GLOBALS["CLASS_SOCKETS"]->REST_API_POST_JSON("/letsencrypt/dnsproviders", $payload);

    // Parse and re-encode to ensure clean JSON output
    $json = json_decode($response, true);
    if(json_last_error() === JSON_ERROR_NONE){
        echo json_encode($json);
    } else {
        // If API response is not valid JSON, return error
        echo json_encode(array("success" => false, "error" => "Invalid API response: " . substr($response, 0, 200)));
    }
}

function provider_edit_js():bool{
    $tpl = new template_admin();
    $page = CurrentPageName();
    $id = intval($_GET["provider-edit-js"]);
    return $tpl->js_dialog5("{edit_provider}", "$page?provider-edit=$id", 600, 500);
}
function provider_delete_js():bool{
    $tpl = new template_admin();
    $id=intval($_GET["provider-delete-js"]);
    $md=$_GET["md"];
    $sock = new sockets();
    $response = $sock->REST_API("/letsencrypt/dnsproviders/$id");
    $json = json_decode($response);
    if(json_last_error() !== JSON_ERROR_NONE || !is_object($json) || !isset($json->id)){
        return  $tpl->js_error("{provider_not_found}");

    }

    $providerName = isset($json->provider_name) ? $json->provider_name : "unknown";

    return $tpl->js_confirm_delete($providerName,"provider-delete",$id,"$('#$md').remove();");
}

function provider_edit_form():bool{
    $tpl = new template_admin();
    $page = CurrentPageName();
    $id = intval($_GET["provider-edit"]);

    // Get current provider data
    $sock = new sockets();
    $response = $sock->REST_API("/letsencrypt/dnsproviders/$id");
    $json = json_decode($response);

    if(json_last_error() !== JSON_ERROR_NONE || !is_object($json) || !isset($json->id)){
        echo $tpl->div_error("{provider_not_found}");
        return false;
    }

    $providerName = isset($json->provider_name) ? $json->provider_name : "unknown";
    $currentParams = isset($json->params) ? (array)$json->params : array();

    $providerTypes = get_provider_types();
    $providerLabel = isset($providerTypes[$providerName]) ? $providerTypes[$providerName] : $providerName;
    $fields = get_provider_fields($providerName);

    $html = array();
    $html[] = "<div id='provider-edit-container'>";

    $html[] = "<table class='table'>";
    $html[] = "<tr>";
    $html[] = "<td style='width:150px'><strong>{provider_type}</strong></td>";
    $html[] = "<td>$providerLabel <code>($providerName)</code></td>";
    $html[] = "</tr>";

    foreach($fields as $key => $field){
        $required = $field["required"] ? " <span style='color:red'>*</span>" : "";
        $inputType = isset($field["type"]) ? $field["type"] : "text";
        $defaultVal = isset($field["default"]) ? $field["default"] : "";
        $currentValue = isset($currentParams[$key]) ? $currentParams[$key] : $defaultVal;

        $html[] = "<tr>";
        $html[] = "<td><strong>{$field['label']}$required</strong></td>";
        $html[] = "<td>";

        if($inputType == "textarea"){
            $html[] = "<textarea id='field_$key' class='form-control' rows='4'>".htmlspecialchars($currentValue)."</textarea>";
        } else if($inputType == "password"){
            // For password fields, show masked value
            $html[] = "<input type='password' id='field_$key' class='form-control' value='".htmlspecialchars($currentValue)."' placeholder='".($currentValue ? "********" : "")."'>";
        } else {
            $html[] = "<input type='text' id='field_$key' class='form-control' value='".htmlspecialchars($currentValue)."'>";
        }

        $html[] = "</td>";
        $html[] = "</tr>";
    }

    $html[] = "</table>";

    // Buttons
    $html[] = "<div style='text-align:right;margin-top:20px'>";
    $html[] = $tpl->button_inline("{save}", "provider_update($id);", ico_save, null, 100);
    $html[] = "</div>";

    $html[] = "</div>";

    // Build fields array for JavaScript
    $fieldsJson = json_encode(array_keys($fields));

    // JavaScript
    $html[] = "<script>
    function provider_update(id){
        var fieldKeys = $fieldsJson;
        var params = {};

        for(var i = 0; i < fieldKeys.length; i++){
            var key = fieldKeys[i];
            var elem = $('#field_' + key);
            if(elem.length){
                params[key] = elem.val();
            }
        }

        $.post('$page', {
            UpdateProvider: 1,
            ProviderId: id,
            ProviderName: '$providerName',
            Params: JSON.stringify(params)
        }, function(data){
            if(data.success){
                dialogInstance5.close();
                LoadAjax('providers-list-container', '$page?providers-list=yes');
            } else {
                alert('Error: ' + (data.error || 'Failed to update provider'));
            }
        }, 'json').fail(function(xhr, status, error){
            alert('Error: ' + error);
            console.log('Response:', xhr.responseText);
        });
    }
    </script>";

    echo $tpl->_ENGINE_parse_body($html);
    return true;
}

function update_provider(){
    // Clear any buffered output
    while(ob_get_level() > 0){
        ob_end_clean();
    }

    header('Content-Type: application/json');

    $id = isset($_POST["ProviderId"]) ? intval($_POST["ProviderId"]) : 0;
    $providerName = isset($_POST["ProviderName"]) ? $_POST["ProviderName"] : "";
    $paramsJson = isset($_POST["Params"]) ? $_POST["Params"] : "{}";

    if($id <= 0){
        echo json_encode(array("success" => false, "error" => "Invalid provider ID"));
        return;
    }

    if(empty($providerName)){
        echo json_encode(array("success" => false, "error" => "Provider name is required"));
        return;
    }

    $params = json_decode($paramsJson, true);
    if(json_last_error() !== JSON_ERROR_NONE){
        $params = array();
    }

    // Cast to object to ensure JSON encoding produces {} not [] for empty params
    $payload = array(
        "provider_name" => $providerName,
        "params" => (object)$params
    );

    $response = $GLOBALS["CLASS_SOCKETS"]->REST_API_PUT_JSON("/letsencrypt/dnsproviders/$id", $payload);

    // Parse and re-encode to ensure clean JSON output
    $json = json_decode($response, true);
    if(json_last_error() === JSON_ERROR_NONE){
        echo json_encode($json);
    } else {
        echo json_encode(array("success" => false, "error" => "Invalid API response: " . substr($response, 0, 200)));
    }
}

function provider_delete_perform():bool{
    $tpl = new template_admin();
    $page = CurrentPageName();
    $id = intval($_POST["provider-delete"]);

    if($id <= 0){
        echo $tpl->_ENGINE_parse_body("{invalid_provider_id}");
        return false;
    }

    $sock = new sockets();
    $response = $sock->REST_API_DELETE("/letsencrypt/dnsproviders/$id");

    $json = json_decode($response);
    if(json_last_error() !== JSON_ERROR_NONE){
        echo $tpl->_ENGINE_parse_body("{error_deleting_provider}");
        return false;
    }

    if(is_object($json) && isset($json->success) && $json->success){
        return admin_tracks("Removed Let's Encrypt DNS provider #$id");
    }
    $error = (is_object($json) && isset($json->error)) ? $json->error : "{error_deleting_provider}";
    echo $tpl->_ENGINE_parse_body($error);
    return false;
}

function provider_certificates_js():bool{
    $tpl = new template_admin();
    $page = CurrentPageName();
    $id = intval($_GET["provider-certs-js"]);
    return $tpl->js_dialog6("{certificates}", "$page?provider-certs=$id", 700, 400);
}

function provider_certificates():bool{
    $tpl = new template_admin();
    $page = CurrentPageName();
    $id = intval($_GET["provider-certs"]);

    $sock = new sockets();
    $response = $sock->REST_API("/letsencrypt/certificates/provider/$id");

    $json = json_decode($response);
    if(json_last_error() !== JSON_ERROR_NONE || !is_object($json)){
        echo $tpl->div_error("{failed_to_fetch_certificates}");
        return false;
    }

    $certs = isset($json->certificates) ? $json->certificates : array();

    if(count($certs) == 0){
        echo $tpl->div_info("{no_certificates_for_provider}");
        return true;
    }

    $html = array();
    $html[] = "<table class='table table-bordered table-striped'>";
    $html[] = "<thead><tr>";
    $html[] = "<th>{domain}</th>";
    $html[] = "<th>{email}</th>";
    $html[] = "<th>{expiry_date}</th>";
    $html[] = "<th>{days_left}</th>";
    $html[] = "</tr></thead>";
    $html[] = "<tbody>";

    foreach($certs as $cert){
        $daysLeft = isset($cert->days_left) ? $cert->days_left : 0;
        $daysClass = "label-success";
        if($daysLeft < 7){
            $daysClass = "label-danger";
        } else if($daysLeft < 14){
            $daysClass = "label-warning";
        } else if($daysLeft < 30){
            $daysClass = "label-info";
        }

        $commonName = isset($cert->common_name) ? $cert->common_name : "";
        $email = isset($cert->email) ? $cert->email : "";
        $expiryDate = isset($cert->expiry_date) ? $cert->expiry_date : "";

        $html[] = "<tr>";
        $html[] = "<td>".htmlspecialchars($commonName)."</td>";
        $html[] = "<td>".htmlspecialchars($email)."</td>";
        $html[] = "<td>".htmlspecialchars($expiryDate)."</td>";
        $html[] = "<td><span class='label $daysClass'>$daysLeft {days}</span></td>";
        $html[] = "</tr>";
    }

    $html[] = "</tbody></table>";

    echo $tpl->_ENGINE_parse_body($html);
    return true;
}
