<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('name', 150);
            $table->decimal('price', 12, 2);
            $table->text('description')->nullable();
            $table->unsignedBigInteger('category_id');
            $table->unsignedBigInteger('subcategory_id');
            $table->string('location', 100)->nullable();
            $table->string('tag', 50)->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->integer('stock')->default(0);

            $table->foreign('category_id')->references('id')->on('categories')->onUpdate('cascade');
            $table->foreign('subcategory_id')->references('id')->on('subcategories')->onUpdate('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
