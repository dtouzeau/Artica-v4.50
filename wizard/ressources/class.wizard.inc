<?php
include_once("/usr/share/artica-postfix/ressources/class.template-admin.inc");
include_once("/usr/share/artica-postfix/ressources/class.identity.inc");

class artica_wizard{
    public $title;
    private $StepNumberApply=1;
    private $html_fields=array();
    private $form_js=array();
    public $allow_contact=0;
    public $company_name="";
    public $savedsettings=array();
    public $netbiosname="";
    public $domainname;
    public $DNS1;
    public $DNS2;
    public $administrator="";
    public $organization="";
    public $countryName="";
    public $stateOrProvinceName="";
    public $localityName="";
    public $organizationUnit="";
    public $Prenom;
    public $snapshot_uploaded=0;
    public $Nom;
    public $raison_sociale="";
    public $administratorpass="";
    public $mail="";
    public $artica_method=0;
    public $DetectedLanguage="";
    private $form_after="";
    public $timezone_def="";
    private $previous_js="";
    public $Telephone="";
    public $eMail="";

    function __construct($title=null,$step=1){
        $this->title=$title;
        $this->StepNumberApply=$step;
        $this->savedsettings=unserialize(base64_decode($GLOBALS["CLASS_SOCKETS"]->GET_INFO("TempWizard")));
        if(!is_array($this->savedsettings)){$this->savedsettings=array();}
        return $this->ParseSettings();
    }

    public function SavedSettings($POST=array()):bool{

        foreach ($POST as $key=>$val){
           $this->savedsettings[$key]=$val;

        }

        $Data=base64_encode(serialize($this->savedsettings));
        $GLOBALS["CLASS_SOCKETS"]->SET_INFO("TempWizard",$Data);
        $GLOBALS["CLASS_SOCKETS"]->SET_INFO("WizardNotify",$Data);
        $GLOBALS["CLASS_SOCKETS"]->SET_INFO("WizardNotify2",$Data);
        $GLOBALS["CLASS_SOCKETS"]->REST_API("/reset/cache");
        return true;
    }

    public function Build():string{

        $f[]=$this->heads();
        $f[]=$this->subheader();
        $f[]=$this->Body();
        $f[]=$this->Footer();
        return @implode("\n",$f);
    }
    public function snapshotuploaded($filename):bool{
        $this->savedsettings["snapshot_uploaded"]=1;
        $this->savedsettings["snapshot_fname"]=$filename;
        $GLOBALS["CLASS_SOCKETS"]->SET_INFO("TempWizard",base64_encode(serialize($this->savedsettings)));
        $GLOBALS["CLASS_SOCKETS"]->REST_API("/reset/cache");
        return true;
    }
    function SnapshotDelete():bool{
        $this->savedsettings["snapshot_uploaded"]=0;
        unset($this->savedsettings["snapshot_fname"]);
        $this->savedsettings["artica_method"]=0;
        $GLOBALS["CLASS_SOCKETS"]->SET_INFO("TempWizard",base64_encode(serialize($this->savedsettings)));
        $GLOBALS["CLASS_SOCKETS"]->REST_API("/reset/cache");
        return true;
    }

    private function ParseSettings():bool{
        include_once(ARTICA_ROOT."/ressources/class.langages.inc");
        $langAutodetect=new articaLang();

        if(!isset($_COOKIE["artica-language"])) {
            $this->DetectedLanguage = $langAutodetect->get_languages();

        }else{
            $this->DetectedLanguage=$_COOKIE["artica-language"];
        }



        if(count($this->savedsettings)<2){
            VERBOSE("savedsettings < 3 Add default time zone");
            $this->timezone_def=getLocalTimezone();
            return false;
        }

        if(!isset($this->savedsettings["snapshot_uploaded"])){
            $this->savedsettings["snapshot_uploaded"]=0;
        }
        $this->snapshot_uploaded=$this->savedsettings["snapshot_uploaded"];

        if(isset($this->savedsettings["lang2"])){
            $this->DetectedLanguage=$this->savedsettings["lang2"];
        }
        $GLOBALS["FIXED_LANGUAGE"] = $this->DetectedLanguage;

        if(isset($this->savedsettings["netbiosname"])) {
            $this->netbiosname = $this->savedsettings["netbiosname"];
        }
        if(isset($this->savedsettings["domain"])) {
            $this->domainname = $this->savedsettings["domain"];
        }
        if(isset($this->savedsettings["DNS1"])) {
            $this->DNS1 = $this->savedsettings["DNS1"];
        }
        if(isset($this->savedsettings["artica_method"])) {
            $this->artica_method = $this->savedsettings["artica_method"];
        }

        if(isset($this->savedsettings["DNS2"])) {
            $this->DNS2 = $this->savedsettings["DNS2"];
        }
       $this->GetHostname();
        if(strlen($this->DNS1)<3){
            list($this->DNS1,$this->DNS2)=$this->GetNamesServers();
        }
        if(isset($this->savedsettings["administratorpass"])) {
            $this->administratorpass = $this->savedsettings["administratorpass"];
        }
        if(isset($this->savedsettings["administrator"])) {
            $this->administrator = $this->savedsettings["administrator"];
        }
        if(isset($this->savedsettings["mail"])) {
            $this->mail = $this->savedsettings["mail"];
        }
        if(isset($this->savedsettings["organization"])) {
            $this->organization = $this->savedsettings["organization"];
        }
        if(isset($this->savedsettings["raison_sociale"])) {
            $this->raison_sociale = $this->savedsettings["raison_sociale"];
        }
        if(isset($this->savedsettings["Prenom"])) {
            $this->Prenom = $this->savedsettings["Prenom"];
        }
        if(isset($this->savedsettings["allow_contact"])) {
            $this->allow_contact = $this->savedsettings["allow_contact"];
        }
        if(isset($this->savedsettings["Telephone"])) {
            $this->Telephone = $this->savedsettings["Telephone"];
        }

        if(isset($this->savedsettings["Nom"])) {
            $this->Nom = $this->savedsettings["Nom"];
        }
        if(isset($this->savedsettings["eMail"])) {
            $this->eMail = $this->savedsettings["eMail"];
        }
        if( $this->organization==null){
            $this->organization="My Company";
        }
        if(isset($this->savedsettings["organizationUnit"])) {
            $this->organizationUnit=$this->savedsettings["organizationUnit"];
        }
        if(isset($this->savedsettings["countryName"])) {
            $this->countryName=$this->savedsettings["countryName"];
        }
        if(isset($this->savedsettings["stateOrProvinceName"])) {
            $this->stateOrProvinceName=$this->savedsettings["stateOrProvinceName"];
        }
        if(isset($this->savedsettings["localityName"])) {
            $this->localityName=$this->savedsettings["localityName"];
        }

        if( $this->organizationUnit==null){
            $this->organizationUnit="IT Dept.";
        }
        if( $this->stateOrProvinceName==null){
            $this->stateOrProvinceName="New York";
        }
        if( $this->countryName==null){
            $this->countryName="US";
        }
        if( $this->localityName==null){
            $this->localityName="Brooklyn";
        }
        if( $this->mail==null){
            $this->mail="root@localdomain.tld";
        }
        if( $this->eMail==null){
            $this->eMail=$this->mail;
        }

        if( $this->administrator==null){
            $this->administrator="Manager";
        }
        if( $this->administratorpass==null){
            $this->administratorpass="secret";
        }
        if( $this->raison_sociale==null){
           $this->raison_sociale=$this->organization;
        }else{
            $this->company_name=$this->raison_sociale;
            $this->organization=$this->raison_sociale;
        }
        if($GLOBALS["VERBOSE"]){
            foreach ($this->savedsettings as $key=>$val){
                VERBOSE("$key: $val",__LINE__);
            }
        }
        if(isset($this->savedsettings["timezones"])){
            if($this->savedsettings["timezones"]<>null){
                $this->timezone_def=$this->savedsettings["timezones"];
            }
        }
        return true;
    }

    private function GetNamesServers():array{
        $arrayNameServers=array();
        $resolv_conf=explode("\n",@file_get_contents("/etc/resolv.conf"));
        foreach ($resolv_conf as $lines){
            $lines=trim($lines);
            if($lines==null){continue;}
            if(preg_match("#127\.0\.0\.1#",$lines)){continue;}
            if(preg_match("#127\.0\.0\.55#",$lines)){continue;}
            if(preg_match("#^nameserver\s+(.+)#",$lines,$re)){
                $g=trim($re[1]);
                if($g=="127.0.0.1"){continue;}
                $arrayNameServers[]=$g;
            }
        }

        if(count($arrayNameServers)==0){
            if(is_file("/etc/resolvconf/resolv.conf.d/original")) {
                $resolv_conf = file("/etc/resolvconf/resolv.conf.d/original");
                foreach ($resolv_conf as $lines) {
                    $lines = trim($lines);
                    if (preg_match("#127\.0\.0\.1#", $lines)) {
                        continue;
                    }
                    if (preg_match("#^nameserver\s+(.+)#", $lines, $re)) {
                        $g = trim($re[1]);
                        if ($g == "127.0.0.1") {
                            continue;
                        }
                        if ($g == "127.0.0.55") {
                            continue;
                        }
                        $arrayNameServers[] = $g;
                    }
                }
            }

        }
        
        if(count($arrayNameServers)<1){
            $arrayNameServers[]="8.8.8.8";
            $arrayNameServers[]="1.1.1.1";
        }
        
        return $arrayNameServers;
    }

