<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}

    $ArticaBackGroundColor=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ArticaBackGroundColor"));
    $ArticaBackGroundBodyColor=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ArticaBackGroundBodyColor"));
    $ArticaFontColor=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ArticaFontColor"));
    if($ArticaBackGroundColor==null){$ArticaBackGroundColor="#ffffff";}
    if($ArticaBackGroundBodyColor==null){$ArticaBackGroundBodyColor="#f3f3f4";}
    if($ArticaFontColor==null){$ArticaFontColor="#676a6c";}

header("Content-type: text/css");
if(!isset($_COOKIE["userfont"])){$_COOKIE["userfont"]=null;}
$html="
body {";
if($_COOKIE["userfont"]==null){
	$fontFamily="font-family: 'lato','Trebuchet MS', 'Helvetica', sans-serif;";
	$html=$html.$fontFamily;
}else{
	if($_COOKIE["userfont"]=="standard"){
		$fontFamily="font-family: \"open sans\",\"Helvetica Neue\",Helvetica,Arial,sans-serif;";
		$html=$html.$fontFamily;
	}else{
		$fontFamily="font-family: '{$_COOKIE["userfont"]}','Trebuchet MS', 'Helvetica', sans-serif;";
		$html=$html.$fontFamily;
	}
}
$html=$html."	font-size: 13px;
}

h1, h2, h3, h4, h5, h6 {
    font-weight: normal;
}
		
.ui-menu-item-wrapper{
	font-weight: bolder;
	$fontFamily
}

.ng-binding{
	font-weight: bolder;
}

.center{
	vertical-align:middle;	
	text-align:center;	

}
.table > thead > tr > th, .table > tbody > tr > th, .table > tfoot > tr > th, .table > thead > tr > td, .table > tbody > tr > td, .table > tfoot > tr > td {
  vertical-align:middle !important;	
}
		
.jstree-node, .jstree-children, .jstree-container-ul {
	background-color:#FFFFFF;
}

.labelform{
	color: $ArticaFontColor;
	font-size: 14px;
	font-weight: bold; 		
}

.labelform > a {
	color: $ArticaFontColor;
	font-size: 14px;
	font-weight: bold; 
	text-decoration: underline #7C7777;
	text-decoration-style: dotted;
	display:block;
}

.labelform > a:hover {
	color: black;
	font-size: 14px;
	font-weight: bold; 
	text-decoration: underline black;
	display:block;
}


.labelform_disabled{
	color: #D6D6D6;
	font-weight: normal;		
}
.labelform_disabled > a {
	color: #D6D6D6;
	font-weight: normal; 
	text-decoration:none;
	border-bottom:0px dotted #7C7777;
}
.labelform::first-letter {
    text-transform: capitalize;
}	
		
.fileinput-button {
  position: relative;
  overflow: hidden;
  display: inline-block;
}
.fileinput-button input {
  position: absolute;
  top: 0;
  right: 0;
  margin: 0;
  opacity: 0;
  -ms-filter: 'alpha(opacity=0)';
  font-size: 200px !important;
  direction: ltr;
  cursor: pointer;
}
	
.rowDisabled{
	color:#AFAFAF;
}
.rowDisabled a{
	color:#AFAFAF;
	
}
.rowDisabled a:hover{
	color:#337AB7;
	
}		
		
		
/* Fixes for IE < 8 */
@media screen\9 {
  .fileinput-button input {
    filter: alpha(opacity=0);
    font-size: 100%;
    height: 100%;
  }
}
.bar {
    height: 18px;
    background: green;
}	
.big-dialog .modal-dialog {
    width: 1600px;
}
		
.CodeMirror-sizer {
	min-height:25px;

}
		
.dialog450 .modal-dialog { width: 450px; }		
.dialog600 .modal-dialog { width: 600px; }
.dialog650 .modal-dialog { width: 650px; }
.dialog700 .modal-dialog { width: 700px; }
.dialog750 .modal-dialog { width: 750px; }
.dialog810 .modal-dialog { width: 810px; }	
.dialog850 .modal-dialog { width: 850px; }		
.dialog880 .modal-dialog { width: 880px; }
.dialog900 .modal-dialog { width: 900px; }
.dialog950 .modal-dialog { width: 950px; }
.dialog980 .modal-dialog { width: 980px; }
.dialog1030 .modal-dialog { width: 1030px; }
.dialog1100 .modal-dialog { width: 1100px; }
.dialog1500 .modal-dialog { width: 1150px; }
.dialog1200 .modal-dialog { width: 1200px; }
.dialog1370 .modal-dialog { width: 1370px; }	
dialog1030
#toast-container > .toast {
    background-image: none !important;
}

