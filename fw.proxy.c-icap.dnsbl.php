<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(isset($_GET["dnsbl"])){dnsbl_js();exit;}
if(isset($_POST["dnsbl"])){dnsbl_save();exit;}
if(isset($_POST["ID"])){rule_save();exit;}
if(isset($_GET["dnsbl-delete-js"])){dnsbl_delete_js();exit;}
if(isset($_GET["enable-dnsbl"])){dnsbl_enable_js();exit;}
if(isset($_POST["dnbsl-delete"])){dnsbl_delete();exit;}
if(isset($_GET["table"])){table();exit;}
if(isset($_GET["popup"])){popup();exit;}
if(isset($_GET["enable-feature"])){enable_feature();exit;}
if(isset($_GET["apply"])){echo reconfigure();exit;}
page();

function dnsbl_js(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$ID=$_GET["dnsbl"];
	$title="{new_item}";
	if($ID<>null){$title=$ID;}
	$tpl->js_dialog1($title, "$page?popup=$ID");

}

function dnsbl_delete_js(){
	$tpl=new template_admin();
	$ID=intval($_GET["delete-rule-js"]);
	$md5=$_GET["md"];
	$tpl->js_confirm_delete($ID, "dnbsl-delete", $ID,"$('#$md5').remove()");
}
function dnsbl_delete(){
	$ID=$_POST["dnbsl-delete"];
	$q=new lib_sqlite("/home/artica/SQLITE/webfilter.db");
	$q->QUERY_SQL("DELETE FROM webfilter_dnsbl WHERE dnsbl='$ID'");
}

function popup(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$ID=$_GET["popup"];
	$q=new lib_sqlite("/home/artica/SQLITE/webfilter.db");
	$ligne=$q->mysqli_fetch_array("SELECT * FROM webfilter_dnsbl WHERE dnsbl='$ID'");
	$btn="{add}";
	$jsafter="dialogInstance1.close();LoadAjax('table-ksrn-dnsbl','$page?table=yes');";
	if($ID<>null){
		$btn="{apply}";
		$jsafter="dialogInstance1.close();";
	}
    $form[]=$tpl->field_text("dnsbl", "{dnsbl_service}", $ID,true);
    $form[]=$tpl->field_text("name", "{description}", $ligne["name"]);
    $form[]=$tpl->field_text("uri", "{description} ({link})", $ligne["uri"]);
    $form[]=$tpl->field_text("tokens", "{parameters} (eg: 127.0.0.1,127.0.0.2)", $ligne["tokens"]);
    echo $tpl->form_outside("{dnsbl_service}", $form,"",$btn,$jsafter,"AsOrgAdmin");
	
}
function dnsbl_enable_js(){
	$dnsbl=$_GET["enable-dnsbl"];
	$q=new lib_sqlite("/home/artica/SQLITE/webfilter.db");
	$ligne=$q->mysqli_fetch_array("SELECT enabled FROM webfilter_dnsbl WHERE dnsbl='$dnsbl'");
	$enabled=intval($ligne["enabled"]);
	if($enabled==1){
		$sql="UPDATE webfilter_dnsbl SET enabled=0 WHERE dnsbl='$dnsbl';";
		$q->QUERY_SQL($sql);
		if(!$q->ok){echo $q->mysql_error;}
	}else{
		$sql="UPDATE webfilter_dnsbl SET enabled=1 WHERE dnsbl='$dnsbl';";
		$q->QUERY_SQL($sql);
		if(!$q->ok){echo $q->mysql_error;}	
	}
}

function dnsbl_save(){
	$tpl=new template_admin();
	$tpl->CLEAN_POST();
	$ID=$_POST["dnsbl"];
	$uid=$_SESSION["uid"];
	if($uid==-100){$uid="Manager";}
	$q=new lib_sqlite("/home/artica/SQLITE/webfilter.db");
	$date=date("Y-m-d H:i:s");

	$q->QUERY_SQL("DELETE FROM webfilter_dnsbl WHERE dnsbl='$ID'");
    $sql="INSERT INTO webfilter_dnsbl (dnsbl,name,uri,enabled,tokens) VALUES ('{$_POST["dnsbl"]}','$uid: $date: {$_POST["name"]}','{$_POST["uri"]}','1','{$_POST["tokens"]}')";
    $q->QUERY_SQL($sql);
	if(!$q->ok){echo $q->mysql_error;}

	
}

function page(){
	$page=CurrentPageName();
	$tpl=new template_admin();
    checkDNSBLTables();


	$html="
	<div class=\"row border-bottom white-bg dashboard-header\">
	    <div class=\"col-sm-12\"><h1 class=ng-binding>{reputation_services} ({CICAP_DNSBL})</h1>
	    <p>{dnsbl_explain}</p>
	</div>
	</div>
	<div class='row'>
	<div id='progress-ksrn-dnsbl-restart'></div>
	<div class='ibox-content' style='min-height:600px'>
	<div id='table-ksrn-dnsbl'></div>
	</div>
	</div>



	<script>
	LoadAjax('table-ksrn-dnsbl','$page?table=yes');
	$.address.state('/');
	$.address.value('/icap-dnsbl');
	$.address.title('Artica: DNSBL');
	</script>";

	if(isset($_GET["main-page"])){
		$tpl=new template_admin("Artica: ClamAV Whitelist",$html);
		echo $tpl->build_firewall();
		return;
	}


	echo $tpl->_ENGINE_parse_body($html);

}

