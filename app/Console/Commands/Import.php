<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class Import extends Command
{
    protected $signature = 'import:agrofit';

    protected $description = 'Copia os dados do agrofit';

    public function handle()
    {

        $response = Http::timeout(3600)
            ->withHeaders([
                'Content-Type' => 'application/x-www-form-urlencoded',
                // 'Cookie' => 'Tomate=1234; Abacaxi=...',
            ])->asForm()->post('https://agrofit.agricultura.gov.br/agrofit_cons/!agrofit.emp_csv', [
                'p_tipo_produto' => 'FORM',
                // 'p_id_tecnica_aplicacao' => '1',
            ]);


        $body = $response->body();

        Log::info($body);


        $this->info('✅ Migração dos dados finalizada.');
        return 0;
    }
}
