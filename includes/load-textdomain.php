<?php

namespace plugin\clone_posts;

/**
 * This will load the current plugin textdomain
 */
function load_textdomain() {
    load_plugin_textdomain('clone-posts', false, 'clone-posts/languages');
}

add_action( 'plugins_loaded', 'plugin\clone_posts\load_textdomain' );