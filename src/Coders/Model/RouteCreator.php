<?php
/**
 * Created by PhpStorm.
 * User: Ertan Çoban
 * Date: 10.04.2018
 * Time: 10:02
 */

namespace Gesirdek\Coders\Model;

use Gesirdek\Meta\Blueprint;
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

        $this->createMainApiRoutes();
        $this->createRoutesJs();

        foreach ($this->moduleNames as $moduleName){
            $this->createFile($moduleName);
        }


    }

    protected function createMainApiRoutes(){
        $file = base_path('routes\\api.php');
        File::put($file, $this->createContents());
    }

    protected function createRoutesJs(){
        $file = base_path('resources\\assets\\js\\router\\routes.js');
        File::put($file, $this->createJsContents());
    }

    protected function createFile($modulename)
    {
        $file = base_path('Modules\\'.studly_case($modulename).'\\Http\\routes.php');
        File::put($file, $this->createModuleContents($modulename));

        $file = base_path('resources\\assets\\js\\router\\routes.js');
        $contents = File::get($file);
        $contents = str_replace('/*{{modulus}}*/', "\t{ path: '/".studly_case($modulename)."', name: '".studly_case($modulename)."', component: ".studly_case($modulename).",
        children: [\n/*{{module_content_".$modulename."}}*/\n\t\t]},\n/*{{modulus}}*/", $contents);
        File::put($file, $contents);

    }

    protected function createModuleContents($modulename)
    {
        $body = "<?php\n";
        $body .= "Route::group(['middleware' => 'jwt.auth', 'prefix' => 'api/".$modulename."', 'namespace' => 'Modules\\".studly_case($modulename)."\Http\Controllers'], function()\n";
        $body .= "{\n";
        $body .= "{{routebody}}\n";
        $body .= "});";

        return $body;
    }

    protected function createContents()
    {
        $body = "<?php\n";
        $body .= "Route::group([/*'middleware' => 'jwt.auth'*/], function()\n";
        $body .= "{\n";
        $body .= "/*{{routebody}}*/\n";
        $body .= "});";

        return $body;
    }

    protected function createJsContents()
    {
        $body = "/*{{imports}}*/\n\n";
        $body .= "export default \n";
        $body .= "[\n";
        $body .= "\t{ path: '/', name: 'Home', component: Home },\n";
        $body .= "\t{ path: \"*\",name:'404' , component: PageNotFound },\n";
        $body .= "/*{{modulus}}*/\n";
        $body .= "]";

        return $body;
    }


    public static function addContent(Blueprint $blueprint){
        if(str_singular($blueprint->table()) != $blueprint->table()){
            if($blueprint->getModuleName() != "app"){
                $file = base_path('Modules\\'.$blueprint->getModuleStudlyCase().'\\Http\\routes.php');
                $contents = File::get($file);
                $contents = str_replace('{{routebody}}', "Route::apiResource('".str_replace('_','-',str_singular($blueprint->table()))."', '".studly_case(str_singular($blueprint->table()))."Controller');\n{{routebody}}", $contents);
                File::put($file, $contents);

                $file = base_path('resources\\assets\\js\\router\\routes.js');
                $contents = File::get($file);
                $contents = str_replace('/*{{imports}}*/', "import ".$blueprint->getModuleStudlyCase().studly_case(str_singular($blueprint->table()))." from '../../../../Modules/".$blueprint->getModuleStudlyCase()."/Resources/assets/js/components/".$blueprint->getModuleStudlyCase().studly_case(str_singular($blueprint->table())).".vue'\n/*{{imports}}*/", $contents);
                File::put($file, $contents);

                $file = base_path('resources\\assets\\js\\router\\routes.js');
                $contents = File::get($file);
                $contents = str_replace('/*{{module_content_'.$blueprint->getModuleName().'}}*/', "\t\t\t{
                    path: '".studly_case(str_singular($blueprint->table()))."',
                    component: ".$blueprint->getModuleStudlyCase().studly_case(str_singular($blueprint->table())).",
                    name:'".kebab_case($blueprint->getModuleStudlyCase().studly_case(str_singular($blueprint->table())))."'
                },\n/*{{module_content_".$blueprint->getModuleName()."}}*/", $contents);
                File::put($file, $contents);
            }else{
                $file = base_path('routes\\api.php');
                $contents = File::get($file);
                $contents = str_replace('/*{{routebody}}*/', "Route::apiResource('".str_replace('_','-',str_singular($blueprint->table()))."', '".studly_case(str_singular($blueprint->table()))."Controller');\n/*{{routebody}}*/", $contents);
                File::put($file, $contents);

                $file = base_path('resources\\assets\\js\\router\\routes.js');
                $contents = File::get($file);
                $contents = str_replace('/*{{imports}}*/', "import ".studly_case(str_singular($blueprint->table()))." from '../components/".studly_case(str_singular($blueprint->table())).".vue'\n/*{{imports}}*/", $contents);
                File::put($file, $contents);

                $file = base_path('resources\\assets\\js\\router\\routes.js');
                $contents = File::get($file);
                $contents = str_replace('/*{{modulus}}*/',"\t".'{ path: \'/\', name: \''.studly_case(str_singular($blueprint->table())).'\', component: '.studly_case(str_singular($blueprint->table())).' },'."\n/*{{modulus}}*/", $contents);
                File::put($file, $contents);
            }
        }
    }

    public static function clearExtras($modulenames){
        foreach ($modulenames as $modulename){
            $file = base_path('Modules\\'.studly_case($modulename).'\\Http\\routes.php');
            $contents = File::get($file);
            $contents = str_replace('{{routebody}}', '', $contents);
            File::put($file, $contents);

            $file = base_path('resources\\assets\\js\\router\\routes.js');
            $contents = File::get($file);
            $contents = str_replace('/*{{module_content_'.$modulename.'}}*/', '', $contents);
            File::put($file, $contents);
        }

        $file = base_path('routes\\api.php');
        $contents = File::get($file);
        $contents = str_replace('/*{{routebody}}*/', '', $contents);
        File::put($file, $contents);


        $file = base_path('resources\\assets\\js\\router\\routes.js');
        $contents = File::get($file);
        $contents = str_replace('/*{{modulus}}*/', '', $contents);
        $contents = str_replace('/*{{imports}}*/', '', $contents);
        File::put($file, $contents);
    }


}