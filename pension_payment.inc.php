
<?php
// pension_payment.inc.php

// Initialize the HTML string for pension payment list
$pen_pay_list_htm = "";

// Check if the form is submitted using POST method
if ($_SERVER["REQUEST_METHOD"] == "POST") { 
	
    // Check if the form name matches
	if ($_POST["form_name"] == "pension_payment_entry") {
		$py_seq = 0;
		$currtab = 1;
		$data = array();

        // Call the function to save the pension payment record and retrieve any errors
		$saveErr = savePensionPaymentRecord($db, $data);

        // If there is data returned, extract it
		if (!empty($data)) {
			extract($data);
			$py_seq = $seq;
		}
		// Display an error message if there is an error during save
		if (!empty($saveErr)) {
			echo "<tr><td colspan='$cols' style='background-color:white; color:orange;' class='alignCenter'>$saveErr</td></tr>";
		} 
        // Redirect to a specific page if needed 
		if (empty($debug) and $py_seq > 0 and false)
			$js_at_end = '<script>window.location.href="?ct=1&pys=' . $py_seq . '"</script>';  // window.location.href="?ls=$labor_seq";
			
	}	
}

// Initialize a flag for showing previous payments
$show_prev_payments = false;

// Check if the function is "pplu" and EmpNo or keyval is not empty
if ($fn == "pplu" and (!empty($EmpNo) or !empty($_GET['keyval']))) {
	$pen_pay_list_htm = pen_payment_hist_htm($db, $py_seq, $_GET['keyval'], $show_prev_payments, $_GET['key']);
	
} elseif (empty($py_seq) and false) {
	$py_seq = doScalar($db, "SELECT max(seq) c1 FROM pension_payment");
}

// Display the HTML table structure
echo "<table border='0' cellpadding='1' cellspacing='0'>" . PHP_EOL;
echo "<tr class='noprint'><td>&nbsp;</td>" . PHP_EOL . "</tr>" . PHP_EOL;

// Display the pension payment form and list
echo "<tr><td>" . pen_payment_form_htm($db, $py_seq, $show_ssn, $selfurl, $pen_pay_list_htm) . "</td></tr>" . PHP_EOL;
echo "</table><p><p>" . PHP_EOL;


function savePensionPaymentRecord($conn, &$data) {
    /**
     * Save a pension payment record in the database.
     *
     * @param resource $conn PostgreSQL connection resource.
     * @param array &$data Reference to an associative array to store the data from the saved record.
     *
     * @return string Error message if there is an error during CRUD operation, empty string otherwise.
     */

	$rv = ""; // Initialize the return value to an empty string
    $numrows = 0; // Initialize the number of rows affected to 0

	// Get the SQL statement for pension payment CRUD operations
	$sql = trim(getPensionPaymentCRUDSQL());
	
    // Execute the SQL statement if it is not empty
	if (!empty($sql)) {
		
		$result = pg_query($conn, $sql);
		$numrows = pg_num_rows($result);
		
        // If returning * at the end of the CRUD SQL statement, extract data
		if ($numrows > 0 and substr(strtoupper($sql), (strlen(rtrim($sql, ';')) - 12), 12) == " RETURNING *") {
			while ($row = pg_fetch_assoc($result)) {
				foreach($row as $key=>$value) {  
					$data += [$key => (is_null($value) ? "" : $value)];						
				}
			}
		}
		
	}
    // Set the error message if there is a CRUD error
	if ($numrows == -1)
		$rv = "CRUD Error";
	return $rv;
}

