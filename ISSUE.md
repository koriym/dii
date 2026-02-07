# Dii 改善項目リスト

> セルフレビュー済み・重要度順にソート

---

## 評価基準

- **重要度**: Critical / High / Medium / Low
- **難易度**: Easy (数分) / Medium (数時間) / Hard (設計変更が必要)
- **影響範囲**: CI・ユーザー・開発者・ドキュメント

---

## Critical (即座に対応すべき)

### 1. [CI] GitHub Actions の deprecated 警告・将来の破壊的変更

**難易度: Easy**

GitHub Actions で古いバージョンを使用しており、Node.js 16 ランタイムの廃止予定により将来 CI が動作しなくなる。

**対象ファイル:**
- `.github/workflows/continuous-integration.yml`
- `.github/workflows/coding-standards.yml`
- `.github/workflows/static-analysis.yml`

**修正内容:**
```yaml
# Before
uses: actions/checkout@v1  # または @v2
uses: actions/cache@v2

# After
uses: actions/checkout@v4
uses: actions/cache@v4
```

**追加修正:** `::set-output` を `$GITHUB_OUTPUT` に変更
```yaml
# Before
run: echo "::set-output name=dir::$(composer config cache-files-dir)"

# After
run: echo "dir=$(composer config cache-files-dir)" >> $GITHUB_OUTPUT
```

---

### 2. [CI] `composer sa` スクリプトが動作しない

**難易度: Easy**

`composer.json` の `scripts.sa` で `psalm` を参照しているが、`require-dev` から `vimeo/psalm` が削除されている（コミット b19e34e）。

**選択肢:**
- A) `vimeo/psalm` を `require-dev` に再追加
- B) `scripts.sa` を削除し、CI の `static-analysis.yml` でのみ実行

**推奨:** 選択肢 A（ローカル実行も可能にするため）

---

## High (早めに対応すべき)

### 3. [CI] Static Analysis が CI パイプラインに組み込まれていない

**難易度: Easy**

`static-analysis.yml` は `workflow_dispatch` のみで、push/PR 時に自動実行されない。コード品質の自動チェックが機能していない。

**修正内容:**
```yaml
on:
  push:
  pull_request:
  workflow_dispatch:
```

---

### 4. [CI] PHP 8.4 のテストが未追加

**難易度: Easy**

PHP 8.4 が2024年11月にリリース済み。`composer.json` の `^8.0` 制約には含まれるが、CI マトリクスでテストされていない。

**修正内容:**
```yaml
php-version:
  - "7.3"
  - "7.4"
  - "8.0"
  - "8.1"
  - "8.2"
  - "8.3"
  - "8.4"  # 追加
```

---

### 5. [Test] コンソール機能のテストが存在しない

**難易度: Medium**

`DiiConsoleApplication` と `DiiConsoleCommandRunner` のユニットテストがない。コンソールコマンドへの DI 機能が正しく動作するか検証されていない。

**対応:**
- `tests/UnitTest/DiiConsoleApplicationTest.php` を新規作成
- `tests/Fake/` にコンソールコマンドの Fixture を追加

---

### 6. [Test] テストメソッド間の `@depends` による結合

**難易度: Easy**

`tests/UnitTest/DiiTest.php` でテスト間の依存関係がある。一つのテストが失敗すると後続のテストがスキップされ、問題の特定が困難になる。

**対応:**
- `@depends` を削除
- 各テストメソッドで必要な setUp を行う

---

## Medium (計画的に対応)

### 7. [Doc] README のタイポと不正確な記述

**難易度: Easy**

| 行 | 現在 | 修正後 |
|----|------|--------|
| 117 | "implemet" | "implement" |
| 119 | "is worked as well" | "works as well" |

---

### 8. [Code] `Dii::$context` のデフォルト値が未定義クラスを参照

**難易度: Medium**

`src/Dii.php:41` で `App::class` をデフォルト値としているが、`Koriym\Dii\App` は `src/` に存在しない。

**選択肢:**
- A) デフォルト値を `null` にし、未設定時に例外をスロー
- B) `src/App.php` にデフォルトの空実装を追加
- C) ドキュメントで「setContext() 必須」を明記（現状維持）

**推奨:** 選択肢 A（明示的なエラーで設定漏れを防ぐ）

---

### 9. [Code] `Dii::getGrapher()` の tmp ディレクトリがハードコード

**難易度: Medium**

`src/Dii.php:112` で `AppModule::class` のファイルパスから tmp ディレクトリを算出。ユーザーのプロジェクト構造に依存し、予期しない場所にキャッシュが作成される可能性。

**対応:**
- `Dii::setTmpDir(string $path)` メソッドを追加
- または設定で指定可能にする

