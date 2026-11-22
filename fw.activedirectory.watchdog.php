<?php
// Post SP10 - 13/10/2026
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.computers.inc");

// Repartiteur(s) mort(s) retire(s): rule_settings()/rule_save() ne sont
// definies NI dans ce fichier NI ailleurs, et aucun code du depot ne produit
// le parametre qui les declenchait -- ni cette page, ni une autre. Les URL
// repondaient donc un fatal « Call to undefined function ». Vestige d'un
// squelette de page recopie; les pages qui utilisent VRAIMENT ?rule-popup=
// ou ?rule-settings= definissent leur propre handler.
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}


if(isset($_GET["search"])){table();exit;}
if(isset($_GET["ShowID-js"])){ShowID_js();exit;}
if(isset($_GET["ShowID"])){ShowID();exit;}
if(isset($_GET["empty-js"])){empty_js();exit;}
if(isset($_POST["empty"])){empty_table();exit;}

page();

function ShowID_js():bool{
	
	$id=$_GET["ShowID-js"];
	if(!is_numeric($id)){
			return false;
	
	}$tpl=new template_admin();
	$page=CurrentPageName();
	$sql="SELECT subject FROM ntlm_admin_mysql WHERE ID=$id";
	$q=new lib_sqlite("/home/artica/SQLITE/system_events.db");
	$ligne=$q->mysqli_fetch_array($sql);
	$subject=$tpl->javascript_parse_text($ligne["subject"]);
	return $tpl->js_dialog($subject, "$page?ShowID=$id");
	
}

function empty_js(){
	$tpl=new template_admin();
	$title="{system_events}";
	$tpl->js_confirm_empty($title,"empty","yes","{$_GET["function"]}();");
	
}

function empty_table(){
	$q=new lib_sqlite("/home/artica/SQLITE/system_events.db");
	$q->QUERY_SQL("DROP TABLE ntlm_admin_mysql");
	
}

function ShowID(){
	
	$tpl=new template_admin();
	// Pentest 2026-11-04 : ShowID etait concatene dans la requete.
	$ShowID=intval($_GET["ShowID"]);
	$sql="SELECT content FROM ntlm_admin_mysql WHERE ID=$ShowID";
	$q=new lib_sqlite("/home/artica/SQLITE/system_events.db");
	$ligne=$q->mysqli_fetch_array($sql);
	
	// Pentest 2026-11-04 : content (donnee externe) affiche sans encodage (XSS stockee).
	$content=$tpl->_ENGINE_parse_body(req_html($ligne["content"]));
	$content=nl2br($content);
	echo "<p>$content</p>";
}

function page():bool{
	$tpl=new template_admin();
    $html=$tpl->page_header("{system_events} Active Directory",ico_eye,"Active Directory {events}","","active-directory-events","active-directory-progress",true);
		
    if(isset($_GET["main-page"])){
	    $tpl=new template_admin(null,$html);
	    echo $tpl->build_firewall();
	    return true;
    }
	echo $tpl->_ENGINE_parse_body($html);
    return true;
}

