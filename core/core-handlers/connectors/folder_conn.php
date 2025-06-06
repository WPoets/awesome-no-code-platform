<?php
namespace aw2\folder_conn;

function get_results($config,$post_type,$read_file=true){
     $modules = array();
	
	if(!isset($config['path'])) return $modules;
	
	$path=$config['path'] . '/' . $post_type;
	
	if(!is_dir($path)) return $modules;
    $files = glob($path . '/*.module.html');
	
	foreach($files as $filename){
		$module=basename($filename);
		$module=str_replace('.module.html','',$module);
		$p=array();
		$p['post_name']=$module;
		$p['post_title']=$module;
		$p['path'] = $filename;
		$p['post_type'] = $post_type;
		if($read_file) $p['post_content'] = file_get_contents($filename);
		$modules[]=$p;
	}
	return $modules;
	
}

function convert_to_module($post_type,$module,$code,$path,$hash){
	$arr=array();
	$arr['module']=$module;
	$arr['post_type']=$post_type;
	$arr['title']=$module;
	$arr['id']=$path;
	$arr['code']=$code;
	$arr['hash']=$hash;
	return $arr;

}


namespace aw2\folder_conn\module;

\aw2_library::add_service('folder_conn.module.get','Get a Module',['namespace'=>__NAMESPACE__]);

function get($atts,$content=null,$shortcode=null){
	if(\aw2_library::pre_actions('all',$atts,$content)==false)return;
	
	extract(\aw2_library::shortcode_atts( array(
	'connection'=>'#default',
	'post_type'=>null,
	'module'=>null,
	), $atts) );
	if(\aw2_library::is_live_debug()){
		
		$live_debug_event=array();
		$live_debug_event['flow']='connection';
		$live_debug_event['action']='connection.called';
		$live_debug_event['stream']='module_get';
		$live_debug_event['hash']=$post_type . ':' . $module;
		$live_debug_event['module']=$module;
		$live_debug_event['connection']=$connection;
		$live_debug_event['post_type']=$post_type;

		$debug_format=array();
		$debug_format['bgcolor']='#DEB6AB';

		\aw2\live_debug\publish_event(['event'=>$live_debug_event,'format'=>$debug_format]);
	}
	//check the location
	$connection_arr=\aw2_library::$stack['code_connections'];
	if(!isset($connection_arr[$connection])) 
		throw new Exception($connection.' connection is not defined');
	
	$config = $connection_arr[$connection];
	$use_env_cache=USE_ENV_CACHE;
	$set_env_cache=SET_ENV_CACHE;
	$readonly= isset($config['read_only'])?$config['read_only']:false;
	 
	if($readonly){
		$use_env_cache=true;
		$set_env_cache=true;
	}
	
	$hash='modules:' . $post_type . ':' . $module;
	if(\aw2_library::is_live_debug()){
		$live_debug_event['action']='connection.getting';
		$live_debug_event['cache_key']=$hash;
		$live_debug_event['use_env_cache']=$use_env_cache;
		$live_debug_event['config']=$config;
		$debug_format['bgcolor']='#DEB6AB';

		\aw2\live_debug\publish_event(['event'=>$live_debug_event,'format'=>$debug_format]);
	}
	$return_value=array();

	if($use_env_cache){
		$return_value=\aw2\global_cache\get(["main"=>$hash ,"db"=>$config['redis_db']],null,null);
		$return_value=json_decode($return_value,true);
	if(\aw2_library::is_live_debug()){
			$live_debug_event['action']='connection.cache.used';
			$live_debug_event['cache_used']='yes';
			$live_debug_event['result']=$return_value;
			$debug_format['bgcolor']='#DEB6AB';

			\aw2\live_debug\publish_event(['event'=>$live_debug_event,'format'=>$debug_format]);
		}
	}

	if(!$return_value){
		$path=$config['path'] . '/' . $post_type . '/' . $module . '.module.html';
		$code = @file_get_contents($path);

		if($code!==false)
			$return_value=\aw2\folder_conn\convert_to_module($post_type,$module,$code,$path,$hash);
		
		if(\aw2_library::is_live_debug()){
				$live_debug_event['action']='connection.cache.not_used';
				$live_debug_event['path']=$path;
				$live_debug_event['cache_used']='no';
				$live_debug_event['result']=$return_value;
				$debug_format['bgcolor']='#DEB6AB';
	
				\aw2\live_debug\publish_event(['event'=>$live_debug_event,'format'=>$debug_format]);
		}	
		if($set_env_cache){
			$ttl = isset($config['cache_expiry'])?$config['cache_expiry']:'600';
			\aw2\global_cache\set(["key"=>$hash,"db"=>$config['redis_db'],'ttl'=>$ttl],json_encode($return_value),null);
		}			
	}
	
	if(defined('SET_DEBUG_CACHE') && SET_DEBUG_CACHE){
		$fields = array('last_accessed'=>date('Y-m-d H:i:s'),'connection'=>$connection);
				
		\aw2\debug_cache\set_access_post_type(["post_type"=>$return_value['post_type'],"fields"=>$fields],'',null);
		\aw2\debug_cache\set_access_module(["post_type"=>$return_value['post_type'],"module"=>$return_value['module'],"fields"=>$fields],'',null);
				
		if(isset(\aw2_library::$stack['app'])){	
			$app_slug = \aw2_library::$stack['app']['slug'];
			$fields['app_name']= \aw2_library::$stack['app']['name'];
			\aw2\debug_cache\set_access_app(["app"=>$app_slug,"fields"=>$fields],'',null);
			unset($fields);
		}
				
	}

	$return_value=\aw2_library::post_actions('all',$return_value,$atts);
	if(\aw2_library::is_live_debug()){
		$live_debug_event['action']='connection.done';
		$debug_format['bgcolor']='#DEB6AB';

		\aw2\live_debug\publish_event(['event'=>$live_debug_event,'format'=>$debug_format]);
	}
	return $return_value;	
}


