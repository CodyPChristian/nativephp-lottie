<?php

namespace CodyPChristian\NativeLottie\Components;

use Native\Mobile\Edge\Components\Native\NativeBladeComponent;
use Native\Mobile\Edge\NativeElementCollector;

/**
 * Blade tag for the in-app Lottie component:
 *   <native:lottie-view src="{{ $path }}" :loop="true" :size="0.6" a11y-label="Loading" />
 *
 * Emits a leaf node the native LottieViewRenderer picks up. In practice the
 * `<native:lottie-view>` precompiler path (NativeTagPrecompiler) emits the leaf
 * directly; this class backs the `<x-native-lottie-view>` Blade-component alias
 * the SDK registers from the manifest's `components[]` entry.
 */
class LottieView extends NativeBladeComponent
{
    protected bool $handlesCollectorManually = true;

    protected function elementType(): string
    {
        return 'lottie_view';
    }

    public function render(): \Closure
    {
        return function (array $data) {
            $attrs = $data['attributes']->getAttributes();

            NativeElementCollector::leaf($this->elementType(), $attrs);

            return '';
        };
    }
}