function table(){
	$tpl=new template_admin();
	$page=CurrentPageName();
    $search="";
    $FILTER="WHERE 1";
    $FILTER_PARAMS=array();
    if(isset($_GET["search"])){$search=$_GET["search"];}
    if(strlen($search)>1){
        $search="*$search*";
        $search=str_replace("**","*",$search);
        $search=str_replace("**","*",$search);
        $search=str_replace("*","%",$search);
        // Pentest 2026-11-04 : search etait concatene dans la requete.
        $FILTER="WHERE subject LIKE ? OR content LIKE ? OR function LIKE ?";
        $FILTER_PARAMS=array("%$search%","%$search%","%$search%");
    }




    $q=new lib_sqlite("/home/artica/SQLITE/system_events.db");

	$sql="SELECT ID,zDate,subject,severity,function,line,filename, LENGTH(content) as content  FROM ntlm_admin_mysql $FILTER ORDER BY zDate DESC LIMIT 500";
	$results=$q->QUERY_SQL_PARAMS($sql,$FILTER_PARAMS);


    $html[]="<table id='table-firewall-rules' class=\"table table-stripped\" data-page-size=\"100\" data-paging=\"true\">";
    $html[]="<thead>";
    $html[]="<tr>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{date}</th>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{events}</th>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{daemon}</th>";
	$html[]="</tr>";
	$html[]="</thead>";
	$html[]="<tbody>";

	// Pentest 2026-11-04 : eth etait recopie sans encodage dans la page (XSS).
	$ethT=preg_replace('#[^A-Za-z0-9_.:@-]#','',(string)($_GET["eth"] ?? ""));
	$jsAfter="LoadAjax('table-loader','$page?table=yes&eth=$ethT');";
	$GLOBALS["jsAfterEnc"]=base64_encode($jsAfter);
	

	
	if(!$q->ok){
		echo $tpl->div_error($q->mysql_error);
        return false;

	}
	
	$severityCL[0]="label-danger";
	$severityCL[1]="label-warning";
	$severityCL[2]="label-primary";
	
	$severityTX[0]="text-danger";
	$severityTX[1]="text-warning";
	$severityTX[2]="text-primary";
	$curs="OnMouseOver=\"this.style.cursor='pointer';\" OnMouseOut=\"this.style.cursor='auto'\"";
	
	$TRCLASS=null;
	foreach ($results as $index=>$ligne){
		if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
		$text_class=null;
		$id=md5(serialize($ligne));
		$zdate=$tpl->time_to_date($ligne["zDate"],true);
		$severity_class=$severityCL[$ligne["severity"]];
        $td1Prc="class=\"$text_class\" style='width:1%' nowrap";
		// Pentest 2026-11-04 : subject/hostname/function/filename (donnee externe) affiches sans encodage (XSS stockee).
		$ligne["ID"]=intval($ligne["ID"]);
		$js="Loadjs('$page?ShowID-js={$ligne["ID"]}')";
		$link="<span><i class='fa fa-search ' id='$id'></i>&nbsp;<a href=\"javascript:blur();\"
		OnClick=\"$js\" class='{$severityTX[$ligne["severity"]]}' style='font-weight:bold'>";

		if($ligne["content"]==0){$link="<span style='font-weight:bold'>";$js="blur()";}

		$text=$link.$tpl->_ENGINE_parse_body(req_html($ligne["subject"])."</a></span>
		<div style='font-size:10px'>{host}:".req_html($ligne["hostname"])." {function}:".req_html($ligne["function"]).", {line}:".req_html($ligne["line"])."</div>");
		if(strpos(" ".$ligne["filename"], "/")>0){$ligne["filename"]=basename($ligne["filename"]);}
		$ligne["filename"]=req_html($ligne["filename"]);
		
		$html[]="<tr class='$TRCLASS'>";
		$html[]="<td $td1Prc><div class='label $severity_class' style='font-size:13px;padding:10px;width:100%' $curs OnClick=\"$js\">$zdate</a></div></td>";
		$html[]="<td class=\"$text_class\">$text</td>";
		$html[]="<td $td1Prc>{$ligne["filename"]}</a></td>";
		$html[]="</tr>";
		

	}


    $topbuttons[] = array("Loadjs('$page?empty-js=yes&function={$_GET["function"]}');", ico_trash, "{empty}");

    $TINY_ARRAY["TITLE"]="{system_events} Active Directory";
    $TINY_ARRAY["ICO"]=ico_eye;
    $TINY_ARRAY["EXPL"]="Active Directory {events}";
    $TINY_ARRAY["BUTTONS"]=$tpl->table_buttons($topbuttons);
    $jstiny="Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";

	$html[]="</tbody>";
	$html[]="<tfoot>";

	$html[]="<tr>";
	$html[]="<td colspan='3'>";
	$html[]="<ul class='pagination pull-right'></ul>";
	$html[]="</td>";
	$html[]="</tr>";
	$html[]="</tfoot>";
	$html[]="</table>";
	$html[]="
	<script>
NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS)."$jstiny
</script>";

			echo $tpl->_ENGINE_parse_body($html);
return true;
}
