.PHONY: up down start stop restart ps logs app db artisan composer migrate key env init fix-perms show-urls horizon queue test

up:
	@echo "=> Levantando contenedores (build incluido)..."
	docker compose up -d --build
	@$(MAKE) show-urls

init:
	@echo "=> Inicializando proyecto por primera vez..."
	@$(MAKE) up
	@$(MAKE) env
	@$(MAKE) fix-perms
	@echo "=> Instalando dependencias de Composer..."
	docker compose exec app composer install
	@echo "=> Generando APP_KEY..."
	docker compose exec app php artisan key:generate
	@echo "=> Ejecutando migraciones..."
	docker compose exec app php artisan migrate
	@echo "=> Limpiando caches de Laravel..."
	docker compose exec app php artisan optimize:clear
	@echo "=> Inicializacion completada."

down:
	@echo "=> Deteniendo y eliminando contenedores/red..."
	docker compose down

start:
	@echo "=> Iniciando contenedores existentes..."
	docker compose start
	@$(MAKE) show-urls

stop:
	@echo "=> Deteniendo contenedores (sin borrar datos)..."
	docker compose stop

restart:
	@echo "=> Reiniciando contenedores..."
	docker compose restart
	@$(MAKE) show-urls

ps:
	@echo "=> Estado de servicios:"
	docker compose ps

logs:
	docker compose logs -f $(filter-out $@,$(MAKECMDGOALS))

app:
	docker compose exec app bash

db:
	docker compose exec db psql -U postgres -d uniph

artisan:
	docker compose exec app php artisan $(filter-out $@,$(MAKECMDGOALS))

composer:
	docker compose exec app composer $(filter-out $@,$(MAKECMDGOALS))

migrate:
	docker compose exec app php artisan migrate

key:
	docker compose exec app php artisan key:generate

env:
	@if [ ! -f src/.env ]; then cp src/.env.example src/.env && echo "=> src/.env creado"; else echo "=> src/.env ya existe"; fi

fix-perms:
	@echo "=> Preparando carpetas y permisos de Laravel..."
	docker compose exec app sh -lc "mkdir -p /var/www/storage/framework/views /var/www/storage/framework/cache /var/www/storage/framework/sessions /var/www/storage/logs /var/www/bootstrap/cache"
	docker compose exec app sh -lc "chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache && chmod -R ug+rwx /var/www/storage /var/www/bootstrap/cache"

show-urls:
	@echo ""
	@echo "=> Accesos:"
	@echo "   App (Nginx): http://localhost"
	@echo "   Horizon:     http://localhost/horizon"
	@echo "   Docs API:    http://localhost/docs/api"
	@echo "   PostgreSQL:  localhost:5432 (postgres/postgres, db: uniph)"
	@echo "   Redis:       localhost:6379"
	@echo ""

horizon:
	docker compose exec app php artisan horizon

queue:
	docker compose exec app php artisan queue:work redis --queue=votaciones,whatsapp,default

worker-logs:
	docker compose logs -f worker

test:
	docker compose exec app php artisan test

docs-export:
	docker compose exec app php artisan scramble:export

%:
	@:
