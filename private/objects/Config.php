<?php
// default values for config
$default_config = array(
    'defaults' => array(
        'domain' => 'https://tales.anonymousgca.eu',
        'profile_picture' => 'https://tales.anonymousgca.eu/common/assets/default/profile_picture.webp',
        'cover_picture' => 'https://tales.anonymousgca.eu/common/assets/default/profile_cover.webp',
    ),
    'site' => array(
        'title' => 'Tales'
    )
);

// path to config file
$config_file = (dirname(__FILE__) . '/../configs/config.ini');

// check if file exists
if (!file_exists($config_file)) {
    // write default config to file
    $handle = fopen($config_file, 'w');
    foreach ($default_config as $section => $values) {
        fwrite($handle, "[$section]\n");
        foreach ($values as $key => $value) {
            fwrite($handle, "$key = $value\n");
        }
    }
    fclose($handle);
} else {
    // read config from file
    $config = parse_ini_file($config_file, true);
    // loop through default config sections
    foreach ($default_config as $section => $values) {
        // check if section exists in config file
        if (!isset($config[$section])) {
            // append section to config file
            $handle = fopen($config_file, 'a');
            fwrite($handle, "\n[$section]\n");
            foreach ($values as $key => $value) {
                fwrite($handle, "$key = $value\n");
            }
            fclose($handle);
        } else {
            // loop through default config keys
            foreach ($values as $key => $value) {
                // check if key exists in config file section
                if (!isset($config[$section][$key])) {
                    // append key to config file section
                    $handle = fopen($config_file, 'a');
                    fwrite($handle, "$key = $value\n");
                    fclose($handle);
                }
            }
        }
    }
}
?>

