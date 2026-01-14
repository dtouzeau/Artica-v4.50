<?php
/**
 * Let's Encrypt DNS-01 Challenge Certificate Management
 *
 * This page provides a web interface for obtaining Let's Encrypt certificates
 * using DNS TXT record validation (DNS-01 challenge method).
 *
 * Workflow:
 * 1. User enters domain and email
 * 2. System creates a task and returns DNS TXT record to create
 * 3. User creates the TXT record in their DNS provider
 * 4. User confirms DNS propagation
 * 5. System verifies and issues certificate
 * 6. Certificate is saved to database automatically
 */
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
if(!isset($GLOBALS["CLASS_SOCKETS"])){
    if(!class_exists("sockets")){
        include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");
    }
    $GLOBALS["CLASS_SOCKETS"]=new sockets();
}

if(isset($_GET["providers"])){providers_start();exit;}
if(isset($_POST["CreateDNSRequest"])){dns_create_request();exit;}
if(isset($_GET["GetDNSRequest"])){dns_create_request_saved();exit;}
if(isset($_GET["confirm-dns"])){dns_confirm();exit;}
if(isset($_GET["verify-task-id"])){verify_task_id();exit;}
if(isset($_GET["RemoveTask"])){dns_delete_task_js();exit;}
if(isset($_GET["CancelTask1"])){dns_cancel_task1_js();exit;}
if(isset($_GET["CancelTask"])){dns_cancel_task_js();exit;}
if(isset($_POST["CancelTask"])){dns_cancel_task_perform();exit;}
if(isset($_POST["RemoveTask"])){dns_delete_task_perform();exit;}
if(isset($_GET["task-status"])){dns_task_status();exit;}
if(isset($_GET["tasks-table"])){dns_tasks_table();exit;}
if(isset($_GET["download-cert"])){dns_download_cert();exit;}
if(isset($_GET["stats"])){dns_stats();exit;}
if(isset($_GET["popup"])){popup();exit;}
if(isset($_GET["task-details-js"])){task_details_js();exit;}
if(isset($_GET["task-details-popup"])){task_details_popup();exit;}
if(isset($_GET["new-request-form"])){echo dns_create_request_form();exit;}
if(isset($_GET["new-request-start"])){dns_create_request_start();exit;}
// DNS Provider management
if(isset($_GET["provider-config"])){provider_config_tab();exit;}
if(isset($_POST["SaveProviderConfig"])){save_provider_config();exit;}
if(isset($_GET["test-provider"])){test_provider();exit;}
// Auto-renewal management
if(isset($_GET["autorenew-config"])){autorenew_config_tab();exit;}
if(isset($_POST["EnableAutoRenewal"])){enable_autorenew();exit;}
if(isset($_GET["disable-autorenew"])){disable_autorenew();exit;}
if(isset($_GET["expiring-certs"])){expiring_certs_tab();exit;}
if(isset($_GET["renewal-results"])){renewal_results_tab();exit;}
if(isset($_GET["trigger-renewal"])){trigger_renewal();exit;}
if(isset($_GET["tabs"])){tabs();exit;}
// Default: show main page
letsencrypt_dns_js();

function letsencrypt_dns_js(){
    $tpl=new template_admin();
    $page=CurrentPageName();
    $function=isset($_GET["function"]) ? $_GET["function"] : "";
    $function2=isset($_GET["function2"]) ? $_GET["function2"] : "";
    return $tpl->js_dialog3("{LETSENCRYPT_DNS_CERTIFICATE}", "$page?tabs=yes&function=$function&function2=$function2", 900, 700);
}
function tabs(){
    $tpl=new template_admin();
    $page=CurrentPageName();
    $function=isset($_GET["function"]) ? $_GET["function"] : "";
    $tabs["{requests}"]="$page?popup=yes&function=$function";
    $tabs["{dns_providers}"]="fw.certificates-center.letsencrypt.providers.php?popup=yes&function=$function";
    $tabs["{auto_renewal}"]="$page?autorenew-config=yes&function=$function";
    $html[]="<div style='margin-top:10px'>";
    $html[]=$tpl->tabs_default($tabs);
    $html[]="</div>";
    echo $tpl->_ENGINE_parse_body($html);
}
function providers_start(){
    $tpl=new template_admin();
    $page=CurrentPageName();
    $function=isset($_GET["function"]) ? $_GET["function"] : "";
    echo $tpl->search_block($page,"","","","&providers-search=yes&mainfunc=$function");
    return true;
}

function popup(){
    $tpl=new template_admin();
    $page=CurrentPageName();
    $function=isset($_GET["function"]) ? $_GET["function"] : "";

    $html=array();
    $html[]="<div style='margin-top:10px'>";
    $html[]=$tpl->div_explain("{LETSENCRYPT_DNS_CERTIFICATE}||{howto_letsencrypt_dns}");
    $html[]="</div>";
    $tabs["{new_request}"]="$page?new-request-start=yes&function=$function";
    $tabs["{pending_tasks}"]="$page?tasks-table=yes&function=$function";
    $tabs["{expiring_certificates}"]="$page?expiring-certs=yes&function=$function";
    //$tabs["{statistics}"]="$page?stats=yes&function=$function";
    $html[]=$tpl->tabs_default($tabs);
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}
function dns_create_request_start():string{
    $page=CurrentPageName();
    echo "<div id='dns-create-request-start'></div>\n";
    echo "<script>LoadAjaxSilent('dns-create-request-start','$page?new-request-form=yes');</script>";
    return true;
}
function dns_providers_list():array
{
    $providersResponse=$GLOBALS["CLASS_SOCKETS"]->REST_API("/letsencrypt/dnsproviders");
    $providersJson=json_decode($providersResponse);
    $providersList=array();
    $providersList["0"]="{create_txt_record_manually}";
    if(is_object($providersJson) && isset($providersJson->providers) && is_array($providersJson->providers)){
        foreach($providersJson->providers as $p){
            $pid=isset($p->id) ? $p->id : 0;
            $pname=isset($p->provider_name) ? $p->provider_name : "Unknown";
            if($pid > 0){
                $providersList["$pid"]=$pname;
            }
        }
    }
    return $providersList;
}

