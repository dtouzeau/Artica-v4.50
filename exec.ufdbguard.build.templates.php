<?php
$GLOBALS["AS_ROOT"]=false;
$GLOBALS["KAV4PROXY_NOSESSION"]=true;
$GLOBALS["OUTPUT"]=false;
$GLOBALS["FORCE"]=false;
$GLOBALS["RELOAD"]=false;
$GLOBALS["RESTART"]=false;
$GLOBALS["VERBOSE"]=false;
$GLOBALS["WRITELOGS"]=false;
$GLOBALS["UFDBPAGE"]=true;
$GLOBALS["DEBUG_TEMPLATE"]=false;
$GLOBALS["FOLLOW"]=false;
$GLOBALS["TITLENAME"]="URLfilterDB daemon";
if(isset($_GET["verbose"])){ini_set_verbosedx();}else{	ini_set('display_errors', 0);ini_set('error_reporting', 0);}
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
include_once(dirname(__FILE__)."/ressources/class.squid.familysites.inc");
include_once(dirname(__FILE__)."/ressources/class.ufdbguard.templates.inc");
include_once(dirname(__FILE__)."/ressources/class.ufdb.parsetemplate.inc");
include_once(dirname(__FILE__)."/ressources/class.templates.manager.inc");
include_once(dirname(__FILE__)."/ressources/class.mysql.catz.inc");
include_once(dirname(__FILE__)."/ressources/class.sockets.inc");
include_once(dirname(__FILE__)."/ressources/class.smtpd.notifications.inc");
$GLOBALS["CLASS_SOCKETS"]=new sockets();
if($argv[1]=="--build"){build_rules();exit;}

function build_rules():bool{

    _out("Reconfiguring Web error page data...");
    if(!is_dir("/home/artica/web_templates")){
        @mkdir("/home/artica/web_templates",0755,true);
    }


    $SquidTemplateid=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidTemplateid"));
    if($SquidTemplateid==0){$SquidTemplateid=1;}
    $q=new lib_sqlite("/home/artica/SQLITE/webfilter.db");
    $TEMPLATE=FORMAT_TEMPLATE($SquidTemplateid);
    @file_put_contents("/home/artica/web_templates/default",$TEMPLATE);

    $INDEX=array();
    $results=$q->QUERY_SQL("SELECT * FROM ufdb_page_rules WHERE enabled=1 ORDER BY zorder");
    foreach ($results as $index=>$ligne){
            export_rule($ligne);
            $INDEX[]=$ligne["zmd5"];
    }

    $smtp_defaults=smtp_defaults();
    if(intval($smtp_defaults["ENABLED_SQUID_WATCHDOG"])==1){
        @file_put_contents("/home/artica/web_templates/smtp_notifications_enabled",1);
        @file_put_contents("/home/artica/web_templates/smtp_sender",$smtp_defaults["smtp_sender"]);

    }else{
        @file_put_contents("/home/artica/web_templates/smtp_notifications_enabled",0);
    }


    @file_put_contents("/home/artica/web_templates/ufdb_page_rules.index",@implode(",",$INDEX));

    export_webfiltering_rules();
    export_categories();
    _out("Parsing ACLs data");
    export_acls();
    $ufdb_page_rules_found=new ufdb_page_rules_found();
    $rule_data=$ufdb_page_rules_found->GetRuleContent("");
    if(!is_array($rule_data)){$rule_data=array();}
    @mkdir("/home/artica/web_templates/default_skin",0755,true);
    foreach ($rule_data as $key=>$val){
        @file_put_contents("/home/artica/web_templates/default_skin/$key",$val);
    }

    _out("Reconfiguring Web error page data done...");
    return true;
}

