<?php
if(!isset($_GET["template"])){die("DIE " .__FILE__." Line: ".__LINE__);}

$template=$_GET["template"];
$page=$_GET["page"];

/*
foreach (glob("ressources/templates/$template/css/*.css") as $filename) {
			//$datas=@file_get_contents("$filename");
			//$datas=str_replace("\n", " ", $datas);
			$css[]=$datas;
		}
*/
header("Content-type: text/css");	

if(preg_match("#; MSIE#",$_SERVER["HTTP_USER_AGENT"])){
		$ASIE=true;
}


		
$css[]="div .form{
  -moz-border-radius: 5px;
  border-radius: 5px;
  border:1px solid #DDDDDD;
  background:url(\"/img/gr-greybox.gif\") repeat-x scroll 0 0 #FBFBFA;
";

if(!$ASIE){
	$css[]="background: -moz-linear-gradient(center top , #F1F1F1 0px, #FFFFFF 45px) repeat scroll 0 0 transparent;
    background: -webkit-gradient(linear, center top, center bottom, from(#F1F1F1), to(#FFFFFF)) repeat scroll 0 0 transparent;
	background: -webkit-linear-gradient( #F1F1F1, #FFFFFF) repeat scroll 0 0 transparent;
	background: -o-linear-gradient(#F1F1F1, #FFFFFF) repeat scroll 0 0 transparent;
	background: -ms-linear-gradient(#F1F1F1, #ffffff) repeat scroll 0 0 transparent;
	background: linear-gradient(#F1F1F1, #ffffff) repeat scroll 0 0 transparent;
";
}
if($ASIE){
	$css[]="filter: progid:DXImageTransform.Microsoft.gradient(startColorstr='#F1F1F1', endColorstr='#ffffff');";
}
		
		
$css[]="background:-moz-linear-gradient(center top , #F1F1F1 0px, #FFFFFF 45px) repeat scroll 0 0 transparent;
  margin:5px;padding:5px;
  -webkit-border-radius: 5px;
  -o-border-radius: 5px;		
 -moz-box-shadow: 2px 2px 8px rgba(0, 0, 0, 0.6);
 -webkit-box-shadow: 2px 2px 8px rgba(0, 0, 0, 0.6);
 box-shadow: 2px 2px 8px rgba(0, 0, 0, 0.6);
}";		
		
		
echo @implode("\n", $css);