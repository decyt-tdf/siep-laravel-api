<?php

namespace App\Jobs;

use App\Http\Controllers\Api\Saneo\v1\SaneoInscripciones;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\Log;

class JobSaneoInscripciones implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $ciclo;
    protected $por_pagina;
    protected $page;
    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($ciclo=2019,$page=1,$por_pagina=10)
    {
        $this->ciclo= $ciclo;
        $this->por_pagina= $por_pagina;
        $this->page = $page;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        Log::info("HANDLE JobSaneoInscripciones($this->ciclo,$this->page,$this->por_pagina) -- START");
        $domagic = new SaneoInscripciones();
        $domagic->start($this->ciclo,$this->page,$this->por_pagina);
        Log::info("HANDLE JobSaneoInscripciones($this->ciclo,$this->page,$this->por_pagina) -- COMPLETE");
    }
}
