# UniPH API

API REST para la gestión de una Propiedad Horizontal (PH). Laravel 12, PostgreSQL 16, Redis, Horizon.

## Inicio rápido

```bash
# Desde la raíz del repositorio
make init      # Primera vez (crea .env, migraciones, etc.)
make up        # Levantar contenedores
make horizon   # Procesar colas (en otra terminal)
```

API: `http://localhost:8000/api`  
Documentación: `http://localhost:8000/docs/api`

## Instalación

Ver `docs/instalacion_uniph.md` para el flujo completo de instalación (tipo WordPress).

## Estructura principal

- **Autenticación**: Sanctum (`POST /api/login`, `GET /api/me`)
- **PH única**: `GET/PUT /api/ph`
- **Inmuebles**: CRUD, carga masiva, validación de coeficientes
- **Reuniones**: Orden del día, convocatorias, preguntas, votaciones
- **Quórum**: `GET /api/quorum`, `POST /api/quorum/pregunta`
- **Reportes**: Acta PDF, estadísticas
- **WhatsApp**: Webhook `GET|POST /api/webhooks/whatsapp`

## Comandos útiles

```bash
make migrate          # Ejecutar migraciones
make artisan migrate  # Idem
make docs-export      # Generar OpenAPI (api.json)
```
