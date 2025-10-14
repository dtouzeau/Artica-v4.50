<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.system.network.inc");
include_once(dirname(__FILE__)."/ressources/class.memcached.inc");
include_once(dirname(__FILE__)."/ressources/class.familysites.inc");

if(isset($_GET["table"])){table();exit;}
if(isset($_GET["delete-js"])){delete_js();exit;}
if(isset($_POST["delete"])){delete();exit;}
if(isset($_GET["accept"])){accept();exit;}
if(isset($_GET["empty-js"])){empty_js();exit;}
if(isset($_POST["empty"])){empty_table();exit;}

page();


function page(){
	$page=CurrentPageName();
	echo "<div style='margin-top:10px' id='ufdbweb-requests-div'></div>
	<script>LoadAjax('ufdbweb-requests-div','$page?table=yes');</script>
	";
}

function empty_js(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$tpl->js_confirm_empty("{requests_list}", "empty", "yes","LoadAjax('ufdbweb-requests-div','$page?table=yes');");
}
function empty_table(){
	$q=new lib_sqlite("/home/artica/SQLITE/webfilter.db");
	$q->QUERY_SQL("DELETE FROM webfilters_usersasks");
}

function delete_js(){
	$md5=$_GET["delete-js"];
	$page=CurrentPageName();
	$tpl=new template_admin();
	$q=new lib_sqlite("/home/artica/SQLITE/webfilter.db");
	$ligne=$q->mysqli_fetch_array("SELECT * FROM webfilters_usersasks WHERE `zmd5`='$md5'");
	$uid=$ligne["uid"];
	$ipaddr=$ligne["ipaddr"];
	$www=$ligne["sitename"];
	$tpl->js_confirm_delete("$uid/$ipaddr ($www)", "delete", $md5,"$('#$md5').remove()");
	
}

function delete(){
	$md5=$_POST["delete"];
	$q=new lib_sqlite("/home/artica/SQLITE/webfilter.db");
	$q->QUERY_SQL("DELETE FROM webfilters_usersasks WHERE `zmd5`='$md5'");
	if(!$q->ok){echo $q->mysql_error;return;}
    admin_tracks("Remove unblock ticket $md5");
}

