<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * "Open sollicitatie" is jobseeker language, while the sender is a freelance
     * company pitching for assignments and the app calls these open-aanbod
     * letters throughout. Matched on the surrounding phrase rather than the bare
     * word so an unrelated sentence a user wrote cannot be caught by accident.
     *
     * @var array<int, array{0: string, 1: string}>
     */
    private const REWRITES = [
        ['Open sollicitatie: freelance softwareontwikkeling voor', 'Open aanbod: freelance softwareontwikkeling voor'],
        ['Open sollicitatie - Robbin Thijssen', 'Open aanbod - Robbin Thijssen'],
        ['mijn open sollicitatie als freelance softwareontwikkelaar', 'mijn open aanbod als freelance softwareontwikkelaar'],
    ];

    private const COLUMNS = ['subject', 'body', 'email_subject', 'email_body'];

    public function up(): void
    {
        $this->rewrite(self::REWRITES);
    }

    public function down(): void
    {
        $this->rewrite(array_map(
            fn (array $pair) => [$pair[1], $pair[0]],
            self::REWRITES,
        ));
    }

    /**
     * @param  array<int, array{0: string, 1: string}>  $rewrites
     */
    private function rewrite(array $rewrites): void
    {
        foreach (DB::table('letter_templates')->get() as $row) {
            $values = [];

            foreach (self::COLUMNS as $column) {
                $value = $row->{$column};

                if (! is_string($value)) {
                    continue;
                }

                foreach ($rewrites as [$from, $to]) {
                    $value = str_replace($from, $to, $value);
                }

                $values[$column] = $value;
            }

            if ($values !== []) {
                DB::table('letter_templates')->where('id', $row->id)->update($values);
            }
        }
    }
};
