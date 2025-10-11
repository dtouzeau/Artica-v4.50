<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.mysql.powerdns.inc");
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}

if(isset($_GET["popup"])){popup();exit;}
if(isset($_GET["tabs"])){tabs();exit;}
if(isset($_GET["items-table-start"])){table_start();exit;}
if(isset($_GET["table"])){table();exit;}
if(isset($_GET["dns-info"])){dns_info();exit;}
if(isset($_GET["dns-info2"])){dns_info2();exit;}
if(isset($_GET["delete-js"])){delete_js();exit;}
if(isset($_POST["domainid"])){dns_info_save();exit;}
if(isset($_GET["expert"])){expert_popup();exit;}
if(isset($_POST["expert"])){expert_save();exit;}
js();

function js(){
	$page=CurrentPageName();
	$domain_id=intval($_GET["domain-id"]);
	$tpl=new template_admin();
	$q=new mysql_pdns();
	$domainame=$q->GetDomainName($domain_id);
	$tpl->js_dialog6("$domainame", "$page?tabs=yes&domain-id=$domain_id",1200);
}
function tabs(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$UnboundEnabled=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UnboundEnabled"));
	$domain_id=intval($_GET["domain-id"]);
	$array["{information}"]="$page?dns-info=$domain_id";
	$array["{records}"]="$page?items-table-start=$domain_id";
	if($UnboundEnabled==0){


        $array["{expert_mode}"]="$page?expert=$domain_id";
        $array["{META_DATA}"]="fw.pdns.metadata.php?popup=yes&domain_id=$domain_id";

	}
	echo $tpl->tabs_default($array);
}
function table_start(){
	$domain_id=intval($_GET["items-table-start"]);
	$page=CurrentPageName();
	$t=time();
	$tpl=new template_admin();
	$PowerDNSEnableClusterSlave=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("PowerDNSEnableClusterSlave"));
	
	
	$html[]="<div class=\"row\" style='margin-top:10px'>";
	$html[]="<div class=\"btn-group\" data-toggle=\"buttons\" style='padding-left:10px'>";
	if($PowerDNSEnableClusterSlave==0){$html[]="<label class=\"btn btn btn-primary\" OnClick=\"Loadjs('fw.dns.records.php?record-js=0&domain-id=$domain_id&function=ss$t');;\">
			<i class='fa fa-plus'></i> {new_record} </label>";}
	$html[]="</div>";
	
	$html[]= "
		
		<div class='ibox-content' style='border:0px'>
			<div class=\"input-group\">
	      		<input type=\"text\" class=\"form-control\" value=\"\" placeholder=\"{search}\" id='search-this-$t' 
	      			OnKeyPress=\"javascript:Search$t(event);\">
	      		<span class=\"input-group-btn\">
	       		 <button style=\"text-transform: capitalize;\" class=\"btn btn-default\" type=\"button\" OnClick=\"javascript:ss$t();\">Go!</button>
	      	</span>
     	</div>
    	</div>
	</div>		
	
	
	
	<div id='items-table-start'></div>
	<script>
		function Search$t(e){
			if(!checkEnter(e) ){return;}
			ss$t();
		}
		
		function ss$t(){
			var ss=encodeURIComponent(document.getElementById('search-this-$t').value);
			LoadAjax('items-table-start','$page?table=yes&search='+ss+'&function=ss$t&domain-id=$domain_id');
		}
		
		function Start$t(){
			var ss=document.getElementById('search-this-$t').value;
			ss$t();
		}
		Start$t();		
	
	
	</script>";
	
	echo $tpl->_ENGINE_parse_body($html);
}
function expert_popup():bool{
    $tpl=new template_admin();
    $q=new mysql_pdns();
    $domain_id=$_GET["expert"];
    $zone=$q->GetDomainName($domain_id);
    $GLOBALS["CLASS_SOCKETS"]->getFrameWork("pdns.php?pdns-util-load-zone=$zone");
    $DESTF=PROGRESS_DIR."/$zone.dump";
    $datas=explode("\n",@file_get_contents($DESTF));
    $newdata=array();
    foreach ($datas as $line){
        $line=trim($line);
        if($line==null){continue;}
        if(preg_match("#^[a-zA-Z]+\s+[0-9]+\s+[0-9]+:[0-9]+#",$line)){continue;}
        $newdata[]=$line;
    }

    $jsrestart="blur();";
    $tpl->field_hidden("expert",$zone);
    $form[]=$tpl->field_textareacode("export", null, @implode("\n",$newdata),"664px");
    echo $tpl->form_outside("$zone: {records}", @implode("\n", $form),null,"{apply}",$jsrestart,null);
    return true;
}
function expert_save():bool{
    $tpl=new template_admin();
    $tpl->CLEAN_POST();
    $zone=$_POST["expert"];
    $DESTF=PROGRESS_DIR."/$zone.save";
    @file_put_contents($DESTF,$_POST["export"]);
    $GLOBALS["CLASS_SOCKETS"]->getFrameWork("pdns.php?pdns-util-save-zone=$zone");

    $DESTF=PROGRESS_DIR."/$zone.log";
    $datas=explode("\n",@file_get_contents($DESTF));
    foreach ($datas as $line){
        $line=trim($line);
        if($line==null){continue;}
        if(preg_match("#^[a-zA-Z]+\s+[0-9]+\s+[0-9]+:[0-9]+#",$line)){continue;}
        if(preg_match("#^Error:\s+(.+)#",$line,$re)){
            $tpl->post_error($re[1]);return false;
        }
    }

    return true;
}


