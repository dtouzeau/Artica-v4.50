<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.external.ad.inc");
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}

if(isset($_GET["popup"])){popup();exit;}
if(isset($_GET["js"])){jsdiv();exit;}
if(isset($_GET["js-popup"])){js_popup();exit;}

js();

function js_popup(){
    $tpl=new template_admin();
    $page=CurrentPageName();
    $_GET["password"]=urlencode($_GET["password"]);
    $_GET["username"]=urlencode($_GET["username"]);
    $_GET["hostname"]=urlencode($_GET["hostname"]);
    $title=$tpl->javascript_parse_text("{browse} {active_directory} {ldap_suffix}");
    $tpl->js_dialog5($title, "$page?popup=yes&field-id={$_GET["field-id"]}&password={$_GET["password"]}&username={$_GET["username"]}&hostname={$_GET["hostname"]}&ssl={$_GET["ssl"]}",650);
}

function content(){
    $page=CurrentPageName();




    $html[]="<div id='browsesuffixdiv'></div>";
    $html[]="<script>";
    $html[]="alert('ok');";
    $html[]="";
    $html[]="</script>";


    echo @implode("\n",$html);


}

function jsdiv(){
    $page=CurrentPageName();
    $tpl=new template_admin();

    $please_fill_username=$tpl->javascript_parse_text("{please_fill} {username}");
    $please_fill_password=$tpl->javascript_parse_text("{please_fill} {password}");
    $please_fill_hostname=$tpl->javascript_parse_text("{please_fill} {hostname}");

    header("content-type: application/x-javascript");
    $suffixid=$_GET["suffixid"];
    $t=time();

    $possible_usernames[]="KerberosUsername";
    $possible_usernames[]="LDAP_DN";

    $possible_passwords[]="KerberosPassword";
    $possible_passwords[]="LDAP_PASSWORD";


    $possible_hosts[]="kerberosActiveDirectoryHost";
    $possible_hosts[]="ADNETIPADDR";
    $possible_hosts[]="LDAP_SERVER";


    $possible_ssl[]="LDAP_SSL";
    $possible_ssl[]="KerberosLDAPS";


    foreach ($possible_usernames as $uuid){
        $id=md5("$uuid$suffixid");
        $fuser[]="if( document.getElementById('$id') ) { return document.getElementById('$id').value;}";

    }
    foreach ($possible_passwords as $uuid){
        $id=md5("$uuid$suffixid");
        $pass[]="if( document.getElementById('$id') ) { return encodeURIComponent(document.getElementById('$id').value);}";

    }
    foreach ($possible_hosts as $uuid){
        $id=md5("$uuid$suffixid");
        $hosts[]="\tif( document.getElementById('$id') ) { ";
        $hosts[]="\t\t value=document.getElementById('$id').value;";
        $hosts[]="\t\t if(value.length > 2){ return value; }";
        $hosts[]="\t}";

    }

    $ssl=array();
    foreach ($possible_ssl as $uuid){
        $id=md5("$uuid$suffixid");
        $ssl[]="if( document.getElementById('$id') ) { return document.getElementById('$id').checked;}";

    }



    $html[]="function username$t(){";
    $html[]=@implode("\n",$fuser);
    $html[]="return '';";
    $html[]="}";
    $html[]="function pass$t(){";
    $html[]=@implode("\n",$pass);
    $html[]="return '';";
    $html[]="}";
    $html[]="function host$t(){";
    $html[]=@implode("\n",$hosts);
    $html[]="return '';";
    $html[]="}";

    $html[]="function ssl$t(){";
    $html[]=@implode("\n",$ssl);
    $html[]="return '0';";
    $html[]="}";

    $html[]="function final$t(){";
    $html[]="\tvar username='';";
    $html[]="\tvar password='';";
    $html[]="\tvar hostname='';";
    $html[]="\tvar use_ssl='0';";
    $html[]="\tusername=username$t();";
    $html[]="\tpassword=pass$t();";
    $html[]="\thostname=host$t();";
    $html[]="\tuse_ssl=ssl$t();";
    $html[]="\tif( username.length==0 ){ alert('$please_fill_username'); return; }";
    $html[]="\tif( password.length==0 ){ alert('$please_fill_password'); return; }";
    $html[]="\tif( hostname.length==0 ){ alert('$please_fill_hostname'); return; }";

    $html[]="\tLoadjs('$page?js-popup=yes&field-id={$_GET["field-id"]}&username='+username+'&password='+password+'&hostname='+hostname+'&ssl='+use_ssl);";
    $html[]="}";
    $html[]="final$t();";
    echo @implode("\n",$html);

}

