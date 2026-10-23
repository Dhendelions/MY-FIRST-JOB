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
        Schema::create('fisheries_groups', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('category')->default('Budidaya Lele'); // Budidaya Lele, Nila, Gurami, Udang, dll
            $table->string('invite_code')->unique(); // cth: LELE-MKMR-88
            $table->string('location');
            $table->integer('pond_count')->default(1);
            $table->string('avatar')->nullable();
            $table->foreignId('created_by')->constrained('users')->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fisheries_groups');
    }
};
