<?php

namespace App\Console\Commands;

use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class Import extends Command
{
    protected $signature = 'import:agrofit';
    protected $description = 'Copia os dados do agrofit';

    public function handle()
    {
        // $this->unzip();

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

                    $item = array_map(fn($v) => $v === null ? null : mb_convert_encoding($v, 'UTF-8', 'UTF-8, ISO-8859-1, Windows-1252'), $item);


                    $this->info(implode(";", $item));
                    die();

                    // TODO: processe a linha ($item é associativo)
                    // ex.: Product::create($item);

                } catch (\Throwable $e) {
                    // Conteúdo bruto da linha, útil para depurar
                    $raw = implode($delimiter, array_map(
                        fn($v) => is_null($v) ? '' : (string) $v,
                        $row
                    ));

                    $msg = "Erro na linha {$line}: {$e->getMessage()} | Conteúdo: {$raw}";
                    // $this->error($msg);

                    // // Log estruturado
                    // Log::error('Import CSV error', [
                    //     'file'      => $path,
                    //     'line'      => $line,
                    //     'error'     => $e->getMessage(),
                    //     'row_array' => $row,
                    //     'raw'       => $raw,
                    // ]);

                    // Continue importando as próximas linhas
                    // continue;
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
}
