<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('flow_executions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('flow_diagram_id')->constrained('flow_diagrams')->onDelete('cascade');
            $table->string('trigger_type'); // 'manual', 'model_event', 'variable'
            $table->string('status'); // 'started', 'completed', 'failed'
            $table->timestamp('started_at');
            $table->timestamp('completed_at')->nullable();
            $table->float('execution_time')->nullable(); // in seconds
            $table->json('variables')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamps();
        });

        Schema::create('flow_node_executions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('flow_execution_id')->constrained()->onDelete('cascade');
            $table->string('node_id');
            $table->string('node_type');
            $table->string('status'); // 'started', 'completed', 'failed', 'skipped'
            $table->timestamp('started_at');
            $table->timestamp('completed_at')->nullable();
            $table->float('execution_time')->nullable(); // in seconds
            $table->json('input_data')->nullable();
            $table->json('output_data')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('flow_node_executions');
        Schema::dropIfExists('flow_executions');
    }
};
