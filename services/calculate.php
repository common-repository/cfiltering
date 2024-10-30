<?php
namespace CollaborativeFilteringService;

if ( !defined( 'COLLABORATIVE_FILTERING_PLUGIN' ) )
	exit;

class CollaborativeFiltering_Calculate extends CollaborativeFiltering_Service_Base
{

	private static $_instance = null;

	protected function __construct()
	{
		if ( !wp_next_scheduled( 'cf_calculate_event' ) ) {
			wp_schedule_single_event( time() + $this->apply_filters( 'calc_interval', COLLABORATIVE_FILTERING_CALC_INTERVAL ), 'cf_calculate_event' );
		}
		add_action( 'cf_calculate_event', function () {
			$this->check_progress();
		} );

		if ( $this->apply_filters( 'calc_log', COLLABORATIVE_FILTERING_CALC_LOG ) ) {
			add_action( 'cf_start_calculate_process', function () {
				$this->log( 'start calc' );
			} );
			add_action( 'cf_end_calculate_process', function ( $start ) {
				$elapsed = ( microtime( true ) - $start ) * 1000;
				$this->log( 'end calc [elapsed time: ' . $elapsed . ' ms]' );
			} );
			add_action( 'cf_start_calculate', function ( $access ) {
				$this->log( 'target count: ' . count( $access ) );
			} );
		}
	}

