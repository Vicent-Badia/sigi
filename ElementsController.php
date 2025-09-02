<?php

namespace App\Http\Controllers\Explotacio;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Session;

use App\Models\Element;
use App\Models\ElementFormulari;
use App\Models\Centre;
use App\Models\Planta;
use App\Models\Despatx;
use App\Models\Departament;
use App\Models\Proveidor;
use App\Http\Middleware\PerfilOperador;
use App\Models\Elemtipo;
use App\Models\Configuracio;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

//VIB-13jun2024-Per a poder utilitzar la funció middleware, fem que la classe herede de \Illuminate\Routing\Controller ( no de Controller )
class ElementsController extends \Illuminate\Routing\Controller
{
  
      //VIB - 2set2022 - Sols tenen accés usuaris amb permís d'operari o administradors
      public function __construct() {
        $this->middleware(PerfilOperador::class);
      }

      public function home()
      {
        $dataReg = new ElementFormulari();
        $centres = Centre::select('cod_centro','nombre_centro')->get();
        $plantes = Planta::select('planta', 'descripcion', 'centro')->get();
        $despatxos = Despatx::select('centro','despacho','planta','descripcion')->get();
        $tipus = Elemtipo::select('tipo_cod','tipo_desc','es_puesto')->get(); 
        //sols departaments actius
        $departaments = Departament::select('dpto_cod', 'dpto_desc', 'dpto_empr')->where('dpto_activo', '=', 'S')->get();
        return view('explotacio.elements', ['dataReg'=>$dataReg, 'centres'=>$centres, 
               'plantes'=>$plantes, 'despatxos'=>$despatxos, 'tipus'=>$tipus, 'departaments'=>$departaments]);
      }

      public function autocompleteElements(Request $request)

      {           // $request->get('query') 
                  $campo= $request->get('campo');
                  if (strlen($request->get('query')) >1 )
                  {
                      //VIB-18des2024-Fem left join amb la taula dbo.centros per a poder buscar pel nom del centre de treball
                      $data = Element::select($campo)
                      ->join('dbo.centros', 'dbo.cathard.hard_ctra', '=', 'dbo.centros.cod_centro')
                      //VIB-8gen2025-Join amb la taula dbo.catdpto per a treure el nom dels departaments. Camp "assignat a".
                      ->join('dbo.catdpto', 'dbo.cathard.hard_dpto', '=', 'dbo.catdpto.dpto_cod')
                      ->join('dbo.cattipo', 'dbo.cathard.hard_tipo', '=', 'dbo.cattipo.tipo_cod')
                      ->join('dbo.catprov', 'dbo.cathard.hard_prov', '=', 'dbo.catprov.prov_cod')->distinct()
                      ->where($campo, 'LIKE', '%' . $request->get('query'). '%')
                      ->get()->pluck($campo); 

                  return response()->json($data); 
                  }   
                     
                  else {
                      $data="";
                      response()->json($data);}
      }

