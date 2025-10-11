<?php
include_once(dirname(__FILE__) . '/framework/class.unix.inc');
include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");
$GLOBALS["CLASS_SOCKETS"] = new sockets();
include_once(dirname(__FILE__) . "/framework/frame.class.inc");
include_once(dirname(__FILE__) . "/ressources/class.categories.inc");

if($argv[1]=="--mkt"){
    movemkt();
    exit;
}
if($argv[1]=="--adv"){
    advertising();
    exit;
}


$pos=new postgres_sql();
$targetpath="/root/143.log";
$newf=array();
$f=explode("\n",@file_get_contents($targetpath));
foreach ($f as $line) {
    $line = trim(strtolower($line));
    if ($line == null) {
        continue;
    }
    //if(!preg_match("#^([0-9]+)\s+(.+?)\s+([0-9]+)\s+(.+)#",$line,$re)){continue;}
    //$domain=$re[2];
    //$catnum=intval($re[3]);
    //$cattext=trim($re[4]);
    if(!preg_match("#^(.+?)\s+([0-9]+)#",$line,$re)){continue;}
    $catnum=$re[2];
    $domain=trim($re[1]);
    $cattext=null;
    //$domain=$re[2];
    //$catnum=intval($re[3]);
    //$cattext=trim($re[4]);
    $CategoryToClean="category_tracker";
    if($catnum==9){continue;}
    if($catnum==43){continue;}
    if($catnum==44){continue;}
    if($catnum==92){continue;}
    if($catnum==999){continue;}
    if($catnum==98){continue;}
    if($catnum==102){continue;}
    if($catnum==124){continue;}
    if($catnum==504){continue;}
    if($catnum==96){continue;}


    if($catnum==997){
        echo "[MOVE]: $domain ===> category_reaffected $cattext - $catnum -\n";
        $pos->QUERY_SQL("DELETE FROM $CategoryToClean WHERE sitename='$domain'");
        $pos->QUERY_SQL("INSERT INTO category_reaffected (sitename) VALUES('$domain') ON CONFLICT DO NOTHING");
        continue;
    }

    if($catnum==1){
        echo "[MOVE]: $domain ===> category_porn $cattext - $catnum -\n";
        $pos->QUERY_SQL("DELETE FROM $CategoryToClean WHERE sitename='$domain'");
        $pos->QUERY_SQL("INSERT INTO category_porn (sitename) VALUES('$domain') ON CONFLICT DO NOTHING");
        continue;
    }
    if($catnum==901){
        echo "[MOVE]: $domain ===> category_porn $cattext - $catnum -\n";
        $pos->QUERY_SQL("DELETE FROM $CategoryToClean WHERE sitename='$domain'");
        $pos->QUERY_SQL("INSERT INTO category_porn (sitename) VALUES('$domain') ON CONFLICT DO NOTHING");
        continue;
    }
    if($catnum==503){
        echo "[MOVE]: $domain ===> category_warez $cattext - $catnum -\n";
        $pos->QUERY_SQL("DELETE FROM $CategoryToClean WHERE sitename='$domain'");
        $pos->QUERY_SQL("INSERT INTO category_warez (sitename) VALUES('$domain') ON CONFLICT DO NOTHING");
        continue;
    }
    if($catnum==3){
        echo "[MOVE]: $domain ===> category_porn $cattext - $catnum -\n";
        $pos->QUERY_SQL("DELETE FROM $CategoryToClean WHERE sitename='$domain'");
        $pos->QUERY_SQL("INSERT INTO category_porn (sitename) VALUES('$domain') ON CONFLICT DO NOTHING");
        continue;
    }

    if($catnum==4){
        echo "[MOVE]: $domain ===> category_sexual_education $cattext - $catnum -\n";
        $pos->QUERY_SQL("DELETE FROM $CategoryToClean WHERE sitename='$domain'");
        $pos->QUERY_SQL("INSERT INTO category_sexual_education (sitename) VALUES('$domain') ON CONFLICT DO NOTHING");
        continue;
    }

    if($catnum==5){
        echo "[MOVE]: $domain ===> category_sex_lingerie $cattext - $catnum -\n";
        $pos->QUERY_SQL("DELETE FROM $CategoryToClean WHERE sitename='$domain'");
        $pos->QUERY_SQL("INSERT INTO category_sex_lingerie (sitename) VALUES('$domain') ON CONFLICT DO NOTHING");
        continue;
    }
    if($catnum==6){
        echo "[MOVE]: $domain ===> category_mixed_adult $cattext - $catnum -\n";
        $pos->QUERY_SQL("DELETE FROM $CategoryToClean WHERE sitename='$domain'");
        $pos->QUERY_SQL("INSERT INTO category_mixed_adult (sitename) VALUES('$domain') ON CONFLICT DO NOTHING");
        continue;
    }

    if($catnum==11){
        echo "[MOVE]: $domain ===> category_religion $cattext - $catnum -\n";
        $pos->QUERY_SQL("DELETE FROM $CategoryToClean WHERE sitename='$domain'");
        $pos->QUERY_SQL("INSERT INTO category_religion (sitename) VALUES('$domain') ON CONFLICT DO NOTHING");
        continue;
    }

    if($catnum==14){
        echo "[MOVE]: $domain ===> category_violence $cattext - $catnum -\n";
        $pos->QUERY_SQL("DELETE FROM $CategoryToClean WHERE sitename='$domain'");
        $pos->QUERY_SQL("INSERT INTO category_violence (sitename) VALUES('$domain') ON CONFLICT DO NOTHING");
        continue;
    }

    if($catnum==15){
        echo "[MOVE]: $domain ===> category_weapons $cattext - $catnum -\n";
        $pos->QUERY_SQL("DELETE FROM $CategoryToClean WHERE sitename='$domain'");
        $pos->QUERY_SQL("INSERT INTO category_weapons (sitename) VALUES('$domain') ON CONFLICT DO NOTHING");
        continue;
    }

    if($catnum==17){
        echo "[MOVE]: $domain ===> category_hacking $cattext - $catnum -\n";
        $pos->QUERY_SQL("DELETE FROM $CategoryToClean WHERE sitename='$domain'");
        $pos->QUERY_SQL("INSERT INTO category_hacking (sitename) VALUES('$domain') ON CONFLICT DO NOTHING");
        continue;
    }

    if($catnum==20){
        echo "[MOVE]: $domain ===> category_hobby_arts $cattext - $catnum -\n";
        $pos->QUERY_SQL("DELETE FROM $CategoryToClean WHERE sitename='$domain'");
        $pos->QUERY_SQL("INSERT INTO category_hobby_arts (sitename) VALUES('$domain') ON CONFLICT DO NOTHING");
        continue;
    }
    if($catnum==21){
        echo "[MOVE]: $domain ===> category_industry $cattext - $catnum -\n";
        $pos->QUERY_SQL("DELETE FROM $CategoryToClean WHERE sitename='$domain'");
        $pos->QUERY_SQL("INSERT INTO category_industry (sitename) VALUES('$domain') ON CONFLICT DO NOTHING");
        continue;
    }
    if($catnum==22){
        echo "[MOVE]: $domain ===> category_sect $cattext - $catnum -\n";
        $pos->QUERY_SQL("DELETE FROM $CategoryToClean WHERE sitename='$domain'");
        $pos->QUERY_SQL("INSERT INTO category_sect (sitename) VALUES('$domain') ON CONFLICT DO NOTHING");
        continue;
    }



    if($catnum==38){
        echo "[MOVE]: $domain ===> category_science_computing $cattext - $catnum -\n";
        $pos->QUERY_SQL("DELETE FROM $CategoryToClean WHERE sitename='$domain'");
        $pos->QUERY_SQL("INSERT INTO category_science_computing (sitename) VALUES('$domain') ON CONFLICT DO NOTHING");
        continue;
    }
    if($catnum==46){
        echo "[MOVE]: $domain ===> category_press $cattext - $catnum -\n";
        $pos->QUERY_SQL("DELETE FROM category_spyware WHERE sitename='$domain'");
        $pos->QUERY_SQL("INSERT INTO category_press (sitename) VALUES('$domain') ON CONFLICT DO NOTHING");
        continue;
    }
    if($catnum==27){
        echo "[MOVE]: $domain ===> category_recreation_schools $cattext - $catnum -\n";
        $pos->QUERY_SQL("DELETE FROM $CategoryToClean WHERE sitename='$domain'");
        $pos->QUERY_SQL("INSERT INTO category_recreation_schools (sitename) VALUES('$domain') ON CONFLICT DO NOTHING");
        continue;
    }

    if($catnum==40){
        echo "[MOVE]: $domain ===> category_searchengines $cattext - $catnum -\n";
        $pos->QUERY_SQL("DELETE FROM $CategoryToClean WHERE sitename='$domain'");
        $pos->QUERY_SQL("INSERT INTO category_searchengines (sitename) VALUES('$domain') ON CONFLICT DO NOTHING");
        continue;
    }

    if($catnum==47){
        echo "[MOVE]: $domain ===> category_dating $cattext - $catnum -\n";
        $pos->QUERY_SQL("DELETE FROM $CategoryToClean WHERE sitename='$domain'");
        $pos->QUERY_SQL("INSERT INTO category_dating (sitename) VALUES('$domain') ON CONFLICT DO NOTHING");
        continue;
    }


    if($catnum==49){
        echo "[MOVE]: $domain ===> category_dictionaries $cattext - $catnum -\n";
        $pos->QUERY_SQL("DELETE FROM $CategoryToClean WHERE sitename='$domain'");
        $pos->QUERY_SQL("INSERT INTO category_dictionaries (sitename) VALUES('$domain') ON CONFLICT DO NOTHING");
        continue;
    }


    if($catnum==50){
        echo "[MOVE]: $domain ===> category_pictureslib $cattext - $catnum -\n";
        $pos->QUERY_SQL("DELETE FROM $CategoryToClean WHERE sitename='$domain'");
        $pos->QUERY_SQL("INSERT INTO category_pictureslib (sitename) VALUES('$domain') ON CONFLICT DO NOTHING");
        continue;
    }

    if($catnum==51){
        echo "[MOVE]: $domain ===> category_chat $cattext - $catnum -\n";
        $pos->QUERY_SQL("DELETE FROM $CategoryToClean WHERE sitename='$domain'");
        $pos->QUERY_SQL("INSERT INTO category_chat (sitename) VALUES('$domain') ON CONFLICT DO NOTHING");
        continue;
    }

    if($catnum==52){
        echo "[MOVE]: $domain ===> category_webmail $cattext - $catnum -\n";
        $pos->QUERY_SQL("DELETE FROM $CategoryToClean WHERE sitename='$domain'");
        $pos->QUERY_SQL("INSERT INTO category_webmail (sitename) VALUES('$domain') ON CONFLICT DO NOTHING");
        continue;
    }
    if($catnum==53){
        echo "[MOVE]: $domain ===> category_forums $cattext - $catnum -\n";
        $pos->QUERY_SQL("DELETE FROM $CategoryToClean WHERE sitename='$domain'");
        $pos->QUERY_SQL("INSERT INTO category_forums (sitename) VALUES('$domain') ON CONFLICT DO NOTHING");
        continue;
    }


    if($catnum==55){
        echo "[MOVE]: $domain ===> category_socialnet $cattext - $catnum -\n";
        $pos->QUERY_SQL("DELETE FROM $CategoryToClean WHERE sitename='$domain'");
        $pos->QUERY_SQL("INSERT INTO category_socialnet (sitename) VALUES('$domain') ON CONFLICT DO NOTHING");
        continue;
    }

    if($catnum==57){
        echo "[MOVE]: $domain ===> category_remote_control $cattext - $catnum -\n";
        $pos->QUERY_SQL("DELETE FROM $CategoryToClean WHERE sitename='$domain'");
        $pos->QUERY_SQL("INSERT INTO category_remote_control (sitename) VALUES('$domain') ON CONFLICT DO NOTHING");
        continue;
    }

    if($catnum==58){
        echo "[MOVE]: $domain ===> category_shopping $cattext - $catnum -\n";
        $pos->QUERY_SQL("DELETE FROM $CategoryToClean WHERE sitename='$domain'");
        $pos->QUERY_SQL("INSERT INTO category_shopping (sitename) VALUES('$domain') ON CONFLICT DO NOTHING");
        continue;
    }
    if($catnum==37){
        echo "[MOVE]: $domain ===> category_health $cattext - $catnum -\n";
        $pos->QUERY_SQL("DELETE FROM $CategoryToClean WHERE sitename='$domain'");
        $pos->QUERY_SQL("INSERT INTO category_health (sitename) VALUES('$domain') ON CONFLICT DO NOTHING");
        continue;
    }

    if($catnum==65){
        echo "[MOVE]: $domain ===> category_recreation_sports $cattext - $catnum -\n";
        $pos->QUERY_SQL("DELETE FROM $CategoryToClean WHERE sitename='$domain'");
        $pos->QUERY_SQL("INSERT INTO category_recreation_sports (sitename) VALUES('$domain') ON CONFLICT DO NOTHING");
        continue;
    }

    if($catnum==66){
        echo "[MOVE]: $domain ===> category_travel $cattext - $catnum -\n";
        $pos->QUERY_SQL("DELETE FROM $CategoryToClean WHERE sitename='$domain'");
        $pos->QUERY_SQL("INSERT INTO category_travel (sitename) VALUES('$domain') ON CONFLICT DO NOTHING");
        continue;
    }

    if($catnum==67){
        echo "[MOVE]: $domain ===> category_automobile_cars $cattext - $catnum -\n";
        $pos->QUERY_SQL("DELETE FROM $CategoryToClean WHERE sitename='$domain'");
        $pos->QUERY_SQL("INSERT INTO category_automobile_cars (sitename) VALUES('$domain') ON CONFLICT DO NOTHING");
        continue;
    }

    if($catnum==68){
        echo "[MOVE]: $domain ===> category_recreation_humor $cattext - $catnum -\n";
        $pos->QUERY_SQL("DELETE FROM $CategoryToClean WHERE sitename='$domain'");
        $pos->QUERY_SQL("INSERT INTO category_recreation_humor (sitename) VALUES('$domain') ON CONFLICT DO NOTHING");
        continue;
    }

    if($catnum==64){
        echo "[MOVE]: $domain ===> category_recreation_nightout $cattext - $catnum -\n";
        $pos->QUERY_SQL("DELETE FROM $CategoryToClean WHERE sitename='$domain'");
        $pos->QUERY_SQL("INSERT INTO category_recreation_nightout (sitename) VALUES('$domain') ON CONFLICT DO NOTHING");
        continue;
    }
    if($catnum==18){
        echo "[MOVE]: $domain ===> category_phishing $cattext - $catnum -\n";
        $pos->QUERY_SQL("DELETE FROM $CategoryToClean WHERE sitename='$domain'");
        $pos->QUERY_SQL("INSERT INTO category_phishing (sitename) VALUES('$domain') ON CONFLICT DO NOTHING");
        continue;
    }

    if($catnum==29){
        echo "[MOVE]: $domain ===> category_associations $cattext - $catnum -\n";
        $pos->QUERY_SQL("DELETE FROM $CategoryToClean WHERE sitename='$domain'");
        $pos->QUERY_SQL("INSERT INTO category_associations (sitename) VALUES('$domain') ON CONFLICT DO NOTHING");
        continue;
    }

    if($catnum==30){
        echo "[MOVE]: $domain ===> category_hobby_arts $cattext - $catnum -\n";
        $pos->QUERY_SQL("DELETE FROM category_spyware WHERE sitename='$domain'");
        $pos->QUERY_SQL("INSERT INTO category_hobby_arts (sitename) VALUES('$domain') ON CONFLICT DO NOTHING");
        continue;
    }

    if($catnum==31){
        echo "[MOVE]: $domain ===> category_financial $cattext - $catnum -\n";
        $pos->QUERY_SQL("DELETE FROM $CategoryToClean WHERE sitename='$domain'");
        $pos->QUERY_SQL("INSERT INTO category_financial (sitename) VALUES('$domain') ON CONFLICT DO NOTHING");
        continue;
    }
    if($catnum==23){
        echo "[MOVE]: $domain ===> category_alcohol $cattext - $catnum -\n";
        $pos->QUERY_SQL("DELETE FROM category_spyware WHERE sitename='$domain'");
        $pos->QUERY_SQL("INSERT INTO category_alcohol (sitename) VALUES('$domain') ON CONFLICT DO NOTHING");
        continue;
    }
    if($catnum==24){
        echo "[MOVE]: $domain ===> category_tobacco $cattext - $catnum -\n";
        $pos->QUERY_SQL("DELETE FROM $CategoryToClean WHERE sitename='$domain'");
        $pos->QUERY_SQL("INSERT INTO category_tobacco (sitename) VALUES('$domain') ON CONFLICT DO NOTHING");
        continue;
    }
    if($catnum==32){
        echo "[MOVE]: $domain ===> category_stockexchange $cattext - $catnum -\n";
        $pos->QUERY_SQL("DELETE FROM $CategoryToClean WHERE sitename='$domain'");
        $pos->QUERY_SQL("INSERT INTO category_stockexchange (sitename) VALUES('$domain') ON CONFLICT DO NOTHING");
        continue;
    }


    if($catnum==33){
        echo "[MOVE]: $domain ===> category_games $cattext - $catnum -\n";
        $pos->QUERY_SQL("DELETE FROM $CategoryToClean WHERE sitename='$domain'");
        $pos->QUERY_SQL("INSERT INTO category_games (sitename) VALUES('$domain') ON CONFLICT DO NOTHING");
        continue;
    }

    if($catnum==34){
        echo "[MOVE]: $domain ===> category_governments $cattext - $catnum -\n";
        $pos->QUERY_SQL("DELETE FROM $CategoryToClean WHERE sitename='$domain'");
        $pos->QUERY_SQL("INSERT INTO category_governments (sitename) VALUES('$domain') ON CONFLICT DO NOTHING");
        continue;
    }

    if($catnum==35){
        echo "[MOVE]: $domain ===> category_weapons $cattext - $catnum -\n";
        $pos->QUERY_SQL("DELETE FROM $CategoryToClean WHERE sitename='$domain'");
        $pos->QUERY_SQL("INSERT INTO category_weapons (sitename) VALUES('$domain') ON CONFLICT DO NOTHING");
        continue;
    }

    if($catnum==36){
        echo "[MOVE]: $domain ===> category_politic $cattext - $catnum -\n";
        $pos->QUERY_SQL("DELETE FROM category_spyware WHERE sitename='$domain'");
        $pos->QUERY_SQL("INSERT INTO category_politic (sitename) VALUES('$domain') ON CONFLICT DO NOTHING");
        continue;
    }

    if($catnum==45){
        echo "[MOVE]: $domain ===> category_jobsearch $cattext - $catnum -\n";
        $pos->QUERY_SQL("DELETE FROM $CategoryToClean WHERE sitename='$domain'");
        $pos->QUERY_SQL("INSERT INTO category_jobsearch (sitename) VALUES('$domain') ON CONFLICT DO NOTHING");
        continue;
    }

    if($catnum==54){
        echo "[MOVE]: $domain ===> category_religion $cattext - $catnum -\n";
        $pos->QUERY_SQL("DELETE FROM $CategoryToClean WHERE sitename='$domain'");
        $pos->QUERY_SQL("INSERT INTO category_religion (sitename) VALUES('$domain') ON CONFLICT DO NOTHING");
        continue;
    }
    if($catnum==56){
        echo "[MOVE]: $domain ===> category_filehosting $cattext - $catnum -\n";
        $pos->QUERY_SQL("DELETE FROM $CategoryToClean WHERE sitename='$domain'");
        $pos->QUERY_SQL("INSERT INTO category_filehosting (sitename) VALUES('$domain') ON CONFLICT DO NOTHING");
        continue;
    }

    if($catnum==60){
        echo "[MOVE]: $domain ===> category_finance_realestate $cattext - $catnum -\n";
        $pos->QUERY_SQL("DELETE FROM $CategoryToClean WHERE sitename='$domain'");
        $pos->QUERY_SQL("INSERT INTO category_finance_realestate (sitename) VALUES('$domain') ON CONFLICT DO NOTHING");
        continue;
    }

    if($catnum==61){
        echo "[MOVE]: $domain ===> category_society $cattext - $catnum -\n";
        $pos->QUERY_SQL("DELETE FROM $CategoryToClean WHERE sitename='$domain'");
        $pos->QUERY_SQL("INSERT INTO category_society (sitename) VALUES('$domain') ON CONFLICT DO NOTHING");
        continue;
    }
    if($catnum==63){
        echo "[MOVE]: $domain ===> category_blog $cattext - $catnum -\n";
        $pos->QUERY_SQL("DELETE FROM $CategoryToClean WHERE sitename='$domain'");
        $pos->QUERY_SQL("INSERT INTO category_blog (sitename) VALUES('$domain') ON CONFLICT DO NOTHING");
        continue;
    }
    if($catnum==71){
        echo "[MOVE]: $domain ===> category_downloads $cattext - $catnum -\n";
        $pos->QUERY_SQL("DELETE FROM $CategoryToClean WHERE sitename='$domain'");
        $pos->QUERY_SQL("INSERT INTO category_downloads (sitename) VALUES('$domain') ON CONFLICT DO NOTHING");
        continue;
    }

    if($catnum==83){
        echo "[MOVE]: $domain ===> category_downloads $cattext - $catnum -\n";
        $pos->QUERY_SQL("DELETE FROM $CategoryToClean WHERE sitename='$domain'");
        $pos->QUERY_SQL("INSERT INTO category_downloads (sitename) VALUES('$domain') ON CONFLICT DO NOTHING");
        continue;
    }

    if($catnum==84){
        if(preg_match("#heuristic#",$cattext)){continue;}
        echo "[MOVE]: $domain ===> category_audio_video $cattext - $catnum -\n";
        $pos->QUERY_SQL("DELETE FROM $CategoryToClean WHERE sitename='$domain'");
        $pos->QUERY_SQL("INSERT INTO category_audio_video (sitename) VALUES('$domain') ON CONFLICT DO NOTHING");
        continue;
    }

    if($catnum==85){
        echo "[MOVE]: $domain ===> category_webapps $cattext - $catnum -\n";
        $pos->QUERY_SQL("DELETE FROM $CategoryToClean WHERE sitename='$domain'");
        $pos->QUERY_SQL("INSERT INTO category_webapps (sitename) VALUES('$domain') ON CONFLICT DO NOTHING");
        continue;
    }

    if($catnum==86){
        echo "[MOVE]: $domain ===> category_proxy $cattext - $catnum -\n";
        $pos->QUERY_SQL("DELETE FROM $CategoryToClean WHERE sitename='$domain'");
        $pos->QUERY_SQL("INSERT INTO category_proxy (sitename) VALUES('$domain') ON CONFLICT DO NOTHING");
        continue;
    }

    if($catnum==88){
        echo "[MOVE]: $domain ===> category_publicite $cattext - $catnum -\n";
        $pos->QUERY_SQL("DELETE FROM $CategoryToClean WHERE sitename='$domain'");
        $pos->QUERY_SQL("INSERT INTO category_publicite (sitename) VALUES('$domain') ON CONFLICT DO NOTHING");
        continue;
    }

    if($catnum==89){
        echo "[MOVE]: $domain ===> category_isp $cattext - $catnum -\n";
        $pos->QUERY_SQL("DELETE FROM $CategoryToClean WHERE sitename='$domain'");
        $pos->QUERY_SQL("INSERT INTO category_isp (sitename) VALUES('$domain') ON CONFLICT DO NOTHING");
        continue;
    }

    if($catnum==97){
        echo "[MOVE]: $domain ===> category_isp $cattext - $catnum -\n";
        $pos->QUERY_SQL("DELETE FROM $CategoryToClean WHERE sitename='$domain'");
        $pos->QUERY_SQL("INSERT INTO category_isp (sitename) VALUES('$domain') ON CONFLICT DO NOTHING");
        continue;
    }
    if($catnum==101){
        echo "[MOVE]: $domain ===> category_mailing $cattext - $catnum -\n";
        $pos->QUERY_SQL("DELETE FROM $CategoryToClean WHERE sitename='$domain'");
        $pos->QUERY_SQL("INSERT INTO category_mailing (sitename) VALUES('$domain') ON CONFLICT DO NOTHING");
        continue;
    }

    if($catnum==103){
        echo "[MOVE]: $domain ===> category_dynamic $cattext - $catnum -\n";
        $pos->QUERY_SQL("DELETE FROM $CategoryToClean WHERE sitename='$domain'");
        $pos->QUERY_SQL("INSERT INTO category_dynamic (sitename) VALUES('$domain') ON CONFLICT DO NOTHING");
        continue;
    }

    if($catnum==110){
        echo "[MOVE]: $domain ===> category_webphone $cattext - $catnum -\n";
        $pos->QUERY_SQL("DELETE FROM $CategoryToClean WHERE sitename='$domain'");
        $pos->QUERY_SQL("INSERT INTO category_webphone (sitename) VALUES('$domain') ON CONFLICT DO NOTHING");
        continue;
    }

    if($catnum==111){
        echo "[MOVE]: $domain ===> category_meetings $cattext - $catnum -\n";
        $pos->QUERY_SQL("DELETE FROM $CategoryToClean WHERE sitename='$domain'");
        $pos->QUERY_SQL("INSERT INTO category_meetings (sitename) VALUES('$domain') ON CONFLICT DO NOTHING");
        continue;
    }

    if($catnum==112){
        echo "[MOVE]: $domain ===> category_webapps $cattext - $catnum -\n";
        $pos->QUERY_SQL("DELETE FROM $CategoryToClean WHERE sitename='$domain'");
        $pos->QUERY_SQL("INSERT INTO category_webapps (sitename) VALUES('$domain') ON CONFLICT DO NOTHING");
        continue;
    }
    if($catnum==113){
        echo "[MOVE]: $domain ===> category_webradio $cattext - $catnum -\n";
        $pos->QUERY_SQL("DELETE FROM $CategoryToClean WHERE sitename='$domain'");
        $pos->QUERY_SQL("INSERT INTO category_webradio (sitename) VALUES('$domain') ON CONFLICT DO NOTHING");
        continue;
    }

    if($catnum==114){
        echo "[MOVE]: $domain ===> category_movies $cattext - $catnum -\n";
        $pos->QUERY_SQL("DELETE FROM $CategoryToClean WHERE sitename='$domain'");
        $pos->QUERY_SQL("INSERT INTO category_movies (sitename) VALUES('$domain') ON CONFLICT DO NOTHING");
        continue;
    }
    echo "[SKIP] $domain ($catnum) === $cattext\n";
    $newf[]=$line;
}
@file_put_contents($targetpath,@implode("\n",$newf));


