# Proテスト\_フリマアプリ

### アプリ機能

フリマ（フリーマーケット）の模擬アプリです。会員登録・メール認証・商品の出品・一覧・検索・詳細・いいね・コメント、Stripe によるクレジットカード・コンビニ決済、購入者と出品者間の取引チャット、双方評価による取引完了、取引完了メールまで一通り利用できます。Laravel 8（PHP 8.2）と Blade でサーバーサイドレンダリング、DB は MySQL、開発環境は Docker で構築しています。

<div style="color: red; font-weight: bold;">

### メール認証に関して

</div>

このアプリケーションでは、開発環境でのメール送信確認のために **MailHog** を使用しています。

**重要**: MailHogは既に`docker-compose.yml`に組み込まれているため、別途ダウンロードやインストールの必要はありません。`docker-compose up -d --build`を実行すると自動的に起動します。

**MailHogのアクセス方法**
- Web UI: http://localhost:8025/
- ここで送信されたメールの内容を確認できます

**メール認証の動作**
1. ユーザーが新規登録すると、認証メールが送信されます
2. メールはMailHogにキャプチャされ、実際のメールアドレスには送信されません
3. メール認証誘導画面（http://localhost/email/guide）に表示される「認証はこちらから」ボタンをサブミットすると、別のタブでMailHogの受信箱（http://localhost:8025/）が開きます
4. MailHogでメールを確認し、メール内の認証URLをクリックして認証を完了します

**取引完了メールの動作**
1. 購入者が取引チャット画面で「取引を完了する」ボタンをサブミットし、出品者への評価を送信すると、出品者宛に取引完了メールが自動送信されます
2. メールはMailHogにキャプチャされ、実際のメールアドレスには送信されません
3. メール内には取引完了の通知と、チャット画面へのアクセスリンク（トークン付きURL）が含まれます
4. 出品者はメール内のリンクをクリックするか、手動で取引チャット画面にアクセスできます

**設定について**
- `.env`ファイルの`MAIL_HOST=mailhog`でMailHogを使用するように.envに指定する環境設定が必要(※後述説明有)


<div style="color: red; font-weight: bold;">

### Stripeについて

</div>

このアプリケーションでは、商品購入時の決済処理に **Stripe** を使用しています。

**重要**: StripeのPHPライブラリは既に`composer.json`に含まれており、`composer install`で自動的にインストールされます。別途ダウンロードやインストールの必要はありません。ただし、StripeアカウントのAPIキー（`STRIPE_PUBLIC_KEY`と`STRIPE_SECRET_KEY`）の.envに指定する環境設定が必要(※後述説明有)

**Stripeの役割**
- クレジットカード決済とコンビニ決済をサポート
- Stripe Checkout APIを使用して決済セッションを作成
- 決済完了後、自動的に注文データを作成

**決済フローの概要**

1. **商品購入画面**
   - ユーザーが商品詳細ページから「購入する」をクリック
   - 決済方法（クレジットカード or コンビニ）を選択
   - 配送先住所を入力

2. **決済セッション作成**
   - `StripeService::createCheckoutSession()`でStripe Checkoutセッションを作成
   - コンビニ決済の場合：この時点で商品を売却済みにマークし、注文データを作成（支払い待ち状態）
   - カード決済の場合：決済完了後に注文データを作成

3. **Stripe Checkout画面**
   - ユーザーがStripeの決済画面にリダイレクト
   - 決済情報を入力（カード情報 or コンビニ選択）

4. **決済完了処理**
   - 決済成功後、`stripe.success`ルートで処理
   - セッションIDから決済情報を取得
   - カード決済の場合：注文データを作成し、商品を売却済みにマーク。支払ステータスはpaidとなります。
   - コンビニ決済の場合：既に注文データが作成されているため、そのまま完了。支払ステータスがpayment_pending:支払い待ち状態ですが取引チャット画面は利用できます。

