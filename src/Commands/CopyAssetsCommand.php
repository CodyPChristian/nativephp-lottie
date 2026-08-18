<?php

namespace CodyPChristian\NativeLottie\Commands;

use Native\Mobile\Plugins\Commands\NativePluginHookCommand;

/**
 * Bundles the app's in-app Lottie animations into the native project so
 * `<native:lottie-view src="...">` can play them with NO network (the Offline
 * page depends on this).
 *
 * Runs as the plugin's `copy_assets` build hook. Every `.lottie` under the app's
 * resources/animations/ is deployed:
 *   - iOS     → NativePHP/Resources/animations/<name>.lottie  (dotLottie v2 is
 *               auto-converted to v1 so lottie-spm can read it)
 *   - Android → app/src/main/assets/animations/<name>.lottie
 *
 * The renderer loads a bundled animation by basename (iOS DotLottieFile.named /
 * Android LottieCompositionSpec.Asset("animations/<name>")); an http(s) `src`
 * bypasses this entirely and streams from the URL.
 *
 * Modelled on s2br/nativephp-mobile-splashscreen's CopyAssetsCommand.
 */
class CopyAssetsCommand extends NativePluginHookCommand
{
    protected $signature = 'nativephp:lottie:copy-assets';

    protected $description = 'Bundle in-app Lottie (.lottie) animations into the native project';

    public function handle(): int
    {
        $sourceDir = base_path('resources/animations');

        if (! is_dir($sourceDir)) {
            $this->info('No resources/animations directory — nothing to bundle.');

            return self::SUCCESS;
        }

        $paths = glob($sourceDir.'/*.lottie') ?: [];

        if (empty($paths)) {
            $this->warn('No .lottie files in resources/animations — skipping. Add offline.lottie / error.lottie to enable in-app animations.');

            return self::SUCCESS;
        }

        foreach ($paths as $sourcePath) {
            $this->info('Deploying: '.basename($sourcePath));

            if ($this->isIos()) {
                $this->deployToIos($sourcePath);
            }

            if ($this->isAndroid()) {
                $this->deployToAndroid($sourcePath);
            }
        }

        return self::SUCCESS;
    }

    protected function deployToIos(string $sourcePath): void
    {
        $animDir = $this->buildPath().'/NativePHP/Resources/animations';
        $this->ensureDirectory($animDir);

        $animationName = pathinfo($sourcePath, PATHINFO_FILENAME);
        $destPath = $animDir.'/'.$animationName.'.lottie';

        if ($this->isV2Format($sourcePath)) {
            $this->info('Detected dotLottie v2 — converting to v1 for lottie-spm...');

            if ($this->convertToV1($sourcePath, $destPath)) {
                $this->info("iOS: v1 animation → {$animationName}.lottie");
            } else {
                $this->error('v2→v1 conversion failed. Copying original (may not play on iOS).');
                copy($sourcePath, $destPath);
            }
        } else {
            copy($sourcePath, $destPath);
            $this->info("iOS: animation → {$animationName}.lottie");
        }
    }

    protected function deployToAndroid(string $sourcePath): void
    {
        $assetsDir = $this->buildPath().'/app/src/main/assets/animations';
        $this->ensureDirectory($assetsDir);

        $filename = pathinfo($sourcePath, PATHINFO_BASENAME);
        $destPath = $assetsDir.'/'.$filename;

        copy($sourcePath, $destPath);
        $this->info("Android: animation → {$filename}");
    }

    /**
     * Returns true if the .lottie file uses dotLottie v2 manifest format.
     * v2 uses manifest version "2" and stores the animation at a/<name>.json.
     * lottie-spm on iOS only supports v1 (manifest version "1.0", animations/main.json).
     */
    protected function isV2Format(string $path): bool
    {
        $zip = new \ZipArchive;
        if ($zip->open($path) !== true) {
            return false;
        }

        $manifest = $zip->getFromName('manifest.json');
        $zip->close();

        if (! $manifest) {
            return false;
        }

        $data = json_decode($manifest, true);

        return ($data['version'] ?? '1.0') !== '1.0';
    }

    /**
     * Converts a dotLottie v2 archive to the v1 format lottie-spm understands.
     *
     * v1 format requirements:
     *   - manifest.json: {"version":"1.0","animations":[{"id":"main","mode":"normal","direction":1}]}
     *   - animations/main.json: the Lottie animation JSON
     *
     * Also strips features that crash lottie-spm (fonts, layer effects, hasMask,
     * text layers, and a Background layer).
     */
    protected function convertToV1(string $sourcePath, string $destPath): bool
    {
        $zip = new \ZipArchive;
        if ($zip->open($sourcePath) !== true) {
            return false;
        }

        $animEntry = null;
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $name = $zip->getNameIndex($i);
            if (str_starts_with($name, 'a/') && str_ends_with($name, '.json')) {
                $animEntry = $name;
                break;
            }
        }

        if (! $animEntry) {
            $this->warn('Could not locate animation JSON in v2 archive.');
            $zip->close();

            return false;
        }

        $animJson = $zip->getFromName($animEntry);
        $zip->close();

        if (! $animJson) {
            return false;
        }

        $data = json_decode($animJson, true);

        unset($data['fonts']);
        foreach ($data['layers'] ?? [] as &$layer) {
            unset($layer['ef'], $layer['hasMask']);
        }
        unset($layer);

        $data['layers'] = array_values(array_filter(
            $data['layers'] ?? [],
            fn ($l) => ($l['nm'] ?? '') !== 'Background' && ($l['ty'] ?? 0) !== 5
        ));

        $data['nm'] = 'main';

        $manifest = json_encode([
            'version' => '1.0',
            'animations' => [['id' => 'main', 'mode' => 'normal', 'direction' => 1]],
        ]);

        $out = new \ZipArchive;
        if ($out->open($destPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== true) {
            return false;
        }

        $out->addFromString('manifest.json', $manifest);
        $out->addFromString('animations/main.json', json_encode($data));
        $out->close();

        return true;
    }
}
