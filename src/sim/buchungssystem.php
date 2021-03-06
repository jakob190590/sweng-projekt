<?php
	require '../lib/DBAccess.php';	
	require '../lib/stdio.php';
	require '../domain/AccessEntry.php';
	
	session_start();
	$dbh = new DBAccess();
	

	$action = $_GET["action"];
	if(empty($action))
		$action = "form";
		
	switch($action) {
		case "form":
			$return = form($dbh);
			break;
		case "save":
			$return = save();
			break;
		case "delete":
			$return = delete();
			break;
	}
?>


<!DOCTYPE html>
<html>
    <head>
        <meta charset="UTF-8">
		<title>Buchungssystem</title>
		<link rel="stylesheet" href="../jquery/jquery.ui.all.css">
		<link rel="stylesheet" tyep="text/css" href="../web.css" />
		<script src="../jquery/jquery-1.8.3.min.js"></script>
		<script src="../jquery/jquery.ui.core.js"></script>
		<script src="../jquery/jquery.ui.slider.js"></script>
		<script src="../jquery/jquery.ui.widget.js"></script>
		<script src="../jquery/jquery.ui.datepicker.js"></script>
		<script src="../jquery/jquery-ui-timepicker-addon.js"></script>
		<script>
			$(function() {
				$.timepicker.regional['de'] = {
				  timeOnlyTitle: 'Uhrzeit auswählen',
				  timeText: 'Zeit',
				  hourText: 'Stunde',
				  minuteText: 'Minute',
				  secondText: 'Sekunde',
				  currentText: 'Jetzt',
				  closeText: 'Auswählen',
				  ampm: false
				};
				$.timepicker.setDefaults($.timepicker.regional['de']);
				
				$('#from').datetimepicker({
					changeMonth: true,
					numberOfMonths: 1,
					dateFormat: "yy-mm-dd",
					onClose: function( selectedDate ) {
						$( "#to" ).datepicker( "option", "minDate", selectedDate );
					}
				});
				$('#to').datetimepicker({
					changeMonth: true,
					numberOfMonths: 1,
					dateFormat: "yy-mm-dd",
					onClose: function( selectedDate ) {
						$( "#from" ).datepicker( "option", "maxDate", selectedDate );
					}
				});
			});
			$(document).ready(function() {
				$('#randVals').click( function() {
					$.ajax({
						url: "randomData.php",
						type: "GET",
					
						success: function (reqCode) {
							var fields = reqCode.split("|");
							$('#name').val(fields[0]);
							$('#from').val(fields[1]);
							$('#to').val(fields[2]);
							$('#location option[value="' + fields[3] + '"]').attr('selected',true);
						}
					});
				});
				
				$('#beginNow').click(function() {
					var increment = $('#beginAdd option:selected').val();
					$('#from').val(getCurTime(increment));
				});
				
				$('#endNow').click(function() {
					var increment = $('#endAdd option:selected').val();
					$('#to').val(getCurTime(increment));
				});
				
				$('#change').click(function() {
					$.ajax({
						url: "getUser.php?id=" + $('#selChange option:selected').val(),
						type: "GET",
					
						success: function (reqCode) {
							var fields = reqCode.split("|");
							$('#name').val(fields[0]);
							$('#from').val(fields[1]);
							$('#to').val(fields[2]);
							$('#location option[value="' + fields[3] + '"]').attr('selected',true);
							$('#accessId').val(fields[4]);
						}
					});
				});
			});
			
			function getCurTime(offset) {
				var now = new Date();
				var d = new Date(now.getTime() + (offset * 60 * 1000));
				var day = ('0' + d.getDate()).slice(-2);
				var month = ('0' + (d.getMonth()+1)).slice(-2);
				var year = d.getFullYear();
				var hour = ('0' + d.getHours()).slice(-2);
				var min = ('0' + d.getMinutes()).slice(-2);
				
				return year + "-" + month + "-" + day + " " + hour + ":" + min;
			}
		</script>
		<?php echo $return["head"]; ?>
	</head>
	
	<body>
		<?php
			echo $return["content"];
		?>
		<div id="debug"></div>
	</body>
</html>

