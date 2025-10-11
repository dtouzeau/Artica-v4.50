<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.system.network.inc");
include_once(dirname(__FILE__)."/ressources/class.squid.inc");
include_once(dirname(__FILE__)."/ressources/class.mimes-types.inc");
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(isset($_GET["table"])){table();exit;}
if(isset($_GET["status"])){status();exit;}
if(isset($_GET["tabs"])){tabs();exit;}
if(isset($_GET["sandbox"])){sandbox_page();exit;}
if(isset($_GET["ext-sandbox-js"])){sandbox_kaspersky_extensions();exit;}
if(isset($_GET["sandbox-kaspersky-extensions"])){sandbox_kaspersky_extensions_list();exit;}
if(isset($_GET["sandbox-kaspersky-exten"])){sandbox_kaspersky_exten();exit;}
if(isset($_POST["EnableKasperskySandbox"])){sandbox_kaspersky_save();exit;}
if(isset($_GET["upload-sandbbox-js"])){sandbox_upload_js();exit;}
if(isset($_GET["upload-sandbbox-popup"])){sandbox_upload_popup();exit;}
if(isset($_GET["file-uploaded"])){sandbox_uploaded_js();exit;}
if(isset($_GET["results"])){results();exit;}
if(isset($_GET["search"])){search();exit;}
if(isset($_GET["delete-js"])){delete_js();exit;}
if(isset($_POST["delete"])){delete_confirm();exit;}

page();

function delete_js(){
    $id         = intval($_GET["delete-js"]);
    $q          = new postgres_sql();
    $tpl        = new template_admin();
    $ligne      = $q->mysqli_fetch_array("SELECT * FROM cicap_sandbox where id=$id");

    $filename=$ligne["filename"];
    $uri=$ligne["uri"];
    $content_type=$ligne["content_type"] ;
    $sbxcode=$ligne["sbxcode"];
    $md=$_GET["md"];
    $tpl->js_confirm_delete("$filename / $content_type - $uri ($sbxcode)","delete",$id,"$('#$md').remove()");

}

function delete_confirm(){
    $id=intval($_POST["delete"]);
    $q=new postgres_sql();
    $ligne      = $q->mysqli_fetch_array("SELECT * FROM cicap_sandbox where id=$id");
    $filename=$ligne["filename"];
    $uri=$ligne["uri"];
    $content_type=$ligne["content_type"] ;
    $sbxcode=$ligne["sbxcode"];
    $logtext="Remove Sandbox threat $filename / $content_type - $uri ($sbxcode)";
    
    $q->QUERY_SQL("DELETE FROM cicap_sandbox where id=$id");
    if(!$q->ok){echo $q->mysql_error;return false;}
    admin_tracks($logtext);
    return true;
}

function page(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $CicapVersion=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("CicapVersion");

    $html=$tpl->page_header("{sandbox_connector} v$CicapVersion",
        "far fa-box",null,"$page?tabs=yes","proxy-sandbox",
        "progress-c-icap-restart",false,"table-loader-c-icap");


    if(isset($_GET["main-page"])){
        $tpl=new template_admin(null,$html);
        echo $tpl->build_firewall("{sandbox_connector}");
        return;
    }

    echo $tpl->_ENGINE_parse_body($html);

}

function sandbox_kaspersky_extensions() {
    $tpl        = new template_admin();
    $page       = CurrentPageName();
    $tpl->js_dialog4("Kaspersky SandBox {extensions}","$page?sandbox-kaspersky-extensions=yes");
}
function sandbox_upload_js(){
    $tpl        = new template_admin();
    $page       = CurrentPageName();
    $tpl->js_dialog4("SandBox {upload}","$page?upload-sandbbox-popup=yes",400);
}
function sandbox_upload_popup(){
    $tpl        = new template_admin();
    $page       = CurrentPageName();
    $html[]="";
    $html[]=$tpl->div_explain("{upload_sb_ask}");
    $html[]="<div class='center' style='margin: 30px'>".$tpl->button_upload("{upload_a_file}",$page)."</div>";
    $html[]="<div id='import-results' style='margin: 30px'></div>";
    echo $tpl->_ENGINE_parse_body($html);
}

function sandbox_uploaded_js(){
    $filename   = $_GET["file-uploaded"];
    $fileencode = urlencode($filename);
    $GLOBALS["CLASS_SOCKETS"]->getFrameWork("cicap.php?sandbox-file=$fileencode");
    admin_tracks("$filename was upload to SandBox detection");
    header("content-type: application/x-javascript");
    echo "dialogInstance4.close();";
}


