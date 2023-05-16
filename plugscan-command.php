<?php

if (!class_exists('WP_CLI')) {
    return;
}

class PlugScan_Command extends WP_CLI_Command
{
    /**
     * Checks for fake plugins in the WordPress plugins folder.
     *
     * ## EXAMPLES
     *
     *     wp plugscan
     *
     * @when before_wp_load
     */
    public function __invoke()
    {
        $this->check_fake_plugins();
    }

private function check_fake_plugins()
{
    $wp_content_dir = defined('WP_CONTENT_DIR') ? WP_CONTENT_DIR : ABSPATH . 'wp-content';
    $plugins_path = $wp_content_dir . '/plugins';
    $plugin_folders = glob($plugins_path . '/*', GLOB_ONLYDIR);

    $fake_plugins = [];

    foreach ($plugin_folders as $plugin_folder) {
        $plugin_folder = basename($plugin_folder);

        $is_fake_plugin = !$this->isPluginInTxtFile($plugin_folder);

        if ($is_fake_plugin) {
            $fake_plugins[] = $plugin_folder;
        }
    }

    if (!empty($fake_plugins)) {
        WP_CLI::warning("Fake plugins found:");
        foreach ($fake_plugins as $fake_plugin) {
            WP_CLI::line("- " . $fake_plugin);
        }
    } else {
        WP_CLI::success("No fake plugins found.");
    }
}

private function isPluginInTxtFile($plugin_folder)
{
    $home = getenv('HOME');
    $plugins_files = ["$home/.wp-cli/packages/vendor/eugenewozniak/plugscan/official_plugins1.txt", "$home/.wp-cli/packages/vendor/eugenewozniak/plugscan/official_plugins2.txt", "$home/.wp-cli/packages/vendor/eugenewozniak/plugscan/pro_plugins.txt"];

    foreach ($plugins_files as $plugins_file) {
        if (!file_exists($plugins_file)) {
            WP_CLI::error("Unable to read " . basename($plugins_file) . " file.");
            return false;
        }

        $handle = fopen($plugins_file, "r");
        if ($handle) {
            while (($line = fgets($handle)) !== false) {
                if (trim($line) === $plugin_folder) {
                    fclose($handle);
                    return true;
                }
            }
            fclose($handle);
        } else {
            WP_CLI::error("Unable to read " . basename($plugins_file) . " file.");
            return false;
        }
    }

    return false;
}

}

WP_CLI::add_command('plugscan', 'PlugScan_Command');
