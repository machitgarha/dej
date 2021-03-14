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

Download the latest release from Phar file in GitHub releases page. After that, change the file's permissions:
```
sudo chmod 755 ./dej.phar
```

Now, install Dej by running:
```
sudo ./dej.phar install
```

#### Make the Phar

If you don't want to download the Phar or you want to make the Phar for other purposes (e.g. development), you have to make the Phar. To perform this action, you need to clone the project. Then, go to the cloned project and run:

```
sudo php ./src/make-phar.php
```

The Phar file will be made in the root directory as `dej.phar`. Now, you can install Dej using the Phar.

### Updating

Currently, there is no automated method for updating Dej. However, you can download a release and install it. For installing a different version, you need to use --force option.

### Uninstalling

Uninstalling can be done by:
```
sudo dej uninstall
```

## License

Dej is licensed under [GPLv3](./LICENSE.md).
