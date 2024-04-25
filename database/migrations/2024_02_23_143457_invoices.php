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
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->string('invoice_id');
            $table->integer('serial_number');
            $table->string('customer_name');
            $table->string('customer_email');
            $table->string('customer_tax_number')->nullable();
            $table->string('customer_location')->nullable();
            $table->text('service');
            $table->text('price')->nullable();
            $table->text('discount')->nullable();
            $table->date('invoice_date')->nullable();
            $table->tinyInteger('is_paid')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};
