<?php
/**
 * Plugin Name: Not Doing it Wrong
 * Plugin URI: https://github.com/itthinx/not-doing-it-wrong
 * Description: A WordPress plugin as a last resort to issues when <code>_doing_it_wrong()</code> is too eager and we want to find out what is happening. Avoids triggering a user error for calls to <code>_doing_it_wrong()</code> and gathers information which is logged at shutdown. The information logged includes originating functions, counts and stack traces. The plugin file <code>not-doing-it-wrong.php</code> must be placed in <code>mu-plugins</code> so it can catch all instances. <strong>The plugin will produce very extensive log entries!</strong>
 * Version: 1.0.0
 * Requires at least: 6.0
 * Requires PHP: 7.4
 * Author: itthinx
 * Author URI: https://www.itthinx.com
 * Donate-Link: https://www.itthinx.com/shop/
 * License: GPLv3
 */

if ( !defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Plugin class.
 */
class Not_Doing_It_Wrong {

	/**
	 * Holds triggered messages.
	 *
	 * @var array
	 */
	private static $archive = array();

	/**
	 * Register actions and filters.
	 */
	public static function init() {
		add_filter( 'doing_it_wrong_trigger_error', array( __CLASS__, 'doing_it_wrong_trigger_error' ), 0, 4 );
		add_action( 'shutdown', array( __CLASS__, 'shutdown' ), PHP_INT_MAX );
	}

	/**
	 * Hooks into the doing_it_wrong_trigger_error filter.
	 *
	 * @param boolean $trigger
	 * @param string $function_name
	 * @param string $message
	 * @param string $version
	 *
	 * @return boolean
	 */
	public static function doing_it_wrong_trigger_error( $trigger, $function_name, $message, $version ) {
		// stack trace
		ob_start();
		debug_print_backtrace();
		$stack_trace = ob_get_contents();
		ob_end_clean();
		$stack_trace_hash = md5( $stack_trace );

		if ( !isset( self::$archive[$function_name] ) ) {
			self::$archive[$function_name] = array(
				'messages' => array()
			);
		}
		$found = false;
		for ( $i = 0; $i < count( self::$archive[$function_name]['messages'] ); $i++ ) {
			if (
				self::$archive[$function_name]['messages'][$i]['message'] === $message
			) {
				self::$archive[$function_name]['messages'][$i]['count']++;
				$found = true;
				if ( key_exists( $stack_trace_hash, self::$archive[$function_name]['messages'][$i]['stack_traces'] ) ) {
					self::$archive[$function_name]['messages'][$i]['stack_traces'][$stack_trace_hash]['count']++;
				} else {
					self::$archive[$function_name]['messages'][$i]['stack_traces'][$stack_trace_hash] = array(
						'stack_trace' => $stack_trace,
						'count' => 1
					);
				}
			}
		}
		if ( !$found ) {
			self::$archive[$function_name]['messages'][] = array(
				'message' => $message,
				'count' => 1,
				'stack_traces' => array(
					$stack_trace_hash => array(
						'stack_trace' => $stack_trace,
						'count' => 1
					)
				),
			);
		}
		$trigger = false;
		return $trigger;
	}

	public static function shutdown() {

		if ( count( self::$archive ) === 0 ) {
			return;
		}

		error_log( '' );
		error_log( '==========================' );
		error_log( '= Not Doing it Wrong ... =' );
		error_log( '==========================' );

		error_log( '' );
		error_log( '===========' );
		error_log( '= Summary =' );
		error_log( '===========' );
		error_log( '' );

		// Summary
		$max_function_name = max( array_map( 'strlen', array_keys( self::$archive ) ) );
		foreach ( self::$archive as $function_name => $data ) {
			$count = 0;
			for ( $i = 0; $i < count( $data['messages'] ); $i++ ) {
				$count += $data['messages'][$i]['count'];
			}
			$sep = sprintf( '+-%-'.$max_function_name.'s-+-%-8s-+', str_repeat( '-', $max_function_name ), '--------' );
			error_log( $sep );
			error_log( sprintf( '| %-'.$max_function_name.'s | %-8s |', 'Function', 'Count' ) );
			error_log( $sep );
			error_log( sprintf( '| %-'.$max_function_name.'s | %8d |', $function_name, $count ) );
			error_log( $sep );
		}

		error_log( '' );
		error_log( '==========' );
		error_log( '= Detail =' );
		error_log( '==========' );

		foreach ( self::$archive as $function_name => $data ) {

			error_log( '' );
			error_log( '* Function : ' . $function_name );
			error_log( '* Count    : ' . $count );
			error_log( '' );

			for ( $i = 0; $i < count( $data['messages'] ); $i++ ) {
				for ( $j = 0; $j < count( $data['messages'][$i]['stack_traces'] ); $j++ ) {
					$stack_traces = $data['messages'][$i]['stack_traces'];
					$k = 0;
					foreach ( $stack_traces as $hash => $stack_trace ) {
						$k++;
						error_log( sprintf( '* Stack Trace %d of %d for function %s : ', $k, count( $stack_traces ), $function_name ) );
						error_log( '' );
						array_map( 'error_log', explode( "\n", $stack_trace['stack_trace'] ) );
						error_log( '' );
					}
				}
			}
		}

		error_log( '' );
	}
}

Not_Doing_It_Wrong::init();
