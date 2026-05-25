<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('imported_contents', function (Blueprint $table) {
            $table->id();
            $table->string('original_name');           // e.g. "Lecture 3.mp4"
            $table->string('stored_name');             // e.g. "lecture-3-abc123.mp4"
            $table->string('relative_path');           // e.g. "video/lecture-3-abc123.mp4"
            $table->string('category', 32);            // video | audio | document | image | archive | other
            $table->string('extension', 16);           // mp4, mp3, pdf, etc.
            $table->string('mime_type', 128)->nullable();
            $table->unsignedBigInteger('size_bytes')->default(0);
            $table->string('hash_sha256', 64)->nullable();
            $table->string('source_drive', 255)->nullable();   // e.g. "/media/usb1" or "G:\\"
            $table->string('scan_status', 32)->default('pending'); // pending|clean|infected|skipped|error
            $table->text('scan_message')->nullable();
            $table->foreignId('imported_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('imported_at')->nullable();
            $table->timestamps();

            $table->index('category');
            $table->index('scan_status');
            $table->index('imported_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('imported_contents');
    }
};