function enable_feature(){
    $EnableKSRNDNSBL=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableKSRNDNSBL"));
    if($EnableKSRNDNSBL==1){
        $GLOBALS["CLASS_SOCKETS"]->SET_INFO("EnableKSRNDNSBL",0);
    }else{
        $GLOBALS["CLASS_SOCKETS"]->SET_INFO("EnableKSRNDNSBL",1);
    }
    echo reconfigure();
    return true;
}
function reconfigure():string{
    $page=CurrentPageName();
    $ARRAY["PROGRESS_FILE"]=PROGRESS_DIR."/ksrn.progress";
    $ARRAY["LOG_FILE"]=PROGRESS_DIR."/ksrn.log";
    $ARRAY["CMD"]="ksrn.php?dnsbl=yes";
    $ARRAY["TITLE"]="{reconfigure}";
    $ARRAY["AFTER"]="LoadAjax('table-ksrn-dnsbl','$page?table=yes');";
    $prgress=base64_encode(serialize($ARRAY));
    header("content-type: application/x-javascript");
    return "Loadjs('fw.progress.php?content=$prgress&mainid=progress-ksrn-dnsbl-restart')";
}
function jscache(){
    $ARRAY["PROGRESS_FILE"]=PROGRESS_DIR."/ksrn.progress";
    $ARRAY["LOG_FILE"]=PROGRESS_DIR."/ksrn.log";
    $ARRAY["CMD"]="ksrn.php?dnsbl-cache=yes";
    $ARRAY["TITLE"]="{clean_cache}";
    $prgress=base64_encode(serialize($ARRAY));
    header("content-type: application/x-javascript");
    return "Loadjs('fw.progress.php?content=$prgress&mainid=progress-ksrn-dnsbl-restart')";
}

