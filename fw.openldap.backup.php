<?php
// Service Pack 9 2026-10-10
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.system.network.inc");
include_once(dirname(__FILE__)."/ressources/class.squid.inc");
// awc_table_style() / awc_pager_js(): feuille de style et pager partages des tableaux
// d'entites. Include INCONDITIONNEL, au premier niveau: place dans le bootstrap
// if(!class_exists("sockets")) de la ligne 2 il ne s'executerait jamais (class.sockets.inc
// est deja chargee par class.template-admin.inc) et la page mourrait sur
// awc_table_style() indefinie -- un spinner qui ne s'arrete plus.
include_once(dirname(__FILE__)."/ressources/class.statistics_appliance.inc");
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(isset($_GET["table-start"])){table_start();exit;}
if(isset($_GET["table"])){table();exit;}
if(isset($_GET["tabs"])){tabs();exit;}
if(isset($_GET["file-uploaded"])){file_uploaded();exit;}
if(isset($_GET["params-js"])){params_js();exit;}
if(isset($_POST["restore"])){exit;}
if(isset($_GET["restore-js"])){restore_js();exit;}
if(isset($_GET["restore-popup"])){restore_popup();exit;}
if(isset($_GET["params"])){params();exit;}
if(isset($_POST["OpenDLAPBackupMaxContainers"])){params_save();exit;}

if(isset($_GET["remove"])){remove_js();exit;}
if(isset($_POST["remove"])){remove_perform();exit;}
if(isset($_GET["download"])){download();exit;}
page();

function tabs(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $array["{containers}"]="$page?table-start=yes";
    $array["{schedule}"]="fw.system.tasks.php?sub-main=yes&ForceTaskType=84";
    echo $tpl->tabs_default($array);

}
function remove_js(){
    $tpl=new template_admin();
    $filename=$_GET["remove"];
    // js_confirm_delete() encode son texte en base64 mais le rend en HTML dans
    // la boite de dialogue: un nom forge s'y executait. Aucun nom legitime ne
    // peut echouer ici -- voir openldap_backup_valid().
    if(!openldap_backup_valid($filename)){return;}
    $md=preg_replace('/[^a-f0-9]/','',(string)($_GET["md"] ?? ""));
    $tpl->js_confirm_delete($filename,"remove",$filename,"$('#$md').remove()");

}
function restore_js(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $filename=$_GET["restore-js"];

    // Vide = le bouton « importer une sauvegarde », qui ouvre le formulaire
    // d'envoi de fichier; tout autre nom doit etre un conteneur reel, sinon il
    // part dans le titre de la boite de dialogue et dans une URL.
    if($filename<>null && !openldap_backup_valid($filename)){return;}

    if($filename<>null){
        if(!isset($_GET["confirm"])) {
            $jsAfter = "Loadjs('$page?restore-js=$filename&confirm=yes');";
            $tpl->js_dialog_confirm_action("{restore} $filename", "restore", "$filename", $jsAfter);
            return;
        }
    }

    $tpl->js_dialog1("{restore} {container} $filename","$page?restore-popup=$filename",550);

}
function restore_popup():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $filename=$_GET["restore-popup"];
    $html[]="<div id='restore-container-upload'></div>";

    if($filename==null){
        $html[]=$tpl->div_explain("{restore_ldap_container_explain}");
        $html[]="<div class='center' style='margin:30px'>";
        $html[]=$tpl->form_button_upload("{upload}",$page,null,"AsSystemAdministrator");
        $html[]="</center>";
    }else{
        $fname=urlencode($filename);
        $jsrestart= $tpl->framework_buildjs(
            "/openldap/backup/import/$fname","openldap.backup.progress","openldap.backup.progress.txt",
            "restore-container-upload","LoadAjax('ldap-containers','$page?table=yes');dialogInstance1.close();");
        $html[]="<script>$jsrestart;</script>";
    }

    echo $tpl->_ENGINE_parse_body($html);
    return true;
}

function file_uploaded():bool{
    if(!fw_progress_page_priv("AsSystemAdministrator")){return false;}   // task link signed for this session: built only for whom may run it
    header("content-type: application/x-javascript");
    $page=CurrentPageName();
    $file=urlencode($_GET["file-uploaded"]);
    $tpl=new template_admin();

    echo $tpl->framework_buildjs(
        "/openldap/backup/import/$file","openldap.backup.progress","openldap.backup.progress.txt",
        "restore-container-upload","LoadAjax('ldap-containers','$page?table=yes');dialogInstance1.close();"
    );
    return true;
}


