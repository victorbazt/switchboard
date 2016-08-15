<html>
<head>
<script>
function pageScroll() 
{
    window.scrollBy(0,10); // horizontal and vertical scroll increments
   	scrolldelay = setTimeout('pageScroll()',350); // scrolls every 100 milliseconds
}
</script>

<style>
body {
font-family: lucida console;
color: #aca89a;
font-size: 11px;	
 overflow-x: hidden !important;
}
.nospecialist {
color: #F26C4F;
}

::-webkit-scrollbar{ width: 10px; height: 10px; }
::-webkit-scrollbar-button:start:decrement, ::-webkit-scrollbar-button:end:increment{ background-color: #7EA7D8; }
::-webkit-scrollbar-track-piece:vertical{ background-color: #ffffff; }
::-webkit-scrollbar-track-piece:horizontal{ background-color: #445faf; }
::-webkit-scrollbar-thumb:vertical{ background: #7EA7D8  no-repeat center; }
</style>

</head>

<body>
<script>pageScroll();</script>
<?php 

ini_set('max_execution_time', 3000000);
date_default_timezone_set("Asia/Singapore");
include("connect.php");
mysql_query(" UPDATE lastlogin SET start='1' ");

$runs = Settings('Runs');
$tickets = array();
$row = '';

if(CheckDMAvailability())
{
	exit();
}
////////////////////////////////////////////////////////////NUMBER OF AUTO-DM RUNS BASED ON USER INPUT/////////////////////////////////////////////////////////
for($x=0;$x<$runs;$x++)
{

	$workgroup = Settings('Workgroup');
	$SearchRegion = Settings('Search Region');
	$NoRegion = Settings('No Region');
	$Sleeptime = Settings('Sleep');
	$titlelength = Settings('Title Length');
	$tablename = Settings('Table Name');
	$frtablename = Settings('FR Table Name');
	$titlesearch = Settings('Title Search');

	$current_region = RegionSelector();
	CheckUserLogin($current_region);
	unset($tickets);
	CheckStop();
	$rtemp = '';
	if(RegionSelector() == '' || RegionSelector() == 'NONE')
		{
			echo 'Auto-DM will not start if no Region was set at the current time. Please assign immediately!<br/>';
			exit();
		}

	echo "Run Time: ".date('h:i:s A')."<br/>";
	echo 'currently running. . .<br/><br/>	';
	echo 'extracting IM tickets<br/>Please Wait. . .<br/><br/>	';
	flush_buffers();
	if(CheckCsvFile($tablename) == '-1')
		continue;

	//open and assign open-tickets.csv values to array
	$handle = fopen("C:\wamp\www\AutoDM\OPEN-TICKETS.csv", "r");
	$i=0; 
	while (($data = fgetcsv($handle, 5000, ",")) !== FALSE) 
	{
		$csvrow = print_r($data, true);
		$tickets[$i] = $data;
		$i+=1;
	}
	fclose($handle);
	$max = '1';
	if($tickets[1][0] != '')
	{
		$max = count($tickets);
	}
	echo '['.date('h:i:s A').'] Extracted a total of '.($max-1).'  Open IM ticket/s.<br/><br/>';
	flush_buffers();
///////////////////////////////////////////////////////LOOP BASED ON THE NUMBER OF TICKETS/////////////////////////////////////////////////////////////////////
	for($i=1; $i<$max;$i++)
	{
		$current_region = RegionSelector();
		flush_buffers();
		CheckStop();
		if(CheckDMAvailability())
		exit();
		$ttitle =  ' '.$tickets[$i][8];
		$rtitle = ' '.preg_replace('/[^A-Za-z0-9]/', ' ', $ttitle);
		$title = ' '.$tickets[$i][8];
		$tnum = $tickets[$i][0];
		#Stop Run if no IM and Open on the csv
		if (!(preg_match('/IM+[0-9]{8}/', $tickets[$i][0]) && ($tickets[$i][3] == 'Open'))) 
		{
			echo "No Open Tickets<br/>";
			continue;
		}
		if ($workgroup != $tickets[$i][7]) 
		{
			echo $tnum." ticket not for your Workgroup.<br/>";
			flush_buffers();
			continue;
		}
		
		$priority = $tickets[$i][5];
		
		$rowapp = array();
		$allregion = array();
		$check_rowapp = mysql_query("SELECT * FROM RowApp order by Rows asc");
		while($fetch_rowapp = mysql_fetch_array($check_rowapp))
		{
			$rowapp[] = $fetch_rowapp['Rows'];
		}
		$check_allregion = mysql_query("SELECT * FROM Region order by Start asc");
		while($fetch_allregion = mysql_fetch_array($check_allregion))
		{
			$allregion[] = $fetch_allregion['Region'];
		}

///////////////////////////////////////////////////////////////TITLE SEARCH ON//////////////////////////////////////////////////////////////////////////
///////////////////////////////////////////////////////////////CRITICAL TICKET!!//////////////////////////////////////////////////////////////////////////
		if($titlesearch == 'Yes')
		{
			if ($priority == '1 - Critical') 
			{
				$temp = '';
				$temp_rpos = 200; //title length
				$temp_rowapp = '%^&*(';
				foreach($rowapp as $rowapps)
				{
					$rlen = strlen($rowapps);
					$rpos = stripos($rtitle, ' '.$rowapps.' ', 1);
					if($rpos < $temp_rpos && $rpos != '')
					{
						$temp_rpos = $rpos;
						$temp_rowapp = $rowapps;
					}
				}
				$rowapps = $temp_rowapp;
				if($temp_rpos < 200)
					{
						$temp = '1';
						checkXSpecialist($tnum, $current_region, $rowapps, $title, $titlesearch);
						continue;
					}	
					else if($temp_rpos < 200 && $NoRegion != 'DM')
					{
						$temp = '1';
						checkXSpecialist($tnum, $current_region, $rowapps, $title, $titlesearch);
						continue;
					}
					else
					{
						echo '<span class="nospecialist">'.$tnum.' No keyword found.</span><br/>';
						checkDM($tnum, $current_region, $priority, 'Rouge',$title, '', $titlesearch);
						continue;
					}
			}
	////////////////////////////////////////////////////////////////IF TICKET IS NOT CRITICAL/////////////////////////////////////////////////////////////////////////////////////////////////
			else
			{
				$temp_regionpos = 200;
				$region = '';
				foreach($allregion as $allregions)
				{
					$regionlen = strlen($allregions);
					$regionpos = stripos('  '.$rtitle, ' '.$allregions.' ', 1);
					if($regionpos < $temp_regionpos && $regionpos != '')
					{
						$temp_regionpos = $regionpos;
						$region = $allregions;
						$rtemp = '1';
					}
				}
				$temp = '';
				$temp_rpos = 200;
				$temp_rowapp = '%^&*(';
				foreach($rowapp as $rowapps)
				{
					$rlen = strlen($rowapps);
					$rpos = stripos($rtitle, ' '.$rowapps.' ', 1);
					if($rpos < $temp_rpos && $rpos != '')
					{
						$temp_rpos = $rpos;
						$temp_rowapp = $rowapps;
					}
				}
				$rowapps = $temp_rowapp;
				flush_buffers();
				if(($temp_rpos < 200) && ($rtemp == '1'))
				{
					$temp = '1';
					if($SearchRegion == 'Yes')
						CheckSpecialist($tnum, $region, $rowapps, $priority, $title, $titlesearch);	
					else
						CheckSpecialistNoRegion($tnum, $rowapps, $priority, $title, $titlesearch);	
					continue;
				}
				else if($temp_rpos < 200 && $NoRegion != 'DM')
				{
					$temp = '1';
					if($SearchRegion == 'Yes')
						CheckSpecialist($tnum, $current_region, $rowapps, $priority, $title, $titlesearch);
					else
						CheckSpecialistNoRegion($tnum, $rowapps, $priority, $title, $titlesearch);	
					continue;
				}
				else if($temp_rpos < 200 && $NoRegion == 'DM' && $rtemp != '1' && $SearchRegion == 'Yes')
				{
					$region = $current_region;
					$temp = '1';
					echo '<span class="nospecialist">No Region Tag found in the Title. Ticket will be assigned to Duty Manager.</span><br/>';
					checkDM($tnum, $region, $priority, 'Rouge',$title, '', $titlesearch);
					continue;
				}	
				else
				{
					echo '<span class="nospecialist">'.$tnum.' No keyword found.</span><br/>';
					checkDM($tnum, $current_region, $priority, 'Rouge',$title, '', $titlesearch);
					continue;
				}
			}
		}
///////////////////////////////////////////////////////////IF TITLE SEARCH IS SET TO NO//////////////////////////////////////////////////////////////////////
		if($titlesearch == 'No')
		{
			if ($priority == '1 - Critical')
			{
				NCheckXSpecialist($tnum, $current_region, '', $title, $titlesearch);
				continue;
			}
	////////////////////////////////////////////////////////////////IF TICKET IS NOT CRITICAL/////////////////////////////////////////////////////////////////////////////////////////////////
			else
			{
				$temp = '';
				$temp_regionpos = 200;
				$region = $current_region;
				foreach($allregion as $allregions)
				{
					$regionlen = strlen($allregions);
					$regionpos = stripos($rtitle, ' '.$allregions.' ', 1);
					if($regionpos < $temp_regionpos && $regionpos != '')
					{
						$temp_regionpos = $regionpos;
						$region = $allregions;
						$rtemp = '1';
					}
				}
						if($rtemp == '1')
						{
							$temp = '1';
							NCheckSpecialist($tnum, $region, '', $priority, $title, $titlesearch);
							continue 2;
						}
						else if($NoRegion != 'DM')
						{
							$temp = '1';
							NCheckSpecialist($tnum, $region, '', $priority, $title, $titlesearch);
							continue 2;
						}	
			}
			if($temp != '1')
			{
				echo '<span class="nospecialist">No Region Tag Found. Ticket will be assigned to Duty Manager.</span><br/>';
				checkDM($tnum, $region, $priority, 'Rouge',$title, '', $titlesearch);
				continue 2;
			}
		}
		echo "<br/><br/><br/><br/>";
		flush_buffers();
	}
//////////////////////////////////////////////////////////////FOR FR TICKETS///////////////////////////////////////////////////////////////////////////////////
	$current_region = RegionSelector();
	CheckUserLogin($current_region);
	unset($tickets);
	CheckStop();
	$rtemp = '';
	if(RegionSelector() == '' || RegionSelector() == 'NONE')
		{
			echo 'Auto-DM will not start if no Region was set at the current time. Please assign immediately!<br/>';
			exit();
		}

	echo '<br/><br/>';
	echo "Run Time: ".date('h:i:s A')."<br/>";
	echo '<br/>';
	echo 'extracting FR tickets<br/>Please Wait. . .<br/><br/>	';
	flush_buffers();
	if(CheckFRCsvFile($frtablename) == '-1')
		continue;

	//open and assign open-tickets.csv values to array
	$handle = fopen("C:\wamp\www\AutoDM\OPEN-FR-TICKETS.csv", "r");
	$i=0; 
	while (($data = fgetcsv($handle, 5000, ",")) !== FALSE) 
	{
		$csvrow = print_r($data, true);
		$tickets[$i] = $data;
		$i+=1;
	}
	fclose($handle);
	$max = '1';
	if($tickets[1][0] != '')
	{
		$max = count($tickets);
	}
	echo '['.date('h:i:s A').'] Extracted a total of '.($max-1).'  Open FR ticket/s.<br/><br/>';
	flush_buffers();
///////////////////////////////////////////////////////LOOP BASED ON THE NUMBER OF TICKETS/////////////////////////////////////////////////////////////////////
	for($i=1; $i<$max;$i++)
	{
		$current_region = RegionSelector();
		flush_buffers();
		CheckStop();
		if(CheckDMAvailability())
		exit();
		$ttitle =  ' '.$tickets[$i][8];
		$rtitle = ' '.preg_replace('/[^A-Za-z0-9]/', ' ', $ttitle);
		$title = ' '.$tickets[$i][8];
		$tnum = $tickets[$i][0];
		#Stop Run if no FR and Open on the csv
		if (!(preg_match('/FR+[0-9]{8}/', $tickets[$i][0]))) 
		{
			echo "No Open Tickets<br/>";
			continue;
		}
		if ($workgroup != $tickets[$i][7]) 
		{
			echo $tnum." ticket not for your Workgroup.<br/>";
			flush_buffers();
			continue;
		}
		
		$priority = $tickets[$i][5];
		
		$rowapp = array();
		$allregion = array();
		$check_rowapp = mysql_query("SELECT * FROM RowApp order by Rows asc");
		while($fetch_rowapp = mysql_fetch_array($check_rowapp))
		{
			$rowapp[] = $fetch_rowapp['Rows'];
		}
		$check_allregion = mysql_query("SELECT * FROM Region order by Start asc");
		while($fetch_allregion = mysql_fetch_array($check_allregion))
		{
			$allregion[] = $fetch_allregion['Region'];
		}

///////////////////////////////////////////////////////////////TITLE SEARCH ON//////////////////////////////////////////////////////////////////////////
///////////////////////////////////////////////////////////////CRITICAL TICKET!!//////////////////////////////////////////////////////////////////////////
		if($titlesearch == 'Yes')
		{
			if ($priority == '1 - Critical') 
			{
				$temp = '';
				$temp_rpos = 200; //title length
				$temp_rowapp = '%^&*(';
				foreach($rowapp as $rowapps)
				{
					$rlen = strlen($rowapps);
					$rpos = stripos($rtitle, ' '.$rowapps.' ', 1);
					if($rpos < $temp_rpos && $rpos != '')
					{
						$temp_rpos = $rpos;
						$temp_rowapp = $rowapps;
					}
				}
				$rowapps = $temp_rowapp;
				if($temp_rpos < 200)
					{
						$temp = '1';
						checkXSpecialist($tnum, $current_region, $rowapps, $title, $titlesearch);
						continue;
					}	
					else if($temp_rpos < 200 && $NoRegion != 'DM')
					{
						$temp = '1';
						checkXSpecialist($tnum, $current_region, $rowapps, $title, $titlesearch);
						continue;
					}
					else
					{
						echo '<span class="nospecialist">'.$tnum.' No keyword found.</span><br/>';
						checkDM($tnum, $current_region, $priority, 'Rouge',$title, '', $titlesearch);
						continue;
					}
			}
	////////////////////////////////////////////////////////////////IF TICKET IS NOT CRITICAL/////////////////////////////////////////////////////////////////////////////////////////////////
			else
			{
				$temp_regionpos = 200;
				$region = '';
				foreach($allregion as $allregions)
				{
					$regionlen = strlen($allregions);
					$regionpos = stripos($rtitle, ' '.$allregions.' ', 1);
					if($regionpos < $temp_regionpos && $regionpos != '')
					{
						$temp_regionpos = $regionpos;
						$region = $allregions;
						$rtemp = '1';
					}
				}
				$temp = '';
				$temp_rpos = 200;
				$temp_rowapp = '%^&*(';
				foreach($rowapp as $rowapps)
				{
					$rlen = strlen($rowapps);
					$rpos = stripos($rtitle, ' '.$rowapps.' ', 1);
					if($rpos < $temp_rpos && $rpos != '')
					{
						$temp_rpos = $rpos;
						$temp_rowapp = $rowapps;
					}
				}
				$rowapps = $temp_rowapp;
				flush_buffers();
				if(($temp_rpos < 200) && ($rtemp == '1'))
				{
					$temp = '1';
					if($SearchRegion == 'Yes')
						CheckSpecialist($tnum, $region, $rowapps, $priority, $title, $titlesearch);	
					else
						CheckSpecialistNoRegion($tnum, $rowapps, $priority, $title, $titlesearch);	
					continue;
				}
				else if($temp_rpos < 200 && $NoRegion != 'DM')
				{
					$temp = '1';					
					if($SearchRegion == 'Yes')
						CheckSpecialist($tnum, $current_region, $rowapps, $priority, $title, $titlesearch);	
					else
						CheckSpecialistNoRegion($tnum, $rowapps, $priority, $title, $titlesearch);	
					continue;
				}
				else if($temp_rpos < 200 && $NoRegion == 'DM' && $rtemp != '1' && $SearchRegion == 'Yes')
				{
					$region = $current_region;
					$temp = '1';
					echo '<span class="nospecialist">No Region Tag found in the Title. Ticket will be assigned to Duty Manager.</span><br/>';
					checkDM($tnum, $region, $priority, 'Rouge',$title, '', $titlesearch);
					continue;
				}	
				else
				{
					echo '<span class="nospecialist">'.$tnum.' No keyword found.</span><br/>';
					checkDM($tnum, $current_region, $priority, 'Rouge',$title, '', $titlesearch);
					continue;
				}
			}
		}
///////////////////////////////////////////////////////////IF TITLE SEARCH IS SET TO NO//////////////////////////////////////////////////////////////////////
		if($titlesearch == 'No')
		{
			if ($priority == '1 - Critical')
			{
				NCheckXSpecialist($tnum, $current_region, '', $title, $titlesearch);
				continue;
			}
	////////////////////////////////////////////////////////////////IF TICKET IS NOT CRITICAL/////////////////////////////////////////////////////////////////////////////////////////////////
			else
			{
				$temp = '';
				$temp_regionpos = 200;
				$region = $current_region;
				foreach($allregion as $allregions)
				{
					$regionlen = strlen($allregions);
					$regionpos = stripos($rtitle, ' '.$allregions.' ', 1);
					if($regionpos < $temp_regionpos && $regionpos != '')
					{
						$temp_regionpos = $regionpos;
						$region = $allregions;
						$rtemp = '1';
					}
				}
						if($rtemp == '1')
						{
							$temp = '1';
							NCheckSpecialist($tnum, $region, '', $priority, $title, $titlesearch);
							continue 2;
						}
						else if($NoRegion != 'DM')
						{
							$temp = '1';
							NCheckSpecialist($tnum, $region, '', $priority, $title, $titlesearch);
							continue 2;
						}	
			}
			if($temp != '1')
			{
				echo '<span class="nospecialist">No Region Tag Found. Ticket will be assigned to Duty Manager.</span><br/>';
				checkDM($tnum, $region, $priority, 'Rouge',$title, '', $titlesearch);
				continue 2;
			}
		}
		echo "<br/>";
		flush_buffers();
	}
	echo "<br/><br/><br/><br/>";
	echo ($x+1)." of ".Settings("Runs")." Run/s<br/>";
	echo "Wait time before next run (".Settings("Sleep")." secs.)<br/><br/><br/><br/>";
	flush_buffers();
	//sleep($Sleeptime);
	for($y=0; $y<$Sleeptime; $y++)
	{
		sleep(1);
		CheckStop();

	}
}
echo 'AUTO DM STOPPED AT EXACTLY '.date('h:i:sA');




###############################################################################################################################################################
###############################################################################################################################################################
																		//FUNCTIONS
###############################################################################################################################################################
###############################################################################################################################################################

#Function to flush the output
function flush_buffers()
{ 
    ob_end_flush(); 
    flush(); 
    ob_start(); 
}
#Function to check/create Open-Tickets CSV File
function CheckCsvFile($tablename)
{
	$filedir = "C:\wamp\www\AutoDM";
	$filename = 'C:\wamp\www\AutoDM\OPEN-TICKETS.csv';
	if (file_exists($filename)) 
	{
		if(Extracttable($tablename) == '-1')
		{
			return -1;
		}
	}
	else 
	{
		if(!file_exists($filedir)) 
		{
			mkdir('C:/wamp/www/AutoDM/', 0700);
		}
		$OpenTicketFile = 'C:\wamp\www\AutoDM\OPEN-TICKETS.csv';
		$OpenTicketHandle = fopen($OpenTicketFile,'w') or die("can't open file");
		fclose($OpenTicketHandle);
		if(Extracttable($tablename) == '-1')
		{
			continue;
		}
	}
}
#Function to check/create Open-FR-Tickets CSV File
function CheckFRCsvFile($frtablename)
{
	$filedir = "C:\wamp\www\AutoDM";
	$filename = 'C:\wamp\www\AutoDM\OPEN-FR-TICKETS.csv';
	if (file_exists($filename)) 
	{
		if(ExtractFRtable($frtablename) == '-1')
		{
			return -1;
		}
	}
	else 
	{
		if(!file_exists($filedir)) 
		{
			mkdir('C:/wamp/www/AutoDM/', 0700);
		}
		$OpenTicketFile = 'C:\wamp\www\AutoDM\OPEN-TICKETS.csv';
		$OpenTicketHandle = fopen($OpenTicketFile,'w') or die("can't open file");
		fclose($OpenTicketHandle);
		if(ExtractFRtable($frtablename) == '-1')
		{
			continue;
		}
	}
}

#Function to Relogin to SM9
function Relogin() 
{
	$check_lastlog = mysql_query("select * from lastlogin");
	$fetch_lastlog = mysql_fetch_array($check_lastlog);
	$username = $fetch_lastlog['username'];
	$password = $fetch_lastlog['password'];
	//	do {
	$iim1 = new COM("imacros");
	$s = $iim1->iimInit("-runner", FALSE);
	$s = $iim1->iimSet("-var_username","$username");
	$s = $iim1->iimSet("-var_password","$password");
	$s = $iim1->iimPlay("C:\wamp\www\AutoDM\Macros\SM LOGIN.iim");
	if ($s == 1) 
	{
		header("location:index.php");
	}
	else 
	{
		header("location:../index.php?error=1");
	}
		//	} while ($s != '1');
}

#Function to Update Specialist Status
function updateStatus ()
{
	$allAvailable = mysql_query("update specialist SET Status='Available' ");
	$cdate = date('Y-m-d');
	$check_leave = mysql_query("select * from schedules where (sstatus='CDO' or sstatus='VL' or sstatus='SL' or sstatus='MEETING' or sstatus='TRAINING') and edate > '$cdate' ");
	while ($row = mysql_fetch_array($check_leave))
	{	
		if ( ( (substr($row['edate'] , 11, 5) >= date('H:i') ) && ($cdate >= substr($row['sdate'] , 0 , 10)) ) || ( ($cdate >= substr($row['sdate'] , 0 , 10)) && ($cdate < substr($row['edate'] , 0 , 10)) ) )
		{
			$Lspecialist = $row['pid'];
			$LStatus = $row['sstatus'];
			$setLeave = mysql_query("update specialist SET Status='$LStatus' where Name='$Lspecialist' ");
		}
	}
	
	
}
#Function to check current DM to assign a Rouge Ticket
function checkDM($tnum, $current_region, $priority, $type, $title, $row, $titlesearch)
{
	updateRole();
	$check_region = mysql_query("SELECT * FROM Region where Region='$current_region' ");
	$fetch_region = mysql_fetch_array($check_region);
	$start = $fetch_region['Start'];
	//60*240 = 4hrs shift of DM
	if(($fetch_region['Start'] > $fetch_region['End']) && ((date('H:i:s') > '00:00:00') && (date('H:i:s') < '12:59:59')))
	{
		$timestamp = strtotime($start) -60*60*20;
	}
	else
	{
		$timestamp = strtotime($start) + 60*240;
	}
	$time = date('H:i:s', $timestamp);
	$dm = '';
	if (strtotime(date('H:i:s')) > $timestamp)
	{
		$dm = 'DM2';
	}
	else
	{
		$dm = 'DM1';
	}
	$check_dm = mysql_query("select * from specialist where Region='$current_region' and Status='Available' and Role='$dm'");
	$fetch_specialist = mysql_fetch_array($check_dm);
	if($fetch_specialist['Name'] == '')
		{
			$check_dm = mysql_query("select * from specialist where Region='$current_region' and Status='Available' and Role='DM1'");
			$fetch_specialist = mysql_fetch_array($check_dm);
		}
	if($fetch_specialist['Name'] == '')
		{
			$check_dm = mysql_query("select * from specialist where Region='$current_region' and Status='Available' and Role='DM2'");
			$fetch_specialist = mysql_fetch_array($check_dm);
		}
	if($fetch_specialist['Name'] == '')
		{
			echo 'No Region/DM was set at this time. Please check immediately!!!<br/>(For Error Checking: Current Region is '.$current_region.' DM is '.$dm.')';
			exit;
		}
	AssignTicket($tnum,$fetch_specialist['Name'], $priority, $type, $title, $row, $titlesearch);
	return;
	
}
#Function to check who will be the assignee for Critical Tickets
function CheckXSpecialist($tnum,$region,$row,$title, $titlesearch) 
{
	updateStatus ();
	if (($row != '') || (($row == '') && ($titlesearch != 'Yes')))
	{
		$check_specialist = mysql_query("select * from specialist where Row LIKE '% $row %' and Region='$region' and Status='Available' and Type='L1' and CritTicket = (select min(CritTicket) from specialist where Region='$region' and Row LIKE '% $row %' and Type='L1' and Status='Available') order by Name");
		$fetch_specialist = mysql_fetch_array($check_specialist);
		if($fetch_specialist['Name'] == '')
		{
			$check_specialist = mysql_query("select * from specialist where Row LIKE '% $row %' and Region='$region' and Status='Available' and Type='L2' and CritTicket = (select min(CritTicket) from specialist where Region='$region' and Row LIKE '% $row %' and Type='L2' and Status='Available') order by Name");
			$fetch_specialist = mysql_fetch_array($check_specialist);
		}
		if($fetch_specialist['Name'] == '')
		{
			$check_specialist = mysql_query("select * from specialist where Row LIKE '% $row %' and Region='$region' and Status='Available' and Type='L3' and CritTicket = (select min(CritTicket) from specialist where Region='$region' and Row LIKE '% $row %' and Type='L3' and Status='Available') order by Name");
			$fetch_specialist = mysql_fetch_array($check_specialist);
		}
		if($fetch_specialist['Name'] == '')
		{
			$check_specialist = mysql_query("select * from specialist where Row LIKE '% $row %' and Region='$region' and Status='Available' and Type='L4' and CritTicket = (select min(CritTicket) from specialist where Region='$region' and Row LIKE '% $row %' and Type='L4' and Status='Available') order by Name");
			$fetch_specialist = mysql_fetch_array($check_specialist);
		}
		if($fetch_specialist['Name'] == '')
		{
			$check_specialist = mysql_query("select * from specialist where Row LIKE '% $row %' and Region='$region' and Status='Available' and Type='L5' and CritTicket = (select min(CritTicket) from specialist where Region='$region' and Row LIKE '% $row %' and Type='L5' and Status='Available') order by Name");
			$fetch_specialist = mysql_fetch_array($check_specialist);
		}
		if($fetch_specialist['Name'] == '')
		{
			echo '<span class="nospecialist">No specialist available for keyword ['.$row.'].</span><br/>';
			checkDM($tnum, $region, '1 - Critical', 'Rouge',$title, $row, $titlesearch);
			return;
		}
	}
	else 
	{
			echo '<span class="nospecialist">'.$tnum.' No keyword found.</span><br/>';
			checkDM($tnum, $region, '1 - Critical', 'Rouge',$title, $row, $titlesearch);
			return;
	}
	AssignTicket($tnum,$fetch_specialist['Name'],'1 - Critical','',$title, $row, $titlesearch);
	return;
}
#Function to check who will be the assignee for Non-Critical Tickets
function CheckSpecialist($tnum,$region,$row,$priority,$title, $titlesearch) 
{
	updateStatus ();
	if ((($row != '') || (($row == '') && ($titlesearch != 'Yes'))) && (preg_match('/IM+[0-9]{8}/', $tnum)))
	{
		$check_specialist = mysql_query("select * from specialist where Row LIKE '% $row %' and Region='$region' and Status='Available' and Type='L1' and Ticket = (select min(Ticket) from specialist where Region='$region' and Row LIKE '% $row %' and Type='L1' and Status='Available') order by Name");
		$fetch_specialist = mysql_fetch_array($check_specialist);
		if($fetch_specialist['Name'] == '')
		{
			$check_specialist = mysql_query("select * from specialist where Row LIKE '% $row %' and Region='$region' and Status='Available' and Type='L2' and Ticket = (select min(Ticket) from specialist where Region='$region' and Row LIKE '% $row %' and Type='L2' and Status='Available') order by Name");
			$fetch_specialist = mysql_fetch_array($check_specialist);
		}
		if($fetch_specialist['Name'] == '')
		{
			$check_specialist = mysql_query("select * from specialist where Row LIKE '% $row %' and Region='$region' and Status='Available' and Type='L3' and Ticket = (select min(Ticket) from specialist where Region='$region' and Row LIKE '% $row %' and Type='L3' and Status='Available') order by Name");
			$fetch_specialist = mysql_fetch_array($check_specialist);
		}
		if($fetch_specialist['Name'] == '')
		{
			$check_specialist = mysql_query("select * from specialist where Row LIKE '% $row %' and Region='$region' and Status='Available' and Type='L4' and Ticket = (select min(Ticket) from specialist where Region='$region' and Row LIKE '% $row %' and Type='L4' and Status='Available') order by Name");
			$fetch_specialist = mysql_fetch_array($check_specialist);
		}
		if($fetch_specialist['Name'] == '')
		{
			$check_specialist = mysql_query("select * from specialist where Row LIKE '% $row %' and Region='$region' and Status='Available' and Type='L5' and Ticket = (select min(Ticket) from specialist where Region='$region' and Row LIKE '% $row %' and Type='L5' and Status='Available') order by Name");
			$fetch_specialist = mysql_fetch_array($check_specialist);
		}
		if($fetch_specialist['Name'] == '')
		{
				echo '<br/><span class="nospecialist">No specialist available for keyword ['.$row.'].</span><br/>';
				//exit;
			checkDM($tnum, $region, $priority, 'Rouge',$title, $row, $titlesearch);
			return;
		}
	}
	else if ((($row != '') || (($row == '') && ($titlesearch != 'Yes'))) && (preg_match('/FR+[0-9]{8}/', $tnum)))
	{
		$check_specialist = mysql_query("select * from specialist where Row LIKE '% $row %' and Region='$region' and Status='Available' and Type='L1' and FrTicket = (select min(FrTicket) from specialist where Region='$region' and Row LIKE '% $row %' and Type='L1' and Status='Available') order by Name");
		$fetch_specialist = mysql_fetch_array($check_specialist);
		if($fetch_specialist['Name'] == '')
		{
			$check_specialist = mysql_query("select * from specialist where Row LIKE '% $row %' and Region='$region' and Status='Available' and Type='L2' and FrTicket = (select min(FrTicket) from specialist where Region='$region' and Row LIKE '% $row %' and Type='L2' and Status='Available') order by Name");
			$fetch_specialist = mysql_fetch_array($check_specialist);
		}
		if($fetch_specialist['Name'] == '')
		{
			$check_specialist = mysql_query("select * from specialist where Row LIKE '% $row %' and Region='$region' and Status='Available' and Type='L3' and FrTicket = (select min(FrTicket) from specialist where Region='$region' and Row LIKE '% $row %' and Type='L3' and Status='Available') order by Name");
			$fetch_specialist = mysql_fetch_array($check_specialist);
		}
		if($fetch_specialist['Name'] == '')
		{
			$check_specialist = mysql_query("select * from specialist where Row LIKE '% $row %' and Region='$region' and Status='Available' and Type='L4' and FrTicket = (select min(FrTicket) from specialist where Region='$region' and Row LIKE '% $row %' and Type='L4' and Status='Available') order by Name");
			$fetch_specialist = mysql_fetch_array($check_specialist);
		}
		if($fetch_specialist['Name'] == '')
		{
			$check_specialist = mysql_query("select * from specialist where Row LIKE '% $row %' and Region='$region' and Status='Available' and Type='L5' and FrTicket = (select min(FrTicket) from specialist where Region='$region' and Row LIKE '% $row %' and Type='L5' and Status='Available') order by Name");
			$fetch_specialist = mysql_fetch_array($check_specialist);
		}
		if($fetch_specialist['Name'] == '')
		{
				echo '<br/><span class="nospecialist">No specialist available for keyword ['.$row.'].</span><br/>';
				//exit;
			checkDM($tnum, $region, $priority, 'Rouge',$title, $row, $titlesearch);
			return;
		}
	}
	else 
	{
		checkDM($tnum, $region, $priority, 'Rouge',$title, $row, $titlesearch);
		return;
	}
	AssignTicket($tnum,$fetch_specialist['Name'],$priority, '',$title, $row, $titlesearch);
	return;
}
#Function to check who will be the assignee for Non-Critical Tickets without Region Search
function CheckSpecialistNoRegion($tnum,$row,$priority,$title, $titlesearch) 
{
	updateStatus ();
	if ((($row != '') || (($row == '') && ($titlesearch != 'Yes'))) && (preg_match('/IM+[0-9]{8}/', $tnum)))
	{
		$check_specialist = mysql_query("select * from specialist where Row LIKE '% $row %' and Status='Available' and Type='L1' and Ticket = (select min(Ticket) from specialist where Row LIKE '% $row %' and Type='L1' and Status='Available') order by Name");
		$fetch_specialist = mysql_fetch_array($check_specialist);
		if($fetch_specialist['Name'] == '')
		{
			$check_specialist = mysql_query("select * from specialist where Row LIKE '% $row %' and Status='Available' and Type='L2' and Ticket = (select min(Ticket) from specialist where Row LIKE '% $row %' and Type='L2' and Status='Available') order by Name");
			$fetch_specialist = mysql_fetch_array($check_specialist);
		}
		if($fetch_specialist['Name'] == '')
		{
			$check_specialist = mysql_query("select * from specialist where Row LIKE '% $row %' and Status='Available' and Type='L3' and Ticket = (select min(Ticket) from specialist where Row LIKE '% $row %' and Type='L3' and Status='Available') order by Name");
			$fetch_specialist = mysql_fetch_array($check_specialist);
		}
		if($fetch_specialist['Name'] == '')
		{
			$check_specialist = mysql_query("select * from specialist where Row LIKE '% $row %' and Status='Available' and Type='L4' and Ticket = (select min(Ticket) from specialist where Row LIKE '% $row %' and Type='L4' and Status='Available') order by Name");
			$fetch_specialist = mysql_fetch_array($check_specialist);
		}
		if($fetch_specialist['Name'] == '')
		{
			$check_specialist = mysql_query("select * from specialist where Row LIKE '% $row %' and Status='Available' and Type='L5' and Ticket = (select min(Ticket) from specialist where Row LIKE '% $row %' and Type='L5' and Status='Available') order by Name");
			$fetch_specialist = mysql_fetch_array($check_specialist);
		}
		if($fetch_specialist['Name'] == '')
		{
				echo '<br/><span class="nospecialist">No specialist available for keyword ['.$row.'].</span><br/>';
				//exit;
			checkDM($tnum, $region, $priority, 'Rouge',$title, $row, $titlesearch);
			return;
		}
	}
	else if ((($row != '') || (($row == '') && ($titlesearch != 'Yes'))) && (preg_match('/FR+[0-9]{8}/', $tnum)))
	{
		$check_specialist = mysql_query("select * from specialist where Row LIKE '% $row %' and Status='Available' and Type='L1' and FrTicket = (select min(FrTicket) from specialist where Row LIKE '% $row %' and Type='L1' and Status='Available') order by Name");
		$fetch_specialist = mysql_fetch_array($check_specialist);
		if($fetch_specialist['Name'] == '')
		{
			$check_specialist = mysql_query("select * from specialist where Row LIKE '% $row %' and Status='Available' and Type='L2' and FrTicket = (select min(FrTicket) from specialist where Row LIKE '% $row %' and Type='L2' and Status='Available') order by Name");
			$fetch_specialist = mysql_fetch_array($check_specialist);
		}
		if($fetch_specialist['Name'] == '')
		{
			$check_specialist = mysql_query("select * from specialist where Row LIKE '% $row %' and Status='Available' and Type='L3' and FrTicket = (select min(FrTicket) from specialist where Row LIKE '% $row %' and Type='L3' and Status='Available') order by Name");
			$fetch_specialist = mysql_fetch_array($check_specialist);
		}
		if($fetch_specialist['Name'] == '')
		{
			$check_specialist = mysql_query("select * from specialist where Row LIKE '% $row %' and Status='Available' and Type='L4' and FrTicket = (select min(FrTicket) from specialist where Row LIKE '% $row %' and Type='L4' and Status='Available') order by Name");
			$fetch_specialist = mysql_fetch_array($check_specialist);
		}
		if($fetch_specialist['Name'] == '')
		{
			$check_specialist = mysql_query("select * from specialist where Row LIKE '% $row %' and Status='Available' and Type='L5' and FrTicket = (select min(FrTicket) from specialist where Row LIKE '% $row %' and Type='L5' and Status='Available') order by Name");
			$fetch_specialist = mysql_fetch_array($check_specialist);
		}
		if($fetch_specialist['Name'] == '')
		{
				echo '<br/><span class="nospecialist">No specialist available for keyword ['.$row.'].</span><br/>';
				//exit;
			checkDM($tnum, $region, $priority, 'Rouge',$title, $row, $titlesearch);
			return;
		}
	}
	else 
	{
		checkDM($tnum, $region, $priority, 'Rouge',$title, $row, $titlesearch);
		return;
	}
	AssignTicket($tnum,$fetch_specialist['Name'],$priority, '',$title, $row, $titlesearch);
	return;
}
#Function to Assign Ticket called by checkxspecialist or checkspecialist function
function AssignTicket($tnum,$assignee,$priority,$type,$title,$row, $titlesearch)
{
	$TextPaging = Settings('Text Paging');
	$row = strtoupper($row);
	if($assignee == '')
	{
		echo $tnum.' Error on getting specialist. Check Availability of specialist.<br/>';
	}
	if ($type == 'Rouge')
	{
		$update = 'Rouge Ticket Assigned to DM '.$assignee.'. [AUTO-DM]';
	}
	else
	{
		if(($row == '') && ($titlesearch == 'No'))
		{
			$update = "Assigned to ".$assignee.". [AUTO-DM]";
		}
		else if(($row == '') && ($titlesearch == 'Yes'))
		{
			$update = "No Keyword Found".PHP_EOL."Assigned to ".$assignee.". [AUTO-DM]";
		}
		else
		{
			$update = 'Keyword Found: '.$row.PHP_EOL.'Assigned to '.$assignee.'. [AUTO-DM]';
		}
	}
 	$iim1 = new COM("imacros");
	$s = $iim1->iimOpen("-runner", FALSE);
	$ticketnum = $tnum;
	$status = 'Accepted';
	$assigned = $assignee;
 	$s = $iim1->iimSet("-var_ticketnum","$ticketnum");
	if (preg_match('/IM+[0-9]{8}/', $ticketnum))
	{
		$s = $iim1->iimPlay("C:\wamp\www\AutoDM\Macros\check assignee.iim");
	}
	else if (preg_match('/FR+[0-9]{8}/', $ticketnum))
	{
		$s = $iim1->iimPlay("C:\wamp\www\AutoDM\Macros\check assignee fr.iim");
	}
	else
	{
		echo 'Ticket not INCIDENT nor FULFILLMENT REQUEST.<br/>';
		continue;
	}
	$emptyassignee = $iim1->iimGetLastExtract;		
	if( $s != 1)
	{
		echo '<span class="nospecialist">'.$ticketnum.' Failed to Open Ticket to check Assignee.</span><br/>';
	}
	if(!($emptyassignee == '[EXTRACT]'))
	{
		$iim1 = new COM("imacros");
		$s = $iim1->iimOpen("-runner", FALSE);
		$s = $iim1->iimPlay("C:\wamp\www\AutoDM\Macros\cancel assign.iim");
		if( $s != 1)
		echo 'Operation Interrupted.<br/>';
		else
		echo $ticketnum.' Already been assigned to a specialist by someone else.<br/>';
	}
	else
	{
		if (preg_match('/IM+[0-9]{8}/', $ticketnum))
		{
			$iim1 = new COM("imacros");
			$s = $iim1->iimOpen("-runner", FALSE);
			$s = $iim1->iimSet("-var_status","$status");
			$s = $iim1->iimSet("-var_assignee","$assigned");
			$s = $iim1->iimSet("-var_update","$update");
			$s = $iim1->iimPlay("C:\wamp\www\AutoDM\Macros\assign ticket.iim");
		}
		else if (preg_match('/FR+[0-9]{8}/', $ticketnum))
		{
			$iim1 = new COM("imacros");
			$s = $iim1->iimOpen("-runner", FALSE);
			$s = $iim1->iimSet("-var_status","$status");
			$s = $iim1->iimSet("-var_assignee","$assigned");
			$s = $iim1->iimSet("-var_update","$update");
			$s = $iim1->iimPlay("C:\wamp\www\AutoDM\Macros\assign fr ticket.iim");
		}
		if( $s != 1)
		{
			echo '<span class="nospecialist">'.$tnum.' Failed to Assign Ticket.</span><br/><br/>';
			return;
		}
		else
		{
		if ($s == '1')
		{
			if (($priority == '1 - Critical') && (preg_match('/IM+[0-9]{8}/', $ticketnum)) && ($type != 'Rouge'))
			{
				$update_ticket = mysql_query("update specialist SET CritTicket=CritTicket+1 where Name='$assignee'");
			}
			else if ($type == 'Rouge')
			{
				$update_ticket = mysql_query("update specialist SET Rouge=Rouge+1 where Name='$assignee'");
			}
			else if (preg_match('/FR+[0-9]{8}/', $ticketnum))
			{
				$update_ticket = mysql_query("update specialist SET FrTicket=FrTicket+1 where Name='$assignee'");
			}
			else 
			{
				$update_ticket = mysql_query("update specialist SET Ticket=Ticket+1 where Name='$assignee'");
			}
			echo '<br/>['.date('h:i:s A').'] '.$tnum.' '.$title.'<br/>'.$priority.', '.$update.'<br/><br/>';
			$check_egn = mysql_query("select * from specialist where Name='$assigned'");
			$fetch_egn = mysql_fetch_array($check_egn);
			//$check_dmphone = mysql_query("select * from specialist where Name='DM.PHONE'");
			//$fetch_dmphone = mysql_fetch_array($check_dmphone);
			$message = ' This Ticket has been assigned to you by Automatic Ticket Dispatcher. [['.$type.' TICKET: '.$ticketnum.']] [[PRIORITY: '.$priority.']] [[TITLE: '.$title.']] [[ASSIGNEE: '.$assigned.'.]] ';
			$date = date('m-d-y H:i:s');
			$update_acceptedticket = mysql_query("INSERT INTO acceptedticket (Ticket, Title, Priority, Type, Specialist, Date) VALUES ('$ticketnum' , '$title' , '$priority' , '$type' , '$assigned' , '$date')");
			$workgroup = Settings('Workgroup');
			
			//EGN Notification
			
			$egnname = $fetch_egn['EGN'];
			
			$s = $iim1->iimSet("-var_account","$egnname");
			$s = $iim1->iimSet("-var_message","$message");
			$s = $iim1->iimPlay("C:\wamp\www\AutoDM\Macros\EGN NOTIFICATION.iim");
			
			if($s != 1)
				echo '<span class="nospecialist">'.$tnum.' Failed to Notify Specialist.</span><br/><br/>';
			// if ($type == 'Rouge')
			// {
				// $egn = $fetch_dmphone['EGN'];
				// if(!isset($egn))
				// {
					// $email = $fetch_dmphone['Email'];
				// }
				// $mobile = $fetch_dmphone['Mobile'];
			// }
			// else
			// {
				// $egn = $fetch_egn['EGN'];
				// $mobile = $fetch_egn['Mobile'];
				// if(!isset($mobile))
				// {
					// $mobile = $fetch_dmphone['Mobile'];
				// }
				// if(!isset($egn))
				// {
					// $email = $fetch_egn['Email'];
				// }
			// }
			// if(isset($egn))
			// {
				// $iim1 = new COM("imacros");
				// $s = $iim1->iimOpen("-runner", FALSE);
				// $s = $iim1->iimSet("-var_egn","$egn"); 
				// $s = $iim1->iimSet("-var_textmessage","$textmessage"); 
				// $s = $iim1->iimPlay("C:\wamp\www\AutoDM\Macros\egnnotification.iim");  
				// //send text message via broadband
				// if($TextPaging == 'Yes')
				// {
					// $mobilemessage = '" '.$priority.' '.$ticketnum.'( '.$title.') has been assigned to '.$assigned.'[AUTO-DM]"';
					// $path="C:\Program Files/Gammu 1.33.0/bin/"; 
					// chdir($path); 
					// $text = exec('gammu sendsms TEXT '.$mobile.' -text '.$mobilemessage);
				// }
			// }
			// else
			// {
				// if($TextPaging == 'Yes')
				// {
					// $mobilemessage = '" '.$priority.' '.$ticketnum.'( '.$title.') has been assigned to '.$assigned.'[AUTO-DM]"';
					// $path="C:\Program Files/Gammu 1.33.0/bin/"; 
					// chdir($path); 
					// $text = exec('gammu sendsms TEXT '.$mobile.' -text '.$mobilemessage);
				// }
			// }
		} 
		else
		{
			echo '<br/>Operation encountered an error or has been stopped. ';
		}
	}
	}
	return;
		
}
#Function to Extract Tickets on SM9
function ExtractTable($tablename)
{
	$temp = 0;
	if($tablename != 'NONE')
	{
		$iim1 = new COM("imacros");
		$s = $iim1->iimOpen("-runner", FALSE);
		$s = $iim1->iimSet("-var_tablename","$tablename"); 
		$s = $iim1->iimPlay("C:\wamp\www\AutoDM\Macros\Extract IM CSV.iim");  
		$temp = 1;
		if( $s != 1)
		{
			$iim1 = new COM("imacros");
			$s = $iim1->iimOpen("-runner", FALSE);
			$s = $iim1->iimSet("-var_tablename","$tablename"); 
			$s = $iim1->iimPlay("C:\wamp\www\AutoDM\Macros\Extract IM CSV.iim");  
		}
		if( $s != 1)
		{
			echo '<span class="nospecialist">Failed to Extract IM Ticket/s. </span><br/><br/>';
			return -1;
		}
	}
		
}
#Function to Extract FR Tickets on SM9
function ExtractFRTable($frtablename)
{
	$temp = 0;
	if($frtablename != 'NONE')
	{
		$iim1 = new COM("imacros");
		$s = $iim1->iimOpen("-runner", FALSE);
		$s = $iim1->iimSet("-var_tablename","$frtablename"); 
		$s = $iim1->iimPlay("C:\wamp\www\AutoDM\Macros\Extract FR CSV.iim");  
		$temp = 1;
		if( $s != 1)
		{
			$iim1 = new COM("imacros");
			$s = $iim1->iimOpen("-runner", FALSE);
			$s = $iim1->iimSet("-var_tablename","$frtablename"); 
			$s = $iim1->iimPlay("C:\wamp\www\AutoDM\Macros\Extract FR CSV.iim");  
		}
		if( $s != 1)
		{
			echo '<span class="nospecialist">Failed to Extract FR Ticket/s. </span><br/><br/>';
			return -1;
		}
	}
		
}
#Function for Region Selector based on data input on the database
function RegionSelector() 
{
	$temp = '';
	$current_region = '';
	$check_region = mysql_query("SELECT * FROM Region where Start < End order by Region desc");
	while($fetch_region = mysql_fetch_array($check_region))
	{
		$start = strtotime($fetch_region['Start']);
		$end = strtotime($fetch_region['End']);
		if((strtotime(date('H:i:s')) >= $start) && (strtotime(date('H:i:s')) <= $end))
		{
			$temp = '1';
			$current_region = $fetch_region['Region'];
			return $current_region;
		}	
		else
		{
			$current_region = 'NONE';
		}
	}
	if($temp != '1')
	{
		$check_region = mysql_query("SELECT * FROM Region where Start > End order by Region desc");
		while($fetch_region = mysql_fetch_array($check_region))
		{
			$start = strtotime($fetch_region['Start']) - 60*60*12;
			$end = strtotime($fetch_region['End']) + 60*60*12;
			if((date('H:i:s') < '23:59:59') && (date('H:i:s') > '12:00:00'))
			{
				if((strtotime(date('H:i:s')) >= $start) && (strtotime(date('H:i:s')) >= $end))
				{
					$current_region = $fetch_region['Region'];
					return $current_region;
				}
			
			}
			else if(date('H:i:s') >= '00:00:00')
			{
				if((date('H:i:s') <= date('H:i:s',$start)) && (date('H:i:s') <= date('H:i:s',$end)))
				{
					$current_region = $fetch_region['Region'];
					return $current_region;
				}
			
			}
			else
			{
				$current_region = 'NONE';
			}
		}
		if($current_region == '' || $current_region == 'NONE')
		{
			echo 'Auto-DM will not start if no Region was set at the current time.<br/>Please assign immediately!<br/>';
			exit();
		}
	}
}
#Check when to stop the Run
function CheckStop()
{
	$check_start = mysql_query("SELECT * FROM lastlogin");
	$fetch_start = mysql_fetch_array($check_start);
	$loginuser = $fetch_start['username'];
	if($fetch_start['start'] == '0')
	{
		echo '<b>STOPPED AT EXACTLY '.date('h:i:sA').' BY THE USER</b>';
		exit();
	}
	/* TO FOLLOW AUTO STOP IF END OF SHIFT
	$check_region = mysql_query("SELECT * FROM specialist where Name='$loginuser' ");
	$fetch_region = mysql_fetch_array($check_start);
	$region = $fetch_region['Region'];
	*/
}
#Check if atleast 1 DM is available for each REGION
function CheckDMAvailability() 
{
	$check_region = mysql_query("SELECT * FROM Region");
	$temp = 0;
	while ($fetch_region = mysql_fetch_array($check_region))
	{
		$result = '';
		$region = $fetch_region['Region'];
		$check_available = mysql_query("SELECT * FROM specialist where Status='Available' and Region='$region' and (Role='DM1' or Role='DM2') ");
		$fetch_available = mysql_fetch_array($check_available);
		if($fetch_available['Name'] == '')
		{
			$temp = 1;
			$result = 'Set atleast one available Duty Manager to '.$region.'.<br/>';
			echo $result;
		}
	}
	if($temp == 1)
		echo 'AUTO DM STOPPED AT EXACTLY '.date('h:i:sA');
	return $temp;
	
		
}
#Get the Settings for the AUTO-DM
function Settings($setting)
{
	$check_setting = mysql_query("SELECT * FROM Settings where Setting='$setting' ");
	$fetch_setting = mysql_fetch_array($check_setting);
	return $fetch_setting['Value'];
}
#Output workgroup on current ticket
function CheckWorkgroup($ticket)
{
	if (strstr($ticket, 'INCIDENT') !== FALSE)
	{
		$ipos = stripos($ticket, 'INCIDENT', 1);
	}
	else if (strstr($ticket, 'INTERACTION') !== FALSE)
	{
		$ipos = stripos($ticket, 'INTERACTION', 1);
	}
	$opos = stripos($ticket, 'Open', 1);
	$service = substr($ticket, ($ipos+8), $opos-$ipos-8);
	return $service;
}
#Trim title to a definite length
function TrimTitle($ticket, $workgroup, $titlelength)
{
	$wlen = strlen($workgroup);
	$wpos = stripos($ticket, $workgroup, 1);
	$title = substr($ticket, $wpos+$wlen , $titlelength+1);
	$title = '  '.$title;
	return $title;
}

#Function to Alert the current DM if the Tool Automatically Logged Out
function alertDM($current_region)
{

}

#Check if the user logged at auto-dm tool is the same user logged at sm9
function CheckUserLogin($current_region)
{
	$iim1 = new COM("imacros");
	$s = $iim1->iimOpen("-runner", FALSE);
	$s = $iim1->iimPlay("C:\wamp\www\AutoDM\Macros\check login.iim");
	$username = $iim1->iimGetLastExtract;	
	$check_username  = mysql_query("SELECT * FROM lastlogin");
	$fetch_username = mysql_fetch_array($check_username);
	$db_username = 'User: '.$fetch_username['username'].'[EXTRACT]';
	$db_username = strtolower($db_username);
	$username = strtolower($username);
		if($db_username == $username)
		{
			return;
		}
		else if($username == '#eanf#[extract]')
		{
			echo 'You are not Logged In at SM9.<br/>';
			echo 'You will be automatically Logged Out in 10 secs.';
			$check_region = mysql_query("SELECT * FROM Region where Region='$current_region' ");
			$fetch_region = mysql_fetch_array($check_region);
			$start = $fetch_region['Start'];
			//60*240 = 4hrs shift of DM
			if(($fetch_region['Start'] > $fetch_region['End']) && ((date('H:i:s') > '00:00:00') && (date('H:i:s') < '12:59:59')))
			{
				$timestamp = strtotime($start) -60*60*20;
			}
			else
			{
				$timestamp = strtotime($start) + 60*240;
			}
			$time = date('H:i:s', $timestamp);
			$dm = '';
			if (strtotime(date('H:i:s')) > $timestamp)
			{
				$dm = 'DM2';
			}
			else
			{
				$dm = 'DM1';
			}
			$check_dm = mysql_query("select * from specialist where Region='$current_region' and Status='Available' and Role='$dm'");
			$fetch_specialist = mysql_fetch_array($check_dm);
			if($fetch_specialist['EGN'] == '')
				{
					$check_dm = mysql_query("select * from specialist where Region='$current_region' and Status='Available' and Role='DM1'");
					$fetch_specialist = mysql_fetch_array($check_dm);
				}
			if($fetch_specialist['EGN'] == '')
				{
					$check_dm = mysql_query("select * from specialist where Region='$current_region' and Status='Available' and Role='DM2'");
					$fetch_specialist = mysql_fetch_array($check_dm);
				}
			if($fetch_specialist['EGN'] == '')
				{
					echo 'No DM available to alert!!!<br/>';
					exit;
				}
			$egn = $fetch_specialist['EGN'];
			$textmessage = 'Auto-DM has stopped because it is out of sync with SM9. Please Re-login immediately.';
			echo '<br/>'.$textmessage;
			$iim1 = new COM("imacros");
			$s = $iim1->iimOpen("-runner", FALSE);
			$s = $iim1->iimSet("-var_egn","$egn"); 
			$s = $iim1->iimSet("-var_textmessage","$textmessage"); 
			$s = $iim1->iimPlay("C:\wamp\www\AutoDM\Macros\egnnotification.iim");  
			$s = $iim1->iimExit();
			flush_buffers();
			sleep(10);
			echo '<script>top.window.location="index.php?error=2"</script>';
			exit();
		}
		else
		{
			echo 'Auto-DM Username and SM9 Username did not match. Please Logout using this Auto-DM tool.';
			exit();
		}
}
#No Title Search Function to check who will be the assignee for Critical Tickets
function NCheckXSpecialist($tnum,$region,$row,$title, $titlesearch) 
{
	updateStatus ();
	if (($row != '') || (($row == '') && ($titlesearch != 'Yes')))
	{
		$check_specialist = mysql_query("select * from specialist where Row='$row' and Region='$region' and Status='Available' and Type='L1' and CritTicket = (select min(CritTicket) from specialist where Region='$region' and Row='$row' and Type='L1' and Status='Available') order by Name");
		$fetch_specialist = mysql_fetch_array($check_specialist);
		if($fetch_specialist['Name'] == '')
		{
			$check_specialist = mysql_query("select * from specialist where Row='$row' and Region='$region' and Status='Available' and Type='L2' and CritTicket = (select min(CritTicket) from specialist where Region='$region' and Row='$row' and Type='L2' and Status='Available') order by Name");
			$fetch_specialist = mysql_fetch_array($check_specialist);
		}
		if($fetch_specialist['Name'] == '')
		{
			$check_specialist = mysql_query("select * from specialist where Row='$row' and Region='$region' and Status='Available' and Type='L3' and CritTicket = (select min(CritTicket) from specialist where Region='$region' and Row='$row' and Type='L3' and Status='Available') order by Name");
			$fetch_specialist = mysql_fetch_array($check_specialist);
		}
		if($fetch_specialist['Name'] == '')
		{
			$check_specialist = mysql_query("select * from specialist where Row='$row' and Region='$region' and Status='Available' and Type='L4' and CritTicket = (select min(CritTicket) from specialist where Region='$region' and Row='$row' and Type='L4' and Status='Available') order by Name");
			$fetch_specialist = mysql_fetch_array($check_specialist);
		}
		if($fetch_specialist['Name'] == '')
		{
			$check_specialist = mysql_query("select * from specialist where Row='$row' and Region='$region' and Status='Available' and Type='L5' and CritTicket = (select min(CritTicket) from specialist where Region='$region' and Row='$row' and Type='L5' and Status='Available') order by Name");
			$fetch_specialist = mysql_fetch_array($check_specialist);
		}
		if($fetch_specialist['Name'] == '')
		{
			echo '<span class="nospecialist">Check Availability of all specialist and make sure Row(Keywords) field is empty.<br/>Tickets will be assigned to Duty Manager.</span><br/>';
			checkDM($tnum, $region, '1 - Critical', 'Rouge',$title, $row, $titlesearch);
			return;
		}
	}
	else 
	{
			echo '<span class="nospecialist">Check Availability of all specialist and make sure Row(Keywords) field is empty.<br/>Tickets will be assigned to Duty Manager.</span><br/>';
			checkDM($tnum, $region, '1 - Critical', 'Rouge',$title, $row, $titlesearch);
			return;
	}
	AssignTicket($tnum,$fetch_specialist['Name'],'1 - Critical','',$title, $row, $titlesearch);
}
#No Title Search Function to check who will be the assignee for Non-Critical Tickets
function NCheckSpecialist($tnum,$region,$row,$priority,$title, $titlesearch) 
{
	updateStatus ();
	if ((($row != '') || (($row == '') && ($titlesearch == 'No'))) && (preg_match('/IM+[0-9]{8}/', $tnum)))
	{
		$check_specialist = mysql_query("select * from specialist where Row='$row' and Region='$region' and Status='Available' and Type='L1' and Ticket = (select min(Ticket) from specialist where Region='$region' and Row='$row' and Type='L1' and Status='Available') order by Name");
		$fetch_specialist = mysql_fetch_array($check_specialist);
		if($fetch_specialist['Name'] == '')
		{
			$check_specialist = mysql_query("select * from specialist where Row='$row' and Region='$region' and Status='Available' and Type='L2' and Ticket = (select min(Ticket) from specialist where Region='$region' and Row='$row' and Type='L2' and Status='Available') order by Name");
			$fetch_specialist = mysql_fetch_array($check_specialist);
		}
		if($fetch_specialist['Name'] == '')
		{
			$check_specialist = mysql_query("select * from specialist where Row='$row' and Region='$region' and Status='Available' and Type='L3' and Ticket = (select min(Ticket) from specialist where Region='$region' and Row='$row' and Type='L3' and Status='Available') order by Name");
			$fetch_specialist = mysql_fetch_array($check_specialist);
		}
		if($fetch_specialist['Name'] == '')
		{
			$check_specialist = mysql_query("select * from specialist where Row='$row' and Region='$region' and Status='Available' and Type='L4' and Ticket = (select min(Ticket) from specialist where Region='$region' and Row='$row' and Type='L4' and Status='Available') order by Name");
			$fetch_specialist = mysql_fetch_array($check_specialist);
		}
		if($fetch_specialist['Name'] == '')
		{
			$check_specialist = mysql_query("select * from specialist where Row='$row' and Region='$region' and Status='Available' and Type='L5' and Ticket = (select min(Ticket) from specialist where Region='$region' and Row='$row' and Type='L5' and Status='Available') order by Name");
			$fetch_specialist = mysql_fetch_array($check_specialist);
		}
		if($fetch_specialist['Name'] == '')
		{
			echo '<span class="nospecialist">Check Availability of all specialist and make sure Row(Keywords) field is empty.<br/>Tickets will be assigned to Duty Manager.</span><br/>';
			checkDM($tnum, $region, $priority, 'Rouge',$title, $row, $titlesearch);
			return;
		}
	}
	else if ((($row != '') || (($row == '') && ($titlesearch == 'No'))) && (preg_match('/FR+[0-9]{8}/', $tnum)))
	{
		$check_specialist = mysql_query("select * from specialist where Row='$row' and Region='$region' and Status='Available' and Type='L1' and FrTicket = (select min(FrTicket) from specialist where Region='$region' and Row='$row' and Type='L1' and Status='Available') order by Name");
		$fetch_specialist = mysql_fetch_array($check_specialist);
		if($fetch_specialist['Name'] == '')
		{
			$check_specialist = mysql_query("select * from specialist where Row='$row' and Region='$region' and Status='Available' and Type='L2' and FrTicket = (select min(FrTicket) from specialist where Region='$region' and Row='$row' and Type='L2' and Status='Available') order by Name");
			$fetch_specialist = mysql_fetch_array($check_specialist);
		}
		if($fetch_specialist['Name'] == '')
		{
			$check_specialist = mysql_query("select * from specialist where Row='$row' and Region='$region' and Status='Available' and Type='L3' and FrTicket = (select min(FrTicket) from specialist where Region='$region' and Row='$row' and Type='L3' and Status='Available') order by Name");
			$fetch_specialist = mysql_fetch_array($check_specialist);
		}
		if($fetch_specialist['Name'] == '')
		{
			$check_specialist = mysql_query("select * from specialist where Row='$row' and Region='$region' and Status='Available' and Type='L4' and FrTicket = (select min(FrTicket) from specialist where Region='$region' and Row='$row' and Type='L4' and Status='Available') order by Name");
			$fetch_specialist = mysql_fetch_array($check_specialist);
		}
		if($fetch_specialist['Name'] == '')
		{
			$check_specialist = mysql_query("select * from specialist where Row='$row' and Region='$region' and Status='Available' and Type='L5' and FrTicket = (select min(FrTicket) from specialist where Region='$region' and Row='$row' and Type='L5' and Status='Available') order by Name");
			$fetch_specialist = mysql_fetch_array($check_specialist);
		}
		if($fetch_specialist['Name'] == '')
		{
			echo '<span class="nospecialist">Check Availability of all specialist and make sure Row(Keywords) field is empty.<br/>Tickets will be assigned to Duty Manager.</span><br/>';
			checkDM($tnum, $region, $priority, 'Rouge',$title, $row, $titlesearch);
			return;
		}
	}
	else 
	{
		echo '<span class="nospecialist">Check Availability of all specialist and make sure Row(Keywords) field is empty.<br/>Tickets will be assigned to Duty Manager.</span><br/>';
		checkDM($tnum, $region, $priority, 'Rouge',$title, $row, $titlesearch);
		return;
	}
	AssignTicket($tnum,$fetch_specialist['Name'],$priority, '',$title, $row, $titlesearch);
}
#Update DM Role of Specialist
function updateRole ()
{
	$allAvailable = mysql_query("update specialist SET Role='---' ");
	$cdate = date('Y-m-d');
	$check_leave = mysql_query("select * from dmschedules where (sstatus='DM1' or sstatus='DM2') and edate > '$cdate' ");
	while ($row = mysql_fetch_array($check_leave))
	{	
		if ( ( (substr($row['edate'] , 11, 5) >= date('H:i') ) && ($cdate >= substr($row['sdate'] , 0 , 10)) ) || ( ($cdate >= substr($row['sdate'] , 0 , 10)) && ($cdate < substr($row['edate'] , 0 , 10)) ) )
		{
			$Lspecialist = $row['pid'];
			$LStatus = $row['sstatus'];
			$LRegion = $row['Region'];
			$setLeave = mysql_query("update specialist SET Role='$LStatus' where Name='$Lspecialist' and Region='$LRegion' ");
		}
	}
	
}

?>

</body>

</html>