<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * STORES: every user can open ONE store. Having a store = seller features unlocked.
 * CATEGORIES: used to group artwork (e.g. "Illustration", "Photography").
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete(); // one store per user
            $table->string('name');
            $table->string('slug')->unique();          // unique store URL: /store/{slug}
            $table->text('description')->nullable();
            $table->string('logo')->nullable();        // path on the "public" disk
            $table->string('banner')->nullable();      // path on the "public" disk
            $table->timestamps();
        });

        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('categories');
        Schema::dropIfExists('stores');
    }
};
