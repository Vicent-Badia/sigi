<?php

namespace App\Http\Controllers\Llistats;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Redirect;

use App\Models\ReportFormulari;
use App\Models\Element;
use App\Models\Centre;
use App\Models\Planta;
use App\Models\Despatx;
use App\Models\Departament;
use App\Models\Elemtipo;
use App\Models\RecompteHw;

//VIB-28feb2025 - Llibreria DomPdf
use Barryvdh\DomPDF\Facade\Pdf;

//VIB-8abr2025 - Llibreria PhpSpreadsheet
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

//VIB-10abr2025 - Llibreria PhpWord
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\Writer\Word2007;
use PhpOffice\PhpWord\Settings;

//VIB-12jun2024 - Middlewares utilitzats per a comprovar el perfil de l'usuari
use App\Http\Middleware\PerfilOperador;

use function PHPUnit\Framework\isEmpty;

class LlistatsController extends \Illuminate\Routing\Controller
{
        //VIB - 2set2022 - Sols tenen accés usuaris amb permís d'autoritzadors o administradors
        public function __construct() {
            $this->middleware(PerfilOperador::class);
          }
      
          public function home()
          {
            $centres = Centre::select('cod_centro','nombre_centro')->get();
            $plantes = Planta::select('planta', 'descripcion', 'centro')->get();
            $despatxos = Despatx::select('centro','despacho','planta','descripcion')->get();
            $plantesCentre = [];
            $despatxosPlanta = [];
            $categories = Elemtipo::select('tipo_cod','tipo_desc','es_puesto')->get(); 
            $departaments = Departament::select('dpto_cod', 'dpto_desc', 'dpto_empr')->where('dpto_activo', '=', 'S')->get();
    
            $request = new ReportFormulari();
            Session::forget('report-msg');
            Session::forget('error-msg');

            return view('llistats.home', ['request'=>$request,  'centres'=>$centres,
              'plantes'=>$plantes, 'despatxos'=>$despatxos, 'plantesCentre'=>$plantesCentre,
              'despatxosPlanta'=>$despatxosPlanta, 'categories'=>$categories, 'departaments'=>$departaments]);
          }

        public function validate(Request $request) {

          $centres = Centre::select('cod_centro','nombre_centro')->get();
          $plantes = Planta::select('planta', 'descripcion', 'centro')->get();
          $despatxos = Despatx::select('centro','despacho','planta','descripcion')->get();
          $plantesCentre = [];
          $despatxosPlanta = [];
          $categories = Elemtipo::select('tipo_cod','tipo_desc','es_puesto')->get(); 
          $departaments = Departament::select('dpto_cod', 'dpto_desc', 'dpto_empr')->where('dpto_activo', '=', 'S')->get();

          Session::forget('report-msg');
          Session::forget('error-msg');

          if ($request->tipusLlistat > 5 || $request->tipusLlistat < 1) {
            Session::flash('error-msg', 'Seleccioneu una opció per a generar el llistat');
            return view('llistats.home', ['request'=>$request,  'centres'=>$centres,
              'plantes'=>$plantes, 'despatxos'=>$despatxos, 'plantesCentre'=>$plantesCentre,
              'despatxosPlanta'=>$despatxosPlanta, 'categories'=>$categories, 'departaments'=>$departaments]);
          } elseif ($request->tipus_informe === null) {
            Session::flash('error-msg', 'Seleccioneu el tipus de document a generar');
            return view('llistats.home', ['request'=>$request,  'centres'=>$centres,
              'plantes'=>$plantes, 'despatxos'=>$despatxos, 'plantesCentre'=>$plantesCentre,
              'despatxosPlanta'=>$despatxosPlanta, 'categories'=>$categories, 'departaments'=>$departaments]);
          }
          
          else {

            if ($request->tipusLlistat == 1) {
              $data = $this->filtrarPerLoccalitzacio($request);

              $numReg = $data->count(); 
              if ($numReg == 0) {
                Session::flash('report-msg', 'No hi ha registres per als criteris seleccionats.');
                return view('llistats.home', ['request'=>$request,  'centres'=>$centres,
                  'plantes'=>$plantes, 'despatxos'=>$despatxos, 'plantesCentre'=>$plantesCentre,
                  'despatxosPlanta'=>$despatxosPlanta, 'categories'=>$categories, 'departaments'=>$departaments]);
              } elseif ($numReg < 1000) {
                  session(['data' => $data]);
                  if($request->tipus_informe === "PDF") {
                    //url de la ruta que torna el PDF (veure routes/web.php)
                    //la ruta crida a la funció de baix: llistatLocalitzacio()
                    return Redirect::to('llistat-localitzacio');
                  }
                  elseif ($request->tipus_informe === "EXCEL") {
                    $this->excelLocalitzacio($data);
                  }
                  elseif ($request->tipus_informe === "WORD") {
                    $this->wordLocalitzacio($data);
                  }
                  
              } else {
                Session::flash('error-msg', 'Hi ha massa dades per a crear el document (més de 1000 registres). Seleccioneu algun altre criteri per a generar el llistat.');
                return view('llistats.home', ['request'=>$request,  'centres'=>$centres,
                  'plantes'=>$plantes, 'despatxos'=>$despatxos, 'plantesCentre'=>$plantesCentre,
                  'despatxosPlanta'=>$despatxosPlanta, 'categories'=>$categories, 'departaments'=>$departaments]);
              }
              

            } else if ($request->tipusLlistat == 2) {

              $data = $this->filtrarPerDepartament($request);

              $numReg = $data->count(); 
              if ($numReg == 0) {
                Session::flash('report-msg', 'No hi ha registres per als criteris seleccionats.');
                return view('llistats.home', ['request'=>$request,  'centres'=>$centres,
                  'plantes'=>$plantes, 'despatxos'=>$despatxos, 'plantesCentre'=>$plantesCentre,
                  'despatxosPlanta'=>$despatxosPlanta, 'categories'=>$categories, 'departaments'=>$departaments]);
              } elseif ($numReg < 1000) {
                session(['data' => $data]);
                if($request->tipus_informe === "PDF") {
                  //url de la ruta que torna el PDF (veure routes/web.php)
                  //la ruta crida a la funció de baix: llistatDepartaments()
                  return Redirect::to('llistat-departaments');
                }
                elseif ($request->tipus_informe === "EXCEL") {
                  $this->excelDepartaments($data);
                }
                elseif ($request->tipus_informe === "WORD") {
                  $this->wordDepartaments($data);
                }
                  
              } else {
                Session::flash('error-msg', 'Hi ha massa dades per a crear el document (més de 1000 registres). Seleccioneu algun altre criteri per a generar el llistat.');
                return view('llistats.home', ['request'=>$request,  'centres'=>$centres,
                  'plantes'=>$plantes, 'despatxos'=>$despatxos, 'plantesCentre'=>$plantesCentre,
                  'despatxosPlanta'=>$despatxosPlanta, 'categories'=>$categories, 'departaments'=>$departaments]);
              }

            } else if ($request->tipusLlistat == 3) {

              if ($request->dataRef == '') {
                Session::flash('error-msg', 'Heu de seleccionar una data de referència per a calcular la garantia dels components.');
                return view('llistats.home', ['request'=>$request,  'centres'=>$centres,
                  'plantes'=>$plantes, 'despatxos'=>$despatxos, 'plantesCentre'=>$plantesCentre,
                  'despatxosPlanta'=>$despatxosPlanta, 'categories'=>$categories, 'departaments'=>$departaments]);
              } else {
                $data = $this->filtrarPerGarantia($request);
                //VIB-5mar2025-$data conté els registres filtrats per centre, tipus de component i sols aquells que tenen data de compra
                //ara hem de filtrar els que estan en garantia
                $index = 0;
                if(!$data->isEmpty()) {
                  foreach($data as $item) {
                    $anys = 2;
                    if ($item->hard_gara !== null) {
                      $anys = $item->hard_gara;
                    }
                    $dataFi = date_add(date_create($item->hard_ffac), date_interval_create_from_date_string($anys . " years"));
                    //si la data de fi de la garantia es menor que la data de referència, llevem l'item de la llista
                    if ($dataFi->format('Y-m-d') < $request->dataRef) {
                      unset($data[$index]);
                    } else {
                      //format per a mostrar la data de compra en el PDF
                      $item->hard_ffac = date_create($item->hard_ffac)->format('d-m-Y');
                    }
                    $index++;
                  }
                  //Ordenar registres
                  if ($request->filtre == "C") {
                    //->orderBy('hard_ctra', 'asc')->orderBy('hard_tipo','asc')->orderBy('hard_cod','asc')->get(); 
                    //->orderBy('hard_ctra', 'asc')->orderBy('hard_tipo','asc')->orderBy('hard_marc','asc')->orderBy('hard_mode','asc')->get(); 
                    $dataOrd = $data->sortBy([
                      ['hard_ctra','asc'],
                      ['hard_tipo','asc'],
                      ['hard_cod','asc']
                    ]);
                  } else {
                    $dataOrd = $data->sortBy([
                      ['hard_ctra','asc'],
                      ['hard_tipo','asc'],
                      ['hard_marc','asc'],
                      ['hard_mode','asc']
                    ]);
                  }
                  $dataOrd->values()->all();
                } 
      
                $numReg = $dataOrd->count(); 
                if ($numReg == 0 || $dataOrd->isEmpty()) {
                  Session::flash('report-msg', 'No hi ha registres per als criteris seleccionats.');
                  return view('llistats.home', ['request'=>$request,  'centres'=>$centres,
                    'plantes'=>$plantes, 'despatxos'=>$despatxos, 'plantesCentre'=>$plantesCentre,
                    'despatxosPlanta'=>$despatxosPlanta, 'categories'=>$categories, 'departaments'=>$departaments]);
                } elseif ($numReg < 1000) {
                  session(['data' => $dataOrd]);
                  session(['dataRef' => $request->dataRef]);
                  if($request->tipus_informe === "PDF") {
                    //url de la ruta que torna el PDF (veure routes/web.php)
                    //la ruta crida a la funció de baix: llistatGarantia()
                    return Redirect::to('llistat-garantia');
                  }
                  elseif ($request->tipus_informe === "EXCEL") {
                    $this->excelGarantia($data);
                  }
                  elseif ($request->tipus_informe === "WORD") {
                    $this->wordGarantia($data);
                  }
                    
                } else {
                  Session::flash('error-msg', 'Hi ha massa dades per a crear el document (més de 1000 registres). Seleccioneu algun altre criteri per a generar el llistat.');
                  return view('llistats.home', ['request'=>$request,  'centres'=>$centres,
                    'plantes'=>$plantes, 'despatxos'=>$despatxos, 'plantesCentre'=>$plantesCentre,
                    'despatxosPlanta'=>$despatxosPlanta, 'categories'=>$categories, 'departaments'=>$departaments]);
                }
              }
            
            } else if ($request->tipusLlistat == 4) {

              if ($request->centre == -1) {
                Session::flash('error-msg', 'Heu de seleccionar un centre.');
                return view('llistats.home', ['request'=>$request,  'centres'=>$centres,
                  'plantes'=>$plantes, 'despatxos'=>$despatxos, 'plantesCentre'=>$plantesCentre,
                  'despatxosPlanta'=>$despatxosPlanta, 'categories'=>$categories, 'departaments'=>$departaments]);
              } else {
                $data = $this->componentsPerCentre($request);

                $numReg = $data->count(); 
                if ($numReg == 0) {
                  Session::flash('report-msg', 'No hi ha registres per als criteris seleccionats.');
                  return view('llistats.home', ['request'=>$request,  'centres'=>$centres,
                    'plantes'=>$plantes, 'despatxos'=>$despatxos, 'plantesCentre'=>$plantesCentre,
                    'despatxosPlanta'=>$despatxosPlanta, 'categories'=>$categories, 'departaments'=>$departaments]);
                } else {
                    session(['data' => $data]);
                    if($request->tipus_informe === "PDF") {
                      //url de la ruta que torna el PDF (veure routes/web.php)
                      //la ruta crida a la funció de baix: llistatRecompte()
                      return Redirect::to('llistat-recompte');
                    }
                    elseif ($request->tipus_informe === "EXCEL") {
                      $this->excelRecompte($data);
                    }
                    elseif ($request->tipus_informe === "WORD") {
                      $this->wordRecompte($data);
                    }
                }
              }

            } else if ($request->tipusLlistat == 5) {

              $data = $this->filtrarPrestats($request);

              $numReg = $data->count(); 
              if ($numReg == 0) {
                Session::flash('report-msg', 'No hi ha registres per als criteris seleccionats.');
                return view('llistats.home', ['request'=>$request,  'centres'=>$centres,
                  'plantes'=>$plantes, 'despatxos'=>$despatxos, 'plantesCentre'=>$plantesCentre,
                  'despatxosPlanta'=>$despatxosPlanta, 'categories'=>$categories, 'departaments'=>$departaments]);
              } elseif ($numReg < 1000) {
                session(['data' => $data]);
                if($request->tipus_informe === "PDF") {
                  //url de la ruta que torna el PDF (veure routes/web.php)
                  //la ruta crida a la funció de baix: llistatPrestats()
                  return Redirect::to('llistat-prestats');
                }
                elseif ($request->tipus_informe === "EXCEL") {
                  $this->excelPrestats($data);
                }
                elseif ($request->tipus_informe === "WORD") {
                  $this->wordPrestats($data);
                }
                  
              } else {
                Session::flash('error-msg', 'Hi ha massa dades per a crear el document (més de 1000 registres). Seleccioneu algun altre criteri per a generar el llistat.');
                return view('llistats.home', ['request'=>$request,  'centres'=>$centres,
                  'plantes'=>$plantes, 'despatxos'=>$despatxos, 'plantesCentre'=>$plantesCentre,
                  'despatxosPlanta'=>$despatxosPlanta, 'categories'=>$categories, 'departaments'=>$departaments]);
              }

            }

          }
          
        }

