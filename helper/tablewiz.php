<?php
/**
 * @file tablewiz.php
 * @date 2018-02-26
 * @author Go Namhyeon <gnh1201@gmail.com>
 * @brief TableWiz helper
 */

if(!function_exists("tablewiz_create")) {
	function tablewiz_create($rows, $bind=array(), $domid="", $domclass="", $thead_html=array(), $tbody_html_list=array()) {
		$html = "";

		if(count($rows) == 0) {
			return $html;
		}

		$dom_element_name = make_random_id(10);
		$domid = empty($domid) ? "tablewiz_id_" . $dom_element_name : $domid;
		$domclass = empty($domclass) ? "tablewiz_class_" . $dom_element_name : $domclass;

		$html_th_elms = "";
		foreach($rows[0] as $k=>$v) {
			$html_th_text = array_key_empty($k, $bind) ? $k : $bind[$k];
			$html_th_elms .= "<th>" . $html_th_text . "</th>";
		}

		// append contents in thead
		foreach($thead_html as $k=>$v) {
			$html_th_elms .= "<th>" . $v . "</th>";
		}

		$html_tr_elms = "";
		foreach($rows as $idx=>$record) {
			$html_tr_elms .= "<tr>";
			foreach($record as $k=>$v) {
				$html_tr_elms .= "<td>" . $v . "</td>";
			}
			$html_tr_elms .= "</tr>";

			// append contents in tbody
			if(count($tbody_html_list) > $idx) {
				$tbody_html = $tbody_html_list[$idx];
				if(is_array($tbody_html)) {
					foreach($tbody_html as $k=>$v) {
						$html_tr_elms .= "<td>" . $v . "</td>";
					}
				}
			}
		}

		$html .= <<<EOF
<table id="$domid" class="$domclass" border="1" cellspacing="0">
	<thead>
		<tr>
			$html_th_elms
		</tr>
	</thead>
	<tbody>
		$html_tr_elms
	</tbody>
</table>
EOF;

		return $html;
	}
}
