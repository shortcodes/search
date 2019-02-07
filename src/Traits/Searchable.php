<?php

namespace Shortcodes\Search\Traits;

use Illuminate\Http\Request;

trait Searchable
{
    protected function search(Request $request)
    {

        $page = $request->get('page', 0);
        $take = $request->get('length', 10);

        $query = self::query();

        if (method_exists($this, 'searchParameters')) {
            $query = self::searchParameters($query, $request);
        }

        return $query->paginate($take, ['*'], 'page', $page);

    }
}