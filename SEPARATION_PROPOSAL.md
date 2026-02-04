# CompiledInjector分離提案

## 目的

Ray.Compilerから**ランタイム実行部分**（CompiledInjector）を分離し、**ビルドツール部分**（Compiler）と明確に分ける。

- ランタイム → `ray/compiled-injector` (本番依存)
- ビルドツール → `ray/compiler` (開発依存)

## メリット

1. **セキュリティ向上**: コンパイル処理（リフレクション、ファイル書き込み、コード生成）を本番から除外
2. **デプロイサイズ削減**: 約35KBのビルドツールを本番から除外
3. **責務の明確化**: TypeScript/tscと同様の関係性
4. **依存関係の単純化**: Ray.Di/Aopはそのまま（変更なし）

## パッケージ構成

### ray/compiled-injector (新規)

**目的**: コンパイル済みスクリプトの実行

```
ray/compiled-injector/
├── composer.json
├── src/
│   ├── CompiledInjector.php
│   ├── ScriptInjectorInterface.php
│   ├── InjectionPoint.php
│   ├── Types.php
│   └── Exception/
│       ├── ExceptionInterface.php
│       ├── ClassNotFound.php
│       ├── Unbound.php
│       ├── ScriptFileNotFound.php
│       └── ScriptDirNotReadable.php
└── src-function/
    ├── singleton.php
    └── prototype.php
```

**composer.json**:
```json
{
  "name": "ray/compiled-injector",
  "description": "Runtime for Ray.Compiler pre-compiled dependency injection scripts",
  "keywords": ["di", "runtime"],
  "license": "MIT",
  "require": {
    "php": "^8.2",
    "ray/di": "^2.19",
    "ray/aop": "^2.18"
  },
  "autoload": {
    "psr-4": {
      "Ray\\Compiler\\": "src"
    },
    "files": [
      "src-function/singleton.php",
      "src-function/prototype.php"
    ]
  },
  "suggest": {
    "ray/compiler": "Required for compiling DI containers at build time"
  }
}
```

サイズ: **約11KB**

### ray/compiler (既存・リファクタ)

**目的**: DIコンテナのコンパイル

```
ray/compiler/
├── composer.json
├── src/
│   ├── Compiler.php
│   ├── CompileVisitor.php
│   ├── InstanceScript.php
│   ├── Scripts.php
│   ├── InjectorFactory.php
│   ├── FilePutContents.php
│   ├── CompilerModule.php
│   ├── DiCompileModule.php
│   ├── OverrideLazyModule.php
│   ├── LazyModuleInterface.php
│   ├── Code4Dependency.php
│   ├── Annotation/
│   │   └── Compile.php
│   └── Exception/
│       ├── CompileLockFailed.php
│       ├── FileNotWritable.php
│       ├── InvalidInstance.php
│       └── NotCompiled.php
└── src-deprecated/
    └── (後方互換性のため保持)
```

**composer.json**:
```json
{
  "name": "ray/compiler",
  "description": "Dependency injection compiler for Ray.Di",
  "keywords": ["di", "compiler", "codegen"],
  "license": "MIT",
  "require": {
    "php": "^8.2",
    "koriym/null-object": "^1.0",
    "ray/aop": "^2.18",
    "ray/di": "^2.19",
    "ray/compiled-injector": "^1.0"
  },
  "require-dev": {
    "ext-pdo": "*",
    "bamarni/composer-bin-plugin": "^1.4",
    "phpunit/phpunit": "^11.5"
  },
  "autoload": {
    "psr-4": {
      "Ray\\Compiler\\": ["src", "src-deprecated"]
    }
  }
}
```

サイズ: **約35KB** (本番では不要)

## アプリケーションでの使用方法

### ビルド時 (CI/CD)

```json
{
  "require": {
    "ray/compiled-injector": "^1.0"
  },
  "require-dev": {
    "ray/compiler": "^2.0"
  }
}
```

```bash
# 開発依存をインストール
composer install

# DIコンテナをコンパイル
php bin/compile.php  # Compiler使用

# 本番用依存のみインストール
composer install --no-dev

# デプロイ: コンパイル済みスクリプト + CompiledInjectorのみ
```

### 実行時

```php
use Ray\Compiler\CompiledInjector;

$injector = new CompiledInjector('/path/to/compiled-scripts');
$app = $injector->getInstance(App::class);
$app->run();
```

## 実装ステップ

### Phase 1: 新パッケージ作成 (後方互換性維持)

