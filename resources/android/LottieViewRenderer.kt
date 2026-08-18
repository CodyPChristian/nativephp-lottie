package com.codypchristian.nativephp.lottie.ui

import androidx.compose.foundation.layout.aspectRatio
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.runtime.Composable
import androidx.compose.runtime.getValue
import androidx.compose.ui.Modifier
import com.airbnb.lottie.compose.LottieAnimation
import com.airbnb.lottie.compose.LottieCompositionSpec
import com.airbnb.lottie.compose.LottieConstants
import com.airbnb.lottie.compose.animateLottieCompositionAsState
import com.airbnb.lottie.compose.rememberLottieComposition
import com.nativephp.mobile.ui.nativerender.NativeUINode

/**
 * Native renderer for the <native:lottie-view> EDGE component — plays a
 * dotLottie animation in-app. There is no built-in EDGE equivalent.
 *
 * NOT YET COMPILED OR RUN. Only the iOS half of this plugin has been built and
 * verified on a device; there is no Android build in this project to check it
 * against. The signature below is deliberately copied from nativephp/mobile-ui's
 * own Android renderers (see ActivityIndicatorRenderer.Render) because the iOS
 * side taught us the contract the hard way:
 *
 *   The plugin originally declared `Render(props: Map<String, Any?>)` and did its
 *   own registration in `registerNativeLottie()`. Both were wrong. NativePHP
 *   GENERATES the registration from nativephp.json's
 *   `components[].android_renderer` / `ios_renderer`, and it calls the renderer
 *   with the NODE, not a prop map — on iOS that produced
 *   "incorrect argument label in call (have 'node:', expected 'props:')" and the
 *   build failed outright. Android will register the same way, so it takes
 *   `(node, modifier)` like every mobile-ui renderer.
 *
 * Treat the details below as unverified until an Android build runs: the exact
 * modifier handling and the GenericProps accessor names are inferred from
 * mobile-ui, not observed.
 *
 * Props from PHP (CodyPChristian\NativeLottie\Elements\LottieView):
 *   src  : String — bundled asset name/relative path OR an http(s) URL.
 *   loop : Boolean (default true).
 *   size : Float — width as a fraction of the container (0.1..1.0), default 0.6.
 *
 * Source resolution:
 *   - http(s)://…   → LottieCompositionSpec.Url(src), streamed.
 *   - anything else → LottieCompositionSpec.Asset("animations/<basename>"),
 *     bundled at build time by nativephp:lottie:copy-assets, so it plays with no
 *     network — which is what the Offline screen depends on.
 *   - blank         → renders nothing; screens supply their own static fallback.
 */
object LottieViewRenderer {

    @Composable
    fun Render(node: NativeUINode, modifier: Modifier) {
        val p = node.props
        val src = p.getString("src").trim()

        if (src.isBlank()) {
            return // Blank src — draw nothing; the screen shows its own fallback.
        }

        val loop = p.getBool("loop", true)
        val size = p.getFloat("size", 0.6f).coerceIn(0.1f, 1f)

        val spec = if (isRemote(src)) {
            LottieCompositionSpec.Url(src)
        } else {
            LottieCompositionSpec.Asset("animations/${basename(src)}")
        }

        val composition by rememberLottieComposition(spec)
        val progress by animateLottieCompositionAsState(
            composition = composition,
            iterations = if (loop) LottieConstants.IterateForever else 1,
        )

        LottieAnimation(
            composition = composition,
            progress = { progress },
            // fillMaxWidth(fraction) + a square aspect ratio, matching what the
            // iOS renderer ended up doing. Compose sizes this correctly from the
            // parent's constraints, so it needs none of the screen-width
            // workaround the SwiftUI side required.
            modifier = modifier
                .fillMaxWidth(size)
                .aspectRatio(1f),
        )
    }

    private fun isRemote(src: String): Boolean =
        src.startsWith("http://", ignoreCase = true) || src.startsWith("https://", ignoreCase = true)

    /** Last path segment: "resources/animations/offline.lottie" → "offline.lottie". */
    private fun basename(src: String): String = src.substringAfterLast('/')
}

// The `android.init_function` entry point lives in LottieInit.kt: it has to
// sit in the package the manifest names (this file is in the `.ui`
// subpackage) and take a Context. See that file for why.