function table(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$q=new mysql_pdns();
	$PowerDNSEnableClusterSlave=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("PowerDNSEnableClusterSlave"));
    $html[]="<table id='table-dns-forward-zones' class=\"footable table table-stripped\" data-page-size=\"100\" data-paging=\"true\">";
	$html[]="<thead>";
	$html[]="<tr>";
	$TRCLASS=null;
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text'>ID</th>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{record}</center></th>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{type}</center></th>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{content}</center></th>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text'></center></th>";
	$html[]="<th data-sortable=true class='text-capitalize center' data-type='text'>{delete}</center></th>";
	$html[]="</tr>";
	$html[]="</thead>";
	$html[]="<tbody>";
	
	$domain_id=intval($_GET["domain-id"]);
	
	//$sql=" SELECT * FROM records  WHERE domain_id=$domain_id ORDER BY name LIMIT 0,100";
	
	$search=$_GET["search"];
	$sql=$q->search_sql($search,$domain_id);
	
	$results = $q->QUERY_SQL($sql);
	if(!$q->ok){echo $tpl->FATAL_ERROR_SHOW_128("LINE ".__LINE__." $q->mysql_error<hr>$sql");return;}
	
	while ($ligne = mysqli_fetch_assoc($results)) {
		if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
		
		$md=md5(serialize($ligne));
		$id=$ligne["id"];
		$color="#000000";
		$type=$ligne["type"];
		$name=$ligne["name"];
		$content=$ligne["content"];
		$disabled=intval($ligne["disabled"]);
		$enable_button=$tpl->icon_nothing();
		$delete_button=$tpl->icon_nothing();
		if(strlen($content)>45){$content=substr($content, 0,42)."...";}
		if(strlen($name)>27){$name=substr($name, 0,24)."...";}
		$jshost="Loadjs('fw.dns.records.php?record-info-js=yes&domainid=$domain_id&type=$type&id=$id&function={$_GET["function"]}');";
		
		if($disabled==0){$enable=true;}else{$enable=false;}
		
		if($PowerDNSEnableClusterSlave==0){
			$enable_button=$tpl->icon_check($enable,"Loadjs('fw.dns.records.php?enable-js=$id&id=$md')","AsDnsAdministrator");
			$delete_button=$tpl->icon_delete("Loadjs('fw.dns.records.php?delete-js=$id&id=$md')","AsDnsAdministrator");
		}
		
		$html[]="<tr class='$TRCLASS' id='$md'>";
		$html[]="<td>". $tpl->td_href($id,null,$jshost)."</td>";
		$html[]="<td>".$tpl->td_href($name,null,$jshost)."</a></td>";
		$html[]="<td>".$tpl->td_href($type,null,$jshost)."</a></td>";
		$html[]="<td style='font-weight:bold'>$content</td>";
		$html[]="<td style='vertical-align:middle'><center>$enable_button</center></td>";
		$html[]="<td style='vertical-align:middle'><center>$delete_button</center></td>";
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
	$(document).ready(function() { $('.footable').footable( { \"filtering\": { \"enabled\": true }, \"sorting\": { \"enabled\": true },\"paging\": { \"size\": {$GLOBALS["FOOTABLE_PSIZE"]} } } ); });
</script>";
	
	echo $tpl->_ENGINE_parse_body($html);
	
}

function dns_info(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$domain_id=intval($_GET["dns-info"]);
	echo "
	<div id='zone-info-domain-progress'></div>		
	<div id='DNSINFO-2'></div><script>LoadAjax('DNSINFO-2','$page?dns-info2=$domain_id');</script>";
	
}

function dns_info2(){
	
	$page=CurrentPageName();
	$tpl=new template_admin();
	$domain_id=intval($_GET["dns-info2"]);
	$q=new mysql_pdns();
	$dname=$q->GetDomainName($domain_id);
	$explain_form=null;
	
	$q=new lib_sqlite("/home/artica/SQLITE/dns.db");
	$sql="CREATE TABLE IF NOT EXISTS `dnsinfos` (domain_id INTEGER PRIMARY KEY,name text,cialdom 
			INTEGER,renewdate INTEGER,zinfo text,explain text)";
	$q->QUERY_SQL($sql);
	if(!$q->FIELD_EXISTS("dnsinfos", "cialdom")){
		$q->QUERY_SQL("ALTER TABLE dnsinfos ADD cialdom integer");
	}
	
	$ligne=$q->mysqli_fetch_array("SELECT * FROM dnsinfos WHERE domain_id=$domain_id");
	if(!$q->ok){echo $tpl->FATAL_ERROR_SHOW_128($q->mysql_error);}
	
	
	if($ligne["renewdate"]>0){
		$renewdate=date("Y-m-d",$ligne["renewdate"]);
	}
	
	$zinfo=unserialize(base64_decode($ligne["zinfo"]));
	$form[]=$tpl->field_hidden("name", $dname);
	$form[]=$tpl->field_hidden("domainid", $domain_id);
	$form[]=$tpl->field_checkbox("cialdom","{official_domain}",$ligne["cialdom"]);
	$form[]=$tpl->field_date("renewdate","{expiredate}",$renewdate);
	$form[]=$tpl->field_textareacode("explain", "{information}", $ligne["explain"]);
	
	$PowerDNSEnableClusterSlave=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("PowerDNSEnableClusterSlave"));
	VERBOSE("PowerDNSEnableClusterSlave=$PowerDNSEnableClusterSlave", __LINE__);
	
	if($PowerDNSEnableClusterSlave==1){
		$tpl->FORM_LOCKED=true;
		$tpl->this_form_locked_explain="{form_locked_cluster_client}";
	}
	
	if(count($zinfo)>0){
	    $FERROR=false;
	    $ferror=array();
		foreach ($zinfo as $line){
			$line=trim($line);
			if($line==null){continue;}
			if($GLOBALS["VERBOSE"]){echo "LINE --- $line ---<br>\n";}
			if(preg_match("#Checked [0-9]+ records of#i",$line)){continue;}
			if(preg_match("#Backend launched with banner#i", $line)){continue;}
            if(preg_match("#UeberBackend destructor#i", $line)){continue;}
            if(preg_match("#Error:(.+)#",$line,$re)){
                if($GLOBALS["VERBOSE"]){echo " ! ! ! ! ! FOUND $line !!!! ---<br>\n"; }
                if(trim($re[1])==null){continue;}
                $FERROR=true;
                $md5=md5($re[1]);
                $ferror[$md5]="<strong><i class=\"fas fa-exclamation-circle\"></i>&nbsp;".trim($re[1])."</strong>";
                continue;
            }
            if(preg_match("#[0-9]+\s+Error(.+)#i", $line,$re)){
                if(trim($re[1])==null){continue;}
                $FERROR=true;
                $md5=md5($re[1]);
                $ferror[$md5]="<strong><i class=\"fas fa-exclamation-circle\"></i>&nbsp;".trim($re[1])."</strong>";
                continue;
            }


			$explain_form.= "<i class=\"far fa-check-circle\"></i>&nbsp;$line<br>";
		}
	}
	
	if($FERROR){
	    $tt=array();
	    foreach ($ferror as $md5=>$line){$tt[]=$line;}
        $html[]=$tpl->div_error(@implode("<br>",$tt));
    }
	
	
	$ARRAY["PROGRESS_FILE"]=PROGRESS_DIR."/dns.rectify-zone.progress";
	$ARRAY["LOG_FILE"]=PROGRESS_DIR."/dns.rectify-zone.log";
	$ARRAY["CMD"]="pdns.php?rectify-zone=$domain_id";
	$ARRAY["TITLE"]="{rectify_zone}";
	$ARRAY["AFTER"]="LoadAjax('DNSINFO-2','$page?dns-info2=$domain_id');";
	$prgress=base64_encode(serialize($ARRAY));
	$jsafter="Loadjs('fw.progress.php?content=$prgress&mainid=zone-info-domain-progress')";
	$tpl->form_add_button("{rectify_zone}", "$jsafter");

    $html[]=$tpl->form_outside($dname, $form,$explain_form,"{apply}","LoadAjax('table-loader','fw.dns.local.domains.php?main=yes');","AsDnsAdministrator");

    echo $tpl->_ENGINE_parse_body($html);
    return true;

}

function dns_info_save(){
	$tpl=new template_admin();
	$tpl->CLEAN_POST();
	$domain_id=$_POST["domainid"];
	$q=new lib_sqlite("/home/artica/SQLITE/dns.db");
	$ligne=$q->mysqli_fetch_array("SELECT zinfo FROM dnsinfos WHERE domain_id=$domain_id");
	
	$fields["domain_id"]=$_POST["domainid"];
	$fields["name"]=$_POST["name"];
	$fields["cialdom"]=$_POST["cialdom"];
	$fields["renewdate"]=strtotime($_POST["renewdate"]);
	$fields["explain"]=sqlite_escape_string2($_POST["explain"]);
	$fields["zinfo"]=$ligne["zinfo"];
	
	$q->QUERY_SQL("DELETE FROM dnsinfos WHERE domain_id=$domain_id");
	
	foreach ($fields as $key=>$val){
		$fa[]="`$key`";
		$fb[]="'$val'";
	}
	
	$sql="INSERT INTO dnsinfos (".@implode(",", $fa).") VALUES (".@implode(",", $fb).")";
	$q->QUERY_SQL($sql);
	if(!$q->ok){echo $q->mysql_error;}
	
	
}

