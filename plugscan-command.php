<?php

if (!class_exists('WP_CLI')) {
    return;
}

class PlugScan_Command extends WP_CLI_Command
{
    private $plugins_hashset = [];
    private $local_files = [
        "/tmp/official_plugins1.txt",
        "/tmp/official_plugins2.txt",
        "/tmp/pro_plugins.txt",
    ];
    private $remote_files = [
        "https://wozner.net/wp-cli/official_plugins1.txt",
        "https://wozner.net/wp-cli/official_plugins2.txt",
        "https://wozner.net/wp-cli/pro_plugins.txt",
    ];

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

    // Set up the HTTP context options for file_get_contents
    $opts = [
        "http" => [
            "method" => "POST",
            "header" => "Content-Type: application/x-www-form-urlencoded\r\n" .
                        "PRIVATE-TOKEN: JECubqeDKpG8CqE3DHSr\r\n",
            "content" => http_build_query(['plugin' => $plugin])
        ]
    ];
    $context = stream_context_create($opts);

    // Send the request and get the response
    $response = file_get_contents('https://wozner.net/wp-cli/add_plugin.php', false, $context);

    if ($response === false) {
        WP_CLI::error("Failed to whitelist plugin: $plugin. Error: " . error_get_last()['message']);
    } else {
        // Check the HTTP status code
        $statusCode = $http_response_header[0];
        if (strpos($statusCode, '200') !== false) {
            WP_CLI::success("Plugin whitelisted: $plugin");
        } else {
            WP_CLI::error("Failed to whitelist plugin: $plugin. HTTP status code: " . $statusCode);
        }
    }
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
            $fake_plugins[] = [
                'name' => $plugin_folder,
                'uri' => $this->get_plugin_uri($plugins_path . '/' . $plugin_folder)
            ];
        }
    }

    if (!empty($fake_plugins)) {
        WP_CLI::warning("Fake plugins found:");
        $output = new \cli\Table();
        $output->setHeaders(['Name', 'Plugin URI']);
        foreach ($fake_plugins as $fake_plugin) {
            $output->addRow([$fake_plugin['name'], $fake_plugin['uri']]);
        }
        $output->display();
    } else {
        WP_CLI::success("No fake plugins found.");
    }
}

private function get_plugin_uri($plugin_dir)
{
    $plugin_uri = 'No file found with Plugin URI, might be fake plugin';
    $php_files = glob($plugin_dir . '/*.php');

    foreach ($php_files as $php_file) {
        $file_content = file_get_contents($php_file);
        $matches = [];

        // look for a line starting with "Plugin URI:"
        if (preg_match('/^.*Plugin URI: (.*)$/mi', $file_content, $matches)) {
            $plugin_uri = trim($matches[1]);
            break;
        }
    }

    return $plugin_uri;
}
    private function isPluginInTxtFile($plugin_folder)
    {
        if (empty($this->plugins_hashset)) {
            $this->buildPluginsHashset();
        }
																	 
			 
		  
												

        // Check if the plugin folder is in the plugins_hashset
        return isset($this->plugins_hashset[$plugin_folder]);
}
															  
													   
		  

private function buildPluginsHashset()
{
    $this->plugins_hashset = []; // Clear the existing hashset

    // Set the HTTP context options for file_get_contents
    $opts = [
        "http" => [
            "header" => "PRIVATE-TOKEN: JECubqeDKpG8CqE3DHSr\r\n"
        ]
    ];
    $context = stream_context_create($opts);

    for ($i = 0; $i < count($this->remote_files); $i++) {
        $remote_file = $this->remote_files[$i];
        $local_file = $this->local_files[$i];

        // Check if the remote file is newer than the local file
        if (!file_exists($local_file) || filesize($local_file) != $this->getRemoteFileSize($remote_file)) {
            // Download the file
            $file_contents = file_get_contents($remote_file, false, $context);
            if ($file_contents === false) {
                WP_CLI::error("Unable to download " . basename($remote_file) . " file.");
                continue;
            }

            // Save the file locally
            file_put_contents($local_file, $file_contents);
        } else {
            // Use the local file
            $file_contents = file_get_contents($local_file);
        }

        // Split the file into lines
        $lines = explode("\n", $file_contents);

        // Store each line in the plugins_hashset
        foreach ($lines as $line) {
            $this->plugins_hashset[trim($line)] = true;
							
			 
        }
    }

    return true;
}

private function getRemoteFileSize($url)
{
    // Fetch the headers for the URL
    $headers = get_headers($url, 1);

    // Return the Content-Length header, if it exists
    if (array_key_exists('Content-Length', $headers)) {
        return (int)$headers['Content-Length'];
    }

    return 0;
}

}

WP_CLI::add_command('plugscan', 'PlugScan_Command');
