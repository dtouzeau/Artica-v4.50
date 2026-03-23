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
.mattermostMenu {
  display: inline-block;
  width: 1em;
  height: 1em;
  vertical-align: -0.125em;

  /* Use the logo as a mask */
  -webkit-mask-image: url(\"/img/mattermost.webp\");
  mask-image: url(\"/img/mattermost.webp\");
  -webkit-mask-repeat: no-repeat;
  mask-repeat: no-repeat;
  -webkit-mask-position: center;
  mask-position: center;
  -webkit-mask-size: contain;
  mask-size: contain;

  /* Color of the logo */
  background-color: rgb(167, 177, 194); /* white */
}
.mattermostTable {
  display: inline-block;
  width: 22.74px;
  height: 22.74px;
  vertical-align: -0.125em;

  /* Use the logo as a mask */
  -webkit-mask-image: url(\"/img/mattermost.webp\");
  mask-image: url(\"/img/mattermost.webp\");
  -webkit-mask-repeat: no-repeat;
  mask-repeat: no-repeat;
  -webkit-mask-position: center;
  mask-position: center;
  -webkit-mask-size: contain;
  mask-size: contain;

  /* Color of the logo */
  background-color: rgb(51, 51, 51);
}
.mattermostTitle {
  display: inline-block;
  width: 104px;
  height: 104px;

  /* Mask using your WebP icon */
  -webkit-mask-image: url(\"/img/mattermost.webp\");
  mask-image: url(\"/img/mattermost.webp\");
  -webkit-mask-repeat: no-repeat;
  mask-repeat: no-repeat;
  -webkit-mask-position: center;
  mask-position: center;
  -webkit-mask-size: contain;
  mask-size: contain;

  /* Logo color */
  background-color: rgb(103, 106, 108); /* black */
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
/* =========================
   IP LINK
========================= */

.ip-link {
    border-bottom: 1px dashed rgba(0,0,0,0.25);
    font-weight: 600;
    cursor: pointer;
    transition: all 0.15s ease;
    color: #2c3e50;
}

.ip-link:hover {
    color: #1e73be;
    border-bottom-color: #1e73be;
}


/* =========================
   CONTEXT MENU
========================= */

.ip-context-menu {
    position: fixed;
    display: none;
    min-width: 220px;
    background: #ffffff;
    border-radius: 8px;
    padding: 6px 0;
    border: 1px solid rgba(0,0,0,0.06);
    box-shadow:
        0 8px 24px rgba(0,0,0,0.12),
        0 2px 6px rgba(0,0,0,0.06);
    z-index: 99999;

    opacity: 0;
    transform: translateY(4px);
    transition: opacity 0.15s ease, transform 0.15s ease;
}

/* Visible state */
.ip-context-menu.show {
    display: block;
    opacity: 1;
    transform: translateY(0);
}


/* =========================
   MENU ITEMS
========================= */

.ip-context-menu ul {
    list-style: none;
    margin: 0;
    padding: 0;
}

.ip-context-menu li {
    padding: 10px 16px;
    font-size: 13px;
    color: #34495e;
    cursor: pointer;
    transition: background 0.15s ease, color 0.15s ease;
    display: flex;
    align-items: center;
    gap: 8px;
}

/* Hover effect */
.ip-context-menu li:hover {
    background: rgba(30,115,190,0.08);
    color: #1e73be;
}

.chatCode{
    --font-sans: \"ui-sans-serif\", \"-apple-system\", \"system-ui\", \"Segoe UI\", \"Helvetica\", \"Apple Color Emoji\", \"Arial\", \"sans-serif\", \"Segoe UI Emoji\", \"Segoe UI Symbol\";
    --font-mono: \"ui-monospace\", \"SFMono-Regular\", \"SF Mono\", \"Menlo\", \"Consolas\", \"Liberation Mono\", \"monospace\";
    --spacing: .25rem;
    --breakpoint-md: 48rem;
    --breakpoint-lg: 64rem;
    --breakpoint-xl: 80rem;
    --breakpoint-2xl: 96rem;
    --container-xs: 20rem;
    --container-sm: 24rem;
    --container-md: 28rem;
    --container-lg: 32rem;
    --container-xl: 36rem;
    --container-2xl: 42rem;
    --container-3xl: 48rem;
    --container-4xl: 56rem;
    --container-5xl: 64rem;
    --container-6xl: 72rem;
    --container-7xl: 80rem;
    --text-xs: .75rem;
    --text-xs--line-height: calc(1 / .75);
    --text-sm: .875rem;
    --text-sm--line-height: calc(1.25 / .875);
    --text-base: 1rem;
    --text-base--line-height: calc(1.5 / 1);
    --text-lg: 1.125rem;
    --text-lg--line-height: calc(1.75 / 1.125);
    --text-xl: 1.25rem;
    --text-xl--line-height: calc(1.75 / 1.25);
    --text-2xl: 1.5rem;
    --text-2xl--line-height: calc(2 / 1.5);
    --text-3xl: 1.875rem;
    --text-3xl--line-height: calc(2.25 / 1.875);
    --text-4xl: 2.25rem;
    --text-4xl--line-height: calc(2.5 / 2.25);
    --text-5xl: 3rem;
    --text-5xl--line-height: 1;
    --text-6xl: 3.75rem;
    --text-6xl--line-height: 1;
    --text-7xl: 4.5rem;
    --text-7xl--line-height: 1;
    --font-weight-extralight: 200;
    --font-weight-light: 300;
    --font-weight-normal: 400;
    --font-weight-medium: 500;
    --font-weight-semibold: 600;
    --font-weight-bold: 700;
    --font-weight-black: 900;
    --tracking-tighter: -.05em;
    --tracking-tight: -.025em;
    --tracking-normal: 0em;
    --tracking-wide: .025em;
    --tracking-wider: .05em;
    --tracking-widest: .1em;
    --leading-tight: 1.25;
    --leading-snug: 1.375;
    --leading-normal: 1.5;
    --leading-relaxed: 1.625;
    --radius-xs: .125rem;
    --radius-sm: .25rem;
    --radius-md: .375rem;
    --radius-lg: .5rem;
    --radius-xl: .75rem;
    --radius-2xl: 1rem;
    --radius-3xl: 1.5rem;
    --radius-4xl: 2rem;
    --shadow-lg: 0 10px 15px -3px #0000001a, 0 4px 6px -4px #0000001a;
    --drop-shadow-xs: 0 1px 1px #0000000d;
    --drop-shadow-sm: 0 1px 2px #00000026;
    --drop-shadow-md: 0 3px 3px #0000001f;
    --drop-shadow-lg: 0 4px 4px #00000026;
    --drop-shadow-xl: 0 9px 7px #0000001a;
    --drop-shadow-2xl: 0 25px 25px #00000026;
    --ease-in: cubic-bezier(.4, 0, 1, 1);
    --ease-out: cubic-bezier(0, 0, .2, 1);
    --ease-in-out: cubic-bezier(.4, 0, .2, 1);
    --animate-spin: spin 1s linear infinite;
    --animate-pulse: pulse 2s cubic-bezier(.4, 0, .6, 1) infinite;
    --animate-bounce: bounce 1s infinite;
    --blur-xs: 4px;
    --blur-sm: 8px;
    --blur-md: 12px;
    --blur-lg: 16px;
    --blur-xl: 24px;
    --blur-2xl: 40px;
    --blur-3xl: 64px;
    --aspect-video: 16 / 9;
    --default-transition-duration: .15s;
    --default-transition-timing-function: cubic-bezier(.4, 0, .2, 1);
    --default-font-family: \"ui-sans-serif\", \"-apple-system\", \"system-ui\", \"Segoe UI\", \"Helvetica\", \"Apple Color Emoji\", \"Arial\", \"sans-serif\", \"Segoe UI Emoji\", \"Segoe UI Symbol\";
    --default-mono-font-family: \"ui-monospace\", \"SFMono-Regular\", \"SF Mono\", \"Menlo\", \"Consolas\", \"Liberation Mono\", \"monospace\";
    --text-heading-2: 1.5rem;
    --text-heading-2--line-height: 1.75rem;
    --text-heading-2--letter-spacing: -.015625rem;
    --text-heading-2--font-weight: 600;
    --text-heading-app: 1.75rem;
    --text-heading-app--line-height: 2.125rem;
    --text-heading-app--letter-spacing: .02375rem;
    --text-heading-app--font-weight: 500;
    --text-heading-3: 1.125rem;
    --text-heading-3--line-height: 1.625rem;
    --text-heading-3--letter-spacing: -.028125rem;
    --text-heading-3--font-weight: 600;
    --text-body-regular: 1rem;
    --text-body-regular--line-height: 1.625rem;
    --text-body-regular--letter-spacing: -.025rem;
    --text-body-regular--font-weight: 400;
    --text-body-small-regular: .875rem;
    --text-body-small-regular--line-height: 1.125rem;
    --text-body-small-regular--letter-spacing: -.01875rem;
    --text-body-small-regular--font-weight: 400;
    --text-footnote-regular: .8125rem;
    --text-footnote-regular--line-height: 1.125rem;
    --text-footnote-regular--letter-spacing: -.005rem;
    --text-footnote-regular--font-weight: 400;
    --text-monospace: .9375rem;
    --text-monospace--line-height: 1.375rem;
    --text-monospace--letter-spacing: -.025rem;
    --text-monospace--font-weight: 400;
    --text-caption-regular: .75rem;
    --text-caption-regular--line-height: 1rem;
    --text-caption-regular--letter-spacing: -.00625rem;
    --text-caption-regular--font-weight: 400;
    --tap-padding-pointer: 32px;
    --tap-padding-mobile: 44px;
    --focus-outline-margin-default: 4px;
    --green-25: #edfaf2;
    --green-50: #d9f4e4;
    --green-75: #b8ebcc;
    --green-100: #8cdfad;
    --green-200: #66d492;
    --green-300: #40c977;
    --green-400: #04b84c;
    --green-500: #00a240;
    --green-600: #008635;
    --green-700: #00692a;
    --green-800: #004f1f;
    --green-900: #003716;
    --green-950: #011c0b;
    --green-1000: #001207;
    --green-a25: #04b84c14;
    --green-a50: #04b84c26;
    --green-a75: #04b84c4a;
    --green-a100: #04b84c73;
    --green-a200: #04b84c99;
    --green-a300: #04b84cbf;
    --purple-25: #f9f5fe;
    --purple-50: #efe5fe;
    --purple-75: #e0cefd;
    --purple-100: #ceb0fb;
    --purple-200: #be95fa;
    --purple-300: #ad7bf9;
    --purple-400: #924ff7;
    --purple-500: #8046d9;
    --purple-600: #6b3ab4;
    --purple-700: #532d8d;
    --purple-800: #3f226a;
    --purple-900: #2c184a;
    --purple-950: #160c25;
    --purple-1000: #100a19;
    --purple-a25: #924ff70f;
    --purple-a50: #924ff726;
    --purple-a75: #924ff747;
    --purple-a100: #924ff773;
    --purple-a200: #924ff799;
    --purple-a300: #924ff7bf;
    --blue-25: #f5faff;
    --blue-50: #e5f3ff;
    --blue-75: #cce6ff;
    --blue-100: #99ceff;
    --blue-200: #66b5ff;
    --blue-300: #339cff;
    --blue-400: #0285ff;
    --blue-500: #0169cc;
    --blue-600: #004f99;
    --blue-700: #003f7a;
    --blue-800: #013566;
    --blue-900: #00284d;
    --blue-950: #000e1a;
    --blue-1000: #000d19;
    --blue-a25: #0285ff0a;
    --blue-a50: #0285ff21;
    --blue-a75: #0285ff40;
    --blue-a100: #0285ff66;
    --blue-a200: #0285ff99;
    --blue-a300: #0285ffcc;
    --orange-25: #fff5f0;
    --orange-50: #ffe7d9;
    --orange-75: #ffcfb4;
    --orange-100: #ffb790;
    --orange-200: #ff9e6c;
    --orange-300: #ff8549;
    --orange-400: #fb6a22;
    --orange-500: #e25507;
    --orange-600: #b9480d;
    --orange-700: #923b0f;
    --orange-800: #6d2e0f;
    --orange-900: #4a2206;
    --orange-950: #281105;
    --orange-1000: #211107;
    --orange-a25: #fb6a2212;
    --orange-a50: #fb6a2229;
    --orange-a75: #fb6a2254;
    --orange-a100: #fb6a227a;
    --orange-a200: #fb6a22a6;
    --orange-a300: #fb6a22cf;
    --red-25: #fff0f0;
    --red-50: #ffe1e0;
    --red-75: #ffc6c5;
    --red-100: #ffa4a2;
    --red-200: #ff8583;
    --red-300: #ff6764;
    --red-400: #fa423e;
    --red-500: #e02e2a;
    --red-600: #ba2623;
    --red-700: #911e1b;
    --red-800: #6e1615;
    --red-900: #4d100e;
    --red-950: #280b0a;
    --red-1000: #1f0909;
    --red-a25: #fa423e14;
    --red-a50: #fa423e29;
    --red-a75: #fa423e4c;
    --red-a100: #fa423e7a;
    --red-a200: #fa423ea3;
    --red-a300: #fa423ec9;
    --pink-25: #fff4f9;
    --pink-50: #ffe8f3;
    --pink-75: #ffd4e8;
    --pink-100: #ffbada;
    --pink-200: #ffa3ce;
    --pink-300: #ff8cc1;
    --pink-400: #ff66ad;
    --pink-500: #e04c91;
    --pink-600: #ba437a;
    --pink-700: #963c67;
    --pink-800: #6e2c4a;
    --pink-900: #4d1f34;
    --pink-950: #29101c;
    --pink-1000: #1a0a11;
    --pink-a25: #ff66ad14;
    --pink-a50: #ff66ad29;
    --pink-a75: #ff66ad47;
    --pink-a100: #ff66ad73;
    --pink-a200: #ff66ad99;
    --pink-a300: #ff66adc2;
    --yellow-25: #fffbed;
    --yellow-50: #fff6d9;
    --yellow-75: #ffeeb8;
    --yellow-100: #ffe48c;
    --yellow-200: #ffdb66;
    --yellow-300: #ffd240;
    --yellow-400: #ffc300;
    --yellow-500: #e0ac00;
    --yellow-600: #ba8e00;
    --yellow-700: #916f00;
    --yellow-800: #6e5400;
    --yellow-900: #4d3b00;
    --yellow-950: #261d00;
    --yellow-1000: #1a1400;
    --yellow-a25: #ffc30014;
    --yellow-a50: #ffc30026;
    --yellow-a75: #ffc30045;
    --yellow-a100: #ffc30073;
    --yellow-a200: #ffc30096;
    --yellow-a300: #ffc300bd;
    -webkit-text-size-adjust: 100%;
    tab-size: 4;
    -webkit-tap-highlight-color: transparent;
    --mkt-header-height: calc(16 * var(--spacing));
    --default-theme-user-msg-bg: var(--message-surface);
    --default-theme-user-msg-text: var(--text-primary);
    --default-theme-submit-btn-bg: #000;
    --default-theme-submit-btn-text: #fff;
    --default-theme-secondary-btn-bg: var(--gray-100);
    --default-theme-secondary-btn-text: var(--text-primary);
    --default-theme-user-selection-bg: color-mix(in oklab, var(--blue-300) 35%, transparent);
    --default-theme-attribution-highlight-bg: var(--yellow-75);
    --default-theme-entity-accent: var(--blue-500);
    --formatted-text-highlight-bg: #bae6fdb3;
    --blue-theme-user-msg-bg: var(--blue-50);
    --blue-theme-user-msg-text: var(--blue-900);
    --blue-theme-submit-btn-bg: var(--blue-400);
    --blue-theme-submit-btn-text: #fff;
    --blue-theme-secondary-btn-bg: var(--blue-50);
    --blue-theme-secondary-btn-text: var(--blue-900);
    --blue-theme-user-selection-bg: color-mix(in oklab, var(--blue-300) 35%, transparent);
    --blue-theme-entity-accent: var(--blue-500);
    --green-theme-user-msg-bg: var(--green-50);
    --green-theme-user-msg-text: var(--green-900);
    --green-theme-submit-btn-bg: var(--green-400);
    --green-theme-submit-btn-text: #fff;
    --green-theme-secondary-btn-bg: var(--green-50);
    --green-theme-secondary-btn-text: var(--green-900);
    --green-theme-user-selection-bg: color-mix(in oklab, var(--green-300) 35%, transparent);
    --green-theme-entity-accent: var(--green-500);
    --yellow-theme-user-msg-bg: var(--yellow-50);
    --yellow-theme-user-msg-text: var(--yellow-900);
    --yellow-theme-submit-btn-bg: var(--yellow-400);
    --yellow-theme-submit-btn-text: #fff;
    --yellow-theme-secondary-btn-bg: var(--yellow-50);
    --yellow-theme-secondary-btn-text: var(--yellow-900);
    --yellow-theme-user-selection-bg: color-mix(in oklab, var(--yellow-300) 35%, transparent);
    --yellow-theme-entity-accent: var(--yellow-500);
    --purple-theme-user-msg-bg: var(--purple-50);
    --purple-theme-user-msg-text: var(--purple-900);
    --purple-theme-submit-btn-bg: var(--purple-400);
    --purple-theme-submit-btn-text: #fff;
    --purple-theme-secondary-btn-bg: var(--purple-50);
    --purple-theme-secondary-btn-text: var(--purple-900);
    --purple-theme-user-selection-bg: color-mix(in oklab, var(--purple-300) 35%, transparent);
    --purple-theme-entity-accent: var(--purple-500);
    --pink-theme-user-msg-bg: var(--pink-50);
    --pink-theme-user-msg-text: var(--pink-900);
    --pink-theme-submit-btn-bg: var(--pink-400);
    --pink-theme-submit-btn-text: #fff;
    --pink-theme-secondary-btn-bg: var(--pink-50);
    --pink-theme-secondary-btn-text: var(--pink-900);
    --pink-theme-user-selection-bg: color-mix(in oklab, var(--pink-300) 35%, transparent);
    --pink-theme-entity-accent: var(--pink-500);
    --orange-theme-user-msg-bg: var(--orange-50);
    --orange-theme-user-msg-text: var(--orange-900);
    --orange-theme-submit-btn-bg: var(--orange-400);
    --orange-theme-submit-btn-text: #fff;
    --orange-theme-secondary-btn-bg: var(--orange-50);
    --orange-theme-secondary-btn-text: var(--orange-900);
    --orange-theme-user-selection-bg: color-mix(in oklab, var(--orange-300) 35%, transparent);
    --orange-theme-entity-accent: var(--orange-500);
    --black-theme-user-msg-bg: #000;
    --black-theme-user-msg-text: #fff;
    --black-theme-submit-btn-bg: #000;
    --black-theme-submit-btn-text: #fff;
    --black-theme-secondary-btn-bg: var(--gray-100);
    --black-theme-secondary-btn-text: var(--text-primary);
    --black-theme-user-selection-bg: color-mix(in oklab, var(--gray-300) 40%, transparent);
    --black-theme-entity-accent: var(--gray-500);
    --theme-user-msg-bg: var(--default-theme-user-msg-bg);
    --theme-user-msg-text: var(--default-theme-user-msg-text);
    --theme-submit-btn-bg: var(--default-theme-submit-btn-bg);
    --theme-submit-btn-text: var(--default-theme-submit-btn-text);
    --theme-secondary-btn-bg: var(--default-theme-secondary-btn-bg);
    --theme-secondary-btn-text: var(--default-theme-secondary-btn-text);
    --theme-user-selection-bg: var(--default-theme-user-selection-bg);
    --theme-attribution-highlight-bg: var(--default-theme-attribution-highlight-bg);
    --theme-entity-accent: var(--default-theme-entity-accent);
    --start: left;
    --end: right;
    --to-end-unit: 1;
    --is-ltr: unset;
    --is-rtl: ;
    --safe-area-max-inset-bottom: env(safe-area-max-inset-bottom,36px);
    --user-chat-width: 70%;
    --sidebar-width: 260px;
    --sidebar-section-margin-top: 1.25rem;
    --sidebar-section-first-margin-top: .5rem;
    --sidebar-expanded-section-margin-bottom: 1.25rem;
    --sidebar-collapsed-section-margin-bottom: .75rem;
    --sidebar-rail-width: calc(13 * var(--spacing));
    --header-height: calc(13 * var(--spacing));
    --stage-scroll-gutter: stable both-edges;
    --white: #fff;
    --black: #000;
    --gray-0: #fff;
    --gray-25: #fcfcfc;
    --gray-50: #f9f9f9;
    --gray-75: #f2f2f2;
    --gray-100: #ececec;
    --gray-150: #e8e8e8;
    --gray-200: #e3e3e3;
    --gray-250: #d8d8d8;
    --gray-300: #cdcdcd;
    --gray-350: silver;
    --gray-400: #b4b4b4;
    --gray-450: #a8a8a8;
    --gray-500: #9b9b9b;
    --gray-550: #818181;
    --gray-600: #676767;
    --gray-650: #545454;
    --gray-700: #424242;
    --gray-750: #2f2f2f;
    --gray-800: #212121;
    --gray-850: #1c1c1c;
    --gray-900: #171717;
    --gray-925: #121212;
    --gray-950: #0d0d0d;
    --gray-975: #0c0c0c;
    --gray-1000: #0b0b0b;
    --brand-purple: #ab68ff;
    --cqh-full: 100cqh;
    --cqw-full: 100cqw;
    --silk-100-lvh-dvh-pct: max(100dvh,100lvh);
    --menu-item-height: calc(var(--spacing) * 9);
    --spring-fast-duration: .667s;
    --spring-fast: linear(0, .01942 1.83%, .07956 4.02%, .47488 13.851%, .65981 19.572%, .79653 25.733%, .84834 29.083%, .89048 32.693%, .9246 36.734%, .95081 41.254%, .97012 46.425%, .98361 52.535%, .99665 68.277%, .99988);
    --spring-common-duration: .667s;
    --spring-common: linear(0, .00506 1.18%, .02044 2.46%, .08322 5.391%, .46561 17.652%, .63901 24.342%, .76663 31.093%, .85981 38.454%, .89862 42.934%, .92965 47.845%, .95366 53.305%, .97154 59.516%, .99189 74.867%, .9991);
    --spring-standard: var(--spring-common);
    --spring-slow-bounce-duration: 1.167s;
    --spring-slow-bounce: linear(0, .00172 0.51%, .00682 1.03%, .02721 2.12%, .06135 3.29%, .11043 4.58%, .21945 6.911%, .59552 14.171%, .70414 16.612%, .79359 18.962%, .86872 21.362%, .92924 23.822%, .97589 26.373%, 1.01 29.083%, 1.0264 31.043%, 1.03767 33.133%, 1.04411 35.404%, 1.04597 37.944%, 1.04058 42.454%, 1.01119 55.646%, 1.00137 63.716%, .99791 74.127%, .99988);
    --spring-bounce-duration: .833s;
    --spring-bounce: linear(0, .00541 1.29%, .02175 2.68%, .04923 4.19%, .08852 5.861%, .17388 8.851%, .48317 18.732%, .57693 22.162%, .65685 25.503%, .72432 28.793%, .78235 32.163%, .83182 35.664%, .87356 39.354%, .91132 43.714%, .94105 48.455%, .96361 53.705%, .97991 59.676%, .9903 66.247%, .99664 74.237%, .99968 84.358%, 1.00048);
    --spring-fast-bounce-duration: 1s;
    --spring-fast-bounce: linear(0, .00683 1.14%, .02731 2.35%, .11137 5.091%, .59413 15.612%, .78996 20.792%, .92396 25.953%, .97109 28.653%, 1.00624 31.503%, 1.03801 36.154%, 1.0477 41.684%, 1.00242 68.787%, .99921);
    --easing-spring-elegant-duration: .58171s;
    --easing-spring-elegant: linear(0 0%, .005927 1%, .022466 2%, .047872 3%, .080554 4%, .119068 5%, .162116 6%, .208536 7.0%, .2573 8%, .3075 9%, .358346 10%, .409157 11%, .45935 12%, .508438 13%, .556014 14.0%, .601751 15%, .645389 16%, .686733 17%, .72564 18%, .762019 19%, .795818 20%, .827026 21%, .855662 22%, .881772 23%, .905423 24%, .926704 25%, .945714 26%, .962568 27%, .977386 28.0%, .990295 29.0%, 1.00143 30%, 1.01091 31%, 1.01888 32%, 1.02547 33%, 1.03079 34%, 1.03498 35%, 1.03816 36%, 1.04042 37%, 1.04189 38%, 1.04266 39%, 1.04283 40%, 1.04247 41%, 1.04168 42%, 1.04052 43%, 1.03907 44%, 1.03737 45%, 1.03549 46%, 1.03348 47%, 1.03138 48%, 1.02922 49%, 1.02704 50%, 1.02486 51%, 1.02272 52%, 1.02063 53%, 1.01861 54%, 1.01667 55.0%, 1.01482 56.0%, 1.01307 57.0%, 1.01142 58.0%, 1.00989 59%, 1.00846 60%, 1.00715 61%, 1.00594 62%, 1.00485 63%, 1.00386 64%, 1.00296 65%, 1.00217 66%, 1.00147 67%, 1.00085 68%, 1.00031 69%, .999849 70%, .999457 71%, .999128 72%, .998858 73%, .99864 74%, .99847 75%, .998342 76%, .998253 77%, .998196 78%, .998169 79%, .998167 80%, .998186 81%, .998224 82%, .998276 83%, .998341 84%, .998415 85%, .998497 86%, .998584 87%, .998675 88%, .998768 89%, .998861 90%, .998954 91%, .999045 92%, .999134 93%, .99922 94%, .999303 95%, .999381 96%, .999455 97%, .999525 98%, .999589 99%, .99965 100%);
    --easing-common: linear(0, 0, .0001, .0002, .0003, .0005, .0007, .001, .0013, .0016, .002, .0024, .0029, .0033, .0039, .0044, .005, .0057, .0063, .007, .0079, .0086, .0094, .0103, .0112, .0121, .0132 1.84%, .0153, .0175, .0201, .0226, .0253, .0283, .0313, .0345, .038, .0416, .0454, .0493, .0535, .0576, .0621, .0667, .0714, .0764, .0816 5.04%, .0897, .098 5.62%, .1071, .1165, .1263 6.56%, .137, .1481 7.25%, .1601 7.62%, .1706 7.94%, .1819 8.28%, .194, .2068 9.02%, .2331 9.79%, .2898 11.44%, .3151 12.18%, .3412 12.95%, .3533, .365 13.66%, .3786, .3918, .4045, .4167, .4288, .4405, .452, .4631 16.72%, .4759, .4884, .5005, .5124, .5242, .5354, .5467, .5576, .5686, .5791, .5894, .5995, .6094, .6194, .6289, .6385, .6477, .6569, .6659 24.45%, .6702, .6747, .6789, .6833, .6877, .6919, .696, .7002, .7043, .7084, .7125, .7165, .7205, .7244, .7283, .7321, .7358, .7396, .7433, .7471, .7507, .7544, .7579, .7615, .7649, .7685, .7718, .7752, .7786, .782, .7853, .7885, .7918, .7951, .7982, .8013, .8043, .8075, .8104, .8135, .8165, .8195, .8224, .8253, .8281, .8309, .8336, .8365, .8391, .8419, .8446, .8472, .8499, .8524, .855, .8575, .8599, .8625 37.27%, .8651, .8678, .8703, .8729, .8754, .8779, .8803, .8827, .8851, .8875, .8898, .892, .8942, .8965, .8987, .9009, .903, .9051, .9071, .9092, .9112, .9132, .9151, .9171, .919, .9209, .9227, .9245, .9262, .928, .9297, .9314, .9331, .9347, .9364, .9379, .9395, .941, .9425, .944, .9454, .9469, .9483, .9497, .951, .9524, .9537, .955, .9562, .9574, .9586, .9599, .961, .9622, .9633, .9644, .9655, .9665, .9676, .9686, .9696, .9705, .9715, .9724, .9733, .9742, .975, .9758, .9766, .9774, .9782, .9789, .9796, .9804, .9811, .9817, .9824, .9831, .9837, .9843, .9849, .9855, .986, .9866, .9871, .9877, .9882, .9887, .9892, .9896 70.56%, .9905 71.67%, .9914 72.82%, .9922, .9929 75.2%, .9936 76.43%, .9942 77.71%, .9948 79.03%, .9954 80.39%, .9959 81.81%, .9963 83.28%, .9968 84.82%, .9972 86.41%, .9975 88.07%, .9979 89.81%, .9982 91.64%, .9984 93.56%, .9987 95.58%, .9989 97.72%, .9991);
    --sharp-edge-top-shadow: 0 1px 0 var(--border-sharp);
    --sharp-edge-top-shadow-placeholder: 0 1px 0 transparent;
    --sharp-edge-bottom-shadow: 0 -1px 0 var(--border-sharp);
    --sharp-edge-bottom-shadow-placeholder: 0 -1px 0 transparent;
    --swiper-theme-color: #007aff;
    color-scheme: light;
    --cot-shimmer-duration: 1400ms;
    --scroll-root-safe-area-height: calc(100lvh - var(--scroll-root-safe-area-inset-top) - var(--scroll-root-safe-area-inset-bottom));
    --scroll-root-safe-area-inset-bottom: calc(var(--sticky-padding-bottom) + var(--screen-keyboard-height,0px) + env(safe-area-inset-bottom,0px));
    --scroll-root-safe-area-inset-top: calc(var(--sticky-padding-top) + env(safe-area-inset-top,0px));
    --sticky-padding-top: var(--header-height);
    --sticky-padding-bottom: 103.99305725097656px;
    --composer-footer_height: var(--composer-bar_footer-current-height,32px);
    --composer-bar_height: var(--composer-bar_current-height,52px);
    --composer-bar_width: var(--composer-bar_current-width,768px);
    --mask-fill: linear-gradient(to bottom, white 0%, white 100%);
    --mask-erase: linear-gradient(to bottom, black 0%, black 100%);
    --composer-overlap-px: 28px;
    --shadow-height: 45px;
    --thread-content-margin: var(--thread-content-margin-sm,calc(var(--spacing) * 6));
    --thread-content-max-width: 40rem;
    text-align: start;
    --interactive-bg-primary-default-context: var(--interactive-bg-primary-default);
    --interactive-bg-primary-hover-context: var(--interactive-bg-primary-hover);
    --interactive-bg-primary-press-context: var(--interactive-bg-primary-press);
    --interactive-bg-primary-inactive-context: var(--interactive-bg-primary-inactive);
    --interactive-bg-primary-selected-context: var(--interactive-bg-primary-selected);
    --interactive-bg-primary-inverted-context: var(--bg-primary-inverted);
    --interactive-border-primary-inverted-context: var(--bg-primary-inverted);
    --interactive-label-primary-context: var(--interactive-label-primary-default);
    --interactive-label-primary-inactive-context: var(--interactive-label-primary-inactive);
    --interactive-label-primary-inverted-context: var(--text-inverted);
    --interactive-icon-primary-context: var(--interactive-icon-primary-default);
    --interactive-icon-primary-inactive-context: var(--interactive-icon-primary-inactive);
    --interactive-icon-primary-inverted-context: var(--icon-inverted);
    --interactive-bg-danger-primary-default-context: var(--red-500);
    --interactive-bg-danger-primary-hover-context: var(--red-600);
    --interactive-bg-danger-primary-press-context: var(--red-500);
    --interactive-bg-danger-primary-inactive-context: #0d0d0d0f;
    --interactive-bg-danger-primary-selected-context: var(--red-500);
    --interactive-label-danger-primary-context: var(--white);
    --interactive-label-danger-primary-press-context: var(--white);
    --interactive-label-danger-primary-inactive-context: var(--gray-500);
    --interactive-icon-danger-primary-context: var(--white);
    --interactive-icon-danger-primary-press-context: var(--white);
    --interactive-icon-danger-primary-inactive-context: var(--gray-500);
    --interactive-bg-danger-soft-default-context: var(--red-50);
    --interactive-bg-danger-soft-hover-context: var(--red-75);
    --interactive-bg-danger-soft-press-context: var(--red-50);
    --interactive-bg-danger-soft-inactive-context: #0d0d0d0f;
    --interactive-bg-danger-soft-selected-context: var(--red-50);
    --interactive-label-danger-soft-context: var(--red-600);
    --interactive-label-danger-soft-press-context: var(--red-500);
    --interactive-label-danger-soft-inactive-context: var(--red-200);
    --interactive-icon-danger-soft-context: var(--red-600);
    --interactive-icon-danger-soft-press-context: var(--red-500);
    --interactive-icon-danger-soft-inactive-context: var(--red-200);
    --interactive-bg-danger-ghost-hover-context: var(--red-a50);
    --interactive-bg-danger-ghost-press-context: var(--red-a25);
    --interactive-bg-danger-ghost-selected-context: var(--red-a50);
    --interactive-label-danger-ghost-context: var(--red-500);
    --interactive-label-danger-ghost-press-context: var(--red-400);
    --interactive-label-danger-ghost-inactive-context: var(--gray-500);
    --interactive-icon-danger-ghost-context: var(--red-500);
    --interactive-icon-danger-ghost-press-context: var(--red-400);
    --interactive-icon-danger-ghost-inactive-context: var(--gray-500);
    --interactive-outline-focus-primary: var(--interactive-focus-ring-primary);
    --interactive-outline-focus-secondary: var(--interactive-focus-ring-secondary);
    --interactive-outline-focus-danger: var(--interactive-focus-ring-danger);
    --interactive-button-outline-focus-primary: var(--interactive-outline-focus-primary);
    --interactive-button-outline-focus-secondary: var(--interactive-outline-focus-secondary);
    --interactive-button-outline-focus-destructive: var(--interactive-outline-focus-danger);
    --interactive-button-outline-focus-sec-destructive: var(--interactive-outline-focus-danger);
    --interactive-button-outline-focus-danger-soft: var(--interactive-outline-focus-danger);
    --interactive-button-outline-focus-danger-ghost: var(--interactive-outline-focus-danger);
    --interactive-bg-default-primary: var(--interactive-bg-primary-default-context);
    --interactive-bg-default-secondary: var(--interactive-bg-secondary-default);
    --interactive-bg-default-accent: var(--interactive-bg-accent-default);
    --interactive-bg-default-control: var(--interactive-bg-control-default);
    --interactive-bg-default-primary-inverted: var(--interactive-bg-primary-inverted-context);
    --interactive-bg-default-danger-primary: var(--interactive-bg-danger-primary-default-context);
    --interactive-bg-default-danger-secondary: var(--interactive-bg-danger-secondary-default);
    --interactive-bg-default-danger-soft: var(--interactive-bg-danger-soft-default-context);
    --interactive-bg-default-danger-ghost: transparent;
    --interactive-bg-hover-primary: var(--interactive-bg-primary-hover-context);
    --interactive-bg-hover-secondary: var(--interactive-bg-secondary-hover);
    --interactive-bg-hover-accent: var(--interactive-bg-accent-hover);
    --interactive-bg-hover-primary-inverted: var(--interactive-bg-primary-inverted-context);
    --interactive-bg-hover-danger-primary: var(--interactive-bg-danger-primary-hover-context);
    --interactive-bg-hover-danger-secondary: var(--interactive-bg-danger-secondary-hover);
    --interactive-bg-hover-danger-soft: var(--interactive-bg-danger-soft-hover-context);
    --interactive-bg-hover-danger-ghost: var(--interactive-bg-danger-ghost-hover-context);
    --interactive-bg-press-primary: var(--interactive-bg-primary-press-context);
    --interactive-bg-press-secondary: var(--interactive-bg-secondary-press);
    --interactive-bg-press-accent: var(--interactive-bg-accent-press);
    --interactive-bg-press-primary-inverted: var(--interactive-bg-primary-inverted-context);
    --interactive-bg-press-danger-primary: var(--interactive-bg-danger-primary-press-context);
    --interactive-bg-press-danger-secondary: var(--interactive-bg-danger-secondary-press);
    --interactive-bg-press-danger-soft: var(--interactive-bg-danger-soft-press-context);
    --interactive-bg-press-danger-ghost: var(--interactive-bg-danger-ghost-press-context);
    --interactive-bg-inactive-primary: var(--interactive-bg-primary-inactive-context);
    --interactive-bg-inactive-secondary: var(--interactive-bg-secondary-inactive);
    --interactive-bg-inactive-accent: var(--interactive-bg-accent-inactive);
    --interactive-bg-inactive-primary-inverted: var(--interactive-bg-primary-inverted-context);
    --interactive-bg-inactive-danger-primary: var(--interactive-bg-danger-primary-inactive-context);
    --interactive-bg-inactive-danger-secondary: var(--interactive-bg-danger-secondary-inactive);
    --interactive-bg-inactive-danger-soft: var(--interactive-bg-danger-soft-inactive-context);
    --interactive-bg-inactive-danger-ghost: transparent;
    --interactive-bg-selected-primary: var(--interactive-bg-primary-selected-context);
    --interactive-bg-selected-secondary: var(--interactive-bg-secondary-selected);
    --interactive-bg-selected-accent: var(--interactive-bg-accent-default);
    --interactive-bg-selected-primary-inverted: var(--interactive-bg-primary-inverted-context);
    --interactive-bg-selected-danger-primary: var(--interactive-bg-danger-primary-selected-context);
    --interactive-bg-selected-danger-secondary: var(--interactive-bg-danger-secondary-default);
    --interactive-bg-selected-danger-soft: var(--interactive-bg-danger-soft-selected-context);
    --interactive-bg-selected-danger-ghost: var(--interactive-bg-danger-ghost-selected-context);
    --interactive-button-bg-default-primary: var(--interactive-bg-default-primary);
    --interactive-button-bg-default-secondary: var(--interactive-bg-secondary-default);
    --interactive-button-bg-default-destructive: var(--interactive-bg-default-danger-primary);
    --interactive-button-bg-default-sec-destructive: var(--interactive-bg-default-danger-secondary);
    --interactive-button-bg-default-danger-soft: var(--interactive-bg-default-danger-soft);
    --interactive-button-bg-default-danger-ghost: var(--interactive-bg-default-danger-ghost);
    --interactive-button-bg-hover-primary: var(--interactive-bg-hover-primary);
    --interactive-button-bg-hover-secondary: var(--interactive-bg-secondary-hover);
    --interactive-button-bg-hover-destructive: var(--interactive-bg-hover-danger-primary);
    --interactive-button-bg-hover-sec-destructive: var(--interactive-bg-hover-danger-secondary);
    --interactive-button-bg-hover-danger-soft: var(--interactive-bg-hover-danger-soft);
    --interactive-button-bg-hover-danger-ghost: var(--interactive-bg-hover-danger-ghost);
    --interactive-button-bg-press-primary: var(--interactive-bg-press-primary);
    --interactive-button-bg-press-secondary: var(--interactive-bg-secondary-press);
    --interactive-button-bg-press-destructive: var(--interactive-bg-press-danger-primary);
    --interactive-button-bg-press-sec-destructive: var(--interactive-bg-press-danger-secondary);
    --interactive-button-bg-press-danger-soft: var(--interactive-bg-press-danger-soft);
    --interactive-button-bg-press-danger-ghost: var(--interactive-bg-press-danger-ghost);
    --interactive-button-bg-inactive-primary: var(--interactive-bg-inactive-primary);
    --interactive-button-bg-inactive-secondary: var(--interactive-bg-secondary-inactive);
    --interactive-button-bg-inactive-destructive: var(--interactive-bg-inactive-danger-primary);
    --interactive-button-bg-inactive-sec-destructive: var(--interactive-bg-inactive-danger-secondary);
    --interactive-button-bg-inactive-danger-soft: var(--interactive-bg-inactive-danger-soft);
    --interactive-button-bg-inactive-danger-ghost: var(--interactive-bg-inactive-danger-ghost);
    --interactive-button-bg-selected-primary: var(--interactive-bg-selected-primary);
    --interactive-button-bg-selected-secondary: var(--interactive-bg-secondary-selected);
    --interactive-button-bg-selected-destructive: var(--interactive-bg-selected-danger-primary);
    --interactive-button-bg-selected-sec-destructive: var(--interactive-bg-selected-danger-secondary);
    --interactive-button-bg-selected-danger-soft: var(--interactive-bg-selected-danger-soft);
    --interactive-button-bg-selected-danger-ghost: var(--interactive-bg-selected-danger-ghost);
    --interactive-border-default-secondary: var(--interactive-border-secondary-default);
    --interactive-border-default-primary-inverted: var(--interactive-border-primary-inverted-context);
    --interactive-border-default-tertiary: var(--interactive-border-tertiary-default);
    --interactive-border-default-danger-secondary: var(--interactive-border-danger-secondary-default);
    --interactive-border-default-danger-soft: transparent;
    --interactive-border-hover-secondary: var(--interactive-border-secondary-hover);
    --interactive-border-hover-primary-inverted: var(--interactive-border-primary-inverted-context);
    --interactive-border-hover-tertiary: var(--interactive-border-tertiary-hover);
    --interactive-border-hover-danger-secondary: var(--interactive-border-danger-secondary-hover);
    --interactive-border-hover-danger-soft: transparent;
    --interactive-border-press-secondary: var(--interactive-border-secondary-press);
    --interactive-border-press-primary-inverted: var(--interactive-border-primary-inverted-context);
    --interactive-border-press-tertiary: var(--interactive-border-tertiary-press);
    --interactive-border-press-danger-secondary: var(--interactive-border-danger-secondary-press);
    --interactive-border-press-danger-soft: transparent;
    --interactive-border-inactive-secondary: var(--interactive-border-secondary-inactive);
    --interactive-border-inactive-primary-inverted: var(--interactive-border-primary-inverted-context);
    --interactive-border-inactive-tertiary: var(--interactive-border-tertiary-inactive);
    --interactive-border-inactive-danger-secondary: var(--interactive-border-danger-secondary-inactive);
    --interactive-border-inactive-danger-soft: transparent;
    --interactive-border-selected-secondary: var(--interactive-border-secondary-default);
    --interactive-border-selected-primary-inverted: var(--interactive-border-primary-inverted-context);
    --interactive-border-selected-tertiary: var(--interactive-border-tertiary-default);
    --interactive-border-selected-danger-secondary: var(--interactive-border-danger-secondary-default);
    --interactive-border-selected-danger-soft: transparent;
    --interactive-button-border-default-primary: transparent;
    --interactive-button-border-default-secondary: var(--interactive-border-secondary-default);
    --interactive-button-border-default-destructive: transparent;
    --interactive-button-border-default-sec-destructive: var(--interactive-border-danger-secondary-default);
    --interactive-button-border-default-danger-soft: var(--interactive-border-default-danger-soft);
    --interactive-button-border-default-danger-ghost: transparent;
    --interactive-button-border-hover-primary: transparent;
    --interactive-button-border-hover-secondary: var(--interactive-border-secondary-hover);
    --interactive-button-border-hover-destructive: transparent;
    --interactive-button-border-hover-sec-destructive: var(--interactive-border-danger-secondary-hover);
    --interactive-button-border-hover-danger-soft: var(--interactive-border-hover-danger-soft);
    --interactive-button-border-hover-danger-ghost: transparent;
    --interactive-button-border-press-primary: transparent;
    --interactive-button-border-press-secondary: var(--interactive-border-secondary-press);
    --interactive-button-border-press-destructive: transparent;
    --interactive-button-border-press-sec-destructive: var(--interactive-border-danger-secondary-press);
    --interactive-button-border-press-danger-soft: var(--interactive-border-press-danger-soft);
    --interactive-button-border-press-danger-ghost: transparent;
    --interactive-button-border-inactive-primary: transparent;
    --interactive-button-border-inactive-secondary: var(--interactive-border-secondary-inactive);
    --interactive-button-border-inactive-destructive: transparent;
    --interactive-button-border-inactive-sec-destructive: var(--interactive-border-danger-secondary-inactive);
    --interactive-button-border-inactive-danger-soft: var(--interactive-border-inactive-danger-soft);
    --interactive-button-border-inactive-danger-ghost: transparent;
    --interactive-button-border-selected-primary: transparent;
    --interactive-button-border-selected-secondary: var(--interactive-border-secondary-default);
    --interactive-button-border-selected-destructive: transparent;
    --interactive-button-border-selected-sec-destructive: var(--interactive-border-danger-secondary-default);
    --interactive-button-border-selected-danger-soft: var(--interactive-border-selected-danger-soft);
    --interactive-button-border-selected-danger-ghost: transparent;
    --interactive-label-default-primary: var(--interactive-label-primary-context);
    --interactive-label-default-secondary: var(--interactive-label-secondary-default);
    --interactive-label-default-primary-inverted: var(--interactive-label-primary-inverted-context);
    --interactive-label-default-tertiary: var(--interactive-label-tertiary-default);
    --interactive-label-default-accent: var(--interactive-label-accent-default);
    --interactive-label-default-danger-primary: var(--interactive-label-danger-primary-context);
    --interactive-label-default-danger-secondary: var(--interactive-label-danger-secondary-default);
    --interactive-label-default-danger-soft: var(--interactive-label-danger-soft-context);
    --interactive-label-default-danger-ghost: var(--interactive-label-danger-ghost-context);
    --interactive-label-hover-primary: var(--interactive-label-primary-context);
    --interactive-label-hover-secondary: var(--interactive-label-secondary-hover);
    --interactive-label-hover-primary-inverted: var(--interactive-label-primary-inverted-context);
    --interactive-label-hover-tertiary: var(--interactive-label-tertiary-hover);
    --interactive-label-hover-accent: var(--interactive-label-accent-hover);
    --interactive-label-hover-danger-primary: var(--interactive-label-danger-primary-context);
    --interactive-label-hover-danger-secondary: var(--interactive-label-danger-secondary-hover);
    --interactive-label-hover-danger-soft: var(--interactive-label-default-danger-soft);
    --interactive-label-hover-danger-ghost: var(--interactive-label-danger-ghost-context);
    --interactive-label-press-primary: var(--interactive-label-primary-context);
    --interactive-label-press-secondary: var(--interactive-label-secondary-press);
    --interactive-label-press-primary-inverted: var(--interactive-label-primary-inverted-context);
    --interactive-label-press-tertiary: var(--interactive-label-tertiary-press);
    --interactive-label-press-accent: var(--interactive-label-accent-press);
    --interactive-label-press-danger-primary: var(--interactive-label-danger-primary-press-context);
    --interactive-label-press-danger-secondary: var(--interactive-label-danger-secondary-press);
    --interactive-label-press-danger-soft: var(--interactive-label-danger-soft-press-context);
    --interactive-label-press-danger-ghost: var(--interactive-label-danger-ghost-press-context);
    --interactive-label-inactive-primary: var(--interactive-label-primary-inactive-context);
    --interactive-label-inactive-primary-inverted: var(--interactive-label-primary-inverted-context);
    --interactive-label-inactive-secondary: var(--interactive-label-secondary-inactive);
    --interactive-label-inactive-tertiary: var(--interactive-label-tertiary-inactive);
    --interactive-label-inactive-accent: var(--interactive-label-accent-inactive);
    --interactive-label-inactive-danger-primary: var(--interactive-label-danger-primary-inactive-context);
    --interactive-label-inactive-danger-secondary: var(--interactive-label-danger-secondary-inactive);
    --interactive-label-inactive-danger-soft: var(--interactive-label-danger-soft-inactive-context);
    --interactive-label-inactive-danger-ghost: var(--interactive-label-danger-ghost-inactive-context);
    --interactive-label-selected-primary: var(--interactive-label-primary-context);
    --interactive-label-selected-primary-inverted: var(--interactive-label-primary-inverted-context);
    --interactive-label-selected-secondary: var(--interactive-label-secondary-selected);
    --interactive-label-selected-tertiary: var(--interactive-label-tertiary-selected);
    --interactive-label-selected-accent: var(--interactive-label-accent-selected);
    --interactive-label-selected-danger-primary: var(--interactive-label-danger-primary-context);
    --interactive-label-selected-danger-secondary: var(--interactive-label-danger-secondary-default);
    --interactive-label-selected-danger-soft: var(--interactive-label-default-danger-soft);
    --interactive-label-selected-danger-ghost: var(--interactive-label-danger-ghost-context);
    --interactive-button-label-default-primary: var(--interactive-label-default-primary);
    --interactive-button-label-default-secondary: var(--interactive-label-secondary-default);
    --interactive-button-label-default-destructive: var(--interactive-label-default-danger-primary);
    --interactive-button-label-default-sec-destructive: var(--interactive-label-default-danger-secondary);
    --interactive-button-label-default-danger-soft: var(--interactive-label-default-danger-soft);
    --interactive-button-label-default-danger-ghost: var(--interactive-label-default-danger-ghost);
    --interactive-button-label-hover-primary: var(--interactive-label-hover-primary);
    --interactive-button-label-hover-secondary: var(--interactive-label-secondary-hover);
    --interactive-button-label-hover-destructive: var(--interactive-label-hover-danger-primary);
    --interactive-button-label-hover-sec-destructive: var(--interactive-label-hover-danger-secondary);
    --interactive-button-label-hover-danger-soft: var(--interactive-label-hover-danger-soft);
    --interactive-button-label-hover-danger-ghost: var(--interactive-label-hover-danger-ghost);
    --interactive-button-label-press-primary: var(--interactive-label-press-primary);
    --interactive-button-label-press-secondary: var(--interactive-label-secondary-press);
    --interactive-button-label-press-destructive: var(--interactive-label-press-danger-primary);
    --interactive-button-label-press-sec-destructive: var(--interactive-label-press-danger-secondary);
    --interactive-button-label-press-danger-soft: var(--interactive-label-press-danger-soft);
    --interactive-button-label-press-danger-ghost: var(--interactive-label-press-danger-ghost);
    --interactive-button-label-inactive-primary: var(--interactive-label-inactive-primary);
    --interactive-button-label-inactive-secondary: var(--interactive-label-secondary-inactive);
    --interactive-button-label-inactive-destructive: var(--interactive-label-inactive-danger-primary);
    --interactive-button-label-inactive-sec-destructive: var(--interactive-label-inactive-danger-secondary);
    --interactive-button-label-inactive-danger-soft: var(--interactive-label-inactive-danger-soft);
    --interactive-button-label-inactive-danger-ghost: var(--interactive-label-inactive-danger-ghost);
    --interactive-button-label-selected-primary: var(--interactive-label-selected-primary);
    --interactive-button-label-selected-secondary: var(--interactive-label-secondary-selected);
    --interactive-button-label-selected-destructive: var(--interactive-label-selected-danger-primary);
    --interactive-button-label-selected-sec-destructive: var(--interactive-label-selected-danger-secondary);
    --interactive-button-label-selected-danger-soft: var(--interactive-label-selected-danger-soft);
    --interactive-button-label-selected-danger-ghost: var(--interactive-label-selected-danger-ghost);
    --interactive-icon-default-primary: var(--interactive-icon-primary-context);
    --interactive-icon-default-secondary: var(--interactive-icon-secondary-default);
    --interactive-icon-default-primary-inverted: var(--interactive-icon-primary-inverted-context);
    --interactive-icon-default-tertiary: var(--interactive-icon-tertiary-default);
    --interactive-icon-default-accent: var(--interactive-icon-accent-default);
    --interactive-icon-default-danger-primary: var(--interactive-icon-danger-primary-context);
    --interactive-icon-default-danger-secondary: var(--interactive-icon-danger-secondary-default);
    --interactive-icon-default-danger-soft: var(--interactive-icon-danger-soft-context);
    --interactive-icon-default-danger-ghost: var(--interactive-icon-danger-ghost-context);
    --interactive-icon-hover-primary: var(--interactive-icon-primary-context);
    --interactive-icon-hover-secondary: var(--interactive-icon-secondary-hover);
    --interactive-icon-hover-primary-inverted: var(--interactive-icon-primary-inverted-context);
    --interactive-icon-hover-tertiary: var(--interactive-icon-tertiary-hover);
    --interactive-icon-hover-accent: var(--interactive-icon-accent-hover);
    --interactive-icon-hover-danger-primary: var(--interactive-icon-danger-primary-context);
    --interactive-icon-hover-danger-secondary: var(--interactive-icon-danger-secondary-hover);
    --interactive-icon-hover-danger-soft: var(--interactive-icon-default-danger-soft);
    --interactive-icon-hover-danger-ghost: var(--interactive-icon-danger-ghost-context);
    --interactive-icon-press-primary: var(--interactive-icon-primary-context);
    --interactive-icon-press-secondary: var(--interactive-icon-secondary-press);
    --interactive-icon-press-primary-inverted: var(--interactive-icon-primary-inverted-context);
    --interactive-icon-press-tertiary: var(--interactive-icon-tertiary-press);
    --interactive-icon-press-accent: var(--interactive-icon-accent-press);
    --interactive-icon-press-danger-primary: var(--interactive-icon-danger-primary-press-context);
    --interactive-icon-press-danger-secondary: var(--interactive-icon-danger-secondary-press);
    --interactive-icon-press-danger-soft: var(--interactive-icon-danger-soft-press-context);
    --interactive-icon-press-danger-ghost: var(--interactive-icon-danger-ghost-press-context);
    --interactive-icon-inactive-primary: var(--interactive-icon-primary-inactive-context);
    --interactive-icon-inactive-primary-inverted: var(--interactive-icon-primary-inverted-context);
    --interactive-icon-inactive-secondary: var(--interactive-icon-secondary-inactive);
    --interactive-icon-inactive-tertiary: var(--interactive-icon-tertiary-inactive);
    --interactive-icon-inactive-accent: var(--interactive-icon-accent-inactive);
    --interactive-icon-inactive-danger-primary: var(--interactive-icon-danger-primary-inactive-context);
    --interactive-icon-inactive-danger-secondary: var(--interactive-icon-danger-secondary-inactive);
    --interactive-icon-inactive-danger-soft: var(--interactive-icon-danger-soft-inactive-context);
    --interactive-icon-inactive-danger-ghost: var(--interactive-icon-danger-ghost-inactive-context);
    --interactive-icon-selected-primary: var(--interactive-icon-primary-context);
    --interactive-icon-selected-primary-inverted: var(--interactive-icon-primary-inverted-context);
    --interactive-icon-selected-secondary: var(--interactive-icon-secondary-selected);
    --interactive-icon-selected-tertiary: var(--interactive-icon-tertiary-selected);
    --interactive-icon-selected-accent: var(--interactive-icon-accent-selected);
    --interactive-icon-selected-danger-primary: var(--interactive-icon-danger-primary-context);
    --interactive-icon-selected-danger-secondary: var(--interactive-icon-danger-secondary-default);
    --interactive-icon-selected-danger-soft: var(--interactive-icon-default-danger-soft);
    --interactive-icon-selected-danger-ghost: var(--interactive-icon-danger-ghost-context);
    --interactive-button-icon-default-primary: var(--interactive-icon-default-primary);
    --interactive-button-icon-default-secondary: var(--interactive-icon-secondary-default);
    --interactive-button-icon-default-destructive: var(--interactive-icon-default-danger-primary);
    --interactive-button-icon-default-sec-destructive: var(--interactive-icon-default-danger-secondary);
    --interactive-button-icon-default-danger-soft: var(--interactive-icon-default-danger-soft);
    --interactive-button-icon-default-danger-ghost: var(--interactive-icon-default-danger-ghost);
    --interactive-button-icon-hover-primary: var(--interactive-icon-hover-primary);
    --interactive-button-icon-hover-secondary: var(--interactive-icon-secondary-hover);
    --interactive-button-icon-hover-destructive: var(--interactive-icon-hover-danger-primary);
    --interactive-button-icon-hover-sec-destructive: var(--interactive-icon-hover-danger-secondary);
    --interactive-button-icon-hover-danger-soft: var(--interactive-icon-hover-danger-soft);
    --interactive-button-icon-hover-danger-ghost: var(--interactive-icon-hover-danger-ghost);
    --interactive-button-icon-press-primary: var(--interactive-icon-press-primary);
    --interactive-button-icon-press-secondary: var(--interactive-icon-secondary-press);
    --interactive-button-icon-press-destructive: var(--interactive-icon-press-danger-primary);
    --interactive-button-icon-press-sec-destructive: var(--interactive-icon-press-danger-secondary);
    --interactive-button-icon-press-danger-soft: var(--interactive-icon-press-danger-soft);
    --interactive-button-icon-press-danger-ghost: var(--interactive-icon-press-danger-ghost);
    --interactive-button-icon-inactive-primary: var(--interactive-icon-inactive-primary);
    --interactive-button-icon-inactive-secondary: var(--interactive-icon-secondary-inactive);
    --interactive-button-icon-inactive-destructive: var(--interactive-icon-inactive-danger-primary);
    --interactive-button-icon-inactive-sec-destructive: var(--interactive-icon-inactive-danger-secondary);
    --interactive-button-icon-inactive-danger-soft: var(--interactive-icon-inactive-danger-soft);
    --interactive-button-icon-inactive-danger-ghost: var(--interactive-icon-inactive-danger-ghost);
    --interactive-button-icon-selected-primary: var(--interactive-icon-selected-primary);
    --interactive-button-icon-selected-secondary: var(--interactive-icon-secondary-selected);
    --interactive-button-icon-selected-destructive: var(--interactive-icon-selected-danger-primary);
    --interactive-button-icon-selected-sec-destructive: var(--interactive-icon-selected-danger-secondary);
    --interactive-button-icon-selected-danger-soft: var(--interactive-icon-selected-danger-soft);
    --interactive-button-icon-selected-danger-ghost: var(--interactive-icon-selected-danger-ghost);
    --main-surface-background: #fffffff2;
    --message-surface: #e9e9e980;
    --composer-surface: var(--message-surface);
    --composer-blue-bg: #daeeff;
    --composer-blue-hover: #bddcf4;
    --composer-blue-hover-tint: #0084ff24;
    --composer-surface-primary: var(--main-surface-primary);
    --dot-color: var(--black);
    --icon-surface: 13 13 13;
    --text-primary-inverse: var(--gray-100);
    --content-primary: #01172b;
    --content-secondary: #44505b;
    --text-quaternary: #00000030;
    --tag-blue: #08f;
    --tag-blue-light: #0af;
    --text-error: #f93a37;
    --text-danger: var(--red-500);
    --text-placeholder: #000000b3;
    --surface-error: 249 58 55;
    --border-xlight: #0000000d;
    --border-medium: #00000026;
    --border-xheavy: #00000040;
    --hint-text: #08f;
    --hint-bg: #b3dbff;
    --border-sharp: #0000000d;
    --main-surface-primary: var(--white);
    --main-surface-primary-inverse: var(--gray-800);
    --main-surface-secondary: var(--gray-50);
    --main-surface-secondary-selected: #0000001a;
    --main-surface-tertiary: var(--gray-100);
    --sidebar-surface-primary: var(--gray-50);
    --sidebar-surface-secondary: var(--gray-100);
    --sidebar-surface-tertiary: var(--gray-200);
    --sidebar-title-primary: #28282880;
    --sidebar-surface: #fcfcfc;
    --sidebar-body-primary: #0d0d0d;
    --sidebar-icon: #7d7d7d;
    --surface-hover: #00000012;
    --link: #2964aa;
    --link-hover: #749ac8;
    --selection: #007aff;
    --scrollbar-color: #0000001a;
    --scrollbar-color-hover: #0003;
    --sidebar-surface-floating-lightness: 1;
    --sidebar-surface-floating-alpha: 1;
    --sidebar-surface-pinned-lightness: .99;
    --sidebar-surface-pinned-alpha: 1;
    --tw-prose-body: var(--text-primary);
    --tw-prose-headings: var(--text-primary);
    --tw-prose-lead: var(--text-primary);
    --tw-prose-links: var(--text-primary);
    --tw-prose-bold: var(--text-primary);
    --tw-prose-counters: var(--text-primary);
    --tw-prose-bullets: var(--text-primary);
    --tw-prose-hr: var(--border-xheavy);
    --tw-prose-quotes: var(--text-primary);
    --tw-prose-captions: var(--text-secondary);
    --tw-prose-code: var(--text-primary);
    --tw-prose-invert-body: var(--text-primary);
    --tw-prose-invert-headings: var(--text-primary);
    --tw-prose-invert-lead: var(--text-primary);
    --tw-prose-invert-links: var(--text-primary);
    --tw-prose-invert-bold: var(--text-primary);
    --tw-prose-invert-counters: var(--text-primary);
    --tw-prose-invert-bullets: var(--text-primary);
    --tw-prose-invert-hr: var(--border-xheavy);
    --tw-prose-invert-quotes: var(--text-primary);
    --tw-prose-invert-captions: var(--text-secondary);
    --tw-prose-invert-kbd: #fff;
    --tw-prose-invert-kbd-shadows: #ffffff1a;
    --tw-prose-invert-code: var(--text-primary);
    --tw-prose-invert-pre-bg: #00000080;
    --tw-prose-quote-borders: lab(91.6229% -.159115 -2.26791);
    --tw-prose-kbd: lab(8.11897% .811279 -12.254);
    --tw-prose-kbd-shadows: lab(8.11897% .811279 -12.254/.1);
    --tw-prose-pre-code: lab(91.6229% -.159115 -2.26791);
    --tw-prose-pre-bg: lab(16.1051% -1.18239 -11.7533);
    --tw-prose-th-borders: lab(85.1236% -.612259 -3.7138);
    --tw-prose-td-borders: lab(91.6229% -.159115 -2.26791);
    --tw-prose-invert-quote-borders: lab(27.1134% -.956401 -12.3224);
    --tw-prose-invert-pre-code: lab(85.1236% -.612259 -3.7138);
    --tw-prose-invert-th-borders: lab(35.6337% -1.58697 -10.8425);
    --tw-prose-invert-td-borders: lab(27.1134% -.956401 -12.3224);
    overflow-wrap: break-word;
    --bg-primary: #fff;
    --bg-primary-inverted: #000;
    --bg-secondary: #e8e8e8;
    --bg-tertiary: #f3f3f3;
    --bg-scrim: #0d0d0d80;
    --bg-elevated-primary: #fff;
    --bg-elevated-secondary: #f9f9f9;
    --bg-accent-static: var(--blue-400);
    --bg-status-warning: var(--orange-25);
    --bg-status-error: var(--red-25);
    --border-default: #0d0d0d1a;
    --border-heavy: #0d0d0d26;
    --border-light: #0d0d0d0d;
    --border-status-warning: var(--orange-50);
    --border-status-error: var(--red-50);
    --text-primary: #0d0d0d;
    --text-secondary: #5d5d5d;
    --text-tertiary: #8f8f8f;
    --text-inverted: #fff;
    --text-inverted-static: #fff;
    --text-accent: var(--blue-200);
    --text-status-warning: var(--orange-500);
    --text-status-error: var(--red-500);
    --icon-primary: #0d0d0d;
    --icon-secondary: #5d5d5d;
    --icon-tertiary: #8f8f8f;
    --interactive-bg-control-default: var(--gray-200);
    --icon-inverted: #fff;
    --icon-inverted-static: #fff;
    --icon-accent: var(--blue-400);
    --icon-status-warning: var(--orange-500);
    --icon-status-error: var(--red-500);
    --interactive-bg-primary-default: #0d0d0d;
    --interactive-bg-primary-hover: #0d0d0dcc;
    --interactive-bg-primary-press: #0d0d0de5;
    --interactive-bg-primary-inactive: #0d0d0d;
    --interactive-bg-primary-selected: #0d0d0d;
    --interactive-bg-secondary-default: #0d0d0d00;
    --interactive-bg-secondary-hover: #0d0d0d05;
    --interactive-bg-secondary-press: #0d0d0d0d;
    --interactive-bg-secondary-inactive: #0d0d0d00;
    --interactive-bg-secondary-selected: #0d0d0d0d;
    --interactive-bg-tertiary-default: #fff;
    --interactive-bg-tertiary-hover: #f9f9f9;
    --interactive-bg-tertiary-press: #f3f3f3;
    --interactive-bg-tertiary-inactive: #fff;
    --interactive-bg-tertiary-selected: #fff;
    --interactive-bg-accent-default: var(--blue-50);
    --interactive-bg-accent-hover: var(--blue-75);
    --interactive-bg-accent-muted-hover: #ebf4ff;
    --interactive-bg-accent-muted-context: #ebf4ff80;
    --interactive-bg-accent-press: var(--blue-100);
    --interactive-bg-accent-muted-press: #e0efff;
    --interactive-bg-accent-inactive: var(--blue-50);
    --interactive-bg-danger-primary-default: var(--red-500);
    --interactive-bg-danger-primary-hover: var(--red-400);
    --interactive-bg-danger-primary-press: var(--red-600);
    --interactive-bg-danger-primary-inactive: var(--red-500);
    --interactive-bg-danger-secondary-default: #0d0d0d00;
    --interactive-bg-danger-secondary-hover: #0d0d0d00;
    --interactive-bg-danger-secondary-press: #0d0d0d00;
    --interactive-bg-danger-secondary-inactive: #0d0d0d00;
    --interactive-focus-ring-primary: #0d0d0d29;
    --interactive-focus-ring-secondary: #0d0d0d1f;
    --interactive-focus-ring-danger: var(--red-500);
    --interactive-border-focus: #0d0d0d;
    --interactive-border-secondary-default: #0d0d0d1a;
    --interactive-border-secondary-hover: #0d0d0d0d;
    --interactive-border-secondary-press: #0d0d0d0d;
    --interactive-border-secondary-inactive: #0d0d0d1a;
    --interactive-border-tertiary-default: #0d0d0d1a;
    --interactive-border-tertiary-hover: #0d0d0d1a;
    --interactive-border-tertiary-press: #0d0d0d0d;
    --interactive-border-tertiary-inactive: #0d0d0d1a;
    --interactive-border-danger-secondary-default: var(--red-500);
    --interactive-border-danger-secondary-hover: var(--red-400);
    --interactive-border-danger-secondary-press: var(--red-600);
    --interactive-border-danger-secondary-inactive: var(--red-500);
    --interactive-label-primary-default: #fff;
    --interactive-label-primary-hover: #fff;
    --interactive-label-primary-press: #fff;
    --interactive-label-primary-inactive: #fff;
    --interactive-label-primary-selected: #fff;
    --interactive-label-secondary-default: #0d0d0d;
    --interactive-label-secondary-hover: #0d0d0de5;
    --interactive-label-secondary-press: #0d0d0dcc;
    --interactive-label-secondary-inactive: #0d0d0d;
    --interactive-label-secondary-selected: #0d0d0d;
    --interactive-label-tertiary-default: #5d5d5d;
    --interactive-label-tertiary-hover: #5d5d5d;
    --interactive-label-tertiary-press: #5d5d5d;
    --interactive-label-tertiary-inactive: #5d5d5d;
    --interactive-label-tertiary-selected: #5d5d5d;
    --interactive-label-accent-default: var(--blue-400);
    --interactive-label-accent-hover: var(--blue-400);
    --interactive-label-accent-press: var(--blue-400);
    --interactive-label-accent-inactive: var(--blue-400);
    --interactive-label-accent-selected: var(--blue-400);
    --interactive-label-danger-primary-default: #fff;
    --interactive-label-danger-primary-hover: #fff;
    --interactive-label-danger-primary-press: #fff;
    --interactive-label-danger-primary-inactive: #fff;
    --interactive-label-danger-secondary-default: var(--red-500);
    --interactive-label-danger-secondary-hover: var(--red-400);
    --interactive-label-danger-secondary-press: var(--red-600);
    --interactive-label-danger-secondary-inactive: var(--red-500);
    --interactive-icon-primary-default: #fff;
    --interactive-icon-primary-hover: #fff;
    --interactive-icon-primary-press: #fff;
    --interactive-icon-primary-selected: #fff;
    --interactive-icon-primary-inactive: #fff;
    --interactive-icon-secondary-default: #0d0d0d;
    --interactive-icon-secondary-hover: #0d0d0de5;
    --interactive-icon-secondary-press: #0d0d0dcc;
    --interactive-icon-secondary-selected: #0d0d0d;
    --interactive-icon-secondary-inactive: #0d0d0d;
    --interactive-icon-tertiary-default: #5d5d5d;
    --interactive-icon-tertiary-hover: #5d5d5d;
    --interactive-icon-tertiary-press: #5d5d5d;
    --interactive-icon-tertiary-selected: #5d5d5d;
    --interactive-icon-tertiary-inactive: #5d5d5d;
    --interactive-icon-accent-default: var(--blue-400);
    --interactive-icon-accent-hover: var(--blue-400);
    --interactive-icon-accent-press: var(--blue-400);
    --interactive-icon-accent-selected: var(--blue-400);
    --interactive-icon-accent-inactive: var(--blue-400);
    --interactive-icon-danger-primary-default: #fff;
    --interactive-icon-danger-primary-hover: #fff;
    --interactive-icon-danger-primary-press: #fff;
    --interactive-icon-danger-primary-inactive: #fff;
    --interactive-icon-danger-secondary-default: var(--red-500);
    --interactive-icon-danger-secondary-hover: var(--red-400);
    --interactive-icon-danger-secondary-press: var(--red-600);
    --interactive-icon-danger-secondary-inactive: var(--red-500);
    --utility-scrollbar: #0000000a;
    font-feature-settings: var(--default-mono-font-feature-settings,normal);
    font-variation-settings: var(--default-mono-font-variation-settings,normal);
    color: #e6edf3;
    background-color: #1e1e2e;
    font-size: .875em;
    font-weight: 400;
    line-height: 1.6;
    font-family: ui-monospace,SFMono-Regular,SF Mono,Menlo,Consolas,Liberation Mono,monospace!important;
    box-sizing: border-box;
    border: 0 solid;
    margin: 12px 0;
    padding: 0;
    border-radius: 8px;
    overflow: hidden;
    }

.chatCode .chatCode-header{
    display: flex;
    align-items: center;
    justify-content: space-between;
    background: #2b2b3d;
    padding: 6px 16px;
    font-size: 12px;
    color: #a0a0b8;
    font-family: ui-sans-serif,-apple-system,system-ui,Segoe UI,Helvetica,Arial,sans-serif;
    border-bottom: 1px solid #3a3a50;
}

.chatCode .chatCode-header .chatCode-lang{
    font-weight: 600;
    text-transform: lowercase;
}

.chatCode .chatCode-header .chatCode-copy{
    cursor: pointer;
    background: none;
    border: none;
    color: #a0a0b8;
    font-size: 12px;
    padding: 2px 8px;
    border-radius: 4px;
    transition: background .15s, color .15s;
}

.chatCode .chatCode-header .chatCode-copy:hover{
    background: #3a3a50;
    color: #e6edf3;
}

.chatCode pre{
    margin: 0;
    margin-left:10px;
    padding: 1px;
    overflow-x: auto;
    background: transparent;
    border: none;
    color: #e6edf3;
    font-size: inherit;
    line-height: inherit;
    font-family: inherit;
    white-space: pre;
    tab-size: 4;
}

.chatCode pre::-webkit-scrollbar{
    height: 6px;
}

.chatCode pre::-webkit-scrollbar-track{
    background: transparent;
}

.chatCode pre::-webkit-scrollbar-thumb{
    background: #3a3a50;
    border-radius: 3px;
}

.chatCode2{
    color: #d4d4d4;
    background-color: #1e1e2e;
    font-size: .875em;
    font-weight: 400;
    line-height: 1.6;
    font-family: ui-monospace,SFMono-Regular,SF Mono,Menlo,Consolas,Liberation Mono,monospace!important;
    box-sizing: border-box;
    border: 0 solid;
    margin: 12px 0;
    padding: 0;
    border-radius: 8px;
    overflow: hidden;
}

.chatCode2 .chatCode-header{
    display: flex;
    align-items: center;
    justify-content: space-between;
    background: #2b2b3d;
    padding: 6px 16px;
    font-size: 12px;
    color: #a0a0b8;
    font-family: ui-sans-serif,-apple-system,system-ui,Segoe UI,Helvetica,Arial,sans-serif;
    border-bottom: 1px solid #3a3a50;
}

.chatCode2 .chatCode-header .chatCode-lang{
    font-weight: 600;
    text-transform: lowercase;
}

.chatCode2 .chatCode-header .chatCode-copy{
    cursor: pointer;
    background: none;
    border: none;
    color: #a0a0b8;
    font-size: 12px;
    padding: 2px 8px;
    border-radius: 4px;
    transition: background .15s, color .15s;
}

.chatCode2 .chatCode-header .chatCode-copy:hover{
    background: #3a3a50;
    color: #d4d4d4;
}

.chatCode2 pre{
    margin: 0;
    padding: 16px;
    overflow-x: auto;
    background: transparent;
    border: none;
    color: #d4d4d4;
    font-size: inherit;
    line-height: inherit;
    font-family: inherit;
    white-space: pre;
    tab-size: 4;
}

.chatCode2 pre::-webkit-scrollbar{
    height: 6px;
}

.chatCode2 pre::-webkit-scrollbar-track{
    background: transparent;
}

.chatCode2 pre::-webkit-scrollbar-thumb{
    background: #3a3a50;
    border-radius: 3px;
}

/* inline code (single backtick) */
.chatCode code.inline,
.chatCode2 code.inline{
    background: rgba(255,255,255,0.08);
    padding: 2px 6px;
    border-radius: 4px;
    font-size: .9em;
}

/* =========================
   OPTIONAL SEPARATOR
========================= */

.ip-context-menu .separator {
    height: 1px;
    background: rgba(0,0,0,0.06);
    margin: 6px 0;
}


/* =========================
   SMALL ARROW INDICATOR
========================= */

.ip-context-menu::before {
    content: \"\";
    position: absolute;
    top: -6px;
    left: 20px;
    width: 10px;
    height: 10px;
    background: #ffffff;
    transform: rotate(45deg);
    border-left: 1px solid rgba(0,0,0,0.06);
    border-top: 1px solid rgba(0,0,0,0.06);
}


/* FontAwesome-like custom icon: DecisionIP */
.fa-decisionip{
display: inline-block;
    width: 1em;
    height: 1em;
    vertical-align: -0.125em;
    background-color: currentColor;
    
    /* Cross-browser Masking */
    -webkit-mask-repeat: no-repeat;
    mask-repeat: no-repeat;
    -webkit-mask-size: contain;
    mask-size: contain;
    -webkit-mask-position: center;
    mask-position: center;

    /* Encoded SVG Data URI */
    -webkit-mask-image: url(\"data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 300 300'%3E%3Cpath fill-rule='evenodd' d='M150 0c-25 0-48 6-69 17l16 27c16-8 34-13 53-13V0zm0 0v31c19 0 37 5 53 13l16-27C198 6 175 0 150 0zM242 35l-26 21c15 17 24 40 26 64h31c-2-31-13-60-31-85zM273 180h-31c-2 24-11 47-26 64l26 21c18-25 29-54 31-85zM150 300c25 0 48-6 69-17l-16-27c-16 8-34 13-53 13v31zm0 0v-31c-19 0-37-5-53-13l-16 27c21 11 44 17 69 17zM58 265l26-21c-15-17-24-40-26-64H27c2 31 13 60 31 85zM27 120h31c2-24 11-47 26-64L58 35c-18 25-29 54-31 85zM150 90c-50 0-95 30-110 60 15 30 60 60 110 60s95-30 110-60c-15-30-60-60-110-60zm0 15a45 45 0 100 90 45 45 0 000-90zm0 25a20 20 0 110 40 20 20 0 010-40zM70 142h25v4H70v-4zm-2 2a3 3 0 11-6 0 3 3 0 016 0zm7 12h20v4H75v-4zm-2 2a3 3 0 11-6 0 3 3 0 016 0zm132-16h25v4h-25v-4zm27 2a3 3 0 11-6 0 3 3 0 016 0zm-27 12h20v4h-20v-4zm22 2a3 3 0 11-6 0 3 3 0 016 0z'/%3E%3C/svg%3E\");
    mask-image: url(\"data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 300 300'%3E%3Cpath fill-rule='evenodd' d='M150 0c-25 0-48 6-69 17l16 27c16-8 34-13 53-13V0zm0 0v31c19 0 37 5 53 13l16-27C198 6 175 0 150 0zM242 35l-26 21c15 17 24 40 26 64h31c-2-31-13-60-31-85zM273 180h-31c-2 24-11 47-26 64l26 21c18-25 29-54 31-85zM150 300c25 0 48-6 69-17l-16-27c-16 8-34 13-53 13v31zm0 0v-31c-19 0-37-5-53-13l-16 27c21 11 44 17 69 17zM58 265l26-21c-15-17-24-40-26-64H27c2 31 13 60 31 85zM27 120h31c2-24 11-47 26-64L58 35c-18 25-29 54-31 85zM150 90c-50 0-95 30-110 60 15 30 60 60 110 60s95-30 110-60c-15-30-60-60-110-60zm0 15a45 45 0 100 90 45 45 0 000-90zm0 25a20 20 0 110 40 20 20 0 010-40zM70 142h25v4H70v-4zm-2 2a3 3 0 11-6 0 3 3 0 016 0zm7 12h20v4H75v-4zm-2 2a3 3 0 11-6 0 3 3 0 016 0zm132-16h25v4h-25v-4zm27 2a3 3 0 11-6 0 3 3 0 016 0zm-27 12h20v4h-20v-4zm22 2a3 3 0 11-6 0 3 3 0 016 0z'/%3E%3C/svg%3E\");
}

.popover {
    z-index: 999999 !important;
}
.loading { @include loading(); }

.select2-container{box-sizing:border-box;display:inline-block;margin:0;position:relative;vertical-align:middle;}.select2-container .select2-selection--single{box-sizing:border-box;cursor:pointer;display:block;height:28px;user-select:none;-webkit-user-select:none;}.select2-container .select2-selection--single .select2-selection__rendered{display:block;padding-left:8px;padding-right:20px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;}.select2-container[dir=\"rtl\"] .select2-selection--single .select2-selection__rendered{padding-right:8px;padding-left:20px;}.select2-container .select2-selection--multiple{box-sizing:border-box;cursor:pointer;display:block;min-height:32px;user-select:none;-webkit-user-select:none;}.select2-container .select2-selection--multiple .select2-selection__rendered{display:inline-block;overflow:hidden;padding-left:8px;text-overflow:ellipsis;white-space:nowrap;}.select2-container .select2-search--inline{float:left;}.select2-container .select2-search--inline .select2-search__field{box-sizing:border-box;border:none;font-size:100%;margin-top:5px;}.select2-container .select2-search--inline .select2-search__field::-webkit-search-cancel-button{-webkit-appearance:none;}.select2-dropdown{background-color:white;border:1px solid #aaa;border-radius:4px;box-sizing:border-box;display:block;position:absolute;left:-100000px;width:100%;z-index:1051;}.select2-results{display:block;}.select2-results__options{list-style:none;margin:0;padding:0;}.select2-results__option{padding:6px;user-select:none;-webkit-user-select:none;}.select2-results__option[aria-selected]{cursor:pointer;}.select2-container--open .select2-dropdown{left:0;}.select2-container--open .select2-dropdown--above{border-bottom:none;border-bottom-left-radius:0;border-bottom-right-radius:0;}.select2-container--open .select2-dropdown--below{border-top:none;border-top-left-radius:0;border-top-right-radius:0;}.select2-search--dropdown{display:block;padding:4px;}.select2-search--dropdown .select2-search__field{padding:4px;width:100%;box-sizing:border-box;}.select2-search--dropdown .select2-search__field::-webkit-search-cancel-button{-webkit-appearance:none;}.select2-search--dropdown.select2-search--hide{display:none;}.select2-close-mask{border:0;margin:0;padding:0;display:block;position:fixed;left:0;top:0;min-height:100%;min-width:100%;height:auto;width:auto;opacity:0;z-index:99;background-color:#fff;filter:alpha(opacity=0);}.select2-hidden-accessible{border:0;clip:rect(0 0 0 0);height:1px;margin:-1px;overflow:hidden;padding:0;position:absolute;width:1px;}.select2-container--default .select2-selection--single{background-color:#fff;border:1px solid #aaa;border-radius:4px;}.select2-container--default .select2-selection--single .select2-selection__rendered{color:#444;line-height:28px;}.select2-container--default .select2-selection--single .select2-selection__clear{cursor:pointer;float:right;font-weight:bold;}.select2-container--default .select2-selection--single .select2-selection__placeholder{color:#999;}.select2-container--default .select2-selection--single .select2-selection__arrow{height:26px;position:absolute;top:1px;right:1px;width:20px;}.select2-container--default .select2-selection--single .select2-selection__arrow b{border-color:#888 transparent transparent transparent;border-style:solid;border-width:5px 4px 0 4px;height:0;left:50%;margin-left:-4px;margin-top:-2px;position:absolute;top:50%;width:0;}.select2-container--default[dir=\"rtl\"] .select2-selection--single .select2-selection__clear{float:left;}.select2-container--default[dir=\"rtl\"] .select2-selection--single .select2-selection__arrow{left:1px;right:auto;}.select2-container--default.select2-container--disabled .select2-selection--single{background-color:#eee;cursor:default;}.select2-container--default.select2-container--disabled .select2-selection--single .select2-selection__clear{display:none;}.select2-container--default.select2-container--open .select2-selection--single .select2-selection__arrow b{border-color:transparent transparent #888 transparent;border-width:0 4px 5px 4px;}.select2-container--default .select2-selection--multiple{background-color:white;border:1px solid #aaa;border-radius:4px;cursor:text;}.select2-container--default .select2-selection--multiple .select2-selection__rendered{box-sizing:border-box;list-style:none;margin:0;padding:0 5px;width:100%;}.select2-container--default .select2-selection--multiple .select2-selection__placeholder{color:#999;margin-top:5px;float:left;}.select2-container--default .select2-selection--multiple .select2-selection__clear{cursor:pointer;float:right;font-weight:bold;margin-top:5px;margin-right:10px;}.select2-container--default .select2-selection--multiple .select2-selection__choice{background-color:#e4e4e4;border:1px solid #aaa;border-radius:4px;cursor:default;float:left;margin-right:5px;margin-top:5px;padding:0 5px;}.select2-container--default .select2-selection--multiple .select2-selection__choice__remove{color:#999;cursor:pointer;display:inline-block;font-weight:bold;margin-right:2px;}.select2-container--default .select2-selection--multiple .select2-selection__choice__remove:hover{color:#333;}.select2-container--default[dir=\"rtl\"] .select2-selection--multiple .select2-selection__choice,.select2-container--default[dir=\"rtl\"] .select2-selection--multiple .select2-selection__placeholder{float:right;}.select2-container--default[dir=\"rtl\"] .select2-selection--multiple .select2-selection__choice{margin-left:5px;margin-right:auto;}.select2-container--default[dir=\"rtl\"] .select2-selection--multiple .select2-selection__choice__remove{margin-left:2px;margin-right:auto;}.select2-container--default.select2-container--focus .select2-selection--multiple{border:solid black 1px;outline:0;}.select2-container--default.select2-container--disabled .select2-selection--multiple{background-color:#eee;cursor:default;}.select2-container--default.select2-container--disabled .select2-selection__choice__remove{display:none;}.select2-container--default.select2-container--open.select2-container--above .select2-selection--single,.select2-container--default.select2-container--open.select2-container--above .select2-selection--multiple{border-top-left-radius:0;border-top-right-radius:0;}.select2-container--default.select2-container--open.select2-container--below .select2-selection--single,.select2-container--default.select2-container--open.select2-container--below .select2-selection--multiple{border-bottom-left-radius:0;border-bottom-right-radius:0;}.select2-container--default .select2-search--dropdown .select2-search__field{border:1px solid #aaa;}.select2-container--default .select2-search--inline .select2-search__field{background:transparent;border:none;outline:0;}.select2-container--default .select2-results>.select2-results__options{max-height:200px;overflow-y:auto;}.select2-container--default .select2-results__option[role=group]{padding:0;}.select2-container--default .select2-results__option[aria-disabled=true]{color:#999;}.select2-container--default .select2-results__option[aria-selected=true]{background-color:#ddd;}.select2-container--default .select2-results__option .select2-results__option{padding-left:1em;}.select2-container--default .select2-results__option .select2-results__option .select2-results__group{padding-left:0;}.select2-container--default .select2-results__option .select2-results__option .select2-results__option{margin-left:-1em;padding-left:2em;}.select2-container--default .select2-results__option .select2-results__option .select2-results__option .select2-results__option{margin-left:-2em;padding-left:3em;}.select2-container--default .select2-results__option .select2-results__option .select2-results__option .select2-results__option .select2-results__option{margin-left:-3em;padding-left:4em;}.select2-container--default .select2-results__option .select2-results__option .select2-results__option .select2-results__option .select2-results__option .select2-results__option{margin-left:-4em;padding-left:5em;}.select2-container--default .select2-results__option .select2-results__option .select2-results__option .select2-results__option .select2-results__option .select2-results__option .select2-results__option{margin-left:-5em;padding-left:6em;}.select2-container--default .select2-results__option--highlighted[aria-selected]{background-color:#5897fb;color:white;}.select2-container--default .select2-results__group{cursor:default;display:block;padding:6px;}.select2-container--classic .select2-selection--single{background-color:#f6f6f6;border:1px solid #aaa;border-radius:4px;outline:0;background-image:-webkit-linear-gradient(top, #ffffff 50%, #eeeeee 100%);background-image:-o-linear-gradient(top, #ffffff 50%, #eeeeee 100%);background-image:linear-gradient(to bottom, #ffffff 50%, #eeeeee 100%);background-repeat:repeat-x;filter:progid:DXImageTransform.Microsoft.gradient(startColorstr='#ffffff', endColorstr='#eeeeee', GradientType=0);}.select2-container--classic .select2-selection--single:focus{border:1px solid #5897fb;}.select2-container--classic .select2-selection--single .select2-selection__rendered{color:#444;line-height:28px;}.select2-container--classic .select2-selection--single .select2-selection__clear{cursor:pointer;float:right;font-weight:bold;margin-right:10px;}.select2-container--classic .select2-selection--single .select2-selection__placeholder{color:#999;}.select2-container--classic .select2-selection--single .select2-selection__arrow{background-color:#ddd;border:none;border-left:1px solid #aaa;border-top-right-radius:4px;border-bottom-right-radius:4px;height:26px;position:absolute;top:1px;right:1px;width:20px;background-image:-webkit-linear-gradient(top, #eeeeee 50%, #cccccc 100%);background-image:-o-linear-gradient(top, #eeeeee 50%, #cccccc 100%);background-image:linear-gradient(to bottom, #eeeeee 50%, #cccccc 100%);background-repeat:repeat-x;filter:progid:DXImageTransform.Microsoft.gradient(startColorstr='#eeeeee', endColorstr='#cccccc', GradientType=0);}.select2-container--classic .select2-selection--single .select2-selection__arrow b{border-color:#888 transparent transparent transparent;border-style:solid;border-width:5px 4px 0 4px;height:0;left:50%;margin-left:-4px;margin-top:-2px;position:absolute;top:50%;width:0;}.select2-container--classic[dir=\"rtl\"] .select2-selection--single .select2-selection__clear{float:left;}.select2-container--classic[dir=\"rtl\"] .select2-selection--single .select2-selection__arrow{border:none;border-right:1px solid #aaa;border-radius:0;border-top-left-radius:4px;border-bottom-left-radius:4px;left:1px;right:auto;}.select2-container--classic.select2-container--open .select2-selection--single{border:1px solid #5897fb;}.select2-container--classic.select2-container--open .select2-selection--single .select2-selection__arrow{background:transparent;border:none;}.select2-container--classic.select2-container--open .select2-selection--single .select2-selection__arrow b{border-color:transparent transparent #888 transparent;border-width:0 4px 5px 4px;}.select2-container--classic.select2-container--open.select2-container--above .select2-selection--single{border-top:none;border-top-left-radius:0;border-top-right-radius:0;background-image:-webkit-linear-gradient(top, #ffffff 0%, #eeeeee 50%);background-image:-o-linear-gradient(top, #ffffff 0%, #eeeeee 50%);background-image:linear-gradient(to bottom, #ffffff 0%, #eeeeee 50%);background-repeat:repeat-x;filter:progid:DXImageTransform.Microsoft.gradient(startColorstr='#ffffff', endColorstr='#eeeeee', GradientType=0);}.select2-container--classic.select2-container--open.select2-container--below .select2-selection--single{border-bottom:none;border-bottom-left-radius:0;border-bottom-right-radius:0;background-image:-webkit-linear-gradient(top, #eeeeee 50%, #ffffff 100%);background-image:-o-linear-gradient(top, #eeeeee 50%, #ffffff 100%);background-image:linear-gradient(to bottom, #eeeeee 50%, #ffffff 100%);background-repeat:repeat-x;filter:progid:DXImageTransform.Microsoft.gradient(startColorstr='#eeeeee', endColorstr='#ffffff', GradientType=0);}.select2-container--classic .select2-selection--multiple{background-color:white;border:1px solid #aaa;border-radius:4px;cursor:text;outline:0;}.select2-container--classic .select2-selection--multiple:focus{border:1px solid #5897fb;}.select2-container--classic .select2-selection--multiple .select2-selection__rendered{list-style:none;margin:0;padding:0 5px;}.select2-container--classic .select2-selection--multiple .select2-selection__clear{display:none;}.select2-container--classic .select2-selection--multiple .select2-selection__choice{background-color:#e4e4e4;border:1px solid #aaa;border-radius:4px;cursor:default;float:left;margin-right:5px;margin-top:5px;padding:0 5px;}.select2-container--classic .select2-selection--multiple .select2-selection__choice__remove{color:#888;cursor:pointer;display:inline-block;font-weight:bold;margin-right:2px;}.select2-container--classic .select2-selection--multiple .select2-selection__choice__remove:hover{color:#555;}.select2-container--classic[dir=\"rtl\"] .select2-selection--multiple .select2-selection__choice{float:right;}.select2-container--classic[dir=\"rtl\"] .select2-selection--multiple .select2-selection__choice{margin-left:5px;margin-right:auto;}.select2-container--classic[dir=\"rtl\"] .select2-selection--multiple .select2-selection__choice__remove{margin-left:2px;margin-right:auto;}.select2-container--classic.select2-container--open .select2-selection--multiple{border:1px solid #5897fb;}.select2-container--classic.select2-container--open.select2-container--above .select2-selection--multiple{border-top:none;border-top-left-radius:0;border-top-right-radius:0;}.select2-container--classic.select2-container--open.select2-container--below .select2-selection--multiple{border-bottom:none;border-bottom-left-radius:0;border-bottom-right-radius:0;}.select2-container--classic .select2-search--dropdown .select2-search__field{border:1px solid #aaa;outline:0;}.select2-container--classic .select2-search--inline .select2-search__field{outline:0;}.select2-container--classic .select2-dropdown{background-color:white;border:1px solid transparent;}.select2-container--classic .select2-dropdown--above{border-bottom:none;}.select2-container--classic .select2-dropdown--below{border-top:none;}.select2-container--classic .select2-results>.select2-results__options{max-height:200px;overflow-y:auto;}.select2-container--classic .select2-results__option[role=group]{padding:0;}.select2-container--classic .select2-results__option[aria-disabled=true]{color:grey;}.select2-container--classic .select2-results__option--highlighted[aria-selected]{background-color:#3875d7;color:white;}.select2-container--classic .select2-results__group{cursor:default;display:block;padding:6px;}.select2-container--classic.select2-container--open .select2-dropdown{border-color:#5897fb;}
";
@file_put_contents("css/angular.css.php.css",$html);
echo $html;