function dns_create_request_form():string{
    $tpl=new template_admin();
    $page=CurrentPageName();

    $form=array();
    $form[]=$tpl->field_hidden("CreateDNSRequest",1);
    $form[]=$tpl->field_text("Domain","{domain}","",true,"e.g., example.com");
    $form[]=$tpl->field_email("Email","{email}","",true,"admin@example.com");

    $dns_providers_list=dns_providers_list();
    if(count($dns_providers_list)==0){
        echo $tpl->div_error("{no_provider_explain_error}");
        return false;
    }

    $form[]=$tpl->field_array_hash($dns_providers_list,"DNS_PROVIDER","{dns_provider}",0);
    $form[]=$tpl->field_checkbox("UseStaging","{test_mode}",0,false,"{letsencrypt_staging_explain}");

    // Key type selection
    $keyTypes=array(
        "RSA2048"=>"RSA 2048-bit ({recommended})",
        "RSA4096"=>"RSA 4096-bit",
        "EC256"=>"ECDSA P-256",
        "EC384"=>"ECDSA P-384"
    );
    $form[]=$tpl->field_array_select($keyTypes,"KeyType","{key_type}","RSA2048");
    $html=array();
    $html[]="<div id='dns-request-form-div'>";
    $html[]=$tpl->form_outside(null, $form, null, "{create_request}",
        "LoadAjaxSilent('dns-request-form-div','$page?GetDNSRequest=yes')",
        "AsCertifsManager");
    $html[]="</div>";

    // JavaScript for form submission
    $html[]="<script>
    function dns_show_new_form(){
        document.getElementById('dns-request-form').style.display = 'block';
    }

    function dns_confirm_propagation(taskId){
        LoadAjax('dns-task-status-' + taskId, '$page?confirm-dns=' + taskId);
    }

    function dns_cancel_task(taskId){
        if(!confirm('Cancel this certificate request?')){
            return;
        }
        LoadAjax('dns-task-status-' + taskId, '$page?cancel-task=' + taskId);
    }

    function dns_refresh_tasks(){
        LoadAjax('dns-tasks-container', '$page?tasks-table=yes');
    }
    </script>";

    return $tpl->_ENGINE_parse_body($html);
}
function dns_create_request_saved():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();

    if(!isset( $_SESSION["NEW_DNS_TASK"])){
        echo $tpl->div_error("No session saved");
        return false;
    }
    $task = $_SESSION["NEW_DNS_TASK"];
    $taskId = $task->id;

    $html = array();
    $html[] = "<div class='panel panel-info' style='margin-top:10px'>";
    $html[] = "<div class='panel-heading'><h4>{dns_record_instructions}</h4></div>";
    $html[] = "<div class='panel-body'>";

    $html[] = "<p><strong>{step} 1:</strong> {create_txt_record_in_dns}</p>";
    $html[] = "<table class='table table-bordered'>";
    $html[] = "<tr><th>{record_type}</th><td><code>TXT</code></td></tr>";
    $html[] = "<tr><th>{record_name}</th><td><code>$task->txt_record_name</code></td></tr>";
    $html[] = "<tr><th>{record_value}</th><td><code style='word-break:break-all;'>$task->txt_record_value</code></td></tr>";
    $html[] = "<tr><th>TTL</th><td><code>300</code> ({or_default})</td></tr>";
    $html[] = "</table>";

    $html[] = "<p><strong>{step} 2:</strong> {wait_dns_propagation}</p>";
    $html[] = "<p class='text-muted'>{dns_propagation_time}</p>";

    $html[] = "<p><strong>{step} 3:</strong> {verify_dns_record} <span id='vvf-$taskId'></span></p>";
    $html[] = "<pre>dig +short TXT $task->txt_record_name</pre>";

    $html[] = "<p><strong>{step} 4:</strong> {confirm_and_get_certificate}</p>";
    $html[] = "</div>";
    $html[] = "</div>";

    // Task status area
    $html[] = "<div id='dns-task-status-$taskId'>";
    $html[] = dns_task_status_html($taskId, $task);
    $html[] = "</div>";

    // Buttons

    $topbuttons[] = array("Loadjs('$page?confirm-dns=$taskId')", ico_ok, "{confirm_dns_created}");
    $topbuttons[] = array("Loadjs('$page?CancelTask1=$taskId')", ico_trash, "{cancel}");
    $topbuttons[] = array("LoadAjaxSilent('dns-create-request-start','$page?new-request-form=yes');", ico_plus, "{new_request}");


    $html[] = "<div style='margin-top:15px;'>";
    $html[] = $tpl->table_buttons($topbuttons);
    $html[] = "</div>";
    $js=$tpl->RefreshInterval_js("vvf-$taskId",$page,"verify-task-id=$taskId");
    $html[] = "<script>$js</script>";
    //

    echo $tpl->_ENGINE_parse_body($html);
    return true;
}
function verify_task_id():bool{
    $taskId=$_GET["verify-task-id"];
    $tpl=new template_admin();
    $response = $GLOBALS["CLASS_SOCKETS"]->REST_API("/letsencrypt/dns/task/$taskId/verify");

    $json = json_decode($response);
    if(json_last_error() !== JSON_ERROR_NONE){
        echo $tpl->_ENGINE_parse_body("<span class='label label-danger'>{error}</span><br><small>".json_last_error()."</small>");
        return false;
    }

    if(property_exists($json, "Status")){
        if(!$json->Status){
            echo "<span class='label label-danger'>$json->Error</span>";
            return false;
        }
    }
   if(property_exists($json, "verified")){
       if(!$json->verified){
           echo $tpl->_ENGINE_parse_body("<span class='label label-warning'>{unverified}</span><br><small><i>$json->message</i></small>");
           return false;
       }
       echo $tpl->_ENGINE_parse_body("<span class='label label-primary'>{success}!</span><br><small><i>$json->message</i></small>");

   }

    echo "<span class='label label-danger'>{unkown} {error}</span>";
    return false;
}

