<?php

Route::group(['middleware' => ['web']], function () {
    Route::get('datatable', [\DelocalZrt\Datatable\Controllers\IndexController::class, 'index']);
    Route::post('users-operation', [\DelocalZrt\Datatable\Controllers\IndexController::class, 'usersOperation']);
    Route::post('users-delete', [\DelocalZrt\Datatable\Controllers\IndexController::class, 'usersDelete']);
    Route::post('users-save', [\DelocalZrt\Datatable\Controllers\IndexController::class, 'usersSave']);

    Route::post('datatableexportcsv', [\DelocalZrt\Datatable\Controllers\IndexController::class, 'downloadAsCsv']);
});

Route::any('datatableusers', [\DelocalZrt\Datatable\Controllers\IndexController::class, 'datatableusers']);