#toast-container > .toast:before {
    position: fixed;
    font-family: FontAwesome;
    font-size: 24px;
    line-height: 18px;
    float: left;
    color: #FFF;
    padding-right: 0.5em;
    margin: auto 0.5em auto -1.5em;
}  

#toast-container > .toast-success:before {
    content: \"\f002\" !important;
}

.social-feed-box{
	text-align:left !important;
}

.centerimg {
    display: block;
    margin-left: auto;
    margin-right: auto;
    width: 50%;
}
.lds-roller div:after {
  content: \" \";
  display: block;
  position: absolute;
  width: 7px;
  height: 7px;
  border-radius: 50%;
  background: #dfc;
  margin: -4px 0 0 -4px;
}
.lds-roller div:nth-child(1) {
  animation-delay: -0.036s;
}
.lds-roller div:nth-child(1):after {
  top: 63px;
  left: 63px;
}
.lds-roller div:nth-child(2) {
  animation-delay: -0.072s;
}
.lds-roller div:nth-child(2):after {
  top: 68px;
  left: 56px;
}
.lds-roller div:nth-child(3) {
  animation-delay: -0.108s;
}
.lds-roller div:nth-child(3):after {
  top: 71px;
  left: 48px;
}
.lds-roller div:nth-child(4) {
  animation-delay: -0.144s;
}
.lds-roller div:nth-child(4):after {
  top: 72px;
  left: 40px;
}
.lds-roller div:nth-child(5) {
  animation-delay: -0.18s;
}
.lds-roller div:nth-child(5):after {
  top: 71px;
  left: 32px;
}
.lds-roller div:nth-child(6) {
  animation-delay: -0.216s;
}
.lds-roller div:nth-child(6):after {
  top: 68px;
  left: 24px;
}
.lds-roller div:nth-child(7) {
  animation-delay: -0.252s;
}
.lds-roller div:nth-child(7):after {
  top: 63px;
  left: 17px;
}
.lds-roller div:nth-child(8) {
  animation-delay: -0.288s;
}
.lds-roller div:nth-child(8):after {
  top: 56px;
  left: 12px;
}
@keyframes lds-roller {
  0% {
    transform: rotate(0deg);
  }
  100% {
    transform: rotate(360deg);
  }
}
.white-bg {
  background-color: $ArticaBackGroundColor;
}

.white-bg .navbar-fixed-top,
.white-bg .navbar-static-top {
  background-color: $ArticaBackGroundColor;
}
.ibox-content {
  background-color: $ArticaBackGroundColor;
}

.gray-bg,
.bg-muted {
  background-color: $ArticaBackGroundBodyColor;
}

.footer{
    background-color: $ArticaBackGroundColor;
}
.ibox-title {
  background-color: $ArticaBackGroundColor;
}
.top-navigation .nav > li > a { color: $ArticaFontColor; }
.ui-state-default, .ui-widget-content .ui-state-default, .ui-widget-header .ui-state-default { color: $ArticaFontColor; }
.author-info { color: $ArticaFontColor; }
.product-name { color: $ArticaFontColor; }
.nav-tabs > li > a:hover, .nav-tabs > li > a:focus { color: $ArticaFontColor; }
body { color: $ArticaFontColor;}
.sidebar-container ul.nav-tabs li.active a { color: $ArticaFontColor;}
.ui-jqgrid-titlebar {color: $ArticaFontColor;}
.file-name small { color: $ArticaFontColor; }
.icons-box .infont a i { color: $ArticaFontColor;}
.note-editor.note-frame .note-editing-area .note-editable { color: $ArticaFontColor;}
.html5buttons a { color: $ArticaFontColor;}
.md-skin.top-navigation .nav.navbar-right > li > a { color: $ArticaFontColor;}
.md-skin .nav > li > a { color: $ArticaFontColor;}
.issue-info a { color: $ArticaFontColor;}
.project-files li a { color: $ArticaFontColor;}
.project-title a { color: $ArticaFontColor;}

