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
    public function scan($_, $__)
    {
        $this->check_fake_plugins();
    }

    public function test($_, $__)
    {
        WP_CLI::line("Hello World");
    }

    public function whitelist($args, $assoc_args)
    {
        list($plugin) = $args;

        // Set up the cURL request
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'https://wozner.net/wp-cli/add_plugin.php');
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query(['plugin' => $plugin]));
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['PRIVATE-TOKEN: JECubqeDKpG8CqE3DHSr ']);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        // Send the request and get the response
        $response = curl_exec($ch);

        // Check for errors
        if (curl_errno($ch)) {
            WP_CLI::error("Failed to whitelist plugin: $plugin. Error: " . curl_error($ch));
        } else {
            // Check the HTTP status code
            $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            if ($statusCode == 200) {
                WP_CLI::success("Plugin whitelisted: $plugin");
            } else {
                WP_CLI::error("Failed to whitelist plugin: $plugin. HTTP status code: " . $statusCode);
            }
        }

        // Close the cURL session
        curl_close($ch);
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
        // Set the HTTP context options for file_get_contents
        $opts = [
            "http" => [
                "header" => "PRIVATE-TOKEN: JECubqeDKpG8CqE3DHSr\r\n"
            ]
        ];
        $context = stream_context_create($opts);

        // Replace local paths with the URLs of your remote files
        $plugins_files = [
            "https://wozner.net/wp-cli/official_plugins1.txt",
            "https://wozner.net/wp-cli/official_plugins2.txt",
            "https://wozner.net/wp-cli/pro_plugins.txt"
        ];

        foreach ($plugins_files as $plugins_file) {
            // Use file_get_contents with the HTTP context to fetch the file
        $file_contents = file_get_contents($plugins_file, false, $context);
        if ($file_contents === false) {
            WP_CLI::error("Unable to read " . basename($plugins_file) . " file.");
            return false;
        }

        // Split the file into lines
        $lines = explode("\n", $file_contents);

        // Check each line for the plugin folder name
        foreach ($lines as $line) {
            if (trim($line) === $plugin_folder) {
                return true;
            }
        }
    }

    return false;
}

}

WP_CLI::add_command('plugscan', 'PlugScan_Command');
