<?php 
// Include necessary session information
require_once("include/session.inc.php");

// Set initial values for tab visibility
$show_emp_info_chg = $show_emp_tab = false;
$show_ssn = true;

// Check if user is logged in; if not, redirect to logout page
if (empty(USERNAME) or empty($userseq)) {
    redirectToURL(HOME_PATH."logout.php");
}

// Initialize variables
$adminprivs = 0;
$pagename = "GVC Pension Payments";
$selfurl = htmlspecialchars($_SERVER["PHP_SELF"]);
$py_seq = 0;

// Get payment sequence from URL parameter
if ($_GET['pys'] > 0)
    $py_seq = $_GET['pys'];
if (empty($py_seq))
    $py_seq = 0;

// Include database connection file
include_once 'include/dbh.inc.php';

// Check database connection
if (!$db) {
    echo "An error occurred when connecting to the db server.\n";
    exit;
}

// Check user privileges to determine tab visibility
if (strpos($_SESSION['kind'], 'L') !== false or strpos($_SESSION['kind'], 'T') !== false or strpos($_SESSION['kind'], 'S') !== false) {
    // $show_form = false;
}

// Set default tab and retrieve tab values from URL parameters
$currtab = 1;
if ($_GET['ct'] > 0)
    $currtab = $_GET['ct'];
$fn = $_GET['fn'];

// Retrieve last current tab value from form and update current tab accordingly
$last_curr_tab = $_POST['last_curr_tab'];
if ($last_curr_tab > 0) 
    $currtab = $last_curr_tab;    
if ($py_seq > 0 and $_POST["form_name"] == "pension_payment_entry") {
    $currtab = 1;
    $selfurl .= (strpos($selfurl, '?') !== false ? "&" : "?") . "pys=$py_seq";
} elseif (strtoupper($_GET['fn']) == 'RPT') {
    $currtab = 2;
}

if ($currtab > 1)
    $py_seq = 0;

if (strpos($selfurl, 'ct=') === false and $currtab > 0)
    $selfurl .= (strpos($selfurl, '?') !== false ? "&" : "?") . "ct=$currtab";

// Set additional parameters for reports
if (!empty($rept)) {
    if (strpos($selfurl, 'year=') === false and $yr > 0)
        $selfurl .= (strpos($selfurl, '?') !== false ? "&" : "?") . "year=$yr";
    if (strpos($selfurl, 'lname=') === false and !empty($lname))
        $selfurl .= (strpos($selfurl, '?') !== false ? "&" : "?") . "lname=$lname";
    if (strpos($selfurl, 'fname=') === false and !empty($fname))
        $selfurl .= (strpos($selfurl, '?') !== false ? "&" : "?") . "fname=$fname";
    if (strpos($selfurl, 'EmpNo=') === false and !empty($EmpNo))
        $selfurl .= (strpos($selfurl, '?') !== false ? "&" : "?") . "EmpNo=$EmpNo";
    if (strpos($selfurl, 'SSN=') === false and !empty($SSN))
        $selfurl .= (strpos($selfurl, '?') !== false ? "&" : "?") . "SSN=$SSN";
    if (strpos($selfurl, 'rpt=') === false)
        $selfurl .= (strpos($selfurl, '?') !== false ? "&" : "?") . "rpt=$rept";
}

// Include header template
include_once("template/header-top-min.tmpl.php"); 

// Set PHP version for debugging
// echo "phpversion = " . phpversion() . "<p>";
?>

<title>GVC Pension Payments</title>

