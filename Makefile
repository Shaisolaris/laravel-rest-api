.PHONY: install dev test lint
install:
	composer install --no-interaction
	cp .env.example .env 2>/dev/null || true
	php artisan key:generate 2>/dev/null || true
dev:
	php artisan serve
lint:
	find . -name "*.php" -not -path "./vendor/*" -exec php -l {} \;
