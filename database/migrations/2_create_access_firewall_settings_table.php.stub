<?php

// database/migrations/xxxx_xx_xx_create_access_settings_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('access_firewall_settings', function (Blueprint $table) {
            $table->id();
            $table->boolean('enable_ip_location')->default(false);
            $table->boolean('enable_dns_location')->default(false);
            $table->boolean('enable_monitoring_control')->default(false);
            $table->boolean('enable_unknown_ip_alert')->default(false);
            $table->foreignId('super_main_administrator_id')
                ->nullable()
                ->constrained('users')
                ->onDelete('restrict');
            $table->string('api_base_path')->default('api/');
            $table->timestamps();
        });

        DB::table('access_firewall_settings')->insert([
            'enable_ip_location' => false,
            'enable_dns_location' => false,
            'enable_monitoring_control' => false,
            'enable_unknown_ip_alert' => false,
            'super_main_administrator_id' => null,
            'api_base_path' => 'api/',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down()
    {
        Schema::dropIfExists('access_firewall_settings');
    }
};
