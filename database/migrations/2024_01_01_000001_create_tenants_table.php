<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('tenants', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->enum('plan', ['free', 'starter', 'pro', 'enterprise'])->default('free');
            $table->boolean('is_active')->default(true);
            $table->json('settings')->nullable();
            $table->timestamp('trial_ends_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index('slug');
            $table->index('is_active');
        });
    }
    public function down(): void { Schema::dropIfExists('tenants'); }
};
