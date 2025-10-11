<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.milter.greylist.inc");

if(isset($_GET["status"])){status();exit;}
if(isset($_GET["tabs"])){tabs();exit;}
if(isset($_GET["table"])){table();exit;}
if(isset($_GET["search"])){search();exit;}
if(isset($_GET["record-js"])){record_js();exit;}
if(isset($_GET["record-popup"])){record_popup();exit;}
if(isset($_GET["explain-type"])){explain_type();exit;}
if(isset($_POST["SaveAclID"])){record_save();exit;}
if(isset($_GET["delete-js"])){delete_js();exit;}
if(isset($_POST["delete"])){delete();exit;}
if(isset($_GET["smtpd-milter-maps-start"])){smtpd_milter_maps_start();exit;}
if(isset($_GET["smtpd-milter-maps-table"])){smtpd_milter_maps_table();exit;}
if(isset($_GET["smtpd-milter-maps-js"])){smtpd_milter_maps_js();exit;}
if(isset($_GET["smtpd-milter-maps-popup"])){smtpd_milter_maps_popup();exit;}
if(isset($_POST["smtpd-milter-item"])){smtpd_milter_maps_save();exit;}
if(isset($_GET["smtpd-milter-maps-enable"])){smtpd_milter_maps_enable();exit;}
if(isset($_GET["smtpd-milter-maps-delete"])){smtpd_milter_maps_delete();exit;}
page();


function smtpd_milter_maps_start(){
	$page=CurrentPageName();
    $instance_id=intval($_GET["instance-id"]);
	echo "<div id='smtpd-milter-maps-div' style='margin-top:10px'></div><script>LoadAjax('smtpd-milter-maps-div','$page?smtpd-milter-maps-table=yes&instance-id=$instance_id');</script>";
}

function delete_js(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$id=$_GET["delete-js"];
	$md=$_GET["id"];
	$q=new postgres_sql();
	$sql="SELECT pattern FROM miltergreylist_acls WHERE ID='$id'";
	$ligne=$q->mysqli_fetch_array($sql);
	$tpl->js_confirm_delete($ligne["pattern"], "delete", $id,"$('#$md').remove()");
	
}
function delete(){
	$q=new postgres_sql();
	$id=$_POST["delete"];
	$q->QUERY_SQL("DELETE FROM miltergreylist_acls WHERE ID='$id'");
	if(!$q->ok){echo $q->mysql_error;return false;}
    $sock=new sockets();
    $sock->REST_API("/postfix/smtpd/restrictions/0");
    return admin_tracks_post("Delete miltergreylist_acls rule for SMTP instance id 0");
}

function smtpd_milter_maps_js(){
    $instance_id=intval($_GET["instance-id"]);
	$page=CurrentPageName();
	$tpl=new template_admin();
	$title="{new_entry}";
	$tpl->js_dialog1("$title", "$page?smtpd-milter-maps-popup=yes&instance-id=$instance_id",850);
	return;
}

function smtpd_milter_maps_popup(){
	$page=CurrentPageName();
	$tpl=new template_admin();
    $instance_id=intval($_GET["instance-id"]);

    $form[]=$tpl->field_hidden("instance_id",$instance_id);
	$form[]=$tpl->field_text("smtpd-milter-item", "{pattern}", null,true);
	
	echo $tpl->form_outside("{new_item}", $form,"{smtpd-milter-maps-explain}{smtpd-milter-maps-explain2}",
			"{add}","dialogInstance1.close();LoadAjax('smtpd-milter-maps-div','$page?smtpd-milter-maps-table=yes&instance-id=$instance_id');","AsPostfixAdministrator");
	
}

