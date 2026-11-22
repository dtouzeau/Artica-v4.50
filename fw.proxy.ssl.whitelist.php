<?php
// awc_*() (awc_table_style/awc_pager_js/awc_filter_js): helpers partages des
// tableaux d'entites, definis dans class.statistics_appliance.inc.
// L'include DOIT rester INCONDITIONNEL. Place dans le bloc
// if(!class_exists("sockets")){...}, il n'etait jamais evalue -- class.template-admin.inc
// charge deja class.sockets.inc -- d'ou un fatal "Call to undefined function
// awc_table_style()" des que la page rendait son tableau.
include_once(dirname(__FILE__)."/ressources/class.statistics_appliance.inc");
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.acls.backend.inc");
// Verrou Artica Meta (politique sslproxy) : entrees Meta en lecture seule
include_once(dirname(__FILE__)."/ressources/class.sslproxy.meta.inc");
$users=new usersMenus();if(!$users->AsProxyMonitor){exit();}
$GLOBALS["GroupType"]["src"]="{src_addr}";
$GLOBALS["GroupType"]["dst"]="{dst_addr}";
$GLOBALS["GroupType"]["dstdomain"]="{dstdomain}";
$GLOBALS["GroupType"]["server_cert_fingerprint"]="{server_cert_fingerprint}";

if(isset($_GET["table"])){rules_list();exit;}
if(isset($_GET["popup-js"])){popup_js();exit;}
if(isset($_GET["popup"])){popup();exit;}
if(isset($_GET["GroupType"])){ExplainThis();exit;}
if(isset($_GET["switch-js"])){switch_perform();exit;}
if(isset($_GET["delete-js"])){delete_js();exit;}
if(isset($_POST["delete"])){delete();exit;}
if(isset($_POST["ID"])){save();exit;}
if(isset($_GET["config-popup-js"])){config_popup_js();exit;}
if(isset($_GET["config-popup"])){config_popup();exit;}

page();

function delete_js(){
    $tpl=new template_admin();
    $ID=intval($_GET["delete-js"]);
    if(sslproxy_meta_row("ssl_whitelist",$ID)){sslproxy_meta_refuse_js();return;}
    // Pentest 2026-11-04 : md5 etait recopie sans encodage dans la page (XSS).
    $md5=preg_replace('/[^A-Za-z0-9_-]/','',(string)($_GET["md5"] ?? ""));
    $ligne=acls_api_get("/proxy/ssl/whitelist/$ID");
    $pattern=is_array($ligne) ? ($ligne["pattern"] ?? $ID) : $ID;
    $tpl->js_confirm_delete($pattern,"delete",$ID,"$('#{$md5}').remove();");
}

