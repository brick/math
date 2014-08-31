Brick\Math
==========

Provides the `BigInteger` and `BigDecimal` classes to work with arbitrary precision numbers.

This component uses the [GMP](http://php.net/manual/en/book.gmp.php) and [BCMath](http://php.net/manual/en/book.bc.php)
extensions for fast computation when they are available, but can also fall back to a native PHP implementation,
guaranteeing that it will work on any PHP installation. All of this is totally transparent to the developer, as the
component auto-detects the fastest implementation available at runtime.

`BigInteger` and `BigDecimal` are serializable.