      public function elementsSearch(Request $request) {
        $dataReg = new ElementFormulari;
        $dataReg->codi = strtoupper($request->codi);
        $dataReg->no_localitzable = $request->localitzable;
        $dataReg->actiu = $request->actiu;
        $dataReg->desafectat = $request->desafectat;
        $dataReg->usuari = strtoupper($request->usuari);
        $dataReg->centre = $request->centre;
        $dataReg->planta = $request->planta;
        $dataReg->despatx = $request->despatx;
        $dataReg->assignat = $request->assignat;
        $dataReg->connexio = strtoupper($request->connexio);
        $dataReg->garantia = $request->garantia;
        $dataReg->fcompra1 = $request->fcompra1;
        $dataReg->fcompra2 = $request->fcompra2;
        $dataReg->proveidor = strtoupper($request->proveidor);
        $dataReg->numSerie = strtoupper($request->numSerie);
        $dataReg->model = strtoupper($request->model);
        $dataReg->marca = strtoupper($request->marca);
        $dataReg->tipus = $request->tipus;
        $dataReg->prestat = $request->prestat;
        $dataReg->plec = strtoupper($request->plec);
        $dataReg->observ = $request->observ;

        session(['status' => '']);

        $query = Element::select('dbo.cathard.*','dbo.centros.nombre_centro', 'dbo.catdpto.dpto_desc', 'dbo.catprov.prov_desc',
          'dbo.cattipo.tipo_desc', 'dbo.cattipo.es_puesto')
          ->leftjoin('dbo.centros', 'dbo.cathard.hard_ctra', '=', 'dbo.centros.cod_centro')
          ->leftjoin('dbo.catdpto', 'dbo.cathard.hard_dpto', '=', 'dbo.catdpto.dpto_cod')
          ->leftjoin('dbo.catprov', 'dbo.cathard.hard_prov', '=', 'dbo.catprov.prov_cod')
          ->leftjoin('dbo.cattipo', 'dbo.cathard.hard_tipo', '=', 'dbo.cattipo.tipo_cod');

        // Codi element - Afegir filtre per al camp hard_cod si s'ha especificat
        if (!empty($dataReg->codi)) {
          $query->where('dbo.cathard.hard_cod', 'LIKE', '%' . $dataReg->codi . '%');
        }

        // Añadir filtro para `hard_usua` si está presente
        if (!empty($dataReg->usuari)) {
          $query->where('dbo.cathard.hard_usua', 'LIKE', '%' . $dataReg->usuari . '%');
        }

        $plantesCentre = [];
        if ($dataReg->centre != '') {
          $query->where('dbo.centros.cod_centro', '=' ,$dataReg->centre);
          $plantesCentre = Planta::select('planta','descripcion','centro')->where('centro', '=', $dataReg->centre)->get();
        }

        $despatxosPlanta = [];
        if (($dataReg->planta != -1) and ($dataReg->centre != '')) {
            $query->where('dbo.cathard.hard_piso', '=', $dataReg->planta);
            $despatxosPlanta = Despatx::select('centro','despacho','planta','descripcion')->where('centro', '=', $dataReg->centre)
                ->where('planta', '=', $dataReg->planta)->get();
        }

        if (($dataReg->despatx != -1) and ($dataReg->planta != -1) and ($dataReg->centre != '')) {
            $query->where('dbo.cathard.hard_desp', '=', $dataReg->despatx);
        }

        //VIB-3gen2025-Checkboxes
        if ($dataReg->actiu == 'true') {
          $query->where('dbo.cathard.hard_acti', '=', 'S');
        } else {
          $query->where('dbo.cathard.hard_acti', '!=', 'S');  
        }

        if ($dataReg->no_localitzable == 'true') {
          $query->where('dbo.cathard.hard_iloc', '=', 'S');
        } 

        if ($dataReg->desafectat == 'true') {
          $query->where('dbo.cathard.hard_desa', '=', 'S');
        } 

        if ($dataReg->prestat == 'true') {
          $query->where('dbo.cathard.prestat', '=', 'S');
        } 

        if ($dataReg->assignat != '') {
          $query->where('dbo.cathard.hard_dpto', '=', $dataReg->assignat);
        }

        if ($dataReg->connexio != '') {
          $query->where('dbo.cathard.hard_conx', 'LIKE', '%' . $dataReg->connexio . '%');
        }

        if($dataReg->fcompra1 != '' && $dataReg->fcompra2 == '') {
          $query->where('dbo.cathard.hard_ffac', '!=', null)->where('dbo.cathard.hard_ffac', '>=', $dataReg->fcompra1);
        }

        if($dataReg->fcompra1 != '' && $dataReg->fcompra2 != '') {
          $query->where('dbo.cathard.hard_ffac', '!=', null)->where('dbo.cathard.hard_ffac', '>=', $dataReg->fcompra1)->where('dbo.cathard.hard_ffac', '<=', $dataReg->fcompra2);
        }

        if($dataReg->proveidor != '') {
          $query->where('dbo.catprov.prov_desc', 'LIKE', '%' . $dataReg->proveidor . '%');
        }

        if($dataReg->tipus != '') {
          $query->where('dbo.cathard.hard_tipo', '=', $dataReg->tipus);
        }

        if($dataReg->numSerie != '') {
          $query->where('dbo.cathard.hard_nser', 'LIKE', '%' . $dataReg->numSerie . '%');
        }
        if($dataReg->model != '') {
          $query->where('dbo.cathard.hard_mode', 'LIKE', '%' . $dataReg->model . '%');
        }
        if($dataReg->marca != '') {
          $query->where('dbo.cathard.hard_marc', 'LIKE', '%' . $dataReg->marca . '%');
        }
        if($dataReg->plec != '') {
          $query->where('dbo.cathard.hard_pliego', 'LIKE', '%' . $dataReg->plec . '%');
        }
        if($dataReg->observ != '') {
          $query->where('dbo.cathard.hard_obse', 'LIKE', '%' . $dataReg->observ . '%');
        }

        //VIB-12feb2025-Filtrar per garantia
        //El camp hard_gara de la taula cathard està a null per a tots els registres
        //Ha d'haver data de compra
        //Primer mirem si hi ha un número d'anys de garantia, si no, utlitzem 2 anys
        //Comprovem si la data de compra >= hui - anys de garantia
        if ($dataReg->garantia == 'true') {
          $twoYearsAGo = date_sub(date_create(date("Y-m-d")), date_interval_create_from_date_string("2 years"));
          //VIB-13feb2025-Agrupar diferents where baix un OR. Equival a (primera query) OR (segona query dins la funció)
          $query->whereNotNull('dbo.cathard.hard_ffac')->whereNull('dbo.cathard.hard_gara')->where('dbo.cathard.hard_ffac', '>=', $twoYearsAGo)
                ->orWhere(function ($query) {
                  $query->whereNotNull('dbo.cathard.hard_ffac')->whereNotNull('dbo.cathard.hard_gara')
                        ->whereRaw('hard_ffac >= dateadd(year, -hard_gara, getdate())');
                  });

        }

        // Ejecutar la consulta y obtener los resultados (o paginarlos si se necesita)
        $results = $query->orderBy('hard_cod')->paginate(20); // O usar `get()` si no deseas paginación
        // Total registres sense paginació
        $numRegTotal = $results->total();

        $centres = Centre::select('cod_centro','nombre_centro')->get();
        $plantes = Planta::select('planta', 'descripcion', 'centro')->get();
        $despatxos = Despatx::select('centro','despacho','planta','descripcion')->get();
        $tipus = Elemtipo::select('tipo_cod','tipo_desc','es_puesto')->get(); 
        //sols departaments actius
        $departaments = Departament::select('dpto_cod', 'dpto_desc', 'dpto_empr')->where('dpto_activo', '=', 'S')->get();

        return view('explotacio.elements',  ['elements'=>$results, 'totalReg'=>$numRegTotal, 'dataReg'=>$dataReg, 
            'centres'=>$centres, 'plantes'=>$plantes, 'plantesCentre'=>$plantesCentre, 'despatxos'=>$despatxos,
            'despatxosPlanta'=>$despatxosPlanta, 'tipus'=>$tipus, 'departaments'=>$departaments]);
      }