function delete(){
    $ID=intval($_POST["delete"]);
    if(sslproxy_meta_row("ssl_whitelist",$ID)){sslproxy_meta_refuse_text();return;}
    $ligne=acls_api_get("/proxy/ssl/whitelist/$ID");
    $pattern=is_array($ligne) ? ($ligne["pattern"] ?? $ID) : $ID;

    list($ok,$err)=acls_api_send("DELETE","/proxy/ssl/whitelist/$ID");
    if(!$ok){echo $err;return;}
    admin_tracks("Removed $pattern from ssl whitelist");

}
function Save(){
    $tpl=new template_admin();
    $tpl->CLEAN_POST();
    include_once('ressources/class.tcpip.inc');
    $ID=intval($_POST["ID"]);
    if(sslproxy_meta_row("ssl_whitelist",$ID)){sslproxy_meta_refuse_post();return;}
    $pattern=trim($_POST["pattern"]);
    $description=$_POST["description"];
    $ztype=$_POST["ztype"];
    $enabled=intval($_POST["enabled"]);
    $f=array();
    $admtrck=array();

    $patternz=array();
    $zDate=date("Y-m-d H:i:s");

    if($ID==0){
        if(strpos(" $pattern", "\n")>0){
            $patternz=explode("\n",$pattern);
        }else{
            $patternz[]=$pattern;
        }

        $ip=new IP();
        $lib=new lib_memcached();

        foreach ($patternz as $item){

            $item=trim(strtolower($item));


            if($ztype=="dstdomain"){
                if(preg_match("#(http|ftp).*?:\/\/#",$item)){
                    $url_extract=parse_url($item);
                    $item=$url_extract["host"];
                }

            }



            if($item==null){continue;}
            $admtrck[]=$item;

            if( ($ztype=="src") OR ($ztype=="dst")){
                if(!$ip->isIPAddressOrRange($item)){continue;}
            }


            $line=str_replace("^","",$item);
            if(substr($line,0,1)=="."){$line=substr($line, 1,strlen($line));}
            $lib->saveKey("isWhite:$line",true,300);

            list($ok,$err)=acls_api_send("POST","/proxy/ssl/whitelist",["pattern"=>$item,"description"=>$description,"ztype"=>$ztype]);
            if(!$ok){echo (new template_admin())->post_error("".$tpl->javascript_parse_text($err));return;}


        }
        admin_tracks("Added in ssl whitelist ".@implode(",",$admtrck));
        return;


    }


    $pattern=trim(strtolower($pattern));



    if($ztype=="dstdomain"){
        if(preg_match("#(http|ftp).*?:\/\/#",$pattern)){
            $url_extract=parse_url($pattern);
            $pattern=$url_extract["host"];
        }

    }


    $ip=new IP();
    if( ($ztype=="src") OR ($ztype=="dst")){
        if(!$ip->isIPAddressOrRange($pattern)){return;}
    }




    list($ok,$err)=acls_api_send("PUT","/proxy/ssl/whitelist/$ID",["pattern"=>$pattern,"description"=>$description]);
    if(!$ok){echo (new template_admin())->post_error("".$tpl->javascript_parse_text($err));return;}

    admin_tracks("Edited ssl whitelist $pattern");



}
function config_popup(){

    $t=time();
    $data[]="# These items allow the proxy to :";
    $data[]="# - Bypass SSL certificates to be fatched by the proxy.";
    $data[]="#";
    $data[]="#";
    $data[]="# SSL splice based on domains";
    $data[]="# # # # # # # # # # # # # # # # # # # # # # # # # # # # # #";
    $data[]=@file_get_contents("/etc/squid3/ssl_splice_whitelist.dstdomain.conf");
    $data[]="";
    $data[]="# SSL splice based destination networks";
    $data[]="# # # # # # # # # # # # # # # # # # # # # # # # # # # # # #";
    $data[]=@file_get_contents("/etc/squid3/ssl_splice_whitelist.dst.conf");
    $data[]="";
    $data[]="# SSL splice based source networks";
    $data[]="# # # # # # # # # # # # # # # # # # # # # # # # # # # # # #";
    $data[]=@file_get_contents("/etc/squid3/ssl_splice_whitelist.src.conf");
    $data[]="";


    echo "<textarea style='margin-top:5px;font-family:\"Courier New\",serif;
	font-weight:bold;width:98%;height:746px;border:5px solid #8E8E8E;
	overflow:auto;font-size:18px !important' id='text-$t'>".@implode("\n", $data)."</textarea>";
}

