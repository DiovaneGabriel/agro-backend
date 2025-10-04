<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Conexão onde a migration será executada.
     *
     * @var string
     */
    protected $connection = 'supabase';

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::connection($this->connection)->statement("
            CREATE VIEW vw_products AS
            SELECT p.*,
                   pb.name AS brand_name
              FROM products p
              JOIN product_brands pb ON pb.product_id = p.id
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::connection($this->connection)->statement("DROP VIEW IF EXISTS vw_products");
    }
};
