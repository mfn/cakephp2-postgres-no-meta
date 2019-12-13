# CakePHP2 - Postgres driver with custom adaptions

This driver was changed in the following ways:

1) The default CakePHP 2.x Postgres driver uses `getColumnMeta` to infer
column types from the server. Although the PHP part has been optimized
in recent years [1], it still incurs an overhead to hit the Postgres
database on every call with a query like `SELECT RELNAME FROM PG_CLASS WHERE OID=...` [2]

Thus, this implementation is born which foregoes any use of the meta
data and simply uses `PDO::FETCH_ASSOC`.

2) There's a problem with special crafted SQL statements which contain the `\`
character [3] which actually isn't CakePHP specific but a problem of the
underlying PDO/PgSQL driver [4].

The method `\Postgres::value()` was overriden to apply the special C-style
escape operation to strings [5].

3) The default PHP/PDO `lastInsertId` always returns a string. This driver is
adapted to return an integer if `is_numeric` returns true on it. This allows
easier integration with codebases using `strict_types=1`.

# Requirements and Installation

1. You need at least CakePHP 2.10.12<br>
   For CakePHP >= 2.0 and < 2.10.12 , you can use version `0.0.2` of this package
1. Add the line `"mfn/cakephp2-postgres-no-meta": "^0.0.5"` to your `app/composer.json`
2. Run `php composer.phar require mfn/cakephp2-postgres-no-meta`
3. Load the plugin in `app/Config/bootstrap.php` with the line
```php
CakePlugin::load('PostgresNoMeta');
```
4. Use the driver in your `app/Config/database.php`: `PostgresNoMeta.Database/PostgresNoMeta` (instead of `Database/Postgres`)
5. Profit!

# Rational

During the switch of a big application from MySQL to Postgres it was discovered
that much overhead was lost on Postgres and it was finally discovered that
these meta queries incur a measurable overhead.

The individual queries are very fast but, depending on your queries, they may
add up until a measurable point.

In our case there were  performance improvement of up to 50% without any
additional changes except activating this class. YMMV.

A little bit later also found problems with the generated SQL statements, which
in special cases were translated from:
```sql
INSERT INTO models(field) VALUES('\'':1');
```
to
```sql
INSERT INTO models(field) VALUES('\''$1');
```
before sent to the server, causing various problems.

# Further reference
- [1] https://github.com/php/php-src/pull/1534
- [2] https://github.com/cakephp/cakephp/issues/6036
- [3] https://github.com/cakephp/cakephp/issues/10373
- [4] https://bugs.php.net/bug.php?id=74220
- [5] https://www.postgresql.org/docs/9.6/static/sql-syntax-lexical.html#SQL-SYNTAX-STRINGS-ESCAPE
