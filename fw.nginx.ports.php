<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
include_once("/usr/share/artica-postfix/ressources/class.nginx.params.inc");
// Pentest 2026-11-04 : service, delete, outgoing, outgoing-popup, port-js, serviceid et md5 etaient recopies sans encodage dans la page (XSS).
foreach (array("service","delete","outgoing","outgoing-popup","port-js","serviceid","table","port-popup") as $xk){if(isset($_GET[$xk])){$_GET[$xk]=intval($_GET[$xk]);}}
if(isset($_GET["md5"])){$_GET["md5"]=preg_replace('#[^A-Za-z0-9]#','',(string)$_GET["md5"]);}
$users=new usersMenus();
if(!$users->AsWebMaster && !$users->AsSystemWebMaster){exit();}

if(isset($_GET["table"])){table();exit;}
if(isset($_GET["port-js"])){port_js();exit;}
if(isset($_GET["port-popup"])){port_popup();exit;}
if(isset($_GET["delete"])){delete_js();exit;}
if(isset($_POST["md5"])){port_save();exit;}
if(isset($_POST["delete"])){delete();exit;}
if(isset($_GET["outgoing"])){outgoing_js();exit;}
if(isset($_POST["outgoing"])){outgoing_save();exit;}
if(isset($_GET["outgoing-popup"])){outgoing_popup();exit;}
table_start();

// Garde serveur des ecritures : le meme droit que celui qui affiche les formulaires.
function np_can_write():bool{
    $users=new usersMenus();
    return (bool)$users->AsSystemWebMaster;
}
function np_deny():bool{
    $tpl=new template_admin();
    echo $tpl->_ENGINE_parse_body("{ERROR_NO_PRIVS2}");
    return false;
}
function np_h($s):string{
    $s=htmlspecialchars((string)$s,ENT_QUOTES,"UTF-8");
    return str_replace(array("{","}","[","]"),array("&#123;","&#125;","&#91;","&#93;"),$s);
}
// Traduit un token PUIS remplace ses valeurs.
function np_t($k,$vars=array()):string{
    $tpl=new template_admin();
    $s=$tpl->_ENGINE_parse_body("{".$k."}");
    return str_replace(array_keys($vars),array_values($vars),$s);
}
// Rafraichissement de l'onglet : une fonction fixe, que les modales appellent apres un enregistrement.
function np_refresh_js($serviceid):string{
    $serviceid=intval($serviceid);
    return "if(typeof PortsRefresh$serviceid==='function'){PortsRefresh$serviceid();}Loadjs('fw.nginx.sites.php?td-row=$serviceid');";
}

function table_start():bool{
    $page=CurrentPageName();
    $ID=intval($_GET["service"]);
    echo "<div id='stream-listen-ports-$ID'></div>
    <script>function PortsRefresh$ID(){LoadAjaxSilent('stream-listen-ports-$ID','$page?table=$ID');}LoadAjax('stream-listen-ports-$ID','$page?table=$ID');</script>";
    return true;
}
function delete_js():bool{
    $tpl=new template_admin();$tpl->CLUSTER_CLI=true;
    $ID=intval($_GET["delete"]);
    $ligne=fetch_stream_port_by_id($ID);
    $serviceid=isset($ligne["serviceid"])?intval($ligne["serviceid"]):0;
    $interface=isset($ligne["interface"])?$ligne["interface"]:"";
    $port=isset($ligne["port"])?intval($ligne["port"]):0;
    return $tpl->js_confirm_delete(req_html("$interface:$port"), "delete", "$ID",np_refresh_js($serviceid));
}
function delete():bool{
    if(!np_can_write()){return np_deny();}
    $ID=intval($_POST["delete"]);
    if($ID<=0){return false;}
    $ligne=fetch_stream_port_by_id($ID);
    if(count($ligne)==0){echo "port $ID: not found";return false;}
    $serviceid=isset($ligne["serviceid"])?intval($ligne["serviceid"]):0;
    $port=isset($ligne["port"])?$ligne["port"]:"";
    $GLOBALS["CLASS_SOCKETS"]->REST_API_NGINX_POST_JSON("/nginx-db/exec", ["action"=>"delete","table"=>"stream_ports","where"=>["ID"=>$ID]]);
    $GLOBALS["CLASS_SOCKETS"]->CLUSTER_NGINX($serviceid);
    $servername=get_servicename($serviceid);
    $GLOBALS["CLASS_SOCKETS"]->REST_API_NGINX("/reverse-proxy/singlehup/$serviceid");
    return admin_tracks("Delete port $port from $servername reverse-proxy site");
}
function get_servicename($ID):string{
    $ID=intval($ID);
    if($ID==0){return "Unknown";}
    $sock=new socksngix($ID);
    return $sock->GetServiceName();
}
function isHarmpID():bool{
    if(!isset($_SESSION["HARMPID"])){return false;}
    return intval($_SESSION["HARMPID"])>0;
}

function outgoing_js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();$tpl->CLUSTER_CLI=true;
    $serviceid=intval($_GET["outgoing"]);
    return $tpl->js_dialog3("{outgoing_interface}", "$page?outgoing-popup=$serviceid",550);
}
function outgoing_popup():bool{
    $tpl=new template_admin();$tpl->CLUSTER_CLI=true;
    $serviceid=intval($_GET["outgoing-popup"]);
    $html[]=$tpl->field_hidden("outgoing",$serviceid);
    $sock=new socksngix($serviceid);
    $ligne=$sock->GetCache();
    $OutGoingInterface=isset($ligne["OutGoingInterface"])?$ligne["OutGoingInterface"]:"";
    $OutGoingTransparent=isset($ligne["OutGoingTransparent"])?$ligne["OutGoingTransparent"]:0;
    $html[]=$tpl->field_interfaces("OutGoingInterface","{outgoing_interface}",$OutGoingInterface);
    $html[]=$tpl->field_checkbox("OutGoingTransparent","{transparent}",$OutGoingTransparent);
    $js="dialogInstance3.close();".np_refresh_js($serviceid);
    echo $tpl->form_outside("", $html,"","{apply}",$js,"AsSystemWebMaster");
    return true;
}
function outgoing_save():bool{
    if(!np_can_write()){return np_deny();}
    $tpl=new template_admin();$tpl->CLUSTER_CLI=true;
    $tpl->CLEAN_POST();
    $serviceid=intval($_POST["outgoing"]);
    $iface=preg_replace('#[^A-Za-z0-9:._-]#','',(string)$_POST["OutGoingInterface"]);
    $transparent=intval($_POST["OutGoingTransparent"])==1?1:0;
    $sock=new socksngix($serviceid);
    $sock->SET_MULTI(array("OutGoingInterface"=>$iface,"OutGoingTransparent"=>$transparent));
    $GLOBALS["CLASS_SOCKETS"]->REST_API_NGINX("/reverse-proxy/singlehup/$serviceid");
    $servicename=get_servicename($serviceid);
    return admin_tracks("Save reverse-proxy $servicename Outgoing interface to $iface transparent=$transparent");
}

