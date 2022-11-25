<?php
/**
 * @file storage.php
 * @date 2018-05-27
 * @updated 2020-06-16
 * @author Go Namhyeon <abuse@catswords.net>
 * @brief Stroage module for ReasonableFramework
 */

if(!is_fn("get_current_working_dir")) {
    function get_current_working_dir($method="getcwd") {
        $working_dir = "";

        switch($method) {
            case "getcwd":
                $working_dir = getcwd();
                break;
            case "dirname":
                $working_dir = dirname(__FILE__);
                break;
            case "basename":
                $working_dir = basename(__DIR__);
                break;
            case "unix":
                if(loadHelper("exectool")) {
                    $working_dir = exec_command("pwd");
                }
                break;
            case "windows":
                if(loadHelper("exectool")) {
                    $exec_contents = implode("\r\n", array("@echo off", "ECHO %cd%"));
                    $exec_file = write_storage_file($exec_contents, array(
                        "filename" => "pwd.bat"
                    ));
                    $working_dir = exec_command($exec_file);
                }
                break;
        }

        return $working_dir;
    }
}

if(!is_fn("get_storage_dir")) {
    function get_storage_dir() {
        return "storage";
    }
}

if(!is_fn("get_safe_path")) {
    function get_safe_path($path) {
        return str_replace("../", "", $path);
    }
}

if(!is_fn("get_storage_path")) {
    function get_storage_path($type="data") {
        $dir_path = sprintf("%s/%s/%s", get_current_working_dir(), get_storage_dir(), get_safe_path($type));

        if(!is_dir($dir_path)) {
            if(!@mkdir($dir_path, 0777)) {
                set_error("Could not create directory. " . $dir_path);
                show_errors();
            }
        }
        return $dir_path;
    }
}

if(!is_fn("get_storage_url")) {
    function get_storage_url($type="data") {
        return sprintf("%s/%s/%s", base_url(), get_storage_dir(), get_safe_path($type));
    }
}

if(!is_fn("allocate_uploaded_files")) {
    function allocate_uploaded_files($options=array()) {
        $response = array(
            "files" => array()
        );
        
        $config = get_config();
        $requests = get_requests();
        $files = $requests['_FILES'];

        $storage_type = get_value_in_array("storage_type", $options, "data");
        $upload_base_path = get_storage_path($storage_type);
        $upload_base_url = get_storage_url($storage_type);
        $upload_allow_ext = array();

        // storage/config/security.ini -> allowextensionsdisabled, allowextensions
        $allow_extensions_disabled = get_value_in_array("allowextensionsdisabled", $config, 0);
        if(empty($allow_extensions_disabled)) {
            $allow_extensions = get_value_in_array("allowextensions", $config, $upload_allow_ext);
        }

        foreach($files as $k=>$file) {
            $upload_ext = get_file_extension($files[$k]['name']);
            $upload_name = make_random_id(32) . (empty($upload_ext) ? "" : "." . $upload_ext);
            $upload_file = $upload_base_path . "/" . $upload_name;
            $upload_url = $upload_base_url . "/" . $upload_name;

            if(count($upload_allow_ext) == 0 || in_array($upload_ext, $upload_allow_ext)) {
                if(move_uploaded_file($files[$k]['tmp_name'], $upload_file)) {
                    // get file source name
                    $upload_source_name = $files[$k]['name'];
                    if(strlen($upload_source_name) == 0) {
                        $upload_source_name = $upload_name;
                    }

                    // make file data
                    $response['files'][$k] = array(
                        "storage_type" => $storage_type,
                        "upload_ext" => $upload_ext,
                        "upload_name" => $upload_name,
                        "upload_file" => $upload_file,
                        "upload_url" => $upload_url,
                        "upload_source_name" => $upload_source_name,
                        "upload_size" => filesize($upload_file),
                        "upload_error" => ""
                    );
                } else {
                    $response['files'][$k] = array(
                        "upload_error" => "File write error."
                    );
                }
            } else {
                $response['files'][$k] = array(
                    "upload_error" => "Not allowed file type."
                );
            }
        }

        return $response['files'];
    }
}

