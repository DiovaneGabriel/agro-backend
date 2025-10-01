<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // ---- Tabelas de referência/lookup ----
        Schema::create('chemical_groups', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->timestamps();
        });

        Schema::create('active_ingredients', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->foreignId('chemical_group_id')
                ->nullable()
                ->constrained('chemical_groups')
                ->cascadeOnUpdate()
                ->restrictOnDelete();
            $table->timestamps();
            $table->index('chemical_group_id');
        });

        Schema::create('classes', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->timestamps();
        });

        Schema::create('action_mechanisms', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->timestamps();
        });

        Schema::create('action_modes', function (Blueprint $table) {
            $table->id();
            $table->string('description')->unique();
            $table->timestamps();
        });

        Schema::create('cultures', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->timestamps();
        });

        Schema::create('pragues', function (Blueprint $table) {
            $table->id();
            $table->string('scientific_name')->unique();
            $table->timestamps();
        });

        Schema::create('prague_common_names', function (Blueprint $table) {
            $table->id();
            $table->foreignId('prague_id')
                ->constrained('pragues')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();
            $table->string('name');
            $table->timestamps();
            $table->unique(['prague_id', 'name']);
            $table->index('prague_id');
        });

        Schema::create('countries', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->timestamps();
        });

        Schema::create('companies', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->foreignId('country_id')
                ->nullable()
                ->constrained('countries')
                ->cascadeOnUpdate()
                ->restrictOnDelete();
            $table->timestamps();
            $table->unique(['name', 'country_id']);
            $table->index('country_id');
        });

        Schema::create('company_types', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->timestamps();
        });

        Schema::create('formulations', function (Blueprint $table) {
            $table->id();
            $table->string('description')->unique();
            $table->timestamps();
        });

        Schema::create('registration_holders', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->timestamps();
        });

        Schema::create('toxicological_classes', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->timestamps();
        });

        Schema::create('environmental_classes', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->timestamps();
        });

        // ---- Products ----
        Schema::create('products', function (Blueprint $table) {
            $table->id();

            $table->boolean('is_active')->default(true);
            $table->boolean('is_organic')->default(false);

            $table->string('register_number')->unique();

            $table->foreignId('formulation_id')
                ->nullable()
                ->constrained('formulations')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            $table->foreignId('registration_holder_id')
                ->nullable()
                ->constrained('registration_holders')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            $table->foreignId('toxicological_class_id')
                ->nullable()
                ->constrained('toxicological_classes')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            $table->foreignId('environmental_class_id')
                ->nullable()
                ->constrained('environmental_classes')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            // Códigos/classificações HRAC & WSSA (texto curto, opcional)
            $table->string('hrac', 50)
                ->nullable()
                ->comment('Herbicide Resistance Action Committee code');
            $table->string('wssa', 50)
                ->nullable()
                ->comment('Weed Science Society of America code');

            $table->timestamps();

            $table->index(['formulation_id', 'registration_holder_id']);
            $table->index(['toxicological_class_id', 'environmental_class_id']);
        });

        Schema::create('product_brands', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')
                ->constrained('products')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();
            $table->string('name');
            $table->timestamps();
            $table->unique(['product_id', 'name']);
            $table->index('product_id');
        });

        Schema::create('product_active_ingredients', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')
                ->constrained('products')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();
            $table->foreignId('active_ingredient_id')
                ->constrained('active_ingredients')
                ->cascadeOnUpdate()
                ->restrictOnDelete();
            $table->string('concentration')->nullable();
            $table->timestamps();
            $table->unique(['product_id', 'active_ingredient_id'], 'u_prod_ai');
            $table->index(['product_id', 'active_ingredient_id']);
        });

        Schema::create('product_classes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')
                ->constrained('products')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();
            $table->foreignId('class_id')
                ->constrained('classes')
                ->cascadeOnUpdate()
                ->restrictOnDelete();
            $table->foreignId('action_mechanism_id')
                ->nullable()
                ->constrained('action_mechanisms')
                ->cascadeOnUpdate()
                ->restrictOnDelete();
            $table->timestamps();
            $table->unique(['product_id', 'class_id', 'action_mechanism_id'], 'u_prod_class_mech');
            $table->index(['product_id', 'class_id']);
        });

        Schema::create('product_action_modes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')
                ->constrained('products')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();
            $table->foreignId('action_mode_id')
                ->constrained('action_modes')
                ->cascadeOnUpdate()
                ->restrictOnDelete();
            $table->timestamps();
            $table->unique(['product_id', 'action_mode_id']);
            $table->index(['product_id', 'action_mode_id']);
        });

        Schema::create('product_cultures', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')
                ->constrained('products')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();
            $table->foreignId('culture_id')
                ->constrained('cultures')
                ->cascadeOnUpdate()
                ->restrictOnDelete();
            $table->timestamps();
            $table->unique(['product_id', 'culture_id']);
            $table->index(['product_id', 'culture_id']);
        });

        Schema::create('product_pragues', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')
                ->constrained('products')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();
            $table->foreignId('prague_id')
                ->constrained('pragues')
                ->cascadeOnUpdate()
                ->restrictOnDelete();
            $table->timestamps();
            $table->unique(['product_id', 'prague_id']);
            $table->index(['product_id', 'prague_id']);
        });

        Schema::create('product_companies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')
                ->constrained('products')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();
            $table->foreignId('company_id')
                ->constrained('companies')
                ->cascadeOnUpdate()
                ->restrictOnDelete();
            $table->foreignId('company_type_id')
                ->constrained('company_types')
                ->cascadeOnUpdate()
                ->restrictOnDelete();
            $table->timestamps();
            $table->unique(['product_id', 'company_id', 'company_type_id'], 'u_prod_company_type');
            $table->index(['product_id', 'company_id', 'company_type_id']);
        });
    }

    public function down(): void
    {
        // Drop na ordem inversa das dependências
        Schema::dropIfExists('product_companies');
        Schema::dropIfExists('product_pragues');
        Schema::dropIfExists('product_cultures');
        Schema::dropIfExists('product_action_modes');
        Schema::dropIfExists('product_classes');
        Schema::dropIfExists('product_active_ingredients');
        Schema::dropIfExists('product_brands');
        Schema::dropIfExists('products');

        Schema::dropIfExists('environmental_classes');
        Schema::dropIfExists('toxicological_classes');
        Schema::dropIfExists('registration_holders');
        Schema::dropIfExists('formulations');
        Schema::dropIfExists('company_types');
        Schema::dropIfExists('companies');
        Schema::dropIfExists('countries');
        Schema::dropIfExists('prague_common_names');
        Schema::dropIfExists('pragues');
        Schema::dropIfExists('cultures');
        Schema::dropIfExists('action_modes');
        Schema::dropIfExists('action_mechanisms');
        Schema::dropIfExists('classes');
        Schema::dropIfExists('active_ingredients');
        Schema::dropIfExists('chemical_groups');
    }
};