    public function GetHostname():string{
        if(strlen($this->netbiosname)>3){return $this->netbiosname;}
        $hostname=base64_decode($GLOBALS["CLASS_SOCKETS"]->getFrameWork("network.php?fqdn=yes"));
        if($hostname==null){$users=new usersMenus();$hostname=$users->fqdn;}
        if(strpos($hostname, '.')>0){
            $Thostname=explode(".", $hostname);
            $this->netbiosname=$Thostname[0];
            unset($Thostname[0]);
            $this->domainname=@implode(".", $Thostname);
        }else{
            $this->netbiosname=$hostname;
        }

        if(preg_match("#[A-Za-z]+\s+[A-Za-z]+#", $this->netbiosname)){$this->netbiosname="";}
        if(strlen($this->domainname)<3){$this->domainname="domain.tld";}
        return $this->netbiosname;

    }



    private  function heads():string{
        $tpl=new template_admin();
        $this->title=$tpl->_ENGINE_parse_body($this->title);
        $f[]="<!DOCTYPE html>";
        $f[]="<html lang=\"en\">";
        $f[]="<head>";
        $f[]="  <meta charset=\"UTF-8\">";
        $f[]="  <meta name=\"viewport\" content=\"width=device-width, initial-scale=1.0\">";
        $f[]="  <meta http-equiv=\"X-UA-Compatible\" content=\"ie=edge\">";
        $f[]="  <link rel=\"icon\" href=\"/ressources/templates/default/favicon.ico\" type= \"image/x-icon\" />";
        $f[]="  <link rel=\"shortcut icon\" href=\"/ressources/templates/default/favicon.ico\" type=\"image/x-icon\" />";
        $f[]="  <title>$this->title</title>";
        $f[]="  <link href=\"/wizard/assets/css/fonts.css\" rel=\"stylesheet\">";
        $f[]="  <link rel=\"stylesheet\" href=\"/wizard/assets/css/materialdesignicons.min.css\">";
        $f[]="  <link rel=\"stylesheet\" href=\"/wizard/assets/css/bootstrap.min.css\">";
        $f[]="  <link rel=\"stylesheet\" href=\"/wizard/assets/css/bd-wizard.css\">";
        $f[]="  <script src=\"/angular/js/jquery/jquery-3.6.0.min.js\"></script>";
        $f[]="  <script src=\"/angular/js/file-upload/jquery.ui.widget.js\"></script>";
        $f[]="  <script src=\"/angular/js/file-upload/jquery.fileupload.js\"></script>";
        $fa[] = "mouse.js";
        $fa[] = "default.js";
        $fa[] = "XHRConnection.js";

        foreach ($fa as $script){
            $f[]="  <script src=\"/$script\"></script>";
        }
        $f[]="</head>";

        return @implode("\n",$f);
    }

    private function subheader():string{
        $blank="target=\"_blank\" rel=\"noopener noreferrer\"";
        $f[]="<body>";
        $f[]="  <header>";
        $f[]="    <nav class=\"navbar navbar-expand-sm navbar-light bg-white\">";
        $f[]="      <div class=\"container\">";
        $f[]="        <a class=\"navbar-brand\" href=\"#\"><img src=\"/wizard/assets/images/LogoHeaderArtica.png\" alt=\"logo\"></a>";
        $f[]="        <button class=\"navbar-toggler d-lg-none\" type=\"button\" data-toggle=\"collapse\" data-target=\"#collapsibleNavId\"";
        $f[]="          aria-controls=\"collapsibleNavId\" aria-expanded=\"false\" aria-label=\"Toggle navigation\">";
        $f[]="          <span class=\"navbar-toggler-icon\"></span>";
        $f[]="        </button>";
        $f[]="        <div class=\"collapse navbar-collapse\" id=\"collapsibleNavId\">";
        $f[]="          <ul class=\"navbar-nav ml-auto mt-2 mt-lg-0\">";
        $f[]="            <li class=\"nav-item\">";
        $f[]="              <a class=\"nav-link\" href=\"https://www.articatech.com\" $blank>Artica Tech</a>";
        $f[]="            </li>";
        $f[]="            <li class=\"nav-item\">";
        $f[]="              <a class=\"nav-link\" href=\"https://wiki.articatech.com\" $blank>Wiki</a>";
        $f[]="            </li>";
        $f[]="            <li class=\"nav-item\">";
        $f[]="              <a class=\"nav-link\" href=\"https://www.articatech.com/support.php\" $blank>Contact</a>";
        $f[]="            </li>";
        $f[]="          </ul>";
        $f[]="        </div>";
        $f[]="      </div>";
        $f[]="    </nav>";
        $f[]="  </header>";

        return @implode("\n",$f);

    }

    public function Step($stepnum=1):string{
        $f[]="<ul role=\"tablist\">";
        $Max=5;
        for($i=1;$i<$Max+1;$i++) {
            $ariaDis="true";
            $araSel="false";
            $class=null;
            $class2="disabled";
            if($i==$stepnum){
                $ariaDis="false";
                $araSel="true";
                $class2="current";
            }
            if($i==1){$class="first";}
            if($i==$Max){$class="last";}

            $f[] = "<li role=\"tab\" class=\"$class $class2\" aria-disabled=\"$ariaDis\" aria-selected=\"$araSel\">";
            $f[]="    <a id=\"wizard-t-$i\" href=\"#wizard-h-$i\" aria-controls=\"wizard-p-$i\">";
            $f[]="            <span class=\"number\">$i</span></a>";
            $f[]="        </li>";
        }
        $f[]="        </ul>";
        return @implode("\n",$f);

    }
    
    public function StepNumber($Number){
        $this->StepNumberApply=$Number;
    }

    public function BuildSection($title,$explain,$html):string{

        $f[]="<section id=\"wizard-p-0\" role=\"tabpanel\"
                style='margin-top:10px' 
                aria-labelledby=\"wizard-h-0\" class=\"body current\" aria-hidden=\"false\">";
        $f[]="<h2 class=\"section-heading\">$title</h2>";
        $f[]="<p>$explain</p>";
        if(is_array($html)){
            $f[]=@implode("\n",$html);
        }else{
            $f[]=$html;
        }

        $tpl=new template_admin();
        return $tpl->_ENGINE_parse_body($f);


    }

    public function multi_fieldtext($array):bool{
        $tpl=new template_admin();
        $f[]="<div class='row'>";


        foreach ($array as $name=>$settings){
            $f[]="<div class='col'>";
            $label=$settings["LABEL"];
            $value=$settings["VALUE"];
            $VALID=$settings["VALID"];
            $f[]="<div class='form-floating mb-3' style='margin-top:10px'>";
            $f[]="<input type='text' name='$name' id='$name' class=\"form-control form-control-lg\"  value='$value'>";
            $f[]=" <label for='$name' style='padding-top:6px' id='label-$name'>$label</label>";
            $f[]="</div>";
            $f[]="</div>";

            $this->field_js($name,$VALID);

        }
        $f[]="</div>";
        $this->html_fields[]=@implode("\n",$f);
return true;
    }

    private function js_validate($name,$validate):bool{
        $black=".style.color='rgb(31, 35, 38)'";
        $scolor=".style.color='#d30038'";
        $bblack=".style.borderColor ='#ececec'";
        $bscolor=".style.borderColor='#d30038'";

        $label_color_red[] = "\t\tif( document.getElementById('label-$name') ){";
        $label_color_red[] = "\t\t\tdocument.getElementById('label-$name')$scolor;";
        $label_color_red[] = "\t\t}";

        $this->form_js[] = "\tif( ! document.getElementById('$name') ){alert('$name not found');}";
        $this->form_js[]="\tdocument.getElementById('$name')$black;";
        $this->form_js[]="\tdocument.getElementById('$name')$bblack;";
        $this->form_js[] = "\tif( document.getElementById('label-$name') ){";
        $this->form_js[] = "\t\tdocument.getElementById('label-$name')$black;";
        $this->form_js[]="\t}";
        $this->form_js[]="\tTestString=document.getElementById('$name').value;";

        if($validate=="text"){
            $this->form_js[]="\tif(TestString.length<2){";
            $this->form_js[] = "console.log('$name: no data!');";
            $this->form_js[]="\t\tdocument.getElementById('$name')$scolor;";
            $this->form_js[]="\t\tdocument.getElementById('$name')$bscolor;";
            $this->form_js[] = @implode("\n",$label_color_red);
            $this->form_js[]="\t\treturn;";
            $this->form_js[]="\t}";
            return true;
        }
        if($validate=="ipaddr") {
            $this->form_js[] = "console.log('ValidateIPAddress: '+TestString);";
            $this->form_js[] = "\tif(!ValidateIPAddress(TestString) ){";
            $this->form_js[]="\t\tdocument.getElementById('$name')$scolor;";
            $this->form_js[]="\t\tdocument.getElementById('$name')$bscolor;";
            $this->form_js[] = @implode("\n",$label_color_red);
            $this->form_js[] = "\t\treturn;";
            $this->form_js[] = "\t}";
            return true;
        }
        if($validate=="mail") {
            $this->form_js[] = "\tif(!ValidateEmail(TestString)){";
            $this->form_js[]="\t\tdocument.getElementById('$name')$scolor;";
            $this->form_js[]="\t\tdocument.getElementById('$name')$bscolor;";
            $this->form_js[] = @implode("\n",$label_color_red);
            $this->form_js[] = "\t\treturn;";
            $this->form_js[] = "\t}";
            return true;
        }
        return true;

    }

