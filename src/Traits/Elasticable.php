<?php

namespace Shortcodes\Search\Traits;

use Shortcodes\Search\Observers\ElasticObserver;

trait Elasticable
{
    public static function bootElasticable()
    {
        static::observe(ElasticObserver::class);
    }

    public function getElasticIndex()
    {
        return env('ELASTICSEARCH_PREFIX') . $this->getTable();
    }

    public function getElasticType()
    {
        return 'default';
    }

    public function reindex()
    {
        $client = resolve('ElasticsearchClient');

        $client->index([
            'index' => $this->getElasticIndex(),
            'type' => $this->getElasticType(),
            'id' => $this->getKey(),
            'refresh' => 'wait_for',
            'body' => $this->getElasticStructure(),
        ]);
    }

    public function getElasticStructure()
    {
        return $this->toArray();
    }

    public function getElasticMapping()
    {
        return null;
    }

    protected function search($request, array $body = [], $fieldsToQuery = null)
    {
        $instance = new static();

        $client = resolve('ElasticsearchClient');
        $page = $request->get('page', 1);
        $length = $request->get('length', 9999);

        $parameters = [
            'index' => $instance->getElasticIndex(),
            'size' => $length,
            'from' => $length * ($page - 1),
            'type' => $instance->getElasticType(),
            'body' => array_merge_recursive($this->getDefaultBody($request, $fieldsToQuery), $body),
        ];

        return $this->buildResults($client->search($parameters), $length, $page);
    }

    private function buildResults(array $items, $length, $page)
    {
        try {

            $filteredItems = collect(array_pluck($items['hits']['hits'], '_source') ?: [])->transform(function ($item) {
                return (object)$item;
            });

            return [
                'data' => $filteredItems->isNotEmpty()?self::whereIn('id', $filteredItems->pluck('id'))->orderByRaw('FIELD (id, '.$filteredItems->pluck('id')->implode(',').')')->get():collect([]),
                'meta' => [
                    'total' => (int)$items['hits']['total'],
                    'pages' => (int)ceil($items['hits']['total'] / $length),
                    'current_page' => (int)$page,
                ]
            ];

        } catch (\Exception $e) {
            throw $e;
        }
    }


    private function getDefaultBody($request, $fieldsToQuery)
    {
        $search = [];

        if ($this->elasticQuerySearch && $query = $request->get('query')) {
            $search['query']['bool']['must'][] = [
                'query_string' => [
                    'fields' => $fieldsToQuery ?? $this->elasticQuerySearch,
                    'query' => "*" . $query . "*"
                ]
            ];
        }

        if (($sortBy = $request->get('sort_by')) && ($sortDirection = $request->get('sort_direction'))) {
            $search['sort'][][$sortBy]['order'] = $sortDirection;
        }

        if ($request->get('active') !== null) {
            $search['query']['bool']['must'][] = [
                'match' => [
                    'active' => $request->get('active')
                ]
            ];
        }

        if (method_exists($this, 'searchParameters')) {
            $search = array_merge($search, $this->searchParameters($request));
        }

        return $search;
    }

}