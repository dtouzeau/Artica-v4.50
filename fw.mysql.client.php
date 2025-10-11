<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
clean_xss_deep();
if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
if(isset($_GET["popup-start"])){popup_start();exit;}
if(isset($_GET["search"])){search();exit;}

js();


function js(){
	$page=CurrentPageName();
	$tpl=new template_admin();
    $users=new usersMenus();
    if(!$users->IsDBAdmin()){$tpl->js_no_privileges();exit();}
	$tpl->js_dialog6("{mysql_client}", "$page?popup-start=yes",1024);
}


function popup_start(){
    $users=new usersMenus();
    if(!$users->IsDBAdmin()){die();}
	$page=CurrentPageName();
	$tpl=new template_admin();
	$t=time();
	if(!isset($_SESSION["MYSQL_CLIENT_QUERY"])){$_SESSION["MYSQL_CLIENT_QUERY"]="SELECT * FROM mydatabase LIMIT 0,10";}
	
	if(intval($_SESSION["MYSQL_CLIENT_PT"])==0){$_SESSION["MYSQL_CLIENT_PT"]=3306;}

	
	$html="
	<table style='width:100%;border:0px;margin-top:10px'>
	<tr>
		<td style='border:0px;padding-left:5px;text-align:right' nowrap><span class=labelform>{mysql_server}</span>:</td>
		<td style='border:0px;padding-left:5px;'><input type='text' id='MYSQL_CLIENT_SRV' class='form-control' value='{$_SESSION["MYSQL_CLIENT_SRV"]}' style='font-size:14px;width:141px'></td>
		
		<td style='border:0px;padding-left:5px;text-align:right'nowrap><span class=labelform>{port}</span>:</td>
		<td style='border:0px;padding-left:5px;'><input type='text' id='MYSQL_CLIENT_PT' class='form-control' value='{$_SESSION["MYSQL_CLIENT_PT"]}' style='font-size:14px;width:71px'></td>
		
		<td style='border:0px;padding-left:5px;text-align:right' nowrap><span class=labelform>{database}</span>:</td>
		<td style='border:0px;padding-left:5px;'><input type='text' id='MYSQL_CLIENT_DB' class='form-control' value='{$_SESSION["MYSQL_CLIENT_DB"]}' style='font-size:14px'></td>
					
	
		<td style='border:0px;padding-left:5px;text-align:right' nowrap><span class=labelform>{mysql_username}</span>:</td>
		<td style='border:0px;padding-left:5px;'><input type='text' id='MYSQL_CLIENT_USER' class='form-control' value='{$_SESSION["MYSQL_CLIENT_USER"]}' style='font-size:14px'></td>
		
		<td style='border:0px;padding-left:5px;text-align:right' nowrap><span class=labelform>{password}</span>:</td>
		<td style='border:0px;padding-left:5px;'><input type='password' id='MYSQL_CLIENT_PASSWORD' class='form-control' value='{$_SESSION["MYSQL_CLIENT_PASSWORD"]}' style='font-size:14px'></td>
	</tr>
	</table>	
	<div class=\"row\" style='border:0px'>
	<div class='ibox-content' style='border:0px'>
	<div class=\"input-group\">
	<input type=\"text\" class=\"form-control\" value=\"{$_SESSION["MYSQL_CLIENT_QUERY"]}\" placeholder=\"{search}\" id='search-this-$t' OnKeyPress=\"javascript:Search$t(event);\">
	<span class=\"input-group-btn\"><button style=\"text-transform: capitalize;\" class=\"btn btn-default\" type=\"button\" OnClick=\"javascript:ss$t();\">Go!</button></span>
	</div>
	</div>
	</div>
	<div class='row'><div id='progress-firehol-restart'></div>
	<div class='ibox-content'>
	
	<div id='table-loader'></div>
	
	</div>
	</div>
	<script>
	function Search$t(e){
	if(!checkEnter(e) ){return;}
	ss$t();
	}
	
	function ss$t(){
	var MYSQL_CLIENT_SRV='';
	var MYSQL_CLIENT_PT='';
	var MYSQL_CLIENT_USER='';
	var MYSQL_CLIENT_PASSWORD='';
	var MYSQL_CLIENT_DB='';
	var MyArray = [];
	
	MYSQL_CLIENT_SRV=encodeURIComponent(document.getElementById('MYSQL_CLIENT_SRV').value);
	MYSQL_CLIENT_PT=encodeURIComponent(document.getElementById('MYSQL_CLIENT_PT').value);
	MYSQL_CLIENT_USER=encodeURIComponent(document.getElementById('MYSQL_CLIENT_USER').value);
	MYSQL_CLIENT_PASSWORD=encodeURIComponent(document.getElementById('MYSQL_CLIENT_PASSWORD').value);
	MYSQL_CLIENT_DB=encodeURIComponent(document.getElementById('MYSQL_CLIENT_DB').value);
	
	MyArray.push('MYSQL_CLIENT_SRV='+MYSQL_CLIENT_SRV);
	MyArray.push('MYSQL_CLIENT_PT='+MYSQL_CLIENT_PT); 
	MyArray.push('MYSQL_CLIENT_USER='+MYSQL_CLIENT_USER); 
	MyArray.push('MYSQL_CLIENT_PASSWORD='+MYSQL_CLIENT_PASSWORD);
	MyArray.push('MYSQL_CLIENT_DB='+MYSQL_CLIENT_DB);  
	
	var add=MyArray.join('&');
	var ss=encodeURIComponent(document.getElementById('search-this-$t').value);
	LoadAjax('table-loader','$page?search='+ss+'&'+add);
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
    $users=new usersMenus();
    if(!$users->IsDBAdmin()){die();}
	$tpl=new template_admin();
	$tpl->CLEAN_POST();
	$_SESSION["MYSQL_CLIENT_QUERY"]=$_GET["search"];
	foreach ($_GET as $key=>$val){

		$_SESSION[$key]=$val;
		
	}
	if(intval($_SESSION["MYSQL_CLIENT_PT"])==0){$_SESSION["MYSQL_CLIENT_PT"]=3306;}
	if($_SESSION["MYSQL_CLIENT_SRV"]==null){
		echo $tpl->FATAL_ERROR_SHOW_128("{mysql_server} {cannot_be_a_null_value}");
		return;
	}
	if($_SESSION["MYSQL_CLIENT_DB"]==null){
		echo $tpl->FATAL_ERROR_SHOW_128("{database} {cannot_be_a_null_value}");
		return;
	}	
	if($_SESSION["MYSQL_CLIENT_USER"]==null){
		echo $tpl->FATAL_ERROR_SHOW_128("{mysql_username} {cannot_be_a_null_value}");
		return;
	}
    $_SESSION["MYSQL_CLIENT_DB"]=$tpl->CLEAN_BAD_XSS($_SESSION["MYSQL_CLIENT_DB"]);
    $_SESSION["MYSQL_CLIENT_USER"]=$tpl->CLEAN_BAD_XSS($_SESSION["MYSQL_CLIENT_USER"]);
    $_SESSION["MYSQL_CLIENT_SRV"]=$tpl->CLEAN_BAD_CHARSNET($_SESSION["MYSQL_CLIENT_SRV"]);
	
	$sql=$_GET["search"];
	
	$db=client_connect();
	if(!$db){return;}
	
	$results=@mysqli_query($db,$sql);
	
	
	if(!$results){
		$errnum=@mysqli_errno($db);
		$des=@mysqli_error($db);
		@mysqli_close($db);
		echo $tpl->_ENGINE_parse_body("<div class='alert alert-danger'>{failed} &laquo;<strong>{query}</strong>&raquo;<br>
				<hr>
				<strong><code>{$_SESSION["MYSQL_CLIENT_USER"]}@{$_SESSION["MYSQL_CLIENT_SRV"]}:{$_SESSION["MYSQL_CLIENT_PT"]}/{$_SESSION["MYSQL_CLIENT_DB"]}
				<br>$sql
				</code></strong>
				<hr>
				N.$errnum \"$des\"</div>");
		return;
	}
	
	$finfo = mysqli_fetch_fields($results);
	
	$tableid=md5($sql);
	
	$html[]="<table id='$tableid' class=\"footable table table-stripped\" data-page-size=\"100\" data-paging=\"true\">";
	$html[]="<thead>";
	$html[]="<tr>";
	foreach ($finfo as $val) {
		$FF[]=$val->name;
		$FieldName=$val->name;
		$html[]="<th data-sortable=true class='text-capitalize'>$FieldName</th>";
	}
	
	if(count($FF)==0){
		echo $tpl->_ENGINE_parse_body("<div class='alert alert-warning'><strong>{query_success_but_nothing_returned}</strong></div>");
		return;
	}

	$html[]="</tr>";
	$html[]="</thead>";
	$html[]="<tbody>";
	
	while($ligne=@mysqli_fetch_array($results,MYSQLI_ASSOC)){
		if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
		$md=md5(serialize($md));
		$html[]="<tr class='$TRCLASS' id='$md'>";
		reset($FF);
		foreach ($FF as $column) {
			$html[]="<td>{$ligne[$column]}</td>";
		}
		
		
	}
	
	$html[]="</tbody>";
	$html[]="<tfoot>";
	
	$html[]="<tr>";
	$html[]="<td colspan='".count($FF)."'>";
	$html[]="<ul class='pagination pull-right'></ul>";
	$html[]="</td>";
	$html[]="</tr>";
	$html[]="</tfoot>";
	$html[]="</table>";
	$html[]="<div><small></small></div>
	<script>
	NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS)."
	$(document).ready(function() { $('#$tableid').footable( { \"filtering\": { \"enabled\": true }, \"sorting\": { \"enabled\": true },\"paging\": { \"size\": {$GLOBALS["FOOTABLE_PSIZE"]} } } ); });
	</script>";
	mysqli_free_result($results);
	mysqli_close($db);
	
		echo @implode("\n", $html);
		
	
	
		
	
}


function client_connect(){
	$tpl=new template_admin();
	$bd=@mysqli_connect("{$_SESSION["MYSQL_CLIENT_SRV"]}",$_SESSION["MYSQL_CLIENT_USER"],$_SESSION["MYSQL_CLIENT_PASSWORD"],$_SESSION["MYSQL_CLIENT_DB"],$_SESSION["MYSQL_CLIENT_PT"],null);
		
	if (mysqli_connect_errno()){
		$mysqli_connect_errno=mysqli_connect_errno();
		$mysqli_connect_error=mysqli_connect_error();
		echo $tpl->FATAL_ERROR_SHOW_128("{failed} &laquo;<strong>{connect}</strong>&raquo;<br>
		<hr>
		<strong><code>{$_SESSION["MYSQL_CLIENT_USER"]}@{$_SESSION["MYSQL_CLIENT_SRV"]}:{$_SESSION["MYSQL_CLIENT_PT"]}/{$_SESSION["MYSQL_CLIENT_DB"]}</code></strong><hr>
		N.$mysqli_connect_errno \"$mysqli_connect_error\"");
		return false;
	}
	return $bd;
}