    private function field_js($FieldID,$validate):bool{

        $this->form_js[] = "";
        $this->form_js[] = "console.log('$FieldID start');";

        $this->form_js[] = "\ttry {";
        if(strlen($validate)<>null){
            $this->js_validate($FieldID,$validate);
        }
        $this->form_js[]="\t\tFieldString=document.getElementById('$FieldID').value;";
        $this->form_js[]="\t\tXHR.appendData('$FieldID',  encodeURIComponent(FieldString));";
        $this->form_js[]="\t}catch (error) {";
        $this->form_js[]="\t\talert(error +' On $FieldID');";
        $this->form_js[]="\t}";
        $this->form_js[] = "console.log('$FieldID end');";
        return true;
    }

    public function field_info_button($label,$value,$btname,$js):bool{
        $id=md5("$label,$value,$js");
        $f[]="<div class='form-floating mb-3' style='margin-top:10px;width:663px'>";
        $f[]="<div class=\"input-group-append\">";
        $f[]="<button style='min-height:58px;float:right' class=\"btn btn-danger\" id='bt_$id' type=\"button\">$btname</button>";
        $f[]="</div>";
        $f[]="<input type='text' name='$id' id='$id' class=\"form-control form-control-lg\"  value='$value' aria-describedby=\"basic-addon2\" disabled>";

        $f[]=" <label for='$id' style='padding-top:6px' id='label-$id'>$label</label>";
        $f[]="</div>";
        $f[]="<script>$( '#bt_$id' ).click(function() { $js });</script>";
        $this->html_fields[]=@implode("\n",$f);
        return true;
    }

    public function field_info_text($label,$value):bool{
        $id=md5("$label,$value");
        $f[]="<div class='form-floating mb-3' style='margin-top:10px;width:663px'>";
        $f[]="<input type='text' name='$id' id='$id' class=\"form-control form-control-lg\"  value='$value' aria-describedby=\"basic-addon2\" disabled>";
        $f[]=" <label for='$id' style='padding-top:6px' id='label-$id'>$label</label>";
        $f[]="</div>";
        $this->html_fields[]=@implode("\n",$f);
        return true;
    }

    public function field_text($label,$name,$value,$validate=""):bool{
        $tpl=new template_admin();
        $label=$tpl->_ENGINE_parse_body($label);
        $f[]="<div class='form-floating mb-3' style='margin-top:10px'>";
        $f[]="<input type='text' name='$name' id='$name' class=\"form-control form-control-lg\"  value='$value'>";
        $f[]=" <label for='$name' style='padding-top:6px' id='label-$name'>$label</label>";
        $f[]="</div>";

        $this->html_fields[]=@implode("\n",$f);
        $this->field_js($name,$validate);
        return true;
    }
    public function field_check($label,$name,$value):bool{
        $checked=null;
        if(intval($value)==1){$checked="checked";}
        $f[]="<div class=\"form-check form-switch\">";
        $f[]="<input class=\"form-check-input\" type=\"checkbox\" id=\"$name\" $checked>";
        $f[]="<label class=\"form-check-label\" for=\"$name\">$label</label>";
        $f[]="</div>";
        $this->form_js[]="\tif( document.getElementById('$name').checked ){";
        $this->form_js[]="\tXHR.appendData('$name',  '1');";
        $this->form_js[]="}else{";
        $this->form_js[]="\tXHR.appendData('$name',  '0');";
        $this->form_js[]="}";
        $this->html_fields[]=@implode("\n",$f);
        return true;

    }

    public function field_password($label,$name,$value):bool{
        $tpl=new template_admin();
        $label=$tpl->_ENGINE_parse_body($label);
        $f[]="<div class='form-floating mb-3' style='margin-top:10px'>";
        $f[]="<input type='password' name='$name' id='$name' class=\"form-control form-control-lg\"  value='$value'>";
        $f[]=" <label for='$name' style='padding-top:6px' id='label-$name'>$label</label>";
        $f[]="</div>";

        $this->html_fields[]=@implode("\n",$f);
        $this->form_js[]=$this->field_js($name,"text");
        return true;
    }

    public function ReturnMainAjax($js):string{
        return "LoadAjaxSilent('main-step','$js');";
    }

    public function form_after($js):bool{
         $this->form_after=$this->ReturnMainAjax($js);
         return true;
    }

    public function field_array_hash($hash,$label,$name,$value):bool{

        $tpl=new template_admin();
        $t=time();

        $OnChange=null;
        $f[]="<div class='form-floating' style='margin-top:10px'>";
        $f[]="<!-- Sended value = $value -->";
        $f[]="<select name=\"$name\" class=\"form-select form-select-lg\" aria-label=\"Floating label select example\" id='$name-$t' $OnChange> ";
        $f[]="\t<option value=\"\" disabled selected>$label</option>";
        $selected=false;
        if($value==null){$selected=true;}

        foreach ($hash as $val=>$key){
            $val=trim($val);
            if(strpos($key, "}")>0){$key=$tpl->javascript_parse_text($key);}
            $lenght=strlen($key);
            if($lenght>50){$key=substr($key, 0,47)."...";}

            if(!$selected){
                if(strtolower($val)==strtolower($value)){
                    $selected=true;
                    $f[]="\t<option value=\"$val\" selected>$key</option>";
                    continue;
                }
            }
            $f[]="\t<option value=\"$val\">$key</option>";
        }

        $f[]="</select>";
        $f[]=" <label for='$name'>$label</label>";
        $f[]="</div>";

        $this->html_fields[]=@implode("\n",$f);
        $this->form_js[]="\tvar F$name=document.getElementById('$name-$t').value;";
        $this->form_js[]="\tXHR.appendData('$name',  encodeURIComponent(F$name));";
        return true;

    }

    public function build_form():string{
        $tpl=new template_admin();
        $previous=$tpl->_ENGINE_parse_body("{previous}");
        $next=$tpl->_ENGINE_parse_body("{next}");
        $f[]=@implode("\n",$this->html_fields);
        $f[]="<div class=\"actions clearfix\" style='float: right;margin-top: 20px'>";
        $f[]="   <ul role='menu' aria-label='Pagination'>";
        if(strlen($this->previous_js)>3) {
            $f[] = "   <li class=\"\" aria-disabled=\"false\">";
            $f[] = "   <a href=\"javascript:blur()\" 
                       OnClick=\"$this->previous_js\"
                        role=\"menuitem\">$previous</a>";
            $f[] = "   </li>";
        }
        $f[]="<li aria-hidden=\"false\" aria-disabled=\"false\">";
        $f[]="   <a href=\"javascript:blur()\" role=\"menuitem\" OnClick=\"PostForm();\">$next</a>";
        $f[]="</li>";
       // $f[]="<li style="display: none;" aria-hidden="true"><a href="#finish" role="menuitem">Finish</a></li></ul>
        $f[]="</div>";

        $f[]="<script>";
        if($this->StepNumberApply>0){
            $f[]="LoadAjaxSilent('vertical-steps','wiz.wizard.php?step=$this->StepNumberApply');";
        }
        $f[]="var x_PostForm= function (obj) {";
        $f[]="\tvar tempvalue=obj.responseText;";
        if(strlen($this->form_after)>3) {
            $f[] = "$this->form_after;";
        }
        $f[]="}";
        $f[]="function PostForm(){";
        $f[] = "\tvar FieldString='';";
        $f[]="console.log('Form posted');";
        $f[]="\tvar XHR = new XHRConnection();";
        $f[]="console.log('XHRConnection posted');";
        $f[]=@implode("\n", $this->form_js);
        $f[]="console.log('Form END');";
        $f[]="\tXHR.sendAndLoad('wiz.wizard.php', 'POST',x_PostForm);";
        $f[]="}";
        $f[]="</script>";
        return @implode("\n",$f);

    }
    
    public function form_previous($js=null):bool{
        $this->previous_js="LoadAjaxSilent('main-step','$js');";
        return true;
    }

    public function button_upload($label="{upload_a_file}",$name,$currentpage=null,$addedparams=null,$field_name=null):bool{
        $tpl=new template_admin();
        $ffname="file-uploaded";
        if($name<>null){$ffname=$name;}
        $btid=md5(time().$label.$currentpage);
        $btname=$tpl->_ENGINE_parse_body($label);
        $Uploading=$tpl->javascript_parse_text("{uploading}");

        $this->html_fields[]="<label for=\"fileupload$btid\" id='label$btid' class=\"form-label\">$label</label>";
        $this->html_fields[]="<input class=\"form-control form-control-lg\" id=\"fileupload$btid\" type=\"file\" name=\"files\"/>";

        $this->form_js[]="\tif( document.getElementById('fileupload$btid') ){";
        $this->form_js[]="\tXHR.appendData('$name',  document.getElementById('fileupload$btid').value);";
        $this->form_js[]="}";

        $this->html_fields[]="<script>";
        $this->html_fields[]="$('#fileupload$btid').fileupload({";
        $this->html_fields[]="url: '/wizard/wiz.upload.php',";
        $this->html_fields[]="dataType: 'json',";
        $this->html_fields[]="add: function (e, data) {";
        $this->html_fields[]="document.getElementById('label$btid').innerHTML='$Uploading....';";
        $this->html_fields[]="data.submit()";
        $this->html_fields[]="},";
        $this->html_fields[]="progressall: function (e, data) {";
        $this->html_fields[]="var progress = parseInt(data.loaded / data.total * 100, 10);";
        $this->html_fields[]="document.getElementById('label$btid').innerHTML='$Uploading '+progress+'%';";
        $this->html_fields[]="},";
        $this->html_fields[]="done: function (e, data) {";
        $this->html_fields[]="document.getElementById('label$btid').innerHTML='$btname';";
        $this->html_fields[]="Loadjs('$currentpage?$ffname='+data.result.files[0].name+'$addedparams');";
        $this->html_fields[]="}";
        $this->html_fields[]="});";
        $this->html_fields[]="</script>";
        return true;

    }

