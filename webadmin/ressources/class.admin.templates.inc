<?php
ini_set("pgsql.auto_reset_persistent",1);
if(!isset($GLOBALS["OUTPUT"])){$GLOBALS["OUTPUT"]=false;}
if(!isset($GLOBALS["TITLENAME"])){$GLOBALS["TITLENAME"]="PostgresSQL";}

class admintpl{
    private $Title=null;
    private $Content=null;


    private function header():string{
        $theme="css/themes/theme-default/bemat-admin.min.css";
        $css[]="css/bootstrap.min.css";

        $html[] = "<!DOCTYPE html>";
        $html[] = "<!--[if lt IE 7]>      <html class=\"no-js lt-ie9 lt-ie8 lt-ie7\"> <![endif]-->";
        $html[] = "<!--[if IE 7]>         <html class=\"no-js lt-ie9 lt-ie8\"> <![endif]-->";
        $html[] = "<!--[if IE 8]>         <html class=\"no-js lt-ie9\"> <![endif]-->";
        $html[] = "<!--[if gt IE 8]><!--> <html class=\"no-js\"> <!--<![endif]-->";
        $html[] = "<head>";
        $html[] = "\t<meta charset=\"utf-8\">";
        $html[] = "\t<meta http-equiv=\"X-UA-Compatible\" content=\"IE=edge\">";
        $html[] = "\t<meta name=\"viewport\" content=\"width=device-width, initial-scale=1\">";
        $html[] = "\t<meta name=\"description\" content=\"\">";
        $html[] = "\t<title>$this->Title</title>";
        $html[] = "\t<link rel=\"shortcut icon\" href=\"favicon.ico\" type=\"image/x-icon\">";
        $html[] = "";
        foreach ($css as $cs) {
           $html[] = "\t<link href=\"$cs\" rel=\"stylesheet\">";
        }
        $html[] = "\t<link href=\"$theme\" rel=\"stylesheet\" id=\"theme-switcher\">";
        $html[] = "</head>";
        $html[] = "<body class=\"container-fluid\">";
        $html[] = "<!-- BODY -->";
        $html[]="<div id=\"splash-screen\" class=\"layout layout-align-center\">";
        $html[]="			<div class=\"logo-box logo-box-primary layout layout-column\">";
        $html[]="				<div class=\"logo\">";
        $html[]="					<b>B</b>";
        $html[]="				</div>";
        $html[]="				<div class=\"logo-label headline-inverse\">";
        $html[]="					<p>BEMAT</p>";
        $html[]="				</div>";
        $html[]="			</div>";
        $html[]="		</div>";
        $html[]="		<div id=\"page-wrapper\">";
        $html[]="			<aside id=\"left-content\" data-toggle=\"open\" data-default=\"open\" data-size=\"\">";
        $html[]="<header class=\"header-container\">";
        $html[]="    <div class=\"header-wrapper\">";
        $html[]="        <div id=\"header-brand\">";
        $html[]="            <div class=\"logo padding-left-2\">";
        $html[]="                <span class=\"logo-image\">B</span>";
        $html[]="                <span class=\"logo-text\">Bemat</span>";
        $html[]="			</div>";
        $html[]="		</div>";
        $html[]="	</div>";
        $html[]="</header>";
        $html[]="				<div id=\"sidebar-wrapper\">";
        $html[]="					<div id=\"userbox\">";
        $html[]="						<div id=\"useravatar\">";
        $html[]="							<div class=\"avatar-thumbnail\">";
        $html[]="    <img src=\"img/avatar/avatar_square_blue.png\" class=\"img-circle\"/>";
        $html[]="							</div>";
        $html[]="						</div>";
        $html[]="";
        $html[]="						<div id=\"userinfo\">";
        $html[]="							<div class=\"btn-group\">";
        $html[]="								<button type=\"button\" class=\"btn btn-default-bright btn-flat dropdown-toggle\" data-toggle=\"dropdown\" aria-haspopup=\"true\" aria-expanded=\"false\">John Doe <i class=\"material-icons\">arrow_drop_down</i>";
        $html[]="								</button>";
        $html[]="								<ul class=\"dropdown-menu\">";
        $html[]="									<li><a href=\"#\"><i class=\"material-icons\">person</i> <span data-i18n=\"userinfo.profile\">Your Profile</span></a></li>";
        $html[]="									<li><a href=\"#\"><i class=\"material-icons\">settings</i> <span data-i18n=\"userinfo.settings\">Settings</span></a></li>";
        $html[]="									<li class=\"divider-horizontal\"></li>";
        $html[]="									<li><a href=\"#\"><i class=\"material-icons\">lock</i> <span data-i18n=\"userinfo.lock\">Lock</span></a></li>";
        $html[]="									<li class=\"divider-horizontal\"></li>";
        $html[]="									<li><a href=\"#\"><i class=\"material-icons\">exit_to_app</i> <span data-i18n=\"userinfo.logout\">Log Out</span></a></li>";
        $html[]="								</ul>";
        $html[]="							</div>";
        $html[]="						</div>";
        $html[]="					</div> <!-- END: #userbox -->";
        return @implode("\n",$html);
    }


    public function BuildPage($title=null,$content=null){

        $this->Title=$title;
        $this->Content=$content;
        $headers=$this->header();


    }
    }