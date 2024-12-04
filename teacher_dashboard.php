<?php
session_start();

// Redirect to login page if session variables are not set or role is not teacher
if (!isset($_SESSION['username']) || $_SESSION['role'] != 'teacher') {
    header("Location: login.html");
    exit();
}

// Function to read CSV file and return data as an array
function read_csv($filename) {
    $rows = [];
    if (($handle = fopen($filename, "r")) !== FALSE) {
        while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
            $rows[] = $data;
        }
        fclose($handle);
    }
    return $rows;
}

// Read data from CSV file
$csv_data = read_csv("session_data.csv");

// Prepare data for average engagement scores
$engagement_scores = [];
if (count($csv_data) > 1) {
    for ($i = 1; $i < count($csv_data); $i++) {
        $engagement_scores[] = floatval($csv_data[$i][3]); // Assuming average engagement score is in the fourth column
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Teacher Dashboard</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-image: url('background.jpg');
            background-size: 1535px 737px;
            background-color: #87ceeb;
            color: #333;
            margin: 0;
            padding: 0;
        }

        .container {
            width: 80%;
            margin: 0 auto;
            padding: 20px;
            background-color: #fff;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            border-radius: 8px;
            margin-top: 50px;
        }

        h2 {
            text-align: center;
            color: #2c3e50;
        }

        h3 {
            text-align: center;
            color: #16a085;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        th, td {
            padding: 12px;
            border: 1px solid #ddd;
            text-align: center;
        }

        th {
            background-color: #16a085;
            color: #fff;
        }

        tr:nth-child(even) {
            background-color: #f2f2f2;
        }

        tr:hover {
            background-color: #ddd;
        }

        .btn {
            display: inline-block;
            padding: 15px 30px;
            font-size: 18px;
            cursor: pointer;
            text-align: center;
            text-decoration: none;
            outline: none;
            color: #fff;
            background-color: #16a085;
            border: none;
            border-radius: 15px;
            box-shadow: 0 9px #999;
            margin: 20px auto;
            display: block;
            width: 250px;
        }

        .btn:hover {
            background-color: #138d75;
        }

        .btn:active {
            background-color: #138d75;
            box-shadow: 0 5px #666;
            transform: translateY(4px);
        }

        .hide {
            display: none;
        }

        canvas {
            margin-top: 20px;
            max-width: 600px;
            margin-left: auto;
            margin-right: auto;
            display: block;
        }
    </style>

    <!-- Include Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <div class="container">
        <h2>Welcome, <?php echo $_SESSION['username']; ?></h2>
        <h3>All Students' Engagement and Attendance Data</h3>

        <button id="viewBtn" class="btn" onclick="toggleTable()">View</button>
        <button id="hideBtn" class="btn hide" onclick="toggleTable()">Hide</button>

        <table id="engagementTable" style="display:none;">
            <tr>
                <th>Name</th>
                <th>Login Time</th>
                <th>Logout Time</th>
                <th>Average Engagement</th>
            </tr>
            <?php
            if (count($csv_data) > 1) {
                // Skip the header row and loop through data
                for ($i = 1; $i < count($csv_data); $i++) {
                    $row = $csv_data[$i];
                    echo "<tr>";
                    echo "<td>" . htmlspecialchars($row[0]) . "</td>";
                    echo "<td>" . htmlspecialchars($row[1]) . "</td>";
                    echo "<td>" . htmlspecialchars($row[2]) . "</td>";
                    echo "<td>" . htmlspecialchars($row[3]) . "</td>";
                    echo "</tr>";
                }
            } else {
                echo "<tr><td colspan='4'>No data available</td></tr>";
            }
            ?>
        </table>

        <canvas id="engagementChart"></canvas>

        <form action="start_prediction.php" method="post">
            <input type="submit" value="Start Engagement Prediction" class="btn" style="width: 300px; font-size: 20px;">
        </form>

        <form action="login.html">
            <button class="btn" style="width: 250px;">Back</button>
        </form>
    </div>

    <script>
        function toggleTable() {
            var table = document.getElementById('engagementTable');
            var viewBtn = document.getElementById('viewBtn');
            var hideBtn = document.getElementById('hideBtn');

            if (table.style.display === "none") {
                table.style.display = "table";
                viewBtn.style.display = "none";
                hideBtn.style.display = "block";
            } else {
                table.style.display = "none";
                viewBtn.style.display = "block";
                hideBtn.style.display = "none";
            }
        }

        // Chart.js script to create a bar chart
        var ctx = document.getElementById('engagementChart').getContext('2d');
        var engagementScores = <?php echo json_encode($engagement_scores); ?>;
        var studentNames = <?php echo json_encode(array_column(array_slice($csv_data, 1), 0)); ?>;

        var myChart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: studentNames,
                datasets: [{
                    label: 'Average Engagement Score',
                    data: engagementScores,
                    backgroundColor: 'rgba(22, 160, 133, 0.6)',
                    borderColor: 'rgba(22, 160, 133, 1)',
                    borderWidth: 1
                }]
            },
            options: {
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });
    </script>
</body>
</html>
