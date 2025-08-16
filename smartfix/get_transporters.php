<?php
include('includes/db.php');

$lat = $_GET['lat'];
$lon = $_GET['lon'];

function haversineGreatCircleDistance($lat1, $lon1, $lat2, $lon2, $earthRadius = 6371) {
  $dLat = deg2rad($lat2 - $lat1);
  $dLon = deg2rad($lon2 - $lon1);
  $lat1 = deg2rad($lat1);
  $lat2 = deg2rad($lat2);

  $a = sin($dLat/2) * sin($dLat/2) +
       sin($dLon/2) * sin($dLon/2) * cos($lat1) * cos($lat2);
  $c = 2 * atan2(sqrt($a), sqrt(1-$a));
  return $earthRadius * $c;
}

// Example: Get all transporters from DB
$query = "SELECT * FROM transporters";
$result = mysqli_query($conn, $query);

$nearby = [];

while ($row = mysqli_fetch_assoc($result)) {
  $dist = haversineGreatCircleDistance($lat, $lon, $row['lat'], $row['lon']);
  if ($dist <= 20) { // Only show within 20km
    $row['distance'] = $dist;
    $nearby[] = $row;
  }
}

header('Content-Type: application/json');
echo json_encode($nearby);
?>