        private function ordenarPerCodi($a, $b) {
          $diff = $a->hard_ctra - $b->hard_ctra;
          return ($diff !== 0) ? $diff : $a->hard_tipo - $b->hard_tipo;
        }

        public function llistatLocalitzacio() {

          $data = session('data');

          //VIB-3mar2025-Hem de passar la colecció $data com un array "data" que conté la colecció d'objectes
          $pdf = Pdf::loadView('llistats.pdfLocalitzacio',['data' => $data])->setPaper('a4','landscape');
       
          // use this method to stream it download
          //return $pdf->download();

          // use this method to stream it online 
          //return $pdf->stream(); 

          //Canviar timeout del servidor
          ini_set('max_execution_time', 120); //120 seconds = 2 minutes

          //return $pdf->stream('llistat_per_plantes.pdf');
          return $pdf->download('llistat_per_plantes.pdf');
        }

        public function excelLocalitzacio($data) {

          $spreadsheet = new Spreadsheet();
          $sheet = $spreadsheet->getActiveSheet();

          $sheet->setTitle('SIGI - Components per ubicació'); 
          $sheet->setCellValue('A1', 'SIGI - Llistat de components per ubicació');

           $centre= $data[0]->nombre_centro;
           $planta = $data[0]->hard_piso;
           if ($data[0]->hard_desp == "-1" || $data[0]->hard_desp == "" || $data[0]->hard_desp == null) {
                $despatx = "";
                $despatxDesc = "";
           } else {
                $despatx = $data[0]->hard_desp;
                $despatxDesc = $data[0]->descripcion;
           }

          $ubicacio = $centre.' Planta '.$planta;
          if ($despatx !== "") {
            $ubicacio = $ubicacio . " Despatx " .$despatx." ".$despatxDesc;
          }
          $sheet->setCellValue('A3', $ubicacio);
          $sheet->setCellValue('A4', "Codi");
          $sheet->setCellValue('B4', "Tipo");
          $sheet->setCellValue('C4', "Marca");
          $sheet->setCellValue('D4', "Model");
          $sheet->setCellValue('E4', "Núm. sèrie");
          $sheet->setCellValue('F4', "Departament");
          $sheet->setCellValue('G4', "Connexió");
          $sheet->setCellValue('H4', "Usuari");

          $row = 5;// Initialize row counter

          foreach ($data as $item) {
            if ($item->hard_desp == "-1" || $item->hard_desp == "" || $item->hard_desp == null) {
              $nouDespatx = "";
              $nouDespatxDesc = "";
            } else {
                $nouDespatx = $item->hard_desp;
                $nouDespatxDesc = $item->descripcion;
            }
            if ($item->hard_piso !== $planta || $item->nombre_centro !== $centre || $nouDespatx !== $despatx ) {
                  $centre= $item->nombre_centro;
                  $planta = $item->hard_piso;
                  $despatx = $nouDespatx;
                  $despatxDesc = $nouDespatxDesc;
                  
                  $row++;
                  $ubicacio = $centre.' Planta '.$planta;
                  if ($despatx !== "") {
                    $ubicacio = $ubicacio . " Despatx " .$despatx." ".$despatxDesc;
                  }
                  $row++;
                  $sheet->setCellValue('A'.$row, $ubicacio);
                  $row++;
                  $sheet->setCellValue('A'.$row, "Codi");
                  $sheet->setCellValue('B'.$row, "Tipo");
                  $sheet->setCellValue('C'.$row, "Marca");
                  $sheet->setCellValue('D'.$row, "Model");
                  $sheet->setCellValue('E'.$row, "Núm. sèrie");
                  $sheet->setCellValue('F'.$row, "Departament");
                  $sheet->setCellValue('G'.$row, "Connexió");
                  $sheet->setCellValue('H'.$row, "Usuari");

                  $row++;
            }

            $sheet->setCellValue('A'.$row, $item->hard_cod);
            $sheet->setCellValue('B'.$row, $item->tipo_desc);
            $sheet->setCellValue('C'.$row, $item->hard_marc);
            $sheet->setCellValue('D'.$row, $item->hard_mode);
            $sheet->setCellValue('E'.$row, $item->hard_nser);
            $sheet->setCellValue('F'.$row, $item->dpto_desc);
            $sheet->setCellValue('G'.$row, $item->hard_conx);
            $sheet->setCellValue('H'.$row, $item->hard_usua);
            $row++;
          }

          $writer = new Xlsx($spreadsheet);
          $fileName = "Llistat_per_plantes.xlsx";

          header("Cache-Control: public");
          header("Content-Description: File Transfer");
          header("Content-Disposition: attachment; filename=" . $fileName);
          header("Content-Type: application/xlsx");
          header("Content-Transfer-Encoding: binary");

          $writer->save("php://output");
          //return response()->download('php://output');

        }

