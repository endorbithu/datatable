<?php

namespace DelocalZrt\Datatable\Services;


use DelocalZrt\Datatable\Contracts\DatatableServiceInterface;

class DatatableServiceSelector
{
    public function create(string $tableIdName, string $type, $data, array $header = [], $eloquentClass = null): DatatableServiceInterface
    {
        return app(DatatableServiceInterface::class)->initTable($tableIdName, $type, $data, $header, $eloquentClass);
    }

    public function operate($value, $operator, $givenValue)
    {
        return app(DatatableServiceInterface::class)->getModifiedValue($value, $operator, $givenValue);
    }

    public function getFilteredRows($eloquent, array $params, $onlyIds = false)
    {
        return app(DatatableServiceInterface::class)->getFilteredRows($eloquent, $params, $onlyIds);
    }

    public function getFilteredIds($eloquent, array $params)
    {
        return app(DatatableServiceInterface::class)->getFilteredIds($eloquent, $params);
    }


    public function getResponseForDatatable($data, $eloquentClass = null)
    {
        return app(DatatableServiceInterface::class)->getResponseForDatatable($data, $eloquentClass);
    }


    public function hasTriggeredBackgroundCsvGenerating(string $controllerClass, string $actionMethod, $data, $request)
    {
        return app(DatatableServiceInterface::class)->hasTriggeredBackgroundCsvGenerating($controllerClass, $actionMethod, $data, $request);
    }

}
