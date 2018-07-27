<?php
Route::prefix('gesirdek')->name('gesirdek.')->group(function () {
    Route::get('get-component-translations/{locale}',"\Gesirdek\Http\Controllers\ApiController@getComponentTranslations")->name('get-component-translations');
    Route::post('set-language/{lang}','\Gesirdek\Http\Controllers\ApiController@setLanguage')->name('set-language');
});