function sandbox_kaspersky_extensions_list(){
    $tpl        = new template_admin();
    $page       = CurrentPageName();
    $t          = time();
    $TRCLASS    = null;
    $security   ="AsSquidAdministrator";
    $CountOfKasperskySandboxMime    = 0;
    $text_class = null;

    include_once(dirname(__FILE__)."/ressources/class.mimes-types.inc");
    $mimes=mimestypes_array();

    $fields[]="{extensions}";
    $fields[]="{BannedMimetype}";
    $fields[]="{enabled}";

    $html[]=$tpl->table_head($fields,"table-$t");
    $KasperskySandboxMime=unserialize($GLOBALS["CLASS_SOCKETS"]->GET_INFO("KasperskySandboxMime"));

    if(is_array($KasperskySandboxMime)) {
        $CountOfKasperskySandboxMime=count($KasperskySandboxMime);
    }
    if ($CountOfKasperskySandboxMime == 0) {
        $KasperskySandboxMime = mimesandboxdefaults();
    }

    foreach ($mimes as $ext=>$mime){
        if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
        $token=md5("$ext$mime");
        $enabled=0;
        if(isset($KasperskySandboxMime[$token])){$enabled=1;}

        $enabled=$tpl->icon_check($enabled,"Loadjs('$page?sandbox-kaspersky-exten=$token')",null,$security);

        $html[]="<tr class='$TRCLASS'>";
        $html[]="<td class=\"$text_class\" width='1%' nowrap>$ext</td>";
        $html[]="<td class=\"$text_class\">$mime</td>";
        $html[]="<td class=\"$text_class\" width='1%'>$enabled</td>";
        $html[]="</tr>";
    }

    $html[]=$tpl->table_footer("table-$t",count($fields),true);
    echo $tpl->_ENGINE_parse_body($html);

}

function sandbox_kaspersky_exten(){
    $ptoken=$_GET["sandbox-kaspersky-exten"];
    include_once(dirname(__FILE__)."/ressources/class.mimes-types.inc");
    $KasperskySandboxMime   = unserialize($GLOBALS["CLASS_SOCKETS"]->GET_INFO("KasperskySandboxMime"));
    $mimes                  = mimestypes_array();
    foreach ($mimes as $ext=>$mime){
        $token=md5("$ext$mime");
        $MAINS[$token]=$mime;
    }
    if(isset($KasperskySandboxMime[$ptoken])){
        unset($KasperskySandboxMime[$ptoken]);
        admin_tracks("Removed scanning $mime in Kaspersky SandBox");

    }else{
        admin_tracks("Added scanning $mime in Kaspersky SandBox");
        $KasperskySandboxMime[$ptoken]=$MAINS[$ptoken];
    }

    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("KasperskySandboxMime",serialize($KasperskySandboxMime));

}


function sandbox_page(){
    $page   = CurrentPageName();
    $tpl    = new template_admin();


    $tpl->CLUSTER_CLI = true;
    $security="AsSquidAdministrator";
    $expl   = null;
    $bt     = "{apply}";
    $CountOfKasperskySandboxMime    = 0;
    $C_ICAP_RECORD=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("C_ICAP_RECORD"));
    $C_ICAP_RECORD_ENABLED=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("CICAPEnableSandBox"));
    if($C_ICAP_RECORD==0){$C_ICAP_RECORD_ENABLED=0;}

    $jsCompile = "blur();";
    $EnableKasperskySandbox=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableKasperskySandbox"));
    $KasperskySandboxAddr=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("KasperskySandboxAddr"));
    $KasperskySandboxMime=unserialize($GLOBALS["CLASS_SOCKETS"]->GET_INFO("KasperskySandboxMime"));
    $SandBoxMaxRetentionTime=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SandBoxMaxRetentionTime"));
    $SandBoxMaxEventRetentionTime=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SandBoxMaxEventRetentionTime"));
    if($SandBoxMaxRetentionTime==0){$SandBoxMaxRetentionTime=180;}
    if($SandBoxMaxEventRetentionTime==0){$SandBoxMaxEventRetentionTime=7;}



    if(is_array($KasperskySandboxMime)) {
        $CountOfKasperskySandboxMime = count($KasperskySandboxMime);
    }
    if($CountOfKasperskySandboxMime==0){
        $KasperskySandboxMime=mimesandboxdefaults();
        $CountOfKasperskySandboxMime = count($KasperskySandboxMime);
    }
    $form[]=$tpl->field_section("{parameters}");
    $form[]=$tpl->field_numeric("SandBoxMaxRetentionTime","{retention_time} {minutes}",$SandBoxMaxRetentionTime);
    $form[]=$tpl->field_numeric("SandBoxMaxEventRetentionTime","{logs_retention} {days}",$SandBoxMaxEventRetentionTime);

    $form[]=$tpl->field_section("Kaspersky Sandbox");
    $form[]=$tpl->field_checkbox("EnableKasperskySandbox","{enable_feature}",$EnableKasperskySandbox,false);
    $form[]=$tpl->field_text("KasperskySandboxAddr","{sandbox_server_address}",$KasperskySandboxAddr);
    $form[]=$tpl->field_info("extension_list", " {extension_list}",

        array("VALUE"=>null,
            "BUTTON"=>true,
            "BUTTON_CAPTION"=>"$CountOfKasperskySandboxMime {extensions}",
            "BUTTON_JS"=>"Loadjs('$page?ext-sandbox-js=yes')"

        ),null);

    if($C_ICAP_RECORD_ENABLED==0){
        $expl   = "{feature_not_installed}";
        $bt     = "{feature_not_installed}";
    }

    if($C_ICAP_RECORD_ENABLED==1) {
        $tpl->form_add_button("{upload}", "Loadjs('$page?upload-sandbbox-js=yes')");
    }
    $html[]=$tpl->form_outside("SandBox: {parameters}", @implode("\n", $form),$expl,$bt,$jsCompile,$security);
    echo $tpl->_ENGINE_parse_body($html);
}
function sandbox_kaspersky_save(){
    $tpl=new template_admin();
    $tpl->SAVE_POSTs();
    admin_tracks("SandBox settings modified.");
    $GLOBALS["CLASS_SOCKETS"]->getFrameWork("cicap.php?reload=yes");
}



