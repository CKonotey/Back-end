<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */

     public function up()
     {
         Schema::table('conversations', function (Blueprint $table) {
             $table->unsignedBigInteger('user_id')->nullable(); // For user ID
             $table->string('user_name')->nullable(); // For the user name

             $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
         });
     }
     /**
     * Reverse the migrations.
     */
     public function down()
     {
         Schema::table('conversations', function (Blueprint $table) {
             $table->dropColumn('user_id');
             $table->dropColumn('user_name');
         });
     }


};