	public static function get_instance()
	{
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new CollaborativeFiltering_Calculate();
		}
		return self::$_instance;
	}

	public function clear_event()
	{
		wp_clear_scheduled_hook( 'cf_calculate_event' );
	}

	public function run_now()
	{
		$this->clear_event();

		$this->execute();
	}

	private function check_progress()
	{
		$time = $this->get_time();
		$now = time();
		if ( $now - $time <= $this->apply_filters( 'calc_interval', COLLABORATIVE_FILTERING_CALC_INTERVAL ) ) {
			return;
		}
		if ( $now - $time <= $this->apply_filters( 'calc_timeout', COLLABORATIVE_FILTERING_CALC_TIMEOUT ) ) {
			return;
		}

		$start = $this->get_start();
		if ( $start > 0 ) {
			$this->set_start( -1 );
		}
		$this->set_time( time() );

		$this->execute();
	}

	private function execute()
	{
		$start = microtime( true );
		$this->do_action( 'start_calculate_process', $start );

		set_time_limit( 0 );

		$data = array( 'time' => time(), 'start' => time() );
		$this->set_time( $data['time'] );
		$this->set_start( $data['start'] );

		$num = $this->calculate( $data );
		$total = $num[1];
		$num = $num[0];

		$elapsed = ( microtime( true ) - $start ) * 1000;
		$this->calculate_sampling_rate( $num, $elapsed, $total );

		$this->do_action( 'end_calculate_process', $start );
	}

	private function calculate_sampling_rate( $num, $elapsed, $total )
	{
		if ( $num <= 0 ) {
			return;
		}

		global $cf_option;
		$threshold = $this->apply_filters( 'calculate_threshold', 0.1 );

		$last = $cf_option->get( 'last_calculated', 0 );
		$sum = $cf_option->get( 'total_calculated', 0 );
		$sampling = $cf_option->get( 'sampling_rate', COLLABORATIVE_FILTERING_DEFAULT_SAMPLING_RATE );

		$sum += $num;
		$now = time();
		if ( $last <= 0 || $sum <= 0 ) {
			$sub = $this->apply_filters( 'calc_interval', COLLABORATIVE_FILTERING_CALC_INTERVAL );
			$rate = 1;
		} else {
			$sub = $now - $last;
			$rate = $num / $sum;
			if ( $rate < 0.1 )
				$rate = 0.1;
			if ( $sum > 0.9 * PHP_INT_MAX ) {
				$sum = intval( floor( 0.9 * PHP_INT_MAX ) );
			}
		}

		if ( $elapsed > $sub * $threshold * 1000 ) {
			$new_sampling1 = $sub * $threshold * 1000 / $elapsed;
		} else {
			$new_sampling1 = 1;
		}

		if ( $total - $num > 0 ) {
			$new_sampling2 = $num * 1.0 / $total;
			if ( $new_sampling1 > $new_sampling2 ) {
				$new_sampling = $new_sampling2;
			} else {
				$new_sampling = $new_sampling1;
			}
		} else {
			$new_sampling = $new_sampling1;
		}

		$new_sampling = $rate * $new_sampling + ( 1 - $rate ) * $sampling;

		$cf_option->set( 'last_calculated', $now, false );
		$cf_option->set( 'total_calculated', $sum, false );
		$cf_option->set( 'sampling_rate', $new_sampling, true );
	}

	private function calculate( $data )
	{
		global $cf_model_access;
		$access = $cf_model_access->fetch_all(
			array(
				array(
					'AND',
					array(
						array( 'is_processed', '=', '?', 0 )
					)
				)
			), array(
				'updated_at' => 'asc'
			), $this->apply_filters( 'calculate_number', COLLABORATIVE_FILTERING_CALCULATE_NUMBER ) );

		$this->do_action( 'start_calculate', $access );

		if ( count( $access ) <= 0 ) {
			$this->init_calculate();
			return array( 0, 0 );
		}

		$total = $cf_model_access->count(
			array(
				array(
					'AND',
					array(
						array( 'is_processed', '=', '?', 0 )
					)
				)
			)
		);

		global $cf_number;
		$group = array();
		$post_ids = array();
		foreach ( $access as $d ) {
			$post_id = $cf_model_access->get_value( $d, 'post_id' ) - 0;
			$user_id = $cf_model_access->get_value( $d, 'user_id' );
			if ( !isset( $group[$user_id] ) ) {
				$group[$user_id] = array();
				$cf_number->register_user( $user_id );
			}
			$group[$user_id][] = $post_id;
			if ( !in_array( $post_id, $post_ids ) ) {
				$post_ids[] = $post_id;
			}
		}

		$n = 0;
		$cf_number->before();
		$killed = false;
		foreach ( $group as $user_id => $ids ) {
			$cf_number->update( $user_id, $ids );
			$post_ids = array_unique( array_merge( $post_ids, $ids ) );
			$n += count( $ids );
			if ( $this->check_end( $data ) ) {
				$killed = true;
				break;
			}
		}
		$cf_number->after();
		$this->calc_jaccard( $post_ids );

		if ( !$killed ) {
			$this->init_calculate();
		}
		return array( $n, $total );
	}

	private function check_end( $data )
	{
		$start = $this->get_start();
		if ( !$start || $start != $data['start'] ) {
			return true;
		}

		$data['time'] = time();
		$this->set_time( $data['time'] );
		return false;
	}

	private function calc_jaccard( $post_ids )
	{
		global $cf_db, $cf_post;
		$table = $cf_db->get_table( 'number' );
		foreach ( $post_ids as $post_id ) {
			$sql = <<< EOS
SELECT
	post_id2 as post_id,
	(
		(SELECT number FROM $table WHERE post_id1 = ? AND post_id2 = t1.post_id2) /
		(
			(SELECT number FROM $table WHERE post_id1 = ? AND post_id1 = post_id2) +
			(SELECT number FROM $table WHERE post_id1 = t1.post_id2 AND post_id1 = post_id2) -
			(SELECT number FROM $table WHERE post_id1 = ? AND post_id2 = t1.post_id2)
		)
	) as jaccard,
	(SELECT
		SUM(number)
		FROM $table
		WHERE
			post_id1 = ? AND
			post_id1 != post_id2
	) as total
FROM $table as t1
WHERE
	post_id1 = ? AND
	post_id1 != post_id2
ORDER BY
	jaccard DESC,
	updated_at DESC,
	post_id DESC;
EOS;
			$bind = $cf_db->init_bind( array_fill( 0, 5, 'i' ), array_fill( 0, 5, $post_id ) );
			$results = array_slice( array_map( function ( $d ) {
				return array( 'post_id' => $d->post_id - 0, 'jaccard' => $d->jaccard - 0, 'total' => $d->total - 0 );
			}, $cf_db->fetch_all( $sql, $bind, 'number', __FILE__, __LINE__ ) ), 0, $this->apply_filters( 'max_save_data_number', COLLABORATIVE_FILTERING_MAX_SAVE_DATA_NUMBER ) );
			$cf_post->set( $post_id, 'jaccard', serialize( $results ) );
		}
	}

	public function get_jaccard( $post_id, $threshold = null, $min_number = null )
	{
		if ( is_null( $threshold ) ) {
			$threshold = $this->apply_filters( 'jaccard_threshold', COLLABORATIVE_FILTERING_JACCARD_THRESHOLD );
		}
		if ( is_null( $min_number ) ) {
			$min_number = $this->apply_filters( 'jaccard_min_number', COLLABORATIVE_FILTERING_JACCARD_MIN_NUMBER );
		}
		global $cf_post;
		$data = $cf_post->get( 'jaccard', $post_id, true, 'a:0:{}' );
		$ret = unserialize( $data );
		$ret = !is_array( $ret ) ? array() : $ret;
		$post_types = $this->valid_post_types();
		$post_statuses = $this->valid_post_statuses();
		$ret = array_map( function ( $d ) use ( $post_types, $post_statuses ) {
			$post = get_post( $d['post_id'] );
			if ( !$post ) {
				return false;
			}
			if ( !empty( $post_types ) && !in_array( $post->post_type, $post_types ) ) {
				return false;
			}
			if ( !empty( $post_statuses ) && !in_array( $post->post_status, $post_statuses ) ) {
				return false;
			}
			$d['post'] = $post;
			return $d;
		}, $ret );
		$ret = array_filter( $ret, function ( $d ) {
			return false !== $d;
		} );
		if ( is_numeric( $threshold ) && $threshold > 0 && $threshold < 1 && count( $ret ) > 0 ) {
			$ret = array_filter( $ret, function ( $d ) use ( $threshold ) {
				return $d['jaccard'] >= $threshold;
			} );
		}
		$ret = array_values( $ret );
		if ( is_int( $min_number ) && $min_number >= 1 && count( $ret ) > 0 && $ret[0]['total'] < $min_number ) {
			$ret = array();
		}
		return $this->apply_filters( 'get_jaccard', $ret, $post_id, $threshold );
	}

	public function get_post_ids( $post_id, $threshold = null, $min_number = null )
	{
		return array_map( function ( $d ) {
			return $d['post']->ID;
		}, $this->get_jaccard( $post_id, $threshold, $min_number ) );
	}

	public function get_posts( $post_id, $threshold = null, $min_number = null )
	{
		return array_map( function ( $d ) {
			return $d['post'];
		}, $this->get_jaccard( $post_id, $threshold, $min_number ) );
	}

	private function get_time()
	{
		global $cf_option;
		return $cf_option->get( 'calculate_time', 0 );
	}

	private function get_start()
	{
		global $cf_option;
		return $cf_option->get( 'calculate_start', 0 );
	}

	private function set_time( $time )
	{
		global $cf_option;
		return $cf_option->set( 'calculate_time', $time );
	}

	private function set_start( $time )
	{
		global $cf_option;
		return $cf_option->set( 'calculate_start', $time );
	}

	private function init_calculate()
	{
		global $cf_option;
		$cf_option->delete( 'calculate_start' );
		return true;
	}
}

$GLOBALS['cf_calculate'] = CollaborativeFiltering_Calculate::get_instance();
