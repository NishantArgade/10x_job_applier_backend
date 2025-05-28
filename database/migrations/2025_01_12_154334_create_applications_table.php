<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('applications', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->string('company')->nullable();
            $table->string('email')->nullable(false);
            $table->string('phone')->nullable();
            $table->string('website')->nullable();
            $table->string('location')->nullable();
            $table->string('apply_for')->nullable();
            $table->dateTime('apply_at')->default(now());
            $table->dateTime('followup_at')->default(now()->addDays(3));
            $table->integer('followup_after_days')->default(3);
            $table->integer('followup_freq')->default(3);
            $table->enum('status', ['pending', 'sent', 'failed'])->default('pending');
            $table->string('source')->nullable();
            $table->boolean('recruitor_reply')->default(false);
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('template_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('resume_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();

            // Indexes
            $table->index(['user_id']);
            $table->unique(['email', 'apply_for']);
            $table->index(['recruitor_reply']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('applications');
    }
};
