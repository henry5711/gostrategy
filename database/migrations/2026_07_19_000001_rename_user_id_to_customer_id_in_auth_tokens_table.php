<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// El login de la API es de customers, no de users (el dashboard/Fortify). auth_tokens
// apuntaba por error a la tabla users; se repunta a customers.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('auth_tokens', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
        });

        Schema::table('auth_tokens', function (Blueprint $table) {
            $table->renameColumn('user_id', 'customer_id');
        });

        Schema::table('auth_tokens', function (Blueprint $table) {
            $table->foreign('customer_id')->references('id')->on('customers')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('auth_tokens', function (Blueprint $table) {
            $table->dropForeign(['customer_id']);
        });

        Schema::table('auth_tokens', function (Blueprint $table) {
            $table->renameColumn('customer_id', 'user_id');
        });

        Schema::table('auth_tokens', function (Blueprint $table) {
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
        });
    }
};
