<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('flow_diagrams', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('category')->nullable();
            $table->string('icon')->nullable();
            $table->string('trigger_code')->unique()->nullable();
            $table->longText('nodes'); // Using longText for larger JSON data
            $table->longText('edges'); // Using longText for larger JSON data
            $table->boolean('is_active')->default(true);
            $table->boolean('is_deletable')->default(true);
            $table->integer('version')->default(1);
            $table->unsignedBigInteger('parent_diagram_id')->nullable();
            $table->boolean('is_latest_version')->default(true);
            $table->text('version_notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down()
    {
        Schema::dropIfExists('flow_diagrams');
    }
};
