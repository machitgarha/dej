<?php

require_once __DIR__ . "/../../vendor/autoload.php";

use Dej\Component\Shell;
use Dej\Component\DataValidation;
use MAChitgarha\Component\JSONFile;
use MAChitgarha\Component\Pusheh;
use Webmozart\PathUtil\Path;
use MAChitgarha\Component\JSON;
use Dej\Component\ShellOutput;

$sh = new ShellOutput();

try {
    // Load configurations and validate it
    $config = (new DataValidation(new JSONFile("config/data.json")))
        ->classValidation()
        ->typeValidation()
        ->return();

    // Load users config file and validate it
    $users = extractMacAsKeys((new DataValidation(new JSONFile("config/users.json")))
        ->classValidation()
        ->typeValidation()
        ->return()
    );

    /*
     * A simple file to send signal for ending up the process by sniffing the last file.
     * By running 'dej stop', first the TCPDump file will stop. Then, this file should be stopped,
     * however, instead of killing the process, let it to do the last step with the last created
     * TCPDump file.
    */
    $stopFile = "config/stop";
} catch (Throwable $e) {
    $sh->error($e);
}

$toLogSkippedPackets = $config->get("logs.skipped_packets");

// Interface info
$interfaceMac = $config->get("interface.mac");

// Files path and their formats for saving
$path = $config->get("save_to.path");
$format = $config->get("save_to.format");

// Set logs configurations
$logsPath = $config->get("logs.path");
$tcpdumpLogsPath = Path::join($logsPath, "tcpdump");
$skippedPacketsFile = Path::join($logsPath, "skipped_packets.log");

// TCPDump executable file
$tcpdump = $config->get("executables.tcpdump");

while (true) {
    // Search for all done packet files
    $donePacketsFiles = glob(Path::join($tcpdumpLogsPath, "packets-done*"));

    // Find the first ready packets file to be sniffed
    if (count($donePacketsFiles) > 0) {
        preg_match("#\d+$#i", $donePacketsFiles[0], $sniffIndexResult);
        $sniffIndex = $sniffIndexResult[0];
    } else
        $sniffIndex = null;

    // Stop the process if 'dej stop' request was sent
    if (file_exists($stopFile)) {
        unlink($stopFile);
        break;
    }        

    // Wait until reading raw packets
    if ($sniffIndex === null) {
        sleep(1);
        continue;
    }

    // Set up files
    $i = (int)$sniffIndex;
    // Files to remove
    $tcpdumpFile = Path::join($tcpdumpLogsPath, "tcpdump" . ($i === 0 ? "" : $i));
    $packetsDoneFile = Path::join($tcpdumpLogsPath, "packets-done$i");
    // Packets file to sniff
    $packetsFile = Path::join($tcpdumpLogsPath, "packets$i");

    // Array to save size of transferred packets based on MAC addresses
    $devicesInfo = [];

    // Splits each packet from the temporary file
    $packetsData = explode(PHP_EOL, file_get_contents($packetsFile));

    // Open the log file
    $logFile = $toLogSkippedPackets ? fopen($skippedPacketsFile, "a") : false;

    // Extracts data from each packet
    foreach ($packetsData as $packetData) {
        // Extract data
        list($macAddresses, $packetSize, $ethertype) = getPacketInfo($packetData);

        // Skip if there aren't two MAC addresses
        if (count((array)$macAddresses) < 2 || $packetSize === 0 || $ethertype === "arp") {
            logPackets($logFile, (array)$macAddresses, (int)$packetSize, $packetData);
            continue;
        }

        // Manipulate remote device's MAC address
        $remoteMac = $macAddresses[$macAddresses[0] === $interfaceMac ? 1 : 0];

        // Update devices info array
        if (isset($devicesInfo[$remoteMac]))
            $devicesInfo[$remoteMac] += $packetSize;
        elseif (!empty($remoteMac))
            $devicesInfo[$remoteMac] = $packetSize;
    }

    // Close file
    if ($logFile)
        fclose($logFile);

    // Saves the sent/received packets to files
    foreach ($devicesInfo as $addr => $size)
        saveToFile($addr, $size);

    // Remove files
    unlink($tcpdumpFile);
    unlink($packetsFile);
    unlink($packetsDoneFile);
}