function dns_create_request(){
    $tpl=new template_admin();
    $page=CurrentPageName();
    $tpl->CLEAN_POST();
    $domain = isset($_POST["Domain"]) ? trim($_POST["Domain"]) : "";
    $email = isset($_POST["Email"]) ? trim($_POST["Email"]) : "";
    $useStaging = isset($_POST["UseStaging"]) ? intval($_POST["UseStaging"]) : 0;
    $keyType = isset($_POST["KeyType"]) ? $_POST["KeyType"] : "RSA2048";

    if(strlen($domain) < 3){
        echo $tpl->post_error($tpl->_ENGINE_parse_body("{invalid_domain_name}"));
        return false;
    }

    if(strlen($email) < 5 || strpos($email, "@") === false){
        echo $tpl->post_error($tpl->_ENGINE_parse_body("{error_email_invalid}"));
        return false;
    }
    $dns_provider_id=isset($_POST["DNS_PROVIDER"]) ? intval($_POST["DNS_PROVIDER"]) : 0;

    // Call the REST API to create DNS request
    $payload = array(
        "domain" => $domain,
        "email" => $email,
        "use_staging" => $useStaging == 1,
        "key_type" => $keyType,
        "dns_provider_id"=>$dns_provider_id
    );
    
    $response = $GLOBALS["CLASS_SOCKETS"]->REST_API_POST_JSON("/letsencrypt/dns/request", $payload);

    $json = json_decode($response);
    if(json_last_error() !== JSON_ERROR_NONE){
        echo $tpl->post_error("{error_api_response}: " . json_last_error_msg());
        return false;
    }

    if(isset($json->error)){
        echo $tpl->post_error($json->error);
        return false;
    }

    if(!$json->success){
        echo $tpl->post_error("{request_failed}");
        return false;
    }
    $_SESSION["NEW_DNS_TASK"]=$json->task;

  return admin_tracks("Create a new Let's Encrypt DNS-01 task for $domain");
}

function dns_task_status_html($taskId, $task):string{
    $tpl = new template_admin();

    $statusLabels = array(
        "pending" => "<span class='label label-warning'>{pending}</span>",
        "verifying" => "<span class='label label-info'>{verifying}</span>",
        "processing" => "<span class='label label-primary'>{processing}</span>",
        "completed" => "<span class='label label-success'>{completed}</span>",
        "failed" => "<span class='label label-danger'>{failed}</span>",
        "expired" => "<span class='label label-default'>{expired}</span>",
        "cancelled" => "<span class='label label-default'>{cancelled}</span>"
    );

    $status = isset($task->status) ? $task->status : "pending";
    $statusLabel = isset($statusLabels[$status]) ? $statusLabels[$status] : "<span class='label label-default'>$status</span>";

    $html = array();
    $html[] = "<div class='well'>";
    $html[] = "<strong>Task ID:</strong> {$taskId}<br>";
    $html[] = "<strong>{domain}:</strong> {$task->domain}<br>";
    $html[] = "<strong>{status}:</strong> $statusLabel<br>";

    if(isset($task->error) && strlen($task->error) > 0){
        $html[] = "<div class='alert alert-danger' style='margin-top:10px;'>{$task->error}</div>";
    }

    if($status == "completed" && isset($task->has_certificate) && $task->has_certificate){
        $page = CurrentPageName();
        $html[] = "<div class='alert alert-success' style='margin-top:10px;'>";
        $html[] = "{certificate_ready}<br>";
        $html[] = "<a href='$page?download-cert=$taskId' class='btn btn-success btn-sm' target='_blank'>{download_certificate}</a>";
        $html[] = "</div>";
    }

    $html[] = "</div>";

    return $tpl->_ENGINE_parse_body(implode("\n", $html));
}

function dns_confirm():bool{
    $tpl = new template_admin();
    $taskId = $_GET["confirm-dns"];

    $response = $GLOBALS["CLASS_SOCKETS"]->REST_API_POST_JSON("/letsencrypt/dns/task/$taskId/confirm", array());

    $json = json_decode($response);
    if(json_last_error() !== JSON_ERROR_NONE){
        $tpl->js_error("Failed to parse API response");
        return false;
    }

    if(isset($json->error)){
        echo $tpl->js_error($json->error);
        return false;
    }

    $html = array();
    $html[] = $tpl->div_success("{dns_confirmation_sent}");
    $html[] = "<p>{certificate_being_issued}</p>";
    $html[] = "<p><em>{please_wait_certificate}</em></p>";

    // Show task status
    if(isset($json->task)){
        $html[] = dns_task_status_html($taskId, $json->task);
    }

    // Auto-refresh script
    $page = CurrentPageName();
    $html[] = "<script>
    setTimeout(function(){
        LoadAjax('dns-task-status-$taskId', '$page?task-status=$taskId');
    }, 5000);
    </script>";

    echo $tpl->_ENGINE_parse_body($html);
    return true;
}

function dns_task_status():bool{
    $tpl = new template_admin();
    $page = CurrentPageName();
    $taskId = $_GET["task-status"];

    $sock = new sockets();
    $response = $sock->REST_API("/letsencrypt/dns/task/{$taskId}");

    $json = json_decode($response);
    if(json_last_error() !== JSON_ERROR_NONE || isset($json->error)){
        echo $tpl->div_error("Failed to get task status");
        return false;
    }



    $task = $json->task;
    echo dns_task_status_html($taskId, $task);

    // Continue polling if still processing
    $status = isset($task->status) ? $task->status : "";
    if($status == "verifying" || $status == "processing"){
        echo "<script>
        setTimeout(function(){
            LoadAjax('dns-task-status-{$taskId}', '$page?task-status=$taskId');
        }, 5000);
        </script>";
    }

    return true;
}
function dns_cancel_task_js():bool{
    $tpl = new template_admin();
    $taskid=$_GET["CancelTask"];
    $md=$_GET["md"];
    return $tpl->js_confirm_delete("{cancel} $taskid","CancelTask",$taskid,"$('#$md').remove();");
}
function dns_delete_task_js():bool{
    $tpl = new template_admin();
    $taskid=$_GET["RemoveTask"];
    $md=$_GET["md"];
    return $tpl->js_confirm_delete("$taskid","RemoveTask",$taskid,"$('#$md').remove();");
}
function dns_cancel_task1_js():bool{
    $tpl = new template_admin();
    $page=CurrentPageName();
    $taskId=$_GET["CancelTask1"];
    $response = $GLOBALS["CLASS_SOCKETS"]->REST_API_POST_JSON("/letsencrypt/dns/task/$taskId/cancel", array());

    $json = json_decode($response);
    if(isset($json->error)){
        return $tpl->js_error($json->error);
    }
    echo "LoadAjaxSilent('dns-create-request-start','$page?new-request-form=yes');";
    return admin_tracks("Let's Encrypt DNS-01 cancel task $taskId");
}

