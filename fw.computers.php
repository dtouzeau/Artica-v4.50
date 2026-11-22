<?php
// Refonte 17/11/2026 (maquette stitch_my_computers) - prefixe de page mycp_
// Inventaire des postes : table PostgreSQL hostsnet (une ligne par MAC), alimentee
// par l'ajout manuel, l'import CSV (/computers/import/csv), les baux DHCP Kea,
// l'ecouteur ARP et les enregistrements DNS Unbound (articarest, module hostnets).
// Post SP10 - 13/10/2026 / Service Pack 9 2026-10-10
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.computers.inc");
// awc_table_style() / awc_pager_js(): include INCONDITIONNEL (cf. CLAUDE.md, awc-tables).
include_once(dirname(__FILE__)."/ressources/class.statistics_appliance.inc");
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}

if(isset($_GET["file-uploaded"])){file_uploaded();exit;}
if(isset($_GET["search"])){table();exit;}
if(isset($_GET["delete-popup"])){mycp_delete_popup();exit;}
if(isset($_GET["delete-js"])){mycp_delete_js();exit;}
if(isset($_POST["delete-sql"])){delete_sql_perform();exit;}
if(isset($_POST["delete-all"])){delete_all_perform();exit;}
if(isset($_GET["export"])){export();exit;}
if(isset($_GET["import"])){import_computers_js();exit;}
if(isset($_GET["import-popup"])){import_computers_popup();exit;}
if(isset($_GET["export-progress"])){export_progress();exit;}
if(isset($_GET["download-export"])){export_download();exit;}
if(isset($_GET["search-my-computers-desktops"])){mycp_build();exit;}
page();


function page(){
	$tpl=new template_admin();
    $page=CurrentPageName();
    $html=$tpl->page_header("{my_computers}","fa-solid fa-computer","{explain_mycomputers}","$page?search-my-computers-desktops=yes","computers","progress-firehol-restart",false,"search-my-computers-desktops");
	if(isset($_GET["main-page"])){
	    $tpl=new template_admin("{my_computers}",$html);
	    echo $tpl->build_firewall();
	    return;
	}
	echo $tpl->_ENGINE_parse_body($html);
}

function mycp_can_write():bool{
    $tpl=new template_admin();
    return $tpl->IsSecurity("computer");
}

// --- Page : bandeau, hote des tuiles, recherche, syntaxe rapide ----------------------

function mycp_build():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $sb=$tpl->search_block($page,"","","","");
    $fn="ss".substr((string)$tpl->search_box_function,6);
    $fn=preg_replace('/[^A-Za-z0-9_]/','',$fn);
    $sbCss="";
    if(preg_match_all('#<style[^>]*>.*?</style>#is',$sb,$re)){
        $sbCss=implode("",$re[0]);
        $sb=preg_replace('#<style[^>]*>.*?</style>#is',"",$sb);
    }
    $html=array();
    $html[]="<div id='mycp-root'>";
    $html[]=mycp_banner();
    $html[]="<div id='mycp-tiles'></div>";
    $html[]="<div class='mycp-search'>$sb</div>";
    $html[]="</div>";
    $sb_after=mycp_quick();
    // La syntaxe rapide se place sous le champ de recherche, avant les resultats.
    $out=$tpl->_ENGINE_parse_body(implode("\n",$html));
    $quick=$tpl->_ENGINE_parse_body($sb_after);
    echo mycp_style().$sbCss.$out."<div id='mycp-quick-src' style='display:none'>$quick</div>".mycp_build_js($fn);
    return true;
}

function mycp_banner():string{
    $chips=array();
    $chips[]=array("fa-solid fa-share-nodes","{mycp_chip_shared}");
    if(intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableKEA"))==1){
        $chips[]=array("fa-solid fa-wand-magic-sparkles","{mycp_chip_dhcp}");
    }else{
        $chips[]=array("fa-solid fa-hand-pointer","{mycp_chip_manual}");
    }
    $chips[]=array("fa-solid fa-file-csv","{mycp_chip_export}");
    $c=array();
    foreach($chips as $x){ $c[]="<span class='mycp-chip'><i class='{$x[0]}' aria-hidden='true'></i>{$x[1]}</span>"; }
    return "<div class='mycp-banner' id='mycp-banner'>
<div class='mycp-bico'><i class='fa-solid fa-computer' aria-hidden='true'></i></div>
<div class='mycp-btxt'><div class='mycp-btitle'>{mycp_banner_title}</div><div class='mycp-bdesc'>{mycp_banner_text}</div>
<div class='mycp-chips'>".implode("",$c)."</div></div>
<button type='button' class='mycp-bclose' aria-label='{mycp_hide_banner}' title='{mycp_hide_banner}' OnClick=\"MycpHideBanner();\"><i class='fa-solid fa-xmark' aria-hidden='true'></i></button></div>";
}

function mycp_quick():string{
    $q=array(
        array("+fixed","{mycp_q_fixed}"),
        array("order by time","{mycp_q_time}"),
        array("order by ip","{mycp_q_ip}"),
        array("limit 500","{mycp_q_500}"),
    );
    $b=array();
    foreach($q as $x){
        $b[]="<button type='button' class='mycp-qchip' OnClick=\"MycpQuick('{$x[0]}',false);\"><span class='mycp-mono'>{$x[0]}</span><span class='mycp-qhelp'>{$x[1]}</span></button>";
    }
    return "<div class='mycp-quick'><span class='mycp-qlbl'>{mycp_quick_syntax}</span>".implode("",$b)."
<button type='button' class='mycp-tipsbtn' aria-expanded='false' aria-controls='mycp-tips' OnClick=\"MycpTips(this);\"><i class='fa-solid fa-circle-info' aria-hidden='true'></i> {mycp_search_tips} <i class='fa-solid fa-chevron-down' aria-hidden='true'></i></button></div>
<div class='mycp-tips' id='mycp-tips' hidden>
<div><b>{mycp_tip_names}</b><div>{mycp_tip_names_help}</div><div class='mycp-mono'>pc-accounting &middot; printer*</div></div>
<div><b>{mycp_tip_ip}</b><div>{mycp_tip_ip_help}</div><div class='mycp-mono'>192.168.1 &middot; 10.0.0.0/8</div></div>
<div><b>{mycp_tip_mac}</b><div>{mycp_tip_mac_help}</div><div class='mycp-mono'>3c:52:82:1a:9f:04 &middot; 3c:52</div></div>
<div><b>{mycp_tip_flags}</b><div>{mycp_tip_flags_help}</div><div class='mycp-mono'>+fixed +dns +unknown &middot; order by alias +asc</div></div>
</div>";
}

