<?php

use App\Enums\Status;
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
        Schema::create('characteristic_options', function (Blueprint $table) {
            $table->id();
            $table->foreignId('characteristic_id')->comment('Característica a la que pertenece esta opción')->constrained();
            $table->string('name', 150)->comment('Valor de la opción, visible al usuario');
            $table->enum('status', Status::cases())->default(Status::Active)->comment('Estado de la opción: active o inactive');
            $table->softDeletes();
            $table->userstamps();
            $table->userstampSoftDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('characteristic_options');
    }
};
