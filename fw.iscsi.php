<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.system.network.inc");
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(isset($_POST["none"])){exit();}
if(isset($_GET["tabs"])){tabs();exit;}
if(isset($_GET["mode-client-popup"])){mode_client_popup();exit;}
if(isset($_GET["mode-client-popup-table"])){mode_client_popup_table();exit;}
if(isset($_GET["mode-client-js"])){mode_client_js();exit;}
if(isset($_GET["mode-client-single"])){mode_client_single();exit;}
if(isset($_GET["mode-client-iscsi-search"])){mode_client_iscsi_search();exit;}
if(isset($_GET["mode-client-iscsi-results"])){mode_client_iscsi_results();exit;}
if(isset($_GET["mod-client-selectadd"])){mode_client_iscsi_results_add();exit;}
if(isset($_GET["mod-client-form"])){mode_client_iscsi_results_form();exit;}
if(isset($_GET["mod-client-fiche-js"])){mode_client_iscsi_fiche_js();exit;}
if(isset($_GET["mod-client-fiche-form"])){mode_client_iscsi_fiche_form();exit;}
if(isset($_POST["ClientID"])){mode_client_iscsi_results_save();exit;}
if(isset($_GET["mode-client-delete"])){mode_client_delete_js();exit;}
if(isset($_POST["mode-client-delete"])){mode_client_delete_perform();exit;}
if(isset($_GET["mode-server-popup"])){mode_server_popup();exit;}
if(isset($_GET["mode-server-popup-table"])){mode_server_popup_table();exit;}
if(isset($_GET["mode-server-js"])){mode_server_js();exit;}
if(isset($_GET["mode-server-single"])){mode_server_single();exit;}
if(isset($_GET["mode-server-delete"])){mode_server_delete();exit;}
if(isset($_POST["iscsiDiskID"])){mode_server_save();exit;}

page();
function page(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$page=CurrentPageName();
	$users=new usersMenus();
	$version=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("ISCSI_VERSION");

	$html="
	<div class=\"row border-bottom white-bg dashboard-header\">
		<div class=\"col-sm-12\"><h1 class=ng-binding>{APP_IETD} v{$version}</h1><p>{iscsi_explain}</p></div>
	</div>
	<div class='row'><div id='progress-iscsi-restart'></div>
	<div class='ibox-content'>
		<div id='table-loader-iscsi-service'></div>
	</div>
	<script>
		LoadAjax('table-loader-iscsi-service','$page?tabs=yes');
	</script>";

	$tpl=new template_admin();
	echo $tpl->_ENGINE_parse_body($html);

}
function tabs(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$sock=new sockets();
	$array["{connections}"]="$page?mode-client-popup=yes";
	$array["{ISCSI_share}"]="$page?mode-server-popup=yes";
	$array["{events}"]="fw.iscsi.events.php";
	echo $tpl->tabs_default($array);
}
function mode_client_js(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$ID=intval($_GET["mode-client-js"]);
	if($ID==0){$title="{add_iscsi_connection}";}else{
		$title=$ID;
	}
	
	$tpl->js_dialog1($title, "$page?mode-client-single=$ID");
}

function mode_server_delete(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$ID=$_GET["mode-server-delete"];
	
	$q=new mysql();
	$sql="SELECT Params FROM iscsi_params WHERE ID='$ID'";
	$ligne=@mysqli_fetch_array($q->QUERY_SQL($sql,'artica_backup'));
	$Params=unserialize(base64_decode($ligne["Params"]));
	$iqn=$Params["WWN"];
	

	$ARRAY["PROGRESS_FILE"]=PROGRESS_DIR."/system_disks_iscsi_progress";
	$ARRAY["LOG_FILE"]=PROGRESS_DIR."/system_disks_iscsi_progress.txt";
	$ARRAY["CMD"]="iscsi.php?remove-server-config=$ID";
	$ARRAY["TITLE"]="{ISCSI_share}::{remove}";
	$ARRAY["AFTER"]="LoadAjax('mode-server-popup-table','$page?mode-server-popup-table=yes');";
	$prgress=base64_encode(serialize($ARRAY));
	$jsrestart="Loadjs('fw.progress.php?content=$prgress&mainid=progress-iscsi-restart')";
	
	$tpl->js_confirm_delete($iqn, "none", "none",$jsrestart);
	
	

	
	
}

