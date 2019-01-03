<?php

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
        return File::get(base_path('resources/assets/js/router/routes.js'));
    }
    private static function putRouteJs($contents){
        File::put(base_path('resources/assets/js/router/routes.js'), $contents);
    }
    private static function getRouteApi(){
        return File::get(base_path('routes/api.php'));
    }
    private static function getModuleRoute($studlymodulename){
        return File::get(base_path('Modules/'.$studlymodulename.'/Routes/api.php'));
    }
    /*private static function getModuleWebRoute($studlymodulename){
        return File::get(base_path('Modules/'.$studlymodulename.'/Routes/web.php'));
    }*/
    private static function putModuleRoute($studlymodulename, $contents){
        return File::put(base_path('Modules/'.$studlymodulename.'/Routes/api.php'), $contents);
    }
    private static function putModuleWebRoute($studlymodulename, $contents){
        return File::put(base_path('Modules/'.$studlymodulename.'/Routes/web.php'), $contents);
    }
    private static function putRouteApi($contents){
        File::put(base_path('routes/api.php'), $contents);
    }
    private static function getMenuFile(){
        return File::get(base_path('resources/assets/js/AdminApp.vue'));
    }
    private static function putMenuFile($contents){
        return File::put(base_path('resources/assets/js/AdminApp.vue'), $contents);
    }
    protected function createRoutes()
    {
        foreach ($this->moduleNames as $moduleName){
            Artisan::call('module:make', ['name' => [studly_case($moduleName)]]);
        }

        $this->createMainApiRoutes();
        $this->createRoutesJs();

        foreach ($this->moduleNames as $moduleName){
            self::putMenuFile(str_replace('/*{{menucontent}}*/','"'.studly_case($moduleName).'":"'.studly_case($moduleName).'",'."\n\t\t\t\t\t\t/*{{menucontent}}*/", self::getMenuFile()));
            $this->createModulePaths($moduleName);
            $this->createFile($moduleName);
            $this->createModuleVue($moduleName);
        }
    }

    protected function createModulePaths($modulename){
        if (!is_dir(base_path('Modules/'.studly_case($modulename).'/Resources/assets/js'))) {
            // dir doesn't exist, make it
            mkdir(base_path('Modules/'.studly_case($modulename).'/Resources/assets/js'));
        }
        if (!is_dir(base_path('Modules/'.studly_case($modulename).'/Resources/assets/js/components'))) {
            // dir doesn't exist, make it
            mkdir(base_path('Modules/'.studly_case($modulename).'/Resources/assets/js/components'));
        }
        if (!is_dir(base_path('Modules/'.studly_case($modulename).'/Resources/lang/tr'))) {
            // dir doesn't exist, make it
            mkdir(base_path('Modules/'.studly_case($modulename).'/Resources/lang/tr'));
        }
        if (!is_dir(base_path('Modules/'.studly_case($modulename).'/Resources/lang/en'))) {
            // dir doesn't exist, make it
            mkdir(base_path('Modules/'.studly_case($modulename).'/Resources/lang/en'));
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
        self::putModuleRoute(studly_case($modulename), $this->createModuleContents($modulename));
        self::putModuleWebRoute(studly_case($modulename),"<?php"."\r\n");

        $contents = str_replace('/*{{modulus}}*/', "\t{ path: '/".studly_case($modulename)."', name: '".studly_case($modulename)."', component: ".studly_case($modulename).",
        children: [\n/*{{module_content_".$modulename."}}*/\n\t\t]},\n/*{{modulus}}*/", self::getRouteJs());
        self::putRouteJs(str_replace('/*{{imports}}*/', "import ".studly_case($modulename)." from '../../../../Modules/".studly_case($modulename)."/Resources/assets/js/components/".studly_case($modulename).".vue'\n/*{{imports}}*/", $contents));
    }
    protected function createModuleVue($modulename){
        $contents = File::get( __DIR__ . '/Templates/module_vue_model');
        $contents = str_replace('{{title}}', kebab_case($modulename), $contents);
        $contents = str_replace('{{name}}', studly_case($modulename),$contents);
        $file = base_path('Modules/'.studly_case($modulename).'/Resources/assets/js/components/'.studly_case($modulename).'.vue');
        File::put($file, $contents);
    }
    protected function createModuleContents($modulename)
    {
        $body = "<?php\n";
        $body .= "Route::group(['prefix' => '".$modulename."'], function()\n";
        $body .= "{\n";
        $body .= "/*{{routebody}}*/\n";
        $body .= "});";

        return $body;
    }
    protected function createContents()
    {
        $body = "<?php\n";
        $body .= "Route::group([], function()\n";
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
            if($blueprint->getModuleName() != "app"){ //Modül için burası
                self::putModuleRoute($blueprint->getModuleStudlyCase(), str_replace('/*{{routebody}}*/', "Route::apiResource('".str_replace('_','-',str_singular($blueprint->table()))."', '".studly_case(str_singular($blueprint->table()))."Controller');\n/*{{routebody}}*/", self::getModuleRoute($blueprint->getModuleStudlyCase())));
                $contents = str_replace('/*{{imports}}*/', "import ".$blueprint->getModuleStudlyCase().studly_case(str_singular($blueprint->table()))." from '../../../../Modules/".$blueprint->getModuleStudlyCase()."/Resources/assets/js/components/".$blueprint->getModuleStudlyCase().studly_case(str_singular($blueprint->table())).".vue'\n/*{{imports}}*/", self::getRouteJs());
                $contents = str_replace('/*{{module_content_'.$blueprint->getModuleName().'}}*/', "\t\t\t{
                    path: '".studly_case(str_singular($blueprint->table()))."',
                    component: ".$blueprint->getModuleStudlyCase().studly_case(str_singular($blueprint->table())).",
                    name:'".kebab_case($blueprint->getModuleStudlyCase().studly_case(str_singular($blueprint->table())))."'
                },\n/*{{module_content_".$blueprint->getModuleName()."}}*/", $contents);
                self::putRouteJs($contents);

                self::putMenuFile(str_replace('/*{{menucontent}}*/','"'.$blueprint->getModuleName()."-".str_replace('_','-',str_singular($blueprint->table())).'":"'.str_replace('_',' ', title_case(substr($blueprint->table(), 3))).'",'."\n\t\t\t\t\t\t/*{{menucontent}}*/", self::getMenuFile()));
            }else{ //Modül dışındakiler için burası

                $contents = str_replace('/*{{routebody}}*/', "Route::apiResource('".str_replace('_','-',str_singular($blueprint->table()))."', '".studly_case(str_singular($blueprint->table()))."Controller');\n/*{{routebody}}*/", self::getRouteApi());
                self::putRouteApi($contents);
                $contents = str_replace('/*{{imports}}*/', "import ".studly_case(str_singular($blueprint->table()))." from '../components/".studly_case(str_singular($blueprint->table())).".vue'\n/*{{imports}}*/", self::getRouteJs());
                $contents = str_replace('/*{{modulus}}*/',"\t".'{ path: \'/'.studly_case(str_singular($blueprint->table())).'\', name: \''.studly_case(str_singular($blueprint->table())).'\', component: '.studly_case(str_singular($blueprint->table())).' },'."\n/*{{modulus}}*/", $contents);
                self::putRouteJs($contents);

                self::putMenuFile(str_replace('/*{{menucontent}}*/','"'.studly_case(str_singular($blueprint->table())).'":"'.title_case(str_replace('_',' ',str_singular(substr($blueprint->table(), 3)))).'",'."\n\t\t\t\t\t\t/*{{menucontent}}*/", self::getMenuFile()));
            }
        }
    }
    public static function clearExtras($modulenames){
        foreach ($modulenames as $modulename){
            self::putModuleRoute(studly_case($modulename), str_replace('/*{{routebody}}*/', '', self::getModuleRoute(studly_case($modulename))));
            self::putRouteJs(str_replace('/*{{module_content_'.$modulename.'}}*/', '', self::getRouteJs()));
        }

        self::putRouteApi(str_replace('/*{{routebody}}*/', '', self::getRouteApi()));
        $contents = str_replace('/*{{modulus}}*/', "\t{ path: \"*\", name: '404' , component: PageNotFound }\n", self::getRouteJs());
        self::putRouteJs(str_replace('/*{{imports}}*/', '', $contents));
        self::putMenuFile(str_replace('/*{{menucontent}}*/','', self::getMenuFile()));
    }
}
