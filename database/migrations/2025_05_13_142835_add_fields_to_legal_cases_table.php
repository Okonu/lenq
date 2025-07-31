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
        Schema::table('legal_cases', function (Blueprint $table) {
            $table->foreignId('law_firm_id')->nullable()->after('user_id')
                ->constrained()->onDelete('cascade');
            $table->foreignId('client_id')->nullable()->after('law_firm_id')
                ->constrained()->onDelete('set null');
            $table->string('category')->nullable()->after('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('legal_cases', function (Blueprint $table) {
            $table->dropForeign(['law_firm_id']);
            $table->dropForeign(['client_id']);
            $table->dropColumn(['law_firm_id', 'client_id', 'category']);
        });
    }
};
