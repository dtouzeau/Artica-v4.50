<?php
include_once(dirname(__FILE__)."/ressources/class.mysql.squid.builder.php");
    if($argv[1]=="--cat"){
        getCatz($argv[2]);
        die();
    }
function getCatz($cat){
$sitenametest   = url_decode_special_tool(trim(strtolower($cat)));
    $sitenametest   = trim($sitenametest);
    $action         = null;
    if($sitenametest==null){return;}
    $category       = 0;
    $sitenametest   = str_replace(";",".",$sitenametest);
    $sitenametest   = str_replace("?","",$sitenametest);
    $srnprovider    = null;
    if(preg_match("#(.+?)VERBOSE#i", $sitenametest,$re)){
        $GLOBALS["VERBOSE"]=true;
        $GLOBALS["NOCACHE"]=true;
        $sitenametest=trim($re[1]);
    }
    $_SESSION["UFDBCAT_TEST_CATEGORIES"]=$sitenametest;
    $class="alert-success";

    $catz=new mysql_catz();
    $categories_descriptions=$catz->categories_descriptions();
    unset($_SESSION["TEST_CATEGORIES"]);
    $time_start = $catz->microtime_float();
    $category = $catz->GET_CATEGORIES($sitenametest);
    $error_text=null;$cached=null;
    VERBOSE("catz->GET_CATEGORIES($sitenametest)=$category",__LINE__);

    $actionsTR["PASS"]="{allow}";
    $actionsTR["WHITE"]="{whitelisted}";
    $actionsTR["LICENSE_ERROR"]="{license_error}";
    $actionsTR["REAFFECTED"]="{reafected}";
    $actionsTR[null]="{allow}";

    if(!is_array($catz->THESHIELD_MAIN)){
        $error_text="&nbsp;-&nbsp;<strong>{connection_error}</strong>";
    }

    if($catz->THESHIELD_MAIN["error"]<>null){
        $error_text="<strong>&nbsp;-&nbsp;{error}: {$catz->THESHIELD_MAIN["error"]}</strong>";
    }
    $ACTION=$catz->THESHIELD_MAIN["ACTION"];
    if(isset($catz->THESHIELD_MAIN["CACHED"])){
        $cached="&nbsp;<small><strong>{cached}</strong></small>";
    }

    $srnprovider=$ACTION;
    if(!isset($actionsTR[$ACTION])){
        $action="&nbsp;&raquo;&raquo;&raquo;&nbsp;{block}!&nbsp;$error_text";
    }else{
        $action="&nbsp;&raquo;&raquo;&raquo;&nbsp;{$actionsTR[$ACTION]}$cached&nbsp;$error_text";
    }




    if(count($catz->CategoriesList)>0){
        $action=null;

        if(intval($catz->CategoriesList[1])==1){
            $catz->CategoriesList[2]=0;
            $catz->CategoriesList[3]=0;
            $class="alert-danger";
            $action="&nbsp;&raquo;&raquo;&raquo;&nbsp;{block}!";
        }

        if(intval($catz->CategoriesList[2])==1){
            $action="&nbsp;&raquo;&raquo;&raquo;&nbsp;{allow}";
        }
        if(intval($catz->CategoriesList[3])==1){
            $action="$action&nbsp;&raquo;&raquo;&raquo;&nbsp;{no_cache}";
        }
    }

    $time_end = $catz->microtime_float();
    $TimeExec = round($time_end - $time_start,3);

    $catz->events[]="Final ($category) ({$TimeExec}ms) Query ({$catz->TimeExec}ms)";
    $_SESSION["TEST_CATEGORIES"]["SITENAME"]=$sitenametest;
    $_SESSION["TEST_CATEGORIES"]["category_id"]=intval($category);

    VERBOSE("Category: $category",__LINE__);


    if(isset($categories_descriptions[$category])){
        VERBOSE("Category: $category (in array) OK",__LINE__);
        $_SESSION["TEST_CATEGORIES"]["RESULTS"]=$categories_descriptions[$category];
    }else{
        VERBOSE("Category: $category (not in array) -- rebuild",__LINE__);
        $libmem=new lib_memcached();
        $GLOBALS["categories_descriptions"]=array();
        $libmem->saveKey("categories_descriptions", serialize(array()),1600);
        $categories_descriptions=$catz->categories_descriptions();
        $_SESSION["TEST_CATEGORIES"]["RESULTS"]=$categories_descriptions[$category];

    }
    $_SESSION["TEST_CATEGORIES"]["EVENTS"]=$catz->events;
    $_SESSION["TEST_CATEGORIES"]["PROVIDER"]=$srnprovider;

    $provider_text=null;
    if(isset($_SESSION["TEST_CATEGORIES"]["PROVIDER"])){
        $provider=$_SESSION["TEST_CATEGORIES"]["PROVIDER"];
        if($provider<>null){
            $provider_text="&nbsp;<small style='color:rgb(60, 118, 61)'>( The Shields: $provider )</small>";
        }
    }


    if(!isset($_SESSION["TEST_CATEGORIES"]["RESULTS"])){$class="alert-danger";}
    if($_SESSION["TEST_CATEGORIES"]["category_id"]==0){$class="alert-danger";}
    if(!isset($_SESSION["TEST_CATEGORIES"]["RESULTS"]["categoryname"])){$_SESSION["TEST_CATEGORIES"]["RESULTS"]["categoryname"]="{unknown}";}
    $tpl=new template_admin();
    $html[]="<div class='alert $class'><H1>";
    $html[]="<img src='{$_SESSION["TEST_CATEGORIES"]["RESULTS"]["category_icon"]}' align=left>&nbsp;{$_SESSION["TEST_CATEGORIES"]["SITENAME"]}$action</h1>";

    if(!isset($_SESSION["TEST_CATEGORIES"]["RESULTS"]["category_description"])){$_SESSION["TEST_CATEGORIES"]["RESULTS"]["category_description"]=null;}
    $js="Loadjs('fw.ufdb.categories.php?category-js={$_SESSION["TEST_CATEGORIES"]["category_id"]}')";
    $category_description=$tpl->_ENGINE_parse_body($_SESSION["TEST_CATEGORIES"]["RESULTS"]["category_description"]);
    $category_text=$tpl->td_href($_SESSION["TEST_CATEGORIES"]["RESULTS"]["categoryname"],$category_description,$js);

    if(isset($_SESSION["TEST_CATEGORIES"]["RESULTS"])){
        $html[]="<H2>{category}:$category_text{$provider_text}</H2>";
        $html[]="<div><i>{$_SESSION["TEST_CATEGORIES"]["RESULTS"]["category_description"]}</i></div><hr>";
    }
    foreach ($_SESSION["TEST_CATEGORIES"]["EVENTS"] as $line){
        $html[]="<div><small>$line</small></div>";

    }
    echo $_SESSION["TEST_CATEGORIES"]["RESULTS"]["categoryname"];
    //echo $tpl->_ENGINE_parse_body(@implode("\n", $html)."</div><script>NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS)."</script>");
}
