<?php

require_once "db.php";
require_once "config/auth.php";

requireLogin();

?>

<!DOCTYPE html>
<html>
<head>
    <title>Reservation System Dashboard</title>
</head>

<body>

<h1>Reservation System Dashboard</h1>

<h2>Welcome, <?php echo htmlspecialchars($_SESSION["full_name"]); ?>!</h2>

<p>Role: <?php echo htmlspecialchars($_SESSION["role"]); ?></p>

<hr>

<h2>Available Trains</h2>

<?php

$train_sql = "
    SELECT 
        t.train_id,
        t.train_number,
        t.train_name,
        t.source,
        t.destination,
        t.departure_time,
        t.arrival_time,
        t.total_seats,
        t.total_seats - COALESCE(SUM(
            CASE 
                WHEN b.status = 'CONFIRMED' THEN b.passenger_count
                ELSE 0
            END
        ), 0) AS available_seats
    FROM trains t
    LEFT JOIN bookings b ON t.train_id = b.train_id
    GROUP BY 
        t.train_id,
        t.train_number,
        t.train_name,
        t.source,
        t.destination,
        t.departure_time,
        t.arrival_time,
        t.total_seats
    ORDER BY t.train_id
";

$train_result = pg_query($conn, $train_sql);

if ($train_result && pg_num_rows($train_result) > 0) {

    echo "<table border='1' cellpadding='8'>";

    echo "<tr>";
    echo "<th>Train No.</th>";
    echo "<th>Train Name</th>";
    echo "<th>Source</th>";
    echo "<th>Destination</th>";
    echo "<th>Departure</th>";
    echo "<th>Arrival</th>";
    echo "<th>Total Seats</th>";
    echo "<th>Available Seats</th>";
    echo "<th>Action</th>";
    echo "</tr>";

    while ($train = pg_fetch_assoc($train_result)) {

        echo "<tr>";

        echo "<td>" . htmlspecialchars($train["train_number"]) . "</td>";
        echo "<td>" . htmlspecialchars($train["train_name"]) . "</td>";
        echo "<td>" . htmlspecialchars($train["source"]) . "</td>";
        echo "<td>" . htmlspecialchars($train["destination"]) . "</td>";
        echo "<td>" . htmlspecialchars($train["departure_time"]) . "</td>";
        echo "<td>" . htmlspecialchars($train["arrival_time"]) . "</td>";
        echo "<td>" . htmlspecialchars($train["total_seats"]) . "</td>";
        echo "<td>" . htmlspecialchars($train["available_seats"]) . "</td>";

        echo "<td>";
        echo "<a href='booking.php?type=train&id=" . $train["train_id"] . "'>Book Now</a>";
        echo "</td>";

        echo "</tr>";
    }

    echo "</table>";
    echo "<br>";
    echo '<a href="my_bookings.php">My Bookings</a>';

} else {

    echo "<p>No trains found.</p>";
}