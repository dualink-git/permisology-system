<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('black_location_lists', function (Blueprint $table) {
            $table->id();
            $table->string('blacklist_name');
            $table->enum('type_address', ['ip', 'dns']); // Puede ser 'ip' o 'dns'
            $table->string('address'); // Almacena la dirección (IP o DNS)
            $table->timestamps();

            $table->unique(['blacklist_name', 'type_address', 'address']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('black_location_lists');
    }
};
