<?php
/**
 * Created by PhpStorm.
 * User: Ertan Çoban
 * Date: 10.04.2018
 * Time: 10:02
 */

namespace Gesirdek\Coders\Model;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
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
            Artisan::call('module:make', [studly_case($moduleName)]);
        }

        foreach ($this->moduleNames as $moduleName){
            $this->createFile($moduleName);
        }
    }

    protected function createFile($modulename)
    {
        $file = base_path('Modules\\'.studly_case($modulename).'\\Http\\routes.php');
        File::put($file, $this->createContents($modulename));
    }

    protected function createContents($modulename)
    {
        $body = "<?php\n";
        $body .= "Route::group(['middleware' => 'jwt.auth', 'prefix' => 'api/".$modulename."', 'namespace' => 'Modules\\".studly_case($modulename)."\Http\Controllers'], function()\n";
        $body .= "{\n";
        $body .= "{{routebody}}\n";
        $body .= "});";

        return $body;
    }


    public static function addContent($modulename, $table){
        if($modulename != "app"){
            $file = base_path('Modules\\'.studly_case($modulename).'\\Http\\routes.php');
            $contents = File::get($file);
            $contents = str_replace('{{routebody}}', "Route::apiResource('".kebab_case(str_singular($table))."', '".studly_case(str_singular($table))."Controller');\n{{routebody}}", $contents);
            File::put($file, $contents);
        }
    }

    public static function clearExtras($modulename){
        $file = base_path('Modules\\'.studly_case($modulename).'\\Http\\routes.php');
        $contents = File::get($file);
        $contents = str_replace('{{routebody}}', '', $contents);
        File::put($file, $contents);
    }


}