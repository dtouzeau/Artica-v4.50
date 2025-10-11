<?php

function TranslateTables():array{
    $q = new postgres_sql();

    $results = $q->QUERY_SQL("SELECT * FROM rbl_tables");
    while ($ligne = pg_fetch_assoc($results)) {
        $response = $ligne["response"];
        $description = $ligne["description"];
        $tablename = $ligne["tablename"];
        $items = $ligne["items"];
        VERBOSE("tableid=$response, tablename=$tablename",__LINE__);
        $DB[$response] = array("tablename" => $tablename, "title" => $description,
            "response" => KeyToIP($response), "items" => $items);
    }

    $DB["1270002"]["tablename"] = "rbl_blacklists";
    $DB["1270002"]["title"] = "{blacklist}";
    $DB["1270002"]["response"] = "127.0.0.2";
    $DB["1270002"]["noremove"] = "true";
    $DB["1270009"]["tablename"] = "rbl_whitelists";
    $DB["1270009"]["title"] = "{whitelist}";
    $DB["1270009"]["response"] = "127.0.0.9";
    $DB["1270009"]["noremove"] = "true";
    return $DB;
}

function KeyToIP($numbers) {
    $last=intval(str_replace("1270","", $numbers));
    return "127.0.0.$last";
}
function GetTableArray($tableid=null){
    if(!is_null($tableid)){
        $_SESSION["RBLDNSD"]["DB-CHOOSE"]=$tableid;
    }else{
        if(isset($_SESSION["RBLDNSD"]["DB-CHOOSE"])) {
            $tableid = $_SESSION["RBLDNSD"]["DB-CHOOSE"];
        }
    }

    $DBS=TranslateTables();

    if(!isset($DBS[$tableid])){
        $tableID2=TableIDTransform($tableid);
        VERBOSE("TableIDTransform: $tableid == $tableID2 = {$DBS[$tableID2]["tablename"]}",__LINE__);
        return $DBS[$tableID2];
    }
    VERBOSE("$tableid is OK",__LINE__);
    return $DBS[$tableid];

}

function GetTableName($tableid=null){
    if(!is_null($tableid)){
        $_SESSION["RBLDNSD"]["DB-CHOOSE"]=$tableid;
    }else{
        if(isset($_SESSION["RBLDNSD"]["DB-CHOOSE"])) {
            $tableid = $_SESSION["RBLDNSD"]["DB-CHOOSE"];
        }
    }
    if(intval($tableid)==0){
        $tableid="1270002";
    }

    $DBS=TranslateTables();
    if($tableid=="1270002"){
        return "rbl_blacklists";
    }
    if($tableid=="1270009"){
        return "rbl_whitelists";
    }
    if(!isset($DBS[$tableid])){
        $tableID2=TableIDTransform($tableid);
        VERBOSE("TableIDTransform: $tableid == $tableID2 = {$DBS[$tableID2]["tablename"]}",__LINE__);
        return $DBS[$tableID2]["tablename"];
    }

    return $DBS[$tableid]["tablename"];
}

function TableIDTransform(int $n) : int {
    $s = (string)$n;
    if (strlen($s) <= 3) {
        return $n;
    }

    $prefix = substr($s, 0, 3);
    $rest   = substr($s, 3);
    $len    = strlen($rest);

    if ($len <= 3) {
        // pad to 4 chars, left with '0'
        $rest = str_pad($rest, 4, '0', STR_PAD_LEFT);
    } else {
        // longer than 3: collapse any leading zeros to exactly one
        if ($rest[0] === '0') {
            $rest = ltrim($rest, '0');
            $rest = '0' . $rest;
        }
    }

    return (int)($prefix . $rest);

}
