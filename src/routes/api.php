<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\AsistenteController;
use App\Http\Controllers\Api\BarcodeController;
use App\Http\Controllers\Api\ConvocatoriaController;
use App\Http\Controllers\Api\HealthController;
use App\Http\Controllers\Api\InstallController;
use App\Http\Controllers\Api\InmuebleController;
use App\Http\Controllers\Api\OrdenDiaController;
use App\Http\Controllers\Api\OpcionController;
use App\Http\Controllers\Api\PhController;
use App\Http\Controllers\Api\PreguntaController;
use App\Http\Controllers\Api\ReunionController;
use App\Http\Controllers\Api\TimerController;
use App\Http\Controllers\Api\UserInmuebleController;
use App\Http\Controllers\Api\ZonaComunController;
use App\Http\Controllers\Api\VotoController;
use App\Http\Controllers\Api\QuorumController;
use App\Http\Controllers\Api\ReporteReunionController;
use App\Http\Controllers\Api\MetricsController;
use App\Http\Controllers\Api\MenuController;
use App\Http\Controllers\Api\WhatsAppWebhookController;
use App\Http\Controllers\Api\InternalSimulateMessageController;
use Illuminate\Support\Facades\Route;

Route::get('/health', HealthController::class);

Route::middleware(['installed', 'throttle:webhooks'])->prefix('webhooks')->group(function (): void {
    Route::get('/whatsapp', [WhatsAppWebhookController::class, 'verify']);
    Route::post('/whatsapp', [WhatsAppWebhookController::class, 'receive']);
});

Route::middleware('not_installed')->prefix('install')->group(function (): void {
    Route::post('/check', [InstallController::class, 'check']);
    Route::post('/run', [InstallController::class, 'run']);
});

Route::get('/install/status', [InstallController::class, 'status']);

Route::middleware('installed')->group(function (): void {
    Route::post('/login', [AuthController::class, 'login']);
});

