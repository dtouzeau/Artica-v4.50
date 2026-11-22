<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.external.ad.inc");
// Kit de tables awc_*. Inclusion INCONDITIONNELLE : dans le bootstrap
// if(!class_exists("sockets")) elle ne s'executerait jamais, et la page mourrait
// sur "Call to undefined function awc_table_style()" -- un spinner infini.
include_once(dirname(__FILE__)."/ressources/class.statistics_appliance.inc");
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}

if(isset($_GET["interfaces"])){content();exit;}
if(isset($_GET["search"])){search();exit;}
if(isset($_GET["popup"])){popup();exit;}
js();


function js(){
	$tpl=new template_admin();
	$page=CurrentPageName();
	//field-id=$id&Hash=$Encoded&field-id2=$id2&value=$DefaultValue&js=$jsafter
    $label=urlencode($_GET["label"]);
	// Pentest 2026-11-04 : label, page, name, field-id, field-id2, Hash et js etaient recopies sans encodage dans la page (XSS).
	$title=$tpl->javascript_parse_text("{select} ".req_html(req_html(req_html($_GET["label"] ?? ""))));
    $DefaultValue=urlencode($_GET["value"]);
    $u=array();
    foreach (array("page","name","field-id2","field-id","Hash","js") as $xk){$u[$xk]=req_url($_GET[$xk] ?? "");}
	$tpl->js_dialog6($title, "$page?popup=yes&page={$u["page"]}&name={$u["name"]}&field-id2={$u["field-id2"]}&field-id={$u["field-id"]}&Hash={$u["Hash"]}&value={$DefaultValue}&js={$u["js"]}&label=$label",500);
}

function popup(){
	$tpl=new template_admin();
	// Pentest 2026-11-04 : page, field-id, field-id2, name, js, label et Hash etaient recopies sans encodage dans la page (XSS).
	$page=preg_replace('#[^A-Za-z0-9_.-]#','',(string)($_GET["page"] ?? ""));
    $t=time();

    $fieldid2=preg_replace('#[^A-Za-z0-9_-]#','',(string)($_GET["field-id2"] ?? ""));
    $fieldid=preg_replace('#[^A-Za-z0-9_-]#','',(string)($_GET["field-id"] ?? ""));
    $SelectedValue=$_GET["value"];
    $name=req_js($_GET["name"] ?? "");
    $js=jsafter_sanitize(base64_decode((string)($_GET["js"] ?? "")));

    $html[]="</div>";
    $html[]="<div class='awc-wrap'><div class='awc-card'><div class='awc-scroll'>";
	$html[]="<table class='awc-tbl'>";
    $html[]="<thead>";
    $html[]="<tr>";
    $html[]="<th>&nbsp;</th>";
    $html[]="<th>".req_html($_GET["label"] ?? "")."</th>";
    $html[]="<th>{select}</th>";
    $html[]="</tr>";
    $html[]="</thead>";
    // L'id du <tbody> ancre awc_pager_js() : sans lui le pager rend vide et
	// toutes les lignes s'affichent d'un bloc, sans que rien ne le signale.
	$html[]="<tbody id='bsel-rows'>";
    $TRCLASS=null;
    $Hash=unserializeb64($_GET["Hash"]);

    foreach ($Hash as $key=>$value){
        $bt_color="btn-primary";

        if(trim(strtolower($SelectedValue))==trim(strtolower($key))) {
            $bt_color="btn-warning";
        }

        $idrow=md5($fieldid2.$value.$fieldid);
        $valuejs=req_js($tpl->javascript_parse_text($value));
        $valuehtml=$tpl->_ENGINE_parse_body(req_html($value));
        $key=req_js($key);
        $button_select=$tpl->button_autnonome("&nbsp;{select}",
            "select{$idrow}()",
            "fas fa-hand-pointer","",0,$bt_color,"small");
        $func[]="";
        $func[]="var X_{$idrow} = function (obj) {";
        $func[]="\tvar results=trim(obj.responseText);";
        $func[]="\tif(results.length>0){alert(results);return;}";
        $func[]="\tdialogInstance6.close();";
        $func[]="\t$js;";
        $func[]="}";
        $func[]="function select{$idrow}(){";
        $func[]="\tif(!document.getElementById('$fieldid')){alert('$fieldid no found!');return;}";
        $func[]="\tif(!document.getElementById('$fieldid2')){alert('$fieldid2 no found!');return;}";
        $func[]="\tdocument.getElementById('$fieldid').value=\"$key\";";
        $func[]="\tdocument.getElementById('$fieldid2').value=\"$valuejs\";";
        $func[]="\tvar XHR = new XHRConnection();";
        $func[]="\tXHR.appendData('$name','$key');";
        $func[]="\tXHR.sendAndLoad('$page', 'POST',X_{$idrow});";
        $func[]="}";
        $func[]="";

        $html[]="<tr id='$idrow'>";
        $html[]="<td style='width:1%' nowrap><i class=\"fas fa-info-circle\"></i></td>";
        $html[]="<td><strong>$valuehtml</strong></td>";
        $html[]="<td width=1% nowrap >$button_select</td>";
    }


    $html[]="</tbody></table></div>";
	$html[]="<div class='awc-foot'><span>$c {items}</span>";
	$html[]="<span class='awc-pg' id='bsel-pager'></span></div>";
	$html[]="</div></div>";
    $html[]="
	<script>
	NoSpinner();\n".$tpl->ICON_SCRIPTS_COMPILE();
    $html[]=@implode("\n",$func);
    $html[]="</script>";

    // Feuille et pager concatenes AUTOUR du parsing : la regex de
    // _ENGINE_parse_body lit chaque regle CSS et chaque bloc JS comme un jeton.
    echo awc_table_style().$tpl->_ENGINE_parse_body($html).awc_pager_js(25,"bsel-rows","bsel-pager");

}