**設定について**
- `.env`ファイルに以下の設定が必要です※後述説明有：
  - `STRIPE_PUBLIC_KEY`: Stripeダッシュボードから取得した公開キー（`pk_test_`で始まる）
  - `STRIPE_SECRET_KEY`: Stripeダッシュボードから取得した秘密キー（`sk_test_`で始まる）
  - `STRIPE_ENVIRONMENT=test`: テスト環境を使用
  - `STRIPE_WEBHOOK_ENABLED=false`: 開発環境ではwebhookは未使用

**注意事項**
- テスト環境では、Stripeのテスト用カード番号を使用してください
- 本番環境では、Stripeダッシュボードから本番用のAPIキーを取得して設定してください
- 詳細な決済フローやテスト用カード情報は、後述の「外部サービス・API」セクションを参照してください

## 画面一覧

| 画面名 | URL（例） | 認証 | 説明 |
|--------|-----------|------|------|
| 商品一覧 | `/` | 不要 | おすすめ・マイリストタブ、検索。 |
| 商品詳細 | `/item/{id}` | 不要 | 商品情報、コメント、いいね、購入ボタン。 |
| ログイン | `/login` | 不要（ゲスト用） | メール・パスワードでログイン。 |
| 会員登録 | `/register` | 不要（ゲスト用） | 名前・メール・パスワードで登録。 |
| メール認証誘導 | `/email/guide` | 不要 | 認証メールの確認方法、MailHog への誘導。 |
| メール認証（コード入力） | `/email/verify-code` 等 | 不要 | 認証コード入力または認証URLで完了。 |
| チャットアクセス確認 | `/chat/{item}/access/{token}` | 不要 | メール内リンクから。未ログイン時はログイン誘導。 |
| マイページ | `/mypage` | 必要 | 出品中・売却済み・購入関連のタブ。 |
| プロフィール編集 | `/mypage/profile` | 必要 | ユーザー名・住所・アイコン等の編集。 |
| 出品 | `/sell` | 必要 | 商品画像・カテゴリ・状態・説明・価格の入力。 |
| 購入（配送先・決済方法） | `/purchase/{id}` | 必要 | 配送先選択、クレジットカード or コンビニ選択。 |
| 配送先変更 | `/purchase/address/{id}` | 必要 | 購入フロー中の配送先変更。 |
| 決済完了 | `/stripe/success/{id}` | 必要 | Stripe 決済成功後の遷移先。 |
| 決済キャンセル | `/stripe/cancel/{id}` | 必要 | Stripe 決済キャンセル時の遷移先。 |
| 取引チャット | `/chat/{item}` | 必要 | 購入者・出品者間のメッセージ、評価、取引完了。 |
| アカウント切り替え確認 | （チャットから遷移） | 不要 | 別アカウントで開いたときのログイン誘導。 |

※ 認証「必要」の画面は、未ログイン時は `/login` にリダイレクトされます。メール未認証の場合は認証誘導画面へ誘導されます。

## データベーステーブル仕様

### usersテーブル

| カラム名 | 型 | primary key | unique key | not null | foreign key | 説明 |
|---------|-----|------------|------------|----------|-------------|------|
| id | bigint | ● | | ● | | ユーザーID |
| name | varchar(255) | | | ● | | ユーザー名 |
| email | varchar(255) | | ● | ● | | メールアドレス |
| email_verified_at | timestamp | | | | | メール認証日時 |
| verification_token | varchar(255) | | | | | メール認証用トークン |
| verification_token_expires_at | timestamp | | | | | メール認証トークンの有効期限 |
| password | varchar(255) | | | ● | | パスワード（ハッシュ化） |
| remember_token | varchar(100) | | | | | ログイン状態保持用トークン |
| created_at | timestamp | | | | | |
| updated_at | timestamp | | | | | |

### itemsテーブル

