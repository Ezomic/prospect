export type CompanyStatus = 'new' | 'sent' | 'replied' | 'bounced' | 'closed';

export type CompanyStatusOption = {
    value: CompanyStatus;
    label: string;
};

export type Company = {
    id: number;
    name: string;
    website: string | null;
    email: string | null;
    contact_name: string | null;
    city: string | null;
    kvk_number: string | null;
    industry: string | null;
    status: CompanyStatus;
    notes: string | null;
    source: string | null;
    contact_role: string | null;
    linkedin_url: string | null;
    lead_score: number | null;
    first_contact_channel: string | null;
    language: 'nl' | 'de' | 'en';
    do_not_contact: boolean;
    do_not_contact_at: string | null;
    do_not_contact_reason: string | null;
    replied_at: string | null;
    bounced_at: string | null;
    follow_up_at: string | null;
    last_contact_at?: string | null;
};

export type CompanySort = 'name' | 'status' | 'lead_score' | 'last_contact';

export type InboundMessageKind = 'reply' | 'bounce';

export type InboundMessage = {
    id: number;
    company_id: number;
    kind: InboundMessageKind;
    from: string;
    subject: string | null;
    body: string | null;
    received_at: string;
};

export type TimelineEntry = {
    at: string;
    kind: string;
    title: string;
    detail: string | null;
    letter_id: number | null;
    interaction_id: number | null;
};