function smtpd_milter_maps_enable(){
	$tpl=new template_admin();
	$ID=intval($_GET["smtpd-milter-maps-enable"]);
	$q=new lib_sqlite("/home/artica/SQLITE/postfix.db");
	$ligne=$q->mysqli_fetch_array("SELECT enabled FROM smtpd_milter_maps WHERE ID=$ID");
	if(intval($ligne["enabled"])==1){
		$q->QUERY_SQL("UPDATE smtpd_milter_maps SET enabled=0 WHERE ID=$ID");
		if(!$q->ok){$tpl->js_mysql_alert($q->mysql_error);}
		return;
	}
	$q->QUERY_SQL("UPDATE smtpd_milter_maps SET enabled=1 WHERE ID=$ID");
	if(!$q->ok){$tpl->js_mysql_alert($q->mysql_error);}
}

function smtpd_milter_maps_delete(){
	$tpl=new template_admin();
	$md=$_GET["md"];
	$ID=intval($_GET["smtpd-milter-maps-delete"]);
	$q=new lib_sqlite("/home/artica/SQLITE/postfix.db");
	$q->QUERY_SQL("DELETE FROM smtpd_milter_maps WHERE ID=$ID");
	if(!$q->ok){$tpl->js_mysql_alert($q->mysql_error);return;}
	echo "$('#$md').remove();\n";
	
}

function smtpd_milter_maps_save(){
	$tpl=new template_admin();
	$tpl->CLEAN_POST();
    $instance_id=intval($_POST["instance_id"]);
	$pattern=$_POST["smtpd-milter-item"];
	$sql="INSERT INTO smtpd_milter_maps (pattern,enabled,instanceid) VALUES ('$pattern',1,$instance_id)";
	$q=new lib_sqlite("/home/artica/SQLITE/postfix.db");
	$q->QUERY_SQL($sql);
	if(!$q->ok){echo $q->mysql_error;}
}

function record_js(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$id=$_GET["record-js"];
	$method=$_GET["method"];
	$function=$_GET["function"];
	$title="{new_entry}";
	
	
	if($id==0){
		$tpl->js_dialog1("{{$method}}::$title", "$page?record-popup=$id&method=$method&function=$function");
		return;
	}
	$q=new postgres_sql();
	$sql="SELECT pattern FROM miltergreylist_acls WHERE ID='$id'";
	$ligne=$q->mysqli_fetch_array($sql);
	$tpl->js_dialog1("{{$method}}::{$ligne["pattern"]}", "$page?record-popup=$id&method=$method&function=$function");
	return;
}
function record_popup(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$id=intval($_GET["record-popup"]);
	$method=$_GET["method"];
	$function=$_GET["function"];
	$script=null;
	$title="{{$method}} {new_entry}";
	$bt="{add}";
	$t=time();
	if($id>0){
	$q=new postgres_sql();
		$sql="SELECT * FROM miltergreylist_acls WHERE ID='$id'";
		$ligne=$q->mysqli_fetch_array($sql);
		$title="{{$method}} {$ligne["pattern"]}";
		$bt="{apply}";
		$script="Onchange$t('{$ligne["type"]}');";
	}
	
	$methods["blacklist"]="{blacklist}";
	$methods["whitelist"]="{whitelist}";
	
	$action=array(
	"addr"=>"{addr}",
	"domain"=>"{milter_greylist_domain}",
	"from"=>"{milter_greylist_from}",
	"rcpt"=>"{milter_greylist_rcpt}");
	
	
	$EnableMilterRegex=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableMilterRegex");
	if($EnableMilterRegex==1){
		$action["envfrom"]="{envfrom}";
		$action["envsubject"]="{envsubject}";
		$action["envbody"]="{envbody}";

	}
	if(!isset($ligne["type"])){
        $ligne["type"]="addr";
    }

	$form[]=$tpl->field_hidden("SaveAclID", $id);
	if($id>0){$form[]=$tpl->field_array_hash($methods, "method","{method}",$method,true);}
	if($id==0){$form[]=$tpl->field_hidden("method",$method);}
	$form[]=$tpl->field_array_hash($action,"type", "{type_of_rule}", $ligne["type"],true,null,"Onchange$t");
	if($id>0){
		$form[]=$tpl->field_text("pattern", "{pattern}", $ligne["pattern"]);
	}else{
		$form[]=$tpl->field_textareacode("pattern", "{pattern}", "\n");
	}
	$form[]=$tpl->field_text("description", "{description}", $ligne["description"]);
	echo "<div id='explain-$t'></div>";
	
	if($function<>null){$function="$function();";}
	echo $tpl->form_outside($title, $form,null,$bt,"{$function}dialogInstance1.close();","AsPostfixAdministrator");
	
	echo "<script>
	function Onchange$t(val){
		LoadAjax('explain-$t','$page?explain-type='+val);
	}
	$script
	</script>";
	
}