function port_js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();$tpl->CLUSTER_CLI=true;
    $ID=intval($_GET["port-js"]);
    $serviceid=isset($_GET["serviceid"])?intval($_GET["serviceid"]):0;
    $md5=isset($_GET["md5"])?$_GET["md5"]:"";
    $title=$ID>0?"{npm_edit_port}":"{npm_new_port}";
    return $tpl->js_dialog3($title, "$page?port-popup=$ID&serviceid=$serviceid&md5=$md5");
}
// Modale d'ajout / d'edition d'un port d'ecoute (maquette Stitch 2026-11-05).
// L'apercu applique cote navigateur les memes regles que np_model() : ce que nginx
// servira vraiment, pas seulement les cases cochees. L'envoi reste port_save().
function port_popup():bool{
    $m=npm_model(intval($_GET["port-popup"]),intval($_GET["serviceid"]),isset($_GET["md5"])?$_GET["md5"]:"");
    $tpl=new template_admin();$tpl->CLUSTER_CLI=true;
    $h=array();
    $h[]="<div class='npm' id='{$m["uid"]}'>";
    $h[]=npm_preview($m);
    $h[]=npm_banner_nocert($m);
    $h[]=npm_readonly($m);
    $h[]=npm_iface($m);
    $h[]=npm_port($m);
    $h[]=npm_options($m);
    $h[]="<div class='npm-err' role='alert' data-npm='err'></div>";
    $h[]=npm_footer($m);
    $h[]="</div>";
    echo np_style().npm_style().$tpl->_ENGINE_parse_body(implode("",$h)).npm_js($m);
    return true;
}

// Modele de la modale : la ligne stream_ports (ou les valeurs d'un ajout) + le site vu par np_model().
function npm_model($ID,$serviceid,$md5):array{
    $tpl=new template_admin();
    $ligne=array("port"=>443,"options"=>"");
    if($ID>0){
        $ligne=fetch_stream_port_by_id($ID);
        $serviceid=isset($ligne["serviceid"])?intval($ligne["serviceid"]):$serviceid;
        $md5=isset($ligne["zmd5"])?strval($ligne["zmd5"]):"";
    }
    $port=isset($ligne["port"])?intval($ligne["port"]):0;
    if($port<5){$port=$ID>0?80:443;}
    $o=$GLOBALS["CLASS_SOCKETS"]->unserializeb64(isset($ligne["options"])?$ligne["options"]:"");
    if(!is_array($o)){$o=array();}
    $site=np_model($serviceid);

    // HTTP/2 est une directive du serveur : un AUTRE port TLS qui la demande l'active aussi pour celui-ci.
    $h2others=false;
    foreach($site["ports"] as $p){
        if($p["id"]==$ID){continue;}
        if($p["ssl"] && $p["h2_opt"] && $site["h2mod"]){$h2others=true;}
    }

    // Interfaces proposees : celles de field_interfaces("nooloop:nodef:…"), sans loopback ni « toutes ».
    $nics=$tpl->list_interfaces(false,array());
    $harmp=isHarmpID();
    $iface="";
    if(!$harmp){
        $iface=isset($ligne["interface"])?trim(strval($ligne["interface"])):"";
        if($iface=="" && $ID==0){
            $iface=isset($nics["eth0"])?"eth0":strval(key($nics));
        }
    }
    return array(
        "uid"=>"npm".substr(md5(microtime().rand(0,65535)),0,10),
        "id"=>$ID,"serviceid"=>$serviceid,"md5"=>preg_replace('#[^A-Za-z0-9]#','',$md5),
        "port"=>$port,"iface"=>$iface,"nic"=>np_nic($iface),"nics"=>$nics,"harmp"=>$harmp,
        "ssl"=>intval(isset($o["ssl"])?$o["ssl"]:0)==1,
        "h2"=>intval(isset($o["http2"])?$o["http2"]:0)==1,
        "pp"=>intval(isset($o["proxy_protocol"])?$o["proxy_protocol"]:0)==1,
        "site"=>$site,"h2others"=>$h2others,
        "can_write"=>np_can_write(),
    );
}

// Texte d'un token pour le JavaScript : traduit, sans les retours [br] des fins de phrase.
function npm_js_str($k):string{
    // Les traductions sont stockees en entites (&#233;) : le JS les pose en textContent, donc on decode ici.
    return trim(html_entity_decode(str_replace(array("<br>","<br/>","<br />","[br]"),"",np_t($k)),ENT_QUOTES,"UTF-8"));
}

function npm_preview($m):string{
    return "<section class='npm-prev' aria-live='polite'>"
        ."<div class='npm-prev-top'><span class='npm-prev-addr'><span data-npm='lock' aria-hidden='true'></span><span class='np-mono' data-npm='addr'></span></span>"
        ."<span class='npm-caps'>{npm_effective}</span></div>"
        ."<div class='np-pills' data-npm='pills'></div></section>";
}
function npm_banner_nocert($m):string{
    return "<div class='np-banner np-banner-warn npm-hide' role='status' data-npm='nocert'><i class='fa-solid fa-triangle-exclamation' aria-hidden='true'></i><span>{npm_nocert_banner}</span></div>";
}
function npm_readonly($m):string{
    if($m["can_write"]){return "";}
    return "<div class='np-banner np-banner-info' role='status'><i class='fa-solid fa-lock' aria-hidden='true'></i><span>{npm_readonly}</span></div>";
}
// Interface : champ en lecture seule + « Select » qui ouvre le selecteur commun
// (fw.field.interfaces.php ecrit la valeur dans #id et « NOM (IP) » dans #btnlabel-id).
function npm_iface($m):string{
    if($m["harmp"]){return "";}
    $fid=$m["uid"]."if";
    $n=$m["nic"];
    $parts=array();
    if($m["iface"]<>""){$parts[]=np_h($m["iface"]);}
    if($n["ip"]<>""){$parts[]=np_h($n["ip"]);}
    if($n["name"]<>"" && $n["name"]<>$m["iface"]){$parts[]=np_h($n["name"]);}
    $txt=count($parts)>0?implode(" &middot; ",$parts):"{npm_iface_none}";
    $miss=($m["iface"]<>"" && !$n["found"])?"":" npm-hide";
    $btn="";
    if($m["can_write"]){
        $btn="<button type='button' class='npm-sel' data-npm='pick'><i class='fa-solid fa-network-wired' aria-hidden='true'></i><span>{npm_select}</span></button>";
    }
    return "<section class='npm-sec'><span class='npm-lbl' id='{$fid}-l'>{listen_interface}</span>"
        ."<div class='npm-ifbox'><span class='npm-ifval np-mono' data-npm='ifval' aria-labelledby='{$fid}-l'>$txt</span>$btn</div>"
        ."<input type='hidden' id='$fid' value=\"".np_h($m["iface"])."\"><span id='btnlabel-$fid' class='npm-hide'></span>"
        ."<p class='np-warn-txt npm-note$miss' data-npm='ifmiss'><i class='fa-solid fa-triangle-exclamation' aria-hidden='true'></i>{np_iface_missing}</p></section>";
}
function npm_port($m):string{
    $fid=$m["uid"]."pt";
    $dis=$m["can_write"]?"":" disabled";
    $chips=array();
    foreach(array(80,443,8080,8443) as $p){
        $chips[]="<button type='button' role='radio' aria-checked='false' class='npm-chip np-mono' data-port='$p'$dis>$p</button>";
    }
    return "<section class='npm-sec'><label class='npm-lbl' for='$fid'>{npm_listen_port}</label>"
        ."<div class='npm-portrow'><input type='number' min='1' max='65535' id='$fid' class='npm-port np-mono' data-npm='port' value='".intval($m["port"])."'$dis>"
        ."<div class='npm-chips' role='radiogroup' aria-label=\"{npm_common_ports}\">".implode("",$chips)."</div></div>"
        ."<p class='npm-help'>{np_port_help}</p></section>";
}
function npm_switch_row($m,$key,$label,$desc,$control=""):string{
    $id=$m["uid"].$key;
    if($control==""){
        $control="<button type='button' role='switch' aria-checked='false' class='npm-sw' id='$id' data-npm-sw='$key' aria-describedby='$id-d'><span class='npm-knob'></span></button>";
    }
    return "<div class='npm-row'><div class='npm-row-txt'><label class='npm-row-lbl' for='$id'>$label</label>"
        ."<div class='npm-row-desc' id='$id-d'><span>$desc</span> <span class='npm-hint' data-npm-hint='$key'></span></div></div>"
        ."<div class='npm-row-ctl'>$control</div></div>";
}
function npm_options($m):string{
    $h=array();
    $h[]="<section class='npm-sec'><h3 class='npm-caps npm-opt-title'>{npm_options}</h3><div class='npm-rows'>";
    $h[]=npm_switch_row($m,"ssl","{npm_ssl}","{npm_ssl_desc}");
    $h[]=npm_switch_row($m,"h2","HTTP/2","{npm_h2_desc}");
    $pp="";
    if(!$m["site"]["pp_global"]){
        $pp="<span class='npm-off-txt'><i class='fa-solid fa-circle-info' aria-hidden='true'></i>{npm_pp_off_global}</span>";
    }
    $h[]=npm_switch_row($m,"pp","{npm_pp}","{npm_pp_desc}",$pp);
    $h[]="</div></section>";
    return implode("",$h);
}
function npm_footer($m):string{
    $label=$m["id"]>0?"{npm_apply}":"{npm_add}";
    if($m["can_write"]){
        $go="<button type='button' class='npm-go' data-npm='go'>$label</button>";
    }else{
        $go="<button type='button' class='npm-go' aria-disabled='true' disabled><i class='fa-solid fa-lock' aria-hidden='true'></i>$label</button>";
    }
    return "<div class='npm-foot'><button type='button' class='npm-cancel' data-npm='cancel'>{npm_cancel}</button>$go</div>";
}