function getPensionPaymentCRUDSQL(&$debug_txt = "") {
    /**
     * Generate SQL query for CRUD operations on pension payment records.
     *
     * @param string &$debug_txt Debugging information (passed by reference).
     *
     * @return string SQL query for the specified CRUD operation.
     */

    
	$returning = " returning *"; // SQL clause for returning all columns in a row

    // Extract and clean input values from $_POST
	$emp_id = substr(preg_replace('/[^0-9]/', '', $_POST["emp_no"]),0,7); // 
	$pen_pmnt_seq = FormatIntNumeric($_POST["pen_pmnt_seq"]);
	$soc_sec_no = substr(preg_replace('/[^0-9-]/', '', $_POST["soc_sec_no"]),0,11);
	$emp_last_name = substr(clean_input(strtoupper($_POST["emp_last_name"])), 0, 40); 
	$emp_first_name = substr(clean_input(strtoupper($_POST["emp_first_name"])), 0, 40); 
	$payment_date = $_POST["payment_date"];

    // Validate and format the payment date
	if (!validateDate($payment_date))
		$payment_date = "NULL";
	else
		$payment_date = "'$payment_date'";

    // Extract and clean other input values
	$payment_reference = preg_replace('/[^A-Z0-9]/', '', strtoupper($_POST["payment_reference"]));
	$pension_paid_amt = preg_replace('/[^0-9.]/', '',$_POST["pension_paid_amt"]);
	$usr = substr(preg_replace('/[^A-Z0-9]/', '', strtoupper(USERNAME)), 0, 12); 

	$employment_start = $_POST["employment_start"];

    // Validate and format the employment start date
	if (!validateDate($employment_start))
		$employment_start = "NULL";
	else
		$employment_start = "'$employment_start'";
	
	$employment_ending = $_POST["employment_ending"];

	// Validate and format the employment ending date
	if (!validateDate($employment_ending))
		$employment_ending = "NULL";
	else
		$employment_ending = "'$employment_ending'"; 

	$comments = str_replace("'","''",clean_input(strtoupper($_POST["comments"])));
	
    // Construct SQL query based on the operation (insert, update, or delete)
    if (empty($pen_pmnt_seq)) {	
        // Insert operation
		$sql = "INSERT INTO pension_payment (soc_sec_no, emp_id, emp_last_name, emp_first_name, payment_date, 
            payment_reference, pension_paid_amt, employment_start, employment_ending, comments, createdby) 
            VALUES ('$soc_sec_no', '$emp_id', '$emp_last_name', '$emp_first_name', $payment_date, 
            '$payment_reference', '$pension_paid_amt', $employment_start, $employment_ending, '$comments', '$usr') $returning";
            
	} elseif ($pen_pmnt_seq > 0 and false) {
        // Delete operation (disabled, as indicated by "and false")
        $sql = "DELETE FROM pension_payment WHERE seq = " . $pen_pmnt_seq . $returning . PHP_EOL;
    } elseif ($pen_pmnt_seq > 0) {
        // Update operation
		$sql = "UPDATE pension_payment SET soc_sec_no = '$soc_sec_no',  employment_start = $employment_start, 
            employment_ending = $employment_ending, emp_id = '$emp_id', emp_last_name = '$emp_last_name', emp_first_name = '$emp_first_name', 
            payment_date = $payment_date, payment_reference = '$payment_reference', pension_paid_amt = '$pension_paid_amt', 
            comments = '$comments'"; 
		
		$sql .= ", modified = now(), modifiedby = '$usr' 
		    WHERE seq = " . $pen_pmnt_seq . $returning; 
	}
	return $sql;
}

