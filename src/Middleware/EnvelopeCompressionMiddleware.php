<?php
declare(strict_types = 1);

namespace Courier\Middleware;

use Courier\Message\Envelope;
use InvalidArgumentException;
use RuntimeException;

final class EnvelopeCompressionMiddleware implements MiddlewareInterface {
  private EnvelopeCompressionAlgorithmEnum $algorithm;

  private function compress(string $data): string|false {
    return match ($this->algorithm) {
      EnvelopeCompressionAlgorithmEnum::GZIP    => gzencode($data),
      EnvelopeCompressionAlgorithmEnum::ZLIB    => gzcompress($data),
      EnvelopeCompressionAlgorithmEnum::DEFLATE => gzdeflate($data)
    };
  }

  private function decompress(string $data): string|false {
    return match ($this->algorithm) {
      EnvelopeCompressionAlgorithmEnum::GZIP    => gzdecode($data),
      EnvelopeCompressionAlgorithmEnum::ZLIB    => gzuncompress($data),
      EnvelopeCompressionAlgorithmEnum::DEFLATE => gzinflate($data)
    };
  }

  public function __construct(EnvelopeCompressionAlgorithmEnum $algorithm = EnvelopeCompressionAlgorithmEnum::DEFLATE) {
    $this->algorithm = $algorithm;
  }

  public function __invoke(Envelope $envelope, callable $next): Envelope {
    if (str_ends_with($envelope->getContentEncoding(), 'compressed') === false) {
      $compressedBody = $this->compress($envelope->getBody());
      if ($compressedBody !== false) {
        $envelope = $envelope
          ->withContentEncoding(
            sprintf(
              '%s, compressed',
              $envelope->getContentEncoding()
            )
          )
          ->withBody($compressedBody);
      }

      return $next($envelope);
    }

    $decompressedBody = $this->decompress($envelope->getBody());
    if ($decompressedBody === false) {
      throw new RuntimeException('Failed to decompress envelope body');
    }

    $envelope = $envelope
      ->withContentEncoding(
        str_replace(
          ', compressed',
          '',
          $envelope->getContentEncoding()
        )
      )
      ->withBody($decompressedBody);

    return $next($envelope);
  }
}