function page():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();


    if(isset($_GET["main-page"])){
        $html=$tpl->page_header("{ssl_protocol}&nbsp;&raquo;&nbsp;{ssl_whitelist}"
            ,"fas fa-file-certificate","{global_ssl_whitelists_proxy_explain}","$page?table=yes","ssl-whitelists",
            "progress-acls-restart",false,"table-loader");

        $tpl=new template_admin("{global_whitelists}",$html);
        echo $tpl->build_firewall();
        return true;
    }

    $html="<div class='row'><div id='progress-acls-restart'></div>
                <div class='ibox-content'>
                    <div id='table-loader'></div>
                </div>
    </div><script>
        LoadAjax('table-loader','$page?table=yes');
    </script>";


    echo $tpl->_ENGINE_parse_body($html);
    return true;

}
function switch_perform(){
    $tpl=new template_admin();
    $ID=intval($_GET["switch-js"]);
    if(sslproxy_meta_row("ssl_whitelist",$ID)){echo "alert(".json_encode(sslproxy_meta_text()).");";return;}
    $ligne=acls_api_get("/proxy/ssl/whitelist/$ID");
    if(!is_array($ligne)){echo "alert('".addslashes("Error fetching record $ID")."');";return;}
    $newEnabled=($ligne["enabled"]==1)?0:1;
    list($ok,$err)=acls_api_send("PUT","/proxy/ssl/whitelist/$ID",["enabled"=>$newEnabled]);
    if(!$ok){echo "alert('".$tpl->javascript_parse_text($err)."');";}
}

function config_popup_js(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $tpl->js_dialog("{configuration}", "$page?config-popup=yes");

}

function popup_js(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $ID=intval($_GET["ID"]);
    $title=$tpl->javascript_parse_text("{new_item}");
    if($ID>0){
        $ligne=acls_api_get("/proxy/ssl/whitelist/$ID");
        if(is_array($ligne)){
            $ztype=$GLOBALS["GroupType"][$ligne["zType"]] ?? $ligne["zType"];
            $pattern=$ligne["pattern"];
            $title="$ztype: $pattern";
        }
    }

    $tpl->js_dialog($title, "$page?popup=yes&ID=$ID");
}
function popup(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $ID=intval($_GET["ID"]);
    $t=time();
    $buttonname="{add}";
    $title=$tpl->javascript_parse_text("{new_item}");
    $ligne["enabled"]=1;
    $close="";
    $idtemp="";
    $js="";

    if($ID>0){
        $buttonname="{apply}";
        $ligne=acls_api_get("/proxy/ssl/whitelist/$ID");
        if(is_array($ligne)){
            $ztype=$GLOBALS["GroupType"][$ligne["zType"]] ?? $ligne["zType"];
            $pattern=$ligne["pattern"];
            $title="$ztype: $pattern";
        }
    }


    if(sslproxy_meta_row("ssl_whitelist",$ID)){sslproxy_meta_lock_form($tpl);}


    $form[]=$tpl->field_hidden("ID", $ID);
    $form[]=$tpl->field_checkbox("enabled","{enabled}",$ligne["enabled"]);
    if($ID==0){
        $close="BootstrapDialog1.close()";
        $form[]=$tpl->field_array_hash($GLOBALS["GroupType"], "ztype", "{type}",null, true,null,"ChangeExplain$t");
        $idtemp=$tpl->id_temp;
    }else{
        $form[]=$tpl->field_hidden("ztype", $ligne["zType"] ?? "");
        // En modification, l'aide du type est deja passee a form_outside (ExplainThis plus bas).
    }
    if($ID>0){
        $form[]=$tpl->field_text("pattern", "{pattern}", $ligne["pattern"],true);
    }else{
        $form[]=$tpl->field_textareacode("pattern", "{pattern}", null);
    }
    $form[]=$tpl->field_text("description", "{description}", $ligne["description"] ?? "");

    $jsafter="LoadAjax('table-loader','$page?table=yes');";

    // Aide du type choisi (ajout): ChangeExplain la charge dans ce bloc. Elle visait
    // un #form-explain que form_outside n'emet pas: l'aide ne s'affichait jamais.
    if($ID==0){
        $html[]="<div id='wl-explain-$t'></div>";
    }
    $html[]=$tpl->form_outside($title,@implode("\n",$form), ExplainThis($ligne["zType"] ?? null),$buttonname,"$jsafter;$close");



    $html[]="<script>";
    $html[]="
function ChangeExplain$t(){

	if(!document.getElementById('$idtemp')){alert('$idtemp!!');return;}
	var GroupType=document.getElementById('$idtemp').value;
	if(GroupType.length==0){return;}
	LoadAjaxSilent('wl-explain-$t','$page?GroupType='+encodeURIComponent(GroupType));

}";
    $html[]="$js";
    $html[]="</script>";

    echo $tpl->_ENGINE_parse_body(@implode("\n", $html));

}


