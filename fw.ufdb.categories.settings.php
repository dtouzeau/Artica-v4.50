<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.patch.tables.fw.inc");
include_once(dirname(__FILE__)."/ressources/class.categories.inc");
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}

if(isset($_GET["section-ftp-js"])){section_ftp_js();exit;}
if(isset($_GET["section-ftp-popup"])){section_ftp_popup();exit;}

if(isset($_GET["section-main-js"])){section_main_js();exit;}
if(isset($_GET["section-main-popup"])){section_main();exit;}



if(isset($_POST["UfdbCatsUploadFTPSchedule"])){save();exit;}

if(isset($_GET["table"])){table();exit;}
if(isset($_GET["tabs"])){tabs();exit;}

if(isset($_POST["PersonalCategoriesLimitBefore"])){save();exit;}
if(isset($_POST["UfdbCatsUpload"])){save();exit;}
if(isset($_GET["parameters"])){table();exit;}
if(isset($_GET["table1"])){table1();exit;}
page();


function page(){
	$page=CurrentPageName();
	$tpl=new template_admin();

	
	$html="
<div class=\"row border-bottom white-bg dashboard-header\">
	<div class=\"col-sm-12\"><h1 class=ng-binding>{your_categories} {parameters}</h1>
	<p>{personal_categories_explain}</p>
	
	</div>
	
</div>
			
                            
			
<div class='row'><div id='progress-categoryparams-restart'></div>
			<div class='ibox-content'>
       	
			 	<div id='table-perso-categoryparams-loader'></div>
                                    
			</div>
</div>
					
			
			
<script>
    $.address.state('/');
	$.address.value('/categories-parameters');
LoadAjax('table-perso-categoryparams-loader','$page?tabs=yes');
			
</script>";
	

	echo $tpl->_ENGINE_parse_body($html);
	
}
function section_ftp_js():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    return  $tpl->js_dialog("{create_repository}","$page?section-ftp-popup=yes");
}
function section_main_js(){
    $tpl=new template_admin();
    $page=CurrentPageName();
    return  $tpl->js_dialog("{parameters}","$page?section-main-popup=yes");

}
function section_js_form():string{
    $page=CurrentPageName();
    return "BootstrapDialog1.close();LoadAjaxSilent('categories-options-section','$page?table1=yes');";
}
function tabs(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$array["{main_parameters}"]="$page?parameters=yes";
	$array["{schedule}"]="fw.proxy.tasks.php?microstart=yes&ForceTaskType=3";
   // $array["{export_rules}"]="fw.categories.export.php";
	$array["{events}"]="fw.system.watchdog.php?microstart=yes&scriptname=exec.compile.categories.php,exec.update.tlse.internal.php,exec.upload.categories.php";

	echo $tpl->tabs_default($array);

}
function table(){
    $page=CurrentPageName();
    echo "<div id='categories-options-section'></div><script>LoadAjaxSilent('categories-options-section','$page?table1=yes');</script>";
}
function table1(){
    $tpl=new template_admin();
    $page=CurrentPageName();
    $UfdbCatsUpload=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UfdbCatsUpload"));
    $ManageOfficialsCategories=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ManageOfficialsCategories"));
    $HideOfficialsCategory=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HideOfficialsCategory"));
    $PersonalCategoriesIndexFrom=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("PersonalCategoriesIndexFrom"));
    if($PersonalCategoriesIndexFrom==0){$PersonalCategoriesIndexFrom=250;}

    $EnableCategoriesRESTFul=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableCategoriesRESTFul"));
    $CategoriesRESTFulAPIKey=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("CategoriesRESTFulAPIKey"));
    $CategoriesRESTFulAllowCreate=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("CategoriesRESTFulAllowCreate"));

    $PersonalCategoriesLimitBefore=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("PersonalCategoriesLimitBefore"));
    if($PersonalCategoriesLimitBefore==0){$PersonalCategoriesLimitBefore=60;}

    $durationL[1]="{no_limit}";
    $durationL[2]="2 {minutes}";
    $durationL[3]="3 {minutes}";
    $durationL[5]="5 {minutes}";
    $durationL[10]="10 {minutes}";
    $durationL[60]="1 {hour}";
    $durationL[120]="2 {hours}";
    $durationL[180]="3 {hours}";

    $tpl->table_form_field_js("Loadjs('$page?section-main-js=yes')");
    $tpl->table_form_field_text("{limit_compile_before}",$durationL[$PersonalCategoriesLimitBefore],ico_clock);

    if($ManageOfficialsCategories==0){
        $tpl->table_form_field_bool("{HideOfficialsCategory}",$HideOfficialsCategory,ico_archive);
    }

    $tpl->table_form_field_text("{first_index}",$PersonalCategoriesIndexFrom);

    if(!$GLOBALS["CLASS_SOCKETS"]->CORP_LICENSE()){
        $tpl->table_form_field_text("RESTFul (API)","{license_error}",ico_lock);
    }else{
        $tpl->table_form_field_bool("RESTFul (API)",$EnableCategoriesRESTFul,ico_lock);
    }



    $tpl->table_form_field_js("Loadjs('$page?section-ftp-js=yes')");
    if($UfdbCatsUpload==0){
        $tpl->table_form_field_bool("{create_repository}",0,ico_proto);
    }else{
        $durations[60]="1 {hour}";
        $durations[120]="2 {hours}";
        $durations[240]="4 {hours}";
        $durations[480]="8 {hours}";
        $durations[720]="12 {hours}";
        $durations[960]="16 {hours}";
        $durations[1440]="1 {day}";
        $durations[2880]="2 {days}";
        $durations[5760]="4 {days}";
        $durations[10080]="1 {week}";
        $durations[43200]="1 {month}";
        $UfdbCatsUploadFTPSchedule=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UfdbCatsUploadFTPSchedule"));
        $UfdbCatsUploadFTPserv=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UfdbCatsUploadFTPserv"));
        $UfdbCatsUploadFTPusr=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UfdbCatsUploadFTPusr"));
        $tpl->table_form_field_text("{create_repository}","{each} $durations[$UfdbCatsUploadFTPSchedule] {minutes} $UfdbCatsUploadFTPusr@$UfdbCatsUploadFTPserv",ico_proto);
    }


    $TINY_ARRAY["TITLE"]="{your_categories} {parameters}";
    $TINY_ARRAY["ICO"]="fad fa-tools";
    $TINY_ARRAY["EXPL"]="{personal_categories_explain}";
    $TINY_ARRAY["BUTTONS"]=null;
    $jstiny="Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";
    echo "<div style='margin-top:20px'>";
    echo $tpl->table_form_compile();
    echo "</div>";
    echo "<script>$jstiny</script>";

}
function section_ftp_popup(){
    $tpl=new template_admin();
    $page=CurrentPageName();
    $UfdbCatsUpload=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UfdbCatsUpload"));
    $UfdbCatsUploadFTPserv=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UfdbCatsUploadFTPserv"));
    $UfdbCatsUploadFTPusr=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UfdbCatsUploadFTPusr"));
    $UfdbCatsUploadFTPpass=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UfdbCatsUploadFTPpass"));
    $UfdbCatsUploadFTPDir=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UfdbCatsUploadFTPDir"));
    $UfdbCatsUploadFTPSchedule=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UfdbCatsUploadFTPSchedule"));
    $UfdbCatsUploadFTPPassive=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UfdbCatsUploadFTPPassive"));
    $UfdbCatsUploadFTPTLS=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UfdbCatsUploadFTPTLS"));

    $durations[60]="1 {hour}";
    $durations[120]="2 {hours}";
    $durations[240]="4 {hours}";
    $durations[480]="8 {hours}";
    $durations[720]="12 {hours}";
    $durations[960]="16 {hours}";
    $durations[1440]="1 {day}";
    $durations[2880]="2 {days}";
    $durations[5760]="4 {days}";
    $durations[10080]="1 {week}";
    $durations[43200]="1 {month}";



    $form[]=$tpl->field_checkbox("UfdbCatsUpload","{enable_feature}",$UfdbCatsUpload,false,"{UfdbCatsUpload_explain}");
    $array["FTP_SERVER"]["KEY"]="UfdbCatsUploadFTPserv";
    $array["FTP_SERVER"]["VALUE"]="$UfdbCatsUploadFTPserv";
    $array["FTP_PASSIVE"]["KEY"]="UfdbCatsUploadFTPPassive";
    $array["FTP_PASSIVE"]["VALUE"]="$UfdbCatsUploadFTPPassive";
    $array["TLS"]["KEY"]="UfdbCatsUploadFTPTLS";
    $array["TLS"]["VALUE"]="$UfdbCatsUploadFTPTLS";
    $array["TARGET_DIR"]["KEY"]="UfdbCatsUploadFTPDir";
    $array["TARGET_DIR"]["VALUE"]="$UfdbCatsUploadFTPDir";
    $array["USERNAME"]["KEY"]="UfdbCatsUploadFTPusr";
    $array["USERNAME"]["VALUE"]="$UfdbCatsUploadFTPusr";
    $array["PASSWORD"]["KEY"]="UfdbCatsUploadFTPpass";
    $array["PASSWORD"]["VALUE"]="$UfdbCatsUploadFTPpass";

    $form[]=$tpl->field_ftp_params($array);
    $form[]=$tpl->field_array_hash($durations,"UfdbCatsUploadFTPSchedule", "{interval}", $UfdbCatsUploadFTPSchedule);
    $html[]=$tpl->form_outside(null, @implode("\n", $form),null,"{apply}",section_js_form(),"AsDansGuardianAdministrator");
    echo $tpl->_ENGINE_parse_body($html);


}
function section_main(){
	$tpl=new template_admin();
	$users=new usersMenus();
	$UfdbCatsUploadFTPSchedule=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UfdbCatsUploadFTPSchedule"));
	$EnableCategoriesRESTFul=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableCategoriesRESTFul"));
	$CategoriesRESTFulAPIKey=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("CategoriesRESTFulAPIKey"));
    $CategoriesRESTFulAllowCreate=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("CategoriesRESTFulAllowCreate"));

    $HideOfficialsCategory=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HideOfficialsCategory"));
	$ManageOfficialsCategories=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ManageOfficialsCategories"));

	$PersonalCategoriesIndexFrom=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("PersonalCategoriesIndexFrom"));
	if($PersonalCategoriesIndexFrom==0){$PersonalCategoriesIndexFrom=250;}
	
	if($CategoriesRESTFulAPIKey==null){$CategoriesRESTFulAPIKey=enable_restful_str(64);}
	
	if($UfdbCatsUploadFTPSchedule==0){$UfdbCatsUploadFTPSchedule=240;}
	
	$durations[60]="1 {hour}";
	$durations[120]="2 {hours}";
	$durations[240]="4 {hours}";
	$durations[480]="8 {hours}";
	$durations[720]="12 {hours}";
	$durations[960]="16 {hours}";
	$durations[1440]="1 {day}";
	$durations[2880]="2 {days}";
	$durations[5760]="4 {days}";
	$durations[10080]="1 {week}";
	$durations[43200]="1 {month}";

    $PersonalCategoriesLimitBefore=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("PersonalCategoriesLimitBefore"));
    if($PersonalCategoriesLimitBefore==0){$PersonalCategoriesLimitBefore=60;}

    $durationL[1]="{no_limit}";
    $durationL[3]="3 {minutes}";
    $durationL[5]="5 {minutes}";
    $durationL[10]="10 {minutes}";
    $durationL[60]="1 {hour}";
    $durationL[120]="2 {hours}";
    $durationL[180]="3 {hours}";



    $form[]=$tpl->field_array_hash($durationL,"PersonalCategoriesLimitBefore", "{limit_compile_before}", $PersonalCategoriesLimitBefore);

	if($ManageOfficialsCategories==0){
		$form[]=$tpl->field_checkbox("HideOfficialsCategory","{HideOfficialsCategory}",$HideOfficialsCategory,false,"");
    }

	$form[]=$tpl->field_numeric("PersonalCategoriesIndexFrom","{first_index}",$PersonalCategoriesIndexFrom);
	
	
	if($users->CORP_LICENSE){
        $form[]=$tpl->field_section("RESTFul");
        $form[]=$tpl->field_checkbox("EnableCategoriesRESTFul","RESTFul (API)",$EnableCategoriesRESTFul,"CategoriesRESTFulAPIKey,CategoriesRESTFulAllowCreate","");
        $form[]=$tpl->field_text("CategoriesRESTFulAPIKey", "{API_KEY}", $CategoriesRESTFulAPIKey);
        $form[]=$tpl->field_checkbox("CategoriesRESTFulAllowCreate","{allow_create_new_categories}",$CategoriesRESTFulAllowCreate,false,"");
	}


    $html[]=$tpl->form_outside(null, @implode("\n", $form),null,"{apply}",section_js_form(),"AsDansGuardianAdministrator");
    echo $tpl->_ENGINE_parse_body($html);

	





}
function save(){
	$tpl=new template_admin();
	$tpl->CLEAN_POST();



    if(isset($_POST["PersonalCategoriesIndexFrom"])) {
        $PersonalCategoriesIndexFromORG=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("PersonalCategoriesIndexFrom"));
        $PersonalCategoriesIndexFrom = $_POST["PersonalCategoriesIndexFrom"];
        $PersonalCategoriesIndexPartners = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("PersonalCategoriesIndexPartners"));

        if($PersonalCategoriesIndexPartners==0){
            if($PersonalCategoriesIndexFrom>4999){
                echo "jserror:Index start from 5000 is banned.\n";
                $GLOBALS["CLASS_SOCKETS"]->SET_INFO("PersonalCategoriesIndexFrom",250);
                return;
            }
        }

        if($PersonalCategoriesIndexFrom<>$PersonalCategoriesIndexFromORG){
            $GLOBALS["CLASS_SOCKETS"]->SET_INFO("PersonalCategoriesIndexFrom",$PersonalCategoriesIndexFrom);
            $GLOBALS["CLASS_SOCKETS"]->getFrameWork("ufdbguard.php?reindexes-catz=yes");
        }

    }
    foreach ($_POST as $key=>$val){$GLOBALS["CLASS_SOCKETS"]->SET_INFO($key, $val);}
    $GLOBALS["CLASS_SOCKETS"]->getFrameWork("ufdbguard.php?UfdbCatsUpload=yes");
	
}
function enable_restful_str($length, $keyspace = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ'){
	$pieces = array();
	$max = mb_strlen($keyspace, '8bit') - 1;
	for ($i = 0; $i < $length; ++$i) {
		$pieces []= $keyspace[random_int(0, $max)];
	}
	return implode('', $pieces);
}