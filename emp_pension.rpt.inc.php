<?php
// emp_pension.rpt.inc.php

// Initialize variables
$export_to_excel = false; // Set export_to_excel flag
$emp_id = $_GET['EmpNo']; // Get Employee ID from the URL parameters
$ssn = $_GET['SSN']; // Get Social Security Number from the URL parameters
$format = $_GET['f']; // Get the format from the URL parameters
if (empty($format))
    $format = 10; // Default format
$cols = 12; // Number of columns in the report

// Calculate the date for June
$fjune = date("m/d/y", strtotime('first day of june ' . date("Y", strtotime($dt))));

// Build the SQL query based on parameters
if (!empty($ssn) and empty($emp_id)) {
    $emp_id = doScalar($db, "SELECT max(emp_id) c1 FROM pension_detail WHERE soc_sec_no = '$ssn'");
}
$sql = "SELECT * FROM emp_pension_rpt('$emp_id',$format) ";

// Execute the SQL query
$result = pg_query($db, $sql);
$resultCheck = pg_num_rows($result);

// Get the current date
$curent_date = date("d-m-Y  H:n:s");

// Display the report form
echo "<form method='GET' id='reportform' name='report_form' action='" . $selfurl . "'>" . PHP_EOL .
    "<div class='noprint' style='text-align: center;font-size:1.2em;color:blue;'>" . PHP_EOL;

// Display search input for Employee ID (if needed)
if (false) {
    echo " <span style='font-weight:bold;'>Employee ID:" . rpt() . "  <input type='text' size='7' maxlength='7' id='emp_id' name='eid' onkeyup='allowintonly(this)' style='font-size:1.0em;' value='" . $emp_id . "'/>"  . PHP_EOL;
    echo "</span> &nbsp; <button style='font-size:1.0em;'>Go</button>" . PHP_EOL .
        "</div>" . PHP_EOL . "</form>" . PHP_EOL;
}

// Display report header
echo "<table border=0 cellpadding=0 cellspacing=0 style='border-collapse:collapse;'>" . PHP_EOL;
echo "<th class='alignCenter' colspan='$cols' style='background-color:white;color:black;'> Giumarra Vineyards Corporation </th>" . PHP_EOL;
echo "<tr><th class='alignCenter' colspan='$cols' style='background-color:white;color:black;'> Employee Pension Wages Report " . rpt(8) . "Printed: $curent_date</th></tr>" . PHP_EOL;

// Check if there are results in the query
if ($resultCheck > 2) {
    $i = 0; // Counter for rows

    // Loop through the result set
    while ($row = pg_fetch_assoc($result)) {
        $i++;

        // Check if it's the first row to display headers
        if ($i == 1) {
            $headers = [
                'Empl ID',
                'Year/Mo',
                'Last Name',
                'First Name',
                'Vesting',
                'Credit',
                'Hours',
                'Wages',
                'Date Hired',
                'Last Worked',
                'Birth Date',
                ''
            ];

            // Display table headers
            echo '<tr>';
            foreach ($headers as $header) {
                echo '<th>' . $header . '</th>' . PHP_EOL; // Inputting Table Headers... Names are in $headers
            }
            echo '</tr>';
            echo "<tbody style='background-color:white;font-family: Calibri, sans-serif;font-size: 12.0pt;'>" . PHP_EOL;
        }

        // Display row data
        echo $row['ln'] . PHP_EOL;
    }

    // Check if export_to_excel flag is set
    if ($export_to_excel) {
        $exl_htm = "<tr><td class='alignCenter' colspan='$cols'><p><div class='noprint'>" .
            '<a onclick="exportTableToExcel(' . "'emp_pension', 'GVC Employee Pension Report')" .
            '">' . "<button type='button'>" . 'Export To Excel</button></a>' . rpt(6) .
            '<a onclick="ExportToExcel(' . "'emp_pension', 'GVC Employee Pension Report')" . '">' . "<button type='button'>" . 'Export to Excel (Unformatted)</button></a>' . PHP_EOL;
        $exl_htm .= "</div></td></tr>" . PHP_EOL;

        echo $exl_htm;
    }

    echo "</tbody>" . PHP_EOL;
} else {
    // Display message if no results found
    echo "<tr><td colspan='$cols' style='font-weight:bold;text-align: center;font-size:18pt;'>No results for entered Employee ID</td></tr>" . PHP_EOL;
}

// Close the table
echo "</table>" . PHP_EOL;
?>