| カラム名 | 型 | primary key | unique key | not null | foreign key | 説明 |
|---------|-----|------------|------------|----------|-------------|------|
| id | bigint | ● | | ● | | 商品ID |
| user_id | bigint | | | ● | ● (users.id) | 出品者ID |
| item_image_paths | varchar(255) | | | ● | | 商品画像パス |
| item_names | varchar(255) | | | ● | | 商品名 |
| brand_names | varchar(255) | | | ● | | ブランド名 |
| item_prices | unsigned integer | | | ● | | 商品価格 |
| like_counts | unsigned integer | | | | | いいね数 |
| comment_counts | unsigned integer | | | | | コメント数 |
| item_descriptions | varchar(255) | | | ● | | 商品説明 |
| conditions | unsigned tinyint | | | ● | | 商品の状態（1:良好、2:目立った傷や汚れ無し、3:やや傷や汚れあり、4:状態が悪い） |
| is_sold | boolean | | | | | 売却済みフラグ |
| created_at | timestamp | | | | | |
| updated_at | timestamp | | | | | |

### commentsテーブル

| カラム名 | 型 | primary key | unique key | not null | foreign key | 説明 |
|---------|-----|------------|------------|----------|-------------|------|
| id | bigint | ● | | ● | | コメントID |
| item_id | bigint | | | ● | ● (items.id) | 商品ID |
| user_id | bigint | | | ● | ● (users.id) | コメント投稿者ID |
| comment_body | varchar(255) | | | ● | | コメント本文 |
| created_at | timestamp | | | | | |
| updated_at | timestamp | | | | | |

### categoriesテーブル

| カラム名 | 型 | primary key | unique key | not null | foreign key | 説明 |
|---------|-----|------------|------------|----------|-------------|------|
| id | bigint | ● | | ● | | カテゴリID |
| category_names | varchar(255) | | | ● | | カテゴリ名 |
| created_at | timestamp | | | | | |
| updated_at | timestamp | | | | | |

### profilesテーブル

| カラム名 | 型 | primary key | unique key | not null | foreign key | 説明 |
|---------|-----|------------|------------|----------|-------------|------|
| id | bigint | ● | | ● | | プロフィールID |
| user_id | bigint | | | ● | ● (users.id) | ユーザーID |
| avatar_paths | varchar(255) | | | | | アバター画像パス |
| usernames | varchar(20) | | | ● | | ユーザー名（表示用） |
| postal_codes | varchar(8) | | | ● | | 郵便番号 |
| addresses | varchar(255) | | | ● | | 住所 |
| building_names | varchar(255) | | | | | 建物名 |
| created_at | timestamp | | | | | |
| updated_at | timestamp | | | | | |

### ordersテーブル

| カラム名 | 型 | primary key | unique key | not null | foreign key | 説明 |
|---------|-----|------------|------------|----------|-------------|------|
| id | bigint | ● | | ● | | 注文ID |
| user_id | bigint | | | ● | ● (users.id) | 購入者ID |
| item_id | bigint | | | ● | ● (items.id) | 商品ID |
| total_amount | unsigned integer | | | ● | | 合計金額 |
| payment_status | varchar(32) | | | | | 支払い状態（paid:支払い済み、payment_pending:支払い待ち） |
| trade_status | varchar(32) | | | | | 取引状態（trading:取引中、completed:取引完了） |
| payment_method | varchar(50) | | | ● | | 支払い方法（card:クレジットカード、konbini:コンビニ） |
| shipping_address | text | | | ● | | 配送先住所 |
| comment | text | | | | | 備考 |
| buyer_last_viewed_at | timestamp | | | | | 購入者が最後にチャット画面を閲覧した時刻 |
| seller_last_viewed_at | timestamp | | | | | 出品者が最後にチャット画面を閲覧した時刻 |
| chat_access_token | varchar(64) | | | | | チャット画面アクセス用トークン（メール内リンク用） |
| chat_access_token_expires_at | timestamp | | | | | チャットアクセストークンの有効期限 |
| created_at | timestamp | | | | | |
| updated_at | timestamp | | | | | |

### category_itemsテーブル（`category_id`と`item_id`の組み合わせにユニーク制約）

| カラム名 | 型 | primary key | unique key | not null | foreign key | 説明 |
|---------|-----|------------|------------|----------|-------------|------|
| id | bigint | ● | | ● | | 中間テーブルID |
| category_id | bigint | | | ● | ● (categories.id) | カテゴリID |
| item_id | bigint | | | ● | ● (items.id) | 商品ID |
| created_at | timestamp | | | | | |
| updated_at | timestamp | | | | | |