.wrapper{
border-radius: 3px;
}

.black{
    color: #000000;
}
.greencell {
 background-color: #676a6c;
 width: 24px;
 height: 24px;
 border-radius: 3px;
}
.greencell:hover {
 background-color: #18a689;
 width: 24px;
 height: 24px;
}
.redcell {
 background-color: #18a689;
 width: 24px;
 height: 24px;
 border-radius: 3px;
}
.redcell:hover {
 background-color: #18a689;
 width: 24px;
 height: 24px;
}
.celltop{
    vertical-align:top;
    white-space: nowrap;
    padding-left:10px !important;
}


.jconfirm .jconfirm-box .jconfirm-buttons button.btn-default {
  height: 50px;
  width:60%;
  font-size:29px;
  text-transform:capitalize;
}
.jconfirm .jconfirm-box .jconfirm-buttons button.btn-default:hover {
  background-color: #bdc3c7 !important;
  color: #000;
}

.loading { 
  position: fixed;
  float: left;
  top: 50%;
  left: 50%;
  height: 100px;
  padding: 0px;
  width: 200px;
  margin-top: -50px;
  margin-left: -70px;
}
@keyframes loading {
  0% { border-top-color: #d13632; }
  11% { border-top-color: #e2571e; }
  22% { border-top-color: #E09128; }
  33% { border-top-color: #ffe400; }
  44% { border-top-color: #7dd132; }
  50% { height: 100px; margin-top: 0px; }
  55% { border-top-color: #32D152; }
  66% { border-top-color: #32d15b; }
  77% { border-top-color: #32bcd1; }
  88% { border-top-color: #323ad1; }
  99% { border-top-color: #cb32d1; }
  100% { border-top-color: #cb32d1; }
}
/*@-moz-keyframes loading {
  50% { height: 100px; margin-top: 0px; }
}
@-o-keyframes loading {
  50% { height: 100px; margin-top: 0px; }
}
@keyframes loading {
  50% { height: 100px; margin-top: 0px; }
}*/
@mixin inner() {
  height: 10px;
  width: 10px;
  background-color: #fff;
  display: inline-block;
  margin-top: 90px;
  -webkit-animation: loading 2.5s infinite;
  -moz-animation: loading 2.5s infinite;
  -o-animation: loading 2.5s infinite;
  animation: loading 2.5s infinite;
  border-top-left-radius: 2px;
  border-top-right-radius: 2px;
  border-top: 5px solid #333;
}
@mixin loading() {
	@for \$i from 1 through 10 {
		.loading-#{\$i} { @include inner(); -webkit-animation-delay: \$i/4+s; animation-delay: \$i/4+s; }
	}
}
.loading { @include loading(); }

.select2-container{box-sizing:border-box;display:inline-block;margin:0;position:relative;vertical-align:middle;}.select2-container .select2-selection--single{box-sizing:border-box;cursor:pointer;display:block;height:28px;user-select:none;-webkit-user-select:none;}.select2-container .select2-selection--single .select2-selection__rendered{display:block;padding-left:8px;padding-right:20px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;}.select2-container[dir=\"rtl\"] .select2-selection--single .select2-selection__rendered{padding-right:8px;padding-left:20px;}.select2-container .select2-selection--multiple{box-sizing:border-box;cursor:pointer;display:block;min-height:32px;user-select:none;-webkit-user-select:none;}.select2-container .select2-selection--multiple .select2-selection__rendered{display:inline-block;overflow:hidden;padding-left:8px;text-overflow:ellipsis;white-space:nowrap;}.select2-container .select2-search--inline{float:left;}.select2-container .select2-search--inline .select2-search__field{box-sizing:border-box;border:none;font-size:100%;margin-top:5px;}.select2-container .select2-search--inline .select2-search__field::-webkit-search-cancel-button{-webkit-appearance:none;}.select2-dropdown{background-color:white;border:1px solid #aaa;border-radius:4px;box-sizing:border-box;display:block;position:absolute;left:-100000px;width:100%;z-index:1051;}.select2-results{display:block;}.select2-results__options{list-style:none;margin:0;padding:0;}.select2-results__option{padding:6px;user-select:none;-webkit-user-select:none;}.select2-results__option[aria-selected]{cursor:pointer;}.select2-container--open .select2-dropdown{left:0;}.select2-container--open .select2-dropdown--above{border-bottom:none;border-bottom-left-radius:0;border-bottom-right-radius:0;}.select2-container--open .select2-dropdown--below{border-top:none;border-top-left-radius:0;border-top-right-radius:0;}.select2-search--dropdown{display:block;padding:4px;}.select2-search--dropdown .select2-search__field{padding:4px;width:100%;box-sizing:border-box;}.select2-search--dropdown .select2-search__field::-webkit-search-cancel-button{-webkit-appearance:none;}.select2-search--dropdown.select2-search--hide{display:none;}.select2-close-mask{border:0;margin:0;padding:0;display:block;position:fixed;left:0;top:0;min-height:100%;min-width:100%;height:auto;width:auto;opacity:0;z-index:99;background-color:#fff;filter:alpha(opacity=0);}.select2-hidden-accessible{border:0;clip:rect(0 0 0 0);height:1px;margin:-1px;overflow:hidden;padding:0;position:absolute;width:1px;}.select2-container--default .select2-selection--single{background-color:#fff;border:1px solid #aaa;border-radius:4px;}.select2-container--default .select2-selection--single .select2-selection__rendered{color:#444;line-height:28px;}.select2-container--default .select2-selection--single .select2-selection__clear{cursor:pointer;float:right;font-weight:bold;}.select2-container--default .select2-selection--single .select2-selection__placeholder{color:#999;}.select2-container--default .select2-selection--single .select2-selection__arrow{height:26px;position:absolute;top:1px;right:1px;width:20px;}.select2-container--default .select2-selection--single .select2-selection__arrow b{border-color:#888 transparent transparent transparent;border-style:solid;border-width:5px 4px 0 4px;height:0;left:50%;margin-left:-4px;margin-top:-2px;position:absolute;top:50%;width:0;}.select2-container--default[dir=\"rtl\"] .select2-selection--single .select2-selection__clear{float:left;}.select2-container--default[dir=\"rtl\"] .select2-selection--single .select2-selection__arrow{left:1px;right:auto;}.select2-container--default.select2-container--disabled .select2-selection--single{background-color:#eee;cursor:default;}.select2-container--default.select2-container--disabled .select2-selection--single .select2-selection__clear{display:none;}.select2-container--default.select2-container--open .select2-selection--single .select2-selection__arrow b{border-color:transparent transparent #888 transparent;border-width:0 4px 5px 4px;}.select2-container--default .select2-selection--multiple{background-color:white;border:1px solid #aaa;border-radius:4px;cursor:text;}.select2-container--default .select2-selection--multiple .select2-selection__rendered{box-sizing:border-box;list-style:none;margin:0;padding:0 5px;width:100%;}.select2-container--default .select2-selection--multiple .select2-selection__placeholder{color:#999;margin-top:5px;float:left;}.select2-container--default .select2-selection--multiple .select2-selection__clear{cursor:pointer;float:right;font-weight:bold;margin-top:5px;margin-right:10px;}.select2-container--default .select2-selection--multiple .select2-selection__choice{background-color:#e4e4e4;border:1px solid #aaa;border-radius:4px;cursor:default;float:left;margin-right:5px;margin-top:5px;padding:0 5px;}.select2-container--default .select2-selection--multiple .select2-selection__choice__remove{color:#999;cursor:pointer;display:inline-block;font-weight:bold;margin-right:2px;}.select2-container--default .select2-selection--multiple .select2-selection__choice__remove:hover{color:#333;}.select2-container--default[dir=\"rtl\"] .select2-selection--multiple .select2-selection__choice,.select2-container--default[dir=\"rtl\"] .select2-selection--multiple .select2-selection__placeholder{float:right;}.select2-container--default[dir=\"rtl\"] .select2-selection--multiple .select2-selection__choice{margin-left:5px;margin-right:auto;}.select2-container--default[dir=\"rtl\"] .select2-selection--multiple .select2-selection__choice__remove{margin-left:2px;margin-right:auto;}.select2-container--default.select2-container--focus .select2-selection--multiple{border:solid black 1px;outline:0;}.select2-container--default.select2-container--disabled .select2-selection--multiple{background-color:#eee;cursor:default;}.select2-container--default.select2-container--disabled .select2-selection__choice__remove{display:none;}.select2-container--default.select2-container--open.select2-container--above .select2-selection--single,.select2-container--default.select2-container--open.select2-container--above .select2-selection--multiple{border-top-left-radius:0;border-top-right-radius:0;}.select2-container--default.select2-container--open.select2-container--below .select2-selection--single,.select2-container--default.select2-container--open.select2-container--below .select2-selection--multiple{border-bottom-left-radius:0;border-bottom-right-radius:0;}.select2-container--default .select2-search--dropdown .select2-search__field{border:1px solid #aaa;}.select2-container--default .select2-search--inline .select2-search__field{background:transparent;border:none;outline:0;}.select2-container--default .select2-results>.select2-results__options{max-height:200px;overflow-y:auto;}.select2-container--default .select2-results__option[role=group]{padding:0;}.select2-container--default .select2-results__option[aria-disabled=true]{color:#999;}.select2-container--default .select2-results__option[aria-selected=true]{background-color:#ddd;}.select2-container--default .select2-results__option .select2-results__option{padding-left:1em;}.select2-container--default .select2-results__option .select2-results__option .select2-results__group{padding-left:0;}.select2-container--default .select2-results__option .select2-results__option .select2-results__option{margin-left:-1em;padding-left:2em;}.select2-container--default .select2-results__option .select2-results__option .select2-results__option .select2-results__option{margin-left:-2em;padding-left:3em;}.select2-container--default .select2-results__option .select2-results__option .select2-results__option .select2-results__option .select2-results__option{margin-left:-3em;padding-left:4em;}.select2-container--default .select2-results__option .select2-results__option .select2-results__option .select2-results__option .select2-results__option .select2-results__option{margin-left:-4em;padding-left:5em;}.select2-container--default .select2-results__option .select2-results__option .select2-results__option .select2-results__option .select2-results__option .select2-results__option .select2-results__option{margin-left:-5em;padding-left:6em;}.select2-container--default .select2-results__option--highlighted[aria-selected]{background-color:#5897fb;color:white;}.select2-container--default .select2-results__group{cursor:default;display:block;padding:6px;}.select2-container--classic .select2-selection--single{background-color:#f6f6f6;border:1px solid #aaa;border-radius:4px;outline:0;background-image:-webkit-linear-gradient(top, #ffffff 50%, #eeeeee 100%);background-image:-o-linear-gradient(top, #ffffff 50%, #eeeeee 100%);background-image:linear-gradient(to bottom, #ffffff 50%, #eeeeee 100%);background-repeat:repeat-x;filter:progid:DXImageTransform.Microsoft.gradient(startColorstr='#ffffff', endColorstr='#eeeeee', GradientType=0);}.select2-container--classic .select2-selection--single:focus{border:1px solid #5897fb;}.select2-container--classic .select2-selection--single .select2-selection__rendered{color:#444;line-height:28px;}.select2-container--classic .select2-selection--single .select2-selection__clear{cursor:pointer;float:right;font-weight:bold;margin-right:10px;}.select2-container--classic .select2-selection--single .select2-selection__placeholder{color:#999;}.select2-container--classic .select2-selection--single .select2-selection__arrow{background-color:#ddd;border:none;border-left:1px solid #aaa;border-top-right-radius:4px;border-bottom-right-radius:4px;height:26px;position:absolute;top:1px;right:1px;width:20px;background-image:-webkit-linear-gradient(top, #eeeeee 50%, #cccccc 100%);background-image:-o-linear-gradient(top, #eeeeee 50%, #cccccc 100%);background-image:linear-gradient(to bottom, #eeeeee 50%, #cccccc 100%);background-repeat:repeat-x;filter:progid:DXImageTransform.Microsoft.gradient(startColorstr='#eeeeee', endColorstr='#cccccc', GradientType=0);}.select2-container--classic .select2-selection--single .select2-selection__arrow b{border-color:#888 transparent transparent transparent;border-style:solid;border-width:5px 4px 0 4px;height:0;left:50%;margin-left:-4px;margin-top:-2px;position:absolute;top:50%;width:0;}.select2-container--classic[dir=\"rtl\"] .select2-selection--single .select2-selection__clear{float:left;}.select2-container--classic[dir=\"rtl\"] .select2-selection--single .select2-selection__arrow{border:none;border-right:1px solid #aaa;border-radius:0;border-top-left-radius:4px;border-bottom-left-radius:4px;left:1px;right:auto;}.select2-container--classic.select2-container--open .select2-selection--single{border:1px solid #5897fb;}.select2-container--classic.select2-container--open .select2-selection--single .select2-selection__arrow{background:transparent;border:none;}.select2-container--classic.select2-container--open .select2-selection--single .select2-selection__arrow b{border-color:transparent transparent #888 transparent;border-width:0 4px 5px 4px;}.select2-container--classic.select2-container--open.select2-container--above .select2-selection--single{border-top:none;border-top-left-radius:0;border-top-right-radius:0;background-image:-webkit-linear-gradient(top, #ffffff 0%, #eeeeee 50%);background-image:-o-linear-gradient(top, #ffffff 0%, #eeeeee 50%);background-image:linear-gradient(to bottom, #ffffff 0%, #eeeeee 50%);background-repeat:repeat-x;filter:progid:DXImageTransform.Microsoft.gradient(startColorstr='#ffffff', endColorstr='#eeeeee', GradientType=0);}.select2-container--classic.select2-container--open.select2-container--below .select2-selection--single{border-bottom:none;border-bottom-left-radius:0;border-bottom-right-radius:0;background-image:-webkit-linear-gradient(top, #eeeeee 50%, #ffffff 100%);background-image:-o-linear-gradient(top, #eeeeee 50%, #ffffff 100%);background-image:linear-gradient(to bottom, #eeeeee 50%, #ffffff 100%);background-repeat:repeat-x;filter:progid:DXImageTransform.Microsoft.gradient(startColorstr='#eeeeee', endColorstr='#ffffff', GradientType=0);}.select2-container--classic .select2-selection--multiple{background-color:white;border:1px solid #aaa;border-radius:4px;cursor:text;outline:0;}.select2-container--classic .select2-selection--multiple:focus{border:1px solid #5897fb;}.select2-container--classic .select2-selection--multiple .select2-selection__rendered{list-style:none;margin:0;padding:0 5px;}.select2-container--classic .select2-selection--multiple .select2-selection__clear{display:none;}.select2-container--classic .select2-selection--multiple .select2-selection__choice{background-color:#e4e4e4;border:1px solid #aaa;border-radius:4px;cursor:default;float:left;margin-right:5px;margin-top:5px;padding:0 5px;}.select2-container--classic .select2-selection--multiple .select2-selection__choice__remove{color:#888;cursor:pointer;display:inline-block;font-weight:bold;margin-right:2px;}.select2-container--classic .select2-selection--multiple .select2-selection__choice__remove:hover{color:#555;}.select2-container--classic[dir=\"rtl\"] .select2-selection--multiple .select2-selection__choice{float:right;}.select2-container--classic[dir=\"rtl\"] .select2-selection--multiple .select2-selection__choice{margin-left:5px;margin-right:auto;}.select2-container--classic[dir=\"rtl\"] .select2-selection--multiple .select2-selection__choice__remove{margin-left:2px;margin-right:auto;}.select2-container--classic.select2-container--open .select2-selection--multiple{border:1px solid #5897fb;}.select2-container--classic.select2-container--open.select2-container--above .select2-selection--multiple{border-top:none;border-top-left-radius:0;border-top-right-radius:0;}.select2-container--classic.select2-container--open.select2-container--below .select2-selection--multiple{border-bottom:none;border-bottom-left-radius:0;border-bottom-right-radius:0;}.select2-container--classic .select2-search--dropdown .select2-search__field{border:1px solid #aaa;outline:0;}.select2-container--classic .select2-search--inline .select2-search__field{outline:0;}.select2-container--classic .select2-dropdown{background-color:white;border:1px solid transparent;}.select2-container--classic .select2-dropdown--above{border-bottom:none;}.select2-container--classic .select2-dropdown--below{border-top:none;}.select2-container--classic .select2-results>.select2-results__options{max-height:200px;overflow-y:auto;}.select2-container--classic .select2-results__option[role=group]{padding:0;}.select2-container--classic .select2-results__option[aria-disabled=true]{color:grey;}.select2-container--classic .select2-results__option--highlighted[aria-selected]{background-color:#3875d7;color:white;}.select2-container--classic .select2-results__group{cursor:default;display:block;padding:6px;}.select2-container--classic.select2-container--open .select2-dropdown{border-color:#5897fb;}
";
@file_put_contents("css/angular.css.php.css",$html);
echo $html;