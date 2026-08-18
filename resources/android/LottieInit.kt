package com.codypchristian.nativephp.lottie

import android.content.Context

/**
 * The `android.init_function` declared in nativephp.json, called once at
 * startup by NativePHP's generated PluginBridgeFunctionRegistration.
 *
 * Two things about this file are load-bearing and were both wrong before, in
 * ways that only surface when the Android app is actually compiled:
 *
 *   THE PACKAGE MUST MATCH `init_function` EXACTLY. The generated file writes
 *   `import com.codypchristian.nativephp.lottie.registerNativeLottie` straight
 *   from the manifest string. The renderer lives in the `.ui` subpackage, and
 *   declaring the function there put it one segment below where the import
 *   looks.
 *
 *   IT MUST TAKE A Context. The generated call is
 *   `registerNativeLottie(context)`, so a no-argument function does not
 *   resolve even when the package is right.
 *
 * Both produce the same "Unresolved reference 'registerNativeLottie'" against
 * a generated file, which points at NativePHP rather than at this plugin.
 * Keeping the init in its own file, named after what it is, makes the contract
 * visible instead of buried at the bottom of the renderer. This mirrors
 * mobile-ui's NativeUIChromeInit.kt, which is the working reference.
 *
 * The body is deliberately empty. This plugin registers no bridge functions --
 * the renderer is picked up by NativePHP's generated renderer registration,
 * the same split already proven on iOS. The function exists so the generated
 * registration links.
 */
@Suppress("UNUSED_PARAMETER")
fun registerNativeLottie(context: Context) {
    // Intentionally empty — see above.
}
