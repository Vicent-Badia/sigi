@extends('layouts.app')

@section('title','Explotació · Elements')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header" style="background-color: #beb1a6;">EXPLOTACIÓ · ELEMENTS MAQUINARI (HARDWARE)</div>

                <div class="card-body">
                    @if (session('status'))
                        <div class="alert alert-success" role="alert">
                            {{ session('status') }}
                            <!--no escapar símbols per a incloure html <br>-->
                            <!--{!! session('status') !!}-->
                        </div>
                    @endif

                    @if (Session::has('saving-msg'))
                        <div class="alert alert-success" role="alert">
                            {{ Session::get('saving-msg') }}
                        </div>
                    @endif

                    @if (Session::has('delete-msg'))
                        <div class="alert alert-success" role="alert">
                            {{ Session::get('delete-msg') }}
                        </div>
                    @endif

                    <script type="text/javascript">
                        $(document).ready(function() {
                                $('#editarBtn').css("display", "none");
                                $('#editarLlocBtn').css("display", "none");
                        });
                    </script>

                    <div class="container">
                        <div class="row">
                                    <h4>Emplena algun camp per a buscar elements</h4>
                        </div>

                        <form action="{{ route('elements_search') }}" class="formulari" method="GET">
                                <div class="row">
                                    <div class="col-sm-1">
                                        <label for="codi" class="form-label">Codi element</label>
                                        <!--VIB-18des2024-Necessitem el paràmetre NAME, és el nom que s'utilitza en $request per a enviar el valor-->
                                        <input type="text" class="typeahead form-control" id="codi" name="codi" maxlength="10" field_search='hard_cod' value="{{ $dataReg->codi }}">
                                        <script type="text/javascript">
                                            
                                            var path = "{{ route('elements_autocomplete') }}";

                                            $('#codi').typeahead({
                                                source: function (query, process ) {
                                                    //$('#codi').val(query.toUpperCase());
                                                    campo= $('#codi').attr('field_search');
                                                    return $.get(path, {
                                                        query: query,
                                                        campo: campo
                                                    }, function (data) {
                                                        return process(data);
                                                    });
                                                }
                                            });
                                        </script>
                                    </div>
                                    <div class="col-sm-2">
                                        <div class="checkbox">
                                            <input class="form-check-input" type="checkbox" value="true" id="localitzable" name="localitzable" @if($dataReg->no_localitzable=="true") checked @endif>
                                            <label class="form-check-label" for="localitzable">
                                                No localitzable
                                            </label>
                                        </div>
                                        <script type="text/javascript">
                                            $('#localitzable').click(function() {
                                                if($("#localitzable").is(':checked')) {
                                                    $("#actiu").prop("checked", false);
                                                }
                                            });
                                        </script>

                                        <div class="checkbox">
                                            <input class="form-check-input" type="checkbox" value="true" id="actiu" name="actiu" @if($dataReg->actiu=="true") checked @endif>
                                            <label class="form-check-label" for="actiu">
                                                Actiu
                                            </label>
                                        </div>
                                        <script type="text/javascript">
                                            $('#actiu').click(function() {
                                                if($("#actiu").is(':checked')) {
                                                    $("#localitzable").prop("checked", false);
                                                    $("#desafectat").prop("checked", false);
                                                }
                                            });
                                        </script>
                                        <div class="checkbox">
                                            <input class="form-check-input" type="checkbox" value="true" id="desafectat" name="desafectat" @if($dataReg->desafectat=="true") checked @endif>
                                            <label class="form-check-label" for="desafectat">
                                                Desafectat
                                            </label>
                                        </div>
                                        <script type="text/javascript">
                                            $('#desafectat').click(function() {
                                                if($("#desafectat").is(':checked')) {
                                                    $("#actiu").prop("checked", false);
                                                }
                                            });
                                        </script>
                                    </div>
                                    <div class="col-sm-3">
                                        <label for="centre" class="form-label">Centre</label>
                                        <select class="form-control form-select" value="{{ $dataReg->centre }}" name="centre" id="centre">
                                            <option value="" selected>Selecciona un centre</option>
                                            @if(!empty($centres))
                                                @foreach($centres as $centre)
                                                    <option value="{{ $centre->cod_centro }}" @if($dataReg->centre == $centre->cod_centro) selected @endif>{{ $centre->nombre_centro }}</option>
                                                @endforeach
                                            @endif
                                        </select>
                                        <!--VIB-10feb2025-Carreguem els valors del select Planta en funció del centre seleccionat-->
                                        <script type="text/javascript">
                                            function removeOptions(selectElement) {
                                                var i, L = selectElement.options.length - 1;
                                                for(i = L; i >= 0; i--) {
                                                    selectElement.remove(i);
                                                }
                                            }
                                            function setCookie(name, value, days) {
                                                var expires = "";
                                                if (days) {
                                                    var date = new Date();
                                                    date.setTime(date.getTime() + (days * 24 * 60 * 60 * 1000));
                                                    expires = "; expires=" + date.toUTCString();
                                                }
                                                document.cookie = name + "=" + (value || "") + expires + "; path=/";
                                                }
                                            $('#centre').click(function() {
                                                console.log( "Centre: " + this.value );
                                                if (this.value !== "") {
                                                    setCookie("centre", this.value, 2);
                                                    const plantes = {!! json_encode($plantes->toArray(), JSON_HEX_TAG) !!};
                                                    const plantesCentre = plantes.filter(item => item.centro.indexOf(this.value) > -1);
                                                    console.log(plantesCentre);
                                                    let selectPlanta = document.getElementById("planta");
                                                    removeOptions(selectPlanta);
                                                    let opt1 = document.createElement("option");
                                                    opt1.value = -1;
                                                    opt1.innerHTML = "Selecciona"
                                                    selectPlanta.appendChild(opt1);

                                                    plantesCentre.forEach(function (item) {
                                                            var opt = document.createElement("option");
                                                            opt.value= item.planta;
                                                            if (item.descripcion !== null) {
                                                                opt.innerHTML = item.planta + " " + item.descripcion; 
                                                            } else {
                                                                opt.innerHTML = item.planta;
                                                            }
                                                            selectPlanta.appendChild(opt);
                                                        });
                                                }
                                            });
                                        </script>
                                    </div>
                                    <div class="col-sm-2">
                                        <label for="planta" class="form-label">Planta</label>
                                        <select type="text" class="form-control form-select" id="planta" name="planta" value="{{ $dataReg->planta }}">
                                            <option value=-1>Selecciona</option>
                                            @if(!empty($plantesCentre))
                                                @foreach($plantesCentre as $planta)
                                                    <option value="{{ $planta->planta }}" @if($dataReg->planta == $planta->planta) selected @endif>{{ $planta->planta }} - {{ $planta->descripcion }}</option>
                                                @endforeach
                                            @endif
                                        </select>
                                    </div>
                                    <!--VIB-10feb2025-Carreguem els valors del select Despatx en funció del centre i la planta seleccionats-->
                                    <script type="text/javascript">
                                            function removeOptionsDespatx(selectElement) {
                                                var i, L = selectElement.options.length - 1;
                                                for(i = L; i >= 0; i--) {
                                                    selectElement.remove(i);
                                                }
                                            }
                                            function getCookie(name) {
                                                var nameEQ = name + "=";
                                                var ca = document.cookie.split(';');
                                                for (var i = 0; i < ca.length; i++) {
                                                    var c = ca[i];
                                                    while (c.charAt(0) == ' ') c = c.substring(1, c.length);
                                                    if (c.indexOf(nameEQ) == 0) return c.substring(nameEQ.length, c.length);
                                                }
                                                return null;
                                                }
                                            $('#planta').click(function() {
                                                console.log( "Planta: " + this.value );
                                                const despatxos = {!! json_encode($despatxos->toArray(), JSON_HEX_TAG) !!};
                                                const centreSeleccionat = getCookie("centre");
                                                const despatxosPlanta = despatxos.filter(item => (item.planta.indexOf(this.value) > -1 && item.centro.indexOf(centreSeleccionat) > -1));
                                                console.log(despatxosPlanta);
                                                let selectDespatx = document.getElementById("despatx");
                                                removeOptionsDespatx(selectDespatx);
                                                let opt1 = document.createElement("option");
                                                opt1.value = -1;
                                                opt1.innerHTML = "Selecciona"
                                                selectDespatx.appendChild(opt1);

                                                despatxosPlanta.forEach(function (item) {
                                                        var opt = document.createElement("option");
                                                        opt.value= item.despacho;
                                                        if (item.descripcion !== null) {
                                                            opt.innerHTML = item.despacho + " " + item.descripcion; 
                                                        } else {
                                                            opt.innerHTML = item.despacho;
                                                        }
                                                        selectDespatx.appendChild(opt);
                                                    });
                                            });
                                        </script>
                                    <div class="col-sm-2">
                                        <label for="despatx" class="form-label">Despatx</label>
                                        <select type="text" class="form-control form-select" id="despatx" name="despatx" value="{{ $dataReg->despatx }}">
                                            <option value=-1>Selecciona</option>
                                            @if(!empty($despatxosPlanta))
                                                @foreach($despatxosPlanta as $despatx)
                                                    <option value="{{ $despatx->despacho }}" @if($dataReg->despatx == $despatx->despacho) selected @endif>{{ $despatx->despacho }} - {{ $despatx->descripcion }}</option>
                                                @endforeach
                                            @endif
                                        </select>
                                    </div>
                                    <div class="col-sm">
                                        <label for="tipo_desc" class="form-label">Tipus</label>
                                        <select class="form-control form-select" value="{{ $dataReg->tipus }}" name="tipus" id="tipus">
                                            <option value="" selected>Selecciona</option>
                                            @if(!empty($tipus))
                                                @foreach($tipus as $tipo)
                                                    <option value="{{ $tipo->tipo_cod }}" @if($dataReg->tipus == $tipo->tipo_cod) selected @endif>{{ $tipo->tipo_desc }}</option>
                                                @endforeach
                                            @endif
                                        </select>
                                    </div>
                                    
                                </div>
                                <div class="row">
                                <div class="col-sm">
                                        <label for="assignat" class="form-label">Assignat a</label>
                                        <select class="form-control form-select" value="{{ $dataReg->assignat }}" name="assignat" id="assignat">
                                            <option value="" selected>Selecciona</option>
                                            @if(!empty($departaments))
                                                @foreach($departaments as $dep)
                                                    <option value="{{ $dep->dpto_cod }}" @if($dataReg->assignat == $dep->dpto_cod) selected @endif>{{ $dep->dpto_cod }} - {{ $dep->dpto_desc }}</option>
                                                @endforeach
                                            @endif
                                        </select>
                                    </div>
                                    <div class="col-sm-4">
                                        <label for="usuari" class="form-label">Usuari</label>
                                        <input type="text" class="typeahead form-control" id="usuari" name="usuari" field_search='hard_usua' value="{{ $dataReg->usuari }}">
                                        <script type="text/javascript">
                                            var path = "{{ route('elements_autocomplete') }}";
                                            $('#usuari').typeahead({
                                            source: function (query, process ) {
                                                //$('#usuari').val(query.toUpperCase());
                                                campo= $('#usuari').attr('field_search');
                                                return $.get(path, {
                                                    query: query,
                                                    campo: campo
                                                }, function (data) {
                                                    return process(data);
                                                });
                                            }
                                            });
                                        </script>
                                    </div>
                                    <div class="col-sm-1">
                                        <label for="connexio" class="form-label">Connexió</label>
                                        <input type="text" class="typeahead form-control" id="connexio" name="connexio" field_search='hard_conx' value="{{ $dataReg->connexio }}">
                                        <script type="text/javascript">
                                            var path = "{{ route('elements_autocomplete') }}";
                                            $('#connexio').typeahead({
                                            source: function (query, process ) {
                                                //$('#connexio').val(query.toUpperCase());
                                                campo= $('#connexio').attr('field_search');
                                                return $.get(path, {
                                                    query: query,
                                                    campo: campo
                                                }, function (data) {
                                                    return process(data);
                                                });
                                            }
                                            });
                                        </script>
                                    </div>
                                    <div class="col-sm-1">
                                        <div class="checkbox">
                                            <input class="form-check-input" type="checkbox" value="true" id="garantia" name="garantia" @if($dataReg->garantia=="true") checked @endif>
                                            <label class="form-check-label" for="garantia">
                                                Garantia
                                            </label>
                                        </div>
                                        <div class="checkbox">
                                            <input class="form-check-input" type="checkbox" value="true" id="prestat" name="prestat" @if($dataReg->prestat=="true") checked @endif>
                                            <label class="form-check-label" for="prestat">
                                                Prestat
                                            </label>
                                        </div>
                                    </div>
                                    <div class="col-sm-2">
                                        <label for="fcompra1" class="form-label">Data de compra (des de)</label>
                                        <input type="date" class="typeahead form-control" id="fcompra1" name="fcompra1" value="{{ $dataReg->fcompra1 }}">
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-sm-2">
                                        <label for="proveidor" class="form-label">Proveïdor</label>
                                        <input type="text" class="typeahead form-control" id="proveidor" name="proveidor" field_search='prov_desc' value="{{ $dataReg->proveidor }}">
                                        <script type="text/javascript">
                                            var path = "{{ route('elements_autocomplete') }}";
                                            $('#proveidor').typeahead({
                                            source: function (query, process ) {
                                                //$('#proveidor').val(query.toUpperCase());
                                                campo= $('#proveidor').attr('field_search');
                                                return $.get(path, {
                                                    query: query,
                                                    campo: campo
                                                }, function (data) {
                                                    return process(data);
                                                });
                                            }
                                            });
                                        </script>
                                    </div>
                                    <div class="col-sm-2">
                                        <label for="numSerie" class="form-label">Núm. sèrie</label>
                                        <input type="text" class="typeahead form-control" id="numSerie" name="numSerie" field_search='hard_nser' value="{{ $dataReg->numSerie }}">
                                        <script type="text/javascript">
                                            var path = "{{ route('elements_autocomplete') }}";
                                            $('#numSerie').typeahead({
                                            source: function (query, process ) {
                                                //$('#numSerie').val(query.toUpperCase());
                                                campo= $('#numSerie').attr('field_search');
                                                return $.get(path, {
                                                    query: query,
                                                    campo: campo
                                                }, function (data) {
                                                    return process(data);
                                                });
                                            }
                                            });
                                        </script>
                                    </div>
                                    <div class="col-sm-2">
                                        <label for="model" class="form-label">Model</label>
                                        <input type="text" class="typeahead form-control" id="model" name="model" field_search='hard_mode' value="{{ $dataReg->model }}">
                                        <script type="text/javascript">
                                            var path = "{{ route('elements_autocomplete') }}";
                                            $('#model').typeahead({
                                            source: function (query, process ) {
                                                //$('#model').val(query.toUpperCase());
                                                campo= $('#model').attr('field_search');
                                                return $.get(path, {
                                                    query: query,
                                                    campo: campo
                                                }, function (data) {
                                                    return process(data);
                                                });
                                            }
                                            });
                                        </script>
                                    </div>
                                    <div class="col-sm-2">
                                        <label for="marca" class="form-label">Marca</label>
                                        <input type="text" class="typeahead form-control" id="marca" name="marca" field_search='hard_marc' value="{{ $dataReg->marca }}">
                                        <script type="text/javascript">
                                            var path = "{{ route('elements_autocomplete') }}";
                                            $('#marca').typeahead({
                                            source: function (query, process ) {
                                                //$('#marca').val(query.toUpperCase());
                                                campo= $('#marca').attr('field_search');
                                                return $.get(path, {
                                                    query: query,
                                                    campo: campo
                                                }, function (data) {
                                                    return process(data);
                                                });
                                            }
                                            });
                                        </script>
                                    </div>
                                    <div class="col-sm-2">
                                        <label for="plec" class="form-label">Codi plec</label>
                                        <input type="text" class="typeahead form-control" id="plec" name="plec" field_search='hard_pliego' value="{{ $dataReg->plec }}">
                                        <script type="text/javascript">
                                            var path = "{{ route('elements_autocomplete') }}";
                                            $('#plec').typeahead({
                                            source: function (query, process ) {
                                                //$('#plec').val(query.toUpperCase());
                                                campo= $('#plec').attr('field_search');
                                                return $.get(path, {
                                                    query: query,
                                                    campo: campo
                                                }, function (data) {
                                                    return process(data);
                                                });
                                            }
                                            });
                                        </script>
                                    </div>
                                    <div class="col-sm-2">
                                        <label for="fcompra2" class="form-label">Data de compra (fins a)</label>
                                        <input type="date" class="typeahead form-control" id="fcompra2" name="fcompra2" value="{{ $dataReg->fcompra2 }}">
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-sm-8">
                                        <label for="observ" class="form-label">Observacions</label>
                                        <input type="text" class="typeahead form-control" id="observ" name="observ" value="{{ $dataReg->observ }}">
                                    </div>
                                </div>
                                <div class="row">
                                            <a href="{{ route('elements') }}" class="refrescar"><button class="btn btn-secondary" type="button" title="Esborrar filtres">
                                                    <span class="fa fa-refresh"></span>
                                            </button></a>
                                            <button class="btn btn-primary mx-auto" type="submit" title="Buscar elements">
                                                <span class="fa fa-search"></span> Buscar
                                            </button>

                                            <a style="display: none;" id="editarBtn" class="crear"><button class="btn btn-primary" type="button" title="Editar component">
                                                    <span class="fa fa-pencil"></span> Editar component
                                            </button></a>
                                            <a style="display: none;" id="editarLlocBtn" class="crear"><button class="btn btn-primary" type="button" title="Editar lloc">
                                                    <span class="fa fa-pencil"></span> Editar lloc
                                            </button></a>
                                            <a href="{{ route('crear_element') }}" class="crear"><button class="btn btn-secondary" type="button" title="Crear nou component">
                                                    <span class="fa fa-plus"></span> Crear nou component
                                            </button></a>
                                            <a href="{{ route('crear_mult') }}" class="crear-mult"><button class="btn btn-secondary" type="button" title="Alta múltiple">
                                                    <span class="fa-regular fa-floppy-disk"></span> Alta múltiple
                                            </button></a>
                                            <a href="{{ route('crear_mult_conf') }}" class="crear-mult"><button class="btn btn-secondary" type="button" title="Afegir configuració llocs">
                                                    <span class="fa fa-plus"></span> Config llocs
                                            </button></a>
                                            
                                            @if(!empty($elements) && $elements->count())
                                                <!--VIB-18des2024-mostrar paginació. Afegim appends(request()->query()) per a que guarde els valors de la consulta
                                                    durant la paginació-->
                                                <div>{{$elements->appends(request()->query())->links()}}</div>  
                                                <div class="totalReg">Total: {{ $totalReg }} registres</div>
                                            @endif
                                </div>
                        </form>
                        <div class="row">
                        <div class="table-responsive">
                            <table class="table table-bordered" id="llistat">
                                <thead>
                                    <tr class="element-row-header">
                                        <th>Codi</th>
                                        <th>Actiu</th>
                                        <th>Centre</th>
                                        <th>Planta</th>
                                        <th>Despatx</th>
                                        <th>Tipus</th>
                                        <th>Assignat a</th>
                                        <th>Usuari</th>
                                        <th>Connexió</th>
                                        <th>Garantia</th>
                                        <th>Prestat</th>
                                        <th>Data compra</th>
                                        <th>Proveïdor</th>
                                        <th>Núm. sèrie</th>
                                        <th>Model</th>
                                        <th>Marca</th>
                                        <th>Codi plec</th>
                                        <th>Observacions</th>
                                        <th width="150px;">Accions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @if(!empty($elements) && $elements->count())
                                        @foreach($elements as $key => $value)
                                            <tr class="element-row" id="{{ $value->hard_cod }}">
                                                <td>{{ $value->hard_cod }}</td>
                                                <td>@if($value->hard_acti == "S") <i class="fa-solid fa-circle-check"></i> @endif</td>
                                                <td>{{ $value->nombre_centro }}</td>
                                                <td>{{ $value->hard_piso }}</td>
                                                <td>@if($value->hard_desp !== "-1") {{ $value->hard_desp }} @endif</td>
                                                <td>{{ $value->tipo_desc }}</td>
                                                <td>{{ $value->dpto_desc }}</td>
                                                <td>{{ $value->hard_usua }}</td>
                                                <td>{{ $value->hard_conx }}</td>
                                                <td>{{ $value->hard_gara }}</td>
                                                <td>@if($value->prestat == "S") <i class="fa-solid fa-circle-check"></i> @endif</td>
                                                <td>@if($value->hard_ffac !== null) {{  date_create($value->hard_ffac)->format('d-m-Y') }} @endif</td>
                                                <td>{{ $value->prov_desc }}</td>
                                                <td>{{ $value->hard_nser }}</td>
                                                <td>{{ $value->hard_mode }}</td>
                                                <td>{{ $value->hard_marc }}</td>
                                                <td>{{ $value->hard_pliego }}</td>
                                                <td>{{ $value->hard_obse }}</td>
                                                <td id="rowButtons">
                                                    <table>
                                                        <tr>
                                                            <td>
                                                                <a href="/element/{{$value->hard_cod}}"><button type="button" class="btn btn-secondary" title="Veure">
                                                                    <span class="fa fa-eye"></span> 
                                                                </button></a>
                                                            </td>
                                                            <td>
                                                                <a href="/element/editar/{{$value->hard_cod}}"><button class="btn btn-primary" title="Editar">
                                                                    <span class="fa fa-pencil"></span> 
                                                                </button></a>
                                                            </td>
                                                            <td>
                                                                <a href="/element/eliminar/{{$value->hard_cod}}"><button class="btn btn-secondary" title="Suprimir">
                                                                    <span class="fa fa-trash"></span>
                                                                </button></a>
                                                            </td>
                                                        </tr>
                                                    </table>
                                                </td>
                                            </tr>
                                            <!--VIB-18feb2025-Carregar els valors del registre dalt, en el formulari de cerca quan fem clic en una fila dels resultats-->
                                            <script type="text/javascript">
                                                function removeOptions(selectElement) {
                                                    var i, L = selectElement.options.length - 1;
                                                    for(i = L; i >= 0; i--) {
                                                        selectElement.remove(i);
                                                    }
                                                }
                                                $('#{{ $value->hard_cod }}').click(function() {
                                                    console.log('Clic fila');
                                                    console.log('{{ $value->hard_cod }}');
                                                    $('#codi').val('{{ $value->hard_cod }}');
                                                    if ('{{ $value->hard_acti }}' == 'S') {
                                                        $('#actiu').val("true");
                                                    } else {
                                                        $('#actiu').val("false");
                                                    }
                                                    if ('{{ $value->hard_iloc }}' == 'S') {
                                                        $('#localitzable').val("true");
                                                    } else {
                                                        $('#localitzable').val("false");
                                                    }
                                                    if ('{{ $value->hard_desa }}' == 'S') {
                                                        $('#desafectat').val("true");
                                                    } else {
                                                        $('#desafectat').val("false");
                                                    }
                                                    $('#centre').val('{{ $value->hard_ctra }}');

                                                    const plantes = {!! json_encode($plantes->toArray(), JSON_HEX_TAG) !!};
                                                    const plantesCentre = plantes.filter(item => item.centro == '{{ $value->hard_ctra }}');
                                                    let selectPlanta = document.getElementById("planta");
                                                    removeOptions(selectPlanta);
                                                    plantesCentre.forEach(function (item) {
                                                        var opt = document.createElement("option");
                                                        opt.value= item.planta;
                                                        if (item.descripcion !== null) {
                                                            opt.innerHTML = item.planta + " " + item.descripcion; 
                                                        } else {
                                                            opt.innerHTML = item.planta;
                                                        }
                                                        if (item.planta === '{{ $value->hard_piso }}') {
                                                        }
                                                        selectPlanta.appendChild(opt);
                                                    });
                                                    $('#planta').val('{{ $value->hard_piso }}');

                                                    const despatxos = {!! json_encode($despatxos->toArray(), JSON_HEX_TAG) !!};
                                                    const despatxosPlanta = despatxos.filter(item => (item.planta == '{{ $value->hard_piso }}' && item.centro == '{{ $value->hard_ctra }}'));
                                                    let selectDespatx = document.getElementById("despatx");
                                                    removeOptionsDespatx(selectDespatx);

                                                    //opció Selecciona si el valor del despatx és null
                                                    var opt = document.createElement("option");
                                                    opt.value = -1;
                                                    opt.innerHTML = "Selecciona";
                                                    selectDespatx.appendChild(opt);

                                                    despatxosPlanta.forEach(function (item) {
                                                            var opt = document.createElement("option");
                                                            opt.value= item.despacho;
                                                            if (item.descripcion !== null) {
                                                                opt.innerHTML = item.despacho + " " + item.descripcion; 
                                                            } else {
                                                                opt.innerHTML = item.despacho;
                                                            }
                                                            selectDespatx.appendChild(opt);
                                                        });
                                                    $('#despatx').val('{{ $value->hard_desp }}');
                                                    $('#tipus').val('{{ $value->hard_tipo }}');
                                                    $('#assignat').val('{{ $value->hard_dpto }}');
                                                    $('#usuari').val('{{ $value->hard_usua }}');
                                                    $('#connexio').val('{{ $value->hard_conx }}');

                                                    //VIB-18feb2025-Calcular si està en garantia d'acord amb la data de compra i els anys de garantia (per defecte 2)
                                                    if ('{{ $value->hard_ffac }}' !== "") {
                                                        const dataCompra = new Date('{{ $value->hard_ffac }}');
                                                        const year = dataCompra.getFullYear();
                                                        const month = dataCompra.getMonth();
                                                        const day = dataCompra.getDate();
                                                        let fiGarantia = '';
                                                        if ('{{ $value->hard_gara }}' !== "") {
                                                            fiGarantia = new Date(year + parseInt('{{ $value->hard_gara }}'), month, day);
                                                        } else {
                                                            fiGarantia = new Date(year + 2, month, day);
                                                        }
                                                        const today = new Date();
                                                        if (today <= fiGarantia) {
                                                            $('#garantia').prop('checked', true);
                                                        } else {
                                                            $('#garantia').prop('checked', false);
                                                        }
                                                    } else {
                                                        $('#garantia').prop('checked', false);
                                                    }

                                                    if('{{ $value->prestat }}' == 'S') {
                                                        $('#prestat').prop('checked', true);
                                                    } else {
                                                        $('#prestat').prop('checked', false);
                                                    }

                                                    if ('{{ $value->hard_ffac }}' !== "") {
                                                        $('#fcompra1').val('{{ $value->hard_ffac }}');
                                                    } else {
                                                        $('#fcompra1').val("");
                                                    }

                                                    $('#proveidor').val('{{ $value->prov_desc }}');
                                                    $('#numSerie').val('{{ $value->hard_nser }}');
                                                    //VIB-6ago2025-Substituir caracter HTML per UTF
                                                    //cometes dobles:
                                                    let model = '{{$value->hard_mode}}'.replaceAll('&quot;', '"');
                                                    //cometa simple:
                                                    model = '{{$value->hard_mode}}'.replaceAll('&#039;', '\'');
                                                    $('#model').val(model);
                                                    $('#marca').val('{{ $value->hard_marc }}');
                                                    $('#plec').val('{{ $value->hard_pliego }}');
                                                    //decodificar text per a eliminar caracters en HTML
                                                    var observElement = document.createElement('textarea');
                                                    observElement.innerHTML = '{{ $value->hard_obse }}';
                                                    $('#observ').val(observElement.value);

                                                    //VIB-11jun2025-Mostrar botó Editar Component dalt
                                                    $('#editarBtn').attr('href', '/element/editar/{{$value->hard_cod}}')
                                                    $('#editarBtn').css("display", "block");
                                                    //Mostrar botó Editar Lloc sols per a components de tipus Lloc (Puesto)
                                                    console.log('{{ $value->es_puesto }}');
                                                    if('{{ $value->es_puesto == 1 }}') {
                                                        $('#editarLlocBtn').attr('href', '/lloc/editar/{{$value->hard_cod}}')
                                                        $('#editarLlocBtn').css("display", "block");
                                                    } else {
                                                        $('#editarLlocBtn').css("display", "none");
                                                    }

                                                    $(window).scrollTop(0);
                                                });
                                            </script>
                                        @endforeach
                                    @else
                                        <tr>
                                            <td colspan="10">No hi ha resultats per a la cerca.</td>
                                        </tr>
                                    @endif
                                </tbody>
                            </table>
                        </div>
                        </div>
                        <div class="row">
                            @if(!empty($elements) && $elements->count())
                                                <!--VIB-18des2024-mostrar paginació. Afegim appends(request()->query()) per a que guarde els valors de la consulta
                                                    durant la paginació-->
                                                <div>{{$elements->appends(request()->query())->links()}}</div>  
                                                <div class="totalReg">Total: {{ $totalReg }} registres</div>
                                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection
