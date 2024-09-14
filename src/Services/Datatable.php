<?php

namespace Endorbit\Datatable\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Facade;

/**
 * @method static \Endorbit\Datatable\Contracts\DatatableServiceInterface create(string $tableIdName, string $type, $data, array $header = [], $eloquentClass = null)
 * @method static \Endorbit\Datatable\Contracts\DatatableServiceInterface operate($value, $operator, $givenValue)
 * @method static \Endorbit\Datatable\Contracts\DatatableServiceInterface getFilteredRows($eloquent, array $params, $onlyIds = false)
 * @method static \Endorbit\Datatable\Contracts\DatatableServiceInterface getFilteredIds($eloquent, array $params)
 * @method static \Endorbit\Datatable\Contracts\DatatableServiceInterface getResponseForDatatable($data)
 * @method static \Endorbit\Datatable\Contracts\DatatableServiceInterface hasTriggeredBackgroundCsvGenerating(string $controllerClass, string $actionMethod, $data, Request $request);
 */
class Datatable extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'datatable';
    }
}