1. **新リポジトリ作成**: `ray-di/Ray.CompiledInjector`
2. **ファイル移動**:
   ```bash
   # Ray.Compilerから以下をコピー
   - src/CompiledInjector.php
   - src/ScriptInjectorInterface.php
   - src/InjectionPoint.php
   - src/Types.php
   - src/Exception/{ExceptionInterface,ClassNotFound,Unbound,ScriptFileNotFound,ScriptDirNotReadable}.php
   - src-function/{singleton,prototype}.php
   ```

3. **composer.json作成**: 上記の通り

4. **テスト移動**:
   ```bash
   # 必要なテストをコピー
   - tests/CompiledInjectorTest.php
   - tests/ScriptInjectorNullObjectTest.php
   - 関連Fakeクラス
   ```

5. **公開**: Packagistに `ray/compiled-injector` v1.0.0

### Phase 2: Ray.Compiler更新

1. **Ray.Compilerのcomposer.json更新**:
   ```json
   {
     "require": {
       "ray/compiled-injector": "^1.0"
     }
   }
   ```

2. **ランタイムファイル削除** (ray/compiled-injectorに移動済み):
   ```bash
   git rm src/CompiledInjector.php
   git rm src/ScriptInjectorInterface.php
   git rm src/InjectionPoint.php
   # ... 他のランタイムファイル
   ```

3. **tests更新**: ランタイム関連テストも削除 (compiled-injectorに移動済み)

4. **リリース**: `ray/compiler` v2.0.0 (破壊的変更だが依存で解決)

### Phase 3: ドキュメント更新

1. **README.md**: 両パッケージの役割を明記
2. **Migration Guide**: v1からv2への移行手順
3. **公式ドキュメント**: 使い分けの説明

## 後方互換性

### 既存ユーザー (ray/compiler v1.x使用中)

```json
{
  "require": {
    "ray/compiler": "^1.13"
  }
}
```

→ **影響なし**。v1.xは引き続きメンテナンス。

### 新規ユーザー / アップグレード

```json
{
  "require": {
    "ray/compiled-injector": "^1.0"
  },
  "require-dev": {
    "ray/compiler": "^2.0"
  }
}
```

→ **推奨構成**。セキュリティとサイズの恩恵を受ける。

## セキュリティ上の比較

### 現状 (ray/compiler v1.x)

**本番環境に含まれる:**
- ✅ CompiledInjector (必要)
- ❌ Compiler (不要だが含まれる)
- ❌ CompileVisitor (不要だが含まれる)
- ❌ InstanceScript (不要だが含まれる)
- ❌ リフレクション処理 (不要だが含まれる)
- ❌ ファイル書き込み処理 (不要だが含まれる)

**攻撃面**: 大

### 分離後 (ray/compiled-injector + ray/compiler v2.x)

**本番環境に含まれる:**
- ✅ CompiledInjector (必要)
- ✅ Ray.Di, Ray.Aop (スクリプトが使用)

**本番環境から除外:**
- ✅ Compiler (devのみ)
- ✅ コンパイル処理全般 (devのみ)

**攻撃面**: 小

## リスクと対策

### リスク1: メンテナンスコスト増加

**対策**:
- ランタイム部分は安定しており変更頻度が低い
- CIでクロスパッケージテスト

### リスク2: バージョン管理の複雑化

**対策**:
- セマンティックバージョニング厳守
- compiled-injector はマイナーバージョンのみ
- compiler は新機能時にメジャー更新

### リスク3: 移行負担

**対策**:
- v1.xは1年間メンテナンス継続
- 詳細な移行ガイド提供
- Breaking Changeは明確に文書化

## 期待される効果

1. **セキュリティ**: ⭐⭐⭐⭐⭐
   - コンパイル処理が本番から完全除外

2. **デプロイサイズ**: ⭐⭐⭐
   - 約35KB削減 (全体の約75%削減)

3. **概念の明確さ**: ⭐⭐⭐⭐⭐
   - ランタイムとビルドツールの分離

4. **パフォーマンス**: ⭐
   - ほぼ変わらず (オートロード対象が減る程度)

5. **複雑性**: ⭐⭐⭐⭐
   - シンプルな分離、依存関係は明確

## 結論

**実装を推奨します。**

- ✅ メリットが明確 (セキュリティ、サイズ、明確性)
- ✅ リスクは管理可能
- ✅ 後方互換性を保てる
- ✅ 概念的にも理にかなっている (TypeScript/tscモデル)

次のステップ: Phase 1の実装開始
