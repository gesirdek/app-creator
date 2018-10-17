<?php

/**
 * Created by Cristian.
 * Date: 19/09/16 11:58 PM.
 */

namespace Gesirdek\Coders\Model;

use Carbon\Carbon;
use Gesirdek\Meta\PgSQL\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Gesirdek\Meta\Blueprint;
use Gesirdek\Support\Classify;
use Gesirdek\Meta\SchemaManager;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Database\DatabaseManager;

class Factory
{
    /**
     * @var \Illuminate\Database\DatabaseManager
     */
    private $db;

    /**
     * @var \Gesirdek\Meta\SchemaManager
     */
    protected $schemas;

    /**
     * @var \Illuminate\Filesystem\Filesystem
     */
    protected $files;

    /**
     * @var \Gesirdek\Support\Classify
     */
    protected $class;

    /**
     * @var \Gesirdek\Coders\Model\Config
     */
    protected $config;

    /**
     * @var \Gesirdek\Coders\Model\ModelManager
     */
    protected $models;

    /**
     * @var \Gesirdek\Coders\Model\Mutator[]
     */
    protected $mutators = [];
    /**
     * ModelsFactory constructor.
     *
     * @param \Illuminate\Database\DatabaseManager $db
     * @param \Illuminate\Filesystem\Filesystem $files
     * @param \Gesirdek\Support\Classify $writer
     * @param \Gesirdek\Coders\Model\Config $config
     */
    public function __construct(DatabaseManager $db, Filesystem $files, Classify $writer, Config $config)
    {
        $this->db = $db;
        $this->files = $files;
        $this->config = $config;
        $this->class = $writer;
    }

    /**
     * @return \Gesirdek\Coders\Model\Mutator
     */
    public function mutate()
    {
        return $this->mutators[] = new Mutator();
    }

    /**
     * @return \Gesirdek\Coders\Model\ModelManager
     */
    protected function models()
    {
        if (! isset($this->models)) {
            $this->models = new ModelManager($this);
        }

        return $this->models;
    }

    /**
     * Select connection to work with.
     *
     * @param string $connection
     * @param string $schema
     *
     * @return $this
     */
    public function on($connection = null, $schema = '')
    {

        $this->schemas = new SchemaManager($this->db->connection($connection), $schema, $this->config);

        return $this;
    }

    /**
     * @param string $schema
     */
    public function map($schema)
    {
        if (! isset($this->schemas)) {
            $this->on();
        }

        $mapper = $this->makeSchema($schema);

        foreach ($mapper->tables() as $blueprint) {
            if ($this->shouldNotExclude($blueprint)) {
                $this->create($mapper->schema(), $blueprint->table(), $blueprint->getModuleName());
            }
        }
    }

