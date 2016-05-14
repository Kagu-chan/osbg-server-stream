<?php
	$requestString = "[" . date("Y.m.d-G:i.s") . "][" . $_SERVER['REMOTE_ADDR'] . "] ?" . $_SERVER['QUERY_STRING'] . " [" . $_SERVER['HTTP_USER_AGENT'] . "]";
	file_put_contents('log.txt', file_get_contents('log.txt') . "\n" . $requestString);

	function ciao($arr, $message="Unknown Message!") {
		if (empty($arr["message"])) $arr["message"] = $message;

		$result = json_encode($arr);
		$result = str_replace("\\", "", $result);

		echo $result;
		exit();
	}

	function arrayToList($files) {
		$res = array();
		$i = 0;
		array_walk($files, function($v, $k) use (&$res, &$i) {
			if (is_array($v)) {
				array_walk($v, function($iv) use(&$res, $k, &$i) {
					$res["f$i"] = $k . DIRECTORY_SEPARATOR . $iv;
					$i++;
				});
			} else
				$res["f$i"] = $v;

			$i += 1;
		});

		return $res;
	}

	function dirToArray($dir) {
		$res = array();

		$cdir = array_diff(scandir($dir), array('..', '.'));
		foreach ($cdir as $key => $value) {
			if (is_dir($dir . DIRECTORY_SEPARATOR . $value)) {
				$res[$value] = dirToArray($dir . DIRECTORY_SEPARATOR . $value);
			} else {
				$res[] = $value; 
			}
		}
		return $res;
	}

	$result = array("success" => FALSE);

	array_walk($_GET, function($v, $k) use ($result) {
		if (strpos($v, "..") !== FALSE) ciao($result, "Unknown Command!");
	});

	$project = filter_input(INPUT_GET, "p");

	if (!file_exists($project)) ciao($result, "Unknown Project!");
	$result["versions"] = array_diff(scandir($project), array('..', '.', 'v.json', 'v0'));
	$data = json_decode(file_get_contents("$project/v.json"), TRUE);

	$result["version"] = $data["version"];

	if (filter_input(INPUT_GET, "current") == "1") {
		$d = $result["version"];
		$query = "http://sources.kagu-chan.de?p=$project&d=$d&f=f0";
		header("Location: $query");
		exit();
	}

	$request = filter_input(INPUT_GET, "d");
	if (empty($request)) {
		$result["success"] = TRUE;
		ciao($result, "Latest Version");
	}

	$dir = implode(DIRECTORY_SEPARATOR, array(".", $project, "v$request"));

	if (!file_exists($dir)) ciao($result, "Unknown Version $request");

	$files = arrayToList(dirToArray($dir)); // array_diff(scandir($dir), array('..', '.'));

	$dl = filter_input(INPUT_GET, "f");
	if (empty($dl)) {
		$result["files"] = $files;
		$result["success"] = TRUE;
		ciao($result, "Filelist");
	}

	$file = implode(DIRECTORY_SEPARATOR, array($dir, $files[$dl]));

	if (file_exists($file)) {
		header("Content-Disposition: attachment; filename=\"" . $files[$dl] . "\"");
		header("Content-Type: application/octet-stream");
		header("Pragma: public");
		header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
		header("Content-Length: " . filesize($file));

		set_time_limit(0);
		$f = @fopen($file, "rb");
		while(!feof($f))
		{
			print(@fread($f, 1024*8));
			ob_flush();
			flush();
		}
	}

	ciao($result, "Unknown Command!");
