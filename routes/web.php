<?php

Route::group(['middleware' => ['web']], function () {
    Route::get('datatable', [\Endorbit\Datatable\Controllers\IndexController::class, 'index']);
    Route::post('users-operation', [\Endorbit\Datatable\Controllers\IndexController::class, 'usersOperation']);
    Route::post('users-delete', [\Endorbit\Datatable\Controllers\IndexController::class, 'usersDelete']);
    Route::post('users-save', [\Endorbit\Datatable\Controllers\IndexController::class, 'usersSave']);

    Route::post('datatableexportcsv', [\Endorbit\Datatable\Controllers\IndexController::class, 'downloadAsCsv']);
});

Route::any('datatableusers', [\Endorbit\Datatable\Controllers\IndexController::class, 'datatableusers']);

