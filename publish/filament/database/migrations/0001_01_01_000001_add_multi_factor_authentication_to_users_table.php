<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->after('remember_token', function (Blueprint $table) {
                if (! Schema::hasColumn('users', 'app_authentication_secret')) {
                    $table->text('app_authentication_secret')->nullable();
                }

                if (! Schema::hasColumn('users', 'app_authentication_recovery_codes')) {
                    $table->text('app_authentication_recovery_codes')->nullable();
                }

                if (! Schema::hasColumn('users', 'has_email_authentication')) {
                    $table->boolean('has_email_authentication')->default(false);
                }
            });
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'app_authentication_secret')) {
                $table->dropColumn('app_authentication_secret');
            }

            if (Schema::hasColumn('users', 'app_authentication_recovery_codes')) {
                $table->dropColumn('app_authentication_recovery_codes');
            }

            if (Schema::hasColumn('users', 'has_email_authentication')) {
                $table->dropColumn('has_email_authentication');
            }
        });
    }
};
