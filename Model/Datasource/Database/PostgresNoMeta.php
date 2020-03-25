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
     * @inheritdoc
     */
    public function resultSet($results)
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
     * @inheritdoc
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

    /**
     * Decorates the parent method; if the $value is a string, escape it
     * according to https://www.postgresql.org/docs/9.6/static/sql-syntax-lexical.html#SQL-SYNTAX-STRINGS-ESCAPE
     *
     * @inheritDoc
     */
    public function value($data, $column = null, $null = true) {
        $value = parent::value($data, $column, $null);

        if (!is_string($value)) {
            return $value;
        }
        if (!isset($value[0])) {
            return $value;
        }

        # This special construct doesn't require further escaping and would break otherwise
        if ($value === "'CURRENT_TIMESTAMP'") {
            return $value;
        }

        # If it starts with a single quote we assume it also ends with one
        # and thus directly jump to the conclusion we can use our
        # special postgres escape method
        if ($value[0] !== "'") {
            return $value;
        }

        return 'E' . str_replace('\\', '\\\\', $value);
    }

    /**
     * Converts the ID to int if it is_numeric
     *
     * @inheritdoc
     * @return int|string
     */
    public function lastInsertId($source = null, $field = 'id')
    {
        $id = parent::lastInsertId($source, $field);

        return is_numeric($id) ? (int) $id : $id;
    }
}
