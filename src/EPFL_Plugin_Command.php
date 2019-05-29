<?php

namespace EPFL_WP_CLI;

/**
 * Manage plugin installation with symlinks
 *
 * Parent class can be found here:
 * https://github.com/wp-cli/extension-command/blob/master/src/Plugin_Command.php
 *
 */
class EPFL_Plugin_Command extends \Plugin_Command  {

    var $PLUGIN_FOLDER = "/wp/wp-content/plugins/";

    /**
	 * Installs one or more plugins.
	 *
	 * ## OPTIONS
	 *
	 * <plugin|zip|url>...
	 * : One or more plugins to install. Accepts a plugin slug, the path to a local zip file, or a URL to a remote zip file.
	 *
	 * [--version=<version>]
	 * : If set, get that particular version from wordpress.org, instead of the
	 * stable version.
	 *
	 * [--force]
	 * : If set, the command will overwrite any installed version of the plugin, without prompting
	 * for confirmation.
	 *
	 * [--activate]
	 * : If set, the plugin will be activated immediately after install.
	 *
	 * [--activate-network]
	 * : If set, the plugin will be network activated immediately after install
	 *
	 * ## EXAMPLES
	 *
	 *     # Install the latest version from wordpress.org and activate
	 *     $ wp plugin install bbpress --activate
	 *     Installing bbPress (2.5.9)
	 *     Downloading install package from https://downloads.wordpress.org/plugin/bbpress.2.5.9.zip...
	 *     Using cached file '/home/vagrant/.wp-cli/cache/plugin/bbpress-2.5.9.zip'...
	 *     Unpacking the package...
	 *     Installing the plugin...
	 *     Plugin installed successfully.
	 *     Activating 'bbpress'...
	 *     Plugin 'bbpress' activated.
	 *     Success: Installed 1 of 1 plugins.
	 *
	 *     # Install the development version from wordpress.org
	 *     $ wp plugin install bbpress --version=dev
	 *     Installing bbPress (Development Version)
	 *     Downloading install package from https://downloads.wordpress.org/plugin/bbpress.zip...
	 *     Unpacking the package...
	 *     Installing the plugin...
	 *     Plugin installed successfully.
	 *     Success: Installed 1 of 1 plugins.
	 *
	 *     # Install from a local zip file
	 *     $ wp plugin install ../my-plugin.zip
	 *     Unpacking the package...
	 *     Installing the plugin...
	 *     Plugin installed successfully.
	 *     Success: Installed 1 of 1 plugins.
	 *
	 *     # Install from a remote zip file
	 *     $ wp plugin install http://s3.amazonaws.com/bucketname/my-plugin.zip?AWSAccessKeyId=123&Expires=456&Signature=abcdef
	 *     Downloading install package from http://s3.amazonaws.com/bucketname/my-plugin.zip?AWSAccessKeyId=123&Expires=456&Signature=abcdef
	 *     Unpacking the package...
	 *     Installing the plugin...
	 *     Plugin installed successfully.
	 *     Success: Installed 1 of 1 plugins.
	 *
	 *     # Update from a remote zip file
	 *     $ wp plugin install https://github.com/envato/wp-envato-market/archive/master.zip --force
	 *     Downloading install package from https://github.com/envato/wp-envato-market/archive/master.zip
	 *     Unpacking the package...
	 *     Installing the plugin...
	 *     Renamed Github-based project from 'wp-envato-market-master' to 'wp-envato-market'.
	 *     Plugin updated successfully
	 *     Success: Installed 1 of 1 plugins.
	 *
	 *     # Forcefully re-install all installed plugins
	 *     $ wp plugin install $(wp plugin list --field=name) --force
	 *     Installing Akismet (3.1.11)
	 *     Downloading install package from https://downloads.wordpress.org/plugin/akismet.3.1.11.zip...
	 *     Unpacking the package...
	 *     Installing the plugin...
	 *     Removing the old version of the plugin...
	 *     Plugin updated successfully
	 *     Success: Installed 1 of 1 plugins.
	 */
    public function install( $args, $assoc_args ) {

        /* Looping through plugins to install */
        foreach($args as $plugin_name)
        {
            /* If an URL or a ZIP file has been given, we can't handle it so we call parent method */
            if(preg_match('/(^http|\.zip$)/', $plugin_name)==1)
            {
                parent::install($args, $assoc_args);
                return;
            }

            /* Looking if plugin is already installed. We cannot call "parent::is_installed()" because
             it just halts process with 1 or 0 to tell plugin installation status... */
            $response = \WP_CLI::launch_self( 'theme is-installed', array($plugin_name), array(), false, true );

            /* If plugin is not installed */
            if($response->return_code == 1)
            {

                /* If plugin is available in WP image */
                $wp_image_plugin_folder = $this->PLUGIN_FOLDER . $plugin_name;
                if(file_exists($wp_image_plugin_folder))
                {
                    /* Creating symlink to "simulate" plugin installation */
                    if(symlink($wp_image_plugin_folder, ABSPATH . 'wp-content/plugins/'. $plugin_name))
                    {
                        \WP_CLI::success("Symlink created for ".$plugin_name);

                        /* If extra args were given (like --activate) */
                        if(sizeof($assoc_args)>0)
                        {
                            /* They cannot be handled here because when we create symlink, WP DB is not updated to
                            say "hey, plugin is installed" so we will get an error when trying to do another action (ie: --activate)
                            because WordPress believe plugin isn't installed */
                            \WP_CLI::warning("Extra args (starting with --) are not handled with symlinked plugins, please call specific WPCLI command(s)");
                        }
                    }
                    else /* Error */
                    {
                        /* We display an error and exit */
                        \WP_CLI::error("Error creating symlink for ".$plugin_name, true);
                    }

                }
                else /* Plugin is not found in WP image  */
                {
                    \WP_CLI::log("No plugin found to create symlink for ". $plugin_name.". Installing it...");
                    parent::install(array($plugin_name), $assoc_args);
                }

            }
            else /* Plugin is already installed */
            {
                /* We call parent function to do remaining things if needed*/
                parent::install(array($plugin_name), $assoc_args);
            }

        } /* END looping through given plugins */
    }
}

/* We override existing commands with extended one */
\WP_CLI::add_command( 'plugin', 'EPFL_WP_CLI\EPFL_Plugin_Command' );