      public function veureElement($codi) {
        $element = Element::select('dbo.cathard.*','dbo.centros.nombre_centro', 'dbo.catdpto.dpto_desc', 'dbo.catprov.prov_desc',
                'dbo.cattipo.tipo_desc', 'dbo.despachos.descripcion')
          ->leftjoin('dbo.centros', 'dbo.cathard.hard_ctra', '=', 'dbo.centros.cod_centro')
          ->leftjoin('dbo.catdpto', 'dbo.cathard.hard_dpto', '=', 'dbo.catdpto.dpto_cod')
          ->leftjoin('dbo.catprov', 'dbo.cathard.hard_prov', '=', 'dbo.catprov.prov_cod')
          ->leftjoin('dbo.cattipo', 'dbo.cathard.hard_tipo', '=', 'dbo.cattipo.tipo_cod')
          ->leftjoin('dbo.despachos', 'dbo.cathard.hard_desp', '=', 'dbo.despachos.despacho')
          ->where('dbo.cathard.hard_cod', '=', $codi)->first();

        $garantia = false;
        $data_garantia = '';
        
        if ($element !== null) {
          if ($element->hard_ffac !== null) {
            //VIB-13feb2025-Elements sense un nombre d'anys de garantia definits
            //agafem data de compra i dos anys de garantia per defecte
            if ($element->hard_gara == null) {
              $data_compra = date_create($element->hard_ffac);
              $data_garantia = date_add($data_compra, date_interval_create_from_date_string("2 years"));
              $today = date_create(date("Y-m-d"));
              if ($data_garantia >= $today) {
                $garantia = true;
              } 
            } else {
              //elements amb uns anys de garantia definits
              $data_compra = date_create($element->hard_ffac);
              $data_garantia = date_add($data_compra, date_interval_create_from_date_string((string)$element->hard_gara . " years"));
              $today = date_create(date("Y-m-d"));
              if ($data_garantia >= $today) {
                $garantia = true;
              } 
            }
          } 
          return view('explotacio.element-view', ['element'=>$element, 'garantia'=>$garantia, 'data_garantia'=>$data_garantia]);

        } else {
          //VIB-10gen2025-Si $element no està definit és perquè s'ha cancel·lat des de la pàgina d'edició
          //tornem a la llista de resultats
          $dataReg = new ElementFormulari();
          return view('explotacio.elements', ['dataReg'=>$dataReg]);
        }
        
      }

      public function crearElement() {
        //VIB-Guardar la URL del llistat amb la cerca
        if (url()->previous() != url()->current()){
          session(['url_llistat' => url()->previous()]);
        }
        $centres = Centre::select('cod_centro','nombre_centro')->get();
        $plantes = Planta::select('planta', 'descripcion', 'centro')->get();
        $despatxos = Despatx::select('centro','despacho','planta','descripcion')->get();
        $request = new ElementFormulari;
        $plantesCentre = [];
        $despatxosPlanta = [];
        $tipus = Elemtipo::select('tipo_cod','tipo_desc','es_puesto')->get(); 
        $departaments = Departament::select('dpto_cod', 'dpto_desc', 'dpto_empr')->where('dpto_activo', '=', 'S')->get();

        return view('explotacio.element-create', ['urlLlistat'=>session('url_llistat'), 'centres'=>$centres,
              'plantes'=>$plantes, 'despatxos'=>$despatxos, 'request'=>$request, 'plantesCentre'=>$plantesCentre,
              'despatxosPlanta'=>$despatxosPlanta, 'tipus'=>$tipus, 'departaments'=>$departaments]);
      }