\aw2_library::add_service('folder_conn.module.meta','Get a Module Meta',['namespace'=>__NAMESPACE__]);

function meta($atts,$content=null,$shortcode=null){
	if(\aw2_library::pre_actions('all',$atts,$content)==false)return;
	
	extract(\aw2_library::shortcode_atts( array(
	'connection'=>'#default',
	'post_type'=>null,
	'module'=>null,
	), $atts) );
	
	//check the location
	$connection_arr=\aw2_library::$stack['code_connections'];
	if(!isset($connection_arr[$connection])) 
		throw new Exception($connection.' connection is not defined');
	
	$config = $connection_arr[$connection];
	$use_env_cache=USE_ENV_CACHE;
	$set_env_cache=SET_ENV_CACHE;
	$readonly= isset($config['read_only'])?$config['read_only']:false;
	 
	if($readonly){
		$use_env_cache=true;
		$set_env_cache=true;
	}

	$hash='modules_meta:' . $post_type . ':' . $module;
	 
	
	$metas=array();
	
	if($use_env_cache){
		$data=\aw2\global_cache\get(["main"=>$hash,"db"=>$config['redis_db']],null,null);
		$metas=json_decode($data,true);
	}
	
	if(!$metas){
		// read the settings.json for the app which is key value folder
		$path=$config['path'] . '/' . $post_type;
		$metas =  @file_get_contents($path.'/settings.json');
		$metas= json_decode($metas,true);
				
		if($set_env_cache){
			$ttl = isset($config['cache_expiry'])?$config['cache_expiry']:'600';
			\aw2\global_cache\set(["key"=>$hash,"db"=>$config['redis_db'],'ttl'=>$ttl],json_encode($metas),null);
		}
		
	}
	
	$return_value=\aw2_library::post_actions('all',$metas,$atts);	

	return $return_value;	
}

\aw2_library::add_service('folder_conn.module.exists','Get a Module',['namespace'=>__NAMESPACE__]);

function exists($atts,$content=null,$shortcode=null){
	if(\aw2_library::pre_actions('all',$atts,$content)==false)return;
	
	extract(\aw2_library::shortcode_atts( array(
	'connection'=>null,
	'post_type'=>null,
	'module'=>null,
	), $atts) );

	if(empty($connection)) 
		throw new Exception('connection is not provided');;

	$results=\aw2\folder_conn\collection\_list($atts);
	
	$module_names = array_column($results, 'post_title', 'post_name');

	
	
	if(isset($module_names[$module]))
		$return_value= true;
	else	
		$return_value= false;
	
	$return_value=\aw2_library::post_actions('all',$return_value,$atts);
	return $return_value;	
}

namespace aw2\folder_conn\collection;

\aw2_library::add_service('folder_conn.collection.get','Get a Collection',['namespace'=>__NAMESPACE__]);

