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
### Dusk
php artisan dusk
### Dusk 一部指定
php artisan dusk --filter=*****

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

## ER図

```mermaid
%% カスタムテーマ（GitHubでも有効）
%% 参考: https://mermaid.js.org/config/theming.html#theme-variables
%%{init: {
  "theme": "base",
  "themeVariables": {
    "fontFamily": "ui-sans-serif, system-ui, -apple-system, Segoe UI, Roboto, Noto Sans, Helvetica Neue, Arial, 'Apple Color Emoji', 'Segoe UI Emoji'",
    "primaryColor":   "#F0F7FF",   
    "primaryBorderColor": "#4B9EFF",
    "primaryTextColor":   "#0B306B",
    "secondaryColor": "#FFFFFF",
    "tertiaryColor":  "#E8F2FF",
    "lineColor":      "#6A8CA6",
    "edgeLabelBackground":"#ffffff",
    "nodeBorder":     "#4B9EFF"
  }
}}%%

erDiagram

    Table users {
      id bigint [pk]
      name varchar
      email varchar
      password varchar
      role varchar
      zipcode varchar
      address varchar
      building varchar
      avatar varchar
    }

    Table attendances {
      id bigint [pk]
      user_id bigint [ref: > users.id]
      work_date date
      clock_in time
      clock_out time
      break1_start time
      break1_end time
      break_started_at time
    }

    Table requests {
      id bigint [pk]
      user_id bigint [ref: > users.id]
      attendance_id bigint [ref: > attendances.id]
      type varchar
      status varchar
      reason text
      target_date date
    }

    Table work_rules {
      id bigint [pk]
      rule_name varchar
      start_time time
      end_time time
    }

    Table holidays {
      id bigint [pk]
      holiday_date date
      name varchar
    }

    Ref: users.id < attendances.user_id
    Ref: users.id < requests.user_id
    Ref: attendances.id < requests.attendance_id
    Ref: work_rules.id < users.id
    Ref: holidays.id < attendances.id

```