function params_js(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $tpl->js_dialog1("{backup_ldap} {parameters}","$page?params=yes",800);
}
function params_save(){
    $tpl=new template_admin();
    $tpl->SAVE_POSTs();
}

function params(){
    $tpl=new template_admin();
    $OpenDLAPBackupMaxContainers=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("OpenDLAPBackupMaxContainers"));
    if($OpenDLAPBackupMaxContainers==0){$OpenDLAPBackupMaxContainers=30;}


    $OpenLDAPBackUpload=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("OpenLDAPBackUpload"));
    $OpenLDAPBackUploadFTPserv=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("OpenLDAPBackUploadFTPserv"));
    $OpenLDAPBackUploadFTPusr=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("OpenLDAPBackUploadFTPusr"));
    $OpenLDAPBackUploadFTPpass=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("OpenLDAPBackUploadFTPpass"));
    $OpenLDAPBackUploadFTPDir=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("OpenLDAPBackUploadFTPDir"));
    $OpenLDAPBackUploadFTPPassive=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("OpenLDAPBackUploadFTPPassive"));
    $OpenLDAPBackUploadFTPTLS=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("OpenLDAPBackUploadFTPTLS"));


    $array["FTP_SERVER"]["KEY"]="OpenLDAPBackUploadFTPserv";
    $array["FTP_SERVER"]["VALUE"]="$OpenLDAPBackUploadFTPserv";
    $array["FTP_PASSIVE"]["KEY"]="OpenLDAPBackUploadFTPPassive";
    $array["FTP_PASSIVE"]["VALUE"]="$OpenLDAPBackUploadFTPPassive";
    $array["TLS"]["KEY"]="OpenLDAPBackUploadFTPTLS";
    $array["TLS"]["VALUE"]="$OpenLDAPBackUploadFTPTLS";
    $array["TARGET_DIR"]["KEY"]="OpenLDAPBackUploadFTPDir";
    $array["TARGET_DIR"]["VALUE"]="$OpenLDAPBackUploadFTPDir";
    $array["USERNAME"]["KEY"]="OpenLDAPBackUploadFTPusr";
    $array["USERNAME"]["VALUE"]="$OpenLDAPBackUploadFTPusr";
    $array["PASSWORD"]["KEY"]="OpenLDAPBackUploadFTPpass";
    $array["PASSWORD"]["VALUE"]="$OpenLDAPBackUploadFTPpass";

    $form[]=$tpl->field_numeric("OpenDLAPBackupMaxContainers","{max_containers}",$OpenDLAPBackupMaxContainers);
    $form[]=$tpl->field_checkbox("OpenLDAPBackUpload","{enable_FTP_backup}",$OpenLDAPBackUpload);
    $form[]=$tpl->field_ftp_params($array);

    $html[]=$tpl->form_outside(null, @implode("\n", $form),null,"{apply}","blur()","AsSystemAdministrator");
    echo $tpl->_ENGINE_parse_body(@implode("\n", $html) );



}

/**
 * Le repertoire des conteneurs. Un seul point de verite: la page le parcourt,
 * y telecharge et y supprime.
 */
function openldap_backup_dir(){
    return "/home/artica/ldap_backup";
}

/**
 * Un nom de conteneur valide.
 *
 * C'est EXACTEMENT le motif que le listing exige deja (`^([0-9]+)\.gz$`, la
 * date epoch de la sauvegarde): aucun nom legitime ne peut echouer ici. Les
 * deux points d'entree qui recevaient un nom sans le verifier ouvraient un
 * parcours de repertoire, chacun dans un sens:
 *
 *   - download()       : "?download=../../../etc/shadow" faisait renvoyer
 *                        n'importe quel fichier lisible par www-data, en piece
 *                        jointe;
 *   - remove_perform() : le nom partait tel quel dans framework/openldap.php,
 *                        dont BACKUP_REMOVE() fait un @unlink() sans controle
 *                        -- soit une suppression de fichier arbitraire.
 *
 * La validation se fait ICI, dans la page, parce que c'est la que la valeur
 * entre. Elle est desormais DOUBLEE cote daemon: la suppression passe par
 * DELETE /openldap/backup/{filename}, dont le handler applique
 * safeContainerName() et resout le chemin dans le seul repertoire de
 * sauvegardes. Le controle de la page seule ne protegeait que les appels
 * venant de la page -- le point d'entree du framework, lui, restait joignable
 * directement.
 */
