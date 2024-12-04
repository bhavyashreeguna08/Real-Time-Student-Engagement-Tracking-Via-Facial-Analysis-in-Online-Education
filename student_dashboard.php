<?php
session_start();

if (!isset($_SESSION['username']) || $_SESSION['role'] != 'student') {
    header("Location: login.html");
    exit();
}

$csv_file = 'session_data.csv';
$student_username = $_SESSION['username'];

// Read CSV file into an array
$data = [];
if (($handle = fopen($csv_file, 'r')) !== false) {
    // Read the first line (header)
    $header = fgetcsv($handle);
    
    // Read subsequent lines as data rows
    while (($row = fgetcsv($handle)) !== false) {
        // Check if the row belongs to the logged-in student
        if ($row[0] == $student_username) {
            $data[] = $row;
        }
    }
    fclose($handle);
}

// Calculate average engagement score for the logged-in student
$average_engagement_score = 0;
$count = 0;
foreach ($data as $row) {
    $average_engagement_score += floatval($row[3]); // Assuming average engagement score is in the fourth column
    $count++;
}
if ($count > 0) {
    $average_engagement_score /= $count;
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Student Dashboard</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-image: url('background.jpg');
            background-size: 1535px 737px;
            background-color: #87ceeb; /* Sky blue background color */
            display: flex;
            justify-content: center;
            align-items: center;
            flex-direction: column;
            height: 100vh;
            margin: 0;
            padding: 0;
        }
        .dashboard-container {
            background-color: #fff;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            text-align: center;
            max-width: 800px; /* Increased width for better table visibility */
            width: 100%;
            position: relative;
        }
        h2 {
            color: #333;
            margin-bottom: 20px;
        }
        h3 {
            color: #555;
            margin-bottom: 20px;
        }
        .view-button, .hide-button {
            background-color: #007bff;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            transition: background-color 0.3s;
            margin-bottom: 20px;
        }
        .view-button:hover, .hide-button:hover {
            background-color: #0056b3;
        }
        table {
            width: 100%; /* Full width table */
            border-collapse: collapse;
            margin-top: 20px;
            display: none; /* Initially hide the table */
        }
        table, th, td {
            border: 1px solid #ccc;
        }
        th, td {
            padding: 10px;
            text-align: center;
            color: #333;
            white-space: nowrap; /* Prevent wrapping of table cell content */
        }
        th {
            background-color: #f2f2f2;
            width: 25%; /* Equal width for each header column */
        }
        tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        tr:nth-child(odd) {
            background-color: #ffffff;
        }
        .back-button {
            background-color: #6c757d;
            color: #fff;
            border: none;
            padding: 10px 20px;
            font-size: 16px;
            border-radius: 5px;
            cursor: pointer;
            transition: background-color 0.3s;
            margin-top: 10px;
        }
        .back-button:hover {
            background-color: #5a6268;
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
    <div class="dashboard-container">
        <h2>Welcome, <?php echo htmlspecialchars($student_username); ?></h2>
        <h3>Your Attendance and Engagement Data</h3>
        <button id="view-button" class="view-button" onclick="showTable()">View Table</button>
        <button id="hide-button" class="hide-button" style="display: none;" onclick="hideTable()">Hide Table</button>
        <table id="data-table">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Login Time</th>
                    <th>Logout Time</th>
                    <th>Average Engagement Score</th>
                </tr>
            </thead>
            <tbody>
                <?php
                if (count($data) > 0) {
                    foreach ($data as $row) {
                        echo "<tr>";
                        foreach ($row as $cell) {
                            echo "<td>" . htmlspecialchars($cell) . "</td>";
                        }
                        echo "</tr>";
                    }
                } else {
                    echo "<tr><td colspan='4'>No data available</td></tr>";
                }
                ?>
            </tbody>
        </table>
        
        <canvas id="engagementChart"></canvas>

        <button class="back-button" onclick="goBack()">Back</button>
    </div>

    <script>
        function showTable() {
            document.getElementById('data-table').style.display = 'table';
            document.getElementById('view-button').style.display = 'none';
            document.getElementById('hide-button').style.display = 'inline-block';
        }

        function hideTable() {
            document.getElementById('data-table').style.display = 'none';
            document.getElementById('view-button').style.display = 'inline-block';
            document.getElementById('hide-button').style.display = 'none';
        }

        function goBack() {
            window.location.href = 'login.html'; // Redirect to the login page
        }

        // Chart.js script to create a bar chart
        var ctx = document.getElementById('engagementChart').getContext('2d');
        var myChart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: ['Average Engagement Score'],
                datasets: [{
                    label: 'Your Average Engagement Score',
                    data: [<?php echo $average_engagement_score; ?>],
                    backgroundColor: 'rgba(54, 162, 235, 0.6)',
                    borderColor: 'rgba(54, 162, 235, 1)',
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
