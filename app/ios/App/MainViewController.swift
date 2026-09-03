import Capacitor
import Network
import UIKit

class MainViewController: CAPBridgeViewController {
    private var pathMonitor: NWPathMonitor?
    private var showingOffline = false

    override func capacitorDidLoad() {
        super.capacitorDidLoad()
        startMonitoring()
    }

    private func startMonitoring() {
        let monitor = NWPathMonitor()
        pathMonitor = monitor

        monitor.pathUpdateHandler = { [weak self] path in
            DispatchQueue.main.async {
                self?.handlePathUpdate(isConnected: path.status == .satisfied)
            }
        }

        monitor.start(queue: DispatchQueue(label: "me.biblingo.app.network-monitor"))
    }

    private func handlePathUpdate(isConnected: Bool) {
        if !isConnected {
            loadOfflinePage()
        } else if showingOffline {
            reloadRemote()
        }
    }

    private func loadOfflinePage() {
        guard !showingOffline, let webView = webView else { return }
        guard let url = Bundle.main.url(forResource: "offline", withExtension: "html", subdirectory: "public") else { return }

        showingOffline = true
        webView.stopLoading()
        webView.loadFileURL(url, allowingReadAccessTo: url.deletingLastPathComponent())
    }

    private func reloadRemote() {
        guard let serverURL = bridge?.config.serverURL else { return }

        showingOffline = false
        webView?.load(URLRequest(url: serverURL))
    }
}
