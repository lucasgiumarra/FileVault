<?php 

require_once("include/session.inc.php");

// Ensure that USERNAME and $userseq are not empty, otherwise redirect to logout
if (empty(USERNAME) or empty($userseq)) {
    redirectToURL(HOME_PATH."logout.php");
}

// Set initial values
$adminprivs = 0;
$rr_max_rate = 12;
$pagename = "Labor";
$t2_rc = 0;

// Include database connection file
include_once 'include/dbh.inc.php';

// Check if the database connection is successful
if (!$db) {
    echo "An error occurred when connecting to the db server.\n";
    exit;
}

// Check the user's role and set adminprivs accordingly
if (strpos($_SESSION['kind'], 'U') !== false) {
    $adminprivs = 200;
} elseif (strpos($_SESSION['kind'], 'PO') !== false) {
    $adminprivs = 150;
}

// Include labor file
include_once 'include/labor.inc.php';

// Include header template
include_once("template/header-top-min.tmpl.php");

// Start session if not already started
if(!isset($_SESSION)){ 
    session_start(); 
}

// Get and process parameters from the URL
$fn = strtoupper($_GET['fn']);

$rept = $_GET['rpt'];

// Set default value for $rept if not provided
if (empty($rept) and !isset($_GET['rpt'])) {
    $rept = "flc_audit";
}

// Set $show variable based on $rept value
if (empty($rept)) {
    $show = false;
}

// Set default value for $currtab
$currtab = 1;

// Set $currtab based on the value from the URL
if ($_GET['ct'] > 0) {
    $currtab = $_GET['ct'];
}

// Set $currtab based on the last_curr_tab from the form submission
$last_curr_tab = $_POST['last_curr_tab'];
if ($last_curr_tab > 0) {
    $currtab = $last_curr_tab; 
}

// Adjust $currtab based on $fn value
if ($fn == 'RVW') {
    $currtab = 2;
}
if ($fn == 'CNT') {
    $currtab = 3;
}