function npm_js($m):string{
    $page=CurrentPageName();
    $s=$m["site"];
    $fid=$m["uid"]."if";
    $C=array(
        "uid"=>$m["uid"],"page"=>$page,"fid"=>$fid,
        "id"=>$m["id"],"serviceid"=>$m["serviceid"],"md5"=>$m["md5"],
        "iface"=>$m["iface"],"ip"=>$m["nic"]["ip"],"harmp"=>$m["harmp"],
        "ssl"=>$m["ssl"],"h2"=>$m["h2"],"pp"=>$m["pp"],
        "tcp"=>$s["type"]==5,"cert"=>$s["has_cert"],"h2mod"=>$s["h2mod"],"h2others"=>$m["h2others"],
        "ppg"=>$s["pp_global"],"pplb"=>$s["pp_lb"],"ppsite"=>$s["pp_site"],"redir80"=>$s["redir80"],
        "w"=>$m["can_write"],
        "nics"=>base64_encode(serialize($m["nics"])),
    );
    $L=array();
    foreach(array("np_not_applied","np_r_nocert","np_r_h2mod","np_r_ppglobal","np_r_nolb","np_skipped_redirect",
                  "npm_forced_443","npm_never_80","npm_h2_needs_ssl","npm_h2_by_other","npm_pp_site","npm_pp_nolb",
                  "npm_tcp_ignored","npm_port_invalid","npm_default_cert","success_form_submited") as $k){
        $L[$k]=npm_js_str($k);
    }
    $flags=JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_QUOT|JSON_HEX_AMP;
    $jsafter="dialogInstance3.close();".np_refresh_js($m["serviceid"]);
    $j=array();
    $j[]="<script>(function(){";
    $j[]="var C=".json_encode($C,$flags).",L=".json_encode($L,$flags).";";
    $j[]="var R=document.getElementById(C.uid);if(!R){return;}";
    $j[]="function q(s){return R.querySelector(s);}";
    $j[]="function esc(t){var d=document.createElement('div');d.textContent=t;return d.innerHTML;}";
    $j[]="var port=q('[data-npm=port]'),ifIn=document.getElementById(C.fid),ifLab=document.getElementById('btnlabel-'+C.fid);";
    $j[]="var want={ssl:C.ssl,h2:C.h2,pp:C.pp},eff={ssl:false,h2:false,pp:false},busy=false;";
    $j[]="function sw(k){return q('[data-npm-sw='+k+']');}";
    $j[]="function setSw(k,val,lock,hint){var b=sw(k),h=q('[data-npm-hint='+k+']');if(h){h.innerHTML=hint?'<i class=\"fa-solid fa-lock\" aria-hidden=\"true\"></i> '+esc(hint):'';}if(!b){return;}"
        ."b.setAttribute('aria-checked',val?'true':'false');if(lock||!C.w){b.setAttribute('aria-disabled','true');}else{b.removeAttribute('aria-disabled');}}";
    $j[]="function pill(cls,ico,t){return '<span class=\"np-pill '+cls+'\">'+(ico?'<i class=\"fa-solid '+ico+'\" aria-hidden=\"true\"></i>':'')+esc(t)+'</span>';}";
    $j[]="function off(lbl,why){return pill('np-off','',lbl+' – '+L.np_not_applied+': '+why);}";
    $j[]="function render(){";
    $j[]=" var p=parseInt(port.value,10)||0,tcp=C.tcp;";
    $j[]=" var ssl=want.ssl,sslLock='';";
    $j[]=" if(tcp){ssl=false;sslLock=L.npm_tcp_ignored;}else if(p==443){ssl=true;sslLock=L.npm_forced_443;}else if(p==80){ssl=false;sslLock=L.npm_never_80;}";
    $j[]=" setSw('ssl',ssl,sslLock!=='',sslLock);eff.ssl=ssl;";
    $j[]=" var h2=want.h2&&ssl,h2Lock='',h2Hint='';";
    $j[]=" if(tcp){h2=false;h2Lock=L.npm_tcp_ignored;}else if(!C.h2mod){h2=false;h2Lock=L.np_r_h2mod;}else if(!ssl){h2=false;h2Lock=L.npm_h2_needs_ssl;}";
    $j[]=" else if(!h2&&C.h2others){h2Hint=L.npm_h2_by_other;}";
    $j[]=" setSw('h2',h2,h2Lock!=='',h2Lock||h2Hint);eff.h2=h2;";
    $j[]=" var pp=want.pp,ppLock='';";
    $j[]=" if(tcp){pp=false;ppLock=L.npm_tcp_ignored;}else if(C.ppsite){pp=true;ppLock=L.npm_pp_site;}";
    $j[]=" if(C.ppg){setSw('pp',pp,ppLock!=='',ppLock||((pp&&!C.pplb)?L.npm_pp_nolb:''));}eff.pp=C.ppg&&pp;";
    // Apercu : memes regles que np_model() + la pose du certificat par defaut sur 443 par port_save().
    $j[]=" var https=ssl&&(C.cert||p==443),pills=[];";
    $j[]=" if(https){pills.push(pill('np-ok','fa-lock','HTTPS'));}else{pills.push(pill('np-neutral','fa-globe','HTTP'));if(ssl){pills.push(off('HTTPS',L.np_r_nocert));}}";
    $j[]=" if(https&&!C.cert){pills.push(pill('np-neutral','fa-certificate',L.npm_default_cert));}";
    $j[]=" if(https&&C.h2mod&&(h2||C.h2others)){pills.push(pill('np-info','fa-bolt','HTTP/2'));}else if(want.h2&&ssl&&!tcp&&!C.h2mod){pills.push(off('HTTP/2',L.np_r_h2mod));}";
    $j[]=" var ppWanted=((C.ppg&&pp)||C.ppsite)&&!tcp;";
    $j[]=" if(ppWanted&&C.ppg&&C.pplb){pills.push(pill('np-violet','fa-right-left','PROXY protocol'));}else if(ppWanted){pills.push(off('PROXY protocol',C.ppg?L.np_r_nolb:L.np_r_ppglobal));}";
    $j[]=" if(p==80&&C.redir80&&!tcp){pills.push(pill('np-off','',L.np_skipped_redirect));}";
    $j[]=" q('[data-npm=pills]').innerHTML=pills.join('');";
    $j[]=" var host=C.harmp?'*':(C.ip||C.iface||'*');if(host.indexOf(':')>=0&&host!==C.iface){host='['+host+']';}";
    $j[]=" q('[data-npm=addr]').textContent=(https?'https':'http')+'://'+host+':'+(p||'?');";
    $j[]=" q('[data-npm=lock]').innerHTML=https?'<i class=\"fa-solid fa-lock npm-ok\"></i>':'<i class=\"fa-solid fa-lock-open npm-mut\"></i>';";
    $j[]=" q('[data-npm=nocert]').classList.toggle('npm-hide',!(ssl&&!C.cert&&!tcp));";
    $j[]=" var cs=R.querySelectorAll('.npm-chip');for(var i=0;i<cs.length;i++){cs[i].setAttribute('aria-checked',parseInt(cs[i].getAttribute('data-port'),10)===p?'true':'false');}";
    $j[]=" var bad=(p<1||p>65535);port.setAttribute('aria-invalid',bad?'true':'false');q('[data-npm=err]').textContent=bad?L.npm_port_invalid:'';";
    $j[]="}";
    $j[]="function toggle(k){var b=sw(k);if(!b||b.getAttribute('aria-disabled')==='true'){return;}want[k]=!(b.getAttribute('aria-checked')==='true');render();}";
    $j[]="['ssl','h2','pp'].forEach(function(k){var b=sw(k);if(b){b.addEventListener('click',function(){toggle(k);});}});";
    $j[]="port.addEventListener('input',render);";
    $j[]="var cs=R.querySelectorAll('.npm-chip');for(var i=0;i<cs.length;i++){cs[i].addEventListener('click',function(){if(!C.w){return;}port.value=this.getAttribute('data-port');render();});}";
    // Selecteur commun : il ecrit « NOM (IP) » dans #btnlabel-<id> ; on en tire l'IP de l'apercu.
    $j[]="var pick=q('[data-npm=pick]');if(pick){pick.addEventListener('click',function(){Loadjs('fw.field.interfaces.php?name=interface&nics='+encodeURIComponent(C.nics)+'&id='+C.fid+'&value='+encodeURIComponent(ifIn.value)+'&suffix-form=');});}";
    $j[]="if(ifLab&&window.MutationObserver){new MutationObserver(function(){var t=ifLab.textContent||'',mm=t.match(/^(.*)\\(([^()]*)\\)\\s*$/);C.iface=ifIn.value;C.ip=mm?mm[2].trim():'';"
        ."var nm=mm?mm[1].trim():'',parts=[C.iface];if(C.ip){parts.push(C.ip);}if(nm&&nm!==C.iface){parts.push(nm);}"
        ."q('[data-npm=ifval]').textContent=parts.join(' · ');q('[data-npm=ifmiss]').classList.add('npm-hide');render();}).observe(ifLab,{childList:true,characterData:true,subtree:true});}";
    $j[]="q('[data-npm=cancel]').addEventListener('click',function(){if(typeof dialogInstance3!=='undefined'){dialogInstance3.close();}});";
    $j[]="var go=q('[data-npm=go]');if(go){go.addEventListener('click',function(){";
    $j[]=" var p=parseInt(port.value,10)||0;if(busy||p<1||p>65535){render();return;}busy=true;go.disabled=true;";
    $j[]=" var X=new XHRConnection();X.appendData('ID',C.id);X.appendData('md5',C.md5);X.appendData('serviceid',C.serviceid);";
    $j[]=" if(ifIn){X.appendData('interface',encodeURIComponent(ifIn.value));}X.appendData('port',p);";
    $j[]=" X.appendData('ssl',eff.ssl?1:0);X.appendData('http2',(want.h2&&eff.ssl)?1:0);X.appendData('proxy_protocol',eff.pp?1:0);";
    $j[]=" X.sendAndLoad(C.page,'POST',function(o){busy=false;go.disabled=false;var r=(o&&o.responseText?o.responseText:'');r=(window.DOMParser?new DOMParser().parseFromString(r,'text/html').body.textContent:r.replace(/<[^>]*>/g,' '))||'';r=r.trim();";
    $j[]="  if(r.length>3){q('[data-npm=err]').textContent=r;return;}";
    $j[]="  if(typeof toastr!=='undefined'){toastr.options.timeOut=2000;toastr.success(L.success_form_submited);}";
    $j[]="  ".str_replace("\n","",$jsafter);
    $j[]=" });";
    $j[]="});}";
    $j[]="render();";
    $j[]="})();</script>";
    return implode("\n",$j);
}

