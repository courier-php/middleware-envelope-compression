<?php
declare(strict_types = 1);

namespace Courier\Middleware;

enum EnvelopeCompressionAlgorithmEnum: int {
  case GZIP = 1;
  case ZLIB = 2;
  case DEFLATE = 3;
}
