<?php
// POST SP10 20261023-12
// Post SP10 - 13/10/2026
// Service Pack 9 2026-10-10
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.mysql.powerdns.inc");
// awc_table_style() / awc_pager_js(): feuille de style et pager partages des tableaux d'entites
include_once(dirname(__FILE__)."/ressources/class.statistics_appliance.inc");
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}

if(isset($_GET["tabs"])){tabs();exit;}
if(isset($_GET["items-table-start"])){table_start();exit;}
if(isset($_GET["table"])){table();exit;}
if(isset($_GET["dns-info"])){dns_info();exit;}
if(isset($_GET["dns-info2"])){dns_info2();exit;}
// (les dispatchs ?popup= et ?delete-js= appelaient popup() et delete_js(),
// absentes de ce fichier ET de ses includes: toute requete les portant mourait
// sur un "Call to undefined function". Les deux noms sont ceux d'AUTRES pages
// -- fw.pdns.metadata.php?popup=yes et fw.dns.records.php?delete-js=, vers
// lesquelles ce fichier renvoie effectivement.)
if(isset($_POST["domainid"])){dns_info_save();exit;}
if(isset($_GET["expert"])){expert_popup();exit;}
if(isset($_POST["expert"])){expert_save();exit;}
js();

/**
 * Le nom de la fonction de rafraichissement transmis dans l'URL: il repart dans
 * des chaines JavaScript sans echappement.
 */
function pdnsdom_function():string{
	return preg_replace('/[^A-Za-z0-9_]/','',(string)(isset($_GET["function"])?$_GET["function"]:""));
}

function js(){
	$page=CurrentPageName();
	$domain_id=intval($_GET["domain-id"]);
	$tpl=new template_admin();
	$q=new mysql_pdns();
	// Le nom de domaine vient de la base et partait BRUT dans le titre.
	$domainame=htmlspecialchars((string)$q->GetDomainName($domain_id));
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
    $domain_id=intval($_GET["expert"]);
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
    // $zone vient du POST et composait DEUX chemins de fichiers ainsi que le
    // parametre d'un appel framework. Un "../.." y ecrivait donc un fichier
    // arbitraire avec le contenu du formulaire. Un nom de zone DNS n'a ni "/"
    // ni "..".
    $zone=(string)($_POST["expert"] ?? "");
    if(preg_match('#^[A-Za-z0-9._-]+$#',$zone)!=1){
        echo $tpl->post_error("{error}: zone");
        return false;
    }
    $DESTF=PROGRESS_DIR."/$zone.save";
    @file_put_contents($DESTF,$_POST["export"]);
    $GLOBALS["CLASS_SOCKETS"]->getFrameWork("pdns.php?pdns-util-save-zone=".urlencode($zone));

    $DESTF=PROGRESS_DIR."/$zone.log";
    $datas=explode("\n",(string)@file_get_contents($DESTF));
    foreach ($datas as $line){
        $line=trim($line);
        if($line==null){continue;}
        if(preg_match("#^[a-zA-Z]+\s+[0-9]+\s+[0-9]+:[0-9]+#",$line)){continue;}
        if(preg_match("#^Error:\s+(.+)#",$line,$re)){
            // post_error() RENVOIE la chaine "jserror:..." -- elle n'etait pas
            // echo'ee, donc l'erreur de pdnsutil etait perdue et form_outside
            // voyait une reponse vide, c'est-a-dire un SUCCES.
            echo $tpl->post_error($re[1]);
            return false;
        }
    }

    return true;
}


