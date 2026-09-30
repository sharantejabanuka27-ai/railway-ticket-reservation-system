<?php

require_once "db.php";
require_once "config/auth.php";

requireLogin();

$user_id = $_SESSION["user_id"];

$message = "";

/* Cancel booking */
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $booking_id = (int) $_POST["booking_id"];

    $cancel_sql = "
        UPDATE bookings
        SET status = 'CANCELLED'
        WHERE booking_id = $1
        AND user_id = $2
        AND status = 'CONFIRMED'
    ";

    $cancel_result = pg_query_params(
        $conn,
        $cancel_sql,
        [$booking_id, $user_id]
    );

    if ($cancel_result && pg_affected_rows($cancel_result) === 1) {
        $message = "Booking cancelled successfully.";
    } else {
        $message = "Unable to cancel this booking.";
    }
}


/* Get user's bookings */
$sql = "
    SELECT 
        b.booking_id,
        b.booking_type,
        b.train_id,
        b.flight_id,
        b.booking_date,
        b.passenger_count,
        b.pnr,
        b.status,
        t.train_number,
        t.train_name,
        t.source AS train_source,
        t.destination AS train_destination,
        f.flight_number,
        f.airline_name,
        f.source AS flight_source,
        f.destination AS flight_destination
    FROM bookings b
    LEFT JOIN trains t ON b.train_id = t.train_id
    LEFT JOIN flights f ON b.flight_id = f.flight_id
    WHERE b.user_id = $1
    ORDER BY b.booking_id DESC
";

$result = pg_query_params($conn, $sql, [$user_id]);

?>

<!DOCTYPE html>
<html>

<head>
    <title>My Bookings</title>
</head>

<body>

<h1>My Bookings</h1>

<p>
    Welcome,
    <?php echo htmlspecialchars($_SESSION["full_name"]); ?>!
</p>

<hr>

<?php if ($message !== "") { ?>

    <p>
        <strong>
            <?php echo htmlspecialchars($message); ?>
        </strong>
    </p>

<?php } ?>


<?php if ($result && pg_num_rows($result) > 0) { ?>

    <table border="1" cellpadding="8">

        <tr>
            <th>PNR</th>
            <th>Type</th>
            <th>Train / Flight</th>
            <th>Route</th>
            <th>Passengers</th>
            <th>Booking Date</th>
            <th>Status</th>
            <th>Action</th>
        </tr>


        <?php while ($booking = pg_fetch_assoc($result)) { ?>

            <tr>

                <td>
                    <?php echo htmlspecialchars($booking["pnr"]); ?>
                </td>

                <td>
                    <?php echo htmlspecialchars($booking["booking_type"]); ?>
                </td>

                <td>

                    <?php

                    if ($booking["booking_type"] === "TRAIN") {

                        echo htmlspecialchars($booking["train_name"]);

                    } else {

                        echo htmlspecialchars($booking["airline_name"]);

                    }

                    ?>

                </td>

                <td>

                    <?php

                    if ($booking["booking_type"] === "TRAIN") {

                        echo htmlspecialchars($booking["train_source"]);
                        echo " → ";
                        echo htmlspecialchars($booking["train_destination"]);

                    } else {

                        echo htmlspecialchars($booking["flight_source"]);
                        echo " → ";
                        echo htmlspecialchars($booking["flight_destination"]);

                    }

                    ?>

                </td>

                <td>
                    <?php echo htmlspecialchars($booking["passenger_count"]); ?>
                </td>

                <td>
                    <?php echo htmlspecialchars($booking["booking_date"]); ?>
                </td>

                <td>
                    <?php echo htmlspecialchars($booking["status"]); ?>
                </td>

                <td>

                    <?php if ($booking["status"] === "CONFIRMED") { ?>

                        <form method="POST">

                            <input
                                type="hidden"
                                name="booking_id"
                                value="<?php echo htmlspecialchars($booking["booking_id"]); ?>"
                            >

                            <button type="submit">
                                Cancel Booking
                            </button>

                        </form>

                    <?php } else { ?>

                        -

                    <?php } ?>

                </td>

            </tr>

        <?php } ?>

    </table>

<?php } else { ?>

    <p>No bookings found.</p>

<?php } ?>


<br>

<a href="dashboard.php">Back to Dashboard</a>

</body>

</html>