function table(){
	$tpl=new template_admin();
	$page=CurrentPageName();
    $EnableKSRNDNSBL=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableKSRNDNSBL"));
	$q=new lib_sqlite("/home/artica/SQLITE/webfilter.db");
    $sql="CREATE TABLE IF NOT EXISTS `webfilter_dnsbl` (
				  `dnsbl` TEXT UNIQUE,
				  `name` TEXT NOT NULL,
				  `uri` TEXT NOT NULL ,
				  `enabled` INTEGER DEFAULT '1'
				)";
	$q->QUERY_SQL($sql);
    $q->QUERY_SQL("DELETE FROM webfilter_dnsbl WHERE dnsbl=''");

    if(!$q->FIELD_EXISTS("webfilter_dnsbl","tokens")){$q->QUERY_SQL("ALTER TABLE webfilter_dnsbl ADD tokens TEXT NULL");}

    $pacth["dbl.spamhaus.org"]="127.0.0.1,127.0.0.2";
    $sprefix="INSERT INTO webfilter_dnsbl (dnsbl,name,uri,enabled,tokens) VALUES";

    foreach ($pacth as $key=>$tokens){
        if(trim($key)==null){continue;}
        $found=$q->mysqli_fetch_array("SELECT name from webfilter_dnsbl WHERE dnsbl='$key'");
        if(strlen(trim($found["name"]))==0){
            $q->QUERY_SQL("$sprefix ('$key','$key','','1','$tokens')");
            if(!$q->ok){
                echo $tpl->FATAL_ERROR_SHOW_128("dnsbl:[$key]<br>".$q->mysql_error);
            }
        }
    }







	if(!$q->ok){
		echo $tpl->FATAL_ERROR_SHOW_128($q->mysql_error);return;
	}

	$t          = time();
	$jsenable   = "Loadjs('$page?enable-feature');";
	$add        = "Loadjs('$page?dnsbl=');";
    $jscache    = jscache();


    //fas fa-plug <i class="fas fa-play-circle"></i>  //<i class="fas fa-stop-circle"></i>
	$html[]="<div class=\"btn-group\" data-toggle=\"buttons\">";
	$html[]="<label class=\"btn btn btn-primary\" OnClick=\"$add\"><i class='fa fa-plus'></i> {new_item} </label>";
	if($EnableKSRNDNSBL==1) {
        $html[] = "<label class=\"btn btn-info\" OnClick=\"$jsenable;\"><i class='fas fa-play-circle'></i> {disable_feature} </label>";
        $html[] = "<label class=\"btn btn-primary\" OnClick=\"Loadjs('$page?apply=yes');\"><i class='fa fa-save'></i> {apply_rules} </label>";
    }else{
        $html[] = "<label class=\"btn btn-default\" OnClick=\"$jsenable;\"><i class='fas fa-stop-circle'></i> {enable_feature} </label>";
    }
    $html[] = "<label class=\"btn btn-info\" OnClick=\"$jscache;\">
                <i class='fa fa-trash'></i> {clean_cache} </label>";

	$html[]="</div>";
	$html[]="<table id='table-$t' class=\"footable table table-stripped\" data-page-size=\"100\" data-paging=\"true\">";
	$html[]="<thead>";
	$html[]="<tr>";
	$html[]="<th data-sortable=true data-type='text'>{dnsbl_service}</th>";
	$html[]="<th data-sortable=true data-type='text'>{description}</th>";
	$html[]="<th data-sortable=true data-type='text'>{enabled}</th>";
	$html[]="<th data-sortable=false>{delete}</th>";
	$html[]="</tr>";
	$html[]="</thead>";
	$html[]="<tbody>";
	
	$jsAfter="LoadAjax('table-loader-webhttp-rules','$page?table=yes&eth={$_GET["eth"]}');";
	$GLOBALS["jsAfterEnc"]=base64_encode($jsAfter);
	
	
	
	$TRCLASS=null;
	
	$results=$q->QUERY_SQL("SELECT * FROM webfilter_dnsbl ORDER BY dnsbl");
	
	
	foreach ($results as $index=>$ligne){
		if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
		$zmd5=md5(serialize($ligne));
		$dnsbl=$ligne["dnsbl"];
        $name=$ligne["name"];
        $uri=$ligne["uri"];
        $enabled=intval($ligne["enabled"]);
        $dnsblmd=urlencode($dnsbl);
        $js="Loadjs('$page?dnsbl=$dnsbl')";
        $disable=false;
        $check=$tpl->icon_check($enabled,"Loadjs('$page?enable-dnsbl=$dnsblmd')",null,"AsOrgAdmin");

        if(preg_match("#mailpolice\.#",$dnsbl)){$disable=true;}
        if($ligne["dnsbl"]=="multi.surbl.org"){ $disable=true;}

        if(preg_match("#^http(s|):\/\/#",$uri)){
            $uri=$tpl->td_href($uri,null,"s_PopUpFull('$uri','1024','900');");
        }
        if($disable){$check=$tpl->icon_nothing();}

		
		$html[]="<tr class='$TRCLASS' id='$zmd5'>";
		$html[]="<td width=1% nowrap><strong>".
            $tpl->td_href($dnsbl,null,$js)."</strong>&nbsp;<small>($uri)</small></td>";
		$html[]="<td>". $tpl->td_href($name,null,$js)."</td>";
		$html[]="<td width=1% class='center' nowrap>$check</td>";
		$html[]="<td width=1% class='center' nowrap>".$tpl->icon_delete("Loadjs('$page?dnsbl-delete-js=$dnsblmd&md=$zmd5')","AsSystemAdministrator") ."</center></td>";
		$html[]="</tr>";		
		
		
	}
	
	$html[]="</tbody>";
	$html[]="<tfoot>";
	
	$html[]="<tr>";
	$html[]="<td colspan='4'>";
	$html[]="<ul class='pagination pull-right'></ul>";
	$html[]="</td>";
	$html[]="</tr>";
	$html[]="</tfoot>";
	$html[]="</table>";
	$html[]="
	<script>
	NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS)."
	$(document).ready(function() { $('#table-$t').footable( { \"filtering\": { \"enabled\": true }, \"sorting\": { \"enabled\": true },\"paging\": { \"size\": {$GLOBALS["FOOTABLE_PSIZE"]} } } ); });
</script>";
	
	echo $tpl->_ENGINE_parse_body(@implode("\n", $html));
}

function checkDNSBLTables(){

    $tpl=new template_admin();
    $q=new lib_sqlite("/home/artica/SQLITE/webfilter.db");
    if($q->COUNT_ROWS("webfilter_dnsbl")>2){return;}
    $f=@file_get_contents(dirname(__FILE__)."/ressources/databases/db.surbl.txt");
    $prefix="INSERT OR IGNORE INTO webfilter_dnsbl(`dnsbl`,`name`,`uri`,`enabled`) VALUES ";
    if(preg_match_all("#<server>(.+?)</server>#is",$f,$servers)){
        foreach ($servers[0] as $line){
            if(preg_match("#<item>(.+?)</item>#",$line,$re)){$server_uri=$re[1];}
            if(preg_match("#<name>(.+?)</name>#",$line,$re)){$name=$re[1];}
            if(preg_match("#<uri>(.+?)</uri>#",$line,$re)){$info=$re[1];}
            $name=$q->sqlite_escape_string2($name);
            $info=$q->sqlite_escape_string2($info);
            $SQ[]="('$server_uri','$name','$info',0)";
        }

    }else{
        echo $tpl->div_error("preg_match failed...");
    }
    if(count($SQ)>0){
        $q->QUERY_SQL($prefix.@implode($SQ, ","));
        if(!$q->ok){

            echo $tpl->div_error($q->mysql_error);
        }
    }
}