    /**
     * @param \Gesirdek\Meta\Blueprint $blueprint
     *
     * @return bool
     */
    protected function shouldNotExclude(Blueprint $blueprint)
    {
        foreach ($this->config($blueprint, 'except', []) as $pattern) {
            if (Str::is($pattern, $blueprint->table())) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param string $schema
     * @param string $table
     */
    public function create($schema, $table, $moduleName)
    {
        if(str_singular($table) == $table){ //pivot table shall not pass
            return;
        }
        
        $model = $this->makeModel($schema, $table);
        $this->createFiles($model, $moduleName);
    }

    /**
     * @param string $schema
     * @param string $table
     */
    public function createFiles($model, $moduleName)
    {
        echo $model->getTable()."\n";
        $moduleTitle = studly_case($moduleName);
        $namespaces = [];
        $namespaceMain = ($moduleTitle == 'App' ? 'App' : 'Modules\\'.$moduleTitle);
        $base = ($moduleTitle == "App" ? "" : 'Modules');

        $namespaces[] = $namespaceMain.'\\Entities'; //Model namespace
        $namespaces[] = $namespaceMain.'\\Http\\Requests'; //Request namespace
        $namespaces[] = $namespaceMain.'\\Http\\Controllers'; //Controller namespace

        $template = $this->prepareTemplate($model, 'model');
        $file = $this->fillTemplate($template, $model, $namespaces, $moduleTitle);
        $this->files->put($this->modelPath($model, $model->usesBaseFiles() ? ['Base'] : [$base, ($moduleTitle == "App" ? "app" : $moduleTitle),'Entities'],'','.php'), $file);

        /*REQUEST MODELS*/
        $template = $this->prepareTemplate($model, 'request_model');
        $file = $this->fillTemplate($template, $model, $namespaces, $moduleTitle);
        $this->files->put($this->modelPath($model, $model->usesBaseFiles() ? ['Base'] : [$base, ($moduleTitle == "App" ? "app" : $moduleTitle),'Http','Requests'],'','Request.php'), $file);

        /*CONTROLLERS*/
        $template = $this->prepareTemplate($model, 'controller_model');
        $file = $this->fillTemplate($template, $model, $namespaces, $moduleTitle);
        $this->files->put($this->modelPath($model, $model->usesBaseFiles() ? ['Base'] : [$base, ($moduleTitle == "App" ? "app" : $moduleTitle),'Http','Controllers'],'','Controller.php'), $file);

        /*VUE MODELS - FRONT-END*/
        $template = $this->prepareTemplate($model, 'vue_model');
        $file = $this->fillTemplate($template, $model, $namespaces, $moduleTitle);
        $this->files->put($this->modelPath($model, $model->usesBaseFiles() ? ['Base'] : [$base, ($moduleTitle == 'App' ? '' : $moduleTitle),($moduleTitle == 'App' ? 'resources' : 'Resources'),'assets','js','components'],($moduleTitle == 'App' ? '' : $moduleTitle),'.vue'), $file);

        /*CREATE LANGUAGE PACKAGE*/
        // todo create for each language from package config
        //$languageFileName = $this->modelPath($model, $model->usesBaseFiles() ? ['Base'] : [$base, ($moduleTitle == 'App' ? '' : $moduleTitle),($moduleTitle == 'App' ? 'resources' : 'Resources'),'lang', 'tr'],'Component-','.php');
        $languageFileName = $this->modelPathSmallCase($model, $model->usesBaseFiles() ? ['Base'] : ['resources','lang','tr'],'Component-'.strtolower($moduleTitle == 'App' ? '' : $moduleTitle),'.php');
        $languageFileName = $this->modelPathSmallCase($model, $model->usesBaseFiles() ? ['Base'] : ['resources','lang','en'],'Component-'.strtolower($moduleTitle == 'App' ? '' : $moduleTitle),'.php');
        $template = $this->prepareTemplate($model, 'language_package');
        $file = $this->fillTemplate($template, $model, $namespaces, $moduleTitle);
        $this->files->put($languageFileName, $file);
        if(!str_contains(php_uname(),'Windows')){
            chmod($languageFileName,775);
        }


        $languageFileName = $this->modelPath($model, $model->usesBaseFiles() ? ['Base'] : [$base, ($moduleTitle == 'App' ? '' : $moduleTitle),($moduleTitle == 'App' ? 'resources' : 'Resources'),'lang', 'en'],'Component-','.php');
        $template = $this->prepareTemplate($model, 'language_package');
        $file = $this->fillTemplate($template, $model, $namespaces, $moduleTitle);
        $this->files->put($languageFileName, $file);
        if(!str_contains(php_uname(),'Windows')){
            chmod($languageFileName,775);
        }
    }

    /**
     * @param string $schema
     * @param string $table
     *
     * @param bool $withRelations
     *
     * @return \Gesirdek\Coders\Model\Model
     */
    public function makeModel($schema, $table, $withRelations = true)
    {
        return $this->models()->make($schema, $table, $this->mutators, $withRelations);
    }

    /**
     * @param string $schema
     *
     * @return \Gesirdek\Meta\Schema
     */
    public function makeSchema($schema)
    {
        return $this->schemas->make($schema);
    }

    /**
     * @param \Gesirdek\Coders\Model\Model $model
     * @todo: Delegate workload to SchemaManager and ModelManager
     *
     * @return array
     */
    public function referencing(Model $model)
    {
        $references = [];

        // TODO: SchemaManager should do this
        foreach ($this->schemas as $schema) {
            $references = array_merge($references, $schema->referencing($model->getBlueprint()));
        }

        // TODO: ModelManager should do this
        foreach ($references as &$related) {
            $blueprint = $related['blueprint'];
            $related['model'] = $model->getBlueprint()->is($blueprint->schema(), $blueprint->table())
                                ? $model
                                : $this->makeModel($blueprint->schema(), $blueprint->table(), false);
        }

        return $references;
    }

    /**
     * @param \Gesirdek\Coders\Model\Model $model
     * @param string $name
     *
     * @return string
     */
    protected function prepareTemplate(Model $model, $name)
    {
        $defaultFile = $this->path([__DIR__, 'Templates', $name]);
        $file = $this->config($model->getBlueprint(), "*.template.$name", $defaultFile);

        return $this->files->get($file);
    }

    /**
     * @param string $template
     * @param \Gesirdek\Coders\Model\Model $model
     *
     * @return mixed
     */
    protected function fillTemplate($template, Model $model, $namespaces, $modulename = 'App')
    {
        $template = str_replace('{{date}}', Carbon::now()->toRssString(), $template);
        $template = str_replace('{{namespacemodel}}', $namespaces[0], $template);
        $template = str_replace('{{namespacerequest}}', $namespaces[1], $template);
        $template = str_replace('{{namespacecontroller}}', $namespaces[2], $template);
        $template = str_replace('{{relatednamespaces}}', $this->getRelatedNamespaces($model), $template);

        $template = str_replace('{{vuefilename}}', ($modulename == 'App' ? '' : studly_case($modulename)) . $model->getClassName(), $template);
        $template = str_replace('{{vuefilenamelower}}', strtolower(($modulename == 'App' ? '' : $modulename)).str_replace('_', '', str_singular($model->getTable())), $template);
        //$template = str_replace('{{modelfields}}', $this->getVueModelFields($model), $template);
        $template = str_replace('{{resources}}', $this->getVueModelResources($model), $template);
        $template = str_replace('{{resourcestwo}}', $this->getVueModelResourcesTwo($model), $template);
        $template = str_replace('{{props}}', $this->getVueModelProps($model), $template);
        $template = str_replace('{{vuefields}}', $this->getVueFields($model), $template);
        $template = str_replace('{{lists}}', $this->getVueLists($model), $template);
        $template = str_replace('{{datetime_modals}}', $this->getVueDatetimeModals($model), $template);
        $template = str_replace('{{listdata}}', $this->getVueListData($model), $template);
        $template = str_replace('{{lowerclass}}', str_replace('_', '-', str_singular($model->getTable())), $template);
        $template = str_replace('{{modulename}}', ($modulename == 'App' ? 'api' : 'api/' . kebab_case($modulename)), $template);

        $template = str_replace('{{parent}}', $model->getParentClass(), $template);
        $template = str_replace('{{rules}}', $this->getRules($model), $template);
        $template = str_replace('{{updatemodel}}', $this->getUpdateModel($model), $template);
        $template = str_replace('{{storemodel}}', $this->getStoreModel($model), $template);
        $template = str_replace('{{properties}}', $this->properties($model), $template);
        $template = str_replace('{{class}}', $model->getClassName(), $template);
        $template = str_replace('{{body}}', $this->body($model), $template);

        $template = str_replace('{{keys}}', $this->getLanguageKeys($model), $template);


        return $template;
    }

    protected function getModelFromTable($table)
    {
        foreach( get_declared_classes() as $class ) {
            if( is_subclass_of( $class, 'Illuminate\Database\Eloquent\Model' ) ) {
                $model = new $class;
                if ($model->getTable() === $table)
                    return $class;
            }
        }

        return false;
    }

    /**
     * @param \Gesirdek\Coders\Model\Model $model
     *
     * @return mixed
     */
    protected function getVueListData(Model $model){
        $body = "";
        foreach ($model->getProperties() as $property => $dataType){
            if($property != 'id' && $property != 'created_at' && $property != 'updated_at' && $property != 'deleted_at'){
                if(str_is('*_id',$property)){
                    if(str_is('*able_id',$property)!==false){
                        $table_name=str_plural(substr($property,0,-7));
                        $route_module_part = $this->getModuleNameOfRelated($table_name);
                        if(!$route_module_part)
                            continue;
                        $body .= '{list:\''.substr($property,0,-2).'list\',source:\'/api/'.$route_module_part.str_replace('_','-',substr($property,0,-7)).'\'}, ';
                    }else{
                        $table_name=str_plural(substr($property,0,-3));
                        $route_module_part = $this->getModuleNameOfRelated($table_name);
                        if(!$route_module_part)
                            continue;
                        $body .= '{list:\''.substr($property,0,-2).'list\',source:\'/api/'.$route_module_part.str_replace('_','-',substr($property,0,-3)).'\'}, ';
                    }
                }
            }
        }
        foreach ($model->getRelations() as $constraint) {
            if(Str::contains($constraint->body(),'belongsToMany')){
                $table_name = str_plural($constraint->name());
                $route_module_part = $this->getModuleNameOfRelated($table_name);
                if(!$route_module_part)
                    continue;
                $body .= '{list:\''.$constraint->name().'\',source:\'/api/'.$route_module_part.str_replace('_','-',str_singular($constraint->name())).'\'}, ';
            }
        }

        return $body;
    }

    protected function getModelsInFolder($paths){
        $out = [];
        foreach ($paths as $path){
            if(!is_dir($path))
                continue;
            $results = scandir($path);
            foreach ($results as $result) {
                if ($result === '.' or $result === '..') continue;
                $filename = $path . '/' . $result;
                if (is_dir($filename)) {
                    $out = array_merge($out, $this->getModelsInFolder([$filename]));
                }else{
                    $class_name = str_replace('/','\\',ucfirst(substr(str_replace(base_path(),'',substr($filename,0,-4)),1)));
                    if(is_subclass_of( $class_name, 'Illuminate\Database\Eloquent\Model' )){
                        $out[] = $class_name;
                    }
                }
            }
        }
        return $out;
    }

    protected function getModuleNameOfRelated($table_name){
        $entity_classes = $this->getModelsInFolder([
            app_path().'/Entities',
            base_path().'/Modules']);
        foreach ($entity_classes as $entity_class){
            $EC = new $entity_class();
            if($EC->getTable() == $table_name){
                $parsed_ec = explode('\\',$entity_class);
                if($parsed_ec[0] === 'Modules'){
                    //echo "this is a module : ".$table_name."\n";
                    return str_slug($parsed_ec[1]).'/';
                }else{
                    //echo "this is not a module : ".$table_name."\n";
                    return '';
                }
            }
        }
        return false;
    }
    /**
     * @param \Gesirdek\Coders\Model\Model $model
     *
     * @return mixed
     */
    protected function getRelatedNamespaces(Model $model){
        $body = "";
        foreach ($model->getRelations() as $constraint) {
            if($model->getTable() == str_plural($constraint->name())){
                continue;
            }

            if(str_contains($constraint->hint(),'|')){
                $hint=explode('|',$constraint->hint())[1];
            }else{
                $hint=$constraint->hint();
            }

            if(str_contains($hint,'Modules\App\\')){
                $body .= "use ".substr(substr($hint,9),0,-2).";\n";
            }
        elseif(str_contains($hint,'|')){
                $body .= "use ".substr(substr($hint,1),0,-2).";\n";
            }else{
                if(substr($hint,-2)==='[]'){
                    $body .= "use ".substr($hint,0,-2).";\n";
                }else{
                    $body .= "use ".$hint.";\n";
                }
            }
        }

        return $body;
    }

    /**
     * @param \Gesirdek\Coders\Model\Model $model
     *
     * @return mixed
     */
    protected function getVueLists(Model $model){
        $body = "";

        foreach ($model->getProperties() as $property => $dataType){
            if($property != 'id' && $property != 'created_at' && $property != 'updated_at' && $property != 'deleted_at'){
                if(str_is('*_id',$property)){
                    $body.= "\t\t".substr($property,0,-2).'list : [],'."\r\n";
                }
            }
        }
        foreach ($model->getRelations() as $constraint) {
            if(Str::contains($constraint->body(),'belongsToMany')){
                $body.= "\t\t".$constraint->name().' : [],'."\r\n";
            }
        }

        return substr(substr($body,2),0,-2);
    }

    /**
     * @param \Gesirdek\Coders\Model\Model $model
     *
     * @return mixed
     */
    protected function getVueFields(Model $model){
        //dd($model->getCasts());
        $body = "\r\n";
        $vfilename = ($model->getBlueprint()->getModuleName() == 'app' ? '' : kebab_case($model->getBlueprint()->getModuleName())).str_replace('_','', str_singular($model->getTable()));
        foreach ($model->getProperties() as $property => $dataType){
            if($property != 'id' && $property != 'created_at' && $property != 'updated_at' && $property != 'deleted_at'){
                if(str_is('*_id',$property)){
                    $body .= "\t\t\t".'<v-select'."\r\n";
                    $body .= "\t\t\t\t".':items="'.substr($property,0,-2).'list"'."\r\n";
                    $body .= "\t\t\t\t".'item-text="name"'."\r\n";
                    $body .= "\t\t\t\t".'item-value="id"'."\r\n";
                    $body .= "\t\t\t\t".'v-model="item.'.$property.'"'."\r\n";
                    $body .= "\t\t\t\t".':label="$t(\''.$vfilename.'.'.$property.'\')"'."\r\n";
                    $body .= "\t\t\t\t".':error-messages="errors.collect(\''.$property.'\')"'."\r\n";
                    $body .= "\t\t\t\t".'v-validate="\'required\'"'."\r\n";
                    $body .= "\t\t\t\t".':data-vv-name="$t(\''.$vfilename.'.'.$property.'\')"'."\r\n";
                    $body .= "\t\t\t\t".'required'."\r\n";
                    $body .= "\t\t\t".'></v-select>'."\r\n";
                }else if($dataType == 'boolean'){
                    $body .= "\t\t\t".'<v-checkbox'."\r\n";
                    $body .= "\t\t\t\t".'v-model="item.'.$property.'"'."\r\n";
                    $body .= "\t\t\t\t".':label="$t(\''.$vfilename.'.'.$property.'\')"'."\r\n";
                    $body .= "\t\t\t\t".':error-messages="errors.collect(\''.$property.'\')"'."\r\n";
                    $body .= "\t\t\t\t".'v-validate="\'required\'"'."\r\n";
                    $body .= "\t\t\t\t".':data-vv-name="$t(\''.$vfilename.'.'.$property.'\')"'."\r\n";
                    $body .= "\t\t\t\t".'type="checkbox"'."\r\n";
                    $body .= "\t\t\t\t".'required'."\r\n";
                    $body .= "\t\t\t".'></v-checkbox>'."\r\n";
                }/*else if($dataType == 'file'){
                    $body .= "\t\t\t".'\''.$property.'\'=>\'required|file\','."\n";
                }else if($dataType == 'integer'){
                    $body .= "\t\t\t".'\''.$property.'\'=>\'required|integer\','."\n";
                }*/else if($dataType == '\Carbon\Carbon') {
                    $body .= "\t\t\t<v-menu\r\n";
					$body .= "\t\t\t\t".'ref="'.$property.'"'."\r\n";
					$body .= "\t\t\t\t".':close-on-content-click="false"'."\r\n";
					$body .= "\t\t\t\t".'v-model="'.$property.'_modal"'."\r\n";
					$body .= "\t\t\t\t".':nudge-right="40"'."\r\n";
					$body .= "\t\t\t\t".':return-value.sync="item.'.$property.'"'."\r\n";
					$body .= "\t\t\t\t".'lazy'."\r\n";
					$body .= "\t\t\t\t".'transition="scale-transition"'."\r\n";
					$body .= "\t\t\t\t".'offset-y'."\r\n";
					$body .= "\t\t\t\t".'full-width'."\r\n";
					$body .= "\t\t\t\t".'min-width="290px"'."\r\n";
					$body .= "\t\t\t>\r\n";
			        $body .= "\t\t\t\t".'<v-text-field'."\r\n";
					$body .= "\t\t\t\t\t".'slot="activator"'."\r\n";
					$body .= "\t\t\t\t\t".'v-model="item.'.$property.'"'."\r\n";
					$body .= "\t\t\t\t\t".':label="$t(\'coreuser.'.$property.'\')"'."\r\n";
					$body .= "\t\t\t\t\t".'prepend-icon="event"'."\r\n";
					$body .= "\t\t\t\t\t".'readonly'."\r\n";
                    $body .= "\t\t\t\t".'></v-text-field>'."\r\n";
			        $body .= "\t\t\t\t".'<v-date-picker'."\r\n";
				    $body .= "\t\t\t\t\t".'v-model="item.'.$property.'"'."\r\n";
				    $body .= "\t\t\t\t\t".':locale="store.getters.locale"'."\r\n";
				    $body .= "\t\t\t\t\t".'@input="$refs.'.$property.'.save(item.'.$property.')"'."\r\n";
                    $body .= "\t\t\t\t".'></v-date-picker>'."\r\n";
			        $body .= "\t\t\t".'</v-menu>'."\r\n";
                }else if($dataType == 'string') {
                    $body .= "\t\t\t".'<v-text-field'."\r\n";
                    $body .= "\t\t\t\t".'v-model="item.'.$property.'"'."\r\n";
                    $body .= "\t\t\t\t".':label="$t(\''.$vfilename.'.'.$property.'\')"'."\r\n";
                    $body .= "\t\t\t\t".':counter="'.$model->getBlueprint()->column($property)->getAttributes()['size'].'"'."\r\n";
                    $body .= "\t\t\t\t".':error-messages="errors.collect(\''.$property.'\')"'."\r\n";
                    $body .= "\t\t\t\t".'v-validate="\'required|max:'.$model->getBlueprint()->column($property)->getAttributes()['size'].'\'"'."\r\n";
                    $body .= "\t\t\t\t".':data-vv-name="$t(\''.$vfilename.'.'.$property.'\')"'."\r\n";
                    $body .= "\t\t\t\t".'required'."\r\n";
                    $body .= "\t\t\t".'></v-text-field>'."\r\n";
                }else if($dataType == 'bigstring') {
                        $body .= "\t\t\t".'<v-text-field'."\r\n";
                        $body .= "\t\t\t\t".'v-model="item.'.$property.'"'."\r\n";
                        $body .= "\t\t\t\t".':label="$t(\''.$vfilename.'.'.$property.'\')"'."\r\n";
                        $body .= "\t\t\t\t".':error-messages="errors.collect(\''.$property.'\')"'."\r\n";
                        $body .= "\t\t\t\t".'v-validate="\'required\'"'."\r\n";
                        $body .= "\t\t\t\t".':data-vv-name="$t(\''.$vfilename.'.'.$property.'\')"'."\r\n";
                        $body .= "\t\t\t\t".'required'."\r\n";
                        $body .= "\t\t\t".'></v-text-field>'."\r\n";
                }else{
                    $body .= "\t\t\t".'<v-text-field'."\r\n";
                    $body .= "\t\t\t\t".'v-model="item.'.$property.'"'."\r\n";
                    $body .= "\t\t\t\t".':label="$t(\''.$vfilename.'.'.$property.'\')"'."\r\n";
                    $body .= "\t\t\t\t".':counter="255"'."\r\n";
                    $body .= "\t\t\t\t".':error-messages="errors.collect(\''.$property.'\')"'."\r\n";
                    $body .= "\t\t\t\t".'v-validate="\'required\'"'."\r\n";
                    $body .= "\t\t\t\t".':data-vv-name="$t(\''.$vfilename.'.'.$property.'\')"'."\r\n";
                    $body .= "\t\t\t\t".'required'."\r\n";
                    $body .= "\t\t\t".'></v-text-field>'."\r\n";
                }
            }
        }
        foreach ($model->getRelations() as $constraint) {
            if(Str::contains($constraint->body(),'belongsToMany')){
                $body .= "\t\t\t".'<v-select'."\r\n";
                $body .= "\t\t\t\t".':items="'.$constraint->name().'"'."\r\n";
                $body .= "\t\t\t\t".'item-text="name"'."\r\n";
                $body .= "\t\t\t\t".'item-value="id"'."\r\n";
                $body .= "\t\t\t\t".'v-model="item.'.$constraint->name().'"'."\r\n";
                $body .= "\t\t\t\t".':label="$t(\''.$vfilename.'.'.$constraint->name().'\')"'."\r\n";
                $body .= "\t\t\t\t".':error-messages="errors.collect(\''.$constraint->name().'\')"'."\r\n";
                $body .= "\t\t\t\t".'v-validate="\'required\'"'."\r\n";
                $body .= "\t\t\t\t".':data-vv-name="$t(\''.$vfilename.'.'.$constraint->name().'\')"'."\r\n";
                $body .= "\t\t\t\t".'required'."\r\n";
                $body .= "\t\t\t\t".'multiple'."\r\n";
                $body .= "\t\t\t\t".'autocomplete'."\r\n";
                $body .= "\t\t\t".'></v-select>'."\r\n";
            }
        }

        return $body;
    }
/**
     * @param \Gesirdek\Coders\Model\Model $model
     *
     * @return mixed
     */
    protected function getVueDatetimeModals(Model $model){
        //dd($model->getCasts());
        $body = "\r\n";
        $vfilename = ($model->getBlueprint()->getModuleName() == 'app' ? '' : kebab_case($model->getBlueprint()->getModuleName())).str_replace('_','', str_singular($model->getTable()));
        foreach ($model->getProperties() as $property => $dataType){
            if($property != 'id' && $property != 'created_at' && $property != 'updated_at' && $property != 'deleted_at'){
                if($dataType == '\Carbon\Carbon') {
                    $body.= "\t\t".$property.'_modal : false,'."\r\n";
                }
            }
        }

        return $body;
    }

    /**
     * @param \Gesirdek\Coders\Model\Model $model
     *
     * @return mixed
     */
    protected function getVueModelProps(Model $model){
        $body = '';
        foreach ($model->getProperties() as $property => $dataType){
            if($property != 'id' && $property != 'created_at' && $property != 'updated_at' && $property != 'deleted_at'){
                if(Str::contains($property,'_id')){
                    $body .= "\t\t".'<td><span v-for="r in '.substr($property,0,-2).'list" v-if="props.item.'.$property.' === r.id">{{ r.name }}</span></td>'."\r\n";
                }else{
                    $body .= "\t\t".'<td>{{ props.item.'.$property.' }}</td>'."\r\n";
                }


            }
        }

        foreach ($model->getRelations() as $constraint) {
            if(Str::contains($constraint->body(),'belongsToMany')){
                //$body .= "\t\t".'<td><span v-for="r in '.$constraint->name().'" v-if="props.item.'.$constraint->name().'.includes(r.id)">{{ r.name }}</span></td>'."\r\n";
                $body .= "\t\t".'<td><v-chip :key="r.id" v-for="r in '.$constraint->name().'" v-if="props.item.'.$constraint->name().'.find(x => x.id === r.id)" class="">{{ r.name }}</v-chip>'."\r\n";

                /*if(Str::contains($constraint->name(),'_id')){
                    $body .= "\t\t".'<td>{{ '.substr($constraint->name(),0,-2).'list.filter(function( a ) { return a.id === props.item.'.$constraint->name().'; })[0].name }}</td>'."\r\n";
                }else{
                    $body .= "\t\t".'<td>{{ props.item.'.$constraint->name().' }}</td>'."\r\n";
                }*/
            }
        }

        return substr($body,2);
    }

    /**
     * @param \Gesirdek\Coders\Model\Model $model
     *
     * @return mixed
     */
    protected function getVueModelResourcesTwo(Model $model){
        $body = '';
        $vfilename = ($model->getBlueprint()->getModuleName() == 'app' ? '' : kebab_case($model->getBlueprint()->getModuleName())).str_replace('_','', str_singular($model->getTable()));
        foreach ($model->getProperties() as $property => $dataType){
            if($property != 'id' && $property != 'created_at' && $property != 'updated_at' && $property != 'deleted_at'){
                $body .='"'.$vfilename.'.'.$property.'", ';
            }
        }
        foreach ($model->getRelations() as $constraint) {
            if(Str::contains($constraint->body(),'belongsToMany')){
                $body .='"'.$vfilename.'.'.$constraint->name().'", ';
            }
        }

        return $body;
    }

    /**
     * @param \Gesirdek\Coders\Model\Model $model
     *
     * @return mixed
     */
    protected function getVueModelResources(Model $model){
        $body = '';
        foreach ($model->getProperties() as $property => $dataType){
            if($property != 'id' && $property != 'created_at' && $property != 'updated_at' && $property != 'deleted_at'){
                $body .= $property.':\'\',';
            }
        }
        foreach ($model->getRelations() as $constraint) {
            if(Str::contains($constraint->body(),'belongsToMany')){
                $body .= $constraint->name().': [],';
            }
        }

        return $body;
    }

    /**
     * @param \Gesirdek\Coders\Model\Model $model
     *
     * @return mixed
     */
    protected function getLanguageKeys(Model $model){
        $body = "";
        $body .="\t".'"title"=>\''.title_case(str_replace('-',' ',$model->getTable())).'\','."\r\n";
        foreach ($model->getProperties() as $property => $dataType){
            if($property != 'id' && $property != 'created_at' && $property != 'updated_at' && $property != 'deleted_at'){
                $body .="\t".'"'.$property.'"=>\''.title_case(str_replace('_',' ',str_replace('_id','',$property))).'\','."\r\n";
            }
        }

        return $body;
    }

    /**
     * @param \Gesirdek\Coders\Model\Model $model
     *
     * @return mixed
     */
    protected function getRules(Model $model){
        $body = "";
        foreach ($model->getProperties() as $property => $dataType){
            if($property != 'id' && $property != 'created_at' && $property != 'updated_at' && $property != 'deleted_at'){
                if(str_is('*_id',$property)){
                    if(str_contains($property, "parent_id")){
                        $body .= "\t\t\t".'\''.$property.'\'=>\'required|exists:'.$model->getTable().',id\','."\n";
                    }else{
                        $body .= "\t\t\t".'\''.$property.'\'=>\'required|exists:'.str_plural(str_before($property,'_id')).',id\','."\n";
                    }
                }else if($dataType == 'boolean'){
                    $body .= "\t\t\t".'\''.$property.'\'=>\'required|boolean\','."\n";
                }else if($dataType == 'file'){
                    $body .= "\t\t\t".'\''.$property.'\'=>\'required|file\','."\n";
                }else if($dataType == 'integer'){
                    $body .= "\t\t\t".'\''.$property.'\'=>\'required|integer\','."\n";
                }else if($dataType == 'string'){
                    $body .= "\t\t\t".'\''.$property.'\'=>\'required|max:'.$model->getBlueprint()->column($property)->getAttributes()['size'].'\','."\n";
                }else{
                    $body .= "\t\t\t".'\''.$property.'\'=>\'required\','."\n";
                }
            }
        }

        return substr(substr($body,0,-2), 3);
    }

    /**
     * @param \Gesirdek\Coders\Model\Model $model
     *
     * @return mixed
     */
    protected function getUpdateModel(Model $model){
        $body = '$model_id = DB::transaction(function () use ($request, $id) {'."\n";
        $body .= "\t\t\t".'$model = '.$model->getClassName().'::find($id);'."\n";

        foreach ($model->getProperties() as $property => $dataType){
            if($property != 'id' && $property != 'created_at' && $property != 'updated_at' && $property != 'deleted_at'){
                switch ($dataType){
                    case 'file': $body .= "\t\t\t".'$model->'.$property.' = $request->file(\''.$property.'\');'."\n"; break;
                    default: $body .= "\t\t\t".'$model->'.$property.' = $request->get(\''.$property.'\');'."\n";
                }
            }
        }
        $body .= "\t\t\t".'$model->save();'."\n\n";
        $body .= "\t\t\t".'/*Add your syncs here*/'."\n";
        foreach ($model->getRelations() as $constraint) {
            if(Str::contains($constraint->body(),'belongsToMany')){
                $body .= "\t\t\t".'$model->'.$constraint->name().'()->sync($request->get(\''.$constraint->name().'\'));'."\n";
            }
        }
        $body .= "\n";
        $body .= "\t\t\t".'return $model->id;'."\n";
        $body .= "\t\t".'});'."\n";
        $body .= "\t\t".'return $model_id;';

        return $body;
    }

    /**
     * @param \Gesirdek\Coders\Model\Model $model
     *
     * @return mixed
     */
    protected function getStoreModel(Model $model){
        $body = '$model_id = DB::transaction(function () use ($request) {'."\n";
        $body .= "\t\t\t".'$model = new '.$model->getClassName().';'."\n";

        foreach ($model->getProperties() as $property => $dataType){
            if($property != 'id' && $property != 'created_at' && $property != 'updated_at' && $property != 'deleted_at'){
                switch ($dataType){
                    case 'file': $body .= "\t\t\t".'$model->'.$property.' = $request->file(\''.$property.'\');'."\n"; break;
                    default: $body .= "\t\t\t".'$model->'.$property.' = $request->get(\''.$property.'\');'."\n";
                }
            }
        }
        $body .= "\t\t\t".'$model->save();'."\n\n";
        $body .= "\t\t\t".'/*Add your syncs here*/'."\n";
        foreach ($model->getRelations() as $constraint) {
            if(Str::contains($constraint->body(),'belongsToMany')){
                $body .= "\t\t\t".'$model->'.$constraint->name().'()->sync($request->get(\''.$constraint->name().'\'));'."\n";
            }
        }
        $body .= "\n";
        $body .= "\t\t\t".'return $model->id;'."\n";
        $body .= "\t\t".'});'."\n";
        $body .= "\t\t".'return $model_id;';

        return $body;
    }

    /**
     * @param \Gesirdek\Coders\Model\Model $model
     *
     * @return string
     */
    protected function properties(Model $model)
    {
        // Process property annotations
        $annotations = '';

        foreach ($model->getProperties() as $name => $hint) {
            $annotations .= $this->class->annotation('property', "$hint \$$name");
        }

        if ($model->hasRelations()) {
            // Add separation between model properties and model relations
            $annotations .= "\n * ";
        }

        foreach ($model->getRelations() as $name => $relation) {
            // TODO: Handle collisions, perhaps rename the relation.
            if ($model->hasProperty($name)) {
                continue;
            }
            $annotations .= $this->class->annotation('property', str_replace("\Modules\App","\App",$relation->hint())." \$$name");
        }

        return $annotations;
    }

    /**
     * @param \Gesirdek\Coders\Model\Model $model
     *
     * @return string
     */
    protected function body(Model $model)
    {
        $body = '';

        foreach ($model->getTraits() as $trait) {
            $body .= $this->class->mixin($trait);
        }

        if ($model->hasCustomCreatedAtField()) {
            $body .= $this->class->constant('CREATED_AT', $model->getCreatedAtField());
        }

        if ($model->hasCustomUpdatedAtField()) {
            $body .= $this->class->constant('UPDATED_AT', $model->getUpdatedAtField());
        }

        if ($model->hasCustomDeletedAtField()) {
            $body .= $this->class->constant('DELETED_AT', $model->getDeletedAtField());
        }

        $body = trim($body, "\n");
        // Separate constants from fields only if there are constants.
        if (! empty($body)) {
            $body .= "\n";
        }

        // Append connection name when required
        if ($model->shouldShowConnection()) {
            $body .= $this->class->field('connection', $model->getConnectionName());
        }

        // When table is not plural, append the table name
        if ($model->needsTableName()) {
            $body .= $this->class->field('table', $model->getTableForQuery());
        }

        if ($model->hasCustomPrimaryKey()) {
            $body .= $this->class->field('primaryKey', $model->getPrimaryKey());
        }

        if ($model->doesNotAutoincrement()) {
            $body .= $this->class->field('incrementing', false, ['visibility' => 'public']);
        }

        if ($model->hasCustomPerPage()) {
            $body .= $this->class->field('perPage', $model->getPerPage());
        }

        if (! $model->usesTimestamps()) {
            $body .= $this->class->field('timestamps', false, ['visibility' => 'public']);
        }

        if ($model->hasCustomDateFormat()) {
            $body .= $this->class->field('dateFormat', $model->getDateFormat());
        }

        if ($model->doesNotUseSnakeAttributes()) {
            $body .= $this->class->field('snakeAttributes', false, ['visibility' => 'public static']);
        }

        if ($model->hasCasts()) {
            $body .= $this->class->field('casts', $model->getCasts(), ['before' => "\n"]);
        }

        if ($model->hasDates()) {
            $body .= $this->class->field('dates', $model->getDates(), ['before' => "\n"]);
        }

        if ($model->hasHidden() && $model->doesNotUseBaseFiles()) {
            $body .= $this->class->field('hidden', $model->getHidden(), ['before' => "\n"]);
        }

        if ($model->hasFillable() && $model->doesNotUseBaseFiles()) {
            $body .= $this->class->field('fillable', $model->getFillable(), ['before' => "\n"]);
        }

        if ($model->hasHints() && $model->usesHints()) {
            $body .= $this->class->field('hints', $model->getHints(), ['before' => "\n"]);
        }

        $withs=[];

        foreach ($model->getRelations() as $constraint) {
            if(str_plural($constraint->name()) == $constraint->name())
                $withs[]=$constraint->name();
        }
        $body .= $this->class->field('with', $withs, ['before' => "\n"]);

        foreach ($model->getMutations() as $mutation) {
            $body .= $this->class->method($mutation->name(), $mutation->body(), ['before' => "\n"]);
        }

        foreach ($model->getRelations() as $constraint) {
            $body .= $this->class->method($constraint->name(), $constraint->body(), ['before' => "\n"]);
        }

        // Make sure there not undesired line breaks
        $body = trim($body, "\n");

        return $body;
    }

    /**
     * @param \Gesirdek\Coders\Model\Model $model
     *
     * @param array $custom
     *
     * @return string
     */
    protected function modelPath(Model $model, $custom = [], $preFix = "", $sufFix = "")
    {
        $modelsDirectory = $this->path(array_merge([$this->config($model->getBlueprint(), 'path')], $custom));

        if (! $this->files->isDirectory($modelsDirectory)) {
            $this->files->makeDirectory($modelsDirectory, 0755, true);
        }

        return $this->path([$modelsDirectory, $preFix.$model->getClassName().$sufFix]);
    }

    /**
     * @param \Gesirdek\Coders\Model\Model $model
     *
     * @param array $custom
     *
     * @return string
     */
    protected function modelPathSmallCase(Model $model, $custom = [], $preFix = "", $sufFix = "")
    {
        $modelsDirectory = $this->path(array_merge([$this->config($model->getBlueprint(), 'path')], $custom));

        if (! $this->files->isDirectory($modelsDirectory)) {
            $this->files->makeDirectory($modelsDirectory, 0755, true);
        }

        return $this->path([$modelsDirectory, $preFix.strtolower($model->getClassName()).$sufFix]);
    }

    /**
     * @param array $pieces
     *
     * @return string
     */
    protected function path($pieces)
    {
        return implode(DIRECTORY_SEPARATOR, (array) $pieces);
    }

    /**
     * @param \Gesirdek\Coders\Model\Model $model
     *
     * @return bool
     */
    public function needsUserFile(Model $model)
    {
        return ! $this->files->exists($this->modelPath($model)) && $model->usesBaseFiles();
    }

    /**
     * @param \Gesirdek\Coders\Model\Model $model
     */
    protected function createUserFile(Model $model)
    {
        $file = $this->modelPath($model);

        $template = $this->prepareTemplate($model, 'user_model');
        $template = str_replace('{{namespace}}', $model->getNamespace(), $template);
        $template = str_replace('{{class}}', $model->getClassName(), $template);
        $template = str_replace('{{parent}}', '\\'.$model->getBaseNamespace().'\\'.$model->getClassName(), $template);
        $template = str_replace('{{body}}', $this->userFileBody($model), $template);

        $this->files->put($file, $template);
    }

    /**
     * @param \Gesirdek\Coders\Model\Model $model
     *
     * @return string
     */
    protected function userFileBody(Model $model)
    {
        $body = '';

        if ($model->hasHidden()) {
            $body .= $this->class->field('hidden', $model->getHidden());
        }

        if ($model->hasFillable()) {
            $body .= $this->class->field('fillable', $model->getFillable(), ['before' => "\n"]);
        }

        // Make sure there is not an undesired line break at the end of the class body
        $body = ltrim(rtrim($body, "\n"), "\n");

        return $body;
    }

    /**
     * @param \Gesirdek\Meta\Blueprint|null $blueprint
     * @param string $key
     * @param mixed $default
     *
     * @return mixed|\Gesirdek\Coders\Model\Config
     */
    public function config(Blueprint $blueprint = null, $key = null, $default = null)
    {
        if (is_null($blueprint)) {
            return $this->config;
        }

        return $this->config->get($blueprint, $key, $default);
    }
}
