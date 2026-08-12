import type { Company } from './company';

export type LetterType = 'open_aanbod' | 'follow_up';

export type LetterStatus = 'draft' | 'ready' | 'sending' | 'sent';

export type LetterStatusOption = {
    value: LetterStatus;
    label: string;
};

export type LetterSummary = {
    id: number;
    company_id: number;
    subject: string;
    status: LetterStatus;
    type: LetterType;
    generated_at: string | null;
    queued_at: string | null;
    scheduled_for: string | null;
    sent_at: string | null;
};

export type Letter = LetterSummary & {
    body: string;
    email_subject: string;
    email_body: string;
    send_error: string | null;
    company?: Company;
};
