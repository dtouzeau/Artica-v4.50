<?php
ini_set('display_errors', 1);	ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);
include_once(dirname(__FILE__).'/ressources/class.templates.inc');
include_once(dirname(__FILE__).'/ressources/externals/vendor/autoload.php');


use Elasticsearch\ClientBuilder;

$hosts = ['192.168.1.144:9200'];



$clientBuilder = ClientBuilder::create();   // Instantiate a new ClientBuilder
$clientBuilder->setHosts($hosts);           // Set the hosts
$clientBuilder->setRetries(2);
$client = $clientBuilder->build();

//zdate,src,dst,l4proto,category,categoryint,familysite,download,upload

$params = [
    'index' => 'deep_inspection_localhost',
    'body' => [
        'settings' => [
            'number_of_shards' => 3,
            'number_of_replicas' => 2
        ],
        'mappings' => [
            '_source' => [
                'enabled' => true
            ],
            'properties' => [
                'zdate' => ['type' => 'date'],
                'src' => ['type' => 'ip'],
                'dst' => ['type' => 'ip'],
                'l4proto' => ['type' => 'integer'],
                'category' => ['type' => 'keyword'],
                'categoryint' => ['type' => 'integer'],
                'familysite' => ['type' => 'keyword'],
                'download' => ['type' => 'long'],
                'upload' => ['type' => 'long'],
            ]
        ]
    ]
];

try{
    $response = $client->indices()->create($params);

} catch (Exception $e) {

    print 'Exception: ' . get_class($e) . '<br>';
    print 'Message: ' . $e->getMessage() . '<br>';
}



echo "Finish...\n";
print_r($response);

