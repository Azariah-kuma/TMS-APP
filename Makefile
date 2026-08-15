SAIL=./vendor/bin/sail

up:
	$(SAIL) up -d

down:
	$(SAIL) down

restart:
	$(SAIL) down
	$(SAIL) up -d

migrate:
	$(SAIL) artisan migrate

fresh:
	$(SAIL) artisan migrate:fresh --seed

composer:
	$(SAIL) composer install

npm:
	$(SAIL) npm install

dev:
	$(SAIL) npm run dev

build:
	$(SAIL) npm run build

bash:
	$(SAIL) bash

logs:
	$(SAIL) logs -f

test:
	$(SAIL) test

# storage/coverage, frontend/coverage にカバレッジレポートを出力する
test-coverage:
	$(SAIL) artisan test --coverage --min=0 \
		--coverage-html=storage/coverage/html \
		--coverage-clover=storage/coverage/clover.xml \
		--coverage-cobertura=storage/coverage/cobertura.xml

frontend-test-coverage:
	cd frontend && npx ng test --watch=false --coverage \
		--coverage-reporters=html --coverage-reporters=text-summary --coverage-reporters=cobertura

# Angular SPA (frontend/)
# ローカルのNode.jsで動作
frontend-install:
	cd frontend && npm install

frontend-dev:
	cd frontend && npm start

frontend-build:
	cd frontend && npm run build

frontend-test:
	cd frontend && npm test -- --watch=false
