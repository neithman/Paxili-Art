<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * ARTWORKS: the products sellers upload.
 *
 * status values:
 *   pending  = waiting for admin review (NOT visible in marketplace)
 *   approved = visible in marketplace
 *   rejected = admin rejected it (seller can edit & resubmit)
 *   removed  = admin deleted it for breaking the rules
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('artworks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained()->cascadeOnDelete();
            $table->foreignId('category_id')->nullable()->constrained()->nullOnDelete();
            $table->string('title');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('tags')->nullable();              // comma separated: "cat, cute, pastel"
            $table->decimal('price', 10, 2);
            $table->string('preview_path');                  // PUBLIC preview image (public disk)
            $table->string('file_path');                     // PRIVATE original file (local/private disk)
            $table->string('file_name');                     // original file name shown on download
            $table->string('status')->default('pending');
            $table->text('rejection_reason')->nullable();
            $table->timestamps();
            $table->softDeletes(); // keep the row so old orders/downloads still make sense

            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('artworks');
    }
};
