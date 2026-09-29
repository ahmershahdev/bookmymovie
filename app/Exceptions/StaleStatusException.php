<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * A guarded status change found the row in a different state than expected:
 * another request got there first, or the move is not allowed at all.
 */
class StaleStatusException extends RuntimeException {}
