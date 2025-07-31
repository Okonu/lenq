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
        Schema::create('firm_members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('law_firm_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null');
            $table->string('role')->default('staff');
            $table->string('title')->nullable();
            $table->string('department')->nullable();
            $table->string('status')->default('invited');
            $table->string('invitation_token')->nullable();
            $table->timestamp('invitation_accepted_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('firm_members');
    }
};