function pen_payment_form_htm($conn, $py_seq, $show_ssn = true, $action = "", $pen_pay_list_htm = "", $add_script = "pension_pay.php", $attrbts = " onsubmit='return validateForm(this);'", $form_name = "pension_payment_entry") {
	/**
     * Generate the HTML form for pension payment entry.
     *
     * @param resource $conn                PostgreSQL connection resource.
     * @param int      $py_seq              Pension sequence number.
     * @param bool     $show_ssn            Flag to determine whether to show Social Security Number input.
     * @param string   $action              Form submission action. Defaults to the current PHP script.
     * @param string   $pen_pay_list_htm    HTML content for pension payment list.
     * @param string   $add_script          Additional script for form action.
     * @param string   $attrbts             Additional attributes for the form tag.
     * @param string   $form_name           Name of the form.
     *
     * @return string HTML content for the pension payment form.
     */
    
    // Set default action if not provided
    if (empty($action)){
		$action = htmlspecialchars($_SERVER["PHP_SELF"]);
    }
    // Initialize variables
    $dis = $dis_;
	$rowcount = 0;
    $pen_py_lu_form_htm = "";

    // If pension sequence number is provided, fetch pension payment details
	if ($py_seq > 0) {
		// SQL query to retrieve pension payment details
		$sql = "SELECT py.*, coalesce(to_char(birth_date,'MM/DD/YYYY'),'') birth_date 
				FROM pension_payment py LEFT JOIN employ e ON py.emp_id = e.emp_no
			WHERE py.seq = $1 
			LIMIT 1;";
		
        // Execute the query
		$result = pg_query_params($conn, $sql, array($py_seq));  
		$rowcount = pg_num_rows($result);

        // If there are results, fetch and set variables
		if ($rowcount > 0) { 
			while ($row = pg_fetch_assoc($result)) {		
				foreach($row as $key=>$value) {
					${$key} = $value;
				}
				$emp_id_display = "";
				if (!empty($emp_id))
					$emp_id_display = substr($emp_id,0,3) . "-" . substr($emp_id,-4);
			}
			
		}	
        // If pen_pay_list_htm is empty and emp_id is not, fetch payment history HTML
		if (empty($pen_pay_list_htm) and !empty($emp_id)) {
			$pen_pay_list_htm = pen_payment_hist_htm($conn, $py_seq, $emp_id);
		}
		
	}
	// Build the employee info lookup section
	if ($show_ssn) {
		$pen_py_lu_form_htm .=  " <td>Social Sec No<td>" . 
		"<td><input autofocus type='text' name='soc_sec_no_lu' maxlength='11' size='11' onchange='doPaymentLookup(this," . '"soc_sec_no"' . ");' onkeydown='lookupEnter(this," . '"soc_sec_no"' . ");' onkeyup='allowintanddashonly(this)' value=''/></td>" . PHP_EOL;
	}
	
	$pen_py_lu_form_htm .= " <td>Employee No<td>" . 
	"<td><input autofocus type='text' id='emp_no_lu_id' name='emp_no_lu' maxlength='9' size='8' onchange='doPaymentLookup(this," . '"emp_no"' . ");' onkeydown='lookupEnter(this," . '"emp_no"' . ");' style='text-transform:uppercase;' onkeyup='allowintanddashonly(this)' value=''/></td>" . PHP_EOL;
	
    // Display pension payment list if available
    if (strlen($pen_pay_list_htm) > 10) {
		$pen_pay_list_htm = PHP_EOL . " <td colspan='6' id='results_id'>" . PHP_EOL . "$pen_pay_list_htm</td>" . PHP_EOL . "</tr>" . PHP_EOL;
	}
    // Section Info 
	$pen_py_lu_form_htm = "<tr><td colspan='2'><fieldset><table border='0'><tr><th colspan='6' class='t_h'>Employee Info Lookup</th></tr><tr>" . $pen_py_lu_form_htm  . 
	    "</tr>$pen_pay_list_htm</table></fieldset></td></tr>". PHP_EOL;
	
    // Build the pension payment form
	$pen_py_form_htm .= "<tr>" . PHP_EOL . " <th>RSN<input type='hidden' id='pen_pmnt_seq_id' name='pen_pmnt_seq' value='$seq'/><input type='hidden' id='employ_lu_seq_id' name='employ_lu_seq' value='0'/></th>" . PHP_EOL .
	    " <td id='pay_seq_td'>" . ($py_seq > 0 ? $py_seq : "") . "</td></tr>" . PHP_EOL;
	
    // Add Social Security Number input if required
	if ($show_ssn) {
		$pen_py_form_htm .= "<tr>" . PHP_EOL . " <th>Social Sec No</th><td>" . 
		    "<input autofocus type='text' name='soc_sec_no' id='ssn_entry' maxlength='11' size='11' onkeyup='allowintanddashonly(this)' value='$soc_sec_no'/></td></tr>" . PHP_EOL;
	}
	
    // Add Employee Number input
	$pen_py_form_htm .= "<tr>" . PHP_EOL . " <th>Employee No</th><td>" . 
	    "<input autofocus type='text' name='emp_no' id='emp_no_entry' maxlength='9' size='8' style='text-transform:uppercase;' onkeyup='allowintanddashonly(this)' value='$emp_id_display'/></td></tr>" . PHP_EOL;

	// Add Employee Last Name input
	$pen_py_form_htm .= "<tr>" . PHP_EOL . " <th>Employee Last Name</th><td>" . 
	    "<input type='text' name='emp_last_name' id='lname_entry' maxlength='40' size='30' style='text-transform:uppercase;' onkeyup='allowonlyuppercaselettersandspacesanddots(this)' value='$emp_last_name'/></td></tr>" . PHP_EOL;
	
	// Add Employee First Name input
	$pen_py_form_htm .= "<tr>" . PHP_EOL . " <th>Employee First Name</th><td>" . 
	    "<input type='text' name='emp_first_name' id='fname_entry' maxlength='30' size='30' onkeyup='allowonlyuppercaselettersandspacesanddots(this)' value='$emp_first_name'/></td></tr>" . PHP_EOL;
	
    // Add Payment Reference input
	$pen_py_form_htm .= "<tr>" . PHP_EOL . " <th>Payment Ref.</th><td>" . 
	    "<input type='text' id='pay_ref_entry' name='payment_reference' maxlength='20' size='20' onkeyup='allowintonly(this)' value='$payment_reference'/></td></tr>" . PHP_EOL;
	
    // Add Pension Paid Amount input
	$pen_py_form_htm .= "<tr>" . PHP_EOL . " <th>Pension Paid Amt.</th><td>" . 
	    "<input type='text' id='paid_amt_entry' name='pension_paid_amt' maxlength='20' size='12' onkeyup='allowintanddotonly(this)' value='$pension_paid_amt'/></td></tr>" . PHP_EOL;
	
    // Add Payment Date input
	$pen_py_form_htm .= "<tr>" . PHP_EOL . " <th>Payment Date</th><td>" . 
        "<input type='date' id='payment_date_id' name='payment_date' value='" . getDateStr($payment_date) . 
        "' min='1970-01-01' max='" . date('Y-m-d',strtotime('+0 days')) . "'></td></tr>" . PHP_EOL;
        
    // Add Employment Start Date input
	$pen_py_form_htm .= "<tr>" . PHP_EOL . " <th>Employment Start Date</th><td>" . 
        "<input type='date' id='employment_start_id' name='employment_start' value='" . getDateStr($employment_start) . 
        "' min='1950-01-01' max='" . date('Y-m-d',strtotime('+0 days')) . "'></td></tr>" . PHP_EOL;
    
    // Add Employment End Date input
	$pen_py_form_htm .= "<tr>" . PHP_EOL . " <th>Employment End Date</th><td>" . 
        "<input type='date' id='employment_ending_id' name='employment_ending' value='" . getDateStr($employment_ending) . 
        "' min='1950-01-01' max='" . date('Y-m-d',strtotime('+0 days')) . "'></td></tr>" . PHP_EOL;
    
    // Add Birth Date input
	$pen_py_form_htm .= "<tr>" . PHP_EOL . " <th>Birth Date</th>" . PHP_EOL .
	    " <td id='birth_date_td'>" . $birth_date . "</td></tr>" . PHP_EOL;

    // Add Comments input
	$pen_py_form_htm .= "<tr>" . PHP_EOL . " <th>Comments</th><td>" . 
	    "<textarea name='comments' rows='5' cols='50' style='text-transform:uppercase;'>$comments</textarea></td></tr>" . PHP_EOL;

    // Assemble the complete HTML form
	$pen_py_form_htm = PHP_EOL . "<fieldset><form method='POST' id='$form_name_id' name='$form_name' action='$action'$attrbts>" . PHP_EOL . 
	    "<table border='0'>" . $pen_py_lu_form_htm . $pen_py_form_htm . 
        "<tr><td colspan='2' class='alignCenter'><p><button$dis class='btn'>Save</button>" . 
        rpt(8) . "<a href='$add_script'><button$dis type='button' class='btn'>Add New</button></a>" . PHP_EOL . 
        "<input type='hidden' name='last_curr_tab' value='0' />" . PHP_EOL . 
        "</td>" . PHP_EOL. $del_btn;
	
	$pen_py_form_htm .= "</tr></table>" . PHP_EOL . 
	    "<input type='hidden' name='form_name' value='$form_name'/>	
	</form></fieldset>" . PHP_EOL;

	return $pen_py_form_htm;
	
}