function npm_style():string{
    $f=array();
    $f[]="<style>";
    $f[]=".npm{display:flex;flex-direction:column;gap:20px;padding:8px 4px 0;color:#0f172a;font-size:14px}";
    $f[]=".npm *{box-sizing:border-box}";
    $f[]=".npm .npm-hide{display:none!important}";
    $f[]=".npm-caps{font-size:12px;font-weight:600;text-transform:uppercase;letter-spacing:.06em;color:#64748b}";
    $f[]=".npm-prev{display:flex;flex-direction:column;gap:12px;padding:16px 20px;background:#f8fafc;border:1px solid #e2e8f0;border-radius:8px}";
    $f[]=".npm-prev-top{display:flex;align-items:center;justify-content:space-between;gap:12px}";
    $f[]=".npm-prev-addr{display:flex;align-items:center;gap:10px;min-width:0;font-size:18px;font-weight:600;word-break:break-all}";
    $f[]=".npm-ok{color:#047857}.npm-mut{color:#64748b}";
    $f[]=".npm-sec{display:flex;flex-direction:column;gap:8px}";
    $f[]=".npm-lbl{margin:0;font-size:13px;font-weight:600;text-transform:uppercase;letter-spacing:.05em;color:#475569}";
    $f[]=".npm-ifbox{display:flex;align-items:stretch;min-height:44px;border:1px solid #cbd5e1;border-radius:8px;background:#f8fafc;overflow:hidden}";
    $f[]=".npm-ifval{flex:1;display:flex;align-items:center;padding:0 14px;font-size:15px;color:#0f172a;min-width:0;word-break:break-all}";
    $f[]=".npm-sel{display:inline-flex;align-items:center;gap:8px;padding:0 18px;border:0;border-left:1px solid #cbd5e1;background:#f1f5f9;color:#0369a1;font-weight:600;font-size:14px;cursor:pointer}";
    $f[]=".npm-sel:hover{background:#e0f2fe}";
    $f[]=".npm-note{margin:0}";
    $f[]=".npm-portrow{display:flex;align-items:center;flex-wrap:wrap;gap:12px}";
    $f[]=".npm-port{width:160px;height:44px;padding:0 14px;border:1px solid #cbd5e1;border-radius:8px;font-size:16px;color:#0f172a;background:#fff}";
    $f[]=".npm-port[aria-invalid=true]{border-color:#b91c1c}";
    $f[]=".npm-chips{display:flex;gap:8px;flex-wrap:wrap}";
    $f[]=".npm-chip{min-width:52px;height:34px;padding:0 12px;border:1px solid #cbd5e1;border-radius:6px;background:#fff;color:#334155;font-size:13px;font-weight:600;cursor:pointer}";
    $f[]=".npm-chip:hover{background:#f1f5f9}";
    $f[]=".npm-chip[aria-checked=true]{background:#0369a1;border-color:#0369a1;color:#fff}";
    $f[]=".npm-chip[disabled],.npm-port[disabled]{cursor:not-allowed;opacity:.65}";
    $f[]=".npm-help{margin:0;font-size:13px;color:#64748b}";
    $f[]=".npm-opt-title{margin:0;padding-bottom:8px;border-bottom:1px solid #e2e8f0}";
    $f[]=".npm-rows{display:flex;flex-direction:column}";
    $f[]=".npm-row{display:flex;align-items:center;justify-content:space-between;gap:16px;min-height:60px;padding:10px 0;border-bottom:1px solid #f1f5f9}";
    $f[]=".npm-row:last-child{border-bottom:0}";
    $f[]=".npm-row-txt{display:flex;flex-direction:column;gap:2px;min-width:0}";
    $f[]=".npm-row-lbl{margin:0;font-size:15px;font-weight:600;color:#0f172a}";
    $f[]=".npm-row-desc{font-size:13px;color:#64748b}";
    $f[]=".npm-hint{color:#0369a1;font-weight:600;white-space:nowrap}";
    $f[]=".npm-sw{position:relative;width:46px;height:26px;flex-shrink:0;border:0;border-radius:999px;background:#cbd5e1;cursor:pointer;transition:background-color .12s;padding:0}";
    $f[]=".npm-knob{position:absolute;top:3px;left:3px;width:20px;height:20px;border-radius:50%;background:#fff;box-shadow:0 1px 2px rgba(15,23,42,.25);transition:transform .12s}";
    $f[]=".npm-sw[aria-checked=true]{background:#0369a1}";
    $f[]=".npm-sw[aria-checked=true] .npm-knob{transform:translateX(20px)}";
    $f[]=".npm-sw[aria-disabled=true]{opacity:.55;cursor:not-allowed}";
    $f[]=".npm-off-txt{display:inline-flex;align-items:center;gap:6px;font-size:13px;font-weight:600;color:#475569;white-space:nowrap}";
    $f[]=".npm-err{font-size:13px;font-weight:600;color:#b91c1c}";
    $f[]=".npm-err:empty{display:none}";
    $f[]=".npm-foot{display:flex;justify-content:flex-end;gap:12px;padding-top:16px;border-top:1px solid #e2e8f0}";
    $f[]=".npm-cancel,.npm-go{display:inline-flex;align-items:center;gap:8px;height:40px;padding:0 20px;border-radius:8px;font-size:14px;font-weight:600;cursor:pointer}";
    $f[]=".npm-cancel{border:1px solid #cbd5e1;background:#fff;color:#334155}";
    $f[]=".npm-cancel:hover{background:#f1f5f9}";
    $f[]=".npm-go{border:1px solid #0369a1;background:#0369a1;color:#fff}";
    $f[]=".npm-go:hover{background:#075985}";
    $f[]=".npm-go[disabled]{opacity:.6;cursor:not-allowed}";
    $f[]=".npm button:focus-visible,.npm input:focus-visible{outline:2px solid #0369a1;outline-offset:2px}";
    $f[]="@media (prefers-reduced-motion:reduce){.npm-sw,.npm-knob{transition:none}}";
    $f[]="</style>";
    return implode("",$f);
}