Route::middleware(['installed', 'auth:sanctum'])->group(function (): void {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);
    Route::post('/change-password', [AuthController::class, 'changePassword']);

    Route::get('/menu', MenuController::class);

    Route::get('/ph', [PhController::class, 'show']);
    Route::put('/ph', [PhController::class, 'update'])->middleware('role:SUPER_ADMIN,ADMIN_PH');

    Route::get('/inmuebles', [InmuebleController::class, 'index']);
    Route::post('/inmuebles', [InmuebleController::class, 'store'])->middleware('role:SUPER_ADMIN,ADMIN_PH,LOGISTICA');
    Route::post('/inmuebles/carga-masiva', [InmuebleController::class, 'cargaMasiva'])
        ->middleware('role:SUPER_ADMIN,ADMIN_PH,LOGISTICA', 'throttle:registros');
    Route::get('/inmuebles/validar-coeficientes', [InmuebleController::class, 'validarCoeficientes']);
    Route::get('/inmuebles/{inmueble}', [InmuebleController::class, 'show']);
    Route::put('/inmuebles/{inmueble}', [InmuebleController::class, 'update'])->middleware('role:SUPER_ADMIN,ADMIN_PH,LOGISTICA');
    Route::patch('/inmuebles/{inmueble}', [InmuebleController::class, 'update'])->middleware('role:SUPER_ADMIN,ADMIN_PH,LOGISTICA');
    Route::delete('/inmuebles/{inmueble}', [InmuebleController::class, 'destroy'])->middleware('role:SUPER_ADMIN,ADMIN_PH,LOGISTICA');

    Route::get('/users/{user}/inmuebles', [UserInmuebleController::class, 'index']);
    Route::post('/users/{user}/inmuebles', [UserInmuebleController::class, 'store'])->middleware('role:SUPER_ADMIN,ADMIN_PH,LOGISTICA');
    Route::delete('/users/{user}/inmuebles/{inmueble}', [UserInmuebleController::class, 'destroy'])->middleware('role:SUPER_ADMIN,ADMIN_PH,LOGISTICA');

    // Asistentes scoped bajo reunión
    Route::get('/reuniones/{reunion}/asistentes', [AsistenteController::class, 'index']);
    Route::post('/reuniones/{reunion}/asistentes', [AsistenteController::class, 'store'])
        ->middleware('role:SUPER_ADMIN,ADMIN_PH,LOGISTICA', 'throttle:registros');
    // Registro tardío: permite incorporar asistentes después del cierre del quórum
    Route::post('/reuniones/{reunion}/asistentes/registro-tardio', [AsistenteController::class, 'registroTardio'])
        ->middleware('role:SUPER_ADMIN,ADMIN_PH,LOGISTICA', 'throttle:registros');
    Route::get('/reuniones/{reunion}/asistentes/{asistente}', [AsistenteController::class, 'show']);
    Route::delete('/reuniones/{reunion}/asistentes/{asistente}', [AsistenteController::class, 'destroy'])
        ->middleware('role:SUPER_ADMIN,ADMIN_PH,LOGISTICA');

    // Endpoints de puerta (globales — no requieren conocer la reunión en la URL)
    Route::post('/asistentes/check-in-by-codigo', [AsistenteController::class, 'checkInByCodigo'])
        ->middleware('role:SUPER_ADMIN,ADMIN_PH,LOGISTICA');
    Route::post('/asistentes/{asistente}/check-in', [AsistenteController::class, 'checkIn'])
        ->middleware('role:SUPER_ADMIN,ADMIN_PH,LOGISTICA');

    Route::post('/barcodes/print', [BarcodeController::class, 'print'])->middleware('role:SUPER_ADMIN,ADMIN_PH,LOGISTICA');

    Route::get('/reuniones', [ReunionController::class, 'index']);
    Route::post('/reuniones', [ReunionController::class, 'store'])->middleware('role:SUPER_ADMIN,ADMIN_PH,LOGISTICA');
    Route::get('/reuniones/{reunion}', [ReunionController::class, 'show']);
    Route::put('/reuniones/{reunion}', [ReunionController::class, 'update'])->middleware('role:SUPER_ADMIN,ADMIN_PH,LOGISTICA');
    Route::patch('/reuniones/{reunion}', [ReunionController::class, 'update'])->middleware('role:SUPER_ADMIN,ADMIN_PH,LOGISTICA');
    Route::delete('/reuniones/{reunion}', [ReunionController::class, 'destroy'])->middleware('role:SUPER_ADMIN,ADMIN_PH,LOGISTICA');
    Route::post('/reuniones/{reunion}/iniciar', [ReunionController::class, 'iniciar'])->middleware('role:SUPER_ADMIN,ADMIN_PH,LOGISTICA');
    Route::post('/reuniones/{reunion}/cerrar', [ReunionController::class, 'cerrar'])->middleware('role:SUPER_ADMIN,ADMIN_PH,LOGISTICA');

    Route::get('/reuniones/{reunion}/orden-dia', [OrdenDiaController::class, 'index']);
    Route::post('/reuniones/{reunion}/orden-dia', [OrdenDiaController::class, 'store'])->middleware('role:SUPER_ADMIN,ADMIN_PH,LOGISTICA');
    Route::post('/reuniones/{reunion}/orden-dia/carga-masiva', [OrdenDiaController::class, 'cargaMasiva'])->middleware('role:SUPER_ADMIN,ADMIN_PH,LOGISTICA');
    Route::put('/reuniones/{reunion}/orden-dia/reordenar', [OrdenDiaController::class, 'reordenar'])->middleware('role:SUPER_ADMIN,ADMIN_PH,LOGISTICA');
    Route::put('/orden-dia/{item}', [OrdenDiaController::class, 'update'])->middleware('role:SUPER_ADMIN,ADMIN_PH,LOGISTICA');
    Route::patch('/orden-dia/{item}', [OrdenDiaController::class, 'update'])->middleware('role:SUPER_ADMIN,ADMIN_PH,LOGISTICA');
    Route::post('/orden-dia/{item}/marcar-ejecutado', [OrdenDiaController::class, 'marcarEjecutado'])->middleware('role:SUPER_ADMIN,ADMIN_PH,LOGISTICA');

    Route::get('/zonas-comunes', [ZonaComunController::class, 'index']);
    Route::post('/zonas-comunes', [ZonaComunController::class, 'store'])->middleware('role:SUPER_ADMIN,ADMIN_PH,LOGISTICA');
    Route::get('/zonas-comunes/{zona}', [ZonaComunController::class, 'show']);
    Route::put('/zonas-comunes/{zona}', [ZonaComunController::class, 'update'])->middleware('role:SUPER_ADMIN,ADMIN_PH,LOGISTICA');
    Route::patch('/zonas-comunes/{zona}', [ZonaComunController::class, 'update'])->middleware('role:SUPER_ADMIN,ADMIN_PH,LOGISTICA');
    Route::delete('/zonas-comunes/{zona}', [ZonaComunController::class, 'destroy'])->middleware('role:SUPER_ADMIN,ADMIN_PH,LOGISTICA');

    Route::get('/reuniones/{reunion}/convocatoria', [ConvocatoriaController::class, 'show']);
    Route::post('/reuniones/{reunion}/convocatoria', [ConvocatoriaController::class, 'store'])->middleware('role:SUPER_ADMIN,ADMIN_PH,LOGISTICA');
    Route::put('/convocatorias/{convocatoria}', [ConvocatoriaController::class, 'update'])->middleware('role:SUPER_ADMIN,ADMIN_PH,LOGISTICA');
    Route::patch('/convocatorias/{convocatoria}', [ConvocatoriaController::class, 'update'])->middleware('role:SUPER_ADMIN,ADMIN_PH,LOGISTICA');
    Route::post('/convocatorias/{convocatoria}/enviar', [ConvocatoriaController::class, 'enviar'])->middleware('role:SUPER_ADMIN,ADMIN_PH,LOGISTICA');
    Route::post('/convocatorias/{convocatoria}/publicar', [ConvocatoriaController::class, 'publicar'])->middleware('role:SUPER_ADMIN,ADMIN_PH,LOGISTICA');

    Route::get('/preguntas', [PreguntaController::class, 'index']);
    Route::post('/preguntas', [PreguntaController::class, 'store'])->middleware('role:SUPER_ADMIN,ADMIN_PH,LOGISTICA');
    Route::get('/preguntas/{pregunta}', [PreguntaController::class, 'show']);
    Route::put('/preguntas/{pregunta}', [PreguntaController::class, 'update'])->middleware('role:SUPER_ADMIN,ADMIN_PH,LOGISTICA');
    Route::patch('/preguntas/{pregunta}', [PreguntaController::class, 'update'])->middleware('role:SUPER_ADMIN,ADMIN_PH,LOGISTICA');
    Route::delete('/preguntas/{pregunta}', [PreguntaController::class, 'destroy'])->middleware('role:SUPER_ADMIN,ADMIN_PH,LOGISTICA');
    Route::post('/preguntas/{pregunta}/abrir', [PreguntaController::class, 'abrir'])->middleware('role:SUPER_ADMIN,ADMIN_PH,LOGISTICA');
    Route::post('/preguntas/{pregunta}/cerrar', [PreguntaController::class, 'cerrar'])->middleware('role:SUPER_ADMIN,ADMIN_PH,LOGISTICA');
    Route::get('/preguntas/{pregunta}/resultados', [PreguntaController::class, 'resultados']);
    Route::get('/preguntas/{pregunta}/inmuebles-votos', [PreguntaController::class, 'inmuebleVotos']);

    Route::get('/opciones', [OpcionController::class, 'index']);
    Route::post('/opciones', [OpcionController::class, 'store'])->middleware('role:SUPER_ADMIN,ADMIN_PH,LOGISTICA');
    Route::get('/opciones/{opcion}', [OpcionController::class, 'show']);
    Route::put('/opciones/{opcion}', [OpcionController::class, 'update'])->middleware('role:SUPER_ADMIN,ADMIN_PH,LOGISTICA');
    Route::patch('/opciones/{opcion}', [OpcionController::class, 'update'])->middleware('role:SUPER_ADMIN,ADMIN_PH,LOGISTICA');
    Route::delete('/opciones/{opcion}', [OpcionController::class, 'destroy'])->middleware('role:SUPER_ADMIN,ADMIN_PH,LOGISTICA');

    Route::get('/timers', [TimerController::class, 'index']);
    Route::post('/timers', [TimerController::class, 'store'])->middleware('role:SUPER_ADMIN,ADMIN_PH,LOGISTICA');
    Route::get('/timers/{timer}', [TimerController::class, 'show']);
    Route::put('/timers/{timer}', [TimerController::class, 'update'])->middleware('role:SUPER_ADMIN,ADMIN_PH,LOGISTICA');
    Route::patch('/timers/{timer}', [TimerController::class, 'update'])->middleware('role:SUPER_ADMIN,ADMIN_PH,LOGISTICA');
    Route::delete('/timers/{timer}', [TimerController::class, 'destroy'])->middleware('role:SUPER_ADMIN,ADMIN_PH,LOGISTICA');
    Route::post('/timers/{timer}/iniciar', [TimerController::class, 'iniciar'])->middleware('role:SUPER_ADMIN,ADMIN_PH,LOGISTICA');
    Route::post('/timers/{timer}/pausar', [TimerController::class, 'pausar'])->middleware('role:SUPER_ADMIN,ADMIN_PH,LOGISTICA');

    Route::get('/votos', [VotoController::class, 'index'])->middleware('role:SUPER_ADMIN,ADMIN_PH,LOGISTICA,LECTURA');
    Route::post('/votos', [VotoController::class, 'store'])->middleware('throttle:registros');
    Route::get('/votos/{voto}', [VotoController::class, 'show'])->middleware('role:SUPER_ADMIN,ADMIN_PH,LOGISTICA,LECTURA');

    Route::get('/quorum', [QuorumController::class, 'actual'])->middleware('role:SUPER_ADMIN,ADMIN_PH,LOGISTICA,LECTURA');
    Route::post('/quorum/pregunta', [QuorumController::class, 'crearPregunta'])->middleware('role:SUPER_ADMIN,ADMIN_PH,LOGISTICA');

    Route::get('/reportes/reuniones/{reunion}/acta-pdf', [ReporteReunionController::class, 'actaPdf'])->middleware('role:SUPER_ADMIN,ADMIN_PH,LOGISTICA,LECTURA');
    Route::get('/reportes/reuniones/{reunion}/estadisticas', [ReporteReunionController::class, 'estadisticas'])->middleware('role:SUPER_ADMIN,ADMIN_PH,LOGISTICA,LECTURA');

    Route::get('/metrics', MetricsController::class)->middleware('role:SUPER_ADMIN,ADMIN_PH');

    Route::post('/internal/simulate-message', InternalSimulateMessageController::class)
        ->middleware('role:SUPER_ADMIN,ADMIN_PH,LOGISTICA');
});