function openldap_backup_valid($filename){
    return preg_match("#^[0-9]+\\.gz$#",(string)$filename)==1;
}

function remove_perform(){
    $tpl=new template_admin();
    $filename=$_POST["remove"];
    if(!openldap_backup_valid($filename)){
        echo $tpl->post_error("{invalid}");
        return;
    }
    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API_DELETE("/openldap/backup/".urlencode($filename)));
    if(!is_object($json) || !isset($json->Status) || !$json->Status){
        $error="{failed}";
        if(is_object($json) && isset($json->Error)){$error=$json->Error;}
        echo $tpl->post_error($error);
        return;
    }
}

function page(){

    $page=CurrentPageName();
    $tpl=new template_admin();

    $html=$tpl->page_header("{backup_ldap}","fad fa-file-archive","{backup_ldap_explain}",
        "$page?tabs=yes","ldap-backup","progress-openldapbackup-restart",false,"table-loader-openldap-backup");



	if(isset($_GET["main-page"])){
        $tpl=new template_admin("{backup_ldap}",$html);
		echo $tpl->build_firewall();
		return;
	}
	echo $tpl->_ENGINE_parse_body($html);

}

function table_start(){
    $page=CurrentPageName();
    echo "<div id='ldap-containers' style='margin-top:20px'></div><script>LoadAjax('ldap-containers','$page?table=yes')</script>";
}

function table(){
	$page=CurrentPageName();
	$tpl=new template_admin();

    $jsrestart=$tpl->framework_buildjs("/openldap/backup/now","openldap.backup.progress",
        "openldap.backup.progress.txt","progress-openldapbackup-restart","LoadAjax('ldap-containers','$page?table=yes');");

    $target_directory=openldap_backup_dir();
    $dir_handle = @opendir($target_directory);
    $MAIN=array();
    if($dir_handle) {
        while ($file = readdir($dir_handle)) {
            if ($file == '.') {
                continue;
            }
            if ($file == '..') {
                continue;
            }
            if (!is_file("$target_directory/$file")) {
                continue;
            }
            if (!preg_match("#^([0-9]+)\.gz$#", $file, $re)) {
                continue;
            }
            $stime = $re[1];
            $MAIN[$stime] = $file;
        }
        closedir($dir_handle);
    }

    krsort($MAIN);

    $rows=array();
    $totalSize=0;
    foreach ($MAIN as $stime=>$file){
        $size=intval(@filesize("$target_directory/$file"));
        $totalSize+=$size;
        $rows[]=openldap_backup_row($stime,$file,$size,$tpl,$page);
    }
    $c=count($rows);

    $html=array();
    $html[]="<div class='awc-wrap'><div class='awc-card'><div class='awc-scroll'><table class='awc-tbl'>";
    $html[]="<thead><tr>";
    $html[]="<th>{containers}</th>";
    $html[]="<th>{createdate}</th>";
    $html[]="<th class='center'>{size}</th>";
    $html[]="<th style='text-align:right'>{actions}</th>";
    $html[]="</tr></thead><tbody id='oldapbk-rows'>";
    if($c==0){
        $html[]="<tr><td colspan='4'><div class='awc-empty'>{no_data}</div></td></tr>";
    }else{
        $html[]=@implode("\n",$rows);
    }
    $html[]="</tbody></table></div>";
    // Le pied porte le cumul: sur une liste de conteneurs bornee par
    // {max_containers}, « combien de place cela prend » est la question que le
    // detail ligne a ligne ne repond pas.
    $html[]="<div class='awc-foot'>";
    $html[]="<span>{showing} $c {items}</span>";
    $html[]="<span>{total}: <strong>".FormatBytes($totalSize/1024)."</strong></span>";
    $html[]="<span class='awc-pg' id='oldapbk-pager'></span>";
    $html[]="</div></div></div>";

    $topbuttons=array();
    $topbuttons[] = array("Loadjs('$page?params-js=yes');", ico_params, "{parameters}");
    $topbuttons[] = array($jsrestart, ico_save, "{backup_now}");
    $topbuttons[] = array("Loadjs('$page?restore-js=')", ico_import, "{import}:{backup}");
    $added="";
    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/openldap/backup/instant"));

    if(is_object($json) && isset($json->Status) && $json->Status) {
        $size=$json->filesize;
        $xTime=$json->filetime;
        $distance=distanceOfTimeInWords($xTime,time());
        $added="<br><i class='font-bold'>InstantLDAPBackup: ".FormatBytes($size/1024)." {since} $distance</i>";
    }

    $TINY_ARRAY=array();
    $TINY_ARRAY["TITLE"]="{backup_ldap}";
    $TINY_ARRAY["ICO"]="fad fa-file-archive";
    $TINY_ARRAY["EXPL"]="{backup_ldap_explain}$added";
    $TINY_ARRAY["BUTTONS"]=$tpl->table_buttons($topbuttons);
    $headsjs= "Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";

    // Le <script> est concatene APRES l'analyse: la regex #{(.+?)}# de
    // _ENGINE_parse_body prendrait chaque accolade JavaScript pour un {token}.
    $script="<script>NoSpinner();\n".$tpl->ICON_SCRIPTS_COMPILE()."\n$headsjs\n</script>";

	echo awc_table_style()
	    .$tpl->_ENGINE_parse_body(@implode("\n", $html))
	    .$script
	    .awc_pager_js($GLOBALS["FOOTABLE_PSIZE"],"oldapbk-rows","oldapbk-pager");
}

