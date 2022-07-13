.PHONY: ci-cs
ci-cs: vendor ## Check all files using defined ECS rules (for CI/CD only)
	vendor/bin/ecs check

.PHONY: ci-rector
ci-rector: vendor ## Check all files using Rector (for CI/CD only)
	vendor/bin/rector process --ansi --dry-run --xdebug

vendor: composer.json
	composer validate
	composer install

.DEFAULT_GOAL := help
help:
	@grep -E '(^[a-zA-Z_-]+:.*?##.*$$)|(^##)' $(MAKEFILE_LIST) | awk 'BEGIN {FS = ":.*?## "}; {printf "\033[32m%-30s\033[0m %s\n", $$1, $$2}' | sed -e 's/\[32m##/[33m/'
.PHONY: help