function mode_server_js(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$ID=intval($_GET["mode-server-js"]);
	if($ID==0){$title="{add_iscsi_disk}";}else{
		$q=new mysql();
		$sql="SELECT * FROM iscsi_params WHERE ID='$ID'";
		$ligne=@mysqli_fetch_array($q->QUERY_SQL($sql,'artica_backup'));
		
		$title=$ID.":{$ligne["hostname"]}/{$ligne["type"]}/{$ligne["shared_folder"]}";}
	$tpl->js_dialog1($title, "$page?mode-server-single=$ID");	
}
function mode_server_single(){
	$ID=intval($_GET["mode-server-single"]);
	$page=CurrentPageName();
	$tpl=new template_admin();
	$q=new mysql();
	$button_text="{add}";
	$title="{add_iscsi_disk}";
	$explain="{iscsi_disk_add_explain}";
	$jsafter="dialogInstance1.close();LoadAjax('mode-server-popup-table','$page?mode-server-popup-table=yes');";
	
	$f=explode("\n",@file_get_contents("/proc/mounts"));
	foreach ($f as $line){
		if(preg_match("#^(.+?)\s+#", $line,$re)){$zMounted[$re[1]]=true;}
		
	}
	
	
	include_once 'ressources/usb.scan.inc';
	while (list ($num, $line) = each ($_GLOBAL["disks_list"])){
		if($num=="size (logical/physical)"){continue;}
		$ID_MODEL_2=$line["ID_MODEL_2"];
		$PARTITIONS=$line["PARTITIONS"];
		//print_r($line);
		if(is_array($PARTITIONS)){
			while (list ($dev, $part) = each ($PARTITIONS)){
				if(isset($zMounted[$dev])){continue;}
				$MOUNTED=$part["MOUNTED"];
				if(preg_match("#\/iSCSI\/", $MOUNTED)){continue;}
				if(strlen($MOUNTED)>20){$MOUNTED=substr($MOUNTED,0,17)."...";}
				$SIZE=$part["SIZE"];
				$TYPE=$part["TYPE"];
				if($TYPE==82){continue;}
				if($TYPE==5){continue;}
				$devname=basename($dev);
				$devs[$dev] ="($devname) $MOUNTED $SIZE";
			}
		}
	}
	$iscsar=array("disk"=>"{disk}/{partition}","file"=>"{file}");
	
	if($ID>0){
		$sql="SELECT * FROM iscsi_params WHERE ID='$ID'";
		$ligne=@mysqli_fetch_array($q->QUERY_SQL($sql,'artica_backup'));
		$button_text="{apply}";
		$title="{$ligne["type"]}: {$ligne["shared_folder"]}";
		$jsafter="LoadAjax('mode-server-popup-table','$page?mode-server-popup-table=yes');";
		$explain=null;
	}
	if(intval($ligne["file_size"])==0){$ligne["file_size"]=5;}
	if($ligne["hostname"]==null){
		$users=new usersMenus();
		$ligne["hostname"]=$users->fqdn;
	
	}
	
	$form[]=$tpl->field_hidden("iscsiDiskID", $ID);
	if($ID==0){$form[]=$tpl->field_array_hash($iscsar, "ztype", "{type}", $ligne["type"]);}
	
	
	
	
	

	
	
	if($ID==0){
		$form[]=$tpl->field_text("hostname", "{hostname}", $ligne["hostname"]);
		$form[]=$tpl->field_text("shared_folder", "{shared_folder}", $ligne["shared_folder"]);
		$form[]=$tpl->field_section("{create_a_virtual_disk}");
		$form[]=$tpl->field_browse_directory("path", "{path}", $ligne["dev"]);
		$form[]=$tpl->field_numeric("size","{size} (GB)",$ligne["file_size"]);
		$form[]=$tpl->field_section("{or_use_partition}","{iscsi_partition_warn}",true);
		$form[]=$tpl->field_array_hash($devs, "partition", "{partition}", $ligne["dev"]);
	}else{
		$form[]=$tpl->field_info("hostname", "{hostname}", $ligne["hostname"]);
		$form[]=$tpl->field_info("shared_folder", "{shared_folder}", $ligne["shared_folder"]);
		$form[]=$tpl->field_info("ztype","{type}",$ligne["type"]);
		
		if($ligne["type"]=='file'){
			$form[]=$tpl->field_info("dev","{path}",$ligne["dev"]);
			$form[]=$tpl->field_info("size","{size} (GB)",$ligne["file_size"]);
		}else{
			$form[]=$tpl->field_info("dev","{partition}",$ligne["dev"]);
			$form[]=$tpl->field_hidden("size", $ligne["file_size"]);
		}
		
	}
	
	$Params=unserialize(base64_decode($ligne["Params"]));
	
	
	
	$form[]=$tpl->field_section("{security}");
	$form[]=$tpl->field_checkbox("EnableAuth","{enable_authentication}",$ligne["EnableAuth"],"uid,password","{iscsi-secu-explain}");
	$form[]=$tpl->field_text("uid", "{username}", $ligne["uid"]);
	$form[]=$tpl->field_password("password", "{password}", $Params["password"]);
	
	
	$form[]=$tpl->field_section("{parameters}");
	$Params=unserialize(base64_decode($ligne["Params"]));
	
	if(!is_numeric($Params["MaxConnections"])){$Params["MaxConnections"]=1;}
	if(!is_numeric($Params["ImmediateData"])){$Params["ImmediateData"]=1;}
	if(!is_numeric($Params["Wthreads"])){$Params["Wthreads"]=8;}
	if($Params["IoType"]==null){$Params["IoType"]="fileio";}
	if($Params["mode"]==null){$Params["mode"]="wb";}
	
	$hashIoType=array("fileio"=>"{fileio}","blockio"=>"{blockio}");
	$hashMode=array("ro"=>"{ro}","wb"=>"{wb}");
	
	$form[]=$tpl->field_numeric("MaxConnections","{MaxConnections}",$Params["MaxConnections"]);
	$form[]=$tpl->field_checkbox("ImmediateData","{ImmediateData}",$Params["ImmediateData"],false,"{ImmediateData_explain}");
	echo $tpl->form_outside($title, @implode("\n", $form),$explain,$button_text,$jsafter,"AsSystemAdministrator");
	
}
function mode_server_save(){
	
	while (list ($num, $val) = each ($_POST)){
		$_POST[$num]=url_decode_special_tool($val);
	}
	
	$hostname=$_POST["hostname"];
	$type=$_POST["ztype"];
	$size=$_POST["file_size"];
	$ID=$_POST["iscsiDiskID"];
	$EnableAuth=$_POST["EnableAuth"];
	$dev=$_POST["dev"];
	$uid=$_POST["uid"];
	$foldername=$_POST["shared_folder"];
	$foldername=strtolower($foldername);
	$foldername=replace_accents($foldername);
	$foldername=str_replace(" ","_",$foldername);
	$foldername=str_replace(".","-",$foldername);
	$foldername=str_replace("/","-",$foldername);
	$foldername=str_replace("[","",$foldername);
	$foldername=str_replace("]","",$foldername);
	$foldername=str_replace("(","",$foldername);
	$foldername=str_replace(")","",$foldername);
	$foldername=str_replace("$","-",$foldername);
	$foldername=str_replace(",","",$foldername);
	$foldername=str_replace(";","",$foldername);
	$foldername=str_replace(":","",$foldername);
	$foldername=str_replace("!","",$foldername);
	$foldername=str_replace("%","",$foldername);
	$foldername=str_replace("*","",$foldername);
	$foldername=str_replace("^","",$foldername);
	$foldername=str_replace("@","",$foldername);
	$foldername=str_replace("}","",$foldername);
	$foldername=str_replace("{","",$foldername);
	$foldername=str_replace("\\","",$foldername);
	$foldername=str_replace("|","",$foldername);
	$foldername=str_replace("#","",$foldername);
	$foldername=str_replace("&","",$foldername);
	$foldername=str_replace("?","",$foldername);
	
	$q=new mysql();
	$tpl=new templates();
	if($ID==0){
		$sql="SELECT ID FROM iscsi_params WHERE shared_folder='$foldername'";
		$ligne=@mysqli_fetch_array($q->QUERY_SQL($sql,"artica_backup"));
		if($ligne["ID"]>0){
			echo $tpl->javascript_parse_text("$foldername {ERROR_OBJECT_ALREADY_EXISTS}");
			return;
		}
	}
	
	if($ID>0){
		$sql="SELECT Params FROM iscsi_params WHERE ID='$ID'";
		$ligne=@mysqli_fetch_array($q->QUERY_SQL($sql,"artica_backup"));
		$Params=unserialize(base64_decode($ligne["Params"]));
	}
	
	if(strpos("  {$_POST["password"]}", '$')>0){echo "Invalid character $ in password";return;}
	if(strpos("  {$_POST["password"]}", '|')>0){echo "Invalid character | in password";return;}
	if(strpos("  {$_POST["password"]}", ';')>0){echo "Invalid character ; in password";return;}
	if(strpos("  {$_POST["password"]}", '#')>0){echo "Invalid character # in password";return;}
	if(strpos("  {$_POST["password"]}", '"')>0){echo "Invalid character \" in password";return;}
	if(strpos("  {$_POST["password"]}", "'")>0){echo "Invalid character ' in password";return;}
	if(strpos("  {$_POST["password"]}", "}")>0){echo "Invalid character } in password";return;}
	if(strpos("  {$_POST["password"]}", "{")>0){echo "Invalid character { in password";return;}
	
	$Params["password"]=$_POST["password"];
	$Params["MaxConnections"]=$_POST["MaxConnections"];
	$Params["IoType"]=$_POST["IoType"];
	$Params["mode"]=$_POST["zmode"];
	$Params["ImmediateData"]=$_POST["ImmediateData"];
	$Params["Wthreads"]=$_POST["Wthreads"];
	
	$ParamsText=mysql_escape_string2(base64_encode(serialize($Params)));
	
	if($foldername==null){$foldername=time();}
	if($type=='file'){$dev=$_POST["path"]."/$foldername.img";}else{$dev=$_POST["partition"];}
	if(!is_numeric($size)){$size=5;}
	if(!is_numeric($ID)){$ID=0;}
	$sql="INSERT INTO iscsi_params (`hostname`,`dev`,`type`,`file_size`,`shared_folder`,`uid`,`EnableAuth`,`Params`)
	VALUES('$hostname','$dev','$type','{$size}','$foldername','$uid','$EnableAuth','$ParamsText')";
	
	$sqlu="UPDATE iscsi_params SET EnableAuth='$EnableAuth',`uid`='$uid',`Params`='$ParamsText' WHERE ID=$ID";
	
	if($ID>0){$sql=$sqlu;}
	
	
	$q->QUERY_SQL($sql,"artica_backup");
	if(!$q->ok){echo $q->mysql_error;return;}
}

