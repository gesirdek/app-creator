<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateRoleUserTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('role_user', function(Blueprint $table)
        {
            $table->integer('role_id')->unsigned();
            $table->foreign('role_id')->references('id')
                ->on('roles');

            $table->integer('user_id')->unsigned();
            $table->foreign('user_id')->references('id')
                ->on('users');

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
        //
    }
}
