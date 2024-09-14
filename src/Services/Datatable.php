<?php

namespace DelocalZrt\Datatable\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Facade;

/**
 * @method static \DelocalZrt\Datatable\Contracts\DatatableServiceInterface create(string $tableIdName, string $type, $data, array $header = [], $eloquentClass = null)
 * @method static \DelocalZrt\Datatable\Contracts\DatatableServiceInterface operate($value, $operator, $givenValue)
 * @method static \DelocalZrt\Datatable\Contracts\DatatableServiceInterface getFilteredRows($eloquent, array $params, $onlyIds = false)
 * @method static \DelocalZrt\Datatable\Contracts\DatatableServiceInterface getFilteredIds($eloquent, array $params)
 * @method static \DelocalZrt\Datatable\Contracts\DatatableServiceInterface getResponseForDatatable($data)
 * @method static \DelocalZrt\Datatable\Contracts\DatatableServiceInterface hasTriggeredBackgroundCsvGenerating(string $controllerClass, string $actionMethod, $data, Request $request);
 */
class Datatable extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'datatable';
    }
}
