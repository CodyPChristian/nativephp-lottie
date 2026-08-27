# nativephp-lottie

An in-app **Lottie EDGE component** for [NativePHP Mobile](https://nativephp.com)
v4. Adds a `<native:lottie-view>` Blade tag that plays a
[dotLottie](https://dotlottie.io) animation **natively** — Jetpack Compose
(`lottie-compose`) on Android, SwiftUI + `lottie-spm` on iOS — from either a
**bundled asset** (offline-safe) or a **remote URL**.

Built as a static-renderer plugin (not code-generation): a `components[]` entry
in `nativephp.json` registers the element and its Blade tag, and
`resources/android` / `resources/ios` ship the native renderers.

## Usage

```blade
{{-- Bundled asset (plays with no network) --}}
<native:lottie-view src="resources/animations/loading.lottie" :loop="true" :size="0.6" a11y-label="Loading" />

{{-- Remote URL (streams over the network) --}}
<native:lottie-view src="https://example.com/anim.lottie" :loop="false" :size="0.5" />
```

### Props

| prop   | type   | default | notes |
| ------ | ------ | ------- | ----- |
| `src`  | string | —       | Bundled asset name / relative path, OR an `http(s)` URL. Null or blank renders nothing. |
| `loop` | bool   | `true`  | Loop indefinitely, or play once. |
| `size` | float  | `0.6`   | Width as a fraction of the container (0.1–1.0). |

## Bundled vs. URL source

The renderer decides how to load the animation from the `src` value:

- **`http(s)://…`** → streamed from the URL
  (Android `LottieCompositionSpec.Url`, iOS `DotLottieFile.loadedFrom(url:)`).
- **anything else** → loaded from the bundled asset by **basename**
  (Android `LottieCompositionSpec.Asset("animations/<name>")`, iOS
  `DotLottieFile.named("<name>")`).

Bundled animations are copied into the native project at build time by the
`copy_assets` hook command (`nativephp:lottie:copy-assets`), which deploys every
`.lottie` file found under the app's `resources/animations/` directory
(dotLottie v2 is auto-converted to v1 for `lottie-spm` on iOS).

## Native dependencies

The plugin declares its own native dependencies via `nativephp.json`, so no
manual Gradle/SPM wiring is required:

- **Android** — `com.airbnb.android:lottie-compose` (min SDK 26).
- **iOS** — the `lottie-spm` Swift package (`Lottie` product, min iOS 18.0),
  pinned to 4.6.1 (`upToNextMajor`). That version is airbnb/lottie-spm's own
  release series and is unrelated to this plugin's version — pinning it to the
  plugin version makes Xcode fail the build with "Could not resolve package
  dependencies: no versions of 'lottie-spm' match the requirement".

## Install

```bash
composer require codypchristian/nativephp-lottie
php artisan native:plugin:register codypchristian/nativephp-lottie
php artisan native:plugin:validate
```

## Device build required to confirm playback

Static PHP/compile checks pass on the dev machine, but actual **Lottie
playback** and **asset bundling** can only be verified in a native
device/simulator build (`php artisan native:run …`). The renderers' registry
wiring (`registerNativeLottie`) follows the installed `nativephp/mobile`
generation's contract.

## Author & license

Authored by Cody of QikSolutions, LLC. Released under the **MIT License** — see
[`LICENSE.md`](LICENSE.md).
