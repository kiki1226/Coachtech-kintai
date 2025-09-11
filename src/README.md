# kintai

## 環境構築
## Dockerビルド
### クローン
    git clone git@github.com:kiki1226/Coachtech-kintai.git
### 起動
    docker-compose up -d
### PHP コンテナに入る
    docker-compose exec php bash
### 停止
    docker-compose down

## Laravel環境構築
### 依存関係インストール
    composer install
### APP_KEY 生成
    php artisan key:generate
### ストレージ公開
    php artisan storage:link
### .env 用意
    cp .env.example .env
### マイグレーション
    php artisan migrate
### シーディング
    php artisan migrate --seed

## テストコード
### Feature / Unit テスト（PHPUnit）
    php artisan test
### Feature 一部指定
    php artisan test --filter=*****


## 管理者ログイン
    'name'      =>  管理者
    'email'     =>  admin@example.com
    'password'  =>  password
    
## URL（開発環境）
    勤怠登録画面                 =>  http://localhost/register
    トップページ(一般ログイン)  =>  http://localhost/login
    トップページ(管理ログイン)  =>  http://localhost/admin/login
    phpMyAdmin              =>  http://localhost:8080/
    メール確認                =>  http://localhost:8025/


## 使用技術
    php     : 8.2-fpm
    Laravel : 11.45.2
    mysql   : 8.0.26
    nginx   : 1.21.1
    jQuery  :'3.8'

# ER図

```mermaid
erDiagram
    users {
        bigint id PK
        varchar name
        varchar email
        varchar password
        varchar role
        varchar zipcode
        varchar address
        varchar building
        varchar avatar
    }

    attendances {
        bigint id PK
        bigint user_id FK
        date work_date
        time clock_in
        time clock_out
        time break1_start
        time break1_end
        time break_started_at
    }

    requests {
        bigint id PK
        bigint user_id FK
        bigint attendance_id FK
        varchar type
        varchar status
        text reason
        date target_date
    }

    work_rules {
        bigint id PK
        varchar rule_name
        time start_time
        time end_time
    }

    holidays {
        bigint id PK
        date holiday_date
        varchar name
    }

    users ||--o{ attendances : "has"
    users ||--o{ requests : "makes"
    attendances ||--o{ requests : "relates"
    work_rules ||--o{ users : "applies"
    holidays ||--o{ attendances : "affects"
```
