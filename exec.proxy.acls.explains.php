<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.squid.acls.groups.inc");

if(isset($argv[1])){
    if($argv[1]=="--rule"){
        explain_rule($argv[2]);
        die();
    }
}

explain_all_rules();


function explain_rule($ID){
    if(is_null($ID)){
        return;
    }
    if(!is_numeric($ID)){
        return;
    }
    if($ID==0){
        return;
    }

    $q = new lib_sqlite("/home/artica/SQLITE/acls.db");
    if(!$q->FIELD_EXISTS("webfilters_sqacls","zExplain")){
        $q->QUERY_SQL("ALTER TABLE webfilters_sqacls ADD zExplain TEXT");
    }
    $ligne = $q->mysqli_fetch_array("SELECT * FROM webfilters_sqacls WHERE ID=$ID");
    $explain = base64_encode(EXPLAIN_THIS_RULE($ligne['ID'], $ligne["enabled"], $ligne["aclgroup"]));
    $q->QUERY_SQL("UPDATE webfilters_sqacls SET zExplain='$explain' WHERE ID=$ID");
    $aclgpid=intval($ligne['aclgpid']);

    $ligne1 = $q->mysqli_fetch_array("SELECT * FROM webfilters_sqacls WHERE ID=$aclgpid");
    $ID1=intval($ligne1['ID']);


    $explain1 = base64_encode(EXPLAIN_THIS_RULE($ID1, $ligne1["enabled"], $ligne1["aclgroup"]));
    $q->QUERY_SQL("UPDATE webfilters_sqacls SET zExplain='$explain1' WHERE ID=$ID1");

    if ($ID1==0 || intval($ligne['ID'])==0){
        $ligne3 = $q->mysqli_fetch_array("select * from webfilters_sqacllinks where gpid=$ID");
        $ligne4 = $q->mysqli_fetch_array("SELECT * FROM webfilters_sqacls WHERE ID={$ligne3['aclid']}");
        $explain4 = base64_encode(EXPLAIN_THIS_RULE($ligne4['ID'], $ligne4["enabled"], $ligne4["aclgroup"]));
        $q->QUERY_SQL("UPDATE webfilters_sqacls SET zExplain='$explain4' WHERE ID={$ligne4['ID']}");

        $ligne5 = $q->mysqli_fetch_array("SELECT * FROM webfilters_sqacls WHERE ID={$ligne4['aclgpid']}");
        $explain5 = base64_encode(EXPLAIN_THIS_RULE($ligne5['ID'], $ligne5["enabled"], $ligne5["aclgroup"]));
        $q->QUERY_SQL("UPDATE webfilters_sqacls SET zExplain='$explain5' WHERE ID={$ligne5['ID']}");
    }
}
function build_progress($text,$pourc){
    $echotext=$text;
    $echotext=str_replace("{reconfigure}", "Reconfigure", $echotext);
    echo "Starting......: ".date("H:i:s")." {$pourc}% $echotext (exec.squid.global.access.php)\n";
    $cachefile="/usr/share/artica-postfix/ressources/logs/sync.rules.progress";
    $array["POURC"]=$pourc;
    $array["TEXT"]=$text;
    @file_put_contents($cachefile, serialize($array));
    @chmod($cachefile,0755);
}
function explain_all_rules(){

    $q = new lib_sqlite("/home/artica/SQLITE/acls.db");
    if(!$q->FIELD_EXISTS("webfilters_sqacls","zExplain")){
        $q->QUERY_SQL("ALTER TABLE webfilters_sqacls ADD zExplain TEXT");
    }

    $results = $q->QUERY_SQL("SELECT * FROM webfilters_sqacls ORDER BY xORDER");

    $max=count($results);
    $i=0;

    foreach ($results as $index => $ligne) {
        $i++;
        $prc=round(($i/$max)*100,0);
        echo "Building {$ligne["aclname"]}\n";
        build_progress("Building {$ligne["aclname"]}",$prc);
        $MUTED = null;
        $ID = $ligne["ID"];
        $explain = base64_encode(EXPLAIN_THIS_RULE($ligne['ID'], $ligne["enabled"], $ligne["aclgroup"]));
        $q->QUERY_SQL("UPDATE webfilters_sqacls SET zExplain='$explain' WHERE ID=$ID");
        if(!$q->ok){
            echo $q->mysql_error."\n";
            build_progress("{failed}",110);
            die();
        }

    }
    build_progress("{done}",100);
}


   function EXPLAIN_THIS_RULE($ID,$enabled,$aclgroup){
        $acls=new squid_acls_groups();
        $FINAL=$acls->ACL_MULTIPLE_EXPLAIN($ID,$enabled,$aclgroup,null,true);
        $page="fw.proxy.acls.php";
        return  "<span id='explain-this-rule-$ID'  data='$page?explain-this-rule=$ID&enabled=$enabled&aclgroup=$aclgroup'>$FINAL</span>";
    }


