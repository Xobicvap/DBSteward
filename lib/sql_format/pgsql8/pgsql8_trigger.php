<?php
/**
 * Manipulate postgresql trigger nodes
 *
 * @copyright 2011 Collaborative Fusion, Inc.
 * @package DBSteward
 * @author Nicholas Kiraly <kiraly.nicholas@gmail.com>
 * @version $Id: pgsql8_trigger.php 2261 2012-01-09 08:37:44Z nkiraly $
 */

class pgsql8_trigger {

  /**
   * Creates and returns SQL for creation of trigger.
   *
   * @return created SQL
   */
  public function get_creation_sql($node_schema, $node_trigger) {
    $event_chunks = preg_split("/[\,\s]+/", $node_trigger['event'], -1, PREG_SPLIT_NO_EMPTY);
    $node_table = dbx::get_table($node_schema, $node_trigger['table']);
    if ( $node_table == null ) {
      throw new exception("Failed to find trigger table " . $node_trigger['table'] . " in schema node " . $node_schema['name']);
    }
    $table_name = pgsql8_diff::get_quoted_name($node_schema['name'], dbsteward::$quote_schema_names) . '.' . pgsql8_diff::get_quoted_name($node_table['name'], dbsteward::$quote_table_names);

    if ( !isset($node_trigger['forEach']) ) {
      throw new exception("trigger forEach must be defined for pg triggers: " . $node_trigger['name']);
    }

    $ddl = "CREATE TRIGGER " . pgsql8_diff::get_quoted_name($node_trigger['name'], dbsteward::$quote_object_names) . "
\t" . $node_trigger['when'] . ' ' . implode(' OR ', $event_chunks) . "
\tON " . $table_name . ' FOR EACH ' . $node_trigger['forEach'] . "
\tEXECUTE PROCEDURE " . $node_trigger['function'] . " ;\n";
    return $ddl;
  }

  /**
   * Creates and returns SQL for dropping the trigger.
   *
   * @return created SQL
   */
  public function get_drop_sql($node_schema, $node_trigger) {
    $node_table = dbx::get_table($node_schema, $node_trigger['table']);
    if ( $node_table == null ) {
      throw new exception("Failed to find trigger table " . $node_trigger['table'] . " in schema node " . $node_schema['name']);
    }
    $table_name = pgsql8_diff::get_quoted_name($node_schema['name'], dbsteward::$quote_schema_names) . '.' . pgsql8_diff::get_quoted_name($node_table['name'], dbsteward::$quote_table_names);
    $ddl = "DROP TRIGGER " . pgsql8_diff::get_quoted_name($node_trigger['name'], dbsteward::$quote_object_names) . " ON " . $table_name . ";\n";
    return $ddl;
  }

  public function equals($trigger_a, $trigger_b) {
    if ( strcasecmp($trigger_a['name'], $trigger_b['name']) != 0 ) {
      return false;
    }
    if ( strcasecmp($trigger_a['table'], $trigger_b['table']) != 0 ) {
      return false;
    }
    if ( strcasecmp($trigger_a['function'], $trigger_b['function']) != 0 ) {
      return false;
    }

    $equals =
      strcasecmp($trigger_a['when'], $trigger_b['when']) == 0
      && strcasecmp($trigger_a['forEach'], $trigger_b['forEach']) == 0
      && strcasecmp($trigger_a['event'], $trigger_b['event']) == 0;

    return $equals;
  }
}

?>
