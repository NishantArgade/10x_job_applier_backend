<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('profiles', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->string('role')->nullable();
            $table->string('email')->unique()->nullable(false);
            $table->string('phone')->nullable();
            $table->json('skills')->nullable();
            $table->string('linkedin_url')->nullable();
            $table->string('naukri_url')->nullable();
            $table->string('github_url')->nullable();
            $table->string('website_url')->nullable();
            $table->string('current_company')->nullable();
            $table->string('current_designation')->nullable();
            $table->string('current_location')->nullable();
            $table->integer('notice_period')->nullable()->comment('Notice period in days');
            $table->float('experience', 2)->nullable()->comment('Experience in years and months (e.g., 1.10)');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('resume_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('template_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('profiles');
    }
};
