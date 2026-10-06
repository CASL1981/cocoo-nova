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
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('identification')->unique();
            $table->string('name');
            $table->string('lastname');
            $table->string('area')->nullable();
            $table->string('email')->unique();
            $table->string('status')->default('active');
            // No va en $fillable a propósito: solo el propio sistema debe poder tocarla,
            // nunca un formulario (ni el de crear, ni el de editar usuario).
            $table->boolean('must_change_password')->default(false);
            $table->unsignedBigInteger('role_id')->nullable();
            $table->string('destination')->nullable();
            $table->string('profile_photo_path', 2048)->nullable();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->rememberToken();
            $table->softDeletes();        // deleted_at (Laravel)
            $table->userstamps();         // created_by, updated_by (paquete)
            $table->userstampSoftDeletes();
            $table->timestamps();
        });

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('sessions');
    }
};
