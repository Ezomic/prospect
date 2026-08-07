<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Literal rewrites applied to the stored template, in order.
     *
     * The CV claims are matched on the claim itself rather than on the whole
     * shipped sentence, because no CV is attached any more: left in a template
     * the user had partly rewritten, the sentence would be a plain untruth in
     * outgoing mail. The portfolio sentences only land on copy that still
     * matches the default, so hand-written wording is never restyled.
     *
     * @var array<int, array{0: string, 1: string}>
     */
    private const REWRITES = [
        ['Mijn cv vindt u in de bijlage.', 'Voorbeelden van mijn werk vindt u op thijssensoftware.nl.'],
        [', samen met mijn cv', ''],
        [
            'In de brief licht ik toe wat ik voor {{ company }} kan betekenen.',
            'In de brief licht ik toe wat ik voor {{ company }} kan betekenen, en op thijssensoftware.nl vindt u een overzicht van mijn werk.',
        ],
    ];

    public function up(): void
    {
        $this->rewrite(self::REWRITES);
    }

    public function down(): void
    {
        $reverse = [];

        foreach (array_reverse(self::REWRITES) as [$from, $to]) {
            if ($to !== '') {
                $reverse[] = [$to, $from];
            }
        }

        $this->rewrite($reverse);
    }

    /**
     * Done in PHP rather than a SQL replace(): the template is a single row,
     * and building the statement as a string would mean quoting the copy by
     * hand into SQL for no benefit.
     *
     * @param  array<int, array{0: string, 1: string}>  $rewrites
     */
    private function rewrite(array $rewrites): void
    {
        foreach (DB::table('letter_templates')->get(['id', 'body', 'email_body']) as $row) {
            $body = $row->body;
            $emailBody = $row->email_body;

            if (! is_string($body) || ! is_string($emailBody)) {
                continue;
            }

            foreach ($rewrites as [$from, $to]) {
                $body = str_replace($from, $to, $body);
                $emailBody = str_replace($from, $to, $emailBody);
            }

            DB::table('letter_templates')
                ->where('id', $row->id)
                ->update(['body' => $body, 'email_body' => $emailBody]);
        }
    }
};
