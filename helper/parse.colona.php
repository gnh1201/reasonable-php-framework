<?php

function parse_colona_format($data) {
    $lines = split_by_line($data);

    $jobargs = array();
    $eof = false;
    $delimiter = ":";

    $jobkey = "";
    $jobvalue = "";
    foreach($lines as $line) {
        $pos = strpos($line, $delimiter);

        if($pos !== false) {
            $jobkey = rtrim(substr($line, 0, $pos));
            $jobvalue = ltrim(substr($line, $pos + strlen($delimiter)));
            if($jobvalue == "<<<EOF") {
                $jobvalue = "";
                $eof = true;
            } else {
                $jobargs[$jobkey] = $jobvalue;
            }
        } elseif($eof) {
            if($line == "EOF;") {
                $jobargs[$jobkey] = $jobvalue;
                $eof = false; 
            } else {
                $jobvalue .= $line;
            }
        }
    }

    return $jobargs;
}
