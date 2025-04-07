<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
       

        Schema::create('accounts', function (Blueprint $table) {
            $table->id();

            $table->unsignedTinyInteger('register_users')->default(1);
            $table->unsignedTinyInteger('active_users')->default(1);
            $table->decimal('price', 8, 2)->default(1000);
            $table->string('phone_number', 20)->nullable();
            $table->string('phone_token')->nullable();
            $table->string('nickname')->nullable();
            $table->string('place_name')->nullable();
            $table->string('image')->nullable();

            $table->unsignedBigInteger('doctor_id')->nullable();
            $table->unsignedBigInteger('status_id')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->foreign('doctor_id')->references('id')->on('doctors');
            $table->foreign('status_id')->references('id')->on('statuses');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('accounts');
    }
};
