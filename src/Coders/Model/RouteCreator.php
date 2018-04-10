<?php
/**
 * Created by PhpStorm.
 * User: Ertan Çoban
 * Date: 10.04.2018
 * Time: 10:02
 */

namespace Gesirdek\Coders\Model;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;

class RouteCreator{
    protected $moduleNames;
    
    
    public function __construct($moduleNames)
    {
        $this->moduleNames = $moduleNames;
        $this->createRoutes();
    }
    
    protected function createRoutes()
    {
        foreach ($this->moduleNames as $moduleName){
            Artisan::call('module:make', [title_case($moduleName)]);
        }

        foreach ($this->moduleNames as $moduleName){
            $this->createFile(title_case($moduleName));
        }
    }

    protected function createFile($modulename)
    {
        $file = base_path('Modules\\'.$modulename.'\\Http\\routes.php');
        Storage::put($file, $this->createContents($modulename));
    }

    protected function createContents($modulename)
    {
        $body = "";
        $body .= "Route::group(['middleware' => 'jwt.auth', 'prefix' => 'api/".$modulename."', 'namespace' => 'Modules\\".title_case($modulename)."\Http\Controllers'], function()\n";
        $body .= "{\n";
        $body .= "{{routebody}}\n";
        $body .= "});";

        return $body;
    }
}