/**
 * Un conteneur de sauvegarde.
 *
 * L'id md5 de la <tr> est CONSERVE: remove_js() cable son js_confirm_delete sur
 * $('#<md>').remove() pour retirer la ligne sans recharger le tableau.
 *
 * L'age relatif passe en sous-titre du nom: un nom de fichier qui est une date
 * epoch ne dit rien a l'oeil, et « la derniere sauvegarde date-t-elle d'hier ou
 * de trois mois » est la seule question que l'on vient poser a cet ecran.
 */
function openldap_backup_row($stime,$file,$size,$tpl,$page):string{
    $md = md5(serialize($file));
    // time_to_date() renvoie du texte UTF-8 simple: l'echapper ici est correct.
    $date=htmlspecialchars($tpl->time_to_date($stime,true));
    $age=distanceOfTimeInWords(intval($stime),time());
    $fenc=urlencode($file);

    $item=$tpl->td_href(htmlspecialchars($file),"{download}","s_PopUp('$page?download=$fenc',0,0);");
    $restore=$tpl->icon_restore("Loadjs('$page?restore-js=$fenc');","AsSystemAdministrator");
    $remove=$tpl->icon_delete("Loadjs('$page?remove=$fenc&md=$md')","AsSystemAdministrator");

    $h ="<tr id='$md'>";
    $h.="<td><div class='awc-ident'>";
    $h.="<span class='awc-ico'><i class='fad fa-file-archive'></i></span>";
    $h.="<span><span class='awc-name'>$item</span>";
    $h.="<div class='awc-meta'>{since} $age</div>";
    $h.="</span></div></td>";
    $h.="<td class='awc-mono'>$date</td>";
    $h.="<td class='center'><span class='awc-seen'>".FormatBytes($size/1024)."</span></td>";
    $h.="<td><div class='awc-acts'>$restore$remove</div></td>";
    $h.="</tr>";
    return $h;
}

function download(){
    $target_directory=openldap_backup_dir();
    $filename=$_GET["download"];

    // Sans ce controle, "?download=../../../etc/shadow" renvoyait le fichier.
    if(!openldap_backup_valid($filename)){
        header("HTTP/1.1 404 Not Found");
        return;
    }

    $filepath="$target_directory/$filename";
    $fsize=@filesize($filepath);


    if(!$GLOBALS["VERBOSE"]){
        header('Content-type: application/x-tar');
        header('Content-Transfer-Encoding: binary');
        header("Content-Disposition: attachment; filename=\"{$filename}\"");
        header("Cache-Control: no-cache, must-revalidate"); // HTTP/1.1
        header("Expires: Sat, 26 Jul 1997 05:00:00 GMT"); // Date dans le passé
        header("Content-Length: ".$fsize);
        ob_clean();
        flush();
    }
    if(is_file($filepath)){
        readfile($filepath);
    }
}


function save(){
	$tpl=new template_admin();
	$tpl->CLEAN_POST();	
	unset($_POST["suffix"]);
	$tpl->SAVE_POSTs();
	
}

