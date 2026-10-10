<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * Violação de regra de negócio do domínio de convidado
 * (vínculos, RSVP, representação) — PRD §2.4 e §2.5.
 */
class GuestDomainException extends RuntimeException {}
