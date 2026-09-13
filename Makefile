SAIL=./vendor/bin/sail
FRONTEND_PID=frontend/.dev-server.pid
FRONTEND_LOG=frontend/dev-server.log

up:
	$(SAIL) up -d
	@if [ -f $(FRONTEND_PID) ] && kill -0 "$$(cat $(FRONTEND_PID))" 2>/dev/null; then \
		echo "フロントエンド開発サーバーは起動済みです（PID $$(cat $(FRONTEND_PID))）"; \
	else \
		cd frontend && nohup node_modules/.bin/ng serve > dev-server.log 2>&1 & \
		echo $$! > $(FRONTEND_PID); \
		echo "フロントエンド開発サーバーを起動しました → http://localhost:4200 （ログ: $(FRONTEND_LOG)）"; \
	fi

down:
	$(SAIL) down
	@if [ -f $(FRONTEND_PID) ]; then \
		kill "$$(cat $(FRONTEND_PID))" 2>/dev/null || true; \
		rm -f $(FRONTEND_PID); \
		echo "フロントエンド開発サーバーを停止しました"; \
	fi

restart:
	$(MAKE) down
	$(MAKE) up

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
# ローカルのNode.jsで動作。開発サーバーは通常`make up`が自動的にバックグラウンドで起動する。
# ログを直接ターミナルに出したい場合や、`make up`とは別に単独で再起動したい場合はこちらを使う。
frontend-install:
	cd frontend && npm install

frontend-dev:
	cd frontend && npm start

frontend-build:
	cd frontend && npm run build

frontend-test:
	cd frontend && npm test -- --watch=false
