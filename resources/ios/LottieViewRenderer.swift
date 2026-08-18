import SwiftUI
import Lottie

/// Native renderer for the `<native:lottie-view>` EDGE component — plays a
/// dotLottie animation in-app. There is no built-in EDGE equivalent.
///
/// HOW THIS IS WIRED, because it is not what a plugin author would guess:
/// nativephp/mobile GENERATES the registration from this package's
/// nativephp.json `components[].ios_renderer` into
/// NativePHP/Bridge/Plugins/PluginRendererRegistration.swift, as
///
///     SwiftUIRendererRegistry.shared.register("lottie_view") {
///         AnyView(LottieViewRenderer(node: $0))
///     }
///
/// so the renderer MUST be a `View` whose memberwise init takes `node:
/// NativeUINode` — exactly like every nativephp/mobile-ui renderer. A
/// `props: [String: Any]` initialiser does not compile against the generated
/// call site, and there is nothing for a plugin to register by hand.
/// `registerNativeLottie()` below is still required, but only because
/// nativephp.json declares `ios.init_function`; it has no renderer work to do.
///
/// Props from PHP (`CodyPChristian\NativeLottie\Elements\LottieView`):
///   src  : String — bundled asset name/relative path OR an http(s) URL.
///   loop : Bool (default true).
///   size : Float — width as a fraction of the container (0.1...1.0), default 0.6.
///
/// Source resolution:
///   - http(s)://…   → `DotLottieFile.loadedFrom(url:)`, streamed.
///   - anything else → `DotLottieFile.named(<basename without extension>)`,
///     bundled at build time by `nativephp:lottie:copy-assets`, so it plays
///     with no network — which is what the Offline screen depends on.
///   - blank         → nothing at all. Screens are expected to render their own
///     static fallback rather than leave a gap here.
struct LottieViewRenderer: View {
    let node: NativeUINode

    private var src: String {
        node.props.getString("src").trimmingCharacters(in: .whitespacesAndNewlines)
    }

    private var loop: Bool {
        node.props.getBool("loop", default: true)
    }

    private var size: CGFloat {
        CGFloat(min(max(node.props.getFloat("size", default: 0.6), 0.1), 1.0))
    }

    private var a11yLabel: String {
        node.props.getString("a11y_label")
    }

    private var isRemote: Bool {
        let s = src.lowercased()
        return s.hasPrefix("http://") || s.hasPrefix("https://")
    }

    /// Bundled animations are loaded by name WITHOUT the extension
    /// (`DotLottieFile.named`): "resources/animations/offline.lottie" → "offline".
    private var bundledName: String {
        ((src as NSString).lastPathComponent as NSString).deletingPathExtension
    }

    private var loopMode: LottieLoopMode {
        loop ? .loop : .playOnce
    }

    var body: some View {
        Group {
            if src.isEmpty {
                EmptyView()
            } else if isRemote, let url = URL(string: src) {
                Lottie.LottieView {
                    try await DotLottieFile.loadedFrom(url: url)
                }
                .resizable()
                .playing(loopMode: loopMode)
                .modifier(LottieSizing(fraction: size))
            } else {
                Lottie.LottieView {
                    try await DotLottieFile.named(bundledName)
                }
                .resizable()
                .playing(loopMode: loopMode)
                .modifier(LottieSizing(fraction: size))
            }
        }
        .modifier(LottieA11yLabel(label: a11yLabel))
    }
}

/// Sizes the animation to a SQUARE of `fraction` x the screen width.
///
/// Two earlier spellings were tried on device and both failed, so the explicit
/// frame is deliberate:
///
///   1. `GeometryReader { … }` — no intrinsic size, expands greedily on BOTH
///      axes. On the Offline screen it claimed the whole flexible column and
///      shoved the title, message and button down to the bottom of the screen.
///
///   2. `.scaledToFit().containerRelativeFrame(.horizontal) { w, _ in w * f }`
///      — lays out correctly but IGNORES `f`. The frame is the right width, and
///      the animation then draws at its own intrinsic point size inside it and
///      centres, so a 100x100 composition renders 100pt wide whatever `size`
///      says. Silently wrong, which is the worst kind.
///
/// The screen width rather than the container width is the basis because a leaf
/// element cannot read its container without a GeometryReader, i.e. without
/// bug 1. In practice these screens are full-width columns, so the two differ
/// only by the column's padding — which does mean `size` values near 1.0 will
/// overflow a padded column. Keep it at or below the documented 0.1...1.0 with
/// that in mind; the 0.6 default is comfortably inside.
private struct LottieSizing: ViewModifier {
    let fraction: CGFloat

    private var side: CGFloat {
        let width = UIApplication.shared.connectedScenes
            .compactMap { $0 as? UIWindowScene }
            .first?
            .screen.bounds.width ?? 390

        return width * fraction
    }

    func body(content: Content) -> some View {
        content
            .scaledToFit()
            .frame(width: side, height: side)
    }
}

private struct LottieA11yLabel: ViewModifier {
    let label: String

    func body(content: Content) -> some View {
        if label.isEmpty {
            content
        } else {
            content.accessibilityLabel(label)
        }
    }
}

/// Declared as `ios.init_function` in nativephp.json and called from the
/// generated `registerPluginBridgeFunctions()`. It must exist for the project to
/// link, but there is deliberately nothing in it: this plugin has no bridge
/// functions, and the renderer is registered by the generated
/// PluginRendererRegistration.swift (see the note on LottieViewRenderer).
func registerNativeLottie() {
    // Intentionally empty — see above.
}