function mycp_build_js(string $fn):string{
    return "<script>
(function(){
  var src=document.getElementById('mycp-quick-src'), sb=document.querySelector('#mycp-root .sb-stitch .input-group');
  if(src && sb){ var res=sb.parentNode.querySelector('[id^=results-]'); while(src.firstChild){ sb.parentNode.insertBefore(src.firstChild, res); } src.parentNode.removeChild(src); }
  function input(){ return document.querySelector('#mycp-root .sb-stitch input.form-control'); }
  window.MycpRefresh=function(){ if(typeof window['$fn']==='function'){ window['$fn'](); } };
  window.MycpQuick=function(v,replace){
    var i=input(); if(!i){return;}
    if(replace){ i.value=v; }
    else if((' '+i.value+' ').indexOf(' '+v+' ')<0){
      if(/^limit /.test(v)){ i.value=i.value.replace(/(^|\\s)limit\\s+[0-9]+/ig,''); }
      if(/^order by /.test(v)){ i.value=i.value.replace(/(^|\\s)order by (time|ip|name|alias)/ig,''); }
      i.value=(i.value+' '+v).replace(/\\s+/g,' ').trim();
    }
    window.MycpRefresh();
  };
  window.MycpTips=function(b){ var t=document.getElementById('mycp-tips'); if(!t){return;} var o=t.hasAttribute('hidden'); if(o){t.removeAttribute('hidden');}else{t.setAttribute('hidden','');} b.setAttribute('aria-expanded',o?'true':'false'); };
  window.MycpHideBanner=function(){ var b=document.getElementById('mycp-banner'); if(b){b.style.display='none';} try{ localStorage.setItem('mycpBanner','0'); }catch(e){} };
  try{ if(localStorage.getItem('mycpBanner')==='0'){ var b=document.getElementById('mycp-banner'); if(b){b.style.display='none';} } }catch(e){}
})();
</script>";
}

// --- Fenetres de suppression : nombre d'appareils et consequences avant l'action ----

function mycp_delete_js():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    if(!mycp_can_write()){ echo $tpl->js_error("{ERROR_NO_PRIVS}"); return false; }
    $fn=preg_replace('/[^A-Za-z0-9_]/','',(string)($_GET["function"] ?? ""));
    $mode=($_GET["delete-js"]=="all")?"all":"search";
    $q=($mode=="search")?"&q=".urlencode((string)($_GET["q"] ?? ""))."&term=".urlencode((string)($_GET["term"] ?? "")):"";
    $title=$mode=="all"?"{mycp_del_all_title_short}":"{mycp_del_search_title_short}";
    return $tpl->js_dialog7($title,"$page?delete-popup=$mode&function=$fn$q",620);
}

function mycp_delete_popup():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    if(!mycp_can_write()){ echo $tpl->div_error("{ERROR_NO_PRIVS}"); return false; }
    $fn=preg_replace('/[^A-Za-z0-9_]/','',(string)($_GET["function"] ?? ""));
    $mode=$_GET["delete-popup"]=="all"?"all":"search";
    $q=new postgres_sql();
    $n=null;
    if($mode=="all"){
        $r=$q->mysqli_fetch_array("SELECT count(*) AS n FROM hostsnet");
        if(is_array($r) && isset($r["n"])){ $n=intval($r["n"]); }
        $title=$n==1?mycp_t("{mycp_del_all_title1}"):mycp_t("{mycp_del_all_title}",array(mycp_num($n)));
        $post="'delete-all':'yes'";
    }else{
        $token=mycp_token_in((string)($_GET["q"] ?? ""));
        $sql=signed_html_decode($token);
        if(strpos($sql,"DELETE FROM hostsnet WHERE ")!==0){ echo $tpl->div_error("{mycp_bad_token}"); return false; }
        $where=substr(str_replace("APUPRES","~",$sql),strlen("DELETE FROM hostsnet WHERE "));
        $r=$q->mysqli_fetch_array("SELECT count(*) AS n FROM hostsnet WHERE $where");
        if(is_array($r) && isset($r["n"])){ $n=intval($r["n"]); }
        $term=req_html((string)($_GET["term"] ?? ""));
        $title=$n==1?mycp_t("{mycp_del_search_title1}",array("&laquo;&nbsp;$term&nbsp;&raquo;")):mycp_t("{mycp_del_search_title}",array(mycp_num($n),"&laquo;&nbsp;$term&nbsp;&raquo;"));
        $post="'delete-sql':".json_encode($token);
    }
    $cons=array("{mycp_del_c_dhcp}","{mycp_del_c_dns}","{mycp_del_c_logs}","{mycp_del_c_undo}");
    $li=array();
    foreach($cons as $c){ $li[]="<li>$c</li>"; }
    $check="";
    $dis="";
    if($mode=="all"){
        $check="<label class='mycp-understand'><input type='checkbox' id='mycp-del-ok' OnChange=\"document.getElementById('mycp-del-go').disabled=!this.checked;\"> ".mycp_t("{mycp_del_understand}",array(mycp_num($n)))."</label>";
        $dis=" disabled";
    }
    $after=$fn!==""?"if(typeof $fn==='function'){ $fn(); }":"";
    $html="<div id='mycp-root' class='mycp-del'>
<div class='mycp-delhead'><span class='mycp-delico'><i class='fa-solid fa-trash' aria-hidden='true'></i></span><div><div class='mycp-deltitle'>$title</div><div class='mycp-muted'>{mycp_del_intro}</div></div></div>
<ul class='mycp-delcons'>".implode("",$li)."</ul>$check
<div class='mycp-delbtns'><button type='button' class='mycp-btn' OnClick=\"Loadjs('$page?export=yes');\"><i class='fa-solid fa-file-csv' aria-hidden='true'></i> {mycp_export_first}</button>
<button type='button' class='mycp-btn mycp-btn-danger' id='mycp-del-go'$dis OnClick=\"MycpDeleteGo();\"><i class='fa-solid fa-trash' aria-hidden='true'></i> {mycp_delete}</button></div></div>";
    echo mycp_style().$tpl->_ENGINE_parse_body($html)."<script>
function MycpDeleteGo(){
  var b=document.getElementById('mycp-del-go'); if(b){b.disabled=true;}
  $.post('$page',{ $post },function(r){
    r=String(r||'').trim();
    if(r.length>0){ alert(r.replace(/<[^>]+>/g,'')); if(b){b.disabled=false;} return; }
    if(typeof dialogInstance7!=='undefined'){ dialogInstance7.close(); }
    $after
  });
}
</script>";
    return true;
}

function delete_sql_perform(){
    $tpl=new template_admin();
    if(!mycp_can_write()){ echo $tpl->_ENGINE_parse_body("{ERROR_NO_PRIVS}"); return false; }
    // Pentest 2026-11-04 : la requete est signee (HMAC de session) par BuildRequests() et refusee si forgee.
    $query = signed_html_decode(mycp_token_in((string)($_POST["delete-sql"] ?? "")));
    if(strpos($query,"DELETE FROM hostsnet WHERE ")!==0){
        echo $tpl->_ENGINE_parse_body("{mycp_bad_token}");
        return false;
    }
    $query=str_replace("APUPRES","~",$query);
    $q=new postgres_sql();
    $q->QUERY_SQL($query);
    if(!$q->ok){
        if(function_exists("writelogs")){writelogs("computers: $q->mysql_error",__FUNCTION__,__FILE__,__LINE__);}
        echo $tpl->_ENGINE_parse_body("{error}");
        return false;
    }
    return admin_tracks("Delete computers matching a search: $query");
}