function get($atts,$content=null,$shortcode=null){
	if(\aw2_library::pre_actions('all',$atts,$content)==false)return;
	
	extract(\aw2_library::shortcode_atts( array(
	'connection'=>null,
	'post_type'=>null,
	), $atts) );
	
	$connection_arr=\aw2_library::$stack['code_connections'];
	if(!isset($connection_arr[$connection])) 
		throw new Exception($connection.' connection is not defined');
	
	$config = $connection_arr[$connection];
	$use_env_cache=USE_ENV_CACHE;
	$set_env_cache=SET_ENV_CACHE;
	$readonly= isset($config['read_only'])?$config['read_only']:false;
	 
	if($readonly){
		$use_env_cache=true;
		$set_env_cache=true;
	}
		
	
	$hash='collection:' . $post_type;
	
	$results=null;
	if($use_env_cache){
		$data=\aw2\global_cache\get(["main"=>$hash,"db"=>$config['redis_db']],null,null);
		$results=json_decode($data,true);
		if(\aw2_library::is_live_debug()){
			$live_debug_event['action']='connection.cache.used';
			$live_debug_event['cache_used']='yes';
			$live_debug_event['result']=$return_value;
			$debug_format['bgcolor']='#DEB6AB';

			\aw2\live_debug\publish_event(['event'=>$live_debug_event,'format'=>$debug_format]);
		}
	}
	
	if(is_null($results)){
		
		$results = \aw2\folder_conn\get_results($config,$post_type);
		if($set_env_cache){
			$ttl = isset($config['cache_expiry'])?$config['cache_expiry']:'600';
			\aw2\global_cache\set(["key"=>$hash,"db"=>$config['redis_db'],'ttl'=>$ttl],json_encode($results),null);
		}
		
		
	}
	$return_value=array();
	foreach ($results as $result) {
		
		$post=\aw2\folder_conn\convert_to_module($result['post_type'],$result['post_name'],$result['post_content'],$result['path'],'modules:' . $result['post_type'] . ':' . $result['post_name']);
		$return_value[$post['module']]=$post;
	}
	$return_value=\aw2_library::post_actions('all',$return_value,$atts);
	return $return_value;	
}


\aw2_library::add_service('folder_conn.collection.list','Get List of ',['func'=>'_list' ,'namespace'=>__NAMESPACE__]);

function _list($atts,$content=null,$shortcode=null){
	if(\aw2_library::pre_actions('all',$atts,$content)==false)return;
	
	extract(\aw2_library::shortcode_atts( array(
	'connection'=>null,
	'post_type'=>null,
	), $atts) );
	
	$connection_arr=\aw2_library::$stack['code_connections'];
	if(!isset($connection_arr[$connection])) 
		throw new Exception($connection.' connection is not defined');
	
	$config = $connection_arr[$connection];
	$use_env_cache=USE_ENV_CACHE;
	$set_env_cache=SET_ENV_CACHE;
	$readonly= isset($config['read_only'])?$config['read_only']:false;
	 
	if($readonly){
		$use_env_cache=true;
		$set_env_cache=true;
	}
		
	$hash='collection_list:' . $post_type;
	$results=null;
	if($use_env_cache){
		$data=\aw2\global_cache\get(["main"=>$hash,"db"=>$config['redis_db']],null,null);
		$results=json_decode($data,true);
	}
	
	if(is_null($results)){
		$results = \aw2\folder_conn\get_results($config,$post_type, false);			
		
		if($set_env_cache){
			$ttl = isset($config['cache_expiry'])?$config['cache_expiry']:'600';
			\aw2\global_cache\set(["key"=>$hash,"db"=>$config['redis_db'],'ttl'=>$ttl],json_encode($results),null);
		}
		
	}
	$return_value=$results;
	$return_value=\aw2_library::post_actions('all',$return_value,$atts);
	return $return_value;	
}


