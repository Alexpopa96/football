import UIKit
import WebKit
import Capacitor

/// Covers the web view with the logo and a spinner until the remote site finishes its first load.
class LoaderBridgeViewController: CAPBridgeViewController {
    private let overlay = UIView()
    private var loadingObservation: NSKeyValueObservation?
    private var hasStartedLoading = false

    override func viewDidLoad() {
        super.viewDidLoad()
        showOverlay()

        loadingObservation = webView?.observe(\.isLoading, options: [.initial, .new]) { [weak self] webView, _ in
            DispatchQueue.main.async {
                guard let self else { return }
                if webView.isLoading {
                    self.hasStartedLoading = true
                } else if self.hasStartedLoading {
                    self.hideOverlay()
                }
            }
        }

        // Never trap the user behind the loader if the server is slow or down.
        DispatchQueue.main.asyncAfter(deadline: .now() + 15) { [weak self] in
            self?.hideOverlay()
        }
    }

    private func showOverlay() {
        overlay.backgroundColor = UIColor(red: 5 / 255, green: 7 / 255, blue: 13 / 255, alpha: 1)
        overlay.frame = view.bounds
        overlay.autoresizingMask = [.flexibleWidth, .flexibleHeight]

        let logo = UIImageView(image: UIImage(named: "LoaderLogo"))
        logo.contentMode = .scaleAspectFit
        logo.translatesAutoresizingMaskIntoConstraints = false

        let spinner = UIActivityIndicatorView(style: .large)
        spinner.color = .white
        spinner.startAnimating()
        spinner.translatesAutoresizingMaskIntoConstraints = false

        overlay.addSubview(logo)
        overlay.addSubview(spinner)
        view.addSubview(overlay)

        NSLayoutConstraint.activate([
            logo.centerXAnchor.constraint(equalTo: overlay.centerXAnchor),
            logo.centerYAnchor.constraint(equalTo: overlay.centerYAnchor, constant: -30),
            logo.widthAnchor.constraint(equalToConstant: 110),
            logo.heightAnchor.constraint(equalToConstant: 110),
            spinner.centerXAnchor.constraint(equalTo: overlay.centerXAnchor),
            spinner.topAnchor.constraint(equalTo: logo.bottomAnchor, constant: 40),
        ])
    }

    private func hideOverlay() {
        guard overlay.superview != nil else { return }
        loadingObservation = nil

        // Short delay so Vue has painted before the overlay fades.
        UIView.animate(withDuration: 0.35, delay: 0.25, options: .curveEaseOut, animations: {
            self.overlay.alpha = 0
        }, completion: { _ in
            self.overlay.removeFromSuperview()
        })
    }
}
