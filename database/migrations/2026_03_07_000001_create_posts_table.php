<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('posts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete()->cascadeOnUpdate();
            $table->text('caption')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->integer('like_count')->default(0);
            $table->integer('comment_count')->default(0);
            $table->enum('status', ['active', 'hidden'])->default('active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('posts');
    }
};