    public function field_sections($field,$config):bool{
        $tpl=new template_admin();

        $f[]="<div class=\"purpose-radios-wrapper\" style='width:1150px'>";
        $this->form_js[]="\tvar selected_field_$field=0;";
        foreach ($config as $value=>$SET) {
            $LABEL=$tpl->_ENGINE_parse_body($SET["LABEL"]);
            $EXPLAIN=$tpl->_ENGINE_parse_body($SET["EXPLAIN"]);
            $PIC=$SET["PIC"];
            $FieldID=md5("$field$value$LABEL");

            $f[] = "<div class=\"purpose-radio\">";
            $f[] = "<input type=\"radio\" name=\"purpose\" id=\"$FieldID\" class=\"purpose-radio-input\" value=\"$value\">";
            $f[] = "    <label for=\"$FieldID\" class=\"purpose-radio-label\">";
            $f[] = "        <span class=\"label-icon\">";
            $f[] = "            <img src=\"assets/images/$PIC-grey-30.png\" alt=\"$LABEL\" class=\"label-icon-default\">";
            $f[] = "            <img src=\"assets/images/$PIC-30.png\" alt=\"$LABEL\" class=\"label-icon-active\">";
            $f[] = "         </span>";
            $f[] = "        <span class=\"label-text\"><strong>$LABEL</strong></span>";
            $f[] = "        <p class=\"label-text\" style='margin-top:5px;font-size:12px;min-height:75px;margin-left:20px;margin-right:20px;margin-bottom:20px'>$EXPLAIN</p>";
            $f[] = "     </label>";
            $f[] = "</div>";


            $this->form_js[]="\tif( document.getElementById('$FieldID').checked ){";
            $this->form_js[]="\tselected_field_$field=1;";
            $this->form_js[]="\tXHR.appendData('$field',  '$value');";
            $this->form_js[]="}";

        }
        $this->form_js[]="\tif( selected_field_$field==0){";
        $this->form_js[]="\talert('Please Choose something');";
        $this->form_js[]="\treturn;";
        $this->form_js[]="\t}";

        $f[]="</div>";
        $this->html_fields[]=@implode("\n",$f);
        return true;
    }

    private function Body():string{
        $clear="wizard clearfix vertical";
        $f[]="<main class=\"d-flex align-items-center\">";
        $f[]="    <div class=\"container\">";
        $f[]="        <div id=\"wizard\" role=\"application\" class=\"$clear\">";
        $f[]="            <div class=\"steps clearfix\" id='vertical-steps'></div>";
        $f[]="            <div class=\"content clearfix\" id='main-step'></div>";
        $f[]="        </div>";
        $f[]="   </div>";
        $f[]="</main>";
        $f[]="<script>";
        $f[]="LoadAjaxSilent('vertical-steps','wiz.wizard.php?step=1');";
        $f[]="LoadAjaxSilent('main-step','wiz.wizard.php?body-step=1');";
        $f[]="</script>";
        return @implode("\n",$f);

    }

    private function Footer():string{
        $f[]="  <script src=\"/wizard/assets/js/popper.min.js\"></script>";
        $f[]="  <script src=\"/wizard/assets/js/bootstrap.min.js\"></script>";
        $f[]="</body>";
        $f[]="</html>";
        return @implode("\n",$f);
    }

    public function SaveIdentity():bool{
        $sidentity=new sidentity();
        $fullhostname=$this->netbiosname.".".$this->domainname;
        $sidentity->SET("myhostname",$fullhostname);
        $GLOBALS["CLASS_SOCKETS"]->SET_INFO("myhostname", $fullhostname);
        $MainArray = unserialize(base64_decode($GLOBALS["CLASS_SOCKETS"]->GET_INFO("resolvConf")));
        if ($this->DNS1 <> null) {$MainArray["DNS1"] = $this->DNS1;}
        if ($this->DNS2  <> null) {$MainArray["DNS2"] = $this->DNS2;}
        $GLOBALS["CLASS_SOCKETS"]->SET_INFO("resolvConf",base64_encode(serialize($MainArray)));

        $GLOBALS["CLASS_SOCKETS"]->SET_INFO("timezones",$this->timezone_def);
        $this->savedsettings["smtp_domainname"]=$this->domainname;

        foreach ($this->savedsettings as $key=>$val){
            $sidentity->SET($key,$val);
        }
        $sidentity->Save();
        $timezoneenc=urlencode(base64_encode(trim($this->timezone_def)));
        $GLOBALS["CLASS_SOCKETS"]->getFrameWork("system.php?zoneinfo-set=$timezoneenc");
        $GLOBALS["CLASS_SOCKETS"]->SET_INFO("EnableArpDaemon", 0);
        $GLOBALS["CLASS_SOCKETS"]->SET_INFO("EnablePHPFPM",0);
        $GLOBALS["CLASS_SOCKETS"]->SET_INFO("EnableFreeWeb",0);
        $GLOBALS["CLASS_SOCKETS"]->SET_INFO("SlapdThreads", 2);
        $GLOBALS["CLASS_SOCKETS"]->SET_INFO("NewLicServer", 1);
        $GLOBALS["CLASS_SOCKETS"]->SET_INFO('Migration',1);
        $GLOBALS["CLASS_SOCKETS"]->REST_API("/reset/cache");
        return true;
    }