        public function wordLocalitzacio($data) {

          $doc = new PhpWord();

          //VIB-10abr2025-Afegir estils al document
          $doc->addFontStyle(
            "Title1",
            array('name' => 'Arial', 'size' => 18, 'color' => '000000', 'bold' => true)
          );
          $doc->addFontStyle(
            "Title2",
            array('name' => 'Arial', 'size' => 16, 'color' => '333333', 'bold' => false)
          );
          $doc->addFontStyle(
            "Normal",
            array('name' => 'Arial', 'size' => 12, 'color' => '000000', 'bold' => false)
          );
          //Afegir una secció al document
          $paper = new \PhpOffice\PhpWord\Style\Paper();
          $section = $doc->addSection(array(
            'pageSizeW' => $paper->getWidth(), 
            'pageSizeH' => $paper->getHeight(), 
            'orientation' => 'landscape'
          ));
          $section->addText("SIGI - Llistat de components per ubicació", "Title1");

          $centre= $data[0]->nombre_centro;
           $planta = $data[0]->hard_piso;
           if ($data[0]->hard_desp == "-1" || $data[0]->hard_desp == "" || $data[0]->hard_desp == null) {
                $despatx = "";
                $despatxDesc = "";
           } else {
                $despatx = $data[0]->hard_desp;
                $despatxDesc = $data[0]->descripcion;
           }

          $ubicacio = $centre.' Planta '.$planta;
          if ($despatx !== "") {
            $ubicacio = $ubicacio . " Despatx " .$despatx." ".$despatxDesc;
          }

          $section->addText("<w:br/>".$ubicacio."<w:br/>", "Title2");

          //Afegir taula
          $styleTable = array(
            'borderColor' => 'FFFFFF',
            'borderSize'  => 6,
            'cellMargin'  => 80
          );
          $styleFirstRow = array('bgColor' => 'DDDDDD');
          $styleCell = array('valign' => 'center');
          $fontStyle = array('size' => 12, 'bold' => false);

          $doc->addTableStyle('Taula', $styleTable, $styleFirstRow);
          $table = $section->addTable('Taula');

          $rowHeight = 700;
          $table->addRow($rowHeight);
          $table->addCell(600, $styleCell)->addText(htmlspecialchars("Codi"), $fontStyle);
          $table->addCell(800, $styleCell)->addText(htmlspecialchars("Tipo"), $fontStyle);
          $table->addCell(700, $styleCell)->addText(htmlspecialchars("Marca"), $fontStyle);
          $table->addCell(2500, $styleCell)->addText(htmlspecialchars("Model"), $fontStyle);
          $table->addCell(1500, $styleCell)->addText(htmlspecialchars("Núm. sèrie"), $fontStyle);
          $table->addCell(1500, $styleCell)->addText(htmlspecialchars("Departament"), $fontStyle);
          $table->addCell(800, $styleCell)->addText(htmlspecialchars("Connexió"), $fontStyle);
          $table->addCell(2500, $styleCell)->addText(htmlspecialchars("Usuari"), $fontStyle);

          //$section->addTextBreak(1);

          $n = 0;
          foreach ($data as $item) {
            if ($item->hard_desp == "-1" || $item->hard_desp == "" || $item->hard_desp == null) {
              $nouDespatx = "";
              $nouDespatxDesc = "";
            } else {
                $nouDespatx = $item->hard_desp;
                $nouDespatxDesc = $item->descripcion;
            }
            if ($item->hard_piso !== $planta || $item->nombre_centro !== $centre || $nouDespatx !== $despatx ) {
                  $centre= $item->nombre_centro;
                  $planta = $item->hard_piso;
                  $despatx = $nouDespatx;
                  $despatxDesc = $nouDespatxDesc;
                  
                  $ubicacio = $centre.' Planta '.$planta;
                  if ($despatx !== "") {
                    $ubicacio = $ubicacio . " Despatx " .$despatx." ".$despatxDesc;
                  }
                  $section->addText("<w:br/>".$ubicacio."<w:br/>", "Title2");

                  $doc->addTableStyle('Taula'.$n, $styleTable, $styleFirstRow);
                  $table = $section->addTable('Taula'.$n);

                  $table->addRow($rowHeight);
                  $table->addCell(600, $styleCell)->addText(htmlspecialchars("Codi"), $fontStyle);
                  $table->addCell(800, $styleCell)->addText(htmlspecialchars("Tipo"), $fontStyle);
                  $table->addCell(700, $styleCell)->addText(htmlspecialchars("Marca"), $fontStyle);
                  $table->addCell(2500, $styleCell)->addText(htmlspecialchars("Model"), $fontStyle);
                  $table->addCell(1500, $styleCell)->addText(htmlspecialchars("Núm. sèrie"), $fontStyle);
                  $table->addCell(1500, $styleCell)->addText(htmlspecialchars("Departament"), $fontStyle);
                  $table->addCell(800, $styleCell)->addText(htmlspecialchars("Connexió"), $fontStyle);
                  $table->addCell(2500, $styleCell)->addText(htmlspecialchars("Usuari"), $fontStyle);
                  
                  $n++;

            }

            $table->addRow($rowHeight);
            $table->addCell(600, $styleCell)->addText(htmlspecialchars($item->hard_cod), $fontStyle);
            $table->addCell(800, $styleCell)->addText(htmlspecialchars($item->tipo_desc), $fontStyle);
            $table->addCell(700, $styleCell)->addText(htmlspecialchars($item->hard_marc), $fontStyle);
            $table->addCell(2500, $styleCell)->addText(htmlspecialchars($item->hard_mode), $fontStyle);
            $table->addCell(1500, $styleCell)->addText(htmlspecialchars($item->hard_nser), $fontStyle);
            $table->addCell(1500, $styleCell)->addText(htmlspecialchars($item->dpto_desc), $fontStyle);
            $table->addCell(800, $styleCell)->addText(htmlspecialchars($item->hard_conx), $fontStyle);
            $table->addCell(2500, $styleCell)->addText(htmlspecialchars($item->hard_usua), $fontStyle);

            $n++;
          }

          //Guardar document
          $writer = new Word2007($doc);
          $fileName = "Llistat_per_plantes.docx";
          header("Cache-Control: public");
          header("Content-Description: File Transfer");
          header("Content-Disposition: attachment; filename=" . $fileName);
          header("Content-Type: application/docx");
          header("Content-Transfer-Encoding: binary");
          $writer->save("php://output");
        }

        public function llistatDepartaments() {

          $data = session('data');

          //VIB-3mar2025-Hem de passar la colecció $data com un array "data" que conté la colecció d'objectes
          $pdf = Pdf::loadView('llistats.pdfDepartaments',['data' => $data])->setPaper('a4','landscape');

          //Canviar timeout del servidor
          ini_set('max_execution_time', 120); //120 seconds = 2 minutes

          return $pdf->download('llistat_per_departaments.pdf');
        }

