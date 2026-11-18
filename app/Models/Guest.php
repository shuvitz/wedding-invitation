<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Guest extends Model
{
    /** @use HasFactory<\Database\Factories\GuestFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'phone',
        'notes',
    ];

    /**
     * The guest's personal link to the invitation (the public site's
     * homepage), greeting them by name via the cover block's `?to=`.
     */
    public function invitationUrl(): string
    {
        return rtrim((string) config('app.frontend_url'), '/').'/?'.http_build_query(['to' => $this->name]);
    }

    /**
     * A wa.me link that opens a chat with the invitation message filled in.
     * Without a phone number WhatsApp asks which contact to send it to.
     */
    public function whatsappUrl(): string
    {
        // Digits only, and a local "08…" number becomes "628…" (wa.me needs the country code).
        // shortcut: assumes Indonesian numbers for the leading-0 case; foreign guests need a "+<code>" number.
        $phone = preg_replace('/^0/', '62', preg_replace('/\D/', '', (string) $this->phone));

        $message = "Kepada Yth. {$this->name},\n\n"
            ."Tanpa mengurangi rasa hormat, kami bermaksud mengundang Bapak/Ibu/Saudara/i untuk hadir di acara pernikahan kami.\n\n"
            ."Info lengkap acara dapat dilihat di:\n{$this->invitationUrl()}\n\n"
            .'Merupakan suatu kehormatan bagi kami apabila Bapak/Ibu/Saudara/i berkenan hadir. Terima kasih.';

        return "https://wa.me/{$phone}?".http_build_query(['text' => $message], '', '&', PHP_QUERY_RFC3986);
    }
}
