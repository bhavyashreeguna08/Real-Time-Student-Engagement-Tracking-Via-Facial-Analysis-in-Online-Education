<?php
session_start();

if (!isset($_SESSION['username']) || $_SESSION['role'] != 'administrator') {
    header("Location: login.html");
    exit();
}

$csv_file = 'session_data.csv';

// Read CSV file into an array
$data = [];
if (($handle = fopen($csv_file, 'r')) !== false) {
    // Read the first line (header)
    $header = fgetcsv($handle);
    
    // Read subsequent lines as data rows
    while (($row = fgetcsv($handle)) !== false) {
        $data[] = $row;
    }
    fclose($handle);
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Administrator Dashboard</title>
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
        .view-button, .hide-button, .back-button {
            background-color: #007bff;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            transition: background-color 0.3s;
            margin-bottom: 20px;
            text-decoration: none; /* Remove underline */
        }
        .view-button:hover, .hide-button:hover, .back-button:hover {
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
        .back-button-container {
            margin-top: 30px; /* Add margin to move the back button lower */
        }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <h2>Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?></h2>
        <h3>All Students' and Teachers' Data</h3>
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
                    echo "<tr><td colspan='5'>No data available</td></tr>";
                }
                ?>
            </tbody>
        </table>
        <div class="back-button-container">
            <a href="login.html" class="back-button">Back </a>
        </div>
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
    </script>
</body>
</html>