    public function timezone():array{

        $timezone[]="Africa/Abidjan";                 //,0x000000 },
        $timezone[]="Africa/Accra";                   //,0x000055 },
        $timezone[]="Africa/Addis_Ababa";             //,0x0000FD },
        $timezone[]="Africa/Algiers";                 //,0x000153 },
        $timezone[]="Africa/Asmara";                  //,0x00027E },
        $timezone[]="Africa/Asmera";                  //,0x0002D4 },
        $timezone[]="Africa/Bamako";                  //,0x00032A },
        $timezone[]="Africa/Bangui";                  //,0x000395 },
        $timezone[]="Africa/Banjul";                  //,0x0003EA },
        $timezone[]="Africa/Bissau";                  //,0x000461 },
        $timezone[]="Africa/Blantyre";                //,0x0004C7 },
        $timezone[]="Africa/Brazzaville";             //,0x00051C },
        $timezone[]="Africa/Bujumbura";               //,0x000571 },
        $timezone[]="Africa/Cairo";                   //,0x0005B5 },
        $timezone[]="Africa/Casablanca";              //,0x00097C },
        $timezone[]="Africa/Ceuta";                   //,0x000A58 },
        $timezone[]="Africa/Conakry";                 //,0x000D5F },
        $timezone[]="Africa/Dakar";                   //,0x000DCA },
        $timezone[]="Africa/Dar_es_Salaam";           //,0x000E30 },
        $timezone[]="Africa/Djibouti";                //,0x000E9D },
        $timezone[]="Africa/Douala";                  //,0x000EF2 },
        $timezone[]="Africa/El_Aaiun";                //,0x000F47 },
        $timezone[]="Africa/Freetown";                //,0x000FAD },
        $timezone[]="Africa/Gaborone";                //,0x0010BC },
        $timezone[]="Africa/Harare";                  //,0x001117 },
        $timezone[]="Africa/Johannesburg";            //,0x00116C },
        $timezone[]="Africa/Kampala";                 //,0x0011DA },
        $timezone[]="Africa/Khartoum";                //,0x001259 },
        $timezone[]="Africa/Kigali";                  //,0x00136C },
        $timezone[]="Africa/Kinshasa";                //,0x0013C1 },
        $timezone[]="Africa/Lagos";                   //,0x00141C },
        $timezone[]="Africa/Libreville";              //,0x001471 },
        $timezone[]="Africa/Lome";                    //,0x0014C6 },
        $timezone[]="Africa/Luanda";                  //,0x00150A },
        $timezone[]="Africa/Lubumbashi";              //,0x00155F },
        $timezone[]="Africa/Lusaka";                  //,0x0015BA },
        $timezone[]="Africa/Malabo";                  //,0x00160F },
        $timezone[]="Africa/Maputo";                  //,0x001675 },
        $timezone[]="Africa/Maseru";                  //,0x0016CA },
        $timezone[]="Africa/Mbabane";                 //,0x001732 },
        $timezone[]="Africa/Mogadishu";               //,0x001788 },
        $timezone[]="Africa/Monrovia";                //,0x0017E3 },
        $timezone[]="Africa/Nairobi";                 //,0x001849 },
        $timezone[]="Africa/Ndjamena";                //,0x0018C8 },
        $timezone[]="Africa/Niamey";                  //,0x001934 },
        $timezone[]="Africa/Nouakchott";              //,0x0019A7 },
        $timezone[]="Africa/Ouagadougou";             //,0x001A12 },
        $timezone[]="Africa/Porto-Novo";              //,0x001A67 },
        $timezone[]="Africa/Sao_Tome";                //,0x001ACD },
        $timezone[]="Africa/Timbuktu";                //,0x001B22 },
        $timezone[]="Africa/Tripoli";                 //,0x001B8D },
        $timezone[]="Africa/Tunis";                   //,0x001C87 },
        $timezone[]="Africa/Windhoek";                //,0x001EB1 },
        $timezone[]="America/Adak";                   //,0x0020F8 },
        $timezone[]="America/Anchorage";              //,0x00246E },
        $timezone[]="America/Anguilla";               //,0x0027E2 },
        $timezone[]="America/Antigua";                //,0x002837 },
        $timezone[]="America/Araguaina";              //,0x00289D },
        $timezone[]="America/Argentina/Buenos_Aires"; //,0x0029F8 },
        $timezone[]="America/Argentina/Catamarca";    //,0x002BA6 },
        $timezone[]="America/Argentina/ComodRivadavia";  //,0x002D67 },
        $timezone[]="America/Argentina/Cordoba";      //,0x002F0D },
        $timezone[]="America/Argentina/Jujuy";        //,0x0030E2 },
        $timezone[]="America/Argentina/La_Rioja";     //,0x003296 },
        $timezone[]="America/Argentina/Mendoza";      //,0x00344E },
        $timezone[]="America/Argentina/Rio_Gallegos"; //,0x00360E },
        $timezone[]="America/Argentina/Salta";        //,0x0037C3 },
        $timezone[]="America/Argentina/San_Juan";     //,0x00396F },
        $timezone[]="America/Argentina/San_Luis";     //,0x003B27 },
        $timezone[]="America/Argentina/Tucuman";      //,0x003E05 },
        $timezone[]="America/Argentina/Ushuaia";      //,0x003FC1 },
        $timezone[]="America/Aruba";                  //,0x00417C },
        $timezone[]="America/Asuncion";               //,0x0041E2 },
        $timezone[]="America/Atikokan";               //,0x0044C7 },
        $timezone[]="America/Atka";                   //,0x00459D },
        $timezone[]="America/Bahia";                  //,0x004903 },
        $timezone[]="America/Barbados";               //,0x004A8C },
        $timezone[]="America/Belem";                  //,0x004B26 },
        $timezone[]="America/Belize";                 //,0x004C21 },
        $timezone[]="America/Blanc-Sablon";           //,0x004D9D },
        $timezone[]="America/Boa_Vista";              //,0x004E51 },
        $timezone[]="America/Bogota";                 //,0x004F5A },
        $timezone[]="America/Boise";                  //,0x004FC6 },
        $timezone[]="America/Buenos_Aires";           //,0x00535D },
        $timezone[]="America/Cambridge_Bay";          //,0x0054F6 },
        $timezone[]="America/Campo_Grande";           //,0x00581E },
        $timezone[]="America/Cancun";                 //,0x005B0D },
        $timezone[]="America/Caracas";                //,0x005D4F },
        $timezone[]="America/Catamarca";              //,0x005DB6 },
        $timezone[]="America/Cayenne";                //,0x005F5C },
        $timezone[]="America/Cayman";                 //,0x005FBE },
        $timezone[]="America/Chicago";                //,0x006013 },
        $timezone[]="America/Chihuahua";              //,0x00652A },
        $timezone[]="America/Coral_Harbour";          //,0x006779 },
        $timezone[]="America/Cordoba";                //,0x00680B },
        $timezone[]="America/Costa_Rica";             //,0x0069B1 },
        $timezone[]="America/Cuiaba";                 //,0x006A3B },
        $timezone[]="America/Curacao";                //,0x006D19 },
        $timezone[]="America/Danmarkshavn";           //,0x006D7F },
        $timezone[]="America/Dawson";                 //,0x006EC3 },
        $timezone[]="America/Dawson_Creek";           //,0x0071E0 },
        $timezone[]="America/Denver";                 //,0x0073BA },
        $timezone[]="America/Detroit";                //,0x007740 },
        $timezone[]="America/Dominica";               //,0x007A9F },
        $timezone[]="America/Edmonton";               //,0x007AF4 },
        $timezone[]="America/Eirunepe";               //,0x007EAC },
        $timezone[]="America/El_Salvador";            //,0x007FBF },
        $timezone[]="America/Ensenada";               //,0x008034 },
        $timezone[]="America/Fort_Wayne";             //,0x0084DB },
        $timezone[]="America/Fortaleza";              //,0x00839D },
        $timezone[]="America/Glace_Bay";              //,0x008745 },
        $timezone[]="America/Godthab";                //,0x008ABC },
        $timezone[]="America/Goose_Bay";              //,0x008D80 },
        $timezone[]="America/Grand_Turk";             //,0x00923D },
        $timezone[]="America/Grenada";                //,0x0094EC },
        $timezone[]="America/Guadeloupe";             //,0x009541 },
        $timezone[]="America/Guatemala";              //,0x009596 },
        $timezone[]="America/Guayaquil";              //,0x00961F },
        $timezone[]="America/Guyana";                 //,0x00967C },
        $timezone[]="America/Halifax";                //,0x0096FD },
        $timezone[]="America/Havana";                 //,0x009C13 },
        $timezone[]="America/Hermosillo";             //,0x009F86 },
        $timezone[]="America/Indiana/Indianapolis";   //,0x00A064 },
        $timezone[]="America/Indiana/Knox";           //,0x00A2F5 },
        $timezone[]="America/Indiana/Marengo";        //,0x00A68C },
        $timezone[]="America/Indiana/Petersburg";     //,0x00A932 },
        $timezone[]="America/Indiana/Tell_City";      //,0x00AE7F },
        $timezone[]="America/Indiana/Vevay";          //,0x00B118 },
        $timezone[]="America/Indiana/Vincennes";      //,0x00B353 },
        $timezone[]="America/Indiana/Winamac";        //,0x00B607 },
        $timezone[]="America/Indianapolis";           //,0x00AC15 },
        $timezone[]="America/Inuvik";                 //,0x00B8C0 },
        $timezone[]="America/Iqaluit";                //,0x00BBB7 },
        $timezone[]="America/Jamaica";                //,0x00BED9 },
        $timezone[]="America/Jujuy";                  //,0x00BF9E },
        $timezone[]="America/Juneau";                 //,0x00C148 },
        $timezone[]="America/Kentucky/Louisville";    //,0x00C4C6 },
        $timezone[]="America/Kentucky/Monticello";    //,0x00C8E4 },
        $timezone[]="America/Knox_IN";                //,0x00CC69 },
        $timezone[]="America/La_Paz";                 //,0x00CFDA },
        $timezone[]="America/Lima";                   //,0x00D041 },
        $timezone[]="America/Los_Angeles";            //,0x00D0E9 },
        $timezone[]="America/Louisville";             //,0x00D4FA },
        $timezone[]="America/Maceio";                 //,0x00D8EF },
        $timezone[]="America/Managua";                //,0x00DA29 },
        $timezone[]="America/Manaus";                 //,0x00DADC },
        $timezone[]="America/Marigot";                //,0x00DBDE },
        $timezone[]="America/Martinique";             //,0x00DC33 },
        $timezone[]="America/Mazatlan";               //,0x00DC9F },
        $timezone[]="America/Mendoza";                //,0x00DF0C },
        $timezone[]="America/Menominee";              //,0x00E0C0 },
        $timezone[]="America/Merida";                 //,0x00E441 },
        $timezone[]="America/Mexico_City";            //,0x00E67C },
        $timezone[]="America/Miquelon";               //,0x00E8F7 },
        $timezone[]="America/Moncton";                //,0x00EB69 },
        $timezone[]="America/Monterrey";              //,0x00F000 },
        $timezone[]="America/Montevideo";             //,0x00F247 },
        $timezone[]="America/Montreal";               //,0x00F559 },
        $timezone[]="America/Montserrat";             //,0x00FA6F },
        $timezone[]="America/Nassau";                 //,0x00FAC4 },
        $timezone[]="America/New_York";               //,0x00FE09 },
        $timezone[]="America/Nipigon";                //,0x010314 },
        $timezone[]="America/Nome";                   //,0x010665 },
        $timezone[]="America/Noronha";                //,0x0109E3 },
        $timezone[]="America/North_Dakota/Center";    //,0x010B13 },
        $timezone[]="America/North_Dakota/New_Salem"; //,0x010EA7 },
        $timezone[]="America/Panama";                 //,0x011250 },
        $timezone[]="America/Pangnirtung";            //,0x0112A5 },
        $timezone[]="America/Paramaribo";             //,0x0115DB },
        $timezone[]="America/Phoenix";                //,0x01166D },
        $timezone[]="America/Port-au-Prince";         //,0x01171B },
        $timezone[]="America/Port_of_Spain";          //,0x011936 },
        $timezone[]="America/Porto_Acre";             //,0x011837 },
        $timezone[]="America/Porto_Velho";            //,0x01198B },
        $timezone[]="America/Puerto_Rico";            //,0x011A81 },
        $timezone[]="America/Rainy_River";            //,0x011AEC },
        $timezone[]="America/Rankin_Inlet";           //,0x011E24 },
        $timezone[]="America/Recife";                 //,0x01210A },
        $timezone[]="America/Regina";                 //,0x012234 },
        $timezone[]="America/Resolute";               //,0x0123F2 },
        $timezone[]="America/Rio_Branco";             //,0x0126EB },
        $timezone[]="America/Rosario";                //,0x0127EE },
        $timezone[]="America/Santarem";               //,0x012994 },
        $timezone[]="America/Santiago";               //,0x012A99 },
        $timezone[]="America/Santo_Domingo";          //,0x012E42 },
        $timezone[]="America/Sao_Paulo";              //,0x012F08 },
        $timezone[]="America/Scoresbysund";           //,0x013217 },
        $timezone[]="America/Shiprock";               //,0x013505 },
        $timezone[]="America/St_Barthelemy";          //,0x013894 },
        $timezone[]="America/St_Johns";               //,0x0138E9 },
        $timezone[]="America/St_Kitts";               //,0x013E3C },
        $timezone[]="America/St_Lucia";               //,0x013E91 },
        $timezone[]="America/St_Thomas";              //,0x013EE6 },
        $timezone[]="America/St_Vincent";             //,0x013F3B },
        $timezone[]="America/Swift_Current";          //,0x013F90 },
        $timezone[]="America/Tegucigalpa";            //,0x0140B1 },
        $timezone[]="America/Thule";                  //,0x014130 },
        $timezone[]="America/Thunder_Bay";            //,0x014377 },
        $timezone[]="America/Tijuana";                //,0x0146C0 },
        $timezone[]="America/Toronto";                //,0x014A35 },
        $timezone[]="America/Tortola";                //,0x014F4C },
        $timezone[]="America/Vancouver";              //,0x014FA1 },
        $timezone[]="America/Virgin";                 //,0x0153DE },
        $timezone[]="America/Whitehorse";             //,0x015433 },
        $timezone[]="America/Winnipeg";               //,0x015750 },
        $timezone[]="America/Yakutat";                //,0x015B90 },
        $timezone[]="America/Yellowknife";            //,0x015EFB },
        $timezone[]="Antarctica/Casey";               //,0x01620B },
        $timezone[]="Antarctica/Davis";               //,0x016291 },
        $timezone[]="Antarctica/DumontDUrville";      //,0x01631B },
        $timezone[]="Antarctica/Mawson";              //,0x0163AD },
        $timezone[]="Antarctica/McMurdo";             //,0x016429 },
        $timezone[]="Antarctica/Palmer";              //,0x01672B },
        $timezone[]="Antarctica/Rothera";             //,0x016A47 },
        $timezone[]="Antarctica/South_Pole";          //,0x016ABD },
        $timezone[]="Antarctica/Syowa";               //,0x016DC5 },
        $timezone[]="Antarctica/Vostok";              //,0x016E33 },
        $timezone[]="Arctic/Longyearbyen";            //,0x016EA8 },
        $timezone[]="Asia/Aden";                      //,0x0171DA },
        $timezone[]="Asia/Almaty";                    //,0x01722F },
        $timezone[]="Asia/Amman";                     //,0x0173AE },
        $timezone[]="Asia/Anadyr";                    //,0x01766E },
        $timezone[]="Asia/Aqtau";                     //,0x01795C },
        $timezone[]="Asia/Aqtobe";                    //,0x017B5B },
        $timezone[]="Asia/Ashgabat";                  //,0x017D13 },
        $timezone[]="Asia/Ashkhabad";                 //,0x017E30 },
        $timezone[]="Asia/Baghdad";                   //,0x017F4D },
        $timezone[]="Asia/Bahrain";                   //,0x0180C2 },
        $timezone[]="Asia/Baku";                      //,0x018128 },
        $timezone[]="Asia/Bangkok";                   //,0x018410 },
        $timezone[]="Asia/Beirut";                    //,0x018465 },
        $timezone[]="Asia/Bishkek";                   //,0x018772 },
        $timezone[]="Asia/Brunei";                    //,0x01891E },
        $timezone[]="Asia/Calcutta";                  //,0x018980 },
        $timezone[]="Asia/Choibalsan";                //,0x0189F9 },
        $timezone[]="Asia/Chongqing";                 //,0x018B72 },
        $timezone[]="Asia/Chungking";                 //,0x018C61 },
        $timezone[]="Asia/Colombo";                   //,0x018D10 },
        $timezone[]="Asia/Dacca";                     //,0x018DAC },
        $timezone[]="Asia/Damascus";                  //,0x018E4D },
        $timezone[]="Asia/Dhaka";                     //,0x01919D },
        $timezone[]="Asia/Dili";                      //,0x01923E },
        $timezone[]="Asia/Dubai";                     //,0x0192C7 },
        $timezone[]="Asia/Dushanbe";                  //,0x01931C },
        $timezone[]="Asia/Gaza";                      //,0x01941F },
        $timezone[]="Asia/Harbin";                    //,0x019768 },
        $timezone[]="Asia/Ho_Chi_Minh";               //,0x01984F },
        $timezone[]="Asia/Hong_Kong";                 //,0x0198C7 },
        $timezone[]="Asia/Hovd";                      //,0x019A93 },
        $timezone[]="Asia/Irkutsk";                   //,0x019C0B },
        $timezone[]="Asia/Istanbul";                  //,0x019EF2 },
        $timezone[]="Asia/Jakarta";                   //,0x01A2DF },
        $timezone[]="Asia/Jayapura";                  //,0x01A389 },
        $timezone[]="Asia/Jerusalem";                 //,0x01A40D },
        $timezone[]="Asia/Kabul";                     //,0x01A73C },
        $timezone[]="Asia/Kamchatka";                 //,0x01A78D },
        $timezone[]="Asia/Karachi";                   //,0x01AA72 },
        $timezone[]="Asia/Kashgar";                   //,0x01AC3F },
        $timezone[]="Asia/Kathmandu";                 //,0x01AD10 },
        $timezone[]="Asia/Katmandu";                  //,0x01AD76 },
        $timezone[]="Asia/Kolkata";                   //,0x01ADDC },
        $timezone[]="Asia/Krasnoyarsk";               //,0x01AE55 },
        $timezone[]="Asia/Kuala_Lumpur";              //,0x01B13E },
        $timezone[]="Asia/Kuching";                   //,0x01B1FB },
        $timezone[]="Asia/Kuwait";                    //,0x01B2E9 },
        $timezone[]="Asia/Macao";                     //,0x01B33E },
        $timezone[]="Asia/Macau";                     //,0x01B479 },
        $timezone[]="Asia/Magadan";                   //,0x01B5B4 },
        $timezone[]="Asia/Makassar";                  //,0x01B897 },
        $timezone[]="Asia/Manila";                    //,0x01B950 },
        $timezone[]="Asia/Muscat";                    //,0x01B9D5 },
        $timezone[]="Asia/Nicosia";                   //,0x01BA2A },
        $timezone[]="Asia/Novokuznetsk";              //,0x01BD12 },
        $timezone[]="Asia/Novosibirsk";               //,0x01C015 },
        $timezone[]="Asia/Omsk";                      //,0x01C309 },
        $timezone[]="Asia/Oral";                      //,0x01C5F1 },
        $timezone[]="Asia/Phnom_Penh";                //,0x01C7C1 },
        $timezone[]="Asia/Pontianak";                 //,0x01C839 },
        $timezone[]="Asia/Pyongyang";                 //,0x01C8FA },
        $timezone[]="Asia/Qatar";                     //,0x01C967 },
        $timezone[]="Asia/Qyzylorda";                 //,0x01C9CD },
        $timezone[]="Asia/Rangoon";                   //,0x01CBA3 },
        $timezone[]="Asia/Riyadh";                    //,0x01CC1B },
        $timezone[]="Asia/Saigon";                    //,0x01CC70 },
        $timezone[]="Asia/Sakhalin";                  //,0x01CCE8 },
        $timezone[]="Asia/Samarkand";                 //,0x01CFE8 },
        $timezone[]="Asia/Seoul";                     //,0x01D11E },
        $timezone[]="Asia/Shanghai";                  //,0x01D1C2 },
        $timezone[]="Asia/Singapore";                 //,0x01D2A2 },
        $timezone[]="Asia/Taipei";                    //,0x01D359 },
        $timezone[]="Asia/Tashkent";                  //,0x01D471 },
        $timezone[]="Asia/Tbilisi";                   //,0x01D5A2 },
        $timezone[]="Asia/Tehran";                    //,0x01D75C },
        $timezone[]="Asia/Tel_Aviv";                  //,0x01D9CA },
        $timezone[]="Asia/Thimbu";                    //,0x01DCF9 },
        $timezone[]="Asia/Thimphu";                   //,0x01DD5F },
        $timezone[]="Asia/Tokyo";                     //,0x01DDC5 },
        $timezone[]="Asia/Ujung_Pandang";             //,0x01DE4E },
        $timezone[]="Asia/Ulaanbaatar";               //,0x01DECA },
        $timezone[]="Asia/Ulan_Bator";                //,0x01E025 },
        $timezone[]="Asia/Urumqi";                    //,0x01E172 },
        $timezone[]="Asia/Vientiane";                 //,0x01E239 },
        $timezone[]="Asia/Vladivostok";               //,0x01E2B1 },
        $timezone[]="Asia/Yakutsk";                   //,0x01E59E },
        $timezone[]="Asia/Yekaterinburg";             //,0x01E884 },
        $timezone[]="Asia/Yerevan";                   //,0x01EB90 },
        $timezone[]="Atlantic/Azores";                //,0x01EE94 },
        $timezone[]="Atlantic/Bermuda";               //,0x01F397 },
        $timezone[]="Atlantic/Canary";                //,0x01F678 },
        $timezone[]="Atlantic/Cape_Verde";            //,0x01F94E },
        $timezone[]="Atlantic/Faeroe";                //,0x01F9C7 },
        $timezone[]="Atlantic/Faroe";                 //,0x01FC6B },
        $timezone[]="Atlantic/Jan_Mayen";             //,0x01FF0F },
        $timezone[]="Atlantic/Madeira";               //,0x020241 },
        $timezone[]="Atlantic/Reykjavik";             //,0x02074A },
        $timezone[]="Atlantic/South_Georgia";         //,0x020903 },
        $timezone[]="Atlantic/St_Helena";             //,0x020C1B },
        $timezone[]="Atlantic/Stanley";               //,0x020947 },
        $timezone[]="Australia/ACT";                  //,0x020C70 },
        $timezone[]="Australia/Adelaide";             //,0x020F8D },
        $timezone[]="Australia/Brisbane";             //,0x0212B9 },
        $timezone[]="Australia/Broken_Hill";          //,0x021380 },
        $timezone[]="Australia/Canberra";             //,0x0216BE },
        $timezone[]="Australia/Currie";               //,0x0219DB },
        $timezone[]="Australia/Darwin";               //,0x021D0E },
        $timezone[]="Australia/Eucla";                //,0x021D94 },
        $timezone[]="Australia/Hobart";               //,0x021E69 },
        $timezone[]="Australia/LHI";                  //,0x0221C7 },
        $timezone[]="Australia/Lindeman";             //,0x022462 },
        $timezone[]="Australia/Lord_Howe";            //,0x022543 },
        $timezone[]="Australia/Melbourne";            //,0x0227EE },
        $timezone[]="Australia/North";                //,0x022B13 },
        $timezone[]="Australia/NSW";                  //,0x022B87 },
        $timezone[]="Australia/Perth";                //,0x022EA4 },
        $timezone[]="Australia/Queensland";           //,0x022F7C },
        $timezone[]="Australia/South";                //,0x023028 },
        $timezone[]="Australia/Sydney";               //,0x023345 },
        $timezone[]="Australia/Tasmania";             //,0x023682 },
        $timezone[]="Australia/Victoria";             //,0x0239C7 },
        $timezone[]="Australia/West";                 //,0x023CE4 },
        $timezone[]="Australia/Yancowinna";           //,0x023D9A },
        $timezone[]="Brazil/Acre";                    //,0x0240BC },
        $timezone[]="Brazil/DeNoronha";               //,0x0241BB },
        $timezone[]="Brazil/East";                    //,0x0242DB },
        $timezone[]="Brazil/West";                    //,0x0245B8 },
        $timezone[]="Canada/Atlantic";                //,0x0246B0 },
        $timezone[]="Canada/Central";                 //,0x024B98 },
        $timezone[]="Canada/East-Saskatchewan";       //,0x0254A2 },
        $timezone[]="Canada/Eastern";                 //,0x024FB2 },
        $timezone[]="Canada/Mountain";                //,0x02562B },
        $timezone[]="Canada/Newfoundland";            //,0x0259A1 },
        $timezone[]="Canada/Pacific";                 //,0x025ECC },
        $timezone[]="Canada/Saskatchewan";            //,0x0262E5 },
        $timezone[]="Canada/Yukon";                   //,0x02646E },
        $timezone[]="CET";                            //,0x026771 },
        $timezone[]="Chile/Continental";              //,0x026A7A },
        $timezone[]="Chile/EasterIsland";             //,0x026E15 },
        $timezone[]="CST6CDT";                        //,0x027157 },
        $timezone[]="Cuba";                           //,0x0274A8 },
        $timezone[]="EET";                            //,0x02781B },
        $timezone[]="Egypt";                          //,0x027ACE },
        $timezone[]="Eire";                           //,0x027E95 },
        $timezone[]="EST";                            //,0x0283A6 },
        $timezone[]="EST5EDT";                        //,0x0283EA },
        $timezone[]="Etc/GMT";                        //,0x02873B },
        $timezone[]="Etc/GMT+0";                      //,0x028807 },
        $timezone[]="Etc/GMT+1";                      //,0x028891 },
        $timezone[]="Etc/GMT+10";                     //,0x02891E },
        $timezone[]="Etc/GMT+11";                     //,0x0289AC },
        $timezone[]="Etc/GMT+12";                     //,0x028A3A },
        $timezone[]="Etc/GMT+2";                      //,0x028B55 },
        $timezone[]="Etc/GMT+3";                      //,0x028BE1 },
        $timezone[]="Etc/GMT+4";                      //,0x028C6D },
        $timezone[]="Etc/GMT+5";                      //,0x028CF9 },
        $timezone[]="Etc/GMT+6";                      //,0x028D85 },
        $timezone[]="Etc/GMT+7";                      //,0x028E11 },
        $timezone[]="Etc/GMT+8";                      //,0x028E9D },
        $timezone[]="Etc/GMT+9";                      //,0x028F29 },
        $timezone[]="Etc/GMT-0";                      //,0x0287C3 },
        $timezone[]="Etc/GMT-1";                      //,0x02884B },
        $timezone[]="Etc/GMT-10";                     //,0x0288D7 },
        $timezone[]="Etc/GMT-11";                     //,0x028965 },
        $timezone[]="Etc/GMT-12";                     //,0x0289F3 },
        $timezone[]="Etc/GMT-13";                     //,0x028A81 },
        $timezone[]="Etc/GMT-14";                     //,0x028AC8 },
        $timezone[]="Etc/GMT-2";                      //,0x028B0F },
        $timezone[]="Etc/GMT-3";                      //,0x028B9B },
        $timezone[]="Etc/GMT-4";                      //,0x028C27 },
        $timezone[]="Etc/GMT-5";                      //,0x028CB3 },
        $timezone[]="Etc/GMT-6";                      //,0x028D3F },
        $timezone[]="Etc/GMT-7";                      //,0x028DCB },
        $timezone[]="Etc/GMT-8";                      //,0x028E57 },
        $timezone[]="Etc/GMT-9";                      //,0x028EE3 },
        $timezone[]="Etc/GMT0";                       //,0x02877F },
        $timezone[]="Etc/Greenwich";                  //,0x028F6F },
        $timezone[]="Etc/UCT";                        //,0x028FB3 },
        $timezone[]="Etc/Universal";                  //,0x028FF7 },
        $timezone[]="Etc/UTC";                        //,0x02903B },
        $timezone[]="Etc/Zulu";                       //,0x02907F },
        $timezone[]="Europe/Amsterdam";               //,0x0290C3 },
        $timezone[]="Europe/Andorra";                 //,0x029501 },
        $timezone[]="Europe/Athens";                  //,0x02977D },
        $timezone[]="Europe/Belfast";                 //,0x029AC0 },
        $timezone[]="Europe/Belgrade";                //,0x029FF7 },
        $timezone[]="Europe/Berlin";                  //,0x02A2C0 },
        $timezone[]="Europe/Bratislava";              //,0x02A616 },
        $timezone[]="Europe/Brussels";                //,0x02A948 },
        $timezone[]="Europe/Bucharest";               //,0x02AD7F },
        $timezone[]="Europe/Budapest";                //,0x02B0A9 },
        $timezone[]="Europe/Chisinau";                //,0x02B41C },
        $timezone[]="Europe/Copenhagen";              //,0x02B7AA },
        $timezone[]="Europe/Dublin";                  //,0x02BAB4 },
        $timezone[]="Europe/Gibraltar";               //,0x02BFC5 },
        $timezone[]="Europe/Guernsey";                //,0x02C41C },
        $timezone[]="Europe/Helsinki";                //,0x02C953 },
        $timezone[]="Europe/Isle_of_Man";             //,0x02CC09 },
        $timezone[]="Europe/Istanbul";                //,0x02D140 },
        $timezone[]="Europe/Jersey";                  //,0x02D52D },
        $timezone[]="Europe/Kaliningrad";             //,0x02DA64 },
        $timezone[]="Europe/Kiev";                    //,0x02DDC7 },
        $timezone[]="Europe/Lisbon";                  //,0x02E0DE },
        $timezone[]="Europe/Ljubljana";               //,0x02E5E2 },
        $timezone[]="Europe/London";                  //,0x02E8AB },
        $timezone[]="Europe/Luxembourg";              //,0x02EDE2 },
        $timezone[]="Europe/Madrid";                  //,0x02F238 },
        $timezone[]="Europe/Malta";                   //,0x02F5FE },
        $timezone[]="Europe/Mariehamn";               //,0x02F9B7 },
        $timezone[]="Europe/Minsk";                   //,0x02FC6D },
        $timezone[]="Europe/Monaco";                  //,0x02FF78 },
        $timezone[]="Europe/Moscow";                  //,0x0303B3 },
        $timezone[]="Europe/Nicosia";                 //,0x030705 },
        $timezone[]="Europe/Oslo";                    //,0x0309ED },
        $timezone[]="Europe/Paris";                   //,0x030D1F },
        $timezone[]="Europe/Podgorica";               //,0x031165 },
        $timezone[]="Europe/Prague";                  //,0x03142E },
        $timezone[]="Europe/Riga";                    //,0x031760 },
        $timezone[]="Europe/Rome";                    //,0x031AA5 },
        $timezone[]="Europe/Samara";                  //,0x031E68 },
        $timezone[]="Europe/San_Marino";              //,0x032194 },
        $timezone[]="Europe/Sarajevo";                //,0x032557 },
        $timezone[]="Europe/Simferopol";              //,0x032820 },
        $timezone[]="Europe/Skopje";                  //,0x032B4B },
        $timezone[]="Europe/Sofia";                   //,0x032E14 },
        $timezone[]="Europe/Stockholm";               //,0x03311C },
        $timezone[]="Europe/Tallinn";                 //,0x0333CB },
        $timezone[]="Europe/Tirane";                  //,0x033705 },
        $timezone[]="Europe/Tiraspol";                //,0x033A0B },
        $timezone[]="Europe/Uzhgorod";                //,0x033D99 },
        $timezone[]="Europe/Vaduz";                   //,0x0340B0 },
        $timezone[]="Europe/Vatican";                 //,0x034343 },
        $timezone[]="Europe/Vienna";                  //,0x034706 },
        $timezone[]="Europe/Vilnius";                 //,0x034A33 },
        $timezone[]="Europe/Volgograd";               //,0x034D72 },
        $timezone[]="Europe/Warsaw";                  //,0x03507B },
        $timezone[]="Europe/Zagreb";                  //,0x03545C },
        $timezone[]="Europe/Zaporozhye";              //,0x035725 },
        $timezone[]="Europe/Zurich";                  //,0x035A66 },
        $timezone[]="Factory";                        //,0x035D15 },
        $timezone[]="GB";                             //,0x035D86 },
        $timezone[]="GB-Eire";                        //,0x0362BD },
        $timezone[]="GMT";                            //,0x0367F4 },
        $timezone[]="GMT+0";                          //,0x0368C0 },
        $timezone[]="GMT-0";                          //,0x03687C },
        $timezone[]="GMT0";                           //,0x036838 },
        $timezone[]="Greenwich";                      //,0x036904 },
        $timezone[]="Hongkong";                       //,0x036948 },
        $timezone[]="HST";                            //,0x036B14 },
        $timezone[]="Iceland";                        //,0x036B58 },
        $timezone[]="Indian/Antananarivo";            //,0x036D11 },
        $timezone[]="Indian/Chagos";                  //,0x036D85 },
        $timezone[]="Indian/Christmas";               //,0x036DE7 },
        $timezone[]="Indian/Cocos";                   //,0x036E2B },
        $timezone[]="Indian/Comoro";                  //,0x036E6F },
        $timezone[]="Indian/Kerguelen";               //,0x036EC4 },
        $timezone[]="Indian/Mahe";                    //,0x036F19 },
        $timezone[]="Indian/Maldives";                //,0x036F6E },
        $timezone[]="Indian/Mauritius";               //,0x036FC3 },
        $timezone[]="Indian/Mayotte";                 //,0x037039 },
        $timezone[]="Indian/Reunion";                 //,0x03708E },
        $timezone[]="Iran";                           //,0x0370E3 },
        $timezone[]="Israel";                         //,0x037351 },
        $timezone[]="Jamaica";                        //,0x037680 },
        $timezone[]="Japan";                          //,0x037745 },
        $timezone[]="Kwajalein";                      //,0x0377CE },
        $timezone[]="Libya";                          //,0x037831 },
        $timezone[]="MET";                            //,0x03792B },
        $timezone[]="Mexico/BajaNorte";               //,0x037C34 },
        $timezone[]="Mexico/BajaSur";                 //,0x037F9D },
        $timezone[]="Mexico/General";                 //,0x0381E2 },
        $timezone[]="MST";                            //,0x038440 },
        $timezone[]="MST7MDT";                        //,0x038484 },
        $timezone[]="Navajo";                         //,0x0387D5 },
        $timezone[]="NZ";                             //,0x038B4E },
        $timezone[]="NZ-CHAT";                        //,0x038ECC },
        $timezone[]="Pacific/Apia";                   //,0x0391B4 },
        $timezone[]="Pacific/Auckland";               //,0x039232 },
        $timezone[]="Pacific/Chatham";                //,0x0395BE },
        $timezone[]="Pacific/Easter";                 //,0x0398B5 },
        $timezone[]="Pacific/Efate";                  //,0x039C13 },
        $timezone[]="Pacific/Enderbury";              //,0x039CD9 },
        $timezone[]="Pacific/Fakaofo";                //,0x039D47 },
        $timezone[]="Pacific/Fiji";                   //,0x039D8B },
        $timezone[]="Pacific/Funafuti";               //,0x039E01 },
        $timezone[]="Pacific/Galapagos";              //,0x039E45 },
        $timezone[]="Pacific/Gambier";                //,0x039EBD },
        $timezone[]="Pacific/Guadalcanal";            //,0x039F22 },
        $timezone[]="Pacific/Guam";                   //,0x039F77 },
        $timezone[]="Pacific/Honolulu";               //,0x039FCD },
        $timezone[]="Pacific/Johnston";               //,0x03A061 },
        $timezone[]="Pacific/Kiritimati";             //,0x03A0B3 },
        $timezone[]="Pacific/Kosrae";                 //,0x03A11E },
        $timezone[]="Pacific/Kwajalein";              //,0x03A17B },
        $timezone[]="Pacific/Majuro";                 //,0x03A1E7 },
        $timezone[]="Pacific/Marquesas";              //,0x03A246 },
        $timezone[]="Pacific/Midway";                 //,0x03A2AD },
        $timezone[]="Pacific/Nauru";                  //,0x03A337 },
        $timezone[]="Pacific/Niue";                   //,0x03A3AF },
        $timezone[]="Pacific/Norfolk";                //,0x03A40D },
        $timezone[]="Pacific/Noumea";                 //,0x03A462 },
        $timezone[]="Pacific/Pago_Pago";              //,0x03A4F2 },
        $timezone[]="Pacific/Palau";                  //,0x03A57B },
        $timezone[]="Pacific/Pitcairn";               //,0x03A5BF },
        $timezone[]="Pacific/Ponape";                 //,0x03A614 },
        $timezone[]="Pacific/Port_Moresby";           //,0x03A669 },
        $timezone[]="Pacific/Rarotonga";              //,0x03A6AD },
        $timezone[]="Pacific/Saipan";                 //,0x03A789 },
        $timezone[]="Pacific/Samoa";                  //,0x03A7EC },
        $timezone[]="Pacific/Tahiti";                 //,0x03A875 },
        $timezone[]="Pacific/Tarawa";                 //,0x03A8DA },
        $timezone[]="Pacific/Tongatapu";              //,0x03A92E },
        $timezone[]="Pacific/Truk";                   //,0x03A9BA },
        $timezone[]="Pacific/Wake";                   //,0x03AA13 },
        $timezone[]="Pacific/Wallis";                 //,0x03AA63 },
        $timezone[]="Pacific/Yap";                    //,0x03AAA7 },
        $timezone[]="Poland";                         //,0x03AAEC },
        $timezone[]="Portugal";                       //,0x03AECD },
        $timezone[]="PRC";                            //,0x03B3C9 },
        $timezone[]="PST8PDT";                        //,0x03B47A },
        $timezone[]="ROC";                            //,0x03B7CB },
        $timezone[]="ROK";                            //,0x03B8E3 },
        $timezone[]="Singapore";                      //,0x03B987 },
        $timezone[]="Turkey";                         //,0x03BA3E },
        $timezone[]="UCT";                            //,0x03BE2B },
        $timezone[]="Universal";                      //,0x03BE6F },
        $timezone[]="US/Alaska";                      //,0x03BEB3 },
        $timezone[]="US/Aleutian";                    //,0x03C21C },
        $timezone[]="US/Arizona";                     //,0x03C582 },
        $timezone[]="US/Central";                     //,0x03C610 },
        $timezone[]="US/East-Indiana";                //,0x03D01A },
        $timezone[]="US/Eastern";                     //,0x03CB1B },
        $timezone[]="US/Hawaii";                      //,0x03D284 },
        $timezone[]="US/Indiana-Starke";              //,0x03D312 },
        $timezone[]="US/Michigan";                    //,0x03D683 },
        $timezone[]="US/Mountain";                    //,0x03D9BA },
        $timezone[]="US/Pacific";                     //,0x03DD33 },
        $timezone[]="US/Pacific-New";                 //,0x03E138 },
        $timezone[]="US/Samoa";                       //,0x03E53D },
        $timezone[]="UTC";                            //,0x03E5C6 },
        $timezone[]="W-SU";                           //,0x03E8BD },
        $timezone[]="WET";                            //,0x03E60A },
        $timezone[]="Zulu";                           //,0x03EBF8 },

        foreach ($timezone as $timezonename){
            $arrayTime[$timezonename]=$timezonename;
        }

        return $arrayTime;

    }

}