function port_save():bool{
    if(!np_can_write()){return np_deny();}
    $nginxsock=new socksngix(0);
    $NginxProxyProtocol=intval($nginxsock->GET_INFO("NginxProxyProtocol"));
    $tpl=new template_admin();$tpl->CLUSTER_CLI=true;
    $tpl->CLEAN_POST();
    $ID=intval($_POST["ID"]);
    $serviceid=intval($_POST['serviceid']);
    if($serviceid<=0){echo "service id: not found";return false;}
    $nginxsock2=new socksngix($serviceid);
    $GLOBALS["CLASS_SOCKETS"]->REST_API_NGINX_POST_JSON("/nginx-db/exec", ["action"=>"delete","table"=>"stream_ports","where"=>["serviceid"=>"0"]]);
    $interface=preg_replace('#[^A-Za-z0-9:._-]#','',(string)(isset($_POST["interface"])?$_POST["interface"]:""));
    $port=intval($_POST["port"]);
    if($port<5){$port=443;}
    if($port>65535){echo npm_js_str("npm_port_invalid");return false;}
    // Options stockees : seulement les trois que le demon lit, jamais tout le $_POST.
    $opts=array(
        "ssl"=>intval(isset($_POST["ssl"])?$_POST["ssl"]:0)==1?1:0,
        "http2"=>intval(isset($_POST["http2"])?$_POST["http2"]:0)==1?1:0,
        "proxy_protocol"=>intval(isset($_POST["proxy_protocol"])?$_POST["proxy_protocol"]:0)==1?1:0,
    );
    if($NginxProxyProtocol==0){$opts["proxy_protocol"]=0;}
    if($port==443){
        $opts["ssl"]=1;
        if(strlen(strval($nginxsock2->GET_INFO("ssl_certificate")))<3){
            $nginxsock2->SET_INFO("ssl_certificate","__DEFAULT__");
        }
    }
    $options=base64_encode(serialize($opts));
    $md5=md5("$interface$port$serviceid");

    // Edition : l'ancienne ligne est supprimee par son ID. Supprimer par la nouvelle
    // empreinte laissait l'ancienne en place des qu'on changeait le port ou l'interface.
    if($ID>0){
        $GLOBALS["CLASS_SOCKETS"]->REST_API_NGINX_POST_JSON("/nginx-db/exec", ["action"=>"delete","table"=>"stream_ports","where"=>["ID"=>$ID]]);
    }
    $GLOBALS["CLASS_SOCKETS"]->REST_API_NGINX_POST_JSON("/nginx-db/exec", ["action"=>"delete","table"=>"stream_ports","where"=>["zmd5"=>$md5]]);
    $GLOBALS["CLASS_SOCKETS"]->REST_API_NGINX_POST_JSON("/nginx-db/exec", ["action"=>"insert","table"=>"stream_ports","values"=>["serviceid"=>$serviceid,"interface"=>$interface,"port"=>$port,"zmd5"=>$md5,"options"=>$options]]);
    // Un ajout est applique et audite comme une modification (il sortait avant).
    $GLOBALS["CLASS_SOCKETS"]->CLUSTER_NGINX($serviceid);
    $servername=get_servicename($serviceid);
    $GLOBALS["CLASS_SOCKETS"]->REST_API_NGINX("/reverse-proxy/singlehup/$serviceid");
    $verb=$ID>0?"Edit port":"Add new port";
    return admin_tracks("$verb $interface:$port for $servername reverse-proxy website");
}

