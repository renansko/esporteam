<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->date('birth_date')->nullable()->after('email_verified_at');
            $table->timestamp('adult_attested_at')->nullable()->after('birth_date');
            $table->boolean('is_adult')->default(false)->after('adult_attested_at');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn(['birth_date', 'adult_attested_at', 'is_adult']);
        });
    }
};
