<?php
include_once(dirname(__FILE__) . '/class.users.menus.inc'); 
include_once(dirname(__FILE__) . '/class.mysql.inc');
include_once(dirname(__FILE__) . '/class.user.inc');
include_once(dirname(__FILE__) . '/class.ini.inc');
include_once(dirname(__FILE__) . '/class.templates.inc');


function GetFlags256($Country=null):string{
    if($Country==null){ return sprintf("<img src='img/flags/256/%s'>","none.png");}
    $Country=strtolower($Country);
    $Country=str_replace(" ","",$Country);
    $array["unitedstates"]="unitedstates.png";
    $array["srilanka"]="srilanka.png";
    $array["china"]="china.png";
    $array["turkey"]="turkey.png";
    $array["australia"]="australia.png";
    $array["japan"]="japan.png";
    $array["brazil"]="brazil.png";
    $array["portugal"]="portugal.png";
    $array["india"]="india.png";
    $array["finland"]="finland.png";
    $array["unitedkingdom"]="unitedkingdom.png";
    $array["france"]="france.png";
    $array["lithuania"]="lithuania.png";
    $array["hongkong"]="hongkong.png";
    $array["germany"]="germany.png";
    $array["russia"]="russia.png";
    $array["croatia"]="croatia.png";
    $array["canada"]="canda.png";
    $array["réunion"]="réunion.png";
    $array["singapore"]="singapore.png";
    $array["pakistan"]="pakistan.png";
    $array["iraq"]="iraq.png";
    $array["southkorea"]="southkorea.png";
    $array["vietnam"]="vietnam.png";
    $array["estonia"]="estonia.png";
    $array["ivorycoast"]="ivorycoast.png";
    $array["switzerland"]="switzerland.png";
    $array["belgium"]="belgium.png";
    $array["ethiopia"]="ethiopia.png";
    $array["thenetherlands"]="thenetherlands.png";
    $array["türkiye"]="türkiye.png";
    $array["iran"]="iran.png";
    $array["seychelles"]="seychelles.png";
    $array["sweden"]="sweden.png";
    $array["poland"]="poland.png";
    $array["lebanon"]="lebanon.png";
    $array["venezuela"]="venezuela.png";
    $array["italy"]="italy.png";
    $array["denmark"]="denmark.png";
    $array["czechia"]="czechia.png";


    if(isset($array[$Country])){
        return sprintf("<img src='img/flags/256/%s'>",$array[$Country]);
    }else{
        return sprintf("<img src='img/flags/256/%s'>","none.png");

    }

}
function GetFlags($Country=null):string{
	if($Country==null){return "flags/info.png";}
	$Country=strtolower($Country);
    if(is_file("/usr/share/artica-postfix/img/flags/$Country.png")){
        return "flags/$Country.png";
    }

	$array["united states"]="us.png";
	$array["france"]="fr.png";
	$array["vietnam"]="vn.png";
	$array["brazil"]="br.png";
	$array["bulgaria"]="bg.png";
	$array["india"]="in.png";
	$array["korea, republic of"]="kr.png";
	$array["spain"]="es.png";
	$array["slovakia"]="sk.png";
	$array["poland"]="pl.png";
	$array["egypt"]="eg.png";
	$array["china"]="cn.png";
	$array["chile"]="cl.png";
	$array["thailand"]="th.png";
	$array["italy"]="it.png";
	$array["indonesia"]="id.png";
	$array["lebanon"]="lb.png";
	$array["israel"]="il.png";
	$array["japan"]="jp.png";
	$array['venezuela']="ve.png";
	$array["lithuania"]="lt.png";
	$array["argentina"]="ar.png";
	$array["mexico"]="mx.png";
	$array["colombia"]="co.png";
	$array["puerto rico"]="pr.png";
	$array["dominican republic"]="do.png";
	$array["united kingdom"]="uk.png";
	$array["peru"]="pe.png";
	$array["honduras"]="hn.png";
	$array["hungary"]="hu.png";
	$array["turkey"]="tr.png";
	$array["belarus"]="by.png";
	$array["canada"]="ca.png";
	$array["germany"]="de.png";
	$array["albania"]="al.png";
	$array["el salvador"]="els.png";
	$array["qatar"]="qa.png";
	$array["singapore"]="sg.png";
	$array["costa rica"]="cr.png";
	$array["romania"]="ro.png";
	$array["ukraine"]="ua.png";
	$array["portugal"]="pt.png";
	$array["russian federation"]="ru.png";
	$array["oman"]="om.png";
	$array["taiwan"]="tw.png";
	$array["czech republic"]="cs.png";
	$array["greece"]="gr.png";
	$array["morocco"]="ma.png";
	$array["uruguay"]="uy.png";
	$array["kazakhstan"]="kz.png";
	$array["libyan arab jamahiriya"]="ly.png";
	$array["switzerland"]="ch.png";
	$array["austria"]="at.png";
	$array["philippines"]="ph.png";
	$array["bahrain"]="bh.png";
	$array["kenya"]="kn.png";
	$array["iran, Islamic Republic of"]="ir.png";
	$array["ireland"]="ie.png";
	$array["united arab emirates"]="ae.png";
	$array["macedonia"]="mk.png";
	$array["kuwait"]="kw.png";
	$array["belgium"]="be.png";
	$array["europe"]="eu.png";
	$array["netherlands"]="nl.png";
	$array["sweden"]="se.png";
	$array["pakistan"]="pak.png";
	$array["saudi arabia"]="saua.png";
	$array["uganda"]="ug.png";
	$array["algeria"]="alg.png";
	$array["nigeria"]="ng.png";
	$array["serbia"]="sr.png";
	$array["tunisia"]="tn.png";
	$array["guatemala"]="gt.png";
	$array["azerbaijan"]="az.png";
	$array["australia"]="ai.png";
	$array["bolivia"]="bo.png";
	$array["paraguay"]="paraguay.png";
    $array["iran"]="ir.png";
    $array["malaysia"]="malaysia.png";
	$array["croatia"]="croatian.png";
	$array["south africa"]="southafrica.png";
	$array["iran, islamic republic of"]="iran.png";
	$array["hong kong"]="hongkong.png";
    $array["finland"]="finland.png";
    $array["moldova"]="moldova.png";
    $array["south korea"]="southkorea.png";
    $array["mayotte"]="mayotte.png";
    $array["cambodia"]="cambodia.png";
    $array["russia"]="russia.png";
    $array["luxembourg"]="luxembourg.png";
    $array["uzbekistan"]="uzbekistan.png";
    $array["sri lanka"]="srilanka.png";
    $array["the netherlands"]="netherlands.png";
	if(!isset($array[$Country])){
        if ($GLOBALS["VERBOSE"]) {VERBOSE("No flag for \"$Country\"", __LINE__);}
        $array[$Country] = "flags/info.png";
    }

	$flag=$array[$Country];
	return "flags/$flag";
	}
?>