<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('user_location_access_end_point_lists', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('association_type');
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('cascade');
            $table->foreignId('role_id')->nullable()->constrained()->onDelete('cascade');
            $table->json('ip_addresses')->nullable();
            $table->json('dns_addresses')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('user_location_access_end_point_lists');
    }
};
