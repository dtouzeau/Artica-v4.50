<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
include_once(dirname(__FILE__)."/ressources/class.nginx.params.inc");
include_once(dirname(__FILE__)."/ressources/class.sockets.inc");
$GLOBALS["CLASS_SOCKETS"]=new sockets();

if(isset($_GET["ruleid"])){serviceid_js();exit;}
if(isset($_GET["tabs"])){tabs();exit;}
if(isset($_POST["ID"])){Save();exit;}
if(isset($_GET["main"])){page();exit;}
if(isset($_GET["engine"])){engine();exit;}
page();


function serviceid_js(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $ID=intval($_GET["ruleid"]);
    $function=$_GET["function"];
    $servicename=null;
    if($ID==0){
        $servicename="{new_mirror_site}";
    }else{
        $q      = new lib_sqlite("/home/artica/SQLITE/nginx.db");
        $ligne=$q->mysqli_fetch_array("SELECT enforceuri FROM httrack_sites WHERE ID=$ID");
        $servicename=trim($ligne["enforceuri"]);
    }

    $tpl->js_dialog1("modal:#$ID - $servicename", "$page?tabs=$ID&function=$function",900);

}
function tabs(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $ID=intval($_GET["tabs"]);
    $function=$_GET["function"];

    $array["{website}"] = "$page?main=$ID&function=$function";
    if($ID>0) {
        $array["{HTTP_ENGINE}"] = "$page?engine=$ID&function=$function";
    }
    echo $tpl->tabs_default($array);

}
function page():bool{
    $tpl    = new template_admin();
    $q      = new lib_sqlite("/home/artica/SQLITE/nginx.db");
    $id=intval($_GET["main"]);
    $ligne=$q->mysqli_fetch_array("SELECT * FROM httrack_sites WHERE ID=$id");
    $function=$_GET["function"];
    $HostHeader=null;
    $btn="{add}";
    $enabled=1;
    $enforceuri="http://articatech.net";
    $schedule=6;
    $maxsitesize=5000;
    if($id>0){
        $btn="{apply}";
        $maxsitesize=$ligne["maxsitesize"];
        $enabled=intval($ligne["enabled"]);
        $HostHeader=trim($ligne["HostHeader"]);
        $exclude=trim($ligne["exclude"]);
        $enforceuri=trim($ligne["enforceuri"]);
        $schedule=intval($ligne["schedule"]);
    }

    if(!is_numeric($maxsitesize)){$maxsitesize=5000;}


    for($i=1;$i<24;$i++){
        $ht="{hours}";
        if($i<10){$ht="{hour}";}
        $H[$i]="{every} $i $ht";
    }

    $tpl->field_hidden("ID",$id);
    $form[]=$tpl->field_checkbox("enabled","{enabled}",$enabled,true);
    $form[]=$tpl->field_text("enforceuri","{enforce_fetch_uri}",$enforceuri,false,"{enforce_fetch_uri_explain}");
    $form[]=$tpl->field_text("HostHeader","{HostHeader}",$HostHeader);
    $form[]=$tpl->field_array_hash($H,"schedule","{synchronize_each}",$schedule);
    $form[]=$tpl->field_numeric("maxsitesize","{max_size_download} (KB)",$maxsitesize);
    echo $tpl->form_outside("{WebCopy_task}", $form,"{WebCopy_task_explain}",$btn,"$function();dialogInstance1.close();","AsSystemWebMaster");
    return true;

}

function engine(){
    $function=$_GET["function"];
    $tpl    = new template_admin();
    $q      = new lib_sqlite("/home/artica/SQLITE/nginx.db");
    $id=intval($_GET["engine"]);
    $ligne=$q->mysqli_fetch_array("SELECT * FROM httrack_sites WHERE ID=$id");
    $btn="{apply}";
    $AddHeader=trim($ligne["AddHeader"]);
    $useproxy=intval($ligne["useproxy"]);
    $maxfilesize=$ligne["maxfilesize"];
    $maxworkingdir=$ligne["maxworkingdir"];
    $maxextern=intval($ligne["maxextern"]);
    $UserAgent = trim($ligne["UserAgent"]);
    $exclude = trim($ligne["exclude"]);

    if($UserAgent==null){$UserAgent="Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/96.0.4664.45 Safari/537.36";}

    $minrate=$ligne["minrate"];
    if($exclude==null){$exclude="*.gz,*.zip,*.exe,*.iso,*.nrg,*.pdf";}
    if(!is_numeric($minrate)){$minrate=512;}
    if(!is_numeric($maxfilesize)){$maxfilesize=512;}
    if(!is_numeric($maxworkingdir)){$maxworkingdir=20;}
    $tpl->field_hidden("ID",$id);
    $form[]=$tpl->field_text("exclude","{exclude}",$exclude);
    $form[]=$tpl->field_numeric("minrate","{MaxRateBw} (KB/s)",$minrate);
    $form[]=$tpl->field_numeric("maxfilesize","{maxfilesize} (KB)",$maxfilesize);
    $form[]=$tpl->field_numeric("maxworkingdir","{maxsitesize} (MB)",$maxworkingdir);
    $form[]=$tpl->field_numeric("maxextern","{maxextern}",$maxextern,"{maxextern_explain}");

    $form[]=$tpl->field_checkbox("useproxy","{use_local_proxy}",$useproxy);
    $form[]=$tpl->field_text("UserAgent","{http_user_agent}",$UserAgent);
    $form[]=$tpl->field_text("AddHeader","{add_header}",$AddHeader);

    echo $tpl->form_outside("{WebCopy_task}", $form,null,$btn,"$function();dialogInstance1.close();","AsSystemWebMaster");
    return true;

}

