<?php
// API endpoint for availability checking
header('Content-Type: application/json');

$response = [
    'available' => true,
    'message' => 'Resource is available for booking.'
];
echo json_encode($response);