function tabs(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $array["{sandbox_connector}"]="$page?sandbox=yes";
    $array["{results}"]="$page?results=yes";
    echo $tpl->tabs_default($array);
}

function results(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $t=time();
    if(!isset($_SESSION["SANDBOXSEARCH"])){$_SESSION["SANDBOXSEARCH"]="max 500";}

    $html="
	<div class=\"row\">
	<div class='ibox-content'>
	<div class=\"input-group\">
	<input type=\"text\" class=\"form-control\" value=\"{$_SESSION["SANDBOXSEARCH"]}\" placeholder=\"{search}\" id='search-this-$t' OnKeyPress=\"javascript:Search$t(event);\">
	<span class=\"input-group-btn\">
	<button style=\"text-transform: capitalize;\" class=\"btn btn-default\" type=\"button\" OnClick=\"javascript:ss$t();\">Go!</button>
	</span>
	</div>
	</div>
	</div>
	<div class='row'><div id='progress-firehol-restart'></div>
	<div class='ibox-content'>

	<div id='table-sandbox-queue'></div>

	</div>
	</div>
	<script>
function Search$t(e){
	if(!checkEnter(e) ){return;}
	ss$t();
}

function ss$t(){
	var ss=encodeURIComponent(document.getElementById('search-this-$t').value);
	LoadAjax('table-sandbox-queue','$page?search='+ss+'&funtion=ss$t');
}

function Start$t(){
	var ss=document.getElementById('search-this-$t').value;
	ss$t();
}
Start$t();
</script>";


    echo $tpl->_ENGINE_parse_body($html);

}

