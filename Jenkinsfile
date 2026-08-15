// Jenkins パイプライン: バックエンド(Pest)・フロントエンド(Angular/Vitest)双方のテストとカバレッジを取得する。

// 前提（Jenkins側の準備）:
//   - エージェントに Docker / Docker Compose v2 が導入されていること
//   - Jenkinsプラグイン: "Pipeline", "JUnit", "Coverage"（recordCoverageステップ）
//   - Node.js はエージェント側に別途インストールしておくこと（frontend/はSailの外）


pipeline {
    agent any

    options {
        timestamps()
        disableConcurrentBuilds()
    }

    environment {
        COMPOSE = 'docker compose -f compose.yaml'
        // 開発中のSailスタック（Composeプロジェクト名 "tms-app"、DBポート5432）と
        // 同じホスト上で衝突しないよう、CI実行時は専用の名前空間・ポートを使う。
        // （Jenkinsジョブ側の設定に頼らず、このファイル単体で安全になるようにする）
        COMPOSE_PROJECT_NAME = 'tms-app-ci'
        FORWARD_DB_PORT = '5433'
        // Sailコンテナ内ユーザーのUID/GIDをJenkinsエージェントに合わせ、
        // bind mountしたファイル（vendor/, storage/ など）の権限不整合を防ぐ。
        WWWUSER = sh(script: 'id -u', returnStdout: true).trim()
        WWWGROUP = sh(script: 'id -g', returnStdout: true).trim()
    }

    stages {
        stage('Prepare .env') {
            steps {
                sh '''
                    if [ ! -f .env ]; then
                        cp .env.example .env
                    fi
                '''
            }
        }

        stage('Start PostgreSQL') {
            steps {
                sh "${COMPOSE} up -d pgsql"
            }
        }

        stage('Backend: install & migrate') {
            steps {
                sh "${COMPOSE} run --rm --no-deps laravel.test composer install --no-interaction --prefer-dist --no-progress"
                // .env はホスト（開発者の作業ディレクトリ）と共有されているため、
                // 無条件に key:generate すると開発中の APP_KEY を書き換えてしまう
                // （既存セッションが無効になる等の副作用がある）。
                // 既にキーが設定済みなら何もせず、未設定の場合（＝新規checkout直後、
                // .env.example から作られたばかりの .env）のみ生成する。
                sh '''
                    if ! grep -qE '^APP_KEY=base64:' .env 2>/dev/null; then
                        $COMPOSE run --rm --no-deps laravel.test php artisan key:generate --force
                    fi
                '''
                sh "${COMPOSE} run --rm --no-deps laravel.test php artisan migrate --force"
            }
        }

        stage('Backend: Pint (style check)') {
            steps {
                sh "${COMPOSE} run --rm --no-deps laravel.test vendor/bin/pint --test"
            }
        }

        stage('Backend: Pest + coverage') {
            steps {
                sh """
                    ${COMPOSE} run --rm --no-deps laravel.test vendor/bin/pest \
                        --colors=never \
                        --log-junit storage/coverage/junit.xml \
                        --coverage \
                        --coverage-cobertura storage/coverage/cobertura.xml \
                        --coverage-clover storage/coverage/clover.xml \
                        --coverage-html storage/coverage/html
                """
            }
        }

        stage('Frontend: install & build') {
            steps {
                dir('frontend') {
                    sh 'npm ci'
                    sh 'npx ng build'
                }
            }
        }

        stage('Frontend: tests + coverage') {
            steps {
                dir('frontend') {
                    sh '''
                        npx ng test --watch=false \
                            --reporters=default --reporters=junit --output-file=coverage/junit.xml \
                            --coverage --coverage-reporters=cobertura --coverage-reporters=lcov
                    '''
                }
            }
        }
    }

    post {
        always {
            junit(
                allowEmptyResults: true,
                testResults: 'storage/coverage/junit.xml,frontend/coverage/junit.xml',
            )

            recordCoverage(
                id: 'tms-app-coverage',
                name: 'tms-app coverage',
                tools: [
                    [parser: 'COBERTURA', pattern: 'storage/coverage/cobertura.xml'],
                    [parser: 'COBERTURA', pattern: 'frontend/coverage/**/cobertura-coverage.xml'],
                ],
            )

            sh "${COMPOSE} down -v || true"
        }
    }
}