function UsSSLToInt($value):int{
    if(is_null($value)){
        return 0;
    }
    $UseSSL=0;
    if(is_string($value)){
        if($value=="true"){
            return 1;
        }
    }
    if(is_bool($value)){
        if($value){
            return 1;
        }
    }
    if(is_numeric($value)){
        return intval($value);
    }
    return $UseSSL;

}
function popup(){

    $tpl=new template_admin();
    $t=time();

    $fieldid=$_GET["field-id"];
    $username=$_GET["username"];
    $password=url_decode_special_tool($_GET["password"]);
    $hostname=$_GET["hostname"];
    $use_ssl=$_GET["ssl"];
    $UseSSL=UsSSLToInt($use_ssl);

    $Pattern["username"]=$username;
    $Pattern["password"]=$password;
    $Pattern["hostname"]=$hostname;
    $Pattern["ssl"]=$UseSSL;
    $Token=urlencode(base64_encode(serialize($Pattern)));

    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/activedirectory/finddse/$Token"));
    if(!$json->Status){
        echo $tpl->_ENGINE_parse_body($tpl->div_error("Use SSL=$UseSSL/{$_GET["ssl"]}<br>$json->Error"));
        return;
    }


    $TRCLASS=null;

    $html[]="</div>";
    $html[]="<table id='table-$t' class=\"footable table table-stripped\" data-page-size=\"100\" data-paging=\"true\">";
    $html[]="<thead>";
    $html[]="<tr>";
    $html[]="<th data-sortable=false>&nbsp;</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{suffix}</th>";
    $html[]="<th data-sortable=false>{select}</th>";
    $html[]="</tr>";
    $html[]="</thead>";
    $html[]="<tbody>";

    foreach ($json->Info as $suffix){
        if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
        $idrow=md5($suffix);
//<i class="fas fa-hand-pointer"></i>
        $button_select=$tpl->button_autnonome("&nbsp;{select}",
            "select{$idrow}()",
            "fas fa-hand-pointer","",0,"btn-primary","small");

        $func[]="function select{$idrow}(){";
        $func[]="\tif(!document.getElementById('$fieldid')){alert('$fieldid no found!');return;}";
        $func[]="\tdocument.getElementById('$fieldid').value=\"$suffix\";";
        $func[]="\tdialogInstance5.close();";
        $func[]="}";


        $html[]="<tr class='$TRCLASS' id='$idrow'>";
        $html[]="<td width=1% nowrap><i class=\"fas fa-info-circle\"></i></td>";
        if(preg_match("#^DC#i",$suffix)){
            $html[]="<td><strong>$suffix</strong></td>";
        }else{
            $html[]="<td>$suffix</td>";
        }

        $html[]="<td width=1% nowrap >$button_select</td>";
    }


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
	NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS)."
	$(document).ready(function() { $('#table-$t').footable( { \"filtering\": { \"enabled\": true }, \"sorting\": { \"enabled\": true },\"paging\": { \"size\": {$GLOBALS["FOOTABLE_PSIZE"]} } } ); });";
    $html[]=@implode("\n",$func);
$html[]="</script>";

    echo $tpl->_ENGINE_parse_body($html);

}

