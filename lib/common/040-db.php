<?php
namespace CollaborativeFilteringDatabase;

if ( !defined( 'COLLABORATIVE_FILTERING_PLUGIN' ) )
	exit;

class CollaborativeFiltering_Database extends \CollaborativeFilteringBase\CollaborativeFiltering_Base_Class
{

	const PREFIX = 'cf_';

	private static $_instance = null;

	private $table_defines;
	private $updated;
	private $version;

	public function __construct()
	{
		$this->table_defines = array();
		$this->updated = "";
		$this->version = false;

		add_action( 'init', array( $this, 'db_update' ) );
		add_action( 'switch_blog', array( $this, 'switch_blog' ) );
	}

	public static function get_instance()
	{
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new CollaborativeFiltering_Database();
		}
		return self::$_instance;
	}

	public function add_table( $signature, $table_name, $fields, $index = array(), $default = array() )
	{
		if ( array_key_exists( $signature, $this->table_defines ) ) {
			$this->log( "db define error [duplicated signature]" );
			@header( 'HTTP/1.1 500 Internal Server Error' );
			exit;
		}
		$this->table_defines[$signature] = array(
			'signature' => $signature,
			'table' => $table_name,
			'field' => $fields,
			'_index' => $index,
			'default' => $default
		);
	}

	public function get_tables()
	{
		$ret = array();
		foreach ( $this->table_defines as $table ) {
			$ret[] = array(
				"signature" => $table["signature"],
				"name" => $this->get_table( $table["signature"] )
			);
		}
		return $ret;
	}

	public function get_field_defines( $signature, $default = true )
	{
		$this->check_index( $signature );
		if ( !$default )
			return $this->table_defines[$signature]["field"];

		$ret = $this->table_defines[$signature]["field"];
		$ret["uuid"] = array( "uuid", "VARCHAR(32)", "NOT NULL" );
		$ret["created_at"] = array( "created_at", "DATETIME", "NOT NULL" );
		$ret["created_by"] = array( "created_by", "VARCHAR(32)", "NOT NULL" );
		$ret["updated_at"] = array( "updated_at", "DATETIME", "NOT NULL" );
		$ret["updated_by"] = array( "updated_by", "VARCHAR(32)", "NOT NULL" );
		$ret["deleted_at"] = array( "deleted_at", "DATETIME", "NULL" );
		$ret["deleted_by"] = array( "deleted_by", "VARCHAR(32)", "NULL" );
		return $ret;
	}

	private function check_index( $signature, $exit = true )
	{
		if ( !isset( $this->table_defines[$signature] ) ) {
			if ( $exit ) {
				$this->log( "not exists signature [" . $signature . "]" );
				@header( 'HTTP/1.1 500 Internal Server Error' );
				exit;
			}
			return false;
		}
		return true;
	}

	private function get_db( $signature )
	{
		global $wpdb;
		return $wpdb;
	}

	private function get_table_name( $name )
	{
		global $table_prefix;
		return $table_prefix . self::PREFIX . $name;
	}

	public function get_table( $signature, $exit = true )
	{
		if ( !$this->check_index( $signature, $exit ) ) {
			return $signature;
		}

		if ( !isset( $this->table_defines[$signature]['name'] ) ) {
			$this->table_defines[$signature]['name'] = $this->get_table_name( $this->table_defines[$signature]['table'] );
		}
		return $this->table_defines[$signature]['name'];
	}

	public function default_fields()
	{
		return array(
			'uuid',
			'created_at', 'created_by',
			'updated_at', 'updated_by',
			'deleted_at', 'deleted_by'
		);
	}

	private function is_default_field( $field )
	{
		return in_array( $field, $this->default_fields() );
	}

	public function get_field_name( $signature, $field, $prefix = false, $table = false )
	{
		$this->check_index( $signature );

		if ( $prefix )
			$prefix = $signature;
		else $prefix = '';
		if ( $table )
			$prefix = $this->get_table( $signature ) . "." . $prefix;

		if ( $this->is_default_field( $field ) ) {
			return $prefix . $field;
		}
		return $prefix . $this->table_defines[$signature]['field'][$field][0];
	}

	public function field( $signature, $field, $prefix = false )
	{
		$this->check_index( $signature );

		if ( $this->is_default_field( $field ) ) {
			return $this->get_table( $signature ) . '.' . $field . ' as ' . $this->get_field_name( $signature, $field, $prefix );
		}
		return $this->get_table( $signature ) . '.' . $this->table_defines[$signature]['field'][$field][0] . ' as ' . $this->get_field_name( $signature, $field, $prefix );
	}

	public function all_field( $signature, $prefix = true )
	{
		$this->check_index( $signature );

		$ret = '';
		foreach ( $this->table_defines[$signature]['field'] as $key => $value ) {
			if ( $ret )
				$ret .= "," . $this->field( $signature, $key, $prefix );
			else $ret = $this->field( $signature, $key, $prefix );
		}
		foreach ( $this->default_fields() as $value ) {
			if ( $ret )
				$ret .= "," . $this->field( $signature, $value, $prefix );
			else $ret = $this->field( $signature, $value, $prefix );
		}
		return $ret;
	}

	public function get_value( $data, $signature, $field, $prefix = false )
	{
		$this->check_index( $signature );

		$tmp = $this->get_field_name( $signature, $field, $prefix );
		return $data->$tmp;
	}


	private function build_sql( $db, $sql, $bind, $signature )
	{
		$this->check_index( $signature );

		if ( $bind === NULL )
			$bind = $this->init_bind();

		for ( $i = 0, $n = count( $bind['bind'] ); $i < $n; $i++ ) {
			$type = $bind['type'][$i];
			if ( $type == 's' )
				$type = '%s';
			elseif ( $type == 'i' )
				$type = '%d';
			elseif ( $type == 'd' )
				$type = '%f';
			$sql = preg_replace( '/\?/', $type, $sql, 1 );
		}

		if ( count( $bind['bind'] ) <= 0 ) {
			array_push( $bind['bind'], null );
		}
		array_unshift( $bind['bind'], $sql );
		$sql = @call_user_func_array( array( $db, 'prepare' ), $bind['bind'] );

		return $sql;
	}

	private function check_db_error( $db, $file, $line, $sql, $bind )
	{
		if ( $db->last_error ) {
			$log = "DB Error\n";
			$log .= "FILE:" . $file . "\n";
			$log .= "LINE:" . $line . "\n";
			$log .= $db->last_error . "\n";
			$log .= $sql . "\n";
			if ( $bind )
				$log .= json_encode( $bind );
			$this->log( $log );

			@header( 'HTTP/1.1 500 Internal Server Error' );
			exit;
		}
	}

	public function execute( $sql, $bind, $signature, $file, $line )
	{
		$db = $this->get_db( $signature );
		$sql = $this->build_sql( $db, $sql, $bind, $signature );

		if ( preg_match( '/^\s*(begin|commit)\s*/i', $sql ) ) {
			$db->query( $sql );
			$ret = true;
		} else {
			$ret = $db->query( $sql );
		}

		$this->check_db_error( $db, $file, $line, $sql, $bind );
		return $ret;
	}

	public function fetch( $sql, $bind, $signature, $file, $line )
	{
		$db = $this->get_db( $signature );
		$sql = $this->build_sql( $db, $sql, $bind, $signature );

		$result = $db->get_results( $sql );

		$this->check_db_error( $db, $file, $line, $sql, $bind );

		if ( count( $result ) > 0 )
			return $result[0];
		return null;
	}

	public function fetch_all( $sql, $bind, $signature, $file, $line )
	{
		$db = $this->get_db( $signature );
		$sql = $this->build_sql( $db, $sql, $bind, $signature );

		$result = $db->get_results( $sql );

		$this->check_db_error( $db, $file, $line, $sql, $bind );

		$ret = array();
		$num = 0;
		if ( $result ) {
			foreach ( $result as $row ) {
				$ret += array( $num => $row );
				$num++;
			}
		}
		return $ret;
	}

	public function init_bind( $type = NULL, $val = NULL )
	{
		$ret = array( "type" => "", "bind" => array() );
		if ( is_array( $type ) && is_array( $val ) ) {
			$ret = $this->add_binds( $ret, $type, $val );
		} elseif ( !is_null( $type ) && !is_null( $val ) ) {
			$ret = $this->add_bind( $ret, $type, $val );
		}
		return $ret;
	}

	public function add_bind( $bind, $type, $val )
	{
		$val = @htmlspecialchars_decode( $val );
		$bind['type'] .= $type;
		array_push( $bind['bind'], $val );
		return $bind;
	}

	public function add_binds( $bind, $type, $val )
	{
		$c1 = count( $type );
		$c2 = count( $val );
		$c = $c1 > $c2 ? $c2 : $c1;
		for ( $i = 0; $i < $c; $i++ ) {
			$value = @htmlspecialchars_decode( $val[$i] );
			$bind['type'] .= $type[$i];
			array_push( $bind['bind'], $value );
		}
		return $bind;
	}


	private function get_checked_field( $table, $check )
	{
		if ( empty( $check ) )
			return "";
		if ( !isset( $table['field'] ) )
			return "";
		if ( !is_array( $table['field'] ) )
			return "";

		if ( isset( $table['field'][$check] ) ) {
			if ( isset( $table['field'][$check][0] ) ) {
				return $table['field'][$check][0];
			}
		} else {
			foreach ( $table['field'] as $field ) {
				if ( $field == $check ) {
					return $check;
				}
			}
		}
		if ( in_array( $check, $this->default_fields() ) && $check !== 'uuid' ) {
			return $check;
		}
		return "";
	}

	public function db_update()
	{
		if ( $this->updated )
			return;

		$version = $this->get_version();
		if ( version_compare( COLLABORATIVE_FILTERING_TABLE_VERSION, $version ) <= 0 )
			return;

		global $cf_user;

		set_time_limit( 60 * 5 );
		$this->update_version();

		$char = defined( "DB_CHARSET" ) ? DB_CHARSET : "utf8";
		require_once ABSPATH . "wp-admin" . DIRECTORY_SEPARATOR . "includes" . DIRECTORY_SEPARATOR . "upgrade.php";

		$for_update = array();
		foreach ( $this->table_defines as $signature => $data ) {
			$table = $this->get_table( $signature );

			$sql = "CREATE TABLE " . $table . " (";
			$sql .= "uuid varchar(32) NOT NULL,\n";
			foreach ( $data['field'] as $key => $value ) {
				$sql .= $value[0] . " " . strtolower( $value[1] ) . " " . $value[2] . ",\n";
			}
			$sql .= <<<EOS
				created_at datetime NOT NULL,
				created_by varchar(32) NOT NULL,
				updated_at datetime NOT NULL,
				updated_by varchar(32) NOT NULL,
				deleted_at datetime NULL,
				deleted_by varchar(32) NULL,
				PRIMARY KEY  (uuid)
EOS;
			if ( isset( $data['_index'] ) && is_array( $data['_index'] ) && count( $data['_index'] ) > 0 ) {
				foreach ( $data['_index'] as $index ) {
					if ( is_array( $index ) ) {
						if ( !isset( $index["key"] ) ) {
							$index_key = "";
							$index_fields = false;
							foreach ( $index as $f ) {
								$f = $this->get_checked_field( $data, $f );
								if ( empty( $f ) )
									continue;
								if ( !$index_fields ) {
									$index_key = $f;
									$index_fields = $f;
								} else {
									$index_key .= "_" . $f;
									$index_fields .= ", " . $f;
								}
							}
						} else {
							if ( !isset( $index["field"] ) )
								continue;
							$index_key = $index["key"];
							if ( is_array( $index["field"] ) ) {
								$index_fields = false;
								foreach ( $index["field"] as $f ) {
									$f = $this->get_checked_field( $data, $f );
									if ( empty( $f ) )
										continue;
									if ( !$index_fields )
										$index_fields = $f;
									else $index_fields .= ", " . $f;
								}
							} else {
								$index_fields = $this->get_checked_field( $data, $index["field"] );
							}
							if ( empty( $index_fields ) )
								continue;
						}
						$sql .= ",\nINDEX index_key_" . $index_key . " (" . $index_fields . ")";
					} else {
						$index_field = $this->get_checked_field( $data, $index );
						if ( empty( $index_field ) )
							continue;
						$sql .= ",\nINDEX index_key_" . $index_field . " (" . $index_field . ")";
					}
				}
				$sql .= "\n";
			}

			$sql .= <<<EOS
			) ENGINE = InnoDB DEFAULT CHARSET = {$char};
EOS;
			$results = dbDelta( $sql );
			if ( $results ) {
				$tmp = "";
				foreach ( $results as $result ) {
					if ( !$result )
						continue;
					if ( $tmp )
						$tmp .= "<br>" . $result;
					else $tmp = $result;
				}
				if ( $tmp ) {
					$for_update[] = $tmp;
				}
			}

			if ( isset( $data['default'] ) && is_array( $data['default'] ) && count( $data['default'] ) > 0 ) {
				$sql = "SELECT * FROM " . $table . " ";
				$sql .= "WHERE deleted_at IS NULL ";
				$sql .= "LIMIT 1 ";
				if ( !$this->fetch( $sql, NULL, $signature, __FILE__, __LINE__ ) ) {
					$sql = false;
					foreach ( $data['field'] as $key => $value ) {
						if ( !$sql ) {
							$sql = "INSERT IGNORE INTO " . $table . " (uuid";
						}
						$sql .= "," . $value[0];
					}
					$sql .= ",created_at,created_by,updated_at,updated_by) VALUES ";
					$values = false;
					$bind = $this->init_bind();
					foreach ( $data['default'] as $default ) {
						if ( !is_array( $default ) || count( $default ) != count( $data['field'] ) )
							continue;

						$v = false;
						$bind = $this->add_bind( $bind, "s", _uuid() );
						foreach ( $default as $d ) {
							if ( !$v ) {
								$v = "(?,?";
							} else {
								$v .= ",?";
							}
							$bind = $this->add_bind( $bind, $d[0], $d[1] );
						}
						$v .= ",NOW(),?,NOW(),?)";
						$bind = $this->add_bind( $bind, "s", $cf_user->user_name );
						$bind = $this->add_bind( $bind, "s", $cf_user->user_name );

						if ( !$values ) {
							$values = $v;
						} else {
							$values .= "," . $v;
						}
					}
					$sql .= $values;
					$this->execute( $sql, $bind, $signature, __FILE__, __LINE__ );
				}
			}
		}

		$this->updated = implode( "<br>", $for_update );
		$this->message( $this->updated );
	}

	public function switch_blog()
	{
		foreach ( $this->table_defines as $signature => $data ) {
			unset( $this->table_defines[$signature]['name'] );
		}
	}

	public function get_version()
	{
		if ( !$this->version )
			$this->version = get_option( "cf_table_version", "0.0.0.0.0" );
		return $this->version;
	}

	private function update_version()
	{
		update_option( "cf_table_version", COLLABORATIVE_FILTERING_TABLE_VERSION );
		$this->version = COLLABORATIVE_FILTERING_TABLE_VERSION;
	}

	public function init_version()
	{
		update_option( "cf_table_version", "0" );
		$this->version = COLLABORATIVE_FILTERING_TABLE_VERSION;
	}

	public function uninstall()
	{
		foreach ( $this->table_defines as $signature => $data ) {
			$table = $this->get_table( $signature );
			$sql = "DROP TABLE IF EXISTS $table";
			$this->execute( $sql, null, $signature, __FILE__, __LINE__ );
		}
		delete_option( "cf_table_version" );
	}
}

$GLOBALS['cf_db'] = CollaborativeFiltering_Database::get_instance();
