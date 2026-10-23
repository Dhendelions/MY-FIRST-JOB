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
        Schema::create('spreadsheet_rows', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sheet_id')->constrained('spreadsheet_sheets')->onDelete('cascade');
            $table->integer('sort_order')->default(1);
            $table->timestamps();

            $table->index(['sheet_id', 'sort_order']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('spreadsheet_rows');
    }
};