function table():bool{
    $tpl=new template_admin();
    $tpl->CLUSTER_CLI=true;
    $serviceid=intval($_GET["table"]);
    $m=np_model($serviceid);
    $html=array();
    $html[]="<div class='np-wrap'>";
    $html[]=np_actions($m);
    $html[]=np_outgoing_card($m);
    $html[]=np_banner_nocert($m);
    $html[]=np_banner_noport($m);
    $html[]=np_ports_table($m);
    $html[]="</div>";
    $html[]="<script>NoSpinner();</script>";
    echo np_style().$tpl->_ENGINE_parse_body(implode("\n",$html));
    return true;
}

// Interface reseau : adresse, nom, reseau ; adresse d'une IP virtuelle keepalived.
function np_nic($iface):array{
    $r=array("iface"=>$iface,"ip"=>"","name"=>"","network"=>"","vip"=>false,"found"=>false);
    if($iface==""){return $r;}
    $eth=new system_nic($iface);
    $r["ip"]=trim(strval($eth->IPADDR));
    $r["name"]=trim(strval($eth->NICNAME));
    $r["network"]=trim(strval($eth->NETWORK));
    $r["found"]=($r["ip"]<>"" || $r["name"]<>"");
    if($r["ip"]=="0.0.0.0" && strpos($iface,":")!==false){
        $vip=np_vip_ip($iface);
        if($vip<>""){$r["ip"]=$vip;$r["vip"]=true;}
    }
    return $r;
}
function np_vip_ip($iface):string{
    if(intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("APP_KEEPALIVED_ENABLE"))<>1 && intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("APP_KEEPALIVED_ENABLE_SLAVE"))<>1){return "";}
    $e=explode(":",$iface);
    $id=isset($e[1])?intval($e[1]):0;
    if($id<=0){return "";}
    $q=new lib_sqlite("/home/artica/SQLITE/interfaces.db");
    $l=$q->mysqli_fetch_array("SELECT ipaddr FROM nics_virtuals WHERE ID=$id");
    return (is_array($l) && isset($l["ipaddr"]) && strlen($l["ipaddr"])>3)?strval($l["ipaddr"]):"";
}

// Modele : les ports tels que le demon reverse-proxy les applique (Certificates.go,
// Frontends.go), pas seulement les cases cochees.
function np_model($serviceid):array{
    $site=new socksngix($serviceid);
    $global=new socksngix(0);
    $row=fetch_nginx_service($serviceid);
    $type=isset($row["type"])?intval($row["type"]):0;
    $hasCert=strlen(strval($site->GET_INFO("ssl_certificate")))>=3;
    $h2mod=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("nginxHTTP2Module"))==1;
    $ppGlobal=intval($global->GET_INFO("NginxProxyProtocol"))==1;
    $ppLB=strlen(trim(strval($global->GET_INFO("set_real_ip_from"))))>0;
    $ppSite=intval($site->GET_INFO("NginxProxyProtocol"))==1;
    $redir80=intval($site->GET_INFO("Redirect80To443"))==1;
    $harmp=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HarmpClientType"))<>0;

    $cache=$site->GetCache();
    $out=isset($cache["OutGoingInterface"])?trim(strval($cache["OutGoingInterface"])):"";
    if(strlen($out)<3){$out="";}

    $ports=array();
    $siteH2=false;
    foreach(fetch_stream_ports_for_service($serviceid) as $l){
        $port=intval($l["port"]);
        if($port==0){continue;}
        $o=$GLOBALS["CLASS_SOCKETS"]->unserializeb64(isset($l["options"])?$l["options"]:"");
        if(!is_array($o)){$o=array();}
        $p=array(
            "id"=>intval($l["ID"]),
            "port"=>$port,
            "iface"=>$harmp?"":trim(strval($l["interface"])),
            "ssl_opt"=>intval(isset($o["ssl"])?$o["ssl"]:0)==1,
            "h2_opt"=>intval(isset($o["http2"])?$o["http2"]:0)==1,
            "pp_opt"=>intval(isset($o["proxy_protocol"])?$o["proxy_protocol"]:0)==1,
        );
        if($port==80){$p["ssl_opt"]=false;}
        if($port==443){$p["ssl_opt"]=true;}
        if($type==5){$p["ssl_opt"]=false;$p["h2_opt"]=false;$p["pp_opt"]=false;}
        if(!$p["ssl_opt"]){$p["h2_opt"]=false;}
        $p["ssl"]=$p["ssl_opt"] && $hasCert;
        // http2 est une directive du serveur : un seul port TLS qui la demande l'active pour tous.
        if($p["ssl"] && $p["h2_opt"] && $h2mod){$siteH2=true;}
        $p["skipped"]=($port==80 && $redir80 && $type<>5);
        $p["nic"]=np_nic($p["iface"]);
        $ports[]=$p;
    }
    $sslRequested=false;
    foreach($ports as $i=>$p){
        if($p["ssl_opt"]){$sslRequested=true;}
        $ports[$i]["h2"]=$p["ssl"] && $siteH2;
        $ports[$i]["pp_wanted"]=($p["pp_opt"] || $ppSite) && $type<>5;
        $ports[$i]["pp"]=$ports[$i]["pp_wanted"] && $ppGlobal && $ppLB;
    }
    return array(
        "id"=>intval($serviceid),"type"=>$type,"has_cert"=>$hasCert,"h2mod"=>$h2mod,
        "pp_global"=>$ppGlobal,"pp_lb"=>$ppLB,"pp_site"=>$ppSite,"redir80"=>$redir80,"ssl_requested"=>$sslRequested,
        "ports"=>$ports,
        "out"=>np_nic($out),"out_transparent"=>intval(isset($cache["OutGoingTransparent"])?$cache["OutGoingTransparent"]:0)==1,
        "can_write"=>np_can_write(),
    );
}