if(!is_fn("read_storage_file")) {
    function read_storage_file($filename, $options=array()) {
        $result = false;

        $storage_type = get_value_in_array("storage_type", $options, "data");
        $max_age = intval(get_value_in_array("max_age", $options, 0)); // max_age (seconds), the value 0 is forever
        $upload_base_path = get_storage_path($storage_type);
        $upload_base_url = get_storage_url($storage_type);
        $upload_filename = sprintf("%s/%s", $upload_base_path, get_safe_path($filename));

        if(file_exists($upload_filename)) {
            $is_valid = false;
            $upload_filesize = filesize($upload_filename);
            $upload_mtime = filemtime($upload_filename);
            $upload_age = get_current_timestamp() - $upload_mtime;

            if($upload_filesize > 0) {
                $is_valid = ($max_age > 0) ? ($upload_age <= $max_age) : true;
            }

            if($is_valid) {
                if($fp = fopen($upload_filename, "r")) {
                    if(array_key_equals("safemode", $options, true)) {
                        $result = "";
                        while(!feof($fp)) {
                            $blocksize = get_value_in_array("blocksize", $options, 8192);
                            $result .= fread($fp, $blocksize);
                        }
                    } else {
                        $result = fread($fp, $upload_filesize);
                    }
                    fclose($fp);
                }

                if(!array_key_empty("encode_base64", $options)) {
                    $result = base64_encode($result);
                }

                if(!array_key_empty("format", $options)) {
                    if(loadHelper("webpagetool")) {
                        if($options['format'] == "json") {
                            $result = get_parsed_json($result, array("stdClass" => true));
                        } elseif($options['format'] == "xml") {
                            $result = get_parsed_xml($result);
                        } elseif($options['format'] == "dom") {
                            $result = get_parsed_dom($result);
                        }
                    }
                }
            }
        }

        return $result;
    }
}

if(!is_fn("iterate_storage_files")) {
    function iterate_storage_files($storage_type, $options=array()) {
        $filenames = array();

        $excludes = array(".", "..");
        $storage_path = get_storage_path($type);

        if(is_dir($storage_path)) {
            if($handle = opendir($storage_path)) {
                while(false !== ($file = readdir($handle))) {
                    if(!in_array($file, $excludes)) {
                        $filenames[] = $file;
                    }
                }
            }
        }
        return $filenames;
    }
}

if(!is_fn("remove_storage_file")) {
    function remove_storage_file($filename, $options=array()) {
        $result = false;

        $storage_type = get_value_in_array("storage_type", $options, "data");
        $max_age = intval(get_value_in_array("max_age", $options, 0)); // max_age (seconds), the value 0 is forever
        $upload_base_path = get_storage_path($storage_type);
        $upload_base_url = get_storage_url($storage_type);
        $upload_filename = sprintf("%s/%s", $upload_base_path, get_safe_path($filename));
        
        // add option: encryption
        $encryption = get_value_in_array("encryption", $options, "");
        if(!empty($encryption)) {
            if(!loadHelper("encryptiontool")) {
                $encryption = "";
            }
        }

        if(file_exists($upload_filename)) {
            $is_valid = false;
            $upload_mtime = filemtime($upload_filename);
            $upload_age = get_current_timestamp() - $upload_mtime;

            if(file_exists($upload_filename)) {
                if($max_age > 0) {
                    $is_valid = ($upload_age > $max_age);
                } else {
                    $is_valid = true;
                }
            }

            if($is_valid) {
                if(!array_key_empty("chmod", $options)) {
                    @chmod($upload_filename, $options['chmod']);
                }

                if(!array_key_empty("chown", $options)) {
                    @chown($upload_filename, $options['chown']);
                }

                if(!array_key_equals("shell", $options, true)) {
                    if(loadHelper("exectool")) {
                        $exec_cmd = ($options['shell'] == "windows") ? "del '%s'" : "rm -f '%s'";
                        exec_command(sprintf($exec_cmd, make_safe_argument($upload_filename)));
                    }
                } else {
                    @unlink($upload_filename);
                }

                $result = !file_exists($upload_filename);
            }
        }

        return $result;
    }
}

if(!is_fn("remove_storage_files")) {
    function remove_storage_files($storage_type, $options=array()) {
        $failed = 0;

        $max_age = intval(get_value_in_array("max_age", $options, 0));
        $excludes = get_array(get_value_in_array("excludes", $options, array()));

        $filenames = iterate_storage_files($storage_type);
        foreach($filenames as $filename) {
            if(!in_array($filename, $excludes)) {
                $rm = remove_storage_file($filename, array(
                    "storage_type" => $storage_type,
                    "max_age" => $max_age
                ));
                if(!$rm) {
                    $failed++;
                }
            }
        }

        return $failed;
    }
}

if(!is_fn("remove_volatile_files")) {
    function remove_volatile_files($storage_type, $max_age=0, $options=array()) {
        return remove_storage_files($storage_type, array(
            "max_age" => $max_age,
            "excludes" => array("index.php", "index.html")
        ), $options);
    }
}

