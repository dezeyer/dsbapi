<?php
header("Content-Type: text/html; charset=utf-8");
if(!isset($_GET["json"])){
error_reporting(E_ALL|E_STRICT); 
ini_set('display_errors', 1);  
}
//--Stundenplan laden:
$dom = new DomDocument("1.0", "utf-8");
//load the html
$dom->loadHTMLFile(json_decode(file_get_contents('https://iphone.dsbcontrol.de/iPhoneService.svc/DSB/timetables/'.str_replace("\"", "", file_get_contents('https://iphone.dsbcontrol.de/iPhoneService.svc/DSB/authid/121061/bbzwnd'))),true)[0]["timetableurl"]);
//$dom->loadHTMLFile("http://dev.simonzeyer.de/vertretungsplan/test.htm");
//discard white space 
$dom->preserveWhiteSpace = true; 

//the table by its tag name
$tables = $dom->getElementsByTagName('table'); 
			
//get all rows from the table
foreach ($tables as $table) { 
	$rows = $table->getElementsByTagName('tr'); 
	  // loop over the table rows
	  
	$i = 0;
	foreach ($rows as $row) { 
		// get each column by tag name
		$cols = $row->getElementsByTagName('td');
		
		//Datum aus der Zweiten Zeile 
		if($i == 1){
			$date = preg_replace('/[^a-z\d .:]/i', "", $cols->item(1)->nodeValue);
		}
		$i++;
		//Api GET Date
		if(!isset($_GET["d"]) || isset($_GET["d"]) && isset($date) && $_GET["d"] == $date){
			//Erste Zeile ausblenden
			if(preg_replace('/[^a-z\d .:]/i', "", $cols->item(0)->nodeValue) != "Klasse"){
				if(isset($std)){$oldstd = $std;}
				if(isset($fach)){$oldfach = $fach;}
				if(isset($raum)){$oldraum = $raum;}
				if(isset($vfach)){$oldvfach = $vfach;}
				if(isset($vraum)){$oldvraum = $vraum;}
				if(isset($merkmal)){$oldmerkmal = $merkmal;}
				if(isset($info)){$oldinfo = $info;}
				$std = preg_replace('/[^a-z\d .:]/i', "", $cols->item(2)->nodeValue); //Stunde
				$fach = preg_replace('/[^a-z\d .:]/i', "", $cols->item(3)->nodeValue); //Fach
				$raum = preg_replace('/[^a-z\d .:]/i', "", $cols->item(4)->nodeValue); //Raum
				$vfach = preg_replace('/[^a-z\d .:]/i', "", $cols->item(5)->nodeValue); //VFach
				$vraum = preg_replace('/[^a-z\d .:]/i', "", $cols->item(6)->nodeValue); //VRaum
				$merkmal = preg_replace('/[^a-z\d .:]/i', "", $cols->item(7)->nodeValue); //Merkmal
				$info = preg_replace('/[^a-z\d .:]/i', "", $cols->item(8)->nodeValue); //Info
				
				//Bei doppelstunden wird sich der eintrag in die 2. Stunde oft erspart, es gelten die infos der 1. stunde. 
				//Hier werden diese Informationen an die 2. Stunde übergeben.
				//Wenn: Gleiches Fach, Gleicher Raum, Vfach leer, VRaum leer, Merkmal leer, Info leer
				if(isset($oldfach) && isset($oldraum) && $fach == $oldfach && $oldraum == $raum){
					if(iscollset($vfach)){
						if(iscollset($vraum)){
							if(iscollset($merkmal)){
								if(iscollset($info)){
									$fach = $oldfach;
									$raum = $oldraum;
									$vfach = $oldvfach;
									$vraum = $oldvraum;
									$merkmal = $oldmerkmal;
									$info = $oldinfo;							
								}
							}
						}
					}
				}
				//Wenn eine klasse geteilt ist und beide Kurse vertretung haben, wird die Stunde nur einmal angegeben. Hier wird sie Übernommen.
				if($std == "" || $std == " "){
					$std = $oldstd;
				}
				
				//Es gibt verschiedene Vertretungstypen. 
				/*	1 - Reiner Ausfall (Merkmal = Frei)
				/*	2 - Anstatt, zeigt auf stunde mit fllt aus (Merkmal = anstatt) 
				/*	3 - Verschoben auf (Merkmal = fllt aus) 
				/*	4 - Reiner Ausfall (Merkmal = Stillarbeit)
				/*	5 - Reiner Ausfall (Merkmal = Zusatzunterricht)
				*/
				
				//Die Stunden schreiben.
				$vertretung =	array(
						"Std" => $std, //Stunde,
						"Fach" => $fach, //Fach,
						"Raum" => $raum , //Raum,
						"VFach" => $vfach, //VFach,
						"VRaum" => $vraum, //VRaum,
						"Merkmal" => $merkmal, //Merkmal,
						"Info" =>  $info, //Info,
					);
				if(preg_replace("/[^a-z\d .:]/i", "", $cols->item(0)->nodeValue) != ""){
					$klasse = preg_replace("/[^a-z\d .:]/i", "", $cols->item(0)->nodeValue);
				}
				if(isset($_GET["k"]) && $_GET["k"] == $klasse){
					$klassearray[$klasse][] = $vertretung;
				}elseif(!isset($_GET["k"])){
					$klassearray[$klasse][] = $vertretung;
				}
			}
		}
	} 
	
	
	
	//Array Zusammenstellen
	if(!empty($klassearray)){
		$array[] = array(
			"Datum" => $date, //Datum,
			"Klassen" => $klassearray,
		);
	}
	//Variablen leeren, um fehler zu vermeiden
	unset($oldstd);
	unset($std);
	unset($fach);
	unset($oldfach);
	unset($raum);
	unset($oldraum);
	unset($vfach);
	unset($oldvfach);
	unset($vraum);
	unset($merkmal);
	unset($oldmerkmal);
	unset($info);
	unset($date);
	unset($vertretung);
	unset($klassearray);
}

