<?php
//SP119
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
if(isset($_GET["explain"])){explain();exit;}
page();
function page(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $html=$tpl->page_header("{AD_GROUP_SEARCH}","fas fa-users-viewfinder","{AD_GROUP_SEARCH_INTRO}","$page?explain=yes","ad-query-users-groups","progress-unbound-restart",false,"table-loader-dns-servers");
    if(isset($_GET["main-page"])){
        $tpl=new template_admin("{AD_GROUP_SEARCH}",$html);
        echo $tpl->build_firewall();
        return;
    }
    $tpl=new templates();
    echo $tpl->_ENGINE_parse_body($html);
}

function explain(){
    $tpl=new template_admin();
    $explain=$tpl->_ENGINE_parse_body("{AD_GROUP_SEARCH_EXPLAIN}");
    $conn_link="<a href='https://wiki.articatech.com/en/rds-proxy/active-directory' target='_NEW'><b>{more} {info}</b></a>";
    $conn_img="<img style='width: 50%' src='img/connection-method.png' />";
    $ad_agent_link="<a href='https://wiki.articatech.com/en/proxy-service/authentication/ad-agent' target='_NEW'><b>{more} {info}</b></a>";
    $ad_agent_img="<img style='width: 50%' src='img/adagent-method.png' />";
    $explain=str_replace("%conn_help_link",$conn_link,$explain);
    $explain=str_replace("%img_conn",$conn_img,$explain);
    $explain=str_replace("%ad_agent_help_link",$ad_agent_link,$explain);
    $explain=str_replace("%img_ad_agent",$ad_agent_img,$explain);
    $html[]="<div style='margin-left:10px;margin-top:5px'>";
    $html[]="<p>$explain</p>";
    $html[]="</div>";
    echo $tpl->_ENGINE_parse_body($html);
}