function dns_cancel_task_perform():bool{

    $taskId = $_POST["CancelTask"];
    $response = $GLOBALS["CLASS_SOCKETS"]->REST_API_POST_JSON("/letsencrypt/dns/task/$taskId/cancel", array());

    $json = json_decode($response);
    if(isset($json->error)){
        echo $json->error;
        return false;
    }
    return admin_tracks("Let's Encrypt DNS-01 cancel task $taskId");
}

function dns_delete_task_perform():bool{
    $taskId = $_POST["RemoveTask"];
    $response = $GLOBALS["CLASS_SOCKETS"]->REST_API_DELETE("/letsencrypt/dns/task/{$taskId}");

    $json = json_decode($response);
    if(isset($json->error)){
        echo $json->error;
        return false;
    }


    return admin_tracks("Lest's Encrypt DNS-01 Remove task $taskId");
}

function dns_tasks_table():bool{
    $tpl = new template_admin();
    $page = CurrentPageName();

    $sock = new sockets();
    $response = $sock->REST_API("/letsencrypt/dns/tasks");

    $json = json_decode($response);
    if(json_last_error() !== JSON_ERROR_NONE){
        echo $tpl->div_error("Failed to fetch tasks");
        return false;
    }

    $html = array();
    $html[] = "<div id='dns-tasks-container'>";

    // Refresh button
    $html[] = "<div style='text-align:right;margin-bottom:10px;margin-top:10px'>";
    $html[] = $tpl->button_inline("{refresh}", "dns_refresh_tasks();", ico_retweet, null, 120);
    $html[] = "</div>";

    $tasks = isset($json->tasks) ? $json->tasks : array();

    if(count($tasks) == 0){
        $html[] = $tpl->div_info("{no_pending_tasks}");
        echo $tpl->_ENGINE_parse_body($html);
        return true;
    }

    $statusLabels = array(
        "pending" => "<span class='label label-warning'>{pending}</span>",
        "verifying" => "<span class='label label-info'>{verifying}</span>",
        "processing" => "<span class='label label-primary'>{processing}</span>",
        "completed" => "<span class='label label-success'>{completed}</span>",
        "failed" => "<span class='label label-danger'>{failed}</span>",
        "expired" => "<span class='label label-default'>{expired}</span>",
        "cancelled" => "<span class='label label-default'>{cancelled}</span>"
    );

    $html[] = "<table class='table table-bordered table-striped'>";
    $html[] = "<thead><tr>";
    $html[] = "<th>ID</th>";
    $html[] = "<th>{domain}</th>";
    $html[] = "<th>{status}</th>";
    $html[] = "<th>{created}</th>";
    $html[] = "<th>{actions}</th>";
    $html[] = "</tr></thead>";
    $html[] = "<tbody>";

    foreach($tasks as $task){
        $taskId = $task->id;
        $status = isset($task->status) ? $task->status : "pending";
        $statusLabel = isset($statusLabels[$status]) ? $statusLabels[$status] : "<span class='label label-default'>$status</span>";
        $createdAt = isset($task->created_at) ? date("Y-m-d H:i", strtotime($task->created_at)) : "-";

        $actions = array();

        $CancelTask="Loadjs('$page?CancelTask=$taskId&md=task-$taskId')";
        $topbuttons=array();





        $topbuttons[] = array("Loadjs('$page?task-details-js=$taskId');", ico_loupe, "{details}");


        if($status == "pending"){
            $actions[] = "<a href=\"javascript:dns_confirm_propagation('$taskId');\" class='btn btn-xs btn-success'>{confirm}</a>";
            $topbuttons[] = array($CancelTask, ico_trash, "{cancel}");
        }

        if($status == "completed" && isset($task->has_certificate) && $task->has_certificate){
            $actions[] = "<a href='$page?download-cert=$taskId' class='btn btn-xs btn-success' target='_blank'>{download}</a>";
        }

        if($status == "completed" || $status == "failed" || $status == "expired" || $status == "cancelled"){
            $topbuttons[] = array("Loadjs('$page?RemoveTask=$taskId&md=task-$taskId')", ico_trash, "{delete}");
        }

        $actionsStr =$tpl->th_buttons($topbuttons);

        $html[] = "<tr id='task-$taskId'>";
        $html[] = "<td><code>$taskId</code></td>";
        $html[] = "<td>{$task->domain}</td>";
        $html[] = "<td>$statusLabel</td>";
        $html[] = "<td>$createdAt</td>";
        $html[] = "<td>$actionsStr</td>";
        $html[] = "</tr>";
    }

    $html[] = "</tbody></table>";
    $html[] = "</div>";

    echo $tpl->_ENGINE_parse_body($html);
    return true;
}

function task_details_js():bool{
    $tpl = new template_admin();
    $page = CurrentPageName();
    $taskId = $_GET["task-details-js"];
    return $tpl->js_dialog4("{task_details}", "$page?task-details-popup=$taskId", 700, 500);
}

