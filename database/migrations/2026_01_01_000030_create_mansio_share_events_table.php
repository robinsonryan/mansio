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
        Schema::create('mansio_share_events', function (Blueprint $table): void {
            $table->uuid('id')->primary()->default(DB::raw('uuidv7()'));
            $table->uuid('share_id')->index();
            $table->uuid('version_id')->nullable();
            $table->string('type');
            $table->string('ip')->nullable();
            $table->string('user_agent')->nullable();
            $table->json('meta')->nullable();
            $table->timestamp('created_at')->nullable();

            $table->index(['share_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mansio_share_events');
    }
};
