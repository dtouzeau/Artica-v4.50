<?php

class nginx_templates{

        public $valid_error_codes=array(403,404,405,496,400,500,502,503,504,901);
        private $nginx_id;
        private $finaldir="/etc/nginx/templates";
        private $ArticaDir="/usr/share/artica-postfix";


        public function __construct($ID=0){
            $this->nginx_id=$ID;

        }

        public function LoadTemplate($code,$nginx_id=0):string{
            if($nginx_id>0){
                $this->nginx_id=$nginx_id;
            }
            $sockngix   = new socksngix($this->nginx_id);
            $key        = "error_page.$code.".$nginx_id;
            $data       = $sockngix->GET_INFO($key);
            $bootstrap_min_css  =   @file_get_contents("$this->ArticaDir/angular/bootstrap.min.css");
            $font_awesome_css   =   @file_get_contents("$this->ArticaDir/angular/font-awesome/css/font-awesome.css");
            $animate_css        =   @file_get_contents("$this->ArticaDir/angular/animate.css");
            $style_css          =   @file_get_contents("$this->ArticaDir/angular/style.css");
            if($data==null){
                $data=@file_get_contents("$this->ArticaDir/$code.html");
                //

            }

            $data=str_replace('<link href="/angular.css.php" rel="stylesheet">',"",$data);

            $data=str_replace('<link href="/angular/bootstrap.min.css" rel="stylesheet">',
                                "<style type='text/css'>$bootstrap_min_css</style>",$data);
            $data=str_replace('<link href="/angular/font-awesome/css/font-awesome.css" rel="stylesheet">',
                                "<style type='text/css'>$font_awesome_css</style>",$data);
            $data=str_replace('<link href="/angular/animate.css" rel="stylesheet">',
                                "<style type='text/css'>$animate_css</style>",$data);
            $data=str_replace('<link href="/angular/style.css" rel="stylesheet">',
                                "<style type='text/css'>$style_css</style>",$data);

            $inlinestyle="<style type=\"text/css\">
                body { color: #676a6c;font-family: 'Helvetica Neue','lato','Trebuchet MS', 'Helvetica', sans-serif; font-size: 14px; line-height: 1.42857143;margin: 0;overflow-x: hidden;}
                 html, body { height: 100%; }
                 * { -webkit-box-sizing: border-box; -moz-box-sizing: border-box; box-sizing: border-box; }
.gray-bg, .bg-muted { background-color: #f3f3f4; }
       .gray-bg { background-color: #f3f3f4; }
        .font-bold { font-weight: 600; }
        .middle-box { max-width: 400px; z-index: 100; margin: 0 auto; padding-top: 40px;}
        .text-center { text-align: center; }
        .fadeInDown {-webkit-animation-name: fadeInDown; animation-name: fadeInDown;}
        .animated {-webkit-animation-duration: 1s;animation-duration: 1s;-webkit-animation-fill-mode: both;animation-fill-mode: both;}
       .h1, h1 { font-size: 36px; }
       .h1, .h2, .h3, h1, h2, h3 { margin-top: 20px; margin-bottom: 10px;}
       .h1, .h2, .h3, .h4, .h5, .h6, h1, h2, h3, h4, h5, h6 { font-family: inherit; font-weight: 500; line-height: 1.1; color: inherit;}
       h1 { margin-top: 0.67em; margin-bottom: -0.1em; font-size: 2em;} 
       h3 { font-size: 25px; }
    </style>";

            $data=str_replace("<!-- TPL_IS_DEFAULT --->",$inlinestyle,$data);


            return $data;
        }

        public function build():string{
            $f          = array();


            if(!is_dir($this->finaldir)){@mkdir($this->finaldir,0755,true);}

            foreach ($this->valid_error_codes as $code){
                $key        = "error_page.$code.".$this->nginx_id;
                $finalfile  = $this->finaldir."/$key.html";
                $data       = $this->LoadTemplate($code,$this->nginx_id);
                @file_put_contents($finalfile,$data);
                $f[]="\terror_page $code /$key.html;";
                $f[]="\tlocation = /$key.html {";
                $f[]="\t\troot $this->finaldir;";
                $f[]="\t\tallow all;";
                $f[]="\t\tinternal;";
                $f[]="\t}";
                $f[]="";
            }

            return @implode("\n",$f);

        }


}