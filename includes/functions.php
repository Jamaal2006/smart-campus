<?php
// Helper functions for bookings and resources

function getAvailableResources($pdo) {
    $stmt = $pdo->prepare("SELECT * FROM resources WHERE quantity > 0");
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}