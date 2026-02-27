<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Acta de reunión #{{ $reunion->id }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; }
        h1, h2, h3 { margin: 0 0 8px 0; }
        .section { margin-bottom: 16px; }
        table { width: 100%; border-collapse: collapse; margin-top: 8px; }
        th, td { border: 1px solid #ccc; padding: 4px 6px; text-align: left; }
        th { background-color: #f0f0f0; }
    </style>
</head>
<body>
<h1>Acta de reunión</h1>

<div class="section">
    <h2>Datos de la reunión</h2>
    <p><strong>ID:</strong> {{ $reunion->id }}</p>
    <p><strong>Tipo:</strong> {{ $reunion->tipo }}</p>
    <p><strong>Fecha:</strong> {{ optional($reunion->fecha)->format('Y-m-d') }}</p>
    <p><strong>Hora:</strong> {{ $reunion->hora }}</p>
    <p><strong>Modalidad:</strong> {{ $reunion->modalidad }}</p>
    <p><strong>Estado:</strong> {{ $reunion->estado }}</p>
</div>

@if($reunion->convocatoria)
    <div class="section">
        <h2>Convocatoria</h2>
        <p><strong>Fecha de convocatoria:</strong> {{ optional($reunion->convocatoria->fecha_convocatoria)->format('Y-m-d') }}</p>
        <p><strong>Medio:</strong> {{ $reunion->convocatoria->medio }}</p>
        <p><strong>Estado:</strong> {{ $reunion->convocatoria->estado }}</p>
    </div>
@endif

<div class="section">
    <h2>Preguntas y opciones</h2>
    @foreach($reunion->preguntas as $pregunta)
        <h3>#{{ $pregunta->id }} - {{ $pregunta->pregunta }}</h3>
        <table>
            <thead>
            <tr>
                <th>Opción</th>
            </tr>
            </thead>
            <tbody>
            @foreach($pregunta->opciones as $opcion)
                <tr>
                    <td>{{ $opcion->texto }}</td>
                </tr>
            @endforeach
            </tbody>
        </table>
    @endforeach
</div>

</body>
</html>

