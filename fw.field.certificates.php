<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
include_once(dirname(__FILE__)."/ressources/class.squid.reverse.inc");
if(isset($_GET["search"])){popup();exit;}
if(isset($_GET["popup-search"])){popup_search();exit;}

js();

function js(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $name=$_GET["name"];
    $id=$_GET["id"];
    $value=$_GET["value"];
    $suffix=$_GET["suffix-form"];
    $NoRoots="";
    if(isset($_GET["NoRoots"])){
        $NoRoots="&NoRoots=yes";
    }
    $tpl->js_dialog13("{certificates}","$page?popup-search=yes&name=$name&id=$id&value=$value&suffix-form=$suffix$NoRoots");
}
function popup_search():bool{
    $name=$_GET["name"];
    $id=$_GET["id"];
    $value=$_GET["value"];
    $suffix=$_GET["suffix-form"];
    $page=CurrentPageName();
    $tpl=new template_admin();
    $NoRoots="";
    if(isset($_GET["NoRoots"])){
        $NoRoots="&NoRoots=yes";
    }
    echo $tpl->search_block($page,"","","","&name=$name&id=$id&value=$value&suffix-form=$suffix$NoRoots");
    return true;
}
function popup(){
    $tpl=new template_admin();
    $fieldid=$_GET["id"];
    $SelectedValue=$_GET["value"];
    $NoRoots=0;
    if(isset($_GET["NoRoots"])){
        $NoRoots=1;
    }
    $IncludeServerCert=0;
    $t=time();
    $html[]="</div>";
    $html[]="<table id='table-$t' class=\"table table-stripped\" data-page-size=\"20\" data-paging=\"true\">";
    $html[]="<thead>";
    $html[]="<tr>";
    $html[]="<th data-sortable=false>{certificates}</th>";
    $html[]="<th data-sortable=false>{select}</th>";
    $html[]="</tr>";
    $html[]="</thead>";
    $html[]="<tbody>";
    $TRCLASS=null;





    if($NoRoots==1){
        $IncludeServerCert=1;
    }

    $squid_reverse=new squid_reverse();
    $sslcertificates=$squid_reverse->ssl_certificates_list(false,$IncludeServerCert);
    $func=array();
    $search=$_GET["search"];
    if($search<>null) {
        $search = "*$search*";
        $search = str_replace("**", "*", $search);
        $search = str_replace("**", "*", $search);
        $search = str_replace("*",".*?",$search);

    }

    foreach ($sslcertificates as $key=>$value){

        if(strlen($search)>1){
            if(!preg_match("#$search#i",$key)){
                continue;
            }
        }

        if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
        $bt_color="btn-primary";
        $error="";
        $optsTxt="";
        $opts=array();
        if(trim(strtolower($SelectedValue))==trim(strtolower($key))) {
            $bt_color="btn-warning";
        }
        if(strlen($key)>1) {
            $keyEnc = urlencode($key);
            $json = json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/certificate/details/$keyEnc"));

            if(!$json->Status){
                $error="<br><i class='text-danger'>$json->Error</i>";
            }else{
                $Info=$json->Info;
                if(property_exists($Info,"OrganizationName")){
                    if($Info->OrganizationName=="Let`s Encrypt"){
                        $opts[]="<span class='label label-success'>Let`s Encrypt</span>";
                    }
                }

                if(property_exists($Info,"isCA")){
                    if($Info->isCA){
                        $opts[]="<span class='label label-success'>Root CA</span>";

                    }
                }
                if(property_exists($Info,"IsSelfSigned")){
                    if($Info->IsSelfSigned){
                        $opts[]="<span class='label label-warning'>{SelfSignedCert}</span>";
                    }
                }
                if(property_exists($Info,"ExpireDate")){
                    $expdate=distanceOfTimeInWords(time(),$Info->ExpireDate);
                        $opts[]="<br>{expire}: $expdate";
                    }
                }
                if(property_exists($Info,"OrganizationalUnitName")){
                    $opts[]="&nbsp;|&nbsp;<i>$Info->OrganizationalUnitName</i>";
                }
                if(property_exists($Info,"DNSNames")){
                    $uls=array();
                    $ico=ico_earth;
                    $CLEAN=array();
                    foreach ($Info->DNSNames as $dnsname) {
                        $CLEAN[$dnsname] = true;
                    }
                    foreach ($CLEAN as $dnsname=>$none) {
                        $uls[]="<div><i class='$ico'></i>&nbsp;$dnsname</div>";
                    }
                    if(count($uls)>0) {
                        $opts[] = @implode("",$uls);
                    }
                }
            }

        $idrow=md5($key.$value.$fieldid);
        $text=base64_encode($value);
        $valuehtml=$tpl->_ENGINE_parse_body($value);
        $button_select=$tpl->button_autnonome("&nbsp;{select}",
            "select$idrow()",
            "fas fa-hand-pointer","",0,$bt_color,"small");
        $func[]="";
        $func[]="function select$idrow(){";
        $func[]="\tif(!document.getElementById('$fieldid')){alert('$fieldid no found!');return;}";
        $func[]="\tif( document.getElementById('btnlabel-$fieldid')){";
        $func[]="\t\tdocument.getElementById('btnlabel-$fieldid').textContent = base64_decode('$text');";
        $func[]="\t}";
        $func[]="\tdocument.getElementById('$fieldid').value=\"$key\";";
        $func[]="\tdialogInstance13.close();";
        $func[]="}";
        $func[]="";
        if(count($opts)>0){
            $optsTxt="<div>".@implode(" ",$opts)."</div>";
        }
        $html[]="<tr class='$TRCLASS' id='$idrow'>";
        $html[]="<td style='width:99%' nowrap><div style='margin-bottom:5px'><strong style='font-size:16px'><i class=\"".ico_certificate."\"></i>&nbsp;$valuehtml</strong></div>$error$optsTxt</td>";
        $html[]="<td style='width:1%' nowrap >$button_select</td>";
    }


    $html[]="</tbody>";
    $html[]="</table>";
    $html[]="
	<script>
	NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS);
    $html[]=@implode("\n",$func);
    $html[]="</script>";

    echo $tpl->_ENGINE_parse_body($html);



}