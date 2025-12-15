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
        Schema::table('product_price_rules', function (Blueprint $table) {
            // Remove the old generic value column
            $table->dropColumn('rule_value');

            // Add specific columns for better querying and data integrity
            $table->date('start_date')->nullable()->after('rule_type')->comment('规则生效开始日期');
            $table->date('end_date')->nullable()->after('start_date')->comment('规则生效结束日期');
            $table->string('days_of_week')->nullable()->after('end_date')->comment('适用周几，如 1,2,3,4,5，空表示不限');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('product_price_rules', function (Blueprint $table) {
            $table->dropColumn(['start_date', 'end_date', 'days_of_week']);
            $table->string('rule_value')->after('rule_type')->comment('规则的值');
        });
    }
};
