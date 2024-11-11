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
        Schema::table('permissions', function (Blueprint $table) {
            $table->json('manual_routes')->nullable()->after('created_at');
            $table->json('route_selection_admin')->nullable()->after('manual_routes');
            $table->json('route_selection_api')->nullable()->after('route_selection_admin');
            $table->json('route_selection_others')->nullable()->after('route_selection_api');
        });
    }

    public function down()
    {
        Schema::table('permissions', function (Blueprint $table) {
            $table->dropColumn('manual_routes');
            $table->dropColumn('route_selection_admin');
            $table->dropColumn('route_selection_api');
            $table->dropColumn('route_selection_others');
        });
    }
};