function delete_all_perform(){
    $tpl=new template_admin();
    if(!mycp_can_write()){ echo $tpl->_ENGINE_parse_body("{ERROR_NO_PRIVS}"); return false; }
    $q=new postgres_sql();
    $q->QUERY_SQL("TRUNCATE TABLE hostsnet");
    if(!$q->ok){
        if(function_exists("writelogs")){writelogs("computers: $q->mysql_error",__FUNCTION__,__FILE__,__LINE__);}
        echo $tpl->_ENGINE_parse_body("{error}");
        return false;
    }
    return admin_tracks("Delete all computers of the inventory");
}

// --- Import / export ------------------------------------------------------------------

function import_computers_js(){
    $RefreshTable=jsafter_token($_GET["RefreshTable"]);
    $page=CurrentPageName();
    $tpl=new template_admin();
    if(!mycp_can_write()){ echo $tpl->js_error("{ERROR_NO_PRIVS}"); return false; }
    $tpl->js_dialog6("{importing_form_csv_file}", "$page?import-popup=yes&RefreshTable=$RefreshTable",720);
}

function import_computers_popup(){
    $tpl = new template_admin();
    $page=CurrentPageName();
    if(!mycp_can_write()){ echo $tpl->div_error("{ERROR_NO_PRIVS}"); return false; }
    $RefreshTable=jsafter_token($_GET["RefreshTable"]);
    $cols=array("{mycp_col_mac}","{mycp_col_ip}","{mycp_col_host}","{mycp_col_fixed}","{mycp_col_alias}","{mycp_col_alias1}","{mycp_col_alias2}","{mycp_col_alias3}","{mycp_col_alias4}","{mycp_col_os}");
    $ex=array("3c:52:82:1a:9f:04","192.168.1.42","pc-accounting","1","Accounting","","","","","Windows 11");
    $th=array(); $td=array();
    foreach($cols as $i=>$c){ $th[]="<th>".($i+1).". $c</th>"; $td[]="<td>".($ex[$i]===""?"&nbsp;":$ex[$i])."</td>"; }
    $html=array();
    $html[]="<div id='mycp-root' class='mycp-imp'>";
    $html[]="<div class='mycp-imphead'><span class='mycp-bico'><i class='fa-solid fa-file-import' aria-hidden='true'></i></span><div><div class='mycp-deltitle'>{mycp_imp_title}</div><div class='mycp-muted'>{mycp_imp_text}</div></div></div>";
    $html[]="<ul class='mycp-imprules'><li>{mycp_imp_rule_sep}</li><li>{mycp_imp_rule_update}</li><li>{mycp_imp_rule_export}</li></ul>";
    $html[]="<div class='mycp-impscroll'><table class='mycp-imptbl'><thead><tr>".implode("",$th)."</tr></thead><tbody><tr>".implode("",$td)."</tr></tbody></table></div>";
    $html[]="<div id='import-computers-div'></div>";
    $html[]="<div class='mycp-impbtn'>".$tpl->button_upload("{mycp_choose_csv}",$page,null,"&RefreshTable=$RefreshTable")."</div>";
    $html[]="</div>";
    echo mycp_style().$tpl->_ENGINE_parse_body(implode("\n",$html));
}

function export_progress(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $js=$tpl->framework_buildjs("/computers/export/csv",
        "export-computers.progress","export-computers.log","export-computers-div",
    "dialogInstance6.close();document.location.href='$page?download-export=yes';");
    echo "<div id='export-computers-div'></div><script>$js</script>";
}

function file_uploaded(){
    if(!fw_progress_page_priv("AsSystemAdministrator")){return false;}   // task link signed for this session: built only for whom may run it
    header("content-type: application/x-javascript");
    $file=$_GET["file-uploaded"];
    $RefreshTable=jsafter_token($_GET["RefreshTable"]);
    if($RefreshTable<>null){$RefreshTable="$RefreshTable();";}
    $tpl=new template_admin();
    $file=base64_encode($file);
    $jsrestart=$tpl->framework_buildjs("/computers/import/csv/$file",
        "export-computers.progress","export-computers.log","import-computers-div",
        "dialogInstance6.close();$RefreshTable");
    echo $jsrestart;
}

function export(){
    $page = CurrentPageName();
    $tpl = new template_admin();
    $tpl->js_dialog6("{export} {computers}", "$page?export-progress=yes",650);
    return true;
}

function export_download(){
    $path="/usr/share/artica-postfix/ressources/logs/computers.csv";
    header('Content-type: application/vnd.ms-excel');
    header('Content-Transfer-Encoding: binary');
    header("Content-Disposition: attachment; filename=\"computers.csv\"");
    header("Pragma: public");
    header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
    header("Expires: Sat, 26 Jul 1997 05:00:00 GMT");
    $fsize = filesize($path);
    header("Content-Length: ".$fsize);
    ob_clean();
    flush();
    readfile($path);
    @unlink($path);
}

// --- Repondeur de la recherche : tuiles + tableau + TINY --------------------------------

