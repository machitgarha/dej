# Dej
<p><b>Note:</b> Still in development. It may have some bugs. Please open an issue if you see one.</p>

Dej is a CLI tool to sniff all transmitted packets over an interface and save results in files. Devices are identified by their MAC addresses. For instance, you can run Dej on your WiFi interface (e.g. wlan0) to watch the data each device used over your WiFi (in fact, Dej was created mainly for this purpose).

Dej needs some tools to run, including PHP, tcpdump and screen, so you must have them installed. Tcpdump needs root permissions, so you must grant root permissions while starting Dej.

## Getting Started
The project works on such Linux systems as Ubuntu or Android well. On Windows, there isn't any support yet.

### Prerequisites
You need PHP7, tcpdump and screen tools to be able to run Dej. Their installation is really easy on Linux systems.<br/>
As mentioned above, you must have root permissions.

### Installing
Really simple. First, clone the project (or download it). Then go to the project directory, and just run:
```
# sh install.sh
```

### Updating
Simple, too. After installation, you can do:
```
# dej update
```

### Uninstalling
You can uninstall by running:
```
# dej uninstall
```
__Note__: Don't forget to confirm uninstallation.