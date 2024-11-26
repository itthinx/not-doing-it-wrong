<?php
/**
 * Plugin Name: Not Doing it Wrong
 * Description: A WordPress plugin as a last resort to issues when <code>_doing_it_wrong()</code> is too eager and we want to find out what is happening. Avoids triggering a user error for calls to <code>_doing_it_wrong()</code> and gathers information which is logged at shutdown. The plugin file <code>not-doing-it-wrong.php</code> must be placed in <code>mu-plugins</code> so it can catch all instances.
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
		error_log( 'Not Doing it Wrong ... ' . var_export( self::$archive, true ) );
	}
}

Not_Doing_It_Wrong::init();