function mycp_model(string $search):array{
    $tpl=new template_admin();
    $m=array("search"=>$search,"term"=>"","rows"=>array(),"ok"=>true,"nopg"=>false,
        "stats"=>array("total"=>null,"fixed"=>null,"dns"=>null,"unknown"=>null,"d7"=>null,"d1"=>null),
        "matched"=>null,"limit"=>200,"delete_sql"=>"","dnsdist"=>intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableDNSDist"))==1);
    if(intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DisablePostGres"))==1){ $m["nopg"]=true; return $m; }
    // CLEAN_BAD_XSS() retire le "+" : les mots-cles +fixed/+dns/+unknown/+asc/+desc sont donc
    // sortis de la saisie AVANT le nettoyage, puis rendus a BuildRequests() (ils ne filtraient rien).
    $kw=array();
    foreach(array("+fixed","+dns","+unknown","+asc","+desc") as $k){
        if(stripos($search,$k)!==false){ $kw[]=$k; $search2=str_ireplace($k," ",$search); $search=$search2; }
    }
    $stringtofind="";
    if(strlen(trim($search))>1){ $stringtofind=url_decode_special_tool($tpl->CLEAN_BAD_XSS(trim($search))); }
    if(count($kw)>0){ $stringtofind=trim($stringtofind." ".implode(" ",$kw)); }
    $m["term"]=trim($stringtofind);
    unset($GLOBALS["DELETE_SQL"],$GLOBALS["COUNT_SQL"],$GLOBALS["MYCP_LIMIT"]);
    $sql=BuildRequests($stringtofind);
    $m["limit"]=intval($GLOBALS["MYCP_LIMIT"] ?? 200);
    $q=new postgres_sql();
    $st=$q->mysqli_fetch_array("SELECT count(*) AS total,
        count(*) FILTER (WHERE dhcpfixed=1) AS fixed,
        count(*) FILTER (WHERE dnsfixed=1) AS dns,
        count(*) FILTER (WHERE unknown=1) AS unknown,
        count(*) FILTER (WHERE updated > NOW() - INTERVAL '7 days') AS d7,
        count(*) FILTER (WHERE updated > NOW() - INTERVAL '1 day') AS d1 FROM hostsnet");
    if(is_array($st) && isset($st["total"])){
        foreach($m["stats"] as $k=>$v){ $m["stats"][$k]=intval($st[$k] ?? 0); }
    }
    $results=$q->QUERY_SQL($sql);
    if(!$q->ok){
        if(function_exists("writelogs")){writelogs("computers: $q->mysql_error",__FUNCTION__,__FILE__,__LINE__);}
        $m["ok"]=false;
        return $m;
    }
    while($ligne=pg_fetch_assoc($results)){ $m["rows"][]=$ligne; }
    if(isset($GLOBALS["COUNT_SQL"])){
        $c=$q->mysqli_fetch_array($GLOBALS["COUNT_SQL"]);
        if(is_array($c) && isset($c["n"])){ $m["matched"]=intval($c["n"]); }
    }else{
        $m["matched"]=$m["stats"]["total"];
    }
    if(isset($GLOBALS["DELETE_SQL"])){
        // Pentest 2026-11-04 : la requete de suppression part signee (signed_html_token).
        $m["delete_sql"]=signed_html_token($GLOBALS["DELETE_SQL"]);
    }
    return $m;
}

function table(){
	$tpl=new template_admin();
	$page=CurrentPageName();
    $search=(string)($_GET["search"] ?? "");
	// $_GET["function"] finit dans des OnClick: seul search_block le produit (noms alphanumeriques).
	$fnraw=preg_replace('/[^A-Za-z0-9_]/','',(string)($_GET["function"] ?? ""));
	$function=base64_encode($fnraw);
    $m=mycp_model($search);

    if($m["nopg"]){
        $installjs=$tpl->framework_buildjs("/postgresql/install","postgres.progress","postgres.log","progress-firehol-restart","$fnraw()");
        $btn=$tpl->button_autnonome("{install} {APP_POSTGRES}",$installjs,ico_cd,"AsSystemAdministrator",240,"btn-warning");
        $html="<div class='mycp-card mycp-warn'><div class='mycp-wtitle'><i class='fa-solid fa-database' aria-hidden='true'></i> {mycp_nopg_title}</div><div>{mycp_nopg_text}</div><div style='margin-top:12px'>$btn</div></div>";
        echo mycp_style()."<div id='mycp-root'>".$tpl->_ENGINE_parse_body($html)."</div>";
        return false;
    }
    if(!$m["ok"]){
		echo mycp_style()."<div id='mycp-root'>".$tpl->_ENGINE_parse_body("<div class='mycp-card mycp-warn'><div class='mycp-wtitle'><i class='fa-solid fa-triangle-exclamation' aria-hidden='true'></i> {mycp_read_error}</div></div>")."</div>";
		return false;
	}
    $write=mycp_can_write();
    $html=array();
    $html[]="<div id='mycp-tiles-src' class='mycp-tiles'>".mycp_tiles($m)."</div>";
    $html[]=mycp_table($m,$function,$fnraw,$write);

    $CallBackMyComps=base64_encode("$fnraw()");
    $topbuttons=array();
    if($write){
        $topbuttons[] = array("Loadjs('fw.add.computer.php?CallBackFunction=$CallBackMyComps')", ico_plus, "{new_computer}");
        $topbuttons[] = array("Loadjs('$page?import=yes&RefreshTable=$function')", ico_import, "{mycp_import}");
    }
    $topbuttons[] = array("Loadjs('$page?export=yes')", ico_export, "{export}");
    if($write && $m["delete_sql"]!=="" && $m["term"]!=="" && $m["matched"]>0){
        $topbuttons[] = array("Loadjs('$page?delete-js=search&function=$fnraw&q=".$m["delete_sql"]."&term=".urlencode($m["term"])."')", ico_trash, "{mycp_delete_results}");
    }
    if($write && $m["stats"]["total"]>0){
        $topbuttons[] = array("Loadjs('$page?delete-js=all&function=$fnraw')", ico_trash, "{delete_all}");
    }
    $TINY_ARRAY=array();
    $TINY_ARRAY["TITLE"]="{my_computers}";
    $TINY_ARRAY["ICO"]="fa-solid fa-computer";
    $TINY_ARRAY["EXPL"]="{explain_mycomputers}";
    $TINY_ARRAY["URL"]="computers";
    $TINY_ARRAY["BUTTONS"]=$tpl->table_buttons($topbuttons);
    $jstiny="Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";

    // Le bloc <script> est concatene APRES l'analyse (accolades JavaScript != jetons).
    $script="<script>
NoSpinner();
(function(){
  var src=document.getElementById('mycp-tiles-src'), host=document.getElementById('mycp-tiles');
  if(src && host){ host.innerHTML=''; host.className='mycp-tiles'; while(src.firstChild){ host.appendChild(src.firstChild); } src.parentNode.removeChild(src); }
})();
$jstiny
</script>";
    echo mycp_style().awc_table_style()."<div id='mycp-root'>".$tpl->_ENGINE_parse_body(implode("\n",$html))."</div>"
        .$script.(count($m["rows"])>0?awc_pager_js($GLOBALS["FOOTABLE_PSIZE"] ?? 50,"comp-rows","comp-pager"):"");
}

// --- Tuiles : chacune lance une recherche filtree ----------------------------------------

function mycp_tiles(array $m):string{
    $s=$m["stats"];
    $h=array();
    $h[]=mycp_tile("fa-solid fa-laptop","{mycp_t_devices}",$s["total"],"{mycp_t_devices_help}","",false);
    $h[]=mycp_tile("fa-solid fa-thumbtack","{mycp_t_fixed}",$s["fixed"],"{mycp_t_fixed_help}","+fixed",false);
    if($m["dnsdist"]){
        $h[]=mycp_tile("fa-solid fa-signature","{mycp_t_dns}",$s["dns"],"{mycp_t_dns_help}","+dns",false);
    }
    $h[]=mycp_tile("fa-solid fa-circle-question","{mycp_t_unknown}",$s["unknown"],"{mycp_t_unknown_help}","+unknown",$s["unknown"]!==null && $s["unknown"]>0);
    $sub=$s["d1"]===null?"&mdash;":mycp_t("{mycp_t_recent_help}",array(mycp_num($s["d1"])));
    $h[]=mycp_tile("fa-solid fa-clock-rotate-left","{mycp_t_recent}",$s["d7"],$sub,"order by time",false);
    return implode("",$h);
}

function mycp_tile(string $ico,string $label,$value,string $help,string $search,bool $warn):string{
    $cls=$warn?" mycp-tile-warn":"";
    $js="MycpQuick('".$search."',true);";
    return "<button type='button' class='mycp-tile$cls' OnClick=\"$js\"><span class='mycp-tlabel'>$label<i class='$ico' aria-hidden='true'></i></span>
<span class='mycp-tval'>".mycp_num($value)."</span><span class='mycp-thelp'>$help</span></button>";
}

// --- Tableau ---------------------------------------------------------------------------------

function mycp_table(array $m,string $function,string $fnraw,bool $write):string{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $h=array();
    if(count($m["rows"])==0){
        if($m["stats"]["total"]===0 && $m["term"]===""){
            $btns="";
            if($write){
                $CallBackMyComps=base64_encode("$fnraw()");
                $btns="<div class='mycp-ebtns'><button type='button' class='mycp-btn mycp-btn-primary' OnClick=\"Loadjs('fw.add.computer.php?CallBackFunction=$CallBackMyComps');\"><i class='fa-solid fa-plus' aria-hidden='true'></i> {new_computer}</button>
<button type='button' class='mycp-btn' OnClick=\"Loadjs('$page?import=yes&RefreshTable=$function');\"><i class='fa-solid fa-file-import' aria-hidden='true'></i> {mycp_import_csv}</button></div>";
            }
            return "<div class='mycp-card mycp-empty'><i class='fa-solid fa-laptop-medical' aria-hidden='true'></i><div class='mycp-etitle'>{mycp_empty_title}</div><div class='mycp-muted'>{mycp_empty_text}</div>$btns</div>";
        }
        $term=req_html($m["search"]);
        return "<div class='mycp-card mycp-empty'><i class='fa-solid fa-magnifying-glass' aria-hidden='true'></i><div class='mycp-etitle'>".mycp_t("{mycp_nomatch}",array("&laquo;&nbsp;$term&nbsp;&raquo;"))."</div>
<button type='button' class='mycp-btn' OnClick=\"MycpQuick('',true);\">{mycp_clear_search}</button></div>";
    }
    $h[]="<div class='awc-wrap'><div class='awc-card'><div class='awc-scroll'>";
    $h[]="<table class='awc-tbl mycp-tbl'>";
    $h[]="<thead><tr><th scope='col'>{mycp_th_device}</th><th scope='col'>{mycp_th_names}</th><th scope='col' class='awc-nowrap'>{addr}</th><th scope='col' class='awc-nowrap'>{mycp_th_hwid}</th><th scope='col'>{mycp_th_services}</th><th scope='col' class='awc-nowrap'>{mycp_th_updated}</th><th scope='col' class='mycp-thact'>{mycp_th_actions}</th></tr></thead>";
    $h[]="<tbody id='comp-rows'>";
    foreach($m["rows"] as $ligne){ $h[]=computer_row($ligne,$function,$m["dnsdist"],$write); }
    $h[]="</tbody></table></div>";
    $shown=count($m["rows"]);
    $more="";
    if($m["matched"]!==null && $m["matched"]>$shown && $m["limit"]<500){
        $more="<button type='button' class='mycp-link' OnClick=\"MycpQuick('limit 500',false);\">{mycp_show_500}</button>";
    }
    $total=$m["matched"]===null?mycp_num($shown):mycp_num($m["matched"]);
    $h[]="<div class='awc-foot'><span>".mycp_t("{mycp_showing}",array("<b>".mycp_num($shown)."</b>","<b>$total</b>"))." $more</span><span class='awc-pg' id='comp-pager'></span></div>";
    $h[]="</div></div>";
    return implode("\n",$h);
}

/**
 * Un poste du parc : nom + constructeur/OS, noms d'usage, IP, MAC, services, derniere
 * mise a jour, actions. Un clic sur la ligne ouvre la fiche (fw.edit.computer.php).
 */
function computer_row($ligne,$function,$EnableDNSDist,$write=true):string{
	$tpl=new template_admin();
	$mac=(string)($ligne["mac"] ?? "");
	$ipaddr=(string)($ligne["ipaddr"] ?? "");
	$fullhostname=trim((string)($ligne["fullhostname"] ?? ""));
	if(strlen($fullhostname)<2){$fullhostname=trim((string)($ligne["hostname"] ?? ""));}
	$unknown=(intval($ligne["unknown"] ?? 0)==1);
	$mac_enc=urlencode($mac);
	$js="Loadjs('fw.edit.computer.php?mac=$mac_enc&CallBackFunction=$function')";

	$vendor=trim((string)($ligne["vendor"] ?? ""));
	if($vendor===""){ $vendor=trim((string)$tpl->MacToVendor($mac)); }
	$os=trim((string)($ligne["computeros"] ?? ""));
	$meta=array();
	if($vendor!==""){ $meta[]=req_html($vendor); }
	if($os!==""){ $meta[]=req_html($os); }

	$name=$fullhostname!==""?req_html($fullhostname):"{mycp_unnamed}";
	$icon="<span class='mycp-dico'><i class='fa-solid fa-desktop' aria-hidden='true'></i></span>";
	$tag="";
	if($unknown){
		$icon="<span class='mycp-dico mycp-dico-warn'><i class='fa-solid fa-circle-question' aria-hidden='true'></i></span>";
		$tag="<div class='mycp-unk'>{mycp_not_identified}</div>";
	}
	$metaH=count($meta)>0?"<div class='mycp-meta'>".implode(" &middot; ",$meta)."</div>":"";

	// Noms d'usage : alias proxy + 4 alias d'hote.
	$aliases=array();
	foreach(array("proxyalias","hostalias1","hostalias2","hostalias3","hostalias4") as $k){
		$v=trim((string)($ligne[$k] ?? ""));
		if($v!==""){ $aliases[]=$v; }
	}
	$al="<span class='mycp-muted'>&mdash;</span>";
	if(count($aliases)>0){
		$chips=array();
		foreach(array_slice($aliases,0,2) as $a){ $chips[]="<span class='mycp-alias'>".req_html($a)."</span>"; }
		if(count($aliases)>2){
			$rest=req_html(implode(", ",array_slice($aliases,2)));
			$chips[]="<span class='mycp-alias mycp-alias-more' title='$rest'>+".(count($aliases)-2)."</span>";
		}
		$al="<div class='mycp-aliases'>".implode("",$chips)."</div>";
	}

	$pills=array();
	if(intval($ligne["dhcpfixed"] ?? 0)==1){ $pills[]="<span class='mycp-pill mycp-p-ok'><i class='fa-solid fa-thumbtack' aria-hidden='true'></i>{mycp_svc_fixed}</span>"; }
	if($EnableDNSDist && intval($ligne["dnsfixed"] ?? 0)==1){ $pills[]="<span class='mycp-pill mycp-p-ok'><i class='fa-solid fa-signature' aria-hidden='true'></i>{mycp_svc_dns}</span>"; }
	if(intval($ligne["hotspotwhite"] ?? 0)==1){ $pills[]="<span class='mycp-pill mycp-p-info'><i class='fa-solid fa-wifi' aria-hidden='true'></i>{mycp_svc_hotspot}</span>"; }
	if(intval($ligne["pxe_enabled"] ?? 0)==1){ $pills[]="<span class='mycp-pill mycp-p-neutral'><i class='fa-solid fa-network-wired' aria-hidden='true'></i>{mycp_svc_pxe}</span>"; }
	$svc=count($pills)>0?"<div class='mycp-pills'>".implode("",$pills)."</div>":"<span class='mycp-muted mycp-small'>{mycp_svc_none}</span>";

	// updated est ecrit par NOW() dans le fuseau de la session PostgreSQL (GMT sur l'appliance) :
	// l'epoque est calculee par la base, strtotime() le lirait dans le fuseau de PHP (1 a 2 h d'ecart).
	$ts=isset($ligne["updated_epoch"]) && $ligne["updated_epoch"]!=="" && $ligne["updated_epoch"]!==null ? intval($ligne["updated_epoch"]) : strtotime((string)($ligne["updated"] ?? ""));
	$upd="<span class='mycp-muted'>&mdash;</span>";
	if($ts!==false && $ts>0){
		$upd="<span title='".date("Y-m-d H:i:s",$ts)."'>".mycp_ago($ts)."</span>";
	}
	$ipH=($ipaddr!=="" && $ipaddr!=="0.0.0.0")?"<span class='mycp-ip'>".req_html($ipaddr)."</span>":"<span class='mycp-muted'>&mdash;</span>";

	$acts="<button type='button' class='mycp-ibtn' aria-label='{mycp_open}' title='{mycp_open}' OnClick=\"event.stopPropagation();$js\"><i class='fa-solid fa-pen' aria-hidden='true'></i></button>";
	if($write){
		$acts.="<button type='button' class='mycp-ibtn mycp-ibtn-del' aria-label='{mycp_delete}' title='{mycp_delete}' OnClick=\"event.stopPropagation();Loadjs('fw.edit.computer.php?remove=$mac_enc&CallBackFunction=$function');\"><i class='fa-solid fa-trash' aria-hidden='true'></i></button>";
	}
	$cls=$unknown?" class='mycp-row mycp-row-unk'":" class='mycp-row'";
	$h ="<tr$cls OnClick=\"$js\">";
	$h.="<td><div class='mycp-ident'>$icon<div><div class='mycp-name'>$name</div>$metaH$tag</div></div></td>";
	$h.="<td>$al</td>";
	$h.="<td class='awc-nowrap'>$ipH</td>";
	$h.="<td class='awc-nowrap mycp-mac'>".req_html($mac)."</td>";
	$h.="<td>$svc</td>";
	$h.="<td class='awc-nowrap'>$upd</td>";
	$h.="<td class='awc-nowrap'><div class='mycp-acts'>$acts</div></td>";
	$h.="</tr>";
	return $h;
}

// --- Outils ----------------------------------------------------------------------------------

// Jeton signe recu encore encode pour l'URL (relais en double) : le base64 ne contient jamais "%".
function mycp_token_in(string $token):string{
    if(strpos($token,"%")!==false){ $token=rawurldecode($token); }
    return $token;
}

function mycp_t(string $token,array $args=array()):string{
    $tpl=new template_admin();
    $s=(string)$tpl->_ENGINE_parse_body($token);
    foreach($args as $a){
        $p=strpos($s,"%s");
        if($p===false){ break; }
        $s=substr($s,0,$p).$a.substr($s,$p+2);
    }
    return $s;
}

function mycp_num($v):string{
    if($v===null){ return "&mdash;"; }
    return number_format($v,0,".",",");
}

function mycp_ago(int $ts):string{
    $d=time()-$ts;
    if($d<60){ return mycp_t("{mycp_ago_now}"); }
    if($d<3600){ return mycp_t("{mycp_ago_min}",array(intval($d/60))); }
    if($d<86400){ return mycp_t("{mycp_ago_hour}",array(intval($d/3600))); }
    if($d<86400*2){ return mycp_t("{mycp_ago_day1}"); }
    if($d<86400*60){ return mycp_t("{mycp_ago_day}",array(intval($d/86400))); }
    return date("Y-m-d",$ts);
}

function BuildRequests($stringtofind){
	$LIMIT=200;
    $AND1="";
	$stringtofind=trim(strtolower($stringtofind));
	$stringtofind=preg_replace('#\s+#'," ",$stringtofind);
	$ipClass=new IP();
	$FLAGS=array();

	if(preg_match("#limit\s+([0-9]+)#", $stringtofind,$re)){
		$stringtofind=trim(str_replace("limit {$re[1]}", "", $stringtofind));
		$LIMIT=intval($re[1]);
	}
	// Au-dela de 1000 lignes le pager du navigateur ne suit plus (cf. awc-tables).
	if($LIMIT<1){$LIMIT=200;}
	if($LIMIT>1000){$LIMIT=1000;}
	$GLOBALS["MYCP_LIMIT"]=$LIMIT;

	foreach(array("+fixed"=>"dhcpfixed=1","+dns"=>"dnsfixed=1","+unknown"=>"unknown=1") as $kw=>$cond){
		if(strpos("  $stringtofind", $kw)){
			$FLAGS[]=$cond;
			$stringtofind=trim(str_replace($kw, "", $stringtofind));
		}
	}

	$ORDERL="";
	$ORDERBY="ORDER BY fullhostname";
	$orders=array("order by time"=>"ORDER BY updated","order by ip"=>"ORDER BY ipaddr","order by name"=>"ORDER BY fullhostname","order by alias"=>"ORDER BY proxyalias");
	foreach($orders as $kw=>$ob){
		if(strpos("  $stringtofind", $kw)){
			$ORDERBY=$ob;
			$stringtofind=trim(str_replace($kw, "", $stringtofind));
		}
	}
	if(strpos("  $stringtofind", "+asc")){
		$ORDERL="ASC";
		$stringtofind=trim(str_replace("+asc", "", $stringtofind));
	}
	if(strpos("  $stringtofind", "+desc")){
		$ORDERL="DESC";
		$stringtofind=trim(str_replace("+desc", "", $stringtofind));
	}
	// Sans +asc/+desc : les noms, IP et alias en ordre croissant, les dates du plus recent au plus ancien.
	if($ORDERL===""){ $ORDERL=($ORDERBY=="ORDER BY updated")?"DESC":"ASC"; }
	$FLAGSQL=count($FLAGS)>0?implode(" AND ",$FLAGS):"";

    if (strlen($stringtofind)<2){
        if($FLAGSQL===""){
            return "SELECT *, CAST(EXTRACT(EPOCH FROM updated AT TIME ZONE current_setting('TimeZone')) AS bigint) AS updated_epoch FROM hostsnet $ORDERBY $ORDERL LIMIT $LIMIT";
        }
        $GLOBALS["COUNT_SQL"]="SELECT count(*) AS n FROM hostsnet WHERE $FLAGSQL";
        $GLOBALS["DELETE_SQL"]="DELETE FROM hostsnet WHERE $FLAGSQL";
        return "SELECT *, CAST(EXTRACT(EPOCH FROM updated AT TIME ZONE current_setting('TimeZone')) AS bigint) AS updated_epoch FROM hostsnet WHERE $FLAGSQL $ORDERBY $ORDERL LIMIT $LIMIT";
    }

	if(preg_match("#(.+?)\s+\(#", $stringtofind,$re)){$stringtofind=trim($re[1]);}

	$stringtofind=str_replace("**", "*", $stringtofind);
	$stringtofind2=str_replace("*", "", $stringtofind);
	$stringtofind=str_replace("*", ".*?", $stringtofind);
	// Pentest 2026-11-04 : la recherche etait concatenee dans la requete.
	$stringtofind=pg_escape_string2($stringtofind);
	$stringtofind2_sql=pg_escape_string2($stringtofind2);

	$INET=false;
	$MAC=false;
	$OR=array();

	if(!preg_match("#[a-z]+#",$stringtofind2)) {
        if (preg_match("#^[0-9]+\.[0-9]+$#", $stringtofind2)) {
            $OR[] = "(ipaddr << inet '$stringtofind2.0.0/16')";
            $INET = true;
        }
        if (preg_match("#^[0-9]+\.[0-9]+.[0-9]+$#", $stringtofind2)) {
            $OR[] = "(ipaddr << inet '$stringtofind2.0/24')";
            $INET = true;
        }
        if($ipClass->IsValid($stringtofind2)){
            $OR[]="( ipaddr = inet '$stringtofind2_sql')";
            $INET=true;
        }
        if($ipClass->IsACDIR($stringtofind2)){
            $OR[]="(ipaddr << inet '$stringtofind2_sql')";
            $INET=true;
        }
        if(!$INET){
            if(preg_match("#^[0-9\.]+$#", $stringtofind2)){
                if($stringtofind2<>null){
                    $tt=explode(".",$stringtofind2);
                    foreach ($tt as $index=>$value){
                        if(strlen( (string) $value)>3){$tt[$index]="0";}
                    }
                    if(!isset($tt[1])){$tt[1]="0";}
                    if(!isset($tt[2])){$tt[2]="0";}
                    if(!isset($tt[3])){$tt[3]="0";}
                    $tipaddr=@implode(".", $tt);
                    if($tipaddr<>"0.0.0.0"){
                        $OR[]="( inet '$tipaddr' >= ipaddr )";
                    }
                }
            }
        }
    }

	if($ipClass->IsvalidMAC($stringtofind2)){
		$OR[]="( mac='$stringtofind2_sql' )";
		$MAC=true;
	}
	if(!$MAC){
		if($stringtofind2<>null){
			if(preg_match("#^[0-9a-z]+(:|-)[0-9a-z]+#", $stringtofind2)){
                    $searchMAC="%$stringtofind2%";
                    $searchMAC=pg_escape_string2(str_replace("%%", "%", $searchMAC));
					$OR[]="( mac::text LIKE '$searchMAC' )";
					$MAC=true;
			}
		}
	}
	if($INET==false){
		if($MAC==false){
			$OR[]="( fullhostname ~ '$stringtofind' )";
            $OR[]="( proxyalias ~ '$stringtofind' )";
			$OR[]="( hostname ~ '$stringtofind' )";
			$OR[]="( hostalias1 ~ '$stringtofind' )";
			$OR[]="( hostalias2 ~ '$stringtofind' )";
			$OR[]="( hostalias3 ~ '$stringtofind' )";
			$OR[]="( hostalias4 ~ '$stringtofind' )";
		}
	}
	if($FLAGSQL!==""){$AND1=" AND $FLAGSQL ";}
	$WHERE="( ".@implode(" OR ", $OR).")$AND1";
    $GLOBALS["COUNT_SQL"]="SELECT count(*) AS n FROM hostsnet WHERE $WHERE";
    $GLOBALS["DELETE_SQL"]=str_replace("~","APUPRES","DELETE FROM hostsnet WHERE $WHERE");
    return "SELECT *, CAST(EXTRACT(EPOCH FROM updated AT TIME ZONE current_setting('TimeZone')) AS bigint) AS updated_epoch FROM hostsnet WHERE $WHERE $ORDERBY $ORDERL LIMIT $LIMIT";
}

// --- Style -------------------------------------------------------------------------------------

function mycp_style():string{
    if(isset($GLOBALS["MYCP_STYLE"])){ return ""; }
    $GLOBALS["MYCP_STYLE"]=true;
    $soft="rgba(16,185,129,.12)"; $acc="#006a62";
    if(function_exists("brand_ui_accents") && function_exists("brand_palette")){
        $a=brand_ui_accents(brand_palette());
        if(!empty($a["accent_soft"])){ $soft=$a["accent_soft"]; }
        if(!empty($a["accent_dark"])){ $acc=$a["accent_dark"]; }
    }
    return "<style>
#mycp-root{display:flex;flex-direction:column;gap:16px;margin-top:10px;color:#111d23}
#mycp-root *{box-sizing:border-box}
#mycp-root .mycp-mono{font-family:monospace}
#mycp-root .mycp-muted{color:#6d7a77}
#mycp-root .mycp-small{font-size:13px}
#mycp-root .mycp-card{background:#fff;border:1px solid #e5e7eb;border-radius:12px;box-shadow:0 1px 2px rgba(0,0,0,.05);padding:18px 20px}
#mycp-root .mycp-banner{position:relative;display:flex;gap:16px;align-items:flex-start;background:#fff;border:1px solid #e5e7eb;border-left:4px solid $acc;border-radius:12px;padding:18px 48px 18px 20px;box-shadow:0 1px 2px rgba(0,0,0,.05)}
#mycp-root .mycp-bico{flex:0 0 48px;width:48px;height:48px;border-radius:50%;background:$soft;color:$acc;display:flex;align-items:center;justify-content:center;font-size:20px}
#mycp-root .mycp-btitle{font-size:19px;font-weight:600;margin-bottom:4px}
#mycp-root .mycp-bdesc{font-size:14px;color:#3d4947;line-height:1.5;max-width:960px}
#mycp-root .mycp-chips{display:flex;flex-wrap:wrap;gap:8px;margin-top:10px}
#mycp-root .mycp-chip{display:inline-flex;align-items:center;gap:6px;font-size:13px;padding:4px 10px;border-radius:999px;background:#e3f0f8;color:#3d4947;border:1px solid #d5e3ec}
#mycp-root .mycp-bclose{position:absolute;top:12px;right:12px;width:30px;height:30px;border:0;border-radius:8px;background:transparent;color:#6d7a77;cursor:pointer}
#mycp-root .mycp-bclose:hover{background:#f1f5f4}
#mycp-root .mycp-tiles{display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:16px}
#mycp-root .mycp-tiles:empty{display:none}
#mycp-root .mycp-tile{display:flex;flex-direction:column;align-items:stretch;text-align:left;gap:4px;background:#fff;border:1px solid #e5e7eb;border-radius:12px;padding:14px 16px;box-shadow:0 1px 2px rgba(0,0,0,.05);cursor:pointer;min-width:0}
#mycp-root .mycp-tile:hover{border-color:#bcc9c6}
#mycp-root .mycp-tlabel{display:flex;justify-content:space-between;align-items:center;gap:8px;font-size:13px;font-weight:600;letter-spacing:.04em;text-transform:uppercase;color:#3d4947}
#mycp-root .mycp-tlabel i{color:#6d7a77;font-size:15px}
#mycp-root .mycp-tval{font-size:24px;font-weight:600;font-variant-numeric:tabular-nums}
#mycp-root .mycp-thelp{font-size:13px;color:#6d7a77}
#mycp-root .mycp-tile-warn{background:#fff8ef;border-color:#ffddb9}
#mycp-root .mycp-tile-warn .mycp-tlabel,#mycp-root .mycp-tile-warn .mycp-tval,#mycp-root .mycp-tile-warn .mycp-thelp,#mycp-root .mycp-tile-warn .mycp-tlabel i{color:#865300}
#mycp-root .mycp-search .sb-stitch{margin:0}
#mycp-root .mycp-search .sb-stitch .ibox-content{padding:0;background:transparent;border:0}
#mycp-root .mycp-search .sb-stitch .input-group{margin-left:0;width:100%;max-width:100%}
#mycp-root .mycp-quick{display:flex;flex-wrap:wrap;align-items:center;gap:8px;margin-top:10px}
#mycp-root .mycp-qlbl{font-size:13px;color:#6d7a77}
#mycp-root .mycp-qchip{display:inline-flex;align-items:center;gap:8px;font-size:13px;padding:4px 10px;border-radius:8px;border:1px solid #d5dedc;background:#fff;color:$acc;cursor:pointer}
#mycp-root .mycp-qhelp{color:#6d7a77}
#mycp-root .mycp-tipsbtn{margin-left:auto;border:0;background:transparent;color:#3d4947;font-size:13px;cursor:pointer}
#mycp-root .mycp-tipsbtn[aria-expanded=true] .fa-chevron-down{transform:rotate(180deg)}
#mycp-root .mycp-tips{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:16px;margin-top:10px;padding:14px 16px;background:#f6f7f9;border-radius:10px;font-size:13px;color:#3d4947}
#mycp-root .mycp-tips[hidden]{display:none}
#mycp-root .mycp-tips b{display:block;color:#111d23;margin-bottom:2px}
#mycp-root .mycp-tips .mycp-mono{color:$acc;margin-top:4px}
#mycp-root [id^=results-]{display:flex;flex-direction:column;gap:16px}
#mycp-root .mycp-tbl tbody tr.mycp-row{cursor:pointer}
#mycp-root .mycp-tbl tbody tr.mycp-row:hover td{background:#f6f7f9}
#mycp-root .mycp-tbl tr.mycp-row-unk td{background:#fffcf6}
#mycp-root .mycp-ident{display:flex;align-items:center;gap:12px;min-width:0}
#mycp-root .mycp-dico{flex:0 0 34px;width:34px;height:34px;border-radius:10px;display:flex;align-items:center;justify-content:center;background:#f1f5f4;color:#3d4947}
#mycp-root .mycp-dico-warn{background:#ffddb9;color:#865300}
#mycp-root .mycp-name{font-size:15px;font-weight:600;overflow-wrap:break-word;word-break:normal}
#mycp-root .mycp-tbl td:first-child{min-width:260px}
#mycp-root .mycp-meta{font-size:13px;color:#6d7a77}
#mycp-root .mycp-unk{font-size:13px;color:#865300;font-weight:600}
#mycp-root .mycp-aliases{display:flex;flex-wrap:wrap;gap:6px}
#mycp-root .mycp-alias{display:inline-block;font-size:13px;padding:2px 8px;border-radius:6px;background:#f1f5f4;color:#3d4947;max-width:180px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
#mycp-root .mycp-alias-more{background:#e3f0f8;cursor:help}
#mycp-root .mycp-ip{font-family:monospace;font-size:13px;padding:2px 8px;border:1px solid #e0e6e4;border-radius:6px;background:#fff}
#mycp-root .mycp-mac{font-family:monospace;font-size:13px;color:#3d4947}
#mycp-root .mycp-pills{display:flex;flex-wrap:wrap;gap:6px}
#mycp-root .mycp-pill{display:inline-flex;align-items:center;gap:5px;font-size:13px;font-weight:600;padding:2px 9px;border-radius:999px;white-space:nowrap}
#mycp-root .mycp-p-ok{background:$soft;color:#0b6b4f}
#mycp-root .mycp-p-info{background:#e3f0f8;color:#1d4ed8}
#mycp-root .mycp-p-neutral{background:#f1f5f4;color:#3d4947}
#mycp-root .mycp-thact{text-align:right}
#mycp-root .mycp-acts{display:flex;justify-content:flex-end;gap:4px}
#mycp-root .mycp-ibtn{width:32px;height:32px;border:0;border-radius:8px;background:transparent;color:#3d4947;cursor:pointer}
#mycp-root .mycp-ibtn:hover{background:#e3f0f8}
#mycp-root .mycp-ibtn-del:hover{background:#ffdad6;color:#ba1a1a}
#mycp-root .mycp-ibtn:focus-visible,#mycp-root .mycp-tile:focus-visible,#mycp-root .mycp-qchip:focus-visible,#mycp-root .mycp-btn:focus-visible,#mycp-root .mycp-bclose:focus-visible,#mycp-root .mycp-link:focus-visible{outline:2px solid $acc;outline-offset:2px}
#mycp-root .mycp-link{border:0;background:transparent;color:$acc;font-weight:600;font-size:13px;cursor:pointer;margin-left:8px}
#mycp-root .mycp-btn{display:inline-flex;align-items:center;gap:6px;font-size:14px;font-weight:600;padding:8px 14px;border-radius:8px;border:1px solid #bcc9c6;background:#fff;color:$acc;cursor:pointer}
#mycp-root .mycp-btn-primary{background:$acc;border-color:$acc;color:#fff}
#mycp-root .mycp-btn-danger{background:#ba1a1a;border-color:#ba1a1a;color:#fff}
#mycp-root .mycp-btn[disabled]{opacity:.5;cursor:not-allowed}
#mycp-root .mycp-empty{display:flex;flex-direction:column;align-items:center;gap:10px;padding:44px 20px;text-align:center;font-size:14px}
#mycp-root .mycp-empty > i{font-size:30px;color:$acc}
#mycp-root .mycp-etitle{font-size:17px;font-weight:600}
#mycp-root .mycp-ebtns{display:flex;gap:10px;margin-top:6px}
#mycp-root .mycp-warn{background:#fff8ef;border-color:#ffddb9;color:#3d4947}
#mycp-root .mycp-wtitle{font-size:16px;font-weight:600;color:#865300;margin-bottom:4px}
#mycp-root.mycp-del,#mycp-root.mycp-imp{gap:14px;margin:0}
#mycp-root .mycp-delhead,#mycp-root .mycp-imphead{display:flex;gap:14px;align-items:flex-start}
#mycp-root .mycp-delico{flex:0 0 44px;width:44px;height:44px;border-radius:50%;background:#ffdad6;color:#ba1a1a;display:flex;align-items:center;justify-content:center;font-size:18px}
#mycp-root .mycp-deltitle{font-size:17px;font-weight:600}
#mycp-root .mycp-delcons,#mycp-root .mycp-imprules{margin:0;padding-left:20px;font-size:14px;color:#3d4947;line-height:1.6}
#mycp-root .mycp-understand{display:flex;gap:8px;align-items:flex-start;font-size:14px;font-weight:400;padding:10px 12px;background:#fff8ef;border:1px solid #ffddb9;border-radius:8px}
#mycp-root .mycp-delbtns{display:flex;justify-content:flex-end;gap:10px}
#mycp-root .mycp-impscroll{overflow-x:auto;border:1px solid #e5e7eb;border-radius:8px}
#mycp-root .mycp-imptbl{border-collapse:collapse;font-size:13px;width:100%}
#mycp-root .mycp-imptbl th{background:#f6f7f9;color:#3d4947;font-weight:600;text-align:left;padding:6px 10px;white-space:nowrap}
#mycp-root .mycp-imptbl td{font-family:monospace;padding:6px 10px;white-space:nowrap;border-top:1px solid #eef2f1}
#mycp-root .mycp-impbtn{text-align:center}
@media (max-width:1200px){#mycp-root .mycp-tips{grid-template-columns:repeat(2,minmax(0,1fr))}}
</style>";
}
