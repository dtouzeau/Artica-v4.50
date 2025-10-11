<?php
$GLOBALS["FAILED_UPLOADED"]=0;
$GLOBALS["VERBOSE"]=false;
$GLOBALS["CLUSTER_SINGLE"]=false;
include_once(dirname(__FILE__).'/framework/class.unix.inc');if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/framework/frame.class.inc");
include_once(dirname(__FILE__)."/ressources/class.categories.inc");
include_once(dirname(__FILE__)."/ressources/class.openssl.aes.inc");

$q=new postgres_sql();
$f=explode("\n",@file_get_contents("/root/143.log"));
$newf=array();
$c=0;
foreach ($f as $line){
    if(!preg_match("#^[0-9]+\s+(.+)\s+([0-9]+)\s+\[(.+)\]\s+\[(.+?)\]#",$line,$re)){
        echo "$line NO matches\n";
        continue;
    }

    $sitename=trim($re[1]);
    if(preg_match("#^mkt([0-9]+)\.com$#",$sitename)){
        continue;
    }

    $catnum=$re[2];
    $catext=trim($re[3]);
    $engine=$re[4];
    if ($engine=="Heuristic"){
        continue;
    }
    if(strtolower($catext)=="suspicious"){
        continue;
    }
    if($catext=="Parked Domains"){
        continue;
    }
    if($catext=="Spam"){
        continue;
    }
    if($catext=="Spyware Effects"){
        continue;
    }

    if($catext=="Alcohol") {
        $c++;
        echo "$c] $sitename -> category_alcohol\n";
        $q->QUERY_SQL("DELETE FROM category_tracker WHERE sitename='$sitename'");
        $q->QUERY_SQL("INSERT INTO category_alcohol (sitename) VALUES ('$sitename') ON CONFLICT DO NOTHING");
        if (!$q->ok) {
            echo "ALERT ***** $q->mysql_error\n";
            die();
        }
        continue;
    }

    if($catext=="Sports / Recreation") {
        $c++;
        echo "$c] $sitename -> category_recreation_sports\n";
        $q->QUERY_SQL("DELETE FROM category_tracker WHERE sitename='$sitename'");
        $q->QUERY_SQL("INSERT INTO category_recreation_sports (sitename) VALUES ('$sitename') ON CONFLICT DO NOTHING");
        if (!$q->ok) {
            echo "ALERT ***** $q->mysql_error\n";
            die();
        }
        continue;
    }

    if($catext=="Art / Culture") {
        $c++;
        echo "$c] $sitename -> category_culture\n";
        $q->QUERY_SQL("DELETE FROM category_tracker WHERE sitename='$sitename'");
        $q->QUERY_SQL("INSERT INTO category_culture (sitename) VALUES ('$sitename') ON CONFLICT DO NOTHING");
        if (!$q->ok) {
            echo "ALERT ***** $q->mysql_error\n";
            die();
        }
        continue;
    }
    if($catext=="Political / Activist Groups") {
        $c++;
        echo "$c] $sitename -> category_politic\n";
        $q->QUERY_SQL("DELETE FROM category_tracker WHERE sitename='$sitename'");
        $q->QUERY_SQL("INSERT INTO category_politic (sitename) VALUES ('$sitename') ON CONFLICT DO NOTHING");
        if (!$q->ok) {
            echo "ALERT ***** $q->mysql_error\n";
            die();
        }
        continue;
    }

    if($catext=="Remote Access Tools") {
        $c++;
        echo "$c] $sitename -> category_remote_control\n";
        $q->QUERY_SQL("DELETE FROM category_tracker WHERE sitename='$sitename'");
        $q->QUERY_SQL("INSERT INTO category_remote_control (sitename) VALUES ('$sitename') ON CONFLICT DO NOTHING");
        if (!$q->ok) {
            echo "ALERT ***** $q->mysql_error\n";
            die();
        }
        continue;
    }

    if($catext=="Social Networking") {
        $c++;
        echo "$c] $sitename -> category_socialnet\n";
        $q->QUERY_SQL("DELETE FROM category_tracker WHERE sitename='$sitename'");
        $q->QUERY_SQL("INSERT INTO category_socialnet (sitename) VALUES ('$sitename') ON CONFLICT DO NOTHING");
        if (!$q->ok) {
            echo "ALERT ***** $q->mysql_error\n";
            die();
        }
        continue;
    }
    if($catext=="Potentially Unwanted Software") {
        $c++;
        echo "$c] $sitename -> category_spyware\n";
        $q->QUERY_SQL("DELETE FROM category_tracker WHERE sitename='$sitename'");
        $q->QUERY_SQL("INSERT INTO category_spyware (sitename) VALUES ('$sitename') ON CONFLICT DO NOTHING");
        if (!$q->ok) {
            echo "ALERT ***** $q->mysql_error\n";
            die();
        }
        continue;
    }

    if($catext=="Web Applications") {
        $c++;
        echo "$c] $sitename -> category_webapps\n";
        $q->QUERY_SQL("DELETE FROM category_tracker WHERE sitename='$sitename'");
        $q->QUERY_SQL("INSERT INTO category_webapps (sitename) VALUES ('$sitename') ON CONFLICT DO NOTHING");
        if (!$q->ok) {
            echo "ALERT ***** $q->mysql_error\n";
            die();
        }
        continue;
    }

    if($catext=="Email") {
        $c++;
        echo "$c] $sitename -> category_mailing\n";
        $q->QUERY_SQL("DELETE FROM category_tracker WHERE sitename='$sitename'");
        $q->QUERY_SQL("INSERT INTO category_mailing (sitename) VALUES ('$sitename') ON CONFLICT DO NOTHING");
        if (!$q->ok) {
            echo "ALERT ***** $q->mysql_error\n";
            die();
        }
        continue;
    }

    if($catext=="Tobacco") {
        $c++;
        echo "$c] $sitename -> category_tobacco\n";
        $q->QUERY_SQL("DELETE FROM category_tracker WHERE sitename='$sitename'");
        $q->QUERY_SQL("INSERT INTO category_tobacco (sitename) VALUES ('$sitename') ON CONFLICT DO NOTHING");
        if (!$q->ok) {
            echo "ALERT ***** $q->mysql_error\n";
            die();
        }
        continue;
    }

    if($catext=="Online Storage") {
        $c++;
        echo "$c] $sitename -> category_filehosting\n";
        $q->QUERY_SQL("DELETE FROM category_tracker WHERE sitename='$sitename'");
        $q->QUERY_SQL("INSERT INTO category_filehosting (sitename) VALUES ('$sitename') ON CONFLICT DO NOTHING");
        if (!$q->ok) {
            echo "ALERT ***** $q->mysql_error\n";
            die();
        }
        continue;
    }

    if($catext=="Brokerage / Trading") {
        $c++;
        echo "$c] $sitename -> category_stockexchange\n";
        $q->QUERY_SQL("DELETE FROM category_tracker WHERE sitename='$sitename'");
        $q->QUERY_SQL("INSERT INTO category_stockexchange (sitename) VALUES ('$sitename') ON CONFLICT DO NOTHING");
        if (!$q->ok) {
            echo "ALERT ***** $q->mysql_error\n";
            die();
        }
        continue;
    }

    if($catext=="Web Hosting") {
        $c++;
        echo "$c] $sitename -> category_isp\n";
        $q->QUERY_SQL("DELETE FROM category_tracker WHERE sitename='$sitename'");
        $q->QUERY_SQL("INSERT INTO category_isp (sitename) VALUES ('$sitename') ON CONFLICT DO NOTHING");
        if (!$q->ok) {
            echo "ALERT ***** $q->mysql_error\n";
            die();
        }
        continue;
    }

    if($catext=="Chat / Instant Messaging") {
        $c++;
        echo "$c] $sitename -> category_chat\n";
        $q->QUERY_SQL("DELETE FROM category_tracker WHERE sitename='$sitename'");
        $q->QUERY_SQL("INSERT INTO category_chat (sitename) VALUES ('$sitename') ON CONFLICT DO NOTHING");
        if (!$q->ok) {
            echo "ALERT ***** $q->mysql_error\n";
            die();
        }
        continue;
    }

    if($catext=="TV / Video Streams") {
        $c++;
        echo "$c] $sitename -> category_webtv\n";
        $q->QUERY_SQL("DELETE FROM category_tracker WHERE sitename='$sitename'");
        $q->QUERY_SQL("INSERT INTO category_webtv (sitename) VALUES ('$sitename') ON CONFLICT DO NOTHING");
        if (!$q->ok) {
            echo "ALERT ***** $q->mysql_error\n";
            die();
        }
        continue;
    }

    if($catext=="Open Image / Media Search") {
        $c++;
        echo "$c] $sitename -> category_pictureslib\n";
        $q->QUERY_SQL("DELETE FROM category_tracker WHERE sitename='$sitename'");
        $q->QUERY_SQL("INSERT INTO category_pictureslib (sitename) VALUES ('$sitename') ON CONFLICT DO NOTHING");
        if (!$q->ok) {
            echo "ALERT ***** $q->mysql_error\n";
            die();
        }
        continue;
    }
    if($catext=="Personals / Dating") {
        $c++;
        echo "$c] $sitename -> category_dating\n";
        $q->QUERY_SQL("DELETE FROM category_tracker WHERE sitename='$sitename'");
        $q->QUERY_SQL("INSERT INTO category_dating (sitename) VALUES ('$sitename') ON CONFLICT DO NOTHING");
        if (!$q->ok) {
            echo "ALERT ***** $q->mysql_error\n";
            die();
        }
        continue;
    }

    if($catext=="Weapons") {
        $c++;
        echo "$c] $sitename -> category_weapons\n";
        $q->QUERY_SQL("DELETE FROM category_tracker WHERE sitename='$sitename'");
        $q->QUERY_SQL("INSERT INTO category_weapons (sitename) VALUES ('$sitename') ON CONFLICT DO NOTHING");
        if (!$q->ok) {
            echo "ALERT ***** $q->mysql_error\n";
            die();
        }
        continue;
    }

    if($catext=="Proxy Avoidance") {
        $c++;
        echo "$c] $sitename -> category_proxy\n";
        $q->QUERY_SQL("DELETE FROM category_tracker WHERE sitename='$sitename'");
        $q->QUERY_SQL("INSERT INTO category_proxy (sitename) VALUES ('$sitename') ON CONFLICT DO NOTHING");
        if (!$q->ok) {
            echo "ALERT ***** $q->mysql_error\n";
            die();
        }
        continue;
    }

    if($catext=="Business / Economy") {
        $c++;
        echo "$c] $sitename -> category_industry\n";
        $q->QUERY_SQL("DELETE FROM category_tracker WHERE sitename='$sitename'");
        $q->QUERY_SQL("INSERT INTO category_industry (sitename) VALUES ('$sitename') ON CONFLICT DO NOTHING");
        if (!$q->ok) {
            echo "ALERT ***** $q->mysql_error\n";
            die();
        }
        continue;
    }

    if($catext=="Cultural / Charitable Organizations") {
        $c++;
        echo "$c] $sitename -> category_humanitarian\n";
        $q->QUERY_SQL("DELETE FROM category_tracker WHERE sitename='$sitename'");
        $q->QUERY_SQL("INSERT INTO category_humanitarian (sitename) VALUES ('$sitename') ON CONFLICT DO NOTHING");
        if (!$q->ok) {
            echo "ALERT ***** $q->mysql_error\n";
            die();
        }
        continue;
    }

    if($catext=="Hacking") {
        $c++;
        echo "$c] $sitename -> category_hacking\n";
        $q->QUERY_SQL("DELETE FROM category_tracker WHERE sitename='$sitename'");
        $q->QUERY_SQL("INSERT INTO category_hacking (sitename) VALUES ('$sitename') ON CONFLICT DO NOTHING");
        if (!$q->ok) {
            echo "ALERT ***** $q->mysql_error\n";
            die();
        }
        continue;
    }
    if($catext=="Search Engines / Portals") {
        $c++;
        echo "$c] $sitename -> category_searchengines\n";
        $q->QUERY_SQL("DELETE FROM category_tracker WHERE sitename='$sitename'");
        $q->QUERY_SQL("INSERT INTO category_searchengines (sitename) VALUES ('$sitename') ON CONFLICT DO NOTHING");
        if (!$q->ok) {
            echo "ALERT ***** $q->mysql_error\n";
            die();
        }
        continue;
    }

    if($catext=="Alternative Spirituality / Occult") {
        $c++;
        echo "$c] $sitename -> category_astrology\n";
        $q->QUERY_SQL("DELETE FROM category_tracker WHERE sitename='$sitename'");
        $q->QUERY_SQL("INSERT INTO category_astrology (sitename) VALUES ('$sitename') ON CONFLICT DO NOTHING");
        if (!$q->ok) {
            echo "ALERT ***** $q->mysql_error\n";
            die();
        }
        continue;
    }

    if($catext=="Government / Legal") {
        $c++;
        echo "$c] $sitename -> category_governments\n";
        $q->QUERY_SQL("DELETE FROM category_tracker WHERE sitename='$sitename'");
        $q->QUERY_SQL("INSERT INTO category_governments (sitename) VALUES ('$sitename') ON CONFLICT DO NOTHING");
        if (!$q->ok) {
            echo "ALERT ***** $q->mysql_error\n";
            die();
        }
        continue;
    }

    if($catext=="Newsgroups / Forums") {
        $c++;
        echo "$c] $sitename -> category_forums\n";
        $q->QUERY_SQL("DELETE FROM category_tracker WHERE sitename='$sitename'");
        $q->QUERY_SQL("INSERT INTO category_forums (sitename) VALUES ('$sitename') ON CONFLICT DO NOTHING");
        if (!$q->ok) {
            echo "ALERT ***** $q->mysql_error\n";
            die();
        }
        continue;
    }

    if($catext=="Religion") {
        $c++;
        echo "$c] $sitename -> category_religion\n";
        $q->QUERY_SQL("DELETE FROM category_tracker WHERE sitename='$sitename'");
        $q->QUERY_SQL("INSERT INTO category_religion (sitename) VALUES ('$sitename') ON CONFLICT DO NOTHING");
        if (!$q->ok) {
            echo "ALERT ***** $q->mysql_error\n";
            die();
        }
        continue;
    }

    if($catext=="News / Media") {
        $c++;
        echo "$c] $sitename -> category_news\n";
        $q->QUERY_SQL("DELETE FROM category_tracker WHERE sitename='$sitename'");
        $q->QUERY_SQL("INSERT INTO category_news (sitename) VALUES ('$sitename') ON CONFLICT DO NOTHING");
        if (!$q->ok) {
            echo "ALERT ***** $q->mysql_error\n";
            die();
        }
        continue;
    }

    if($catext=="Arts / Entertainment") {
        $c++;

        if(preg_match("#(museum|museo|musee)#",$sitename)){
            echo "$c] $sitename -> category_culture\n";
            $q->QUERY_SQL("DELETE FROM category_tracker WHERE sitename='$sitename'");
            $q->QUERY_SQL("INSERT INTO category_culture (sitename) VALUES ('$sitename') ON CONFLICT DO NOTHING");
            if (!$q->ok) {
                echo "ALERT ***** $q->mysql_error\n";
                die();
            }
            continue;

        }

        if(preg_match("#video#",$sitename)){
            echo "$c] $sitename -> category_audio_video\n";
            $q->QUERY_SQL("DELETE FROM category_tracker WHERE sitename='$sitename'");
            $q->QUERY_SQL("INSERT INTO category_audio_video (sitename) VALUES ('$sitename') ON CONFLICT DO NOTHING");
            if (!$q->ok) {
                echo "ALERT ***** $q->mysql_error\n";
                die();
            }
            continue;

        }

        if(preg_match("#radio\.#",$sitename)){
            echo "$c] $sitename -> category_webradio\n";
            $q->QUERY_SQL("DELETE FROM category_tracker WHERE sitename='$sitename'");
            $q->QUERY_SQL("INSERT INTO category_webradio (sitename) VALUES ('$sitename') ON CONFLICT DO NOTHING");
            if (!$q->ok) {
                echo "ALERT ***** $q->mysql_error\n";
                die();
            }
            continue;
        }
        if(preg_match("#(film|movie|stream|series|warner)#",$sitename)){
            echo "$c] $sitename -> category_movies\n";
            $q->QUERY_SQL("DELETE FROM category_tracker WHERE sitename='$sitename'");
            $q->QUERY_SQL("INSERT INTO category_movies (sitename) VALUES ('$sitename') ON CONFLICT DO NOTHING");
            if (!$q->ok) {
                echo "ALERT ***** $q->mysql_error\n";
                die();
            }
            continue;
        }
        if(preg_match("#(musique|music|musik)#",$sitename)){
            echo "$c] $sitename -> category_music\n";
            $q->QUERY_SQL("DELETE FROM category_tracker WHERE sitename='$sitename'");
            $q->QUERY_SQL("INSERT INTO category_music (sitename) VALUES ('$sitename') ON CONFLICT DO NOTHING");
            if (!$q->ok) {
                echo "ALERT ***** $q->mysql_error\n";
                die();
            }
            continue;
        }



        if(preg_match("#tattoo#",$sitename)){
            echo "$c] $sitename -> category_tattooing\n";
            $q->QUERY_SQL("DELETE FROM category_tracker WHERE sitename='$sitename'");
            $q->QUERY_SQL("INSERT INTO category_tattooing (sitename) VALUES ('$sitename') ON CONFLICT DO NOTHING");
            if (!$q->ok) {
                echo "ALERT ***** $q->mysql_error\n";
                die();
            }
            continue;
        }

        if(preg_match("#photo#",$sitename)){
            echo "$c] $sitename -> category_photo\n";
            $q->QUERY_SQL("DELETE FROM category_tracker WHERE sitename='$sitename'");
            $q->QUERY_SQL("INSERT INTO category_photo (sitename) VALUES ('$sitename') ON CONFLICT DO NOTHING");
            if (!$q->ok) {
                echo "ALERT ***** $q->mysql_error\n";
                die();
            }
            continue;
        }


        if(preg_match("#^tv#",$sitename)){
            echo "$c] $sitename -> category_webtv\n";
            $q->QUERY_SQL("DELETE FROM category_tracker WHERE sitename='$sitename'");
            $q->QUERY_SQL("INSERT INTO category_webtv (sitename) VALUES ('$sitename') ON CONFLICT DO NOTHING");
            if (!$q->ok) {
                echo "ALERT ***** $q->mysql_error\n";
                die();
            }
            continue;
        }


        echo "$c] $sitename -> category_hobby_arts\n";
        $q->QUERY_SQL("DELETE FROM category_tracker WHERE sitename='$sitename'");
        $q->QUERY_SQL("INSERT INTO category_hobby_arts (sitename) VALUES ('$sitename') ON CONFLICT DO NOTHING");
        if (!$q->ok) {
            echo "ALERT ***** $q->mysql_error\n";
            die();
        }
        continue;
    }
    if($catext=="Radio / Audio Streams") {
        $c++;
        echo "$c] $sitename -> category_webradio\n";
        $q->QUERY_SQL("DELETE FROM category_tracker WHERE sitename='$sitename'");
        $q->QUERY_SQL("INSERT INTO category_webradio (sitename) VALUES ('$sitename') ON CONFLICT DO NOTHING");
        if (!$q->ok) {
            echo "ALERT ***** $q->mysql_error\n";
            die();
        }
        continue;
    }

    if($catext=="Financial Services") {
        $c++;
        echo "$c] $sitename -> category_financial\n";
        $q->QUERY_SQL("DELETE FROM category_tracker WHERE sitename='$sitename'");
        $q->QUERY_SQL("INSERT INTO category_financial (sitename) VALUES ('$sitename') ON CONFLICT DO NOTHING");
        if (!$q->ok) {
            echo "ALERT ***** $q->mysql_error\n";
            die();
        }
        continue;
    }

    if($catext=="Society / Daily Living") {
        $c++;
        echo "$c] $sitename -> category_society\n";
        $q->QUERY_SQL("DELETE FROM category_tracker WHERE sitename='$sitename'");
        $q->QUERY_SQL("INSERT INTO category_society (sitename) VALUES ('$sitename') ON CONFLICT DO NOTHING");
        if (!$q->ok) {
            echo "ALERT ***** $q->mysql_error\n";
            die();
        }
        continue;
    }

        if($catext=="Job Search / Careers"){
        $c++;
        echo "$c] $sitename -> category_jobsearch\n";
        $q->QUERY_SQL("DELETE FROM category_tracker WHERE sitename='$sitename'");
        $q->QUERY_SQL("INSERT INTO category_jobsearch (sitename) VALUES ('$sitename') ON CONFLICT DO NOTHING");
        if(!$q->ok){echo "ALERT ***** $q->mysql_error\n";die();}
        continue;

    }

    if($catext=="Gambling"){
        $c++;
        echo "$c] $sitename -> category_gamble\n";
        $q->QUERY_SQL("DELETE FROM category_tracker WHERE sitename='$sitename'");
        $q->QUERY_SQL("INSERT INTO category_gamble (sitename) VALUES ('$sitename') ON CONFLICT DO NOTHING");
        if(!$q->ok){echo "ALERT ***** $q->mysql_error\n";die();}
        continue;
    }
    if($catext=="Games"){
        $c++;
        echo "$c] $sitename -> category_games\n";
        $q->QUERY_SQL("DELETE FROM category_tracker WHERE sitename='$sitename'");
        $q->QUERY_SQL("INSERT INTO category_games (sitename) VALUES ('$sitename') ON CONFLICT DO NOTHING");
        if(!$q->ok){echo "ALERT ***** $q->mysql_error\n";die();}
        continue;
    }

    if($catext=="Real Estate"){
        $c++;
        echo "$c] $sitename -> category_finance_realestate\n";
        $q->QUERY_SQL("DELETE FROM category_tracker WHERE sitename='$sitename'");
        $q->QUERY_SQL("INSERT INTO category_finance_realestate (sitename) VALUES ('$sitename') ON CONFLICT DO NOTHING");
        if(!$q->ok){echo "ALERT ***** $q->mysql_error\n";die();}
        continue;
    }

    if(strtolower($catext)=="health"){
        $c++;
        echo "$c] $sitename -> category_health\n";
        $q->QUERY_SQL("DELETE FROM category_tracker WHERE sitename='$sitename'");
        $q->QUERY_SQL("INSERT INTO category_health (sitename) VALUES ('$sitename')");
        continue;
    }

    if($catext=="Personal Pages / Blogs"){
        $c++;
        echo "$c] $sitename -> category_blog\n";
        $q->QUERY_SQL("DELETE FROM category_tracker WHERE sitename='$sitename'");
        $q->QUERY_SQL("INSERT INTO category_blog (sitename) VALUES ('$sitename') ON CONFLICT DO NOTHING");
        if(!$q->ok){echo "ALERT ***** $q->mysql_error\n";die();}
        continue;
    }

    if($catext=="Web Advertisements"){
        $c++;
        echo "$c] $sitename -> category_publicite\n";
        $q->QUERY_SQL("DELETE FROM category_tracker WHERE sitename='$sitename'");
        $q->QUERY_SQL("INSERT INTO category_publicite (sitename) VALUES ('$sitename') ON CONFLICT DO NOTHING");
        if(!$q->ok){echo "ALERT ***** $q->mysql_error\n";die();}
        continue;
    }


    if($catext=="Shopping"){
        $c++;
        echo "$c] $sitename -> category_shopping\n";
        $q->QUERY_SQL("DELETE FROM category_tracker WHERE sitename='$sitename'");
        $q->QUERY_SQL("INSERT INTO category_shopping (sitename) VALUES ('$sitename') ON CONFLICT DO NOTHING");
        if(!$q->ok){echo "ALERT ***** $q->mysql_error\n";die();}
        continue;
    }
    if($catext=="Auctions"){
        $c++;
        echo "$c] $sitename -> category_shopping\n";
        $q->QUERY_SQL("DELETE FROM category_tracker WHERE sitename='$sitename'");
        $q->QUERY_SQL("INSERT INTO category_shopping (sitename) VALUES ('$sitename') ON CONFLICT DO NOTHING");
        if(!$q->ok){echo "ALERT ***** $q->mysql_error\n";die();}
        continue;
    }

    if($catext=="Animals / Pets"){
        $c++;
        echo "$c] $sitename -> category_hobby_pets\n";
        $q->QUERY_SQL("DELETE FROM category_tracker WHERE sitename='$sitename'");
        $q->QUERY_SQL("INSERT INTO category_hobby_pets (sitename) VALUES ('$sitename') ON CONFLICT DO NOTHING");
        if(!$q->ok){echo "ALERT ***** $q->mysql_error\n";die();}
        continue;
    }

    if($catext=="Online Meetings"){
        $c++;
        echo "$c] $sitename -> category_meetings\n";
        $q->QUERY_SQL("DELETE FROM category_tracker WHERE sitename='$sitename'");
        $q->QUERY_SQL("INSERT INTO category_meetings (sitename) VALUES ('$sitename') ON CONFLICT DO NOTHING");
        if(!$q->ok){echo "ALERT ***** $q->mysql_error\n";die();}
        continue;
    }

    if($catext=="Dynamic DNS Host"){
        $c++;
        echo "$c] $sitename -> category_dynamic\n";
        $q->QUERY_SQL("DELETE FROM category_tracker WHERE sitename='$sitename'");
        $q->QUERY_SQL("INSERT INTO category_dynamic (sitename) VALUES ('$sitename') ON CONFLICT DO NOTHING");
        if(!$q->ok){echo "ALERT ***** $q->mysql_error\n";die();}
        continue;
    }

    if($catext=="Drugs"){
        $c++;
        echo "$c] $sitename -> category_drugs\n";
        $q->QUERY_SQL("DELETE FROM category_tracker WHERE sitename='$sitename'");
        $q->QUERY_SQL("INSERT INTO category_drugs (sitename) VALUES ('$sitename') ON CONFLICT DO NOTHING");
        if(!$q->ok){echo "ALERT ***** $q->mysql_error\n";die();}
        continue;
    }

    if($catext=="Nudity"){
        $c++;
        echo "$c] $sitename -> category_mixed_adult\n";
        $q->QUERY_SQL("DELETE FROM category_tracker WHERE sitename='$sitename'");
        $q->QUERY_SQL("INSERT INTO category_mixed_adult (sitename) VALUES ('$sitename') ON CONFLICT DO NOTHING");
        if(!$q->ok){echo "ALERT ***** $q->mysql_error\n";die();}
        continue;
    }

    if($catext=="Greeting Cards"){
        $c++;
        echo "$c] $sitename -> category_gifts\n";
        $q->QUERY_SQL("DELETE FROM category_tracker WHERE sitename='$sitename'");
        $q->QUERY_SQL("INSERT INTO category_gifts (sitename) VALUES ('$sitename') ON CONFLICT DO NOTHING");
        if(!$q->ok){echo "ALERT ***** $q->mysql_error\n";die();}
        continue;
    }

    if($catext=="Audio / Video Clips"){
        $c++;
        echo "$c] $sitename -> category_audio_video\n";
        $q->QUERY_SQL("DELETE FROM category_tracker WHERE sitename='$sitename'");
        $q->QUERY_SQL("INSERT INTO category_audio_video (sitename) VALUES ('$sitename') ON CONFLICT DO NOTHING");
        if(!$q->ok){echo "ALERT ***** $q->mysql_error\n";die();}
        continue;
    }

    if($catext=="Translation"){
        $c++;
        echo "$c] $sitename -> category_translators\n";
        $q->QUERY_SQL("DELETE FROM category_tracker WHERE sitename='$sitename'");
        $q->QUERY_SQL("INSERT INTO category_translators (sitename) VALUES ('$sitename') ON CONFLICT DO NOTHING");
        if(!$q->ok){echo "ALERT ***** $q->mysql_error\n";die();}
        continue;
    }

    if($catext=="Beauty / Fashion"){
        $c++;
        echo "$c] $sitename -> category_womanbrand\n";
        $q->QUERY_SQL("DELETE FROM category_tracker WHERE sitename='$sitename'");
        $q->QUERY_SQL("INSERT INTO category_womanbrand (sitename) VALUES ('$sitename') ON CONFLICT DO NOTHING");
        if(!$q->ok){echo "ALERT ***** $q->mysql_error\n";die();}
        continue;
    }

    if($catext=="For Kids"){
        $c++;
        echo "$c] $sitename -> category_children\n";
        $q->QUERY_SQL("DELETE FROM category_tracker WHERE sitename='$sitename'");
        $q->QUERY_SQL("INSERT INTO category_children (sitename) VALUES ('$sitename') ON CONFLICT DO NOTHING");
        if(!$q->ok){echo "ALERT ***** $q->mysql_error\n";die();}
        continue;
    }

    if($catext=="Internet Telephony"){
        $c++;
        echo "$c] $sitename -> category_webphone\n";
        $q->QUERY_SQL("DELETE FROM category_tracker WHERE sitename='$sitename'");
        $q->QUERY_SQL("INSERT INTO category_webphone (sitename) VALUES ('$sitename') ON CONFLICT DO NOTHING");
        if(!$q->ok){echo "ALERT ***** $q->mysql_error\n";die();}
        continue;
    }

    if($catext=="Military" OR $catext=="Violence / Hate / Racism"){
        $c++;
        echo "$c] $sitename -> category_violence\n";
        $q->QUERY_SQL("DELETE FROM category_tracker WHERE sitename='$sitename'");
        $q->QUERY_SQL("INSERT INTO category_violence (sitename) VALUES ('$sitename') ON CONFLICT DO NOTHING");
        if(!$q->ok){echo "ALERT ***** $q->mysql_error\n";die();}
        continue;
    }

    if($catext=="Media Sharing"){
        $c++;
        if(preg_match("#photo#",$sitename)){

            echo "$c] $sitename -> category_photo\n";
            $q->QUERY_SQL("DELETE FROM category_tracker WHERE sitename='$sitename'");
            $q->QUERY_SQL("INSERT INTO category_photo (sitename) VALUES ('$sitename') ON CONFLICT DO NOTHING");
            if(!$q->ok){echo "ALERT ***** $q->mysql_error\n";die();}
            continue;
        }
        echo "$c] $sitename -> category_pictureslib\n";
        $q->QUERY_SQL("DELETE FROM category_tracker WHERE sitename='$sitename'");
        $q->QUERY_SQL("INSERT INTO category_pictureslib (sitename) VALUES ('$sitename') ON CONFLICT DO NOTHING");
        if (!$q->ok) {
            echo "ALERT ***** $q->mysql_error\n";
            die();
        }
    }


    if($catext=="Reference"){
        $c++;
        echo "$c] $sitename -> category_dictionaries\n";
        $q->QUERY_SQL("DELETE FROM category_tracker WHERE sitename='$sitename'");
        $q->QUERY_SQL("INSERT INTO category_dictionaries (sitename) VALUES ('$sitename') ON CONFLICT DO NOTHING");
        if(!$q->ok){echo "ALERT ***** $q->mysql_error\n";die();}
        continue;
    }

    if($catext=="Content Servers"){
        $c++;
        echo "$c] $sitename -> category_isp\n";
        $q->QUERY_SQL("DELETE FROM category_tracker WHERE sitename='$sitename'");
        $q->QUERY_SQL("INSERT INTO category_isp (sitename) VALUES ('$sitename') ON CONFLICT DO NOTHING");
        if(!$q->ok){echo "ALERT ***** $q->mysql_error\n";die();}
        continue;
    }

    if($catext=="Intimate Apparel / Swimsuit"){
        $c++;
        echo "$c] $sitename -> category_sex_lingerie\n";
        $q->QUERY_SQL("DELETE FROM category_tracker WHERE sitename='$sitename'");
        $q->QUERY_SQL("INSERT INTO category_sex_lingerie (sitename) VALUES ('$sitename') ON CONFLICT DO NOTHING");
        if(!$q->ok){echo "ALERT ***** $q->mysql_error\n";die();}
        continue;
    }

    if($catext=="Humor / Jokes"){
        $c++;
        echo "$c] $sitename -> category_recreation_humor\n";
        $q->QUERY_SQL("DELETE FROM category_tracker WHERE sitename='$sitename'");
        $q->QUERY_SQL("INSERT INTO category_recreation_humor (sitename) VALUES ('$sitename') ON CONFLICT DO NOTHING");
        if(!$q->ok){echo "ALERT ***** $q->mysql_error\n";die();}
        continue;
    }

    if($catext=="Peer-to-Peer"){
        $c++;
        echo "$c] $sitename -> category_downloads\n";
        $q->QUERY_SQL("DELETE FROM category_tracker WHERE sitename='$sitename'");
        $q->QUERY_SQL("INSERT INTO category_downloads (sitename) VALUES ('$sitename') ON CONFLICT DO NOTHING");
        if(!$q->ok){echo "ALERT ***** $q->mysql_error\n";die();}
        continue;
    }

    if($catext=="Software Downloads"){
        $c++;
        if(preg_match("#font#",$sitename)){
            echo "$c] $sitename -> category_pictureslib\n";
            $q->QUERY_SQL("DELETE FROM category_tracker WHERE sitename='$sitename'");
            $q->QUERY_SQL("INSERT INTO category_pictureslib (sitename) VALUES ('$sitename') ON CONFLICT DO NOTHING");
            if (!$q->ok) {
                echo "ALERT ***** $q->mysql_error\n";
                die();
            }
            continue;
        }

        echo "$c] $sitename -> category_downloads\n";
        $q->QUERY_SQL("DELETE FROM category_tracker WHERE sitename='$sitename'");
        $q->QUERY_SQL("INSERT INTO category_downloads (sitename) VALUES ('$sitename') ON CONFLICT DO NOTHING");
        if(!$q->ok){echo "ALERT ***** $q->mysql_error\n";die();}
        continue;
    }

    

    if($catext=="Computers / Internet"){
        $c++;
        echo "$c] $sitename -> category_science_computing\n";
        $q->QUERY_SQL("DELETE FROM category_tracker WHERE sitename='$sitename'");
        $q->QUERY_SQL("INSERT INTO category_science_computing (sitename) VALUES ('$sitename') ON CONFLICT DO NOTHING");
        if(!$q->ok){echo "ALERT ***** $q->mysql_error\n";die();}
        continue;

    }
    if($catext=="Travel"){
        $c++;
        echo "$c] $sitename -> category_recreation_travel\n";
        $q->QUERY_SQL("DELETE FROM category_tracker WHERE sitename='$sitename'");
        $q->QUERY_SQL("INSERT INTO category_recreation_travel (sitename) VALUES ('$sitename') ON CONFLICT DO NOTHING");
        if(!$q->ok){echo "ALERT ***** $q->mysql_error\n";die();}
        continue;
    }

    if($catext=="Phishing"){
        $c++;
        echo "$c] $sitename -> category_phishing\n";
        $q->QUERY_SQL("DELETE FROM category_tracker WHERE sitename='$sitename'");
        $q->QUERY_SQL("INSERT INTO category_phishing (sitename) VALUES ('$sitename') ON CONFLICT DO NOTHING");
        if(!$q->ok){echo "ALERT ***** $q->mysql_error\n";die();}
        continue;
    }

    if($catext=="Compromised Sites"){
        $c++;
        echo "$c] $sitename -> category_phishing\n";
        $q->QUERY_SQL("DELETE FROM category_tracker WHERE sitename='$sitename'");
        $q->QUERY_SQL("INSERT INTO category_phishing (sitename) VALUES ('$sitename') ON CONFLICT DO NOTHING");
        if(!$q->ok){echo "ALERT ***** $q->mysql_error\n";die();}
        continue;
    }

    if($catext=="Illegal / Questionable / Scam"){
        $c++;
        echo "$c] $sitename -> category_phishing\n";
        $q->QUERY_SQL("DELETE FROM category_tracker WHERE sitename='$sitename'");
        $q->QUERY_SQL("INSERT INTO category_phishing (sitename) VALUES ('$sitename') ON CONFLICT DO NOTHING");
        if(!$q->ok){echo "ALERT ***** $q->mysql_error\n";die();}
        continue;
    }

    if($catext=="Restaurants / Dining / Food"){
        $c++;
        echo "$c] $sitename -> category_recreation_nightout\n";
        $q->QUERY_SQL("DELETE FROM category_tracker WHERE sitename='$sitename'");
        $q->QUERY_SQL("INSERT INTO category_recreation_nightout (sitename) VALUES ('$sitename')");
        continue;

    }
    if($catext=="Virus / Malware / Spyware"){
        $c++;
        echo "$c] $sitename -> category_malware\n";
        $q->QUERY_SQL("DELETE FROM category_tracker WHERE sitename='$sitename'");
        $q->QUERY_SQL("INSERT INTO category_malware (sitename) VALUES ('$sitename')");
        continue;
    }

    if($catext=="Vehicles"){
        $c++;
        echo "$c] $sitename -> category_automobile_cars\n";
        $q->QUERY_SQL("DELETE FROM category_tracker WHERE sitename='$sitename'");
        $q->QUERY_SQL("INSERT INTO category_automobile_cars (sitename) VALUES ('$sitename')");
        continue;
    }

    if(strtolower($catext)=="pornography"){
        $c++;
        echo "$c] $sitename -> category_porn\n";
        $q->QUERY_SQL("DELETE FROM category_tracker WHERE sitename='$sitename'");
        $q->QUERY_SQL("INSERT INTO category_porn (sitename) VALUES ('$sitename')");
        continue;
    }


    if(strtolower($catext)=="sex education"){
        $c++;
        echo "$c] $sitename -> category_mixed_adult\n";
        $q->QUERY_SQL("DELETE FROM category_tracker WHERE sitename='$sitename'");
        $q->QUERY_SQL("INSERT INTO category_mixed_adult (sitename) VALUES ('$sitename')");
        continue;

    }
    if($catext=="Adult / Mature Content"){
        $c++;
        echo "$c] $sitename -> category_mixed_adult\n";
        $q->QUERY_SQL("DELETE FROM category_tracker WHERE sitename='$sitename'");
        $q->QUERY_SQL("INSERT INTO category_mixed_adult (sitename) VALUES ('$sitename') ON CONFLICT DO NOTHING");
        if(!$q->ok){echo "ALERT ***** $q->mysql_error\n";die();}
        continue;
    }

    if(strtolower($catext)=="education"){
        $c++;
        echo "$c] $sitename -> category_recreation_schools\n";
        $q->QUERY_SQL("DELETE FROM category_tracker WHERE sitename='$sitename'");
        $q->QUERY_SQL("INSERT INTO category_recreation_schools (sitename) VALUES ('$sitename')");
        continue;
    }

    $newf[]=$line;
}

@file_put_contents("/root/143.log",@implode("\n",$newf));