// Extracts useful info from a packet info
function getPacketInfo(string $packetData) {
    // Prevents emptiness of packet info
    if (empty($packetData))
        return 0;

    // Regular expressions to find MAC addresses and packet size and packet type
    $macAddressRegex = "/([\da-f]{2}:){5}[\da-f]{2}/i";
    $packetSizeRegex = "/(length) \d+/i";
    $ethertypeRegex = "/(ethertype) \S+/i";

    // Find MAC addresses
    preg_match_all($macAddressRegex, $packetData, $macAddresses, 1);

    // Find packet size
    preg_match($packetSizeRegex, $packetData, $packetSize);
    if (empty($packetSize))
        return 0;
    $packetSize = explode(" ", $packetSize[0])[1];

    // Find ethertype
    preg_match($ethertypeRegex, $packetData, $ethertype);
    if (empty($ethertype))
        return 0;
    $ethertype = explode(" ", $ethertype[0])[1];

    /*
     * Returns all extracted data as an array.
     * The first index contains the MAC addresses,
     * the second index is the packet size (in bytes),
     * and the last one is the ethertype.
     */
    return [
        $macAddresses[0],
        (int)$packetSize,
        strtolower($ethertype)
    ];
}

// Saves extracted data into files, named by MAC addresses
function saveToFile(string $macAddress, int $packetsTotalSize) {
    global $users, $path, $format;

    // Convert it to float, bytes and kilobytes are decimal
    $packetsTotalSize /= 10 ** 6;

    // Produces the filename
    $macFilePath = $path . $macAddress . $format;
    
    $filePath = $path . ($users[$macAddress] ?? $macAddress) . $format;

    // Prevent duplicate files of one device
    $macFileLastVal = 0;
    if ($macFilePath !== $filePath && is_readable($macFilePath)) {
        $macFileLastVal = format(file_get_contents($macFilePath), false);
        unlink($macFilePath);
    }

    // If the main file exists, then get the last value
    $lastVal = 0;
    if (is_readable($filePath))
        $lastVal = format(file_get_contents($filePath), false);

    // Adds new value to the last value
    $lastVal += $macFileLastVal + $packetsTotalSize;

    // Updates the file
    $file = fopen($filePath, "w");
    fwrite($file, format($lastVal));
    fclose($file);
}

// Formats a number into a better human readable one
function format(string $num, bool $addColons = true) {
    if ($addColons) {
        // Get decimal part with leading zero in bytes for larger number support
        $decPart = substr((string)sprintf("%.6f", $num - floor($num)), 2, 6);

        return number_format(floor($num) . $decPart);
    }

    // Split number into parts
    $numParts = explode(",", $num);
    $countNumParts = count($numParts);

    // Extract Megabytes
    $mbPart = "";
    $i = 0;
    for (; $i < $countNumParts - 2; $i++)
        $mbPart .= $numParts[$i];
    
    // Extract bytes
    $bytesPart = "";
    for (; $i < $countNumParts; $i++)
        $bytesPart .= sprintf('%03d', $numParts[$i]);

    return (float)("$mbPart.$bytesPart");
}

// Log skipped packets
function logPackets($file, array $macAddresses, int $packetSize, string $packetData) {
    global $toLogSkippedPackets;
    
    // Return if logging skipped packets is disabled or if the file is wrong
    if (!$toLogSkippedPackets || !is_resource($file))
        return;
        
    // Output MAC addresses
    $output = "Extracted MAC addresses were: ";
    $macAddressesLast = count($macAddresses) - 1;
    foreach ($macAddresses as $macAddress)
        $output .= "$macAddress" . ($macAddress ===
            $macAddresses[$macAddressesLast] ? PHP_EOL : ", ");
    
    // Output packet size
    $output .= "Extracted packet size was: $packetSize (bytes)" . PHP_EOL;

    // Output the whole packet data
    $output .= "The whole packet data was:" . PHP_EOL;
    $output .= $packetData . PHP_EOL . PHP_EOL;

    // Write to the file
    fwrite($file, $output);
}

function extractMacAsKeys(JSON $usersData): array
{
    $usersData->getDataAsArray();

    $users = [];
    foreach ($usersData->iterate() as $user)
        if ($user !== null)
            foreach ((array)$user->mac as $mac)
                $users[$mac] = $user->name;    
    
    return $users;
}
