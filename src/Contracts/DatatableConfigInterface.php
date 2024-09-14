<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 2022. 01. 04.
 * Time: 15:19
 */

namespace Endorbit\Datatable\Contracts;


interface DatatableConfigInterface
{
    public function getType();

    public function setType($type);

    public function setTypeToSelect();

    public function setTypeToCheckbox();

    public function disableCsv();

    public function setToolbarAtBottom($toolbarAtBottom = true);

    public function enableChooseType();

    public function disableChooseType();

    public function enableFormTag();

    public function disableFormTag();

    public function setSelectedIds($selectedIds);

    public function getSelectedIds();

    public function setOperations($operation);

    public function getOperations();

    public function setAction(array $actions);

    public function addAction(array $actions, ?string $key = null);

    public function deleteAction(string $key);

    public function setOrder($order);

    public function getDescription(): string;

    public function setDescription(string $description);


}
