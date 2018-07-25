<?php
Route::group(['prefix'=>'gesirdek'], function()
{
    /**
     * Get component translations for vue
     * @param $locale
     * @return array
     */
    Route::get('get-component-translations/{locale}', function ($locale) {
        $return = [];
        $escapes = ['.','..'];
        foreach (scandir(resource_path('lang/'.$locale)) as $component){
            if(in_array($component,$escapes) || substr($component,0,10)!=='Component-')
                continue;
            $cname = substr($component,0,-4);
            $return[substr($cname,10)]= trans($cname,[],$locale);
        }
        return $return;
    });

    /**
     * Change language
     * @param $lang
     * @return int
     */
    Route::post('set-language/{lang}', function ($lang) {
        if (\Illuminate\Support\Facades\Auth::check()) {
            $user = User::find(\Illuminate\Support\Facades\Auth::user()->id);
            $user->lang = $lang;
            $user->save();
        } else {
            setcookie('locale', $lang);
        }
        return 1;

    });
});