        public function excelDepartaments($data) {

          $spreadsheet = new Spreadsheet();
          $sheet = $spreadsheet->getActiveSheet();

          $sheet->setTitle('SIGI - HW per departament'); 
          $sheet->setCellValue('A1', 'SIGI - Llistat de components per departament');

          $departament= $data[0]->dpto_desc;
          $departamentCodi = $data[0]->hard_dpto;

          $sheet->setCellValue('A3', "Departament: " . $departament);
          $sheet->setCellValue('A4', "Codi");
          $sheet->setCellValue('B4', "Tipo");
          $sheet->setCellValue('C4', "Marca");
          $sheet->setCellValue('D4', "Model");
          $sheet->setCellValue('E4', "Núm. sèrie");
          $sheet->setCellValue('F4', "Connexió");
          $sheet->setCellValue('G4', "Ubicació");
          $sheet->setCellValue('H4', "Usuari");

          $row = 5;// Initialize row counter

          foreach ($data as $item) {
            if ( $item->hard_dpto !== $departamentCodi ) {
              $departament= $item->dpto_desc;
              $departamentCodi = $item->hard_dpto;
              $row++;

              $sheet->setCellValue('A'.$row, "Departament: " . $departament);
              $row++;
              $sheet->setCellValue('A'.$row, "Codi");
              $sheet->setCellValue('B'.$row, "Tipo");
              $sheet->setCellValue('C'.$row, "Marca");
              $sheet->setCellValue('D'.$row, "Model");
              $sheet->setCellValue('E'.$row, "Núm. sèrie");
              $sheet->setCellValue('F'.$row, "Connexió");
              $sheet->setCellValue('G'.$row, "Ubicació");
              $sheet->setCellValue('H'.$row, "Usuari");
              $row++;
            } 

            $sheet->setCellValue('A'.$row, $item->hard_cod);
            $sheet->setCellValue('B'.$row, $item->tipo_desc);
            $sheet->setCellValue('C'.$row, $item->hard_marc);
            $sheet->setCellValue('D'.$row, $item->hard_mode);
            $sheet->setCellValue('E'.$row, $item->hard_nser);
            $sheet->setCellValue('F'.$row, $item->hard_conx);
            $ubicacio = $item->hard_ctra . " " . $item->hard_piso;
            if($item->hard_desp !== "") {
              $ubicacio = $ubicacio . " " . $item->hard_desp . " " . $item->descripcion;
            }
            $sheet->setCellValue('G'.$row, $ubicacio);
            $sheet->setCellValue('H'.$row, $item->hard_usua);
            $row++;
          }

          $writer = new Xlsx($spreadsheet);
          $fileName = "Llistat_per_departaments.xlsx";
          header("Cache-Control: public");
          header("Content-Description: File Transfer");
          header("Content-Disposition: attachment; filename=" . $fileName);
          header("Content-Type: application/xlsx");
          header("Content-Transfer-Encoding: binary");
          $writer->save("php://output");
          //return response()->download('php://output');

        }

        public function wordDepartaments($data) {

          $doc = new PhpWord();

          //VIB-10abr2025-Afegir estils al document
          $doc->addFontStyle(
            "Title1",
            array('name' => 'Arial', 'size' => 18, 'color' => '000000', 'bold' => true)
          );
          $doc->addFontStyle(
            "Title2",
            array('name' => 'Arial', 'size' => 16, 'color' => '333333', 'bold' => false)
          );
          $doc->addFontStyle(
            "Normal",
            array('name' => 'Arial', 'size' => 12, 'color' => '000000', 'bold' => false)
          );
          //Afegir una secció al document
          $paper = new \PhpOffice\PhpWord\Style\Paper();
          $section = $doc->addSection(array(
            'pageSizeW' => $paper->getWidth(), 
            'pageSizeH' => $paper->getHeight(), 
            'orientation' => 'landscape'
          ));
          $section->addText("SIGI - Llistat de components per departament", "Title1");

          $departament= $data[0]->dpto_desc;
          $departamentCodi = $data[0]->hard_dpto;

          $section->addText("<w:br/>Departament: ".$departament."<w:br/>", "Title2");

          //Afegir taula
          $styleTable = array(
            'borderColor' => 'FFFFFF',
            'borderSize'  => 6,
            'cellMargin'  => 80
          );
          $styleFirstRow = array('bgColor' => 'DDDDDD');
          $styleCell = array('valign' => 'center');
          $fontStyle = array('size' => 12, 'bold' => false);

          $doc->addTableStyle('Taula', $styleTable, $styleFirstRow);
          $table = $section->addTable('Taula');

          $rowHeight = 700;
          $table->addRow($rowHeight);
          $table->addCell(600, $styleCell)->addText(htmlspecialchars("Codi"), $fontStyle);
          $table->addCell(900, $styleCell)->addText(htmlspecialchars("Tipo"), $fontStyle);
          $table->addCell(800, $styleCell)->addText(htmlspecialchars("Marca"), $fontStyle);
          $table->addCell(2500, $styleCell)->addText(htmlspecialchars("Model"), $fontStyle);
          $table->addCell(1500, $styleCell)->addText(htmlspecialchars("Núm. sèrie"), $fontStyle);
          $table->addCell(1500, $styleCell)->addText(htmlspecialchars("Connexió"), $fontStyle);
          $table->addCell(800, $styleCell)->addText(htmlspecialchars("Ubicació"), $fontStyle);
          $table->addCell(2500, $styleCell)->addText(htmlspecialchars("Usuari"), $fontStyle);

          //$section->addTextBreak(1);

          $n = 0;
          foreach ($data as $item) {
            if ( $item->hard_dpto !== $departamentCodi ) {

                  $departament= $item->dpto_desc;
                  $departamentCodi = $item->hard_dpto;
            
                  $section->addText("<w:br/>Departament: ".$departament."<w:br/>", "Title2");

                  $doc->addTableStyle('Taula'.$n, $styleTable, $styleFirstRow);
                  $table = $section->addTable('Taula'.$n);

                  $table->addRow($rowHeight);
                  $table->addCell(600, $styleCell)->addText(htmlspecialchars("Codi"), $fontStyle);
                  $table->addCell(900, $styleCell)->addText(htmlspecialchars("Tipo"), $fontStyle);
                  $table->addCell(800, $styleCell)->addText(htmlspecialchars("Marca"), $fontStyle);
                  $table->addCell(2500, $styleCell)->addText(htmlspecialchars("Model"), $fontStyle);
                  $table->addCell(1500, $styleCell)->addText(htmlspecialchars("Núm. sèrie"), $fontStyle);
                  $table->addCell(1500, $styleCell)->addText(htmlspecialchars("Connexió"), $fontStyle);
                  $table->addCell(800, $styleCell)->addText(htmlspecialchars("Ubicació"), $fontStyle);
                  $table->addCell(2500, $styleCell)->addText(htmlspecialchars("Usuari"), $fontStyle);
                  
                  $n++;

            }

            $table->addRow($rowHeight);
            $table->addCell(600, $styleCell)->addText(htmlspecialchars($item->hard_cod), $fontStyle);
            $table->addCell(900, $styleCell)->addText(htmlspecialchars($item->tipo_desc), $fontStyle);
            $table->addCell(800, $styleCell)->addText(htmlspecialchars($item->hard_marc), $fontStyle);
            $table->addCell(2500, $styleCell)->addText(htmlspecialchars($item->hard_mode), $fontStyle);
            $table->addCell(1500, $styleCell)->addText(htmlspecialchars($item->hard_nser), $fontStyle);
            $table->addCell(1500, $styleCell)->addText(htmlspecialchars($item->hard_conx), $fontStyle);
            $ubicacio = $item->hard_ctra . " " . $item->hard_piso;
            if($item->hard_desp !== "") {
              $ubicacio = $ubicacio . " " . $item->hard_desp . " " . $item->descripcion;
            }
            $table->addCell(800, $styleCell)->addText(htmlspecialchars($ubicacio), $fontStyle);
            $table->addCell(2500, $styleCell)->addText(htmlspecialchars($item->hard_usua), $fontStyle);

            $n++;
          }

          //Guardar document
          $writer = new Word2007($doc);
          $fileName = "Llistat_per_departaments.docx";
          header("Cache-Control: public");
          header("Content-Description: File Transfer");
          header("Content-Disposition: attachment; filename=" . $fileName);
          header("Content-Type: application/docx");
          header("Content-Transfer-Encoding: binary");
          $writer->save("php://output");
        }

        public function llistatGarantia() {

          $data = session('data');
          $dataRef = date_format(date_create(session('dataRef')), "d-m-Y");

          //VIB-3mar2025-Hem de passar la colecció $data com un array "data" que conté la colecció d'objectes
          $pdf = Pdf::loadView('llistats.pdfGarantia', ['data' => $data, 'dataRef' => $dataRef])->setPaper('a4','landscape');

          //Canviar timeout del servidor
          ini_set('max_execution_time', 120); //120 seconds = 2 minutes

          return $pdf->download('llistat_en_garantia.pdf');
        }

