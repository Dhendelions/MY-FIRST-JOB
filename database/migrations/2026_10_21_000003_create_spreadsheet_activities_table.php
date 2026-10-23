<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('spreadsheet_activities', function (Blueprint $table) {
            $table->id();
            $table->uuid('event_uuid')->unique();
            $table->foreignId('group_id')->constrained('fisheries_groups')->cascadeOnDelete();
            $table->foreignId('sheet_id')->nullable()->constrained('spreadsheet_sheets')->nullOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('action', 60);
            $table->string('cell_range', 80)->nullable();
            $table->text('old_value')->nullable();
            $table->text('new_value')->nullable();
            $table->json('details')->nullable();
            $table->timestamp('committed_at');
            $table->timestamps();
            $table->index(['group_id', 'sheet_id', 'committed_at']);
        });
    }

    public function down(): void { Schema::dropIfExists('spreadsheet_activities'); }
};
