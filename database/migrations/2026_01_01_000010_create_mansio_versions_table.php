<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mansio_versions', function (Blueprint $table): void {
            $table->uuid('id')->primary()->default(DB::raw('uuidv7()'));
            $table->string('shareable_type');
            $table->uuid('shareable_id');
            $table->integer('sequence');
            $table->string('content_path');
            $table->string('mime');
            $table->bigInteger('size_bytes');
            $table->string('checksum');
            $table->string('source_ref')->nullable();
            $table->string('summary')->nullable();
            $table->string('published_by')->nullable();
            $table->string('published_by_type')->nullable();
            $table->timestamp('published_at');
            $table->timestamp('created_at')->nullable();

            $table->index(['shareable_type', 'shareable_id', 'sequence']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mansio_versions');
    }
};
