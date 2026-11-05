<?php

namespace App\Exceptions;

use Exception;

/**
 * Thrown when the Shopify Admin API rejects or fails a request. Rendered as a 502 (bad gateway) JSON response.
 */
class ShopifyApiException extends Exception
{
    /** @var mixed */
    public $details;

    public function __construct(string $message = 'Shopify request failed', $details = null)
    {
        parent::__construct($message);
        $this->details = $details;
    }

    public function render()
    {
        return response()->json(['error' => $this->getMessage(), 'details' => $this->details], 502);
    }
}