function accept():bool{
    $tpl=new template_admin();
	$md5=$_GET["accept"];
	$q=new lib_sqlite("/home/artica/SQLITE/webfilter.db");
	$ligne=$q->mysqli_fetch_array("SELECT * FROM webfilters_usersasks WHERE `zmd5`='$md5'");

    $decoded=base64_decode($ligne["info"]);
    if(strpos($decoded,"||")>0){
        $exploded=explode("||",$decoded);
        $MAIN["CATEGORY"]=$exploded[1];
        $MAIN["IPADDR"]=$exploded[2];
        //$MAIN["CLIENT_HOSTNAME"]=$exploded[3];
        //$MAIN["URL"]=$exploded[0];
        //$MAIN["CATEGORY_NAME"]=$exploded[11];
        //$MAIN["UFDBGRULE"]=$exploded[12];
        $MAIN["USERID"]=$exploded[4];
        $MAIN["SITENAME"]=$exploded[7];
        $MAIN["maxtime"]=0;

    }else {
        $MAIN = unserialize($decoded);
        if (!is_array($MAIN)) {
            echo $tpl->js_mysql_alert("Not an array!");
            return false;
        }
    }

    if(preg_match("#^(www|ww1|ww2|ww3)\.(.+)#",$MAIN["SITENAME"],$re)){
        $MAIN["SITENAME"]=$re[2];
    }
    if(!isset($MAIN["FAMILYSITE"])){
        $fam=new familysite();
        $MAIN["FAMILYSITE"]=$fam->GetFamilySites($MAIN["SITENAME"]);
    }

	//$ufdb_page_rules_md5=$MAIN["UFDBGRULE"];
	//$SITENAME=$MAIN["SITENAME"];
    //$URL=$MAIN["URL"];
    //$IPADDR=$MAIN["IPADDR"];
    //$_CATEGORIES_K=$MAIN["CATEGORY"];
    //$CATEGORY_NAME=$MAIN["CATEGORY_NAME"];
    //$CLIENT_HOSTNAME=$MAIN["CLIENT_HOSTNAME"];
    //$templateid=$MAIN["TEMPLATE_ID"];
    $FAMILYSITE=$MAIN["FAMILYSITE"];
	$maxtime=$MAIN["maxtime"];
	$uid=$MAIN["USERID"];
	$IPADDR=$MAIN["IPADDR"];
	if($maxtime==0){$MAX="5184000";}else{$MAX=$maxtime;}
    $EnOfLife = strtotime("+{$MAX} minutes", time());



	
	$FFIELDS[]="`md5`";
	$FFIELDS[]="`logintime`";
	$FFIELDS[]="`finaltime`";
	$FFIELDS[]="`uid`";
	$FFIELDS[]="`www`";
	$FFIELDS[]="`ipaddr`";
	$FFIELDS[]="`details`";
	
	$FFDATA[]="'$md5'";
	$FFDATA[]=time();
	$FFDATA[]=$EnOfLife;
	$FFDATA[]="'$uid'";
	$FFDATA[]="'$FAMILYSITE'";
	$FFDATA[]="'$IPADDR'";
	$FFDATA[]="'{$ligne["info"]}'";
	$sql="INSERT OR IGNORE INTO ufdbunlock (".@implode(",", $FFIELDS).") VALUES (".@implode(",", $FFDATA).")";
	$q->QUERY_SQL($sql);
	if(!$q->ok){echo $tpl->js_mysql_alert($q->mysql_error);return false;}
	$q->QUERY_SQL("DELETE FROM webfilters_usersasks WHERE `zmd5`='$md5'");
	
	header("content-type: application/x-javascript");
	echo "$('#$md5').remove();\n";
	
	$distance=distanceOfTimeInWords(time(),$EnOfLife);
	$text=$tpl->_ENGINE_parse_body("{success} $IPADDR/$uid {to} *.$FAMILYSITE $distance");
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/proxy/whitelists/nohupcompile");
    admin_tracks("Accept unblock ticket $md5");
	echo $tpl->js_display_results($text);
    return true;
	
}
function table(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	//zmd5,ipaddr,sitename,uid,`info`,zdate

    $btns[]="<div class=\"btn-group\" data-toggle=\"buttons\">";
    $btns[]="<label class=\"btn btn btn-danger\" OnClick=\"Loadjs('$page?empty-js=yes&function={$_GET["function"]}');\"><i class='fas fa-trash-alt'></i> {empty} </label>";
    $btns[]="</div>";
	$html[]="<table id='ufdbweb-requests-table' class=\"footable table table-stripped\" data-page-size=\"100\" data-paging=\"true\">";
	$html[]="<thead>";
	$html[]="<tr>";
	$html[]="<th data-sortable=true class='text-capitalize' >{website}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' >{ticket}</th>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{member}</th>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{created}</th>";
	$html[]="<th data-sortable=false>{accept}</th>";
	$html[]="<th data-sortable=false>{delete}</th>";
	$html[]="</tr>";
	$html[]="</thead>";
	$html[]="<tbody>";
	
	
	
	$q=new lib_sqlite("/home/artica/SQLITE/webfilter.db");
	$sql="SELECT * FROM `webfilters_usersasks` ORDER BY zDate LIMIT 500";
	$results=$q->QUERY_SQL($sql);
	if(!$q->ok){echo $tpl->FATAL_ERROR_SHOW_128($q->mysql_error);return;}
	$sst="style='width:1%' nowrap";
	$TRCLASS=null;
	foreach ($results as $index=>$ligne){
		$zmd5=$ligne["zmd5"];
		$zDate=$ligne["zDate"];
		$uid=$ligne["uid"];
		if($uid=="unknown"){$uid=null;}
		if($uid==null){$uid=$tpl->icon_nothing();}
		$ipaddr=$ligne["ipaddr"];
		$www=$ligne["sitename"];
		$delete=$tpl->icon_delete("Loadjs('$page?delete-js=$zmd5')","AsProxyMonitor");
		if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
		$html[]="<tr class='$TRCLASS' id='$zmd5'>";
		$html[]="<td><strong>$www</strong></td>";
        $html[]="<td $sst><strong>$zmd5</strong></td>";
		$html[]="<td $sst>$uid/$ipaddr</td>";
		$distance=distanceOfTimeInWords(time(),$zDate);
		
		$accept=$tpl->icon_check(0,"Loadjs('$page?accept=$zmd5')","AsProxyMonitor");
		
		$html[]="<td $sst>".$tpl->time_to_date($zDate,true)."<br><small>$distance</small></td>";
		$html[]="<td class='center' $sst>$accept</center></td>";
		$html[]="<td class='center' $sst>$delete</center></td>";
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

    $TINY_ARRAY["TITLE"]="{requests_list}";
    $TINY_ARRAY["ICO"]="fa-solid fa-user-tag";
    $TINY_ARRAY["EXPL"]="{web_page_error_request_explain}";
    $TINY_ARRAY["BUTTONS"]=@implode("",$btns);
    $jstiny="Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";

    $html[]="
	<script>
	NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS)."
	$(document).ready(function() { $('#ufdbweb-requests-table').footable( { \"filtering\": { \"enabled\": true }, \"sorting\": { \"enabled\": true },\"paging\": { \"size\": {$GLOBALS["FOOTABLE_PSIZE"]} } } ); });
	$jstiny
</script>";	
	echo $tpl->_ENGINE_parse_body($html);
	
}