<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Display Students</title>
<style>
    table {
        width: 100%;
        border-collapse: collapse;
    }

    table, th, td {
        border: 1px solid black;
    }

    th, td {
        padding: 8px;
        text-align: left;
    }

    tr:nth-child(even) {
        background-color: #f2f2f2; /* Light gray */
    }

    tr:nth-child(odd) {
        background-color: #ccccff; /* Light blue */
    }
</style>
</head>
<body>

<?php
include "../link.php";
if(isset($_POST['display'])){
    $roomid = $_POST['display'];
    echo "<table>
            <thead>
            <tr>
            <th>Name</th>
            <th>Class</th>
            <th>Roll No.</th>
            <th>Bench No.</th> <!-- Added Bench No. column header -->
            </tr>
            </thead>
            <tbody>";

    $display = "SELECT students.name, class.year, class.dept, class.division, students.rollno, batch.bench_no
                FROM students 
                INNER JOIN batch ON students.class = batch.class_id 
                INNER JOIN class ON batch.class_id = class.class_id 
                WHERE batch.room_id='$roomid' AND batch.startno <= students.rollno AND batch.endno >= students.rollno
                ORDER BY students.rollno ASC,class.dept ASC";

    $display_query = mysqli_query($conn, $display);
    if(mysqli_num_rows($display_query) > 0){
        $bench_number = 1; // Initialize bench number
        while($row = mysqli_fetch_assoc($display_query)){
            echo "<tr>
                    <td>".$row['name']."</td>
                    <td>".$row['year']." ".$row['dept']." ".$row['division']."</td>
                    <td>".$row['rollno']."</td>
                    <td>".$bench_number."</td> <!-- Display bench number -->
                </tr>";
            $bench_number++; // Increment bench number for the next student
        }
    }
    echo "</tbody></table>";
}
?>

</body>
</html>
