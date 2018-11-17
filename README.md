# Dej
<p><b>Note:</b> Still in development. It may have some bugs. Please open an issue if you see one.</p>

Dej is a command-line tool which can sniff all transmitted packets on an interface and save results inside files. Devices separated by MAC addresses. For instance, you can run Dej on your WiFi-related-interface to see how much network data each device used by your WiFi (in fact, Dej was created mainly for this purpose).

Dej uses PHP, tcpdump and screen to run, so you must have them installed. Because tcpdump can’t be ran without root permissions, you must give root permissions to Dej (e.g. run as sudo).

## Getting Started
The project works on such Linux systems as Ubuntu or Android well. On Windows, there isn't any support yet.

### Prerequisites
You need PHP7, tcpdump and screen tools to be able to run Dej. Their installation is really easy on Linux systems.<br/>
Also, you need to run some commands as root.

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
__Note__: Don't forget to confirm to uninstallation.