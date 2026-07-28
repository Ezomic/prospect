export type CompanyStatus = 'new' | 'sent' | 'replied' | 'bounced' | 'closed';

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
};