function task_details_popup():bool{
    $tpl = new template_admin();
    $page = CurrentPageName();
    $taskId = $_GET["task-details-popup"];

    $sock = new sockets();
    $response = $sock->REST_API("/letsencrypt/dns/task/{$taskId}");

    $json = json_decode($response);
    if(json_last_error() !== JSON_ERROR_NONE || isset($json->error)){
        echo $tpl->div_error("Failed to get task details");
        return false;
    }

    $task = $json->task;

    $statusLabels = array(
        "pending" => "<span class='label label-warning'>{pending}</span>",
        "verifying" => "<span class='label label-info'>{verifying}</span>",
        "processing" => "<span class='label label-primary'>{processing}</span>",
        "completed" => "<span class='label label-success'>{completed}</span>",
        "failed" => "<span class='label label-danger'>{failed}</span>",
        "expired" => "<span class='label label-default'>{expired}</span>",
        "cancelled" => "<span class='label label-default'>{cancelled}</span>"
    );

    $status = isset($task->status) ? $task->status : "pending";
    $statusLabel = isset($statusLabels[$status]) ? $statusLabels[$status] : $status;

    $html = array();
    $html[] = "<table class='table table-bordered'>";
    $html[] = "<tr><th style='width:30%;'>Task ID</th><td><code>{$task->id}</code></td></tr>";
    $html[] = "<tr><th>{domain}</th><td>{$task->domain}</td></tr>";
    $html[] = "<tr><th>{email}</th><td>{$task->email}</td></tr>";
    $html[] = "<tr><th>{status}</th><td>$statusLabel</td></tr>";
    $html[] = "<tr><th>{key_type}</th><td>{$task->key_type}</td></tr>";
    $html[] = "<tr><th>{staging}</th><td>" . ($task->use_staging ? "{yes}" : "{no}") . "</td></tr>";
    $html[] = "<tr><th>TXT {record_name}</th><td><code>{$task->txt_record_name}</code></td></tr>";
    $html[] = "<tr><th>TXT {record_value}</th><td><code style='word-break:break-all;'>{$task->txt_record_value}</code></td></tr>";
    $html[] = "<tr><th>{created}</th><td>" . (isset($task->created_at) ? date("Y-m-d H:i:s", strtotime($task->created_at)) : "-") . "</td></tr>";
    $html[] = "<tr><th>{updated}</th><td>" . (isset($task->updated_at) ? date("Y-m-d H:i:s", strtotime($task->updated_at)) : "-") . "</td></tr>";
    $html[] = "<tr><th>{expires}</th><td>" . (isset($task->expires_at) ? date("Y-m-d H:i:s", strtotime($task->expires_at)) : "-") . "</td></tr>";

    if(isset($task->completed_at)){
        $html[] = "<tr><th>{completed}</th><td>" . date("Y-m-d H:i:s", strtotime($task->completed_at)) . "</td></tr>";
    }

    if(isset($task->error) && strlen($task->error) > 0){
        $html[] = "<tr><th>{error}</th><td class='text-danger'>{$task->error}</td></tr>";
    }

    $html[] = "</table>";

    // Actions
    $html[] = "<div style='text-align:right;margin-top:15px;'>";

    if($status == "completed" && isset($task->has_certificate) && $task->has_certificate){
        $html[] = "<a href='$page?download-cert=$taskId' class='btn btn-success' target='_blank'>{download_certificate}</a> ";
    }

    if($status == "pending"){
        $html[] = $tpl->button_autnonome("{confirm_dns}", "dns_confirm_propagation('$taskId');dialogInstance3.close();", "ok-48.png", null, 150, "btn-success") . " ";
    }

    $html[] = "</div>";

    echo $tpl->_ENGINE_parse_body($html);
    return true;
}

function dns_download_cert():bool{
    $taskId = $_GET["download-cert"];

    $sock = new sockets();
    $response = $sock->REST_API("/letsencrypt/dns/task/{$taskId}/certificate");

    $json = json_decode($response);
    if(json_last_error() !== JSON_ERROR_NONE || isset($json->error)){
        header("Content-Type: text/plain");
        echo "Error: Failed to download certificate";
        return false;
    }

    $domain = isset($json->domain) ? $json->domain : "certificate";
    $certificate = isset($json->certificate) ? $json->certificate : "";
    $privateKey = isset($json->private_key) ? $json->private_key : "";

    header("Content-Type: application/x-pem-file");
    header("Content-Disposition: attachment; filename=\"{$domain}.pem\"");
    echo $certificate;
    echo "\n";
    echo $privateKey;

    return true;
}

function dns_stats():bool{
    $tpl = new template_admin();

    $sock = new sockets();
    $response = $sock->REST_API("/letsencrypt/dns/stats");

    $json = json_decode($response);
    if(json_last_error() !== JSON_ERROR_NONE){
        echo $tpl->div_error("Failed to fetch statistics");
        return false;
    }

    $html = array();
    $html[] = "<table class='table table-bordered'>";
    $html[] = "<tr><th style='width:40%;'>{total_tasks}</th><td>{$json->total_tasks}</td></tr>";
    $html[] = "<tr><th>{max_tasks}</th><td>{$json->max_tasks}</td></tr>";
    $html[] = "<tr><th>Task TTL</th><td>{$json->task_ttl}</td></tr>";
    $html[] = "</table>";

    if(isset($json->status_counts) && is_object($json->status_counts)){
        $html[] = "<h4>{status_breakdown}</h4>";
        $html[] = "<table class='table table-bordered'>";
        foreach($json->status_counts as $status => $count){
            $html[] = "<tr><td>$status</td><td>$count</td></tr>";
        }
        $html[] = "</table>";
    }

    echo $tpl->_ENGINE_parse_body($html);
    return true;
}

// ============================================================================
// DNS Provider Configuration
// ============================================================================