function movemkt(){
    $q=new postgres_sql();

    $LIKE="piwik.%";
    $table_destination="category_tracker";
    $cats[]="category_industry";
    $cats[]="category_shopping";
    $cats[]="category_science_computing";
    $cats[]="category_society";
    $cats[]="category_hobby_arts";

    foreach ($cats as $sourcetable) {
        $sql = "SELECT sitename FROM $sourcetable WHERE sitename LIKE '$LIKE'";
        $results = $q->QUERY_SQL($sql);
        while ($ligne = pg_fetch_assoc($results)) {
            $sitename = $ligne["sitename"];
            echo "[MOVE]: $sourcetable-> $table_destination == $sitename\n";
            $q->QUERY_SQL("DELETE FROM $sourcetable WHERE sitename='$sitename'");
            $q->QUERY_SQL("INSERT INTO $table_destination (sitename) VALUES('$sitename') ON CONFLICT DO NOTHING");
        }
    }


}

function advertising(){
    $q=new postgres_sql();

    $LIKE="%metrics.%";
    $table_destination="category_tracker";
    $cats[]="category_publicite";


    foreach ($cats as $sourcetable) {
        $sql = "SELECT sitename FROM $sourcetable WHERE sitename LIKE '$LIKE'";
        $results = $q->QUERY_SQL($sql);
        while ($ligne = pg_fetch_assoc($results)) {
            $sitename = $ligne["sitename"];
            if(preg_match("#^(ads|ad)\.#")){continue;}
            if(preg_match("#(affiliate|advert)#")){continue;}
            echo "[MOVE]: $sourcetable-> $table_destination == $sitename\n";
            $q->QUERY_SQL("DELETE FROM $sourcetable WHERE sitename='$sitename'");
            $q->QUERY_SQL("INSERT INTO $table_destination (sitename) VALUES('$sitename') ON CONFLICT DO NOTHING");
        }
    }


}