      public function crearElementGuardar(Request $request) {
        $request->session()->forget('status');

        $codi = $request->codi;

        $element = Element::select('dbo.cathard.*')
          ->where('dbo.cathard.hard_cod', '=', $codi)->first();

        $centres = Centre::select('cod_centro','nombre_centro')->get();
        $plantes = Planta::select('planta', 'descripcion', 'centro')->get();
        $despatxos = Despatx::select('centro','despacho','planta','descripcion')->get();
        $tipus = Elemtipo::select('tipo_cod','tipo_desc','es_puesto')->get(); 
        $departaments = Departament::select('dpto_cod', 'dpto_desc', 'dpto_empr')->where('dpto_activo', '=', 'S')->get();
        $plantesCentre = [];
        if ($request->centre !== "-1") {
          $plantesCentre = Planta::select('planta','descripcion','centro')->where('centro', '=', $request->centre)->get();
        }
        $despatxosPlanta = [];
        if (($request->planta != "-1") and ($request->centre != "-1")) {
            $despatxosPlanta = Despatx::select('centro','despacho','planta','descripcion')->where('centro', '=', $request->centre)
                ->where('planta', '=', $request->planta)->get();
        }

        if ($element !== null) {
        
          $request->session()->flash('status', 'El codi ja s\'ha utilitzat per a un altre component.');
          return view('explotacio.element-create', ['urlLlistat'=>session('url_llistat'), 'centres'=>$centres,
                  'plantes'=>$plantes, 'despatxos'=>$despatxos, 'request'=>$request, 'plantesCentre'=>$plantesCentre,
                  'despatxosPlanta'=>$despatxosPlanta, 'tipus'=>$tipus, 'departaments'=>$departaments]);

        } else {

          //VIB-10gen2025-Comprovar si s'ha escrit un centre i un departament existent
          $codCentre = $request->centro;
          $codPlanta = $request->planta;
          //Comprovar si s'ha escrit un proveïdor existent o està el camp en blanc
          $provOk = false;
          $codProv = '';
          if ($request->proveidor == '') {
            $provOk = true;
          } else {
            $codProv = Proveidor::select('prov_cod')->where('prov_desc', '=', $request->proveidor)->first();
            if ($codProv !== null) {
              $provOk = true;
            }
          }

          if ($codCentre !== "-1" && $codPlanta !== "-1" && $request->assignat !== null && $provOk && $request->tipus !== null) {

            //VIB-3jun2025-Si el tipus és un lloc, el codi sols té 6 caracters
            $regTipus = Elemtipo::where('tipo_cod', '=', $request->tipus)->first(); 
            if ($regTipus->es_puesto == 1) {
              if (strlen($request->codi) > 6) {
                $request->session()->flash('status', 'En el cas dels llocs (unitat central, portàtil, servidor...), el codi de l\'element té un màxim de 6 caracters.');
                return view('explotacio.element-create', ['urlLlistat'=>session('url_llistat'), 'centres'=>$centres,
                        'plantes'=>$plantes, 'despatxos'=>$despatxos, 'request'=>$request, 'plantesCentre'=>$plantesCentre,
                        'despatxosPlanta'=>$despatxosPlanta, 'tipus'=>$tipus, 'departaments'=>$departaments]);
              }
            }

            //guardar el registre
            $reg = Element::firstOrNew(array('hard_cod' => $request->codi));
            //actualitzar camps
            if($request->localitzable == "true") {
              $reg->hard_iloc = "S";
            } else {
              $reg->hard_iloc = "N";
            }
            if($request->actiu == "true") {
              $reg->hard_acti = "S";
            } else {
              $reg->hard_acti = "N";
            }
            if($request->desafectat == "true") {
              $reg->hard_desa = "S";
            } else {
              $reg->hard_desa = "N";
            }

            $reg->hard_ctra = $codCentre;
            $reg->hard_piso = $codPlanta;

            if($request->despatx !== "-1") {
              $reg->hard_desp = $request->despatx;
            } 
            
            $reg->hard_dpto = $request->assignat;
            $reg->hard_usua = $request->usuari;
            $reg->hard_conx = $request->connexio;
            $reg->hard_ffac = $request->fcompra;
            $reg->hard_gara = $request->anys_garantia;
            $reg->hard_fact = $request->data_act;
            if ($codProv !== '') {
              $reg->hard_prov = $codProv->prov_cod;
            } else {
              $reg->hard_prov = null;
            }

            $reg->hard_tipo = $request->tipus;

            $reg->hard_nser = $request->numSerie;
            $reg->hard_mode = $request->model;
            $reg->hard_marc = $request->marca;
            $reg->hard_fbaja = $request->hard_fbaja;
            $reg->hard_obse = $request->observ;
            $reg->hard_vers = 1;
            if($request->prestat == "true") {
              $reg->prestat = "S";
            } else {
              $reg->prestat = "N";
            }
            $reg->hard_pliego = $request->plec;

            //VIB-26feb2025-Si hi ha una connexió amb un lloc, actualitzem els camps
            //amb les dades del lloc
            $lloc = Element::where('hard_cod', '=', $request->connexio)->first();

            if ($lloc !== null) {
              $reg->hard_ctra = $lloc->hard_ctra;
              $reg->hard_piso = $lloc->hard_piso;
              $reg->hard_desp = $lloc->hard_desp;
              $reg->hard_dpto = $lloc->hard_dpto;
              $reg->hard_usua = $lloc->hard_usua;
              $reg->hard_fact = $lloc->hard_fact;
            }

            $reg->save();

            Session::flash('saving-msg', 'S\'ha creat el component '.$reg->hard_cod. '.');
            return Redirect::to(session('url_llistat'));

          } else {
            //el nom del centre o el departament no és correcte
            $request->session()->flash('status', 'Reviseu el nom del centre, la planta, el tipus de component, el departament assignat i el proveïdor. Si és necessari, podeu crear un nou centre des del menú Administració -> Centres.');
            return view('explotacio.element-create', ['urlLlistat'=>session('url_llistat'), 'centres'=>$centres,
                    'plantes'=>$plantes, 'despatxos'=>$despatxos, 'request'=>$request, 'plantesCentre'=>$plantesCentre,
                    'despatxosPlanta'=>$despatxosPlanta, 'tipus'=>$tipus, 'departaments'=>$departaments]);
          }

        }
      }

      public function editarElement($codi) {

        //VIB-Guardar la URL del llistat amb la cerca
        if (url()->previous() != url()->current()){
          session(['url_llistat' => url()->previous()]);
        }

        session(['status' => '']);

        $element = Element::select('dbo.cathard.*','dbo.centros.nombre_centro', 'dbo.catdpto.dpto_desc', 'dbo.catprov.prov_desc',
                    'dbo.cattipo.tipo_desc', 'dbo.cattipo.es_puesto')
          ->leftjoin('dbo.centros', 'dbo.cathard.hard_ctra', '=', 'dbo.centros.cod_centro')
          ->leftjoin('dbo.catdpto', 'dbo.cathard.hard_dpto', '=', 'dbo.catdpto.dpto_cod')
          ->leftjoin('dbo.catprov', 'dbo.cathard.hard_prov', '=', 'dbo.catprov.prov_cod')
          ->leftjoin('dbo.cattipo', 'dbo.cathard.hard_tipo', '=', 'dbo.cattipo.tipo_cod')
          ->where('dbo.cathard.hard_cod', '=', $codi)->first();

        $centres = Centre::select('cod_centro','nombre_centro')->get();
        $plantes = Planta::select('planta', 'descripcion', 'centro')->get();
        $despatxos = Despatx::select('centro','despacho','planta','descripcion')->get();
        $plantesCentre = Planta::select('planta','descripcion','centro')->where('centro', '=', $element->hard_ctra)->get();
        $despatxosPlanta = Despatx::select('centro','despacho','planta','descripcion')->where('centro', '=', $element->hard_ctra)
            ->where('planta', '=', $element->hard_piso)->get();
        $tipus = Elemtipo::select('tipo_cod','tipo_desc','es_puesto')->get(); 
        $departaments = Departament::select('dpto_cod', 'dpto_desc', 'dpto_empr')->where('dpto_activo', '=', 'S')->get();

        return view('explotacio.element-edit', ['dataReg'=>$element, 'urlLlistat'=>session('url_llistat'), 'centres'=>$centres, 
            'plantes'=>$plantes, 'despatxos'=>$despatxos, 'plantesCentre'=>$plantesCentre, 'despatxosPlanta'=>$despatxosPlanta,
            'tipus'=>$tipus, 'departaments'=>$departaments]);
      }