// Get current URL
$selfurl = htmlspecialchars($_SERVER["PHP_SELF"]);
?>

    <title>GVC Labor</title>
	
	
	<script>
		
		// Function to set default values for an input field based on other fields
		function setDefaultVal(obj, seed_id, dp = 2, wkr_count_id, dflt2 = 0) {	
			if (document.getElementById(seed_id) != null && obj.value == 0 && document.getElementById(wkr_count_id) != null && document.getElementById(wkr_count_id).value > 0) {
				var seed_val = document.getElementById(seed_id).value;
				if (seed_val > 0) {	
					obj.value = Number (seed_val).toFixed(dp);  
				} else if (dflt2 > 0) {
					obj.value = Number (dflt2).toFixed(dp);
				}
			}
		}
		// Function to set default values for all pieces based on certain conditions
		function  setAllPcDefaults() {
			let jobs = 0;
			if (document.getElementById("jobsid") != null) {
				jobs = document.getElementById("jobsid").value;
				if (jobs > 0) {
					for (let j = 1; j <= jobs; j++) { 
						
						setPcDefaults(j);
					}
				}
			}
			
		}
		
		// Function to handle job selection changes
		function changeJobSel(obj, no, rnch_sel_id_stub = "block_sel_") {	
			// This does not work at present
			let job = obj.value

			if (job == "96" || job == "99") {
				let sel_id = rnch_sel_id_stub + no;
				
				if (document.getElementById(sel_id) != null) {
					document.getElementById(sel_id).value = job.substr(id.length - 2);
				}
			}
		}
		// Function to set default values for pieces based on the number of workers
		function setPcDefaults(job_no, dp = 2) {
			let fo_pieces = 0;
			let id = "pieces_id_FO_" + job_no;
			
			if (document.getElementById(id) != null) {
				fo_pieces = document.getElementById(id).value;
				
			}
			
			if (fo_pieces > 0) {
				const crew_count_id_stub = "wkr_count_id_CR";
				let ln = 1;
				let crw_cnt = 0;
				let crew_count = 0;
				let crew_count_id = crew_count_id_stub + ln + "_" + job_no;
				while(document.getElementById(crew_count_id) != null) {
					crw_cnt = Number (document.getElementById(crew_count_id).value);
					if (crw_cnt > 0)
						crew_count += crw_cnt;
					ln++;
					crew_count_id = crew_count_id_stub + ln + "_" + job_no;
				}
				
				if (crew_count > 0) {
					let pcs = fo_pieces/crew_count;
					pcs = Number (pcs).toFixed(0);
					pcs = Number (pcs).toFixed(2);
					// alert("pcs = " + pcs + ", " + document.getElementById("wkr_count_HE_" + job_no).value);
					// pc_rate_id_HE_1  pc_rate_id_CH_1    pieces_id_CH_1
					if (document.getElementById("pieces_id_HE_" + job_no) != null && document.getElementById("pc_rate_id_HE_" + job_no) != null) {
						if (document.getElementById("pc_rate_id_HE_" + job_no).value > 0 && document.getElementById("wkr_count_id_HE_" + job_no).value > 0) {
							document.getElementById("pieces_id_HE_" + job_no).value = pcs;
							//document.getElementById("pieces_id_HE_" + job_no).focus();
						}
					}
					
					if (document.getElementById("pieces_id_CH_" + job_no) != null && document.getElementById("pc_rate_id_CH_" + job_no) != null) {
						if (document.getElementById("pc_rate_id_CH_" + job_no).value > 0 && document.getElementById("wkr_count_id_CH_" + job_no).value > 0) {
							document.getElementById("pieces_id_CH_" + job_no).value = pcs;
							//document.getElementById("pieces_id_CH_" + job_no).focus();
						}
					}
			
				}
			
			}
		}

		// Function to set default values for a labor item based on certain conditions
		function setLaborItemDefault(input_id, role, col, job_no = 1, wkr_count_id) {
			
			var wkr_count = 0;
			
			if (document.getElementById(wkr_count_id) != null) {
				wkr_count = document.getElementById(wkr_count_id).value;
				
			}
			if (wkr_count > 0) {
				
				if (role == "FO" && col == "rate") {
					let fr_input_id = "foreman_rate_amt_id";
					if (document.getElementById(fr_input_id) != null) {
						let foreman_rate_amt = document.getElementById(fr_input_id).value;
						if (foreman_rate_amt > 50) {
							document.getElementById(input_id).value = foreman_rate_amt;
							document.getElementById(input_id).select();
							return;
						}
					}
				} else if (role == "CR" && col == "pc_rate" && input_id.substr(input_id.length - 2) == "_1") {
					
					let pcr_input_id = "pc_rate_id_HE_1";
					if (document.getElementById(pcr_input_id) != null) {
						let pc_rate_amt = document.getElementById(pcr_input_id).value;
						if (pc_rate_amt > 0) {
							document.getElementById(input_id).value = pc_rate_amt;
							document.getElementById(input_id).select();
							//alert(pc_rate_amt);
							return;
						}
					}
				}
				var job_code = "";
				var job_code_id = "job_select_jc11_" + job_no;
				if (document.getElementById(job_code_id) != null) {
					job_code = document.getElementById(job_code_id).value;
				} else {
					job_code_id = "job_" + job_no + "_select"; // job_1_select
				}
				if (document.getElementById(job_code_id) != null) {
					job_code = document.getElementById(job_code_id).value;
				}
				var param_str = role + ":" + document.getElementById('crew_no_id').value + ":" + job_code.replace(/^0+(?!\.|$)/, '') + ":" + col + ":" + document.getElementById('crew_sub_id').value;
				// console.log(job_code_id + ", jc = " + job_code + ", param_str = '" + param_str + "'");
				setValFromFetch(input_id, 'labor_item', 'getLastLaborItemValSQL', param_str);
			} else {
				document.getElementById(input_id).select();
			}
		}
		
		// Function to fetch and set values based on a server-side response
		function setValFromFetch(input_id, table, fn = "", ps = "", mthd='post'){
			// (A) GET FORM DATA	
			if (table.length == 0 || input_id.length == 0 || document.getElementById(input_id) == null)
				return;
			let data = new URLSearchParams();
			
			data.append("table", table);
			data.append("fn", fn); 
			data.append("ps", ps); 
		 
			// (B) USE FETCH TO SEND TEXT
			fetch("val_select.php", {
				method: mthd,
				body: data
			})
			.then(function (response) {
				return response.text();
			})
			.then(function (text) {
				
				if (text.substring(0, 1) != '0') {
					
					document.getElementById(input_id).value = text.slice(2).trim();
					document.getElementById(input_id).select();
					
				} else {
					console.log(text);
				}

			})
			.catch(function (error) {
				console.log(error)
			}); 
		}
		
		// Function to handle changes in ranch selection
		function changeRanchSel(obj, no, sel = "", sel_id_stub = "block_sel_") {	
			fetch_ranch_json(obj.value, no, sel, sel_id_stub);
		}
		
		// Function to fetch JSON data for ranches
		function fetch_ranch_json(rnch, no, sel = "", sel_id_stub = "block_sel_", mthd='post'){
			// (A) GET FORM DATA		
			let whr = "var_cd not in ('')";
			let job_no_name = "job_" + no;
			let job_code = document.getElementsByName(job_no_name)[0].value;
			//alert("job_code = '" + job_code + "'");
			let data = new URLSearchParams();
			let wrk_date_year = document.forms["labor_entry"]["work_dt"].value.substring(0, 4);
			// document.getElementsByName('work_dt')[0].value
			// if (rnch.trim() == "4931")
				// rnch = "49";
			data.append("ranch", rnch);
			data.append("yr", wrk_date_year);
			data.append("job_code", job_code);
			// data.append("wh", whr); 
			
			// (B) USE FETCH TO SEND TEXT
			fetch("json_select.php", {
				method: mthd,
				body: data
			})
			.then(function (response) {
				return response.text();
			})
			.then(function (text) {
				//alert(text);
				if (text.substring(0, 2) == '1;') {
					// console.log(text.slice(2));
					populateSelectFromJson(text.slice(2), no, rnch, sel, sel_id_stub, job_code);
				} else {
					console.log(text);
					alert("Failed to load blocks for '" + rnch + "'");
				}
				// 
				
			})
			.catch(function (error) {
				console.log(error)
			});
		 
		}
		
		// Function to populate a dropdown based on JSON data
		function populateSelectFromJson (jsn, no, rnch, sel = "", sel_id_stub = "block_sel_", job_code = "") {
			var sel_id = sel_id_stub + no;
			
			if (document.getElementById(sel_id) != null) {
				
				js_obj = eval(jsn);
				const selectedValues = sel.trim().split(',');
				let dropdown = document.getElementById(sel_id);
				dropdown.length = 0;
				let defaultOption = document.createElement('option');
				if (rnch.substring(0, 2) == "49")
					rnch = rnch.substring(2, 6);
				
				if (rnch == 9600 || rnch == 9900)
					dropdown.style.display = "none";
				s = 0;
				
				let option;
				if (job_code != "11" && job_code != "011" && rnch != 96 && rnch != 99) {
					option = document.createElement("option");
					option.text = 'GEN - 000';
					option.value = 'GEN:0:000';
					dropdown.add(option);
				}

				for (let i = 0; i < js_obj.length; i++) {
					
					let obj = js_obj[i];
					if (obj['blk'] != '') {
						option = document.createElement("option");
						let txt = obj['blk'];
						
						if (obj['variety'] != '')
							txt += ' - ';
						option.text = txt + obj['variety'];
						option.value = obj['blk'].trim() + ':' + obj['tr_seq'] + ':' + obj['variety'].trim();
						if (selectedValues.indexOf(obj['blk'].trim()) >= 0) {
							s++;
							option.selected = true;
						}
						dropdown.add(option);
					}
					
				}
				if (rnch == "32NW") {
					option = document.createElement("option");
					option.text = '0 - Nursery';
					option.value = 'NS:0:099';
					dropdown.add(option);
				}
				if (s == 0)
					dropdown.selectedIndex = 0;
				var opts = dropdown.length;
				var maxlen = 6;
				if (opts < maxlen)
					dropdown.size = opts;
				else 
					dropdown.size = maxlen;
				
				if (dropdown.size > 0)
					dropdown.style.display = "block";
	
			} else {
				alert("Error on populating block list");
			}
		}

		// Function to handle crew labor entry form submission
		function crewLaborEntry() {
			event.preventDefault();
			// && document.forms["labor_entry"]["labor_seq"].value == 0
			var crew_no = document.getElementById('crew_no_id').value;
			var wrk_date = document.forms["labor_entry"]["work_dt"].value;
			var crew_sub = document.getElementById('crew_sub_id').value;
			
			// alert(crew_no); //  && document.forms["labor_entry"]["last_crew"].value != crew_no
			if (crew_no.length < 2) {
				alert('No crew selected');
				document.forms["labor_entry"]["crew_no"].focus();
			} else if (!isValidDate(wrk_date)) {	
				alert("Invalid date");
				document.forms["labor_entry"]["work_dt"].focus();
			} else {
				
				var s = "?fn=e&crw=" + crew_no + "&dt=" + wrk_date + "&cs=" + crew_sub;
				console.log(event.key + ', ' + s);
				if (document.getElementById("jobsid") != null)
					s = s + "&j=" + document.forms["labor_entry"]["jobs"].value;
				if (document.getElementById("cnoid") != null)
					s = s + "&cno=" + document.forms["labor_entry"]["cno"].value;
				window.location.href = s;
				
			}
		}
		
		// Function to handle changes in report selection
		function handleRptSel(obj) {
			if (obj.value == "wkly_crew_recap1")
				document.getElementById("cb_span").style.display = "none";// block
			else 
				document.getElementById("cb_span").style.display = "none";
			
			
			let itm_htm = document.getElementById("from_date_td").innerHTML;
			
			if (obj.value =="wkly_crew_recap1" || obj.value =="wkly_crew_recap2" || obj.value == "flc_audit0") {
				// Hide filter options not related to these reports
				document.getElementById("from_date_td").innerHTML = itm_htm.replaceAll("From:","Date:")
				document.getElementById("cont_span").style.display = "none";
				document.getElementById("to_date_td").style.display = "none";
				
			} else if (obj.value == "flc_audit") {
				document.getElementById("cont_span").style.display = "none";
			} else {
				// Show all filter options
				document.getElementById("cont_span").style.display = "block";
				document.getElementById("to_date_td").style.display = "block";
				document.getElementById("from_date_td").innerHTML = itm_htm.replaceAll("Date:","From:")				
			}
			
		}
		// Function to handle labor report generation
		function doLaborReport() {
			event.preventDefault();
			var qs0 = "";
			var form = document.getElementById('labor_review_id');
			var crew_no = document.getElementById('crw_id').value;
			var crew_sub_rpt = document.getElementById('crew_sub_id_rpt').value;
			if (crew_no != '900')
				crew_sub_rpt = "";
			var bgn_date = form["bgdt"].value;

			if (form["enddt"] != null) {
				var end_date = form["enddt"].value;
				qs0 += "&enddt=" + end_date;
			}

			if (form["eb"] != null) {
				var eb = form["eb"].value;
				qs0 += "&eb=" + eb;
			}

			if (form["cncd"] != null) {
				var cncd = form["cncd"].value;
				qs0 += "&cncd=" + cncd;
			}

			var rpt = form["rpt"].value;

			var qs = "?ct=2&crw_filter=" + crew_no + "&crew_sub_rpt=" + crew_sub_rpt + "&bgdt=" + bgn_date + qs0 + '&rpt=' + rpt;
			console.log('qs = ' + qs);
			window.location.href = qs;
		}
		
		// Function to handle labor record deletion
		function deleteLaborEntry(lbr_rsn = 0) {
			event.preventDefault();
			var qs0 = "";
			var lbrsn = document.getElementById('labor_seq_id').value;
			
			if (lbrsn != lbr_rsn)
				return;
			if (confirm("Delete labor record " + lbrsn + ": are you sure?")) {
				if (confirm("Final warning: delete labor record " + lbrsn + "?")) {
					var qs = "?fn=dellr&lbrsn=" + lbrsn;
					console.log('qs = ' + qs);
					window.location.href = qs;
				}
			}
		}

		// Function to handle crew labor entry on Enter key press
		function crewLaborEnter(obj) {
			if(event.key === 'Enter') {		
				crewLaborEntry(); 				
			}
		}
		// Function to navigate to the next or previous labor record
		function nextPrevRec(labor_seq, mode, crew_no, work_date = "", maxyr = 2099, minyr = 2020) {
			// ?j=1&cno=3
			if (labor_seq > 0 &&crew_no > 0 && (mode == "nxr" || mode == "pvr")) {
				var qs = "?fn=" + mode + "&ls=" + labor_seq + "&crw=" + crew_no;
				if (isValidDate(work_date, minyr, maxyr))
					qs += "&dt=" + work_date;
				// alert("qs = '" + qs + "', crew = " + crew_no + ", dt = " + work_date + ", dir =" + mode);
				window.location.href = qs;
			}
		}
		// Function to navigate to the next labor record
		function nextRec(labor_seq, crew_no, work_date = "", maxyr = 2099, minyr = 2020) {
			nextPrevRec(labor_seq, "nxr", crew_no, work_date, maxyr, minyr);
		}
		// Function to navigate to the previous labor record
		function prevRec(labor_seq, crew_no, work_date = "", maxyr = 2099, minyr = 2020) {
			nextPrevRec(labor_seq, "pvr", crew_no, work_date, maxyr, minyr);
		}
		// Function to validate the labor entry form
		function validateForm(obj) {
					
			let crew_no = obj.crew_no;

			if(!(crew_no.value > 0)){
				alert("Invalid crew number");
				crew_no.focus();
				return false;
			}

			let work_dt = obj.work_dt;
			
			if (!isValidDate(work_dt.value, 2022, <?php echo date("Y") ?>)) {
				alert("Invalid date");
				work_dt.focus();			
				return false;
			}

			let jobs = obj.jobs;

			if(isNaN(jobs.value) || jobs.value == '' || jobs.value <= 0){// || jobs_count === null){ 
				alert("Invalid jobs");
				jobs.value = obj.job_count.value; 
				jobs.focus();			
				return false;
			}

			let job_sel_id = "job_1_select"; 
			let input_obj = "";
			let ranch_list_id = "";
			let block_list_id = "";
			for (let j = 1; j <= jobs.value; j++) { 
				// validations of Job Code, ranch and block
				input_obj = 	document.getElementById(job_sel_id)
				if (input_obj != null)  {
					
					if(input_obj.value == ''){
						alert("Missing Job " + j + " code" );
						input_obj.focus()
						return false;
					}
				}

				ranch_list_id = "ranch_list_" + j + "_select";
				input_obj = document.getElementById(ranch_list_id)
				if (input_obj != null)  {
					
					if(input_obj.value == ''){
						alert("Missing Ranch for job " + j );
						input_obj.focus()
						return false;
					}		
				}

				block_list_id = "block_sel_" + j;
				input_obj = document.getElementById(block_list_id)
				if (input_obj != null)  {
					if(input_obj.value == ''){
						alert("Missing block for job " + j );
						input_obj.focus()
						return false;
					}		
				}
				job_sel_id = "job_" + (j + 1) + "_select";
			}
			return confirm("Submit entry: are you sure?");
		}
		
		// Function to validate date format and range
		function isValidDate(dateString, minyr = 1950, maxyr = 2099, dateFormat = "Ymd")
		{
			
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
		// Function to allow only uppercase letters, dots, and spaces
		function allowonlyuppercaselettersdotsandspaces(obj) {	
		   obj.value=obj.value.toUpperCase();
		   obj.value=obj.value.replace(/[^A-Z .]/g, '');
		}
		// Function to allow only uppercase letters
		function allowonlyuppercaseletters(obj) {	
		   obj.value=obj.value.toUpperCase();
		   obj.value=obj.value.replace(/[^A-Z]/g, '');
		}
		// Function to allow only integers and dots
		function allowintanddotonly(obj){
			obj.value=obj.value.replace(/[^0-9.]/g, '');   
		}
		// Function to allow only integers
		function allowintonly(obj){
			obj.value=obj.value.replace(/[^0-9]/g, '');   
		}	
		// Function to allow only uppercase letters and integers
		function allowonlyuppercaselettersandints(obj) {
		   obj.value=obj.value.toUpperCase();
		   obj.value=obj.value.replace(/[^A-Z0-9. ?-]/g, '');
		}
		
		// Function to open a specific tab and set focus
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
		  
		  // set focus   
		  var focus_id = '';
		  if (tabName == "Review") {  
			
			if (document.getElementById("rvw_focus_id") != null) {
				focus_id = document.getElementById("rvw_focus_id").value;
			}
			if (focus_id.length > 0 && document.getElementById(focus_id) != null )	
				document.getElementById(focus_id).focus();
			else if (document.getElementById("crw_id") != null) 
				document.getElementById("crw_id").focus();
		  } else if (tabName == "Entry") {  
			
			if (document.getElementById("focus_id") != null) {
				focus_id = document.getElementById("focus_id").value;
			}
			if (focus_id.length > 0 && document.getElementById(focus_id) != null )	
				document.getElementById(focus_id).focus();
			else if (document.getElementById("job_1_select") != null) 
				document.getElementById("job_1_select").focus();
			else if (document.getElementById("job_2_select") != null) 
				document.getElementById("job_2_select").focus();
			else if (document.getElementById("job_3_select") != null) 
				document.getElementById("job_3_select").focus();
			else if (document.getElementById("job_4_select") != null) 
				document.getElementById("job_4_select").focus();
			else if (document.getElementById("job_5_select") != null) 
				document.getElementById("job_5_select").focus();
			else if (document.getElementById("job_6_select") != null) 
				document.getElementById("job_6_select").focus();
			else if (document.getElementById("role_1_select") != null) 
				document.getElementById("role_1_select").focus();
			else if (document.getElementById("wkr_count_id_FO_1") != null) 
				document.getElementById("wkr_count_id_FO_1").focus();
			else if (document.getElementById("wkr_count_id_HE_1") != null) 
				document.getElementById("wkr_count_id_HE_1").focus();
			else if (document.getElementById("wkr_count_id_CH_1") != null) 
				document.getElementById("wkr_count_id_CH_1").focus();
			else if (document.getElementById("wkr_count_id_CR1_1") != null) 
				document.getElementById("wkr_count_id_CR1_1").focus();
			else if (document.getElementById("crew_no_id") != null) 
				document.getElementById("crew_no_id").focus();
			else if (document.getElementById("lbr_datepicker") != null) 
				document.getElementById("lbr_datepicker").focus(); 		
		  }
		}

		// Function to export HTML table to Excel
		function ExportToExcel(elementID, filename = '', type = 'xlsx', dl) {
            document.querySelectorAll(".hide_on_download").forEach(a=>a.style.display  = "none");
			var elt = document.getElementById(elementID);
            var wb = XLSX.utils.table_to_book(elt, { sheet: "sheet1" });
			var fn = '';
			if (filename.length > 0)
				fn = filename + '.' + type;
			document.querySelectorAll(".hide_on_download").forEach(a=>a.style.display  = "block");
            return dl ?
                XLSX.write(wb, { bookType: type, bookSST: true, type: 'base64' }) :
                XLSX.writeFile(wb, fn || ('MySheetName.' + (type || 'xlsx')));
			
        }
		// Function to export HTML table to Excel (alternative method)
		function exportTableToExcel(tableID, filename = ''){
			// Source: https://www.codexworld.com/export-html-table-data-to-excel-using-javascript/
			document.querySelectorAll(".hide_on_download").forEach(a=>a.style.display  = "none");
			var downloadLink;
			var dataType = 'application/vnd.ms-excel';
			var tableSelect = document.getElementById(tableID);
			var tableHTML = tableSelect.outerHTML.replace(/ /g, '%20');
			// I had to encode # signs as well before the script would work with any non-Safari browser:
	
			// Specify file name
			filename = filename?filename+'.xls':'excel_data.xls';
			
			// Create download link element
			downloadLink = document.createElement("a");
			
			document.body.appendChild(downloadLink);
			
			if (navigator.msSaveOrOpenBlob){
				var blob = new Blob(['\ufeff', tableHTML], {
					type: dataType
				});
				navigator.msSaveOrOpenBlob( blob, filename);
			} else {
				// Create a link to the file
				downloadLink.href = 'data:' + dataType + ', ' + tableHTML;
			
				// Setting the file name
				downloadLink.download = filename;
				
				//triggering the function
				downloadLink.click();
			}
			document.querySelectorAll(".hide_on_download").forEach(a=>a.style.display  = "block");
		}

		
	</script>
	
    <link rel="stylesheet" href="../style/common.css">    
    <style> 
	 
	* {
		font-family: Arial, Helvetica, sans-serif;
		font-size:1.6vmin;
	}

		
	.alignRight {
       text-align: right;
     }
	 .alignCenter {
       text-align: center;
     }
	 
	 .alignCenterTop {
		text-align: center;
		vertical-align: top;			
	 }
	 
	 .error {color: #FF0000;}
	 

	tr {
		height: 20px;
	}

	td {
		font-size:2.0vmin;
		padding-left: 5px;
		padding-right: 5px;
		
	}
	
	th {			
		padding:2px 2px;
		font-family: Arial, Helvetica, sans-serif;
		font-size:1.1em;	
		color:white;
		background-color:blue;
		text-align: center;
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
	  border-radius: 8px 8px 0 0;
	  outline: none;
	  cursor: pointer;
	  padding: 4px 14px;
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
	

	</style> 
	
</head>
<body>
<div>

<?php

$dis = "";

?>


<!-- Tab links -->
<div class="tab">
  <!-- Button for Data Entry tab -->
  <button class="tablinks" onclick="openTab(event, 'Entry')" id="tab_1">Data Entry</button>
  
  <?php
    // Conditionally display Review tab based on a PHP condition
    if (true) {
        echo '  <button class="tablinks" onclick="openTab(event, ' . "'Review'" . ');" id="tab_2">Review</button>';
    }

    // Conditionally display Contractor tab based on a PHP condition
    if (false) {
        echo '  <button class="tablinks" onclick="openTab(event, ' . "'Contractor'" . ')"' . (($currtab == 3) ? ' id="tab_3"' : "") . ">Contractor</button>";
    }
  ?>
</div>

<!-- Tab content for Data Entry -->
<div id="Entry" class="tabcontent">
  <?php
    // Include the PHP file for labor entry
    include_once("include/labor.entry.inc.php");
  ?>
</div>

<!-- Tab content for Review -->
<div id="Review" class="tabcontent">
  <?php
    // Include the PHP file for labor report review
    // $currtab == 2
    if (true) {
      include_once("include/labor.rpt.inc.php");
    }
  ?>
</div>

<?php
// Conditionally display Contractor tab based on a PHP condition
if (false) {
    echo "<div id='Contractor' class='tabcontent'>" . PHP_EOL;
    // Include the PHP file for contractor information
    // include_once("include/contractor.inc.php");
    echo "</div>" . PHP_EOL; 
}

// JavaScript to set the default tab based on the PHP variable $currtab
echo "<script>" . PHP_EOL;
echo "  document.getElementById('tab_$currtab').click();" . PHP_EOL;
echo "</script>";
?>

 
