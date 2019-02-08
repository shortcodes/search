<?php

namespace App\Console\Commands;

use Elasticsearch\Client;
use Illuminate\Console\Command;
use Shortcodes\Search\Traits\Elasticable;

class ReindexSearch extends Command
{
    protected $name = "search:reindex";
    protected $description = "Indexes all models to elasticsearch";

    public function __construct(Client $search)
    {
        parent::__construct();

        $this->search = $search;
    }

    public function handle()
    {

        foreach ($this->getModels() as $searchableModel) {

            $bar = $this->output->createProgressBar($searchableModel::count());

            $this->info("\nIndexing " . $searchableModel);

            $newModel = new $searchableModel();

            if ($this->search->indices()->exists(['index' => $newModel->getElasticIndex()])) {
                $this->search->indices()->delete(['index' => $newModel->getElasticIndex()]);
            }

            if ($mappings = $newModel->getElasticMapping()) {

                $params = [
                    'index' => $newModel->getElasticIndex(),
                    'body' => [
                        'mappings' => [
                            $newModel->getElasticType() => $newModel->getElasticMapping()
                        ]
                    ]
                ];

                $this->search->indices()->create($params);
            }

            foreach ($searchableModel::cursor() as $model) {

                $this->search->index([
                    'index' => $model->getElasticIndex(),
                    'type' => $model->getElasticType(),
                    'id' => $model->id,
                    'body' => $model->getElasticStructure(),
                ]);

                $bar->advance();
            }

            $bar->finish();
        }
        $this->info("\nDone all");
    }

    public function getModels($pathFilename = null)
    {
        $path = $pathFilename ? $pathFilename : app_path();
        $out = [];

        $results = scandir($path);
        foreach ($results as $result) {
            if ($result === '.' or $result === '..') {
                continue;
            }

            $filename = $path . '\\' . $result;

            if (is_dir($filename) && $result === "Models") {
                $out = array_merge($out, $this->getModels($filename));
                continue;
            } elseif (is_dir($filename)) {
                continue;
            }

            $appPhraseIndex = strpos(substr($filename, 0, -4), '\\app\\');

            $class = str_replace('\\app\\', '\\App\\', substr($filename, $appPhraseIndex, -4));
            if (in_array(Elasticable::class, class_uses($class))) {
                $out[] = $class;
            }
        }
        return $out;
    }
}