        public function excelGarantia($data) {

          $data = session('data');
          $dataRef = date_format(date_create(session('dataRef')), "d-m-Y");

          $spreadsheet = new Spreadsheet();
          $sheet = $spreadsheet->getActiveSheet();

          $sheet->setTitle('SIGI - HW en garantia'); 
          $sheet->setCellValue('A1', 'SIGI - Llistat de components en garantia el dia '.$dataRef);

          $centre= $data->first()->nombre_centro;
          $tipus = $data->first()->tipo_desc;

          $sheet->setCellValue('A3', $centre);
          $sheet->setCellValue('A4', $tipus);

          $sheet->setCellValue('A5', "Codi");
          $sheet->setCellValue('B5', "Marca");
          $sheet->setCellValue('C5', "Model");
          $sheet->setCellValue('D5', "Núm. sèrie");
          $sheet->setCellValue('E5', "Departament");
          $sheet->setCellValue('F5', "Data compra");
          $sheet->setCellValue('G5', "Garantia (anys)");

          $row = 6;// Initialize row counter

          foreach ($data as $item) {
            if ($item->nombre_centro !== $centre) {
              $centre= $item->nombre_centro;
              $tipus = $item->tipo_desc;
              $row++;

              $sheet->setCellValue('A'.$row, $centre);
              $row++;
              $sheet->setCellValue('A'.$row, $tipus);
              $row++;

              $sheet->setCellValue('A'.$row, "Codi");
              $sheet->setCellValue('B'.$row, "Marca");
              $sheet->setCellValue('C'.$row, "Model");
              $sheet->setCellValue('D'.$row, "Núm. sèrie");
              $sheet->setCellValue('E'.$row, "Departament");
              $sheet->setCellValue('F'.$row, "Data compra");
              $sheet->setCellValue('G'.$row, "Garantia (anys)");
              $row++;
            } 

            if ($item->tipo_desc !== $tipus) {
              $tipus = $item->tipo_desc;

              $sheet->setCellValue('A'.$row, $tipus);
              $row++;

              $sheet->setCellValue('A'.$row, "Codi");
              $sheet->setCellValue('B'.$row, "Marca");
              $sheet->setCellValue('C'.$row, "Model");
              $sheet->setCellValue('D'.$row, "Núm. sèrie");
              $sheet->setCellValue('E'.$row, "Departament");
              $sheet->setCellValue('F'.$row, "Data compra");
              $sheet->setCellValue('G'.$row, "Garantia (anys)");
              $row++;
            } 

            $sheet->setCellValue('A'.$row, $item->hard_cod);
            $sheet->setCellValue('B'.$row, $item->hard_marc);
            $sheet->setCellValue('C'.$row, $item->hard_mode);
            $sheet->setCellValue('D'.$row, $item->hard_nser);
            $sheet->setCellValue('E'.$row, $item->dpto_desc);
            $sheet->setCellValue('F'.$row, $item->hard_ffac);
            $sheet->setCellValue('G'.$row, $item->hard_gara);
            $row++;
          }

          $writer = new Xlsx($spreadsheet);
          $fileName = "Llistat_HW_en_garantia.xlsx";
          header("Cache-Control: public");
          header("Content-Description: File Transfer");
          header("Content-Disposition: attachment; filename=" . $fileName);
          header("Content-Type: application/xlsx");
          header("Content-Transfer-Encoding: binary");
          $writer->save("php://output");

        }

        public function wordGarantia($data) {

          $data = session('data');
          $dataRef = date_format(date_create(session('dataRef')), "d-m-Y");

          $doc = new PhpWord();

          //VIB-10abr2025-Afegir estils al document
          $doc->addFontStyle(
            "Title1",
            array('name' => 'Arial', 'size' => 18, 'color' => '000000', 'bold' => true)
          );
          $doc->addFontStyle(
            "Title2",
            array('name' => 'Arial', 'size' => 16, 'color' => '333333', 'bold' => true)
          );
          $doc->addFontStyle(
            "Title3",
            array('name' => 'Arial', 'size' => 14, 'color' => '333333', 'bold' => false)
          );
          $doc->addFontStyle(
            "Normal",
            array('name' => 'Arial', 'size' => 12, 'color' => '000000', 'bold' => false)
          );
          //Afegir una secció al document
          $paper = new \PhpOffice\PhpWord\Style\Paper();
          $section = $doc->addSection(array(
            'pageSizeW' => $paper->getWidth(), 
            'pageSizeH' => $paper->getHeight(), 
            'orientation' => 'landscape'
          ));
          $section->addText("SIGI - Llistat de components en garantia el dia ".$dataRef, "Title1");

          $centre= $data->first()->nombre_centro;
          $tipus = $data->first()->tipo_desc;

          $section->addText("<w:br/>".$centre, "Title2");
          $section->addText("<w:br/>".$tipus."<w:br/>", "Title3");

          //Afegir taula
          $styleTable = array(
            'borderColor' => 'FFFFFF',
            'borderSize'  => 6,
            'cellMargin'  => 80
          );
          $styleFirstRow = array('bgColor' => 'DDDDDD');
          $styleCell = array('valign' => 'center');
          $fontStyle = array('size' => 12, 'bold' => false);

          $doc->addTableStyle('Taula', $styleTable, $styleFirstRow);
          $table = $section->addTable('Taula');

          $rowHeight = 700;
          $table->addRow($rowHeight);
          $table->addCell(600, $styleCell)->addText(htmlspecialchars("Codi"), $fontStyle);
          $table->addCell(700, $styleCell)->addText(htmlspecialchars("Marca"), $fontStyle);
          $table->addCell(4000, $styleCell)->addText(htmlspecialchars("Model"), $fontStyle);
          $table->addCell(1500, $styleCell)->addText(htmlspecialchars("Núm. sèrie"), $fontStyle);
          $table->addCell(2000, $styleCell)->addText(htmlspecialchars("Departament"), $fontStyle);
          $table->addCell(1500, $styleCell)->addText(htmlspecialchars("Data compra"), $fontStyle);
          $table->addCell(600, $styleCell)->addText(htmlspecialchars("Garantia"), $fontStyle);

          //$section->addTextBreak(1);

          $n = 0;
          foreach ($data as $item) {
            if ( $item->nombre_centro !== $centre ) {

                $centre= $item->nombre_centro;
                $tipus = $item->tipo_desc;
            
                $section->addText("<w:br/>".$centre, "Title2");
                $section->addText("<w:br/>".$tipus."<w:br/>", "Title3");

                  $doc->addTableStyle('Taula'.$n, $styleTable, $styleFirstRow);
                  $table = $section->addTable('Taula'.$n);

                  $table->addRow($rowHeight);
                  $table->addCell(600, $styleCell)->addText(htmlspecialchars("Codi"), $fontStyle);
                  $table->addCell(700, $styleCell)->addText(htmlspecialchars("Marca"), $fontStyle);
                  $table->addCell(4000, $styleCell)->addText(htmlspecialchars("Model"), $fontStyle);
                  $table->addCell(1500, $styleCell)->addText(htmlspecialchars("Núm. sèrie"), $fontStyle);
                  $table->addCell(2000, $styleCell)->addText(htmlspecialchars("Departament"), $fontStyle);
                  $table->addCell(1500, $styleCell)->addText(htmlspecialchars("Data compra"), $fontStyle);
                  $table->addCell(600, $styleCell)->addText(htmlspecialchars("Garantia"), $fontStyle);
                  
                  $n++;

            } elseif ($item->tipo_desc !== $tipus) {

              $tipus = $item->tipo_desc;
              $section->addText("<w:br/>".$tipus."<w:br/>", "Title3");

              $doc->addTableStyle('Taula'.$n, $styleTable, $styleFirstRow);
              $table = $section->addTable('Taula'.$n);

              $table->addRow($rowHeight);
              $table->addCell(600, $styleCell)->addText(htmlspecialchars("Codi"), $fontStyle);
              $table->addCell(700, $styleCell)->addText(htmlspecialchars("Marca"), $fontStyle);
              $table->addCell(4000, $styleCell)->addText(htmlspecialchars("Model"), $fontStyle);
              $table->addCell(1500, $styleCell)->addText(htmlspecialchars("Núm. sèrie"), $fontStyle);
              $table->addCell(2000, $styleCell)->addText(htmlspecialchars("Departament"), $fontStyle);
              $table->addCell(1500, $styleCell)->addText(htmlspecialchars("Data compra"), $fontStyle);
              $table->addCell(600, $styleCell)->addText(htmlspecialchars("Garantia"), $fontStyle);
              
              $n++;

            }

            $table->addRow($rowHeight);
            $table->addCell(600, $styleCell)->addText(htmlspecialchars($item->hard_cod), $fontStyle);
            $table->addCell(700, $styleCell)->addText(htmlspecialchars($item->hard_marc), $fontStyle);
            $table->addCell(4000, $styleCell)->addText(htmlspecialchars($item->hard_mode), $fontStyle);
            $table->addCell(1500, $styleCell)->addText(htmlspecialchars($item->hard_nser), $fontStyle);
            $table->addCell(2000, $styleCell)->addText(htmlspecialchars($item->dpto_desc), $fontStyle);
            $table->addCell(1500, $styleCell)->addText(htmlspecialchars($item->hard_ffac), $fontStyle);
            $table->addCell(600, $styleCell)->addText(htmlspecialchars($item->hard_gara), $fontStyle);

            $n++;
          }

          //Guardar document
          $writer = new Word2007($doc);
          $fileName = "Llistat_HW_en_garantia.docx";
          header("Cache-Control: public");
          header("Content-Description: File Transfer");
          header("Content-Disposition: attachment; filename=" . $fileName);
          header("Content-Type: application/docx");
          header("Content-Transfer-Encoding: binary");
          $writer->save("php://output");
        }