<?php
	//-----------------------------------------------------
			
	function form($dbh) {
		$content = '
		<h2>Benutzerdaten</h2>
		<form action="?action=save" method="post">
			<table border="0" cellspacing="0" cellpadding="4">
				<tr>
					<td>Name</td>
					<td><input type="text" name="name" id="name" size="35" /></td>
				</tr>
				<tr>
					<td>Ort</td>
					<td>
						<select name="location" id="location">';
							$result = $dbh->query("SELECT Location FROM `lock` ORDER BY Location");
							while (($loc = $result->fetchColumn()) !== false) {
								$content .= '
							<option value="'.xsafe($loc).'">'.xsafe($loc).'</option>';
							}
							$content .= '
						</select>
					</td>
				</tr>
				<tr>
					<td>Begin</td>
					<td>
						<input type="text" name="begin" id="from" />
						&nbsp;&nbsp;&nbsp;jetzt
						<select id="beginAdd">
							<option value="0">+0</option>
							<option value="1">+1</option>
							<option value="2">+2</option>
							<option value="3">+3</option>
							<option value="4">+4</option>
							<option value="5">+5</option>
							<option value="6">+6</option>
							<option value="7">+7</option>
							<option value="8">+8</option>
							<option value="9">+9</option>
							<option value="10">+10</option>
						</select>
						<input type="button" value="Min" id="beginNow">
					</td>
				</tr>
				<tr>
					<td>Ende</td>
					<td>
						<input type="text" name="end" id="to" />
						&nbsp;&nbsp;&nbsp;jetzt
						<select id="endAdd">
							<option value="0">+0</option>
							<option value="1">+1</option>
							<option value="2">+2</option>
							<option value="3">+3</option>
							<option value="4">+4</option>
							<option value="5">+5</option>
							<option value="6">+6</option>
							<option value="7">+7</option>
							<option value="8">+8</option>
							<option value="9">+9</option>
							<option value="10">+10</option>
						</select>
						<input type="button" value="Min" id="endNow">
					</td>
				</tr>
			</table><br/>
			<input type="hidden" value="-1" name="accessId" id="accessId" />
			<input type="submit" value="speichern">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<input type="button" id="randVals" value="zufällig" />
			<div id="debug"></div>
		</from>';
		
		// for changing and deleting values
		
		$content .= '<br/>
		<hr>
		<br/>
		<h2>Daten ändern</h2>
		<select name="ids" id="selChange">';
		if(count($_SESSION['accessEntryList']) > 0) {
			foreach($_SESSION['accessEntryList'] as $keyid => $accessEntry) {
				if($keyid > 0)
					$content .= '
			<option value="'.$accessEntry->id.'">'.$accessEntry->id.' ('.$accessEntry->firstName.' '.$accessEntry->lastName.')</option>';
			}
		}
		$content .= '
		</select>
		<input type="button" value="ändern" id="change">
	';
		
		
		$return["content"] = $content;
		return $return;
	}
	
	function save() {								
		//regex to check date time string
		$pattern = '/^([\d]{4})[-](0[1-9]|1[0-2])[-](0[1-9]|1[0-9]|2[0-9]|3[0-1])[ ](0[0-9]|1[0-9]|2[0-3])[:]([0-5][0-9])/';
		
		if((1 == preg_match($pattern, $_POST["begin"])) && (1 == preg_match($pattern, $_POST["end"]))) {
			
			$nameArray = explode(" ", $_POST["name"]);
			
			if(count($_SESSION['accessEntryList']) == 0) //dummy eintrag erzeugen
				$_SESSION['accessEntryList'][0] = new AccessEntry();

			if($_POST["accessId"] == -1) {
				$_SESSION['accessEntryList'][] = new AccessEntry(count($_SESSION['accessEntryList']), $nameArray[0], $nameArray[1], $_POST["location"], $_POST["begin"], $_POST["end"]);
			}
			else {
				$_SESSION['accessEntryList'][$_POST["accessId"]] = new AccessEntry($_POST["accessId"], $nameArray[0], $nameArray[1], $_POST["location"], $_POST["begin"], $_POST["end"]);
			}
			
			$return["content"] =  '<div class="succ" >Benutzer erfolgreich eingetragen</div>';
		}	
		else
			$return["content"] =  '<div class="err" >Benutzer konnte nicht angelegt werden!</div>';
		$return["head"] = '<meta http-equiv="refresh" content="1;url=?action=form">';
		return $return;
	}
	
	function delete() {
	
	}	
?>
