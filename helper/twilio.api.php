<?php
/**
 * @file twilio.api.php
 * @date 2019-04-08
 * @author Go Namhyeon <abuse@catswords.net>
 * @brief Twilio REST API interface module
 * @documentation https://www.twilio.com/docs/sms/send-messages
 */

if(!is_fn("twilio_get_config")) {
    function twilio_get_config() {
        $config = get_config();

        return array(
            "sid" => get_value_in_array("twilio_sid", $config, ""),
            "token" => get_value_in_array("twilio_token", $config, ""),
            "from" => get_value_in_array("twilio_from", $config, ""),
            "char_limit" => get_value_in_array("twilio_char_limit", $config, 160)
        );
    }
}

if(!is_fn("twilio_get_message_blocks")) {
    function twilio_parse_messages($message) {
        $strings = array();

        $cnf = twilio_get_config();

        if(loadHelper("string.utils")) {
            $strings = get_splitted_strings($message, $cnf['char_limit']);
        } else {
            $strings[] = substr($messages, 0, $cnf['char_limit']);
        }

        return $strings;
    }
}

if(!is_fn("twilio_send_message")) {
    function twilio_send_message($message, $to) {
        $response = false;

        $cnf = twilio_get_config();
        $messages = twilio_parse_messages($message);

        if(loadHelper("webpagetool")) {
            $bind = array(
                "sid" => $cnf['sid']
            );
            $request_url = get_web_binded_url("https://api.twilio.com/2010-04-01/Accounts/:sid/Messages.json", $bind);
            foreach($messages as $_message) {
                $response = get_web_json($request_url, "post.cmd", array(
                    "headers" => array(
                        "Content-Type" => "application/x-www-form-urlencoded",
                        "Authentication" => array("Basic", $cnf['sid'], $cnf['token']),
                    ),
                    "data" => array(
                        "Body" => $_message,
                        "From" => $cnf['from'],
                        "To" => $to,
                    )
                ));
            }
        }

        return $response;
    }
}

if(!is_fn("twilio_send_voice")) {
    function twilio_send_voice($message="", $to) {
        $response = false;

        $cnf = twilio_get_config();
        $url = "http://catswords.re.kr/ep/storage/data/voice.xml";

        if(loadHelper("webpagetool")) {
            $bind = array(
                "sid" => $cnf['sid']
            );
            $request_url = sprintf("https://api.twilio.com/2010-04-01/Accounts/:sid/Calls.json", $bind);
            $response = get_web_page($request_url, "post.cmd", array(
                "headers" => array(
                    "Content-Type" => "application/x-www-form-urlencoded",
                    "Authentication" => array("Basic", $cnf['sid'], $cnf['token']),
                ),
                "data" => array(
                    "Url" => $url,
                    "From" => $cnf['from'],
                    "To" => $to,
                ),
            ));
        }

        return $response;
    }
}
