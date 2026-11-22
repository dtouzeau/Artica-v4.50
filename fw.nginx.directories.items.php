<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
include_once(dirname(__FILE__)."/ressources/class.nginx.params.inc");
// awc_table_style() / awc_pager_js(): feuille de style et pager partages des tableaux d'entites
include_once(dirname(__FILE__)."/ressources/class.statistics_appliance.inc");
if(isset($_GET["container-move"])){container_move();exit;}
if(isset($_GET["table"])){table();exit;}
if(isset($_GET["id-js"])){id_js();exit;}
if(isset($_GET["id-popup"])){id_popup();exit;}
if(isset($_GET["delete"])){delete_js();exit;}
if(isset($_POST["ID"])){id_save();exit;}
if(isset($_POST["delete"])){delete();exit;}



table_start();
function container_move(){
	$ID=intval($_GET["container-move"]);
	$dir=intval($_GET["dir"]);

	$ligne=fetch_subdir_item_by_id($ID);
	$CurrentOrder=isset($ligne["zorder"])?intval($ligne["zorder"]):0;
	$NextOrder=($dir==0)?$CurrentOrder-1:$CurrentOrder+1;

	// Swap zorder with the row currently at NextOrder (preserves the
	// original semantics: no directoryid scope).
	$GLOBALS["CLASS_SOCKETS"]->REST_API_NGINX_POST_JSON("/nginx-db/exec",[
	    "action"=>"update","table"=>"ngx_subdir_items",
	    "set"=>["zorder"=>$CurrentOrder],
	    "where"=>["zorder"=>$NextOrder]
	]);
	$GLOBALS["CLASS_SOCKETS"]->REST_API_NGINX_POST_JSON("/nginx-db/exec",[
	    "action"=>"update","table"=>"ngx_subdir_items",
	    "set"=>["zorder"=>$NextOrder],
	    "where"=>["ID"=>$ID]
	]);

	// Compact gaps — renumber 1..N across the whole table (matching original).
	$rows=fetch_subdir_items_all();
	$c=1;
	foreach ($rows as $r){
	    $rid=isset($r["ID"])?intval($r["ID"]):0;
	    if($rid<=0){continue;}
	    $GLOBALS["CLASS_SOCKETS"]->REST_API_NGINX_POST_JSON("/nginx-db/exec",[
	        "action"=>"update","table"=>"ngx_subdir_items",
	        "set"=>["zorder"=>$c],"where"=>["ID"=>$rid]
	    ]);
	    $c++;
	}
}


function table_start(){
	$page       = CurrentPageName();
	$ID         = intval($_GET["directory_id"]);
	$serviceid  = intval($_GET["serviceid"]);
	$md5        = $_GET["md5"];
	//directory_id=$ID&serviceid=$serviceid&md5=$md5



	echo "<div id='ngx_subdir_items-$ID'></div>
	<script>LoadAjax('ngx_subdir_items-$ID','$page?table=$ID&serviceid=$serviceid&md5=$md5')</script>";
}
function delete_js():bool{
	$page           = CurrentPageName();
	$tpl            = new template_admin();
	$ID             = $_GET["delete"];
	$md5            = $_GET["md5"];
	$directoryid    = intval($_GET["directoryid"]);

	$dir=fetch_directory_by_id($directoryid);
	$serviceid=isset($dir["serviceid"])?intval($dir["serviceid"]):0;

	$item=fetch_subdir_item_by_id($ID);
	$itemLabel=isset($item["item"])?$item["item"]:"#$ID";
	$tpl->js_confirm_delete($itemLabel, "delete", "$ID","$('#$md5').remove();LoadAjaxSilent('itemsofdir-$directoryid','fw.nginx.directories.php?items=$directoryid');Loadjs('fw.nginx.hup.php?hup=yes&serviceid=$serviceid')");

   return true;
}
function get_servicename($serviceid=0):string{
    $ID=intval($serviceid);
    if($ID==0){return "Unknown";}
    $sock=new socksngix($ID);
    return $sock->GetServiceName();
}
function  delete(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$ID=$_POST["delete"];
	$GLOBALS["CLASS_SOCKETS"]->REST_API_NGINX_POST_JSON("/nginx-db/exec", array("action"=>"delete","table"=>"ngx_subdir_items","where"=>array("ID"=>"$ID")));
}


