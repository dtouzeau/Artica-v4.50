<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
include_once(dirname(__FILE__)."/ressources/class.sockets.inc");
$GLOBALS["CLASS_SOCKETS"]=new sockets();

if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(isset($_GET["popup"])){popup();exit;}
if(isset($_GET["operation"])){operation();exit;}
if(isset($_GET["remove-folder"])){remove_folder();exit;}
if(isset($_POST["remove-folder"])){remove_folder_perform();exit;}
if(isset($_GET["add-folder"])){add_folder_js();exit;}
if(isset($_POST["add-folder"])){add_folder_perform();exit;}


js();


function remove_folder():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $folder=$_GET["remove-folder"];
    $function=$_GET["function"];
    return $tpl->js_confirm_delete($folder, "remove-folder","$folder","$function()");
}
function add_folder_js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $folder=$_GET["add-folder"];
    $function=$_GET["function"];
    return $tpl->js_prompt("{new_directory}", $folder,ico_plus,$page,"add-folder","$function()","",$folder);
}
/**
 * Ask articarest to create a directory.
 * REST: GET /system/createfolder/<urlencoded path> -> {Status,Info,Error}
 * @return array array("ok"=>bool,"error"=>string)
 */
function api_create_directory($directory):array{
    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/system/createfolder/".urlencode($directory)));
    if(!is_object($json)){
        VERBOSE("api_create_directory: no answer for [$directory]",__LINE__);
        return array("ok"=>false,"error"=>"Unable to create $directory: no answer from the API");
    }
    if(!isset($json->Status) or !$json->Status){
        $error="";
        if(isset($json->Error)){$error=trim(strval($json->Error));}
        if(strlen($error)==0){$error="Unable to create $directory";}
        VERBOSE("api_create_directory: [$directory] -> $error",__LINE__);
        return array("ok"=>false,"error"=>$error);
    }
    admin_tracks("Create a new directory $directory");
    return array("ok"=>true,"error"=>null);
}
/**
 * Ask articarest to remove a directory recursively.
 * REST: GET /system/deletefolder/<urlencoded path> -> {Status,Info,Error}
 * @return array array("ok"=>bool,"error"=>string)
 */
function api_delete_directory($directory):array{
    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/system/deletefolder/".urlencode($directory)));
    if(!is_object($json)){
        VERBOSE("api_delete_directory: no answer for [$directory]",__LINE__);
        return array("ok"=>false,"error"=>"Unable to delete $directory: no answer from the API");
    }
    if(!isset($json->Status) or !$json->Status){
        $error="";
        if(isset($json->Error)){$error=trim(strval($json->Error));}
        if(strlen($error)==0){$error="Unable to delete $directory";}
        VERBOSE("api_delete_directory: [$directory] -> $error",__LINE__);
        return array("ok"=>false,"error"=>$error);
    }
    admin_tracks("Delete a directory $directory");
    return array("ok"=>true,"error"=>null);
}
/**
 * Ask articarest for the sub-directories of $directory, as jstree nodes.
 * REST: GET /system/tree/<urlencoded path> -> [{id,text,children}] or {"error":"..."}
 * The daemon owns the protected-directories blacklist and the dot/space filtering,
 * so the WebConsole never reads the filesystem itself.
 * The returned node ids are absolute paths.
 * @return array jstree nodes
 */
