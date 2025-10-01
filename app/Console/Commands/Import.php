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


                    // # active ingredients
                    // foreach (self::toArray($item[3], ["+"]) as $row) {
                    //     $row = explode("(", $row);

                    //     $chemicalGroupName = self::normalize(str_replace(")", "", $row[1]));
                    //     if ($chemicalGroupName) {
                    //         $chemicalGroup = ChemicalGroup::whereRaw("lower(name) = ?", strtolower($chemicalGroupName))->first();
                    //         if (!$chemicalGroup) {
                    //             $chemicalGroup = new ChemicalGroup();
                    //             $chemicalGroup->name = Str::title($chemicalGroupName);
                    //             $chemicalGroup->save();
                    //         }
                    //     }

                    //     $activeIngredientName = self::normalize(str_replace(")", "", $row[0]));
                    //     if ($activeIngredientName) {
                    //         $activeIngredient = ActiveIngredient::whereRaw("lower(name) = ?", strtolower($activeIngredientName))->first();
                    //         if (!$activeIngredient) {
                    //             $activeIngredient = new ActiveIngredient();
                    //             $activeIngredient->name = Str::title($activeIngredientName);
                    //             $activeIngredient->chemical_group_id = $chemicalGroup->id;
                    //             $activeIngredient->save();
                    //         }
                    //     }

                    //     $productActiveIngredient = ProductActiveIngredient::where("product_id", $product->id)
                    //         ->where("active_ingredient_id", $activeIngredient->id)
                    //         ->first();

                    //     if (!$productActiveIngredient) {
                    //         $productActiveIngredient = new ProductActiveIngredient();
                    //         $productActiveIngredient->product_id = $product->id;
                    //         $productActiveIngredient->active_ingredient_id = $activeIngredient->id;
                    //         $productActiveIngredient->concentration = self::normalize(str_replace(")", "", $row[2]));
                    //         $productActiveIngredient->save();
                    //     }
                    // }

                    // # classes
                    // foreach (self::toArray($item[5]) as $value) {
                    //     if ($value) {
                    //         $agroClass = AgroClass::whereRaw("lower(name) = ?", strtolower($value))->firstOrNew();
                    //         $agroClass->name = $value;
                    //         $agroClass->save();

                    //         $productClass = ProductClass::where("product_id", $product->id)
                    //             ->where("class_id", $agroClass->id)
                    //             ->first();
                    //         if (!$productClass) {
                    //             $productClass = new ProductClass();
                    //             $productClass->product_id = $product->id;
                    //             $productClass->class_id = $agroClass->id;
                    //             $productClass->save();
                    //         }
                    //     }
                    // }

                    // # action modes
                    // foreach (self::toArray($item[6]) as $value) {
                    //     if ($value) {
                    //         $actionMode = ActionMode::whereRaw("lower(description) = ?", strtolower($value))->firstOrNew();
                    //         $actionMode->description =  Str::title($value);
                    //         $actionMode->save();

                    //         $productActionMode = ProductActionMode::where("product_id", $product->id)
                    //             ->where("action_mode_id", $actionMode->id)
                    //             ->first();
                    //         if (!$productActionMode) {
                    //             $productActionMode = new ProductActionMode();
                    //             $productActionMode->product_id = $product->id;
                    //             $productActionMode->action_mode_id = $actionMode->id;
                    //             $productActionMode->save();
                    //         }
                    //     }
                    // }

                    // # cultures
                    // // TODO: todas as culturas
                    // foreach (self::toArray($item[7]) as $value) {
                    //     if ($value) {
                    //         $culture = Culture::whereRaw("lower(name) = ?", strtolower($value))->firstOrNew();
                    //         $culture->name = $value;
                    //         $culture->save();

                    //         $productCulture = ProductCulture::where("product_id", $product->id)
                    //             ->where("culture_id", $culture->id)
                    //             ->first();
                    //         if (!$productCulture) {
                    //             $productCulture = new ProductCulture();
                    //             $productCulture->product_id = $product->id;
                    //             $productCulture->culture_id = $culture->id;
                    //             $productCulture->save();
                    //         }
                    //     }
                    // }

                    // # pragues
                    // $pragueName = self::normalize($item[8]);
                    // if ($pragueName) {
                    //     $prague = Prague::whereRaw("lower(scientific_name) = ?", strtolower($pragueName))->firstOrNew();
                    //     $prague->scientific_name = $pragueName;
                    //     $prague->save();

                    //     $productPrague = ProductPrague::where("product_id", $product->id)
                    //         ->where("prague_id", $prague->id)
                    //         ->first();

                    //     if (!$productPrague) {
                    //         $productPrague = new ProductPrague();
                    //         $productPrague->product_id = $product->id;
                    //         $productPrague->prague_id = $prague->id;
                    //         $productPrague->save();
                    //     }

                    //     foreach (self::toArray($item[9]) as $value) {
                    //         if ($value) {
                    //             $commomName = PragueCommonName::where("prague_id", $prague->id)
                    //                 ->whereRaw("lower(name) = ?", strtolower($value))
                    //                 ->first();

                    //             if (!$commomName) {
                    //                 $commomName = new PragueCommonName();
                    //                 $commomName->prague_id = $prague->id;
                    //                 $commomName->name = $value;
                    //                 $commomName->save();
                    //             }
                    //         }
                    //     }
                    // }

                    // # companies
                    // foreach (self::toArray($item[10], ["+"]) as $value) {
                    //     $value = str_replace("<", "+", $value);
                    //     $value = str_replace(">", "+", $value);
                    //     $value = explode("+", $value);

                    //     if (isset($value[1]) && isset($value[2])) {
                    //         $countryName = trim($value[1]);
                    //         if ($countryName) {
                    //             $country = Country::whereRaw("lower(name) = ?", strtolower($countryName))->firstOrNew();
                    //             $country->name = $countryName;
                    //             $country->save();

                    //             $companyName = trim(str_replace("(", "", $value[0]));
                    //             if ($companyName) {
                    //                 $company = Company::whereRaw("lower(name) = ?", strtolower($companyName))->firstOrNew();
                    //                 $company->name = $companyName;
                    //                 $company->country_id = $country->id;
                    //                 $company->save();

                    //                 $typeName = trim(str_replace(")", "", $value[2]));
                    //                 $companyType = CompanyType::whereRaw("lower(name) = ?", $typeName)->firstOrNew();
                    //                 $companyType->name = $typeName;
                    //                 $companyType->save();

                    //                 $productCompany = ProductCompany::where("product_id", $product->id)
                    //                     ->where("company_id", $company->id)
                    //                     ->firstOrNew();
                    //                 $productCompany->product_id = $product->id;
                    //                 $productCompany->company_id = $company->id;
                    //                 $productCompany->company_type_id = $companyType->id;
                    //                 $productCompany->save();
                    //             }
                    //         }
                    //     }
                    // }

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

    private function clear()
    {
        DB::table('cms.product_brands_product_links')->delete();
        DB::table('cms.products_environmental_class_links')->delete();
        DB::table('cms.products_toxicological_class_links')->delete();
        DB::table('cms.products_registration_holder_links')->delete();
        DB::table('cms.products_formulation_links')->delete();

        ProductBrand::query()->forceDelete();
        Product::query()->forceDelete();
        EnvironmentalClass::query()->forceDelete();
        ToxicologicalClass::query()->forceDelete();
        RegistrationHolder::query()->forceDelete();
        Formulation::query()->forceDelete();
    }
}
