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
    do_not_contact: boolean;
    replied_at: string | null;
    bounced_at: string | null;
    follow_up_at: string | null;
};
