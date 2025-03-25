<?php

if (!defined('ABSPATH')){
	exit;
}
//this function controls the walker class for aw2_menu class

function aw2_navwalker_modify_nav_menu_args( $args )
{


	// Check if the custom walker is explicitly requested
	$use_custom_walker = isset( $args['use_custom_walker'] ) && $args['use_custom_walker'] === true;

	// Fire a hook to allow third-party handling
	$allow_third_party = apply_filters( 'aw2_allow_third_party_menu_walker', false, $args );

	// If third-party handling is allowed and custom walker is not explicitly requested, skip
	if ( $allow_third_party && !$use_custom_walker ) {
		return $args;
	}
	

	if(!isset($args['container']))
	{
		$args['container'] ='div';
	}
	if(!isset($args['container_class']) || empty($args['container_class']))
	{
		$args['container_class'] = 'collapse navbar-collapse';
	}

	if(!isset($args['walker']) || empty($args['walker']))
	{
		
		if ( !class_exists( 'wp_bootstrap_navwalker' )  ) {
			require('wp_bootstrap_navwalker.php');
		}
		
		$args['walker'] = new wp_bootstrap_navwalker();
		$args['fallback_cb']='wp_bootstrap_navwalker::fallback';
		@$args['menu_class'] =$args['menu_class'].' nav navbar-nav';
	}
	
	if(isset($args['walker']) && $args['walker']=='default')
	{
		$args['walker'] = new Walker_Nav_Menu;
		$args['fallback_cb']='';
		$args['menu_class'] =$args['menu_class'].' nav navbar-nav';
		$args['container_class'] =$args['container_class'].' navbar-collapse ';
	}

	return $args;
}

add_filter( 'wp_nav_menu_args', 'aw2_navwalker_modify_nav_menu_args' );
