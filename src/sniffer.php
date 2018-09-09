<?php

// Includes
$incPath = "includes";
$filesPath = [
    "directory.php",
    "load_json.php"
];
foreach ($filesPath as $filePath)
    require_once "$incPath/$filePath";

// Load data config file
$dataJson = new LoadJSON("data.json");
$dataJson->class_validation();
$dataJson->type_validation();
$configData = $dataJson->data;

// Load users config file
$usersJson = new LoadJSON("users.json", LoadJSON::ARRAY_DATA_TYPE, true);

// Further operations if file exist
if ($usersJson->data) {
    $usersJson->regex_validation();

    // Flip array keys and values for better access
    $users = array_combine(
        array_values($usersJson->data),
        array_keys($usersJson->data));
}

// Interface info
$interfaceName = $configData->interface->name;
$interfaceMac = $configData->interface->mac;

// Files path and their formats for saving
$path = force_end_slash($configData->save_to->path);
$format = $configData->save_to->format;

// Count of packets to receive each step
$packetsCount = $configData->packets_count;

// Load executables
$tcpdump = $configData->executables->tcpdump;

// Set logs configurations
$logDir = "log";
directory($logDir);
$skippedPacketsFile = "$logDir/skipped_packets.log";
$toLogSkippedPackets = $configData->logs->skipped_packets;

while (true) {
    // Array to save size of transferred packets based on MAC addresses
    $devicesInfo = [];

    /*
     * Uses TCPDump to sniff network packets.
     * Official TCPDump site: www.tcpdump.org
     * -e: To get MAC address and use them
     * -i: Sniffing this interface
     * -t: Removing timestamp from the output
     * -c: Count of packets getting in each step
     */
    $cmd = "$tcpdump -e -i $interfaceName "
        . "-t -c $packetsCount --immediate-mode";
    $output = `$cmd`;

    // Splits each packet from the output
    $packetsData = explode(PHP_EOL, $output);

    // Open the log file
    $logFile = $toLogSkippedPackets ? fopen($skippedPacketsFile, "a") : false;

    // Extracts data from each packet
    foreach ($packetsData as $packetData) {
        // Extract data
        list($macAddresses, $packetSize) = get_info($packetData);

        // Skip if there aren't two MAC addresses
        if (count((array)$macAddresses) !== 2 || $packetSize === 0) {
            log_packets($logFile, (array)$macAddresses, (int)$packetSize,
                $packetData);
            continue;
        }

        // Manipulate remote device's MAC address
        $remoteMac = $macAddresses[$macAddresses[0] === $interfaceMac
            ? 1 : 0];

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
        save_to_file($addr, $size);
}

// Extracts useful info from a packet info
function get_info(string $packetData)
{
    // Prevents emptiness of packet info
    if (empty($packetData))
        return 0;

    // Regular expressions to find MAC addresses and packet size
    $macAddressRegex = "/([\da-f]{2}[:-]){5}[\da-f]{2}/i";
    $packetSizeRegex = "/(length) \d+/i";

    // Find MAC addresses
    $macAddresses = [];
    preg_match_all($macAddressRegex, $packetData, $macAddresses, 1);

    // Find packet size
    $packetSize = [];
    preg_match($packetSizeRegex, $packetData, $packetSize);
    $packetSize = explode(" ", $packetSize[0])[1];

    /*
     * Returns all extracted data as an array.
     * The first index contains the MAC addresses,
     * and the last index is the packet size (in bytes).
     */
    return [
        $macAddresses[0],
        (int)$packetSize
    ];
}

// Saves extracted data into files, named by MAC addresses
function save_to_file(string $macAddress, int $packetsTotalSize)
{
    global $users, $path, $format;

    // Convert it to float, bytes and kilobytes are decimal
    $packetsTotalSize /= 10 ** 6;

    // Produces the filename
    directory($path);
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
function log_packets($file, array $macAddresses, int $packetSize,
    string $packetData) {
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