function rules_list(){
    $tpl=new template_admin();
    $page=CurrentPageName();
    $type=$tpl->_ENGINE_parse_body("{type}");
    $description=$tpl->_ENGINE_parse_body("{description}");
    $items=$tpl->_ENGINE_parse_body("{items}");
    $date=$tpl->javascript_parse_text("{date}");


    $jsrestart=$tpl->framework_buildjs(
        "/proxy/ssl/build",
        "squid.ssl.rules.articarest.progress",
        "squid.ssl.rules.progress.log",
        "progress-acls-restart");


    $add="Loadjs('$page?popup-js=yes&ID=0');";
    $html[]=sslproxy_meta_banner($tpl,"ssl_whitelist","{sslproxy_meta_whitelist_explain}");
    $html[]=$tpl->_ENGINE_parse_body("
			<div class='awc-wrap'><div class='awc-card'><div class='awc-scroll'><table class='awc-tbl'>");
    $html[]="<thead>";
    $html[]="<tr>";
    $html[]="<th>$date</th>";
    $html[]="<th>$type</th>";
    $html[]="<th>$items</th>";
    $html[]="<th>$description</th>";
    $html[]="<th></th>";
    $html[]="<th></th>";
    $html[]="</tr>";
    $html[]="</thead>";
    $html[]="<tbody id='sslwbl-rows'>";
    $TRCLASS=null;

    $results=acls_api_get("/proxy/ssl/whitelist");
    if(!is_array($results)){echo "<tr><td colspan='6' class='awc-empty'>Error fetching whitelist</td></tr>"
        ."</tbody></table></div></div></div>";return;}


    foreach ($results as $index=>$ligne) {
        $TRCLASS=null;

        $ligne["md5"]=md5(serialize($ligne));
        $ligne["zType"]=$tpl->javascript_parse_text($GLOBALS["GroupType"][$ligne["zType"]] ?? $ligne["zType"]);
        $ligne["description"]=$tpl->_ENGINE_parse_body($ligne["description"]);

        $js="Loadjs('$page?popup-js=yes&ID={$ligne["id"]}')";
        $wsec=sslproxy_meta_sec(sslproxy_meta_row("ssl_whitelist",$ligne["id"]));
        $enabled=$tpl->icon_check($ligne["enabled"],"Loadjs('$page?switch-js={$ligne["id"]}')",null,$wsec);
        $delete=$tpl->icon_delete("Loadjs('$page?delete-js={$ligne["id"]}&md5={$ligne["md5"]}')",$wsec);
        $html[]="<tr class='$TRCLASS' id='{$ligne["md5"]}'>";
        $html[]="<td style='width:1%' nowrap>{$ligne["zDate"]}</td>";
        $html[]="<td style='width:1%' nowrap><a href=\"javascript:blur();\" OnClick=\"$js\" style='font-weight:bold;' nowrap>{$ligne["zType"]}</span></td>";
        $html[]="<td style='width:1%' nowrap><a href=\"javascript:blur();\" OnClick=\"$js\" style='font-weight:bold;'>{$ligne["pattern"]}</span>".sslproxy_meta_badge("ssl_whitelist",$ligne["id"])."</td>";
        $html[]="<td style='width:80%' nowrap>{$ligne["description"]}</td>";
        $html[]="<td class='center' style='width:1%'>$enabled</td>";
        $html[]="<td class='center' style='width:1%'>$delete</td>";
        $html[]="</tr>";

    }
    $html[]="</tbody>";
        $html[]="</table></div><div class='awc-foot'><span>&nbsp;</span><span class='awc-pg' id='sslwbl-pager'></span></div></div></div>";
    $html[]="<script>";
    $html[]="NoSpinner();";
    $html[]=@implode("\n",$tpl->ICON_SCRIPTS);
        $buttons="			<div class=\"btn-group\" data-toggle=\"buttons\">
			<label class=\"btn btn btn-primary\" OnClick=\"$add\"><i class='fa fa-plus'></i> {new_rule} </label>
			<label class=\"btn btn btn-info\" OnClick=\"$jsrestart\"><i class='fa fa-save'></i> {apply} </label>
			<label class=\"btn btn btn-warning\" OnClick=\"Loadjs('$page?config-popup-js=yes')\">
				<i class='fa fa-code'></i> {configuration} </label>
			</div>";

    $TINY_ARRAY["TITLE"]="{ssl_protocol}&nbsp;&raquo;&nbsp;{ssl_whitelist}";
    $TINY_ARRAY["ICO"]="fad fa-file-certificate";
    $TINY_ARRAY["EXPL"]="{global_ssl_whitelists_proxy_explain}";
    $TINY_ARRAY["URL"]="proxy-whitelists";
    $TINY_ARRAY["BUTTONS"]=$buttons;
    $jstiny="Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";



    $html[]=$jstiny;
    $html[]="</script>";
    echo awc_table_style()
	    .@implode("\n", $html)
	    .awc_pager_js($GLOBALS["FOOTABLE_PSIZE"],"sslwbl-rows","sslwbl-pager");
}
function ExplainThis($tata=null):string{
    if($tata<>null){$_GET["GroupType"]=$tata;}
    $GroupType=$_GET["GroupType"];
    $explain="";
    if($GroupType=="src"){$explain="{acl_src_text}";}
    if($GroupType=="dst"){$explain="{acl_dst_text}";}
    if($GroupType=="arp"){$explain="{ComputerMacAddress}";}
    if($GroupType=="dstdomain"){$explain="{squid_ask_domain}";}
    if($GroupType=="maxconn"){$explain="{squid_aclmax_connections_explain}";}
    if($GroupType=="port"){$explain="{acl_squid_remote_ports_explain}";}
    if($GroupType=="ext_user"){$explain="{acl_squid_ext_user_explain}";}
    if($GroupType=="req_mime_type"){$explain="{req_mime_type_explain}";}
    if($GroupType=="rep_mime_type"){$explain="{rep_mime_type_explain}";}
    if($GroupType=="referer_regex"){$explain="{acl_squid_referer_regex_explain}";}
    if($GroupType=="srcdomain"){$explain="{acl_squid_srcdomain_explain}";}
    if($GroupType=="url_regex_extensions"){$explain="{url_regex_extensions_explain}";}
    if($GroupType=="max_user_ip"){$explain="<b>{acl_max_user_ip_title}</b><br>{acl_max_user_ip_text}";}
  //  if($GroupType=="quota_time"){$explain="{acl_quota_time_text}";}
    if($GroupType=="quota_size"){$explain="{acl_quota_size_text}";}
    if($GroupType=="ssl_sni"){$explain="{acl_ssl_sni_text}";}
    if($GroupType=="myportname"){$explain="{acl_myportname_text}";}
    if($GroupType=="rep_header_filename"){$explain="{rep_header_filename_explain}";}
    if($GroupType=="browser"){$explain="{acl_squid_browser_explain}";}
    if($GroupType=="server_cert_fingerprint"){$explain="{server_cert_fingerprint_explain}";}



    if($tata<>null){return $explain;}
    if($explain==null){return "";}
    $html="<div class='alert alert-info'>$explain</div>";
    $tpl=new templates();
    echo $tpl->_ENGINE_parse_body($html);
    return "";

}
