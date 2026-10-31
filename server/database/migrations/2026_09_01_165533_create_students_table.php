<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('students')) {
            return;
        }
        Schema::create('students', function (Blueprint $table) {
            $table->increments('Student_ID');
            $table->string('Email', 64)->unique();
            $table->string('Name', 32);
            $table->string('Phone', 54);
            $table->string('Image', 255);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('students');
    }
};
