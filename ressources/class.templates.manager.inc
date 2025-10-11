<?php
include_once(dirname(__FILE__)."/class.wifidog.templates.inc");
include_once(dirname(__FILE__)."/class.template-admin.inc");
class templates_manager{
	private $templateid;
	public $mysql_error_html;
	public $CssContent=null;
	public $headContent=null;
	public $BodyContent=null;
	public $TemplateName=null;
	public $ok=true;
	public $mysql_error=null;
    public $HotSpotWIFI4EU_ENABLE;
    public $HotSpotWIFI4EU_DEBUG_VAL;
    public $HotSpotWIFI4EU_UUID;
    public $HotSpotWIFI4EU_LANG;
    public $HotSpotWIFI4EU_DEBUG;
    public $WIFI4EUImage;
    public $WIFI4EUTEXTH1;
    public $WIFI4EUTEXTH2;
    public $WIFI4EUTEXTBTN;
    public $WIFI4EUTEXTH1COLOR;
    public $WIFI4EUTEXTH2COLOR;
    public $WIFI4EUTEXTBTNCOLOR;
    public $WIFI4EUBTNCOLOR;
    public $WIFI4UEENABLETERMS;
    public $WIFI4UETERMSTEXT;
    public $WIFI4UEENABLEPRIVACY;
    public $WIFI4UEPRIVACYTEXT;
    public $WIFI4EULINKCOLOR;
    public $WIFI4EUTEXTH1SIZE;
    public $WIFI4EUTEXTH2SIZE;
    public $WIFI4EUTEXTBODYSIZE;
    public $WIFI4EUTEXTBUTTONSIZE;
    public $WIFI4UETERMSCONTENT;
    public $WIFI4UEPRIVACYCONTENT;
    public $WIFI4EUTEXTBODYCOLOR;
    public $WIFI4UEERRORTEXT;
	public $formHiddens;
    public $label_username;
    public $label_password;
	public $HotSpotAutoLogin;
	public $MainExplain;
	public $MainTitle;
	public $BuildUrlForceRegister;
    public $LoginTitle;
    public $SubmitButton;
    public $SubWelcome;
    public $opts;
    public $DivLeftColor;
    public $DivRigthColor;
    public $DivRigthBGColor;
    public $DivRigthButtonColor;
    public $DivRigthButtonTextColor;
    public $DivRigthButtonHoverColor;
    public $DivBackgroundImage;
    public $DivLeftBGColor;
    public $ErrorMessage;
    public $RedirectPage;


	function __construct($templateid=1,$opts=array()){
		if(!is_numeric($templateid)){$templateid=1;}
        $this->opts=$opts;
		if(count($opts)>0){
		    foreach ($opts as $attr=>$value){
		        if($GLOBALS["VERBOSE"]){
		            VERBOSE("template.$templateid | SET $attr == $value",__LINE__);
                }
		        $this->$attr=$value;
            }
        }
		
		if($templateid>0){
			$this->templateid=$templateid;
			$this->load();
		}
        $wifidog_templates=new wifidog_templates();
        $this->HotSpotWIFI4EU_ENABLE=$wifidog_templates->HotSpotWIFI4EU_ENABLE;
        $this->HotSpotWIFI4EU_UUID=$wifidog_templates->HotSpotWIFI4EU_UUID;
        $this->HotSpotWIFI4EU_LANG=$wifidog_templates->HotSpotWIFI4EU_LANG;
        $this->HotSpotWIFI4EU_DEBUG=$wifidog_templates->HotSpotWIFI4EU_DEBUG;
        $this->WIFI4EUImage=$wifidog_templates->WIFI4EUImage;
        $this->WIFI4EUTEXTH1=$wifidog_templates->WIFI4EUTEXTH1;
        $this->WIFI4EUTEXTH2=$wifidog_templates->WIFI4EUTEXTH2;
        $this->WIFI4EUTEXTBTN=$wifidog_templates->WIFI4EUTEXTBTN;
        $this->WIFI4EUTEXTH1COLOR=$wifidog_templates->WIFI4EUTEXTH1COLOR;
        $this->WIFI4EUTEXTH2COLOR=$wifidog_templates->WIFI4EUTEXTH2COLOR;
        $this->WIFI4EUTEXTBTNCOLOR=$wifidog_templates->WIFI4EUTEXTBTNCOLOR;
        $this->WIFI4EUBTNCOLOR=$wifidog_templates->WIFI4EUBTNCOLOR;
        $this->WIFI4UEENABLETERMS=$wifidog_templates->WIFI4UEENABLETERMS;
        $this->WIFI4UETERMSTEXT=$wifidog_templates->WIFI4UETERMSTEXT;
        $this->WIFI4UEENABLEPRIVACY=$wifidog_templates->WIFI4UEENABLEPRIVACY;
        $this->WIFI4UEPRIVACYTEXT=$wifidog_templates->WIFI4UEPRIVACYTEXT;
        $this->WIFI4EULINKCOLOR=$wifidog_templates->WIFI4EULINKCOLOR;
        $this->WIFI4EUTEXTH1SIZE=$wifidog_templates->WIFI4EUTEXTH1SIZE;
        $this->WIFI4EUTEXTH2SIZE=$wifidog_templates->WIFI4EUTEXTH2SIZE;
        $this->WIFI4EUTEXTBODYSIZE=$wifidog_templates->WIFI4EUTEXTBODYSIZE;
        $this->WIFI4EUTEXTBUTTONSIZE=$wifidog_templates->WIFI4EUTEXTBUTTONSIZE;
        $this->WIFI4UETERMSCONTENT=$wifidog_templates->WIFI4UETERMSCONTENT;
        $this->WIFI4UEPRIVACYCONTENT=$wifidog_templates->WIFI4UEPRIVACYCONTENT;
        $this->WIFI4EUTEXTBODYCOLOR=$wifidog_templates->WIFI4EUTEXTBODYCOLOR;
        $this->WIFI4UEERRORTEXT=$wifidog_templates->WIFI4UEERRORTEXT;
	}

