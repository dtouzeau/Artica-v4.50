<?php
// POST SP10 20261023-12
// Post SP10 - 13/10/2026
// Service Pack 9 2026-10-10
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.mysql.powerdns.inc");
// awc_table_style() / awc_pager_js(): feuille de style et pager partages des tableaux d'entites
include_once(dirname(__FILE__)."/ressources/class.statistics_appliance.inc");
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}

if(isset($_GET["search"])){main();exit;}
if(isset($_GET["main"])){main();exit;}
if(isset($_GET["newdomain-js"])){newdomain_js();exit;}
if(isset($_GET["new-domain-popup"])){newdomain_popup();exit;}
if(isset($_POST["newdomain"])){newdomain_save();exit;}
if(isset($_GET["delete-js"])){delete_js();exit;}
if(isset($_POST["delete"])){domain_delete();exit;}
page();



/**
 * Le nom de la fonction de rafraichissement transmis dans l'URL: il repart dans
 * des chaines JavaScript sans echappement.
 */
function dnsloc_function():string{
	return preg_replace('/[^A-Za-z0-9_]/','',(string)(isset($_GET["function"])?$_GET["function"]:""));
}
/** L'appel de rafraichissement, ou une chaine vide s'il n'y a rien a appeler. */
function dnsloc_call():string{
	$fname=dnsloc_function();
	return ($fname==="")?"":"$fname();";
}

function newdomain_js(){
	
	$page=CurrentPageName();
	$tpl=new template_admin();
	$function=dnsloc_function();
	$title=$tpl->javascript_parse_text("{new_domain}");
	$tpl->js_dialog($title, "$page?new-domain-popup=yes&function=$function");
	
}

function delete_js(){
	
	header("content-type: application/x-javascript");
	$page=CurrentPageName();
	$tpl=new templates();
	$t=time();
	$ask=$tpl->javascript_parse_text("{pdns_delete_domain_ask}");	
	// Pentest 2026-11-04 : delete-js etait concatene dans la requete.
	$id=intval($_GET["delete-js"]);
	
	$q=new mysql_pdns();
	$sql="SELECT name FROM domains WHERE id=$id";
	$ligne=mysqli_fetch_array($q->QUERY_SQL($sql));
	$domain=$ligne["name"];
	$sql="SELECT COUNT(id) AS tcount FROM records WHERE domain_id=$id";
	$ligne=mysqli_fetch_array($q->QUERY_SQL($sql));
	$items=intval($ligne["tcount"]);
	
	$ask=str_replace("%d", "$domain", $ask);
	$ask=str_replace("%c", "$items", $ask);
	// $_GET["id"] repartait BRUT dans un selecteur jQuery: c'est un md5.
	$rowmd=preg_replace('/[^a-f0-9]/','',(string)($_GET["id"] ?? ""));
	
	
	$html="			
var xSave$t=function (obj) {
	var results=obj.responseText;
	if(results.length>0){alert(results);return;}	
	$('#{$rowmd}').remove();
}
function Save$t(){
	if(!confirm('$ask')){return;}
	var XHR = new XHRConnection();
	XHR.appendData('delete','$id');
	XHR.sendAndLoad('$page', 'POST',xSave$t);	
}
	
Save$t()";
	echo $html;
	
}



