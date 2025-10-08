<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        /**
         * 1) products: remover hrac/wssa
         */
        if (Schema::hasTable('products')) {
            Schema::table('products', function (Blueprint $table) {
                if (Schema::hasColumn('products', 'hrac')) {
                    $table->dropColumn('hrac');
                }

                if (Schema::hasColumn('products', 'wssa')) {
                    $table->dropColumn('wssa');
                }
            });
        }

        /**
         * 2) action_mechanisms: adicionar hrac/wssa
         *    (strings curtas e opcionais; ajuste o tamanho se precisar)
         */
        if (Schema::hasTable('action_mechanisms')) {
            Schema::table('action_mechanisms', function (Blueprint $table) {
                if (!Schema::hasColumn('action_mechanisms', 'hrac')) {
                    $table->string('hrac', 50)->nullable()->after('name');
                }
                if (!Schema::hasColumn('action_mechanisms', 'wssa')) {
                    $table->string('wssa', 50)->nullable()->after('hrac');
                }
            });
        }

        /**
         * 3) product_classes: remover action_mechanism_id
         */
        if (Schema::hasTable('product_classes') && Schema::hasColumn('product_classes', 'action_mechanism_id')) {
            // dropar FK se existir
            try {
                Schema::table('product_classes', function (Blueprint $table) {
                    $table->dropForeign(['action_mechanism_id']);
                });
            } catch (\Throwable $e) {
                // segue o baile
            }

            Schema::table('product_classes', function (Blueprint $table) {
                $table->dropColumn('action_mechanism_id');
            });
        }

        /**
         * 4) active_ingredient_action_mechanisms: criar tabela pivô
         */
        if (!Schema::hasTable('active_ingredient_action_mechanisms')) {
            Schema::create('active_ingredient_action_mechanisms', function (Blueprint $table) {
                $table->id();

                $table->foreignId('active_ingredient_id')
                    ->constrained('active_ingredients')
                    ->cascadeOnUpdate()
                    ->cascadeOnDelete();

                $table->foreignId('class_id')
                    ->constrained('classes')
                    ->cascadeOnUpdate()
                    ->restrictOnDelete();

                $table->foreignId('action_mechanism_id')
                    ->constrained('action_mechanisms')
                    ->cascadeOnUpdate()
                    ->restrictOnDelete();

                $table->timestamps();

                // Evita duplicidade da mesma combinação
                $table->unique(['active_ingredient_id', 'class_id', 'action_mechanism_id'], 'aiam_unique_triplet');
            });
        }
    }

    public function down(): void
    {
        /**
         * Reverter 4) dropar a pivô
         */
        if (Schema::hasTable('active_ingredient_action_mechanisms')) {
            Schema::dropIfExists('active_ingredient_action_mechanisms');
        }

        /**
         * Reverter 3) product_classes: adicionar action_mechanism_id novamente
         */
        if (Schema::hasTable('product_classes') && !Schema::hasColumn('product_classes', 'action_mechanism_id')) {
            Schema::table('product_classes', function (Blueprint $table) {
                $table->foreignId('action_mechanism_id')
                    ->nullable() // deixe nullable para facilitar rollback
                    ->constrained('action_mechanisms')
                    ->cascadeOnUpdate()
                    ->nullOnDelete();
            });
        }

        /**
         * Reverter 2) action_mechanisms: remover hrac/wssa
         */
        if (Schema::hasTable('action_mechanisms')) {
            Schema::table('action_mechanisms', function (Blueprint $table) {
                if (Schema::hasColumn('action_mechanisms', 'wssa')) {
                    // $table->dropIndex(['wssa']);
                    // $table->dropUnique(['wssa']);
                    // try {
                    // } catch (\Throwable $e) {
                    // }
                    // try {
                    // } catch (\Throwable $e) {
                    // }
                    // $table->dropColumn('wssa');
                }
                // if (Schema::hasColumn('action_mechanisms', 'hrac')) {
                //     try {
                //         $table->dropIndex(['hrac']);
                //     } catch (\Throwable $e) {
                //     }
                //     try {
                //         $table->dropUnique(['hrac']);
                //     } catch (\Throwable $e) {
                //     }
                //     $table->dropColumn('hrac');
                // }
            });
        }

        /**
         * Reverter 1) products: adicionar hrac/wssa de volta
         */
        if (Schema::hasTable('products')) {
            Schema::table('products', function (Blueprint $table) {
                if (!Schema::hasColumn('products', 'hrac')) {
                    $table->string('hrac', 50)->nullable()->after('environmental_class_id');
                }
                if (!Schema::hasColumn('products', 'wssa')) {
                    $table->string('wssa', 50)->nullable()->after('hrac');
                }
            });
        }
    }
};