	public function wifi4eu_css():string{
	    $css[]="@media (max-width: 600px) {#wifi4eubanner{width:100%;}}";
        $css[]="@media (min-width: 600px) {#wifi4eubanner{width:100%;}}";
        $css[]=".identity-container{position: absolute;top: 0px;left: 0px;height: 100%;width: 100%;text-align: center;}";
        $css[]=".button{background-color:$this->WIFI4EUBTNCOLOR;color:$this->WIFI4EUTEXTBTNCOLOR;border:none;padding:15px 32px;text-align:center;text-decoration:none;display:inline-block;font-size:$this->WIFI4EUTEXTBUTTONSIZE;cursor:pointer;}";
        $css[]="body{font-family:Helvetica,Arial,sans-serif;font-weight:100;font-size:$this->WIFI4EUTEXTBODYSIZE;line-height:30px;color:$this->WIFI4EUTEXTBODYCOLOR}";
        $css[]=".footer{bottom:0;width:30%;text-align:left;position:fixed;}";
        $css[]=".h1{color:$this->WIFI4EUTEXTH1COLOR;font-size:$this->WIFI4EUTEXTH1SIZE;text-align:center;}";
        $css[]=".h2{color:$this->WIFI4EUTEXTH2COLOR;font-size:$this->WIFI4EUTEXTH2SIZE;text-align:center;}";
        $css[]="a{color:$this->WIFI4EULINKCOLOR}";
        $css[]=".container{display:block;position:relative;margin-top:8px;cursor:pointer;font-size:$this->WIFI4EUTEXTBODYSIZE;-webkit-user-select:none;-moz-user-select:none;-ms-user-select:none;user-select:none;}";
        $css[]=".container input {  position: absolute;  opacity: 0;  cursor: pointer;  height: 0;  width: 0;}";
        $css[]=".checkmark {  position: absolute;  top: 0;  height: 25px;  width: 25px;  background-color: #eee;}";
        $css[]=".container:hover input ~ .checkmark {  background-color: #ccc;}";
        $css[]=".container input:checked ~ .checkmark {  background-color: #2196F3;}";
        $css[]=".checkmark:after {  content: \"\";  position: absolute;  display: none;}";
        $css[]=".container input:checked ~ .checkmark:after {  display: block;}";
        $css[]=".container .checkmark:after {  left: 9px;  top: 5px;  width: 5px;  height: 10px;  border: solid white;  border-width: 0 3px 3px 0;  -webkit-transform: rotate(45deg);  -ms-transform: rotate(45deg);  transform: rotate(45deg);}";
        $css[]=".modal {  display: none; /* Hidden by default */  position: fixed; /* Stay in place */  z-index: 1; /* Sit on top */  padding-top: 100px; /* Location of the box */  left: 0;  top: 0;  width: 100%; /* Full width */  height: 100%; /* Full height */  overflow: auto; /* Enable scroll if needed */  background-color: rgb(0,0,0); /* Fallback color */  background-color: rgba(0,0,0,0.4); /* Black w/ opacity */}";
        $css[]=".modal-content {  background-color: #fefefe;  margin: auto;  padding: 20px;  border: 1px solid #888;  width: 80%;}";
        $css[]=".closet {  color: #aaaaaa;  float: right;  font-size: 28px;  font-weight: bold;}";
        $css[]=".closep {  color: #aaaaaa;  float: right;  font-size: 28px;  font-weight: bold;}";
        $css[]=".close:hover,.close:focus {  color: #000;  text-decoration: none;  cursor: pointer;}";
        return @implode("\n",$css);
    }
	public function enterprise_login_css():string{
	        $polices=@file_get_contents("/usr/share/artica-postfix/css/myriad-pro.css");
	        $css[]=$polices;
            $css[]="html{font-family:sans-serif;-ms-text-size-adjust:100%;-webkit-text-size-adjust:100%}";
            $css[]="body{margin:0}";
            $css[]="article,aside,details,figure,footer,header,hgroup,main,menu,nav,section,summary{display:block}";
            $css[]="audio,canvas,progress,video{display:inline-block;vertical-align:baseline}";
            $css[]="audio:not([controls]){display:none;height:0}";
            $css[]="[hidden],template{display:none}";
            $css[]="a{background-color:transparent}";
            $css[]="a:active,a:hover{outline:0}";
            $css[]="abbr[title]{border-bottom:1px dotted}";
            $css[]="b,strong{font-weight:bold}";
            $css[]="dfn{font-style:italic}";
            $css[]="h1{margin:.67em 0;font-size:2em}";
            $css[]="mark{color:#000;background:#ff0}";
            $css[]="small{font-size:80%}";
            $css[]="sub,sup{position:relative;font-size:75%;line-height:0;vertical-align:baseline}";
            $css[]="sup{top:-0.5em}";
            $css[]="sub{bottom:-0.25em}";
            $css[]="img{border:0}";
            $css[]="svg:not(:root){overflow:hidden}";
            $css[]="figure{margin:1em 40px}";
            $css[]="hr{height:0;box-sizing:content-box}";
            $css[]="pre{overflow:auto}";
            $css[]="code,kbd,pre,samp{font-family:monospace,monospace;font-size:1em}";
            $css[]="button,input,optgroup,select,textarea{margin:0;font:inherit;color:inherit}";
            $css[]="button{overflow:visible}";
            $css[]="button,select{text-transform:none}";
            $css[]="button,html input[type.articabut],input[type=reset],input[type=submit]{cursor:pointer;-webkit-appearance.articabut}";
            $css[]="button[disabled],html input[disabled]{cursor:default}";
            $css[]="button::-moz-focus-inner,input::-moz-focus-inner{padding:0;border:0}";
            $css[]="input{line-height:normal}";
            $css[]="input[type=checkbox],input[type=radio]{padding:0;box-sizing:border-box}";
            $css[]="input[type=number]::-webkit-inner-spin.articabut,input[type=number]::-webkit-outer-spin.articabut{height:auto}";
            $css[]="input[type=search]{box-sizing:content-box;-webkit-appearance:textfield}";
            $css[]="input[type=search]::-webkit-search-cancel.articabut,input[type=search]::-webkit-search-decoration{-webkit-appearance:none}";
            $css[]="fieldset{padding:.35em .625em .75em;margin:0 2px;border:1px solid silver}";
            $css[]="legend{padding:0;border:0}";
            $css[]="textarea{overflow:auto}";
            $css[]="optgroup{font-weight:bold}";
            $css[]="td,th{padding:0}";
            $css[]="*,*:before,*:after{box-sizing:border-box}";
            $css[]="html{position:relative;min-height:100%;font-size:14px}";
            $css[]="body{margin:0 auto;font-family:\"myriad-pro\",Myriad Pro,Arial,sans-serif;-webkit-font-smoothing:antialiased;font-weight:400;line-height:1.6;color:#5f6468}";
            $css[]="body.is-sticky{margin-bottom:263px}";
            $css[]="main{position:fixed;top:80px;right:0;bottom:0;left:280px;padding:50px;overflow:auto;transition:all .3s linear}";
            $css[]="@media all and (max-width: 1223px){main{top:60px;left:0;padding:25px 25px 0}}";
            $css[]="@media all and (max-width: 1024px){main{position:relative;top:0;width:100%}}";
            $css[]="@media all and (max-width: 767px){body.is-sticky{margin-bottom:636px}";
            $css[]="main{position:relative;top:0;width:100%}}";
            $css[]="@media all and (max-width: 420px){body.is-sticky{margin-bottom:655px}}";
            $css[]="p{margin:0;font-weight:400}";
            $css[]="p.is-primary{color:#006e96}";
            $css[]="b,strong{font-weight:600}";
            $css[]="h1,h2,h3,h4,h5,h6{margin:0;line-height:1.25;text-rendering:optimizeLegibility}";
            $css[]="h1{font-size:50px;font-weight:300}";
            $css[]="h2{font-size:30px;font-weight:400}";
            $css[]="h3{font-size:28px;font-weight:300;max-width: 74%;}";
            $css[]="h4{font-size:24px;font-weight:500;letter-spacing:-0.01em}";
            $css[]="h5{font-size:20px;font-weight:500;letter-spacing:-0.01em}";
            $css[]="h6{font-size:14px;font-weight:600;letter-spacing:.1em;text-transform:uppercase}";
            $css[]="@media all and (max-width: 1024px){h1{font-size:35px}h2{font-size:24px}h3{font-size:22px}}";
            $css[]="@media all and (max-width: 767px){h1{font-size:30px}}";
            $css[]="a{color:#006e96;text-decoration:none;transition:color .3s linear}";
            $css[]="a:hover,a:active{color:#00364a}";
            $css[]="a:focus{color:#00364a;outline:none}";
            $css[]="abbr{cursor:help;border-bottom:1px dotted #e2e3e4}";
            $css[]="blockquote{padding-left:15px;margin:10px 0;font-style:italic;color:#5f6468;border-left:2px solid #e2e3e4}";
            $css[]="hr{margin:0;border-top:0;border-right:0;border-bottom:1px solid #e2e3e4;border-left:0}";
            $css[]="img{display:block;max-width:100%;margin:0}";
            $css[]="::selection{color:#fff;background-color:#161718}";
            $css[]="footer{position:absolute;right:0;bottom:0;left:0;display:block;width:100%;padding:75px 0;background:#2e3133}";
            $css[]="";
            $css[]="@media all and (max-width: 767px){";
            $css[]="footer .artica-spacer-cell{text-align:center}}";
            $css[]=" .artica-main-inside .articabut,";
            $css[]=" .artica-main-inside .articabut-blue {margin-top:35px}";
            $css[]="a.articabut a.articabut-blue {width:auto}";

            $css[]=".artica-spacer-cell{display:inline-block;width:100%;padding:0 10px;margin:0;font-size:14px;text-align:left;vertical-align:top}";
            $css[]=".artica-spacer-cell.with-1col{width:8.3333333333%}";
            $css[]=".artica-spacer-cell.with-2col{width:16.6666666667%}";
            $css[]=".artica-spacer-cell.with-3col{width:25%}";
            $css[]=".artica-spacer-cell.with-4col{width:33.3333333333%}";
            $css[]=".artica-spacer-cell.with-5col{width:41.6666666667%}";
            $css[]=".artica-spacer-cell.with-6col{width:50%}";
            $css[]=".artica-spacer-cell.with-7col{width:58.3333333333%}";
            $css[]=".artica-spacer-cell.with-8col{width:66.6666666667%}";
            $css[]=".artica-spacer-cell.with-9col{width:75%}";
            $css[]=".artica-spacer-cell.with-10col{width:83.3333333333%}";
            $css[]=".artica-spacer-cell.with-11col{width:91.6666666667%}";
            $css[]=".artica-spacer-cell.with-1off{margin-left:8.3333333333%}";
            $css[]=".artica-spacer-cell.with-2off{margin-left:16.6666666667%}";
            $css[]=".artica-spacer-cell.with-3off{margin-left:25%}";
            $css[]=".artica-spacer-cell.with-4off{margin-left:33.3333333333%}";
            $css[]=".artica-spacer-cell.with-5off{margin-left:41.6666666667%}";
            $css[]=".artica-spacer-cell.with-6off{margin-left:50%}";
            $css[]=".artica-spacer-cell.with-7off{margin-left:58.3333333333%}";
            $css[]=".artica-spacer-cell.with-8off{margin-left:66.6666666667%}";
            $css[]=".artica-spacer-cell.with-9off{margin-left:75%}";
            $css[]=".artica-spacer-cell.with-10off{margin-left:83.3333333333%}";
            $css[]=".artica-spacer-cell.with-11off{margin-left:91.6666666667%}";
            $css[]=".artica-spacer-cell.is-centered{text-align:center}";
            $css[]=".artica-spacer-cell.is-right{text-align:right}";
            $css[]=".artica-spacer-cell.is-top{vertical-align:top}";
            $css[]=".artica-spacer-cell.is-middle{vertical-align:middle}";
            $css[]=".artica-spacer-cell.is-bottom{vertical-align:bottom}";
            $css[]=".artica-spacer-centered{max-width:1400px}";
            $css[]=".artica-spacer-cell{width:50% !important}";
            $css[]="}";
            $css[]="@media all and (max-width: 420px){.artica-spacer-cell{width:100% !important}}";
            $css[]="";
            $css[]=".articabut,.btn-secondary,.articabut-blue{position:relative;display:inline-block;padding:13px 30px;font-family:\"myriad-pro\",Myriad Pro,Arial,sans-serif;font-size:12px;font-weight:600;line-height:1;letter-spacing:.1em;text-align:center;text-transform:uppercase;cursor:pointer;border:0;border-radius:2px;transition:all .3s ease-in-out;appearance:none;user-select:none}";
            $css[]=".articabut:disabled,.btn-secondary:disabled,.articabut-blue:disabled,.articabut.is-disabled,.is-disabled.btn-secondary,.is-disabled.articabut-blue{color:#92979b;cursor:not-allowed;background-color:#f7f7f8}";
            $css[]=".articabut:disabled:hover,.btn-secondary:disabled:hover,.articabut-blue:disabled:hover,.articabut:disabled:focus,.btn-secondary:disabled:focus,.articabut-blue:disabled:focus,.articabut:disabled:active,.btn-secondary:disabled:active,.articabut-blue:disabled:active,.articabut.is-disabled:hover,.is-disabled.btn-secondary:hover,.is-disabled.articabut-blue:hover,.articabut.is-disabled:focus,.is-disabled.btn-secondary:focus,.is-disabled.articabut-blue:focus,.articabut.is-disabled:active,.is-disabled.btn-secondary:active,.is-disabled.articabut-blue:active{color:#92979b;background-color:#f7f7f8;outline:none}";
            $css[]=".articabut-muted{color:#5f6468;background-color:#e2e3e4}";
            $css[]=".articabut-muted:hover{color:#5f6468;background-color:#eff0f0}";
            $css[]=".articabut-muted:focus,.articabut-muted:active{color:#5f6468;background-color:#c7cacc;outline:none}";
            $css[]=".articabut-danger{color:#fff;background-color:#f00000}";
            $css[]=".articabut-danger:hover{color:#fff;background-color:#ff0b0b}";
            $css[]=".articabut-danger:focus,.articabut-danger:active{color:#fff;background-color:#bd0000;outline:none}";
            $css[]=".articabut-primary{color:#fff;background-color:#006e96}";
            $css[]=".articabut-primary:hover{color:#fff;background-color:#0081b0}";
            $css[]=".articabut-primary:focus,.articabut-primary:active{color:#fff;background-color:#004963;outline:none}";
            $css[]=".articabut-success,.articabut-blue{color:#fff;background-color:#48c878}";
            $css[]=".articabut-success:hover,.articabut-blue:hover{color:$this->DivRigthButtonTextColor;background-color:$this->DivRigthButtonHoverColor}";
            $css[]=".articabut-success:focus,.articabut-blue:focus,.articabut-success:active,.articabut-blue:active{color:#fff;background-color:#33aa60;outline:none}";
            $css[]=".articabut-info{color:#fff;background-color:#78d2dc}";
            $css[]=".articabut-info:hover{color:#fff;background-color:#8cd9e1}";
            $css[]=".articabut-info:focus,.articabut-info:active{color:#fff;background-color:#50c5d2;outline:none}";
            $css[]=".articabut-white{color:#006e96;background-color:#fff}";
            $css[]=".articabut-white:hover{color:#006e96;background-color:#fff}";
            $css[]=".articabut-white:focus,.articabut-white:active{color:#006e96;background-color:#e6e6e6;outline:none}";
            $css[]="";
            $css[]=".articabut,.articabut-blue{color:#fff}";
            $css[]="form{position:relative;display:block;margin-bottom:50px;clear:both}";
            $css[]="fieldset{position:relative;display:block;min-width:100%;padding:50px 0 0;margin:0;margin-bottom:25px;background:#fff;border:1px solid #e2e3e4;border-radius:2px}";
            $css[]="fieldset .flash{margin-bottom:0;border-radius:0}";
            $css[]="fieldset:last-of-type{margin-bottom:0}";
            $css[]="legend{position:absolute;top:1px;right:1px;left:1px;display:block;width:100%;padding:15px 35px;margin:0;font-size:12px;font-weight:600;letter-spacing:.2em;color:#5f6468;text-transform:uppercase;border-bottom:1px solid #e2e3e4;border-radius:2px 2px 0 0}";
            $css[]="legend a{display:block;color:#5f6468}";
            $css[]="legend a span{display:block;float:right}";
            $css[]="label,input,select,textarea{display:inline-block;font-family:\"myriad-pro\",Myriad Pro,Arial,sans-serif;line-height:1.6}";
            $css[]="label{display:inline-block;width:40%;margin-top:10px;font-size:12px;font-weight:600;letter-spacing:.1em;color:#92979b;text-transform:uppercase;vertical-align:top}";
            $css[]="label.without-margin{margin-top:0}";
            $css[]="input:not([type=submit]):not([type=file]):not([type=checkbox]):not([type=radio]),textarea{display:inline-block;padding:7.5px 15px;font-family:\"myriad-pro\",Myriad Pro,Arial,sans-serif;font-size:14px;color:#474a4d;vertical-align:top;background-color:$this->DivRigthBGColor;border:1px solid #e2e3e4;border-radius:2px;transition:border-color .3s linear;appearance:none}";
            $css[]="input:not([type=submit]):not([type=file]):not([type=checkbox]):not([type=radio]):hover,textarea:hover{border-color:#c7cacc}";
            $css[]="input:not([type=submit]):not([type=file]):not([type=checkbox]):not([type=radio]):focus,input:not([type=submit]):not([type=file]):not([type=checkbox]):not([type=radio]):active,textarea:focus,textarea:active{border-color:#006e96;outline:none}";
            $css[]="input:not([type=submit]):not([type=file]):not([type=checkbox]):not([type=radio]):disabled,textarea:disabled{cursor:not-allowed;background-color:#e2e3e4}";
            $css[]="input:not([type=submit]):not([type=file]):not([type=checkbox]):not([type=radio]):disabled:hover,textarea:disabled:hover{border-color:#e2e3e4}";
            $css[]="input:not([type=submit]):not([type=file]):not([type=checkbox]):not([type=radio]):disabled:active,textarea:disabled:active{border-color:#e2e3e4;outline:none}";
            $css[]="input:not([type=submit]):not([type=file]):not([type=checkbox]):not([type=radio]).is-second,textarea.is-second{margin-top:5px;margin-left:40%}";
            $css[]="input:not([type=submit]):not([type=file]):not([type=checkbox]):not([type=radio]).is-small,textarea.is-small{width:70px}";
            $css[]=".artica-form-item{position:relative;display:block;margin-bottom:20px;font-size:0}";
            $css[]=".artica-form-item.with-margin{margin-top:30px}";
            $css[]=".artica-form-item:last-child{margin-bottom:0}";
            $css[]=".artica-form-item .articabut,.artica-form-item .articabut-blue,.artica-form-item .control{display:inline-block;width:60%;vertical-align:top}";
            $css[]=".artica-form-item p{font-size:14px}";
            $css[]=".artica-form-item.is-top label{margin-top:2px}";
            $css[]=".artica-form-item.with-error{position:relative}";
            $css[]=".artica-form-item.with-error input{border-color:#f00000 !important}";
            $css[]=".artica-form-item.with-error label{color:#f00000}";
            $css[]=".artica.articabuts{display:block;margin-top:30px}";
            $css[]=".artica-form-exit{position:relative;display:inline-block;width:60%;min-height:39px;font-size:14px;vertical-align:top}";
            $css[]=".artica-form-exit strong{display:inline-block;width:120px;vertical-align:middle}";
            $css[]=".artica-form-exit img{display:inline-block;width:39px;margin-right:10px;vertical-align:middle;border-radius:2px}";
            $css[]=".artica-form-exit.is-selects{font-size:0}";
            $css[]=".artica-form-exit.is-selects .control{width:120px}";
            $css[]=".artica-form-exit.with-image img{width:100%;margin-right:0}";
            $css[]=".artica-form-exit.with-payments img{cursor:pointer;opacity:.5}";
            $css[]=".artica-form-exit.with-payments img.is-selected{opacity:1}";
            $css[]=".artica-form-exit.with-check{position:relative}";
            $css[]=".artica-form-exit.with-check .checkbox{position:absolute;right:0;width:14px}";
            $css[]=".artica-form-exit.with-checkinputs .control{display:inline-block;width:39%;margin-top:20px;vertical-align:middle}";
            $css[]=".artica-form-exit.with-checkinputs>input{display:inline-block;width:60%;margin-top:10px;vertical-align:middle}";
            $css[]=".artica-form2 label{width:100% !important}";
            $css[]=".artica-form2 input:not([type=submit]):not([type=file]):not([type=checkbox]):not([type=radio]){width:100%;padding:0 0 10px;color:$this->DivRigthColor;border:0;border-bottom:1px solid #e2e3e4;border-radius:0}";
            $css[]=".artica-form2 input:not([type=submit]):not([type=file]):not([type=checkbox]):not([type=radio])::-webkit-input-placeholder{color:$this->DivRigthColor}";
            $css[]=".artica-form2 input:not([type=submit]):not([type=file]):not([type=checkbox]):not([type=radio])::-moz-placeholder{color:$this->DivRigthColor}";
            $css[]=".artica-form2 input:not([type=submit]):not([type=file]):not([type=checkbox]):not([type=radio]):-ms-input-placeholder{color:$this->DivRigthColor}";
            $css[]=".artica-form2 input:not([type=submit]):not([type=file]):not([type=checkbox]):not([type=radio])::placeholder{color:$this->DivRigthColor}";
            $css[]=".artica-form2 input:not([type=submit]):not([type=file]):not([type=checkbox]):not([type=radio]):hover{border-color:#e2e3e4}";
            $css[]=".artica-form2 input:not([type=submit]):not([type=file]):not([type=checkbox]):not([type=radio]):focus,.artica-form2 input:not([type=submit]):not([type=file]):not([type=checkbox]):not([type=radio]):active{border-color:#e2e3e4}";
            $css[]=".artica-form2 .dropdown{width:100%}";
            $css[]=".artica-form2 .dropdown>a{padding:0 0 10px;color:#92979b;border:0;border-bottom:1px solid #e2e3e4;border-radius:0}";
            $css[]=".artica-form2 .dropdown>a i{margin-top:4px}";
            $css[]=".artica-form2 .dropdown-menu{margin-top:0;border:0}";
            $css[]="@media all and (max-width: 1223px){form{margin-bottom:25px}}";
            $css[]="@media all and (max-width: 767px){label{display:block;width:100%;margin-bottom:5px}input:not([type=submit]):not([type=file]):not([type=checkbox]):not([type=radio]),textarea{display:block;width:100%}input:not([type=submit]):not([type=file]):not([type=checkbox]):not([type=radio]).is-second,textarea.is-second{margin-left:0}.artica-form-item .articabut,.artica-form-item .btn-secondary,.artica-form-item .articabut-blue,.artica-form-item .control{display:block;width:100%}.artica-form-exit{width:100%}}";
            $css[]="header{position:relative;display:block;margin-top:80px;margin-bottom:50px}";
            $css[]=".artica-main-container{width:100%;height:60%;text-align:center}";
            $css[]=".artica-div-left{position:fixed;top:0;right:40%;bottom:0;left:0;padding:0 75px;overflow:auto;background:#fff}";
            $css[]=".artica-logo-wrapper{display:block;width:100%;height:15%;max-width:900px;padding-top:75px;margin:0 auto;text-align:left}";
            $css[]=".artica-logo{display:inline-block}";
            $css[]=".artica-logo img{display:block;height:40px}";
            $css[]=".artica-main-inside{display:block;width:100%;max-width:900px;margin:75px auto;text-align:left}";
            $css[]=".artica-main-inside h1{color:$this->DivLeftColor}";
            $css[]=".artica-main-inside h3{color:$this->DivLeftColor}";
            $css[]=".artica-main-inside p{margin:30px 0 0;color:$this->DivLeftColor}";
            $css[]=".artica-main-inside .articabut,.artica-main-inside .btn-secondary,.artica-main-inside .articabut-blue{margin-top:35px}";
            $css[]=".artica-div-right{position:fixed;top:0;right:0;bottom:0;left:60%;padding:0 75px;overflow:auto;background:$this->DivRigthBGColor}";
            $css[]=".artica-div-right .artica-main-container{height:100%}";
            $css[]=".artica-form{display:block;width:100%;max-width:400px;margin:75px 0;text-align:left}";
            $css[]=".artica-form h3{margin-bottom:25px;color:$this->DivRigthColor}";
            $css[]=".artica-form h6{margin:40px 0 15px;color:#a5a8ac}";
            $css[]=".artica-form h6:first-of-type{margin-top:0}";
            $css[]=".artica-form p{margin-bottom:25px;color:#a5a8ac}";
            $css[]="@media all and (max-height: 750px){.artica-div-left .artica-main-container{height:80%;padding:0}.artica-div-right .artica-main-container{padding:0}.artica-div-right .artica-main-container.is-big{display:block;align-items:none;height:auto}.artica-logo-wrapper{height:10%;padding-top:40px}.artica-main-inside{margin:40px auto}}";
            $css[]="@media all and (max-height: 625px){.artica-div-left .artica-main-container{display:block;align-items:none;height:auto;padding:0}.artica-logo-wrapper{height:auto}.artica-main-inside{margin:75px auto}}";
            $css[]="@media all and (max-height: 450px){.artica-div-right .artica-main-container{display:block;align-items:none;height:auto}}";
            $css[]="@media all and (max-width: 1223px){.artica-div-left,.artica-div-right{padding-right:40px;padding-left:40px}}";
            $css[]="@media all and (max-width: 1024px){.artica-div-left,.artica-div-right{position:relative;padding-right:20px;padding-left:20px;overflow:visible}.artica-div-left{right:0}.artica-main-container{display:block;align-items:none;height:auto;padding:0}.artica-logo-wrapper{height:auto;padding-top:75px}.artica-main-inside{padding:125px 0;margin:0 auto}.unauthorized-links{display:block;align-items:none;height:auto;padding-bottom:75px}.artica-div-right{left:0;text-align:center}.artica-form{display:inline-block;padding:125px 0;margin:0}}";
            $css[]="@media all and (max-height: 625px)and (max-width: 1024px){.artica-logo-wrapper{padding-top:40px}.artica-main-inside{padding:40px 0}.artica-form{display:inline-block;padding:75px 0;margin:0}.artica-logo-wrapper{padding-top:75px}}";
            $css[]=".artica-form2 .dropdown a{color:$this->DivRigthColor}";
            $css[]=".artica-form2 .dropdown .default{color:$this->DivRigthColor}";
            $css[]=".artica-form2 .dropdown .deselect-option{right:30px;top:3px}";
            $css[]=".artica-form2 .inline-error{margin-left:0}";
            $css[]=".artica-main-container .form-actions .articabut-primary{margin-right:5px}";
            $css[]=".rotate{animation:rotating 1s linear infinite;-ms-animation:rotating 1s linear infinite;-webkit-animation:rotating 1s linear infinite;-moz-animation:rotating 1s linear infinite;-o-animation:rotating 1s linear infinite}";
            $css[]=".articabut .icon.rotate,.articabut .uw-captcha a.rotate.refresh-image-captcha,.uw-captcha .articabut a.rotate.refresh-image-captcha,.btn-secondary .icon.rotate,.btn-secondary .uw-captcha a.rotate.refresh-image-captcha,.uw-captcha .btn-secondary a.rotate.refresh-image-captcha,.articabut-blue .icon.rotate,.articabut-blue .uw-captcha a.rotate.refresh-image-captcha,.uw-captcha .articabut-blue a.rotate.refresh-image-captcha,.articabut .rotate.inline-error:before,.btn-secondary .rotate.inline-error:before,.articabut-blue .rotate.inline-error:before{position:absolute;font-size:16px;line-height:0;margin:7px 0 0 3px}";
            $css[]="@keyframes rotating{from{transform:rotate(0deg);-ms-transform:rotate(0deg);-moz-transform:rotate(0deg);-webkit-transform:rotate(0deg);-o-transform:rotate(0deg)}";
            $css[]="to{transform:rotate(360deg);-ms-transform:rotate(360deg);-moz-transform:rotate(360deg);-webkit-transform:rotate(360deg);-o-transform:rotate(360deg)}";
            $css[]="}";
            $css[]=".token-list{border-bottom:1px solid #e2e3e4;border-top:1px solid #e2e3e4;display:block;list-style:outside none none;overflow:hidden;padding:10px 0}";
            $css[]=".token-list li{float:left;font-size:120%;margin:0;padding:3px 0;text-align:center;width:50%}";
            $css[]=".file-custom::after{content:\"\"}";
            $css[]=".artica-form-item.error label{color:#f00000}";
            $css[]=".color-danger{color:#f00000}";
            $css[]=".color-info{color:#78d2dc}";
            $css[]=".color-success{color:#48c878}";
            $css[]=".color-warning{color:#e0ad3f}";
            $css[]="form{margin-bottom:25px}";
            $css[]=".artica-form-exit .artica-form-exit{position:static;width:100%}";
            $css[]=".artica-form-exit .artica-form-exit .uw-date-mask-element{margin-top:10px;display:inline-block;width:60%}";
            $css[]=".artica-form-exit .artica-form-exit .uw-date-mask-element input{width:100% !important}";
            $css[]=".artica-form-exit .artica-form-exit .vat-selector{width:60%;margin-bottom:7px}";
            $css[]=".artica-form-exit img{border:1px solid #e2e3e4}";
            $css[]=".artica-form-item.legend{margin-top:-18px;position:inherit;margin-bottom:0}";
            $css[]=".artica-form .artica-form-item.legend{margin-top:-28px;position:inherit}";
            $css[]=".artica-main-container.artica-llform{display:flex;align-items:center}";
            $css[]=".artica-form-item.legend .artica-form-exit{font-size:12px}";
            $css[]=".artica-form label.radio-inline{width:25% !important}";
            $css[]=".artica-form .artica-form-exit{width:100%}";
            $css[]=".artica-spacer-top{background:#f7f7f7 none repeat scroll 0 0;height:30px;text-align:center;font-family:\"myriad pro\",\"myriad\",Helvetica,Arial,sans-serif;-webkit-font-smoothing:antialiased;border-bottom:1px solid #ebebe6;position:fixed;top:0;z-index:100;font-size:16px;width:100%}";
            $css[]=".artica-spacer-top .artica-spacer-centered{margin:0 auto}";
            $css[]=".artica-spacer-top .grid{display:block;font-size:0;margin:0 auto;padding:0;text-align:left}";
            $css[]=".artica-spacer-top .artica-spacer-cell{display:inline-block;font-size:16px;margin:0;padding:0 20px;text-align:left;vertical-align:top;width:100%}";
            $css[]=".artica-div-left,.artica-div-right{top:30px}";
            $css[]=".artica-form2 input:not([type=submit]):not([type=file]):not([type=checkbox]):not([type=radio])::-webkit-input-placeholder{color:$this->DivRigthColor;opacity:1 !important}";
            $css[]=".artica-form2 input:not([type=submit]):not([type=file]):not([type=checkbox]):not([type=radio])::-moz-placeholder{color:$this->DivRigthColor;opacity:1 !important}";
            $css[]=".artica-form2 input:not([type=submit]):not([type=file]):not([type=checkbox]):not([type=radio]):-ms-input-placeholder{color:$this->DivRigthColor;opacity:1 !important}";
            $css[]=".artica-form2 input:not([type=submit]):not([type=file]):not([type=checkbox]):not([type=radio])::placeholder{color:$this->DivRigthColor;opacity:1 !important}";
            $css[]=".artica-form2 .dropdown>a{color:$this->DivRigthColor}";
            $css[]="p.small{font-size:12px}";
            $css[]=".artica-div-left,.artica-div-right{position:relative}";
            $css[]=".artica-div-right{left:0;width:100%}";
            $css[]=".artica-div-login{display:flex;flex-direction:column}";
            $css[]=".artica-logo-wrapper{padding-top:20px;display:flex;color:$this->DivLeftColor;}";
            $css[]=".artica-logo-wrapper .artica-logo img{height:25px}";
            $css[]=".artica-main-inside{padding:10px 0 20px 0}";
            $css[]=".artica-main-inside h1,.artica-main-inside h3,.artica-main-inside p{display:none}";
            $css[]=".artica-main-inside .articabut,.artica-main-inside .btn-secondary,.artica-main-inside .articabut-blue{margin-top:10px}";
            $css[]=".artica-form{padding:30px 0}";
            $css[]="";
            $css[]="@media(min-width: 576px){.artica-div-right .brand-up,.artica-div-right .brand-down{display:block}.artica-main-inside h1,.artica-main-inside h3,.artica-main-inside p{display:block}.artica-form .form-link{margin-left:10px}}";
            $css[]="@media(min-width: 992px){.artica-div-left,.artica-div-right{position:fixed}.artica-div-left{right:40%}.artica-div-right{left:60%;width:auto}.artica-logo-wrapper{padding-top:40px;display:block}.artica-logo-wrapper .artica-logo img{height:40px}.artica-main-inside .articabut,.artica-main-inside .btn-secondary,.artica-main-inside .articabut-blue{margin-top:35px}}";
            $css[]="@media(min-width: 1222px){.choose-language-certificate-form{width:50%}}";
            $css[]="";
            $css[]="";
            $css[]=".articabut , .articabut-blue {";
            $css[]="	position: relative;";
            $css[]="	display: inline-block;";
            $css[]="	padding: 13px 30px;";
            $css[]="	font-family: \"myriad-pro\",Myriad Pro,Arial,sans-serif;";
            $css[]="	font-size: 12px;";
            $css[]="	font-weight: 600;";
            $css[]="	line-height: 1;";
            $css[]="	letter-spacing: .1em;";
            $css[]="	text-align: center;";
            $css[]="	text-transform: uppercase;";
            $css[]="	cursor: pointer;";
            $css[]="	border: 0;";
            $css[]="	border-radius: 2px;";
            $css[]="	transition: all .3s ease-in-out;";
            $css[]="	appearance: none;";
            $css[]="	user-select: none;";
            $css[]="}";
            $css[]="";
            $css[]="button, html input[type=\"button\"], input[type=\"reset\"], input[type=\"submit\"] {";
            $css[]="    cursor: pointer;";
            $css[]="    -webkit-appearance: button;";
            $css[]="";
            $css[]="}";
            $css[]=".articabut-blue {";
            $css[]="    color: $this->DivRigthButtonTextColor;";
            $css[]="    background-color: $this->DivRigthButtonColor;";
            $css[]="";
            $css[]="}";
            $css[]="artica-form .form-link {";
            $css[]="    margin-left: 10px;";
            $css[]="}";
            $css[]="artica-form .form-link {";
            $css[]="    margin-left: 0;";
            $css[]="    margin-top: 10px;";
            $css[]="}";
            $css[]=".form-link:hover {";
            $css[]="    color: #5f6468;";
            $css[]="}";
            $css[]=".form-link {";
            $css[]="    display: inline-block;";
            $css[]="    margin-left: 15px;";
            $css[]="    font-size: 14px;";
            $css[]="    color: $this->DivRigthColor;";
            $css[]="}";
            $css[]=".artica-error { position: relative; display: inline-block; width: 100%; padding: 20px; margin-bottom: 20px; font-size: 14px; font-weight: 600; text-align: center; border-radius: 2px; color: #f00000; background-color: rgba(240,0,0,.2); }";
            return @implode("\n",$css);
    }
	public function enterprise_login_form():array{
        $t=time();
        if(count($this->opts)>0){
            foreach ($this->opts as $attr=>$value){
                $this->{$attr}=$value;
            }
        }
        $content[]="    <div class=\"artica-form\">";
        $content[]="    <h3><!-- LOGINTITLE --></h3>";
        $content[]="<!-- ERROR -->";
        $content[]="<form method=\"post\" id=\"form-$t\" name=\"authForm\" action=\"/auth\" class=\"artica-form2\">";
        $content[]="<input type='hidden' id='ActiveDirectoryLogin' name='ActiveDirectoryLogin' value='yes'>";
        $content[]="<!-- FHIDDENS -->";
        $content[]="        <div class=\"artica-form-item\">";
        $content[]="            <div class=\"artica-form-exit\">";
        $content[]="                    <input name=\"username\" maxlength=\"255\" id=\"username\" placeholder=\"%LABEL_USER\" class=\"form-control\" type=\"text\" style=\"background-image: %FIELDBG;\" autocomplete=\"off\">";
        $content[]="            </div>";
        $content[]="        </div>    ";
        $content[]="        <div class=\"artica-form-item\">";
        $content[]="            <div class=\"artica-form-exit\"><input type=\"password\" name=\"password\" autocomplete=\"off\" id=\"password\" placeholder=\"%LABEL_PASSWORD\" class=\"form-control\" value=\"\" style=\"background-image: %FIELDBG;\"></div>";
        $content[]="        </div>";
        $content[]="        <!-- Actions -->";
        $content[]="        <div class=\"artica-buttons\">";
        $content[]="            <input class=\"articabut articabut-blue\" type=\"submit\" value=\"%SUBMIT\" name=\"submit-1\" id=\"authForm-submit-1\">";
        $content[]="        </div>";
        $content[]="        <!-- REGISTER -->";
        return $content;

    }
    private function wifi4eu_template():string{
        $this->WIFI4EUImage = "<img id=\"wifi4eubanner\"  class=\"identity-image\">";
        $this->HotSpotWIFI4EU_DEBUG_VAL = "var selftestModus = false;";
        if ($this->HotSpotWIFI4EU_DEBUG == 1) {
            $this->HotSpotWIFI4EU_DEBUG_VAL = "var selftestModus = true;";
        }

        $footer="http://articatech.net";
        if($this->HotSpotWIFI4EU_LANG=="es"){
            $footer="https://artica-proxy.es";
        }
        if($this->HotSpotWIFI4EU_LANG=="pt"){
            $footer="https://artica-proxy.pt";
        }
        $ArticaSplashHotSpotRedirectText=$GLOBALS['CLASS_SOCKETS']->GET_INFO('ArticaSplashHotSpotRedirectText');
        $TEMPLATE[]="<!DOCTYPE html>";
        $TEMPLATE[]="<html  lang=\"en\">";
        $TEMPLATE[]="<head>";
        $TEMPLATE[]="<!-- HEAD_START -->";
        $TEMPLATE[]="<meta http-equiv=\"content-type\" content=\"text/html; charset=UTF-8\">";
        $TEMPLATE[]="<meta http-equiv=\"x-ua-compatible\" content=\"ie=edge\">";
        $TEMPLATE[]="<title>WIFI4EU</title>    ";
        $TEMPLATE[]="<meta name=\"viewport\" content=\"width=device-width, initial-scale=1.0\">";
        $TEMPLATE[]="<script type=\"text/javascript\" language=\"javascript\" src=\"/hotspot.php?local-js=jquery-1.8.3.js\"></script>";
        $TEMPLATE[]="<script type=\"text/javascript\" language=\"javascript\" src=\"/hotspot.php?local-js=jquery-ui-1.8.22.custom.min.js\"></script>";
        $TEMPLATE[]="<link href=\"hotspot.php?css-999998=yes\" media=\"screen\" rel=\"stylesheet\" type=\"text/css\">";
        $TEMPLATE[]= "</head>";
        $TEMPLATE[]="<body>";
        $TEMPLATE[]="<div id=\"identityContainer\" class=\"identity-container\">";
        $TEMPLATE[]="$this->WIFI4EUImage";
        if(!empty($this->WIFI4EUTEXTH1)){
            $TEMPLATE[]="<h1 class=\"h1\">$this->WIFI4EUTEXTH1</h1>";
        }
        if(!empty($this->WIFI4EUTEXTH2)){
            $TEMPLATE[]="<h2 class=\"h2\">$this->WIFI4EUTEXTH2</h2>";
        }
        $TEMPLATE[]="%DYNAMIC_CONTENT%";

        $TEMPLATE[]="<button id=\"connect_button\" type=\"submit\" form=\"wifi4euform\" class=\"button\">$this->WIFI4EUTEXTBTN</button>";
        if($this->WIFI4UEENABLETERMS==1){
                $TEMPLATE[]="<div id=\"modalTerms\" class=\"modal\"><div class=\"modal-content\"><span class=\"closet\">&times;</span><p>$this->WIFI4UETERMSCONTENT</p></div></div>";
                $TEMPLATE[]="<label class=\"container\"><input id=\"terms_conditions\" type=\"checkbox\"><span class=\"checkmark checkmarkterms\"></span><span style=\"padding-left: 30px;\">$this->WIFI4UETERMSTEXT</span></label>";
        }
        if($this->WIFI4UEENABLEPRIVACY==1){
            $TEMPLATE[]="<div id=\"modalPrivacy\" class=\"modal\"><div class=\"modal-content\"><span class=\"closep\">&times;</span><p>$this->WIFI4UEPRIVACYCONTENT</p></div></div>";
            $TEMPLATE[]="<label class=\"container\"><input id=\"privacy_policy\" type=\"checkbox\"><span class=\"checkmark checkmarkprivacy\"></span><span style=\"padding-left: 30px;\">$this->WIFI4UEPRIVACYTEXT</span></label>";
        }
        $TEMPLATE[]="</div>";
        $TEMPLATE[]="<footer class=\"footer\">Power by <a href='$footer' target='_new'>Artica Tech</a></footer>";

        $TEMPLATE[]="<script type=\"text/javascript\">
    var wifi4euTimerStart = Date.now();
    var wifi4euNetworkIdentifier = '$this->HotSpotWIFI4EU_UUID';
    var wifi4euLanguage = '$this->HotSpotWIFI4EU_LANG';
    
    var termsval=false;
    var privacyval=false;

    window.onclick = function(event) {           
        if (event.target == modalterms) {  
            modalterms.style.display = \"none\";
        }
        if (event.target == modalprivacy) {
            modalprivacy.style.display = \"none\";
        }
    }
    ";
        if($this->WIFI4UEENABLETERMS==1) {
            $TEMPLATE[] = "
    //TERMS AND CONDITIONS        
    termsval=true;
    $('#terms_conditions').change(function() {\$(\".checkmarkterms\").css({\"border\":\"0px solid red\"});})
    var modalterms = document.getElementById(\"modalTerms\");
    var terms = document.getElementById(\"terms\");
    var span1 = document.getElementsByClassName(\"closet\")[0];
    terms.onclick = function() {
        modalterms.style.display = \"block\";
    }
    span1.onclick = function() {
        modalterms.style.display = \"none\";
    } ";
        }
        if($this->WIFI4UEENABLEPRIVACY==1){
            $TEMPLATE[]="
    //PRIVACY POLICY        
    privacyval=true;
    $('#privacy_policy').change(function() {\$(\".checkmarkprivacy\").css({\"border\":\"0px solid red\"});})
    var modalprivacy = document.getElementById(\"modalPrivacy\");
    var privacy = document.getElementById(\"privacy\");
    var span2 = document.getElementsByClassName(\"closep\")[0];
    privacy.onclick = function() {
        modalprivacy.style.display = \"block\";
    }
    span2.onclick = function() {
        modalprivacy.style.display = \"none\";
    }
            ";
        }
    $TEMPLATE[]="
            $('#wifi4euform').on('submit', function (e) {
                e.preventDefault();
                if (termsval==true){
                    $(\".checkmarkterms\").css({\"border\":\"0px solid red\"});
                    if($(\"#terms_conditions\").prop('checked') == false){
                        console.log('term unchecked')
                        $(\".checkmarkterms\").fadeTo('slow', 0.5).css({\"border\":\"2px solid red\"}).fadeTo('slow', 1.0);
                        return false;
                        
                    }
                }
                if (privacyval==true){
                    if($(\"#privacy_policy\").prop('checked') == false){
                        console.log('privacy unchecked')
                        $(\".checkmarkprivacy\").fadeTo('slow', 0.5).css({\"border\":\"2px solid red\"}).fadeTo('slow', 1.0);
                        return false;
                        
                    }
                }
                var dataString = $(\"#wifi4euform\").serialize();
                var url = 'hotspot.php'
                $.ajax({
                    type: 'POST',
                    url: url,
                    data: dataString,
                    success: function (bucket) {
                    console.log(bucket)
                        var isURL = validURL(bucket);
                        console.log(isURL);                       
                        if(isURL == true){
                            Swal.fire({
                              position: 'top-end',
                              icon: 'success',
                              title: '$ArticaSplashHotSpotRedirectText',
                              showConfirmButton: false,
                              timer: 2000
                            }).then(function(){
                                location.href=bucket
                                }                           
                            )             
                        }
                        else {
                            Swal.fire(
                                'Warning',
                                '$this->WIFI4UEERRORTEXT',
                                'warning'
                            )
                        }
 
                    },
                    error: function (bucket){
                        Swal.fire(
                            'error',
                            bucket.responseJSON,
                            'error'
                        )
                    }
                })
            })
            
            function validURL(str) {
                console.log(typeof(str))
                try{
                      var pattern = new RegExp('^(https?:\\\/\\\/)?'+ // protocol
                        '((([a-z\\\d]([a-z\\\d-]*[a-z\\\d])*)\\\.)+[a-z]{2,}|'+ // domain name
                        '((\\\d{1,3}\\\.){3}\\\d{1,3}))'+ // OR ip (v4) address
                        '(\\\:\\\d+)?(\\\/[-a-z\\\d%_.~+]*)*'+ // port and path
                        '(\\\?[;&a-z\\\d%_.~+=-]*)?'+ // query string
                        '(\\\#[-a-z\\\d_]*)?$','i'); // fragment locator
                      return !!pattern.test(str);
                  }
                  catch(error){
                      return false;
                      
                  }
            }

 
    </script>
    ";
        $TEMPLATE[]="<script src=\"//cdn.jsdelivr.net/npm/sweetalert2@10\"></script>";
        $TEMPLATE[]="<script type=\"text/javascript\" src=\"https://collection.wifi4eu.ec.europa.eu/wifi4eu.min.js\"></script>";
        $TEMPLATE[]="<!-- JSEND -->";
        $TEMPLATE[]="</body>";
        $TEMPLATE[]="</html>";
        return @implode("\n",$TEMPLATE);
    }

    private function entreprise_template():string{
	    $bgphoto=$this->DivBackgroundImage;
        $scr1=" type=\"text/javascript\" language=\"javascript\"";
        $TEMPLATE[]="<!DOCTYPE html>";
        $TEMPLATE[]="<html  lang=\"en\">";
        $TEMPLATE[]="    <head>";
        $TEMPLATE[]="         <!-- HEAD_START -->";
        $TEMPLATE[]="        <meta http-equiv=\"content-type\" content=\"text/html; charset=UTF-8\">";
        $TEMPLATE[]="        <meta http-equiv=\"x-ua-compatible\" content=\"ie=edge\">";
        $TEMPLATE[]="        <title><!-- MainTitle --></title>    ";
        $TEMPLATE[]="        <meta name=\"viewport\" content=\"width=device-width, initial-scale=1\">";
        $TEMPLATE[]="<script $scr1 src=\"/hotspot.php?local-js=jquery-1.8.3.js\"></script>";
        $TEMPLATE[]="<script $scr1 src=\"/hotspot.php?local-js=jquery-ui-1.8.22.custom.min.js\"></script>";
        $TEMPLATE[]="        <style type=\"text/css\">.tk-myriad-pro{font-family:\"myriad-pro\",sans-serif;}</style>";
        $TEMPLATE[]="<link href=\"/hotspot.php?css-99999=yes\" media=\"screen\" rel=\"stylesheet\" type=\"text/css\">";
        $TEMPLATE[]="";
        $TEMPLATE[]="<body>";
        $TEMPLATE[]="    <div style=\"display: block;\">";
        $TEMPLATE[]="        <img src=\"/img/pt.gif\" width=\"1\" height=\"1\">";
        $TEMPLATE[]="    </div>";
        $TEMPLATE[]="    <div class=\"artica-spacer-top\">";
        $TEMPLATE[]="        <div class=\"grid artica-spacer-centered\">";
        $TEMPLATE[]="            <div class=\"artica-spacer-cell\"></div>";
        $TEMPLATE[]="        </div>";
        $TEMPLATE[]="    </div>";
        $TEMPLATE[]="<div class=\"artica-div-login\">";
        $TEMPLATE[]="    <aside class=\"artica-div-left\" style=\"background: url('$bgphoto') no-repeat center center; background-size: cover;background-color:$this->DivLeftBGColor;\">";
        $TEMPLATE[]="        <div class=\"artica-logo-wrapper\">";
        $TEMPLATE[]="        <h2><!-- SubWelcome --></h2>";

        $TEMPLATE[]="</div>";
        $TEMPLATE[]="<div class=\"artica-main-container\">";
        $TEMPLATE[]="    <div class=\"artica-main-inside\">";
        $TEMPLATE[]="        <h1><!-- MainTitle --></h1>";
        $TEMPLATE[]="        <h3><!-- MainExplain --></h3>";
        $TEMPLATE[]="        <p style='font-size: medium;'><!-- SecondExplain --></p>";
        $TEMPLATE[]="    </div>";
        $TEMPLATE[]="</div>";
        $TEMPLATE[]="</aside>";
        $TEMPLATE[]="";
        $TEMPLATE[]="    <div class=\"artica-div-right\">";
        $TEMPLATE[]="    <div class=\"artica-main-container artica-llform\">";
        $TEMPLATE[]="<!-- ContentStart -->";
        $TEMPLATE[]="%DYNAMIC_CONTENT%";
        $TEMPLATE[]="<!-- ContentEnd -->";
        $TEMPLATE[]="</div>";
        $TEMPLATE[]="</div>";
        $TEMPLATE[]="    </div>";
        $TEMPLATE[]="</div>";
        $TEMPLATE[]="<!-- JSEND -->";
        $TEMPLATE[]="</body>";
        $TEMPLATE[]="</html>";
        return @implode("\n",$TEMPLATE);

    }

	
	
	private function load(){


        if($GLOBALS["VERBOSE"]){
            VERBOSE("templateid:$this->templateid", __LINE__);
            $trace=@debug_backtrace();
            foreach ($trace as $index=>$array){
                $line=$array["line"];
                $function=$array["function"];
                $file=basename($array["file"]);
                VERBOSE("From $file $function() in line $line", __LINE__);
            }

        }
	    if($this->templateid==99999){
            VERBOSE("Building specific template 99999", __LINE__);
	        $this->BodyContent=$this->entreprise_template();
	        return true;
        }

        if($this->templateid==999998){
            VERBOSE("Building specific template 99999", __LINE__);
            $this->BodyContent=$this->wifi4eu_template();
            return true;
        }

        $tpl=new template_admin();
		$q=new lib_sqlite("/home/artica/SQLITE/proxy.db");
		$ligne=$q->mysqli_fetch_array("SELECT * FROM templates_manager WHERE ID='$this->templateid'");
		
		$this->CssContent=base64_decode($ligne["CssContent"]);
		$this->headContent=base64_decode($ligne["headContent"]);
		$this->BodyContent=base64_decode($ligne["BodyContent"]);
		$this->TemplateName=$tpl->utf8_encode($ligne["TemplateName"]);
		
		if($GLOBALS["VERBOSE"]){VERBOSE("CssContent:".strlen($this->CssContent), __LINE__);}
		
		if($this->headContent==null){
			$this->headContent="<!DOCTYPE HTML>\n\t<html>\n\t\t<head>\n\t\t\t<title>%TITLE_HEAD%</title>\n\t\t\t%JQUERY%\n\t\t\t%CSS%\n\t\t\t<script type=\"text/javascript\">\n\t\t\tfunction blur(){ }\n\t\t\tfunction checkIfTopMostWindow(){ }\n\t\t</script>\n\t\t</head>";
		}
		
		if(strpos($this->headContent, "checkIfTopMostWindow")==0){
			$this->headContent=str_ireplace("</head>", "\n<script>\n\tfunction checkIfTopMostWindow(){}\n</script>\n</head>",$this->headContent);
		}

		if(strlen($this->BodyContent)<5){$this->BodyContent="<body>%DYNAMIC_CONTENT%";}
		
	}
	
	public function BuildTables(){
		return true;
	}
	
	public function Create(){
	
		$q=new lib_sqlite("/home/artica/SQLITE/proxy.db");
		$sql="INSERT INTO templates_manager (`TemplateName`) VALUES ('$this->TemplateName')";
		$q->QUERY_SQL($sql);
		$this->ok=$q->ok;
		$this->mysql_error=$q->mysql_error;
		$this->mysql_error_html=$q->mysql_error_html();
	}
	
	function Delete(){
		$q=new lib_sqlite("/home/artica/SQLITE/proxy.db");
		$q->QUERY_SQL("DELETE FROM templates_manager WHERE ID='$this->templateid'");
		if(!$q->ok){echo $q->mysql_error;}
	}
	
	public function Save(){
		$CssContent=base64_encode($this->CssContent);
		$headContent=base64_encode($this->headContent);
		$BodyContent=base64_encode($this->BodyContent);
		$TemplateName=mysql_escape_string2($this->TemplateName);
		$q=new lib_sqlite("/home/artica/SQLITE/proxy.db");
		
		$sql="UPDATE templates_manager 
			SET 
			`CssContent`='$CssContent',
			`headContent`='$headContent',
			`BodyContent`='$BodyContent',
			`TemplateName`='$TemplateName' WHERE `ID`='$this->templateid'";
		
		$q->QUERY_SQL($sql);
		$this->ok=$q->ok;
		$this->mysql_error=$q->mysql_error;
		$this->mysql_error_html=$q->mysql_error_html();
        if($this->ok){
            $GLOBALS["CLASS_SOCKETS"]->REST_API("/proxy/templates");
        }
	}
	
	
	
	
}


class templates_objects{

    
    public function __construct(){
    }

    private function _imageinline($img_file):string{
        $imgData = base64_encode(file_get_contents($img_file));
        return 'data: '.mime_content_type($img_file).';base64,'.$imgData;

    }
    private function _jsinline($jsfile):string{
        if(!is_file($jsfile)){return "";}
        $Data = base64_encode(file_get_contents($jsfile));
        return "data:text/javascript;base64,$Data";

    }
    private function fileinline($filename):string{
        $BASE_FILES="/usr/share/squid3/icons/tplfiles";
        if(preg_match("#\.(jpg|png|gif|webp|tiff|psd|raw|bmp|heif|indd|svg|ai|eps)$#",strtolower($filename))){
            return $this->_imageinline("$BASE_FILES/$filename");
        }
        if(preg_match("#\.js$#",$filename)){
            return $this->_jsinline("$BASE_FILES/$filename");
        }

        return "";
    }

    public function ParseContent($content=null):string{
        if($content==null){return "";}
        if(!preg_match_all("#\[file=(.*?)\]#s", $content, $re)){return $content;}
       
        foreach ($re[1] as $num=>$filename){
                $replace=$this->fileinline($filename);
                if($GLOBALS["VERBOSE"]) {
                    echo "ParseContent: {$re[0][$num]} == <$replace>\n";
                }
                $content=str_replace($re[0][$num], $this->fileinline($filename), $content);
            }
        return $content;
    }


}