function newdomain_popup(){
		$page=CurrentPageName();
		$tpl=new template_admin();
        $function=dnsloc_function();
		$ERROR_VALUE_MISSING_PLEASE_FILL_THE_FORM=$tpl->javascript_parse_text('{ERROR_VALUE_MISSING_PLEASE_FILL_THE_FORM}');
        if($function<>null){$function="$function();";}
		$form[]=$tpl->field_text("newdomain", "{domain}", null);
		$html[]=$tpl->form_outside("{new_domain}", @implode("\n", $form),null,"{add}",
				"BootstrapDialog1.close();$function","AsDnsAdministrator");
		echo $tpl->_ENGINE_parse_body(@implode("\n", $html));
}
function newdomain_save(){
    $tpl=new template_admin();
    $tpl->CLEAN_POST();
	$q=new mysql_pdns();
    $MyHostname=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("myhostname"));
    $q->QUERY_SQL("ALTER TABLE `records` ALTER `articasrv` SET DEFAULT '".mysql_escape_string2($MyHostname)."'");
    $q->QUERY_SQL("ALTER TABLE `records` ALTER `explainthis` SET DEFAULT ''");
    $domain=trim(strtolower($_POST["newdomain"]));
	$domain=url_decode_special_tool($domain);
    if(strpos(" $domain","%")>0){
        echo $tpl->post_error("[$domain] {invalid}");
        return false;
    }
    if(strpos(" $domain",";")>0){
        echo $tpl->post_error("[$domain] {invalid}");
        return false;
    }
    if(strpos(" $domain",",")>0){
        echo $tpl->post_error("[$domain] {invalid}");
        return false;
    }


	if($domain==null){
        echo $tpl->post_error("{no_data}");
		return false;
	}
	$q->AddDomain($domain);
    return true;
}
function domain_delete(){
	// Pentest 2026-11-04 : delete etait concatene dans la requete.
	$id=intval($_POST["delete"]);
	$q=new mysql_pdns();

	$Domain=$q->GetDomainName($id);

	$q->QUERY_SQL("DELETE FROM records WHERE domain_id=$id");
	if(!$q->ok){echo $q->mysql_error;return;}
	$q->QUERY_SQL("DELETE FROM domains WHERE id=$id");
	if(!$q->ok){echo $q->mysql_error;return;}
	$q->QUERY_SQL("DELETE FROM domainmetadata WHERE id=$id");
	if(!$q->ok){echo $q->mysql_error;return;}

	$q->QUERY_SQL("DELETE FROM pdnsutil_dnssec WHERE domain_id=$id");
	$q->QUERY_SQL("DELETE FROM pdnsutil_chkzones WHERE domain_id=$id");

	$q=new lib_sqlite("/home/artica/SQLITE/dns.db");
	$q->QUERY_SQL("DELETE FROM dnsinfos WHERE domain_id=$id");

	admin_tracks("Removed all datas from domain $Domain");

	$sock=new sockets();
	$sock->getFrameWork("pdns.php?dnssec=yes");
	

}

function title_extention():string{
    $Key="PowerDNSEnableClusterSlave";
    $keydate="PowerDNSClusterClientDate";
    $ClusterSlave=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO($Key));
    if($ClusterSlave==1){return "";}
    $ClusterClientDate=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO($keydate));
    if($ClusterClientDate==0){return "";}
    $tpl=new template_admin();
    $text_date=$tpl->time_to_date($ClusterClientDate,true);
    return "&nbsp;<small>{last_sync}: $text_date</small>";

}

function page(){
    $page       = CurrentPageName();
	$tpl        = new template_admin();
	$title_add  = title_extention();


    $html=$tpl->page_header("{local_domains}$title_add","fab fab fa-soundcloud",
    "{local_domains_dns_explain}","$page?main=yes","dns-local-domains","progress-firehol-restart",true,"table-local-domains");



    if(isset($_GET["main-page"])){
        $tpl=new template_admin(null,$html);
        echo $tpl->build_firewall();
        return true;
    }


	echo $tpl->_ENGINE_parse_body($html);
    return true;
}

