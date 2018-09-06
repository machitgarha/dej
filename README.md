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
First, clone the project (or download it). Then go to the project directory and just run:
```
$ ./dej help
``` 
If you want to install it globally and use it anywhere from the command line, you should use this way:

First, you need to copy Dej command file (i.e. the file named 'dej') to one of directories in $PATH environment. Consider you selected `/usr/local/bin` directory. Do (# means you must run as root):
```
# cp ./dej /usr/local/bin/dej
```
Then:
```
$ cd /usr/local/bin
```
Make it executable:
```
$ chmod 755 dej
```
Open it for editing:
```
# nano dej
```
You'll see this line at the beginning of the file:
```
src="./"
```
Change the 'src' variable to to directory which you cloned (or downloaded) the repository. Then, save the file and exit.

That's it! Now you can enjoy using Dej! Simply run `dej help` to get more information.