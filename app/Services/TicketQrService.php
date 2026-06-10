<?php

namespace App\Services;

use App\Models\ticketBooking;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use chillerlan\QRCode\Output\QRGdImagePNG;
use chillerlan\QRCode\Output\QRMarkupHTML;
use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;
use Illuminate\Support\Str;

class TicketQrService
{
    public function generateTicketNumber(): string
    {
        do {
            $number = 'TKT-'.strtoupper(Str::random(12));
        } while (ticketBooking::query()->where('ticket_number', $number)->exists());

        return $number;
    }

    public function getQrCodeSvg(string $ticketNumber): string
    {
        $renderer = new ImageRenderer(
            new RendererStyle(160),
            new SvgImageBackEnd
        );

        return (new Writer($renderer))->writeString($this->buildPayload($ticketNumber));
    }

    /**
     * QR output suitable for DomPDF (PNG data URI, or HTML fallback).
     *
     * @return array{type: 'img', src: string}|array{type: 'html', markup: string}
     */
    public function getQrCodeForPdf(string $ticketNumber): array
    {
        if (extension_loaded('gd')) {
            $options = new QROptions([
                'outputInterface' => QRGdImagePNG::class,
                'outputBase64' => true,
                'scale' => 6,
                'bgColor' => '#ffffff',
                'imageTransparent' => false,
            ]);

            $src = (new QRCode($options))->render($this->buildPayload($ticketNumber));

            if (is_string($src) && str_starts_with($src, 'data:image/png;base64,')) {
                return ['type' => 'img', 'src' => $src];
            }
        }

        $options = new QROptions([
            'outputInterface' => QRMarkupHTML::class,
            'outputBase64' => false,
            'cssClass' => 'ticket-qr-code',
            'bgColor' => '#ffffff',
        ]);

        return [
            'type' => 'html',
            'markup' => (new QRCode($options))->render($this->buildPayload($ticketNumber)),
        ];
    }

    private function buildPayload(string $ticketNumber): string
    {
        return json_encode([
            'ticket' => $ticketNumber,
            'app' => config('app.name'),
        ], JSON_THROW_ON_ERROR);
    }
}
