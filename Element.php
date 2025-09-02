<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Element extends Model
{
    use HasFactory;

    //VIB - 27nov2024 - Connexió base de dades SIGI_JMS
    protected $connection = 'sqlsrv';

    protected  $table = 'dbo.cathard';

    //VIB-10gen2025-Eloquent espera una columna autoincremental anomenada id com a clau primària
    //hem de definir manualment la clau primària de la taula
    protected $primaryKey = 'hard_cod';
    //hard_cod no és un camp autoincremental. Si no ho especifiquem, el camp hard_cod apareixerà a zero.
    public $incrementing = false;
    //no utilitzar les columnes update_at i created_at
    public $timestamps = false;

    protected $fillable = [
        'hard_cod',
    ];
}
