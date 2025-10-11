<?php
$GLOBALS["VERBOSE"]=false;
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"]))include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");
$GLOBALS["CLASS_SOCKETS"]=new sockets();
include_once(dirname(__FILE__).'/ressources/class.langages.inc');


page();

function page(){
    $tpl=new template_admin();
    $page=CurrentPageName();
    $addong=null;
    $title=$tpl->_ENGINE_parse_body("{WELCOME_ON_ARTICA_PROJECT}");
    $jqueryToUse=$GLOBALS["CLASS_SOCKETS"]->jQueryToUse();
    $HEAD=" ";

    $f[]="<!DOCTYPE html>
<html>

<head>
    <meta charset=\"utf-8\">
    <meta name=\"viewport\" content=\"width=device-width, initial-scale=1.0\">
    <meta http-equiv=\"refresh\" content=\"11;/wizard/wiz.wizard.php\">
    <title>$title</title>

    <link href=\"/angular/bootstrap.min.css\" rel=\"stylesheet\">
    <link href=\"/angular/font-awesome/css/all.min.css\" rel=\"stylesheet\">
    <link rel=\"preload\" href=\"/angular/js/jquery/$jqueryToUse\" as=\"script\">
    <link href=\"/angular/animate.css\" rel=\"stylesheet\">
    <link href=\"/angular/style.css\" rel=\"stylesheet\">
    <script type=\"text/javascript\" language=\"javascript\" src=\"/angular/js/jquery/$jqueryToUse\"></script>

</head>
";


    $f[]="<body class=\"gray-bg\">";
    $f[]="<center style='margin:20px'><h3 class=center><a href=\"/wizard/wiz.wizard.php\">&laquo;Skip Introduction&raquo;</h3></center>";
    $f[]="<video width=\"480\" height=\"320\" autoplay='autoplay' preload playsinline id='animmp4'>";
    $f[]="<source src=\"/img/anim.mp4\" type=\"video/mp4\">";
    $f[]="</video>
    
    
    
	</body>
<script>
function toggleMute() {

  var video=document.getElementById(\"animmp4\");
  if(video.muted){
    video.muted = false;
    video.play();
  } else {
    video.muted = true;
    video.play();
  }

}

$(document).ready(function(){
  setTimeout(toggleMute,2000);
})

</script>
</html>
";

    echo @implode("\n", $f);

}