function id_js(){
	$page           = CurrentPageName();
	$tpl            = new template_admin();
	$ID             = $_GET["id-js"];
	$serviceid      = $_GET["serviceid"];
	$md5            = $_GET["md5"];
    $directoryid    = intval($_GET["directoryid"]);
	$title="{new_item}: $directoryid {rule}: $serviceid";
	if($ID==0){$title="{new_rule}";}
	$tpl->js_dialog4($title, "$page?id-popup=$directoryid&serviceid=$serviceid&md5=$md5");
}
function id_popup(){
	$page           = CurrentPageName();
	$tpl            = new template_admin();
    $directoryid    = $_GET["id-popup"];
	$serviceid      = $_GET["serviceid"];
	$md5            = $_GET["md5"];
    $title          = "{new_item}";
    $btname         = "{add}";

    $tt[]="dialogInstance4.close()";
    $tt[]="LoadAjax('ngx_subdir_items-$directoryid','$page?table=$directoryid&serviceid=$serviceid&md5=$md5')";
    $tt[]="LoadAjaxSilent('itemsofdir-$directoryid','fw.nginx.directories.php?items=$directoryid')";
    $tt[]="Loadjs('fw.nginx.hup.php?hup=yes&serviceid=$serviceid')";
    $js=@implode(";",$tt);

	

	
	$form[]=$tpl->field_hidden("ID", 0);
	$form[]=$tpl->field_hidden("serviceid", $serviceid);
    $form[]=$tpl->field_hidden("directoryid", $directoryid);
	$form[]=$tpl->field_text("ipaddr", "{ipaddr}", null);
	echo $tpl->form_outside($title, $form,"{ngx_stream_access_module}",$btname,"$js","AsSystemWebMaster");
	
}

function id_save(){
	$tpl=new template_admin();
	$tpl->CLEAN_POST();
	$serviceid      = intval($_POST["serviceid"]);
    $directoryid    = intval($_POST["directoryid"]);

	if($serviceid==0){echo "Service ID missing or null\n";return;}
    if($directoryid==0){echo "Directory ID missing or null\n";return;}

	$item=trim($_POST["ipaddr"]);
	if($item<>"*"){
		$ipclass=new IP();
		if(!$ipclass->isIPAddressOrRange($item)){
			echo "Wrong item $item";return;
		}
	}

	$GLOBALS["CLASS_SOCKETS"]->REST_API_NGINX_POST_JSON("/nginx-db/exec", array("action"=>"insert","table"=>"ngx_subdir_items","values"=>array("directoryid"=>"$directoryid","serviceid"=>"$serviceid","item"=>"$item")));
}

function table(){
	$page       = CurrentPageName();
	$tpl        = new template_admin();
    $serviceid  = intval($_GET["serviceid"]);
    $md5        = $_GET["md5"];
    $directoryid= intval($_GET["table"]);

    $topbuttons[] = array("Loadjs('$page?id-js=0&directoryid=$directoryid&serviceid=$serviceid&md5=$md5');",
        ico_plus,"{new_item}");
    $html[]="<div style='margin-top:10px'>";
    $html[]=$tpl->th_buttons($topbuttons);
    $html[]="</div>";
	$html[]="<div class='awc-wrap'><div class='awc-card'><div class='awc-scroll'><table class='awc-tbl awc-tbl-compact'>";
	$html[]="<thead>";
	$html[]="<tr>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text' nowrap>{item}</th>";
	$html[]="<th data-sortable=false>{order}</th>";
	$html[]="<th data-sortable=false>{delete}</th>";

	$html[]="</tr>";
	$html[]="</thead>";
	$html[]="<tbody>";

	

	// Daemon owns the schema (nginxsites.FixTables creates ngx_subdir_items
	// at startup and after restore), so no CREATE TABLE / INDEX needed here.
	$results=fetch_subdir_items_by_directoryid($directoryid);


	$TRCLASS=null;
	foreach ($results as $md5=>$ligne){
		$TRCLASS=null;
		
		$md5        = md5(serialize($ligne));
		$ID         = $ligne["ID"];
		$item       = trim($ligne["item"]);
		$zorder     = $ligne["zorder"];
        $mv_up      = $tpl->icon_up("Loadjs('$page?container-move={$ligne["ID"]}&dir=0')","AsSystemWebMaster");
        $mv_down    = $tpl->icon_down("Loadjs('$page?container-move={$ligne["ID"]}&dir=1')","AsSystemWebMasters");

        if($item=="*"){$item="{all}";}
		$html[]="<tr class='$TRCLASS' id='$md5'>";
		$html[]="<td nowrap><strong>$item</strong></td>";
	
		
		if($zorder<2){$mv_up=null;}
		$html[]="<td style='width:1%' class='center' nowrap>$mv_up&nbsp;&nbsp;$mv_down</td>";
		
		
		$html[]="<td style='width:1%' class='center'>". $tpl->icon_delete("Loadjs('$page?delete=$ID&md5=$md5&directoryid=$directoryid')","AsSystemWebMaster")."</td>";
		$html[]="</tr>";
	}

	$html[]="</tbody></table></div></div></div>";
	$html[]="
	<script>
	NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS)."
	</script>";
	echo awc_table_style().$tpl->_ENGINE_parse_body($html);

}