function pen_payment_hist_htm($conn, &$py_seq = 0, $keyval = "", &$show_prev_payments = false, $key = "emp_no") {
	/**
     * Generate HTML content for the pension payment history based on employee number or other key.
     *
     * @param resource $conn                    PostgreSQL connection resource.
     * @param int      $py_seq                  Pension sequence number (passed by reference).
     * @param string   $keyval                  Value of the key (e.g., employee number).
     * @param bool     $show_prev_payments      Flag to determine whether to show previous payments (passed by reference).
     * @param string   $key                     Key used for querying (e.g., "emp_no").
     * 
     * @return string HTML content for the pension payment history.
     */
    
     // Store the original pension sequence number and initialize variables
    $orig_py_seq = $py_seq;
	$use_orig_py_seq = false;
	$rowcount = 0;
	$pen_py_form_htm = "";

    // Check if key value is provided
	if (!empty($keyval)) {
        // If the key is "emp_no", remove non-numeric characters from the value
        if ($key == "emp_no")
			$keyval = preg_replace('/[^0-9]/', '', $keyval); 
        // SQL query to retrieve pension payment history
        $sql = "SELECT coalesce(py.seq,0) py_seq, e.seq e_seq, 
				case when length(coalesce(e.emp_no,'')) > 0 then left(e.emp_no,3) || '-' || right(e.emp_no,4) else '' end AS emp_no,
				coalesce(e.emp_last_name,'') e_emp_last_name, coalesce(e.emp_first_name,'') e_emp_first_name,  
				coalesce(e.soc_sec_no,'') e_soc_sec_no, coalesce(py.soc_sec_no,'') py_soc_sec_no, 
				coalesce(py.emp_last_name,'') py_emp_last_name, coalesce(py.emp_first_name,'') py_emp_first_name, 
				coalesce(to_char(py.payment_date,'MM/DD/YYYY'),'') payment_date, 
				coalesce(py.pension_paid_amt,0) pension_paid_amt, 
				coalesce(to_char(e.birth_date,'MM/DD/YYYY'),'') birth_date, 
				coalesce(to_char(e.date_hired,'MM/DD/YYYY'),'') date_hired, 
				coalesce(to_char(e.date_terminated,'MM/DD/YYYY'),'') date_terminated  
				FROM employ e 
				LEFT JOIN pension_payment py ON py.emp_id = e.emp_no  
				WHERE " . $key . " = $1 ORDER BY py.payment_date, py.seq, e.seq";
		// Execute the query
        $result = pg_query_params($conn, $sql, array($keyval));  
		$rowcount = pg_num_rows($result);

		$i = 0;
        // If there are results, generate HTML content
        if  ($rowcount > 0) { 
		
			$pen_py_form_htm = "<fieldset>" . PHP_EOL . "<table>" . PHP_EOL;
			while ($row = pg_fetch_assoc($result)) {
				$i++;
				foreach($row as $key=>$value) {
					${$key} = $value;
					// echo $key . " = " . $value . "</br>";
				}
                // If a pension sequence number is provided, generate detailed payment history
                if ($py_seq > 0) {
					if ($py_seq == $orig_py_seq)
						$use_orig_py_seq = true;
					$pen_py_form_htm .= "<tr>" . PHP_EOL;
					if ($i == 1) {
						$pen_py_form_htm .= " <th colspan='5' class='alignCenter'>" . 
						"Pension Payment History for Emp No $emp_no" . " </th>" . PHP_EOL . "</tr>" . PHP_EOL;
						$pen_py_form_htm .= "<tr>" . PHP_EOL . 
						" <th class='t_h'>Pay Date</th>" . PHP_EOL . 
						" <th class='t_h'>Pay Amt</th>" . PHP_EOL . 
						" <th class='t_h'>Last Name</th>" . PHP_EOL . 
						" <th class='t_h'>First Name</th>" . PHP_EOL . 
						" <th class='t_h'>SSN</th>" . PHP_EOL . 
						"</tr>" . PHP_EOL . "<tr>" . PHP_EOL;
					}
					$url_stub = "?ct=1&source=pp_hist_list&sel=lname_entry";
                    // htm_link is a standard function for this site in another file
					$pen_py_form_htm .= " <td>" . htm_link($payment_date, ($url_stub . "&pys=" . $py_seq), " title='RSN: $py_seq' style='font-size:1.0em;'") . "</td>" . PHP_EOL; 
					$pen_py_form_htm .= " <td class='alignRight'>" . number_format($pension_paid_amt,2) . "</td>" . PHP_EOL;
					$pen_py_form_htm .= " <td>$py_emp_last_name</td>" . PHP_EOL;
					$pen_py_form_htm .= " <td>$py_emp_first_name</td>" . PHP_EOL;
					$pen_py_form_htm .= " <td>" . $py_soc_sec_no . "</td>" . PHP_EOL;
				}
				// If a pension sequence number is provided, close the row
                if ($py_seq > 0)
					$pen_py_form_htm .= "</tr>" . PHP_EOL;
			}
			
		}	
        // If a pension sequence number is provided, update the flag and close the fieldset
        if ($py_seq > 0) {
			$show_prev_payments = true;
			$pen_py_form_htm .= "</table>" . PHP_EOL . 
			"</fieldset>" . PHP_EOL;
			if ($use_orig_py_seq){
				$py_seq = $orig_py_seq;
            }
		}
	}
	return $pen_py_form_htm;
}
