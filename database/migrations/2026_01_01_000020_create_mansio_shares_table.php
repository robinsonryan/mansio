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
        Schema::create('mansio_shares', function (Blueprint $table): void {
            $table->uuid('id')->primary()->default(DB::raw('uuidv7()'));
            $table->string('shareable_type');
            $table->uuid('shareable_id');
            $table->string('owner_type')->nullable();
            $table->uuid('owner_id')->nullable();
            $table->string('token')->unique();
            $table->uuid('pinned_version_id')->nullable();
            $table->string('label')->nullable();
            $table->string('status')->default('active');
            $table->timestamp('expires_at')->nullable();
            $table->string('password_hash')->nullable();
            $table->integer('max_views')->nullable();
            $table->integer('view_count')->default(0);
            $table->boolean('one_time')->default(false);
            $table->json('settings')->nullable();
            $table->timestamp('revoked_at')->nullable();
            $table->timestamps();

            $table->index(['shareable_type', 'shareable_id']);
            $table->index('expires_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mansio_shares');
    }
};
