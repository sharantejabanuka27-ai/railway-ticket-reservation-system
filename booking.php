<?php

require_once "db.php";
require_once "config/auth.php";

requireLogin();

$message = "";

$type = $_GET["type"] ?? "";
$id = $_GET["id"] ?? "";

if ($type === "train") {

    $sql = "SELECT * FROM trains WHERE train_id = $1";
    $result = pg_query_params($conn, $sql, [$id]);

} elseif ($type === "flight") {

    $sql = "SELECT * FROM flights WHERE flight_id = $1";
    $result = pg_query_params($conn, $sql, [$id]);

} else {
    die("Invalid booking type.");
}

if (!$result || pg_num_rows($result) !== 1) {
    die("Train or flight not found.");
}

$transport = pg_fetch_assoc($result);


/* Handle booking form */

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $passenger_count = (int)$_POST["passenger_count"];

    if ($passenger_count < 1) {

    $message = "Please enter at least 1 passenger.";

} else {

    // Check available seats before booking
    if ($type === "train") {

        $seat_sql = "
            SELECT 
                t.total_seats,
                COALESCE(SUM(
                    CASE 
                        WHEN b.status = 'CONFIRMED' THEN b.passenger_count
                        ELSE 0
                    END
                ), 0) AS booked_seats
            FROM trains t
            LEFT JOIN bookings b ON t.train_id = b.train_id
            WHERE t.train_id = $1
            GROUP BY t.total_seats
        ";

    } else {

        $seat_sql = "
            SELECT 
                f.total_seats,
                COALESCE(SUM(
                    CASE 
                        WHEN b.status = 'CONFIRMED' THEN b.passenger_count
                        ELSE 0
                    END
                ), 0) AS booked_seats
            FROM flights f
            LEFT JOIN bookings b ON f.flight_id = b.flight_id
            WHERE f.flight_id = $1
            GROUP BY f.total_seats
        ";
    }

    $seat_result = pg_query_params($conn, $seat_sql, [$id]);

    if (!$seat_result || pg_num_rows($seat_result) == 0) {

        $message = "Unable to check seat availability.";

    } else {

        $seat_data = pg_fetch_assoc($seat_result);

        $total_seats = (int)$seat_data["total_seats"];
        $booked_seats = (int)$seat_data["booked_seats"];
        $available_seats = $total_seats - $booked_seats;

        if ($passenger_count > $available_seats) {

            $message = "Only " . $available_seats . " seat(s) are available.";

        } else {

            // Sample fare: ₹500 per passenger
            $total_amount = $passenger_count * 500;

            $pnr = "PNR" . strtoupper(substr(uniqid(), -6));

        if ($type === "train") {

            $insert = "
                INSERT INTO bookings
                (user_id, booking_type, train_id, flight_id,
                 passenger_count, total_amount, pnr, status)
                VALUES ($1, 'TRAIN', $2, NULL, $3, $4, $5, 'CONFIRMED')
            ";

            $insert_result = pg_query_params(
                $conn,
                $insert,
                [
                    $_SESSION["user_id"],
                    $id,
                    $passenger_count,
                    $total_amount,
                    $pnr
                ]
            );

        } else {

            $insert = "
                INSERT INTO bookings
                (user_id, booking_type, train_id, flight_id,
                 passenger_count, total_amount, pnr, status)
                VALUES ($1, 'FLIGHT', NULL, $2, $3, $4, $5, 'CONFIRMED')
            ";

            $insert_result = pg_query_params(
                $conn,
                $insert,
                [
                    $_SESSION["user_id"],
                    $id,
                    $passenger_count,
                    $total_amount,
                    $pnr
                ]
            );
        }

        if ($insert_result) {

    $message = "Booking successful! Your PNR is: " . $pnr;

} else {

    $message = "Booking failed: " . pg_last_error($conn);

}

} // closes passenger availability else

} // closes seat query else

} // closes passenger_count else

} // closes POST request

?>

<!DOCTYPE html>
<html>

<head>

    <title>Make Reservation</title>

</head>

<body>

<h1>Reservation System</h1>

<h2>Make a Reservation</h2>

<?php if ($message !== ""): ?>

    <p><strong><?php echo htmlspecialchars($message); ?></strong></p>

<?php endif; ?>


<h3>
<?php

if ($type === "train") {
    echo "Train: " . htmlspecialchars($transport["train_name"]);
} else {
    echo "Flight: " . htmlspecialchars($transport["airline_name"]);
}

?>
</h3>


<?php if ($type === "train"): ?>

    <p>
        Train Number:
        <?php echo htmlspecialchars($transport["train_number"]); ?>
    </p>

    <p>
        Route:
        <?php echo htmlspecialchars($transport["source"]); ?>
        →
        <?php echo htmlspecialchars($transport["destination"]); ?>
    </p>

<?php else: ?>

    <p>
        Flight Number:
        <?php echo htmlspecialchars($transport["flight_number"]); ?>
    </p>

    <p>
        Route:
        <?php echo htmlspecialchars($transport["source"]); ?>
        →
        <?php echo htmlspecialchars($transport["destination"]); ?>
    </p>

<?php endif; ?>


<hr>


<form method="POST">

    <label>Number of Passengers:</label>

    <input
        type="number"
        name="passenger_count"
        min="1"
        max="100"
        required
    >

    <br><br>

    <button type="submit">
        Confirm Booking
    </button>

</form>


<br>

<a href="dashboard.php">
    Back to Dashboard
</a>

</body>

</html>