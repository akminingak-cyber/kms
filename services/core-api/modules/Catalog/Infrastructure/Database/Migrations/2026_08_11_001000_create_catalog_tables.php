<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        /*
         * The editorial vocabulary. Channels and programmes reference a
         * category by identifier; when VOD arrives in a later phase, titles use
         * the same table rather than inventing a parallel one.
         */
        Schema::create('catalog.categories', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->uuid('uuid')->unique();
            $table->string('slug', 60)->unique();
            $table->string('name', 100);

            // Self-referencing, so it stays inside this schema and a real
            // foreign key is permitted.
            $table->unsignedBigInteger('parent_id')->nullable();
            $table->foreign('parent_id')
                ->references('id')->on('catalog.categories')
                ->nullOnDelete();

            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestampsTz();

            $table->index(['parent_id', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('catalog.categories');
    }
};
