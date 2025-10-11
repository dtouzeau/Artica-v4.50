<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.ccurl.inc");

if(isset($_GET["popup"])){popup();exit;}
if(isset($_GET["table"])){table();exit;}
if(isset($_GET["view"])){view();exit;}
js();


function js(){
	$tpl=new template_admin();
	$title=$tpl->javascript_parse_text("{videos_help}");
	$page=CurrentPageName();
	$tpl->js_dialog6($title, "$page?popup=yes",900);
}

function popup(){
	$tpl=new template_admin();
	$t=time();
	$page=CurrentPageName();
	
	$html[]="<h1 class=ng-binding>{videos_help}</h1>";
	
	
	$html[]="<div class=\"input-group\" style='margin-top:20px'>
	<input type=\"text\" class=\"form-control\" value=\"{$_SESSION["YOUTUBE_SEARCH"]}\" placeholder=\"{search}\" id='search-this-$t' 
	OnKeyPress=\"javascript:zSearchInYoutubeEvent(event);\">
	<span class=\"input-group-btn\">
	<button style=\"text-transform: capitalize;\" class=\"btn btn-default\" type=\"button\" OnClick=\"javascript:zSearchInYoutube();\">Go!</button>
	</span>
	</div>
	";
	
	$html[]="<div id='youtube-search-div'></div>
	
	
	
	
<script>
function zSearchInYoutubeEvent(e){
	if(!checkEnter(e) ){return;}
	zSearchInSystemEvents();
}
	
function zSearchInYoutube(){
	var ss=encodeURIComponent(document.getElementById('search-this-$t').value);
	LoadAjax('youtube-search-div','$page?table=yes&t=$t&search='+ss);
}
	
function Start$t(){
	var ss=document.getElementById('search-this-$t').value;
	zSearchInYoutube();
}
Start$t();
</script>";
echo $tpl->_ENGINE_parse_body($html);
	
}

function table(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$_SESSION["YOUTUBE_SEARCH"]=$_GET["search"];
	$searchencod=urlencode($_GET["search"]);
	$curl=new ccurl("https://www.googleapis.com/youtube/v3/search?part=id%2Csnippet&maxResults=10&channelId=UC2NZbd1F9HcnSHSc7F40W1g&q=$searchencod&key=AIzaSyBs04_Fectn-vvp5dvF93m7A1wU1CgdZ_U");
	$curl->NoHTTP_POST=True;
	if(!$curl->get()){
		echo $tpl->_ENGINE_parse_body( "<div class='alert alert-danger'>$curl->error</div>");
		return;
	}

	
	$json=json_decode($curl->data);
	//var_dump($json);
	
	
	foreach ($json->items as $index=>$class){
		$title=$class->snippet->title;
		$description=$class->snippet->description;
		$thumbnails=$class->snippet->thumbnails->default->url;
		$videoId=trim($class->id->videoId);
		$publishedAt=strtotime($class->snippet->publishedAt);
		$Time=$tpl->time_to_date($publishedAt,true);
		if($videoId==null){continue;}
		
		$show=$tpl->js_show_video($title,$Time,$videoId);
		$show="s_PopUp('https://youtu.be/$videoId',1024,768)";
		
		$f[]="<div class=\"social-feed-box\" style='margin-top:10px'>";
		$f[]="	<div class=\"pull-right social-action dropdown\"></div>";
		$f[]="	<a class=\"pull-left\" href=\"\"><img src=\"$thumbnails\" alt=\"image\" class=img-thumbnail style='margin:5px'></a>";
		$f[]="	<div class=\"media-body\"><a href=\"#\">
					<H2 style='margin:0px'>$title</h2></a>
					<small class=\"text-muted\">$Time</small></div>";
		$f[]="";
		$f[]="<div class=\"social-body\">";
		$f[]="	<p style='margin-top:-15px'>$description</p>";
		$f[]="	<div class=\"btn-group\">";
		$f[]="		<button style=\"text-transform: capitalize;\" class=\"btn btn-white btn-xs\" OnClick=\"javascript:$show\"><i class=\"fab fa-youtube\"></i> {view_the_video}</button>";
		$f[]="   </div>";
		$f[]="</div>";
		$f[]="<div class=\"social-footer\">";
		$f[]="	<div class=\"social-comment\"></div>";
		$f[]="</div></div>";
	
	}
	
	
	

	
	
	echo $tpl->_ENGINE_parse_body($f);
	
	
	
	
}
function view(){
	$tpl=new template_admin();

	$tpl->js_display_results(@implode("", $html),true);
}