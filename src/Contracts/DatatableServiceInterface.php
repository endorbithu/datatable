<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 2022. 01. 04.
 * Time: 15:19
 */

namespace Endorbit\Datatable\Contracts;


use Illuminate\Database\Eloquent\Model;

interface DatatableServiceInterface extends DatatableConfigInterface
{
    public function initTable(string $tableIdName, string $type, $data, array $header = [], $eloquentClass = null);

    public function render($dataTable = null): string;

    public function setData($datas);

    public function getData();

    public function getTableIdName();

    public function setTableIdName($tableIdName);

    public function setHeader(array $headers);

    public function getCsvFiles(): array;



    //public function getSelectImage();

    //public function setSelectImage($selectImage);

    //public function getSelectRightSide();

    // public function setSelectRightSide($selectRightSide);

    //public function getSelectSubId();

    //public function setSelectSubId($selectSubId);


    //public function setPaging($paging);

    public function getCsv();

    public function setCsv($csv);

    public function getAjaxUrl();

    public function setAjaxUrl($ajaxUrl);

    //public function getSelectedToSession();

    //public function setSelectedToSession($selectedToSession);


    public function getToolbarAtBottom();

    public function getChooseType();

    public function setChooseType($chooseType);


    //public function setSelectAll($isSelectAll);


    //public function setIsSelect2Char($isSelect2Char);

    //public function getIsSelect2Char();


    //public function setAjax($isAjax);

    //public function getAjax();

    public function hasTriggeredBackgroundCsvGenerating(string $controllerClass, string $actionMethod, $data, $request);

    public function getFilteredRows($eloquent, array $params, $onlyIds = false);

    public function getFilteredIds($eloquent, array $params);

    public function getModifiedValue($value, $operator, $givenValue);

    public function getResponseForDatatable($data, $eloquentClass = null);

    /**
     * @return mixed
     */
    public function setEloquentClass(?string $eloquentClass): void;

    /**
     * @param mixed $eloquentClass
     */
    public function getEloquentClass(): ?string;

    public function setSearch($search);

    public function setForm($form);

    public function getForm();


}
