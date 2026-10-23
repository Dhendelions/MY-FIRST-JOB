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
        Schema::create('spreadsheet_sheets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('group_id')->constrained('fisheries_groups')->onDelete('cascade');
            $table->string('name');
            $table->string('category')->default('produksi'); // produksi, bahan, packaging, payroll, air-listrik, custom
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('spreadsheet_sheets');
    }
};