function provider_config_tab():bool{
    $tpl = new template_admin();
    $page = CurrentPageName();
    $sock = new sockets();

    // Get list of providers
    $providersResponse = $sock->REST_API("/letsencrypt/providers");
    $providers = json_decode($providersResponse);

    // Get current status
    $statusResponse = $sock->REST_API("/letsencrypt/provider/status");
    $status = json_decode($statusResponse);

    $html = array();
    $html[] = "<div id='provider-config-container'>";

    // Current status
    $html[] = "<div class='panel panel-default'>";
    $html[] = "<div class='panel-heading'><h4>{current_configuration}</h4></div>";
    $html[] = "<div class='panel-body'>";

    if(isset($status->configured) && $status->configured){
        $providerName = isset($status->provider_name) ? $status->provider_name : (isset($status->provider) ? $status->provider : "");
        $html[] = "<p><strong>{configured_provider}:</strong> <span class='label label-success'>$providerName</span></p>";
        $html[] = "<p>";
        $html[] = $tpl->button_autnonome("{test_connection}", "test_dns_provider();", "ok-48.png", null, 180, "btn-info");
        $html[] = "</p>";
    } else {
        $html[] = "<p><span class='label label-warning'>{no_provider_configured}</span></p>";
        $html[] = "<p class='text-muted'>{select_provider_below}</p>";
    }

    $html[] = "</div>";
    $html[] = "</div>";

    // Provider selection
    $html[] = "<div class='panel panel-primary'>";
    $html[] = "<div class='panel-heading'><h4>{configure_dns_provider}</h4></div>";
    $html[] = "<div class='panel-body'>";

    // Build provider options
    $providerOptions = array();
    $providerFields = array();
    if(isset($providers->providers)){
        foreach($providers->providers as $p){
            $providerOptions[$p->type] = $p->name . " - " . $p->description;
            $providerFields[$p->type] = $p->fields;
        }
    }

    $html[] = "<div class='form-group'>";
    $html[] = "<label for='ProviderType'>{dns_provider}</label>";
    $html[] = "<select id='ProviderType' class='form-control' onchange='provider_type_changed();'>";
    $html[] = "<option value=''>-- {select_provider} --</option>";
    foreach($providerOptions as $type => $name){
        $html[] = "<option value='$type'>$name</option>";
    }
    $html[] = "</select>";
    $html[] = "</div>";

    // Dynamic fields container
    $html[] = "<div id='provider-fields-container'></div>";

    // Store field definitions in JavaScript
    $fieldsJson = json_encode($providerFields);
    $html[] = "<script>var providerFieldDefs = $fieldsJson;</script>";

    // Save button
    $html[] = "<div id='provider-save-btn' style='display:none;margin-top:15px;'>";
    $html[] = $tpl->button_autnonome("{save_configuration}", "save_provider_config();", "save-48.png", "AsSystemAdministrator", 200, "btn-success");
    $html[] = " ";
    $html[] = $tpl->button_autnonome("{test_before_save}", "test_provider_config();", "ok-48.png", null, 180, "btn-info");
    $html[] = "</div>";

    $html[] = "</div>";
    $html[] = "</div>";

    // Test result area
    $html[] = "<div id='provider-test-result'></div>";

    // JavaScript
    $html[] = "<script>
    function provider_type_changed(){
        var type = document.getElementById('ProviderType').value;
        var container = document.getElementById('provider-fields-container');
        var saveBtn = document.getElementById('provider-save-btn');

        if(!type){
            container.innerHTML = '';
            saveBtn.style.display = 'none';
            return;
        }

        var fields = providerFieldDefs[type] || [];
        var html = '';

        // Special handling for manual mode
        if(type == 'manual'){
            html += '<div class=\"alert alert-info\">';
            html += '<h4><i class=\"fa fa-info-circle\"></i> {manual_dns_mode}</h4>';
            html += '<p>{manual_dns_explain}</p>';
            html += '<ol>';
            html += '<li>{manual_step1}</li>';
            html += '<li>{manual_step2}</li>';
            html += '<li>{manual_step3}</li>';
            html += '<li>{manual_step4}</li>';
            html += '</ol>';
            html += '<p class=\"text-warning\"><strong>{note}:</strong> {manual_no_autorenew}</p>';
            html += '</div>';
        } else {
            for(var i = 0; i < fields.length; i++){
                var f = fields[i];
                var inputType = (f.type == 'password') ? 'password' : 'text';
                var required = f.required ? '<span class=\"text-danger\">*</span>' : '';

                if(f.type == 'boolean'){
                    html += '<div class=\"form-group\">';
                    html += '<label><input type=\"checkbox\" id=\"provider_' + f.name + '\"> ' + f.label + '</label>';
                    html += '<p class=\"help-block\">' + f.description + '</p>';
                    html += '</div>';
                } else {
                    html += '<div class=\"form-group\">';
                    html += '<label for=\"provider_' + f.name + '\">' + f.label + ' ' + required + '</label>';
                    html += '<input type=\"' + inputType + '\" id=\"provider_' + f.name + '\" class=\"form-control\" placeholder=\"' + f.description + '\">';
                    html += '<p class=\"help-block\">' + f.description + '</p>';
                    html += '</div>';
                }
            }
        }

        container.innerHTML = html;
        saveBtn.style.display = 'block';
    }

    function get_provider_config(){
        var type = document.getElementById('ProviderType').value;
        if(!type) return null;

        var fields = providerFieldDefs[type] || [];
        var config = {};

        for(var i = 0; i < fields.length; i++){
            var f = fields[i];
            var el = document.getElementById('provider_' + f.name);
            if(el){
                if(f.type == 'boolean'){
                    config[f.name] = el.checked ? 'true' : 'false';
                } else {
                    config[f.name] = el.value;
                }
            }
        }

        return {provider: type, config: config};
    }

    function save_provider_config(){
        var data = get_provider_config();
        if(!data){
            alert('{select_provider_first}');
            return;
        }

        var XHR = new XHRConnection();
        XHR.appendData('SaveProviderConfig', '1');
        XHR.appendData('ProviderType', data.provider);
        XHR.appendData('ProviderConfig', JSON.stringify(data.config));
        XHR.sendAndLoad('$page', 'POST', function(r){
            document.getElementById('provider-test-result').innerHTML = r;
            setTimeout(function(){ LoadAjax('provider-config-container','$page?provider-config=yes'); }, 2000);
        });
    }

    function test_provider_config(){
        var data = get_provider_config();
        if(!data){
            alert('{select_provider_first}');
            return;
        }

        document.getElementById('provider-test-result').innerHTML = '<div class=\"loading\"></div>';

        var XHR = new XHRConnection();
        XHR.appendData('TestProviderConfig', '1');
        XHR.appendData('ProviderType', data.provider);
        XHR.appendData('ProviderConfig', JSON.stringify(data.config));
        XHR.sendAndLoad('$page', 'POST', function(r){
            document.getElementById('provider-test-result').innerHTML = r;
        });
    }

    function test_dns_provider(){
        document.getElementById('provider-test-result').innerHTML = '<div class=\"loading\"></div>';
        LoadAjax('provider-test-result', '$page?test-provider=yes');
    }
    </script>";

    $html[] = "</div>";

    echo $tpl->_ENGINE_parse_body($html);
    return true;
}