### item_likesテーブル（`item_id`と`user_id`の組み合わせにユニーク制約）

| カラム名 | 型 | primary key | unique key | not null | foreign key | 説明 |
|---------|-----|------------|------------|----------|-------------|------|
| id | bigint | ● | | ● | | いいねID |
| item_id | bigint | | | ● | ● (items.id) | 商品ID |
| user_id | bigint | | | ● | ● (users.id) | いいねしたユーザーID |
| created_at | timestamp | | | | | |
| updated_at | timestamp | | | | | |

### messagesテーブル

| カラム名 | 型 | primary key | unique key | not null | foreign key | 説明 |
|---------|-----|------------|------------|----------|-------------|------|
| id | bigint | ● | | ● | | メッセージID |
| item_id | bigint | | | ● | ● (items.id) | 商品ID |
| user_id | bigint | | | ● | ● (users.id) | メッセージ送信者ID |
| message | text | | | ● | | メッセージ本文 |
| image_path | varchar(255) | | | | | 画像パス（添付画像がある場合） |
| created_at | timestamp | | | | | |
| updated_at | timestamp | | | | | |
| deleted_at | timestamp | | | | | ソフトデリート用（削除日時） |

### ratingsテーブル

| カラム名 | 型 | primary key | unique key | not null | foreign key | 説明 |
|---------|-----|------------|------------|----------|-------------|------|
| id | bigint | ● | | ● | | 評価ID |
| order_id | bigint | | | ● | ● (orders.id) | 注文ID |
| rater_id | bigint | | | ● | ● (users.id) | 評価する人（購入者または出品者） |
| rated_id | bigint | | | ● | ● (users.id) | 評価される人（購入者または出品者） |
| rating | unsigned tinyint | | | ● | | 評価値（1-5） |
| created_at | timestamp | | | | | |
| updated_at | timestamp | | | | | |

## ER 図

<img src="docs/images/ER.svg" width="800" alt="ER図">

ソース（編集用）: [docs/ER.dio]

<div style="border: 3px solid #333; background-color: #f5f5f5; padding: 15px; margin: 20px 0; border-radius: 5px;">
<h2 style="color: #333; font-weight: bold; margin-top: 0; border-bottom: 2px solid #333; padding-bottom: 10px;">🐳 Docker ビルド</h2>
</div>

1. `git@github.com:StudioRocha/ProTest_mock_case1.git`
2. `docker-compose up -d --build`

※補足: MySQL は OS によって起動しない場合があるため、各 PC に合わせて docker-compose.yml を編集してください。

<div style="border: 3px solid #333; background-color: #f5f5f5; padding: 15px; margin: 20px 0; border-radius: 5px;">
<h2 style="color: #333; font-weight: bold; margin-top: 0; border-bottom: 2px solid #333; padding-bottom: 10px;">🚀 Laravel 環境構築</h2>
</div>

1. コマンドライン上 PHPコンテ内に入る

```bash
 docker-compose exec php bash
```

2. PHP コンテナ上

```bash
 composer install
```

3. .env.example をcopyして.envにリネーム

4. .env に以下の環境変数を設定、又は追加
   <BR>

<span style="color: red; font-size: 12px;">Stripeを使う場合は.env STRIPE_PUBLIC_KEY/STRIPE_SECRET_KEY の値を自身の stripe アカウントのダッシュボードから取得して設定をお願いします。</span>

```text
# ===========================================
# データベース設定
# ===========================================
DB_CONNECTION=mysql
DB_HOST=mysql
DB_PORT=3306
DB_DATABASE=laravel_db
DB_USERNAME=laravel_user
DB_PASSWORD=laravel_pass

# ===========================================
# メール認証設定/応用要件
# ===========================================
# AUTO_VERIFY_ENABLED=false: "MailHog経由/コード入力なしで承認"ボタンは非表示（デフォルト）
MAIL_MAILER=smtp
MAIL_HOST=mailhog
MAIL_PORT=1025
MAIL_USERNAME=null
MAIL_PASSWORD=null
MAIL_ENCRYPTION=null
MAIL_FROM_ADDRESS=noreply@example.com
MAIL_FROM_NAME="${APP_NAME}"
AUTO_VERIFY_ENABLED=false

# ===========================================
# Stripe設定/応用要件
# ===========================================
# StripeのAPIキーは以下にPUBLIC_KEYとSECRET_KEYのそれぞれの設定をお願いいたします

STRIPE_PUBLIC_KEY=pk_test_your_publishable_key_here
STRIPE_SECRET_KEY=sk_test_your_secret_key_here
STRIPE_ENVIRONMENT=test
STRIPE_WEBHOOK_ENABLED=false

```

