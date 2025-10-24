<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('ad_script_tasks', function (Blueprint $table) {
            $table->id();
            $table->longText('reference_script');
            $table->text('outcome_description');
            $table->longText('new_script')->nullable();
            $table->longText('analysis')->nullable();
            $table->string('status')->default('pending');
            $table->longText('error_details')->nullable();
            $table->timestamps();
        });

    }

    public function down(): void
    {
        Schema::dropIfExists('ad_script_tasks');
    }
};