        public function llistatRecompte() {

          $data = session('data');
          $centre = $data[0]->nombre_centro;
          $n = 0;
          $totals = [];
          $component = '';
          foreach ($data as $item) {
            if ($component !== $item->tipo_desc) {
              $component = $item->tipo_desc;
              $reg = new RecompteHw();
              $reg->desc = $item->tipo_desc;
              $totals[$n] = $reg;
              $n++;
            }
            if ($item->hard_acti == 'N') {
              $reg->noActius++;
            } else {
              $reg->actius++;
            }
            $reg->total++;
          }

          //dd($totals);

          //VIB-3mar2025-Hem de passar la colecció $data com un array "data" que conté la colecció d'objectes
          $pdf = Pdf::loadView('llistats.pdfRecompte',['data' => $totals, 'centre' => $centre])->setPaper('a4','landscape');
       
          // use this method to stream it download
          //return $pdf->download();

          // use this method to stream it online 
          //return $pdf->stream(); 

          //Canviar timeout del servidor
          ini_set('max_execution_time', 120); //120 seconds = 2 minutes

          //return $pdf->stream('llistat_per_plantes.pdf');
          return $pdf->download('recompte_components.pdf');
        }

        public function excelRecompte($data) {

          $spreadsheet = new Spreadsheet();
          $sheet = $spreadsheet->getActiveSheet();

          $sheet->setTitle('SIGI - Recompte Hw'); 
          $sheet->setCellValue('A1', 'SIGI - Recompte de components per centre');

          $centre = $data[0]->nombre_centro;
          $n = 0;
          $totals = [];
          $component = '';
       
          $sheet->setCellValue('A2', $centre);

          $sheet->setCellValue('A4', "Component");
          $sheet->setCellValue('B4', "Actius");
          $sheet->setCellValue('C4', "No actius");
          $sheet->setCellValue('D4', "Total");

          $row = 5;// Initialize row counter

          foreach ($data as $item) {

            if ($component !== $item->tipo_desc) {
              $component = $item->tipo_desc;
              $reg = new RecompteHw();
              $reg->desc = $item->tipo_desc;
              $totals[$n] = $reg;
              $n++;
            }
            if ($item->hard_acti == 'N') {
              $reg->noActius++;
            } else {
              $reg->actius++;
            }
            $reg->total++;

          }

          $totalActius = 0;
          $totalNoActius = 0;
          $totalTots = 0;

          foreach ($totals as $item) {

            $sheet->setCellValue('A'.$row, $item->desc);
            $sheet->setCellValue('B'.$row, $item->actius);
            $sheet->setCellValue('C'.$row, $item->noActius);
            $sheet->setCellValue('D'.$row, $item->total);

            $row++;

            $totalActius = $totalActius + $item->actius;
            $totalNoActius = $totalNoActius + $item->noActius;
            $totalTots = $totalTots + $item->total;

          }

          $row++;

          $sheet->setCellValue('A'.$row, "TOTALS");
          $sheet->setCellValue('B'.$row, $totalActius);
          $sheet->setCellValue('C'.$row, $totalNoActius);
          $sheet->setCellValue('D'.$row, $totalTots);

          $writer = new Xlsx($spreadsheet);
          $fileName = "Recompte components Hw.xlsx";

          header("Cache-Control: public");
          header("Content-Description: File Transfer");
          header("Content-Disposition: attachment; filename=" . $fileName);
          header("Content-Type: application/xlsx");
          header("Content-Transfer-Encoding: binary");

          $writer->save("php://output");

        }

        public function wordRecompte($data) {

          $doc = new PhpWord();

          //VIB-10abr2025-Afegir estils al document
          $doc->addFontStyle(
            "Title1",
            array('name' => 'Arial', 'size' => 18, 'color' => '000000', 'bold' => true)
          );
          $doc->addFontStyle(
            "Title2",
            array('name' => 'Arial', 'size' => 16, 'color' => '333333', 'bold' => false)
          );
          $doc->addFontStyle(
            "Normal",
            array('name' => 'Arial', 'size' => 12, 'color' => '000000', 'bold' => false)
          );
          //Afegir una secció al document
          $paper = new \PhpOffice\PhpWord\Style\Paper();
          $section = $doc->addSection(array(
            'pageSizeW' => $paper->getWidth(), 
            'pageSizeH' => $paper->getHeight(), 
            'orientation' => 'landscape'
          ));
          $section->addText("SIGI - Recompte de components per centre", "Title1");

          $centre = $data[0]->nombre_centro;
          $n = 0;
          $totals = [];
          $component = '';

          $section->addText("<w:br/>".$centre."<w:br/>", "Title2");

          //Afegir taula
          $styleTable = array(
            'borderColor' => 'FFFFFF',
            'borderSize'  => 6,
            'cellMargin'  => 80
          );
          $styleFirstRow = array('bgColor' => 'DDDDDD');
          $styleCell = array('valign' => 'center');
          $fontStyle = array('size' => 12, 'bold' => false);

          $doc->addTableStyle('Taula', $styleTable, $styleFirstRow);
          $table = $section->addTable('Taula');

          $rowHeight = 700;
          $table->addRow($rowHeight);
          $table->addCell(4000, $styleCell)->addText(htmlspecialchars("Component"), $fontStyle);
          $table->addCell(700, $styleCell)->addText(htmlspecialchars("Actius"), $fontStyle);
          $table->addCell(1200, $styleCell)->addText(htmlspecialchars("No actius"), $fontStyle);
          $table->addCell(700, $styleCell)->addText(htmlspecialchars("Total"), $fontStyle);

          //$section->addTextBreak(1);

          foreach ($data as $item) {

            if ($component !== $item->tipo_desc) {
              $component = $item->tipo_desc;
              $reg = new RecompteHw();
              $reg->desc = $item->tipo_desc;
              $totals[$n] = $reg;
              $n++;
            }
            if ($item->hard_acti == 'N') {
              $reg->noActius++;
            } else {
              $reg->actius++;
            }
            $reg->total++;

          }

          $totalActius = 0;
          $totalNoActius = 0;
          $totalTots = 0;
          $totalTipus = 0;

          foreach ($totals as $item) {

            $table->addRow($rowHeight);
            $table->addCell(4000, $styleCell)->addText(htmlspecialchars($item->desc), $fontStyle);
            $table->addCell(700, $styleCell)->addText(htmlspecialchars($item->actius), $fontStyle);
            $table->addCell(1200, $styleCell)->addText(htmlspecialchars($item->noActius), $fontStyle);
            $table->addCell(700, $styleCell)->addText(htmlspecialchars($item->total), $fontStyle);

            $totalActius = $totalActius + $item->actius;
            $totalNoActius = $totalNoActius + $item->noActius;
            $totalTots = $totalTots + $item->total;
            $totalTipus++;
          }

          $section->addText("<w:br/>Total tipus de components: ".$totalTipus, "Normal");
          $section->addText("<w:br/>Actius: ".$totalActius, "Normal");
          $section->addText("<w:br/>No actius: ".$totalNoActius, "Normal");
          $section->addText("<w:br/>Total components: ".$totalTots, "Normal");

          //Guardar document
          $writer = new Word2007($doc);
          $fileName = "Recompte_components_per_centre.docx";
          header("Cache-Control: public");
          header("Content-Description: File Transfer");
          header("Content-Disposition: attachment; filename=" . $fileName);
          header("Content-Type: application/docx");
          header("Content-Transfer-Encoding: binary");
          $writer->save("php://output");
        }

        public function llistatPrestats() {

          $data = session('data');

          //VIB-3mar2025-Hem de passar la colecció $data com un array "data" que conté la colecció d'objectes
          $pdf = Pdf::loadView('llistats.pdfPrestats',['data' => $data])->setPaper('a4','landscape');

          //Canviar timeout del servidor
          ini_set('max_execution_time', 120); //120 seconds = 2 minutes

          return $pdf->download('llistat_hw_prestat.pdf');
        }

