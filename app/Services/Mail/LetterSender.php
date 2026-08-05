<?php

namespace App\Services\Mail;

use App\Enums\CompanyStatus;
use App\Enums\LetterStatus;
use App\Mail\OutreachMail;
use App\Models\Letter;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Symfony\Component\Mime\Email;
use Webklex\PHPIMAP\ClientManager;
use Webklex\PHPIMAP\Folder;

/**
 * Sends an open-aanbod letter as an email from the configured outreach
 * account, with the letter PDF and the user's CV attached. After sending it
 * marks the letter and company as sent, and best-effort appends the message
 * to the IMAP Sent folder.
 */
class LetterSender
{
    public function send(Letter $letter, User $user): void
    {
        if ($letter->sent_at !== null) {
            throw new RuntimeException('This letter has already been sent.');
        }

        if ($letter->company->email === null) {
            throw new RuntimeException('The company has no email address.');
        }

        if ($user->cv_path === null || ! Storage::disk('local')->exists($user->cv_path)) {
            throw new RuntimeException('No CV is available to attach.');
        }

        $pdf = Pdf::loadView('pdf.letter', ['letter' => $letter])->output();
        $cv = Storage::disk('local')->get($user->cv_path);

        $captured = null;

        $mail = (new OutreachMail($letter, $pdf, (string) $cv, $user->cv_original_name ?? 'cv.pdf'))
            ->withSymfonyMessage(function (Email $message) use (&$captured) {
                $captured = $message;
            });

        Mail::to($letter->company->email)->send($mail);

        $letter->forceFill([
            'status' => LetterStatus::Sent,
            'sent_at' => now(),
        ])->save();

        // Only ever advance a fresh company. A follow-up letter to a company
        // that already replied, bounced or was closed must not discard that
        // outcome, not least because ProcessIncomingMail keys off Sent.
        if ($letter->company->status === CompanyStatus::New) {
            $letter->company->update(['status' => CompanyStatus::Sent]);
        }

        if ($captured !== null) {
            $this->appendToSentFolder($captured->toString());
        }
    }

    private function appendToSentFolder(string $rawMessage): void
    {
        $config = config('services.outreach_imap');

        if (! is_array($config) || empty($config['host'])) {
            return;
        }

        try {
            $client = (new ClientManager)->make([
                'host' => $config['host'],
                'port' => $config['port'] ?? null,
                'encryption' => $config['encryption'] ?? null,
                'validate_cert' => true,
                'username' => $config['username'] ?? null,
                'password' => $config['password'] ?? null,
                'timeout' => 30,
            ]);
            $client->connect();

            $sentFolder = null;
            foreach ($client->getFolders(false) as $folder) {
                if (! $folder instanceof Folder) {
                    continue;
                }

                if (str_contains(strtolower($folder->name), 'sent')) {
                    $sentFolder = $folder;
                    break;
                }
            }

            // appendMessage lives on the Folder, not the Client.
            $sentFolder?->appendMessage($rawMessage, ['Seen']);
        } catch (\Throwable) {
            // Best-effort: a successful send must not fail because the IMAP
            // append did not work.
        }
    }
}
