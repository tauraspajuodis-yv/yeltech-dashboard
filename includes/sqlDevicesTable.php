<?php
require_once 'dbh.inc.php';
session_start();

$function = $_POST['function'];

if ($function == 'showDevices') {
	// Display all devices to super admins
	if ($_SESSION['roleId'] == 4 || $_SESSION['roleId'] == 3) {
		$groupFilter = $_SESSION['groupId'];
	} else {
		$groupFilter = $_POST['groupId'];
	}

	$searchString = $_POST['pageSearchString'];

	$productTypeSearch = '';
	if (isset($_POST['selectedProducts'])) {
		if (is_array($_POST['selectedProducts'])) {
			for ($i = 0; $i < count($_POST['selectedProducts']); $i++) {
				if ($i == count($_POST['selectedProducts'])-1) {
					$productTypeSearch .= "devices.productId = {$_POST['selectedProducts'][$i]}";
				} else {
					$productTypeSearch .= "devices.productId = {$_POST['selectedProducts'][$i]} OR ";
				}
			}
		} else {
			$productTypeSearch = "devices.productId = {$_POST['selectedProducts']}";
		}
	} else {
		$productTypeSearch = "devices.productId = '-1'";
	}

	$devicesPerPage = $_POST['devicesPerPage'];
	$offset = $_POST['offset'];

	$sql = "
	SELECT devices.deviceId, devices.deviceName, devices.deviceAlias, devices.nextCalibrationDue, devices.deviceStatus, devices.customLocation, `groups`.groupName as groupName
	FROM devices
	LEFT JOIN `groups` on devices.groupId = `groups`.groupId
	WHERE ($productTypeSearch) AND (devices.groupId = $groupFilter) AND (devices.deviceName LIKE '%$searchString%' OR devices.deviceAlias LIKE '%$searchString%')
	ORDER BY `groups`.groupName ASC, devices.deviceName ASC
	LIMIT $devicesPerPage OFFSET $offset
	";

	$resultArray = array();

	$result = mysqli_query($conn, $sql);
	$resultCheck = mysqli_num_rows($result);

	if ($resultCheck > 0) {
		while ($row = mysqli_fetch_assoc($result)) {
			// Find location from last status message for each device
			$sql2 = "
			SELECT smsStatus.latitude, smsStatus.longitude 
			FROM smsStatus 
			WHERE ((latitude IS NOT NULL AND longitude IS NOT NULL) AND (deviceId = {$row['deviceId']})) 
			ORDER BY smsStatusId DESC LIMIT 1;
			";
			
			$result2 = mysqli_query($conn, $sql2);
			$resultCheck = mysqli_num_rows($result2);
			
			if ($resultCheck > 0) {
				while ($row2 = mysqli_fetch_assoc($result2)) {
					$row += $row2;
				}
			}

			$resultArray[] = $row;
		}
	}


	$sqlTotal = "
	SELECT COUNT(*) as totalRows FROM devices WHERE ($productTypeSearch) AND (devices.groupId = $groupFilter) AND (devices.deviceName LIKE '%$searchString%' OR devices.deviceAlias LIKE '%$searchString%')
	";

	$result = mysqli_query($conn, $sqlTotal);
	$resultCheck = mysqli_num_rows($result);

	if ($resultCheck > 0) {
		while ($row = mysqli_fetch_assoc($result)) {
			// Return power role id
			$powerRole = array('powerRole' => $_SESSION['roleId']);
			$row += $powerRole;
			$resultArray[] = $row;
		}
	}

	echo json_encode($resultArray);
	exit();
}


?>