function btns(){
    $tpl=new template_admin();
    $page=CurrentPageName();
    $q          = new mysql_pdns();
    $PowerDNSEnableClusterSlave=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("PowerDNSEnableClusterSlave"));
    $EnablePDNS      = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnablePDNS"));
    $UnboundEnabled=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UnboundEnabled"));
    $new_entry=$tpl->_ENGINE_parse_body("{new_domain}");
    $MyHostname=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("myhostname"));
    $q->QUERY_SQL("ALTER TABLE `records` ALTER `articasrv` SET DEFAULT '".mysql_escape_string2($MyHostname)."'");
    $q->QUERY_SQL("ALTER TABLE `records` ALTER `explainthis` SET DEFAULT ''");

    $ARRAY["PROGRESS_FILE"]="/usr/share/artica-postfix/ressources/logs/web/recusor.restart.progress";
    $ARRAY["LOG_FILE"]="/usr/share/artica-postfix/ressources/logs/web/recusor.restart.log";
    $ARRAY["CMD"]="pdns.php?reload-recusor=yes";
    $ARRAY["TITLE"]="{reconfigure_service} {APP_PDNS_RECURSOR}";
    $prgress=base64_encode(serialize($ARRAY));
    $jsrestart="Loadjs('fw.progress.php?".fw_progress_args_b64($prgress)."&mainid=progress-firehol-restart')";

    if($UnboundEnabled==1){

        $jsrestart=$tpl->framework_buildjs("/unbound/restart",
            "unbound.restart.progress","unbound.restart.log",
            "progress-firehol-restart");
    }




    $ARRAY["PROGRESS_FILE"]="/usr/share/artica-postfix/ressources/logs/pdns.dnssec.progress";
    $ARRAY["LOG_FILE"]="/usr/share/artica-postfix/ressources/logs/pdns.dnssec.progress.log";
    $ARRAY["CMD"]="pdns.php?dnssec=yes";
    $ARRAY["TITLE"]="{repair_domains}";
    $prgress=base64_encode(serialize($ARRAY));
    $jsrepair="Loadjs('fw.progress.php?".fw_progress_args_b64($prgress)."&mainid=progress-firehol-restart')";





    $RepairMySQL=$tpl->framework_buildjs(
        "pdns.php?repair-database=yes",
        "pdns.repair.progress",
        "pdns.repair.log",
        "progress-firehol-restart",
        // Sans nom de fonction, "();"" produirait une SyntaxError qui avorterait
        // tout le bloc engendre par framework_buildjs().
        dnsloc_call()
    );


    $bts[]="<div class=\"btn-group\" data-toggle=\"buttons\">";
    if($PowerDNSEnableClusterSlave==0){
        $bts[]="<label class=\"btn btn btn-primary\" OnClick=\"Loadjs('$page?newdomain-js=yes&function=".dnsloc_function()."');\"><i class='fa fa-plus'></i> $new_entry </label>";
    }
    $bts[]="<label class=\"btn btn btn-info\" OnClick=\"$jsrestart\"><i class='fa fa-save'></i> {reconfigure_service} </label>";

    if($UnboundEnabled==0){
        $bts[]=$tpl->_ENGINE_parse_body("<label class=\"btn btn btn-primary\" OnClick=\"$jsrepair\"><i class='fas fa-screwdriver'></i> {repair_domains} </label>");
    }
    if($EnablePDNS==1){
        $bts[]=$tpl->_ENGINE_parse_body("<label class=\"btn btn btn-warning\" OnClick=\"$RepairMySQL\"><i class='fas fa-screwdriver'></i> {mysql_repair} </label>");
    }

    $bts[]="</div>";
    return @implode("",$bts);

}

