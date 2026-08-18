<?php

namespace CodyPChristian\NativeLottie\Elements;

use Native\Mobile\Edge\CallbackRegistry;
use Native\Mobile\Edge\Element;

/**
 * In-app Lottie animation (the `<native:lottie-view>` EDGE component). Plays a
 * dotLottie animation natively — from a bundled asset (offline-safe) or a remote
 * URL — via the LottieViewRenderer (Compose / SwiftUI). There is no built-in EDGE
 * equivalent, which is why this bespoke plugin exists (authored like
 * qikcms/native-ui's ImageCompare).
 *
 * Fluent API mirrors nativephp/native-ui Elements:
 *   LottieView::make()->src('resources/animations/offline.lottie')->loop(true)->size(0.6);
 *
 * Props resolved to the native renderer:
 *   src  : String — a bundled asset name/relative path (e.g. "offline.lottie" or
 *          "resources/animations/offline.lottie") OR an http(s) URL. The renderer
 *          picks bundled-asset vs. URL loading from the value.
 *   loop : Bool   — loop indefinitely (default true) or play once.
 *   size : Float  — width as a fraction of the container (0.1–1.0).
 */
class LottieView extends Element
{
    protected string $type = 'lottie_view';

    /** @var array<string, mixed> */
    protected array $props = [];

    public static function make(): static
    {
        return new static;
    }

    public function src(string $src): static
    {
        $this->props['src'] = $src;

        return $this;
    }

    public function loop(bool $loop = true): static
    {
        $this->props['loop'] = $loop;

        return $this;
    }

    /** Width as a fraction of the container, 0.1–1.0. */
    public function size(float $value): static
    {
        $this->props['size'] = max(0.1, min(1.0, $value));

        return $this;
    }

    public function applyAttributes(array $attrs): void
    {
        if (isset($attrs['src'])) {
            $this->props['src'] = $attrs['src'];
        }
        if (isset($attrs['loop'])) {
            $this->props['loop'] = filter_var($attrs['loop'], FILTER_VALIDATE_BOOLEAN);
        }
        if (isset($attrs['size'])) {
            $this->size((float) $attrs['size']);
        }

        $this->applyA11yAttributes($attrs);
    }

    protected function resolveProps(CallbackRegistry $registry): array
    {
        return $this->props + ['loop' => true];
    }
}
