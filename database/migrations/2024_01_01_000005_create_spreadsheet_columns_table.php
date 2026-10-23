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
        Schema::create('spreadsheet_columns', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sheet_id')->constrained('spreadsheet_sheets')->onDelete('cascade');
            $table->string('letter', 10); // A, B, C, dst
            $table->string('title')->nullable()->default(''); // Nama kolom buatan user, default kosong
            $table->integer('width')->default(160); // 110, 160, 230, 320 px
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->index(['sheet_id', 'sort_order']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('spreadsheet_columns');
    }
};