function Save(){

    $q      = new lib_sqlite("/home/artica/SQLITE/nginx.db");
    $sql="CREATE TABLE IF NOT EXISTS `httrack_sites` ( `ID` INTEGER PRIMARY KEY AUTOINCREMENT, `enabled` INTEGER NOT NULL DEFAULT 0 , `serviceid` INTEGER NOT NULL DEFAULT 0 , `size` INTEGER DEFAULT '0', `minrate` INTEGER NOT NULL DEFAULT '512', `maxfilesize` INTEGER NOT NULL DEFAULT '512', `maxsitesize` INTEGER NOT NULL DEFAULT '5000', `maxworkingdir` INTEGER NOT NULL DEFAULT '20',`UserAgent` TEXT NULL )";

    if(!$q->FIELD_EXISTS("httrack_sites","schedule")){
        $q->QUERY_SQL("ALTER TABLE httrack_sites ADD schedule INTEGER NOT NULL DEFAULT 6");
    }

    if(!$q->FIELD_EXISTS("httrack_sites","UserAgent")){
        $q->QUERY_SQL("ALTER TABLE httrack_sites ADD UserAgent TEXT NULL");
    }
    if(!$q->FIELD_EXISTS("httrack_sites","HostHeader")){
        $q->QUERY_SQL("ALTER TABLE httrack_sites ADD HostHeader TEXT NULL");
    }
    if(!$q->FIELD_EXISTS("httrack_sites","AddHeader")){
        $q->QUERY_SQL("ALTER TABLE httrack_sites ADD AddHeader TEXT NULL");
    }
    if(!$q->FIELD_EXISTS("httrack_sites","exclude")){
        $q->QUERY_SQL("ALTER TABLE httrack_sites ADD exclude TEXT NULL");
    }
    if(!$q->FIELD_EXISTS("httrack_sites","enforceuri")){
        $q->QUERY_SQL("ALTER TABLE httrack_sites ADD enforceuri TEXT NULL");
    }
    if(!$q->FIELD_EXISTS("httrack_sites","useproxy")){
        $q->QUERY_SQL("ALTER TABLE httrack_sites ADD useproxy INTEGER NOT NULL DEFAULT 0");
    }
    if(!$q->FIELD_EXISTS("httrack_sites","maxextern")){
        $q->QUERY_SQL("ALTER TABLE httrack_sites ADD maxextern INTEGER NOT NULL DEFAULT 0");
    }
    if(!$q->FIELD_EXISTS("httrack_sites","actiondel")){
        $q->QUERY_SQL("ALTER TABLE httrack_sites ADD actiondel INTEGER NOT NULL DEFAULT 0");
    }
    if(!$q->FIELD_EXISTS("httrack_sites","notfound")){
        $q->QUERY_SQL("ALTER TABLE httrack_sites ADD notfound TEXT NULL");
    }

    $q->QUERY_SQL($sql);

    $edit=array();
    $log=array();
    $insert_value=array();
    $insert_key=array();
    $tpl=new template_admin();
    $tpl->CLEAN_POST();
    $ID=intval($_POST["ID"]);

    if(isset($_POST["enforceuri"])) {
        $_POST["enforceuri"] = trim($_POST["enforceuri"]);
        if ($_POST["enforceuri"] <> null) {
            if (!preg_match("#^(http|https|ftp|ftps):\/#i", $_POST["enforceuri"])) {
                echo $tpl->post_error("{$_POST["enforceuri"]} {wrong_value}");
                return false;
            }
        }
    }


    foreach ($_POST as $key=>$value){
        if($key=="ID"){continue;}
        $insert_value[]="'$value'";
        $insert_key[]="`$key`";
        $edit[]="`$key`='$value'";
        $log[]="$key:$value";

    }

    if($ID==0){
        $sql="INSERT INTO httrack_sites (".@implode(",",$insert_key).") VALUES (".@implode(",",$insert_value).")";
    }else{
        $sql="UPDATE httrack_sites SET ".@implode(",",$edit)." WHERE ID=$ID";
    }

    $q->QUERY_SQL($sql);
    if(!$q->ok){$tpl->post_error($q->mysql_error);return false;}
    admin_tracks("Editing WebCopy Service for {$_POST["enforceuri"]} with ".@implode(" ",$log));
    //$GLOBALS["CLASS_SOCKETS"]->getFrameWork("nginx.php?webcopy-sync=$serviceid");
    return true;
}