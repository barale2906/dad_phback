<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Barcodes</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; }
        .meta { margin-bottom: 12px; }
        .grid { width: 100%; border-collapse: collapse; }
        .grid td { width: 25%; border: 1px solid #ccc; padding: 8px; text-align: center; vertical-align: middle; }
        .code { font-size: 20px; font-weight: bold; letter-spacing: 1px; }
        .label { font-size: 10px; color: #555; margin-top: 4px; }
    </style>
</head>
<body>
<div class="meta">
    <strong>Secuencia:</strong> {{ $inicio }} - {{ $inicio + $cantidad - 1 }}<br>
    <strong>Cantidad:</strong> {{ $cantidad }}<br>
    <strong>Repeticiones:</strong> {{ $repeticiones }}
</div>
<table class="grid">
    <tr>
        @foreach($items as $index => $item)
            <td>
                <div class="code">{{ $item }}</div>
                <div class="label">Codigo para impresion</div>
            </td>
            @if(($index + 1) % 4 === 0 && !$loop->last)
                </tr><tr>
            @endif
        @endforeach
    </tr>
</table>
</body>
</html>
