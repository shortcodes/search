<?php

namespace Shortcodes\Search\Observers;

use Elasticsearch\Client;

class ElasticObserver
{
    private $elasticsearch;

    public function __construct(Client $elasticsearch)
    {
        $this->elasticsearch = $elasticsearch;
    }

    public function saved($model)
    {
        $this->elasticsearch->index([
            'index' => $model->getElasticIndex(),
            'type' => $model->getElasticType(),
            'id' => $model->id,
            'body' => $model->getElasticStructure(),
        ]);
    }

    public function deleted($model)
    {
        $this->elasticsearch->delete([
            'index' => $model->getElasticIndex(),
            'type' => $model->getElasticType(),
            'id' => $model->id,
        ]);
    }
}