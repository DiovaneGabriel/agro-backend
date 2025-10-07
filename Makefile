CONTAINER_PHP = agro-php

up:
	docker compose up -d

down:
	docker compose down

bash:
	docker exec -it ${CONTAINER_PHP} /bin/bash

composer-update:
	docker exec -it ${CONTAINER_PHP} sh -c "cd /var/www/html && composer update"

install:
	docker run -it -v ./start-app:/var/www/html diovanegabriel/php8.3-laravel:latest /bin/bash -c "composer create-project laravel/laravel . && php artisan install:api" && \
	sudo chmod -R 777 . && \
	mv ./start-app/* . && \
	mv ./start-app/.editorconfig . && \
	mv ./start-app/.env . && \
	mv ./start-app/.env.example . && \
	mv ./start-app/.gitattributes . && \
	mv ./start-app/.gitignore . && \
	rm -rf ./start-app/ && \
	docker container prune -f && \
	docker compose up -d && \
	make composer-update

migrate:
	docker exec -it ${CONTAINER_PHP} sh -c "php artisan migrate"

migrate-undo:
	clear && \
	docker exec -it ${CONTAINER_PHP} sh -c "php artisan migrate:reset"

import:
	clear && \
	docker exec -it ${CONTAINER_PHP} sh -c "php artisan import:agrofit"

import-action-mechanism:
	clear && \
	docker exec -it ${CONTAINER_PHP} sh -c "php artisan import:action-mechanism"

transfer:
	clear && \
	docker exec -it ${CONTAINER_PHP} sh -c "php artisan transfer:agrofit"