        public function excelPrestats($data) {

          $spreadsheet = new Spreadsheet();
          $sheet = $spreadsheet->getActiveSheet();

          $sheet->setTitle('SIGI - HW per departament'); 
          $sheet->setCellValue('A1', 'SIGI - Llistat de components prestats per departament');

          $departament= $data[0]->dpto_desc;
          $departamentCodi = $data[0]->hard_dpto;

          $sheet->setCellValue('A3', "Departament: " . $departament);
          $sheet->setCellValue('A4', "Codi");
          $sheet->setCellValue('B4', "Tipo");
          $sheet->setCellValue('C4', "Marca");
          $sheet->setCellValue('D4', "Model");
          $sheet->setCellValue('E4', "Núm. sèrie");
          $sheet->setCellValue('F4', "Connexió");
          $sheet->setCellValue('G4', "Ubicació");
          $sheet->setCellValue('H4', "Usuari");
          $sheet->setCellValue('I4', "Observacions");

          $row = 5;// Initialize row counter

          foreach ($data as $item) {
            if ( $item->hard_dpto !== $departamentCodi ) {
              $departament= $item->dpto_desc;
              $departamentCodi = $item->hard_dpto;
              $row++;

              $sheet->setCellValue('A'.$row, "Departament: " . $departament);
              $row++;
              $sheet->setCellValue('A'.$row, "Codi");
              $sheet->setCellValue('B'.$row, "Tipo");
              $sheet->setCellValue('C'.$row, "Marca");
              $sheet->setCellValue('D'.$row, "Model");
              $sheet->setCellValue('E'.$row, "Núm. sèrie");
              $sheet->setCellValue('F'.$row, "Connexió");
              $sheet->setCellValue('G'.$row, "Ubicació");
              $sheet->setCellValue('H'.$row, "Usuari");
              $sheet->setCellValue('I4', "Observacions");

              $row++;
            } 

            $sheet->setCellValue('A'.$row, $item->hard_cod);
            $sheet->setCellValue('B'.$row, $item->tipo_desc);
            $sheet->setCellValue('C'.$row, $item->hard_marc);
            $sheet->setCellValue('D'.$row, $item->hard_mode);
            $sheet->setCellValue('E'.$row, $item->hard_nser);
            $sheet->setCellValue('F'.$row, $item->hard_conx);
            $ubicacio = $item->hard_ctra . " " . $item->hard_piso;
            if($item->hard_desp !== "") {
              $ubicacio = $ubicacio . " " . $item->hard_desp . " " . $item->descripcion;
            }
            $sheet->setCellValue('G'.$row, $ubicacio);
            $sheet->setCellValue('H'.$row, $item->hard_usua);
            $sheet->setCellValue('I'.$row, $item->hard_obse);

            $row++;
          }

          $writer = new Xlsx($spreadsheet);
          $fileName = "Llistat_prestats.xlsx";
          header("Cache-Control: public");
          header("Content-Description: File Transfer");
          header("Content-Disposition: attachment; filename=" . $fileName);
          header("Content-Type: application/xlsx");
          header("Content-Transfer-Encoding: binary");
          $writer->save("php://output");
          //return response()->download('php://output');

        }

        public function wordPrestats($data) {

          $doc = new PhpWord();

          //VIB-10abr2025-Afegir estils al document
          $doc->addFontStyle(
            "Title1",
            array('name' => 'Arial', 'size' => 18, 'color' => '000000', 'bold' => true)
          );
          $doc->addFontStyle(
            "Title2",
            array('name' => 'Arial', 'size' => 16, 'color' => '333333', 'bold' => false)
          );
          $doc->addFontStyle(
            "Normal",
            array('name' => 'Arial', 'size' => 12, 'color' => '000000', 'bold' => false)
          );
          //Afegir una secció al document
          $paper = new \PhpOffice\PhpWord\Style\Paper();
          $section = $doc->addSection(array(
            'pageSizeW' => $paper->getWidth(), 
            'pageSizeH' => $paper->getHeight(), 
            'orientation' => 'landscape'
          ));
          $section->addText("SIGI - Llistat de components prestats per departament", "Title1");

          $departament= $data[0]->dpto_desc;
          $departamentCodi = $data[0]->hard_dpto;

          $section->addText("<w:br/>Departament: ".$departament."<w:br/>", "Title2");

          //Afegir taula
          $styleTable = array(
            'borderColor' => 'FFFFFF',
            'borderSize'  => 6,
            'cellMargin'  => 80
          );
          $styleFirstRow = array('bgColor' => 'DDDDDD');
          $styleCell = array('valign' => 'center');
          $fontStyle = array('size' => 10, 'bold' => false);

          $doc->addTableStyle('Taula', $styleTable, $styleFirstRow);
          $table = $section->addTable('Taula');

          $rowHeight = 700;
          $table->addRow($rowHeight);
          $table->addCell(600, $styleCell)->addText(htmlspecialchars("Codi"), $fontStyle);
          $table->addCell(1000, $styleCell)->addText(htmlspecialchars("Tipo"), $fontStyle);
          $table->addCell(800, $styleCell)->addText(htmlspecialchars("Marca"), $fontStyle);
          $table->addCell(2500, $styleCell)->addText(htmlspecialchars("Model"), $fontStyle);
          $table->addCell(1500, $styleCell)->addText(htmlspecialchars("Núm. sèrie"), $fontStyle);
          $table->addCell(1500, $styleCell)->addText(htmlspecialchars("Connexió"), $fontStyle);
          $table->addCell(1000, $styleCell)->addText(htmlspecialchars("Ubicació"), $fontStyle);
          $table->addCell(2000, $styleCell)->addText(htmlspecialchars("Usuari"), $fontStyle);
          $table->addCell(2500, $styleCell)->addText(htmlspecialchars("Observacions"), $fontStyle);

          //$section->addTextBreak(1);

          $n = 0;
          foreach ($data as $item) {
            if ( $item->hard_dpto !== $departamentCodi ) {

                  $departament= $item->dpto_desc;
                  $departamentCodi = $item->hard_dpto;
            
                  $section->addText("<w:br/>Departament: ".$departament."<w:br/>", "Title2");

                  $doc->addTableStyle('Taula'.$n, $styleTable, $styleFirstRow);
                  $table = $section->addTable('Taula'.$n);

                  $table->addRow($rowHeight);
                  $table->addCell(600, $styleCell)->addText(htmlspecialchars("Codi"), $fontStyle);
                  $table->addCell(1000, $styleCell)->addText(htmlspecialchars("Tipo"), $fontStyle);
                  $table->addCell(800, $styleCell)->addText(htmlspecialchars("Marca"), $fontStyle);
                  $table->addCell(2500, $styleCell)->addText(htmlspecialchars("Model"), $fontStyle);
                  $table->addCell(1500, $styleCell)->addText(htmlspecialchars("Núm. sèrie"), $fontStyle);
                  $table->addCell(1500, $styleCell)->addText(htmlspecialchars("Connexió"), $fontStyle);
                  $table->addCell(1000, $styleCell)->addText(htmlspecialchars("Ubicació"), $fontStyle);
                  $table->addCell(2000, $styleCell)->addText(htmlspecialchars("Usuari"), $fontStyle);
                  $table->addCell(2500, $styleCell)->addText(htmlspecialchars("Observacions"), $fontStyle);

                  $n++;

            }

            $table->addRow($rowHeight);
            $table->addCell(600, $styleCell)->addText(htmlspecialchars($item->hard_cod), $fontStyle);
            $table->addCell(1000, $styleCell)->addText(htmlspecialchars($item->tipo_desc), $fontStyle);
            $table->addCell(800, $styleCell)->addText(htmlspecialchars($item->hard_marc), $fontStyle);
            $table->addCell(2500, $styleCell)->addText(htmlspecialchars($item->hard_mode), $fontStyle);
            $table->addCell(1500, $styleCell)->addText(htmlspecialchars($item->hard_nser), $fontStyle);
            $table->addCell(1500, $styleCell)->addText(htmlspecialchars($item->hard_conx), $fontStyle);
            $ubicacio = $item->hard_ctra . " " . $item->hard_piso;
            if($item->hard_desp !== "") {
              $ubicacio = $ubicacio . " " . $item->hard_desp . " " . $item->descripcion;
            }
            $table->addCell(1000, $styleCell)->addText(htmlspecialchars($ubicacio), $fontStyle);
            $table->addCell(2000, $styleCell)->addText(htmlspecialchars($item->hard_usua), $fontStyle);
            $table->addCell(2500, $styleCell)->addText(htmlspecialchars($item->hard_obse), $fontStyle);

            $n++;
          }

          //Guardar document
          $writer = new Word2007($doc);
          $fileName = "Llistat_hw_prestat.docx";
          header("Cache-Control: public");
          header("Content-Description: File Transfer");
          header("Content-Disposition: attachment; filename=" . $fileName);
          header("Content-Type: application/docx");
          header("Content-Transfer-Encoding: binary");
          $writer->save("php://output");
        }