if(isset($_GET["json"])){

echo json_encode($array);

}else{
echo "DATA JSON: <br><a href=\"https://iphone.dsbcontrol.de/iPhoneService.svc/DSB/timetables/".str_replace("\"", "", file_get_contents('https://iphone.dsbcontrol.de/iPhoneService.svc/DSB/authid/121061/bbzwnd'))."\">https://iphone.dsbcontrol.de/iPhoneService.svc/DSB/timetables/".str_replace("\"", "", file_get_contents('https://iphone.dsbcontrol.de/iPhoneService.svc/DSB/authid/121061/bbzwnd'))."</a>";
echo "<br><br>URL des aktuelle Vertretungsplan:<br><a href=\"".json_decode(file_get_contents('https://iphone.dsbcontrol.de/iPhoneService.svc/DSB/timetables/'.str_replace("\"", "", file_get_contents('https://iphone.dsbcontrol.de/iPhoneService.svc/DSB/authid/121061/bbzwnd'))),true)[0]["timetableurl"]."\">".json_decode(file_get_contents('https://iphone.dsbcontrol.de/iPhoneService.svc/DSB/timetables/'.str_replace("\"", "", file_get_contents('https://iphone.dsbcontrol.de/iPhoneService.svc/DSB/authid/121061/bbzwnd'))),true)[0]["timetableurl"]."</a>";
echo "<br><br>Zuletzt wurde der Vertretungsplan aktualisiert:<br>". str_replace(".", "-",json_decode(file_get_contents('https://iphone.dsbcontrol.de/iPhoneService.svc/DSB/timetables/'.str_replace("\"", "", file_get_contents('https://iphone.dsbcontrol.de/iPhoneService.svc/DSB/authid/121061/bbzwnd'))),true)[0]["timetabledate"].":00");
echo "<br><br><a href=\"?k=FOSW12.5\">Plan der Klasse FOSW12.5</a> | <a href=\"?d=".date("d.m.Y")."\">Plan von heute (".date("d.m.Y").")</a> | <a href=\"?k=FOSW12.5&d=".date("d.m.Y")."\">Plan der Klasse FOS12.5 von heute (".date("d.m.Y").")</a> | <a href=\"".$_SERVER["PHP_SELF"]."\">Reset</a>";
echo"<br><br><pre>";
print_r($array);
}


function iscollset($coll){
	if($coll == "" || $coll == " "){
		return true;
	}else{
		return false;
	}

}
?
