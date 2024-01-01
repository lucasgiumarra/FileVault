<script> 
    function handleRptSel(obj, clear_inputs = true) {
		let span = "opt_rpt_params";
		

        if (obj.value =="payment_recap") { 
			
			document.getElementById(span).style.display = "block";
			if (document.getElementById("EmpNo_filter") != null && clear_inputs == true)
				document.getElementById("EmpNo_filter").value = "";
			if (document.getElementById("SSN_filter") != null && clear_inputs == true)
				document.getElementById("SSN_filter").value = "";

			} else {
			
			document.getElementById(span).style.display = "none";

		}

    }
</script>
<?php  // rpt.pension.inc.php

$rowcount = 0;
$min_yr = 1955; 
$max_yr = date("Y");

// Handle year parameter
if ($_GET['year'] >= $min_yr and $_GET['year'] <= $max_yr)
	$yr = $_GET['year'];
if (empty($yr))
	$yr = 0;

// Handle name parameter  
$lname = isset($_GET['lname']) ? $_GET['lname'] : '';
$fname = isset($_GET['fname']) ? $_GET['fname'] : '';
$EmpNo = isset($_GET['EmpNo']) ? preg_replace('/[^0-9]/', '', $_GET['EmpNo']) : '';  //preg_replace('/[^0-9]/', '', $_GET['EmpNo']) : '';  
$SSN = isset($_GET['SSN']) ? $_GET['SSN'] : '';
$rept = $_GET['rpt']; 
$wh = "";
  
// Build SQL WHERE clause based on parameters
if (!empty($EmpNo)) {
	$wh .= "AND py.emp_id = lpad('" . preg_replace('/[^0-9,]/', '', $EmpNo) . "', 7, '0') "; 
}
if (!empty($SSN)) {
	$wh .= "AND py.soc_sec_no = '$SSN' ";
}

if (!empty($lname)) {
	$wh .= "AND py.emp_last_name ilike '$lname%' ";
}
if (!empty($fname)) {
	$wh .= "AND py.emp_first_name ilike '$fname%' ";
}

if (($yr >= $min_yr and $yr <= $max_yr)) {
	$wh .= "AND date_part('year',py.payment_date) = $yr ";
} 

echo "<table>" . PHP_EOL;
echo "<tbody style='background-color:white'>" . PHP_EOL .
    "<tr class='noprint'><td colspan='1' style='background-color:#d9d9d9;' class='alignCenter'>" . PHP_EOL . 
    "<form id='searchForm' action='$selfurl' method='get'>" . PHP_EOL . 
    "<input type='hidden' name='ct' value='2'><span id='opt_rpt_params' style='display:none;'>";

// Input field for year
if ($rept == 'payment_recap' or true)
	echo "Year " . getYear($yr, $min_yr, 0, " autofocus") . rpt(4);

if(!empty($EmpNo)){
	$EmpNo = substr_replace($EmpNo, '-', 3, 0); //when !empty
}

// Input fields for name parameters
echo "<label for='lname' id='lname_label'>Last Name:</label>  
		<input type='text' maxlength='35' size='16' style='text-transform:uppercase;' onkeyup='allowonlyuppercaselettersandspaces(this)' id='lname_filter' name='lname' value='$lname'/>
		<label for='fname' id='fname_label'>First Name:</label>
		<input type='text' maxlength='30' size='12' style='text-transform:uppercase;' onkeyup='allowonlyuppercaselettersandspaces(this)' id='fname_filter' name='fname' value='$fname'/>
		</span><label for='EmpNo'>Emp No:</label>  
		<input type='text' id='EmpNo_filter' name='EmpNo' maxlength='9' size='8' style='text-transform:uppercase;' onkeyup='allowintanddashonly(this)' value='$EmpNo'/>
		<label for='SSN'>SSN:</label>
		<input type='text' id='SSN_filter' name='SSN' maxlength='11' size='11' onkeyup='allowintanddashonly(this)' value='$SSN'/>
		<button class='btn' id='gobtn'>Go</button>" .  rpt(5) . 
		getListFromArray($rept, array("payment_recap","wage_history"), array("Payments Recap","Wage History"), "rpt", "-- Report --", " onchange='handleRptSel(this)'") . PHP_EOL. 
		rpt(5) . "<a target='_blank' href='?ct=2'><button type='button' class='btn' id='nutabbtn'>New Tab</button></a>
		</form></td></tr>" . PHP_EOL; // $selfurl