---

### 10. [CI] `--no-suggest` フラグが Composer 2 で非推奨

**難易度: Easy**

`continuous-integration.yml` で使用している `--no-suggest` は Composer 2.x で無視される。警告が出る。

**修正:** フラグを削除

---

### 11. [Config] `phpcs.xml` の PHP バージョンが不整合

**難易度: Easy**

`composer.json` では `^7.1` だが、`phpcs.xml` では `70200`（7.2.0）をターゲット。

**修正:** `70100` に変更

---

### 12. [Test] `SilentAutoload::autoload()` のテストがない

**難易度: Easy**

`SilentAutoload` クラスのユニットテストが存在しない。

---

## Low (余裕があれば対応)

### 13. [Config] 空の `phpstan.neon` ファイル

**難易度: Easy**

使われていない設定ファイル。削除するか、PHPStan を導入するか決定が必要。

---

### 14. [Code] `DiiWebApplication::createController()` の暗黙的 null 返却

**難易度: Easy**

メソッド末尾に明示的な `return null;` がない。動作に影響はないが、コードの意図が不明確。

---

### 15. [Code] `class_implements()` の戻り値チェック

**難易度: Easy**

`src/Dii.php:75` で `class_implements($type)` が `false` を返す可能性の理論的な問題。実際には設定が正しければ発生しない。

**対応（任意）:**
```php
$implements = class_implements($type);
if ($implements && in_array(Injectable::class, $implements, true)) {
```

---

### 16. [Code] 非推奨 `AnnotationRegistry::registerLoader()`

**難易度: Medium**

Doctrine Annotations 2.0 で削除予定だが、Ray.Di が内部で処理する可能性が高い。Ray.Di のアップデート時に確認。

---

### 17. [Test] Integration テストの安定性

**難易度: Hard**

`tests/IntegrationTest.php` が PHP ビルトインサーバーに依存。ポート競合や起動失敗で不安定になりやすい。

**対応（任意）:**
- サーバー起動を mock に置き換え
- または HTTP クライアントのモックを使用

---

### 18. [Test] `DiiTest` で static メソッドをインスタンス経由で呼び出し

**難易度: Easy**

テストの意図は明確だが、`Dii::method()` の形式で呼ぶ方が自然。

---

## 設計上の制約 (リファクタリング検討)

以下は現在の設計に起因する制約。修正には後方互換性を破壊する変更が必要なため、メジャーバージョンアップ時に検討。

### 19. [Architecture] 全て static メソッド・グローバルステート

**難易度: Hard (BC break)**

`Dii` クラスが static プロパティでグローバル状態を保持。テスト間でステートがリークする可能性。

---

### 20. [Architecture] 親クラスのロジックをコピー

**難易度: Hard (BC break)**

`DiiWebApplication::createController()` と `DiiConsoleCommandRunner::createCommand()` が Yii のコードをコピー。Yii のアップデートに追従できないリスク。

---

### 21. [Architecture] `Injectable` マーカーインターフェースの制約

**難易度: Hard (BC break)**

サードパーティクラスに DI を適用できない。設定ベースで DI 対象を指定する仕組みがあると柔軟。

---

## 対応不要

### 22. LICENSE の著作権年

自動更新ワークフローが存在。正常動作を確認済みであれば対応不要。

### 23. `error_reporting` のスレッドセーフ性

PHP の Web リクエストは単一スレッドで処理されるため、実際の問題にはならない。

---

## 推奨対応順序

### Phase 1: CI 修正 (即座に)
1. GitHub Actions のバージョン更新 (#1)
2. psalm の依存関係修正 (#2)
3. Static Analysis を CI に追加 (#3)
4. PHP 8.4 テスト追加 (#4)
5. `--no-suggest` 削除 (#10)

### Phase 2: テスト強化
6. テストの `@depends` 削除 (#6)
7. コンソールテスト追加 (#5)
8. `SilentAutoload` テスト追加 (#12)

### Phase 3: コード・ドキュメント改善
9. README タイポ修正 (#7)
10. デフォルト context の改善 (#8)
11. tmp ディレクトリ設定 (#9)

---

## 補足: セルフレビューで除外・降格した項目

| 当初の評価 | 再評価後 | 理由 |
|-----------|---------|------|
| `class_implements` の問題 (Critical) | Low | 設定が正しければ発生しない理論上の問題 |
| スレッドセーフ性 (Medium) | 対応不要 | PHP Web リクエストは単一スレッド |
| `AnnotationRegistry` 非推奨 (Medium) | Low | Ray.Di が内部処理する可能性大 |
| 暗黙的 null 返却 (Medium) | Low | 親クラスと同じ動作で問題なし |
