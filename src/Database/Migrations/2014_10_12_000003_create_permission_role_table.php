<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreatePermissionRoleTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('permission_role', function(Blueprint $table)
        {
            $table->integer('role_id')->unsigned();
            $table->foreign('role_id')->references('id')
                ->on('roles');

            $table->integer('permission_id')->unsigned();
            $table->foreign('permission_id')->references('id')
                ->on('permissions');

            $table->timestamps();
            if(config('database.connections.'.config('database.default').'.driver') == 'mysql'){
                $table->engine = 'InnoDB';
            }
            $table->charset = 'utf8';
            $table->collation = 'utf8_general_ci';
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('role_user');
        Schema::dropIfExists('permission_role');
        Schema::dropIfExists('permissions');
        Schema::dropIfExists('roles');
    }
}
