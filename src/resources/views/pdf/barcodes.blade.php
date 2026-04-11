<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Códigos de barras</title>
    <style>
        * {
            margin: 0;
            padding: 0;
        }

        body {
            font-family: DejaVu Sans, sans-serif;
            margin-top:    {{ $margenTop }}mm;
            margin-bottom: {{ $margenBottom }}mm;
            margin-left:   {{ $margenLeft }}mm;
            margin-right:  {{ $margenRight }}mm;
        }

        /*
         * table-layout: fixed obliga a DomPDF a respetar exactamente los anchos
         * declarados en las celdas, sin redistribuir espacio según el contenido.
         * El padding va en el .rotulo-inner para que no se sume al ancho del td.
         */
        table.grid {
            table-layout:    fixed;
            border-collapse: collapse;
            width:           auto;
        }

        td.rotulo {
            width:    {{ $rotuloAncho }}mm;
            height:   {{ $rotuloAlto }}mm;
            padding:  0;
            border:   0.2mm solid #cccccc;
            overflow: hidden;
        }

        .rotulo-inner {
            width:          {{ $rotuloAncho }}mm;
            height:         {{ $rotuloAlto }}mm;
            padding:        1.5mm;
            text-align:     center;
            vertical-align: middle;
            overflow:       hidden;
        }

        .barcode-img {
            display:    block;
            width:      {{ $rotuloAncho - 3 }}mm;
            height:     {{ $rotuloAlto - 8 }}mm;
            margin:     0 auto;
            object-fit: contain;
        }

        .barcode-num {
            display:        block;
            margin-top:     0.5mm;
            font-size:      8px;
            font-weight:    bold;
            text-align:     center;
            letter-spacing: 0.5px;
            white-space:    nowrap;
            overflow:       hidden;
        }
    </style>
</head>
<body>
<table class="grid">
    @foreach($items as $index => $item)
        @if($index % $columnas === 0)
            <tr>
        @endif

        <td class="rotulo">
            <div class="rotulo-inner">
                <img class="barcode-img" src="{{ $item['imagen'] }}" alt="{{ $item['numero'] }}">
                <span class="barcode-num">{{ $item['numero'] }}</span>
            </div>
        </td>

        @if(($index + 1) % $columnas === 0 || $loop->last)
            </tr>
        @endif
    @endforeach
</table>
</body>
</html>