function tree_nodes($directory):array{
    $directory=trim($directory);
    if(strlen($directory)==0){$directory="/";}
    VERBOSE("tree_nodes: /system/tree/".urlencode($directory),__LINE__);
    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/system/tree/".urlencode($directory)),true);
    if(!is_array($json)){
        VERBOSE("tree_nodes: [$directory] -> invalid answer",__LINE__);
        return array();
    }
    if(isset($json["error"])){
        VERBOSE("tree_nodes: [$directory] -> {$json["error"]}",__LINE__);
        return array();
    }
    $res=array();
    foreach($json as $ligne){
        if(!is_array($ligne)){continue;}
        if(!isset($ligne["id"])){continue;}
        if(strlen(trim($ligne["id"]))==0){continue;}
        $text=isset($ligne["text"]) ? $ligne["text"] : basename($ligne["id"]);
        $res[]=array("text"=>$text,"children"=>true,"id"=>$ligne["id"],"icon"=>"jstree-folder");
    }
    return $res;
}
function remove_folder_perform():bool{
    $tpl=new template_admin();
    $user=new usersMenus();
    if(!$user->AsAnAdministratorGeneric){
        echo $tpl->post_error("You are not allowed to remove a directory");
        return false;
    }
    $targetFolder=$_POST["remove-folder"];
    $results=api_delete_directory($targetFolder);
    if(!$results["ok"]){
        echo $tpl->post_error($results["error"]);
        return false;
    }
    return true;

}
function add_folder_perform():bool{
    $tpl=new template_admin();
    $user=new usersMenus();
    if(!$user->AsAnAdministratorGeneric){
        echo $tpl->post_error("You are not allowed to create a directory");
        return false;
    }

    $sourceFolder=$_POST["KeyID"];
    if($sourceFolder=="/"){
        echo $tpl->post_error("You must select a valid directory");
        return false;
    }
    $targetFolder=$_POST["add-folder"];
    $newpath="$sourceFolder/$targetFolder";
    $results=api_create_directory($newpath);
    if(!$results["ok"]){
        echo $tpl->post_error($results["error"]);
        return false;
    }
    return true;
}

function js(){
	$page=CurrentPageName();
    $tpl=new template_admin();
    $titlesrc=$_GET["title"];
    $title=$tpl->javascript_parse_text("$titlesrc: {select_a_directory}");

    $field_id=$_GET["field-id"];

    $tokens[]="field-id=$field_id";
    $tokens[]="title=".urlencode($titlesrc);

    if(isset($_GET["basepath"])) {
        $basePath = $_GET["basepath"];
        $tokens[] = "basepath=$basePath";
    }
    if(isset($_GET["no-select"])){
        $tokens[] = "no-select=yes";
    }

    $ztok=@implode("&",$tokens);
	$tpl->js_dialog4($title, "$page?popup=yes&$ztok");
	
	
}

