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
        Schema::create('characteristics', function (Blueprint $table) {
            $table->id();
            $table->string('name', 150)->comment('Nombre visible de la característica');
            $table->string('code', 100)->unique()->comment('Identificador interno en snake_case usado por el código para ubicar esta característica');
            $table->enum('status', Status::cases())->default(Status::Active)->comment('Estado de la característica: active o inactive');
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
        Schema::dropIfExists('characteristics');
    }
};
