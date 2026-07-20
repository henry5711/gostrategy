<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('dni', 45)->nullable()->unique()->after('id');
            $table->foreignId('region_id')->nullable()->after('dni')->constrained('regions');
            $table->foreignId('commune_id')->nullable()->after('region_id')->constrained('communes');
            $table->string('last_name', 45)->nullable()->after('name');
            $table->string('address', 255)->nullable()->after('email');
            $table->dateTime('date_reg')->nullable()->after('address');
            $table->string('status', 10)->nullable()->after('date_reg');
            $table->softDeletes();

            $table->string('password')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('region_id');
            $table->dropConstrainedForeignId('commune_id');
            $table->dropColumn(['dni', 'last_name', 'address', 'date_reg', 'status', 'deleted_at']);
            $table->string('password')->nullable(false)->change();
        });
    }
};
