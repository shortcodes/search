# search
Useful traits for different methods to search data (elasticsearch ,eloquent)

# configuring elasticsearch

In **config/services.php** you need to add
    
    'search' => [
        'enabled' => env('ELASTICSEARCH_ENABLED', false),
        'hosts' => explode(',', env('ELASTICSEARCH_HOSTS')),
    ],

And add variables to **.env**

    ELASTICSEARCH_ENABLED=true
    ELASTICSEARCH_HOSTS=localhost:9200
    ELASTICSEARCH_PREFIX=prefix #if you use more than 1 application on same elastic server

Every elastic model need to use **Elasticable** trait

To search in elasticsearch there is a need to use method searchParameters in model

    public function searchParameters($request) : array
    {
        //must return array with elasticsearch rules
        
        return [];
    }

# configuring eloquent search

Every eloquent type search model need to use **Searchable** trait and implement searchParameters method 

    public function searchParameters($query,$request) : array
    {
        //must return queryBuilder class
        
        return $query;
    }
