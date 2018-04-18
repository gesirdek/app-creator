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
    private static function getRouteJs(){
        return File::get(base_path('resources\assets\js\router\routes.js'));
    }
    private static function putRouteJs($contents){
        File::put(base_path('resources\assets\js\router\routes.js'), $contents);
    }
    private static function getRouteApi(){
        return File::get(base_path('routes\api.php'));
    }
    private static function putRouteApi($contents){
        File::put(base_path('routes\api.php'), $contents);
    }
    protected function createRoutes()
    {
        foreach ($this->moduleNames as $moduleName){
            Artisan::call('module:make', ['name' => [studly_case($moduleName)]]);
        }

        $this->createMainApiRoutes();
        $this->createRoutesJs();

        foreach ($this->moduleNames as $moduleName){
            $this->createModulePaths($moduleName);
            $this->createFile($moduleName);
            $this->createModuleVue($moduleName);
        }
    }
    protected function createModulePaths($modulename){
        if (!is_dir(base_path('Modules\\'.studly_case($modulename).'\Resources\assets\js'))) {
            // dir doesn't exist, make it
            mkdir(base_path('Modules\\'.studly_case($modulename).'\Resources\assets\js'));
        }
        if (!is_dir(base_path('Modules\\'.studly_case($modulename).'\Resources\assets\js\components'))) {
            // dir doesn't exist, make it
            mkdir(base_path('Modules\\'.studly_case($modulename).'\Resources\assets\js\components'));
        }
    }
    protected function createMainApiRoutes(){
        self::putRouteApi($this->createContents());
    }
    protected function createRoutesJs(){
        self::putRouteJs($this->createJsContents());
    }
    protected function createFile($modulename)
    {
        $file = base_path('Modules\\'.studly_case($modulename).'\Http\routes.php');
        File::put($file, $this->createModuleContents($modulename));

        $contents = self::getRouteJs();
        $contents = str_replace('/*{{modulus}}*/', "\t{ path: '/".studly_case($modulename)."', name: '".studly_case($modulename)."', component: ".studly_case($modulename).",
        children: [\n/*{{module_content_".$modulename."}}*/\n\t\t]},\n/*{{modulus}}*/", $contents);
        $contents = str_replace('/*{{imports}}*/', "import ".studly_case($modulename)." from '../../../../Modules/".studly_case($modulename)."/Resources/assets/js/components/".studly_case($modulename).".vue'\n/*{{imports}}*/", $contents);
        self::putRouteJs($contents);
    }
    protected function createModuleVue($modulename){
        $contents = "<template>\n";
        $contents .="\t<div>\n";
        $contents .="\t\t<h1>".title_case($modulename)."</h1>\n";
        $contents .="\t\t<router-view></router-view>\n";
        $contents .="\t</div>\n";
        $contents .="</template>\n";
        $contents .="<script>\n";
        $contents .="\t\texport default {\n";
        $contents .="\t\t\tname: '".studly_case($modulename)."',\n";
        $contents .="\t\t\tdata () {\n";
        $contents .="\t\t\t\treturn {}\n";
        $contents .="\t\t\t}\n";
        $contents .="\t\t}\n";
        $contents .="</script>";

        $file = base_path('Modules\\'.studly_case($modulename).'\Resources\assets\js\components\\'.studly_case($modulename).'.vue');
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
        $body = "import Home from '../components/Home.vue'\n";
        $body .= "import PageNotFound from '../components/PageNotFound.vue'\n";
        $body .= "/*{{imports}}*/\n\n";
        $body .= "export default \n";
        $body .= "[\n";
        $body .= "\t{ path: '/', name: 'Home', component: Home },\n";
        $body .= "/*{{modulus}}*/\n";
        $body .= "]";

        return $body;
    }
    public static function addContent(Blueprint $blueprint){
        if(str_singular($blueprint->table()) != $blueprint->table()){
            if($blueprint->getModuleName() != "app"){
                $file = base_path('Modules\\'.$blueprint->getModuleStudlyCase().'\Http\routes.php');
                $contents = File::get($file);
                $contents = str_replace('{{routebody}}', "Route::apiResource('".str_replace('_','-',str_singular($blueprint->table()))."', '".studly_case(str_singular($blueprint->table()))."Controller');\n{{routebody}}", $contents);
                File::put($file, $contents);

                $contents = self::getRouteJs();
                $contents = str_replace('/*{{imports}}*/', "import ".$blueprint->getModuleStudlyCase().studly_case(str_singular($blueprint->table()))." from '../../../../Modules/".$blueprint->getModuleStudlyCase()."/Resources/assets/js/components/".$blueprint->getModuleStudlyCase().studly_case(str_singular($blueprint->table())).".vue'\n/*{{imports}}*/", $contents);
                self::putRouteJs($contents);

                $contents = self::getRouteJs();
                $contents = str_replace('/*{{module_content_'.$blueprint->getModuleName().'}}*/', "\t\t\t{
                    path: '".studly_case(str_singular($blueprint->table()))."',
                    component: ".$blueprint->getModuleStudlyCase().studly_case(str_singular($blueprint->table())).",
                    name:'".kebab_case($blueprint->getModuleStudlyCase().studly_case(str_singular($blueprint->table())))."'
                },\n/*{{module_content_".$blueprint->getModuleName()."}}*/", $contents);
                self::putRouteJs($contents);
            }else{
                $contents = self::getRouteApi();
                $contents = str_replace('/*{{routebody}}*/', "Route::apiResource('".str_replace('_','-',str_singular($blueprint->table()))."', '".studly_case(str_singular($blueprint->table()))."Controller');\n/*{{routebody}}*/", $contents);
                self::putRouteApi($contents);

                $contents = self::getRouteJs();
                $contents = str_replace('/*{{imports}}*/', "import ".studly_case(str_singular($blueprint->table()))." from '../components/".studly_case(str_singular($blueprint->table())).".vue'\n/*{{imports}}*/", $contents);
                self::putRouteJs($contents);

                $contents = self::getRouteJs();
                $contents = str_replace('/*{{modulus}}*/',"\t".'{ path: \'/'.studly_case(str_singular($blueprint->table())).'\', name: \''.studly_case(str_singular($blueprint->table())).'\', component: '.studly_case(str_singular($blueprint->table())).' },'."\n/*{{modulus}}*/", $contents);
                self::putRouteJs($contents);
            }
        }
    }
    public static function clearExtras($modulenames){
        foreach ($modulenames as $modulename){
            $file = base_path('Modules\\'.studly_case($modulename).'\Http\routes.php');
            $contents = File::get($file);
            $contents = str_replace('{{routebody}}', '', $contents);
            File::put($file, $contents);

            $contents = self::getRouteJs();
            $contents = str_replace('/*{{module_content_'.$modulename.'}}*/', '', $contents);
            self::putRouteJs($contents);
        }

        $contents = self::getRouteApi();
        $contents = str_replace('/*{{routebody}}*/', '', $contents);
        self::putRouteApi($contents);

        $contents = self::getRouteJs();
        $contents = str_replace('/*{{modulus}}*/', "\t{ path: \"*\", name: '404' , component: PageNotFound }\n", $contents);
        $contents = str_replace('/*{{imports}}*/', '', $contents);
        self::putRouteJs($contents);
    }
}