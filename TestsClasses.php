<?php
ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);

include_once(dirname(__FILE__)."/ressources/class.categories.mem.inc");
FILL_CATEGORIES_MEM();

foreach ($GLOBALS["categories_descriptions"] as $CatID=>$main){

    echo "OfficialsCategories[$CatID] = NewCategory($CatID,\"{$main["categorykey"]}\",\"{$main["categoryname"]}\",\"{$main["categorytable"]}\",\"{$main["category_description"]}\")\n";
}