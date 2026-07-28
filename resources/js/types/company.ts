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
    replied_at: string | null;
    bounced_at: string | null;
    follow_up_at: string | null;
};
