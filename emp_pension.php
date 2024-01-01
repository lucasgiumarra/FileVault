<?php 

require_once("../include/session.inc.php");

// Check if USERNAME and $userseq are empty, if yes, redirect to logout page
if (empty(USERNAME) or empty($userseq)) {
    redirectToURL(HOME_PATH."logout.php");
}
 
// Set Page Name
$pagename = "Employee Pension";

include_once '../include/dbh.inc.php';
//Check if database connection is successfull
if (!$db) {
  echo "An error occurred when connecting to the db server.\n";
  exit;
}

//Include template headers
include_once("template/header-top-min.tmpl.php"); 
?>
<style>
    * {
        font-family: Calibri, sans-serif;
    }

    .alignLeft {
        text-align: left;
    }

    .alignRight {
        text-align: right;
    }

    .alignCenter {
        text-align: center;
    }

    td {
        padding: 2px 4px;
        color: black;
        font-size: 12.0pt;
        font-weight: 400;
        font-style: normal;
        text-decoration: none;
        font-family: Calibri, sans-serif;
        white-space: nowrap;
        text-align: right;
    }

    th {
        padding: 5px 5px;
        font-family: Calibri, sans-serif;
        font-size: 18px;
        text-align: center;
        vertical-align: bottom;
    }

    select {
        font-size: 0.7rem;
    }

    @media print {
        tr.page-break {
            break-before: page;
        }

        .noprint {
            visibility: hidden;
        }

        .showonprint {
            visibility: visible;
        }
    }

    table {
        width: 85%; /* Ensure the table spans the entire width of its container */
		margin: 0 auto; 
    }
</style> 
	  
</head>
<body>
<div>
            


<?php

// Initialize variables
$export_to_excel = false;
$emp_id = $_GET['eid'];
$format = $_GET['f'];
$cols = 12;
$fjune = date("m/d/y", strtotime('first day of june ' . date("Y", strtotime($dt)) ));


// If format is not provided, set default value
if (empty($format)){
	$format = 10;
}

// SQL query to retrieve employee pension report data
$sql = "SELECT * FROM emp_pension_rpt('$emp_id',$format) ";

//Execute the sql query 
$result = pg_query($db, $sql);  
$resultCheck = pg_num_rows($result);

//Set report name and current date
$rptname = "EMPLOYEE PENSION REPORT";
$selfurl = htmlspecialchars($_SERVER["PHP_SELF"]);
$curent_date = date("d-m-Y  H:n:s");

//Output the form for entering EMployee ID
echo "<form method='GET' id='reportform' name='report_form' action='" . $selfurl . "'>" . PHP_EOL .
	"<div class='noprint' style='text-align: center;font-size:1.2em;color:blue;'>" . PHP_EOL; 
echo " <span style='font-weight:bold;'>Employee ID:" . rpt() . "  <input type='text' size='7' maxlength='7' id='emp_id' name='eid' onkeyup='allowintonly(this)' style='font-size:1.0em;' value='" . $emp_id . "'/>"  . PHP_EOL;
echo "</span> &nbsp; <button style='font-size:1.0em;'>Go</button>" . PHP_EOL . 
	 "</div>" . PHP_EOL . "</form>" . PHP_EOL; 

//Output the main table for displaying the employee pension report
echo "<table border=0 cellpadding=0 cellspacing=0 style='border-collapse:collapse;'>" . PHP_EOL;
echo "<th class='alignCenter' colspan='$cols'> Giumarra Vineyards Corporation </th>" . PHP_EOL;
echo "<tr><th class='alignCenter' colspan='$cols'> Employee Pension Wages Report " . rpt(8) . "Printed: $curent_date</th></tr>". PHP_EOL;

//Check if there are more than two rows in the result
if ($resultCheck > 2) {

	// Output table headers
	while ($row = pg_fetch_assoc($result)) {
		$i++;
		if ($i == 1) {
            // Output table headers based on $headers array
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
			
			echo '<tr>';
			foreach ($headers as $header) { 
				echo '<th>' . $header . '</th>' . PHP_EOL;  
			}
			echo '</tr>';
		}
        // Output row data
		echo $row['ln'] . PHP_EOL;
	}
    // If exporting to Excel is enabled, add export options
	if ($export_to_excel) {
		
		$exl_htm = "<tr><td class='alignCenter' colspan='$cols'><p><div class='noprint'>" . 
			'<a onclick="exportTableToExcel(' . "'emp_pension', 'GVC Employee Pension Report')" . 
			'">' . "<button type='button'>" . 'Export To Excel</button></a>' . rpt(6) . 
			'<a onclick="ExportToExcel(' . "'emp_pension', 'GVC Employee Pension Report')" . '">' . "<button type='button'>" . 'Export to Excel (Unformatted)</button></a>'. PHP_EOL;
		$exl_htm .= "</div></td></tr>" . PHP_EOL;
		
		echo $exl_htm;
	}
	
}
else {
    // Output a message if no results for the entered Employee ID
	echo "<tr><td colspan='$cols' style='font-weight:bold;text-align: center;font-size:18pt;'>No results for entered Employee ID</td></tr>" . PHP_EOL;
	
}

echo "</table>" . PHP_EOL;

?>




</div>
</body>
</html>
