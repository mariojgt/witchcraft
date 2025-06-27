<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        // Create simulation runs table
        Schema::create('flow_simulation_runs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('flow_diagram_id');
            $table->json('execution_log'); // Store the complete execution log
            $table->json('final_variables')->nullable(); // Store final variables state
            $table->enum('status', ['success', 'error', 'partial'])->default('success');
            $table->text('error_message')->nullable();
            $table->integer('total_nodes')->default(0);
            $table->integer('completed_nodes')->default(0);
            $table->timestamp('started_at');
            $table->timestamp('completed_at')->nullable();
            $table->integer('duration_ms')->nullable(); // Duration in milliseconds
            $table->timestamps();

            $table->foreign('flow_diagram_id')->references('id')->on('flow_diagrams')->onDelete('cascade');
            $table->index(['flow_diagram_id', 'created_at']);
            $table->index(['status', 'created_at']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('flow_simulation_runs');
    }
};
