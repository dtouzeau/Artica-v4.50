<?php
include_once(dirname(__FILE__) . "/class.sqlite.inc");
include_once(dirname(__FILE__) . "/class.templates.manager.inc");
include_once(dirname(__FILE__) . "/class.wifidog.settings.inc");
include_once(dirname(__FILE__) . "/class.users.menus.inc");

class wifidog_templates
{
    private $head_content;
    private $ruleid;
    private $headerAdd;
    public $MainTitle;
    public $FORM_HEAD;
    public $backgroundColor = null;
    public $RedirectPage = null;

    public $FontSize = null;
    public $FieldsStyle = null;
    public $LegendsStyle = null;
    public $BackgroundPicturePath="";
    public $SMS_CODE_ERROR=null;

    public $BackgroundRepeat = null;
    public $WelcomeMessage = null;
    public $WelcomeMessageActiveDirectory = null;
    public $LabelVoucher = null;
    public $VoucherExplain=null;
    public $VoucherDevice=null;
    public $TERMS_EXPLAIN = null;
    public $TERMS_TITLE=null;
    public $TERMS_CONDITIONS = null;
    public $REGISTER_MESSAGE_EXPLAIN = null;
    public $REGISTER_MESSAGE_SUCCESS = null;
    public $REGISTER_MESSAGE_TIMEOUT = null;
    public $REGISTER_MESSAGE = null;
    public $REGISTER_GENERIC_PASSWORD = 0;
    public $REGISTER_GENERIC_LABEL = null;
    public $REGISTER_GENERIC_PASSTXT = null;
    public $REGISTER_GENERIC_PASSERR = null;
    public $REGISTER_SUBJECT = null;
    public $CONFIRM_MESSAGE = null;
    public $RegisterTitle = null;
    public $RegisterButton=null;
    public $LoginTitle = null;

    public $LostPasswordLink = null;
    public $ConnectionButton = null;
    public $AcceptButton = null;
    public $ArticaSplashHotSpotRedirectText = null;
    public $LabelUsername = null;
    public $DomainAccount = null;
    public $LabelPassword = null;
    public $SMS_SMTP_SUBJECT = null;
    public $SMS_SMTP_BODY = null;
    public $SMS_CONFIRM_MESSAGE = null;
    public $SMS_BUTTON = null;
    public $SMS_INTRO = null;
    public $SMS_FIELD = null;
    public $SMS_FONT_SIZE = 0;
    public $FooterText = null;
    public $RECOVER_MESSAGE = null;
    public $ERROR_STYLE = null;

    public $authentication_failed = null;
    public $LabelEmail = null;
    public $SubmitButton = null;
    public $MobileLabel = null;
    public $LabelConfirm = null;
    public $CancelButton = null;
    public $PasswordMismatch = null;
    public $SessionExpired=null;
    public $ErrorThisAccountExists = null;
    public $ErrorInvalidMail = null;
    public $ALL_METHODS_LABELS = null;
    public $TemplateID = 0;
    private $CORP_LICENSE = false;

    public $HotSpotAutoLogin;
    public $formHiddens;
    public $MainExplain;
    public $ErrorMessage;
    public $BuildUrlForceRegister;
    public $DivLeftColor;
    public $DivLeftBGColor;
    public $DivRigthColor;
    public $DivRigthBGColor;
    public $DivRigthButtonColor;
    public $DivRigthButtonTextColor;
    public $DivRigthButtonHoverColor;
    public $DivBackgroundImage;
    //WIFI4EU
    public $HotSpotWIFI4EU_ENABLE;
    public $HotSpotWIFI4EU_UUID;
    public $HotSpotWIFI4EU_LANG;
    public $HotSpotWIFI4EU_DEBUG;
    public $WIFI4EUImage;
    public $WIFI4EUTEXTH1;
    public $WIFI4EUTEXTH2;
    public $WIFI4EUTEXTBTN;
    public $WIFI4EUTEXTH1COLOR;
    public $WIFI4EUTEXTH2COLOR;
    public $FontFamily="";
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
    public $backGroundField;
    public $SubWelcome=null;
    public $SuccessWelcome=null;
    public $SuccessTitle=null;

    function __construct($template_id = 0)
    {
        $users = new usersMenus();
        $this->CORP_LICENSE = $users->CORP_LICENSE;
        $this->TemplateID = $template_id;
        $this->default_values();


    }

    public function CSS_99999()
    {
        $opts = $this->GetOpts();
        $templates_manager = new templates_manager(99999, $opts);
        return $templates_manager->enterprise_login_css();


    }

    public function CSS_999998()
    {
        $opts = $this->GetOptsWifi4EU();
        $templates_manager = new templates_manager(999998, $opts);
        return $templates_manager->wifi4eu_css();


    }


