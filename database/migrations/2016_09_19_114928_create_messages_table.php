<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateMessagesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('messages',function(Blueprint $table){
            $table->increments('id');
            $table->string('from_id');
            $table->string('to_id')->nullable();
            $table->string('message');
            $table->integer('read');
            $table->timestamps();
        });

        Schema::table('sessions',function($table){
            $table->integer('exec_last_activity')->after('last_activity')->default(0);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('sessions',function($table){
            $table->dropColumn(['exec_last_activity']);
        });

        Schema::drop('messages');
    }
}