function search(){
    $tpl=new template_admin();
    $page=CurrentPageName();
    $vpn=new openvpn();
    $nic=new networking();
    $sock=new sockets();
    $page=CurrentPageName();

    $date=$tpl->javascript_parse_text("{connection_date}");
    $events=$tpl->javascript_parse_text("{events}");
    $website=$tpl->_ENGINE_parse_body("{website}");
    $Items_text=$tpl->_ENGINE_parse_body("{items}");
    $hostname=$tpl->_ENGINE_parse_body("{hostname}");
    $html[]="<table id='table-firewall-rules' class=\"footable table table-stripped\" data-page-size=\"100\" data-paging=\"true\">";
    $html[]="<thead>";
    $html[]="<tr>";

    //INSERT INTO webfilter (zDate,website,category,rulename,public_ip,blocktype,why,hostname,client,PROXYNAME,rqs)


    $TRCLASS=null;
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{time}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>SandBox</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{status}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{filename}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{dstdomain}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{member}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{duration}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>DEL</th>";



    $html[]="</tr>";
    $html[]="</thead>";
    $html[]="<tbody>";

    $_SESSION["SANDBOXSEARCH"]=$_GET["search"];

    if($_GET["search"]<>null) {
        $search = $tpl->query_pattern(trim(strtolower($_GET["search"])), $aliases);
        VERBOSE("{$_GET["search"]} = '{$search["Q"]}'");
        if($search["Q"]<>null){

            $pattern="%{$_GET["search"]}%";
            $search["Q"]="WHERE ( (uri ILIKE '$pattern') OR ( filename ILIKE '$pattern') OR (content_type ILIKE '$pattern') OR (sbxcode ILIKE '$pattern') )";
        }
    }



    $q=new postgres_sql();
    $q->create_sandbox_table();
    if(intval($search["MAX"])==0){$search["MAX"]=250;}
    $sql="SELECT * FROM cicap_sandbox {$search["Q"]} ORDER BY id DESC LIMIT {$search["MAX"]}";
    $results=$q->QUERY_SQL($sql);
    $Items=pg_num_rows($results);

    if(!$q->ok){echo "<div class='alert alert-danger'>$q->mysql_error<br><strong><code>{$_GET["search"]}</code></strong><br><strong><code>$sql</code></strong></div>";}


    $tpl2=new templates();
    while ($ligne = pg_fetch_assoc($results)) {
        if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
        $md5file=$ligne["md5file"];
        $id=$ligne["id"];
        $workname=$ligne["workname"];
        $filetime=$ligne["filetime"];
        $filesize=FormatBytes($ligne["filesize"]/1024);
        $filename=$ligne["filename"];
        $uri=$ligne["uri"];
        $content_type=$ligne["content_type"] ;
        $ipaddr=$ligne["ipaddr"];
        $username=$ligne["username"];
        $sandboxsrv=$ligne["sandboxsrv"];
        $posttime=$ligne["posttime"];
        $restime=$ligne["restime"];
        $sbxcode=$ligne["sbxcode"];
        $icon="fas fa-check-circle";
        $processtime = $tpl->icon_nothing();
        if($restime>0) {
            $processtime = distanceOfTimeInWords($posttime, $restime, true);
        }

        $textclass=null;

        if($sandboxsrv=="KSB"){$sandboxsrv="Kaspersky SandBox (KSB)";}

        $date=$tpl2->time_to_date($filetime,true);
        $md=md5(serialize($ligne));
        $fuser="fa-user";
        $why=$ligne["why"];
        if($ligne["client"]==null){
            if($ligne["hostname"]<>null){$ligne["client"]=$ligne["hostname"];}
        }

        if($sbxcode=="DETECTED"){
            $icon="fas fa-virus";
            $textclass="text-danger";
        }
        if($sbxcode=="ABORTED" OR $sbxcode=="TIMEOUT"){
            $icon="fas fa-exclamation-circle";
            $textclass="text-warning";
        }

        if($ligne["client"]==null){$ligne["client"]="&nbsp;-&nbsp;";$fuser="fa-user-o";}
        $del=$tpl->icon_delete("Loadjs('$page?delete-js=$id&md=$md5file')","AsDansGuardianAdministrator");
        $html[]="<tr class='$TRCLASS' id='$md5file'>";
        $html[]="<td nowrap class='$textclass' width='1%'><span class='fa fa-clock'> </span>&nbsp;{$date}</td>";
        $html[]="<td nowrap class='$textclass' width='1%'><span class='fa fa-box'></span>&nbsp;$sandboxsrv</td>";
        $html[]="<td nowrap class='$textclass' width='1%'><span class='$icon'></span>&nbsp;$sbxcode</td>";
        $html[]="<td nowrap class='$textclass'><span class='fa fa-file' ></span>$filename <small>$content_type $filesize</small></td>";
        $html[]="<td nowrap class='$textclass'><span class='fa fa-cloud' ></span>&nbsp;$uri</td>";
        $html[]="<td nowrap class='$textclass'><span class='fa fa-desktop' ></span>&nbsp;$ipaddr</td>";
        $html[]="<td nowrap class='$textclass'><span class='fa fa-clock' ></span>&nbsp;$processtime</td>";
        $html[]="<td nowrap class='$textclass' width='1%'>$del</td>";

        $html[]="</tr>";
    }

    $html[]="</tbody>";
    $html[]="<tfoot>";

    $html[]="<tr>";
    $html[]="<td colspan='8'>";
    $html[]="<ul class='pagination pull-right'></ul>";
    $html[]="</td>";
    $html[]="</tr>";
    $html[]="</tfoot>";
    $html[]="</table>";
    $html[]="<div><i>$Items $Items_text &laquo;$sql&raquo;</i></div>
	<script>
	NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS)."
	$(document).ready(function() { $('.footable').footable(
	{
	\"filtering\": {
	\"enabled\": false
	},
	\"sorting\": {
	\"enabled\": true
	}
	
	}
	
	
	); });
</script>";

    echo $tpl->_ENGINE_parse_body($html);
}