function main(){
	$tpl=new template_admin();
	$page=CurrentPageName();
	$q=new mysql_pdns();
    $function=dnsloc_function();
	$database='artica_backup';
	$UnboundEnabled=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UnboundEnabled"));
	$PowerDNSDNSSEC=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("PowerDNSDNSSEC"));
	$PowerDNSEnableClusterSlave=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("PowerDNSEnableClusterSlave"));
	$DNSSEC=false;
	$PDNSStatus=false;
	// ($rowToAdd servait a recalculer un colspan a la main: le nombre de
	// colonnes est desormais deduit du tableau d'en-tetes.)
	if($UnboundEnabled==0){$PDNSStatus=true;if($PowerDNSDNSSEC==1){$DNSSEC=true;}}

	$delete=$tpl->javascript_parse_text("{delete}");
	$items=$tpl->_ENGINE_parse_body("{items}");
	$domains=$tpl->_ENGINE_parse_body("{domains}");



	// Les en-tetes sont assembles dans un tableau: le nombre de colonnes varie
	// selon la configuration (statut PowerDNS, DNSSEC, metadonnees) et il faut
	// le connaitre pour le colspan de l'etat vide. Il etait auparavant recalcule
	// a la main par un compteur $rowToAdd incremente a deux endroits eloignes.
	$TH=array();
	$TH[]="<th class='center'>ID</th>";
	$TH[]="<th>$domains</th>";
	if($PDNSStatus){ $TH[]="<th class='center'>{status}</th>"; }
	if($DNSSEC){ $TH[]="<th class='center'>DNSSEC</th>"; }
	if($UnboundEnabled==0){ $TH[]="<th class='center' nowrap>{META_DATA}</th>"; }
	$TH[]="<th class='center'>$items</th>";
	$TH[]="<th style='text-align:right'>$delete</th>";
	$COLS=count($TH);

	// Le terme partait TEL QUEL dans la clause LIKE: une simple quote suffisait
	// a detourner la requete.
	$LIKE="";
	$search=trim((string)($_GET["search"] ?? ""));
	if($search!==""){
		$like=str_replace(array("'","\\"),"",str_replace("*","%",$search));
		$LIKE="AND `name` LIKE '%$like%'";
	}

	$sql="SELECT * FROM records WHERE `type`='SOA' $LIKE ORDER BY name";
	$results = $q->QUERY_SQL($sql,$database);
	// L'erreur etait affichee PUIS l'execution continuait: mysqli_fetch_assoc()
	// sur false est une TypeError sous PHP 8.
	if(!$q->ok){echo $tpl->FATAL_ERROR_SHOW_128(htmlspecialchars((string)$q->mysql_error));return;}
	
	$qSQLITE=new lib_sqlite("/home/artica/SQLITE/dns.db");

	
	$rows=array();
	while ($ligne = mysqli_fetch_assoc($results)) {
		$dnssec_row=null;
		$PDNSStatus_row=null;
		$addTodomain=array();
		$md=md5(serialize($ligne));
		$domain_id=intval($ligne["domain_id"]);
		$ligne2=mysqli_fetch_array($q->QUERY_SQL("SELECT COUNT(id) AS tcount FROM records WHERE domain_id=$domain_id"));
		$items=intval(is_array($ligne2)?$ligne2["tcount"]:0);
        $duplicate=null;

		$ligne2=mysqli_fetch_array($q->QUERY_SQL("SELECT COUNT(*) AS tcount FROM domainmetadata WHERE domain_id=$domain_id"));
		$domainmetadata=intval(is_array($ligne2)?$ligne2["tcount"]:0);

		// Ces trois lectures se faisaient sans verifier que la ligne existe:
		// un domaine absent de dnsinfos produisait trois avertissements PHP 8.
		$ligne2=$qSQLITE->mysqli_fetch_array("SELECT * FROM dnsinfos WHERE domain_id=$domain_id");
		if(!is_array($ligne2)){$ligne2=array();}
		$cialdom=intval($ligne2["cialdom"] ?? 0);
		$renewdate=intval($ligne2["renewdate"] ?? 0);
		$explain=(string)($ligne2["explain"] ?? "");
		$distance_text=null;
		$distance_color="text-primary";
		if($cialdom==1){
			$DistanceInMns=DistanceInMns($renewdate);
			if($DistanceInMns<43200){$distance_color="text-warning";}
			if($DistanceInMns<21600){$distance_color="text-danger";}
			$distance_text=distanceOfTimeInWords($renewdate,time());
			if($DistanceInMns<1){$distance_text="{expired}";}
			$addTodomain[]="<span class='$distance_color' style='font-weight:bold'>{renew}: $distance_text</span>";
		}
			
		// $explain vient de SQLite et partait BRUT dans le HTML.
		if($explain!==""){$addTodomain[]="<small>".htmlspecialchars($explain)."</small>";}
		
		
		if($DNSSEC){
			$sql="SELECT id FROM pdnsutil_dnssec WHERE domain_id=$domain_id";
			$ligne2=mysqli_fetch_array($q->QUERY_SQL($sql));
			if(intval(is_array($ligne2)?($ligne2["id"] ?? 0):0)>0){
				$dnssec_row="<td style='width:1%' nowrap><a href=\"javascript:blur();\" 
						OnClick=\"Loadjs('fw.pdns.dnssec.php?domain_id=$domain_id');\"><span class='label label-primary'>{enabled}</span></a></td>";
			}else{
				$dnssec_row="<td style='width:1%' nowrap><span class='label'>{error}</span></td>";
			}
		}
		VERBOSE("PDNSStatus == $PDNSStatus",__LINE__);
		if($PDNSStatus){
            $PDNSStatus_row=isPdnsError($domain_id);
		}
		
		
		$jshost="GotoPDNSRecords($domain_id);";
		$metadata=$tpl->td_href(FormatNumber($domainmetadata)."&nbsp;","{META_DATA}","Loadjs('fw.pdns.metadata.php?domain_id=$domain_id')");
		
		// Le nom de domaine vient de la base et partait BRUT dans le HTML.
		$domain=$tpl->td_href(htmlspecialchars((string)$ligne["name"]),"{items}","Loadjs('fw.dns.domain.php?domain-id=$domain_id')");
		$row ="<tr id='$md'>";
		$row.="<td class='center' style='width:1%' nowrap>$domain_id</td>";

		if(isset($ALREADY_DOM[$domain_id])){
            $duplicate="&nbsp;&nbsp;<span class='label label-danger'>{duplicated_record}: SOA</span>";
        }else{
            $ALREADY_DOM[$domain_id]=true;
        }



		$delete_icon=$tpl->icon_nothing();
		if($PowerDNSEnableClusterSlave==0){
			$delete_icon=$tpl->icon_delete("Loadjs('$page?delete-js=$domain_id&id=$md')","AsDnsAdministrator");
		}
		
		$addTodomain_text=null;
		if(count($addTodomain)>0){$addTodomain_text="<div class='awc-meta'>".@implode("<br>", $addTodomain)."</div>";}
		$row.="<td><span class='awc-name'>$domain</span>{$duplicate}$addTodomain_text</td>";
		$row.=(string)$PDNSStatus_row;
		$row.=(string)$dnssec_row;
		if($UnboundEnabled==0){$row.="<td class='center' style='width:1%' nowrap>$metadata</td>";}
		$row.="<td class='center' style='width:1%' nowrap><span class='awc-seen'>".FormatNumber($items)."</span></td>";
		$row.="<td><div class='awc-acts'>$delete_icon</div></td>";
		$row.="</tr>";
		$rows[]=$row;
	}
	$c=count($rows);

	// Le tableau est monte ICI, une fois le nombre de colonnes et de lignes
	// connus. Le <tfoot> portait un pager footable dont l'init ciblait
	// #table-dns-forward-zones -- un id qu'AUCUN <table> de cette page ne porte
	// -- et son colspan etait recalcule par un compteur $rowToAdd incremente a
	// deux endroits eloignes du fichier.
	$html=array();
	$html[]="<div class='awc-wrap'><div class='awc-card'><div class='awc-scroll'><table class='awc-tbl awc-tbl-compact'>";
	$html[]="<thead><tr>".@implode("",$TH)."</tr></thead>";
	$html[]="<tbody id='dnsloc-rows'>";
	if($c==0){
		$html[]="<tr><td colspan='$COLS'><div class='awc-empty'>{no_data}</div></td></tr>";
	}else{
		$html[]=@implode("\n",$rows);
	}
	$html[]="</tbody></table></div>";
	$html[]="<div class='awc-foot'>";
	$html[]="<span>{showing} $c {items}</span>";
	$html[]="<span class='awc-pg' id='dnsloc-pager'></span>";
	$html[]="</div></div></div>";


    $title_add  = title_extention();
    $TINY_ARRAY["TITLE"]="{local_domains} $title_add";
    $TINY_ARRAY["ICO"]="fab fab fa-soundcloud";
    $TINY_ARRAY["EXPL"]="{local_domains_dns_explain}";
    $TINY_ARRAY["URL"]="dns-local-domains";
    $TINY_ARRAY["BUTTONS"]=btns();
    $jstiny="Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";


    // Le <script> est concatene APRES l'analyse: la regex #{(.+?)}# de
    // _ENGINE_parse_body lisait la configuration JSON de footable comme des
    // {tokens}.
    $script="<script>\n$jstiny\nNoSpinner();\n".$tpl->ICON_SCRIPTS_COMPILE()."\n</script>";

    echo awc_table_style()
        .$tpl->_ENGINE_parse_body(@implode("\n", $html))
        .$script
        .awc_pager_js(50,"dnsloc-rows","dnsloc-pager");
}
function FormatNumber($number, $decimals = 0, $thousand_separator = '&nbsp;', $decimal_point = '.'){$tmp1 = round((float) $number, $decimals); while (($tmp2 = preg_replace('/(\d+)(\d\d\d)/', '\1 \2', $tmp1)) != $tmp1)$tmp1 = $tmp2; return strtr($tmp1, array(' ' => $thousand_separator, '.' => $decimal_point));}
function DistanceInMns($time){
	$data1 = $time;
	$data2 = time();
	$difference = ($data2 - $data1);
	return round($difference/60);
}