<!-- JavaScript for handling various actions on the page -->
<script>
    
    // Function to handle Enter key press for payment lookup
    function lookupEnter(obj, key, emp_no_lu_id = 'emp_no_lu_id') {
        if(event.key === 'Enter') {       
            event.preventDefault(); // Prevent the form from submitting
            doPaymentLookup(obj, key, emp_no_lu_id);                
        }
    }
    
    // Function to perform payment lookup
    function doPaymentLookup(obj, key, emp_no_lu_id = 'emp_no_lu_id') {
        if (obj.value.length > 5) {
            getPaymentInfo(obj.value, key);
            let emp_no_lu = document.getElementById(emp_no_lu_id);
            if (emp_no_lu != null && emp_no_lu.value.length == 7)
                emp_no_lu.value = emp_no_lu.value.slice(0,3) + '-' + emp_no_lu.value.slice(-4);
        }
    }
    
    // Function to get payment information using Fetch API
    function getPaymentInfo(keyval, key = 'ssn') {
        // (A) GET FORM DATA
        let data = new URLSearchParams();
        data.append("fn", "pay_info");
        data.append("keyval", keyval); 
        data.append("key", key);
        
        // (B) USE FETCH TO SEND TEXT
        fetch("json_select.php", {
            method: 'post',
            body: data
        })
        .then(function (response) {
            return response.text();
        })
        .then(function (text) {
            // Process the text response
            if (text.substring(0, 2) == '1;') {
                // Redirect to a different page
                let s = "?ct=1&fn=pplu&sel=lname_entry&source=form_lu&key=" + key + "&keyval=" + keyval;
                window.location.href = s;
                return;
            } else {
                // Continue processing payment information
                getEmpInfo(keyval, key);
            }
        })
        .catch(function (error) {
            console.log(error)
        });
    }
    
    // Function to clear payment entry fields
    function clearPaymentEntryFields(clearRSN = true) {
        if (clearRSN == true) {
            document.getElementById("pen_pmnt_seq_id").value = 0;
            document.getElementById("pay_seq_td").innerHTML = "";
        }
        document.getElementById("birth_date_td").innerHTML = "";
        document.getElementById('pay_ref_entry').value = "";
        document.getElementById('paid_amt_entry').value = "";
        document.getElementById('payment_date_id').value = "";
        document.getElementById('employment_start_id').value = "";
        document.getElementById('employment_ending_id').value = "";
        
        document.getElementsByName('employ_lu_seq')[0].value = 0;
        document.getElementById('ssn_entry').value = "";
        document.getElementById('lname_entry').value = "";
        document.getElementById('fname_entry').value = "";
        
        document.getElementById('emp_no_entry').value = "";
    }
    
    // Function to perform employee lookup
    function doEmpLookup(obj,key) {
        getEmpInfo(obj.value, key);
    }

    // Function to get employee information using Fetch API
    function getEmpInfo(keyval, key = 'ssn') {
        // (A) GET FORM DATA
        let data = new URLSearchParams();
        data.append("fn", "emp_info");
        data.append("keyval", keyval); 
        data.append("key", key);
        
        // (B) USE FETCH TO SEND TEXT
        fetch("json_select.php", {
            method: 'post',
            body: data
        })
        .then(function (response) {
            return response.text();
        })
        .then(function (text) {
            // Process the text response
            if (text.substring(0, 2) == '1;') {
                // Process employee information
                rslt = text.slice(2);
                js_obj = JSON.parse(rslt); 
                let obj = js_obj[0];
                
                // Clear payment entry fields
                clearPaymentEntryFields();
                
                // Set employee information in the form
                document.getElementsByName('employ_lu_seq')[0].value = obj['seq'];
                document.getElementById('ssn_entry').value = obj['soc_sec_no'];
                document.getElementById('lname_entry').value = obj['emp_last_name'];
                document.getElementById('fname_entry').value = obj['emp_first_name'];
                
                document.getElementById('emp_no_entry').value = obj['emp_no'];
                document.getElementById('employment_start_id').value = formatDate(obj['date_hired']);
                document.getElementById('employment_ending_id').value = formatDate(obj['date_terminated']);
                
                // Set current entry field
                setCurrEntryField();
                
                document.getElementById("birth_date_td").innerHTML = obj['birth_date'];        
            } 
        })
        .catch(function (error) {
            console.log(error)
        });
    }
    
    // Function to set current entry field
    function setCurrEntryField() {
        if (document.getElementById('ssn_entry').value == "")
            document.getElementById('ssn_entry').select();
        else if (document.getElementById('emp_no_entry').value == "")
            document.getElementById('emp_no_entry').select();
        else  {
            document.getElementById('lname_entry').select();    
        }
    }

    // Function to validate form inputs
    function validateForm(obj) {
        // Define regular expressions for validation
        var ssnRegex = /^\d{3}-\d{2}-\d{4}$/;
        var prRegex = /^\d{10}$/;
        var EmpIdRegex = /^\d{3}-\d{4}$/;

        // Get values from form inputs
        let ssn = obj.soc_sec_no.value;
        let emp_no = obj.emp_no.value;
        let payment_ref = obj.payment_reference.value;
        let pension_paid = obj.pension_paid_amt.value;

        // Define validation functions
        let ssnValidate = (ssn_ref) => ssnRegex.test(ssn_ref);
        let empNoValidate = (emp_no_ref) => EmpIdRegex.test(emp_no_ref);
        let payRefValidate = (pay_ref) => pay_ref.trim() !== '';
        let pensionPaidValidate = (pension_amount) => (pension_amount > 0);

        // Perform validation
        let valid_ssn =  '' || ssnValidate(ssn);
        let valid_emp_no = empNoValidate(emp_no); 
        let valid_payment_ref = payRefValidate(payment_ref); 
        let valid_pension_paid = pensionPaidValidate(pension_paid);

        // Display error messages and return false if validation fails
        if (!valid_ssn) {
            alert("Invalid social security no.");
            obj.soc_sec_no.select();
            return false;
        }

        if (!valid_emp_no) {
            alert("Invalid Employee ID " + emp_no);
            obj.emp_no.select();
            return false;
        }

        if (!valid_payment_ref) {
            alert("Invalid Payment Reference");
            obj.payment_reference.select();
            return false;
        }

        if (!valid_pension_paid) {
            alert("Invalid Pension Paid Amount");
            obj.pension_paid.select();
            return false;
        }

        // Confirm form submission
        if (confirm("Submit entry: are you sure?")) {
            obj.last_curr_tab.value = 1;
            return true;
        }
    }

    // Function to format date input
    function formatDate(inputDate) {
        if (inputDate == null)
            return;
        // Assuming inputDate is in the format "yyyy/mm/dd"
        var parts = inputDate.split('/');
        if (parts.length === 3) {
            // Rearrange the parts to the "mm/dd/yyyy" format
            var formattedDate = parts[1] + '/' + parts[2] + '/' + parts[0];
            return formattedDate;
        } else {
            // Return the original input if the format is not as expected
            return inputDate;
        }
    }

    // Function to check if date is valid
    function isValidDate(dateString, minyr = 1950, maxyr = 2099, dateFormat = "Ymd"){
        // Replace - with /
        dateString = dateString.replace(/-/g, "/");
        
        // Parse the date parts to integers
        var parts = dateString.split("/");
        var day = parseInt(parts[2], 10);
        var month = parseInt(parts[1], 10);
        var year = parseInt(parts[0], 10);
        
        // Check the pattern
        if ( dateFormat == "Ymd" ) {
            if (!/^\d{4}\/\d{1,2}\/\d{1,2}$/.test(dateString))
                return false;
        } else  {
            if (!/^\d{1,2}\/\d{1,2}\/\d{4}$/.test(dateString))
                return false;
            else {
                day = parseInt(parts[1], 10);
                month = parseInt(parts[0], 10);
                year = parseInt(parts[2], 10);    
            }
        }

        // Check the ranges of month and year
        if(year < minyr || year > maxyr || month < 1 || month > 12)
            return false;

        var monthLength = [ 31, 28, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31 ];

        // Adjust for leap years
        if(year % 400 == 0 || (year % 100 != 0 && year % 4 == 0))
            monthLength[1] = 29;

        // Check the range of the day
        return day > 0 && day <= monthLength[month - 1];
    }

    // Function to allow only uppercase letters and spaces
    function allowonlyuppercaselettersandspaces(obj) {   
    obj.value=obj.value.toUpperCase();
    obj.value=obj.value.replace(/[^A-Z ]/g, '');
    }
    
    // Function to allow only uppercase letters, spaces, and dots
    function allowonlyuppercaselettersandspacesanddots(obj) {  
        obj.value=obj.value.toUpperCase();
        obj.value=obj.value.replace(/[^A-Z .]/g, '');
        }
    
    function allowintanddotonly(obj){

        obj.value=obj.value.replace(/[^0-9.]/g, '');   
    
    }
    function allowintanddashonly(obj){

        obj.value=obj.value.replace(/[^0-9-]/g, '');   
    
    }
    
    function allowintonly(obj){

        obj.value=obj.value.replace(/[^0-9]/g, '');   
    
    }	

    function allowonlyuppercaselettersandints(obj) {
        // javascript:this.value=this.value.toUpperCase();
        
        obj.value=obj.value.toUpperCase();
        obj.value=obj.value.replace(/[^A-Z0-9. ?-]/g, '');
    }
    
    function openTab(evt, tabName) {
        // Declare all variables
        var i, tabcontent, tablinks;

        // Get all elements with class="tabcontent" and hide them
        tabcontent = document.getElementsByClassName("tabcontent");
        for (i = 0; i < tabcontent.length; i++) {
        tabcontent[i].style.display = "none";
        }

        // Get all elements with class="tablinks" and remove the class "active"
        tablinks = document.getElementsByClassName("tablinks");
        for (i = 0; i < tablinks.length; i++) {
        tablinks[i].className = tablinks[i].className.replace(" active", "");
        }

        // Show the current tab, and add an "active" class to the button that opened the tab
        document.getElementById(tabName).style.display = "block";
        evt.currentTarget.className += " active";
    }
    

		
	</script>
	
    <link rel="stylesheet" href="../style/common.css">    
    <style> 
	 
	* {
		font-family: Arial, Helvetica, sans-serif;
		font-size:1.6vmin;
	}
	table {
		
		/*margin: 0 auto; */
	}
		
	.alignRight {
       text-align: right;
     }
	 .alignCenter {
       text-align: center;
     }
	 .error {color: #FF0000;}
	 

	tr {
		height: 20px;
	}

	td {
		font-size:2.4vmin;
		padding-left: 5px;
		padding-right: 5px;
		
	}
	
	th {			
		padding:2px 2px;
		font-family: Arial, Helvetica, sans-serif;
		font-size:1.1em;	
		color:white;
		background-color:blue;
		text-align: left;
	}
	
	.t_h {			
		padding:1px 1px;
		font-family: Arial, Helvetica, sans-serif;
		font-size:1.1em;	
		color:white;
		background-color:black;
		text-align: center;
	}
	
	select {
		font-size:1.2rem;
	}
	
	input {
		padding:1px; 
		border:2px solid #ccc; 
		-webkit-border-radius: 5px;
		border-radius: 5px;
		font-size:1.2rem;
		font-family:'Lucida Console', monospace;
		color:blue;
	}
	
	
	::placeholder { /* Chrome, Firefox, Opera, Safari 10.1+ */
		color: grey;
		opacity: 1; /* Firefox */
		}
	

	select {
		font-size:1.2rem;
	}	
	.sel {
		font-size:1.5rem;
		font-family:'Lucida Console', monospace;
	}
	 
	button {
		font-size:1.4rem;
	}
	
	.btn {
	   border: 1px solid #ddd;
	   background-color: #f0f0f0;
	   padding: 4px 12px;
		
		-o-transition: background-color .2s ease-in; 
		-moz-transition: background-color .2s ease-in;
		-webkit-transition: background-color .2s ease-in; 
		transition: background-color .2s ease-in; 
	}
	
	.btn:hover {
		background-color: #e5e5e5;    
	}

	.btn:active {
		background-color: #ccc;
	}
	
	@media print {
            tr.page-break  { break-before: page; }
			.noprint { display: none; } 
			.showonprint { display: block;}
    }
	
	
	/* Style the tab */
	.tab {
	  overflow: hidden;
	  border: 1px solid #ccc;
	  background-color: #f1f1f1;
	}

	/* Style the buttons that are used to open the tab content */
	.tab button {
	  background-color: inherit;
	  float: left;
	  border: 1px solid #ccc;
	  outline: none;
	  cursor: pointer;
	  padding: 14px 16px;
	  transition: 0.3s;
	}

	/* Change background color of buttons on hover */
	.tab button:hover {
	  background-color: #ddd;
	}

	/* Create an active/current tablink class */
	.tab button.active {
	  background-color: #ccc;
	}

	/* Style the tab content */
	.tabcontent {
	  font-size:4.8rem;
	  display: none;
	  padding: 6px 12px;
	  border: 1px solid #ccc;
	  border-top: none;
	  height: 85%;
	} 
	
	.tabcontent {
	  animation: fadeEffect 0.5s; /* Fading effect takes 1 second */
	}

	/* Go from zero to full opacity */
	@keyframes fadeEffect {
	  from {opacity: 0;}
	  to {opacity: 1;}
	}

		
	</style> 
	
</head>
<body>
<div>

<?php

if(!isset($_SESSION)){ 
    session_start(); 
}

$formMsg = $dis = "";

?>

<!-- Tab links -->
<div class="tab">
  <button class="tablinks" onclick="openTab(event, 'Entry')"<?php echo ($currtab != 2 and $currtab != 3) ? ' id="defaultOpen"' : '' ?>>Payment Entry</button>
  <button class="tablinks" onclick="openTab(event, 'Report')"<?php echo ($currtab == 2) ? ' id="defaultOpen"' : '' ?>>Reports</button>
<?php
if ($show_emp_tab) {
	echo '<button class="tablinks" onclick="openTab(event, ' . "'Emp')" . '"' . (($currtab == 3) ? ' id="defaultOpen"' : '') . ">Emp</button>";
}
if ($show_emp_info_chg) {
	echo '<button class="tablinks" onclick="openTab(event, ' . "'EmpInfoChg')" . '"' . (($currtab == 4) ? ' id="defaultOpen"' : '') . ">Emp Info Changes</button>";
}

?>

</div>

<!-- Tab content -->
<div id="Entry" class="tabcontent">
<?php
    // Include pensn_payment entry file
	include_once("include/pensn_payment.inc.php");
?>

</div>



<div id="Report" class="tabcontent">

<?php
	// Include pension report file
	 include_once("include/rpt.pension.inc.php");
?>


<?php
    // Include employee tab if set to show
	if ($show_emp_tab) {
		echo '<div id="Emp" class="tabcontent">';
		include_once("include/pensn_emp.inc.php");
		echo '</div>';
	}
    // Include employee info changes tab if set to show
	if ($show_emp_info_chg) {
		echo '<div id="EmpInfoChg" class="tabcontent">';
		include_once("include/pensn_emp_info_chg.inc.php");
		echo '</div>';
	}
?>

<script>
	// Get the element with id="defaultOpen" and click on it 
	document.getElementById("defaultOpen").click();
</script>

<?php
    // Include footer file
	echo "<br/>";
	 include_once("template/footer-min.tmpl.php");
?>