    function default_values()
    {
        $this->MainTitle = "HotSpot System";
        $SockTemplate = new wifidog_settings();
        $this->WelcomeMessage = $this->utf8_decode($SockTemplate->GET_INFO("WelcomeMessage"));
        $this->WelcomeMessageActiveDirectory = $this->utf8_decode($SockTemplate->GET_INFO("WelcomeMessageActiveDirectory"));
        $this->RegisterTitle =  $this->utf8_decode($SockTemplate->GET_INFO("RegisterTitle"));
        $this->RegisterButton =  $this->utf8_decode($SockTemplate->GET_INFO("RegisterButton"));

        $this->LoginTitle = $this->utf8_decode($SockTemplate->GET_INFO("LoginTitle"));
        //$this->TemplateID=intval($SockTemplate->GET_INFO("TemplateID"));
        if ($this->TemplateID == 0) {
            $this->TemplateID = 4;
        }
        if ($this->TemplateID <> 99999) {
            if (!$this->CORP_LICENSE) {
                $this->TemplateID = 4;
            }
        }
        $this->backGroundField=$SockTemplate->GET_INFO("backGroundField");
        $this->backgroundColor = $SockTemplate->GET_INFO("backgroundColor");
        $this->SubWelcome =  $this->utf8_decode($SockTemplate->GET_INFO("SubWelcome"));
        $this->SuccessTitle= $this->utf8_decode($SockTemplate->GET_INFO("SuccessTitle"));
        $this->SuccessWelcome=  $this->utf8_decode($SockTemplate->GET_INFO("SuccessWelcome"));
        $this->DivLeftColor = $SockTemplate->GET_INFO("DivLeftColor");
        $this->DivLeftBGColor = $SockTemplate->GET_INFO("DivLeftBGColor");


        $this->DivRigthColor = $SockTemplate->GET_INFO("DivRigthColor");
        $this->DivRigthBGColor = $SockTemplate->GET_INFO("DivRigthBGColor");
        $this->DivRigthButtonColor = $SockTemplate->GET_INFO("DivRigthButtonColor");
        $this->DivRigthButtonTextColor = $SockTemplate->GET_INFO("DivRigthButtonTextColor");
        $this->DivRigthButtonHoverColor = $SockTemplate->GET_INFO("DivRigthButtonHoverColor");
        $this->DivBackgroundImage = $SockTemplate->GET_INFO("DivBackgroundImage");

        $this->FontFamily = intval($SockTemplate->GET_INFO("FontFamily"));
        $this->FontSize = intval($SockTemplate->GET_INFO("FontSize"));

        $this->FieldsStyle = $SockTemplate->GET_INFO("FieldsStyle");
        $this->LegendsStyle = $SockTemplate->GET_INFO("LegendsStyle");


        $this->BackgroundRepeat = $SockTemplate->GET_INFO("BackgroundRepeat");
        $this->ConnectionButton = $SockTemplate->GET_INFO("ConnectionButton");
        $this->FooterText = $this->utf8_decode($SockTemplate->GET_INFO("FooterText"));


        $this->LabelUsername = $this->utf8_decode($SockTemplate->GET_INFO("LabelUsername"));
        $this->LabelPassword = $this->utf8_decode($SockTemplate->GET_INFO("LabelPassword"));
        $this->DomainAccount = $this->utf8_decode($SockTemplate->GET_INFO("DomainAccount"));

        $this->TERMS_EXPLAIN = $this->utf8_decode($SockTemplate->GET_INFO("TERMS_EXPLAIN"));
        $this->TERMS_TITLE = $this->utf8_decode($SockTemplate->GET_INFO("TERMS_TITLE"));
        $this->TERMS_CONDITIONS = $this->utf8_decode($SockTemplate->GET_INFO("TERMS_CONDITIONS"));
        $this->REGISTER_MESSAGE_EXPLAIN =  $this->utf8_decode($SockTemplate->GET_INFO("REGISTER_MESSAGE_EXPLAIN"));
        $this->REGISTER_MESSAGE_SUCCESS =  $this->utf8_decode($SockTemplate->GET_INFO("REGISTER_MESSAGE_SUCCESS"));
        $this->REGISTER_MESSAGE_TIMEOUT =  $this->utf8_decode($SockTemplate->GET_INFO("REGISTER_MESSAGE_TIMEOUT"));


        $this->REGISTER_MESSAGE =  $this->utf8_decode($SockTemplate->GET_INFO("REGISTER_MESSAGE"));
        $this->REGISTER_SUBJECT =  $this->utf8_decode($SockTemplate->GET_INFO("REGISTER_SUBJECT"));
        $this->LostPasswordLink = $SockTemplate->GET_INFO("LostPasswordLink");
        $this->CONFIRM_MESSAGE =  $this->utf8_decode($SockTemplate->GET_INFO("CONFIRM_MESSAGE"));
        $this->LabelVoucher =  $this->utf8_decode($SockTemplate->GET_INFO("LabelVoucher"));
        $this->VoucherDevice=$SockTemplate->GET_INFO("VoucherDevice");
        $this->VoucherExplain= $this->utf8_decode($SockTemplate->GET_INFO("VoucherExplain"));

        $this->SMS_SMTP_SUBJECT = $SockTemplate->GET_INFO("SMS_SMTP_SUBJECT");
        $this->SMS_SMTP_BODY = $SockTemplate->GET_INFO("SMS_SMTP_BODY");
        $this->SMS_INTRO = $SockTemplate->GET_INFO("SMS_INTRO");
        $this->SMS_BUTTON = $SockTemplate->GET_INFO("SMS_BUTTON");
        $this->SMS_FIELD = $SockTemplate->GET_INFO("SMS_FIELD");
        $this->SMS_FONT_SIZE = intval($SockTemplate->GET_INFO("SMS_FONT_SIZE"));
        $this->SMS_CODE_ERROR = $SockTemplate->GET_INFO("SMS_CODE_ERROR");
        $this->FORM_HEAD = $SockTemplate->GET_INFO("FORM_HEAD");

        $this->REGISTER_GENERIC_PASSWORD = intval($SockTemplate->GET_INFO("REGISTER_GENERIC_PASSWORD"));
        $this->REGISTER_GENERIC_LABEL =  $this->utf8_decode($SockTemplate->GET_INFO("REGISTER_GENERIC_LABEL"));
        $this->REGISTER_GENERIC_PASSTXT =  $this->utf8_decode($SockTemplate->GET_INFO("REGISTER_GENERIC_PASSTXT"));
        $this->REGISTER_GENERIC_PASSERR =  $this->utf8_decode($SockTemplate->GET_INFO("REGISTER_GENERIC_PASSERR"));
        $this->ERROR_STYLE = $SockTemplate->GET_INFO("ERROR_STYLE");
        $this->authentication_failed =  $this->utf8_decode($SockTemplate->GET_INFO("authentication_failed"));
        $this->AcceptButton =  $this->utf8_decode($SockTemplate->GET_INFO("AcceptButton"));
        $this->LabelEmail =  $this->utf8_decode($SockTemplate->GET_INFO("LabelEmail"));
        $this->SubmitButton =  $this->utf8_decode($SockTemplate->GET_INFO("SubmitButton"));
        $this->LabelConfirm =  $this->utf8_decode($SockTemplate->GET_INFO("LabelConfirm"));
        $this->CancelButton =  $this->utf8_decode($SockTemplate->GET_INFO("CancelButton"));
        $this->PasswordMismatch =  $this->utf8_decode($SockTemplate->GET_INFO("PasswordMismatch"));
        $this->SessionExpired= $this->utf8_decode($SockTemplate->GET_INFO("SessionExpired"));
        $this->MobileLabel =  $this->utf8_decode($SockTemplate->GET_INFO("MobileLabel"));
        $this->ErrorThisAccountExists = $this->utf8_decode($SockTemplate->GET_INFO("ErrorThisAccountExists"));
        $this->ErrorInvalidMail =  $this->utf8_decode($SockTemplate->GET_INFO("ErrorInvalidMail"));
        $this->ALL_METHODS_LABELS = $SockTemplate->GET_INFO("ALL_METHODS_LABELS");
        $this->ArticaSplashHotSpotRedirectText =  $this->utf8_decode($this->utf8_decode($SockTemplate->GET_INFO("ArticaSplashHotSpotRedirectText")));

        if($this->TERMS_TITLE==null){
            $this->TERMS_TITLE="{Terms_Conditions}";
        }

        if ($this->REGISTER_MESSAGE == null) {
            $this->REGISTER_MESSAGE = "Hi, in order to activate your account on the HotSpot system,\nclick on the link below";
        }
        if ($this->REGISTER_SUBJECT == null) {
            $this->REGISTER_SUBJECT = "HotSpot account validation";
        }
        if ($this->CONFIRM_MESSAGE == null) {
            $this->CONFIRM_MESSAGE = "Success\nA message as been sent to you.\nPlease check your WebMail system in order to confirm your registration<br>\nYour can surf on internet for %s minutes";
        }
        if ($this->SMS_CONFIRM_MESSAGE == null) {
            $this->SMS_CONFIRM_MESSAGE = "Success\nAn SMS as been sent to you\nPlease check your mobile";
        }
        if ($this->SMS_FIELD == null) {
            $this->SMS_FIELD = "Confirmation code";
        }
        if ($this->SMS_FONT_SIZE == 0) {
            $this->SMS_FONT_SIZE = 44;
        }

        if($this->backGroundField==null){
            $this->backGroundField="url(&quot;data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABAAAAASCAYAAABSO15qAAAAAXNSR0IArs4c6QAAAPhJREFUOBHlU70KgzAQPlMhEvoQTg6OPoOjT+JWOnRqkUKHgqWP4OQbOPokTk6OTkVULNSLVc62oJmbIdzd95NcuGjX2/3YVI/Ts+t0WLE2ut5xsQ0O+90F6UxFjAI8qNcEGONia08e6MNONYwCS7EQAizLmtGUDEzTBNd1fxsYhjEBnHPQNG3KKTYV34F8ec/zwHEciOMYyrIE3/ehKAqIoggo9inGXKmFXwbyBkmSQJqmUNe15IRhCG3byphitm1/eUzDM4qR0TTNjEixGdAnSi3keS5vSk2UDKqqgizLqB4YzvassiKhGtZ/jDMtLOnHz7TE+yf8BaDZXA509yeBAAAAAElFTkSuQmCC&quot;);background-repeat: no-repeat; background-attachment: scroll; background-size: 16px 18px; background-position: 98% 50%;";
        }


        if ($this->backgroundColor == null) {
            $this->backgroundColor = "005447";
        }


        if ($this->LabelVoucher == null) {
            $this->LabelVoucher = "Your voucher";
        }
        if($this->VoucherExplain==null){
            $this->VoucherExplain="Please enter here the credentials provided at the hotel reception.<br>
If you do not have them, please dial 003 on your room phone.";
        }
        if($this->VoucherDevice==null){
            $this->VoucherDevice="This session is locked on a different device";
        }

        if ($this->FontSize == 0) {
            $this->FontSize = 16;
        }
        $this->MainTitle = $this->utf8_decode($SockTemplate->GET_INFO("MainTitle"));
        $this->BackgroundPicturePath = $SockTemplate->GET_INFO("BackgroundPicturePath");
        if ($this->BackgroundPicturePath == null) {
            $this->BackgroundPicturePath = "/img/wifidog-500.png";
        }
        if ($this->BackgroundRepeat == null) {
            $this->BackgroundRepeat = "no-repeat";
        }
        if ($this->REGISTER_GENERIC_LABEL == null) {
            $this->REGISTER_GENERIC_LABEL = "Unlock password";
        }
        if ($this->REGISTER_GENERIC_PASSTXT == null) {
            $this->REGISTER_GENERIC_PASSTXT = "Thisisapassword";
        }
        if ($this->REGISTER_GENERIC_PASSERR == null) {
            $this->REGISTER_GENERIC_PASSERR = "Please, ask the correct password to your local administrator";
        }
        if ($this->authentication_failed == null) {
            $this->authentication_failed = "Authentication failed, please retry";
        }
        if ($this->SuccessTitle == null) {
            $this->SuccessTitle = "Authentication Success";
        }


        if ($this->WelcomeMessage == null) {
            $this->WelcomeMessage = "Welcome to our HotSpot system, set here here your user name and password in order to have a free Internet access";
        }
        if ($this->WelcomeMessageActiveDirectory == null) {
            $this->WelcomeMessageActiveDirectory = "use your Active Directory account with your Active Directory domain like <strong>user@domain.ltd</strong>";
        }
        if ($this->REGISTER_MESSAGE_SUCCESS == null) {
            $this->REGISTER_MESSAGE_SUCCESS = "Your Account is Now Validated!<br>Thank you for confirming your email address.";
        }
        if ($this->REGISTER_MESSAGE_EXPLAIN == null) {
            $this->REGISTER_MESSAGE_EXPLAIN = "As an customer, you can avail of free access to Internet by registering here.<br>After filling the form you will have access to a short period in order to confirm your registration";
        }
        if ($this->TERMS_CONDITIONS == null) {
            $this->TERMS_CONDITIONS = @file_get_contents(dirname(__FILE__) . "/databases/wifi-terms.txt");
        }
        if ($this->TERMS_EXPLAIN == null) {
            $this->TERMS_EXPLAIN = "To signup you are required to read our \"TERMS and CONDITIONS\".<br>Once you have read these terms and conditions please click \"ACCEPT\" acknowledging you understand and accept these terms and conditions.";
        }
        if ($this->RegisterTitle == null) {
            $this->RegisterTitle = "Register";
        }
        if ($this->RegisterButton == null) {
            $this->RegisterButton = "{register}";
        }


        if ($this->LoginTitle == null) {
            $this->LoginTitle = "Login to your account";
        }
        if ($this->LostPasswordLink == null) {
            $this->LostPasswordLink = "Lost password";
        }
        if ($this->ConnectionButton == null) {
            $this->ConnectionButton = "Connection";
        }
        if ($this->SMS_SMTP_SUBJECT == null) {
            $this->SMS_SMTP_SUBJECT = "sms-%TIME%-1:Account1:pass1:+33612345678:%MOBILE%";
        }
        if ($this->SMS_SMTP_BODY == null) {
            $this->SMS_SMTP_BODY = "message=Your proxy unlock code is %CODE%";
        }
        if ($this->SMS_INTRO == null) {
            $this->SMS_INTRO = "In order to access to Internet, give your cell number in this form.<br>You will receive a confirmation code by SMS";
        }
        if ($this->SMS_BUTTON == null) {
            $this->SMS_BUTTON = "Send me the code";
        }
        if ($this->SMS_CODE_ERROR == null) {
            $this->SMS_CODE_ERROR = "Wrong confirmation code entered, please retry";
        }
        if ($this->LabelEmail == null) {
            $this->LabelEmail = "eMail";
        }
        if ($this->AcceptButton == null) {
            $this->AcceptButton = "Accept";
        }
        if ($this->LabelUsername == null) {
            $this->LabelUsername = "Username";
        }
        if ($this->LabelPassword == null) {
            $this->LabelPassword = "Password";
        }
        if ($this->DomainAccount == null) {
            $this->DomainAccount = "Domain account";
        }
        if ($this->SubmitButton == null) {
            $this->SubmitButton = "Submit";
        }
        if ($this->LabelConfirm == null) {
            $this->LabelConfirm = "Confirm";
        }
        if ($this->CancelButton == null) {
            $this->CancelButton = "Cancel";
        }

        if ($this->PasswordMismatch == null) {
            $this->PasswordMismatch = "Password Mismatch!";
            $SockTemplate->SET_INFO("PasswordMismatch", $this->PasswordMismatch);
        }
        if($this->SessionExpired ==null){
            $this->SessionExpired = "Your session is expired!";
            $SockTemplate->SET_INFO("SessionExpired",  $this->SessionExpired );

        }

        if ($this->ErrorThisAccountExists == null) {
            $this->ErrorThisAccountExists = "This account already exists";
            $SockTemplate->SET_INFO("ErrorThisAccountExists", $this->ErrorThisAccountExists);
        }
        if ($this->ErrorInvalidMail == null) {
            $this->ErrorInvalidMail = "Invalid eMail address";
            $SockTemplate->SET_INFO("ErrorInvalidMail", $this->ErrorInvalidMail);
        }
        if ($this->MobileLabel == null) {
            $this->MobileLabel = "Mobile";
            $SockTemplate->SET_INFO("MobileLabel", $this->MobileLabel);
        }

        if ($this->ALL_METHODS_LABELS == null) {
            $this->ALL_METHODS_LABELS = "LABEL->Select the connection type\nNONE->Choose a method\nSMS->Register via SMS\nVOUCHER->Use a Voucher\nREGISTER->Create an Account\nLOGIN->Use your credentials\nRECOVER->Recover your password";
            $SockTemplate->SET_INFO("ALL_METHODS_LABELS", $this->ALL_METHODS_LABELS);
        }

        if ($this->REGISTER_MESSAGE_TIMEOUT == null) {
            $this->REGISTER_MESSAGE_TIMEOUT = "You took too long to confirm your registration, please enter your eMail address again to receive the confirmation message again.";
        }

        if ($this->ERROR_STYLE == null) {
            $this->ERROR_STYLE = "color: #a94442;background-color: #f2dede;border-color: #ebccd1;padding: 15px;margin-bottom: 10px;margin-bottom: 20px;border: 1px solid transparent;
  border-top-color: transparent;border-right-color: transparent;border-bottom-color: transparent;
  border-left-color: transparent;border-radius: 4px;-webkit-box-sizing: border-box;  -moz-box-sizing: border-box;box-sizing: border-box;-webkit-text-size-adjust: 100%;  -webkit-tap-highlight-color";
        }


        if ($this->ArticaSplashHotSpotRedirectText == null) {
            $this->ArticaSplashHotSpotRedirectText = "Please wait, redirecting to";
        }
        if ($this->RECOVER_MESSAGE == null) {
            $this->RECOVER_MESSAGE = "Hi, in order to recover your password on the HotSpot system,\nclick on the link below";
        }


        if ($this->FieldsStyle == null) {
            $this->FieldsStyle = "width:80%;background-color: #FFFFFF;\nborder: 1px solid #CCCCCC;\nbox-shadow: 0 1px 1px rgba(0, 0, 0, 0.075) inset;\ntransition: border 0.2s linear 0s, box-shadow 0.2s linear 0s;\npadding:3px;\ncolor:#4C535C;";
        }

        if ($this->LegendsStyle == null) {
            $this->LegendsStyle = "color: #4C535C;\nfont-weight: normal;\ntext-align: right;\ntext-transform: capitalize;";
        }

        if ($this->FooterText == null) {
            $this->FooterText = "<p style='width:80%;background-color: #FFFFFF;\nborder: 1px solid #CCCCCC;\nbox-shadow: 0 1px 1px rgba(0, 0, 0, 0.075) inset;\ntransition: border 0.2s linear 0s, box-shadow 0.2s linear 0s;\npadding:3px;\ncolor:#4C535C;'>You using an HotSpot system using a Community License<br>\nFor more informations, see <a href=\"http://artica-proxy.com\">Artica Proxy</a></p>";
        }

        if ($this->MainTitle == null) {
            $this->MainTitle = "HotSpot System";
        }
        if ($this->SubWelcome == null) {
            $this->SubWelcome = "Welcome to your company Internet access.";
        }
        if($this->SuccessWelcome==null){
            $this->SuccessWelcome="Thank you, you are now identified.<br>You can now access the Internet";
        }

        if ($this->DivLeftColor == null) {
            $this->DivLeftColor = "#FFFFFF";
        }
        if($this->DivLeftBGColor==null){
            $this->DivLeftBGColor= "#000000";
        }

        if ($this->DivRigthColor == null) {
            $this->DivRigthColor = "#474a4d";
        }
        if ($this->DivRigthBGColor == null) {
            $this->DivRigthBGColor = "#FFFFFF";
        }
        if ($this->DivRigthButtonColor == null) {
            $this->DivRigthButtonColor = "#006e96";
        }
        if ($this->DivRigthButtonTextColor == null) {
            $this->DivRigthButtonTextColor = "#FFFFFF";
        }
        if ($this->DivRigthButtonHoverColor == null) {
            $this->DivRigthButtonHoverColor = "#5cce86";
        }
        if ($this->DivBackgroundImage == null) {
            $this->DivBackgroundImage = "/img/bg_moon-1500x1000.jpg";
        }
        $fullpath = "/usr/share/artica-postfix/$this->DivBackgroundImage";
        $fullpath = str_replace("//", "/", $fullpath);
        if (!is_file($fullpath)) {
            $this->DivBackgroundImage = "/img/bg_moon-1500x1000.jpg";
        }
        //GET WIFI4EU VALS
        $WIFI4EUTEXTH1 = trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("WIFI4EUTEXTH1"));
        $WIFI4EUTEXTH2 = trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("WIFI4EUTEXTH2"));
        $WIFI4EUTEXTBTN = trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("WIFI4EUTEXTBTN"));
        $WIFI4EUTEXTH1COLOR = trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("WIFI4EUTEXTH1COLOR"));
        $WIFI4EUTEXTH2COLOR = trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("WIFI4EUTEXTH2COLOR"));
        $WIFI4EUTEXTBTNCOLOR = trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("WIFI4EUTEXTBTNCOLOR"));
        $WIFI4EUBTNCOLOR = trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("WIFI4EUBTNCOLOR"));
        $HotSpotWIFI4EU_ENABLE = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HotSpotWIFI4EU_ENABLE"));
        $HotSpotWIFI4EU_UUID = trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HotSpotWIFI4EU_UUID"));
        $HotSpotWIFI4EU_LANG = trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HotSpotWIFI4EU_LANG"));
        $HotSpotWIFI4EU_DEBUG = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HotSpotWIFI4EU_DEBUG"));
        $WIFI4UEENABLETERMS = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("WIFI4UEENABLETERMS"));
        $WIFI4UETERMSTEXT = trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("WIFI4UETERMSTEXT"));
        $WIFI4UEENABLEPRIVACY = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("WIFI4UEENABLEPRIVACY"));
        $WIFI4UEPRIVACYTEXT = trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("WIFI4UEPRIVACYTEXT"));
        $WIFI4EULINKCOLOR = trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("WIFI4EULINKCOLOR"));
        $WIFI4EUTEXTH1SIZE = trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("WIFI4EUTEXTH1SIZE"));
        $WIFI4EUTEXTH2SIZE = trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("WIFI4EUTEXTH2SIZE"));
        $WIFI4EUTEXTBODYSIZE = trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("WIFI4EUTEXTBODYSIZE"));
        $WIFI4EUTEXTBUTTONSIZE = trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("WIFI4EUTEXTBUTTONSIZE"));
        $WIFI4UETERMSCONTENT = trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("WIFI4UETERMSCONTENT"));
        $WIFI4UEPRIVACYCONTENT = trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("WIFI4UEPRIVACYCONTENT"));
        $WIFI4EUTEXTBODYCOLOR=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("WIFI4EUTEXTBODYCOLOR"));
        $WIFI4UEERRORTEXT=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("WIFI4UEERRORTEXT"));
        //WIFI4EU FAIOLVER VALS
        if (empty($WIFI4EUTEXTBODYCOLOR)){
            $WIFI4EUTEXTBODYCOLOR="#000000";
        }
        if (empty($WIFI4EUTEXTBTN)) {
            $WIFI4EUTEXTBTN = "Access Now";
        }
        if (empty($WIFI4EUTEXTH1COLOR)) {
            $WIFI4EUTEXTH1COLOR = "#cccccc";
        }
        if (empty($WIFI4EUTEXTH2COLOR)) {
            $WIFI4EUTEXTH2COLOR = "#696969";
        }
        if (empty($WIFI4EUTEXTBTNCOLOR)) {
            $WIFI4EUTEXTBTNCOLOR = "#ffffff";
        }
        if (empty($WIFI4EUBTNCOLOR)) {
            $WIFI4EUBTNCOLOR = "#164094";
        }
        if (empty($HotSpotWIFI4EU_LANG)) {
            $HotSpotWIFI4EU_LANG = "en";
        }
        if (empty($WIFI4EULINKCOLOR)) {
            $WIFI4EULINKCOLOR = "#38761d";
        }
        if (empty($WIFI4EUTEXTH1SIZE)) {
            $WIFI4EUTEXTH1SIZE = "20";
        }
        if (empty($WIFI4EUTEXTH2SIZE)) {
            $WIFI4EUTEXTH2SIZE = "18";
        }
        if (empty($WIFI4EUTEXTBODYSIZE)) {
            $WIFI4EUTEXTBODYSIZE = "12";
        }
        if (empty($WIFI4EUTEXTBUTTONSIZE)) {
            $WIFI4EUTEXTBUTTONSIZE = "24";
        }
        if (empty($WIFI4UETERMSTEXT)) {
            $WIFI4UETERMSTEXT = "I agree with the <a href='#' id='terms'>Terms & Conditions</a>";
        }
        if (empty($WIFI4UEPRIVACYTEXT)) {
            $WIFI4UEPRIVACYTEXT = "I agree with the <a href='#' id='privacy'>Privacy Policy</a>";
        }
        if(empty($WIFI4UEERRORTEXT)){
            $WIFI4UEERRORTEXT="Seams that this ip is blocked in our system";
        }
        //WIFI4EU SET DEFAULT VALS
        $this->HotSpotWIFI4EU_ENABLE = $HotSpotWIFI4EU_ENABLE;
        $this->HotSpotWIFI4EU_UUID = $HotSpotWIFI4EU_UUID;
        $this->HotSpotWIFI4EU_LANG = $HotSpotWIFI4EU_LANG;
        $this->HotSpotWIFI4EU_DEBUG = $HotSpotWIFI4EU_DEBUG;
        $this->WIFI4EUImage = "<img id=\"wifi4eubanner\"  class=\"identity-image\">";
        $this->WIFI4EUTEXTH1 = $WIFI4EUTEXTH1;
        $this->WIFI4EUTEXTH2 = $WIFI4EUTEXTH2;
        $this->WIFI4EUTEXTBTN = $WIFI4EUTEXTBTN;
        $this->WIFI4EUTEXTH1COLOR = $WIFI4EUTEXTH1COLOR;
        $this->WIFI4EUTEXTH2COLOR = $WIFI4EUTEXTH2COLOR;
        $this->WIFI4EUTEXTBTNCOLOR = $WIFI4EUTEXTBTNCOLOR;
        $this->WIFI4EUBTNCOLOR = $WIFI4EUBTNCOLOR;
        $this->WIFI4UEENABLETERMS = $WIFI4UEENABLETERMS;
        $this->WIFI4UETERMSTEXT = $WIFI4UETERMSTEXT;
        $this->WIFI4UEENABLEPRIVACY = $WIFI4UEENABLEPRIVACY;
        $this->WIFI4UEPRIVACYTEXT = $WIFI4UEPRIVACYTEXT;
        $this->WIFI4EULINKCOLOR = $WIFI4EULINKCOLOR;
        $this->WIFI4EUTEXTH1SIZE = $WIFI4EUTEXTH1SIZE;
        $this->WIFI4EUTEXTH2SIZE = $WIFI4EUTEXTH2SIZE;
        $this->WIFI4EUTEXTBODYSIZE = $WIFI4EUTEXTBODYSIZE;
        $this->WIFI4EUTEXTBUTTONSIZE = $WIFI4EUTEXTBUTTONSIZE;
        $this->WIFI4UETERMSCONTENT = $WIFI4UETERMSCONTENT;
        $this->WIFI4UEPRIVACYCONTENT = $WIFI4UEPRIVACYCONTENT;
        $this->WIFI4EUTEXTBODYCOLOR=$WIFI4EUTEXTBODYCOLOR;
        $this->WIFI4UEERRORTEXT=$WIFI4UEERRORTEXT;


    }

    public function char($text){
        $tpl=new template_admin();
        $text = htmlentities($text, ENT_NOQUOTES, "UTF-8");
        $text = htmlspecialchars_decode($text);
        return $tpl->utf8_decode($text);
    }

    private function utf8_decode($string):string{
        if(is_null($string)){
            return "";
        }
        if(PHP_MAJOR_VERSION>7) {
            return $string;
        }
        $tpl=new template_admin();
        return $tpl->utf8_decode($string);

    }

    private function SavePic($fileSource)
    {
        echo "[$fileSource]..: SavePic()\n";
        if (trim($fileSource) == null) {
            return;
        }
        $template_path = "/home/squid/hotspot/$this->ruleid/files";
        @mkdir($template_path, 0755, true);

        if (is_file("$template_path/$fileSource")) {
            echo "$template_path/$fileSource..: Already exists..\n";
            return;
        }

        $q = new lib_sqlite("/home/artica/SQLITE/proxy.db");
        $ligne = $q->mysqli_fetch_array("SELECT * FROM templates_files WHERE filename='$fileSource'");

        $contentfile = $ligne["contentfile"];
        $contenttype = $ligne["contenttype"];
        $contentsize = $ligne["contentsize"];
        $content = base64_decode($contentfile);

        if ($contenttype == "text/css") {
            if (preg_match_all("#\[file=(.*?)\]#s", $content, $re)) {
                foreach ($re[1] as $num=>$filename){
                    echo "[$filename]..: SavePic()\n";
                    $this->SavePic($filename);
                    $content = str_replace($re[0][$num], "/$template_path/" . urlencode($filename), $content);
                }
            }

        }

        @file_put_contents("$template_path/$fileSource.type", $contenttype);
        @file_put_contents("$template_path/$fileSource", $content);
        echo "$template_path/$fileSource..: [SAVED]..\n";
    }


    public function enterprise_login_form(): array{
        $tpl = new templates_manager(99999, $this->GetOpts());
        $tpl->formHiddens = $this->formHiddens;
        $tpl->BuildUrlForceRegister = $this->BuildUrlForceRegister;
        return $tpl->enterprise_login_form();
    }

    private function GetOpts(): array{
        if ($this->LoginTitle == null) { $this->LoginTitle = "Login to your account"; }
        $options["MainExplain"] = $this->MainExplain;
        $options["LoginTitle"] = $this->LoginTitle;
        $options["MainTitle"] = $this->MainTitle;
        $options["SubmitButton"] = $this->SubmitButton;
        $options["SubWelcome"] = $this->SubWelcome;
        $options["DivLeftColor"] = $this->DivLeftColor;
        $options["DivLeftBGColor"] = $this->DivLeftBGColor;
        $options["DivRigthColor"] = $this->DivRigthColor;
        $options["DivRigthBGColor"] = $this->DivRigthBGColor;
        $options["DivRigthButtonColor"] = $this->DivRigthButtonColor;
        $options["DivRigthButtonTextColor"] = $this->DivRigthButtonTextColor;
        $options["DivRigthButtonHoverColor"] = $this->DivRigthButtonHoverColor;
        $options["DivBackgroundImage"] = $this->DivBackgroundImage;
        $options["ErrorMessage"] = $this->ErrorMessage;
        $options["RedirectPage"] = $this->RedirectPage;
        return $options;
    }

    private function GetOptsWifi4EU(): array
    {

        $options["WIFI4EUImage"] = $this->WIFI4EUImage;
        $options["WIFI4EUTEXTH1"] = $this->WIFI4EUTEXTH1;
        $options["WIFI4EUTEXTH2"] = $this->WIFI4EUTEXTH2;
        $options["WIFI4EUTEXTBTN"] = $this->WIFI4EUTEXTBTN;
        $options["WIFI4EUTEXTH1COLOR"] = $this->WIFI4EUTEXTH1COLOR;
        $options["WIFI4EUTEXTH2COLOR"] = $this->WIFI4EUTEXTH2COLOR;
        $options["WIFI4EUTEXTBTNCOLOR"] = $this->WIFI4EUTEXTBTNCOLOR;
        $options["WIFI4EUBTNCOLOR"] = $this->WIFI4EUBTNCOLOR;
        $options["HotSpotWIFI4EU_ENABLE"] = $this->HotSpotWIFI4EU_ENABLE;
        $options["HotSpotWIFI4EU_UUID"] = $this->HotSpotWIFI4EU_UUID;
        $options["HotSpotWIFI4EU_LANG"] = $this->HotSpotWIFI4EU_LANG;
        $options["HotSpotWIFI4EU_DEBUG"] = $this->HotSpotWIFI4EU_DEBUG;
        $options["WIFI4UEENABLETERMS"] = $this->WIFI4UEENABLETERMS;
        $options["WIFI4UETERMSTEXT"] = $this->WIFI4UETERMSTEXT;
        $options["WIFI4UEENABLEPRIVACY"] = $this->WIFI4UEENABLEPRIVACY;
        $options["WIFI4UEPRIVACYTEXT"] = $this->WIFI4UEPRIVACYTEXT;
        $options["WIFI4EULINKCOLOR"] = $this->WIFI4EULINKCOLOR;
        $options["WIFI4EUTEXTH1SIZE"] = $this->WIFI4EUTEXTH1SIZE . 'px';
        $options["WIFI4EUTEXTH2SIZE"] = $this->WIFI4EUTEXTH2SIZE . 'px';
        $options["WIFI4EUTEXTBODYSIZE"] = $this->WIFI4EUTEXTBODYSIZE . 'px';
        $options["WIFI4EUTEXTBUTTONSIZE"] = $this->WIFI4EUTEXTBUTTONSIZE . 'px';
        $options["WIFI4UETERMSCONTENT"] = $this->WIFI4UETERMSCONTENT;
        $options["WIFI4UEPRIVACYCONTENT"] = $this->WIFI4UEPRIVACYCONTENT;
        $options["WIFI4EUTEXTBODYCOLOR"]=$this->WIFI4EUTEXTBODYCOLOR;
        $options["RedirectPage"] = $this->RedirectPage;
        $options['WIFI4UEERRORTEXT']=$this->WIFI4UEERRORTEXT;

        return $options;
    }

    function build($content)
    {
        $RedirectPage = null;


        if ($this->RedirectPage <> null) {
            $RedirectPage = "\n<META http-equiv=\"refresh\" content=\"3; URL=$this->RedirectPage\">";
        }
        $jquery = "$RedirectPage\n<!-- Template ID = $this->TemplateID -->\n$this->headerAdd\n<script type=\"text/javascript\" language=\"javascript\" src=\"/hotspot.php?local-js=jquery-1.8.3.js\"></script>\n
		<script type=\"text/javascript\" language=\"javascript\" src=\"/hotspot.php?local-js=jquery-ui-1.8.22.custom.min.js\"></script>";
        $css = "<link href=\"/hotspot.php?css=yes&ruleid=$this->ruleid&template-id=$this->TemplateID\" rel=\"stylesheet\"  type=\"text/css\"/>";


        $tpl = new templates_manager($this->TemplateID, $this->GetOpts());

        $template = $tpl->headContent . "\n" . $tpl->BodyContent;

        if ($this->TemplateID == 99999) {

            if ($this->RedirectPage <> null) {
                $t = time();
                $f[] = "<script>";
                $f[] = "function FinalRedirect$t() {";
                $f[] = "\twindow.setTimeout(function () {";
                $f[] = "\t\tlocation.href = \"$this->RedirectPage\";";
                $f[] = "\t}, 1000);";
                $f[] = "\t});";
                $f[] = "FinalRedirect$t()";
                $f[] = "</script>";
                $template = str_replace("<!-- JSEND -->", @implode("\n", $f), $template);
                $template = str_replace("<!-- HEAD_START -->", "<META http-equiv=\"refresh\" content=\"1; URL=$this->RedirectPage\">", $template);

            }
            if($content==null){return $template;}
            $template = str_replace("%DYNAMIC_CONTENT%", utf8_encode($content), $template);
            return $template;

        }
        if ($this->TemplateID == 999998) {
            $tpl = new templates_manager($this->TemplateID, $this->GetOptsWifi4EU());
            $template = $tpl->headContent . "\n" . $tpl->BodyContent;
            if ($this->RedirectPage <> null) {
                return $this->RedirectPage;
                  $t = time();
//                $f[] = "<script>";
//                $f[] = "function FinalRedirect$t() {";
//                $f[] = "\twindow.setTimeout(function () {";
//                $f[] = "\t\tlocation.href = \"$this->RedirectPage\";";
//                $f[] = "\t}, 1000);";
//                $f[] = "\t});";
//                $f[] = "FinalRedirect$t()";
//                $f[] = "</script>";
//                $template = str_replace("<!-- JSEND -->", @implode("\n", $f), $template);
//                $template = str_replace("<!-- HEAD_START -->", "<META http-equiv=\"refresh\" content=\"1; URL=$this->RedirectPage\">", $template);
            }
            if($content==null){return $template;}
            $template = str_replace("%DYNAMIC_CONTENT%", utf8_encode($content), $template);
            return $template;
        }


        if (preg_match_all("#\[file=(.*?)\]#s", $template, $re)) {
            foreach ($re[1] as $num => $filename) {
                echo "Template: Found PIC.... $filename\n";
                $this->SavePic($filename);
                $template = str_replace($re[0][$num], "/hotspot/$this->ruleid/files/" . urlencode($filename), $template);
            }

        }
        if (preg_match_all("#\[file=(.*?)\]#s", $this->FooterText, $re)) {
            foreach ($re[1] as $num => $filename) {
                $this->SavePic($filename);
                $this->FooterText = str_replace($re[0][$num], "/hotspot/$this->ruleid/files/" . urlencode($filename), $this->FooterText);
            }

        }

        $f[] = $content;
        $footer = "<center class='footer' style='width:100%'>" . $this->char($this->FooterText) . "</center></div>";
        if ($this->RedirectPage <> null) {
            $f[] = "<script>";
            $f[] = "$(document).ready(function () {";
            $f[] = "\twindow.setTimeout(function () {";
            $f[] = "\t\tlocation.href = \"$this->RedirectPage\";";
            $f[] = "\t}, 3000);";
            $f[] = "\t});";
            $f[] = "</script>";
        }

        $f[] = "</body>";
        $f[] = "</html>";
        $final_content = @implode("\n", $f);
        $template = str_replace("%DYNAMIC_CONTENT%", utf8_encode($final_content), $template);
        $template = str_replace("%FOOTER%", $footer, $template);
        $template = str_replace("%CSS%", utf8_encode($css), $template);
        $template = str_replace("%JQUERY%", utf8_encode($jquery), $template);
        return $template;
    }

    private function replace_accents($data)
    {
        return htmlentities(utf8_encode($data), 0, "UTF-8");
    }


    function css()
    {
        $template_path = "/home/squid/hotspot/$this->ruleid/files";
        $tpl = new templates_manager($this->TemplateID);

        if (preg_match_all("#\[file=(.*?)\]#s", $tpl->CssContent, $re)) {
            foreach ($re[1] as $num=>$filename){
                echo "CSS: Found PIC.... $filename\n";
                $this->SavePic($filename);
                $tpl->CssContent = str_replace($re[0][$num], "/hotspot/$this->ruleid/files/" . urlencode($filename), $tpl->CssContent);
            }

        }

        return $tpl->CssContent;
    }


}