function export_categories(){
    $catz=new mysql_catz();
    $categories_descriptions=$catz->categories_descriptions();
    foreach ( $categories_descriptions as $categoryid=>$array){
        if(!isset($array["categoryname"])){
            continue;
        }
      $categoryname=$array["categoryname"];
      $fname="/home/artica/web_templates/webfilter.category.$categoryid.name";
      @file_put_contents($fname,$categoryname);
    }
}
function export_acls(){
    $q=new lib_sqlite("/home/artica/SQLITE/acls.db");
    $results = $q->QUERY_SQL("SELECT * FROM webfilters_sqacls");
    if(!$results){
        return false;
    }
    foreach($results as $index=>$ligne) {
        $ID = $ligne["ID"];
        $aclname = $ligne["aclname"];
        $fname="/home/artica/web_templates/proxyacl.rule.$ID.name";
        @file_put_contents($fname,$aclname);
    }
    return true;
}
function export_rule($ligne){
    $zmd5=$ligne["zmd5"];
    $path="/home/artica/web_templates/webrule_$zmd5";
    if(!is_dir($path)){@mkdir($path,0755,true);}
    foreach ($ligne as $key=>$val){
        echo "Building $path/$key\n";
        if($key=="smtp_ticket1_subj"){
            @file_put_contents("$path/$key",$val);
            continue;
        }
        if($key=="smtp_ticket1_body"){
            @file_put_contents("$path/$key",$val);
            continue;
        }

        @file_put_contents("$path/$key",utf8_decode_switch($val));
    }
    $temlplateid=intval($ligne["templateid"]);
    if($temlplateid==0){$temlplateid=1;}
    $TEMPLATE=FORMAT_TEMPLATE($temlplateid);
    echo "Building $path/TEMPLATE\n";
    @file_put_contents("$path/TEMPLATE",$TEMPLATE);
}
function utf8_decode_switch($value):string{
    if(is_null($value)){
        return "";
    }
    if(PHP_MAJOR_VERSION>7) {
        return $value;
    }
    $tpl=new template_admin();
    return $tpl->utf8_decode($value);
}



function _out($text){
    $LOG_SEV = LOG_INFO;
    if (!function_exists("openlog")) {return false;}
    openlog("web-error-page", LOG_PID, LOG_SYSLOG);
    syslog($LOG_SEV, "[PHP]:[INFO] $text");
    closelog();
    return true;
}

function export_webfiltering_rules():bool{
    $q=new lib_sqlite("/home/artica/SQLITE/webfilter.db");
    $sql="SELECT ID,groupname FROM webfilter_rules WHERE enabled=1";
    $results = $q->QUERY_SQL($sql);
    if(!$results){
        _out("No Web filtering error rule found");
        return false;
    }
    foreach ($results as $index=>$ligne){
        $ID=$ligne["ID"];
        $fname="/home/artica/web_templates/webfilter.rule.$ID.name";
        @file_put_contents($fname,$ligne["groupname"]);
    }
    return true;
}
function imageinline($img_file):string{
    $imgData = base64_encode(file_get_contents($img_file));
    $src = 'data: '.mime_content_type($img_file).';base64,'.$imgData;
    return $src;
}
function jsinline($jsfile):string{
    if(!is_file($jsfile)){return "";}
    $Data = base64_encode(file_get_contents($jsfile));
    $src = "data:text/javascript;base64,$Data";
    return $src;
}
function fileinline($filename):string{
    $BASE_FILES="/usr/share/squid3/icons/tplfiles";
    if(preg_match("#\.(jpg|png|gif|webp|tiff|psd|raw|bmp|heif|indd|svg|ai|eps)$",strtolower($filename))){
        return imageinline("$BASE_FILES/$filename");
    }
    if(preg_match("#\.js$#",$filename)){
        return jsinline("$BASE_FILES/$filename");
    }
    return "";
}

function FORMAT_TEMPLATE($templateid,$scripts=null):string{
	$memcached=new lib_memcached();

	$template=$memcached->getKey("UFDB_FORMATED_TEMPLATE_$templateid");
	
	if($memcached->MemCachedFound){
		if(!$GLOBALS["VERBOSE"]){
			if(strlen($template)>100){if(strpos($template,"%CSS%")==0){return $template."</body></html>";}}
		}
	}
    $templates_manager=new templates_manager($templateid);
	$jquery="\n\n<script type=\"text/javascript\" language=\"javascript\" src=\"/js/jquery-1.8.3.js\"></script>\n<script type=\"text/javascript\" language=\"javascript\" src=\"/js/jquery-ui-1.8.22.custom.min.js\"></script>";
	$css="<!-- templateid $templateid -->\n<style type=\"text/css\">\n$templates_manager->CssContent\n</style>";

    $parser=new templates_objects();
    $templates_manager->headContent=$parser->ParseContent($templates_manager->headContent);
    $templates_manager->BodyContent=$parser->ParseContent($templates_manager->BodyContent);
	$template=$templates_manager->headContent."\n".$templates_manager->BodyContent;
	$memcached->saveKey("UFDB_FORMATED_TEMPLATE_$templateid", $template,300);
	$template=str_replace("%CSS%", $css, $template);
	$template=str_replace("%JQUERY%", $scripts."\n".$jquery, $template);
	return $template."</body></html>";
	
}

function ini_set_verbosedx(){
	ini_set('html_errors',0);
	ini_set('display_errors', 1);
	ini_set('error_reporting', E_ALL);
	ini_set('error_prepend_string','');
	ini_set('error_append_string','');
	$GLOBALS["VERBOSE"]=true;
	$GLOBALS["DEBUG_TEMPLATE"]=true;
}