function record_save(){
	$tpl=new template_admin();
	$tpl->CLEAN_POST();
	$q=new postgres_sql();
	
	$id=$_POST["SaveAclID"];
	$mode=$_POST["method"];
	$type=$_POST["type"];
	$pattern=$_POST["pattern"];
	$instance="master";
	$infos=$_POST["description"];
	
	$infos=trim($infos);
	$infos=str_replace("\n", "", $infos);
	$infos=str_replace("\r", "", $infos);
	$infos=str_replace("'", "`", $infos);


    $TR=array();
	$prefix="INSERT INTO miltergreylist_acls (id,zdate,instance,method,type,pattern,description) VALUES ";
	if($id==0){
		$ipclass=new IP();
		$sql="SELECT id FROM miltergreylist_acls ORDER BY id desc LIMIT 1";
		$ligne=$q->mysqli_fetch_array($sql);
		$lastid=$ligne["id"];

		$IMPORT=array();
		$tr=explode("\n",$pattern);
		foreach ($tr as $patterns){
	        $lastid++;
            $newinfos=$infos;
            $patterns=trim($patterns);
			if($patterns==null){continue;}

			if(preg_match("#^[0-9]+\s+(.+?)\s+(blacklist|whitelist)\s+(.+?)\s+(.+?)\s+(.+)#",$patterns,$re)) {
                $instance = $re[1];
                $zmethod = $re[2];
                if ($instance == "\N") {
                    $instance = "master";
                }
                $type = $re[3];
                $pattern = $re[4];
                $description = $re[5];
                $description = str_replace("'", "`", $description);
                $IMPORT[] = "NOW(),'$instance','$zmethod','$type','$pattern','$description')";
                continue;
            }else{
                $tpl->post_error("$patterns No Matches");
            }

			if($ipclass->isValid($patterns)){
				$hostname=gethostbyaddr($patterns);
				if($hostname<>$patterns){
					if(strpos("  $infos", $hostname)==0){$newinfos=$newinfos." $hostname";}
				}
			}
			
			//$patterns=pg_escape_bytea($patterns);
			$TR[]="($lastid,NOW(),'$instance','$mode','$type','$patterns','$newinfos')";
		}
		
		if(count($TR)>0) {
            $sql = $prefix . @implode(",", $TR);
            $q->QUERY_SQL($sql);
            if (!$q->ok) {
                echo $tpl->post_error($q->mysql_error . "<br>$sql\n");
                return false;
            }
        }


		if(count($IMPORT)>0){
            $TR=array();
		    foreach ($IMPORT as $line){
                $lastid++;
                $TR[]="($lastid,$line";
            }
            $sql=$prefix.@implode(",", $TR);
            writelogs($sql,__FUNCTION__,__FILE__,__LINE__);
            $q->QUERY_SQL($sql);
            if(!$q->ok){echo $tpl->post_error($q->mysql_error."<br>$sql\n");return false;}

        }
        $sock=new sockets();
        $sock->REST_API("/postfix/smtpd/restrictions/0");
        return admin_tracks_post("Save rules for SMTP instance id 0");

	}
	
	
	$sql="UPDATE miltergreylist_acls SET method='$mode',type='$type',pattern='$pattern',description='$infos' WHERE id=$id;";
	$q->QUERY_SQL($sql);
	if(!$q->ok){echo $q->mysql_error."<br>$sql\n";return false;}
    $sock=new sockets();
    $sock->REST_API("/postfix/smtpd/restrictions/0");
    return admin_tracks_post("Save rules for SMTP instance id 0");
}

