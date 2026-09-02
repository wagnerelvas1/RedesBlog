export type CommunityRole = 'member' | 'admin';

export type PostSort = 'hot' | 'new' | 'top' | 'controversial';
export type TopRange = 'day' | 'week' | 'month' | 'year' | 'all';
export type CommentSort = 'best' | 'new' | 'top' | 'old' | 'controversial';

export type Author = {
    id: number;
    name: string;
    username: string;
    avatar_url: string | null;
} | null;

export type Attachment = {
    id: number;
    url: string;
    original_name: string;
    mime_type: string;
    width: number | null;
    height: number | null;
    position: number;
};

export type CommunitySummary = {
    id: number;
    name: string;
    title: string;
    avatar_url: string | null;
    members_count: number;
};

export type Community = CommunitySummary & {
    description: string | null;
    rules: string | null;
    banner_url: string | null;
    posts_count: number;
    created_at: string;
    creator: Author;
};

export type Membership = {
    is_member: boolean;
    is_admin: boolean;
    is_creator: boolean;
    is_banned: boolean;
    role: CommunityRole | null;
};

export type CommunityPermissions = {
    can_update: boolean;
    can_manage_members: boolean;
    can_manage_admins: boolean;
    can_delete: boolean;
    can_post: boolean;
};

export type Member = {
    id: number;
    name: string;
    username: string;
    avatar_url: string | null;
    role: CommunityRole;
    is_creator: boolean;
    banned_at: string | null;
    joined_at: string | null;
};

export type Post = {
    id: number;
    title: string;
    body: string | null;
    slug: string;
    is_pinned: boolean;
    score: number;
    upvotes_count: number;
    downvotes_count: number;
    comments_count: number;
    edited_at: string | null;
    created_at: string;
    author: Author;
    community: CommunitySummary;
    attachments: Attachment[];
    viewer_vote: number;
    is_saved: boolean;
    can_update: boolean;
    can_delete: boolean;
    can_pin: boolean;
};

export type Comment = {
    id: number;
    post_id: number;
    parent_id: number | null;
    body: string;
    depth: number;
    score: number;
    replies_count: number;
    edited_at: string | null;
    created_at: string;
    is_deleted: boolean;
    author: Author;
    attachment: Attachment | null;
    viewer_vote: number;
    can_update: boolean;
    can_delete: boolean;
    replies: Comment[];
    has_more_replies: boolean;
};

export type VoteResult = {
    score: number;
    upvotes_count: number;
    downvotes_count: number;
    viewer_vote: number;
};

export type CursorPage<T> = {
    data: T[];
    next_cursor: string | null;
    prev_cursor: string | null;
};

export type Paginated<T> = {
    data: T[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
};
