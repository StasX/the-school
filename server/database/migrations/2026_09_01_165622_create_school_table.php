<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('school')) {
            return;
        }
        Schema::create('school', function (Blueprint $table) {
            $table->unsignedInteger('Course_ID');
            $table->unsignedInteger('Student_ID');

            $table->primary([
                'Course_ID',
                'Student_ID',
            ]);

            $table->foreign('Course_ID')
                ->references('Course_ID')
                ->on('courses')
                ->cascadeOnDelete();

            $table->foreign('Student_ID')
                ->references('Student_ID')
                ->on('students')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('school');
    }
};
