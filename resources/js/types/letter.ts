import type { Company } from './company';

export type LetterStatus = 'draft' | 'ready' | 'sent';

export type LetterStatusOption = {
    value: LetterStatus;
    label: string;
};

export type LetterSummary = {
    id: number;
    company_id: number;
    subject: string;
    status: LetterStatus;
    generated_at: string | null;
    sent_at: string | null;
};

export type Letter = LetterSummary & {
    body: string;
    company?: Company;
};
