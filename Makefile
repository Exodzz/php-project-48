lint:
	@composer exec --verbose phpcs -- --standard=PSR12 src bin

install:
	composer install
	@chmod -R 755 bin/*