<?php
/**
 * @file hiddentool.php
 * @created_on 2021-05-24
 * @updated_on 2021-05-24
 * @author Go Namhyeon <gnh1201@gmail.com>
 * @brief Tools for Hidden Services (e.g. Tor, I2P, etc...)
 */

if (!is_fn("detect_hidden_service")) {
    function detect_hidden_service() {
	    $score = 0;

        $suffixes = array(".onion", ".i2p");
        $forwarded_host = get_header_value("X-Forwarded-Host");
        if (!empty($forwarded_host)) {
            if (in_array(end(explode('.', $forwarded_host)), $suffixes)) {
                $score += 1;
            }
        }
        
        return $score;
    };
}
