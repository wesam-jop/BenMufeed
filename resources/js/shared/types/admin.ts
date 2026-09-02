export type Paginated<T> = {
    data: T[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    from: number | null;
    to: number | null;
    links: Array<{
        url: string | null;
        label: string;
        active: boolean;
    }>;
};

export type FlashMessages = {
    toast?: {
        type: 'success' | 'info' | 'warning' | 'error';
        message: string;
    };
    success?: string;
    error?: string;
    message?: string;
};
