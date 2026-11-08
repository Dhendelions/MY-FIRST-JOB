<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'is_active')) {
                $table->boolean('is_active')->default(true)->after('role');
            }
            if (!Schema::hasColumn('users', 'last_active_at')) {
                $table->timestamp('last_active_at')->nullable()->after('is_active');
            }
        });

        if (!Schema::hasTable('company_activity_logs')) {
            Schema::create('company_activity_logs', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('user_id')->nullable()->index();
                $table->string('user_name')->nullable();
                $table->string('action', 100)->index();
                $table->string('resource_type', 100)->nullable()->index();
                $table->string('resource_id', 100)->nullable();
                $table->text('description');
                $table->json('properties')->nullable();
                $table->timestamp('created_at')->nullable()->index();

                $table->foreign('user_id')
                    ->references('id')
                    ->on('users')
                    ->nullOnDelete();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('company_activity_logs');

        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'last_active_at')) {
                $table->dropColumn('last_active_at');
            }
            if (Schema::hasColumn('users', 'is_active')) {
                $table->dropColumn('is_active');
            }
        });
    }
};