function np_actions($m):string{
    $tpl=new template_admin();$tpl->CLUSTER_CLI=true;
    $page=CurrentPageName();
    $id=$m["id"];
    $b=array();
    $b[]=array("Loadjs('$page?port-js=0&serviceid=$id&md5=');",ico_plus,"{new_entry}","AsSystemWebMaster");
    return "<div class='np-actions'>".$tpl->th_buttons($b)."</div>";
}
function np_nic_line($n):string{
    $parts=array();
    if($n["iface"]<>""){$parts[]=np_h($n["iface"]);}
    if($n["name"]<>"" && $n["name"]<>$n["iface"]){$parts[]=np_h($n["name"]);}
    if($n["network"]<>""){$parts[]=np_h($n["network"]);}
    if($n["vip"]){$parts[]="{np_virtual_ip}";}
    return implode(" &middot; ",$parts);
}
function np_outgoing_card($m):string{
    $page=CurrentPageName();
    $id=$m["id"];
    $n=$m["out"];
    $tr="";
    if($m["out_transparent"]){
        $tr="<span class='np-pill np-info' title=\"{np_transparent_tip}\"><i class='fa-solid fa-eye-slash' aria-hidden='true'></i>{transparent}</span>";
    }
    if($n["iface"]==""){
        $val="<span class='np-out-def'>{np_default_route}</span>";
    }else{
        $ip=$n["ip"]<>""?"<span class='np-mono np-out-ip'>".np_h($n["ip"])."</span>":"";
        $val="$ip<span class='np-out-nic'>".np_nic_line($n)."</span>";
        if(!$n["found"]){$val.="<span class='np-warn-txt'><i class='fa-solid fa-triangle-exclamation' aria-hidden='true'></i>{np_out_missing}</span>";}
    }
    if($m["can_write"]){
        $btn="<button type='button' class='btn btn-info abt abt-c2 abt-sm' OnClick=\"Loadjs('$page?outgoing=$id');\"><i class='fa-solid fa-pen-to-square' aria-hidden='true'></i><span>{np_modify}</span></button>";
    }else{
        $btn="<button type='button' class='btn abt abt-c2 abt-sm abt-l' aria-disabled='true'><i class='fa-solid fa-lock' aria-hidden='true'></i><span>{np_modify}</span></button>";
    }
    return "<section class='np-card np-out'><span class='np-out-ico'><i class='fa-solid fa-ethernet' aria-hidden='true'></i></span><div class='np-out-mid'><div class='np-caps'>{outgoing_interface}$tr</div><div class='np-out-val'>$val</div></div><div>$btn</div></section>";
}
function np_banner_nocert($m):string{
    if($m["has_cert"] || !$m["ssl_requested"]){return "";}
    return "<div class='np-banner np-banner-warn' role='status'><i class='fa-solid fa-triangle-exclamation' aria-hidden='true'></i><span>{np_banner_nocert}</span></div>";
}
function np_banner_noport($m):string{
    if(count($m["ports"])>0 || $m["type"]==14){return "";}
    return "<div class='np-banner np-banner-info' role='status'><i class='fa-solid fa-circle-info' aria-hidden='true'></i><span>{np_banner_noport}</span></div>";
}
// Pastille d'une option demandee mais que le serveur n'applique pas : texte + raison.
function np_pill_off($label,$reason):string{
    return "<span class='np-pill np-off'>$label &ndash; {np_not_applied}: $reason</span>";
}
function np_proto_pills($m,$p):string{
    $h=array();
    if($p["ssl"]){
        $h[]="<span class='np-pill np-ok'><i class='fa-solid fa-lock' aria-hidden='true'></i>HTTPS</span>";
    }else{
        $h[]="<span class='np-pill np-neutral'><i class='fa-solid fa-globe' aria-hidden='true'></i>HTTP</span>";
        if($p["ssl_opt"]){$h[]=np_pill_off("HTTPS","{np_r_nocert}");}
    }
    if($p["h2"]){
        $h[]="<span class='np-pill np-info'><i class='fa-solid fa-bolt' aria-hidden='true'></i>HTTP/2</span>";
    }elseif($p["h2_opt"] && $p["ssl"] && !$m["h2mod"]){
        $h[]=np_pill_off("HTTP/2","{np_r_h2mod}");
    }
    if($p["pp"]){
        $h[]="<span class='np-pill np-violet'><i class='fa-solid fa-right-left' aria-hidden='true'></i>PROXY protocol</span>";
    }elseif($p["pp_wanted"]){
        $h[]=np_pill_off("PROXY protocol",$m["pp_global"]?"{np_r_nolb}":"{np_r_ppglobal}");
    }
    if($p["skipped"]){
        $h[]="<span class='np-pill np-off'>{np_skipped_redirect}</span>";
    }
    return "<div class='np-pills'>".implode("",$h)."</div>";
}
function np_address($p):string{
    $proto=$p["ssl"]?"https":"http";
    $host=$p["iface"]=="" ? "*" : ($p["nic"]["ip"]<>"" ? $p["nic"]["ip"] : $p["iface"]);
    if(strpos($host,":")!==false && $host<>$p["iface"]){$host="[$host]";}
    return "$proto://$host:".$p["port"];
}
function np_delete_button($m,$p,$addr):string{
    $page=CurrentPageName();
    $aria="{np_delete} ".np_h($addr);
    if(!$m["can_write"]){
        return "<button type='button' class='btn abt abt-g abt-ico abt-l' aria-disabled='true' aria-label=\"$aria\"><i class='fa-solid fa-lock' aria-hidden='true'></i></button>";
    }
    return "<button type='button' class='btn abt abt-g abt-ico np-del' aria-label=\"$aria\" title=\"$aria\" OnClick=\"Loadjs('$page?delete={$p["id"]}');\"><i class='fa-solid fa-trash-can' aria-hidden='true'></i></button>";
}
function np_port_row($m,$p):string{
    $page=CurrentPageName();
    $addr=np_address($p);
    $ico=$p["ssl"]?"<i class='fa-solid fa-lock np-ico-ok' aria-hidden='true'></i>":"<i class='fa-solid fa-lock-open np-ico-off' aria-hidden='true'></i>";
    if($p["iface"]==""){
        $sub="{np_all_interfaces}";
    }else{
        $sub=np_nic_line($p["nic"]);
        if(!$p["nic"]["found"]){$sub.=" <span class='np-warn-txt'><i class='fa-solid fa-triangle-exclamation' aria-hidden='true'></i>{np_iface_missing}</span>";}
    }
    $link="<button type='button' class='np-addr np-mono' OnClick=\"Loadjs('$page?port-js={$p["id"]}&serviceid={$m["id"]}');\">".np_h($addr)."</button>";
    $cls=$p["skipped"]?" class='np-skipped'":"";
    $h=array();
    $h[]="<tr$cls>";
    $h[]="<td class='np-c-ico'>$ico</td>";
    $h[]="<td class='np-c-addr'>$link<div class='np-sub'>$sub</div></td>";
    $h[]="<td class='np-c-port'>{$p["port"]}</td>";
    $h[]="<td class='np-c-proto'>".np_proto_pills($m,$p)."</td>";
    $h[]="<td class='np-c-del'>".np_delete_button($m,$p,$addr)."</td>";
    $h[]="</tr>";
    return implode("",$h);
}
function np_ports_empty($m):string{
    $page=CurrentPageName();
    $btn="";
    if($m["can_write"]){
        $btn="<button type='button' class='btn btn-primary abt abt-s' OnClick=\"Loadjs('$page?port-js=0&serviceid={$m["id"]}&md5=');\"><i class='".ico_plus."' aria-hidden='true'></i><span>{new_entry}</span></button>";
    }
    return "<div class='np-empty'><span class='np-empty-big'><i class='fa-solid fa-plug' aria-hidden='true'></i></span><p>{np_no_port}</p>$btn</div>";
}
function np_ports_table($m):string{
    $h=array();
    $h[]="<section class='np-card np-tbl-card' aria-label=\"{ports}\">";
    if(count($m["ports"])==0){
        $h[]=np_ports_empty($m);
        $h[]="</section>";
        return implode("",$h);
    }
    $h[]="<table class='np-tbl'><thead><tr><th class='np-c-ico'></th><th class='np-c-addr'>{np_address}</th><th class='np-c-port'>{np_port}</th><th class='np-c-proto'>{np_protocol}</th><th class='np-c-del'></th></tr></thead><tbody>";
    foreach($m["ports"] as $p){$h[]=np_port_row($m,$p);}
    $h[]="</tbody></table>";
    $n=count($m["ports"]);
    $h[]="<div class='np-foot'>".($n==1?"{np_one_port}":np_t("np_ports_count",array("%s"=>$n)))."</div>";
    $h[]="</section>";
    return implode("",$h);
}
function np_style():string{
    $f=array();
    $f[]="<style>";
    $f[]=".np-wrap{display:flex;flex-direction:column;gap:16px;padding:12px 0 8px;color:#0f172a;font-size:14px}";
    $f[]=".np-wrap *{box-sizing:border-box}";
    $f[]=".np-actions .btn-group{display:flex;flex-wrap:wrap;gap:8px}";
    $f[]=".np-card{background:#fff;border:1px solid #e2e8f0;border-radius:10px;box-shadow:0 1px 2px rgba(15,23,42,.06)}";
    $f[]=".np-mono{font-family:monospace}";
    $f[]=".np-out{display:flex;align-items:center;gap:16px;min-height:88px;padding:16px}";
    $f[]=".np-out-ico{width:40px;height:40px;flex-shrink:0;border-radius:8px;background:#f0fdfa;border:1px solid #ccfbf1;color:#0e7490;font-size:18px;display:flex;align-items:center;justify-content:center}";
    $f[]=".np-out-mid{flex:1;min-width:0;display:flex;flex-direction:column;gap:4px}";
    $f[]=".np-caps{display:flex;align-items:center;gap:8px;font-size:12px;font-weight:600;text-transform:uppercase;letter-spacing:.04em;color:#64748b}";
    $f[]=".np-caps .np-pill{text-transform:none;letter-spacing:0}";
    $f[]=".np-out-val{display:flex;flex-wrap:wrap;align-items:baseline;gap:4px 12px}";
    $f[]=".np-out-ip{font-size:15px;font-weight:600;color:#0f172a}";
    $f[]=".np-out-nic,.np-out-def{font-size:13px;color:#475569}";
    $f[]=".np-warn-txt{display:inline-flex;align-items:center;gap:6px;font-size:13px;color:#b45309}";
    $f[]=".np-banner{display:flex;align-items:flex-start;gap:10px;padding:12px 16px;border-radius:8px;border:1px solid;font-size:13px;line-height:1.5}";
    $f[]=".np-banner>svg,.np-banner>i{margin-top:3px;flex-shrink:0}";
    $f[]=".np-banner-warn{background:#fffbeb;border-color:#fde68a;color:#92400e}";
    $f[]=".np-banner-info{background:#f8fafc;border-color:#e2e8f0;color:#475569}";
    $f[]=".np-tbl-card{overflow:hidden}";
    $f[]=".np-tbl{width:100%;border-collapse:collapse;table-layout:fixed}";
    $f[]=".np-tbl th{height:40px;padding:0 16px;background:#f8fafc;border-bottom:1px solid #e2e8f0;font-size:12px;font-weight:600;text-transform:uppercase;letter-spacing:.04em;color:#64748b;text-align:left}";
    $f[]=".np-tbl td{padding:12px 16px;border-bottom:1px solid #f1f5f9;vertical-align:middle}";
    $f[]=".np-tbl tbody tr{transition:background-color .15s}";
    $f[]=".np-tbl tbody tr:hover{background:#f8fafc}";
    $f[]=".np-tbl .np-c-ico{width:48px;text-align:center;font-size:16px}";
    $f[]=".np-tbl .np-c-port{width:96px;text-align:right}";
    $f[]=".np-tbl td.np-c-port{font-weight:600;font-size:15px;font-variant-numeric:tabular-nums}";
    $f[]=".np-tbl .np-c-proto{width:380px}";
    $f[]=".np-tbl .np-c-del{width:64px;text-align:right}";
    $f[]=".np-ico-ok{color:#047857}.np-ico-off{color:#64748b}";
    $f[]=".np-addr{display:inline-block;max-width:100%;padding:0;border:0;background:none;font-size:15px;font-weight:600;color:#0f172a;text-align:left;cursor:pointer;word-break:break-all}";
    $f[]=".np-addr:hover{text-decoration:underline}";
    $f[]=".np-addr:focus-visible{outline:2px solid #2563eb;outline-offset:2px;border-radius:2px}";
    $f[]=".np-sub{margin-top:2px;font-size:13px;color:#475569}";
    $f[]=".np-skipped .np-addr,.np-skipped .np-c-port{color:#94a3b8}";
    $f[]=".np-pills{display:flex;flex-wrap:wrap;gap:6px}";
    $f[]=".np-pill{display:inline-flex;align-items:center;gap:6px;padding:3px 10px;border-radius:999px;border:1px solid transparent;font-size:13px;font-weight:600;line-height:1.35}";
    $f[]=".np-ok{background:#ecfdf5;color:#047857;border-color:#a7f3d0}";
    $f[]=".np-info{background:#eff6ff;color:#1d4ed8;border-color:#bfdbfe}";
    $f[]=".np-violet{background:#f2f6f6;color:#005447;border-color:#bfd4d1}";
    $f[]=".np-neutral{background:#f1f5f9;color:#475569;border-color:#e2e8f0}";
    $f[]=".np-off{background:#f8fafc;color:#475569;border:1px dashed #cbd5e1;font-weight:500;border-radius:8px}";
    $f[]=".np-tbl .np-del,.np-tbl .np-del>svg{color:#94a3b8}";
    $f[]=".np-tbl .np-del:hover,.np-tbl .np-del:focus-visible,.np-tbl .np-del:hover>svg,.np-tbl .np-del:focus-visible>svg{color:#b91c1c;background:#fef2f2}";
    $f[]=".np-foot{height:40px;display:flex;align-items:center;padding:0 16px;font-size:13px;color:#64748b}";
    $f[]=".np-empty{display:flex;flex-direction:column;align-items:center;justify-content:center;gap:12px;min-height:160px;padding:24px;color:#475569}";
    $f[]=".np-empty-big{font-size:40px;line-height:1;color:#cbd5e1}";
    $f[]=".np-empty p{margin:0;font-size:14px}";
    $f[]="@media (max-width:1199px){.np-tbl .np-c-port{display:none}.np-tbl .np-c-proto{width:300px}}";
    $f[]="@media (prefers-reduced-motion:reduce){.np-tbl tbody tr{transition:none}}";
    $f[]="</style>";
    return implode("",$f);
}