function mode_client_single(){
	$ID=intval($_GET["mode-client-single"]);
	if($ID==0){mode_client_browse();return;}
}

function  mode_client_browse(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$t=time();
	$html[]="
			
	<div class=\"row\"> 
		<div class='ibox-content'><p>{add_iscsi_explain}</p>
		<div class=\"input-group\">
      		<input type=\"text\" class=\"form-control\" value=\"{$_SESSION["ISCSI"]["addr"]}\" 
      			placeholder=\"{search_iscsi_connection}\" id='search-this-$t' OnKeyPress=\"javascript:Search$t(event);\">
      		<span class=\"input-group-btn\">
       		 <button style=\"text-transform: capitalize;\" class=\"btn btn-default\" type=\"button\" OnClick=\"javascript:ss$t();\">Go!</button>
      	</span>
     </div>
    </div>
	<div id='mode-client-browse'></div>
	<script>
		function Search$t(e){
			if(!checkEnter(e) ){return;}
			ss$t();
		}
		
		function ss$t(){
			var ss=encodeURIComponent(document.getElementById('search-this-$t').value);
			LoadAjax('mode-client-browse','$page?mode-client-iscsi-search='+ss);
		}
		
		function Start$t(){
			var ss=document.getElementById('search-this-$t').value;
			ss$t();
		}
		Start$t();
	</script>";
	
	
	
	echo $tpl->_ENGINE_parse_body(@implode("\n", $html));
	
	
	
}