      public function editarElementSave(Request $request) {

        $request->session()->forget('status');

        $codi = $request->codi;

        $element = Element::select('dbo.cathard.*','dbo.centros.nombre_centro', 'dbo.catdpto.dpto_desc', 'dbo.catprov.prov_desc', 'dbo.cattipo.tipo_desc')
          ->leftjoin('dbo.centros', 'dbo.cathard.hard_ctra', '=', 'dbo.centros.cod_centro')
          ->leftjoin('dbo.catdpto', 'dbo.cathard.hard_dpto', '=', 'dbo.catdpto.dpto_cod')
          ->leftjoin('dbo.catprov', 'dbo.cathard.hard_prov', '=', 'dbo.catprov.prov_cod')
          ->leftjoin('dbo.cattipo', 'dbo.cathard.hard_tipo', '=', 'dbo.cattipo.tipo_cod')
          ->where('dbo.cathard.hard_cod', '=', $codi)->first();

        $centres = Centre::select('cod_centro','nombre_centro')->get();
        $plantes = Planta::select('planta', 'descripcion', 'centro')->get();
        $despatxos = Despatx::select('centro','despacho','planta','descripcion')->get();
        $plantesCentre = Planta::select('planta','descripcion','centro')->where('centro', '=', $element->hard_ctra)->get();
        $despatxosPlanta = Despatx::select('centro','despacho','planta','descripcion')->where('centro', '=', $element->hard_ctra)
            ->where('planta', '=', $element->hard_piso)->get();
        $tipus = Elemtipo::select('tipo_cod','tipo_desc','es_puesto')->get(); 
        $departaments = Departament::select('dpto_cod', 'dpto_desc', 'dpto_empr')->where('dpto_activo', '=', 'S')->get();

        if ($request->centre == '' || $request->planta == -1 || $request->assignat == null || $request->tipus == null) {
        
          $request->session()->flash('status', 'Heu d\'omplir els camps Centre, Planta, Tipus i Assignat a.');
          return view('explotacio.element-edit', ['dataReg'=>$element, 'urlLlistat'=>session('url_llistat'), 'centres'=>$centres, 
                'plantes'=>$plantes, 'despatxos'=>$despatxos, 'plantesCentre'=>$plantesCentre, 'despatxosPlanta'=>$despatxosPlanta,
                'tipus'=>$tipus, 'departaments'=>$departaments]);

        } else {

          //Comprovar si s'ha escrit un proveïdor existent o està el camp en blanc
          $provOk = false;
          $codProv = '';
          if ($request->proveidor == '') {
            $provOk = true;
          } else {
            $codProv = Proveidor::select('prov_cod')->where('prov_desc', '=', strtoupper($request->proveidor))->first();
            if ($codProv !== null) {
              $provOk = true;
            }
          }

          if ($provOk) {

            //guardar canvis en el registre
            //$reg = Element::where('hard_cod', $request->codi)->first();
            $reg = Element::firstOrNew(array('hard_cod' => $request->codi));
            //actualitzar camps
            if($request->localitzable == "true") {
              $reg->hard_iloc = "S";
            } else {
              $reg->hard_iloc = "N";
            }
            if($request->actiu == "true") {
              $reg->hard_acti = "S";
            } else {
              $reg->hard_acti = "N";
            }
            if($request->desafectat == "true") {
              $reg->hard_desa = "S";
            } else {
              $reg->hard_desa = "N";
            }

            $reg->hard_ctra = $request->centre;
            $reg->hard_piso = $request->planta;
            if ($reg->hard_desp !== "-1") {
              $reg->hard_desp = $request->despatx;
            } else {
              $reg->hard_desp = '';
            }
            
            $reg->hard_dpto = $request->assignat;
            $reg->hard_usua = strtoupper($request->usuari);
            $reg->hard_conx = $request->connexio;
            $reg->hard_fact = $request->data_act;

            //VIB-26feb2025-Si hi ha una connexió amb un lloc, actualitzem els camps
            //amb les dades del lloc
            $lloc = Element::where('hard_cod', '=', $request->connexio)->first();

            if ($lloc !== null) {
              $reg->hard_ctra = $lloc->hard_ctra;
              $reg->hard_piso = $lloc->hard_piso;
              $reg->hard_desp = $lloc->hard_desp;
              $reg->hard_dpto = $lloc->hard_dpto;
              $reg->hard_usua = $lloc->hard_usua;
              $reg->hard_fact = $lloc->hard_fact;
            }

            $reg->hard_ffac = $request->fcompra;
            $reg->hard_gara = $request->anys_garantia;
            if ($codProv !== '') {
              $reg->hard_prov = $codProv->prov_cod;
            } else {
              $reg->hard_prov = null;
            }
            $reg->hard_tipo = $request->tipus;

            $reg->hard_nser = strtoupper($request->numSerie);
            $reg->hard_mode = strtoupper($request->model);
            $reg->hard_marc = strtoupper($request->marca);
            //VIB-Petició 17feb2025-Actiu=No quan posem una data de baixa
            if ($request->hard_fbaja !== null) {
              $reg->hard_fbaja = $request->hard_fbaja;
              $reg->hard_acti = "N";
            } else {
              $reg->hard_fbaja = $request->hard_fbaja;
            }
            $reg->hard_obse = strtoupper($request->observ);
            if($request->prestat == "true") {
              $reg->prestat = "S";
            } else {
              $reg->prestat = "N";
            }
            $reg->hard_pliego = strtoupper($request->plec);

            $reg->save();

            session(['status' => 'El component '.$request->codi.' s\'ha modificat correctament.']);
            Session::flash('saving-msg', 'S\'ha modificat el component '.$codi. '.');

            return Redirect::to(session('url_llistat'));

          } else {
            //el nom del centre o el departament no és correcte
            $request->session()->flash('status', 'Reviseu el nom del centre, el departament assignat i el proveïdor. Si és necessari, podeu crear un nou centre des del menú Administració -> Centres.');
            return view('explotacio.element-edit', ['dataReg'=>$element, 'urlLlistat'=>session('url_llistat'), 'centres'=>$centres, 
                'plantes'=>$plantes, 'despatxos'=>$despatxos, 'plantesCentre'=>$plantesCentre, 'despatxosPlanta'=>$despatxosPlanta, 'tipus'=>$tipus]);
          }

        }
        
      }

