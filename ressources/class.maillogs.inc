<?php

function parse_recipitents($strings,$ashtml=false){
    $strings=str_replace("#012",", ", $strings);
    if($ashtml){$strings=htmlentities($strings);}
    $zrcpts_to=array();
    $zrcpts=explode(",",$strings);
    foreach ($zrcpts as $xlinez){
        $xlinez=trim($xlinez);
        if($xlinez==null){continue;}
        $zrcpts_to[]=$xlinez;
    }
    if(count($zrcpts_to)>1) {
        if($ashtml){
            $tpl=new template_admin();
            $array_help["TITLE"]="{recipients}";
            $array_help["content"]=@implode("<br>",$zrcpts_to);
            $array_help["ico"]="fa fa-envelope";
            $zcontent=serialize($array_help);
            $scontent=urlencode(base64_encode($zcontent));

            return $tpl->td_href($zrcpts_to[0] . "...&nbsp;(".count($zrcpts_to).")", null,
                "LoadAjax('artica-modal-dialog','fw.popup.php?array=$scontent')");
        }
    }

    return @implode(",",$zrcpts_to);
}
function clean_header($text){

    $text=str_replace("#015","",$text );
    $text=str_replace("#012","",$text );

    if (preg_match('/\?(utf-8|ISO-8859-1)\?/i', $text)) {
        if(function_exists("mb_decode_mimeheader")) {
            $text = mb_decode_mimeheader($text);
        }else{
            $text = "! utf8 mb_decode_mimeheader false";
        }
    }
    return $text;
}