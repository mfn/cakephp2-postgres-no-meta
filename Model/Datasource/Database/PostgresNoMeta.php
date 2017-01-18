<?php

App::uses('Postgres', 'Model/Datasource/Database');

/**
 * A Postgres driver which does not use getMetaData() to infer types
 *
 * This cuts down latency because pdo_pgsql internally makes additional
 * queries to the database for every such call.
 */
class PostgresNoMeta extends \Postgres
{
    /**
     * Deliberately do nothing in here; not required because of our
     * own fetchResult.
     *
     * This avoid calling getColumnMeta which is expensive in Postgres
     *
     * @param array $results
     * @see fetchResult
     * @override
     */
    public function resultSet(&$results)
    {
        # Deliberately don't do anything in here
    }

    /**
     * Don't use any kind of meta data, simply fetch columns via
     * FETCH_ASSOC and return the values.
     *
     * Note: compared to the parent, we don't have:
     * - dedicated logic to detect arbitrary booleans
     * - no support for `binary` and `bytea`-types
     *
     * @return array|bool
     * @override
     */
    public function fetchResult()
    {
        if ($row = $this->_result->fetch(PDO::FETCH_ASSOC)) {
            $resultRow = [];
            foreach ($row as $column => $value) {
                if (false === strpos($column, '__')) {
                    $resultRow[0][$column] = $value;
                    continue;
                }

                list($table, $name) = explode('__', $column);
                $resultRow[$table][$name] = $value;
            }

            return $resultRow;
        }
        $this->_result->closeCursor();

        return false;
    }
}