if ($rept == 'wage_history') {
	include_once 'emp_pension.rpt.inc.php'; // include/

} else {
	if (!empty($wh)) {
        // Build an execute  SQL query
		$sql = "SELECT *
				FROM pension_payment py
				WHERE py.seq > 0 $wh
				ORDER BY py.seq;";  
	
		$result = pg_query($db, $sql);  
		$rowcount = pg_num_rows($result);

		echo ($adminprivs >= 2500) ? ("1. rc = $rowcount, sql = $sql <p>") : "";
		$i = 0;
		if ($rowcount > 0) {
			$rpt_url = "?ct=1";
			
			if (!empty($rept)) {
				if (strpos($rpt_url, 'year=') === false and $yr > 0)
				$rpt_url .= "&year=$yr";
				if (strpos($rpt_url, 'lname=') === false and !empty($lname))
					$rpt_url .= "&lname=$lname";
				if (strpos($rpt_url, 'fname=') === false and !empty($fname))
					$rpt_url .= "&fname=$fname";
				if (strpos($rpt_url, 'EmpNo=') === false and !empty($EmpNo))
					$rpt_url .= "&EmpNo=$EmpNo";
				if (strpos($rpt_url, 'SSN=') === false and !empty($SSN))
					$rpt_url .= "&SSN=$SSN";
				if (strpos($rpt_url, 'rpt=') === false)
					$rpt_url .= "&rpt=$rept";
			}
			
			echo "<tr style='height: 18px;'><td colspan='2'>" . PHP_EOL . "<table border='0'>" . PHP_EOL;
			echo "<tr><th></th><th class='t_h'>RSN</th><th class='t_h'>Pay Date</th>";
			if ($show_ssn)
				echo "<th class='t_h'>SSN</th>";
			echo "" . PHP_EOL;
			echo "<th class='t_h'>Emp. No.</th>" . PHP_EOL;
			echo "<th class='t_h'>Last Name</th>" . PHP_EOL;
			echo "<th class='t_h'>First Name</th>" . PHP_EOL;
			echo "<th class='t_h'>Payment Ref.</th>" . PHP_EOL;
			echo "<th class='t_h'>Pension Paid Amt</th>" . PHP_EOL;
			echo "<th class='t_h'>Emp. Start</th>" . PHP_EOL;
			echo "<th class='t_h'>Emp. End</th>" . PHP_EOL;
			echo "<th class='t_h'>Comments</th>" . PHP_EOL;
			

			while ($row = pg_fetch_assoc($result)) {
				$i++;
				foreach($row as $key=>$value) {
						${$key} = $value;
				}
				$emp_id_display = ""; // #d9d9d9  med dark grey: AEAAAA, lt green: #E2EFDA, pale blue (subtots): #D9E1F2, another pale blue (total cost total): #DDEBF7
				if (!empty($emp_id))
					$emp_id_display = substr($emp_id,0,3) . "-" . substr($emp_id,-4);
				echo "<tr style='height: 18px;" . (($i % 2 == 0) ? "" : "background-color:#D9E1F2;") . "'>" . PHP_EOL;

				echo " <td class='alignRight'>" . $i . ".</td>" . PHP_EOL . 
					" <td class='alignRight'>" . htm_link($seq, ($rpt_url. "&pys=" . $seq), " style='font-size:1.0em;'", "", $adminprivs >= 0) . "</td>" . PHP_EOL . 
					" <td>" . date("m/d/y", strtotime($payment_date)) . "</td>" . PHP_EOL; 
				if ($show_ssn)
					echo " <td nowrap class='alignRight'>$soc_sec_no</td>" . PHP_EOL;
				
				echo " <td class='alignRight' nowrap>$emp_id_display</td>" . PHP_EOL;
				echo " <td class='alignRight' nowrap style='text-align:left;'>$emp_last_name</td>" . PHP_EOL;
				echo " <td class='alignRight' nowrap style='text-align:left;'>$emp_first_name</td>" . PHP_EOL;
				echo " <td class='alignRight'>$payment_reference</td>" . PHP_EOL;
				echo " <td class='alignRight'>" . number_format($pension_paid_amt, 2) . "</td>" . PHP_EOL;
				echo " <td class='alignRight'>". date("m/d/y", strtotime($employment_start)) ." </td>" . PHP_EOL;
				echo " <td class='alignRight'>". date("m/d/y", strtotime($employment_ending)) ."</td>" . PHP_EOL;
				echo " <td class='alignRight'>$comments</td>" . PHP_EOL;
				echo "</tr>" . PHP_EOL;
				
			}
			echo "</table></td></tr>" . PHP_EOL . "" . PHP_EOL;
			
			
		}
	}
}

 echo "</tbody>" . PHP_EOL;
	echo "</table><p>" . PHP_EOL;
echo "<script>handleRptSel(document.getElementById('rpt_select'), false);</script>";