function mode_client_iscsi_search(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$sock=new sockets();
	$search=urlencode(trim(url_decode_special_tool($_GET["mode-client-iscsi-search"])));
	
	
	$ARRAY["PROGRESS_FILE"]=PROGRESS_DIR."/system_disks_iscsi_progress";
	$ARRAY["LOG_FILE"]=PROGRESS_DIR."/system_disks_iscsi_progress.txt";
	$ARRAY["CMD"]="iscsi.php?iscsi-search=$search";
	$ARRAY["TITLE"]="{search_iscsi_connection}";
	$ARRAY["AFTER"]="LoadAjax('mode-client-browse','$page?mode-client-iscsi-results=yes');";
	
	$prgress=base64_encode(serialize($ARRAY));
	$jsrestart="Loadjs('fw.progress.php?content=$prgress&mainid=mode-client-browse')";
	echo "<script>
	//{$ARRAY["PROGRESS_FILE"]}\n//{$ARRAY["LOG_FILE"]}
	
	$jsrestart</script>";

}

function mode_client_iscsi_results(){
	
	$array=unserialize(@file_get_contents(PROGRESS_DIR."/iscsi-search.array"));
    if(!is_array($array)){$array=array();}
	$page=CurrentPageName();
	$tpl=new template_admin();
	$sock=new sockets();
	$sock->getFrameWork("iscsi.php?iscsi-sessions=yes");
	$array_sessions=unserialize(@file_get_contents(PROGRESS_DIR."/iscsi-sessions.array"));
	
	while (list ($ip, $subarray) = each ($array_sessions)){
		while (list ($ip, $subarray2) = each ($subarray)){
			if(preg_match("#(.+?)\s+\(#",$subarray2["FOLDER"],$re)){$subarray2["FOLDER"]=$re[1];}
			$ids="{$subarray2["ISCSI"]}:{$subarray2["PORT"]}:{$subarray2["FOLDER"]}";
			$MSESSIONS[$ids]=true;
		}
	}
	
	$html[]="<table id='table-iscsi-mod-client-search' class=\"footable table table-stripped\" data-page-size=\"100\" data-paging=\"true\">";
	$html[]="<thead>";
	$html[]="<tr>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{hostname}</th>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{port}</th>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{disk}</th>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{directory}</th>";
	$html[]="<th data-sortable=false style='width:1%'></th>";
	$html[]="</tr>";
	$html[]="</thead>";
	$html[]="<tbody>";
	$TRCLASS=null;
	while (list ($hostname, $subarray) = each ($array)){
		while (list ($index, $subarray2) = each ($subarray)){
			
			$notif=null;
			$subarray2_enc=base64_encode(serialize($subarray2));
			$md=md5($subarray2_enc);
			
			if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
			$aclid=$index;
			$sessionid="{$subarray2["ISCSI"]}:{$subarray2["PORT"]}:{$subarray2["FOLDER"]}";
			$select=$tpl->icon_select("Loadjs('$page?mod-client-selectadd=$subarray2_enc&md=$md')","AsSystemAdministrator");
			if(isset($MSESSIONS[$sessionid])){
				$notif="&nbsp;&nbsp;<span class='label label-warning'>{already_used}</span>";
				$select="&nbsp;";
			}
			
			$html[]="<tr class='$TRCLASS' id='$md'>";
			$html[]="<td class=\"\" nowrap><i class='".ico_hd."'></i>&nbsp;{$hostname} [{$subarray2["IP"]}]$notif</td>";
			$html[]="<td width=1% nowrap>{$subarray2["PORT"]}</td>";
			$html[]="<td>{$subarray2["ISCSI"]}</td>";
			$html[]="<td>{$subarray2["FOLDER"]}</td>";
			$html[]="<td>$select</td>";
			$html[]="</tr>";
			
		}
	}

		$html[]="</tbody>";
		$html[]="<tfoot>";
		
		$html[]="<tr>";
		$html[]="<td colspan='5'>";
		$html[]="<ul class='pagination pull-right'></ul>";
		$html[]="</td>";
		$html[]="</tr>";
		$html[]="</tfoot>";
		$html[]="</table>";
		$html[]="
	<script>
	NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS)."
	$(document).ready(function() { $('#table-iscsi-mod-client-search').footable( { \"filtering\": { \"enabled\": true }, \"sorting\": { \"enabled\": true },\"paging\": { \"size\": {$GLOBALS["FOOTABLE_PSIZE"]} } } ); });
	</script>";
		
		echo $tpl->_ENGINE_parse_body(@implode("\n", $html));	
	
}

function mode_client_iscsi_results_add(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$array=unserialize(base64_decode($_GET["mod-client-selectadd"]));
    if(!is_array($array)){$array=array();}
	$title="{$array["IP"]}:{$array["PORT"]}/{$array["FOLDER"]}";
	$tpl->js_dialog2($title, "$page?mod-client-form={$_GET["mod-client-selectadd"]}&md={$_GET["md"]}");
}
function mode_client_iscsi_results_form(){
	$page=CurrentPageName();
	$tpl=new template_admin();	
	$subarray2=unserialize(base64_decode($_GET["mod-client-form"]));
	$title="{$subarray2["IP"]}:{$subarray2["PORT"]}";
	$ligne["EnableAuth"]=0;
	$ligne["Persistante"]=1;
	$form[]=$tpl->field_hidden("ClientID", 0);
	$form[]=$tpl->field_hidden("Params", "{$_GET["mod-client-form"]}");
	$form[]=$tpl->field_info("disk", "{disk}", $subarray2["ISCSI"]);
	$form[]=$tpl->field_info("directory", "{directory}", $subarray2["FOLDER"]);
	$form[]=$tpl->field_checkbox("persistante","{persistante_connection}",$ligne["Persistante"]);
	$form[]=$tpl->field_checkbox("EnableAuth","{enable_authentication}",$ligne["EnableAuth"],"username,password");
	$form[]=$tpl->field_text("username", "{username}", $ligne["username"]);
	$form[]=$tpl->field_password("password", "{password}", $ligne["password"]);
	echo $tpl->form_outside($title, @implode("\n", $form),null,"{add}","$('#{$_GET["md"]}').remove();dialogInstance2.close();LoadAjax('mode-client-popup-table','$page?mode-client-popup-table=yes');","AsSystemAdministrator");
}
function mode_client_iscsi_fiche_js(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$ID=intval($_GET["mod-client-fiche-js"]);
	$sql="SELECT Params FROM iscsi_client WHERE ID='{$ID}'";
	$q=new mysql();
	$ligne=@mysqli_fetch_array($q->QUERY_SQL($sql,'artica_backup'));
	$array=unserialize(base64_decode($ligne["Params"]));
    if(!is_array($array)){$array=array();}
	$title="{$array["IP"]}:{$array["PORT"]}/{$array["FOLDER"]}";
	$tpl->js_dialog1($title, "$page?mod-client-fiche-form=$ID");
}

function mode_client_delete_js(){
	$page=CurrentPageName();
	$ID=$_GET["mode-client-delete"];
	$sql="SELECT * FROM iscsi_client WHERE ID='{$ID}'";
	$q=new mysql();
	$tpl=new template_admin();
	$ligne=@mysqli_fetch_array($q->QUERY_SQL($sql,'artica_backup'));
	$subarray2=unserialize(base64_decode($ligne["Params"]));
	$title="{$subarray2["IP"]}:{$subarray2["PORT"]} ({$subarray2["FOLDER"]})";
	
	
	$ARRAY["PROGRESS_FILE"]=PROGRESS_DIR."/iscsi.install.prg";
	$ARRAY["LOG_FILE"]=PROGRESS_DIR."/iscsi.install.log";
	$ARRAY["CMD"]="iscsi.php?delete-client=$ID";
	$ARRAY["TITLE"]="{delete_iscsi_connection}";
	$ARRAY["AFTER"]="LoadAjax('mode-client-popup-table','$page?mode-client-popup-table=yes');";
	
	$prgress=base64_encode(serialize($ARRAY));
	$jsrestart="Loadjs('fw.progress.php?content=$prgress&mainid=progress-iscsi-restart')";
	
	$tpl->js_confirm_delete($title, "mode-client-delete", $ID,$jsrestart);
}

function mode_client_delete_perform(){
	$sock=new sockets();
	$ID=$_POST["mode-client-delete"];
	
	
}

function mode_client_iscsi_fiche_form(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$q=new mysql();
	$ID=intval($_GET["mod-client-fiche-form"]);
	$sql="SELECT * FROM iscsi_client WHERE ID='{$ID}'";
	$ligne=@mysqli_fetch_array($q->QUERY_SQL($sql,'artica_backup'));
	
	$subarray2=unserialize(base64_decode($ligne["Params"]));
	$title="{$subarray2["IP"]}:{$subarray2["PORT"]}";
	
	$form[]=$tpl->field_hidden("ClientID", $ID);
	$form[]=$tpl->field_hidden("Params", "{$ligne["Params"]}");
	$form[]=$tpl->field_info("disk", "{disk}", $subarray2["ISCSI"]);
	$form[]=$tpl->field_info("directory", "{directory}", $subarray2["FOLDER"]);
	$form[]=$tpl->field_checkbox("persistante","{persistante_connection}",$ligne["Persistante"]);
	$form[]=$tpl->field_checkbox("EnableAuth","{enable_authentication}",$ligne["EnableAuth"],"username,password");
	$form[]=$tpl->field_text("username", "{username}", $ligne["username"]);
	$form[]=$tpl->field_password("password", "{password}", $ligne["password"]);
	echo $tpl->form_outside($title, @implode("\n", $form),null,"{apply}","LoadAjax('mode-client-popup-table','$page?mode-client-popup-table=yes');","AsSystemAdministrator");
}


function mode_client_iscsi_results_save(){
	
	foreach ($_POST as $key=>$val){
		$_POST[$key]=url_decode_special_tool($val);
	}
	
	
	$subarray2=unserialize(base64_decode($_POST["Params"]));
	
	$ID=$_POST["ClientID"];
	if(!is_numeric($ID)){$ID=0;}
	$q=new mysql();
	
	if(!$q->FIELD_EXISTS("iscsi_client","EnableAuth","artica_backup")){
		$sql="ALTER TABLE `iscsi_client` ADD `EnableAuth` smallint(1) NOT NULL DEFAULT 1";
		$q->QUERY_SQL($sql,'artica_backup');
	}
	
	
	
	$sql="INSERT INTO iscsi_client(username,password,Params,hostname,directory,Persistante,EnableAuth)
	VALUES('{$_POST["username"]}','{$_POST["password"]}','{$_POST["Params"]}','{$subarray2["ISCSI"]}:{$subarray2["PORT"]}','{$subarray2["FOLDER"]}','{$_POST["persistante"]}','{$_POST["EnableAuth"]}')";
	
	$sql_edit="UPDATE `iscsi_client`
	SET `username`='{$_POST["username"]}',
	`password`='{$_POST["password"]}',
	`Persistante`='{$_POST["persistante"]}',
	`EnableAuth`='{$_POST["EnableAuth"]}'
	WHERE `ID`='{$_POST["ID"]}'
	";
	if($ID>0){$sql=$sql_edit;}
	
	
	$q->QUERY_SQL($sql,"artica_backup");
	if(!$q->ok){echo $q->mysql_error_html(true);return;}
	
	
}


function mode_client_popup(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$html="<div id='mode-client-popup-table'></div>
	<script>
		LoadAjax('mode-client-popup-table','$page?mode-client-popup-table=yes');
	</script>		
	";
	echo $html;
}
function mode_server_popup(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	
	$ISCSI_TARGETCLI_INSTALLED=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ISCSI_TARGETCLI_INSTALLED"));
	
	if($ISCSI_TARGETCLI_INSTALLED==0){
		echo $tpl->FATAL_ERROR_SHOW_128("{ISCSI_TARGETCLI_NOT_INSTALLED}");
		return;
	}
	
	$html="<div id='mode-server-popup-table'></div>
	<script>
	LoadAjax('mode-server-popup-table','$page?mode-server-popup-table=yes');
	</script>
	";
	echo $html;
}
function mode_server_popup_table(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$sock=new sockets();
	
	// https://www.server-world.info/en/note?os=Debian_9&p=iscsi
	
	$ARRAY["PROGRESS_FILE"]=PROGRESS_DIR."/system_disks_iscsi_progress";
	$ARRAY["LOG_FILE"]=PROGRESS_DIR."/system_disks_iscsi_progress.txt";
	$ARRAY["CMD"]="iscsi.php?build-server-config=yes";
	$ARRAY["TITLE"]="{ISCSI_share}::{apply}";
	$ARRAY["AFTER"]="LoadAjax('mode-server-popup-table','$page?mode-server-popup-table=yes');";
	$prgress=base64_encode(serialize($ARRAY));
	$jsrestart="Loadjs('fw.progress.php?content=$prgress&mainid=progress-iscsi-restart')";
	
	$html[]="<div class='ibox-content'>
	<div class=\"btn-group\" data-toggle=\"buttons\">
	<label class=\"btn btn btn-primary\" OnClick=\"Loadjs('$page?mode-server-js=0');\"><i class='fa fa-plus'></i> {add_iscsi_disk} </label>
	<label class=\"btn btn btn-info\" OnClick=\"$jsrestart\"><i class='fa fa-plus'></i> {apply_parameters} </label>
	</div>";
	
	$html[]="<table id='table-iscsi-mod-server' class=\"footable table table-stripped\" data-page-size=\"100\" data-paging=\"true\">";
	$html[]="<thead>";
	$html[]="<tr>";
	$html[]="<th data-sortable=true style='width:1%'></th>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text' nowrap>{shared_folder}</th>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{hostname}</th>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{size}</th>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{status}</th>";
	$html[]="<th data-sortable=false style='width:1%'>DEL</th>";
	$html[]="</tr>";
	$html[]="</thead>";
	$html[]="<tbody>";	
	

	
	$iSCSIDumpArray=unserialize($GLOBALS["CLASS_SOCKETS"]->GET_INFO("iSCSIDumpArray"));
	
	$sql="SELECT * FROM iscsi_params ORDER BY ID DESC";
	$q=new mysql();
	$results=$q->QUERY_SQL($sql,'artica_backup');
	
	$class=null;
	$TRCLASS=null;
	$sock=new sockets();

	
	
	while($ligne=mysqli_fetch_array($results,MYSQLI_ASSOC)){
		$LOGSWHY=array();
		$Params=unserialize(base64_decode($ligne["Params"]));
		$overloaded=null;
		$size=$ligne["file_size"];
		$hostname=$ligne["hostname"];
		$type=$ligne["type"];
		$shared_folder=$ligne["shared_folder"];
		$ID=$ligne["ID"];
		$status="<span class='label'>{unknown}</span>";
		$icon_grey="ok32-grey.png";
		$icon_warning_32="warning32.png";
		$icon_red_32="32-red.png";
		$icon="ok-32.png";
		$icon_f=$icon_grey;
		$size_text="-";
		$hostname=$ligne["hostname"];
		$tbl=explode(".",$hostname);
		$newhostname=@implode(".",$tbl);
		$iqn=$Params["WWN"];
		
		if(isset($iSCSIDumpArray[$iqn])){
			$status="<span class='label label-primary'>&nbsp;OK&nbsp;</span>";
			
		}
		
		
		

		if($type=="file"){$size_text="{$size}G";}
	
		$md=md5(serialize($ligne));
	
		$html[]="<tr class='$TRCLASS' id='$md'>";
		$html[]="<td class=\"center\" width=1% nowrap><i class='{$class}".ico_hd."'></i>&nbsp;{$ligne["ID"]}</td>";
		$html[]="<td width=1% nowrap>". $tpl->td_href("$shared_folder ($type)","{click_to_edit}","Loadjs('$page?mode-server-js={$ligne["ID"]}');")."</td>";
		$html[]="<td>{$hostname} ($iqn)</td>";
		$html[]="<td>{$size_text}</td>";
		$html[]="<td width=1% nowrap>$status</td>";
		$html[]="<td>". $tpl->icon_delete("Loadjs('$page?mode-server-delete=$ID&md=$md')")."</td>";
		$html[]="</tr>";
	
	}	
	
	
	$html[]="</tbody>";
	$html[]="<tfoot>";
	
	$html[]="<tr>";
	$html[]="<td colspan='6'>";
	$html[]="<ul class='pagination pull-right'></ul>";
	$html[]="</td>";
	$html[]="</tr>";
	$html[]="</tfoot>";
	$html[]="</table>";
	$html[]="
	<script>
	NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS)."
	$(document).ready(function() { $('#table-iscsi-mod-server').footable( { \"filtering\": { \"enabled\": true }, \"sorting\": { \"enabled\": true },\"paging\": { \"size\": {$GLOBALS["FOOTABLE_PSIZE"]} } } ); });
	</script>";
	
	echo $tpl->_ENGINE_parse_body(@implode("\n", $html));	
}



function mode_client_popup_table(){
	$page=CurrentPageName();
	$tpl=new template_admin();

	$html[]="<div class='ibox-content'>
	<div class=\"btn-group\" data-toggle=\"buttons\">
	<label class=\"btn btn btn-primary\" OnClick=\"Loadjs('$page?mode-client-js=0');\"><i class='fa fa-plus'></i> {add_iscsi_connection} </label>
	 
	</div>";
	

	
	$html[]="<table id='table-iscsi-mod-client' class=\"footable table table-stripped\" data-page-size=\"100\" data-paging=\"true\">";
	$html[]="<thead>";
	$html[]="<tr>";
	$html[]="<th data-sortable=true style='width:1%'></th>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{ipaddr}:{port}</th>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{disk}</th>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{system}</th>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{directory}</th>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{status}</th>";
	$html[]="<th data-sortable=false style='width:1%'></th>";
	$html[]="<th data-sortable=false style='width:1%'></th>";
	$html[]="</tr>";
	$html[]="</thead>";
	$html[]="<tbody>";
	
	
	$sql="SELECT * FROM iscsi_client";
	$q=new mysql();
	$results=$q->QUERY_SQL($sql,'artica_backup');

	$class=null;
	$TRCLASS=null;
	$sock=new sockets();
	$sock->getFrameWork("iscsi.php?iscsi-sessions=yes");
	$array_sessions=unserialize(@file_get_contents(PROGRESS_DIR."/iscsi-sessions.array"));
	
	while (list ($ip, $subarray) = each ($array_sessions)){
		while (list ($ip, $subarray2) = each ($subarray)){
			if(preg_match("#(.+?)\s+\(#",$subarray2["FOLDER"],$re)){$subarray2["FOLDER"]=$re[1];}
			$ids="{$subarray2["ISCSI"]}:{$subarray2["PORT"]}:{$subarray2["FOLDER"]}";
			
			$MSESSIONS[$ids]=array("DEVNAME"=>$subarray2["DEVNAME"],"DEVSTATE"=>$subarray2["DEVSTATE"]);
		}
	}
	
	
	
	while($ligne=mysqli_fetch_array($results,MYSQLI_ASSOC)){
		if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
		$aclid=$ligne["ID"];
		$Params=$ligne["Params"];
		$status="<span class=label>{disconnected}</span>";
		$system=$tpl->icon_nothing();
		
		$subarray2=unserialize(base64_decode($Params));
		$ids="{$subarray2["ISCSI"]}:{$subarray2["PORT"]}:{$subarray2["FOLDER"]}";
		
		if(isset($MSESSIONS[$ids])){
			$status="<span class='label label-primary'>{connected}</span>";
			$system=$MSESSIONS[$ids]["DEVNAME"]." ({$MSESSIONS[$ids]["DEVSTATE"]})";
		}
		
		
		
		
		$md=md5($Params);
		
		$html[]="<tr class='$TRCLASS' id='$md'>";
		$html[]="<td class=\"center\" width=1% nowrap><i class='{$class}".ico_hd."'></i>&nbsp;{$ligne["ID"]}</td>";
		$html[]="<td width=1% nowrap>". $tpl->td_href("{$subarray2["IP"]}:{$subarray2["PORT"]}","{click_to_edit}","Loadjs('$page?mod-client-fiche-js=$aclid')")."</td>";
		$html[]="<td>{$subarray2["ISCSI"]}</td>";
		$html[]="<td>{$system}</td>";
		$html[]="<td>{$subarray2["FOLDER"]}</td>";
		$html[]="<td width=1% nowrap>$status</td>";
		$html[]="<td>". $tpl->icon_delete("Loadjs('$page?mode-client-delete=$aclid&md=$md')")."</td>";
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
	$html[]="
	<script>
	NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS)."
	$(document).ready(function() { $('#table-iscsi-mod-client').footable( { \"filtering\": { \"enabled\": true }, \"sorting\": { \"enabled\": true },\"paging\": { \"size\": {$GLOBALS["FOOTABLE_PSIZE"]} } } ); });
	</script>";
	
	echo $tpl->_ENGINE_parse_body(@implode("\n", $html));
}