5. アプリケーションキーの作成
   <BR> PHP コンテナ上

```bash
php artisan key:generate
```

6. マイグレーションの実行
   <BR> PHP コンテナ上

```bash
php artisan migrate
```

7. 全キャッシュクリア（一括）任意
   <BR> PHP コンテナ上

```bash
php artisan optimize:clear

```

8. ストレージリンク作成<BR>
   <BR> PHP コンテナ上

```bash
php artisan storage:link

```

9. ブラウザで開発環境に接続

http://localhost/

もし接続時に UnexpectedValueException ページエラーが出た場合。<BR>
![Warning](https://img.shields.io/badge/Warning-Permission%20denied-red)

Windows WSL 環境で http://localhost/　接続時にファイル権限のエラーが出る場合があります。
"Permission denied"というエラーが発生した場合は、
このプロジェクトの root ディレクトリで下記のコマンドを実行してパーミッションを変更後に再接続。

```bash
sudo chmod -R 777 src/*
```

10. シーディングの実行(指定されたダミーデータの一括生成)

```bash
 php artisan db:seed
```

### 生成されるデータ

#### カテゴリデータ

-   **テーブル**: `categories`
-   **データ数**:14 件
-   **内容**:
    -   'ファッション','家電','インテリア',
        'レディース','メンズ','コスメ','本',
        'ゲーム','スポーツ','キッチン','ハンドメイド',
        'アクセサリー','おもちゃ','ベビー・キッズ'

#### 商品データ

-   **テーブル**: `items`
-   **データ数**: 10 件
-   **内容**:
    -   固定商品 10 点を事前定義（CO01～CO10）
    -   ユーザー1（田中太郎）: CO01～CO05を出品
    -   ユーザー2（佐藤花子）: CO06～CO10を出品
    -   ユーザー3（鈴木一郎）: 商品なし
    -   各商品に 1-3 個のカテゴリをランダム割り当て
    -   ローカル画像パスを使用（`images/items/`配下）
    -   サンプル商品例：
        -   CO01: 腕時計 (¥15,000) - Rolax
        -   CO02: HDD (¥5,000) - 西芝
        -   CO03: 玉ねぎ3束 (¥300)
        -   CO05: ノートPC (¥45,000)
        -   CO09: コーヒーミル (¥4,000) - Starbacks
        -   CO10: メイクセット (¥2,500)

#### ユーザーデータ

-   **テーブル**: `users`
-   **データ数**: 3 件
-   **内容**:
    -   固定ユーザー3人（全員パスワード: password）
        -   田中太郎（tanaka@example.com）
        -   佐藤花子（sato@example.com）
        -   鈴木一郎（suzuki@example.com）※何も紐づけられていない状態のユーザー
    -   全ユーザーが会員登録時のメール認証済み状態
    -   アバター画像は未登録の状態

#### プロフィールデータ

-   **テーブル**: `profiles`
-   **データ数**: 3 件
-   **内容**:
    -   各ユーザーに対応するプロフィール情報
    -   ユーザー名、郵便番号、住所、建物名を含む
    -   アバター画像は未設定（null）

### 注意事項

-   シーディングは開発・テスト環境でのみ実行してください
-   本番環境では実行しないでください
-   既存データがある場合は、事前にバックアップを取ってください
-   商品画像は外部 URL から自動ダウンロードされます

<div style="border: 3px solid #333; background-color: #f5f5f5; padding: 15px; margin: 20px 0; border-radius: 5px;">
<h2 style="color: #333; font-weight: bold; margin-top: 0; border-bottom: 2px solid #333; padding-bottom: 10px;">🧪 PHPUnitを利用したテストに関して</h2>
</div>

**テスト環境の設定について**

このアプリケーションでは、`.env.testing`ファイルは使用していません。テスト環境の設定は`phpunit.xml`ファイル内で直接定義されています。

`phpunit.xml`の`<php>`セクション内で、以下のようなテスト環境の環境変数が設定されています：

- `APP_ENV=testing`
- `DB_DATABASE=laravel_test_db`
- `DB_USERNAME=laravel_user`
- `DB_PASSWORD=laravel_pass`
- `CACHE_DRIVER=array`
- `MAIL_MAILER=array`
- その他のテスト用設定

テスト実行時は、これらの設定が自動的に適用されます。

### テスト用データベースの準備

Feature テストを実行するために、テスト用データベースの準備が必要です。

#### 1. テスト用データベースの作成

MySQL に root ユーザーで接続する

```bash
docker compose exec -it mysql mysql -uroot -p
```

パスワード(root)を入力後、以下の SQL コマンドを実行

```sql
CREATE DATABASE laravel_test_db;
```

#### 2. テスト用データベースのマイグレーション作成

> **注意**: Windows WSL 環境では下記のマイグレーションするにはパーミッション変更が必要になるケースがあります。

**事前に権限を付与する（推奨）**

![Warning](https://img.shields.io/badge/Warning-Illuminate\Database\QueryException-red)

マイグレーション実行前に、以下のコマンドを実行して権限を付与しておくことを推奨します。

MySQL に root ユーザーで接続する

```bash
docker compose exec -it mysql mysql -uroot -p
```

パスワード(root)を入力後、以下の SQL コマンドを実行

```sql
GRANT ALL PRIVILEGES ON laravel_test_db.* TO 'laravel_user'@'%';
FLUSH PRIVILEGES;
```

 php コンテナに入る

```bash
docker-compose exec php bash
```

php コンテナ上でテスト用DBに対してのマイグレーションを実行

```bash
DB_DATABASE=laravel_test_db php artisan migrate:fresh
```

### 機能テスト全ての一括実行


php コンテナ上

```bash
php artisan test --testsuite=Feature
```

### Feature テスト一覧：
※　テスト ID: 17 以降がProテストでの機能要件用のテストケースです。
1. **RegisterFeatureTest.php** - 会員登録機能（テスト ID: 1）
2. **LoginFeatureTest.php** - ログイン機能（テスト ID: 2）
3. **LogoutFeatureTest.php** - ログアウト機能（テスト ID: 3）
4. **ItemListFeatureTest.php** - 商品一覧機能（テスト ID: 4）
5. **MyListFeatureTest.php** - マイリスト機能（テスト ID: 5）
6. **ItemSearchFeatureTest.php** - 商品検索機能（テスト ID: 6）
7. **ItemDetailFeatureTest.php** - 商品詳細表示機能（テスト ID: 7）
8. **ItemLikeFeatureTest.php** - 商品いいね機能（テスト ID: 8）
9. **ItemCommentFeatureTest.php** - 商品コメント機能（テスト ID: 9）
10. **ItemPurchaseFeatureTest.php** - 商品購入機能（テスト ID: 10）
11. **PaymentMethodSelectionFeatureTest.php** - 支払い方法選択機能（テスト ID: 11）
12. **AddressChangeFeatureTest.php** - 住所変更機能（テスト ID: 12）
13. **UserProfileFeatureTest.php** - ユーザープロフィール表示機能（テスト ID: 13）
14. **UserProfileUpdateFeatureTest.php** - ユーザープロフィール更新機能（テスト ID: 14）
15. **ItemRegistrationFeatureTest.php** - 商品出品機能（テスト ID: 15）
16. **EmailVerificationFeatureTest.php** - メール認証機能（テスト ID: 16）
17. **TestID17_TradingChatViewFeatureTest.php** - 取引チャット確認機能（テスト ID: 17）
18. **TestID18_RatingAverageFeatureTest.php** - 評価平均確認機能（テスト ID: 18）
19. **TestID19_ChatMessagePostFeatureTest.php** - 取引チャット投稿機能（テスト ID: 19）
20. **TestID20_ChatMessageEditDeleteFeatureTest.php** - 取引チャット編集・削除機能（テスト ID: 20）
21. **TestID21_TradingRatingFeatureTest.php** - 取引評価機能（テスト ID: 21）
22. **TestID22_TradeCompletedEmailFeatureTest.php** - 取引完了メール送信機能（テスト ID: 22）

### 注意事項
-   上記コマンドは PHP コンテナ内で実行してください
-   テスト用データベース（`laravel_test_db`）は本番データベース（`laravel_db`）とは別です
-   `RefreshDatabase`トレイトにより、テスト実行時に自動的にデータベースがリフレッシュされます

<BR>
    
## 使用技術(実行環境)

-   php:8.2-fpm
-   Laravel 8.75
-   Laravel Fortify (認証パッケージ)
-   MySQL 8.0.26
-   mailhog

## 外部サービス・API

-   Stripe 　 18.0

**決済処理**: Stripe Checkout API を使用

#### 設定情報

-   **環境**: テスト環境（test mode）
-   **通貨**: JPY（日本円）
-   **決済方法**:
    -   クレジットカード決済
    -   コンビニ決済（Konbini）

#### API 設定ファイル

-   **設定ファイル**: `src/config/stripe.php`
-   **サービスクラス**: `src/app/Services/StripeService.php`

**注意**: `STRIPE_PUBLIC_KEY`と`STRIPE_SECRET_KEY`には Stripe アカウントの API キーを入力してください。

-   Stripe ダッシュボード → 開発者 → API キー から取得可能
-   テスト環境では `pk_test_` と `sk_test_` で始まるキーを使用 -　　開発環境では　 webhook は未使用

#### 決済フロー

1. **決済セッション作成**: `StripeService::createCheckoutSession()`
2. **Stripe Checkout 画面**: ユーザーが決済情報を入力
3. **決済完了**: `stripe.success`ルートで処理
4. **決済キャンセル**: `items.show`ルートに戻る

#### テスト用カード情報

Stripe のテストモードでは、以下のテストカード番号を使用できます。

**成功する決済（正常系）**

| カード番号          | ブランド         | CVC                     | 有効期限                | 説明                 |
| ------------------- | ---------------- | ----------------------- | ----------------------- | -------------------- |
| 4242 4242 4242 4242 | Visa             | 任意の 3 桁（例: 123）  | 未来の日付（例: 12/25） | 正常に決済が完了する |
| 5555 5555 5555 4444 | Mastercard       | 任意の 3 桁（例: 123）  | 未来の日付（例: 12/25） | 正常に決済が完了する |
| 3782 822463 10005   | American Express | 任意の 4 桁（例: 1234） | 未来の日付（例: 12/25） | 正常に決済が完了する |

**失敗する決済（異常系）**

| カード番号          | ブランド | CVC                    | 有効期限                | 説明                 |
| ------------------- | -------- | ---------------------- | ----------------------- | -------------------- |
| 4000 0000 0000 0002 | Visa     | 任意の 3 桁（例: 123） | 未来の日付（例: 12/25） | カードが拒否される   |
| 4000 0000 0000 9995 | Visa     | 任意の 3 桁（例: 123） | 未来の日付（例: 12/25） | 資金不足で拒否される |

**注意事項**

-   **CVC（セキュリティコード）**: テストモードでは任意の有効な値を使用できます
    -   Visa、Mastercard など: 任意の 3 桁の数字（例: `123`, `456`, `999`）
    -   American Express: 任意の 4 桁の数字（例: `1234`, `5678`）
-   **有効期限**: 未来の日付であれば任意の値で問題ありません（例: `12/25`, `01/30`）
-   **郵便番号**: 任意の値で問題ありません（例: `123-4567`, `000-0000`）
-   これらのカード番号は**テストモードでのみ**使用可能です。本番環境では使用できません。

#### 実装詳細

-   **モック化**: テスト時は StripeService をモック化

## URL

-   開発環境：http://localhost/
-   phpMyAdmin:：http://localhost:8080/
-   MailHog ：http://localhost:8025/
