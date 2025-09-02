<!doctype html>
<html lang="es">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
    <meta name="viewport" content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Llistat de components</title>
</head>
<body>

    <style>
        /*VIB-3mar2025-Format llistats PDF*/
        #pdf-doc, #pdf-title {
        font-family: "Montserrat", Arial, sans-serif;
        }
        .pdf-footer {
        font-family: "Montserrat", Arial, sans-serif;
        margin-top: 50px;
        }
        .details tr th, .details tr td {
            padding: 5px;
            margin: 5px;
        }
        .details tr th {
            color: #FFF;
            background-color: #333;
        }
        .details tr {
            background-color: #DDD;
        }
    </style>
    
    <h2 id="pdf-title">SIGI - Llistat de components en garantia el dia {{ $dataRef }}</h2>
 
    <div id="pdf-doc">
        @php
           $numReg = 0;
           $numRegPag = 0;
           $centre= $data->first()->nombre_centro;
           $tipus = $data->first()->tipo_desc;
        @endphp
        <h3>{{ $centre }}</h3> 
        <h4>{{ $tipus }}</h4>
        <table class="details">
            <tr>
                <th>Codi</th>
                <th>Marca</th>
                <th>Model</th>
                <th>Núm. sèrie</th>
                <th>Departament</th>
                <th>Data compra</th>
                <th>Garantia (anys)</th>
            </tr>
            @foreach($data as $item)

                @if ($item->nombre_centro !== $centre)
                    @php
                        $centre= $item->nombre_centro;
                        $tipus = $item->tipo_desc;
                    @endphp
                    </table>
                    <!--VIB-5mar2025-Si hi ha més de 12 registres, la nova taula comença en una pàgina nova-->
                    <h3 @if($numRegPag > 12) style="page-break-before: always;" @php $numRegPag = 0; @endphp @endif >
                        {{ $centre }}
                    </h3> 
                    <h4>{{ $tipus }}</h4>
                    <table class="details">
                    <tr>
                        <th>Codi</th>
                        <th>Marca</th>
                        <th>Model</th>
                        <th>Núm. sèrie</th>
                        <th>Departament</th>
                        <th>Data compra</th>
                        <th>Garantia (anys)</th>
                    </tr>
                @elseif ($item->tipo_desc !== $tipus)
                    @php
                        $tipus = $item->tipo_desc;
                    @endphp
                    </table>
                    <!--VIB-5mar2025-Si hi ha més de 12 registres, la nova taula comença en una pàgina nova-->
                    <h4 @if($numRegPag > 12) style="page-break-before: always;" @php $numRegPag = 0; @endphp @endif>
                        {{ $tipus }}
                    </h4>
                    <table class="details">
                    <tr>
                        <th>Codi</th>
                        <th>Marca</th>
                        <th>Model</th>
                        <th>Núm. sèrie</th>
                        <th>Departament</th>
                        <th>Data compra</th>
                        <th>Garantia (anys)</th>
                    </tr>
                @endif
                <tr>
                    <td>
                        {{ $item->hard_cod }}
                    </td>
                    <td>
                        {{ $item->hard_marc }}
                    </td>
                    <td>
                        {{ $item->hard_mode }}
                    </td>
                    <td>
                        {{ $item->hard_nser }}
                    </td>
                    <td>
                        {{ $item->dpto_desc }}
                    </td>
                    <td>
                        {{ $item->hard_ffac }}
                    </td>
                    <td>
                        {{ $item->hard_gara }}
                    </td>
                </tr>
                    @php $numReg++; $numRegPag++; @endphp
            @endforeach
        </table>
    </div>
 
    <div class="pdf-footer">
        <div>Total registres: {{ $numReg }}</div>
    </div>
</body>
</html>