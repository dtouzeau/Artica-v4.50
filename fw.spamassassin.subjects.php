<?php
$GLOBALS['TABLENAME']="spamasssin_subjects";
$GLOBALS["EXPLAIN"]="spamassassin_subjects_explain";
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}

if(isset($_GET["rule-js"])){rule_js();exit;}
if(isset($_GET["table"])){table();exit;}
if(isset($_GET["popup"])){popup();exit;}
if(isset($_GET["rule-delete"])){delete();exit;}
if(isset($_POST["ID"])){save();exit;}
page();



function rule_js(){
	$func=$_GET["func"];
	$ID=$_GET["rule-js"];
	$page=CurrentPageName();
	$tpl=new template_admin();
	$title="{rule} $ID";
	if($ID==0){$title="{new_rule}";}
	$tpl->js_dialog1($title, "$page?popup=$ID&func=$func");
	
}

function delete(){
	$ID=$_GET["rule-delete"];
	$tpl=new template_admin();
	$q=new lib_sqlite("/home/artica/SQLITE/spamassassin.db");
	$ligne=$q->mysqli_fetch_array("DELETE FROM {$GLOBALS['TABLENAME']} WHERE ID=$ID");
	if(!$q->ok){$tpl->js_mysql_alert($q->mysql_error);return;}
	echo "$('#{$_GET["md"]}').remove();";
}

function popup(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$ID=intval($_GET["popup"]);
	$btn="{apply}";
	$func=$_GET["func"];
	$q=new lib_sqlite("/home/artica/SQLITE/spamassassin.db");
	$ligne=$q->mysqli_fetch_array("SELECT * FROM {$GLOBALS['TABLENAME']} WHERE ID=$ID");
	
	$tpl->field_hidden("ID", $ID);
	if($ID==0){
		$title="{new_rule}";
		$btn="{add}";
		$form[]=$tpl->field_textareacode("pattern", "{pattern}", null);
	}else{
		$title="{rule} $ID";
		
		$form[]=$tpl->field_text("pattern", "{pattern}", $ligne["pattern"]);
	}
	
	echo $tpl->form_outside($title, $form,"{{$GLOBALS["EXPLAIN"]}}",$btn,"{$func}();dialogInstance1.close();","AsPostfixAdministrator");
	
	
}

function save(){
	$ID=$_POST["ID"];
	$tpl=new template_admin();
	$tpl->CLEAN_POST();
	$q=new lib_sqlite("/home/artica/SQLITE/spamassassin.db");
	if($ID==0){
		$items2=explode("\n",$_POST["pattern"]);
		foreach ($items2 as $ligne){
			$ligne=trim($ligne);
			if($ligne==null){continue;}
			$ligne=$q->sqlite_escape_string2($ligne);
			$zdate=date("Y-m-d H:i:s");
			$q->QUERY_SQL("INSERT OR IGNORE INTO {$GLOBALS['TABLENAME']} (pattern,zdate) VALUES ('$ligne','$zdate')");
			if(!$q->ok){echo $q->mysql_error;return;}
		}
		return;
	}
	$_POST["pattern"]=$q->sqlite_escape_string2($_POST["pattern"]);
	$q->QUERY_SQL("UPDATE {$GLOBALS['TABLENAME']} SET pattern='{$_POST["pattern"]}' WHERE ID=$ID");
	
}

