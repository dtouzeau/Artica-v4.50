<?php
if(isset($_GET["VERBOSE"])){$GLOBALS["VERBOSE"]=true;}
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");
$GLOBALS["CLASS_SOCKETS"]=new sockets();
if(isset($_GET["delete"])){delete();exit;}
if(isset($_GET["resend"])){resend();exit;}
if(isset($_GET["trust"])){trust();exit;}

$request_uri=$_SERVER["REQUEST_URI"];
$request_uri=str_replace("/api/rest/safe/", "", $request_uri);
$f=explode("/",$request_uri);

if(preg_match("#^([0-9a-z]+)#",$_GET["msgid"],$re)){$_GET["msgid"]=$re[1];}
$urlmd5=$_GET["msgid"];
if($GLOBALS["VERBOSE"]){echo "urlmd5=$urlmd5<b>";}


$q=new postgres_sql();

$ligne=$q->mysqli_fetch_array("SELECT msgid,sender FROM mimedefang_urls WHERE urlmd5='$urlmd5'");
$msgid=$ligne["msgid"];
$sender=$ligne["sender"];

buildpage(null,$msgid,$sender);

function trust(){
    $tpl=new template_admin();
    $_POST["mailfrom"]=$_GET["trust"];
    $_POST["mailto"]="*";
    $new_zmd5=md5($_POST["mailfrom"].$_POST["mailto"]);
    $date=date("Y-m-d H:i:s");
    $q=new postgres_sql();
    $sqladd="INSERT INTO autowhite(zmd5,zdate,mailfrom,mailto) VALUES ('$new_zmd5','$date','{$_POST["mailfrom"]}','{$_POST["mailto"]}') ON CONFLICT DO NOTHING";
    $q->QUERY_SQL($sqladd);
    if(!$q->ok){
        $tpl->js_display_results("$q->mysql_error",true);
        return;
    }

    $tpl->js_display_results("{$_POST["mailfrom"]} {success}",true);


}

function resend(){

    $id=$_GET["resend"];
    $sock=new sockets();
    $sock->getFrameWork("mimedefang.php?urls-resend=$id");
    $tpl=new template_admin();
    $tpl->js_display_results("{check_your_inbox}");

}

function delete(){
    $q=new postgres_sql();
    $msgid=$_GET["delete"];
    $q->QUERY_SQL("DELETE FROM mimedefang_urls WHERE msgid='$msgid'");
    $q->QUERY_SQL("DELETE FROM mimedefang_msgurls WHERE msgid='$msgid'");

    echo "location.reload(true);\n";
}

