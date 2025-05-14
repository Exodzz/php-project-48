lint:
	@composer exec --verbose phpcs -- --standard=PSR12 src bin
lint-fix:
	@composer exec --verbose phpcbf -- --standard=PSR12 src bin
install:
	composer install
	@chmod -R 755 bin/*
test:
	composer exec --verbose phpunit tests
test-coverage:
	composer run test-coverage;
testcov-html:
	composer run test-coverage-html;