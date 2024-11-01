<?php

function wp_plugin_skeleton_has_template_tag() {
	return apply_filters('wp_plugin_skeleton_has_template_tag', !!(wp_plugin_skeleton_get_template_tag()));
}
function wp_plugin_skeleton_get_template_tag() {
	return apply_filters('wp_plugin_skeleton_get_template_tag', WP_Plugin_Skeleton::template_tag());
}
function wp_plugin_skeleton_the_template_tag() {
	echo apply_filters('wp_plugin_skeleton_the_template_tag', wp_plugin_skeleton_get_template_tag());
}