<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('resource_usage', function (Blueprint $table) {
            $table->id();
            $table->string('base_path')->comment('The monitored directory path');
            $table->bigInteger('file_count')->comment('Total number of files in the directory');
            $table->bigInteger('disk_usage_mb')->comment('Disk usage in megabytes');
            $table->bigInteger('available_inode')->comment('Available inodes on the filesystem');
            $table->bigInteger('available_space_mb')->comment('Available space in megabytes');
            $table->timestamp('checked_at')->comment('When the check was performed');
            $table->timestamps();

            // Add index for efficient querying by path and date
            $table->index(['base_path', 'checked_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('resource_usage');
    }
};