      public function eliminar($codi) {

        //VIB-Guardar la URL del llistat amb la cerca
        if (url()->previous() != url()->current()){
          session(['url_llistat' => url()->previous()]);
        }

        $element = Element::select('dbo.cathard.*','dbo.centros.nombre_centro', 'dbo.catdpto.dpto_desc', 'dbo.catprov.prov_desc', 'dbo.cattipo.tipo_desc')
          ->leftjoin('dbo.centros', 'dbo.cathard.hard_ctra', '=', 'dbo.centros.cod_centro')
          ->leftjoin('dbo.catdpto', 'dbo.cathard.hard_dpto', '=', 'dbo.catdpto.dpto_cod')
          ->leftjoin('dbo.catprov', 'dbo.cathard.hard_prov', '=', 'dbo.catprov.prov_cod')
          ->leftjoin('dbo.cattipo', 'dbo.cathard.hard_tipo', '=', 'dbo.cattipo.tipo_cod')
          ->where('dbo.cathard.hard_cod', '=', $codi)->first();
    
        return view('explotacio.element-delete', ['element'=>$element, 'urlLlistat'=>session('url_llistat')]);
      }
    
    public function eliminar_def(Request $request){
    
         $codi = $request->input('codi');
    
         $deleted = Element::where('hard_cod', $codi)->forceDelete();
         if ($deleted > 0) {
          Session::flash('delete-msg', 'El component '.$codi. ' s\'ha esborrat correctament.');
        } else {
          Session::flash('delete-msg', 'Error esborrant el component '.$codi. '.');
        }
         return Redirect::to(session('url_llistat'));
    }

    public function altaMultiple() {
        //VIB-Guardar la URL del llistat amb la cerca
        if (url()->previous() != url()->current()){
          session(['url_llistat' => url()->previous()]);
        }
        $centres = Centre::select('cod_centro','nombre_centro')->get();
        $plantes = Planta::select('planta', 'descripcion', 'centro')->get();
        $despatxos = Despatx::select('centro','despacho','planta','descripcion')->get();
        $request = new ElementFormulari;
        $plantesCentre = [];
        $despatxosPlanta = [];
        $tipus = Elemtipo::select('tipo_cod','tipo_desc','es_puesto')->get(); 
        $departaments = Departament::select('dpto_cod', 'dpto_desc', 'dpto_empr')->where('dpto_activo', '=', 'S')->get();

        return view('explotacio.element-multiple', ['urlLlistat'=>session('url_llistat'), 'centres'=>$centres,
              'plantes'=>$plantes, 'despatxos'=>$despatxos, 'request'=>$request, 'plantesCentre'=>$plantesCentre,
              'despatxosPlanta'=>$despatxosPlanta, 'tipus'=>$tipus, 'departaments'=>$departaments]);
      }