function buildpage($content,$msgid,$sender){
    $t=time();
    $sock=new sockets();
    $page=CurrentPageName();
    $tpl=new template_admin();
    $LOCKED=false;
    $MimedefangUrlsCheckerTrustAutoWhitelist=intval($sock->GET_INFO("MimedefangUrlsCheckerTrustAutoWhitelist"));
    $MimeDefangAutoWhiteList=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("MimeDefangAutoWhiteList"));
    if($MimeDefangAutoWhiteList==0){$MimedefangUrlsCheckerTrustAutoWhitelist=0;}
    $delete_btn="<label class=\"btn btn btn-danger\" OnClick=\"Loadjs('$page?delete=$msgid')\"><i class='fas fa-trash-alt'></i> {delete_message} </label>";

    if($MimedefangUrlsCheckerTrustAutoWhitelist==1){
        $senderenc=urlencode($sender);
        $trust_sender=$tpl->_ENGINE_parse_body("{trust_sender}");
        $trust_sender=str_replace("%s",$sender,$trust_sender);
        $trustuser="<label class=\"btn btn btn-warning\" OnClick=\"Loadjs('$page?trust=$senderenc')\"><i class='fas fa-trash-alt'></i> $trust_sender </label>";

    }


    $title="<h2>Message $msgid {from} $sender</h2>";
    if($msgid==null){
        $title="<h2>{the_message_doent_exists}</h2>";
        $LOCKED=true;
        $delete_btn=null;
    }

    $smtp_urls_intro3=null;
    $q=new postgres_sql();


    if($msgid<>null){
        $results=$q->QUERY_SQL("SELECT * FROM mimedefang_urls WHERE msgid='$msgid' ORDER by phishing DESC");
        $numofmessages=pg_num_rows($results);

    $html[]="<table id='table-$t' class=\"footable table table-stripped\" data-page-size=\"100\" data-paging=\"true\">";
    $html[]="<thead>";
    $html[]="<tr>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>ID</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{status}</center></th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{urls}</center></th>";


    $html[]="</tr>";
    $html[]="</thead>";
    $html[]="<tbody>";
    $TRCLASS=null;


    while ($ligne = pg_fetch_assoc($results)) {
        $md=md5(serialize($ligne));
        if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
        $ttlmin=intval($ligne["ttlmin"]);
        $scanned=intval($ligne["scanned"]);
        $affected=intval($ligne["affected"]);
        $ttl_max=$ligne["ttlmax"];
        $sender=$ligne["sender"];
        $sender_text=$sender;
        $phishing=intval($ligne["phishing"]);
        if($phishing==1){$LOCKED=true;}
        $content_type=$ligne["content_type"];

        if(strlen($sender_text)>30){$sender_text=substr($sender_text,0,27)."...";}
        $url=$ligne["urlsource"];
        $url_text=$url;
        $id=$ligne["id"];

        if($affected==0){$scanned=0;}

        if($scanned==0){
            $LOCKED=true;
            if($ttlmin==0) {
                $icon = "<i class=\"fas fa-clock\"></i>";
                $label = "<span class=label>$icon&nbsp;{waiting}</span>";
                $url = "{not_available} ({$ligne["familysite"]})";

            }
            if($ttlmin>0){
                $icon = "<i class=\"fas fa-clock\"></i>";
                $label = "<span class='label label-warning'>$icon&nbsp;{waiting}</span>";
                $zdate=date("Y-m-d H:i:s",$ttlmin);
                $url="{not_available} ({$ligne["familysite"]})&nbsp".distanceOfTimeInWords(time(),$ttlmin,true). "&nbsp;<small>($zdate)</small>";


            }


        }else{

            if(!$LOCKED) {
                $urldest = $ligne["urldest"];
                if (strlen($urldest) > 50) {
                    $urldest = substr($urldest, 0, 47) . "...";
                }
                $icon = "<i class=\"fas fa-thumbs-up\"></i>";
                $label = "<span class='label label-primary'>$icon&nbsp;{success}</span>";
                $url = "<a href=\"{$ligne["urldest"]}\">$urldest</a> ($content_type)";
            }else{
                $icon = "<i class=\"fas fa-thumbs-up\"></i>";
                $label = "<span class='label label-danger'>$icon&nbsp;{locked}</span>";
                $url = $ligne["familysite"]."&nbsp;($content_type)";


            }


        }



        $html[]="<tr class='$TRCLASS' id='$md'>";
        $html[]="<td style='width:1%'>$id</td>";
        $html[]="<td style='width:1%'>$label</td>";
        $html[]="<td >$url</td>";
        $html[]="</tr>";


    }

    $html[]="</tbody>";
    $html[]="</table>";

    $html[]="<script>
	NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS)."
	$(document).ready(function() { $('#table-$t').footable( { \"filtering\": { \"enabled\": true }, \"sorting\": { \"enabled\": true } } ); });
</script>";
    }

    $ttl_max_text=null;
    if($ttl_max>0){
        $ttl_max_date=$tpl->time_to_date($ttl_max,true);
        $ttl_max_text=$tpl->_ENGINE_parse_body("{smtp_urls_ttl_max_text}");
        $ttl_max_text="<div class='alert alert-danger'>".str_replace("%s",$ttl_max_date,$ttl_max_text)."</div>";
    }
    $resend_btn=null;

    $smtp_urls_intro2=$tpl->_ENGINE_parse_body("{smtp_urls_intro2}");
    $smtp_urls_intro2=str_replace("%msgid",$msgid,$smtp_urls_intro2);
    $smtp_urls_intro2=str_replace("%num",$numofmessages,$smtp_urls_intro2);

    if(!$LOCKED) {
        $smtp_urls_intro3="<div class='alert alert-info'>".$tpl->_ENGINE_parse_body("{smtp_urls_intro3}")."</div>";



        $ligne2=$q->mysqli_fetch_array("SELECT id FROM mimedefang_msgurls WHERE msgid='$msgid'");
        $idMessage=intval($ligne2["id"]);
        if($idMessage>0) {
            $resend_btn = "<label class=\"btn btn btn-primary\" OnClick=\"Loadjs('$page?resend=$idMessage')\"><i class='fas fa-share-square'></i> {resend_mail} </label>";
        }

     }


    $INTRO="<p>{smtp_urls_intro}</p>
            <p>$smtp_urls_intro2</p>";

    if($msgid==null){$INTRO=null;}
    $jqueryToUse=$GLOBALS["CLASS_SOCKETS"]->jQueryToUse();
    $html2="<!DOCTYPE html>
<html>

<head>

    <meta charset=\"utf-8\">
    <meta name=\"viewport\" content=\"width=device-width, initial-scale=1.0\">

    <title>URLS | Message $msgid {from} $sender</title>
    <link rel=\"icon\" href=\"/ressources/templates/default/favicon.ico\" type=\"image/x-icon\" />
    <link rel=\"shortcut icon\" href=\"/ressources/templates/default/favicon.ico\" type=\"image/x-icon\" />
    <link href=\"/angular/bootstrap.min.css\" rel=\"stylesheet\">
    <link href=\"/angular/animate.css\" rel=\"stylesheet\">
    <link href=\"/font-awesome/css/all.min.css\" rel=\"stylesheet\">
    <link href=\"/angular/animate.css\" rel=\"stylesheet\">
    <link href=\"/angular/style.css\" rel=\"stylesheet\">
    <link href=\"/angular/fonts.css\" rel=\"stylesheet\">
    <link href=\"/angular/js/plugins/sweetalert/sweetalert.css\" rel=\"stylesheet\">
    <link href=\"/angular.css.php\" rel=\"stylesheet\">
    <script type=\"text/javascript\" language=\"javascript\" src=\"/mouse.js\"></script>
	<script type=\"text/javascript\" language=\"javascript\" src=\"/angular/js/jquery/$jqueryToUse\"></script>
	<script type=\"text/javascript\" language=\"javascript\" src=\"/js/jquery.Wload.js\"></script>
    <script type=\"text/javascript\" language=\"javascript\" src=\"/angular/font-awesome/js/all.js\"></script>
     <script type=\"text/javascript\" language=\"javascript\" src=\"/angular/js/bootstrap/bootstrap.min.js\"></script>
      <script type=\"text/javascript\" language=\"javascript\" src=\"/angular/js/plugins/sweetalert/sweetalert.min.js\"></script>
    <script type=\"text/javascript\" language=\"javascript\" src=\"/XHRConnection.js\"></script>
    <script type=\"text/javascript\" language=\"javascript\" src=\"/default.js\"></script>

</head>

<body class=\"gray-bg\">

<div class=\"lock-word animated fadeInDown\" style='padding-left:150px'>
    <span class='first-word'>LOCKED URLS</span>
</div>
    <div class=\"middle-box animated fadeInDown\" style='margin-top:200px;max-width:630px'>
        <div>
            $title
            $INTRO
            <div class=\"btn-group\" data-toggle=\"buttons\" style='margin-top:3px;margin-bottom:5px'>
            $trustuser
            $resend_btn
            $delete_btn
            </div>
            ".@implode($html,"\n")."
            $ttl_max_text
            $smtp_urls_intro3
        </div>
    </div>

    <!-- Mainly scripts -->

    

</body>

</html>";


    echo $tpl->_ENGINE_parse_body($html2);
}