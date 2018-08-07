<?php

namespace Gesirdek\Http\Controllers;

use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Modules\Core\Entities\User;


class ApiController extends Controller
{

    /**
     * Get component translations for vue
     * @param $locale
     * @return array
     */
    public function getComponentTranslations($locale)
    {
        $return = [];
        $escapes = ['.','..'];
        foreach (scandir(resource_path('lang/'.$locale)) as $component){
            if(in_array($component,$escapes) || substr($component,0,10)!=='Component-')
                continue;
            $cname = substr($component,0,-4);
            $return[substr($cname,10)]= trans($cname,[],$locale);
        }
        return $return;
    }

    /**
     * Change language
     * @param $lang
     * @return int
     */
    public function setLanguage($lang){
        if(Auth::check()){
            //TODO will it work? test this
            $user = Auth::user();
            $user->lang = $lang;
            $user->save();
        }else{
            setcookie('locale',$lang);
        }
        return 1;
    }
}