if(!is_fn("write_storage_file")) {
    function write_storage_file($data, $options=array()) {
        $result = false;

        $filename = get_value_in_array("filename", $options, make_random_id(32));
        if(!array_key_empty("extension", $options)) {
            $filename = sprintf("%s.%s", $filename, $options['extension']);
        }

        $storage_type = get_value_in_array("storage_type", $options, "data");
        $mode = get_value_in_array("mode", $options, "w");
        $upload_base_path = get_storage_path($storage_type);
        $upload_base_url = get_storage_url($storage_type);
        $upload_filename = sprintf("%s/%s", $upload_base_path, get_safe_path($filename));

        $encryption = get_value_in_array("encryption", $options, "");
        if(!empty($encryption)) {
            if(!loadHelper("encryptiontool")) {
                $encryption = "";
            }
        }

        if(file_exists($upload_filename) && in_array($mode, array("fake"))) {
            if(!array_key_empty("filename", $options)) {
                $result = $upload_filename;
            } else {
                $result = write_storage_file($data, $options);
            }
        } else {
            if($mode == "fake") {
                $result = $upload_filename;
            } elseif($fhandle = fopen($upload_filename, $mode)) {
                // if it is append, check the `rotate_size` option
                if($mode == "a") {
                    $rotate_size = intval(get_value_in_array("rotate_size", $options, 0));
                    $rotate_ratio = floatval(get_value_in_array("rotate_ratio", $options, 0.9));
                    $size_limit = floor($rotate_size * $rotate_ratio);
                    if($rotate_size > 0) {
                        if($rotate_size > filesize($upload_filename)) {
                            if(loadHelper("exectool")) {
                                exec_command(sprintf("tail -c %s '%s' > '%s'", $size_limit, $upload_filename, $upload_filename));
                            } else {
                                write_common_log("failed load exectool helper", "system/storage");
                            }
                        }
                    }
                }

                // write a file
                if(fwrite($fhandle, $data)) {
                    $result = $upload_filename;
                    if(!array_key_empty("chmod", $options)) {
                        @chmod($result, $options['chmod']);
                    }
                    if(!array_key_empty("chown", $options)) {
                        @chown($result, $options['chown']);
                    }
                }
                fclose($fhandle);
            } else {
                write_common_log("maybe, your storage is write-protected. " . $upload_filename, "system/storage");
                $result = false;
            }
        }

        if(array_key_equals("basename", $options, true)) {
            $result = basename($result);
        }

        if(array_key_equals("url", $options, true)) {
            $result = sprintf("%s/%s", $upload_base_url, get_safe_path($filename));
        }

        return $result;
    }
}

if(!is_fn("append_storage_file")) {
    function append_storage_file($data, $options=array()) {
        $options['mode'] = "a";

        if(!array_key_empty("nl", $options)) {
            switch($options['nl']) {
                case "<": $data = DOC_EOL . $data; break;
                case ">": $data = $data . DOC_EOL; break;
                case "<>": $data = DOC_EOL . $data . DOC_EOL; break;
            }
        }

        return write_storage_file($data, $options);
    }
}

if(!is_fn("get_real_path")) {
    function get_real_path($filename) {
        $filename = get_safe_path($filename);
        return file_exists($filename) ? realpath($filename) : false;
    }
}

if(!is_fn("retrieve_storage_dir")) {
    function retrieve_storage_dir($type, $recursive=false, $excludes=array(".", ".."), $files=array()) {
        $storage_path = get_storage_path($type);

        if(is_dir($storage_path)) {
            if($handle = opendir($storage_path)) {
                while(false !== ($file = readdir($handle))) {
                    if(!in_array($file, $excludes)) {
                        $file_path = sprintf("%s/%s", $storage_path, $file);
                        if(is_file($file_path)) {
                            $files[] = $file_path;
                        } elseif($recursive) {
                            $files = retrieve_storage_dir($type . "/" . $file, $recursive, $excludes, $files);
                        }
                    }
                }
                closedir($handle);
            }
        }

        return $files;
    }
}

if(!is_fn("get_file_extension")) {
    function get_file_extension($file, $options=array()) {
        $result = false;

        // option 'multiple': extension a.b.c.d.f...z
        if(array_key_equals("multiple", $options, true)) {
            $name = basename($file);
            $pos = strpos($name, '.');
            $result = substr($name, $pos + 1);
        } else {
            $result = pathinfo($file, PATHINFO_EXTENSION);
        }

        return $result;
    }
}

if(!is_fn("check_file_extension")) {
    function check_file_extension($file, $extension, $options=array()) {
        return (get_file_extension($file, $options) === $extension);
    }
}

if(!is_fn("get_file_name")) {
    function get_file_name($name, $extension="", $basepath="") {
        $result = "";

        $result .= empty($basepath) ? "" : ($name . "/");
        $result .= $name;
        $result .= empty($extension) ? "" : ("." . $extension);

        return $result;
    }
}
