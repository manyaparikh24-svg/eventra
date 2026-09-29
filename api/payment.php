<?php

include "db.php";
require_once "razorpay_config.php";

if (($_SESSION['role'] ?? '') !== 'attendee') {
    header("Location: login.php");
    exit;
}

$eventId = (int) ($_POST['event_id'] ?? 0);
$qty     = (int) ($_POST['num_tickets'] ?? 0);

$stmt = mysqli_prepare(
    $conn,
    "SELECT * FROM events
     WHERE event_id = ? AND status = 'approved'"
);

mysqli_stmt_bind_param($stmt, "i", $eventId);
mysqli_stmt_execute($stmt);

$event = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

include "header.php";

if (!$event || $qty < 1) {
    echo "<p>Something went wrong. Please start the booking again.</p>";
    include "footer.php";
    exit;
}

if ($qty > $event['seats_left']) {
    echo "<p class='error'>Not enough seats available.</p>";
    include "footer.php";
    exit;
}

$total = $qty * $event['price'];
?>

<div class="detail-box">

    <h2>Checkout</h2>

    <p>
        <?php echo htmlspecialchars($event['title']); ?>
        &mdash;
        <?php echo $qty; ?> ticket(s)
    </p>

    <p class="price">
        Total: Rs. <?php echo number_format($total, 2); ?>
    </p>

    <button type="button" id="pay-button">
        Pay Rs. <?php echo number_format($total, 2); ?>
    </button>

    <p id="payment-message" class="error"></p>

</div>


<!-- Razorpay Checkout -->
<script src="https://checkout.razorpay.com/v1/checkout.js"></script>

<script>

document.getElementById("pay-button").onclick = async function () {

    const button = document.getElementById("pay-button");
    const message = document.getElementById("payment-message");

    button.disabled = true;
    button.innerText = "Creating payment...";
    message.innerText = "";

    const formData = new FormData();

    formData.append("event_id", "<?php echo $eventId; ?>");
    formData.append("num_tickets", "<?php echo $qty; ?>");

    try {

        /* Create Razorpay order */
        const response = await fetch("create_order.php", {
            method: "POST",
            body: formData
        });

        const order = await response.json();

        if (!order.success) {
            throw new Error(order.message || "Unable to create order.");
        }

        /* Razorpay Checkout */
        const options = {

            key: order.key_id,

            amount: order.amount,

            currency: "INR",

            name: "Eventra",

            description: order.event_title,

            order_id: order.order_id,

            handler: async function (paymentResponse) {

                button.innerText = "Verifying payment...";

                const verifyData = new FormData();

                verifyData.append(
                    "razorpay_payment_id",
                    paymentResponse.razorpay_payment_id
                );

                verifyData.append(
                    "razorpay_order_id",
                    paymentResponse.razorpay_order_id
                );

                verifyData.append(
                    "razorpay_signature",
                    paymentResponse.razorpay_signature
                );

                verifyData.append(
                    "event_id",
                    "<?php echo $eventId; ?>"
                );

                verifyData.append(
                    "num_tickets",
                    "<?php echo $qty; ?>"
                );

                const verifyResponse = await fetch(
                    "verify_payment.php",
                    {
                        method: "POST",
                        body: verifyData
                    }
                );

                const result = await verifyResponse.json();

                if (result.success) {

                    window.location.href =
                        "confirmation.php?code=" +
                        encodeURIComponent(result.ticket_code);

                } else {

                    message.innerText =
                        result.message || "Payment verification failed.";

                    button.disabled = false;
                    button.innerText =
                        "Pay Rs. <?php echo number_format($total, 2); ?>";
                }
            },

            modal: {
                ondismiss: function () {

                    button.disabled = false;

                    button.innerText =
                        "Pay Rs. <?php echo number_format($total, 2); ?>";
                }
            },

            theme: {
                color: "#3399cc"
            }
        };

        const razorpay = new Razorpay(options);

        razorpay.on("payment.failed", function (response) {

            message.innerText =
                "Payment failed. Please try again.";

            button.disabled = false;

            button.innerText =
                "Pay Rs. <?php echo number_format($total, 2); ?>";
        });

        razorpay.open();

    } catch (error) {

        message.innerText = error.message;

        button.disabled = false;

        button.innerText =
            "Pay Rs. <?php echo number_format($total, 2); ?>";
    }
};

</script>

<?php include "footer.php"; ?>