<?php

function send_web_hook($message, $networkid, $options=array()) {
	$response = false;
	$id = get_value_in_array("id", $options, "");

	switch($networkid) {
		case "nateon":
			$request_url = sprintf("https://teamroom.nate.com/api/webhook/%s", $id);
			if(loadHelper("webpagetool")) {
				$response = get_web_page($request_url, "post", array(
					"headers" => array(
						"Content-Type" => "application/x-www-form-urlencoded",
					),
					"data" => array(
						"content" => urlencode($message),
					),
				));
			}
						
						
			break;

		case "discord":
			$request_url = sprintf("https://discordapp.com/api/webhooks/%s", $id);
			$response = get_web_json($request_url, "jsondata", array(
				"headers" => array(
					"Content-Type" => "application/json",
				),
				"data" => array(
					"content" => $message,
					"username" => get_value_in_array("username", $options, "anonymous"),
				),
			));
			break;
			
		case "slack":
			$request_url = sprintf("https://hooks.slack.com/services/%s", $id);
			$response = get_web_json($request_url, "jsondata", array(
				"headers" => array(
					"Content-Type" => "application/json",
				),
				"data" => array(
					"channel" => sprintf("#%s", get_value_in_array("channel", $options, "general")),
					"username" => get_value_in_array("username", $options, "anonymous"),
					"text" => $message,
					"icon_emoji" => sprintf(":%s:", get_value_in_array("emoji", $options, "ghost")),
				),
			));
			break;
	}

	return $response;
}
