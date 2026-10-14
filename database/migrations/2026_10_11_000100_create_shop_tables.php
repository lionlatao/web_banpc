<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', fn (Blueprint $t) => $t->boolean('is_admin')->default(false));
        Schema::create('categories', function (Blueprint $t) {
            $t->id();
            $t->string('name');
            $t->string('slug')->unique();
            $t->timestamps();
        });
        Schema::create('products', function (Blueprint $t) {
            $t->id();
            $t->foreignId('category_id')->constrained()->restrictOnDelete();
            $t->string('name');
            $t->string('brand');
            $t->text('description');
            $t->unsignedBigInteger('price');
            $t->unsignedInteger('stock')->default(0);
            $t->json('specs')->nullable();
            $t->boolean('active')->default(true);
            $t->timestamps();
        });
        Schema::create('orders', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->constrained()->restrictOnDelete();
            $t->string('recipient');
            $t->string('phone', 30);
            $t->text('address');
            $t->text('note')->nullable();
            $t->unsignedBigInteger('total');
            $t->string('status')->default('pending');
            $t->string('checkout_token', 64)->unique();
            $t->timestamps();
        });
        Schema::create('order_items', function (Blueprint $t) {
            $t->id();
            $t->foreignId('order_id')->constrained()->cascadeOnDelete();
            $t->foreignId('product_id')->constrained()->restrictOnDelete();
            $t->string('product_name');
            $t->unsignedBigInteger('price');
            $t->unsignedInteger('quantity');
            $t->timestamps();
        });
        Schema::create('pc_builds', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->constrained()->cascadeOnDelete();
            $t->string('name');
            $t->timestamps();
        });
        Schema::create('build_items', function (Blueprint $t) {
            $t->id();
            $t->foreignId('pc_build_id')->constrained()->cascadeOnDelete();
            $t->foreignId('product_id')->constrained()->restrictOnDelete();
            $t->string('slot');
            $t->unique(['pc_build_id', 'slot']);
            $t->timestamps();
        });
    }

    public function down(): void
    {
        foreach (['build_items', 'pc_builds', 'order_items', 'orders', 'products', 'categories'] as $table) {
            Schema::dropIfExists($table);
        }Schema::table('users', fn (Blueprint $t) => $t->dropColumn('is_admin'));
    }
};
