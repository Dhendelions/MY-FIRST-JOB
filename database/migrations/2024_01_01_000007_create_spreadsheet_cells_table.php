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
        Schema::create('spreadsheet_cells', function (Blueprint $table) {
            $table->id();
            $table->foreignId('row_id')->constrained('spreadsheet_rows')->onDelete('cascade');
            $table->foreignId('column_id')->constrained('spreadsheet_columns')->onDelete('cascade');
            $table->text('value')->nullable();
            $table->timestamps();

            $table->unique(['row_id', 'column_id']);
            $table->index(['column_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('spreadsheet_cells');
    }
};