function page(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	if(!isset($_GET["t"])){$t=time();}else{$t=$_GET["t"];}
	
	
	$ARRAY["PROGRESS_FILE"]="/usr/share/artica-postfix/ressources/logs/spamassassin.urls.progress";
	$ARRAY["LOG_FILE"]=PROGRESS_DIR."/spamassassin.urls.progress.log";
	$ARRAY["CMD"]="milter-spamass.php?urls-database=yess";
	$ARRAY["TITLE"]="{building_rules}";
	$prgress=base64_encode(serialize($ARRAY));
	$jsrestart="Loadjs('fw.progress.php?content=$prgress&mainid=progress-firehol-restart');";
	
	$add="Loadjs('$page?rule-js=0&func=ss$t')";
	$html[]="	<div class=\"row\"> 
		<div class='ibox-content'>";
	$html[]="<div class=\"btn-group\" data-toggle=\"buttons\" style='margin-top:10px'>";
	$html[]="<label class=\"btn btn btn-primary\" OnClick=\"$add\"><i class='fa fa-plus'></i> {new_rule} </label>";
	$html[]="<label class=\"btn btn btn-info\" OnClick=\"$jsrestart\"><i class='fa fa-save'></i> {apply_parameters} </label>";
	$html[]="</div>";
	$html[]="<div class=\"btn-group\" data-toggle=\"buttons\"></div>";
	
	$html[]="

		<div class=\"input-group\" style='margin-top:10px'>
      		<input type=\"text\" class=\"form-control\" value=\"*\" placeholder=\"{search}\" id='search-this-$t' OnKeyPress=\"javascript:Search$t(event);\">
      		<span class=\"input-group-btn\">
       		 <button style=\"text-transform: capitalize;\" class=\"btn btn-default\" type=\"button\" OnClick=\"javascript:ss$t();\">Go!</button>
      	</span>
     </div>
    </div>
</div>	
	
	
	
		
	<div class='row'><div id='progress-firehol-restart'></div>
	<div class='ibox-content'>";
	
	

	$html[]="<div id='table-loader-$t'></div>

	</div>
	</div>
		
		
		
<script>
		function Search$t(e){
			if(!checkEnter(e) ){return;}
			ss$t();
		}
		
		function ss$t(){
			var ss=encodeURIComponent(document.getElementById('search-this-$t').value);
			LoadAjax('table-loader-$t','$page?table=yes&func=ss$t&search='+ss);
		}
		
		function Start$t(){
			var ss=document.getElementById('search-this-$t').value;
			ss$t();
		}
		Start$t();
	</script>";

	
	echo $tpl->_ENGINE_parse_body($html);

}

function table(){
	
	
	$page=CurrentPageName();
	$tpl=new template_admin();
	$sock=new sockets();
	$TRCLASS=null;
	$t=time();
	
	$q=new lib_sqlite("/home/artica/SQLITE/spamassassin.db");
	$search="*{$_GET["search"]}*";
	$search=str_replace("**", "*", $search);
	$search=str_replace("**", "*", $search);
	$search=str_replace("*", "%", $search);
	
	$sql="SELECT * FROM `{$GLOBALS['TABLENAME']}` WHERE pattern LIKE '$search' ORDER BY pattern LIMIT 150";
	$results=$q->QUERY_SQL($sql);
	if(!$q->ok){echo $tpl->FATAL_ERROR_SHOW_128($q->mysql_error);return;}
	
	
	
	$html[]="<table id='table-$t' class=\"table table-stripped\" data-page-size=\"100\" data-paging=\"true\">";
	$html[]="<thead>";
	$html[]="<tr>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text' width=1% nowrap>{ID}</th>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text' width=1%>{date}</th>";
	$html[]="<th data-sortable=true class='text-capitalize' width=1%>{pattern}</th>";
	$html[]="<th data-sortable=false width=1%>{delete}</th>";
	$html[]="</tr>";
	$html[]="</thead>";
	$html[]="<tbody>";
	
	
	
	
	foreach ($results as $index=>$ligne){
		if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
		$ID=$ligne["ID"];
		$md=md5(serialize($ligne));
		$date=$ligne["zdate"];
		$pattern=$ligne["pattern"];
		$html[]="<tr class='$TRCLASS' id='$md'>";
		$html[]="<td width=1%><center>$ID</center></td>";
		$html[]="<td width=1% nowrap>$date</td>";
		$html[]="<td width=99%><strong>". $tpl->td_href($pattern,null,"Loadjs('$page?rule-js={$ligne['ID']}')")."</strong></td>";
		$html[]="<td width=1% class='center' nowrap>". $tpl->icon_delete("Loadjs('$page?rule-delete={$ligne['ID']}&md=$md')","AsPostfixAdministrator")."</center></td>";
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
	
	</script>";
	
	
	echo $tpl->_ENGINE_parse_body(@implode("\n", $html));
}


