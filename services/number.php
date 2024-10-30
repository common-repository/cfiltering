<?php
namespace CollaborativeFilteringService;

if ( !defined( 'COLLABORATIVE_FILTERING_PLUGIN' ) )
	exit;

class CollaborativeFiltering_Number extends CollaborativeFiltering_Service_Base
{

	private static $_instance = null;

	private $list  = array();
	private $add   = array();
	private $keys  = array();
	private $n     = 0;
	private $users = array();
	private $access;
	private $ulist = array();

	public static function get_instance()
	{
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new CollaborativeFiltering_Number();
		}
		return self::$_instance;
	}

	private function register( $post_id1, $post_id2 )
	{
		$new = false;
		if ( !isset( $this->keys[$post_id1] ) ) {
			$this->keys[$post_id1] = array();
		}
		if ( !isset( $this->keys[$post_id1][$post_id2] ) ) {
			$this->keys[$post_id1][$post_id2] = $this->n++;
			$new = true;
		}

		$k = $this->keys[$post_id1][$post_id2];
		if ( $new ) {
			$this->list[$k] = array( $post_id1, $post_id2, 1 );
			if ( !isset( $this->add[1] ) ) {
				$this->add[1] = array();
			}
			$this->add[1][$k] = false;
		} else {
			$k = $this->keys[$post_id1][$post_id2];
			$n = $this->list[$k][2];
			unset( $this->add[$n][$k] );
			$n++;

			$this->list[$k][2] = $n;
			if ( !isset( $this->add[$n] ) ) {
				$this->add[$n] = array();
			}
			$this->add[$n][$k] = false;
		}
	}

	public function update( $user_id, $post_ids )
	{
		if ( count( $post_ids ) <= 0 ) {
			return;
		}
		global $cf_model_access;
		$rows = isset( $this->access[$user_id] ) ? $this->access[$user_id] : array();
		$this->ulist[] = array( $user_id, $post_ids );

		if ( count( $rows ) <= 0 && count( $post_ids ) <= 1 ) {
			$post_id = $post_ids[0];
			$this->register( $post_id, $post_id );
			return;
		}

		foreach ( $rows as $r ) {
			$p1 = $cf_model_access->get_value( $r, "post_id" ) - 0;
			foreach ( $post_ids as $p2 ) {
				$this->register( $p1, $p2 );
				$this->register( $p2, $p1 );
			}
		}
		foreach ( $post_ids as $p1 ) {
			foreach ( $post_ids as $p2 ) {
				$this->register( $p1, $p2 );
			}
		}
	}

	public function register_user( $user_id )
	{
		if ( !isset( $this->users[$user_id] ) ) {
			$this->users[$user_id] = $user_id;
		}
	}

	public function before()
	{
		if ( count( $this->users ) <= 0 ) {
			$this->access = array();
			return;
		}

		global $cf_model_access;
		$rows = $cf_model_access->fetch_all(
			array(
				array(
					"AND",
					array(
						array( "user_id", "in", "(?" . str_repeat( ",?", count( $this->users ) - 1 ) . ")", array_values( $this->users ) ),
						array( "is_processed", "=", "?", 1 )
					)
				)
			)
		);

		$this->access = array();
		foreach ( $rows as $row ) {
			$user_id = $cf_model_access->get_value( $row, 'user_id' );
			if ( !isset( $this->access[$user_id] ) ) {
				$this->access[$user_id] = array();
			}
			$this->access[$user_id][] = $row;
		}
	}

	public function after()
	{
		global $cf_model_access, $cf_model_number;

		$cf_model_access->update(
			array(
				"is_processed" => 1
			),
			array_map( function ( $d ) {
				return array(
					"AND",
					array(
						array( "user_id", "LIKE", "?", $d[0] ),
						array( "post_id", "in", "(?" . str_repeat( ",?", count( $d[1] ) - 1 ) . ")", $d[1] ),
						array( "is_processed", "=", "?", 0 )
					)
				);
			}, $this->ulist ),
			"OR"
		);

		$not_exist = $this->list;
		$exist = array_map( function ( $d ) use ( $cf_model_number, &$not_exist ) {
			$p1 = $cf_model_number->get_value( $d, "post_id1" ) - 0;
			$p2 = $cf_model_number->get_value( $d, "post_id2" ) - 0;
			$k = $this->keys[$p1][$p2];
			$n = $this->list[$k][2];
			unset( $not_exist[$k] );
			$this->add[$n][$k] = $k;
			return array( $p1, $p2, $n );
		}, $cf_model_number->fetch_all(
			array_map( function ( $d ) {
				return array(
					"AND",
					array(
						array( "post_id1", "=", "?", $d[0] ),
						array( "post_id2", "=", "?", $d[1] )
					)
				);
			}, $this->list ),
			null, null, null, null,
			"OR"
		) );

		if ( count( $not_exist ) > 0 ) {
			$cf_model_number->insert_all(
				array(
					"post_id1",
					"post_id2",
					"number"
				),
				array_map( function ( $d ) {
					return array( $d[0], $d[1], $d[2] );
				}, $not_exist )
			);
		}

		if ( count( $exist ) > 0 ) {
			foreach ( $this->add as $n => $keys ) {
				$keys = array_filter( $keys, function ( $k ) {
					return false !== $k;
				} );
				if ( count( $keys ) > 0 ) {
					$cf_model_number->update(
						array(
							"number" => array( "number + $n" )
						),
						array_map( function ( $k ) {
							$d = $this->list[$k];
							return array(
								"AND",
								array(
									array( "post_id1", "=", "?", $d[0] ),
									array( "post_id2", "=", "?", $d[1] )
								)
							);
						}, $keys ),
						"OR"
					);
				}
			}
		}
	}
}

$GLOBALS['cf_number'] = CollaborativeFiltering_Number::get_instance();
