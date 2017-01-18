# CakePHP2 - Postgres driver without getColumnMeta support

The default CakePHP 2.x Postgres driver uses `getColumnMeta` to infer
column types from the server. Although the PHP part has been optimized
in recent years, it still incurs an overhead to hit the Postgres
database on every call with a query like `SELECT RELNAME FROM PG_CLASS WHERE OID=...`

Thus, this implementation is born which foregoes any use of the meta
data and simply uses `PDO::FETCH_ASSOC`.

# Installation

1. Add the line `"mfn/cakephp2-postgres-no-meta": "^0.0.1"` to your `app/composer.json`
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

# Further reference
- https://github.com/cakephp/cakephp/issues/6036
- https://github.com/php/php-src/pull/1534
