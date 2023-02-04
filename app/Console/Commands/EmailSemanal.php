<?php

namespace App\Console\Commands;

use App\Mail\NotificacionSemanal;
use App\Models\Alerta;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

class EmailSemanal extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'notificacion:emailsemanal';
   
    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Notificacion de Eventos de la proxima semana';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        /* $texto= "[".date("Y-m-d H:i:s")."]: Prueba de Eventos de la proxima semana";
        Storage::append("archivo.txt", $texto); */
      

       

        $NotificacionSemanal = Alerta::where('vencimiento', date("Y-m-d", strtotime("+1 week")))->get();
        $NotificacionDiaria = Alerta::where('vencimiento', date("Y-m-d", strtotime("+1 day")))->get();

        if ($NotificacionSemanal->count() > 0) {
        foreach ($NotificacionSemanal as $alerta) {
            Mail::to("lgoviedo17@hotmail.com")->send(new NotificacionSemanal($alerta)); 
            Mail::to("asarmiento9@hotmail.com")->send(new NotificacionSemanal($alerta));            
        }
        }
        if ($NotificacionDiaria->count() > 0) {
            foreach ($NotificacionDiaria as $alerta) {
                Mail::to("lgoviedo17@hotmail.com")->send(new NotificacionSemanal($alerta));   
                Mail::to("asarmiento9@hotmail.com")->send(new NotificacionSemanal($alerta));         
            }
            }

        return 0;

        
    
       // return Command::SUCCESS;
    }
}
