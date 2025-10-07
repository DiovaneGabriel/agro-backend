<?php

namespace App\Console\Commands;

use App\Models\ActionMechanism;
use App\Models\ActionMode;
use App\Models\ActiveIngredient;
use App\Models\ActiveIngredientActionMechanisms;
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
use Illuminate\Support\Str;

class ImportActionMechanism extends Command
{
    protected $signature = 'import:action-mechanism';
    protected $description = 'Copia os dados do agrofit';

    public function handle()
    {

        $path = storage_path('app/private/action_mechanism_herbicide.csv'); // caminho físico
        $delimiter = ',';

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
                    $activeIngredientName = normalize($item[0]);
                    $className = normalize($item[2]);
                    $actionMechanismName = normalize($item[3]);
                    $hrac = normalize($item[4]);
                    $wssa = normalize($item[5]);

                    $activeIngredient = ActiveIngredient::query()
                        ->where("name", $activeIngredientName)
                        ->first();

                    $class = AgroClass::query()
                        ->where("name", $className)
                        ->first();

                    $actionMechanism = ActionMechanism::firstOrNew(["name" => $actionMechanismName]);
                    $actionMechanism->hrac = $hrac;
                    $actionMechanism->wssa = $wssa;
                    $actionMechanism->save();

                    if ($activeIngredient && $class) {

                        $activeIngredientActionMechanisms = ActiveIngredientActionMechanisms::firstOrNew(
                            [
                                "active_ingredient_id" => $activeIngredient->id,
                                "class_id" => $class->id,
                                "action_mechanism_id" => $actionMechanism->id
                            ]
                        );
                        $activeIngredientActionMechanisms->save();
                    }

                    // $this->info(var_dump($item));
                    // die();
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
}