function save_provider_config(){
    $tpl = new template_admin();

    $providerType = isset($_POST["ProviderType"]) ? $_POST["ProviderType"] : "";
    $configJson = isset($_POST["ProviderConfig"]) ? $_POST["ProviderConfig"] : "{}";

    if(empty($providerType)){
        echo $tpl->div_error("{select_provider_first}");
        return false;
    }

    $config = json_decode($configJson, true);
    if(!is_array($config)){
        echo $tpl->div_error("Invalid configuration");
        return false;
    }

    $payload = array(
        "provider" => $providerType,
        "config" => $config
    );

    $response = $GLOBALS["CLASS_SOCKETS"]->REST_API_POST_JSON("/letsencrypt/provider/configure", $payload);
    $json = json_decode($response);

    if(isset($json->error)){
        echo $tpl->div_error($json->error);
        return false;
    }

    if(isset($json->success) && $json->success){
        $providerName = isset($json->provider_name) ? $json->provider_name : $providerType;
        echo $tpl->div_success("{provider_configured}: $providerName");
        return true;
    }

    echo $tpl->div_error("{configuration_failed}");
    return false;
}

function test_provider(){
    $tpl = new template_admin();

    $response = $GLOBALS["CLASS_SOCKETS"]->REST_API_POST_JSON("/letsencrypt/provider/test", array());
    $json = json_decode($response);

    if(isset($json->success) && $json->success){
        $providerName = isset($json->provider_name) ? $json->provider_name : "";
        echo $tpl->div_success("{connection_test_successful}: " . $providerName);
        return true;
    }

    $error = "{connection_test_failed}";
    if(isset($json->message)){
        $error = $json->message;
    } elseif(isset($json->error)){
        $error = $json->error;
    }
    echo $tpl->div_error($error);
    return false;
}

// ============================================================================
// Auto-Renewal Configuration
// ============================================================================

function autorenew_config_tab(){
    $tpl = new template_admin();
    $page = CurrentPageName();
    $sock = new sockets();

    // Get auto-renewal status
    $response = $sock->REST_API("/letsencrypt/autorenew/status");
    $status = json_decode($response);

    $html = array();
    $html[] = "<div id='autorenew-config-container'>";

    // Get counts
    $thresholdDays = (is_object($status) && isset($status->threshold_days)) ? $status->threshold_days : 30;
    $checkInterval = (is_object($status) && isset($status->check_interval)) ? $status->check_interval : "6h0m0s";
    $certsWithProvider = (is_object($status) && isset($status->certs_with_provider)) ? $status->certs_with_provider : 0;

    // Explanation
    $html[] = $tpl->div_explain("{autorenew_explain}");

    // Status panel
    $html[] = "<div class='panel panel-default'>";
    $html[] = "<div class='panel-heading'><h4>{auto_renewal_status}</h4></div>";
    $html[] = "<div class='panel-body'>";

    $html[] = "<table class='table table-bordered'>";
    $html[] = "<tr><th style='width:40%;'>{certificates_with_provider}</th><td><span class='label label-primary'>$certsWithProvider</span></td></tr>";
    $html[] = "<tr><th>{renewal_threshold}</th><td>$thresholdDays {days}</td></tr>";
    $html[] = "<tr><th>{check_interval}</th><td>$checkInterval</td></tr>";
    $html[] = "</table>";

    if($certsWithProvider == 0){
        $html[] = $tpl->div_warning("{no_certificates_with_provider_explain}");
    } else {
        $html[] = $tpl->div_info("{autorenew_active_explain}");
    }

    $html[] = "</div>";
    $html[] = "</div>";

    // Renewal results
    $html[] = "<div class='panel panel-info'>";
    $html[] = "<div class='panel-heading'><h4>{recent_renewal_results}</h4></div>";
    $html[] = "<div class='panel-body' id='renewal-results-content'>";
    $html[] = "</div>";
    $html[] = "</div>";

    // JavaScript
    $html[] = "<script>
    // Load renewal results
    LoadAjax('renewal-results-content', '$page?renewal-results=yes');
    </script>";

    $html[] = "</div>";

    echo $tpl->_ENGINE_parse_body($html);
    return true;
}

function enable_autorenew(){
    // Clear any buffered output
    while(ob_get_level() > 0){
        ob_end_clean();
    }
    header('Content-Type: application/json');

    $thresholdDays = isset($_POST["ThresholdDays"]) ? intval($_POST["ThresholdDays"]) : 30;
    if($thresholdDays < 1) $thresholdDays = 30;

    $payload = array("threshold_days" => $thresholdDays);

    $response = $GLOBALS["CLASS_SOCKETS"]->REST_API_POST_JSON("/letsencrypt/autorenew/enable", $payload);
    $json = json_decode($response, true);

    if(json_last_error() === JSON_ERROR_NONE){
        echo json_encode($json);
    } else {
        echo json_encode(array("success" => false, "error" => "Invalid API response"));
    }
}

function disable_autorenew(){
    $tpl = new template_admin();
    $page = CurrentPageName();

    $response = $GLOBALS["CLASS_SOCKETS"]->REST_API_POST_JSON("/letsencrypt/autorenew/disable", array());
    $json = json_decode($response);

    echo $tpl->div_success("{auto_renewal_disabled}");
    echo "<script>setTimeout(function(){ LoadAjax('autorenew-config-container','$page?autorenew-config=yes'); }, 1000);</script>";
    return true;
}