        private function filtrarPerLoccalitzacio(Request $request) {

          $query = Element::select('dbo.cathard.*','dbo.centros.nombre_centro', 'dbo.catdpto.dpto_desc', 'dbo.catprov.prov_desc', 'dbo.cattipo.tipo_desc')
          ->leftjoin('dbo.centros', 'dbo.cathard.hard_ctra', '=', 'dbo.centros.cod_centro')
          ->leftjoin('dbo.catdpto', 'dbo.cathard.hard_dpto', '=', 'dbo.catdpto.dpto_cod')
          ->leftjoin('dbo.catprov', 'dbo.cathard.hard_prov', '=', 'dbo.catprov.prov_cod')
          ->leftjoin('dbo.cattipo', 'dbo.cathard.hard_tipo', '=', 'dbo.cattipo.tipo_cod');

          if ($request->centre != '-1') {
            $query->where('dbo.centros.cod_centro', '=' ,$request->centre);
          }

          if (($request->planta != -1) and ($request->centre != '')) {
              $query->where('dbo.cathard.hard_piso', '=', $request->planta);
          }

          if (($request->despatx != -1) and ($request->planta != -1) and ($request->centre != '')) {
              $query->where('dbo.cathard.hard_desp', '=', $request->despatx);
          }

          if ($request->actius == 'S') {
            $query->where('dbo.cathard.hard_acti', '=', 'S');
          } else if($request->actius == 'N') {
            $query->where('dbo.cathard.hard_acti', '=', 'N');  
          }

          if($request->tipus[0] != null) {
            //VIB-4mar2025-Agrupar diferents orWhere baix un AND. Equival a (primera query) AND (segona query dins la funció amb ORs)
            $tipusQuery = "(";
            for($n=0; $n<count($request->tipus); $n++) {
                if($n==0) {
                  $tipusQuery = $tipusQuery . "hard_tipo = " .$request->tipus[0];
                } else {
                  $tipusQuery = $tipusQuery . " OR hard_tipo = " .$request->tipus[$n];
                }
            }
            $tipusQuery = $tipusQuery . ")";
            //dd(DB::raw($tipusQuery));
            $query->whereRaw($tipusQuery);
          }

          return $query->orderBy('hard_ctra', 'asc')->orderBy('hard_piso','asc')->orderBy('hard_desp','asc')->orderBy('hard_cod','asc')->get(); 

        }

        private function filtrarPerDepartament(Request $request) {

          $query = Element::select('dbo.cathard.*','dbo.centros.nombre_centro', 'dbo.catdpto.dpto_desc', 'dbo.catprov.prov_desc', 'dbo.cattipo.tipo_desc')
          ->leftjoin('dbo.centros', 'dbo.cathard.hard_ctra', '=', 'dbo.centros.cod_centro')
          ->leftjoin('dbo.catdpto', 'dbo.cathard.hard_dpto', '=', 'dbo.catdpto.dpto_cod')
          ->leftjoin('dbo.catprov', 'dbo.cathard.hard_prov', '=', 'dbo.catprov.prov_cod')
          ->leftjoin('dbo.cattipo', 'dbo.cathard.hard_tipo', '=', 'dbo.cattipo.tipo_cod');

          if ($request->actius == 'S') {
            $query->where('dbo.cathard.hard_acti', '=', 'S');
          } else if($request->actius == 'N') {
            $query->where('dbo.cathard.hard_acti', '=', 'N');  
          }

          if($request->departaments[0] != null) {
            $tipusQuery = "(";
            for($n=0; $n<count($request->departaments); $n++) {
                if($n==0) {
                  $tipusQuery = $tipusQuery . "hard_dpto = " .$request->departaments[0];
                } else {
                  $tipusQuery = $tipusQuery . " OR hard_dpto = " .$request->departaments[$n];
                }
            }
            $tipusQuery = $tipusQuery . ")";
            //dd(DB::raw($tipusQuery));
            $query->whereRaw($tipusQuery);
          }

          if($request->tipus[0] != null) {
            $tipusQuery = "(";
            for($n=0; $n<count($request->tipus); $n++) {
                if($n==0) {
                  $tipusQuery = $tipusQuery . "hard_tipo = " .$request->tipus[0];
                } else {
                  $tipusQuery = $tipusQuery . " OR hard_tipo = " .$request->tipus[$n];
                }
            }
            $tipusQuery = $tipusQuery . ")";
            //dd(DB::raw($tipusQuery));
            $query->whereRaw($tipusQuery);
          }

          return $query->orderBy('hard_dpto', 'asc')->orderBy('hard_tipo','asc')->orderBy('hard_cod','asc')->get(); 

        }

        private function filtrarPerGarantia(Request $request) {

          $query = Element::select('dbo.cathard.*','dbo.centros.nombre_centro', 'dbo.catdpto.dpto_desc', 'dbo.catprov.prov_desc', 'dbo.cattipo.tipo_desc')
          ->leftjoin('dbo.centros', 'dbo.cathard.hard_ctra', '=', 'dbo.centros.cod_centro')
          ->leftjoin('dbo.catdpto', 'dbo.cathard.hard_dpto', '=', 'dbo.catdpto.dpto_cod')
          ->leftjoin('dbo.catprov', 'dbo.cathard.hard_prov', '=', 'dbo.catprov.prov_cod')
          ->leftjoin('dbo.cattipo', 'dbo.cathard.hard_tipo', '=', 'dbo.cattipo.tipo_cod');

          if ($request->centre != '-1') {
            $query->where('dbo.centros.cod_centro', '=' ,$request->centre);
          }

          if($request->tipus[0] != null) {
            $tipusQuery = "(";
            for($n=0; $n<count($request->tipus); $n++) {
                if($n==0) {
                  $tipusQuery = $tipusQuery . "hard_tipo = " .$request->tipus[0];
                } else {
                  $tipusQuery = $tipusQuery . " OR hard_tipo = " .$request->tipus[$n];
                }
            }
            $tipusQuery = $tipusQuery . ")";
            //dd(DB::raw($tipusQuery));
            $query->whereRaw($tipusQuery);
          }

          //Registres amb una data de compra
          $query->whereNotNull("hard_ffac");

          return $query->get(); 

        }

        private function componentsPerCentre(Request $request) {
          
          $query = Element::select('dbo.cathard.*','dbo.centros.nombre_centro', 'dbo.catdpto.dpto_desc', 'dbo.catprov.prov_desc', 'dbo.cattipo.tipo_desc')
          ->leftjoin('dbo.centros', 'dbo.cathard.hard_ctra', '=', 'dbo.centros.cod_centro')
          ->leftjoin('dbo.catdpto', 'dbo.cathard.hard_dpto', '=', 'dbo.catdpto.dpto_cod')
          ->leftjoin('dbo.catprov', 'dbo.cathard.hard_prov', '=', 'dbo.catprov.prov_cod')
          ->leftjoin('dbo.cattipo', 'dbo.cathard.hard_tipo', '=', 'dbo.cattipo.tipo_cod');

          $query->where('dbo.centros.cod_centro', '=' ,$request->centre)->orderBy('dbo.cattipo.tipo_desc');

          return $query->get(); 

        }

        private function filtrarPrestats(Request $request) {

          $query = Element::select('dbo.cathard.*','dbo.centros.nombre_centro', 'dbo.catdpto.dpto_desc', 'dbo.catprov.prov_desc', 'dbo.cattipo.tipo_desc')
          ->leftjoin('dbo.centros', 'dbo.cathard.hard_ctra', '=', 'dbo.centros.cod_centro')
          ->leftjoin('dbo.catdpto', 'dbo.cathard.hard_dpto', '=', 'dbo.catdpto.dpto_cod')
          ->leftjoin('dbo.catprov', 'dbo.cathard.hard_prov', '=', 'dbo.catprov.prov_cod')
          ->leftjoin('dbo.cattipo', 'dbo.cathard.hard_tipo', '=', 'dbo.cattipo.tipo_cod');

          $query->where('dbo.cathard.prestat', '=' ,'S');

          if($request->departaments[0] != null) {
            $tipusQuery = "(";
            for($n=0; $n<count($request->departaments); $n++) {
                if($n==0) {
                  $tipusQuery = $tipusQuery . "hard_dpto = " .$request->departaments[0];
                } else {
                  $tipusQuery = $tipusQuery . " OR hard_dpto = " .$request->departaments[$n];
                }
            }
            $tipusQuery = $tipusQuery . ")";
            //dd(DB::raw($tipusQuery));
            $query->whereRaw($tipusQuery);
          }

          if($request->tipus[0] != null) {
            $tipusQuery = "(";
            for($n=0; $n<count($request->tipus); $n++) {
                if($n==0) {
                  $tipusQuery = $tipusQuery . "hard_tipo = " .$request->tipus[0];
                } else {
                  $tipusQuery = $tipusQuery . " OR hard_tipo = " .$request->tipus[$n];
                }
            }
            $tipusQuery = $tipusQuery . ")";
            //dd(DB::raw($tipusQuery));
            $query->whereRaw($tipusQuery);
          }

          return $query->orderBy('hard_dpto', 'asc')->orderBy('hard_tipo','asc')->orderBy('hard_cod','asc')->get(); 

        }

}
