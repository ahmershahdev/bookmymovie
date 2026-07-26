<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->string('customer_name', 100)->nullable()->after('user_id');
            $table->string('customer_email', 150)->nullable()->after('customer_name');
            $table->string('customer_phone', 20)->nullable()->after('customer_email');
            $table->string('customer_address', 500)->nullable()->after('customer_phone');
        });
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropColumn([
                'customer_name',
                'customer_email',
                'customer_phone',
                'customer_address',
            ]);
        });
    }
};