      public function altaMultipleValidar(Request $request) {
        $request->session()->forget('status');

        $codi = $request->codi;
        $codiFinal = $request->codiFinal;
        $lletres = $request->lletres;
        $lletraFinal = $request->lletraFinal;

        $centres = Centre::select('cod_centro','nombre_centro')->get();
        $plantes = Planta::select('planta', 'descripcion', 'centro')->get();
        $despatxos = Despatx::select('centro','despacho','planta','descripcion')->get();
        $tipus = Elemtipo::select('tipo_cod','tipo_desc','es_puesto')->get(); 
        $departaments = Departament::select('dpto_cod', 'dpto_desc', 'dpto_empr')->where('dpto_activo', '=', 'S')->get();

        $plantesCentre = [];
        if ($request->centre !== "-1") {
          $plantesCentre = Planta::select('planta','descripcion','centro')->where('centro', '=', $request->centre)->get();
        }
        $despatxosPlanta = [];
        if (($request->planta != "-1") and ($request->centre != "-1")) {
            $despatxosPlanta = Despatx::select('centro','despacho','planta','descripcion')->where('centro', '=', $request->centre)
                ->where('planta', '=', $request->planta)->get();
        }

        if($codiFinal < $codi) {
          $request->session()->flash('status', 'El codi final és menor que el codi inicial.');
          return view('explotacio.element-multiple', ['urlLlistat'=>session('url_llistat'), 'centres'=>$centres,
                  'plantes'=>$plantes, 'despatxos'=>$despatxos, 'request'=>$request, 'plantesCentre'=>$plantesCentre,
                  'despatxosPlanta'=>$despatxosPlanta, 'tipus'=>$tipus, 'departaments'=>$departaments]);
        }

        //Guardar zeros del principi del codi
        $zeros = '';
        $n = 0;
        while ($codi[$n] == '0') {
          $zeros = $zeros . '0';
          $n++;
        }
        $codi = ltrim($codi, '0');
        $codiFinal = ltrim($codiFinal, '0');

        $exist = false;
        $codiRep = '';
        for ($n=$codi; $n <= $codiFinal; $n++) {
          $element = Element::select('dbo.cathard.*')
            ->where('dbo.cathard.hard_cod', '=', $lletres.$zeros.$n.$lletraFinal)->first();
          if($element !== null) {
            $exist = true;
            $codiRep = $codiRep . " " . $lletres.$zeros.$n.$lletraFinal;
          }
        }

        if ($exist) {
        
          $request->session()->flash('status', 'Els codis '. $codiRep . ' ja s\'han utilitzat per a un altre component.');
          return view('explotacio.element-multiple', ['urlLlistat'=>session('url_llistat'), 'centres'=>$centres,
                  'plantes'=>$plantes, 'despatxos'=>$despatxos, 'request'=>$request, 'plantesCentre'=>$plantesCentre,
                  'despatxosPlanta'=>$despatxosPlanta, 'tipus'=>$tipus, 'departaments'=>$departaments]);

        } else {

          //VIB-10gen2025-Comprovar si s'ha escrit un centre i un departament existent
          $codCentre = $request->centre;
          $codPlanta = $request->planta;
          //Comprovar si s'ha escrit un proveïdor existent o està el camp en blanc
          $provOk = false;
          $codProv = '';
          if ($request->proveidor == '') {
            $provOk = true;
          } else {
            $codProv = Proveidor::select('prov_cod')->where('prov_desc', '=', $request->proveidor)->first();
            if ($codProv !== null) {
              $provOk = true;
            }
          }

          if ($codCentre !== "-1" && $codPlanta !== "-1" && $request->assignat !== null && $provOk && $request->tipus !== null) {

            //Dades correctes
            //VIB-3jun2025-Si els elements a donar d'alta són "puesto", sols 6 caracters en el codi
            $regTipus = Elemtipo::where('tipo_cod', '=', $request->tipus)->first(); 
            if ($regTipus->es_puesto == 1) {
              if (strlen($request->lletres)+strlen($request->codiFinal)+strlen($request->lletraFinal) > 6) {
                $request->session()->flash('status', 'En el cas dels llocs (Unitat central, portàtil, servidor...), el codi de l\'element té un màxim de 6 caracters.');
                return view('explotacio.element-multiple', ['urlLlistat'=>session('url_llistat'), 'centres'=>$centres,
                    'plantes'=>$plantes, 'despatxos'=>$despatxos, 'request'=>$request, 'plantesCentre'=>$plantesCentre,
                    'despatxosPlanta'=>$despatxosPlanta, 'tipus'=>$tipus, 'departaments'=>$departaments]);
              }
            }

            //Demanar confirmació
            $numReg = $codiFinal - $codi + 1;
            $nomCentre = Centre::select('nombre_centro')->where('cod_centro', '=', $request->centre)->first();
            $nomTipus = Elemtipo::select('tipo_desc')->where('tipo_cod', '=', $request->tipus)->first();
            $nomDep = Departament::select('dpto_desc')->where('dpto_cod', '=', $request->assignat)->first();

            return view('explotacio.element-multiple-confirm', ['elements'=>$request, 'urlLlistat'=>session('url_llistat'),
                        'numReg'=>$numReg, 'nomCentre'=>$nomCentre, 'nomTipus'=>$nomTipus, 'nomDep'=>$nomDep]);
            
          } else {
            //el nom del centre o el departament no és correcte
            $request->session()->flash('status', 'Reviseu el nom del centre, la planta, el tipus de component, el departament assignat i el proveïdor. Si és necessari, podeu crear un nou centre des del menú Administració -> Centres.');
            return view('explotacio.element-multiple', ['urlLlistat'=>session('url_llistat'), 'centres'=>$centres,
                    'plantes'=>$plantes, 'despatxos'=>$despatxos, 'request'=>$request, 'plantesCentre'=>$plantesCentre,
                    'despatxosPlanta'=>$despatxosPlanta, 'tipus'=>$tipus, 'departaments'=>$departaments]);
          }

        }
      }

      public function altaMultipleGuardar(Request $request) {

        $request->session()->forget('status');

        $codi = $request->codi;
        $codiFinal = $request->codiFinal;
        $lletres = $request->lletres;
        $codCentre = $request->centre;
        $codPlanta = $request->planta;
        $lletraFinal = $request->lletraFinal;

        $prov = '';
        if ($request->proveidor !== null) {
          $prov = Proveidor::select('prov_cod')->where('prov_desc', '=', $request->proveidor)->first();
        }
            //Guardar zeros del principi del codi
            $zeros = '';
            $n = 0;
            while ($codi[$n] == '0') {
              $zeros = $zeros . '0';
              $n++;
            }
            $codi = ltrim($codi, '0');
            $codiFinal = ltrim($codiFinal, '0');

            //guardar els registres
            for ($n=$codi; $n <= $codiFinal; $n++) {
              $reg = Element::firstOrNew(array('hard_cod' => $lletres.$zeros.$n.$lletraFinal));
              $reg->hard_iloc = "N";
              $reg->hard_acti = "S";
              $reg->hard_desa = "N";
              $reg->hard_ctra = $codCentre;
              $reg->hard_piso = $codPlanta;
              if ($request->despatx !== "-1") {
                $reg->hard_desp = $request->despatx;
              } else {
                $reg->hard_desp = null;
              }
              $reg->hard_dpto = $request->assignat;
              $reg->hard_ffac = $request->fcompra;
              $reg->hard_gara = $request->anys_garantia;
              if ($prov !== '') {
                $reg->hard_prov = $prov->prov_cod;
              } else {
                $reg->hard_prov = null;
              }
              $reg->hard_tipo = $request->tipus;
              $reg->hard_mode = $request->model;
              $reg->hard_marc = $request->marca;
              $reg->hard_obse = "";
              $reg->hard_vers = 1;
              $reg->prestat = "N";
              $reg->hard_pliego = $request->plec;
  
              $reg->save();
            }

            $request->session()->flash('status', 'Components guardats correctament.');
            return Redirect::to(session('url_llistat'));

      }