function explain_type(){
	$tpl=new template_admin();
	if($_GET["explain-type"]==null){return;}
	$mil=new milter_greylist();
	$page=CurrentPageName();
	
	
	
	
	$action=$mil->actionlist;
	$action["gpid"]="{objects_group}";
	$subtitle=$action[$_GET["explain-type"]];
	
	
	echo $tpl->_ENGINE_parse_body("<div class='alert alert-info'>
			<strong style='font-size:18px'>$subtitle</strong><br>
			{{$_GET["explain-type"]}_text}</div>");
	
}



function page(){
	$page=CurrentPageName();
	$tpl=new template_admin();

    $instance_id=intval($_GET["instance-id"]);

    $html=$tpl->page_header("{blacklist}/{whitelist}",
        "fas fa-filter",
        "{APP_POSTFIX_BACKWHITE_EXPLAIN}",
        "$page?tabs=yes&instance-id=$instance_id",
        "rbl-service-$instance_id",
        "progress-postfixwbl-restart",false,
        "table-rbldnsd"
    );

	if(isset($_GET["main-page"])){
		$tpl=new template_admin("{blacklist}/{whitelist}",$html);
	echo $tpl->build_firewall();return;}
	
	
	echo $tpl->_ENGINE_parse_body($html);

}

function tabs(){
	$page=CurrentPageName();
	$tpl=new template_admin();
    $instance_id=intval($_GET["instance-id"]);
    $EnablePostfixMultiInstance=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnablePostfixMultiInstance"));

    if($instance_id==0) {
        $array["{status}"] = "$page?status=yes&instance-id=$instance_id";
        $array["{blacklist}"] = "$page?table=yes&method=blacklist&instance-id=$instance_id";
        $array["{whitelist}"] = "$page?table=yes&method=whitelist&instance-id=$instance_id";
        if($EnablePostfixMultiInstance==0){
            $array["{whitelist} ({filters})"]="$page?smtpd-milter-maps-start=yes&instance-id=$instance_id";
        }
    }
    if($EnablePostfixMultiInstance==1) {
        if($instance_id>0) {
            $array["{whitelist} ({filters})"] = "$page?smtpd-milter-maps-start=yes&instance-id=$instance_id";
        }
    }
    $instancename="SMTP Master";
    if($instance_id>0){
        $q=new lib_sqlite("/home/artica/SQLITE/postfix.db");
        $ligne=$q->mysqli_fetch_array("SELECT instancename from postfix_instances WHERE id='$instance_id'");
        $instancename="&nbsp;<small>({$ligne["instancename"]})</small>";
    }


    $TINY_ARRAY["TITLE"]="{blacklist}/{whitelist} $instancename";
    $TINY_ARRAY["ICO"]="fas fa-filter";
    $TINY_ARRAY["EXPL"]="{APP_POSTFIX_BACKWHITE_EXPLAIN}";
    $TINY_ARRAY["URL"]="rbl-service-$instance_id";
    $TINY_ARRAY["BUTTONS"]=null;
    $jstiny="Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";


	echo $tpl->tabs_default($array);
    echo "<script>$jstiny</script>";
}
function table(){
	$viarbl=null;
	$page=CurrentPageName();
	$tpl=new template_admin();
	$t=time();
	$html[]="

	</div>
	<div class='ibox-content'>
		<div id='postfix-tablediv-{$_GET["method"]}'></div>
	
	</div>
	</div>
";
	
	if(isset($_GET["viarbl"])){$viarbl="&viarbl=yes";}
	$html[]=$tpl->search_block($page,"postgres","miltergreylist_acls","postfix-tablediv-{$_GET["method"]}","&method={$_GET["method"]}$viarbl");


	
echo $tpl->_ENGINE_parse_body($html);

}

function IntelligentSearch($search){
    $search=trim($search);
    if($search==null){return null;}
    if(preg_match("#FIELD:#i",$search)){return null;}
    $search="*$search*";
    $search=str_replace("**","*",$search);
    $search=str_replace("*",".*?",$search);
    if(preg_match("#(.+?)\s+(.+)#",$search,$re)){
        $search=$re[1];
        $Nextpattern=trim($re[2]);
    }
    $results["Q"]=" WHERE (pattern ~ '$search') OR (description ~ '$search')";

    if(is_numeric($Nextpattern)){
        $results["MAX"]=$Nextpattern;
        return $results;
    }

    if(preg_match("#(MAX|LIMIT|)([\s=])([0-9]+)#",$Nextpattern)){
        $results["MAX"]=$re[1];
    }
    return $results;

}

function search(){
    $tpl=new template_admin();
    $page=CurrentPageName();
    $function=$_GET["function"];
    $DisablePostGres=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DisablePostGres"));
    if($DisablePostGres==1){
        $installjs=$tpl->framework_buildjs(
            "/postgresql/install","postgres.progress","postgres.log",
            "progress-dhcrequests-restart",
            "$function()"
        );

        $btn=$tpl->button_autnonome("{install} {APP_POSTGRES}",$installjs,ico_cd,"AsSystemAdministrator",240,"btn-warning");
        $install="<div style='text-align:right;margin-top:20px'>$btn</div>";

        $html[]=$tpl->div_warning("{APP_POSTGRES} {missing}||{need_postgresql_1}<hr>$install");
        echo $tpl->_ENGINE_parse_body($html);
        return false;
    }




	$sock=new sockets();
	$q=new postgres_sql();
	$table="miltergreylist_acls";
	$method=$_GET["method"];
	$EnableMilterRegex=intval($sock->GET_INFO("EnableMilterRegex"));

    $jsApply="Loadjs('fw.postfix.articarest.php?smtpd-client-restrictions=yes&instance-id=0')";

	
	if(isset($_GET["viarbl"])){
		$ARRAY["PROGRESS_FILE"]="/usr/share/artica-postfix/ressources/logs/rbldnsd.compile.progress";
		$ARRAY["LOG_FILE"]="/usr/share/artica-postfix/ressources/logs/rbldnsd.compile.progress.log";
		$ARRAY["CMD"]="rbldnsd.php?compile=yes";
		$ARRAY["TITLE"]="{APP_RBLDNSD}";
		$prgress=base64_encode(serialize($ARRAY));
		$jsApply="Loadjs('fw.progress.php?content=$prgress&mainid=progress-rbldnsd-restart')";
	}
	
	$btns=$tpl->_ENGINE_parse_body("
			<div class=\"btn-group\" data-toggle=\"buttons\">
			<label class=\"btn btn btn-primary\" OnClick=\"Loadjs('$page?record-js=0&method=$method&function={$_GET["function"]}');;\">
			<i class='fa fa-plus'></i> {new_entry} </label>
			<label class=\"btn btn btn-info\" OnClick=\"$jsApply\">
				<i class='fa fa-save'></i> {compile_database} </label>
			</div>");
	
	$search=$_GET["search"];
    $querys=IntelligentSearch($search);
    if(!isset($querys["Q"])) {
        $querys = $tpl->query_pattern($search);
        $MAX = $querys["MAX"];
    }
	if($MAX==0){$MAX=150;}

	if(!$q->TABLE_EXISTS("miltergreylist_acls")){

    }

	
	$sql="SELECT * FROM (SELECT * FROM $table WHERE method='{$_GET["method"]}') as t {$querys["Q"]} LIMIT $MAX";
	//(id,zdate,instance,method,type,pattern,description)

	
	$results = $q->QUERY_SQL($sql);
	if(!$q->ok){echo $tpl->FATAL_ERROR_SHOW_128("LINE ".__LINE__." $sql<br>$q->mysql_error");return;}

	
	$TRCLASS=null;
	$html[]="<table id='table-miltergrey-list-acls' class=\"footable table table-stripped\" data-page-size=\"100\" data-paging=\"true\">";
	$html[]="<thead>";
	$html[]="<tr>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text'>ID</th>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{date}</center></th>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{type}</center></th>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{records}</center></th>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{explain}</center></th>";
	$html[]="<th data-sortable=true class='text-capitalize center' data-type='text'>{delete}</center></th>";
	$html[]="</tr>";
	$html[]="</thead>";
	$html[]="<tbody>";
	
	while ($ligne = pg_fetch_assoc($results)) {
		if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}

		$md=md5(serialize($ligne));
		$id=$ligne["id"];
		$color="#000000";
		$zdate=$tpl->time_to_date(strtotime($ligne["zdate"]));
		$type=$ligne["type"];
		$pattern=$ligne["pattern"];
		$description=$ligne["description"];
		
		if($EnableMilterRegex==0){
			if($type=="envsubject"){
				$description="<small class='text-danger'><i class='fas fa-exclamation-square'></i>&nbsp;{APP_MILTER_REGEX} {inactive}</small> $description";
			}
			if($type=="envbody"){
				$description="<small class='text-danger'><i class='fas fa-exclamation-square'></i>&nbsp;{APP_MILTER_REGEX} {inactive}</small> $description";
			}
			
		}
		
		
		$js="Loadjs('$page?record-js=$id&method=$method&function={$_GET["function"]}');";
		$html[]="<tr class='$TRCLASS' id='$md'>";
		$html[]="<td style='width:1%'>".$tpl->td_href($id,"{view2}",$js)."</td>";
		$html[]="<td style='width:1%' nowrap>$zdate</td>";
		$html[]="<td style='width:1%' nowrap>{{$type}}</td>";
		$html[]="<td style='width:1%' nowrap>".$tpl->td_href($pattern,"{view2}",$js)."</td>";
		$html[]="<td>$description</td>";
		$html[]="<td style='vertical-align:middle'><center>".$tpl->icon_delete("Loadjs('$page?delete-js=$id&id=$md')","AsPostfixAdministrator")."</center></td>";
		$html[]="</tr>";
	}


    $title="{whitelist} {$querys["Q"]}";
    if($method=="blacklist"){
        $title="{blacklist} {$querys["Q"]}";
    }

    $TINY_ARRAY["TITLE"]="$title";
    $TINY_ARRAY["ICO"]="fas fa-filter";
    $TINY_ARRAY["EXPL"]="{APP_POSTFIX_BACKWHITE_EXPLAIN}";
    $TINY_ARRAY["URL"]="rbl-service";
    $TINY_ARRAY["BUTTONS"]=$btns;
    $jstiny="Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";

	$html[]="</tbody>";
	$html[]="<tfoot>";

	$html[]="<tr>";
	$html[]="<td colspan='6'>";
	$html[]="<ul class='pagination pull-right'></ul>";
	$html[]="</td>";
	$html[]="</tr>";
	$html[]="</tfoot>";
	$html[]="</table>";
	$html[]="<small>$sql</small>
<script>
	NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS)."
	$(document).ready(function() { $('#table-miltergrey-list-acls').footable( { \"filtering\": { \"enabled\": false }, \"sorting\": { \"enabled\": true },\"paging\": { \"size\": {$GLOBALS["FOOTABLE_PSIZE"]} } } ); });
	$jstiny
</script>";

	echo $tpl->_ENGINE_parse_body($html);
}

function smtpd_milter_maps_table(){
	$t=time();
	$tpl=new template_admin();
	$page=CurrentPageName();
	$instance_id=intval($_GET["instance-id"]);
	

	$ARRAY["PROGRESS_FILE"]="/usr/share/artica-postfix/ressources/logs/smtpd_client_restrictions_progress";
	$ARRAY["LOG_FILE"]="/usr/share/artica-postfix/ressources/logs/smtpd_client_restrictions_progress.log";
	$ARRAY["CMD"]="postfix.php?smtpd-client-restrictions=yes&instance-id=$instance_id";
	$ARRAY["TITLE"]="{smtpd_client_restrictions}";
	$prgress=base64_encode(serialize($ARRAY));
	$jsApply="Loadjs('fw.progress.php?content=$prgress&mainid=progress-postfixwbl-restart')";
	
	
	$btns[]="<div class=\"btn-group\" data-toggle=\"buttons\" style=''>";
    $btns[]="<label class=\"btn btn btn-primary\" OnClick=\"Loadjs('$page?smtpd-milter-maps-js=yes&instance-id=$instance_id');\">";
    $btns[]="<i class='fa fa-plus'></i> {new_item} </label>";

    $btns[]="<label class=\"btn btn btn-info\" OnClick=\"javascript:$jsApply\">
				<i class='fa fa-save'></i> {compile_database} </label>
			</div>";

    $btns[]="</div>";

	
	$html[]="<table id='smtpd-milter-maps-$t' class=\"footable table table-stripped\" data-page-size=\"100\" data-paging=\"true\">";
	$html[]="<thead>";
	$html[]="<tr>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{items}</th>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text' nowrap class='center'>{enabled}</th>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text' class='center'>{delete}</th>";
	$html[]="</tr>";
	$html[]="</thead>";
	$html[]="<tbody>";
	
	$q=new lib_sqlite("/home/artica/SQLITE/postfix.db");

    if(!$q->FIELD_EXISTS("smtpd_milter_maps","instanceid")){
        $q->QUERY_SQL("ALTER TABLE smtpd_milter_maps ADD instanceid INTEGER NOT NULL DEFAULT 0");
    }

	$results=$q->QUERY_SQL("SELECT * FROM smtpd_milter_maps WHERE instanceid=$instance_id ORDER BY pattern");
	if(!$q->ok){echo $tpl->FATAL_ERROR_SHOW_128($q->mysql_error);}
    $TRCLASS=null;
	foreach ($results as $index=>$ligne){
		if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
		$id=md5(serialize($ligne));
		$html[]="<tr class='$TRCLASS' id='$id'>";
		$pattern=$ligne["pattern"];
		$ID=$ligne["ID"];
		$html[]="<td><strong>$pattern</strong></td>";
		$html[]="<td width=1% class='center'>". $tpl->icon_check($ligne["enabled"],
                    "Loadjs('$page?smtpd-milter-maps-enable=$ID')","AsPostfixAdministrator")."</td>";
		$html[]="<td width=1% class='center'>". $tpl->icon_delete("Loadjs('$page?smtpd-milter-maps-delete=$ID&md=$id')","AsPostfixAdministrator")."</td>";
		$html[]="</tr>";
	}
	
	if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
	$html[]="<td><strong>127.0.0.0/8</strong></td>";
	$html[]="<td width=1% class='center'>". $tpl->icon_nothing()."</td>";
	$html[]="<td width=1% class='center'>". $tpl->icon_nothing()."</td>";
	$html[]="</tr>";
	
	
	$html[]="</tbody>";
	$html[]="<tfoot>";
	
	$html[]="<tr>";
	$html[]="<td colspan='3'>";
	$html[]="<ul class='pagination pull-right'></ul>";
	$html[]="</td>";
	$html[]="</tr>";
	$html[]="</tfoot>";
	$html[]="</table>";

    $instancename="SMTP Master";
    if($instance_id>0){
        $q=new lib_sqlite("/home/artica/SQLITE/postfix.db");
        $ligne=$q->mysqli_fetch_array("SELECT instancename from postfix_instances WHERE id='$instance_id'");
        $instancename="&nbsp;<small>({$ligne["instancename"]})</small>";
    }

    $TINY_ARRAY["TITLE"]="{whitelist} ({filters}) $instancename";
    $TINY_ARRAY["ICO"]="fas fa-filter";
    $TINY_ARRAY["EXPL"]="{APP_POSTFIX_BACKWHITE_EXPLAIN}";
    $TINY_ARRAY["URL"]="rbl-service-$instance_id";
    $TINY_ARRAY["BUTTONS"]=@implode("",$btns);
    $jstiny="Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";
    echo "<script>$jstiny</script>";


	$html[]="
	<script>
	NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS)."
	$(document).ready(function() { $('#smtpd-milter-maps-$t').footable( { \"filtering\": { \"enabled\": true }, \"sorting\": { \"enabled\": true },\"paging\": { \"size\": {$GLOBALS["FOOTABLE_PSIZE"]} } } ); });
	</script>";
	
	echo $tpl->_ENGINE_parse_body(@implode("\n", $html));
	
}


function status(){
	$sock=new sockets();
	$tpl=new template_admin();
    $instance_id=intval($_GET["instance_id"]);
	$SUM_COUNT_SMTP_BLK=$sock->GET_INFO("SUM_COUNT_SMTP_BLK");
	$SUM_COUNT_SMTP_WHL=$sock->GET_INFO("SUM_COUNT_SMTP_WHL");
	$SUM_COUNT_SMTP_BLK_BLOCK=$sock->GET_INFO("SUM_COUNT_SMTP_BLK_BLOCK");
	
	
	if($SUM_COUNT_SMTP_BLK==0){
		$html[]=$tpl->widget_style1("gray-bg","fal fa-list-ol","{NUMBER_OF_BLACKLIST_RULES}","{none}");
	}else{
		$SUM_COUNT_SMTP_BLK=FormatNumber($SUM_COUNT_SMTP_BLK);
		$html[]=$tpl->widget_style1("navy-bg","fal fa-list-ol","{NUMBER_OF_BLACKLIST_RULES}",$SUM_COUNT_SMTP_BLK);
	}
	
	if($SUM_COUNT_SMTP_WHL==0){
		$html[]=$tpl->widget_style1("gray-bg","fal fa-list-ol","{NUMBER_OF_WHITELIST_RULES}","{none}");
	}else{
		$SUM_COUNT_SMTP_WHL=FormatNumber($SUM_COUNT_SMTP_WHL);
		$html[]=$tpl->widget_style1("navy-bg","fal fa-list-ol","{NUMBER_OF_WHITELIST_RULES}",$SUM_COUNT_SMTP_BLK);
	}	
	
	if($SUM_COUNT_SMTP_BLK_BLOCK==0){
		$html[]=$tpl->widget_style1("gray-bg","fas fa-ban","{NUMBER_OF_BLOCKED_MESSAGES}","{none}");
	}else{
		$SUM_COUNT_SMTP_BLK_BLOCK=FormatNumber($SUM_COUNT_SMTP_BLK_BLOCK);
		$html[]=$tpl->widget_style1("red-bg","fas fa-ban","{NUMBER_OF_BLOCKED_MESSAGES}",$SUM_COUNT_SMTP_BLK_BLOCK);
	}

	echo $tpl->_ENGINE_parse_body($html);

    $instancename="SMTP Master";
    if($instance_id>0){
        $q=new lib_sqlite("/home/artica/SQLITE/postfix.db");
        $ligne=$q->mysqli_fetch_array("SELECT instancename from postfix_instances WHERE id='$instance_id'");
        $instancename="&nbsp;<small>({$ligne["instancename"]})</small>";
    }


    $TINY_ARRAY["TITLE"]="{blacklist}/{whitelist} {status} $instancename";
    $TINY_ARRAY["ICO"]="fas fa-filter";
    $TINY_ARRAY["EXPL"]="{APP_POSTFIX_BACKWHITE_EXPLAIN}";
    $TINY_ARRAY["URL"]="rbl-service-$instance_id";
    $TINY_ARRAY["BUTTONS"]=null;
    $jstiny="Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";
	echo "<script>$jstiny</script>";
	
}
function FormatNumber($number, $decimals = 0, $thousand_separator = '&nbsp;', $decimal_point = '.'){$tmp1 = round((float) $number, $decimals); while (($tmp2 = preg_replace('/(\d+)(\d\d\d)/', '\1 \2', $tmp1)) != $tmp1)$tmp1 = $tmp2; return strtr($tmp1, array(' ' => $thousand_separator, '.' => $decimal_point));}