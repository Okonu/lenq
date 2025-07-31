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
        Schema::create('generated_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('law_firm_id')->constrained()->onDelete('cascade');
            $table->foreignId('conversation_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('legal_case_id')->nullable()->constrained()->onDelete('set null');

            $table->string('title');
            $table->enum('type', ['contract', 'agreement', 'letter', 'memo', 'brief', 'motion', 'general']);
            $table->enum('format', ['pdf', 'docx', 'html']);

            $table->text('generation_prompt');
            $table->longText('content');

            $table->timestamp('downloaded_at')->nullable();
            $table->integer('download_count')->default(0);

            $table->json('generation_metadata')->nullable();

            $table->timestamps();

            $table->index(['user_id', 'created_at']);
            $table->index(['conversation_id']);
            $table->index(['legal_case_id']);
            $table->index('type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('generated_documents');
    }
};
