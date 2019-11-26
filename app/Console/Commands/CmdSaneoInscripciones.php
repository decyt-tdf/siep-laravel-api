<?php

namespace App\Console\Commands;

use App\Http\Controllers\Api\Saneo\v1\SaneoInscripciones;
use App\Jobs\WhileJobSaneoInscripciones;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class CmdSaneoInscripciones extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'siep:saneo_inscripciones {ciclo} {page} {por_pagina?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Realiza un saneo recorriendo todas las inscripciones';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $ciclo = $this->argument('ciclo');
        $por_pagina = $this->argument('por_pagina');
        $page = $this->argument('page');
/*
        TEST REQUEST TIMEOUT
        Log::info("ARTISAN TestFpmJob: dispatch");
        TestFpmJob::dispatch();
        Log::info("ARTISAN TestFpmJob: dispatch done");
*/
        //$this->info("JobSaneoRepitenciaAndPromocion: $ciclo, $page, $por_pagina");
        Log::info("ARTISAN SaneoInscripciones HANDLE ciclo: $ciclo / page:$page / por_pagina: $por_pagina");

        //- Procesamos el saneo de la primer pagina -
        $saneo = new SaneoInscripciones();
        $saneo = $saneo->start($ciclo,$page,$por_pagina);

        // Obtenemos ultima pagina
        $ultimaPagina = $saneo['last_page'];
        //-------------------------------------------

        // Paginar Jobs debido al problema que surge con el max_execution_time entre nginx php-fpm
        Log::info("ARTISAN SaneoInscripciones ciclo: $ciclo / page:$page / por_pagina: $por_pagina / ultimaPagina:$ultimaPagina ");

        $delay = 1;
        $nextPage = $page;
        for($i=1;$i<=$ultimaPagina;$i++) {
            if( ($i%300) == 0)
            {
                Log::info("WhileJobSaneoInscripciones::dispatch($ciclo,$nextPage,$por_pagina,$i);");
                WhileJobSaneoInscripciones::dispatch($ciclo,$nextPage,$por_pagina,$i)->delay(now()->addMinutes($delay));
                $nextPage = $i;
            }
        }

        if($nextPage<$ultimaPagina)
        {
            Log::info("WhileJobSaneoInscripciones::dispatch($ciclo,$nextPage,$por_pagina,$ultimaPagina);");
            WhileJobSaneoInscripciones::dispatch($ciclo,$nextPage,$por_pagina,$ultimaPagina)->delay(now()->addMinutes($delay));
        }

        Log::info("ARTISAN SaneoInscripciones DISPATCHED");
    }
}
