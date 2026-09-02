export type User = {
    id: number;
    name: string;
    username: string;
    email: string;
    bio: string | null;
    avatar_url: string | null;
    email_verified_at: string | null;
    created_at: string;
    updated_at: string;
    [key: string]: unknown; // This allows for additional properties...
};

export type Auth = {
    user: User | null;
};