      public function altaExcel(Request $request) {

        $centres = Centre::select('cod_centro','nombre_centro')->get();
        $plantes = Planta::select('planta', 'descripcion', 'centro')->get();
        $despatxos = Despatx::select('centro','despacho','planta','descripcion')->get();
        $plantesCentre = [];
        $despatxosPlanta = [];
        $tipus = Elemtipo::select('tipo_cod','tipo_desc','es_puesto')->get(); 
        $departaments = Departament::select('dpto_cod', 'dpto_desc', 'dpto_empr')->where('dpto_activo', '=', 'S')->get();

        $request->validate([
          'arxiuExcel'=> 'required|mimes:xls,xlsx,csv|max:2048', 
        ]);

        $ext = $request->arxiuExcel->extension();

        if($ext == 'xls' || $ext == 'xlsx' || $ext == 'csv'){
            //$fileName = time().'.'.$ext;  
            //guardar en storage/app/excels
            //$request->arxiuExcel->storeAs('excels', $fileName);
            $spreadsheet = new Spreadsheet();
            $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($request->arxiuExcel);
            $worksheet = $spreadsheet->getSheet(0);
            $codi = 'a';
            $numSerieNou = '';
            $connexioNova = '';
            $n = 1;
            $llista = "<br>";
            $llistaError = "<br>";
            $errors = false;
            while ($codi !== null) {
              $codi = $worksheet->getCell('A'.$n)->getValue();
              $numSerieNou = $worksheet->getCell('B'.$n)->getValue();
              $connexioNova = $worksheet->getCell('C'.$n)->getValue();
              //Seleccionar el component
              $element = Element::select('dbo.cathard.*')->where('hard_cod', '=', $codi)->first();
              if ($element !== null) {
                //Actualitzar camps
                $element->hard_nser = $numSerieNou;
                $element->hard_conx = $connexioNova;
                $element->save();
                $llista = $llista . $codi . ' ' . $numSerieNou . ' ' . $connexioNova . "<br>";
              } else {
                $errors = true;
                $llistaError = $llistaError . $codi . "<br>";
              }
              
              $n++;
            }

            $request->session()->flash('status', 'S\'han actualitzat els components:' . $llista);
            return Redirect::to(session('url_llistat'));
        } else {
          $request->session()->flash('status', 'Error en l\'enviament de l\'arxiu.');
          return view('explotacio.element-multiple', ['urlLlistat'=>session('url_llistat'), 'centres'=>$centres,
                      'plantes'=>$plantes, 'despatxos'=>$despatxos, 'request'=>$request, 'plantesCentre'=>$plantesCentre,
                      'despatxosPlanta'=>$despatxosPlanta, 'tipus'=>$tipus, 'departaments'=>$departaments]);
        }

      }

      public function altaMultipleConf() {
        //VIB-Guardar la URL del llistat amb la cerca
        if (url()->previous() != url()->current()){
          session(['url_llistat' => url()->previous()]);
        }
        
        $request = new ElementFormulari;
       
        $configuracio = new Configuracio();

        return view('explotacio.configuracio-multiple', ['urlLlistat'=>session('url_llistat'), 'request'=>$request, 'configuracio'=>$configuracio]);
      }

      public function altaMultipleConfValidar(Request $request) {
        $request->session()->forget('status');

        $codi = $request->codi;
        $codiFinal = $request->codiFinal;
        $lletres = $request->lletres;

        $configuracio = new Configuracio();

        if($codiFinal < $codi) {
          $request->session()->flash('status', 'El codi final és menor que el codi inicial.');
          return view('explotacio.configuracio-multiple', ['urlLlistat'=>session('url_llistat'), 'request'=>$request, 'configuracio'=>$configuracio]);
        }

        if($codi > 9999) {
          $request->session()->flash('status', 'El codi numèric ha de tindre 4 cifres.');
          return view('explotacio.configuracio-multiple', ['urlLlistat'=>session('url_llistat'), 'request'=>$request, 'configuracio'=>$configuracio]);
        }

        //Guardar zeros del principi del codi
        $zeros = '';
        $n = 0;
        while ($codi[$n] == '0') {
          $zeros = $zeros . '0';
          $n++;
        }
        $codi = ltrim($codi, '0');
        $codiFinal = ltrim($codiFinal, '0');

        $exist = true;
        $codiRep = '';
        for ($n=$codi; $n <= $codiFinal; $n++) {
          $element = Element::select('dbo.cathard.*')
            ->where('dbo.cathard.hard_cod', '=', $lletres.$zeros.$n)->first();
          if($element == null) {
            $exist = false;
            $codiRep = $codiRep . " " . $lletres.$zeros.$n;
          }
        }

        if (!$exist) {
        
          $request->session()->flash('status', 'No hi ha components amb els codis '. $codiRep . '.');
          return view('explotacio.configuracio-multiple', ['urlLlistat'=>session('url_llistat'), 'request'=>$request, 'configuracio'=>$configuracio]);

        } else {

            //Dades correctes

            //Demanar confirmació
            $numReg = $codiFinal - $codi + 1;

            return view('explotacio.configuracio-multiple-confirm', ['elements'=>$request, 'urlLlistat'=>session('url_llistat'),
                        'numReg'=>$numReg]);
            
        }
      }

      public function altaMultipleConfGuardar(Request $request) {

        $request->session()->forget('status');

        $codi = $request->codi;
        $codiFinal = $request->codiFinal;
        $lletres = $request->lletres;
        
        //Guardar zeros del principi del codi
            $zeros = '';
            $n = 0;
            while ($codi[$n] == '0') {
              $zeros = $zeros . '0';
              $n++;
            }
            $codi = ltrim($codi, '0');
            $codiFinal = ltrim($codiFinal, '0');

            //guardar els registres
            for ($n=$codi; $n <= $codiFinal; $n++) {
              $reg = Configuracio::firstOrNew(array('codigo' => $lletres.$zeros.$n));
              $reg->procesador = $request->proc;
              $reg->velocidad = $request->velocitat;
              $reg->ram = $request->ram;
              $reg->tipo_memoria = $request->tipo_memoria;
              $reg->hd = $request->hd;
              
              $reg->video = $request->video;
              $reg->placa_base = $request->placa_base;
              $reg->tarjeta_sonido = $request->tarjeta_sonido;
              
              $reg->otros = $request->otros;
  
              $reg->save();
            }

            $request->session()->flash('status', 'Configuració guardada correctament.');
            return Redirect::to(session('url_llistat'));

      }

}