function table(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$q=new mysql_pdns();
	$PowerDNSEnableClusterSlave=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("PowerDNSEnableClusterSlave"));
	$domain_id=intval($_GET["domain-id"]);
	$function=pdnsdom_function();

	// search_sql() nettoie desormais le terme (voir class.mysql.powerdns.inc):
	// il partait auparavant tel quel dans quatre clauses LIKE.
	$results = $q->QUERY_SQL($q->search_sql((string)($_GET["search"] ?? ""),$domain_id));
	if(!$q->ok){echo $tpl->FATAL_ERROR_SHOW_128("LINE ".__LINE__." ".htmlspecialchars((string)$q->mysql_error));return;}

	$rows=array();
	while ($ligne = mysqli_fetch_assoc($results)) {
		$md=md5(serialize($ligne));
		$id=intval($ligne["id"]);
		$type=(string)$ligne["type"];
		$name=(string)$ligne["name"];
		$content=(string)$ligne["content"];
		$disabled=intval($ligne["disabled"]);
		$enable_button=$tpl->icon_nothing();
		$delete_button=$tpl->icon_nothing();
		if(strlen($content)>45){$content=substr($content, 0,42)."...";}
		if(strlen($name)>27){$name=substr($name, 0,24)."...";}
		$jshost="Loadjs('fw.dns.records.php?record-info-js=yes&domainid=$domain_id&type=".urlencode($type)."&id=$id&function=$function');";

		$enable=($disabled==0);

		if($PowerDNSEnableClusterSlave==0){
			$enable_button=$tpl->icon_check($enable,"Loadjs('fw.dns.records.php?enable-js=$id&id=$md')",null,"AsDnsAdministrator");
			$delete_button=$tpl->icon_delete("Loadjs('fw.dns.records.php?delete-js=$id&id=$md')","AsDnsAdministrator");
		}

		// Nom, type et contenu sont des donnees d'enregistrement DNS et
		// partaient BRUTS dans le HTML. Les deux </a> orphelins fermaient un
		// element que td_href() referme deja.
		$rows[]="<tr id='$md'>"
			."<td class='center' style='width:1%' nowrap>".$tpl->td_href($id,"{click_to_edit}",$jshost)."</td>"
			."<td><span class='awc-name'>".$tpl->td_href(htmlspecialchars($name),"{click_to_edit}",$jshost)."</span></td>"
			."<td class='center' style='width:1%' nowrap><span class='awc-tag'>".$tpl->td_href(htmlspecialchars($type),"{click_to_edit}",$jshost)."</span></td>"
			."<td class='awc-mono' style='font-weight:bold'>".htmlspecialchars($content)."</td>"
			."<td class='center' style='width:1%'>$enable_button</td>"
			."<td><div class='awc-acts'>$delete_button</div></td>"
			."</tr>";
	}
	$c=count($rows);

	$html=array();
	$html[]="<div class='awc-wrap'><div class='awc-card'><div class='awc-scroll'><table class='awc-tbl awc-tbl-compact'>";
	$html[]="<thead><tr>";
	$html[]="<th class='center'>ID</th>";
	$html[]="<th>{record}</th>";
	$html[]="<th class='center'>{type}</th>";
	$html[]="<th>{content}</th>";
	$html[]="<th class='center'>{enable}</th>";
	$html[]="<th style='text-align:right'>{delete}</th>";
	$html[]="</tr></thead><tbody id='pdnsrec-rows'>";
	if($c==0){
		$html[]="<tr><td colspan='6'><div class='awc-empty'>{no_data}</div></td></tr>";
	}else{
		$html[]=@implode("\n",$rows);
	}
	// Le <tfoot> portait un pager footable dont l'init ciblait $('.footable') --
	// un selecteur GLOBAL, alors que ce tableau porte .awc-tbl: il ne faisait
	// rien ici et reinitialisait tout autre tableau footable de la page.
	$html[]="</tbody></table></div>";
	$html[]="<div class='awc-foot'>";
	$html[]="<span>{showing} $c {items}</span>";
	$html[]="<span class='awc-pg' id='pdnsrec-pager'></span>";
	$html[]="</div></div></div>";

	// Le <script> est concatene APRES l'analyse: la regex #{(.+?)}# de
	// _ENGINE_parse_body lisait la configuration JSON de footable comme des
	// {tokens}. ICON_SCRIPTS_COMPILE() est requis: ce tableau porte des
	// icon_check(), qui alimentent aussi $GLOBALS["TPL_ICON_SCRIPTS"].
	$script="<script>NoSpinner();\n".$tpl->ICON_SCRIPTS_COMPILE()."</script>";

	echo awc_table_style()
		.$tpl->_ENGINE_parse_body(@implode("\n",$html))
		.$script
		.awc_pager_js(50,"pdnsrec-rows","pdnsrec-pager");

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
	$jsafter="Loadjs('fw.progress.php?".fw_progress_args_b64($prgress)."&mainid=zone-info-domain-progress')";
	$tpl->form_add_button("{rectify_zone}", "$jsafter");

    $html[]=$tpl->form_outside($dname, $form,$explain_form,"{apply}","LoadAjax('table-loader','fw.dns.local.domains.php?main=yes');","AsDnsAdministrator");

    echo $tpl->_ENGINE_parse_body($html);
    return true;

}

function dns_info_save(){
	$tpl=new template_admin();
	$tpl->CLEAN_POST();
	// Pentest 2026-11-04 : domainid/name/cialdom/zinfo etaient concatenes dans la requete.
	$domain_id=intval($_POST["domainid"]);
	$q=new lib_sqlite("/home/artica/SQLITE/dns.db");
	$ligne=$q->mysqli_fetch_array("SELECT zinfo FROM dnsinfos WHERE domain_id=$domain_id");
	
	$fields["domain_id"]=$domain_id;
	$fields["name"]=$_POST["name"];
	$fields["cialdom"]=$_POST["cialdom"];
	$fields["renewdate"]=strtotime($_POST["renewdate"]);
	$fields["explain"]=$_POST["explain"];
	$fields["zinfo"]=$ligne["zinfo"];
	
	$q->QUERY_SQL("DELETE FROM dnsinfos WHERE domain_id=$domain_id");
	
	$fv=array();
	foreach ($fields as $key=>$val){
		$fa[]="`$key`";
		$fb[]="?";
		$fv[]=(string)$val;
	}
	
	$sql="INSERT INTO dnsinfos (".@implode(",", $fa).") VALUES (".@implode(",", $fb).")";
	$q->QUERY_SQL_PARAMS($sql,$fv);
	if(!$q->ok){echo $q->mysql_error;}
	
	
}

