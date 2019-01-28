<?php

namespace Gesirdek\Coders\Console;

use App\Entities\Permission;
use Illuminate\Console\Command;
use Gesirdek\Coders\Model\Factory;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;

class CodeModelsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'code:models
                            {--s|schema= : The name of the PgSQL database}
                            {--c|connection= : The name of the connection}
                            {--t|table= : The name of the table}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Parse connection schema into models';

    /**
     * @var \Gesirdek\Coders\Model\Factory
     */
    protected $models;

    /**
     * @var \Illuminate\Contracts\Config\Repository
     */
    protected $config;

    /**
     * Create a new command instance.
     *
     * @param \Gesirdek\Coders\Model\Factory $models
     * @param \Illuminate\Contracts\Config\Repository $config
     */
    public function __construct(Factory $models, Repository $config)
    {
        parent::__construct();

        $this->models = $models;
        $this->config = $config;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        /*$this->createPermissions();
        exit;*/
        $this->line('requirements are installing...');
        exec('npm i');
        exec('npm i -S font-awesome js-cookie sweetalert2 vee-validate material-design-icons material-icons vue-i18n vue-router vue-timeago vuetify vuex vuex-router-sync');
        exec('npm i -D babel-plugin-syntax-dynamic-import babel-loader sass-loader vue-loader');

        if(config('models.*.user_management')){
            //Artisan commands
            //Artisan::call('module:make', ['name' => [studly_case($moduleName)]]);
            $this->line('passport package is downloading...');
            exec('composer require laravel/passport');
            $this->line('migrations...');
            Artisan::call('migrate');
            $this->line('passport extension installing...');
            Artisan::call('passport:install');
            $this->line('user management tables are installing...');
            Artisan::call('migrate', ['--path' => 'vendor/gesirdek/app-creator/src/Database/Migrations']);
            $this->line('application is being created...');
        }

        $connection = $this->getConnection();
        $schema = $this->getSchema($connection);
        $table = $this->getTable();


        // Check whether we just need to generate one table
        if ($table) {
            $this->models->on($connection)->create($schema, $table,'app');
            $this->info("Check out your models for $table");
        }

        // Otherwise map the whole database
        else {
            $this->models->on($connection, $schema)->map($schema);
            $this->info("Check out your models for $schema");
        }

        if(config('models.*.user_management')){
            $this->createPermissions();
        }
    }

    /**
     * @return string
     */
    protected function getConnection()
    {
        return $this->config->get('database.default');
    }

    /**
     * @param $connection
     *
     * @return string
     */
    protected function getSchema($connection)
    {
        return $this->config->get("database.connections.$connection.database");
    }

    /**
     * @return string
     */
    protected function getTable()
    {
        return $this->option('table');
    }

    /*
     * @return boolean
     */
    protected function createPermissions(){
        $routeCollection = Route::getRoutes();
        foreach ($routeCollection as $value) {
            if(isset($value->action["controller"])){
                DB::transaction(function() use ($value)
                {
                    $permission=DB::table('permissions');
                    if($permission->where('route',$value->action["controller"])->exists()){

                    }else{
                        $exploded=explode('\\',$value->action["controller"]);
                        $permission->insert([
                            'route'=>$value->action["controller"],
                            'name'=> $exploded[count($exploded)-1],
                            "created_at" =>  \Carbon\Carbon::now(),
                            "updated_at" => \Carbon\Carbon::now(),
                        ]);
                    }
                    $role=DB::table('roles');
                    if($role->count() == 0){
                        $role_id=$role->insertGetId([
                            'name'=>'admin',
                            "created_at" =>  \Carbon\Carbon::now(),
                            "updated_at" => \Carbon\Carbon::now()
                        ]);
                        $role_user=DB::table('role_user');
                        $role_user->insert([
                            'role_id'=>$role_id,
                            'user_id'=>config('models.*.admin_id'),
                            "created_at" =>  \Carbon\Carbon::now(),
                            "updated_at" => \Carbon\Carbon::now()
                        ]);
                    }
                });
            }
        }
    }
}
