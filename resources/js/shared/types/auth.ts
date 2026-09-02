export type Branch = {
    id: number;
    name: string;
    code: string;
    currency?: string;
};

export type Role = {
    id: number;
    name: string;
};

export type User = {
    id: number;
    name: string;
    username: string;
    email: string | null;
    avatar?: string;
    branch_id?: number | null;
    branch?: Branch | null;
    roles?: Role[];
    permissions?: string[];
    email_verified_at: string | null;
    two_factor_enabled?: boolean;
    is_super_admin?: boolean;
    is_active?: boolean;
    created_at: string;
    updated_at: string;
    [key: string]: unknown;
};

export type CustomerUser = {
    id: number;
    name: string | null;
    phone: string;
    needs_password_setup?: boolean;
};

export type Auth = {
    user: User | null;
    customer: CustomerUser | null;
    permissions: string[];
    dashboardUrl?: string | null;
};

export type Passkey = {
    id: number;
    name: string;
    authenticator: string | null;
    created_at_diff: string;
    last_used_at_diff: string | null;
};

export type TwoFactorSetupData = {
    svg: string;
    url: string;
};

export type TwoFactorSecretKey = {
    secretKey: string;
};