function isPdnsError($domain_id):string{
    $domain_id=intval($domain_id);
    VERBOSE(__FUNCTION__,__LINE__);
    $q          = new mysql_pdns();
    $tpl        = new template_admin();

    if($q->TABLE_EXISTS("pdnsutil_chkzones")) {
        $sql = "SELECT COUNT(*) AS tcount FROM pdnsutil_chkzones WHERE domain_id=$domain_id";
        $ligne2 = mysqli_fetch_array($q->QUERY_SQL($sql));


        if (!$q->ok) {
            return "<td style='width:1%' nowrap><i class=\"fas fa-exclamation-circle\"></i><span class='text-danger'>" . $tpl->td_href("MySQL Error", $q->mysql_error) . "</span></td>";
        }

        $zcount = $ligne2["tcount"];
        if ($zcount > 0) {
            return "<td style='width:1%' nowrap><a href=\"javascript:blur();\" 
						OnClick=\"Loadjs('fw.pdns.domains.status.php?domain_id=$domain_id');\"
						><span class='label label-warning'>{$zcount} {errors}</span></a></td>";
        }
    }

    $q      = new lib_sqlite("/home/artica/SQLITE/dns.db");
    $ligne  = $q->mysqli_fetch_array("SELECT * FROM dnsinfos WHERE domain_id=$domain_id");

    if(!$q->ok){
        return "<td style='width:1%' nowrap><i class=\"fas fa-exclamation-circle\"></i><span class='text-danger'>".
            $tpl->td_href("MySQL Error",$q->mysql_error)."</span></td>";
    }



    if($ligne["renewdate"]>0){
        $renewdate=date("Y-m-d",$ligne["renewdate"]);
    }

    $zinfo=unserialize(base64_decode($ligne["zinfo"]));

    if(count($zinfo)>0) {
        foreach ($zinfo as $line) {
            $line = trim($line);
            if ($line == null) {
                continue;
            }
            VERBOSE($line,__LINE__);

            if (preg_match("#Backend launched with banner#i", $line)) {
                continue;
            }
            if (preg_match("#UeberBackend destructor#i", $line)) {
                continue;
            }
            if (preg_match("#Error:(.+)#", $line, $re)) {
                if (trim($re[1]) == null) {
                    continue;
                }
                return "<td style='width:1%' nowrap><span class='label label-danger'>{error}</span></a></td>";

            }
            if (preg_match("#[0-9]+\s+Error(.+)#i", $line)) {
                if (trim($re[1]) == null) {
                    continue;
                }
                return "<td style='width:1%' nowrap><span class='label label-danger'>{error}</span></a></td>";

            }
        }
    }

    return "<td style='width:1%' nowrap><span class='label label-primary'>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;OK&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</span></a></td>";
}


?>