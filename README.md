# AI対戦３並べ (Powered by Laravel AI SDK)

AI対戦３並べのCLI版です。

Laravel(13) AI SDKで作りました。

<img src="tic-tac-toe-against-ai.png" title="AI対戦３並べ" width="600" />

## 前提
- 対応言語：日本語
- [Git](https://git-scm.com/book/ja/v2/%E4%BD%BF%E3%81%84%E5%A7%8B%E3%82%81%E3%82%8B-Git%E3%81%AE%E3%82%A4%E3%83%B3%E3%82%B9%E3%83%88%E3%83%BC%E3%83%AB)インストール済（なくても遊べます。あればコピーと更新が楽。）
- PHP8.3CLI以降インストール済（Laravel13要件）
- [Composer v2](https://getcomposer.org/)インストール済
- Laravel AI SDK[(Text)サポート対象のAIサービス](https://laravel.com/framework/docs/13.x/ai-sdk#provider-support)が利用可能

## 使い方

このリポジトリを何らかの手段でローカルにコピーしてください。

```bash
git clone https://github.com/macocci7/tic-tac-toe-against-ai.git
```

ローカルにコピーしたリポジトリのフォルダに入ります。

```bash
cd tic-tac-toe-against-ai
```

次のコマンドで依存関係をインストールしてください。

```bash
composer install
php artisan vendor:publish --provider="Laravel\Ai\AiServiceProvider"
```

OpenAI等の有料サービスを使う場合は`.env`に[APIキーを設定](https://laravel.com/framework/docs/13.x/ai-sdk#configuration)してください。

```
OPENAI_API_KEY=sk-proj-********************************
```

Ollamaをローカルで使用する場合は設定不要です。

CLI上でコマンドで実行します。

▼コマンドの書式
```
php artisan play:tic-tac-toe [プロバイダー名] [モデル名]
```

▼コマンド例
```
php artisan play:tic-tac-toe
php artisan play:tic-tac-toe openai
php artisan play:tic-tac-toe ollama gemma3:1b
```
プロバイダー名とモデル名を省略した場合、`.env`内のAPIキーが設定されているプロバイダーが選択されます。

プロバイダー名のみでモデル名を省略した場合、一番安いモデルが選択されます。

ollamaの場合はモデル名を指定しないとエラーになります。

該当するプロバイダー、該当するモデルが無い場合はエラーになります。

## アップデートの仕方

▼依存関係のアップデート
```bash
composer update
```

▼このリポジトリの更新をローカルに反映する
```bash
git fetch origin
git pull origin main
```

▼上記２つをまとめて実行
```bash
composer update-repo
```

## LICENSE

[MIT](LICENSE)