function renewal_results_tab():bool{
    $tpl = new template_admin();
    $sock = new sockets();

    $response = $sock->REST_API("/letsencrypt/autorenew/results");
    $json = json_decode($response);

    $html = array();

    $results = isset($json->results) ? $json->results : array();

    if(count($results) == 0){
        $html[] = "<p class='text-muted'>{no_renewal_results}</p>";
        echo $tpl->_ENGINE_parse_body($html);
        return true;
    }

    // Status label mapping (PHP 7.3 compatible)
    $statusLabels = array(
        "success" => "<span class='label label-success'>{success}</span>",
        "failed" => "<span class='label label-danger'>{failed}</span>",
        "pending" => "<span class='label label-warning'>{pending}</span>"
    );

    $html[] = "<table class='table table-bordered table-striped'>";
    $html[] = "<thead><tr>";
    $html[] = "<th>{domain}</th>";
    $html[] = "<th>{status}</th>";
    $html[] = "<th>{started}</th>";
    $html[] = "<th>{completed}</th>";
    $html[] = "<th>{details}</th>";
    $html[] = "</tr></thead>";
    $html[] = "<tbody>";

    foreach($results as $r){
        $status = isset($r->status) ? $r->status : "unknown";
        $statusLabel = isset($statusLabels[$status]) ? $statusLabels[$status] : "<span class='label label-default'>$status</span>";

        $started = isset($r->started_at) ? date("Y-m-d H:i", strtotime($r->started_at)) : "-";
        $completed = isset($r->completed_at) && $r->completed_at != "0001-01-01T00:00:00Z" ? date("Y-m-d H:i", strtotime($r->completed_at)) : "-";

        $details = "";
        if(isset($r->error) && strlen($r->error) > 0){
            $details = "<span class='text-danger'>" . htmlspecialchars($r->error) . "</span>";
        } elseif(isset($r->new_expiry) && $r->new_expiry != "0001-01-01T00:00:00Z"){
            $details = "{new_expiry}: " . date("Y-m-d", strtotime($r->new_expiry));
        }

        $html[] = "<tr>";
        $html[] = "<td>{$r->domain}</td>";
        $html[] = "<td>$statusLabel</td>";
        $html[] = "<td>$started</td>";
        $html[] = "<td>$completed</td>";
        $html[] = "<td>$details</td>";
        $html[] = "</tr>";
    }

    $html[] = "</tbody></table>";

    echo $tpl->_ENGINE_parse_body($html);
    return true;
}

function expiring_certs_tab():bool{
    $tpl = new template_admin();
    $page = CurrentPageName();
    $sock = new sockets();

    // Get expiring certificates
    $response = $sock->REST_API("/letsencrypt/autorenew/expiring?days=90");
    $json = json_decode($response);

    $html = array();
    $html[] = "<div id='expiring-certs-container'>";

    // Refresh button
    $html[] = "<div style='text-align:right;margin-bottom:10px;margin-top:10px'>";
    $html[] = $tpl->button_autnonome("{refresh}", "LoadAjax('expiring-certs-container','$page?expiring-certs=yes');", ico_refresh, null, 120);
    $html[] = "</div>";

    $certs = isset($json->certificates) ? $json->certificates : array();

    if(count($certs) == 0){
        $html[] = $tpl->div_success("{no_expiring_certificates}");
        $html[] = "</div>";
        echo $tpl->_ENGINE_parse_body($html);
        return true;
    }

    $thresholdDays = isset($json->threshold_days) ? $json->threshold_days : 90;
    $html[] = "<p><strong>{certificates_expiring_within}:</strong> " . $thresholdDays . " {days}</p>";

    $html[] = "<table class='table table-bordered table-striped'>";
    $html[] = "<thead><tr>";
    $html[] = "<th>{domain}</th>";
    $html[] = "<th>{email}</th>";
    $html[] = "<th>{expiry_date}</th>";
    $html[] = "<th>{days_left}</th>";
    $html[] = "<th>{actions}</th>";
    $html[] = "</tr></thead>";
    $html[] = "<tbody>";

    $td1="style='width:1%' nowrap";
    foreach($certs as $cert){
        $daysLeft = isset($cert->DaysLeft) ? $cert->DaysLeft : 0;
        $daysClass = "success";
        if($daysLeft < 7) $daysClass = "danger";
        elseif($daysLeft < 14) $daysClass = "warning";
        elseif($daysLeft < 30) $daysClass = "info";

        $expiryDate = isset($cert->ExpiryDate) && $cert->ExpiryDate != "0001-01-01T00:00:00Z"
            ? date("Y-m-d", strtotime($cert->ExpiryDate)) : "-";

        $commonName = isset($cert->CommonName) ? $cert->CommonName : "";
        $domain = htmlspecialchars($commonName);
        $email = isset($cert->Email) ? $cert->Email : "";

        $html[] = "<tr>";
        $html[] = "<td>$domain</td>";
        $html[] = "<td $td1>" . htmlspecialchars($email) . "</td>";
        $html[] = "<td $td1>$expiryDate</td>";
        $html[] = "<td $td1><span class='label label-$daysClass'>$daysLeft {days}</span></td>";
        $html[] = "<td $td1>";
        $html[] = "<a href='javascript:trigger_renewal(\"$domain\");' class='btn btn-xs btn-primary'>{renew_now}</a>";
        $html[] = "</td>";
        $html[] = "</tr>";
    }

    $html[] = "</tbody></table>";

    // JavaScript for triggering renewal
    $html[] = "<script>
    function trigger_renewal(domain){
        if(!confirm('{confirm_trigger_renewal} ' + domain + '?')){
            return;
        }
        LoadAjax('expiring-certs-container', '$page?trigger-renewal=' + encodeURIComponent(domain));
    }
    </script>";

    $html[] = "</div>";

    echo $tpl->_ENGINE_parse_body($html);
    return true;
}

function trigger_renewal():bool{
    $tpl = new template_admin();
    $page = CurrentPageName();

    $domain = $_GET["trigger-renewal"];

    $response = $GLOBALS["CLASS_SOCKETS"]->REST_API_POST_JSON("/letsencrypt/autorenew/trigger/$domain", array());
    $json = json_decode($response);

    if(isset($json->error)){
        echo $tpl->div_error($json->error);
        echo "<script>setTimeout(function(){ LoadAjax('expiring-certs-container','$page?expiring-certs=yes'); }, 2000);</script>";
        return false;
    }

    if(isset($json->success) && $json->success){
        echo $tpl->div_success("{renewal_triggered}: $domain");
        echo "<p class='text-muted'>{renewal_running_in_background}</p>";
        echo "<script>setTimeout(function(){ LoadAjax('expiring-certs-container','$page?expiring-certs=yes'); }, 3000);</script>";
        return true;
    }

    echo $tpl->div_error("{operation_failed}");
    return false;
}