function popup(){
	$tpl=new template_admin();
	$page=CurrentPageName();
    $field_id=$_GET["field-id"];
    $topbuttons=array();
	$t=time();
    $basePath="/";
    if(isset($_GET["basepath"])) {
        $basePath = urlencode($_GET["basepath"]);
    }
    $users=new usersMenus();
    if($users->AsAnAdministratorGeneric) {
        $topbuttons[] = array("CreateDir$t()", ico_plus, "{new_directory}");
        $topbuttons[] = array("Delete$t()", ico_trash, "{remove_directory}");
        $topbuttons[] = array("Selected$t()", ico_arrow_right, "{select}: <span id='bt-$t'></span>");
    }
    $btns=$tpl->table_buttons($topbuttons);



	$html[]="<input type='hidden' id='selected-$t' value=''>";
    $html[]=$btns;
	$html[]="<div id='jstree_$t' style='margin:30px'></div>";
    $html[]="<script>
function buildTree$t() {
  const selector = '#jstree_$t';
  const tree = $(selector);
  tree.jstree({
        core: {
            data: {
                url: 'fw.browse-directory.backend.php?basePath=$basePath',
                data: function (node) {
                    return { id: node.id };
                }
            },
            check_callback: true,
        },
    });
  
  tree.on('select_node.jstree', function (e, data) {
    $('#bt-$t').text(data.node.id);
  });
    
  tree.on('loaded.jstree', function () {
    const inst = tree.jstree(true);
    const root = inst.get_node('#');
    if (!root || !root.children) return;
    root.children.forEach(function (firstLevelId) {
      inst.open_node(firstLevelId, function () {
        const firstLevelNode = inst.get_node(firstLevelId);
        if (firstLevelNode && firstLevelNode.children) {
          // Open all second-level children
          firstLevelNode.children.forEach(function (secondLevelId) {
            inst.open_node(secondLevelId);
          });
        }
      });
    });
  });

 }
function Selected$t(){
      let ref = $('#jstree_$t').jstree(true), sel = ref.get_selected();
        if (sel.length) {
            const forbidden = ['/', '/var', '/home', '/usr','/root',  '/lib',  '/usr/share',  '/usr/share/artica-postfix',  '/usr/lib',  '/usr/bin',  '/usr/sbin',  '/usr/local',  '/usr/lib32',  '/etc',  '/sbin',  '/proc',  '/sys',  '/dev',  '/boot',  '/lost+found',  '/bin',  '/run',  '/mnt',  '/tmp','/opt','/srv','/media','/automounts'];
            var fieldid='$field_id';
            var value=sel[0];
            if (!forbidden.includes(value)) {
                document.getElementById('$field_id').value=value;
                dialogInstance4.close();
            }
       }
}
function CreateDir$t(){
    let ref = $('#jstree_$t').jstree(true), sel = ref.get_selected();
    if (sel.length) {
        var value=sel[0];
        const encoded = encodeURIComponent(value);
        Loadjs('$page?add-folder='+encoded+'&function=Refresh$t');
        return true;
    }
    let basePath='$basePath'
    if (basePath.length > 3) {
        Loadjs('$page?add-folder='+basePath+'&function=Refresh$t');
    }
}

function Refresh$t(){
    let ref = $('#jstree_$t').jstree(true), sel = ref.get_selected();
    ref.refresh();
} 

function Delete$t(){
     let ref = $('#jstree_$t').jstree(true), sel = ref.get_selected();
    if (sel.length) {
        var value=sel[0];
        const encoded = encodeURIComponent(value);
        Loadjs('$page?remove-folder='+encoded+'&function=Refresh$t');
    }   
}

$(document).ready(function () {
   buildTree$t();
});

		</script>";
	
	
	echo $tpl->_ENGINE_parse_body(@implode("\n", $html));
	
	
}
function operation(){
	$default="/";
    $rslt=array();
    if(isset($_GET["basepath"])) {
        if(strpos($_GET["basepath"],"==")>0) {
            $default=base64_decode($_GET["basepath"]);
        }else{
            $default=$_GET["basepath"];
        }
    }

	switch($_GET['operation']) {
		case 'get_node':

			$node = isset($_GET['id']) && $_GET['id'] !== '#' ? $_GET['id'] : $default;
            VERBOSE("get_node: tree_nodes($node)",__LINE__);
			if(isset($_GET['id']) && $_GET['id'] === '#'){
				// Initial load: expose the base path as a single opened root node.
				$label=basename($default);
				if(strlen($label)==0){$label="/";}
				$rslt=array(array("text"=>$label,"children"=>tree_nodes($default),"id"=>$default,
						"icon"=>"jstree-folder","state"=>array("opened"=>true,"disabled"=>true)));
				break;
			}
			$rslt = tree_nodes($node);
			break;


		case "get_content":
			$rslt=array();
			break;


		case "create_node":
			break;

		case "rename_node":
			$node = isset($_GET['id']) && $_GET['id'] !== '#' ? $_GET['id'] : $default;
			$text=replace_accents($_GET["text"]);
			$Directory=rtrim($node,"/")."/".$text;
			$rslt=array();
			$results=api_create_directory($Directory);
			if(!$results["ok"]){
				$GLOBALS["OPERATION_ERROR"]=$results["error"];
			}
			break;
	}


			header('Content-Type: application/json; charset=utf-8');
			if(isset($GLOBALS["OPERATION_ERROR"])){
				http_response_code(500);
				echo json_encode(array("error"=>$GLOBALS["OPERATION_ERROR"]));
				return;
			}
			echo json_encode($rslt);
}
