<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();  // Start session only if not already active
}

include 'includes/functions.php'; 

if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');  // Redirect if not logged in
    exit();
}

// Save mood and reflection to the database
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Check if mood is set and not empty
    if (isset($_POST['mood']) && !empty($_POST['mood'])) {
        $stmt = $conn->prepare("INSERT INTO moods (user_id, mood, reflection, time, date) VALUES (?, ?, ?, ?, ?)");

        if ($stmt === false) {
            die("SQL error: " . $conn->error);  // Debug SQL error
        }

        // Capture current time and date
        $current_time = date("H:i:s");
        $current_date = date("Y-m-d");

        // Get the mood and reflection from POST data
        $mood = $_POST['mood'];
        $reflection = $_POST['reflection'];

        // Bind parameters
        $stmt->bind_param(
            "issss",
            $_SESSION['user_id'],
            $mood,
            $reflection,
            $current_time,
            $current_date
        );

        // Execute statement and check for errors
        if (!$stmt->execute()) {
            die("Execute failed: " . $stmt->error); // Print execute error
        }

        $stmt->close();

        header('Location: dashboard.php');  // Refresh after submission
        exit();
    } else {
        die("Mood is not set!"); // Handle the case where mood is not set
    }
}

// Logout functionality
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: index.php');
    exit();
}

// Fetch moods from the database for the graph
$moods = [];
$stmt = $conn->prepare("SELECT mood, date, time FROM moods WHERE user_id = ? ORDER BY date DESC");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    $moods[] = $row;
}

$stmt->close();

// Prepare data for the graph
$moodDates = [];
$moodScores = [];
foreach ($moods as $moodEntry) {
    $moodDates[] = $moodEntry['date'] . " " . $moodEntry['time'];  // Combine date and time for x-axis
    // Assign mood values
    switch ($moodEntry['mood']) {
        case 'happy':
            $moodScores[] = 8; // Example score for happy
            break;
        case 'neutral':
            $moodScores[] = 5; // Example score for neutral
            break;
        case 'sad':
            $moodScores[] = 2; // Example score for sad
            break;
        case 'angry':
            $moodScores[] = 1; // Example score for angry
            break;
        case 'excited':
            $moodScores[] = 9; // Example score for excited
            break;
        case 'anxious':
            $moodScores[] = 4; // Example score for anxious
            break;
        case 'frustrated':
            $moodScores[] = 3; // Example score for frustrated
            break;
        case 'bored':
            $moodScores[] = 2; // Example score for bored
            break;
        case 'content':
            $moodScores[] = 7; // Example score for content
            break;
        case 'confused':
            $moodScores[] = 3; // Example score for confused
            break;
        default:
            $moodScores[] = 0; // Default for unrecognized moods
            break;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mood Tracker Dashboard</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        body {
            background-image: url('dashboard.jpg'); /* Replace with your actual image path */
            background-size: cover;
            background-position: center;
        }

        .container {
            backdrop-filter: blur(10px);
            padding: 20px;
            border-radius: 15px;
            background: rgba(255, 255, 255, 0.8);
        }

        .chart-container {
            position: relative;
            height: 40vh; /* Set height for responsiveness */
            width: 80vw;  /* Adjust width */
            margin: auto;
        }
    </style>
</head>
<body>
    <div class="container mt-5">
        <h2 class="text-center">Your Mood Tracker Dashboard</h2>
        <div class="chart-container">
            <canvas id="moodChart"></canvas>
        </div>

        <form method="POST" class="mt-3">
            <div class="d-flex justify-content-around">
                <button type="submit" name="mood" value="happy" class="btn btn-light">😃</button>
                <button type="submit" name="mood" value="neutral" class="btn btn-light">😐</button>
                <button type="submit" name="mood" value="sad" class="btn btn-light">😢</button>
                <button type="submit" name="mood" value="angry" class="btn btn-light">😡</button>
                <button type="submit" name="mood" value="excited" class="btn btn-light">🤩</button>
                <button type="submit" name="mood" value="anxious" class="btn btn-light">😰</button>
                <button type="submit" name="mood" value="frustrated" class="btn btn-light">😤</button>
                <button type="submit" name="mood" value="bored" class="btn btn-light">😒</button>
                <button type="submit" name="mood" value="content" class="btn btn-light">😊</button>
                <button type="submit" name="mood" value="confused" class="btn btn-light">🤔</button>
            </div>
            <input type="text" name="reflection" class="form-control mt-2" placeholder="Reflect on your day..." required>
        </form>

        <button id="resetChart" class="btn btn-warning mt-3">Reset Chart</button>
        <a href="?logout=1" class="btn btn-danger mt-3">Logout</a>
    </div>

    <script>
        const ctx = document.getElementById('moodChart').getContext('2d');
        const moodChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: <?php echo json_encode($moodDates); ?>, // Dates and times
                datasets: [{
                    label: 'Mood Score',
                    data: <?php echo json_encode($moodScores); ?>, // Mood scores
                    backgroundColor: 'rgba(75, 192, 192, 0.2)', // Light background
                    borderColor: 'rgba(75, 192, 192, 1)',
                    borderWidth: 2,
                    fill: true,
                    pointRadius: 5,
                    pointBackgroundColor: 'rgba(255, 99, 132, 1)',
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        max: 10 // Set based on your mood scale
                    }
                }
            }
        });

        // Reset Chart functionality
        document.getElementById('resetChart').addEventListener('click', function() {
            moodChart.data.labels = []; // Clear labels
            moodChart.data.datasets[0].data = []; // Clear data
            moodChart.update(); // Update chart
        });
    </script>
</body>
</html>
