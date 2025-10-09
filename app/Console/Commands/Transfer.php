<?php

namespace App\Console\Commands;

use App\Models\ActionMechanism;
use App\Models\ActionMode;
use App\Models\ActiveIngredient;
use App\Models\ActiveIngredientActionMechanisms;
use App\Models\AgroClass;
use App\Models\ChemicalGroup;
use App\Models\CommonPrague;
use App\Models\Company;
use App\Models\CompanyType;
use App\Models\Country;
use App\Models\Culture;
use App\Models\EnvironmentalClass;
use App\Models\Formulation;
use App\Models\Prague;
use App\Models\PragueCommonName;
use App\Models\Product;
use App\Models\ProductActionMode;
use App\Models\ProductActiveIngredient;
use App\Models\ProductBrand;
use App\Models\ProductClass;
use App\Models\ProductCompany;
use App\Models\ProductCulture;
use App\Models\ProductPrague;
use App\Models\RegistrationHolder;
use App\Models\ToxicologicalClass;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class Transfer extends Command
{
    protected $signature = 'transfer:agrofit';
    protected $description = 'Transfere os dados entre bancos';

    public function handle()
    {
        $this->clear();
        $this->makeTransfer();

        $this->info('✅ Transferencia de dados finalizada.');
        return 0;
    }

    private function clear()
    {
        DB::setDefaultConnection('supabase');

        ProductCompany::query()->forceDelete();
        CompanyType::query()->forceDelete();
        Company::query()->forceDelete();
        Country::query()->forceDelete();
        PragueCommonName::query()->forceDelete();
        CommonPrague::query()->forceDelete();
        ProductPrague::query()->forceDelete();
        Prague::query()->forceDelete();
        ProductCulture::query()->forceDelete();
        Culture::query()->forceDelete();
        ProductActionMode::query()->forceDelete();
        ActionMode::query()->forceDelete();
        ActiveIngredientActionMechanisms::query()->forceDelete();
        ProductClass::query()->forceDelete();
        AgroClass::query()->forceDelete();
        ProductActiveIngredient::query()->forceDelete();
        ActiveIngredient::query()->forceDelete();
        ChemicalGroup::query()->forceDelete();
        ProductBrand::query()->forceDelete();
        Product::query()->forceDelete();
        EnvironmentalClass::query()->forceDelete();
        ToxicologicalClass::query()->forceDelete();
        RegistrationHolder::query()->forceDelete();
        Formulation::query()->forceDelete();

        $this->info("dados apagados");
    }

    private function makeTransfer()
    {

        $this->transfer(Formulation::class);
        $this->transfer(RegistrationHolder::class);
        $this->transfer(ToxicologicalClass::class);
        $this->transfer(EnvironmentalClass::class);
        $this->transfer(Product::class);
        $this->transfer(ProductBrand::class);
        $this->transfer(ChemicalGroup::class);
        $this->transfer(ActiveIngredient::class);
        $this->transfer(ProductActiveIngredient::class);
        $this->transfer(AgroClass::class);
        $this->transfer(ProductClass::class);
        $this->transfer(ActionMode::class);
        $this->transfer(ProductActionMode::class);
        $this->transfer(Culture::class);
        $this->transfer(ProductCulture::class);
        $this->transfer(Prague::class);
        $this->transfer(CommonPrague::class);
        $this->transfer(ProductPrague::class);
        $this->transfer(PragueCommonName::class);
        $this->transfer(Country::class);
        $this->transfer(Company::class);
        $this->transfer(CompanyType::class);
        $this->transfer(ProductCompany::class);
        $this->transfer(ActionMechanism::class);
        $this->transfer(ActiveIngredientActionMechanisms::class);
    }

    private function transfer($class)
    {
        $table = $class::getModel()->getTable();

        $rows = DB::connection('pgsql')->table($table)->get();

        $dados = $rows->map(function ($item) {
            return (array) $item;
        })->toArray();

        $lotes = array_chunk($dados, 10000);

        foreach ($lotes as $i => $lote) {
            DB::connection('supabase')->table($table)->insertOrIgnore($lote);
            $this->info($table . " lote: " . ($i + 1));
        }
    }
}
