<?php
/**
 * @file jCryption.lnk.php
 * @date 2018-09-30
 * @author Go Namhyeon <abuse@catswords.net>
 * @brief jCryption (alternative HTTPS on javascript) Helper
 */

if(!is_fn("jCryption_load")) {
    function jCryption_load() {
        $required_files = array(
            "jCryption/sqAES",
            "jCryption/JCryption"
        );
        foreach($required_files as $file) {
            $inc_file = get_current_working_dir() . "/vendor/_dist/" . $file . ".php";
            if(file_exists($inc_file)) {
                include($inc_file);
            }
        }
    }
}

if(!is_fn("jCryption_get")) {
    function jCryption_get($idx=0, $selector="") {
        $s = array();
        $s[] = "JCryption::decrypt();";
        $s[] = sprintf("$(function(){$(\"%s\").jCryption();});", $selector);
        $s[] = sprintf("%s/vendor/_dist/jCryption/js/jquery.jcryption.3.1.0.js", base_url());
        return $s[$idx];
    }
}
