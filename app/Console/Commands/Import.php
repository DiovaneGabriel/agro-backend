<?php

namespace App\Console\Commands;

use App\Models\ActionMode;
use App\Models\ActiveIngredient;
use App\Models\AgroClass;
use App\Models\ChemicalGroup;
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
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class Import extends Command
{
    protected $signature = 'import:agrofit';
    protected $description = 'Copia os dados do agrofit';

    public function handle()
    {
        // $this->unzip();
        $this->clear();

        $path = storage_path('app/unzipped/base.csv'); // caminho físico
        $delimiter = ';';

        $stream = @fopen($path, 'r');
        if ($stream === false) {
            $this->error("Não consegui abrir o arquivo CSV em: {$path}");
            return 1;
        }

        $header = null;
        $line   = 0; // linha real lida do arquivo (inclui header)

        try {
            while (($row = fgetcsv($stream, 0, $delimiter)) !== false) {
                $line++;

                // Header
                if ($header === null) {
                    if (isset($row[0])) {
                        // remove BOM da 1ª coluna, se houver
                        $row[0] = preg_replace('/^\xEF\xBB\xBF/', '', $row[0]);
                    }
                    $header = $row;
                    continue;
                }

                try {
                    // Pad se vier com menos colunas
                    if (count($row) < count($header)) {
                        $row = array_pad($row, count($header), null);
                    }

                    // Se vier com mais colunas, avisa/erra (opcional: truncar)
                    if (count($row) > count($header)) {
                        throw new \RuntimeException(sprintf(
                            'Quantidade de colunas maior que o header: esperado %d, recebido %d',
                            count($header),
                            count($row)
                        ));
                    }

                    // array_combine seguro
                    $item = @array_combine($header, $row);
                    if ($item === false) {
                        throw new \RuntimeException('Falha ao combinar header com a linha (array_combine retornou false).');
                    }

                    $item = array_values($item);

                    $id = $item[15];
                    $isOrganic = self::normalize($item[13]) != 'NÃO' ? true : false;
                    $isActive = self::normalize($item[14]) != 'TRUE' ? false : true;
                    $registerNumber = self::normalize($item[0]);

                    $product = Product::where('reference_id', $id)->firstOrNew();
                    $product->reference_id = $id;
                    $product->is_active = $isActive;
                    $product->is_organic = $isOrganic;
                    $product->register_number = $registerNumber;
                    // $product->HRAC
                    // $product->WSSA
                    $product->save();

                    $this->formulation($product, $item);
                    $this->registrationHolder($product, $item);
                    $this->toxicologicalClass($product, $item);
                    $this->environmentalClass($product, $item);

                    $this->brands($product, $item);
                    $this->activeIngredients($product, $item);
                    $this->classes($product, $item);
                    $this->actionMode($product, $item);
                    $this->cultures($product, $item);
                    $this->pragues($product, $item);
                    $this->companies($product, $item);

                    $this->info(var_dump($item));
                    die();
                } catch (\Throwable $e) {
                    $raw = implode($delimiter, array_map(
                        fn($v) => is_null($v) ? '' : (string) $v,
                        $row
                    ));

                    $msg = "Erro na linha {$line}: {$e->getMessage()} | Conteúdo: {$raw}";
                    throw new Exception($msg);
                }
            }
        } finally {
            if (is_resource($stream)) {
                fclose($stream);
            }
        }

        $this->info('✅ Migração dos dados finalizada.');
        return 0;
    }

    private static function normalize($value)
    {
        $value = mb_convert_encoding($value, 'UTF-8', 'UTF-8, ISO-8859-1, Windows-1252');
        $value = str_replace(".", "", $value);
        $value = trim($value);
        // $value = Str::title($value);

        return $value;
    }

    private static function toArray($value, array $separators = [" e ", ", ", "/", ";"])
    {
        foreach ($separators as $separator) {
            $value = str_replace($separator, "+", $value);
        }

        $array = explode("+", $value);
        $array = array_map(fn($v) => self::normalize($v), $array);

        return $array;
    }

    private function unzip()
    {
        $zip = new \ZipArchive;
        $zipPath = storage_path('app/private/base.zip');
        $extractPath = storage_path('app/unzipped/');

        if ($zip->open($zipPath) === true) {
            $zip->extractTo($extractPath);
            $zip->close();
            $this->info("Arquivo extraído em: {$extractPath}");
        } else {
            $this->error("Erro ao abrir o arquivo ZIP.");
        }
    }

    private function formulation(Product $product, $item)
    {
        $formulationDescription = self::normalize($item[2]);

        $formulation = Formulation::whereRaw("lower(description) = ?", strtolower($formulationDescription))->firstOrNew();
        $formulation->description = $formulationDescription;
        $formulation->save();

        DB::table('cms.products_formulation_links')->updateOrInsert(
            [
                'product_id' => $product->id,
                'formulation_id' => $formulation->id,
            ],
        );
    }

    private function registrationHolder(Product $product, $item)
    {
        $registrationHolderName = self::normalize($item[4]);
        $registrationHolder = RegistrationHolder::whereRaw("lower(name) = ?", strtolower($registrationHolderName))->firstOrNew();
        $registrationHolder->name = $registrationHolderName;
        $registrationHolder->save();

        DB::table('cms.products_registration_holder_links')->updateOrInsert(
            [
                'product_id' => $product->id,
                'registration_holder_id' => $registrationHolder->id,
            ],
        );
    }


    private function toxicologicalClass(Product $product, $item)
    {
        $toxicologicalClassName = self::normalize($item[11]);
        $toxicologicalClass = ToxicologicalClass::whereRaw("lower(name) = ?", strtolower($toxicologicalClassName))->firstOrNew();
        $toxicologicalClass->name = $toxicologicalClassName;
        $toxicologicalClass->save();

        DB::table('cms.products_toxicological_class_links')->updateOrInsert(
            [
                'product_id' => $product->id,
                'toxicological_class_id' => $toxicologicalClass->id,
            ],
        );
    }

    private function environmentalClass(Product $product, $item)
    {
        $environmentalClassName = self::normalize($item[12]);
        $environmentalClass = EnvironmentalClass::whereRaw("lower(name) = ?", strtolower($environmentalClassName))->firstOrNew();
        $environmentalClass->name = $environmentalClassName;
        $environmentalClass->save();

        DB::table('cms.products_environmental_class_links')->updateOrInsert(
            [
                'product_id' => $product->id,
                'environmental_class_id' => $environmentalClass->id,
            ],
        );
    }

    private function brands(Product $product, $item)
    {
        foreach (self::toArray($item[1]) as $value) {
            if ($value) {
                $brand = ProductBrand::whereRaw("lower(name) = ?", strtolower($value))->firstOrNew();
                $brand->name = $value;
                $brand->save();

                DB::table('cms.product_brands_product_links')->updateOrInsert(
                    [
                        'product_id' => $product->id,
                        'product_brand_id' => $brand->id,
                    ],
                );
            }
        }
    }

    private function activeIngredients(Product $product, $item)
    {
        foreach (self::toArray($item[3], ["+"]) as $row) {
            $row = explode("(", $row);

            $activeIngredientName = self::normalize(str_replace(")", "", $row[0]));
            $chemicalGroupName = self::normalize(str_replace(")", "", $row[1]));
            $concentration = self::normalize(str_replace(")", "", $row[2]));
            if ($activeIngredientName && $chemicalGroupName && $concentration) {
                $chemicalGroup = ChemicalGroup::whereRaw("lower(name) = ?", strtolower($chemicalGroupName))->firstOrNew();
                $chemicalGroup->name = Str::title($chemicalGroupName);
                $chemicalGroup->save();

                $activeIngredient = ActiveIngredient::whereRaw("lower(name) = ?", strtolower($activeIngredientName))->firstOrNew();
                $activeIngredient->name = Str::title($activeIngredientName);
                $activeIngredient->save();

                DB::table('cms.active_ingredients_chemical_group_links')->updateOrInsert(
                    [
                        'active_ingredient_id' => $activeIngredient->id,
                        'chemical_group_id' => $chemicalGroup->id,
                    ],
                );

                $productActiveIngredient = ProductActiveIngredient::whereRaw("lower(concentration) = ?", strtolower($concentration))->firstOrNew();
                $productActiveIngredient->concentration = $concentration;
                $productActiveIngredient->save();

                DB::table('cms.product_active_ingredients_active_ingredient_links')->updateOrInsert(
                    [
                        'product_active_ingredient_id' => $productActiveIngredient->id,
                        'active_ingredient_id' => $activeIngredient->id,
                    ],
                );

                DB::table('cms.product_active_ingredients_product_links')->updateOrInsert(
                    [
                        'product_active_ingredient_id' => $productActiveIngredient->id,
                        'product_id' => $product->id,
                    ],
                );
            }
        }
    }

    private function classes(Product $product, $item)
    {
        foreach (self::toArray($item[5]) as $value) {
            if ($value) {
                $agroClass = AgroClass::whereRaw("lower(name) = ?", strtolower($value))->firstOrNew();
                $agroClass->name = $value;
                $agroClass->save();

                $record = DB::table('cms.product_classes_class_links', "pccl")
                    ->join('cms.product_classes_product_links as pcpl', "pcpl.product_class_id", "=", "pccl.product_class_id")
                    ->where('pccl.class_id', $agroClass->id)
                    ->where('pcpl.product_id', $product->id)
                    ->first();

                if (!$record) {
                    $productClass = new ProductClass();
                    $productClass->save();

                    DB::table('cms.product_classes_class_links')
                        ->insert([
                            'product_class_id' => $productClass->id,
                            'class_id' => $agroClass->id,
                        ]);

                    DB::table('cms.product_classes_product_links')
                        ->insert([
                            'product_class_id' => $productClass->id,
                            'product_id' => $product->id,
                        ]);
                }
            }
        }
    }

    private function actionMode(Product $product, $item)
    {
        foreach (self::toArray($item[6]) as $value) {
            if ($value) {
                $actionMode = ActionMode::whereRaw("lower(description) = ?", strtolower($value))->firstOrNew();
                $actionMode->description =  Str::title($value);
                $actionMode->save();

                $record = DB::table('cms.product_action_modes_action_mode_links', "pamaml")
                    ->join('cms.product_action_modes_product_links as pampl', "pampl.product_action_mode_id", "=", "pamaml.product_action_mode_id")
                    ->where('pamaml.action_mode_id', $actionMode->id)
                    ->where('pampl.product_id', $product->id)
                    ->first();

                if (!$record) {
                    $productActionMode = new ProductActionMode();
                    $productActionMode->save();

                    DB::table('cms.product_action_modes_action_mode_links')
                        ->insert([
                            'product_action_mode_id' => $productActionMode->id,
                            'action_mode_id' => $actionMode->id,
                        ]);

                    DB::table('cms.product_action_modes_product_links')
                        ->insert([
                            'product_action_mode_id' => $productActionMode->id,
                            'product_id' => $product->id,
                        ]);
                }
            }
        }
    }

    private function cultures(Product $product, $item)
    {
        // TODO: todas as culturas
        foreach (self::toArray($item[7]) as $value) {
            if ($value) {
                $culture = Culture::whereRaw("lower(name) = ?", strtolower($value))->firstOrNew();
                $culture->name = $value;
                $culture->save();

                $record = DB::table('cms.product_cultures_culture_links', "pccl")
                    ->join('cms.product_cultures_product_links as pcpl', "pcpl.product_culture_id", "=", "pccl.product_culture_id")
                    ->where('pccl.culture_id', $culture->id)
                    ->where('pcpl.product_id', $product->id)
                    ->first();

                if (!$record) {
                    $productCulture = new ProductCulture();
                    $productCulture->save();

                    DB::table('cms.product_cultures_culture_links')
                        ->insert([
                            'product_culture_id' => $productCulture->id,
                            'culture_id' => $culture->id,
                        ]);

                    DB::table('cms.product_cultures_product_links')
                        ->insert([
                            'product_culture_id' => $productCulture->id,
                            'product_id' => $product->id,
                        ]);
                }
            }
        }
    }

    private function pragues(Product $product, $item)
    {
        $pragueName = self::normalize($item[8]);
        if ($pragueName) {
            $prague = Prague::whereRaw("lower(scientific_name) = ?", strtolower($pragueName))->firstOrNew();
            $prague->scientific_name = $pragueName;
            $prague->save();

            $record = DB::table('cms.product_pragues_prague_links', "pppl")
                ->join('cms.product_pragues_product_links as pppl2', "pppl2.product_prague_id", "=", "pppl.product_prague_id")
                ->where('pppl.prague_id', $prague->id)
                ->where('pppl2.product_id', $product->id)
                ->first();

            if (!$record) {
                $productPrague = new ProductPrague();
                $productPrague->save();

                DB::table('cms.product_pragues_prague_links')
                    ->insert([
                        'product_prague_id' => $productPrague->id,
                        'prague_id' => $prague->id,
                    ]);

                DB::table('cms.product_pragues_product_links')
                    ->insert([
                        'product_prague_id' => $productPrague->id,
                        'product_id' => $product->id,
                    ]);
            }

            foreach (self::toArray($item[9]) as $value) {
                if ($value) {
                    $commomName = PragueCommonName::whereRaw("lower(name) = ?", strtolower($value))->firstOrNew();
                    $commomName->name = Str::title($value);
                    $commomName->save();

                    DB::table('cms.prague_common_names_prague_links')->updateOrInsert(
                        [
                            'prague_common_name_id' => $commomName->id,
                            'prague_id' => $prague->id,
                        ],
                    );
                }
            }
        }
    }

    private function companies(Product $product, $item)
    {
        foreach (self::toArray($item[10], ["+"]) as $value) {
            $value = str_replace("<", "+", $value);
            $value = str_replace(">", "+", $value);
            $value = explode("+", $value);

            if (isset($value[1]) && isset($value[2])) {
                $countryName = trim($value[1]);
                $companyName = trim(str_replace("(", "", $value[0]));
                $typeName = trim(str_replace(")", "", $value[2]));

                if ($countryName && $companyName && $typeName) {
                    $country = Country::whereRaw("lower(name) = ?", strtolower($countryName))->firstOrNew();
                    $country->name = $countryName;
                    $country->save();

                    $company = Company::whereRaw("lower(name) = ?", strtolower($companyName))->firstOrNew();
                    $company->name = $companyName;
                    $company->save();

                    DB::table('cms.companies_country_links')->updateOrInsert(
                        [
                            'company_id' => $company->id,
                            'country_id' => $country->id,
                        ],
                    );

                    $companyType = CompanyType::whereRaw("lower(name) = ?", $typeName)->firstOrNew();
                    $companyType->name = $typeName;
                    $companyType->save();

                    $record = DB::table('cms.product_companies_company_links', "pccl")
                        ->join('cms.product_companies_company_type_links as pcctl', "pcctl.product_company_id", "=", "pccl.product_company_id")
                        ->join('cms.product_companies_product_links as pcpl', "pcpl.product_company_id", "=", "pccl.product_company_id")
                        ->where('pccl.company_id', $company->id)
                        ->where('pcctl.company_type_id', $companyType->id)
                        ->where('pcpl.product_id', $product->id)
                        ->first();

                    if (!$record) {
                        $productCompany = new ProductCompany();
                        $productCompany->save();

                        DB::table('cms.product_companies_company_links')
                            ->insert([
                                'product_company_id' => $productCompany->id,
                                'company_id' => $company->id,
                            ]);

                        DB::table('cms.product_companies_company_type_links')
                            ->insert([
                                'product_company_id' => $productCompany->id,
                                'company_type_id' => $companyType->id,
                            ]);

                        DB::table('cms.product_companies_product_links')
                            ->insert([
                                'product_company_id' => $productCompany->id,
                                'product_id' => $product->id,
                            ]);
                    }
                }
            }
        }
    }

    private function clear()
    {
        DB::table('cms.product_companies_product_links')->delete();
        DB::table('cms.product_companies_company_type_links')->delete();
        DB::table('cms.product_companies_company_links')->delete();
        DB::table('cms.companies_country_links')->delete();
        DB::table('cms.prague_common_names_prague_links')->delete();
        DB::table('cms.product_pragues_product_links')->delete();
        DB::table('cms.product_pragues_prague_links')->delete();
        DB::table('cms.product_cultures_product_links')->delete();
        DB::table('cms.product_cultures_culture_links')->delete();
        DB::table('cms.product_action_modes_product_links')->delete();
        DB::table('cms.product_action_modes_action_mode_links')->delete();
        DB::table('cms.product_classes_product_links')->delete();
        DB::table('cms.product_classes_class_links')->delete();
        DB::table('cms.product_active_ingredients_product_links')->delete();
        DB::table('cms.product_active_ingredients_active_ingredient_links')->delete();
        DB::table('cms.active_ingredients_chemical_group_links')->delete();
        DB::table('cms.product_brands_product_links')->delete();
        DB::table('cms.products_environmental_class_links')->delete();
        DB::table('cms.products_toxicological_class_links')->delete();
        DB::table('cms.products_registration_holder_links')->delete();
        DB::table('cms.products_formulation_links')->delete();

        ProductCompany::query()->forceDelete();
        CompanyType::query()->forceDelete();
        Country::query()->forceDelete();
        PragueCommonName::query()->forceDelete();
        ProductPrague::query()->forceDelete();
        Prague::query()->forceDelete();
        ProductCulture::query()->forceDelete();
        Culture::query()->forceDelete();
        ActionMode::query()->forceDelete();
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
    }
}
