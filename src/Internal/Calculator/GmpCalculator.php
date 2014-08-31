<?php

namespace Brick\Math\Internal\Calculator;

use Brick\Math\Internal\Calculator;

/**
 * Calculator implementation built around the GMP library.
 *
 * @internal
 */
class GmpCalculator extends Calculator
{
    /**
     * {@inheritdoc}
     */
    public function add($a, $b)
    {
        return gmp_strval(gmp_add($a, $b));
    }

    /**
     * {@inheritdoc}
     */
    public function sub($a, $b)
    {
        return gmp_strval(gmp_sub($a, $b));
    }

    /**
     * {@inheritdoc}
     */
    public function mul($a, $b)
    {
        return gmp_strval(gmp_mul($a, $b));
    }

    /**
     * {@inheritdoc}
     */
    public function div($a, $b)
    {
        list ($q, $r) = gmp_div_qr($a, $b);

        $q = gmp_strval($q);
        $r = gmp_strval($r);

        return [$q, $r];
    }

    /**
     * {@inheritdoc}
     */
    public function pow($a, $e)
    {
        return gmp_strval(gmp_pow($a, $e));
    }
}