namespace aw2\folder_conn\func;
\aw2_library::add_service('folder_conn.func.get','Get a Func',['namespace'=>__NAMESPACE__]);
function get($atts,$content=null,$shortcode=null){
	
	extract(\aw2_library::shortcode_atts( array(
	'main'=>null,
	'connection'=>null,
	'namespace'=>null,
	'func'=>null,
	), $atts) );

    
	// If connection is not defined, throw exception
	if(!$connection)
		throw new \Exception('Connection parameter is required');
	
	// Check the location
	$connection_arr=\aw2_library::$stack['code_connections'];


/*
	if(!isset($connection_arr[$connection])) 
		throw new \Exception($connection.' connection is not defined');
	
	$config = $connection_arr[$connection];
*/
    $config=array(
        'connection_service'=>'folder_conn',
        'path'=>'/var/www/awesome-enterprise/core-handlers/namespace-functions',  // Any code which is present on CDN can be used using URL connection
        'redis_db'=>89,
        'cache_expiry'=>3000
     )   ;

    $func_folder = null;
	$func_filename = null;
	
	// Process main parameter if provided
	if($main !== null) {
		$parts = explode(':', $main);
		if(count($parts) === 2) {
			$func_folder = $parts[0];
			$func_filename = $parts[1];
		}
	}
	
	// If namespace and func are provided, use them
	if($namespace !== null && $func !== null) {
		$func_folder = $namespace;
		$func_filename = $func;
	}
	
	// Validate we have the required information
	if(!$func_folder || !$func_filename)
		throw new \Exception('Function folder and filename are required. Provide either main=folder:filename or both namespace and func parameters');
	
	// Create hash and filename
	$hash = 'func:' . $func_folder . ':' . $func_filename;

	
	$return_value = array();
	
	// Try to get from cache if enabled
	if(defined('USE_ENV_CACHE') && USE_ENV_CACHE) {
		$return_value = \aw2\global_cache\get(["main" => $hash, "db" => $config['redis_db']], null, null);
		if($return_value) {
			return json_decode($return_value, true);
		}
	}
	
	// If not in cache or cache disabled, read from file
	$path = $config['path'] . '/' . $func_folder . '/' . $func_filename . '.func.awesome';
	
	// Check if file exists
	if(!file_exists($path)) {
		$return_value = [
			'exists' => false
		];
	} else {
		$content = file_get_contents($path);
		
		$ab = new \array_builder();
		$return_value = $ab->parse($content);
		$return_value['exists'] = true;
	}
	
	// Store in cache if enabled
	if(defined('SET_ENV_CACHE') && SET_ENV_CACHE) {
		$ttl = isset($config['cache_expiry']) ? $config['cache_expiry'] : '300';
		\aw2\global_cache\set(["key" => $hash, "db" => $config['redis_db'], 'ttl' => $ttl], json_encode($return_value), null);
	}
	
	return $return_value;
}



namespace aw2\folder_conn\service;
\aw2_library::add_service('folder_conn.service.get','Get a service',['namespace'=>__NAMESPACE__]);
function get($atts,$content=null,$shortcode=null){
	
	extract(\aw2_library::shortcode_atts( array(
	'main'=>null,
	'connection'=>null,
	), $atts) );
	
	// If main is not defined, throw exception
	if(!$main)
		throw new \Exception('Main parameter is required');
		
	// Create hash for caching
	$hash = 'service:' . $main;
    
	$return_value = array();

	/*
	// Check the location in code connections
	$connection_arr = \aw2_library::$stack['code_connections'];
	if(!isset($connection_arr[$connection])) 
		throw new \Exception($connection.' connection is not defined');
	
	$config = $connection_arr[$connection];
	*/

	$config = array(
		'connection_service'=>'folder_conn',
		'path'=>'/var/www/awesome-enterprise/core-handlers/repository-services',  
		'redis_db'=>89,
		'cache_expiry'=>3000
	);
	
	// Try to get from cache if enabled
	if(defined('USE_ENV_CACHE_temp') && USE_ENV_CACHE) {
		$return_value = \aw2\global_cache\get(["main" => $hash, "db" => $config['redis_db']], null, null);
		if($return_value) {
			return json_decode($return_value, true);
		}
	}
	
	// Split $main into parts with .
	$parts = explode('.', $main);
	
	$current_folder = $config['path'];
	$service_filename = null;
	$path=null;	
	// Loop through the parts to find the service file
	foreach($parts as $part) {
		// Check if this part with .service.html exists in current folder
		$potential_service_file = $current_folder . '/' . $part . '.service.html';
		if(file_exists($potential_service_file)) {
			$service_filename = $part . '.service.html';
			// Set the full path for the service file
			$path = $current_folder . '/' . $service_filename;
			break;
		}
		
		// Check if this part is a folder
		$potential_folder = $current_folder . '/' . $part;
		if(is_dir($potential_folder)) {
			$current_folder = $potential_folder;
		} else {
			// Neither a service file nor a folder - can't proceed
			break;
		}
	}
	

	
	// Check if file exists
	if(is_null($path)) {
		$return_value = [
			'exists' => false
		];
	} else {
		$content = file_get_contents($path);
		
		\util::var_dump($content);
		$ab = new \array_builder();
		$return_value = $ab->parse($content);
		$return_value['exists'] = true;
	}
	
	// Store in cache if enabled
	if(defined('SET_ENV_CACHE') && SET_ENV_CACHE) {
		$ttl = isset($config['cache_expiry']) ? $config['cache_expiry'] : '300';
		\aw2\global_cache\set(["key" => $hash, "db" => $config['redis_db'], 'ttl' => $ttl], json_encode($return_value), null);
	}
	
	return $return_value;
}