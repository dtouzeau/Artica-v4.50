<?php
include_once(dirname(__FILE__).'/class.templates.inc');
include_once(dirname(__FILE__).'/externals/vendor/autoload.php');
use Elasticsearch\ClientBuilder;

class ElastiClient{
    private $database       = null;


    public function __construct($database=null){
        $this->database=$database;


    }


    public function CreateNDPITemplate(){


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
    }


}