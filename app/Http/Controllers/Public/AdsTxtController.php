<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Services\SettingsService;
use Illuminate\Http\Response;

class AdsTxtController extends Controller
{
    public function __construct(private readonly SettingsService $settings) {}

    /**
     * ads.txt declares who is authorised to sell this site's inventory.
     *
     * The contents come from settings because the publisher id is issued by
     * AdSense after approval. An empty file would tell buyers that nobody is
     * authorised, which is worse than having none at all - so this 404s until
     * there is something real to serve.
     */
    public function __invoke(): Response
    {
        $body = trim((string) $this->settings->get('adsense_ads_txt'));

        if ($body === '') {
            $clientId = trim((string) $this->settings->get('adsense_client_id'));

            abort_if($clientId === '', 404);

            // The line AdSense asks every publisher to add, built from the
            // publisher id so the common case needs no manual entry.
            $body = 'google.com, '.ltrim($clientId, 'ca-').', DIRECT, f08c47fec0942fa0';
        }

        return response($body."\n", 200, [
            'Content-Type' => 'text/plain; charset=UTF-8',
        ]);
    }
}
