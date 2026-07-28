<?php

namespace App\Actions\Letters;

use App\Enums\LetterStatus;
use App\Models\Company;
use App\Models\Letter;

class GenerateLetter
{
    public function handle(Company $company): Letter
    {
        return $company->letters()->create([
            'subject' => $this->subject($company),
            'body' => $this->body($company),
            'status' => LetterStatus::Draft,
            'generated_at' => now(),
        ]);
    }

    private function subject(Company $company): string
    {
        return "Open sollicitatie: freelance softwareontwikkeling voor {$company->name}";
    }

    private function body(Company $company): string
    {
        $greeting = $company->contact_name !== null
            ? "Beste {$company->contact_name}"
            : 'Geachte heer, mevrouw';

        $opening = $company->city !== null
            ? "{$company->name} in {$company->city} viel mij op"
            : "{$company->name} viel mij op";

        $opening .= $company->industry !== null
            ? " binnen de {$company->industry}."
            : '.';

        return <<<LETTER
        {$greeting},

        Mijn naam is Robbin Thijssen, freelance softwareontwikkelaar bij Thijssen Software. {$opening} Graag stel ik mij via deze weg voor.

        Ik bouw moderne webapplicaties met Laravel, Vue en TypeScript, van het eerste ontwerp tot oplevering, hosting en onderhoud. Mijn kracht zit in het zelfstandig oppakken van een vraagstuk en het opleveren van werkende, onderhoudbare software.

        Ik denk dat ik van waarde kan zijn voor het ontwikkelteam van {$company->name}. In een kort gesprek licht ik graag toe wat ik voor u kan betekenen. Mijn cv vindt u in de bijlage.

        Met vriendelijke groet,

        Robbin Thijssen
        Thijssen Software
        LETTER;
    }
}
